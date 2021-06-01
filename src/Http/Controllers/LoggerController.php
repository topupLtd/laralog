<?php

namespace Topup\Logger\Http\Controllers;

use App\Http\Controllers\Controller;
use Exception;
use Request;
use Topup\Logger\Models\Logger;

class LoggerController extends Controller
{
    public function index()
    {
        $logs = Logger::latest('request_time')->simplePaginate(10);
        return view('topup-logger::index', compact('logs'));
    }

    public function saveLog($guzzleReqResContainer)
    {
        $log = [];
        foreach ($guzzleReqResContainer as $transaction) {

            $log['req']['method'] = $transaction['request']->getMethod() ?? 'Unknown';
            $log['req']['url'] = $transaction['request']->getUri() ?? 'Unknown';
            $log['req']['header'] = json_encode($transaction['request']->getHeaders()) ?? json_encode(['header' => 'Unknown']);
            $log['req']['body'] = (string)$transaction['request']->getBody() ?? 'Unknown';

            if ($transaction['response']) {
                $log['res']['header'] = json_encode($transaction['response']->getHeaders()) ?? json_encode(['header' => 'Unknown']);
                $log['res']['body'] = (string)$transaction['response']->getBody() ?? 'Unknown';
            } elseif ($transaction['error'] ?? false) {
                $log['res']['body'] = 'error in api response. Check log';
                logger($transaction['error'] ?? 'Uncaught error');
            }
        }

        $this->_save($log);
    }

    protected function _save($log)
    {
        $logger = new Logger();
        $logger->user_id = auth()->id() ?? null;
        $logger->method = $log['req']['method'];
        $logger->type = 'outbound';
        $logger->url = $log['req']['url'];
        $logger->request_header = json_encode($log['req']['header']);
        $logger->response_header = json_encode($log['res']['header']);
        $logger->request_body = (string)$log['req']['body'];
        $logger->response_body = (string)$log['res']['body'];
        $logger->ip = Request::ip();
        $logger->duration = (double) number_format(microtime(true) - LARAVEL_START, 3);
        $logger->request_time = now();

        try {
            $logger->save();
        } catch (Exception $exception) {
            logger()->error('Could not save API log');
            logger()->error($exception);
        }
    }
}
