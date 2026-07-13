<?php

namespace Modules\Warung\app\Models;

use Illuminate\Database\Eloquent\Model;

class HargaHistory extends Model
{
    protected $table = 'harga_produk_history';

    protected $fillable = ['warung_produk_id', 'harga_jual', 'diupdate_pada'];

    protected function casts(): array
    {
        return [
            'harga_jual' => 'float',
            'diupdate_pada' => 'datetime',
        ];
    }

    public function warungProduk()
    {
        return $this->belongsTo(Produk::class, 'warung_produk_id');
    }
}