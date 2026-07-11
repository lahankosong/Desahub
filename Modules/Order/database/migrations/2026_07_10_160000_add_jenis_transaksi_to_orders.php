<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->enum('jenis_transaksi', ['online', 'pos'])->default('online')->after('metode_pembayaran');
            $table->enum('metode_pengiriman', ['diantar_kurir', 'ambil_sendiri'])->nullable()->after('jenis_transaksi');
            $table->text('alamat_antar')->nullable()->after('metode_pengiriman');
            $table->text('catatan')->nullable()->after('alamat_antar');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['jenis_transaksi', 'metode_pengiriman', 'alamat_antar', 'catatan']);
        });
    }
};