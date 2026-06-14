<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('workflow_approvals') && !Schema::hasColumn('workflow_approvals', 'revisi_target')) {
            Schema::table('workflow_approvals', function (Blueprint $table) {
                // Daftar bagian yang diminta diperbaiki saat verifikator menekan "Minta Revisi"
                // (mis. ["rincian", "mitra_dokumen", "surat_pengantar"]). Mendampingi kolom catatan.
                $table->json('revisi_target')->nullable()->after('catatan');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('workflow_approvals') && Schema::hasColumn('workflow_approvals', 'revisi_target')) {
            Schema::table('workflow_approvals', function (Blueprint $table) {
                $table->dropColumn('revisi_target');
            });
        }
    }
};
