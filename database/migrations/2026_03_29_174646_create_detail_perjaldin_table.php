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
        Schema::create('detail_perjaldin', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tagihan_id')->constrained('tagihan')->cascadeOnDelete();
            $table->foreignId('pegawai_id')->constrained('master_pegawai')->restrictOnDelete();
            $table->string('no_spt', 100);
            $table->string('tujuan', 100);
            $table->string('rekening', 100);
            $table->date('tgl_berangkat');
            $table->integer('lama_hari');
            $table->decimal('biaya_tiket', 15, 2)->default(0);
            $table->decimal('biaya_transport', 15, 2)->default(0);
            $table->decimal('biaya_penginapan', 15, 2)->default(0);
            $table->decimal('uang_harian', 15, 2)->default(0);
            $table->decimal('uang_representasi', 15, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detail_perjaldin');
    }
};
