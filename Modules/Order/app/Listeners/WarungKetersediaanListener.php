<?php

namespace Modules\Order\app\Listeners;

use Modules\Order\app\Events\OrderDibuat;
use Modules\Core\app\Contracts\Sellable;
use Modules\Core\app\Traits\HasKetersediaanLog;
use Modules\Warung\app\Events\KetersediaanBerubah;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Listener: trigger prosesPengurangan() pada tiap item (implementasi Sellable),
 * yang akan memancarkan KetersediaanBerubah.
 *
 * Race-condition protection: UPDATE atomik bergerbang pada ketersediaan_cache,
 * dibungkus 1 DB transaction bersama insert log ketersediaan_movements.
 */
class WarungKetersediaanListener
{
    public function handle(OrderDibuat $event): void
    {
        DB::transaction(function () use ($event) {
            foreach ($event->items as $item) {
                $sellableClass = $item['sellable_type'];
                $sellableId = $item['sellable_id'];
                $qty = $item['qty'];

                // 1. Kurangi cache secara atomik (gerbang: WHERE qty >= jumlah)
                $berhasil = HasKetersediaanLog::kurangiCacheAtomik($sellableClass, $sellableId, $qty);

                if (! $berhasil) {
                    Log::warning("Ketersediaan tidak cukup untuk {$sellableClass}#{$sellableId}, qty={$qty}");
                    throw new \RuntimeException("Stok tidak mencukupi untuk {$sellableClass}#{$sellableId}");
                }

                // 2. Catat pergerakan di log append-only
                HasKetersediaanLog::catatPergerakan(
                    sellableType: $sellableClass,
                    sellableId: $sellableId,
                    outletId: $event->outlet_id,
                    jumlahPerubahan: -$qty,
                    alasan: 'penjualan',
                    referensiId: $event->order_id
                );

                // 3. Panggil prosesPengurangan di model Sellable (logika spesifik vertikal)
                $sellable = $sellableClass::find($sellableId);
                if ($sellable instanceof Sellable) {
                    $sellable->prosesPengurangan($qty, $event->order_id);
                }

                // 4. Pancarkan KetersediaanBerubah
                KetersediaanBerubah::dispatch(
                    sellableType: $sellableClass,
                    sellableId: $sellableId,
                    outletId: $event->outlet_id,
                    jumlahPerubahan: -$qty,
                    statusTersedia: null,
                    alasan: 'penjualan',
                    referensiId: $event->order_id,
                    terjadiPada: now()->toDateTimeString()
                );
            }
        });
    }
}