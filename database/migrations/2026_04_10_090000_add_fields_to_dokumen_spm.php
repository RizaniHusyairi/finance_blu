<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dokumen_spm', function (Blueprint $table) {
            $table->foreignId('dipa_revision_item_id')->nullable()->after('ppspm_id')->constrained('dipa_revision_items')->nullOnDelete();
            $table->string('tahun_anggaran', 10)->nullable()->after('dipa_revision_item_id');
            $table->string('jenis_tagihan', 50)->default('NON REMUNERASI')->after('tahun_anggaran');
            $table->string('jatuh_tempo', 50)->default('Segera')->after('jenis_tagihan');
            $table->string('cara_bayar', 50)->default('SP2D BLU - TRF')->after('jatuh_tempo');
            $table->decimal('nominal_spm', 18, 2)->nullable()->after('cara_bayar');
            $table->foreignId('dibuat_oleh_id')->nullable()->after('nominal_spm')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('dokumen_spm', function (Blueprint $table) {
            $table->dropForeign(['dipa_revision_item_id']);
            $table->dropForeign(['dibuat_oleh_id']);
            $table->dropColumn([
                'dipa_revision_item_id',
                'tahun_anggaran',
                'jenis_tagihan',
                'jatuh_tempo',
                'cara_bayar',
                'nominal_spm',
                'dibuat_oleh_id',
            ]);
        });
    }
};
