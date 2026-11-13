<?php

namespace Topup\Logger\Http\Controllers;

use App\Http\Controllers\Controller;
use Exception;
use Request;
use Topup\Logger\Models\Logger;
use Topup\Logger\Support\Redactor;

class LoggerController extends Controller
{
    const PER_PAGE = 20;

    /** what the list shows; headers and bodies (the heavy columns) are fetched per log by show() */
    const LIST_COLUMNS = ['id', 'user_id', 'method', 'type', 'url', 'ip', 'duration', 'request_time'];

    const DETAIL_COLUMNS = ['id', 'url', 'request_header', 'request_body', 'response_header', 'response_body'];

    /**
     * Newest logs first, paged by id ("?before=<id>" / "?after=<id>") rather than OFFSET, so any
     * page is a short primary-key range read however deep it is. id follows insertion order, as
     * request_time does.
     */
    public function index()
    {
        $before = (int) request()->query('before');
        $after = (int) request()->query('after');
        $paged = $before > 0 || $after > 0;
        $logs = null;

        if ($before > 0) {
            $logs = Logger::query()->select(self::LIST_COLUMNS)
                ->where('id', '<', $before)
                ->orderByDesc('id')
                ->limit(self::PER_PAGE)
                ->get();
        } elseif ($after > 0) {
            $newer = Logger::query()->select(self::LIST_COLUMNS)
                ->where('id', '>', $after)
                ->orderBy('id')
                ->limit(self::PER_PAGE)
                ->get();

            // a short page means we reached the newest logs: show the full first page instead
            if ($newer->count() === self::PER_PAGE) {
                $logs = $newer->reverse()->values();
            }
        }

        if ($logs === null) {
            $logs = Logger::query()->select(self::LIST_COLUMNS)
                ->orderByDesc('id')
                ->limit(self::PER_PAGE)
                ->get();
        }

        $first = $logs->first();
        $last = $logs->last();

        $newerUrl = $first && Logger::query()->where('id', '>', $first->id)->exists()
            ? route('topup.logger', ['after' => $first->id])
            : null;
        $olderUrl = $last && Logger::query()->where('id', '<', $last->id)->exists()
            ? route('topup.logger', ['before' => $last->id])
            : null;
        $newestUrl = $newerUrl || ($paged && !$first) ? route('topup.logger') : null;

        return view('topup-logger::index', compact('logs', 'newerUrl', 'olderUrl', 'newestUrl'));
    }

    /**
     * Headers and bodies of one log, loaded by the list page when a log is expanded.
     */
    public function show($id)
    {
        $log = Logger::findOrFail($id, self::DETAIL_COLUMNS);

        // rows stored before redaction existed may hold binary bodies that are not valid UTF-8
        return response()->json($log, 200, ['Cache-Control' => 'no-store, private'], JSON_INVALID_UTF8_SUBSTITUTE);
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
