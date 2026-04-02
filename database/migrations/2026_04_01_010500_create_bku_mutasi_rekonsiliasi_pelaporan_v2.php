<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('buku_kas_umum')) {
        Schema::create('buku_kas_umum', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal_transaksi');
            $table->string('nomor_bukti', 100);
            $table->text('uraian');
            $table->enum('arus_kas', ['DEBIT_MASUK', 'KREDIT_KELUAR']);
            $table->decimal('nominal', 18, 2);
            $table->decimal('saldo_akhir', 18, 2);
            $table->foreignId('sumber_rekening_id')->constrained('rekening_bank')->restrictOnDelete();
            $table->foreignId('referensi_pengeluaran_id')->nullable()->constrained('tagihan')->nullOnDelete();
            $table->foreignId('referensi_penerimaan_id')->nullable()->constrained('transaksi_penerimaan')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['tanggal_transaksi', 'arus_kas']);
        });
        }

        if (!Schema::hasTable('import_mutasi_bank')) {
        Schema::create('import_mutasi_bank', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rekening_bank_id')->constrained('rekening_bank')->restrictOnDelete();
            $table->date('periode_awal')->nullable();
            $table->date('periode_akhir')->nullable();
            $table->string('nama_file_asli', 255)->nullable();
            $table->string('path_file', 500)->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('uploaded_at')->nullable();
            $table->enum('status_import', ['UPLOADED', 'PARSED', 'FAILED'])->default('UPLOADED');
            $table->text('catatan_error')->nullable();
            $table->timestamps();
        });
        }

        if (!Schema::hasTable('detail_mutasi_bank')) {
        Schema::create('detail_mutasi_bank', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_mutasi_bank_id')->constrained('import_mutasi_bank')->cascadeOnDelete();
            $table->date('tanggal_transaksi');
            $table->string('deskripsi', 255)->nullable();
            $table->string('nomor_referensi_bank', 100)->nullable();
            $table->decimal('debit', 18, 2)->default(0);
            $table->decimal('kredit', 18, 2)->default(0);
            $table->decimal('saldo', 18, 2)->nullable();
            $table->enum('arah_mutasi', ['MASUK', 'KELUAR']);
            $table->enum('status_rekonsiliasi', ['BELUM', 'PARTIAL', 'MATCHED', 'SELISIH'])->default('BELUM');
            $table->timestamps();

            $table->index(['tanggal_transaksi', 'status_rekonsiliasi']);
        });
        }

        if (!Schema::hasTable('rekonsiliasi_bank')) {
        Schema::create('rekonsiliasi_bank', function (Blueprint $table) {
            $table->id();
            $table->foreignId('detail_mutasi_bank_id')->constrained('detail_mutasi_bank')->cascadeOnDelete();
            $table->foreignId('bku_id')->nullable()->constrained('buku_kas_umum')->nullOnDelete();
            $table->foreignId('transaksi_penerimaan_id')->nullable()->constrained('transaksi_penerimaan')->nullOnDelete();
            $table->foreignId('tagihan_id')->nullable()->constrained('tagihan')->nullOnDelete();
            $table->decimal('nominal_mutasi', 18, 2);
            $table->decimal('nominal_sistem', 18, 2)->default(0);
            $table->decimal('selisih', 18, 2)->default(0);
            $table->enum('status', ['MATCHED', 'PARTIAL', 'SELISIH', 'MANUAL_OVERRIDE'])->default('SELISIH');
            $table->text('catatan')->nullable();
            $table->foreignId('direkonsiliasi_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('direkonsiliasi_pada')->nullable();
            $table->timestamps();
        });
        }

        if (!Schema::hasTable('rekonsiliasi_bank_logs')) {
        Schema::create('rekonsiliasi_bank_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rekonsiliasi_bank_id')->constrained('rekonsiliasi_bank')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('aksi', 50);
            $table->text('catatan')->nullable();
            $table->timestamps();
        });
        }

        if (!Schema::hasTable('laporan_pengesahan_blu')) {
        Schema::create('laporan_pengesahan_blu', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_laporan', 100)->unique();
            $table->unsignedTinyInteger('periode_bulan');
            $table->year('tahun');
            $table->decimal('total_penerimaan', 18, 2)->default(0);
            $table->decimal('total_pengeluaran', 18, 2)->default(0);
            $table->decimal('saldo_akhir_blu', 18, 2)->default(0);
            $table->enum('status_pengesahan', ['DRAFT', 'VERIFIKASI_KPPN', 'DISAHKAN'])->default('DRAFT');
            $table->string('status_sp3b', 50)->nullable();
            $table->foreignId('disetujui_kpa_id')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();
        });
        }

    }

    public function down(): void
    {
        Schema::dropIfExists('laporan_pengesahan_blu');
        Schema::dropIfExists('rekonsiliasi_bank_logs');
        Schema::dropIfExists('rekonsiliasi_bank');
        Schema::dropIfExists('detail_mutasi_bank');
        Schema::dropIfExists('import_mutasi_bank');
        Schema::dropIfExists('buku_kas_umum');
    }
};
