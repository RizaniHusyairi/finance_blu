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
        Schema::create('detail_honorarium', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tagihan_id')->constrained('tagihan')->cascadeOnDelete();
            $table->foreignId('personel_id')->constrained('master_personel_eksternal')->restrictOnDelete();
            $table->decimal('nilai_honor', 15, 2);
            $table->decimal('pph', 15, 2);
            $table->string('rekening', 100);
            $table->string('jenis_bank', 50);
            $table->string('nama_rekening', 100);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detail_honorarium');
    }
};
