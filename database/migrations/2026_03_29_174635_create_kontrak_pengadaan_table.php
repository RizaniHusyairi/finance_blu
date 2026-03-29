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
        Schema::create('kontrak_pengadaan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('master_mitra_vendor')->restrictOnDelete();
            $table->foreignId('master_dipa_id')->constrained('master_dipas')->restrictOnDelete();
            $table->string('nomor_spk', 100)->unique();
            $table->date('tanggal_spk');
            $table->text('nama_pekerjaan');
            $table->decimal('nilai_total_kontrak', 15, 2);
            $table->enum('metode_pembayaran', ['LUMPSUM', 'TERMIN']);
            $table->boolean('ada_uang_muka')->default(false);
            $table->decimal('nilai_uang_muka', 15, 2)->default(0);
            $table->decimal('sisa_uang_muka_belum_lunas', 15, 2)->default(0);
            $table->integer('jangka_waktu');
            $table->enum('satuan_waktu', ['HARI', 'MINGGU', 'BULAN']);
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');
            $table->enum('status_kontrak', ['AKTIF', 'SELESAI', 'DIBATALKAN'])->default('AKTIF');
            // --- KOLOM FILE DOKUMEN KONTRAK AWAL ---
            $table->string('file_spk')->nullable()->comment('Arsip Surat Perintah Kerja');
            $table->string('file_spmk')->nullable()->comment('Arsip Surat Perintah Mulai Kerja');
            $table->string('file_ringkasan_kontrak')->nullable()->comment('Arsip Ringkasan Kontrak');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kontrak_pengadaan');
    }
};
