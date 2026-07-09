<?php

namespace Modules\Core\app\Traits;

use Illuminate\Support\Facades\DB;

/**
 * Trait untuk model yang butuh pencatatan ketersediaan append-only.
 *
 * Pola: field kuantitas (stok, saldo) disimpan sebagai log transaksi append-only,
 * nilai akhir = SUM dari seluruh log. Tidak boleh ada UPDATE langsung kolom stok.
 *
 * Konsisten dengan prinsip append-only di audit ledger Brimola (Margosystem).
 */
trait HasKetersediaanLog
{
    /**
     * Ambil nilai ketersediaan terkini dari SUM seluruh log.
     *
     * @param string $sellableType Model vertikal, mis. 'Warung\Produk', 'Apotik\Obat'
     * @param int $sellableId
     * @return int
     */
    public static function getKetersediaan(string $sellableType, int $sellableId): int
    {
        return (int) DB::table('ketersediaan_movements')
            ->where('sellable_type', $sellableType)
            ->where('sellable_id', $sellableId)
            ->sum('jumlah_perubahan');
    }

    /**
     * Catat pergerakan ketersediaan (append-only).
     *
     * @param string $sellableType
     * @param int $sellableId
     * @param int $outletId
     * @param int $jumlahPerubahan Positif (restock/koreksi/pembatalan), negatif (penjualan)
     * @param string $alasan 'penjualan' | 'restock' | 'koreksi' | 'toggle_status' | 'pembatalan'
     * @param int|null $referensiId Mis. order_id penyebab perubahan
     * @return void
     */
    public static function catatPergerakan(
        string $sellableType,
        int $sellableId,
        int $outletId,
        int $jumlahPerubahan,
        string $alasan,
        ?int $referensiId = null
    ): void {
        DB::table('ketersediaan_movements')->insert([
            'sellable_type'    => $sellableType,
            'sellable_id'      => $sellableId,
            'outlet_id'        => $outletId,
            'jumlah_perubahan'  => $jumlahPerubahan,
            'alasan'           => $alasan,
            'referensi_id'     => $referensiId,
            'terjadi_pada'     => now(),
            'created_at'       => now(),
        ]);
    }

    /**
     * Ambil ketersediaan cache untuk operasi atomik bergerbang (race condition).
     * Ini melengkapi (bukan mengganti) log append-only.
     *
     * @param string $sellableType
     * @param int $sellableId
     * @return int
     */
    public static function getKetersediaanCache(string $sellableType, int $sellableId): int
    {
        $row = DB::table('ketersediaan_cache')
            ->where('sellable_type', $sellableType)
            ->where('sellable_id', $sellableId)
            ->first();

        return $row ? (int) $row->qty : 0;
    }

    /**
     * Kurangi stok secara atomik bergerbang — mencegah oversell.
     * Dipakai sebelum checkout. Kalau affected_rows = 0 → tolak (stok tidak cukup).
     *
     * @param string $sellableType
     * @param int $sellableId
     * @param int $jumlah Quantity yang akan dikurangi
     * @return bool True jika berhasil dikurangi, false jika stok tidak cukup
     */
    public static function kurangiCacheAtomik(string $sellableType, int $sellableId, int $jumlah): bool
    {
        $affected = DB::table('ketersediaan_cache')
            ->where('sellable_type', $sellableType)
            ->where('sellable_id', $sellableId)
            ->where('qty', '>=', $jumlah)
            ->decrement('qty', $jumlah);

        return $affected > 0;
    }

    /**
     * Tambah stok cache.
     *
     * @param string $sellableType
     * @param int $sellableId
     * @param int $jumlah
     * @return void
     */
    public static function tambahCache(string $sellableType, int $sellableId, int $jumlah): void
    {
        DB::table('ketersediaan_cache')->updateOrInsert(
            [
                'sellable_type' => $sellableType,
                'sellable_id'   => $sellableId,
            ],
            [
                'qty' => DB::raw("qty + {$jumlah}"),
            ]
        );
    }
}