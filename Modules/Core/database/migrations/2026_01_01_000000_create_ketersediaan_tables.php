<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Log append-only pergerakan ketersediaan — sumber kebenaran stok
        // Pola: tidak ada UPDATE langsung kolom stok di tabel produk.
        // Nilai akhir = SUM(jumlah_perubahan) dari seluruh log.
        Schema::create('ketersediaan_movements', function (Blueprint $table) {
            $table->id();
            $table->string('sellable_type'); // model vertikal, mis. 'Modules\Warung\app\Models\Produk'
            $table->unsignedBigInteger('sellable_id');
            $table->unsignedBigInteger('outlet_id');
            $table->integer('jumlah_perubahan'); // positif (restock) / negatif (penjualan)
            $table->enum('alasan', ['penjualan', 'restock', 'koreksi', 'toggle_status', 'pembatalan']);
            $table->unsignedBigInteger('referensi_id')->nullable(); // mis. order_id
            $table->timestamp('terjadi_pada');
            $table->timestamps();

            $table->index(['sellable_type', 'sellable_id']);
            $table->index('outlet_id');
        });

        // Cache stok untuk operasi atomik bergerbang (race condition protection)
        // Melengkapi (bukan mengganti) log ketersediaan_movements
        Schema::create('ketersediaan_cache', function (Blueprint $table) {
            $table->id();
            $table->string('sellable_type');
            $table->unsignedBigInteger('sellable_id');
            $table->integer('qty')->default(0);

            $table->unique(['sellable_type', 'sellable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ketersediaan_cache');
        Schema::dropIfExists('ketersediaan_movements');
    }
};