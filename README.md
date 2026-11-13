# Topup Logger

A tiny package to debug request & response. It will log request & response header, user IP, time took to process the
request & sending response. If a logged in user make the request it will log the user ID too.

## Installation

1. Install the package via composer

```bash
composer require topup/laralog
```

## Usage

1. (Optional) Set environment variables to use your own custom layout

```bash
TOPUP_LOGGER_LAYOUT="topup-logger::app"
TOPUP_LOGGER_CONTENT_SECTION=content
```

Both env value will fall back to package default value.

2. Migrate using the command bellow:

```bash
php artisan migrate
```

3. Use middleware `topup-logger` to any route that need to debug

4. Log outbound request with guzzle http

```bash
use GuzzleHttp\Client;
use Topup\Logger\Http\Middleware\TopupGuzzleLoggerMiddleware;

$logger = new TopupGuzzleLoggerMiddleware();
$handlerStack = HandlerStack::create();
$handlerStack->setHandler(new CurlHandler());
$handlerStack->push($logger->log());

$client = new Client(['handler' => $handlerStack]);
```

Or
```bash
$history = Middleware::history($this->_guzzleReqResContainer);
$this->_handlerStack = HandlerStack::create();
$this->_handlerStack->push($history);

$client = new Client(['handler' => $handlerStack]);

(new LoggerController())->saveLog($this->_guzzleReqResContainer);
```

5. Apply middlewares for package routes via .env

```bash
TOPUP_LOGGER_ROUTE_MIDDLEWARE=web,auth,admin
```

Separate multiple middlewares with a comma(,) i.e. auth,admin

Default will fallback to `web`

Empty is allowed if you intend not to use any middleware

```bash
TOPUP_LOGGER_ROUTE_MIDDLEWARE=
```

6. Secrets are redacted before a log is stored

Values of headers, body fields and query parameters whose names look secret (password, token,
authorization, cookie, api key, signature, otp, pin, card data...) are stored as `[REDACTED]`, as are
JWTs and `Bearer ...` credentials anywhere in the data. JSON, form-encoded, XML and plain-text bodies
are handled; binary bodies (files, images) are stored as their size only.

```bash
# more names to redact, comma separated
TOPUP_LOGGER_REDACT_KEYS=national_id,dob
# store only body sizes, never their content
TOPUP_LOGGER_LOG_BODIES=false
```

Protect the log viewer route with your admin middleware (see 5); the default `web` alone lets anyone
open `/topup-logger`.

7. Enjoy
