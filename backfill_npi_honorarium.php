<?php

use Illuminate\Support\Facades\DB;
use App\Models\WorkflowDefinition;
use App\Models\WorkflowDefinitionStep;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

DB::transaction(function () {
    $definition = WorkflowDefinition::updateOrCreate(
        ['kode' => 'NPI_HONORARIUM'],
        [
            'nama' => 'Verifikasi NPI Honorarium',
            'target_type' => 'App\\Models\\DokumenNpi',
            'status_aktif' => true,
        ]
    );

    $steps = [
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
    ];

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
    
    echo "Workflow NPI_HONORARIUM berhasil ditanam!\n";
});
