<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel short link generic yang dipakai untuk memperpendek URL publik
 * (mis. invoice mitra di WhatsApp). Slug random 8-12 karakter, tidak bisa
 * ditebak, dan tetap menyimpan reference ke entitas asli.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('short_links', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 32)->unique();
            $table->string('target_type', 64);              // mis. 'tagihan_jasa'
            $table->unsignedBigInteger('target_id');
            $table->unsignedInteger('clicked_count')->default(0);
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('last_clicked_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['target_type', 'target_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('short_links');
    }
};
