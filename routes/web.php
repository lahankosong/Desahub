<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WebAuthController;

/*
|--------------------------------------------------------------------------
| PWA Web Routes — Multi-Role Layout
|--------------------------------------------------------------------------
| 1 aplikasi Laravel, 3 role: /warung, /konsumen, /kurir
| Masing-masing punya layout, manifest, dan Service Worker scope sendiri.
*/

// ========== Public — Auth Pages (shared views, per-role) ==========

foreach (['warung', 'konsumen', 'kurir'] as $role) {
    Route::get("/{$role}/login", [WebAuthController::class, 'showLogin'])->name("{$role}.login");
    Route::post("/{$role}/login", [WebAuthController::class, 'login']);

    Route::get("/{$role}/register", [WebAuthController::class, 'showRegister'])->name("{$role}.register");
    Route::post("/{$role}/register", [WebAuthController::class, 'register']);

    Route::get("/{$role}/verify-otp", [WebAuthController::class, 'showVerifyOtp'])->name("{$role}.verify-otp");
    Route::post("/{$role}/verify-otp", [WebAuthController::class, 'verifyOtp']);
}

// Shared logout
Route::post('/logout', [WebAuthController::class, 'logout'])->name('logout');

// ========== Protected — Role Dashboards ==========

Route::middleware('auth')->group(function () {
    Route::get('/warung', fn() => view('warung.dashboard'))->name('warung.dashboard');
    Route::get('/konsumen', fn() => view('konsumen.dashboard'))->name('konsumen.dashboard');
    Route::get('/kurir', fn() => view('kurir.dashboard'))->name('kurir.dashboard');
});

// ========== Root ==========
Route::get('/', function () {
    return view('welcome');
});