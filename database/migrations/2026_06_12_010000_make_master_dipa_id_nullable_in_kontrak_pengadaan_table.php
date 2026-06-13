<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * COA (DIPA) tidak lagi dipilih saat pembuatan kontrak — pembebanan
     * anggaran dilakukan PPK di halaman Proses Tagihan setelah tagihan
     * disetujui seluruh verifikator.
     */
    public function up(): void
    {
        Schema::table('kontrak_pengadaan', function (Blueprint $table) {
            $table->unsignedBigInteger('master_dipa_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kontrak_pengadaan', function (Blueprint $table) {
            $table->unsignedBigInteger('master_dipa_id')->nullable(false)->change();
        });
    }
};
