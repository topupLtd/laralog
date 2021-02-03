<?php

namespace Topup\Logger\Providers;

use Illuminate\Support\ServiceProvider;
use Topup\Logger\Http\Middleware\TopupLoggerMiddleware;

class LoggerServiceProvider extends ServiceProvider
{

    public function boot()
    {
        $this->loadRoutesFrom(__DIR__ . '/../../routes/web.php');
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'topup-logger');
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
        $this->mergeConfigFrom(__DIR__ . '/../../config/topup-logger.php', 'topup-logger');
    }

    public function register()
    {
        $this->app->singleton('topup-logger', function () {
            return new TopupLoggerMiddleware();
        });
    }
}
