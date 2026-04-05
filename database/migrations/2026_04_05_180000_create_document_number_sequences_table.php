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
        if (Schema::hasTable('document_number_sequences')) {
            return;
        }

        Schema::create('document_number_sequences', function (Blueprint $table) {
            $table->id();
            $table->string('series_prefix', 50);
            $table->string('suffix_code', 100);
            $table->year('tahun');
            $table->unsignedInteger('last_number')->default(0);
            $table->unsignedTinyInteger('number_padding')->default(4);
            $table->boolean('is_active')->default(true);
            $table->string('keterangan')->nullable();
            $table->timestamps();

            $table->unique(
                ['series_prefix', 'suffix_code', 'tahun'],
                'doc_num_seq_series_suffix_year_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_number_sequences');
    }
};
