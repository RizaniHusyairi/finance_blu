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
        Schema::create('log_status_dokumen', function (Blueprint $table) {
            $table->id();
            $table->morphs('dokumen'); 
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->string('role_saat_itu', 100); 
            $table->string('status_sebelumnya', 50)->nullable(); // Misal: 'PENDING_PPK'
            $table->string('status_baru', 50); // Misal: 'REVISI_BENDAHARA'
            $table->string('aksi', 50); 
            $table->text('catatan')->nullable(); 
            $table->string('ip_address', 45)->nullable(); 
            $table->timestamps(); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('log_status_dokumen');
    }
};
