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
        Schema::create('pejabats', function (Blueprint $table) {
            $table->id('pejabat_id');
            $table->foreignId('perjaldin_id')->constrained('perjaldins', 'perjaldin_id')->cascadeOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->string('nama_pejabat');
            $table->string('nip')->nullable();
            $table->string('no_spt');
            $table->string('no_sppd');
            $table->string('tujuan');
            $table->date('tanggal_berangkat');
            $table->integer('lama_perjalanan_dinas');
            $table->decimal('tiket', 15, 2)->default(0);
            $table->decimal('transport', 15, 2)->default(0);
            $table->decimal('uang_harian', 15, 2)->default(0);
            $table->decimal('penginapan', 15, 2)->default(0);
            $table->decimal('uang_representasi', 15, 2)->default(0);
            $table->string('rekening')->nullable();
            $table->string('status')->default('Draft');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pejabats');
    }
};
