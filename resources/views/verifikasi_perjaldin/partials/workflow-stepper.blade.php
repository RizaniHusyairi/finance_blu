{{-- workflow-stepper.blade.php --}}
{{-- Variables: $tagihan, $userRole --}}
@php
    $status = $tagihan->status;
    $logs = $tagihan->logs->sortBy('created_at');
    $workflow = collect($tagihan->workflowInstances ?? [])->sortByDesc('created_at')->first();
    $approvals = collect($workflow?->approvals ?? [])->sortBy([['urutan_step', 'asc'], ['id', 'asc']])->values();

    $roleLabels = [
        'PPSPM' => 'PPSPM',
        'BENDAHARA_PENERIMAAN' => 'Bendahara Penerimaan',
        'BENDAHARA_PENGELUARAN' => 'Bendahara Pengeluaran',
        'PPK' => 'PPK',
        'KASUBBAG' => 'Kasubbag',
        'Kepala Subbagian Keuangan dan Tata Usaha' => 'Kasubbag',
    ];

    $stateFromApproval = function ($approval) use ($workflow) {
        return match ($approval->status) {
            'APPROVED' => 'done',
            'REVISION' => 'revision',
            'REJECTED' => 'rejected',
            'PENDING' => ($workflow && (int) $workflow->step_saat_ini === (int) $approval->urutan_step) ? 'active' : 'pending',
            default => 'pending',
        };
    };

    $steps = collect([
        [
            'label' => 'Pengajuan',
            'sub' => 'Operator Perjaldin',
            'icon' => 'bi-person-fill-up',
            'state' => 'done',
            'log_at' => optional($logs->firstWhere('aksi', 'SUBMIT'))->created_at,
        ],
    ])->merge($approvals->map(fn ($approval) => [
        'label' => (int) $approval->urutan_step === 2 ? 'Persetujuan Kasubbag' : 'Verifikasi ' . ($roleLabels[$approval->role_code] ?? $approval->role_code),
        'sub' => $approval->actedByUser?->name ?? ($roleLabels[$approval->role_code] ?? $approval->role_code),
        'icon' => (int) $approval->urutan_step === 2 ? 'bi-patch-check-fill' : 'bi-clipboard2-check',
        'state' => $stateFromApproval($approval),
        'log_at' => $approval->acted_at,
    ]))->values();

    if ($steps->count() === 1) {
        $steps = $steps->merge([
            ['label' => 'Verifikasi Paralel', 'sub' => 'PPSPM, Bendahara Penerimaan, Bendahara Pengeluaran, PPK', 'icon' => 'bi-clipboard2-check', 'state' => str_starts_with($status, 'PENDING_') ? 'active' : 'pending', 'log_at' => null],
            ['label' => 'Persetujuan Kasubbag', 'sub' => 'Kasubbag', 'icon' => 'bi-patch-check-fill', 'state' => $status === 'PENDING_KASUBBAG' ? 'active' : ($status === 'DISETUJUI_PERJALDIN' ? 'done' : 'pending'), 'log_at' => null],
        ]);
    }

    $stateCfg = [
        'done' => ['bg' => 'bg-success', 'text' => 'text-success', 'icon' => 'bi-check-circle-fill', 'label' => 'Selesai'],
        'active' => ['bg' => 'bg-primary', 'text' => 'text-primary', 'icon' => 'bi-clock-fill', 'label' => 'Menunggu'],
        'revision' => ['bg' => 'bg-warning', 'text' => 'text-warning', 'icon' => 'bi-arrow-counterclockwise', 'label' => 'Revisi'],
        'rejected' => ['bg' => 'bg-danger', 'text' => 'text-danger', 'icon' => 'bi-x-circle-fill', 'label' => 'Ditolak'],
        'pending' => ['bg' => 'bg-secondary', 'text' => 'text-secondary', 'icon' => 'bi-circle', 'label' => 'Belum'],
    ];

    $lastRevisionLog = $tagihan->logs
        ->filter(fn($l) => str_starts_with((string) $l->status_baru, 'REVISI_'))
        ->sortByDesc('created_at')
        ->first();
@endphp

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-bottom py-3">
        <h6 class="mb-0 fw-bold"><i class="bi bi-diagram-3 text-primary me-2"></i>Posisi Verifikasi</h6>
    </div>
    <div class="card-body py-4">
        <div class="row g-3 mb-4">
            @foreach($steps as $step)
                @php $cfg = $stateCfg[$step['state']]; @endphp
                <div class="col-6 col-md text-center">
                    <div class="d-inline-flex align-items-center justify-content-center rounded-circle text-white {{ $cfg['bg'] }} shadow-sm mb-2" style="width:40px;height:40px;">
                        <i class="bi {{ $cfg['icon'] }}"></i>
                    </div>
                    <div class="small fw-bold {{ $cfg['text'] }}">{{ $step['label'] }}</div>
                    <div style="font-size:0.72rem;" class="text-muted text-truncate px-1">{{ $step['sub'] }}</div>
                    <span class="badge {{ $cfg['bg'] }} mt-1" style="font-size:0.68rem;">{{ $cfg['label'] }}</span>
                    @if($step['log_at'])
                        <div style="font-size:0.68rem;" class="text-muted mt-1">{{ $step['log_at']->format('d M, H:i') }}</div>
                    @endif
                </div>
            @endforeach
        </div>

        @if($lastRevisionLog && str_starts_with($status, 'REVISI_'))
            <div class="alert alert-warning border-start border-4 border-warning py-3 mb-0 rounded-3">
                <div class="d-flex gap-3 align-items-start">
                    <i class="bi bi-exclamation-triangle-fill text-warning fs-5 mt-1"></i>
                    <div>
                        <div class="fw-bold small mb-1">Catatan Revisi</div>
                        <p class="mb-1 small">{{ $lastRevisionLog->catatan ?? '-' }}</p>
                        <small class="text-muted"><i class="bi bi-clock me-1"></i>{{ $lastRevisionLog->created_at->format('d M Y, H:i') }}</small>
                    </div>
                </div>
            </div>
        @elseif($status === 'DISETUJUI_PERJALDIN')
            <div class="alert alert-success border-0 py-2 mb-0 text-center rounded-3">
                <i class="bi bi-check-circle-fill me-2"></i><strong>Dokumen telah disetujui seluruh verifikator dan siap diproses Operator BLU.</strong>
            </div>
        @endif
    </div>
</div>
