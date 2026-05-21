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
        Schema::create('laporan_utilitas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mitra_jasa_id')->constrained('mitra_jasa')->cascadeOnDelete();
            $table->foreignId('layanan_jasa_id')->constrained('layanan_jasas')->cascadeOnDelete(); // 439 for Air, 440 for Listrik
            $table->enum('jenis', ['listrik', 'air']);
            $table->string('nomor_meter')->nullable(); // Opsional, nomor meteran/ID Pelanggan jika ada
            $table->integer('bulan');
            $table->integer('tahun');
            
            // Data Meteran
            $table->integer('stan_awal')->default(0);
            $table->integer('stan_akhir')->default(0);
            $table->integer('pemakaian')->default(0); // stan_akhir - stan_awal
            
            // Hitungan Biaya
            $table->decimal('tarif_per_unit', 15, 2)->default(0); // per kWh atau per m3
            $table->decimal('total_biaya', 15, 2)->default(0); // pemakaian * tarif
            
            // Status Workflow
            $table->enum('status', ['draft', 'dikirim_ke_admin_jasa', 'ditolak', 'ditagihkan'])->default('draft');
            $table->text('catatan_admin_jasa')->nullable(); // Alasan penolakan dari admin jasa
            
            // Tautan jika sudah jadi tagihan resmi
            $table->foreignId('tagihan_jasa_id')->nullable()->constrained('tagihan_jasas')->nullOnDelete();
            
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            
            // Cegah duplikat laporan di bulan yang sama untuk meteran yang sama
            $table->unique(['mitra_jasa_id', 'layanan_jasa_id', 'bulan', 'tahun'], 'unik_laporan_utilitas');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('laporan_utilitas');
    }
};
