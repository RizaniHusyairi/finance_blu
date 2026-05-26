<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class WorkflowTagihanJasaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $workflow = \App\Models\WorkflowDefinition::updateOrCreate(
            ['kode' => 'TAGIHAN_JASA'],
            [
                'nama' => 'Alur Persetujuan Tagihan Jasa (PNBP)',
                'target_type' => \App\Models\TagihanJasa::class,
                'status_aktif' => true,
            ]
        );

        $steps = [
            [
                'urutan_step' => 1,
                'role_code' => 'Koordinator Jasa',
                'nama_step' => 'Verifikasi Koordinator Jasa',
                'is_required' => true,
                'can_reject' => true,
                'can_request_revision' => true,
            ],
            [
                'urutan_step' => 2,
                'role_code' => 'Kepala Seksi Pelayanan dan Kerjasama',
                'nama_step' => 'Verifikasi Kepala Seksi Pelayanan dan Kerjasama',
                'is_required' => true,
                'can_reject' => true,
                'can_request_revision' => true,
            ],
            [
                'urutan_step' => 3,
                'role_code' => 'Kepala Subbagian Keuangan dan Tata Usaha',
                'nama_step' => 'Verifikasi KASUBBAG TU',
                'is_required' => true,
                'can_reject' => true,
                'can_request_revision' => true,
            ],
            [
                'urutan_step' => 4,
                'role_code' => 'KPA', // KPA is Kabandara
                'nama_step' => 'Persetujuan dan TTD Kabandara',
                'is_required' => true,
                'can_reject' => true,
                'can_request_revision' => true,
            ],
        ];

        foreach ($steps as $step) {
            \App\Models\WorkflowDefinitionStep::updateOrCreate(
                [
                    'workflow_definition_id' => $workflow->id,
                    'urutan_step' => $step['urutan_step'],
                ],
                $step
            );
        }
    }
}
