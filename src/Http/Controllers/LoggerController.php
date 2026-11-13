<?php

namespace Topup\Logger\Http\Controllers;

use App\Http\Controllers\Controller;
use Exception;
use Request;
use Topup\Logger\Models\Logger;
use Topup\Logger\Support\Redactor;

class LoggerController extends Controller
{
    public function index()
    {
        $logs = Logger::latest('request_time')->simplePaginate(10);
        return view('topup-logger::index', compact('logs'));
    }

    /**
     * Store the transactions recorded by Guzzle's history middleware, one row each.
     * SECURITY: secrets in headers, bodies and query strings are masked first (see Redactor).
     */
    public function saveLog($guzzleReqResContainer)
    {
        $redactor = new Redactor();

        foreach ($guzzleReqResContainer as $transaction) {
            $request = $transaction['request'] ?? null;
            if (!$request) {
                continue;
            }
            $response = $transaction['response'] ?? null;

            $log = [
                'req' => [
                    'method' => $request->getMethod(),
                    'url' => $redactor->url((string) $request->getUri()),
                    'header' => json_encode($redactor->headers($request->getHeaders())),
                    'body' => $redactor->body((string) $request->getBody(), $request->getHeaderLine('Content-Type')),
                ],
            ];

            if ($response) {
                $log['res'] = [
                    'header' => json_encode($redactor->headers($response->getHeaders())),
                    'body' => $redactor->body((string) $response->getBody(), $response->getHeaderLine('Content-Type')),
                ];
            } else {
                $log['res'] = [
                    'header' => 'error in api response. Check log',
                    'body' => 'error in api response. Check log',
                ];
                logger($transaction['error'] ?? 'Uncaught error');
            }

            $this->_save($log);
        }
    }

    protected function _save($log)
    {
        try {
            $logger = new Logger();
            $logger->user_id = auth()->id() ?? null;
            $logger->method = $log['req']['method'];
            $logger->type = 'outbound';
            $logger->url = $log['req']['url'];
            $logger->request_header = $log['req']['header'];
            $logger->response_header = $log['res']['header'];
            $logger->request_body = $log['req']['body'];
            $logger->response_body = $log['res']['body'];
            $logger->ip = Request::ip();
            $logger->duration = (double)number_format(microtime(true) - LARAVEL_START, 3);
            $logger->request_time = now();
            $logger->save();
        } catch (Exception $exception) {
            logger()->error('Could not save API log');
            logger()->error($exception);
        }
    }
}
