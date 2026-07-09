<?php

namespace Modules\Order\app\Listeners;

use Modules\Order\app\Events\OrderDibuat;

/**
 * Listener: munculkan order baru ke daftar kurir yang online.
 * Di MVP, kurir polling atau lihat daftar order available secara manual.
 */
class KurirListener
{
    public function handle(OrderDibuat $event): void
    {
        // Untuk MVP, cukup log bahwa order baru tersedia.
        // Kurir akan lihat order dengan status 'dibuat' dan kurir_id = null.
        // Tidak ada action khusus selain memastikan order sudah tercatat di DB.
    }
}