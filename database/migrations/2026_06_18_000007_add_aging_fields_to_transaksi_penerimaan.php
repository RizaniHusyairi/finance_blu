<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Register piutang ber-aging (sheet `Februari` File 2): kualitas piutang,
 * persentase & nominal penyisihan, tanggal bayar, dan catatan validasi.
 *
 * Kolom yang sudah ada (lihat create_transaksi_tagihan_v2): nominal_tagihan,
 * nominal_denda_keterlambatan, total_dibayar, tanggal_jatuh_tempo, status_pembayaran.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transaksi_penerimaan', function (Blueprint $table) {
            if (! Schema::hasColumn('transaksi_penerimaan', 'kualitas')) {
                // LANCAR | KURANG_LANCAR | DIRAGUKAN | MACET
                $table->string('kualitas', 20)->nullable()->after('status_pembayaran');
            }
            if (! Schema::hasColumn('transaksi_penerimaan', 'persentase_penyisihan')) {
                $table->decimal('persentase_penyisihan', 5, 2)->default(0)->after('kualitas');
            }
            if (! Schema::hasColumn('transaksi_penerimaan', 'penyisihan')) {
                $table->decimal('penyisihan', 18, 2)->default(0)->after('persentase_penyisihan');
            }
            if (! Schema::hasColumn('transaksi_penerimaan', 'tanggal_bayar')) {
                $table->date('tanggal_bayar')->nullable()->after('penyisihan');
            }
            if (! Schema::hasColumn('transaksi_penerimaan', 'catatan_validasi')) {
                $table->text('catatan_validasi')->nullable()->after('tanggal_bayar');
            }
        });
    }

    public function down(): void
    {
        Schema::table('transaksi_penerimaan', function (Blueprint $table) {
            foreach (['catatan_validasi', 'tanggal_bayar', 'penyisihan', 'persentase_penyisihan', 'kualitas'] as $col) {
                if (Schema::hasColumn('transaksi_penerimaan', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
