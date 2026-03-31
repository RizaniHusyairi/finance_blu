<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('transaksi_penerimaan')) {
        Schema::create('transaksi_penerimaan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mitra_id')->constrained('master_pihak')->restrictOnDelete();
            $table->foreignId('coa_id')->constrained('master_coas')->restrictOnDelete();
            $table->string('nomor_invoice', 100)->unique();
            $table->date('tanggal_invoice')->nullable();
            $table->date('tanggal_jatuh_tempo')->nullable();
            $table->decimal('nominal_tagihan', 18, 2);
            $table->decimal('nominal_denda_keterlambatan', 18, 2)->default(0);
            $table->decimal('total_dibayar', 18, 2)->default(0);
            $table->enum('status_pembayaran', ['UNPAID', 'PARTIAL', 'PAID'])->default('UNPAID');
            $table->text('keterangan')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
        }

        if (!Schema::hasTable('tagihan')) {
        Schema::create('tagihan', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_tagihan', 100)->unique();
            $table->enum('tipe_tagihan', ['PERJALDIN', 'KONTRAK', 'HONORARIUM']);
            $table->foreignId('master_dipa_id')->constrained('master_dipas')->restrictOnDelete();
            $table->foreignId('pihak_id')->nullable()->constrained('master_pihak')->nullOnDelete();
            $table->text('deskripsi');
            $table->decimal('total_bruto', 18, 2);
            $table->decimal('total_potongan', 18, 2)->default(0);
            $table->decimal('total_netto', 18, 2);
            $table->string('status', 50)->default('DRAFT');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['tipe_tagihan', 'status']);
        });
        }

        if (!Schema::hasTable('potongan_tagihan')) {
        Schema::create('potongan_tagihan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tagihan_id')->constrained('tagihan')->cascadeOnDelete();
            $table->foreignId('pajak_id')->nullable()->constrained('master_tarif_pajak')->nullOnDelete();
            $table->foreignId('akun_potongan_id')->nullable()->constrained('master_coas')->nullOnDelete();
            $table->string('jenis_potongan', 50);
            $table->string('deskripsi');
            $table->decimal('dpp', 18, 2)->default(0);
            $table->decimal('persentase_tarif_snapshot', 8, 4)->nullable();
            $table->string('nama_pajak_snapshot', 100)->nullable();
            $table->decimal('nominal_potongan', 18, 2);
            $table->string('kode_billing', 50)->nullable();
            $table->string('ntpn', 50)->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
        }

        if (!Schema::hasTable('log_status_dokumen')) {
        Schema::create('log_status_dokumen', function (Blueprint $table) {
            $table->id();
            $table->morphs('dokumen');
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->string('role_saat_itu', 100);
            $table->string('status_sebelumnya', 50)->nullable();
            $table->string('status_baru', 50);
            $table->string('aksi', 50);
            $table->text('catatan')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
        });
        }

        if (!Schema::hasTable('detail_perjaldin')) {
        Schema::create('detail_perjaldin', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tagihan_id')->constrained('tagihan')->cascadeOnDelete();
            $table->foreignId('pegawai_id')->constrained('master_pegawai')->restrictOnDelete();
            $table->string('no_spt', 100);
            $table->string('tujuan', 100);
            $table->date('tgl_berangkat');
            $table->unsignedInteger('lama_hari');
            $table->decimal('biaya_tiket', 18, 2)->default(0);
            $table->decimal('biaya_transport', 18, 2)->default(0);
            $table->decimal('biaya_penginapan', 18, 2)->default(0);
            $table->decimal('uang_harian', 18, 2)->default(0);
            $table->decimal('uang_representasi', 18, 2)->default(0);
            $table->softDeletes();
            $table->timestamps();
        });
        }

        if (!Schema::hasTable('detail_kontrak')) {
        Schema::create('detail_kontrak', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tagihan_id')->constrained('tagihan')->cascadeOnDelete();
            $table->foreignId('kontrak_termin_id')->constrained('kontrak_termin')->restrictOnDelete();
            $table->string('nomor_bapp', 100)->nullable();
            $table->date('tanggal_bapp')->nullable();
            $table->string('nomor_bast', 100)->nullable();
            $table->date('tanggal_bast')->nullable();
            $table->string('nomor_bap', 100)->nullable();
            $table->date('tanggal_bap')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
        }

        if (!Schema::hasTable('detail_honorarium')) {
        Schema::create('detail_honorarium', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tagihan_id')->constrained('tagihan')->cascadeOnDelete();
            $table->string('nama_personel', 255);
            $table->string('nrp_nip', 100)->nullable();
            $table->string('pangkat_korp', 100)->nullable();
            $table->string('jabatan', 100)->nullable();
            $table->decimal('nilai_honor', 18, 2);
            $table->decimal('pph', 18, 2)->default(0);
            $table->string('rekening', 100);
            $table->string('jenis_bank', 50);
            $table->string('nama_rekening', 100);
            $table->softDeletes();
            $table->timestamps();
        });
        }

        if (!Schema::hasTable('realisasi_anggaran')) {
        Schema::create('realisasi_anggaran', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dipa_revision_item_id')->constrained('dipa_revision_items')->restrictOnDelete();
            $table->foreignId('tagihan_id')->nullable()->constrained('tagihan')->nullOnDelete();
            $table->date('tanggal_pencairan');
            $table->string('nomor_bukti', 100)->unique();
            $table->decimal('nominal_cair', 18, 2)->default(0);
            $table->text('keterangan')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();
        });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('realisasi_anggaran');
        Schema::dropIfExists('detail_honorarium');
        Schema::dropIfExists('detail_kontrak');
        Schema::dropIfExists('detail_perjaldin');
        Schema::dropIfExists('log_status_dokumen');
        Schema::dropIfExists('potongan_tagihan');
        Schema::dropIfExists('tagihan');
        Schema::dropIfExists('transaksi_penerimaan');
    }
};
