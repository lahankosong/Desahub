<?php

namespace Modules\Payment\app\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PembayaranDiterima
{
    use Dispatchable, SerializesModels;

    public int $order_id;
    public string $metode;
    public float $jumlah;
    public string $status; // 'lunas' | 'sebagian' (untuk DP)
    public string $diterima_pada;

    public function __construct(
        int $order_id,
        string $metode,
        float $jumlah,
        string $status,
        string $diterima_pada
    ) {
        $this->order_id = $order_id;
        $this->metode = $metode;
        $this->jumlah = $jumlah;
        $this->status = $status;
        $this->diterima_pada = $diterima_pada;
    }
}