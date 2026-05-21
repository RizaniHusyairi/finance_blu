<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('mitra_jasa')) {
            Schema::create('mitra_jasa', function (Blueprint $table) {
                $table->id();
                $table->string('kode_mitra', 50)->nullable()->unique();
                $table->string('nama_mitra', 150);
                $table->string('jenis_mitra', 50)->nullable();
                $table->string('npwp', 30)->nullable();
                $table->string('email', 150)->nullable();
                $table->string('no_telepon', 30)->nullable();
                $table->text('alamat')->nullable();
                $table->string('nama_penanggung_jawab', 150)->nullable();
                $table->string('jabatan_penanggung_jawab', 150)->nullable();
                $table->boolean('status_aktif')->default(true);
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('kontrak_mitra_jasa')) {
            Schema::create('kontrak_mitra_jasa', function (Blueprint $table) {
                $table->id();
                $table->foreignId('mitra_jasa_id')->constrained('mitra_jasa')->cascadeOnDelete();
                $table->string('nomor_kontrak')->nullable();
                $table->string('nama_kontrak')->nullable();
                $table->string('jenis_dokumen', 50)->nullable();
                $table->date('tanggal_kontrak')->nullable();
                $table->date('tanggal_mulai')->nullable();
                $table->date('tanggal_selesai')->nullable();
                $table->decimal('nilai_kontrak', 18, 2)->nullable();
                $table->string('file_kontrak')->nullable();
                $table->string('status_kontrak', 30)->default('AKTIF');
                $table->text('keterangan')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('mitra_jasa_layanan')) {
            Schema::create('mitra_jasa_layanan', function (Blueprint $table) {
                $table->id();
                $table->foreignId('mitra_jasa_id')->constrained('mitra_jasa')->cascadeOnDelete();
                $table->foreignId('layanan_jasa_id')->constrained('layanan_jasas')->cascadeOnDelete();
                $table->boolean('status_aktif')->default(true);
                $table->date('tanggal_mulai')->nullable();
                $table->date('tanggal_selesai')->nullable();
                $table->text('keterangan')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->unique(['mitra_jasa_id', 'layanan_jasa_id'], 'mitra_jasa_layanan_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('mitra_jasa_layanan');
        Schema::dropIfExists('kontrak_mitra_jasa');
        Schema::dropIfExists('mitra_jasa');
    }
};
