<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permohonan_non_schedule', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mitra_jasa_id')->constrained('mitra_jasa')->cascadeOnDelete();
            $table->string('nomor_surat', 100);
            $table->date('tanggal_surat');
            $table->string('jenis_penerbangan', 30);
            $table->date('tanggal_penerbangan_dari');
            $table->date('tanggal_penerbangan_sampai')->nullable();
            $table->string('rute', 150)->nullable();
            $table->string('nomor_penerbangan', 50)->nullable();
            $table->string('registrasi_pesawat', 50)->nullable();
            $table->text('keterangan')->nullable();
            $table->string('file_surat');
            $table->string('status', 20)->default('DIAJUKAN');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('catatan_review')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['mitra_jasa_id', 'status']);
            $table->index('tanggal_penerbangan_dari');
        });

        Schema::create('pemakaian_garbarata', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mitra_jasa_id')->constrained('mitra_jasa')->cascadeOnDelete();
            $table->foreignId('layanan_jasa_id')->nullable()->constrained('layanan_jasas')->nullOnDelete();
            $table->foreignId('tagihan_jasa_id')->nullable()->constrained('tagihan_jasas')->nullOnDelete();
            $table->foreignId('permohonan_non_schedule_id')->nullable()->constrained('permohonan_non_schedule')->nullOnDelete();
            $table->date('tanggal');
            $table->string('nomor_penerbangan', 50)->nullable();
            $table->string('registrasi_pesawat', 50)->nullable();
            $table->dateTime('docking_at');
            $table->dateTime('undocking_at');
            $table->integer('durasi_menit')->default(0);
            $table->integer('jumlah_rentang')->default(0);
            $table->text('keterangan')->nullable();
            $table->string('file_pendukung')->nullable();
            $table->string('status', 20)->default('SIAP_DITAGIH');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['mitra_jasa_id', 'status']);
            $table->index('tanggal');
        });

        Schema::table('tagihan_jasas', function (Blueprint $table) {
            $table->string('jenis_penerbangan', 30)->default('schedule')->after('tipe_pnbp');
            $table->foreignId('permohonan_non_schedule_id')->nullable()->after('jenis_penerbangan')
                ->constrained('permohonan_non_schedule')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tagihan_jasas', function (Blueprint $table) {
            $table->dropForeign(['permohonan_non_schedule_id']);
            $table->dropColumn(['jenis_penerbangan', 'permohonan_non_schedule_id']);
        });
        Schema::dropIfExists('pemakaian_garbarata');
        Schema::dropIfExists('permohonan_non_schedule');
    }
};
