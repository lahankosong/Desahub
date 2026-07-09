<?php

namespace Modules\Order\app\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderDibatalkan
{
    use Dispatchable, SerializesModels;

    public int $order_id;
    public string $dibatalkan_oleh_type;
    public int $dibatalkan_oleh_id;
    public string $alasan;
    public string $terjadi_pada;

    public function __construct(
        int $order_id,
        string $dibatalkan_oleh_type,
        int $dibatalkan_oleh_id,
        string $alasan,
        string $terjadi_pada
    ) {
        $this->order_id = $order_id;
        $this->dibatalkan_oleh_type = $dibatalkan_oleh_type;
        $this->dibatalkan_oleh_id = $dibatalkan_oleh_id;
        $this->alasan = $alasan;
        $this->terjadi_pada = $terjadi_pada;
    }
}