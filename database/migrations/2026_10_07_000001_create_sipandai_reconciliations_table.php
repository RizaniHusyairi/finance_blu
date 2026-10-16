<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sipandai_airline_mappings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('mitra_jasa_id')->unique();
            $table->string('airline_code', 3)->unique();
            $table->unsignedBigInteger('updated_by');
            $table->timestamps();
        });
        Schema::create('sipandai_reconciliations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('penjualan_id')->index();
            $table->string('report_hash', 64);
            $table->string('airline_code', 3);
            $table->json('snapshot');
            $table->json('comparison');
            $table->unsignedBigInteger('fetched_by');
            $table->timestamp('fetched_at');
            $table->unsignedBigInteger('accepted_by')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->text('note')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sipandai_reconciliations');
        Schema::dropIfExists('sipandai_airline_mappings');
    }
};
