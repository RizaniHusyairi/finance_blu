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

            $table->unique(['master_dipa_id', 'nomor_revisi'], 'dipa_revisions_master_nomor_unq');
        });

        Schema::create('dipa_revision_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dipa_revision_id')->constrained('dipa_revisions')->cascadeOnDelete();
            $table->foreignId('coa_id')->constrained('master_coas')->restrictOnDelete();
            $table->decimal('nilai_pagu', 18, 2)->default(0);
            $table->boolean('status_aktif')->default(true);
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['dipa_revision_id', 'coa_id'], 'dipa_rev_items_revision_coa_unq');
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
