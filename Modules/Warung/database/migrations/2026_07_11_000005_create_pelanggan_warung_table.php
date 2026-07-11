<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pelanggan_warung', function (Blueprint $table) {
            $table->id();
            $table->foreignId('outlet_id')->constrained('outlets')->cascadeOnDelete();
            $table->string('nama', 200);
            $table->string('no_hp', 20)->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->index(['outlet_id', 'nama']);
            $table->index(['outlet_id', 'no_hp']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pelanggan_warung');
    }
};