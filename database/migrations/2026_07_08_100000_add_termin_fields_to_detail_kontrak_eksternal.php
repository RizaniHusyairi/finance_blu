<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tambah konsep TERMIN / LUMPSUM / UANG MUKA pada tagihan kontrak eksternal.
 * Satu Surat Pesanan bisa menghasilkan beberapa tagihan termin; tiap detail
 * membawa ringkasan kontrak (metode, nilai total, uang muka) + data termin-nya
 * sendiri (jenis, persentase, potongan angsuran uang muka, retensi).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('detail_kontrak_eksternal', function (Blueprint $table) {
            // Ringkasan kontrak (diduplikasi ke tiap tagihan termin).
            $table->string('metode_pembayaran', 10)->default('LUMPSUM')->after('nama_pekerjaan');
            $table->decimal('nilai_total_kontrak', 18, 2)->nullable()->after('metode_pembayaran');
            $table->boolean('ada_uang_muka')->default(false)->after('nilai_total_kontrak');
            $table->decimal('nilai_uang_muka', 18, 2)->default(0)->after('ada_uang_muka');

            // Data termin milik detail/tagihan ini.
            $table->string('jenis_termin', 12)->nullable()->after('total_termin');
            $table->decimal('persentase', 8, 4)->default(100)->after('jenis_termin');
            $table->string('keterangan_termin', 150)->nullable()->after('persentase');
            $table->decimal('potongan_angsuran_uang_muka', 18, 2)->default(0)->after('keterangan_termin');
            $table->decimal('nilai_retensi', 18, 2)->default(0)->after('potongan_angsuran_uang_muka');
        });
    }

    public function down(): void
    {
        Schema::table('detail_kontrak_eksternal', function (Blueprint $table) {
            $table->dropColumn([
                'metode_pembayaran',
                'nilai_total_kontrak',
                'ada_uang_muka',
                'nilai_uang_muka',
                'jenis_termin',
                'persentase',
                'keterangan_termin',
                'potongan_angsuran_uang_muka',
                'nilai_retensi',
            ]);
        });
    }
};
