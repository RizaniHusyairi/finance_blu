<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== TABEL: workflow_instances ===" . PHP_EOL;
$rows = DB::table('workflow_instances')->get();
foreach ($rows as $r) {
    echo "ID:{$r->id} | status:{$r->status} | step_saat_ini:{$r->step_saat_ini} | doc_id:{$r->workflowable_id}" . PHP_EOL;
}

echo PHP_EOL . "=== TABEL: workflow_approvals ===" . PHP_EOL;
$rows2 = DB::table('workflow_approvals')->orderBy('workflow_instance_id')->orderBy('urutan_step')->get();
foreach ($rows2 as $r) {
    echo "ID:{$r->id} | inst:{$r->workflow_instance_id} | step:{$r->urutan_step} | role:{$r->role_code} | status:{$r->status} | nama:{$r->nama_step}" . PHP_EOL;
}
