<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Longgarkan batasan satu laporan utilitas per mitra+layanan+periode.
     * Admin Listrik/Air kini boleh mencatat lebih dari satu laporan dalam
     * periode (bulan/tahun) yang sama untuk mitra & layanan yang sama.
     */
    public function up(): void
    {
        Schema::table('laporan_utilitas', function (Blueprint $table) {
            $table->dropUnique('unik_laporan_utilitas');
        });
    }

    public function down(): void
    {
        Schema::table('laporan_utilitas', function (Blueprint $table) {
            $table->unique(['mitra_jasa_id', 'layanan_jasa_id', 'bulan', 'tahun'], 'unik_laporan_utilitas');
        });
    }
};
