<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('warung_produk', function (Blueprint $table) {
            if (!Schema::hasColumn('warung_produk', 'produk_master_id')) {
                $table->foreignId('produk_master_id')
                      ->nullable()
                      ->after('outlet_id')
                      ->constrained('produk_master')
                      ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('warung_produk', function (Blueprint $table) {
            $table->dropForeign(['produk_master_id']);
            $table->dropColumn('produk_master_id');
        });
    }
};