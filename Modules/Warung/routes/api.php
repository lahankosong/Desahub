<?php

use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    // Produk per outlet
    Route::get('/outlets/{outlet_id}/produk', [\Modules\Warung\app\Http\Controllers\Api\ProdukController::class, 'index']);
    // CRUD produk
    Route::get('/produk/{id}', [\Modules\Warung\app\Http\Controllers\Api\ProdukController::class, 'show']);
    Route::post('/produk', [\Modules\Warung\app\Http\Controllers\Api\ProdukController::class, 'store']);
    Route::put('/produk/{id}', [\Modules\Warung\app\Http\Controllers\Api\ProdukController::class, 'update']);
    Route::post('/produk/{id}/restock', [\Modules\Warung\app\Http\Controllers\Api\ProdukController::class, 'restock']);
});