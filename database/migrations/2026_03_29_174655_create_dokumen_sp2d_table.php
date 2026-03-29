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
        Schema::create('dokumen_sp2d', function (Blueprint $table) {
            $table->id();
            $table->foreignId('npi_id')->constrained('dokumen_npi')->restrictOnDelete();
            $table->string('nomor_sp2d', 100)->unique();
            $table->date('tanggal_sp2d');
            // --- KOLOM FILE ARSIP ---
            $table->string('file_dokumen_sp2d')->nullable()->comment('Arsip file PDF SP2D bertanda tangan');
            $table->string('bukti_transfer_bank')->nullable()->comment('Bisa berupa foto struk atau PDF dari bank');
            $table->foreignId('bendahara_pengeluaran_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('disetujui_kasubag_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('waktu_approval_kasubag')->nullable();
            $table->foreignId('disetujui_ppk_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('waktu_approval_ppk')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dokumen_sp2d');
    }
};
