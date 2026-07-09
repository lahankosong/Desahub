<?php

namespace Modules\Core\app\Contracts;

/**
 * Kontrak opsional untuk vertikal yang punya kewajiban pelaporan ke pemerintah.
 * Hanya diimplementasikan oleh vertikal teregulasi:
 * - Apotik: resep obat
 * - TokoPupuk: subsidi by NIK petani
 *
 * Modul Compliance mendengarkan Event OrderDibuat/PembayaranDiterima,
 * cek instanceof ComplianceReportable, lalu catat ke compliance_reports.
 */
interface ComplianceReportable
{
    /**
     * Jenis regulasi yang berlaku untuk vertikal ini.
     * Contoh: 'resep_obat', 'pupuk_nik'
     */
    public function getJenisRegulasi(): string;

    /**
     * Data tambahan yang wajib dilaporkan terkait transaksi ini.
     * Return array dengan field spesifik regulasi (mis. no_resep, nik_petani, dll).
     */
    public function getDataPelaporan(): array;

    /**
     * Apakah item ini wajib dilaporkan untuk transaksi tertentu?
     */
    public function wajibDilaporkan(): bool;
}