<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Penanda perbaikan terarah pada rantai dokumen pencairan: verifikator
 * mengembalikan rantai ke Operator BLU (PAJAK) atau PPK (COA) tanpa
 * membatalkan hasil verifikasi tagihan. Kolom dikosongkan kembali saat
 * bagian terkait disimpan ulang oleh penanggung jawabnya.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tagihan', function (Blueprint $table) {
            $table->string('chain_correction_target', 20)->nullable()->after('kpa_approval_notes');
            $table->text('chain_correction_note')->nullable()->after('chain_correction_target');
            $table->foreignId('chain_correction_requested_by')->nullable()
                ->after('chain_correction_note')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('chain_correction_requested_at')->nullable()->after('chain_correction_requested_by');
        });
    }

    public function down(): void
    {
        Schema::table('tagihan', function (Blueprint $table) {
            $table->dropConstrainedForeignId('chain_correction_requested_by');
            $table->dropColumn(['chain_correction_target', 'chain_correction_note', 'chain_correction_requested_at']);
        });
    }
};
