<?php

namespace Modules\Core\app\Traits;

trait HasRounding
{
    /**
     * Bulatkan harga sesuai aturan Desahub:
     * - Desimal 00-25 → bulatkan ke 0 (bawah)
     * - Desimal 26-75 → bulatkan ke 50
     * - Desimal 76-99 → bulatkan ke 100 (atas)
     *
     * Contoh: 4561 → 4550, 4576 → 4600, 4525 → 4500
     */
    public static function bulatkanHarga(float|int $harga): int
    {
        $harga = (int) round($harga);
        $last2 = $harga % 100;

        if ($last2 <= 25) {
            // round down to 0
            return $harga - $last2;
        }

        if ($last2 <= 75) {
            // round to 50
            return $harga - $last2 + 50;
        }

        // 76-99: round up to 100
        return $harga - $last2 + 100;
    }
}