<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabel users — identitas dasar 1 orang: nama, HP, password
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('hp', 20)->unique();
            $table->string('password');
            $table->timestamps();
        });

        // Tabel personal access tokens untuk Sanctum
        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->morphs('tokenable');
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        // Profil outlet — generik untuk pemilik outlet apapun vertikalnya
        // Ekstensi per-vertikal (warung_detail, apotik_detail, dst) nempel ke outlet_profiles,
        // BUKAN ke users (lihat Outlet module)
        Schema::create('outlet_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });

        // Profil konsumen — minimal, satu per user
        Schema::create('konsumen_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('alamat')->nullable();
            $table->timestamps();
        });

        // Profil kurir
        Schema::create('kurir_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('no_kendaraan')->nullable();
            $table->string('tipe_kendaraan')->nullable(); // motor, mobil
            $table->boolean('is_online')->default(false);
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->timestamp('terakhir_online')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kurir_profiles');
        Schema::dropIfExists('konsumen_profiles');
        Schema::dropIfExists('outlet_profiles');
        Schema::dropIfExists('personal_access_tokens');
        Schema::dropIfExists('users');
    }
};