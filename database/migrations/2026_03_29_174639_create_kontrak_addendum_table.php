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
        Schema::create('kontrak_addendum', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kontrak_pengadaan_id')->constrained('kontrak_pengadaan')->cascadeOnDelete();
            $table->string('nomor_addendum', 100)->unique();
            $table->date('tanggal_addendum');
            $table->enum('jenis_addendum', ['TAMBAH_KURANG_NILAI', 'PERPANJANGAN_WAKTU', 'GANTI_SPESIFIKASI', 'KOMBINASI']);
            $table->text('keterangan_alasan');
            $table->decimal('nilai_kontrak_lama', 15, 2);
            $table->date('tanggal_selesai_lama');
            $table->integer('jangka_waktu_lama');
            $table->decimal('nilai_kontrak_baru', 15, 2)->nullable();
            $table->date('tanggal_selesai_baru')->nullable();
            $table->integer('jangka_waktu_baru')->nullable();
            $table->string('file_addendum')->nullable();
            $table->enum('status_addendum', ['DRAFT', 'APPROVED_PPK'])->default('DRAFT');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kontrak_addendum');
    }
};
