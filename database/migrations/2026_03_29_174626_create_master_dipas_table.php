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
        Schema::create('master_dipas', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_dipa', 100)->unique(); 
            $table->year('tahun_anggaran'); 
            $table->decimal('total_pagu', 15, 2)->default(0); 
            $table->integer('revisi_ke')->default(0); 
            $table->date('tanggal_disahkan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('master_dipas');
    }
};
