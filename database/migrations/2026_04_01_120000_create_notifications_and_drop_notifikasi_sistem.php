<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('notifications')) {
            Schema::create('notifications', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('type');
                $table->morphs('notifiable');
                $table->text('data');
                $table->timestamp('read_at')->nullable();
                $table->timestamps();
            });
        }

        Schema::dropIfExists('notifikasi_sistem');
    }

    public function down(): void
    {
        if (!Schema::hasTable('notifikasi_sistem')) {
            Schema::create('notifikasi_sistem', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('judul', 150);
                $table->text('pesan');
                $table->boolean('is_read')->default(false);
                $table->string('link_url')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'is_read']);
            });
        }

        Schema::dropIfExists('notifications');
    }
};
