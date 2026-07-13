<?php

namespace Modules\Warung\app\Models;

use Illuminate\Database\Eloquent\Model;

class ProdukMaster extends Model
{
    protected $table = 'produk_master';

    protected $fillable = [
        'barcode', 'nama', 'varian', 'netto', 'deskripsi',
        'foto', 'het', 'kategori_id', 'created_by_outlet_id',
    ];

    protected function casts(): array
    {
        return [
            'netto' => 'float',
            'het' => 'float',
        ];
    }

    public function kategori()
    {
        return $this->belongsTo(Kategori::class);
    }

    public function createdByOutlet()
    {
        return $this->belongsTo(\Modules\Outlet\app\Models\Outlet::class, 'created_by_outlet_id');
    }

    public function warungProduk()
    {
        return $this->hasMany(Produk::class, 'produk_master_id');
    }

    public static function findByBarcode(string $barcode): ?self
    {
        return static::where('barcode', $barcode)->first();
    }

    public static function daftarkan(array $data): self
    {
        return static::create($data);
    }
}