<?php

use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Public
    Route::post('/register', [\Modules\Auth\app\Http\Controllers\Api\AuthController::class, 'register']);
    Route::post('/login', [\Modules\Auth\app\Http\Controllers\Api\AuthController::class, 'login']);

    // Protected (Sanctum)
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [\Modules\Auth\app\Http\Controllers\Api\AuthController::class, 'logout']);
        Route::get('/profil', [\Modules\Auth\app\Http\Controllers\Api\AuthController::class, 'profil']);
    });
});