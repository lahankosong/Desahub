<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Buat tabel profil untuk skema multi-peran:
     * 1 users -> bisa punya outlet_profiles, konsumen_profiles, kurir_profiles.
     */
    public function up(): void
    {
        // Profil outlet — generik untuk pemilik outlet apapun vertikalnya
        Schema::create('outlet_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('nik')->nullable()->unique();
            $table->string('foto_ktp')->nullable();
            $table->string('foto_selfie_ktp')->nullable();
            $table->timestamps();
        });

        // Profil konsumen — default semua user bisa punya
        Schema::create('konsumen_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('alamat')->nullable();
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->timestamps();
        });

        // Profil kurir — hanya jika user diterima sebagai kurir
        Schema::create('kurir_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->boolean('is_online')->default(false);
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->string('foto_kendaraan')->nullable();
            $table->string('no_plat')->nullable();
            $table->string('jenis_kendaraan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kurir_profiles');
        Schema::dropIfExists('konsumen_profiles');
        Schema::dropIfExists('outlet_profiles');
    }
};