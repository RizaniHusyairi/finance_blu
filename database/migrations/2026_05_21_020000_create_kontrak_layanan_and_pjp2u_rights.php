<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('kontrak_mitra_jasa_layanan')) {
            Schema::create('kontrak_mitra_jasa_layanan', function (Blueprint $table) {
                $table->id();
                $table->foreignId('kontrak_mitra_jasa_id')->constrained('kontrak_mitra_jasa')->cascadeOnDelete();
                $table->foreignId('layanan_jasa_id')->constrained('layanan_jasas')->cascadeOnDelete();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->unique(['kontrak_mitra_jasa_id', 'layanan_jasa_id'], 'kontrak_layanan_unique');
            });
        }

        if (! Schema::hasTable('mitra_jasa_pjp2u')) {
            Schema::create('mitra_jasa_pjp2u', function (Blueprint $table) {
                $table->id();
                $table->foreignId('mitra_jasa_id')->constrained('mitra_jasa')->cascadeOnDelete();
                $table->foreignId('kontrak_mitra_jasa_id')->nullable()->constrained('kontrak_mitra_jasa')->nullOnDelete();
                $table->foreignId('layanan_jasa_id')->constrained('layanan_jasas')->cascadeOnDelete();
                $table->date('tanggal_mulai')->nullable();
                $table->date('tanggal_selesai')->nullable();
                $table->boolean('status_aktif')->default(true);
                $table->text('keterangan')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['mitra_jasa_id', 'status_aktif'], 'mitra_pjp2u_status_index');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('mitra_jasa_pjp2u');
        Schema::dropIfExists('kontrak_mitra_jasa_layanan');
    }
};
