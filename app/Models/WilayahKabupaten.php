<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WilayahKabupaten extends Model
{
    protected $table = 'wilayah_kabupaten';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $primaryKey = 'kode';
    public $timestamps = false;

    protected $fillable = ['kode', 'provinsi_kode', 'nama'];

    public function provinsi(): BelongsTo
    {
        return $this->belongsTo(WilayahProvinsi::class, 'provinsi_kode', 'kode');
    }

    public function kecamatan(): HasMany
    {
        return $this->hasMany(WilayahKecamatan::class, 'kabupaten_kode', 'kode');
    }
}