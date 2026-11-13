<?php

namespace Topup\Logger\Http\Middleware;

use Exception;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Request;
use Topup\Logger\Models\Logger;
use Topup\Logger\Support\Redactor;

class TopupGuzzleLoggerMiddleware
{
    protected $_log = [];

    /**
     * Guzzle handler to log request & response
     *
     * @return \Closure
     */
    public function log()
    {
        return function (callable $handler) {
            return function (
                RequestInterface $request,
                array $options
            ) use ($handler) {

                $promise = $handler($request, $options);
                return $promise->then(
                    function (ResponseInterface $response) use ($request) {
                        $this->_log = ['req' => $request, 'res' => $response];

                        $this->_save();

                        return $response;
                    }
                );
            };
        };
    }

    /**
     * SECURITY: secrets in headers, bodies and query strings are masked first (see Redactor).
     */
    protected function _save()
    {
        /** @var RequestInterface $request */
        $request = $this->_log['req'];
        /** @var ResponseInterface $response */
        $response = $this->_log['res'];
        $redactor = new Redactor();

        $logger = new Logger();
        $logger->user_id = auth()->id() ?? null;
        $logger->method = $request->getMethod();
        $logger->type = 'outbound';
        $logger->url = $redactor->url((string) $request->getUri());
        $logger->request_header = json_encode($redactor->headers($request->getHeaders()));
        $logger->response_header = json_encode($redactor->headers($response->getHeaders()));
        $logger->request_body = $redactor->body((string) $request->getBody(), $request->getHeaderLine('Content-Type'));
        $logger->response_body = $redactor->body((string) $response->getBody(), $response->getHeaderLine('Content-Type'));
        $logger->ip = Request::ip();
        $logger->duration = number_format(microtime(true) - LARAVEL_START, 3);
        $logger->request_time = now();

        try {
            $logger->save();
        } catch (Exception $exception) {
            logger()->error('Could not save API log');
            logger()->error($exception);
        }
    }

}
