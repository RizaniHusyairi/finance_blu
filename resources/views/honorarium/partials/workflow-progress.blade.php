{{-- partial: workflow-progress.blade.php (honorarium) --}}
{{-- Variables: $tagihan --}}
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

    // Nama verifikator yang ditugaskan pada tagihan (snapshot saat pengajuan),
    // dipakai bila approval belum punya assigned_user_id / belum ditindak.
    $verifikatorNames = [
        'PPSPM' => $tagihan->ppspm_nama_snapshot,
        'BENDAHARA_PENERIMAAN' => $tagihan->bendahara_penerimaan_nama_snapshot,
        'BENDAHARA_PENGELUARAN' => $tagihan->bendahara_pengeluaran_nama_snapshot,
        'PPK' => $tagihan->ppk_nama_snapshot,
        'KOORDINATOR_KEUANGAN' => $tagihan->koordinator_keuangan_nama_snapshot,
        'KASUBBAG' => $tagihan->kasubbag_nama_snapshot,
        'Kepala Subbagian Keuangan dan Tata Usaha' => $tagihan->kasubbag_nama_snapshot,
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

    // Phase 1: Pengajuan
    $submitLog = $logs->firstWhere('aksi', 'SUBMIT');

    // Phase 2: Parallel verifiers (urutan_step = 1)
    $parallelApprovals = $approvals->filter(fn($a) => (int) $a->urutan_step === 1)->values();

    // Phase 3: Kasubbag (urutan_step = 2)
    $kasubbagApproval = $approvals->firstWhere('urutan_step', 2);

    if ($parallelApprovals->isNotEmpty()) {
        $parallelSteps = $parallelApprovals->map(fn($a) => [
            'role'  => $roleLabels[$a->role_code] ?? $a->role_code,
            'icon'  => $roleIcons[$a->role_code] ?? 'bi-clipboard2-check',
            'name'  => $a->actedByUser?->name ?? $a->assignedUser?->name ?? $verifikatorNames[$a->role_code] ?? 'Belum ditugaskan',
            'state' => $stateFromApproval($a),
            'log_at'=> $a->acted_at ? \Carbon\Carbon::parse($a->acted_at) : null,
        ]);
    } else {
        $placeholderRoles = ['BENDAHARA_PENERIMAAN', 'BENDAHARA_PENGELUARAN', 'KOORDINATOR_KEUANGAN', 'PPK', 'PPSPM'];
        $parallelSteps = collect($placeholderRoles)->map(fn($r) => [
            'role'  => $roleLabels[$r],
            'icon'  => $roleIcons[$r],
            'name'  => $verifikatorNames[$r] ?? 'Belum ditugaskan',
            'state' => str_starts_with($status, 'PENDING_') ? 'active' : 'pending',
            'log_at'=> null,
        ]);
    }

    if ($kasubbagApproval) {
        $kasubbagState = $stateFromApproval($kasubbagApproval);
        $kasubbagName  = $kasubbagApproval->actedByUser?->name ?? $kasubbagApproval->assignedUser?->name ?? $verifikatorNames['KASUBBAG'] ?? 'Belum ditugaskan';
        $kasubbagLogAt = $kasubbagApproval->acted_at ? \Carbon\Carbon::parse($kasubbagApproval->acted_at) : null;
    } else {
        $kasubbagState = $status === 'PENDING_KASUBBAG' ? 'active' : ($status === 'DISETUJUI' ? 'done' : 'pending');
        $kasubbagName  = $verifikatorNames['KASUBBAG'] ?? 'Belum ditugaskan';
        $kasubbagLogAt = null;
    }

    $parallelDone  = $parallelSteps->filter(fn($s) => $s['state'] === 'done')->count();
    $parallelTotal = $parallelSteps->count();
    $parallelPct   = $parallelTotal > 0 ? round($parallelDone / $parallelTotal * 100) : 0;

    $connectorLeftState  = 'done';
    $allParallelDone     = $parallelDone === $parallelTotal;
    $connectorRightState = $allParallelDone ? ($kasubbagState === 'done' ? 'done' : 'active') : 'pending';

    $lastRevisionLog = $tagihan->logs
        ->filter(fn($l) => str_starts_with((string) $l->status_baru, 'REVISI_') || str_contains(strtolower($l->aksi ?? ''), 'revision'))
        ->sortByDesc('created_at')
        ->first();
@endphp

@push('css')
<style>
    /* ══════ Parallel Flow Stepper (Posisi Verifikasi) ══════ */
    .pf-wrap .pf-mono {
        font-family: 'JetBrains Mono', SFMono-Regular, Menlo, Monaco, Consolas, monospace !important;
        letter-spacing: -0.02em;
    }
    .parallel-flow {
        display: flex;
        align-items: center;
        gap: 0;
        position: relative;
    }
    .pf-phase {
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
        position: relative;
        z-index: 2;
    }
    .pf-phase-start, .pf-phase-end { flex: 0 0 auto; min-width: 110px; }
    .pf-phase-parallel { flex: 1 1 auto; }

    .pf-node {
        width: 52px; height: 52px;
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.3rem;
        border: 3px solid #e2e8f0;
        background: #fff;
        transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        box-shadow: 0 4px 12px -2px rgba(0,0,0,0.06);
        position: relative;
    }
    .pf-node:hover {
        transform: scale(1.15) translateY(-3px);
        box-shadow: 0 8px 20px -4px rgba(59,130,246,0.18);
    }
    .pf-node.done    { background: #d1fae5; border-color: #10b981; color: #059669; }
    .pf-node.active  { background: #dbeafe; border-color: #3b82f6; color: #2563eb; animation: pf-pulse 2s infinite; }
    .pf-node.revision{ background: #fef3c7; border-color: #f59e0b; color: #d97706; }
    .pf-node.rejected{ background: #fee2e2; border-color: #ef4444; color: #dc2626; }
    .pf-node.pending { background: #f1f5f9; border-color: #cbd5e1; color: #94a3b8; }

    @keyframes pf-pulse {
        0%,100% { box-shadow: 0 0 0 0 rgba(59,130,246,0.35); }
        50%     { box-shadow: 0 0 0 10px rgba(59,130,246,0); }
    }

    .pf-connector {
        flex: 0 0 40px;
        height: 4px;
        position: relative;
        z-index: 1;
    }
    .pf-connector::before {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0; bottom: 0;
        border-radius: 2px;
    }
    .pf-connector.done::before    { background: linear-gradient(90deg, #10b981, #34d399); }
    .pf-connector.active::before  { background: linear-gradient(90deg, #34d399, #3b82f6); }
    .pf-connector.pending::before { background: #e2e8f0; }
    .pf-connector.active::after {
        content: '';
        position: absolute;
        top: -1px; left: 0; right: 0; height: 6px;
        background: repeating-linear-gradient(90deg, transparent, transparent 6px, rgba(59,130,246,0.25) 6px, rgba(59,130,246,0.25) 12px);
        border-radius: 3px;
        animation: pf-dash 1s linear infinite;
    }
    @keyframes pf-dash { 0%{transform:translateX(0)} 100%{transform:translateX(12px)} }

    .parallel-track {
        background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
        border: 1.5px solid #e2e8f0;
        border-radius: 16px;
        padding: 20px 16px;
        position: relative;
        width: 100%;
    }
    .parallel-track-label {
        position: absolute;
        top: -11px; left: 50%;
        transform: translateX(-50%);
        background: linear-gradient(135deg, #3b82f6, #6366f1);
        color: #fff;
        font-size: 0.65rem;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        padding: 3px 14px;
        border-radius: 20px;
        white-space: nowrap;
        box-shadow: 0 2px 8px -1px rgba(59,130,246,0.25);
    }

    .pf-verifier-row {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 8px 12px;
        border-radius: 10px;
        transition: all 0.25s ease;
        margin-bottom: 4px;
    }
    .pf-verifier-row:last-child { margin-bottom: 0; }
    .pf-verifier-row:hover {
        background: rgba(59,130,246,0.04);
        transform: translateX(3px);
    }
    .pf-verifier-dot {
        width: 30px; height: 30px;
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: 0.78rem;
        flex-shrink: 0;
        border: 2.5px solid;
        background: #fff;
        transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
    }
    .pf-verifier-row:hover .pf-verifier-dot { transform: scale(1.18); }
    .pf-verifier-dot.done     { border-color: #10b981; color: #059669; background: #d1fae5; }
    .pf-verifier-dot.active   { border-color: #3b82f6; color: #2563eb; background: #dbeafe; animation: pf-pulse 2s infinite; }
    .pf-verifier-dot.revision { border-color: #f59e0b; color: #d97706; background: #fef3c7; }
    .pf-verifier-dot.rejected { border-color: #ef4444; color: #dc2626; background: #fee2e2; }
    .pf-verifier-dot.pending  { border-color: #cbd5e1; color: #94a3b8; background: #f8fafc; }

    .pf-progress-bar-track {
        height: 6px;
        background: #e2e8f0;
        border-radius: 3px;
        overflow: hidden;
        margin-top: 12px;
    }
    .pf-progress-bar-fill {
        height: 100%;
        background: linear-gradient(90deg, #10b981, #34d399);
        border-radius: 3px;
        transition: width 0.6s cubic-bezier(0.4, 0, 0.2, 1);
    }

    @media (max-width: 767px) {
        .parallel-flow {
            flex-direction: column;
            gap: 0;
        }
        .pf-connector {
            width: 4px; height: 28px;
            flex: 0 0 28px;
        }
        .pf-connector::before { width: 4px; height: 100%; }
        .pf-phase-start, .pf-phase-end { min-width: auto; }
    }
</style>
@endpush

<div class="card pf-wrap shadow-sm mb-4 border-0">
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
                    <div class="text-muted pf-mono mt-1" style="font-size: 0.58rem;">{{ $submitLog->created_at->format('d M, H:i') }}</div>
                @endif
            </div>

            {{-- Connector Left --}}
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
                                <div class="fw-semibold small text-dark text-truncate">{{ $ps['name'] }}</div>
                                <div class="text-muted text-truncate" style="font-size: 0.66rem;">{{ $ps['role'] }}</div>
                            </div>
                            <div class="d-flex align-items-center gap-2 flex-shrink-0">
                                <span class="badge bg-{{ $cfg['badge'] }}-subtle text-{{ $cfg['badge'] }} border border-{{ $cfg['badge'] }}-subtle rounded-pill" style="font-size: 0.56rem;">{{ $cfg['label'] }}</span>
                                @if($ps['log_at'])
                                    <span class="text-muted pf-mono d-none d-sm-inline" style="font-size: 0.56rem;">{{ $ps['log_at']->format('d M, H:i') }}</span>
                                @endif
                            </div>
                        </div>
                    @endforeach

                    <div class="pf-progress-bar-track">
                        <div class="pf-progress-bar-fill" style="width: {{ $parallelPct }}%;"></div>
                    </div>
                    <div class="text-center mt-1">
                        <span class="text-muted pf-mono" style="font-size: 0.6rem;">{{ $parallelDone }}/{{ $parallelTotal }} verifikator selesai</span>
                    </div>
                </div>
            </div>

            {{-- Connector Right --}}
            <div class="pf-connector {{ $connectorRightState }}"></div>

            {{-- ③ PHASE: Kasubbag --}}
            <div class="pf-phase pf-phase-end">
                @php $kCfg = $stateCfg[$kasubbagState]; @endphp
                <div class="pf-node {{ $kasubbagState }}">
                    <i class="bi bi-patch-check-fill"></i>
                </div>
                <div class="fw-bold small text-{{ $kCfg['badge'] }} mt-2 text-truncate" style="max-width: 110px;">{{ $kasubbagName }}</div>
                <div class="text-muted" style="font-size: 0.68rem;">Kasubbag</div>
                <span class="badge bg-{{ $kCfg['badge'] }}-subtle text-{{ $kCfg['badge'] }} border border-{{ $kCfg['badge'] }}-subtle rounded-pill mt-1" style="font-size: 0.58rem;">{{ $kCfg['label'] }}</span>
                @if($kasubbagLogAt)
                    <div class="text-muted pf-mono mt-1" style="font-size: 0.58rem;">{{ $kasubbagLogAt->format('d M, H:i') }}</div>
                @endif
            </div>

        </div>

        {{-- Revision Alert --}}
        @if($lastRevisionLog && (str_starts_with($status, 'REVISI_') || str_contains(strtolower($status), 'revisi')))
            <div class="border border-warning border-opacity-50 bg-warning bg-opacity-10 rounded-3 p-3 mt-4 mb-0">
                <div class="d-flex gap-3 align-items-start">
                    <div class="rounded-circle bg-warning bg-opacity-25 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 36px; height: 36px;">
                        <i class="bi bi-exclamation-triangle-fill text-warning"></i>
                    </div>
                    <div class="flex-fill">
                        <div class="fw-bold small text-dark mb-1">Catatan Revisi</div>
                        <p class="mb-2 small text-dark">{{ $lastRevisionLog->catatan ?? '-' }}</p>
                        <div class="d-flex align-items-center gap-2">
                            <small class="text-muted pf-mono" style="font-size: 0.68rem;"><i class="bi bi-clock me-1"></i>{{ $lastRevisionLog->created_at->format('d M Y, H:i') }}</small>
                            @if($lastRevisionLog->user)
                                <small class="text-muted" style="font-size: 0.68rem;"><i class="bi bi-person-circle me-1"></i>{{ $lastRevisionLog->user->name ?? '-' }}</small>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @elseif($status === 'DISETUJUI')
            <div class="bg-success bg-opacity-10 border border-success border-opacity-30 rounded-3 p-3 mt-4 mb-0 text-center">
                <i class="bi bi-check-circle-fill text-success me-2"></i>
                <strong class="text-success small">Dokumen telah disetujui seluruh verifikator dan siap diproses ke pembuatan SPP.</strong>
            </div>
        @endif
    </div>
</div>
