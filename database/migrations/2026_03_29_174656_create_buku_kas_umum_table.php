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
        Schema::create('buku_kas_umum', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal_transaksi');
            $table->string('nomor_bukti', 100);
            $table->text('uraian');
            $table->enum('arus_kas', ['DEBIT_MASUK', 'KREDIT_KELUAR']);
            $table->decimal('nominal', 15, 2);
            $table->decimal('saldo_akhir', 15, 2); 
            $table->foreignId('sumber_rekening_id')->constrained('rekening_bank')->restrictOnDelete();
            $table->foreignId('referensi_pengeluaran_id')->nullable()->constrained('tagihan')->nullOnDelete();
            $table->foreignId('referensi_penerimaan_id')->nullable()->constrained('transaksi_penerimaan')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('buku_kas_umum');
    }
};
