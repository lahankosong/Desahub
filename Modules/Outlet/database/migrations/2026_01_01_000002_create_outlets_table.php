<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('outlets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('nama');
            $table->decimal('lat', 10, 7);
            $table->decimal('lng', 10, 7);
            $table->string('alamat')->nullable();
            // level_verifikasi: dasar (OTP HP), terverifikasi (lokasi usaha dicek admin)
            // Prasyarat untuk transaksi Grosir
            $table->enum('level_verifikasi', ['dasar', 'terverifikasi'])->default('dasar');
            $table->timestamps();

            // Index untuk query bounding-box (optimasi Haversine)
            $table->index(['lat', 'lng']);
        });

        // Pivot: 1 outlet bisa punya >1 vertikal (mis. warung + jual pulsa)
        Schema::create('outlet_vertikal', function (Blueprint $table) {
            $table->id();
            $table->foreignId('outlet_id')->constrained('outlets')->cascadeOnDelete();
            $table->enum('vertikal', ['warung', 'apotik', 'warung_makan', 'toko_bangunan', 'toko_pupuk']);
            $table->enum('status', ['aktif', 'nonaktif'])->default('aktif');
            $table->timestamp('aktif_sejak')->useCurrent();
            $table->timestamps();

            // Unique: satu outlet tidak boleh punya baris ganda untuk vertikal yang sama
            $table->unique(['outlet_id', 'vertikal']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outlet_vertikal');
        Schema::dropIfExists('outlets');
    }
};