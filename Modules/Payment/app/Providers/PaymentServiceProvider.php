<?php

namespace Modules\Payment\app\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Modules\Payment\app\Events\PembayaranDiterima;
use Modules\Order\app\Listeners\StatusSelesaiListener;

class PaymentServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

        // Listener untuk PembayaranDiterima -> ubah status order jadi selesai
        Event::listen(PembayaranDiterima::class, StatusSelesaiListener::class);

        // API routes
        \Illuminate\Support\Facades\Route::prefix('api')
            ->middleware('api')
            ->group(__DIR__ . '/../../routes/api.php');
    }
}