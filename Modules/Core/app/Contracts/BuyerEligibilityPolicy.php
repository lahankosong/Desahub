<?php

namespace Modules\Core\app\Contracts;

/**
 * Kontrak BuyerEligibilityPolicy — opsional, untuk outlet yang punya aturan pembeli khusus.
 *
 * Default: semua outlet boleh dibeli siapa saja (Konsumen maupun Outlet lain).
 * Outlet yang punya aturan pembeli khusus (mis. Warung Grosir) mengimplementasikan
 * kontrak ini untuk override.
 *
 * Order module memanggil kontrak ini saat checkout, sebelum OrderDibuat dipancarkan.
 *
 * @see project.md bagian "Kontrak BuyerEligibilityPolicy"
 * @see project.md bagian "Tingkatan Warung: Biasa vs Grosir"
 */
interface BuyerEligibilityPolicy
{
    /**
     * Cek apakah buyer tertentu boleh membeli dari outlet ini.
     *
     * @param string $buyerType Tipe pembeli (Konsumen, Outlet)
     * @param int    $buyerId   ID pembeli
     * @return bool true jika boleh, false jika ditolak
     */
    public function bolehDibeliOleh(string $buyerType, int $buyerId): bool;

    /**
     * Alasan penolakan (jika bolehDibeliOleh return false).
     * Berguna untuk pesan error ke pengguna.
     */
    public function getAlasanPenolakan(): string;
}