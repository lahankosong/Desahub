<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WilayahProvinsi extends Model
{
    protected $table = 'wilayah_provinsi';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $primaryKey = 'kode';
    public $timestamps = false;

    protected $fillable = ['kode', 'nama'];

    public function kabupaten(): HasMany
    {
        return $this->hasMany(WilayahKabupaten::class, 'provinsi_kode', 'kode');
    }
}