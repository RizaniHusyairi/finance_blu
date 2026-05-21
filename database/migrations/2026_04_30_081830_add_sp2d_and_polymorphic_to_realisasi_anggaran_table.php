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
        Schema::table('realisasi_anggaran', function (Blueprint $table) {
            $table->foreignId('dokumen_sp2d_id')->nullable()->constrained('dokumen_sp2d')->nullOnDelete();
            $table->foreignId('master_coa_id')->nullable()->constrained('master_coas')->nullOnDelete();
            $table->nullableMorphs('sourceable');
            $table->string('status')->default('TERCATAT');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('realisasi_anggaran', function (Blueprint $table) {
            $table->dropForeign(['dokumen_sp2d_id']);
            $table->dropForeign(['master_coa_id']);
            $table->dropColumn(['dokumen_sp2d_id', 'master_coa_id', 'sourceable_type', 'sourceable_id', 'status']);
        });
    }
};
