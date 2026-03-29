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
        Schema::create('tagihan', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_tagihan', 100)->unique();
            $table->enum('tipe_tagihan', ['PERJALDIN', 'KONTRAK', 'HONORARIUM']);
            $table->foreignId('master_dipa_id')->constrained('master_dipas')->restrictOnDelete();
            $table->text('deskripsi');
            $table->decimal('total_bruto', 15, 2);
            $table->decimal('total_potongan', 15, 2)->default(0);
            $table->decimal('total_netto', 15, 2);
            $table->string('status', 50)->default('DRAFT'); 
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('diverifikasi_ppk_id')->nullable()->constrained('users');
            $table->dateTime('waktu_verifikasi_ppk')->nullable();
            $table->foreignId('diverifikasi_bendahara_id')->nullable()->constrained('users');
            $table->dateTime('waktu_verifikasi_bendahara')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tagihan');
    }
};
