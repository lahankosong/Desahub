<?php

namespace Modules\Warung\app\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Core\app\Contracts\Sellable;
use Modules\Core\app\Traits\HasKetersediaanLog;

class Produk extends Model implements Sellable
{
    protected $table = 'warung_produk';

    protected $fillable = [
        'outlet_id', 'produk_master_id', 'nama', 'harga', 'harga_beli',
        'satuan', 'deskripsi', 'barcode', 'foto', 'kategori', 'diskon', 'bundle',
    ];

    public function outlet()
    {
        return $this->belongsTo(\Modules\Outlet\app\Models\Outlet::class);
    }

    public function produkMaster()
    {
        return $this->belongsTo(ProdukMaster::class, 'produk_master_id');
    }

    public function hargaHistory()
    {
        return $this->hasMany(HargaHistory::class, 'warung_produk_id');
    }

    // --- Implementasi Sellable ---

    public function getNama(): string
    {
        return $this->nama;
    }

    public function getHarga(int $qty = 1): float
    {
        // Warung Biasa: harga tetap terlepas qty.
        // Warung Grosir (override): bisa implementasi price-break nanti.
        return (float) $this->harga;
    }

    public function getSatuan(): string
    {
        return $this->satuan;
    }

    public function cekTersedia(int $qty = 1): bool
    {
        return HasKetersediaanLog::getKetersediaanCache(
            self::class,
            $this->id
        ) >= $qty;
    }

    public function prosesPengurangan(int $qty, ?int $referensiId = null): void
    {
        // Pengurangan sudah ditangani atomik oleh WarungKetersediaanListener.
        // Method ini untuk logika spesifik vertikal (jika ada), saat ini no-op.
    }
}