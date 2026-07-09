<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // COD settlements — append-only, catat uang tunai yang dipegang kurir
        // Penyetoran dicatat manual admin untuk MVP (belum otomatis)
        Schema::create('cod_settlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('kurir_id')->constrained('kurir_profiles')->cascadeOnDelete();
            $table->decimal('jumlah_diterima', 12, 2);
            $table->enum('status_setor', ['belum_disetor', 'sudah_disetor'])->default('belum_disetor');
            $table->timestamp('dicatat_pada')->useCurrent();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cod_settlements');
    }
};