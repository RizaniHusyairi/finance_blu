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
        Schema::create('detail_dipas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dipa_id')->constrained('master_dipas')->cascadeOnDelete();
            $table->foreignId('coa_id')->constrained('master_coas')->restrictOnDelete(); 
            $table->decimal('nilai_pagu', 15, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detail_dipas');
    }
};
