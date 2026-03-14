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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('budget_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type'); // Non-Remunerasi, Honor, Perjaldin
            $table->string('payment_method')->nullable();
            $table->text('description')->nullable();
            
            $table->string('bast_number')->nullable();
            $table->date('bast_date')->nullable();
            
            // Workflow stages
            $table->string('spp_number')->nullable();
            $table->date('spp_date')->nullable();
            $table->string('spm_number')->nullable();
            $table->date('spm_date')->nullable();
            $table->string('npi_number')->nullable();
            $table->date('npi_date')->nullable();
            $table->string('sp2d_number')->nullable();
            $table->date('sp2d_date')->nullable();
            
            $table->decimal('gross_amount', 20, 2)->default(0);
            $table->decimal('net_amount', 20, 2)->default(0);
            $table->string('status')->default('Draft'); // Draft -> SPP -> SPM -> NPI -> SP2D
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
