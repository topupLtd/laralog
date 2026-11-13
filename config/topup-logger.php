<?php

return [
    'layout' => env('TOPUP_LOGGER_LAYOUT', 'topup-logger::app'),
    'section' => env('TOPUP_LOGGER_CONTENT_SECTION', 'content'),

    // SECURITY: values of headers, body fields and query parameters whose names look secret
    // (password, token, authorization, cookie, api key, otp, pin, card data...) are stored as
    // "[REDACTED]". Add more names here, comma separated, e.g. "national_id,dob".
    'redact_keys' => array_filter(array_map('trim', explode(',', (string) env('TOPUP_LOGGER_REDACT_KEYS', '')))),

    // false stores only the size of request/response bodies, never their content
    'log_bodies' => env('TOPUP_LOGGER_LOG_BODIES', true),

    // bodies longer than this many bytes are cut (after redaction) and marked as truncated; 0 = no limit
    'max_body_bytes' => (int) env('TOPUP_LOGGER_MAX_BODY_BYTES', 0),

    // "php artisan topup-logger:prune" deletes logs older than this many days
    'prune_days' => (int) env('TOPUP_LOGGER_PRUNE_DAYS', 30),
];
