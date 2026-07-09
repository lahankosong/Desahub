<?php

namespace Modules\Core\app\Contracts;

interface Sellable
{
    /**
     * Nama item yang dijual.
     */
    public function getNama(): string;

    /**
     * Harga saat ini, terima parameter qty supaya vertikal yang butuh
     * harga bertingkat (mis. Warung Grosir) bisa terapkan price-break.
     * Vertikal biasa cukup selalu return harga tetap terlepas qty.
     */
    public function getHarga(int $qty = 1): float;

    /**
     * Satuan produk: pcs / kg / sak / porsi, dst.
     */
    public function getSatuan(): string;

    /**
     * Cek apakah item boleh dibeli untuk qty tertentu.
     * Stok fisik ATAU status tersedia/habis, tergantung vertikal.
     */
    public function cekTersedia(int $qty = 1): bool;

    /**
     * Logika spesifik vertikal saat pengurangan terjadi:
     * - Warung/Apotik: kurangi stok fisik
     * - WarungMakan: bisa no-op (bukan stok angka)
     *
     * @param int $qty Jumlah yang dikurangi
     * @param int|null $referensiId ID referensi (mis. order_id) penyebab perubahan
     * @return void
     */
    public function prosesPengurangan(int $qty, ?int $referensiId = null): void;
}