<?php

use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    Route::post('/kurir/online', [\Modules\Kurir\app\Http\Controllers\Api\KurirController::class, 'toggleOnline']);
    Route::get('/kurir/available-orders', [\Modules\Kurir\app\Http\Controllers\Api\KurirController::class, 'availableOrders']);
    Route::post('/kurir/orders/{id}/klaim', [\Modules\Kurir\app\Http\Controllers\Api\KurirController::class, 'klaimOrder']);
    Route::post('/kurir/orders/{id}/update-status', [\Modules\Kurir\app\Http\Controllers\Api\KurirController::class, 'updateStatus']);
    Route::get('/kurir/my-orders', [\Modules\Kurir\app\Http\Controllers\Api\KurirController::class, 'myOrders']);
});