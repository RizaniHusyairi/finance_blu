<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('mitra_jasa_konsesi')) {
            Schema::create('mitra_jasa_konsesi', function (Blueprint $table) {
                $table->id();
                $table->foreignId('mitra_jasa_id')->constrained('mitra_jasa')->cascadeOnDelete();
                $table->foreignId('kontrak_mitra_jasa_id')->nullable()->constrained('kontrak_mitra_jasa')->nullOnDelete();
                $table->foreignId('layanan_jasa_id')->nullable()->constrained('layanan_jasas')->nullOnDelete();
                $table->string('jenis_konsesi', 50);
                $table->decimal('persentase_konsesi', 8, 4)->nullable();
                $table->decimal('nilai_tetap', 18, 2)->nullable();
                $table->decimal('nilai_minimum_guarantee', 18, 2)->nullable();
                $table->string('periode_pelaporan', 30)->default('bulanan');
                $table->date('tanggal_mulai');
                $table->date('tanggal_selesai')->nullable();
                $table->boolean('status_aktif')->default(true);
                $table->text('catatan')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('mitra_jasa_penjualan')) {
            Schema::create('mitra_jasa_penjualan', function (Blueprint $table) {
                $table->id();
                $table->foreignId('mitra_jasa_id')->constrained('mitra_jasa')->cascadeOnDelete();
                $table->foreignId('mitra_jasa_konsesi_id')->nullable()->constrained('mitra_jasa_konsesi')->nullOnDelete();
                $table->foreignId('kontrak_mitra_jasa_id')->nullable()->constrained('kontrak_mitra_jasa')->nullOnDelete();
                $table->foreignId('layanan_jasa_id')->nullable()->constrained('layanan_jasas')->nullOnDelete();
                $table->string('periode_tipe', 30)->default('bulanan');
                $table->date('periode_mulai');
                $table->date('periode_selesai');
                $table->unsignedTinyInteger('bulan')->nullable();
                $table->unsignedSmallInteger('tahun')->nullable();
                $table->decimal('total_omzet', 18, 2)->default(0);
                $table->unsignedInteger('total_transaksi')->nullable();
                $table->decimal('nilai_konsesi', 18, 2)->default(0);
                $table->decimal('nilai_minimum_guarantee', 18, 2)->nullable();
                $table->decimal('nilai_tagihan', 18, 2)->default(0);
                $table->string('file_laporan')->nullable();
                $table->string('status', 30)->default('draft');
                $table->text('catatan_mitra')->nullable();
                $table->text('catatan_verifikator')->nullable();
                $table->timestamp('submitted_at')->nullable();
                $table->timestamp('verified_at')->nullable();
                $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('tagihan_jasa_id')->nullable()->constrained('tagihan_jasas')->nullOnDelete();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('mitra_jasa_penjualan');
        Schema::dropIfExists('mitra_jasa_konsesi');
    }
};
