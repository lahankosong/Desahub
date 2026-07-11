<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('outlets', function (Blueprint $table) {
            $table->string('provinsi')->nullable()->after('alamat');
            $table->string('kabupaten')->nullable()->after('provinsi');
            $table->string('kecamatan')->nullable()->after('kabupaten');
            $table->string('desa_kelurahan')->nullable()->after('kecamatan');
            $table->string('rt')->nullable()->after('desa_kelurahan');
            $table->string('rw')->nullable()->after('rt');
            $table->string('kode_pos', 10)->nullable()->after('rw');
            // GPS
            $table->decimal('lat', 10, 7)->nullable()->change();
            $table->decimal('lng', 10, 7)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('outlets', function (Blueprint $table) {
            $table->dropColumn(['provinsi', 'kabupaten', 'kecamatan', 'desa_kelurahan', 'rt', 'rw', 'kode_pos']);
        });
    }
};