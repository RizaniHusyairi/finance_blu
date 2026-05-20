{{-- partial: workflow-progress.blade.php --}}
{{-- Variables: $tagihan --}}
@php
    $status = $tagihan->status;
    $workflow = collect($tagihan->workflowInstances ?? [])->sortByDesc('created_at')->first();
    $approvals = collect($workflow?->approvals ?? [])->sortBy([['urutan_step', 'asc'], ['id', 'asc']])->values();

    $stateFromApproval = function ($approval) use ($workflow) {
        if (!$approval) return 'pending';
        return match ($approval->status) {
            'APPROVED' => 'done',
            'REVISION' => 'revision',
            'REJECTED' => 'rejected',
            'PENDING' => ($workflow && (int) $workflow->step_saat_ini === (int) $approval->urutan_step) ? 'active' : 'pending',
            default => 'pending',
        };
    };

    if ($approvals->isNotEmpty()) {
        $step1Approvals = $approvals->where('urutan_step', 1);
        $kasubbagApproval = $approvals->where('urutan_step', 2)->first()
            ?? $approvals->where('role_code', 'KASUBBAG')->first();

        $hasRejected = $step1Approvals->contains('status', 'REJECTED');
        $hasRevision = $step1Approvals->contains('status', 'REVISION');
        $allApproved = $step1Approvals->count() > 0 && $step1Approvals->every(fn($a) => $a->status === 'APPROVED');

        $paralelState = 'pending';
        if ($hasRejected)        $paralelState = 'rejected';
        elseif ($hasRevision)    $paralelState = 'revision';
        elseif ($allApproved)    $paralelState = 'done';
        elseif ($workflow && (int) $workflow->step_saat_ini === 1) $paralelState = 'active';

        $latestActedAt = $step1Approvals->max('acted_at');

        $steps = collect([
            [
                'label' => 'Pengajuan',
                'sublabel' => 'Operator Perjaldin',
                'icon' => 'bi-send-fill',
                'state' => 'done',
                'acted_at' => optional($tagihan->logs->firstWhere('aksi', 'SUBMIT'))->created_at,
            ],
            [
                'label' => 'Verifikasi Paralel',
                'sublabel' => 'PPSPM, Bend. Penerimaan, Bend. Pengeluaran, PPK',
                'icon' => 'bi-clipboard2-check-fill',
                'state' => $paralelState,
                'acted_at' => $latestActedAt ? \Carbon\Carbon::parse($latestActedAt) : null,
            ],
        ]);

        if ($kasubbagApproval) {
            $steps->push([
                'label' => 'Persetujuan Kasubbag',
                'sublabel' => $kasubbagApproval->actedByUser?->name ?? 'Kepala Subbagian Keu & TU',
                'icon' => 'bi-patch-check-fill',
                'state' => $stateFromApproval($kasubbagApproval),
                'acted_at' => $kasubbagApproval->acted_at ? \Carbon\Carbon::parse($kasubbagApproval->acted_at) : null,
            ]);
        }
    } else {
        $steps = collect([
            ['label' => 'Pengajuan', 'sublabel' => 'Operator Perjaldin', 'icon' => 'bi-send-fill', 'state' => 'done', 'acted_at' => null],
            ['label' => 'Verifikasi Paralel', 'sublabel' => 'PPSPM, Bend. Penerimaan, Bend. Pengeluaran, PPK', 'icon' => 'bi-clipboard2-check-fill', 'state' => str_starts_with($status, 'PENDING_') ? 'active' : 'pending', 'acted_at' => null],
            ['label' => 'Persetujuan Kasubbag', 'sublabel' => 'Kepala Subbagian Keu & TU', 'icon' => 'bi-patch-check-fill', 'state' => $status === 'PENDING_KASUBBAG' ? 'active' : ($status === 'DISETUJUI_PERJALDIN' ? 'done' : 'pending'), 'acted_at' => null],
        ]);
    }

    $stateLabels = [
        'done' => 'Selesai',
        'active' => 'Berjalan',
        'revision' => 'Revisi',
        'rejected' => 'Ditolak',
        'pending' => 'Menunggu',
    ];
    $stateIcons = [
        'done' => 'bi-check-lg',
        'active' => 'bi-clock-fill',
        'revision' => 'bi-arrow-counterclockwise',
        'rejected' => 'bi-x-lg',
        'pending' => 'bi-circle',
    ];

    $totalSteps = $steps->count();
    $doneCount = $steps->where('state', 'done')->count();
    $progressPct = $totalSteps > 0 ? round(($doneCount / $totalSteps) * 100) : 0;

    $lastRevisionLog = $tagihan->logs
        ->filter(fn($l) => str_contains(strtolower($l->status_baru ?? ''), 'revisi') || str_contains(strtolower($l->aksi ?? ''), 'revision'))
        ->sortByDesc('created_at')
        ->first();
@endphp

<div class="modern-card">
    <div class="mc-head mc-icon-primary">
        <div class="mc-head-left">
            <div class="mc-icon"><i class="bi bi-diagram-3-fill"></i></div>
            <div>
                <h6 class="mc-title">Progress Verifikasi</h6>
                <p class="mc-sub">Alur persetujuan dokumen perjalanan dinas</p>
            </div>
        </div>
        <span class="mc-pill mc-pill-primary">
            <i class="bi bi-bar-chart-fill"></i> {{ $progressPct }}% ({{ $doneCount }}/{{ $totalSteps }})
        </span>
    </div>
    <div class="mc-body">
        <div class="wf-stepper">
            @foreach($steps as $step)
                <div class="wf-step wf-state-{{ $step['state'] }} {{ $step['state'] === 'active' ? 'is-active' : '' }}">
                    <div class="wf-circle">
                        <i class="bi {{ $stateIcons[$step['state']] }}"></i>
                    </div>
                    <p class="wf-label">{{ $step['label'] }}</p>
                    <p class="wf-sublabel">{{ \Illuminate\Support\Str::limit($step['sublabel'], 50) }}</p>
                    <span class="wf-state-pill">
                        <i class="bi {{ $stateIcons[$step['state']] }}"></i> {{ $stateLabels[$step['state']] }}
                    </span>
                    @if($step['acted_at'])
                        <div class="wf-time">
                            <i class="bi bi-clock"></i> {{ $step['acted_at']->isoFormat('D MMM, HH:mm') }}
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        @if($lastRevisionLog && str_starts_with($status, 'REVISI_'))
            <div class="wf-revision-callout">
                <div class="wfr-icon"><i class="bi bi-exclamation-triangle-fill"></i></div>
                <div>
                    <p class="wfr-title">Catatan Revisi</p>
                    <p>{{ $lastRevisionLog->catatan ?? 'Tidak ada catatan khusus.' }}</p>
                    <small class="text-muted" style="font-size: .72rem;">
                        <i class="bi bi-clock me-1"></i>{{ $lastRevisionLog->created_at->isoFormat('D MMMM YYYY, HH:mm') }}
                    </small>
                </div>
            </div>
        @endif
    </div>
</div>
