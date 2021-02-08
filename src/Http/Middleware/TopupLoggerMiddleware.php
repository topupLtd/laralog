<?php

namespace Topup\Logger\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Topup\Logger\Models\Logger;

class TopupLoggerMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        return $next($request);
    }

    public function terminate($request, $response)
    {
        $logger = new Logger();
        $logger->user_id = auth()->id() ?? null;
        $logger->method = $request->method();
        $logger->url = $request->fullUrl();
        $logger->request_header = json_encode($request->header());
        $logger->response_header = $response->headers;
        $logger->request_body = $request->getContent();
        $logger->response_body = $response->getContent();
        $logger->ip = $request->ip();
        $logger->duration = number_format(microtime(true) - LARAVEL_START, 3);
        $logger->request_time = now();

        try {
            $logger->save();
        } catch (\Exception $exception) {
            logger()->error('Could not save API log');
            logger()->error($exception);
        }
    }
}
