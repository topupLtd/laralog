<?php

namespace Topup\Logger\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Topup\Logger\Models\Logger;
use Topup\Logger\Support\Redactor;

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

    /**
     * SECURITY: secrets in headers (incl. Set-Cookie), bodies and query strings are masked first
     * (see Redactor).
     */
    public function terminate($request, $response)
    {
        // never log the log viewer: each view would store the page (and the logs on it) as a new log
        if ($request->routeIs('topup.logger', 'topup.logger.*')) {
            return;
        }

        $redactor = new Redactor();
        $responseHeaders = isset($response->headers) ? $response->headers : null;
        $responseBody = method_exists($response, 'getContent') ? $response->getContent() : '';

        $logger = new Logger();
        $logger->user_id = auth()->id() ?? null;
        $logger->method = $request->method();
        $logger->url = $redactor->url($request->fullUrl());
        $logger->request_header = json_encode($redactor->headers($request->headers->all()));
        $logger->response_header = json_encode($redactor->headers($responseHeaders ? $responseHeaders->all() : []));
        $logger->request_body = $redactor->body((string) $request->getContent(), (string) $request->headers->get('Content-Type'));
        $logger->response_body = $redactor->body(
            $responseBody === false ? '' : (string) $responseBody,
            $responseHeaders ? (string) $responseHeaders->get('Content-Type') : ''
        );
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
