<?php

namespace Modules\Order\app\Listeners;

use Modules\Payment\app\Events\PembayaranDiterima;
use Modules\Order\app\Models\Order;

/**
 * Listener: ubah status order jadi 'selesai' saat pembayaran diterima.
 */
class StatusSelesaiListener
{
    public function handle(PembayaranDiterima $event): void
    {
        $order = Order::find($event->order_id);

        if ($order && $order->status === 'diantar') {
            $order->status = 'selesai';
            $order->selesai_pada = now();
            $order->save();
        }
    }
}