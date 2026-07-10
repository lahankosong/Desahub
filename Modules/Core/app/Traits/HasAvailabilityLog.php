<?php

namespace Modules\Core\app\Traits;

use Illuminate\Support\Facades\DB;

/**
 * Trait untuk model yang butuh pencatatan log ketersediaan append-only.
 *
 * Digunakan oleh model produk per-vertikal (mis. Warung\Produk, Apotik\Obat).
 *
 * Pola: UPDATE atomik bergerbang pada ketersediaan_cache +
 *       INSERT log ke ketersediaan_movements dalam 1 transaksi DB.
 *
 * Ini melengkapi (bukan mengganti) strategi resolusi konflik sync offline.
 * ketersediaan_cache melindungi dari race condition real-time di server,
 * log append-only tetap sumber kebenaran untuk sinkronisasi lintas device offline.
 *
 * @see project.md bagian "Integritas Transaksi" poin 1
 * @see project.md bagian "Strategi Resolusi Konflik Sinkronisasi"
 */
trait HasAvailabilityLog
{
    /**
     * Kurangi ketersediaan dengan UPDATE atomik bergerbang.
     *
     * @param int $qty Jumlah yang dikurangi
     * @param int $referensiId ID referensi (biasanya order_id)
     * @return bool true jika berhasil, false jika stok tidak cukup (race condition)
     */
    public function kurangiKetersediaan(int $qty, int $referensiId): bool
    {
        return DB::transaction(function () use ($qty, $referensiId) {
            // 1. UPDATE atomik bergerbang — mencegah oversell race condition
            $affected = DB::table('ketersediaan_cache')
                ->where('sellable_type', static::class)
                ->where('sellable_id', $this->getKey())
                ->where('qty', '>=', $qty)
                ->update([
                    'qty' => DB::raw("qty - {$qty}"),
                    'updated_at' => now(),
                ]);

            if ($affected === 0) {
                // Stok tidak cukup saat itu juga — race condition tercegah oleh DB
                return false;
            }

            // 2. INSERT log append-only — sumber kebenaran untuk sync offline
            DB::table('ketersediaan_movements')->insert([
                'sellable_type' => static::class,
                'sellable_id' => $this->getKey(),
                'outlet_id' => $this->getOutletId(),
                'jumlah_perubahan' => -$qty,
                'status_tersedia' => null,
                'alasan' => 'penjualan',
                'referensi_id' => $referensiId,
                'terjadi_pada' => now(),
            ]);

            return true;
        });
    }

    /**
     * Tambah ketersediaan (restock, koreksi, kompensasi pembatalan).
     *
     * @param int $qty Jumlah yang ditambah
     * @param string $alasan 'restock' | 'koreksi' | 'pembatalan'
     * @param int|null $referensiId ID referensi (opsional)
     */
    public function tambahKetersediaan(int $qty, string $alasan = 'restock', ?int $referensiId = null): void
    {
        DB::transaction(function () use ($qty, $alasan, $referensiId) {
            // 1. UPDATE cache
            DB::table('ketersediaan_cache')
                ->updateOrInsert(
                    [
                        'sellable_type' => static::class,
                        'sellable_id' => $this->getKey(),
                    ],
                    [
                        'qty' => DB::raw("COALESCE(qty, 0) + {$qty}"),
                        'outlet_id' => $this->getOutletId(),
                        'updated_at' => now(),
                    ]
                );

            // 2. INSERT log
            DB::table('ketersediaan_movements')->insert([
                'sellable_type' => static::class,
                'sellable_id' => $this->getKey(),
                'outlet_id' => $this->getOutletId(),
                'jumlah_perubahan' => $qty,
                'status_tersedia' => null,
                'alasan' => $alasan,
                'referensi_id' => $referensiId,
                'terjadi_pada' => now(),
            ]);
        });
    }

    /**
     * Ubah status tersedia/habis (untuk vertikal non-stok seperti Warung Makan).
     *
     * @param bool $tersedia
     * @param int|null $referensiId
     */
    public function setStatusTersedia(bool $tersedia, ?int $referensiId = null): void
    {
        DB::transaction(function () use ($tersedia, $referensiId) {
            // 1. UPDATE cache
            DB::table('ketersediaan_cache')
                ->updateOrInsert(
                    [
                        'sellable_type' => static::class,
                        'sellable_id' => $this->getKey(),
                    ],
                    [
                        'status_tersedia' => $tersedia,
                        'outlet_id' => $this->getOutletId(),
                        'updated_at' => now(),
                    ]
                );

            // 2. INSERT log
            DB::table('ketersediaan_movements')->insert([
                'sellable_type' => static::class,
                'sellable_id' => $this->getKey(),
                'outlet_id' => $this->getOutletId(),
                'jumlah_perubahan' => null,
                'status_tersedia' => $tersedia,
                'alasan' => 'toggle_status',
                'referensi_id' => $referensiId,
                'terjadi_pada' => now(),
            ]);
        });
    }

    /**
     * Dapatkan ketersediaan saat ini dari cache.
     *
     * @return array{qty: int|null, status_tersedia: bool|null}
     */
    public function getKetersediaan(): array
    {
        $cache = DB::table('ketersediaan_cache')
            ->where('sellable_type', static::class)
            ->where('sellable_id', $this->getKey())
            ->first();

        return [
            'qty' => $cache->qty ?? null,
            'status_tersedia' => $cache->status_tersedia ?? null,
        ];
    }

    /**
     * Dapatkan outlet_id dari model produk.
     * Harus diimplementasikan oleh model yang menggunakan trait ini.
     */
    abstract public function getOutletId(): int;
}