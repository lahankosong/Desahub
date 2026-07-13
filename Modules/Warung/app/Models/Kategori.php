<?php

namespace Modules\Warung\app\Models;

use Illuminate\Database\Eloquent\Model;

class Kategori extends Model
{
    protected $table = 'kategoris';

    protected $fillable = [
        'nama',
        'parent_id',
    ];

    public function parent()
    {
        return $this->belongsTo(Kategori::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Kategori::class, 'parent_id');
    }

    public function produkMaster()
    {
        return $this->hasMany(ProdukMaster::class, 'kategori_id');
    }

    /**
     * Ambil semua kategori level 0 (parent_id = null)
     */
    public static function root()
    {
        return static::whereNull('parent_id')->get();
    }
}