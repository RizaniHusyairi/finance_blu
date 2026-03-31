<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('kontrak_pengadaan')) {
        Schema::create('kontrak_pengadaan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('master_pihak')->restrictOnDelete();
            $table->foreignId('master_dipa_id')->constrained('master_dipas')->restrictOnDelete();
            $table->foreignId('dipa_revision_item_id')->nullable()->constrained('dipa_revision_items')->nullOnDelete();
            $table->string('nomor_spk', 100)->unique();
            $table->date('tanggal_spk');
            $table->text('nama_pekerjaan');
            $table->decimal('nilai_total_kontrak', 18, 2);
            $table->enum('metode_pembayaran', ['LUMPSUM', 'TERMIN']);
            $table->boolean('ada_uang_muka')->default(false);
            $table->decimal('nilai_uang_muka', 18, 2)->default(0);
            $table->decimal('sisa_uang_muka_belum_lunas', 18, 2)->default(0);
            $table->unsignedInteger('jangka_waktu');
            $table->enum('satuan_waktu', ['HARI', 'MINGGU', 'BULAN']);
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');
            $table->unsignedInteger('masa_pemeliharaan_hari')->default(0);
            $table->string('ketentuan_sanksi', 255)->nullable();
            $table->string('mata_uang', 10)->default('IDR');
            $table->enum('status_kontrak', ['DRAFT', 'PENDING_REVIEW', 'AKTIF', 'SELESAI', 'DIBATALKAN'])->default('DRAFT');
            $table->softDeletes();
            $table->timestamps();
        });
        }

        if (!Schema::hasTable('kontrak_termin')) {
        Schema::create('kontrak_termin', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kontrak_pengadaan_id')->constrained('kontrak_pengadaan')->cascadeOnDelete();
            $table->enum('jenis_termin', ['UANG_MUKA', 'PROGRESS', 'PELUNASAN', 'RETENSI']);
            $table->unsignedInteger('termin_ke');
            $table->string('keterangan_termin');
            $table->decimal('persentase', 8, 4);
            $table->decimal('nilai_bruto_termin', 18, 2);
            $table->decimal('potongan_angsuran_uang_muka', 18, 2)->default(0);
            $table->decimal('nilai_retensi', 18, 2)->default(0);
            $table->enum('status_termin', ['LOCKED', 'READY_TO_BILL', 'SUDAH_DITAGIH'])->default('LOCKED');
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['kontrak_pengadaan_id', 'termin_ke'], 'kontrak_termins_kontrak_termin_unq');
        });
        }

        if (!Schema::hasTable('jaminan_kontrak')) {
        Schema::create('jaminan_kontrak', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kontrak_pengadaan_id')->constrained('kontrak_pengadaan')->cascadeOnDelete();
            $table->enum('jenis_jaminan', ['UANG_MUKA', 'PELAKSANAAN', 'PEMELIHARAAN']);
            $table->string('penjamin', 150);
            $table->string('nomor_jaminan', 100);
            $table->date('tanggal_jaminan');
            $table->unsignedInteger('masa_berlaku_hari');
            $table->date('tanggal_mulai_jaminan');
            $table->date('tanggal_selesai_jaminan');
            $table->decimal('nilai_jaminan', 18, 2);
            $table->softDeletes();
            $table->timestamps();
        });
        }

        if (!Schema::hasTable('kontrak_addendum')) {
        Schema::create('kontrak_addendum', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kontrak_pengadaan_id')->constrained('kontrak_pengadaan')->cascadeOnDelete();
            $table->string('nomor_addendum', 100)->unique();
            $table->date('tanggal_addendum');
            $table->enum('jenis_addendum', ['TAMBAH_KURANG_NILAI', 'PERPANJANGAN_WAKTU', 'GANTI_SPESIFIKASI', 'KOMBINASI']);
            $table->text('keterangan_alasan');
            $table->decimal('nilai_kontrak_lama', 18, 2);
            $table->date('tanggal_selesai_lama');
            $table->unsignedInteger('jangka_waktu_lama');
            $table->decimal('nilai_kontrak_baru', 18, 2)->nullable();
            $table->date('tanggal_selesai_baru')->nullable();
            $table->unsignedInteger('jangka_waktu_baru')->nullable();
            $table->enum('status_addendum', ['DRAFT', 'APPROVED'])->default('DRAFT');
            $table->softDeletes();
            $table->timestamps();
        });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('kontrak_addendum');
        Schema::dropIfExists('jaminan_kontrak');
        Schema::dropIfExists('kontrak_termin');
        Schema::dropIfExists('kontrak_pengadaan');
    }
};
