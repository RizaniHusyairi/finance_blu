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
        Schema::create('riwayat_revisi_dipa', function (Blueprint $table) {
            $table->id();
            $table->foreignId('master_dipa_id')->constrained('master_dipas')->cascadeOnDelete();
            $table->integer('nomor_revisi')->default(0);
            $table->date('tanggal_revisi');
            $table->decimal('pagu_sebelumnya', 15, 2);
            $table->decimal('pagu_baru', 15, 2);
            $table->string('file_dokumen_dipa')->nullable();
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('riwayat_revisi_dipa');
    }
};
