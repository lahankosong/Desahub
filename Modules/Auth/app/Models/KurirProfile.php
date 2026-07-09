<?php

namespace Modules\Auth\app\Models;

use Illuminate\Database\Eloquent\Model;

class KurirProfile extends Model
{
    protected $fillable = ['user_id', 'no_kendaraan', 'tipe_kendaraan', 'is_online', 'lat', 'lng', 'terakhir_online'];

    protected function casts(): array
    {
        return [
            'is_online' => 'boolean',
            'terakhir_online' => 'datetime',
        ];
    }

    public function user() { return $this->belongsTo(User::class); }
}