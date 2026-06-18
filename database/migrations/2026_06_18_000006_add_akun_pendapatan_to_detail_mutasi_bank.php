<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Klasifikasi penerimaan: tiap baris rekening koran (detail_mutasi_bank) yang
 * masuk dapat ditandai ke akun pendapatan + jenis layanan — dasar pembentukan
 * baris BKU Penerimaan (Fase 3) dan rekap realisasi per akun (Fase 4).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('detail_mutasi_bank', function (Blueprint $table) {
            if (! Schema::hasColumn('detail_mutasi_bank', 'akun_pendapatan_id')) {
                $table->foreignId('akun_pendapatan_id')->nullable()->after('kategori_mutasi')
                    ->constrained('akun_pendapatan')->nullOnDelete();
            }
            if (! Schema::hasColumn('detail_mutasi_bank', 'layanan_jasa_id')) {
                $table->foreignId('layanan_jasa_id')->nullable()->after('akun_pendapatan_id')
                    ->constrained('layanan_jasas')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('detail_mutasi_bank', function (Blueprint $table) {
            foreach (['layanan_jasa_id', 'akun_pendapatan_id'] as $col) {
                if (Schema::hasColumn('detail_mutasi_bank', $col)) {
                    $table->dropConstrainedForeignId($col);
                }
            }
        });
    }
};
