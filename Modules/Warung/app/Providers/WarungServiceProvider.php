<?php

namespace Modules\Warung\app\Providers;

use Illuminate\Support\ServiceProvider;

class WarungServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
        \Illuminate\Support\Facades\Route::prefix('api')
            ->middleware('api')
            ->group(__DIR__ . '/../../routes/api.php');
    }
}