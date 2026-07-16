<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            // Snapshot identitas saat kejadian (user bisa berubah/dihapus kemudian)
            $table->string('user_name')->nullable();
            $table->string('user_role')->nullable();
            $table->string('event', 30); // login, logout, login_gagal, create, update, delete, aksi
            $table->string('modul', 60)->nullable(); // segmen pertama nama route, mis. "tagihan"
            $table->string('description', 500)->nullable();
            $table->string('method', 10)->nullable();
            $table->string('route')->nullable();
            $table->text('url')->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->json('properties')->nullable(); // input tersanitasi / detail tambahan
            $table->unsignedSmallInteger('status_code')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['user_id', 'created_at']);
            $table->index(['event', 'created_at']);
            $table->index('created_at');
            $table->index('modul');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
