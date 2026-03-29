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
        Schema::create('master_coas', function (Blueprint $table) {
            $table->id();
            $table->string('kd_program', 10)->nullable();
            $table->string('kd_giat', 10)->nullable();
            $table->string('kd_output', 10)->nullable();
            $table->string('kd_suboutput', 10)->nullable();
            $table->string('kd_komponen', 10)->nullable();
            $table->string('kd_subkomponen', 10)->nullable();
            $table->string('kd_akun', 20)->nullable();
            $table->string('kd_item', 20)->nullable(); 
            $table->string('kode_mak_lengkap', 100)->unique()->nullable()->comment('Gabungan seluruh kode urut');
            $table->string('nama_akun', 150); 
            $table->string('jenis_akun', 50)->nullable(); 
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('master_coas');
    }
};
