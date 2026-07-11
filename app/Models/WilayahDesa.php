<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WilayahDesa extends Model
{
    protected $table = 'wilayah_desa';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $primaryKey = 'kode';
    public $timestamps = false;

    protected $fillable = ['kode', 'kecamatan_kode', 'nama'];

    public function kecamatan(): BelongsTo
    {
        return $this->belongsTo(WilayahKecamatan::class, 'kecamatan_kode', 'kode');
    }
}