<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tarif berjadwal (temporal pricing) untuk layanan jasa.
 *
 * Menyimpan periode tarif khusus/diskon untuk sebuah layanan. Tarif normal tetap
 * berada di layanan_jasas.tarif_dasar; tabel ini hanya berisi override berbasis
 * tanggal. Saat periode berakhir, tarif otomatis kembali normal karena resolver
 * tidak lagi menemukan baris yang berlaku.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('layanan_jasa_tarifs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('layanan_jasa_id')->constrained('layanan_jasas')->cascadeOnDelete();
            // null = berlaku untuk semua mitra; diisi = diskon khusus mitra tertentu.
            $table->foreignId('mitra_jasa_id')->nullable()->constrained('mitra_jasa')->nullOnDelete();
            $table->string('jenis', 20)->default('DISKON'); // DISKON | TARIF_KHUSUS
            $table->decimal('tarif', 20, 2); // tarif efektif selama periode
            $table->decimal('persen_diskon', 5, 2)->nullable(); // informasi label saja
            $table->date('berlaku_mulai');
            $table->date('berlaku_sampai')->nullable(); // null = tanpa batas akhir
            $table->string('keterangan')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['layanan_jasa_id', 'is_active']);
            $table->index(['berlaku_mulai', 'berlaku_sampai']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('layanan_jasa_tarifs');
    }
};
