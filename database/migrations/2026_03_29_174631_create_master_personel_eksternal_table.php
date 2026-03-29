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
        Schema::create('master_personel_eksternal', function (Blueprint $table) {
            $table->id();
            $table->string('nrp_nik', 50)->unique();
            $table->string('nama_lengkap', 100);
            $table->string('pangkat', 50);
            $table->string('jabatan', 100);
            $table->string('no_hp', 100);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('master_personel_eksternal');
    }
};
