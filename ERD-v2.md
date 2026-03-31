1. Master Data Inti

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('master_coas', function (Blueprint $table) {
            $table->id();
            $table->string('kd_program', 10)->nullable();
            $table->string('kd_giat', 10)->nullable();
            $table->string('kd_output', 10)->nullable();
            $table->string('kd_suboutput', 10)->nullable();
            $table->string('kd_komponen', 10)->nullable();
            $table->string('kd_subkomponen', 10)->nullable();
            $table->string('kd_akun', 20)->nullable();
            $table->string('kd_item', 20)->nullable();
            $table->string('kode_mak_lengkap', 100)->nullable()->unique();
            $table->string('nama_akun', 150);
            $table->string('jenis_akun', 50)->nullable();
            $table->boolean('status_aktif')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('master_dipas', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_dipa', 100)->unique();
            $table->year('tahun_anggaran');
            $table->date('tanggal_disahkan')->nullable();
            $table->unsignedInteger('revisi_aktif_ke')->default(0);
            $table->boolean('status_aktif')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('dipa_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('master_dipa_id')->constrained('master_dipas')->cascadeOnDelete();
            $table->unsignedInteger('nomor_revisi')->default(0);
            $table->date('tanggal_revisi')->nullable();
            $table->decimal('total_pagu', 18, 2)->default(0);
            $table->string('file_dokumen_dipa')->nullable();
            $table->text('keterangan')->nullable();
            $table->boolean('is_active')->default(false);
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['master_dipa_id', 'nomor_revisi']);
        });

        Schema::create('dipa_revision_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dipa_revision_id')->constrained('dipa_revisions')->cascadeOnDelete();
            $table->foreignId('coa_id')->constrained('master_coas')->restrictOnDelete();
            $table->decimal('nilai_pagu', 18, 2)->default(0);
            $table->boolean('status_aktif')->default(true);
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['dipa_revision_id', 'coa_id']);
        });

        Schema::create('master_pihak', function (Blueprint $table) {
            $table->id();
            $table->enum('kategori', ['PENGELUARAN', 'PENERIMAAN', 'KEDUANYA'])->default('PENGELUARAN');
            $table->enum('jenis_entitas', ['PERORANGAN', 'BADAN_USAHA', 'INSTANSI', 'SATKER', 'KOLEKTIF'])->default('BADAN_USAHA');
            $table->string('kode_pihak', 50)->nullable()->unique();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('npwp', 30)->nullable();
            $table->string('nama_pihak', 150);
            $table->string('nama_penanggung_jawab', 150)->nullable();
            $table->string('tipe_supplier', 50)->nullable();
            $table->text('alamat')->nullable();
            $table->string('email', 150)->nullable();
            $table->string('no_telepon', 30)->nullable();
            $table->boolean('status_aktif')->default(true);
            $table->softDeletes();
            $table->timestamps();

            $table->index(['kategori', 'jenis_entitas']);
        });

        Schema::create('master_pegawai', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('nip', 50)->nullable()->unique();
            $table->string('nama_lengkap', 150);
            $table->string('jabatan', 100)->nullable();
            $table->string('npwp', 50)->nullable();
            $table->boolean('status_aktif')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('rekening_bank', function (Blueprint $table) {
            $table->id();
            $table->morphs('pemilik');
            $table->string('nama_bank', 100);
            $table->string('nomor_rekening', 50);
            $table->string('nama_rekening', 150);
            $table->string('kode_bank', 20)->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('status_aktif')->default(true);
            $table->softDeletes();
            $table->timestamps();

            $table->index(['nama_bank', 'nomor_rekening']);
        });

        Schema::create('master_tarif_pajak', function (Blueprint $table) {
            $table->id();
            $table->string('kode_pajak', 30)->nullable()->unique();
            $table->string('jenis_pajak', 50);
            $table->decimal('persentase', 8, 4);
            $table->text('rumus')->nullable();
            $table->date('berlaku_mulai')->nullable();
            $table->date('berlaku_sampai')->nullable();
            $table->boolean('status_aktif')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('master_tarif_pajak');
        Schema::dropIfExists('rekening_bank');
        Schema::dropIfExists('master_pegawai');
        Schema::dropIfExists('master_pihak');
        Schema::dropIfExists('dipa_revision_items');
        Schema::dropIfExists('dipa_revisions');
        Schema::dropIfExists('master_dipas');
        Schema::dropIfExists('master_coas');
    }
};


2. Workflow Approval Fleksibel

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflow_definitions', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 50)->unique(); // contoh: TAGIHAN_KONTRAK, TAGIHAN_PERJALDIN
            $table->string('nama', 150);
            $table->string('target_type', 100); // App\Models\Tagihan, App\Models\DokumenSpp, dll
            $table->boolean('status_aktif')->default(true);
            $table->timestamps();
        });

        Schema::create('workflow_definition_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_definition_id')->constrained('workflow_definitions')->cascadeOnDelete();
            $table->unsignedInteger('urutan_step');
            $table->string('nama_step', 100);
            $table->string('role_code', 100); // contoh: PPK, PPSPM, BEN_PENERIMA
            $table->boolean('is_required')->default(true);
            $table->boolean('can_reject')->default(true);
            $table->boolean('can_request_revision')->default(true);
            $table->timestamps();

            $table->unique(['workflow_definition_id', 'urutan_step']);
        });

        Schema::create('workflow_instances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_definition_id')->constrained('workflow_definitions')->restrictOnDelete();
            $table->morphs('workflowable'); // tagihan, spp, spm, npi, sp2d, dll
            $table->unsignedInteger('step_saat_ini')->default(1);
            $table->enum('status', ['DRAFT', 'IN_PROGRESS', 'APPROVED', 'REJECTED', 'REVISION'])->default('DRAFT');
            $table->timestamps();

            $table->index(['status', 'step_saat_ini']);
        });

        Schema::create('workflow_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_instance_id')->constrained('workflow_instances')->cascadeOnDelete();
            $table->unsignedInteger('urutan_step');
            $table->string('nama_step', 100);
            $table->string('role_code', 100);
            $table->foreignId('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('acted_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', ['PENDING', 'APPROVED', 'REJECTED', 'REVISION'])->default('PENDING');
            $table->text('catatan')->nullable();
            $table->dateTime('acted_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['workflow_instance_id', 'urutan_step']);
            $table->index(['assigned_user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_approvals');
        Schema::dropIfExists('workflow_instances');
        Schema::dropIfExists('workflow_definition_steps');
        Schema::dropIfExists('workflow_definitions');
    }
};

3. Manajemen Kontrak

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
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

            $table->unique(['kontrak_pengadaan_id', 'termin_ke']);
        });

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

    public function down(): void
    {
        Schema::dropIfExists('kontrak_addendum');
        Schema::dropIfExists('jaminan_kontrak');
        Schema::dropIfExists('kontrak_termin');
        Schema::dropIfExists('kontrak_pengadaan');
    }
};

4. Transaksi, Tagihan, Potongan

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
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


5. Dokumen Pencairan + Arsip Generik

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dokumen_spp', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tagihan_id')->constrained('tagihan')->restrictOnDelete();
            $table->foreignId('dipa_revision_item_id')->nullable()->constrained('dipa_revision_items')->nullOnDelete();
            $table->string('kategori_pembayaran', 50)->default('SP2D BLU - TRF');
            $table->string('jenis_tagihan', 50)->default('NON REMINERASI');
            $table->decimal('nominal_spp', 18, 2);
            $table->string('nomor_spp', 100)->unique();
            $table->date('tanggal_spp');
            $table->string('status', 50)->default('DRAFT');
            $table->foreignId('dibuat_oleh_id')->constrained('users')->restrictOnDelete();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('dokumen_spm', function (Blueprint $table) {
            $table->id();
            $table->foreignId('spp_id')->constrained('dokumen_spp')->restrictOnDelete();
            $table->string('nomor_spm', 100)->unique();
            $table->date('tanggal_spm');
            $table->foreignId('ppspm_id')->constrained('users')->restrictOnDelete();
            $table->string('status', 50)->default('DRAFT');
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('dokumen_npi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('spm_id')->constrained('dokumen_spm')->restrictOnDelete();
            $table->string('nomor_npi', 100)->unique();
            $table->date('tanggal_npi');
            $table->foreignId('bendahara_penerimaan_id')->constrained('users')->restrictOnDelete();
            $table->string('status', 50)->default('DRAFT');
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('dokumen_sp2d', function (Blueprint $table) {
            $table->id();
            $table->foreignId('npi_id')->constrained('dokumen_npi')->restrictOnDelete();
            $table->string('nomor_sp2d', 100)->unique();
            $table->date('tanggal_sp2d');
            $table->foreignId('bendahara_pengeluaran_id')->constrained('users')->restrictOnDelete();
            $table->string('status', 50)->default('DRAFT');
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('arsip_dokumen', function (Blueprint $table) {
            $table->id();
            $table->morphs('documentable'); // tagihan, kontrak, spp, spm, npi, sp2d, potongan, dll
            $table->string('jenis_dokumen', 100); // contoh: SPK, BAST, KWITANSI, PDF_SPM
            $table->string('nama_file_asli', 255);
            $table->string('path_file', 500);
            $table->string('disk', 50)->default('public');
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('ukuran_file')->nullable();
            $table->string('checksum', 128)->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('uploaded_at')->nullable();
            $table->text('keterangan')->nullable();
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();

            $table->index(['jenis_dokumen', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('arsip_dokumen');
        Schema::dropIfExists('dokumen_sp2d');
        Schema::dropIfExists('dokumen_npi');
        Schema::dropIfExists('dokumen_spm');
        Schema::dropIfExists('dokumen_spp');
    }
};

6. BKU, Mutasi Bank, Rekonsiliasi, Pelaporan

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
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

        Schema::create('rekonsiliasi_bank_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rekonsiliasi_bank_id')->constrained('rekonsiliasi_bank')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('aksi', 50);
            $table->text('catatan')->nullable();
            $table->timestamps();
        });

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

    public function down(): void
    {
        Schema::dropIfExists('notifikasi_sistem');
        Schema::dropIfExists('laporan_pengesahan_blu');
        Schema::dropIfExists('rekonsiliasi_bank_logs');
        Schema::dropIfExists('rekonsiliasi_bank');
        Schema::dropIfExists('detail_mutasi_bank');
        Schema::dropIfExists('import_mutasi_bank');
        Schema::dropIfExists('buku_kas_umum');
    }
};