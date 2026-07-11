<?php

namespace App\Models\Chat;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Outlet\app\Models\Outlet;

class Percakapan extends Model
{
    protected $table = 'percakapan';

    protected $fillable = [
        'outlet_id', 'konsumen_id', 'dibuat_pada',
    ];

    protected function casts(): array
    {
        return [
            'dibuat_pada' => 'datetime',
        ];
    }

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class, 'outlet_id');
    }

    public function konsumen(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'konsumen_id');
    }

    public function pesan(): HasMany
    {
        return $this->hasMany(Pesan::class, 'percakapan_id')->orderBy('dikirim_pada', 'asc');
    }

    /**
     * Pesan terakhir di percakapan ini (untuk preview inbox).
     */
    public function pesanTerakhir(): HasMany
    {
        return $this->hasMany(Pesan::class, 'percakapan_id')->orderBy('dikirim_pada', 'desc');
    }

    /**
     * Jumlah pesan yang belum dibaca oleh pihak Outlet
     * (yaitu pesan yang dikirim Konsumen dan dibaca_pada masih NULL).
     */
    public function getBelumDibacaOutletAttribute(): int
    {
        return $this->pesan()
            ->where('pengirim_type', 'Konsumen')
            ->whereNull('dibaca_pada')
            ->count();
    }
}