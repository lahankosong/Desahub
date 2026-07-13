<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('harga_produk_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warung_produk_id')->constrained('warung_produk')->cascadeOnDelete();
            $table->decimal('harga_lama', 15, 2)->nullable();
            $table->decimal('harga_baru', 15, 2);
            $table->foreignId('outlet_id')->constrained('outlets')->cascadeOnDelete();
            $table->timestamp('dicatat_pada')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('harga_produk_history');
    }
};