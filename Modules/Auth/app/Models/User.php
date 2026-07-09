<?php

namespace Modules\Auth\app\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $fillable = [
        'nama',
        'hp',
        'password',
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    public function outletProfile()
    {
        return $this->hasOne(\Modules\Auth\app\Models\OutletProfile::class, 'user_id');
    }

    public function konsumenProfile()
    {
        return $this->hasOne(\Modules\Auth\app\Models\KonsumenProfile::class, 'user_id');
    }

    public function kurirProfile()
    {
        return $this->hasOne(\Modules\Auth\app\Models\KurirProfile::class, 'user_id');
    }
}