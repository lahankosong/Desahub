<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Allow nullable buyer_id (POS walk-in: buyer_type=Umum, buyer_id=null)
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('buyer_id')->nullable()->change();
        });

        // 2. Add metode_pembayaran enum values for POS
        DB::statement("ALTER TABLE orders MODIFY COLUMN metode_pembayaran ENUM('cod', 'transfer', 'dp', 'qris', 'tunai_pos', 'tempo') NOT NULL DEFAULT 'cod'");
    }

    public function down(): void
    {
        // Revert buyer_id to NOT NULL (may fail if null rows exist)
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('buyer_id')->nullable(false)->change();
        });

        // Revert metode_pembayaran
        DB::statement("ALTER TABLE orders MODIFY COLUMN metode_pembayaran ENUM('cod', 'transfer', 'dp', 'qris') NOT NULL DEFAULT 'cod'");
    }
};