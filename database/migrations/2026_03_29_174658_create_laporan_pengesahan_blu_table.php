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
        Schema::create('laporan_pengesahan_blu', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_laporan', 100)->unique();
            $table->integer('periode_bulan'); 
            $table->year('tahun');
            $table->decimal('total_penerimaan', 15, 2)->default(0);
            $table->decimal('total_pengeluaran', 15, 2)->default(0);
            $table->decimal('saldo_akhir_blu', 15, 2);
            $table->string('file_dokumen_sakti')->nullable();
            $table->enum('status_pengesahan', ['DRAFT', 'VERIFIKASI_KPPN', 'DISAHKAN'])->default('DRAFT');
            $table->string('status_sp3b', 50)->nullable(); // SP3B status untuk tracking
            $table->foreignId('disetujui_kpa_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('laporan_pengesahan_blu');
    }
};
