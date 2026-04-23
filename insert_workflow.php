<?php
use App\Models\WorkflowDefinition;

$definition = WorkflowDefinition::updateOrCreate(
    ['kode' => 'SPM_HONORARIUM_PPSPM'],
    [
        'nama' => 'Verifikasi SPM Honorarium',
        'target_type' => 'App\Models\DokumenSpm',
        'status_aktif' => true,
    ]
);

// Delete existing steps
$definition->steps()->delete();

// Create new steps
$definition->steps()->createMany([
    [
        'urutan_step' => 1,
        'nama_step' => 'Verifikasi Kasubbag',
        'role_code' => 'Kepala Subbagian Keuangan dan Tata Usaha',
        'is_required' => true,
        'can_reject' => true,
        'can_request_revision' => true,
    ],
    [
        'urutan_step' => 1,
        'nama_step' => 'Verifikasi PPSPM',
        'role_code' => 'PPSPM',
        'is_required' => true,
        'can_reject' => true,
        'can_request_revision' => true,
    ],
]);

echo "Workflow SPM_HONORARIUM_PPSPM created successfully.";
