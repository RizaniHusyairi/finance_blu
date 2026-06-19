<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Diskriminator untuk mutasi kas "non-pola" di BKU Penerimaan — yang TIDAK
 * berasal dari tagihan jasa (akun_pendapatan_id) maupun jurnal SILABI
 * (kode_transaksi). Dipakai untuk baris manual seperti Pemindahbukuan (PBK),
 * Setor Kas Negara, Pengembalian, Biaya Admin Bank, Bunga, Koreksi.
 *
 * Lihat App\Enums\JenisTransaksiPenerimaan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('buku_kas_umum', function (Blueprint $table) {
            if (! Schema::hasColumn('buku_kas_umum', 'jenis_transaksi')) {
                $table->string('jenis_transaksi', 30)->nullable()->after('kode_transaksi');
            }
        });
    }

    public function down(): void
    {
        Schema::table('buku_kas_umum', function (Blueprint $table) {
            if (Schema::hasColumn('buku_kas_umum', 'jenis_transaksi')) {
                $table->dropColumn('jenis_transaksi');
            }
        });
    }
};
