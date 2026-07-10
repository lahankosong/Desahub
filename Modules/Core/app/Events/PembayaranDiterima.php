<?php

namespace Modules\Core\app\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * Event: PembayaranDiterima
 *
 * Dipancarkan oleh modul Payment saat pembayaran (COD/transfer/DP/nanti QRIS) dikonfirmasi.
 * Listener: Order (ubah status jadi selesai/pelunasan DP).
 *
 * @see events.md
 */
class PembayaranDiterima
{
    use Dispatchable;

    /**
     * @param int $orderId
     * @param string $metode
     * @param float $jumlah
     * @param string $status 'lunas' | 'sebagian' (untuk DP)
     * @param string $diterimaPada datetime
     */
    public function __construct(
        public readonly int $orderId,
        public readonly string $metode,
        public readonly float $jumlah,
        public readonly string $status,
        public readonly string $diterimaPada,
    ) {}
}