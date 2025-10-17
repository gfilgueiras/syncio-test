<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PayloadController;

Route::prefix('payloads')->group(function () {
    Route::post('/first', [PayloadController::class, 'storeFirst']);
    Route::post('/second', [PayloadController::class, 'storeSecond']);
});

Route::get('/diff', [PayloadController::class, 'diff']);


