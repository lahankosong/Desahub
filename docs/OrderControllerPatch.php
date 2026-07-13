<?php
// UPDATE method index() di OrderWebController
// Ganti baris eager loading yang lama dengan yang ini:

$orders = $query
    ->with([
        'items',                    // item order (untuk tampilkan daftar produk)
        'settlement',               // cod_settlements — supaya tidak N+1 di blade
        'buyer',                    // relasi polymorphic ke Konsumen/PelangganWarung
    ])
    ->orderBy('created_at', 'desc')
    ->take(50)
    ->get();

// ─── Pastikan Model Order punya relasi settlement() ──────────────
// Tambahkan ke Modules/Order/app/Models/Order.php:

public function settlement()
{
    return $this->hasOne(\Illuminate\Database\Eloquent\Relations\HasOne::class, 'cod_settlements', 'order_id', 'id')
                ->select(['order_id','jumlah_diterima','status_setor','dicatat_oleh']);
    // Kalau model CodSettlement sudah ada, pakai ini:
    // return $this->hasOne(CodSettlement::class);
}

// ─── Relasi buyer() polymorphic ─────────────────────────────────
// Tambahkan ke Modules/Order/app/Models/Order.php:

public function buyer()
{
    // Resolve model berdasarkan buyer_type
    if ($this->buyer_type === 'Konsumen') {
        return $this->belongsTo(\Modules\Auth\app\Models\KonsumenProfile::class, 'buyer_id');
    }
    if ($this->buyer_type === 'PelangganWarung') {
        return $this->belongsTo(\Modules\Warung\app\Models\PelangganWarung::class, 'buyer_id');
    }
    return null;
}

// Atau kalau mau pakai morphTo standar, pastikan buyer_type di DB
// mengandung nama class lengkap (bukan alias 'Konsumen')
