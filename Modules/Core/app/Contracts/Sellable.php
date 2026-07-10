<?php

namespace Modules\Core\app\Contracts;

/**
 * Kontrak Sellable — implementasikan oleh setiap model produk per-vertikal.
 *
 * Order hanya menyimpan referensi polymorphic (sellable_type + sellable_id),
 * tidak pernah tahu isi produk. Semua interaksi dengan produk dilakukan
 * lewat kontrak ini.
 *
 * @see project.md bagian "Kontrak Sellable"
 */
interface Sellable
{
    /**
     * Nama item yang dijual.
     */
    public function getNama(): string;

    /**
     * Harga saat ini untuk kuantitas tertentu.
     *
     * Menerima parameter qty supaya vertikal yang butuh harga bertingkat
     * (mis. Warung Grosir) bisa terapkan price-break.
     * Vertikal biasa cukup selalu return harga tetap terlepas qty.
     */
    public function getHarga(int $qty = 1): float;

    /**
     * Satuan produk (pcs / kg / sak / porsi, dst).
     */
    public function getSatuan(): string;

    /**
     * Cek apakah item tersedia untuk dibeli sejumlah qty.
     *
     * Untuk vertikal berbasis stok angka (Warung, Apotik): return true jika stok >= qty.
     * Untuk vertikal non-stok (Warung Makan): return true/false berdasarkan status tersedia/habis.
     */
    public function cekTersedia(int $qty): bool;

    /**
     * Proses pengurangan setelah order.
     *
     * Untuk vertikal berbasis stok: kurangi stok fisik.
     * Untuk vertikal non-stok: bisa no-op.
     *
     * @param int $qty Jumlah yang dikurangi
     * @param int $referensiId ID referensi (biasanya order_id) untuk audit trail
     */
    public function prosesPengurangan(int $qty, int $referensiId): void;
}