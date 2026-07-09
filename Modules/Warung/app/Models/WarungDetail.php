<?php

namespace Modules\Warung\app\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Core\app\Contracts\BuyerEligibilityPolicy;
use Modules\Outlet\app\Models\Outlet;

class WarungDetail extends Model implements BuyerEligibilityPolicy
{
    protected $table = 'warung_detail';

    protected $fillable = ['outlet_id', 'jam_buka', 'jam_tutup', 'kategori_warung', 'tier'];

    public function outlet()
    {
        return $this->belongsTo(Outlet::class);
    }

    /**
     * Warung Grosir hanya boleh di-order oleh Warung Biasa yang terverifikasi.
     * Tertutup total untuk Konsumen dan pihak lain.
     */
    public function bolehDibeliOleh(string $buyerType, int $buyerId): bool
    {
        // Warung Biasa: semua boleh beli (default behavior)
        if ($this->tier === 'biasa') {
            return true;
        }

        // Warung Grosir: hanya Outlet (bukan Konsumen)
        if ($buyerType !== 'Outlet') {
            return false;
        }

        // Pembeli harus outlet vertikal warung, tier biasa, level terverifikasi
        $pembeli = Outlet::with('warungDetail')->find($buyerId);

        if (! $pembeli) {
            return false;
        }

        // Cek level_verifikasi — harus terverifikasi oleh admin
        if ($pembeli->level_verifikasi !== 'terverifikasi') {
            return false;
        }

        // Cek pembeli adalah Warung Biasa (tier = 'biasa')
        if ($pembeli->warungDetail && $pembeli->warungDetail->tier === 'biasa') {
            return true;
        }

        return false;
    }

    /**
     * Update tier berdasarkan entri terbaru di warung_grosir_approvals.
     * BUKAN edit manual — diupdate lewat Event.
     */
    public static function refreshTier(int $outletId): void
    {
        $terakhir = \DB::table('warung_grosir_approvals')
            ->where('outlet_id', $outletId)
            ->orderBy('terjadi_pada', 'desc')
            ->first();

        $tier = $terakhir && $terakhir->jenis === 'disetujui' ? 'grosir' : 'biasa';

        self::where('outlet_id', $outletId)->update(['tier' => $tier]);
    }
}