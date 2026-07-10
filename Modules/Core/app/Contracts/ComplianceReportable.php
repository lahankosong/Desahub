<?php

namespace Modules\Core\app\Contracts;

/**
 * Kontrak ComplianceReportable — opsional, khusus vertikal teregulasi.
 *
 * Hanya diimplementasikan oleh vertikal yang punya kewajiban pelaporan
 * ke pemerintah (Apotik: resep obat, Toko Pupuk: subsidi by NIK petani).
 *
 * Modul Compliance mendengarkan Event OrderDibuat/PembayaranDiterima,
 * cek instanceof ComplianceReportable, lalu catat ke compliance_reports
 * (append-only). Modul Order/Payment tidak pernah disentuh.
 *
 * @see project.md bagian "Kontrak ComplianceReportable"
 */
interface ComplianceReportable
{
    /**
     * Kode vertikal untuk keperluan pelaporan.
     * Contoh: 'apotik', 'toko_pupuk'
     */
    public function getVertikalKode(): string;

    /**
     * Data tambahan yang wajib dilampirkan dalam laporan compliance.
     *
     * Return array dengan struktur bebas sesuai kebutuhan vertikal,
     * mis. Apotik: ['no_resep' => '...', 'dokter' => '...'],
     * Toko Pupuk: ['nik_petani' => '...', 'no_subsidi' => '...'].
     */
    public function getComplianceData(): array;

    /**
     * Tipe laporan yang dibutuhkan.
     * Return array of string, mis. ['penjualan', 'pembelian'].
     */
    public function getJenisLaporan(): array;

    /**
     * Apakah vertikal ini membutuhkan pelaporan pemerintah?
     * Bisa dinamis — mis. produk tertentu saja yang perlu dilaporkan.
     */
    public function perluDilaporkan(): bool;
}