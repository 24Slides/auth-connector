<?php

use Illuminate\Support\Facades\Route;

Route::group(['namespace' => 'Slides\Connector\Auth\Http\Controllers'], function () {
    Route::post('connector/webhook/{key}', 'WebhookController');
});