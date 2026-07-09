<?php

use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    Route::get('/outlets', [\Modules\Outlet\app\Http\Controllers\Api\OutletController::class, 'index']);
    Route::get('/outlets/{id}', [\Modules\Outlet\app\Http\Controllers\Api\OutletController::class, 'show']);
});