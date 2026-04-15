<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tagihan_perjaldin_komponen', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tagihan_id')->constrained('tagihan')->cascadeOnDelete();
            $table->string('kode_komponen', 50); // TIKET, TRANSPORT, PENGINAPAN, UANG_HARIAN, UANG_REPRESENTASI
            $table->string('nama_komponen', 100);
            $table->foreignId('dipa_revision_item_id')->nullable()->constrained('dipa_revision_items')->nullOnDelete();
            $table->decimal('total_nominal', 18, 2)->default(0);
            $table->unsignedInteger('jumlah_peserta')->default(0);
            $table->string('status_proses', 50)->default('DRAFT');
            $table->timestamps();

            $table->unique(['tagihan_id', 'kode_komponen'], 'uk_tagihan_komponen');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tagihan_perjaldin_komponen');
    }
};
