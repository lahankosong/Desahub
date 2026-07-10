<?php

namespace Modules\Auth\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KurirProfile extends Model
{
    protected $fillable = [
        'user_id',
        'is_online',
        'lat',
        'lng',
        'foto_kendaraan',
        'no_plat',
        'jenis_kendaraan',
    ];

    protected $casts = [
        'is_online' => 'boolean',
        'lat' => 'float',
        'lng' => 'float',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}