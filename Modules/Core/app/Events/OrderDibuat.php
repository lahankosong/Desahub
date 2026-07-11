<?php

namespace Modules\Core\app\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * Event: OrderDibuat
 *
 * Dipancarkan oleh modul Order saat checkout diselesaikan.
 * Listener: Kurir (munculkan order), Warung (trigger prosesPengurangan -> emit KetersediaanBerubah).
 *
 * Payload DTO — bukan Eloquent Model utuh.
 * Field additive-only, tidak boleh dihapus/diubah.
 *
 * @see events.md
 */
class OrderDibuat
{
    use Dispatchable;

    /**
     * @param int $orderId
     * @param int $outletId Outlet penjual (generik, berlaku semua vertikal)
     * @param string $buyerType 'Konsumen' | 'Outlet' (polymorphic)
     * @param int $buyerId
     * @param array $items [{sellable_type, sellable_id, nama_produk, qty, harga_satuan}] — nama_produk snapshot, tidak boleh di-JOIN ulang
     * @param float $totalHarga
     * @param string $metodePembayaran 'cod' | 'transfer' | 'dp'
     * @param string $dibuatPada datetime
     */
    public function __construct(
        public readonly int $orderId,
        public readonly int $outletId,
        public readonly string $buyerType,
        public readonly int $buyerId,
        public readonly array $items,
        public readonly float $totalHarga,
        public readonly string $metodePembayaran,
        public readonly string $dibuatPada,
    ) {}
}