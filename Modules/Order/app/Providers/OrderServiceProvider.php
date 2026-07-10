<?php

namespace Modules\Order\app\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Modules\Order\app\Events\OrderDibuat;
use Modules\Order\app\Events\OrderDibatalkan;
use Modules\Order\app\Listeners\KurirListener;
use Modules\Order\app\Listeners\WarungKetersediaanListener;
use Modules\Order\app\Listeners\StatusSelesaiListener;
use Modules\Order\app\Listeners\PembatalanKetersediaanListener;

class OrderServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

        // Daftarkan Listener untuk Event OrderDibuat
        Event::listen(OrderDibuat::class, KurirListener::class);
        Event::listen(OrderDibuat::class, WarungKetersediaanListener::class);

        // Daftarkan Listener untuk Event OrderDibatalkan
        Event::listen(OrderDibatalkan::class, PembatalanKetersediaanListener::class);

        // API routes
        \Illuminate\Support\Facades\Route::prefix('api')
            ->middleware('api')
            ->group(__DIR__ . '/../../routes/api.php');
    }
}