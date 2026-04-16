<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    $spp = \App\Models\DokumenSpp::find(1);
    echo "SPP found: " . $spp->nomor_spp . " status: " . $spp->status . PHP_EOL;
    echo "komponen_id: " . $spp->tagihan_perjaldin_komponen_id . PHP_EOL;
    
    $komponen = $spp->tagihanPerjaldinKomponen;
    echo "Komponen: " . ($komponen ? $komponen->nama_komponen : 'NULL') . PHP_EOL;
    
    $wi = $spp->workflowInstance;
    echo "Workflow Instance: " . ($wi ? $wi->id : 'NULL') . PHP_EOL;
    
    $user = \App\Models\User::find(2);
    echo "User: " . $user->name . " roles: " . $user->getRoleNames()->implode(',') . PHP_EOL;
    
    $svc = app(\App\Services\SppPerjaldinWorkflowService::class);
    $result = $svc->submit($spp, $user, '127.0.0.1');
    echo "SUCCESS! Instance status: " . $result->status . PHP_EOL;
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . PHP_EOL;
    echo "File: " . $e->getFile() . ":" . $e->getLine() . PHP_EOL;
}
