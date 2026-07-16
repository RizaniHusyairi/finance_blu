<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Master data Kontrak Eksternal (pola Manajemen SPK): kontrak yang ditandatangani
 * di luar sistem disimpan sebagai master + skema termin, lalu tiap termin
 * ditagih satu per satu (LOCKED → READY_TO_BILL → DRAFT → SUDAH_DITAGIH).
 * Status disimpan sebagai string (bukan enum) demi kompatibilitas sqlite test.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kontrak_eksternal', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('master_pihak')->restrictOnDelete();
            $table->string('nomor_surat_pesanan', 150);
            $table->date('tanggal_surat_pesanan')->nullable();
            $table->string('sumber', 100)->default('INAPROC');
            $table->string('nama_pekerjaan', 255);
            $table->string('metode_pembayaran', 10)->default('LUMPSUM'); // LUMPSUM|TERMIN
            $table->decimal('nilai_total_kontrak', 18, 2);
            $table->boolean('ada_uang_muka')->default(false);
            $table->decimal('nilai_uang_muka', 18, 2)->default(0);
            $table->string('status_kontrak', 20)->default('DRAFT'); // DRAFT|AKTIF|SELESAI|DIBATALKAN
            // Verifikator default kontrak — di-snapshot ke tiap tagihan saat billing.
            $table->foreignId('ppk_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('ppspm_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('koordinator_keuangan_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('bendahara_pengeluaran_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('bendahara_penerimaan_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('kasubbag_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('diaktifkan_at')->nullable();
            $table->foreignId('diaktifkan_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('kontrak_eksternal_termin', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kontrak_eksternal_id')->constrained('kontrak_eksternal')->cascadeOnDelete();
            $table->string('jenis_termin', 12); // UANG_MUKA|PROGRESS|PELUNASAN|RETENSI
            $table->unsignedSmallInteger('termin_ke');
            $table->string('keterangan_termin', 150);
            $table->decimal('persentase', 8, 4);
            $table->decimal('nilai_bruto_termin', 18, 2);
            $table->decimal('potongan_angsuran_uang_muka', 18, 2)->default(0);
            $table->decimal('nilai_retensi', 18, 2)->default(0);
            $table->string('status_termin', 20)->default('LOCKED'); // LOCKED|READY_TO_BILL|DRAFT|SUDAH_DITAGIH
            $table->softDeletes();
            $table->timestamps();
            $table->unique(['kontrak_eksternal_id', 'termin_ke'], 'ke_termin_unq');
        });

        Schema::table('detail_kontrak_eksternal', function (Blueprint $table) {
            // Tautan detail → termin master; unique = guard tagih ganda
            // (pola DetailKontrak.kontrak_termin_id pada SPK).
            $table->foreignId('kontrak_eksternal_termin_id')->nullable()
                ->constrained('kontrak_eksternal_termin')->nullOnDelete();
            $table->unique('kontrak_eksternal_termin_id', 'dke_termin_unq');
        });

        // Kolom termin per-tagihan pindah ke master — drop bila ada
        // (migration 2026_07_08 mungkin sudah/belum ter-apply).
        $dropCandidates = [
            'metode_pembayaran', 'nilai_total_kontrak', 'ada_uang_muka', 'nilai_uang_muka',
            'jenis_termin', 'persentase', 'keterangan_termin',
            'potongan_angsuran_uang_muka', 'nilai_retensi',
        ];
        $drops = array_values(array_filter(
            $dropCandidates,
            fn ($col) => Schema::hasColumn('detail_kontrak_eksternal', $col)
        ));
        if ($drops !== []) {
            Schema::table('detail_kontrak_eksternal', function (Blueprint $table) use ($drops) {
                $table->dropColumn($drops);
            });
        }
    }

    public function down(): void
    {
        Schema::table('detail_kontrak_eksternal', function (Blueprint $table) {
            $table->dropUnique('dke_termin_unq');
            $table->dropConstrainedForeignId('kontrak_eksternal_termin_id');
        });
        Schema::dropIfExists('kontrak_eksternal_termin');
        Schema::dropIfExists('kontrak_eksternal');
    }
};
