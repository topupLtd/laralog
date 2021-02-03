<?php

namespace Topup\Logger\Http\Middleware;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Topup\Logger\Models\Logger;

class TopupGuzzleLoggerMiddleware
{

    public function log()
    {
        return function (callable $handler) {
            return function (
                RequestInterface $request,
                array $options
            ) use ($handler) {
                $reqHeader = json_encode($request->getHeaders());
                $reqBody = (string)$request->getBody();
                $reqUrl = $request->getUri();
                $reqMethod = $request->getMethod();
                $req = [
                    'header' => $reqHeader,
                    'body' => $reqBody,
                    'url' => $reqUrl,
                    'method' => $reqMethod,
                ];
                $promise = $handler($request, $options);
                return $promise->then(
                    function (ResponseInterface $response) use ($req) {
                        $resBody = $response->getBody();
                        logger(gettype($resBody));
                        $resHeader = json_encode($response->getHeaders());

                        $log = [
                            'req' => $req,
                            'res' => [
                                'header' => $resHeader,
                                'body' => $resBody,
                            ],
                        ];

                        $this->_save($log);

                        return $response->withHeader('x-test', 123);
                    }
                );
            };
        };
    }

    protected function _save($log)
    {
        $logger = new Logger();
        $logger->user_id = auth()->id() ?? null;
        $logger->method = $log['req']['method'];
        $logger->type = 'outbound';
        $logger->url = $log['req']['url'];
        $logger->request_header = $log['req']['header'];
        $logger->response_header = $log['res']['header'];
        $logger->request_body = $log['req']['body'];
        $logger->response_body = $log['res']['body'];
        $logger->ip = \Request::ip();
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
