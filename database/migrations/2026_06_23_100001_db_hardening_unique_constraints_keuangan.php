<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DB hardening — anti-dobel-posting keuangan di level DB (DB-01, DB-08, DB-02).
 *
 * PRA-SYARAT: tidak boleh ada baris duplikat/orphan eksisting (pindai dulu;
 * dev = 0). Di produksi: ambil backup (php artisan db:backup) & bersihkan
 * duplikat sebelum menjalankan migrasi ini.
 */
return new class extends Migration
{
    public function up(): void
    {
        // DB-01: satu komponen (SP2D + item DIPA) tak boleh tercatat realisasi ganda.
        Schema::table('realisasi_anggaran', function (Blueprint $table) {
            $table->unique(['dokumen_sp2d_id', 'dipa_revision_item_id', 'deleted_at'], 'realisasi_sp2d_item_unq');
        });

        // DB-08: lindungi sisi PENERIMAAN BKU (sisi pengeluaran sudah unik).
        Schema::table('buku_kas_umum', function (Blueprint $table) {
            $table->unique(['referensi_penerimaan_id', 'nomor_bukti'], 'bku_ref_penerimaan_nomor_bukti_unq');
        });

        // DB-02: FK riil kode_transaksi (kode_transaksi.kode sudah UNIQUE) + unik jurnal.
        Schema::table('transaksi_pembukuan', function (Blueprint $table) {
            $table->foreign('kode_transaksi', 'transaksi_pembukuan_kode_fk')
                ->references('kode')->on('kode_transaksi')->restrictOnDelete();
            $table->unique(['no_bukti', 'kode_transaksi', 'rekening_bank_id'], 'transaksi_pembukuan_bukti_unq');
        });
    }

    public function down(): void
    {
        Schema::table('realisasi_anggaran', function (Blueprint $table) {
            $table->dropUnique('realisasi_sp2d_item_unq');
        });

        Schema::table('buku_kas_umum', function (Blueprint $table) {
            $table->dropUnique('bku_ref_penerimaan_nomor_bukti_unq');
        });

        Schema::table('transaksi_pembukuan', function (Blueprint $table) {
            $table->dropForeign('transaksi_pembukuan_kode_fk');
            $table->dropUnique('transaksi_pembukuan_bukti_unq');
        });
    }
};
