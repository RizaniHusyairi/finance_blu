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
        'KOORDINATOR_KEUANGAN' => 'Koordinator Keuangan',
    ];

    $roleIcons = [
        'PPSPM' => 'bi-person-workspace',
        'BENDAHARA_PENERIMAAN' => 'bi-cash-stack',
        'BENDAHARA_PENGELUARAN' => 'bi-wallet-fill',
        'PPK' => 'bi-shield-check',
        'KASUBBAG' => 'bi-patch-check-fill',
        'KOORDINATOR_KEUANGAN' => 'bi-graph-up-arrow',
        'Kepala Subbagian Keuangan dan Tata Usaha' => 'bi-patch-check-fill',
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

    $stateCfg = [
        'done'     => ['icon' => 'bi-check-circle-fill', 'label' => 'Selesai',   'badge' => 'success'],
        'active'   => ['icon' => 'bi-clock-fill',        'label' => 'Menunggu',  'badge' => 'primary'],
        'revision' => ['icon' => 'bi-arrow-counterclockwise', 'label' => 'Revisi', 'badge' => 'warning'],
        'rejected' => ['icon' => 'bi-x-circle-fill',     'label' => 'Ditolak',   'badge' => 'danger'],
        'pending'  => ['icon' => 'bi-circle',             'label' => 'Belum',     'badge' => 'secondary'],
    ];

    // === Separate steps into 3 phases ===
    // Phase 1: Pengajuan (always done if document exists)
    $submitLog = $logs->firstWhere('aksi', 'SUBMIT');

    // Phase 2: Parallel verifiers (urutan_step = 1 in approvals)
    $parallelApprovals = $approvals->filter(fn($a) => (int) $a->urutan_step === 1)->values();

    // Phase 3: Kasubbag (urutan_step = 2 in approvals)
    $kasubbagApproval = $approvals->firstWhere('urutan_step', 2);

    // If no approvals at all, build placeholder parallel + kasubbag
    $hasApprovals = $approvals->isNotEmpty();

    // Build parallel verifier data
    if ($parallelApprovals->isNotEmpty()) {
        $parallelSteps = $parallelApprovals->map(fn($a) => [
            'label' => $roleLabels[$a->role_code] ?? $a->role_code,
            'icon'  => $roleIcons[$a->role_code] ?? 'bi-clipboard2-check',
            'sub'   => $a->actedByUser?->name ?? ($roleLabels[$a->role_code] ?? $a->role_code),
            'state' => $stateFromApproval($a),
            'log_at'=> $a->acted_at,
        ]);
    } else {
        // Placeholder when no approvals exist
        $placeholderRoles = ['PPSPM', 'BENDAHARA_PENERIMAAN', 'BENDAHARA_PENGELUARAN', 'PPK'];
        $parallelSteps = collect($placeholderRoles)->map(fn($r) => [
            'label' => $roleLabels[$r],
            'icon'  => $roleIcons[$r],
            'sub'   => $roleLabels[$r],
            'state' => str_starts_with($status, 'PENDING_') ? 'active' : 'pending',
            'log_at'=> null,
        ]);
    }

    // Kasubbag state
    if ($kasubbagApproval) {
        $kasubbagState = $stateFromApproval($kasubbagApproval);
        $kasubbagName  = $kasubbagApproval->actedByUser?->name ?? 'Kasubbag';
        $kasubbagLogAt = $kasubbagApproval->acted_at;
    } else {
        $kasubbagState = $status === 'PENDING_KASUBBAG' ? 'active' : ($status === 'DISETUJUI_PERJALDIN' ? 'done' : 'pending');
        $kasubbagName  = 'Kasubbag';
        $kasubbagLogAt = null;
    }

    // Calculate parallel progress
    $parallelDone  = $parallelSteps->filter(fn($s) => $s['state'] === 'done')->count();
    $parallelTotal = $parallelSteps->count();
    $parallelPct   = $parallelTotal > 0 ? round($parallelDone / $parallelTotal * 100) : 0;

    // Connector states
    $connectorLeftState  = 'done'; // pengajuan always done
    $allParallelDone     = $parallelDone === $parallelTotal;
    $connectorRightState = $allParallelDone ? ($kasubbagState === 'done' ? 'done' : 'active') : 'pending';

    $lastRevisionLog = $tagihan->logs
        ->filter(fn($l) => str_starts_with((string) $l->status_baru, 'REVISI_'))
        ->sortByDesc('created_at')
        ->first();
@endphp

<div class="card info-doc-card shadow-sm mb-4">
    <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
        <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-diagram-3 text-primary me-2"></i>Posisi Verifikasi</h6>
        <div class="d-flex gap-2">
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 small">
                {{ $parallelDone }}/{{ $parallelTotal }} Paralel
            </span>
            @if($kasubbagState === 'done')
                <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 small">
                    <i class="bi bi-check-all me-1"></i>Lengkap
                </span>
            @endif
        </div>
    </div>
    <div class="card-body py-4 px-4">

        {{-- ═══ PARALLEL FLOW VISUALIZATION ═══ --}}
        <div class="parallel-flow">

            {{-- ① PHASE: Pengajuan --}}
            <div class="pf-phase pf-phase-start">
                <div class="pf-node done">
                    <i class="bi bi-person-fill-up"></i>
                </div>
                <div class="fw-bold small text-success mt-2">Pengajuan</div>
                <div class="text-muted" style="font-size: 0.68rem;">Operator</div>
                <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill mt-1" style="font-size: 0.58rem;">Selesai</span>
                @if($submitLog)
                    <div class="text-muted font-mono-premium mt-1" style="font-size: 0.58rem;">{{ $submitLog->created_at->format('d M, H:i') }}</div>
                @endif
            </div>

            {{-- → Connector Left --}}
            <div class="pf-connector {{ $connectorLeftState }}"></div>

            {{-- ② PHASE: Parallel Verification Track --}}
            <div class="pf-phase pf-phase-parallel">
                <div class="parallel-track">
                    <div class="parallel-track-label">
                        <i class="bi bi-arrow-left-right me-1"></i>Verifikasi Paralel
                    </div>

                    @foreach($parallelSteps as $ps)
                        @php $cfg = $stateCfg[$ps['state']]; @endphp
                        <div class="pf-verifier-row">
                            <div class="pf-verifier-dot {{ $ps['state'] }}">
                                <i class="bi {{ $cfg['icon'] }}"></i>
                            </div>
                            <div class="flex-fill text-start" style="min-width: 0;">
                                <div class="fw-semibold small text-dark text-truncate">{{ $ps['label'] }}</div>
                                <div class="text-muted text-truncate" style="font-size: 0.66rem;">{{ $ps['sub'] }}</div>
                            </div>
                            <div class="d-flex align-items-center gap-2 flex-shrink-0">
                                <span class="badge bg-{{ $cfg['badge'] }}-subtle text-{{ $cfg['badge'] }} border border-{{ $cfg['badge'] }}-subtle rounded-pill" style="font-size: 0.56rem;">{{ $cfg['label'] }}</span>
                                @if($ps['log_at'])
                                    <span class="text-muted font-mono-premium d-none d-sm-inline" style="font-size: 0.56rem;">{{ $ps['log_at']->format('d M, H:i') }}</span>
                                @endif
                            </div>
                        </div>
                    @endforeach

                    {{-- Progress bar --}}
                    <div class="pf-progress-bar-track">
                        <div class="pf-progress-bar-fill" style="width: {{ $parallelPct }}%;"></div>
                    </div>
                    <div class="text-center mt-1">
                        <span class="text-muted font-mono-premium" style="font-size: 0.6rem;">{{ $parallelDone }}/{{ $parallelTotal }} verifikator selesai</span>
                    </div>
                </div>
            </div>

            {{-- → Connector Right --}}
            <div class="pf-connector {{ $connectorRightState }}"></div>

            {{-- ③ PHASE: Kasubbag --}}
            <div class="pf-phase pf-phase-end">
                @php $kCfg = $stateCfg[$kasubbagState]; @endphp
                <div class="pf-node {{ $kasubbagState }}">
                    <i class="bi bi-patch-check-fill"></i>
                </div>
                <div class="fw-bold small text-{{ $kCfg['badge'] }} mt-2">Kasubbag</div>
                <div class="text-muted text-truncate" style="font-size: 0.68rem; max-width: 100px;">{{ $kasubbagName }}</div>
                <span class="badge bg-{{ $kCfg['badge'] }}-subtle text-{{ $kCfg['badge'] }} border border-{{ $kCfg['badge'] }}-subtle rounded-pill mt-1" style="font-size: 0.58rem;">{{ $kCfg['label'] }}</span>
                @if($kasubbagLogAt)
                    <div class="text-muted font-mono-premium mt-1" style="font-size: 0.58rem;">{{ $kasubbagLogAt->format('d M, H:i') }}</div>
                @endif
            </div>

        </div>

        {{-- Revision Alert --}}
        @if($lastRevisionLog && str_starts_with($status, 'REVISI_'))
            <div class="border border-warning border-opacity-50 bg-warning bg-opacity-10 rounded-3 p-3 mt-4 mb-0">
                <div class="d-flex gap-3 align-items-start">
                    <div class="rounded-circle bg-warning bg-opacity-25 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 36px; height: 36px;">
                        <i class="bi bi-exclamation-triangle-fill text-warning"></i>
                    </div>
                    <div class="flex-fill">
                        <div class="fw-bold small text-dark mb-1">Catatan Revisi</div>
                        <p class="mb-2 small text-dark">{{ $lastRevisionLog->catatan ?? '-' }}</p>
                        <div class="d-flex align-items-center gap-2">
                            <small class="text-muted font-mono-premium" style="font-size: 0.68rem;"><i class="bi bi-clock me-1"></i>{{ $lastRevisionLog->created_at->format('d M Y, H:i') }}</small>
                            @if($lastRevisionLog->user)
                                <small class="text-muted" style="font-size: 0.68rem;"><i class="bi bi-person-circle me-1"></i>{{ $lastRevisionLog->user->name ?? '-' }}</small>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @elseif($status === 'DISETUJUI_PERJALDIN')
            <div class="bg-success bg-opacity-10 border border-success border-opacity-30 rounded-3 p-3 mt-4 mb-0 text-center">
                <i class="bi bi-check-circle-fill text-success me-2"></i>
                <strong class="text-success small">Dokumen telah disetujui seluruh verifikator dan siap diproses Operator BLU.</strong>
            </div>
        @endif
    </div>
</div>
