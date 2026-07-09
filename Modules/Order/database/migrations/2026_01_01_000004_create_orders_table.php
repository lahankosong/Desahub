<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('outlet_id')->constrained('outlets')->cascadeOnDelete();

            // Buyer polymorphic: 'Konsumen' di MVP, 'Outlet' untuk B2B Warung Biasa->Warung Grosir (Fase 2)
            $table->string('buyer_type'); // 'Konsumen' | 'Outlet'
            $table->unsignedBigInteger('buyer_id');

            $table->decimal('total_harga', 12, 2);
            $table->enum('metode_pembayaran', ['cod', 'transfer', 'dp', 'qris'])->default('cod');

            // State machine: dibuat -> diambil_kurir -> diantar -> selesai
            //                dibuat -> dibatalkan
            //                diambil_kurir -> dibatalkan
            //                diantar -> gagal_kirim -> dibatalkan
            $table->enum('status', [
                'dibuat',
                'diambil_kurir',
                'diantar',
                'selesai',
                'dibatalkan',
                'gagal_kirim',
            ])->default('dibuat');

            // Kurir yang klaim order (nullable sampai ada kurir ambil)
            $table->foreignId('kurir_id')->nullable()->constrained('kurir_profiles')->nullOnDelete();

            $table->timestamp('dibuat_pada')->useCurrent();
            $table->timestamp('diambil_pada')->nullable();
            $table->timestamp('diantar_pada')->nullable();
            $table->timestamp('selesai_pada')->nullable();
            $table->timestamp('dibatalkan_pada')->nullable();
            $table->timestamps();

            $table->index(['buyer_type', 'buyer_id']);
            $table->index(['outlet_id', 'status']);
        });

        // Order items — polymorphic sellable, tidak tahu isi produk per vertikal
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->string('sellable_type'); // model vertikal, mis. 'Modules\Warung\app\Models\Produk'
            $table->unsignedBigInteger('sellable_id');
            $table->integer('qty');
            $table->decimal('harga_satuan', 12, 2); // harga hasil resolusi Sellable::getHarga(qty) saat transaksi
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};