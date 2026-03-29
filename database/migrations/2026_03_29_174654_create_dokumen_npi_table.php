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
        Schema::create('dokumen_npi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('spm_id')->constrained('dokumen_spm')->restrictOnDelete();
            $table->string('nomor_npi', 100)->unique();
            $table->date('tanggal_npi');
            // --- KOLOM FILE ARSIP ---
            $table->string('file_dokumen_npi')->nullable()->comment('Arsip file PDF NPI bertanda tangan');
            $table->foreignId('bendahara_penerimaan_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('disetujui_kasubag_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('waktu_approval_kasubag')->nullable();
            $table->foreignId('disetujui_ppk_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('waktu_approval_ppk')->nullable();
            $table->foreignId('disetujui_bend_pengeluaran_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('waktu_approval_bend_pengeluaran')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dokumen_npi');
    }
};
