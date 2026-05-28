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
        Schema::create('document_signatures', function (Blueprint $table) {
            $table->id();
            $table->morphs('documentable'); // documentable_type, documentable_id
            $table->string('document_label', 50); // BAPP, BAST, BAP
            $table->string('role', 50); // vendor, tim_pemeriksa, ppk
            $table->string('signer_name', 150)->nullable();
            $table->string('signer_phone', 30)->nullable();
            $table->string('status', 30)->default('pending'); // pending, signed, rejected
            $table->string('magic_token', 64)->nullable()->unique();
            $table->timestamp('signed_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('hash_data', 255)->nullable(); // hash persetujuan
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_signatures');
    }
};
