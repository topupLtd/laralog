<?php

namespace Topup\Logger\Http\Middleware;

use Exception;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Request;
use Topup\Logger\Models\Logger;

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

                $req = [
                    'header' => $request->getHeaders(),
                    'body' => $request->getBody(),
                    'url' => $request->getUri(),
                    'method' => $request->getMethod(),
                ];

                $this->_log['req'] = $req;

                $promise = $handler($request, $options);
                return $promise->then(
                    function (ResponseInterface $response) {
                        $res = [
                            'header' => $response->getHeaders(),
                            'body' => $response->getBody(),
                        ];
                        $this->_log['res'] = $res;

                        $this->_save();

                        return $response;
                    }
                );
            };
        };
    }

    protected function _save()
    {
        $logger = new Logger();
        $logger->user_id = auth()->id() ?? null;
        $logger->method = $this->_log['req']['method'];
        $logger->type = 'outbound';
        $logger->url = $this->_log['req']['url'];
        $logger->request_header = json_encode($this->_log['req']['header']);
        $logger->response_header = json_encode($this->_log['res']['header']);
        $logger->request_body = (string)$this->_log['req']['body'];
        $logger->response_body = (string)$this->_log['res']['body'];
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
