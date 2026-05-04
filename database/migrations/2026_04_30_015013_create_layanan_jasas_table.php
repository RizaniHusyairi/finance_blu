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
        Schema::create('layanan_jasas', function (Blueprint $table) {
            $table->id();
            $table->string('kode_layanan')->nullable()->unique();
            $table->string('nama_layanan');
            $table->string('pic_name')->nullable();
            $table->decimal('tarif_dasar', 20, 2)->default(0);
            $table->string('satuan')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('layanan_jasas');
    }
};
