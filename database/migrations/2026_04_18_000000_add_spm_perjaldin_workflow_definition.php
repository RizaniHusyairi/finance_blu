<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('workflow_definitions') || !Schema::hasTable('workflow_definition_steps')) {
            return;
        }

        DB::table('workflow_definitions')->updateOrInsert(
            ['kode' => 'SPM_PERJALDIN_PPSPM'],
            [
                'nama' => 'Verifikasi SPM Perjaldin',
                'target_type' => 'App\\Models\\DokumenSpm',
                'status_aktif' => true,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        $workflowId = DB::table('workflow_definitions')
            ->where('kode', 'SPM_PERJALDIN_PPSPM')
            ->value('id');

        if (!$workflowId) {
            return;
        }

        foreach ($this->steps() as $step) {
            DB::table('workflow_definition_steps')->updateOrInsert(
                [
                    'workflow_definition_id' => $workflowId,
                    'urutan_step' => $step['urutan_step'],
                    'role_code' => $step['role_code'],
                ],
                array_merge($step, [
                    'workflow_definition_id' => $workflowId,
                    'updated_at' => now(),
                    'created_at' => now(),
                ])
            );
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('workflow_definitions') || !Schema::hasTable('workflow_definition_steps')) {
            return;
        }

        $workflowId = DB::table('workflow_definitions')
            ->where('kode', 'SPM_PERJALDIN_PPSPM')
            ->value('id');

        if (!$workflowId) {
            return;
        }

        DB::table('workflow_definition_steps')
            ->where('workflow_definition_id', $workflowId)
            ->delete();

        if (!Schema::hasTable('workflow_instances')
            || !DB::table('workflow_instances')->where('workflow_definition_id', $workflowId)->exists()) {
            DB::table('workflow_definitions')
                ->where('id', $workflowId)
                ->delete();
        }
    }

    private function steps(): array
    {
        return [
            [
                'urutan_step' => 1,
                'nama_step' => 'Verifikasi PPSPM',
                'role_code' => 'PPSPM',
                'is_required' => true,
                'can_reject' => true,
                'can_request_revision' => true,
            ],
            [
                'urutan_step' => 1,
                'nama_step' => 'Verifikasi Kasubbag',
                'role_code' => 'Kepala Subbagian Keuangan dan Tata Usaha',
                'is_required' => true,
                'can_reject' => true,
                'can_request_revision' => true,
            ],
        ];
    }
};
