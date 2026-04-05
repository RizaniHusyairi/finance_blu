<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kontrak_pengadaan', function (Blueprint $table) {
            if (!Schema::hasColumn('kontrak_pengadaan', 'nomor_spmk')) {
                $table->string('nomor_spmk', 150)->nullable()->after('tanggal_spk');
            }

            if (!Schema::hasColumn('kontrak_pengadaan', 'tanggal_spmk')) {
                $table->date('tanggal_spmk')->nullable()->after('nomor_spmk');
            }
        });

        Schema::table('master_pihak', function (Blueprint $table) {
            if (!Schema::hasColumn('master_pihak', 'jabatan_penandatangan')) {
                $table->string('jabatan_penandatangan', 150)->nullable()->after('nama_penanggung_jawab');
            }
        });
    }

    public function down(): void
    {
        Schema::table('kontrak_pengadaan', function (Blueprint $table) {
            if (Schema::hasColumn('kontrak_pengadaan', 'tanggal_spmk')) {
                $table->dropColumn('tanggal_spmk');
            }

            if (Schema::hasColumn('kontrak_pengadaan', 'nomor_spmk')) {
                $table->dropColumn('nomor_spmk');
            }
        });

        Schema::table('master_pihak', function (Blueprint $table) {
            if (Schema::hasColumn('master_pihak', 'jabatan_penandatangan')) {
                $table->dropColumn('jabatan_penandatangan');
            }
        });
    }
};
