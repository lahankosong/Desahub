<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Chat Konsumen-Outlet (Sesi 15).
     *
     * Scope: inbox per pasangan Konsumen-Outlet (bukan per-order),
     * supaya pertanyaan pra-order tetap bisa ditanyakan tanpa perlu order dibuat dulu.
     * Bukan real-time (Livewire butuh koneksi) — polling ringan via refresh.
     *
     * @see docs/last_update.md Sesi 15
     */
    public function up(): void
    {
        Schema::create('percakapan', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('outlet_id');
            $table->unsignedBigInteger('konsumen_id'); // user_id konsumen
            $table->timestamp('dibuat_pada')->useCurrent();
            $table->timestamps();

            $table->foreign('outlet_id')->references('id')->on('outlets')->cascadeOnDelete();
            $table->foreign('konsumen_id')->references('id')->on('users')->cascadeOnDelete();
            $table->unique(['outlet_id', 'konsumen_id']);
            $table->index(['outlet_id', 'konsumen_id']);
        });

        Schema::create('pesan', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('percakapan_id');
            $table->string('pengirim_type'); // 'Konsumen' | 'Outlet'
            $table->unsignedBigInteger('pengirim_id');
            $table->text('isi_pesan');
            $table->timestamp('dikirim_pada')->useCurrent();
            $table->timestamp('dibaca_pada')->nullable();
            $table->timestamps();

            $table->foreign('percakapan_id')->references('id')->on('percakapan')->cascadeOnDelete();
            $table->index(['percakapan_id', 'dikirim_pada']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pesan');
        Schema::dropIfExists('percakapan');
    }
};