<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Realisasi bulanan PNBP Umum per item. Di-input / di-import oleh
 * Bendahara Penerimaan (sumber: SIMPONI / SAKTI / rekening koran).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pnbp_umum_realisasis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pnbp_umum_item_id')->constrained('pnbp_umum_items')->cascadeOnDelete();
            $table->unsignedSmallInteger('tahun');
            $table->unsignedTinyInteger('bulan'); // 1-12
            $table->decimal('nilai', 18, 2)->default(0);
            $table->string('keterangan')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['pnbp_umum_item_id', 'tahun', 'bulan']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pnbp_umum_realisasis');
    }
};
