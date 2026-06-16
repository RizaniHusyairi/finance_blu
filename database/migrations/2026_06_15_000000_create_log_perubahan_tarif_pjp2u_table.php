<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('log_perubahan_tarif_pjp2u')) {
            return;
        }

        Schema::create('log_perubahan_tarif_pjp2u', function (Blueprint $table) {
            $table->id();
            $table->foreignId('layanan_jasa_id')->constrained('layanan_jasas')->cascadeOnDelete();
            $table->decimal('tarif_lama', 18, 2)->default(0);
            $table->decimal('tarif_baru', 18, 2)->default(0);
            // revisi_resmi | diskon | koreksi
            $table->string('tipe_perubahan', 20);
            $table->date('berlaku_mulai');
            $table->date('berlaku_sampai')->nullable();
            $table->string('nomor_referensi', 100)->nullable();
            $table->text('alasan');
            $table->string('file_pendukung')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['layanan_jasa_id', 'berlaku_mulai'], 'log_tarif_pjp2u_layanan_mulai_idx');
            $table->index('tipe_perubahan', 'log_tarif_pjp2u_tipe_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('log_perubahan_tarif_pjp2u');
    }
};
