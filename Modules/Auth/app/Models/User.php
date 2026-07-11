<?php

namespace Modules\Auth\app\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, SoftDeletes;

    protected $fillable = [
        'nama',
        'no_hp',
        'password',
        'email',
        'google_id',
        'no_hp_verified_at',
        'otp_code',
        'otp_expires_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'otp_code',
    ];

    protected $casts = [
        'no_hp_verified_at' => 'datetime',
        'otp_expires_at' => 'datetime',
    ];

    /**
     * Relasi ke profil outlet (jika user mendaftarkan outlet).
     */
    public function outletProfile(): HasOne
    {
        return $this->hasOne(OutletProfile::class);
    }

    /**
     * Relasi ke profil konsumen (default semua user punya).
     */
    public function konsumenProfile(): HasOne
    {
        return $this->hasOne(KonsumenProfile::class);
    }

    /**
     * Relasi ke profil kurir (jika user daftar/diterima sebagai kurir).
     */
    public function kurirProfile(): HasOne
    {
        return $this->hasOne(KurirProfile::class);
    }

    /**
     * Cek apakah user punya peran tertentu (via profil).
     */
    public function hasRole(string $role): bool
    {
        return match ($role) {
            'outlet' => $this->outletProfile()->exists(),
            'konsumen' => $this->konsumenProfile()->exists(),
            'kurir' => $this->kurirProfile()->exists(),
            default => false,
        };
    }
}