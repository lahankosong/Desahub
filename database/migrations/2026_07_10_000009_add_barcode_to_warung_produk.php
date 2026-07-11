<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('warung_produk', function (Blueprint $table) {
            $table->string('barcode', 50)->nullable()->unique()->after('satuan');
            $table->string('foto')->nullable()->after('barcode');
            $table->string('kategori')->nullable()->after('foto');
            $table->unsignedTinyInteger('diskon')->nullable()->after('kategori')->default(0);
            $table->string('bundle')->nullable()->after('diskon');
        });
    }

    public function down(): void
    {
        Schema::table('warung_produk', function (Blueprint $table) {
            $table->dropColumn('barcode');
        });
    }
};