<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Alur terpadu proses tagihan: verifikasi SP2D diringkas menjadi satu step
     * oleh PPK saja (verifikasi penuh sudah terjadi di tahap tagihan dan pada
     * SPP/SPM/NPI). Aman untuk instance yang sedang berjalan karena step
     * di-snapshot ke workflow_approvals saat startWorkflow.
     */
    private array $sp2dCodes = ['SP2D_KONTRAK', 'SP2D_PERJALDIN', 'SP2D_HONORARIUM'];

    public function up(): void
    {
        $definitionIds = DB::table('workflow_definitions')
            ->whereIn('kode', $this->sp2dCodes)
            ->pluck('id');

        if ($definitionIds->isEmpty()) {
            return;
        }

        DB::table('workflow_definition_steps')
            ->whereIn('workflow_definition_id', $definitionIds)
            ->where('role_code', '!=', 'PPK')
            ->delete();
    }

    public function down(): void
    {
        // Step yang dihapus dapat dikembalikan dengan menjalankan ulang
        // WorkflowDefinitionSeeder versi lama; tidak di-restore otomatis.
    }
};
