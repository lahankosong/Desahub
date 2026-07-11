<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambah kolom snapshot nama_produk ke order_items.
     *
     * @see project.md bagian "Integritas Transaksi — 8. Snapshot Nama Produk"
     * @see last_update.md Sesi 16
     *
     * nama_produk diisi oleh BuatOrder/OrderController dari Sellable::getNama()
     * pada saat transaksi terjadi — SAMA PERSIS dengan pola harga_satuan
     * yang sudah snapshot. Data lama (order_items sebelum migration ini)
     * perlu backfill manual lewat JOIN satu kali ke warung_produk.
     */
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->string('nama_produk', 255)->after('sellable_id');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('nama_produk');
        });
    }
};