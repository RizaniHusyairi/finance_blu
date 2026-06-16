<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tagihan_jasa_payment_proofs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tagihan_jasa_id')->constrained('tagihan_jasas')->cascadeOnDelete();
            $table->foreignId('mitra_jasa_id')->nullable()->constrained('mitra_jasa')->nullOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('tanggal_bayar');
            $table->decimal('nominal_bayar', 20, 2);
            $table->string('bank_pengirim', 100)->nullable();
            $table->string('nomor_referensi', 150)->nullable();
            $table->string('file_path');
            $table->string('original_name')->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('status', 40)->default('MENUNGGU_VERIFIKASI');
            $table->text('catatan_mitra')->nullable();
            $table->text('catatan_verifikator')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tagihan_jasa_id', 'status']);
            $table->index(['nomor_referensi']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tagihan_jasa_payment_proofs');
    }
};
