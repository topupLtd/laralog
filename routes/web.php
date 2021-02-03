<?php

$middlewares = env('TOPUP_LOGGER_ROUTE_MIDDLEWARE', 'web');

if($middlewares) {
    $middlewares = explode(',',$middlewares);
} else {
    $middlewares = [];
}

Route::group(['middleware' => $middlewares], function(){
    Route::get('/topup-logger', 'Topup\Logger\Http\Controllers\LoggerController@index')->name("topup.logger");
});


