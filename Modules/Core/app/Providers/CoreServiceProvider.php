<?php

namespace Modules\Core\app\Providers;

use Illuminate\Support\ServiceProvider;

class CoreServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Kontrak tidak perlu di-bind karena dideteksi via instanceof.
        // Module ini hanya menyediakan kontrak, trait, dan helper.
    }

    /**
     * Bootstrap any module services.
     */
    public function boot(): void
    {
        // Load migrations dari module ini
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
    }
}