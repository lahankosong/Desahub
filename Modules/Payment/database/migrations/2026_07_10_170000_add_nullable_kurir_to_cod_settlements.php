<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cod_settlements', function (Blueprint $table) {
            $table->foreignId('kurir_id')->nullable()->change();
            $table->string('dicatat_oleh', 50)->default('warung')->after('status_setor'); // warung/kurir/admin
        });
    }

    public function down(): void
    {
        Schema::table('cod_settlements', function (Blueprint $table) {
            $table->foreignId('kurir_id')->nullable(false)->change();
            $table->dropColumn('dicatat_oleh');
        });
    }
};