<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('document_numbers')) {
            return;
        }

        Schema::create('document_numbers', function (Blueprint $table) {
            $table->id();
            $table->string('document_key', 50);
            $table->string('sequence_group', 100)->nullable();
            $table->string('series_prefix', 50);
            $table->string('suffix_code', 100);
            $table->year('tahun');
            $table->unsignedInteger('running_number');
            $table->unsignedTinyInteger('number_padding')->default(4);
            $table->string('full_number', 180)->unique();
            $table->enum('status', ['AVAILABLE', 'RESERVED', 'USED', 'CANCELLED'])->default('AVAILABLE');
            $table->foreignId('reserved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reserved_at')->nullable();
            $table->foreignId('used_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('used_at')->nullable();
            $table->nullableMorphs('documentable');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['document_key', 'tahun', 'status']);
            $table->index(['series_prefix', 'suffix_code', 'tahun', 'running_number'], 'doc_numbers_series_suffix_year_run_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_numbers');
    }
};
