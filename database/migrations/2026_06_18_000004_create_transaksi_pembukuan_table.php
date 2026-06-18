<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Jurnal transaksi tunggal Bendahara Pengeluaran — setara sheet `Input_Transaksi`
 * File 1. Satu baris berisi kode transaksi (A…R2); PostingPembukuanService
 * mendistribusikannya ke BKU + buku pembantu sesuai kode_transaksi.posting_rules.
 *
 * Mirror kolom Input_Transaksi A:N (Tanggal, No.Bkt, Kode, Uraian, Penerima,
 * Keg/Output/Akun, CP (UP/LS/GU), Pungut/Setor Pajak, Jumlah Kotor, Unsur Pajak).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('transaksi_pembukuan')) {
            Schema::create('transaksi_pembukuan', function (Blueprint $table) {
                $table->id();
                $table->date('tanggal');
                $table->string('no_bukti', 100);
                $table->string('kode_transaksi', 8); // FK logis ke kode_transaksi.kode
                $table->text('uraian');
                $table->foreignId('penerima_id')->nullable()->constrained('master_pihak')->nullOnDelete();
                $table->foreignId('keg_output_akun_id')->nullable()->constrained('master_coas')->nullOnDelete();
                $table->string('cara_pembayaran', 10)->nullable(); // UP | TU | GU | LS
                $table->string('pungut_setor_pajak', 20)->nullable();
                $table->decimal('jumlah_kotor', 18, 2)->default(0);
                $table->decimal('unsur_pajak_ppn', 18, 2)->default(0);
                $table->decimal('unsur_pajak_pph', 18, 2)->default(0);
                $table->foreignId('rekening_bank_id')->nullable()->constrained('rekening_bank')->nullOnDelete();
                // Tautan opsional ke dokumen sumber (Tagihan/SP2D) bila digenerate dari workflow.
                $table->nullableMorphs('referensi');
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->softDeletes();
                $table->timestamps();

                $table->index(['tanggal', 'kode_transaksi']);
                $table->index('no_bukti');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('transaksi_pembukuan');
    }
};
