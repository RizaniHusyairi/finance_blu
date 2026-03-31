<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('kontrak_pengadaan', function (Blueprint $table) {
            // 1. Relasi ke Rincian Anggaran / MAK (Supaya realisasi per item akurat)
            $table->foreignId('detail_dipa_id')->nullable()->after('master_dipa_id')
                  ->constrained('detail_dipas')->nullOnDelete();
            
            // 2. Jangka Waktu Pemeliharaan (Biasanya dalam Hari)
            $table->integer('masa_pemeliharaan_hari')->default(0)->after('jangka_waktu')
                  ->comment('Diambil dari Resume Kontrak poin 11');

            // 3. Ketentuan Sanksi / Denda
            $table->string('ketentuan_sanksi', 255)->nullable()->after('masa_pemeliharaan_hari')
                  ->comment('Diambil dari Resume Kontrak poin 12, misal: 1/1000 per hari');
            
            // Opsi Tambahan: Mata Uang (Default IDR sesuai sheet InputKontrak)
            $table->string('mata_uang', 10)->default('IDR')->after('nilai_total_kontrak');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kontrak_pengadaan', function (Blueprint $table) {
            $table->dropForeign(['detail_dipa_id']);
            $table->dropColumn([
                'detail_dipa_id', 
                'masa_pemeliharaan_hari', 
                'ketentuan_sanksi',
                'mata_uang'
            ]);
        });
    }
};
