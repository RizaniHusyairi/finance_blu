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
                'nama' => 'Verifikasi Tagihan Kontrak oleh PPK (legacy)',
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
                // Workflow baru: 5 verifikator paralel di step 1, lalu Kasubbag finalisasi di step 2
                'kode' => 'TAGIHAN_KONTRAK_VERIFIKATOR',
                'nama' => 'Verifikasi Tagihan Kontrak (Multi-Verifikator)',
                'target_type' => 'App\\Models\\Tagihan',
                'steps' => [
                    // === STEP 1 (paralel) — semua wajib approve baru lanjut ke step 2 ===
                    ['urutan_step' => 1, 'nama_step' => 'Verifikasi PPK',                  'role_code' => 'PPK',                   'is_required' => true, 'can_reject' => true, 'can_request_revision' => true],
                    ['urutan_step' => 1, 'nama_step' => 'Verifikasi PPSPM',                'role_code' => 'PPSPM',                 'is_required' => true, 'can_reject' => true, 'can_request_revision' => true],
                    ['urutan_step' => 1, 'nama_step' => 'Verifikasi Koordinator Keuangan', 'role_code' => 'KOORDINATOR_KEUANGAN',  'is_required' => true, 'can_reject' => true, 'can_request_revision' => true],
                    ['urutan_step' => 1, 'nama_step' => 'Verifikasi Bendahara Pengeluaran','role_code' => 'BENDAHARA_PENGELUARAN', 'is_required' => true, 'can_reject' => true, 'can_request_revision' => true],
                    ['urutan_step' => 1, 'nama_step' => 'Verifikasi Bendahara Penerimaan', 'role_code' => 'BENDAHARA_PENERIMAAN',  'is_required' => true, 'can_reject' => true, 'can_request_revision' => true],
                    // === STEP 2 — finalisasi Kasubbag ===
                    ['urutan_step' => 2, 'nama_step' => 'Persetujuan Kasubbag',            'role_code' => 'KASUBBAG',              'is_required' => true, 'can_reject' => true, 'can_request_revision' => true],
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
                        'nama_step' => 'Verifikasi Koordinator Keuangan',
                        'role_code' => 'Koordinator Keuangan',
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
            [
                'kode' => 'SPM_KONTRAK_PPSPM',
                'nama' => 'Verifikasi SPM Kontrak',
                'target_type' => 'App\\Models\\DokumenSpm',
                'steps' => [
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
                ],
            ],
            [
                'kode' => 'SPM_PERJALDIN_PPSPM',
                'nama' => 'Verifikasi SPM Perjaldin',
                'target_type' => 'App\\Models\\DokumenSpm',
                'steps' => [
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
                ],
            ],
            [
                'kode' => 'NPI_KONTRAK',
                'nama' => 'Verifikasi NPI Kontrak',
                'target_type' => 'App\\Models\\DokumenNpi',
                'steps' => [
                    [
                        'urutan_step' => 1,
                        'nama_step' => 'Verifikasi Bendahara Penerimaan',
                        'role_code' => 'Bendahara Penerimaan',
                        'is_required' => true,
                        'can_reject' => true,
                        'can_request_revision' => true,
                    ],
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
            [
                'kode' => 'NPI_PERJALDIN',
                'nama' => 'Verifikasi NPI Perjaldin',
                'target_type' => 'App\\Models\\DokumenNpi',
                'steps' => [
                    [
                        'urutan_step' => 1,
                        'nama_step' => 'Verifikasi Bendahara Penerimaan',
                        'role_code' => 'Bendahara Penerimaan',
                        'is_required' => true,
                        'can_reject' => true,
                        'can_request_revision' => true,
                    ],
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
            [
                'kode' => 'SP2D_PERJALDIN',
                'nama' => 'Verifikasi SP2D Perjaldin',
                'target_type' => 'App\Models\DokumenSp2d',
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
            [
                'kode' => 'SP2D_KONTRAK',
                'nama' => 'Verifikasi SP2D Kontrak',
                'target_type' => 'App\Models\DokumenSp2d',
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
            [
                'kode' => 'TAGIHAN_HONORARIUM',
                'nama' => 'Verifikasi Tagihan Honorarium',
                'target_type' => 'App\Models\Tagihan',
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
                        'nama_step' => 'Verifikasi Bendahara Pengeluaran',
                        'role_code' => 'Bendahara Pengeluaran',
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
                        'role_code' => $stepData['role_code'],
                    ],
                    $stepData
                );
            }
        }
    }
}
