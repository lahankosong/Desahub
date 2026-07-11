<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('piutang', function (Blueprint $table) {
            $table->id();
            $table->foreignId('outlet_id')->constrained('outlets')->cascadeOnDelete();
            $table->foreignId('pelanggan_warung_id')->constrained('pelanggan_warung')->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->decimal('jumlah', 12, 2);
            $table->decimal('terbayar', 12, 2)->default(0);
            $table->decimal('sisa', 12, 2)->storedAs('jumlah - terbayar');
            $table->date('jatuh_tempo');
            $table->enum('status', ['aktif', 'lunas', 'gagal_bayar'])->default('aktif');
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->index(['outlet_id', 'status']);
            $table->index('jatuh_tempo');
            $table->index(['pelanggan_warung_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('piutang');
    }
};