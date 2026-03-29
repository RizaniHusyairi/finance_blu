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
        Schema::create('kontrak_termin', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kontrak_pengadaan_id')->constrained('kontrak_pengadaan')->cascadeOnDelete();
            $table->enum('jenis_termin', ['UANG_MUKA', 'PROGRESS', 'PELUNASAN', 'RETENSI']);
            $table->integer('termin_ke');
            $table->string('keterangan_termin');
            $table->decimal('persentase', 5, 2);
            $table->decimal('nilai_bruto_termin', 15, 2);
            $table->decimal('potongan_angsuran_uang_muka', 15, 2)->default(0);
            $table->decimal('nilai_retensi', 15, 2)->default(0);
            $table->enum('status_termin', ['LOCKED', 'READY_TO_BILL', 'SUDAH_DITAGIH'])->default('LOCKED');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kontrak_termin');
    }
};
