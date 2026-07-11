<?php

namespace Modules\Warung\app\Models;

use Illuminate\Database\Eloquent\Model;

class Piutang extends Model
{
    protected $table = 'piutang';

    protected $fillable = [
        'outlet_id', 'pelanggan_warung_id', 'order_id',
        'jumlah', 'terbayar', 'jatuh_tempo', 'status', 'catatan',
    ];

    protected function casts(): array
    {
        return [
            'jumlah' => 'float',
            'terbayar' => 'float',
            'jatuh_tempo' => 'date',
        ];
    }

    public function outlet()
    {
        return $this->belongsTo(\Modules\Outlet\app\Models\Outlet::class);
    }

    public function pelanggan()
    {
        return $this->belongsTo(PelangganWarung::class, 'pelanggan_warung_id');
    }

    public function order()
    {
        return $this->belongsTo(\Modules\Order\app\Models\Order::class);
    }

    /**
     * Lingkup: hanya piutang aktif (belum lunas).
     */
    public function scopeAktif($query)
    {
        return $query->where('status', 'aktif');
    }

    /**
     * Lingkup: piutang yang sudah melewati jatuh tempo.
     */
    public function scopeJatuhTempo($query)
    {
        return $query->where('status', 'aktif')
                     ->whereDate('jatuh_tempo', '<', now()->toDateString());
    }
}