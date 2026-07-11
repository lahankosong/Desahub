<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WilayahKecamatan extends Model
{
    protected $table = 'wilayah_kecamatan';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $primaryKey = 'kode';
    public $timestamps = false;

    protected $fillable = ['kode', 'kabupaten_kode', 'nama'];

    public function kabupaten(): BelongsTo
    {
        return $this->belongsTo(WilayahKabupaten::class, 'kabupaten_kode', 'kode');
    }

    public function desa(): HasMany
    {
        return $this->hasMany(WilayahDesa::class, 'kecamatan_kode', 'kode');
    }
}