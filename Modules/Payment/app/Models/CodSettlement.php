<?php

namespace Modules\Payment\app\Models;

use Illuminate\Database\Eloquent\Model;

class CodSettlement extends Model
{
    protected $table = 'cod_settlements';

    protected $fillable = [
        'order_id',
        'kurir_id',
        'jumlah_diterima',
        'status_setor',
        'dicatat_oleh',
        'dicatat_pada',
    ];

    protected function casts(): array
    {
        return [
            'jumlah_diterima' => 'float',
            'dicatat_pada' => 'datetime',
        ];
    }

    public function order()
    {
        return $this->belongsTo(\Modules\Order\app\Models\Order::class, 'order_id');
    }
}