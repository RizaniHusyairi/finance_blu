<?php
$updated = \App\Models\WorkflowApproval::whereIn('role_code', ['Koordinator Keuangan', 'Kepala Subbagian Keuangan dan Tata Usaha'])
    ->where('status', 'PENDING')
    ->whereNotNull('assigned_user_id')
    ->update(['assigned_user_id' => null]);
echo "Updated " . $updated . " approvals to be role-based queues.\n";
