<?php

namespace Modules\Core\app\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * Event: OrderDibatalkan
 *
 * Dipancarkan oleh modul Order saat pembatalan oleh Konsumen/Warung/Kurir/Admin
 * atau gagal kirim setelah retry.
 *
 * Listener:
 * - Warung: kembalikan ketersediaan lewat entri KOMPENSASI baru (append-only, bukan edit log lama)
 * - Order: set status jadi dibatalkan
 *
 * @see events.md
 */
class OrderDibatalkan
{
    use Dispatchable;

    /**
     * @param int $orderId
     * @param string $dibatalkanOlehType 'Konsumen' | 'Warung' | 'Kurir' | 'Admin'
     * @param int $dibatalkanOlehId
     * @param string $alasan
     * @param string $terjadiPada datetime
     */
    public function __construct(
        public readonly int $orderId,
        public readonly string $dibatalkanOlehType,
        public readonly int $dibatalkanOlehId,
        public readonly string $alasan,
        public readonly string $terjadiPada,
    ) {}
}