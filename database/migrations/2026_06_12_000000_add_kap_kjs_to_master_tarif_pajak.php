<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kode Akun Pajak (KAP, 6 digit) & Kode Jenis Setoran (KJS, 3 digit)
 * untuk pembuatan kode billing/SSP, mengikuti kalkulator pajak Subbag Keuangan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('master_tarif_pajak', function (Blueprint $table) {
            $table->string('kode_akun_pajak', 10)->nullable()->after('persentase');
            $table->string('kode_jenis_setoran', 5)->nullable()->after('kode_akun_pajak');
        });
    }

    public function down(): void
    {
        Schema::table('master_tarif_pajak', function (Blueprint $table) {
            $table->dropColumn(['kode_akun_pajak', 'kode_jenis_setoran']);
        });
    }
};
