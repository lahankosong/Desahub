<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Provinces
        Schema::create('wilayah_provinsi', function (Blueprint $table) {
            $table->string('kode', 2)->primary();
            $table->string('nama', 100);
        });

        // Regencies/Cities
        Schema::create('wilayah_kabupaten', function (Blueprint $table) {
            $table->string('kode', 5)->primary();
            $table->string('provinsi_kode', 2);
            $table->string('nama', 100);
            $table->foreign('provinsi_kode')->references('kode')->on('wilayah_provinsi')->cascadeOnDelete();
            $table->index('provinsi_kode');
        });

        // Districts
        Schema::create('wilayah_kecamatan', function (Blueprint $table) {
            $table->string('kode', 8)->primary();
            $table->string('kabupaten_kode', 5);
            $table->string('nama', 100);
            $table->foreign('kabupaten_kode')->references('kode')->on('wilayah_kabupaten')->cascadeOnDelete();
            $table->index('kabupaten_kode');
        });

        // Villages
        Schema::create('wilayah_desa', function (Blueprint $table) {
            $table->string('kode', 13)->primary();
            $table->string('kecamatan_kode', 8);
            $table->string('nama', 100);
            $table->foreign('kecamatan_kode')->references('kode')->on('wilayah_kecamatan')->cascadeOnDelete();
            $table->index('kecamatan_kode');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wilayah_desa');
        Schema::dropIfExists('wilayah_kecamatan');
        Schema::dropIfExists('wilayah_kabupaten');
        Schema::dropIfExists('wilayah_provinsi');
    }
};