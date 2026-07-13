<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('warung_produk', function (Blueprint $table) {
            if (!Schema::hasColumn('warung_produk', 'varian')) {
                $table->string('varian', 100)->nullable()->after('nama');
            }
            if (!Schema::hasColumn('warung_produk', 'netto')) {
                $table->decimal('netto', 10, 2)->nullable()->after('deskripsi')->comment('dalam gram/ml');
            }
            if (!Schema::hasColumn('warung_produk', 'harga_grosir')) {
                $table->decimal('harga_grosir', 15, 2)->nullable()->after('harga')->comment('Harga untuk pembelian grosir (qty besar)');
            }
            if (!Schema::hasColumn('warung_produk', 'min_qty_grosir')) {
                $table->integer('min_qty_grosir')->nullable()->after('harga_grosir')->default(0)->comment('Minimal qty untuk harga grosir');
            }
        });
    }

    public function down(): void
    {
        Schema::table('warung_produk', function (Blueprint $table) {
            $table->dropColumn(['varian', 'netto', 'harga_grosir', 'min_qty_grosir']);
        });
    }
};