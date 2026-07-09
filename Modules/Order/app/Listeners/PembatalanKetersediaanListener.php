<?php

namespace Modules\Order\app\Listeners;

use Modules\Core\app\Traits\HasKetersediaanLog;
use Modules\Order\app\Events\OrderDibatalkan;
use Modules\Order\app\Models\Order;
use Modules\Warung\app\Events\KetersediaanBerubah;

/**
 * Listener: kembalikan ketersediaan lewat entri kompensasi baru di ketersediaan_movements
 * (jumlah_perubahan positif, alasan='pembatalan'), BUKAN edit/hapus entri lama.
 */
class PembatalanKetersediaanListener
{
    public function handle(OrderDibatalkan $event): void
    {
        $order = Order::with('items')->find($event->order_id);

        if (! $order) {
            return;
        }

        foreach ($order->items as $item) {
            $sellableType = $item->sellable_type;
            $sellableId = $item->sellable_id;

            // Kembalikan cache stok
            HasKetersediaanLog::tambahCache($sellableType, $sellableId, $item->qty);

            // Catat kompensasi di log append-only (positif = pengembalian)
            HasKetersediaanLog::catatPergerakan(
                sellableType: $sellableType,
                sellableId: $sellableId,
                outletId: $order->outlet_id,
                jumlahPerubahan: $item->qty,
                alasan: 'pembatalan',
                referensiId: $event->order_id
            );

            // Pancarkan KetersediaanBerubah
            KetersediaanBerubah::dispatch(
                sellableType: $sellableType,
                sellableId: $sellableId,
                outletId: $order->outlet_id,
                jumlahPerubahan: $item->qty,
                statusTersedia: null,
                alasan: 'pembatalan',
                referensiId: $event->order_id,
                terjadiPada: now()->toDateTimeString()
            );
        }
    }
}