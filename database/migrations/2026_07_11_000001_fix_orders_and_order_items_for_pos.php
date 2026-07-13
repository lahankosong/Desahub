<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. orders.buyer_id: buat nullable (POS buyer_type=Umum tidak punya buyer_id)
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('buyer_id')->nullable()->change();
        });

        // 2. orders.metode_pembayaran: tambah 'tunai_pos' dan 'tempo' ke enum
        // Laravel Blueprint tidak support alter enum langsung, pakai raw SQL
        DB::statement("
            ALTER TABLE orders
            MODIFY COLUMN metode_pembayaran
            ENUM('cod','transfer','dp','qris','tunai_pos','tempo')
            NOT NULL DEFAULT 'cod'
        ");

        // 3. order_items: tambah nama_produk (snapshot nama saat transaksi)
        // Sesuai keputusan desain Sesi 16: nama HARUS di-snapshot, jangan JOIN ke tabel produk
        // nullable dulu untuk kompatibilitas data lama, lalu backfill
        // Cek dulu apakah kolom sudah ada (untuk kompatibilitas dengan database yang sudah ada)
        if (!Schema::hasColumn('order_items', 'nama_produk')) {
            Schema::table('order_items', function (Blueprint $table) {
                $table->string('nama_produk', 255)->nullable()->after('sellable_id');
            });
        }

        // Backfill nama_produk untuk order_items lama (best-effort JOIN ke warung_produk)
        DB::statement("
            UPDATE order_items oi
            JOIN warung_produk wp ON wp.id = oi.sellable_id
                AND oi.sellable_type LIKE '%Produk%'
            SET oi.nama_produk = wp.nama
            WHERE oi.nama_produk IS NULL
        ");
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('nama_produk');
        });

        DB::statement("
            ALTER TABLE orders
            MODIFY COLUMN metode_pembayaran
            ENUM('cod','transfer','dp','qris')
            NOT NULL DEFAULT 'cod'
        ");

        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('buyer_id')->nullable(false)->change();
        });
    }
};
