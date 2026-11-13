<?php

namespace Topup\Logger\Support;

/**
 * SECURITY: masks secrets in request/response data before it is written to topup_loggers.
 *
 * A header, body field or query parameter is secret when its name looks like one (password,
 * token, authorization, cookie, api key, OTP, PIN, card data...). Names are compared in lower
 * case with "-", "_", "." and spaces removed, so "Id-Tocken", "tocken_id" and "x-api-key" all
 * match. JSON, form-encoded, XML and plain-text bodies are handled; anything that is not valid
 * UTF-8 (files, images) is replaced by its size. JWTs and "Bearer ..." credentials are masked
 * wherever they appear. Bodies longer than topup-logger.max_body_bytes are cut after redaction.
 */
class Redactor
{
    const MASK = '[REDACTED]';

    /** a name containing one of these is secret */
    const CONTAINS = [
        'password', 'passwd', 'passcode', 'passphrase', 'secret', 'token', 'tocken',
        'authorization', 'authorisation', 'cookie', 'signature', 'apikey', 'accesskey',
        'accesscode', 'privatekey', 'credential', 'session', 'sessid', 'cardnumber', 'cvv', 'cvc',
        'iban', 'accountnumber', 'accountno',
    ];

    /** a name equal to one of these is secret */
    const EXACT = ['pass', 'pwd', 'pin', 'otp', 'pan', 'rib', 'key', 'auth', 'sig', 'sid'];

    /** a name starting or ending with one of these is secret (otp_code, sms_otp, new_pin) */
    const AFFIXES = ['otp', 'pin', 'pwd'];

    /** @var string[] */
    private $extraKeys;

    /** @var bool */
    private $logBodies;

    /** @var int */
    private $maxBodyBytes;

    /**
     * @param string[]|null $extraKeys     more name fragments to treat as secret (default: config)
     * @param bool|null     $logBodies     false keeps only body sizes (default: config)
     * @param int|null      $maxBodyBytes  longer bodies are cut after redaction, 0 = no limit (default: config)
     */
    public function __construct(?array $extraKeys = null, $logBodies = null, $maxBodyBytes = null)
    {
        if ($extraKeys === null) {
            $extraKeys = (array) self::config('topup-logger.redact_keys', []);
        }
        if ($logBodies === null) {
            $logBodies = self::config('topup-logger.log_bodies', true);
        }
        if ($maxBodyBytes === null) {
            $maxBodyBytes = self::config('topup-logger.max_body_bytes', 0);
        }

        $this->extraKeys = array_values(array_filter(array_map([$this, 'normalize'], $extraKeys)));
        $this->logBodies = filter_var($logBodies, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) !== false;
        $this->maxBodyBytes = max(0, (int) $maxBodyBytes);
    }

    public function isSensitiveKey($name)
    {
        $key = $this->normalize($name);
        if ($key === '') {
            return false;
        }

        foreach (array_merge(self::CONTAINS, $this->extraKeys) as $fragment) {
            if (strpos($key, $fragment) !== false) {
                return true;
            }
        }

        if (in_array($key, self::EXACT, true)) {
            return true;
        }

        foreach (self::AFFIXES as $affix) {
            $length = strlen($affix);
            if (strlen($key) > $length && (substr($key, 0, $length) === $affix || substr($key, -$length) === $affix)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array $headers  name => value or name => [values] (PSR-7 / Symfony shape)
     * @return array
     */
    public function headers($headers)
    {
        if (!is_array($headers)) {
            return [];
        }

        $out = [];
        foreach ($headers as $name => $values) {
            $sensitive = is_string($name) && $this->isSensitiveKey($name);
            $list = is_array($values) ? $values : [$values];

            foreach ($list as $index => $value) {
                $value = is_scalar($value) ? (string) $value : '';
                try {
                    $list[$index] = $sensitive ? $this->maskHeaderValue($value) : $this->scrubText($value);
                } catch (\RuntimeException $e) {
                    $list[$index] = self::MASK;
                }
            }

            $out[$name] = is_array($values) ? $list : reset($list);
        }

        return $out;
    }

    /**
     * @param  string $body
     * @param  string $contentType
     * @return string
     */
    public function body($body, $contentType = '')
    {
        // cut only after redacting: a secret split by the cut could no longer be recognised
        return $this->limit($this->redactBody($body, $contentType));
    }

    private function limit($text)
    {
        if ($this->maxBodyBytes === 0 || strlen($text) <= $this->maxBodyBytes) {
            return $text;
        }

        $cut = function_exists('mb_strcut')
            ? mb_strcut($text, 0, $this->maxBodyBytes, 'UTF-8') // never splits a multi-byte character
            : substr($text, 0, $this->maxBodyBytes);

        return $cut . "\n[truncated, " . strlen($text) . ' bytes in total]';
    }

    private function redactBody($body, $contentType)
    {
        $body = (string) $body;
        if ($body === '') {
            return '';
        }

        if (!$this->logBodies) {
            return '[' . strlen($body) . ' bytes, not logged]';
        }

        if (preg_match('//u', $body) !== 1) {
            return '[' . strlen($body) . ' bytes of binary data, not logged]';
        }

        try {
            $json = $this->redactJsonString($body);
            if ($json !== null) {
                return $this->scrubText($json);
            }

            $contentType = strtolower((string) $contentType);
            if (strpos($contentType, 'application/x-www-form-urlencoded') !== false
                || preg_match('/^[\w.%\[\]-]++=[^&\s]*+(?:&[\w.%\[\]-]++=[^&\s]*+)*+$/', $body) === 1) {
                return $this->scrubText($this->redactPairs($body));
            }

            return $this->scrubText($this->redactLooseText($body));
        } catch (\RuntimeException $e) {
            return '[' . strlen($body) . ' bytes, could not be redacted, not logged]';
        }
    }

    /**
     * @param  string $url
     * @return string
     */
    public function url($url)
    {
        $url = (string) $url;

        try {
            return $this->redactUrl($url);
        } catch (\RuntimeException $e) {
            return strtok($url, '?#') . (strpbrk($url, '?#') !== false ? '?' . self::MASK : '');
        }
    }

    private function redactUrl($url)
    {
        // credentials in the authority part: https://user:pass@host
        $url = $this->replace('#^([a-z][a-z0-9+.-]*://)[^/?\#@]*+@#i', '$1' . self::MASK . '@', $url);

        $fragment = '';
        if (($hash = strpos($url, '#')) !== false) {
            $fragment = substr($url, $hash);
            $url = substr($url, 0, $hash);
        }

        if (($question = strpos($url, '?')) !== false) {
            $url = substr($url, 0, $question + 1) . $this->redactPairs(substr($url, $question + 1));
        }

        return $this->scrubText($url . $fragment);
    }

    private function normalize($name)
    {
        return preg_replace('/[^a-z0-9]/', '', strtolower((string) $name));
    }

    private function maskHeaderValue($value)
    {
        if (preg_match('/^(Bearer|Basic|Digest|Token|Negotiate)\s+\S/i', $value, $match)) {
            return $match[1] . ' ' . self::MASK;
        }

        return self::MASK;
    }

    /** JWTs and "Bearer xxx" / "Basic xxx" credentials, wherever they appear */
    private function scrubText($text)
    {
        $text = $this->replace('/\beyJ[A-Za-z0-9_-]{5,}+\.[A-Za-z0-9_-]{5,}+\.[A-Za-z0-9_-]*+/', self::MASK, $text);

        return $this->replace('/\b(Bearer|Basic)\s++(?!\[REDACTED\])[A-Za-z0-9._~+\/=-]{8,}+/i', '$1 ' . self::MASK, $text);
    }

    /** a=1&password=x  ->  a=1&password=[REDACTED] */
    private function redactPairs($pairs)
    {
        $parts = explode('&', $pairs);

        foreach ($parts as $index => $pair) {
            $kv = explode('=', $pair, 2);
            if (count($kv) === 2 && $this->isSensitiveKey(urldecode($kv[0]))) {
                $parts[$index] = $kv[0] . '=' . self::MASK;
            }
        }

        return implode('&', $parts);
    }

    /**
     * @return string|null  the redacted JSON, or null when $text is not a JSON object/array
     */
    private function redactJsonString($text)
    {
        $trimmed = ltrim($text);
        if ($trimmed === '' || ($trimmed[0] !== '{' && $trimmed[0] !== '[')) {
            return null;
        }

        $decoded = json_decode($text, false, 512, JSON_BIGINT_AS_STRING);
        if (json_last_error() !== JSON_ERROR_NONE || !(is_array($decoded) || is_object($decoded))) {
            return null;
        }

        $encoded = json_encode(
            $this->redactValue($decoded),
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION
        );

        return $encoded === false ? null : $encoded;
    }

    private function redactValue($value, $depth = 0)
    {
        if ($depth > 32) {
            return $value;
        }

        if (is_array($value) || is_object($value)) {
            foreach ($value as $key => &$item) {
                $item = (is_string($key) && $this->isSensitiveKey($key))
                    ? self::MASK
                    : $this->redactValue($item, $depth + 1);
            }
            unset($item);

            return $value;
        }

        if (is_string($value)) {
            // JSON sent as a string inside JSON ("payload": "{\"password\": ...}")
            $nested = $this->redactJsonString($value);

            return $nested !== null ? $nested : $value;
        }

        return $value;
    }

    /** XML, SOAP and plain text: <Password>x</Password>, "token": "x", password="x", token=x */
    private function redactLooseText($text)
    {
        $self = $this;
        $mask = self::MASK;

        $text = $this->replace(
            '/<((?:[\w.-]++:)?([\w.-]++))(\s[^>]*+)?>([^<]*+)<\/\1\s*>/',
            function ($m) use ($self, $mask) {
                return $self->isSensitiveKey($m[2])
                    ? '<' . $m[1] . (isset($m[3]) ? $m[3] : '') . '>' . $mask . '</' . $m[1] . '>'
                    : $m[0];
            },
            $text
        );

        $text = $this->replace(
            '/("((?:[^"\\\\]++|\\\\.)*+)"\s*+:\s*+)("(?:[^"\\\\]++|\\\\.)*+"|[^\s,}\]]++)/',
            function ($m) use ($self, $mask) {
                return $self->isSensitiveKey($m[2]) ? $m[1] . '"' . $mask . '"' : $m[0];
            },
            $text
        );

        $text = $this->replace(
            '/\b([A-Za-z][\w.:-]*+)(\s*+=\s*+)("[^"]*+"|\'[^\']*+\')/',
            function ($m) use ($self, $mask) {
                return $self->isSensitiveKey($m[1]) ? $m[1] . $m[2] . $m[3][0] . $mask . $m[3][0] : $m[0];
            },
            $text
        );

        return $this->replace(
            '/\b([A-Za-z][\w.-]*+)(\s*+[=:]\s*+)(?!\[REDACTED\])((?:(?:Bearer|Basic|Digest|Token)\s++)?[^\s&,;"\'<>]++)/i',
            function ($m) use ($self, $mask) {
                return $self->isSensitiveKey($m[1]) ? $m[1] . $m[2] . $mask : $m[0];
            },
            $text
        );
    }

    /**
     * preg_replace / preg_replace_callback that throws instead of returning null when PCRE gives
     * up (backtrack or JIT limits on very large input), so callers can fail closed.
     */
    private function replace($pattern, $replacement, $subject)
    {
        $result = is_callable($replacement)
            ? preg_replace_callback($pattern, $replacement, $subject)
            : preg_replace($pattern, $replacement, $subject);

        if ($result === null) {
            throw new \RuntimeException('Log redaction failed: ' . preg_last_error());
        }

        return $result;
    }

    private static function config($key, $default)
    {
        try {
            return function_exists('config') ? config($key, $default) : $default;
        } catch (\Throwable $e) {
            return $default; // no application container (e.g. used outside Laravel)
        }
    }
}
