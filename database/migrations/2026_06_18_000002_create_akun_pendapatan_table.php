<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Master "Akun Pendapatan" — mengikuti sheet `Akun Pendapatan` File 2.
 *
 * Setiap penerimaan pada BKU Penerimaan diklasifikasi ke kombinasi
 * kode_akun (424xxx) + kode_jenis (901–939) → mis. 424919.92 "Tagihan Listrik".
 * Ditautkan opsional ke master_coas (akun 4xxx) dan pohon layanan_jasas.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('akun_pendapatan')) {
            Schema::create('akun_pendapatan', function (Blueprint $table) {
                $table->id();
                $table->string('kode_akun', 12);              // 424919
                $table->string('kode_jenis', 12)->nullable(); // 921
                // Label akun+sub seperti tampil di BKU Penerimaan (mis. 424919.92).
                // TIDAK unik — beberapa jenis layanan berbagi label yang sama.
                $table->string('kode_gabungan', 24)->nullable();
                $table->string('uraian_akun', 255);
                $table->string('uraian_jenis', 255)->nullable();
                $table->foreignId('coa_id')->nullable()->constrained('master_coas')->nullOnDelete();
                $table->foreignId('layanan_jasa_id')->nullable()->constrained('layanan_jasas')->nullOnDelete();
                $table->boolean('status_aktif')->default(true);
                $table->timestamps();

                $table->unique(['kode_akun', 'kode_jenis'], 'akun_pendapatan_akun_jenis_unq');
                $table->index('kode_gabungan');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('akun_pendapatan');
    }
};
