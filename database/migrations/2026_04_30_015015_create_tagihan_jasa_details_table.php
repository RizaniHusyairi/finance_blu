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
        Schema::create('tagihan_jasa_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tagihan_jasa_id')->constrained('tagihan_jasas')->onDelete('cascade');
            $table->foreignId('layanan_jasa_id')->constrained('layanan_jasas')->onDelete('cascade');
            $table->decimal('qty', 10, 2)->default(1);
            $table->decimal('harga_satuan', 20, 2)->default(0);
            $table->decimal('subtotal', 20, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tagihan_jasa_details');
    }
};
