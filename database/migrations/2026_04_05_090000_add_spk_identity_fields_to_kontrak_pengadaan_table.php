<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('kontrak_pengadaan')) {
            return;
        }

        Schema::table('kontrak_pengadaan', function (Blueprint $table) {
            if (! Schema::hasColumn('kontrak_pengadaan', 'nama_ppk')) {
                $table->string('nama_ppk', 150)->nullable()->after('nama_pekerjaan');
            }

            if (! Schema::hasColumn('kontrak_pengadaan', 'nip_ppk')) {
                $table->string('nip_ppk', 50)->nullable()->after('nama_ppk');
            }

            if (! Schema::hasColumn('kontrak_pengadaan', 'nomor_surat_undangan_pengadaan')) {
                $table->string('nomor_surat_undangan_pengadaan', 150)->nullable()->after('nip_ppk');
            }

            if (! Schema::hasColumn('kontrak_pengadaan', 'nomor_ba_hasil_pengadaan')) {
                $table->string('nomor_ba_hasil_pengadaan', 150)->nullable()->after('nomor_surat_undangan_pengadaan');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('kontrak_pengadaan')) {
            return;
        }

        Schema::table('kontrak_pengadaan', function (Blueprint $table) {
            foreach ([
                'nomor_ba_hasil_pengadaan',
                'nomor_surat_undangan_pengadaan',
                'nip_ppk',
                'nama_ppk',
            ] as $column) {
                if (Schema::hasColumn('kontrak_pengadaan', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
