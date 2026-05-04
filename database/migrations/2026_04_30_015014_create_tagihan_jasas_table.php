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
        Schema::create('tagihan_jasas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mitra_id')->constrained('master_pihak')->onDelete('cascade');
            $table->string('file_kontrak')->nullable();
            $table->string('nomor_kontrak')->nullable();
            $table->date('tanggal_mulai_kontrak')->nullable();
            $table->date('tanggal_selesai_kontrak')->nullable();
            $table->string('nomor_tagihan')->unique();
            $table->date('tanggal_tagihan');
            $table->decimal('total_tagihan', 20, 2)->default(0);
            $table->string('status')->default('DRAFT');
            $table->string('nomor_va')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tagihan_jasas');
    }
};
