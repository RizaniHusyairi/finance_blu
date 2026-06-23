<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DB-03 + DB-04 — laporan_utilitas:
 *  - tipe meteran integer → decimal(18,3) (tampung m³ desimal & cegah overflow),
 *  - cegah duplikasi laporan per periode dengan pembeda nomor_meter.
 *
 * Catatan: pada MySQL, baris dengan nomor_meter NULL tidak dianggap duplikat
 * oleh unique. Agar proteksi penuh, jadikan nomor_meter wajib di sisi aplikasi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('laporan_utilitas', function (Blueprint $table) {
            $table->decimal('stan_awal', 18, 3)->default(0)->change();
            $table->decimal('stan_akhir', 18, 3)->default(0)->change();
            $table->decimal('pemakaian', 18, 3)->default(0)->change();
        });

        Schema::table('laporan_utilitas', function (Blueprint $table) {
            $table->unique(
                ['mitra_jasa_id', 'layanan_jasa_id', 'nomor_meter', 'bulan', 'tahun'],
                'unik_laporan_utilitas_v2'
            );
        });
    }

    public function down(): void
    {
        Schema::table('laporan_utilitas', function (Blueprint $table) {
            $table->dropUnique('unik_laporan_utilitas_v2');
        });

        Schema::table('laporan_utilitas', function (Blueprint $table) {
            $table->integer('stan_awal')->default(0)->change();
            $table->integer('stan_akhir')->default(0)->change();
            $table->integer('pemakaian')->default(0)->change();
        });
    }
};
