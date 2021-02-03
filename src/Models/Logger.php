<?php

namespace Topup\Logger\Models;

use Illuminate\Database\Eloquent\Model;

class Logger extends Model
{
    public $timestamps = false;
    protected $table = 'topup_loggers';
}
