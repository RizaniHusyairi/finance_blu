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
        Schema::create('dokumen_spp', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tagihan_id')->constrained('tagihan')->restrictOnDelete();
            $table->foreignId('detail_dipa_id')->nullable()->constrained('detail_dipas')->restrictOnDelete();
            $table->string('kategori_pembayaran', 50); 
            $table->decimal('nominal_spp', 15, 2);
            $table->string('nomor_spp', 100)->unique();
            $table->date('tanggal_spp');
            // --- KOLOM FILE ARSIP ---
            $table->string('file_dokumen_spp')->nullable()->comment('Arsip file PDF SPP bertanda tangan');
            $table->string('status', 50)->default('DRAFT'); 
            $table->foreignId('dibuat_oleh_id')->constrained('users')->restrictOnDelete();
            // Gerbang Verifikasi SPP
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
        Schema::dropIfExists('dokumen_spp');
    }
};
