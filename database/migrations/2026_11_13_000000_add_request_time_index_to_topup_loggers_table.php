<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lets topup-logger:prune find old logs without scanning the whole table.
 * On a large table this takes a while to build; run it off-peak.
 */
class AddRequestTimeIndexToTopupLoggersTable extends Migration
{
    public function up()
    {
        Schema::table('topup_loggers', function (Blueprint $table) {
            $table->index('request_time');
        });
    }

    public function down()
    {
        Schema::table('topup_loggers', function (Blueprint $table) {
            $table->dropIndex(['request_time']);
        });
    }
}
