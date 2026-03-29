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
        Schema::create('potongan_tagihan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tagihan_id')->constrained('tagihan')->cascadeOnDelete();
            $table->foreignId('pajak_id')->nullable()->constrained('master_tarif_pajak')->restrictOnDelete();
            $table->foreignId('akun_potongan_id')->nullable()->constrained('master_coas')->restrictOnDelete();
            $table->string('jenis_potongan', 50); 
            $table->string('deskripsi');
            $table->decimal('dpp', 15, 2)->default(0);
            $table->decimal('nominal_potongan', 15, 2);
            $table->string('kode_billing', 50)->nullable();
            $table->string('ntpn', 50)->nullable();
            // --- KOLOM FILE DOKUMEN PAJAK ---
            $table->string('file_faktur_pajak')->nullable()->comment('Arsip Faktur Pajak dari Vendor');
            $table->string('file_bukti_setor_pajak')->nullable()->comment('Arsip BPN / SSP / Bukti Potong');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('potongan_tagihan');
    }
};
