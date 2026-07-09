<?php

use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    Route::post('/payments/konfirmasi', [\Modules\Payment\app\Http\Controllers\Api\PaymentController::class, 'konfirmasi']);
});