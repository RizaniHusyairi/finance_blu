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
        Schema::create('budgets', function (Blueprint $table) {
            $table->id();
            $table->string('program_code')->nullable();
            $table->string('activity_code')->nullable();
            $table->string('output_code')->nullable();
            $table->string('suboutput_code')->nullable();
            $table->string('component_code')->nullable();
            $table->string('subcomponent_code')->nullable();
            $table->string('account_code')->nullable();
            $table->string('item_code')->nullable();
            $table->string('coa')->nullable(); // Full Chart of Account
            $table->text('description')->nullable();
            $table->decimal('initial_budget', 20, 2)->default(0);
            $table->decimal('realized_budget', 20, 2)->default(0);
            $table->decimal('remaining_budget', 20, 2)->default(0);
            $table->integer('year');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('budgets');
    }
};
