<?php

use Illuminate\Support\Facades\Route;

Route::middleware('web')->prefix('admin')->name('admin.')->group(function () {

    // Halaman login
    Route::get('/login', [\Modules\Admin\app\Http\Controllers\Auth\AdminAuthController::class, 'showLogin'])
        ->name('login');
    Route::post('/login', [\Modules\Admin\app\Http\Controllers\Auth\AdminAuthController::class, 'login'])
        ->name('login.post');
    Route::post('/logout', [\Modules\Admin\app\Http\Controllers\Auth\AdminAuthController::class, 'logout'])
        ->name('logout');

    // Protected dashboard
    Route::middleware(\Modules\Admin\app\Http\Middleware\AdminMiddleware::class)->group(function () {

        Route::get('/', [\Modules\Admin\app\Http\Controllers\Admin\DashboardController::class, 'index'])
            ->name('dashboard');

        // Outlet verification
        Route::get('/outlets', [\Modules\Admin\app\Http\Controllers\Admin\OutletController::class, 'index'])
            ->name('outlets.index');
        Route::patch('/outlets/{id}/verify', [\Modules\Admin\app\Http\Controllers\Admin\OutletController::class, 'verify'])
            ->name('outlets.verify');

        // Orders
        Route::get('/orders', [\Modules\Admin\app\Http\Controllers\Admin\OrderController::class, 'index'])
            ->name('orders.index');

        // COD settlements
        Route::get('/payments', [\Modules\Admin\app\Http\Controllers\Admin\PaymentController::class, 'index'])
            ->name('payments.index');
        Route::patch('/payments/{id}/setor', [\Modules\Admin\app\Http\Controllers\Admin\PaymentController::class, 'setor'])
            ->name('payments.setor');
    });
});