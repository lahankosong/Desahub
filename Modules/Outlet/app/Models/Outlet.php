<?php

namespace Modules\Outlet\app\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Core\app\Contracts\BuyerEligibilityPolicy;

class Outlet extends Model
{
    protected $fillable = [
        'owner_user_id', 'nama', 'lat', 'lng', 'alamat', 'level_verifikasi',
    ];

    protected function casts(): array
    {
        return [
            'lat' => 'float',
            'lng' => 'float',
        ];
    }

    public function vertikals()
    {
        return $this->hasMany(OutletVertikal::class);
    }

    public function warungDetail()
    {
        return $this->hasOne(\Modules\Warung\app\Models\WarungDetail::class);
    }

    /**
     * Cek eligibility pembeli — default: semua boleh.
     * Warung Grosir meng-override via kontrak BuyerEligibilityPolicy.
     */
    public function bolehDibeliOleh(string $buyerType, int $buyerId): bool
    {
        if ($this->warungDetail && $this->warungDetail instanceof BuyerEligibilityPolicy) {
            return $this->warungDetail->bolehDibeliOleh($buyerType, $buyerId);
        }

        return true; // Default: semua outlet boleh dibeli siapa saja
    }
}