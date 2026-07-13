<?php

namespace Modules\Warung\app\Models;

use Illuminate\Database\Eloquent\Model;

class HargaHistory extends Model
{
    protected $table = 'harga_produk_history';

    public $timestamps = false;

    protected $fillable = [
        'warung_produk_id',
        'harga_lama',
        'harga_baru',
        'outlet_id',
        'dicatat_pada',
    ];

    protected function casts(): array
    {
        return [
            'harga_lama' => 'float',
            'harga_baru' => 'float',
            'dicatat_pada' => 'datetime',
        ];
    }

    public function warungProduk()
    {
        return $this->belongsTo(Produk::class, 'warung_produk_id');
    }

    public function outlet()
    {
        return $this->belongsTo(\Modules\Outlet\app\Models\Outlet::class);
    }
}