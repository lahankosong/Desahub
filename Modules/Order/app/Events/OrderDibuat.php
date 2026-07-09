<?php

namespace Modules\Order\app\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Dipancarkan saat checkout diselesaikan.
 *
 * Payload DTO-style, additive-only — field tidak boleh dihapus/diubah.
 * Harga = hasil resolusi Sellable::getHarga(qty) saat transaksi.
 */
class OrderDibuat
{
    use Dispatchable, SerializesModels;

    public int $order_id;
    public int $outlet_id;
    public string $buyer_type;
    public int $buyer_id;
    public array $items;
    public float $total_harga;
    public string $metode_pembayaran;
    public string $dibuat_pada;

    public function __construct(
        int $order_id,
        int $outlet_id,
        string $buyer_type,
        int $buyer_id,
        array $items,
        float $total_harga,
        string $metode_pembayaran,
        string $dibuat_pada
    ) {
        $this->order_id = $order_id;
        $this->outlet_id = $outlet_id;
        $this->buyer_type = $buyer_type;
        $this->buyer_id = $buyer_id;
        $this->items = $items;
        $this->total_harga = $total_harga;
        $this->metode_pembayaran = $metode_pembayaran;
        $this->dibuat_pada = $dibuat_pada;
    }
}