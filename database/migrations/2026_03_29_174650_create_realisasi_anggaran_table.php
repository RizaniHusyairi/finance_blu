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
        Schema::create('realisasi_anggaran', function (Blueprint $table) {
            $table->id();
            $table->foreignId('detail_dipa_id')->constrained('detail_dipas')->restrictOnDelete();
            $table->date('tanggal_pencairan');
            $table->string('nomor_bukti', 100)->unique(); // Misal: nomor kuitansi/SP2D
            $table->decimal('nominal_cair', 15, 2)->default(0);
            $table->text('keterangan')->nullable(); // Misal: "Pembayaran tiket pesawat dinas..."
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('realisasi_anggaran');
    }
};
