{{-- partial: workflow-progress.blade.php --}}
{{-- Variables: $tagihan --}}
@php
    $status = $tagihan->status;
    $workflow = collect($tagihan->workflowInstances ?? [])->sortByDesc('created_at')->first();
    $approvals = collect($workflow?->approvals ?? [])->sortBy([['urutan_step', 'asc'], ['id', 'asc']])->values();

    $roleLabels = [
        'PPSPM' => 'PPSPM',
        'BENDAHARA_PENERIMAAN' => 'Bendahara Penerimaan',
        'BENDAHARA_PENGELUARAN' => 'Bendahara Pengeluaran',
        'PPK' => 'PPK',
        'KASUBBAG' => 'Kepala Subbagian Keuangan dan Tata Usaha',
        'Kepala Subbagian Keuangan dan Tata Usaha' => 'Kepala Subbagian Keuangan dan Tata Usaha',
    ];

    $stateFromApproval = function ($approval) use ($workflow) {
        if (!$approval) {
            return 'pending';
        }

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
        if ($hasRejected) {
            $paralelState = 'rejected';
        } elseif ($hasRevision) {
            $paralelState = 'revision';
        } elseif ($allApproved) {
            $paralelState = 'done';
        } elseif ($workflow && (int) $workflow->step_saat_ini === 1) {
            $paralelState = 'active';
        }

        $latestActedAt = $step1Approvals->max('acted_at');

        $steps = collect([
            [
                'label' => 'Pengajuan',
                'sublabel' => 'Operator Perjaldin',
                'icon' => 'bi-person-fill-up',
                'state' => 'done',
                'acted_at' => optional($tagihan->logs->firstWhere('aksi', 'SUBMIT'))->created_at,
            ],
            [
                'label' => 'Verifikasi Paralel',
                'sublabel' => 'PPSPM, Bendahara Penerimaan, Bendahara Pengeluaran, PPK',
                'icon' => 'bi-clipboard2-check',
                'state' => $paralelState,
                'acted_at' => $latestActedAt ? \Carbon\Carbon::parse($latestActedAt) : null,
            ],
        ]);

        if ($kasubbagApproval) {
            $steps->push([
                'label' => 'Persetujuan Kepala Subbagian Keuangan dan Tata Usaha',
                'sublabel' => $kasubbagApproval->actedByUser?->name ?? 'Kepala Subbagian Keuangan dan Tata Usaha',
                'icon' => 'bi-patch-check',
                'state' => $stateFromApproval($kasubbagApproval),
                'acted_at' => $kasubbagApproval->acted_at ? \Carbon\Carbon::parse($kasubbagApproval->acted_at) : null,
            ]);
        }
    } else {
        $steps = collect([
            ['label' => 'Pengajuan', 'sublabel' => 'Operator Perjaldin', 'icon' => 'bi-person-fill-up', 'state' => 'done', 'acted_at' => null],
            ['label' => 'Verifikasi Paralel', 'sublabel' => 'PPSPM, Bendahara Penerimaan, Bendahara Pengeluaran, PPK', 'icon' => 'bi-clipboard2-check', 'state' => str_starts_with($status, 'PENDING_') ? 'active' : 'pending', 'acted_at' => null],
            ['label' => 'Persetujuan Kepala Subbagian Keuangan dan Tata Usaha', 'sublabel' => 'Kepala Subbagian Keuangan dan Tata Usaha', 'icon' => 'bi-patch-check', 'state' => $status === 'PENDING_KASUBBAG' ? 'active' : ($status === 'DISETUJUI_PERJALDIN' ? 'done' : 'pending'), 'acted_at' => null],
        ]);
    }

    $stateColors = [
        'done' => ['bg' => 'bg-success', 'text' => 'text-success'],
        'active' => ['bg' => 'bg-primary', 'text' => 'text-primary'],
        'revision' => ['bg' => 'bg-warning', 'text' => 'text-warning'],
        'rejected' => ['bg' => 'bg-danger', 'text' => 'text-danger'],
        'pending' => ['bg' => 'bg-secondary', 'text' => 'text-secondary'],
    ];
    $stateLabels = [
        'done' => 'Selesai',
        'active' => 'Menunggu',
        'revision' => 'Revisi',
        'rejected' => 'Ditolak',
        'pending' => 'Belum',
    ];
    $stateIcons = [
        'done' => 'bi-check-circle-fill',
        'active' => 'bi-clock-fill',
        'revision' => 'bi-arrow-counterclockwise',
        'rejected' => 'bi-x-circle-fill',
        'pending' => 'bi-circle',
    ];

    $lastRevisionLog = $tagihan->logs
        ->filter(fn($l) => str_contains(strtolower($l->status_baru ?? ''), 'revisi') || str_contains(strtolower($l->aksi ?? ''), 'revision'))
        ->sortByDesc('created_at')
        ->first();
@endphp

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-bottom py-3">
        <h6 class="mb-0 fw-bold text-dark">
            <i class="bi bi-diagram-3 text-primary me-2"></i>Progress Verifikasi
        </h6>
    </div>
    <div class="card-body py-4">
        <div class="d-flex align-items-start position-relative px-2 flex-wrap gap-3">
            @foreach($steps as $step)
                @php $c = $stateColors[$step['state']]; @endphp
                <div class="text-center" style="min-width:130px;flex:1">
                    <div class="d-inline-flex align-items-center justify-content-center rounded-circle text-white {{ $c['bg'] }} shadow-sm mb-2" style="width:40px;height:40px;">
                        <i class="bi {{ $stateIcons[$step['state']] }} fs-6"></i>
                    </div>
                    <div class="small fw-bold {{ $c['text'] }}">{{ $step['label'] }}</div>
                    <div style="font-size:0.72rem;" class="text-muted text-truncate px-1">{{ $step['sublabel'] }}</div>
                    <span class="badge mt-1 {{ $c['bg'] }}" style="font-size:0.68rem;">{{ $stateLabels[$step['state']] }}</span>
                    @if($step['acted_at'])
                        <div style="font-size:0.68rem;" class="text-muted mt-1">{{ $step['acted_at']->format('d M, H:i') }}</div>
                    @endif
                </div>
            @endforeach
        </div>

        @if($lastRevisionLog && str_starts_with($status, 'REVISI_'))
            <div class="alert alert-warning border-start border-4 border-warning mt-4 mb-0 py-3 rounded-3">
                <div class="d-flex gap-3 align-items-start">
                    <i class="bi bi-exclamation-triangle-fill text-warning fs-5 mt-1"></i>
                    <div>
                        <div class="fw-bold mb-1">Catatan Revisi</div>
                        <p class="mb-1 small">{{ $lastRevisionLog->catatan ?? 'Tidak ada catatan khusus.' }}</p>
                        <small class="text-muted">
                            <i class="bi bi-clock me-1"></i>{{ $lastRevisionLog->created_at->format('d M Y, H:i') }}
                        </small>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
