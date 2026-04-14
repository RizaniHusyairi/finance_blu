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
        Schema::create('master_uang_harian_perjaldins', function (Blueprint $table) {
            $table->id();
            $table->string('provinsi')->unique();
            $table->string('satuan')->default('OH');
            $table->integer('luar_kota')->default(0);
            $table->integer('dalam_kota_lebih_8_jam')->default(0);
            $table->integer('diklat')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('master_uang_harian_perjaldins');
    }
};
