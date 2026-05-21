<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('mitra_layanan_jasa')) {
            Schema::create('mitra_layanan_jasa', function (Blueprint $table) {
                $table->id();
                $table->foreignId('mitra_id')->constrained('master_pihak')->cascadeOnDelete();
                $table->foreignId('layanan_jasa_id')->constrained('layanan_jasas')->cascadeOnDelete();
                $table->boolean('status_aktif')->default(true);
                $table->date('tanggal_mulai')->nullable();
                $table->date('tanggal_selesai')->nullable();
                $table->text('keterangan')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->unique(['mitra_id', 'layanan_jasa_id'], 'mitra_layanan_jasa_unique');
            });
        }

        if (! Schema::hasTable('admin_jasa_layanan')) {
            Schema::create('admin_jasa_layanan', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('layanan_jasa_id')->constrained('layanan_jasas')->cascadeOnDelete();
                $table->boolean('status_aktif')->default(true);
                $table->date('tanggal_mulai')->nullable();
                $table->date('tanggal_selesai')->nullable();
                $table->text('keterangan')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->unique(['user_id', 'layanan_jasa_id'], 'admin_jasa_layanan_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_jasa_layanan');
        Schema::dropIfExists('mitra_layanan_jasa');
    }
};
