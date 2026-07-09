<?php

namespace Modules\Warung\app\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class KetersediaanBerubah
{
    use Dispatchable, SerializesModels;

    public string $sellable_type;
    public int $sellable_id;
    public int $outlet_id;
    public ?int $jumlah_perubahan;
    public ?bool $status_tersedia;
    public string $alasan;
    public ?int $referensi_id;
    public string $terjadi_pada;

    public function __construct(
        string $sellable_type,
        int $sellable_id,
        int $outlet_id,
        ?int $jumlah_perubahan,
        ?bool $status_tersedia,
        string $alasan,
        ?int $referensi_id,
        string $terjadi_pada
    ) {
        $this->sellable_type = $sellable_type;
        $this->sellable_id = $sellable_id;
        $this->outlet_id = $outlet_id;
        $this->jumlah_perubahan = $jumlah_perubahan;
        $this->status_tersedia = $status_tersedia;
        $this->alasan = $alasan;
        $this->referensi_id = $referensi_id;
        $this->terjadi_pada = $terjadi_pada;
    }
}