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
        Schema::create('perjaldin_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('perjaldin_id')->constrained('perjaldins', 'perjaldin_id')->onDelete('cascade');
            $table->string('user_name')->nullable();
            $table->string('action');
            $table->text('catatan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('perjaldin_logs');
    }
};
