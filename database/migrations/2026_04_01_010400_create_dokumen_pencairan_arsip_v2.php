<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('dokumen_spp')) {
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
        }

        if (!Schema::hasTable('dokumen_spm')) {
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
        }

        if (!Schema::hasTable('dokumen_npi')) {
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
        }

        if (!Schema::hasTable('dokumen_sp2d')) {
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
        }

        if (!Schema::hasTable('arsip_dokumen')) {
        Schema::create('arsip_dokumen', function (Blueprint $table) {
            $table->id();
            $table->morphs('documentable');
            $table->string('jenis_dokumen', 100);
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
