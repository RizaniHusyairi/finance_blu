<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DB-05 — kolom audit `updated_by` pada tabel keuangan inti + FK yang hilang.
 * Pengisian otomatis ditangani trait App\Models\Concerns\Blameable pada model.
 */
return new class extends Migration
{
    private array $tables = [
        'tagihan',
        'tagihan_jasas',
        'dokumen_spp',
        'dokumen_spm',
        'dokumen_npi',
        'dokumen_sp2d',
        'transaksi_pembukuan',
        'realisasi_anggaran',
    ];

    public function up(): void
    {
        foreach ($this->tables as $t) {
            if (! Schema::hasColumn($t, 'updated_by')) {
                Schema::table($t, function (Blueprint $table) {
                    $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                });
            }
        }

        // FK yang hilang: mitra_jasa_penjualan_details.created_by adalah
        // unsignedBigInteger tanpa constraint.
        Schema::table('mitra_jasa_penjualan_details', function (Blueprint $table) {
            $table->foreign('created_by', 'mjpd_created_by_fk')
                ->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        foreach ($this->tables as $t) {
            if (Schema::hasColumn($t, 'updated_by')) {
                Schema::table($t, function (Blueprint $table) {
                    $table->dropConstrainedForeignId('updated_by');
                });
            }
        }

        Schema::table('mitra_jasa_penjualan_details', function (Blueprint $table) {
            $table->dropForeign('mjpd_created_by_fk');
        });
    }
};
