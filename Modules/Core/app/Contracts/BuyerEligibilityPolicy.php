<?php

namespace Modules\Core\app\Contracts;

/**
 * Kontrak opsional untuk outlet yang punya aturan pembeli khusus.
 * Default: semua outlet boleh dibeli siapa saja (Konsumen maupun Outlet lain).
 *
 * Outlet yang punya aturan pembeli khusus (mis. Warung Grosir)
 * mengimplementasikan kontrak ini untuk override.
 */
interface BuyerEligibilityPolicy
{
    /**
     * Cek apakah outlet ini boleh dibeli oleh entitas tertentu.
     *
     * @param string $buyerType 'Konsumen' atau 'Outlet'
     * @param int $buyerId ID pembeli
     * @return bool
     */
    public function bolehDibeliOleh(string $buyerType, int $buyerId): bool;
}