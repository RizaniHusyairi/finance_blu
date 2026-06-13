<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SP2D perjaldin alur terpadu mencatat realisasi PER KOMPONEN (satu baris per
 * COA) dengan nomor bukti SP2D yang sama — unique constraint pada nomor_bukti
 * membuat komponen kedua gagal (duplicate entry). Guard anti dobel-posting
 * tetap ada di BudgetRealizationService (cek dokumen_sp2d_id TERCATAT).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('realisasi_anggaran', function (Blueprint $table) {
            $table->dropUnique('realisasi_anggaran_nomor_bukti_unique');
            $table->index('nomor_bukti');
        });
    }

    public function down(): void
    {
        Schema::table('realisasi_anggaran', function (Blueprint $table) {
            $table->dropIndex(['nomor_bukti']);
            $table->unique('nomor_bukti');
        });
    }
};
