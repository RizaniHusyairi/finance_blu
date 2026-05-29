<?php
$user = \App\Models\User::role('Koordinator Keuangan')->first();
if (!$user) {
    echo "User not found\n";
    exit;
}
echo "User: " . $user->name . "\n";

$roleCodes = ['Koordinator Keuangan'];

$query = \App\Models\Spp::with([
    'workflowInstances' => fn($q) => $q->latest()->limit(1),
    'workflowInstances.approvals'
])
->whereHas('tagihan', fn ($q) => $q->where('tipe_tagihan', 'KONTRAK'))
->whereHas('workflowInstances.definition', fn ($q) => $q->where('kode', 'SPP_KONTRAK_PPK'))
->whereNotIn('status', ['DRAFT']);

$query->whereHas('workflowInstances', function ($q) use ($roleCodes, $user) {
    $q->whereHas('approvals', function ($q2) use ($roleCodes, $user) {
        $q2->whereIn('role_code', $roleCodes)
           ->where(function ($q3) use ($user) {
               $q3->whereNull('assigned_user_id')
                  ->orWhere('assigned_user_id', $user->id);
           });
    });
});

$allSpps = $query->get();
echo "Count allSpps: " . $allSpps->count() . "\n";

$listMenunggu = collect();
foreach ($allSpps as $spp) {
    $wf = $spp->workflowInstances->first();
    if (!$wf) continue;

    $myApprovals = $wf->approvals->whereIn('role_code', $roleCodes);
    if ($myApprovals->isEmpty()) continue;

    $approval = $myApprovals->where('status', 'PENDING')->first() ?? $myApprovals->first();
    echo "SPP ID: " . $spp->id . " Approval Status: " . $approval->status . "\n";
    
    if ($approval->status === 'PENDING') {
        $listMenunggu->push($spp);
    }
}
echo "Menunggu: " . $listMenunggu->count() . "\n";
