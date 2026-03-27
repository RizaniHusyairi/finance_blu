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
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->string('contract_number')->unique();
            $table->date('date')->nullable();
            $table->text('description')->nullable();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->foreignId('budget_id')->constrained()->cascadeOnDelete();
            $table->decimal('total_amount', 20, 2)->default(0);
            $table->enum('status', ['Draft', 'Menunggu PPK', 'Revisi', 'Ditolak PPK', 'Aktif', 'Selesai', 'Batal'])->default('Draft'); // Draft, Menunggu PPK, Revisi, Ditolak PPK, Aktif, Selesai, Batal
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('type')->nullable(); // Barang / Jasa
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contracts');
    }
};
