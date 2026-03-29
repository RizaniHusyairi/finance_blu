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
        Schema::create('detail_kontrak', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tagihan_id')->constrained('tagihan')->cascadeOnDelete();
            $table->foreignId('kontrak_termin_id')->constrained('kontrak_termin')->restrictOnDelete();
            $table->string('nomor_bapp', 100)->nullable()->comment('Berita Acara Pemeriksaan Pekerjaan');
            $table->date('tanggal_bapp')->nullable();
            $table->string('nomor_bast', 100)->nullable()->comment('Berita Acara Serah Terima');
            $table->date('tanggal_bast')->nullable();
            $table->string('nomor_bap', 100)->nullable()->comment('Berita Acara Pembayaran');
            $table->date('tanggal_bap')->nullable();
            // --- KOLOM FILE DOKUMEN PENAGIHAN ---
            $table->string('file_bapp')->nullable()->comment('Arsip file BAPP');
            $table->string('file_bast')->nullable()->comment('Arsip file BAST');
            $table->string('file_bap')->nullable()->comment('Arsip file BAP');
            $table->string('file_invoice')->nullable()->comment('Arsip Surat Permohonan Pembayaran');
            $table->string('file_kwitansi')->nullable()->comment('Arsip Kwitansi bermaterai');
            $table->string('file_lampiran_lainnya')->nullable()->comment('File pendukung lainnya dalam bentuk ZIP/PDF');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detail_kontrak');
    }
};
