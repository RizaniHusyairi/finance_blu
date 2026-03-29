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
        Schema::create('dokumen_spm', function (Blueprint $table) {
            $table->id();
            $table->foreignId('spp_id')->constrained('dokumen_spp')->restrictOnDelete();
            $table->string('nomor_spm', 100)->unique();
            $table->date('tanggal_spm');
            // --- KOLOM FILE ARSIP ---
            $table->string('file_dokumen_spm')->nullable()->comment('Arsip file PDF SPM bertanda tangan');
            $table->foreignId('ppspm_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('disetujui_kasubag_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('waktu_approval_kasubag')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dokumen_spm');
    }
};
