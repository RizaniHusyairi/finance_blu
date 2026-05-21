<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mitra_jasa_penjualan_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mitra_jasa_penjualan_id')
                ->constrained('mitra_jasa_penjualan')
                ->cascadeOnDelete();
            $table->date('periode_mulai');
            $table->date('periode_selesai');
            $table->decimal('total_omzet', 18, 2)->default(0);
            $table->integer('total_transaksi')->nullable();
            $table->string('file_laporan')->nullable();
            $table->text('catatan_mitra')->nullable();
            $table->dateTime('submitted_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mitra_jasa_penjualan_details');
    }
};
