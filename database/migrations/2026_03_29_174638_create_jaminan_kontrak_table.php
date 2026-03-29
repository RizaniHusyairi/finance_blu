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
        Schema::create('jaminan_kontrak', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kontrak_pengadaan_id')->constrained('kontrak_pengadaan')->cascadeOnDelete();
            $table->enum('jenis_jaminan', ['UANG_MUKA', 'PELAKSANAAN', 'PEMELIHARAAN']);
            $table->string('penjamin', 150);
            $table->string('nomor_jaminan', 100);
            $table->date('tanggal_jaminan');
            $table->integer('masa_berlaku_hari');
            $table->date('tanggal_mulai_jaminan');
            $table->date('tanggal_selesai_jaminan');
            $table->decimal('nilai_jaminan', 15, 2);
            $table->string('file_jaminan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jaminan_kontrak');
    }
};
