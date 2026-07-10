<?php

namespace Modules\Auth\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KonsumenProfile extends Model
{
    protected $fillable = [
        'user_id',
        'alamat',
        'lat',
        'lng',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}