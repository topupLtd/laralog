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

6. Enjoy
