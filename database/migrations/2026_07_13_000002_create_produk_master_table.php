<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('produk_master', function (Blueprint $table) {
            $table->id();
            $table->string('barcode', 50)->unique();
            $table->string('nama');
            $table->string('varian')->nullable();
            $table->decimal('netto', 10, 2)->nullable()->comment('dalam gram/ml');
            $table->text('deskripsi')->nullable();
            $table->string('foto')->nullable();
            $table->decimal('het', 15, 2)->nullable()->comment('Harga Eceran Tertinggi (referensi)');
            $table->foreignId('kategori_id')->nullable()->constrained('kategoris')->nullOnDelete();
            $table->foreignId('created_by_outlet_id')->nullable()->constrained('outlets')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('produk_master');
    }
};