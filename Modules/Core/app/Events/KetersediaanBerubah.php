<?php

namespace Modules\Core\app\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * Event: KetersediaanBerubah
 *
 * Dipancarkan setiap kali ketersediaan item berubah (penjualan, restock, koreksi, toggle).
 * Ini SATU-SATUNYA cara ketersediaan berubah — tidak boleh UPDATE langsung kolom stok.
 *
 * Nilai akhir = SUM dari seluruh log (vertikal stok angka) atau status terakhir (vertikal non-stok).
 *
 * @see events.md
 */
class KetersediaanBerubah
{
    use Dispatchable;

    /**
     * @param string $sellableType Model vertikal, mis. 'Warung\Produk', 'Apotik\Obat'
     * @param int $sellableId
     * @param int $outletId
     * @param int|null $jumlahPerubahan Untuk vertikal stok angka; boleh negatif (penjualan) atau positif (restock)
     * @param bool|null $statusTersedia Untuk vertikal non-stok (mis. Warung Makan: tersedia/habis)
     * @param string $alasan 'penjualan' | 'restock' | 'koreksi' | 'toggle_status' | 'pembatalan'
     * @param int|null $referensiId mis. order_id penyebab perubahan
     * @param string $terjadiPada datetime
     */
    public function __construct(
        public readonly string $sellableType,
        public readonly int $sellableId,
        public readonly int $outletId,
        public readonly ?int $jumlahPerubahan,
        public readonly ?bool $statusTersedia,
        public readonly string $alasan,
        public readonly ?int $referensiId,
        public readonly string $terjadiPada,
    ) {}
}