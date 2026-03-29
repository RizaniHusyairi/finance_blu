<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('honorarium_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained('transactions')->cascadeOnDelete();
            $table->unsignedInteger('sequence_no')->default(1);

            $table->string('name');
            $table->string('nrp')->nullable();
            $table->string('rank_corps')->nullable();
            $table->string('position')->nullable();

            $table->decimal('honor_amount', 20, 2)->default(0);
            $table->decimal('pph_amount', 20, 2)->default(0);
            $table->decimal('net_amount', 20, 2)->default(0);

            $table->string('bank_account_number')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('bank_account_name')->nullable();
            $table->string('phone_number')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('honorarium_items');
    }
};