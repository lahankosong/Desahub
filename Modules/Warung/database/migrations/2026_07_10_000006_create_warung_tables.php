<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Ekstensi vertikal Warung — detail per-outlet
        Schema::create('warung_detail', function (Blueprint $table) {
            $table->id();
            $table->foreignId('outlet_id')->constrained('outlets')->cascadeOnDelete();
            $table->time('jam_buka')->nullable();
            $table->time('jam_tutup')->nullable();
            $table->string('kategori_warung')->nullable(); // sembako, kelontong, dll
            // tier: field CACHE, sumber kebenaran di warung_grosir_approvals
            $table->enum('tier', ['biasa', 'grosir'])->default('biasa');
            $table->timestamps();
        });

        // Produk Warung — implementasi kontrak Sellable
        Schema::create('warung_produk', function (Blueprint $table) {
            $table->id();
            $table->foreignId('outlet_id')->constrained('outlets')->cascadeOnDelete();
            $table->string('nama');
            $table->decimal('harga', 12, 2);
            $table->string('satuan')->default('pcs'); // pcs, kg, sak, dll
            $table->text('deskripsi')->nullable();
            $table->string('barcode')->nullable()->index();
            $table->timestamps();
        });

        // Log approval Warung Grosir — append-only, sumber kebenaran tier
        Schema::create('warung_grosir_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('outlet_id')->constrained('outlets')->cascadeOnDelete();
            $table->foreignId('sales_id')->nullable()->comment('FK sales_profiles, nullable karena belum ada module Sales');
            $table->enum('jenis', ['disetujui', 'dicabut']);
            $table->text('catatan')->nullable();
            $table->timestamp('terjadi_pada')->useCurrent();
            $table->timestamps();

            // Index untuk query "entri terbaru" penentu status tier
            $table->index(['outlet_id', 'terjadi_pada']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warung_grosir_approvals');
        Schema::dropIfExists('warung_produk');
        Schema::dropIfExists('warung_detail');
    }
};