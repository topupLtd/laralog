<?php

namespace Topup\Logger\Http\Controllers;

use App\Http\Controllers\Controller;
use Topup\Logger\Models\Logger;

class LoggerController extends Controller
{
    public function index()
    {
        $logs = Logger::latest('request_time')->simplePaginate(10);
        return view('topup-logger::index', compact('logs'));
    }
}
