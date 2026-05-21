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
        Schema::create('standing_instructions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dokumen_spp_id')->constrained('dokumen_spp')->onDelete('cascade');
            $table->string('nomor_surat')->nullable();
            $table->date('tanggal_surat')->nullable();
            $table->foreignId('ppk_user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('kpa_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('nama_ppk_snapshot')->nullable();
            $table->string('jabatan_ppk_snapshot')->nullable();
            $table->string('nama_kpa_snapshot')->nullable();
            $table->string('jabatan_kpa_snapshot')->nullable();
            $table->string('rekening_sumber_nomor')->nullable();
            $table->string('rekening_sumber_nama')->nullable();
            $table->string('rekening_sumber_bank')->nullable();
            $table->string('rekening_tujuan_nomor')->nullable();
            $table->string('rekening_tujuan_nama')->nullable();
            $table->string('rekening_tujuan_bank')->nullable();
            $table->decimal('nominal_transfer', 15, 2)->nullable();
            $table->text('nominal_terbilang')->nullable();
            $table->text('uraian_penggunaan')->nullable();
            $table->string('status')->default('DRAFT');
            $table->foreignId('dibuat_oleh_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('difinalkan_oleh_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('finalized_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('standing_instructions');
    }
};
