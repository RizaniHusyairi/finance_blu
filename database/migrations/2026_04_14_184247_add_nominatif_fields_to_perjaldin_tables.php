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
        // Tambahkan field di tabel tagihan
        Schema::table('tagihan', function (Blueprint $table) {
            $table->unsignedTinyInteger('periode_bulan')->nullable()->after('tanggal_perjaldin');
            $table->unsignedSmallInteger('periode_tahun')->nullable()->after('periode_bulan');
            $table->string('kota_ttd', 100)->nullable()->default('Samarinda')->after('periode_tahun');
            $table->date('tanggal_ttd')->nullable()->after('kota_ttd');
            
            $table->foreignId('ppk_user_id')->nullable()->constrained('users')->nullOnDelete()->after('tanggal_ttd');
            $table->string('ppk_nama_snapshot', 150)->nullable()->after('ppk_user_id');
            $table->string('ppk_nip_snapshot', 100)->nullable()->after('ppk_nama_snapshot');
            
            $table->foreignId('bendahara_pengeluaran_user_id')->nullable()->constrained('users')->nullOnDelete()->after('ppk_nip_snapshot');
            $table->string('bendahara_pengeluaran_nama_snapshot', 150)->nullable()->after('bendahara_pengeluaran_user_id');
            $table->string('bendahara_pengeluaran_nip_snapshot', 100)->nullable()->after('bendahara_pengeluaran_nama_snapshot');
        });

        // Tambahkan field di tabel detail_perjaldin
        Schema::table('detail_perjaldin', function (Blueprint $table) {
            if (!Schema::hasColumn('detail_perjaldin', 'no_sppd')) {
                $table->string('no_sppd', 100)->nullable()->after('no_spt');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('detail_perjaldin', function (Blueprint $table) {
            if (Schema::hasColumn('detail_perjaldin', 'no_sppd')) {
                $table->dropColumn('no_sppd');
            }
        });

        Schema::table('tagihan', function (Blueprint $table) {
            $table->dropForeign(['ppk_user_id']);
            $table->dropForeign(['bendahara_pengeluaran_user_id']);
            $table->dropColumn([
                'periode_bulan',
                'periode_tahun',
                'kota_ttd',
                'tanggal_ttd',
                'ppk_user_id',
                'ppk_nama_snapshot',
                'ppk_nip_snapshot',
                'bendahara_pengeluaran_user_id',
                'bendahara_pengeluaran_nama_snapshot',
                'bendahara_pengeluaran_nip_snapshot',
            ]);
        });
    }
};
