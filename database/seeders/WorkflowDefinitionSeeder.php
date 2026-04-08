<?php

namespace Database\Seeders;

use App\Models\WorkflowDefinition;
use App\Models\WorkflowDefinitionStep;
use Illuminate\Database\Seeder;

class WorkflowDefinitionSeeder extends Seeder
{
    public function run(): void
    {
        $workflows = [
            [
                'kode' => 'TAGIHAN_KONTRAK_PPK',
                'nama' => 'Verifikasi Tagihan Kontrak oleh PPK',
                'target_type' => 'App\\Models\\Tagihan',
                'steps' => [
                    [
                        'urutan_step' => 1,
                        'nama_step' => 'Verifikasi PPK',
                        'role_code' => 'PPK',
                        'is_required' => true,
                        'can_reject' => true,
                        'can_request_revision' => true,
                    ],
                ],
            ],
            [
                'kode' => 'SPP_KONTRAK_PPK',
                'nama' => 'Verifikasi SPP Kontrak',
                'target_type' => 'App\\Models\\DokumenSpp',
                'steps' => [
                    [
                        'urutan_step' => 1,
                        'nama_step' => 'Verifikasi PPK',
                        'role_code' => 'PPK',
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
                ],
            ],
        ];

        foreach ($workflows as $wfData) {
            $steps = $wfData['steps'];
            unset($wfData['steps']);

            $definition = WorkflowDefinition::updateOrCreate(
                ['kode' => $wfData['kode']],
                [
                    'nama' => $wfData['nama'],
                    'target_type' => $wfData['target_type'],
                    'status_aktif' => true,
                ]
            );

            foreach ($steps as $stepData) {
                WorkflowDefinitionStep::updateOrCreate(
                    [
                        'workflow_definition_id' => $definition->id,
                        'urutan_step' => $stepData['urutan_step'],
                    ],
                    $stepData
                );
            }
        }
    }
}
