<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\WorkflowDefinition;
use App\Models\WorkflowDefinitionStep;
use Spatie\Permission\Models\Role;

class SppPerjaldinWorkflowSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Definition untuk PERJALDIN
        $wfPerjaldin = WorkflowDefinition::updateOrCreate(
            ['kode' => 'PERJALDIN'],
            [
                'nama' => 'Verifikasi Dokumen Perjaldin',
                'target_type' => 'App\Models\Tagihan',
                'status_aktif' => true,
            ]
        );

        WorkflowDefinitionStep::where('workflow_definition_id', $wfPerjaldin->id)->delete();
        
        foreach ([
            ['nama_step' => 'Verifikasi PPSPM', 'role_code' => 'PPSPM'],
            ['nama_step' => 'Verifikasi Bendahara Penerimaan', 'role_code' => 'BENDAHARA_PENERIMAAN'],
            ['nama_step' => 'Verifikasi Bendahara Pengeluaran', 'role_code' => 'BENDAHARA_PENGELUARAN'],
            ['nama_step' => 'Verifikasi PPK', 'role_code' => 'PPK'],
            ['nama_step' => 'Verifikasi Koordinator Keuangan', 'role_code' => 'Koordinator Keuangan'],
        ] as $step) {
            WorkflowDefinitionStep::create([
                'workflow_definition_id' => $wfPerjaldin->id,
                'urutan_step' => 1,
                'nama_step' => $step['nama_step'],
                'role_code' => $step['role_code'],
                'is_required' => true,
                'can_reject' => true,
                'can_request_revision' => true,
            ]);
        }

        WorkflowDefinitionStep::create([
            'workflow_definition_id' => $wfPerjaldin->id,
            'urutan_step' => 2,
            'nama_step' => 'Persetujuan Kasubbag',
            'role_code' => 'KASUBBAG',
            'is_required' => true,
            'can_reject' => true,
            'can_request_revision' => true,
        ]);

        // 2. Definition untuk SPP_PERJALDIN (Per Komponen)
        $wfSpp = WorkflowDefinition::updateOrCreate(
            ['kode' => 'SPP_PERJALDIN'],
            [
                'nama' => 'Verifikasi SPP Per Item Perjaldin',
                'target_type' => 'App\Models\DokumenSpp',
                'status_aktif' => true,
            ]
        );

        WorkflowDefinitionStep::where('workflow_definition_id', $wfSpp->id)->delete();
        
        WorkflowDefinitionStep::create([
            'workflow_definition_id' => $wfSpp->id,
            'urutan_step' => 1,
            'nama_step' => 'Verifikasi PPK',
            'role_code' => 'PPK',
            'is_required' => true,
            'can_reject' => true,
            'can_request_revision' => true,
        ]);

        WorkflowDefinitionStep::create([
            'workflow_definition_id' => $wfSpp->id,
            'urutan_step' => 1,
            'nama_step' => 'Verifikasi Koordinator Keuangan',
            'role_code' => 'Koordinator Keuangan',
            'is_required' => true,
            'can_reject' => true,
            'can_request_revision' => true,
        ]);

        WorkflowDefinitionStep::create([
            'workflow_definition_id' => $wfSpp->id,
            'urutan_step' => 1,
            'nama_step' => 'Verifikasi Kepala Subbagian Keuangan dan Tata Usaha',
            'role_code' => 'Kepala Subbagian Keuangan dan Tata Usaha',
            'is_required' => true,
            'can_reject' => true,
            'can_request_revision' => true,
        ]);

        // Create Roles if they construct
        $roles = [
            'Operator BLU',
            'Operator Perjaldin',
            'PPK',
            'PPSPM',
            'Bendahara Penerimaan',
            'Bendahara Pengeluaran',
            'Kepala Subbagian Keuangan dan Tata Usaha',
            'Koordinator Keuangan',
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }
    }
}
