<?php

use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    Route::get('/orders', [\Modules\Order\app\Http\Controllers\Api\OrderController::class, 'index']);
    Route::get('/orders/{id}', [\Modules\Order\app\Http\Controllers\Api\OrderController::class, 'show']);
    Route::post('/orders', [\Modules\Order\app\Http\Controllers\Api\OrderController::class, 'store']);
    Route::post('/orders/{id}/batal', [\Modules\Order\app\Http\Controllers\Api\OrderController::class, 'batal']);
});