<?php
use App\Models\DokumenSpp;
use App\Services\WorkflowService;

$spps = DokumenSpp::whereHas('tagihan', function($q) {
    $q->where('tipe_tagihan', 'HONORARIUM');
})->where('status', 'Menunggu Verifikasi')
  ->doesntHave('workflowInstances')
  ->get();

$count = 0;
foreach ($spps as $spp) {
    app(WorkflowService::class)->startWorkflow('SPP_KONTRAK_PPK', $spp, $spp->ppk_verifikator_id);
    $count++;
}
echo "Backfilled {$count} SPPs.";
