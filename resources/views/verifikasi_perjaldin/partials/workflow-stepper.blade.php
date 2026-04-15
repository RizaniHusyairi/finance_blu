{{-- workflow-stepper.blade.php --}}
{{-- Variables: $tagihan, $userRole ('PPK' | 'Bendahara Pengeluaran') --}}
@php
    $status = $tagihan->status;
    $logs   = $tagihan->logs->sortBy('created_at');

    // Helper: ambil log terakhir per status
    $logByStatus = fn(string $s) => $logs->firstWhere('status_baru', $s);
    $logPendingBendahara = $logByStatus('PENDING_BENDAHARA');
    $logDisetujui = $logByStatus('DISETUJUI_PERJALDIN');
    $logRevisiPpk = $logs->filter(fn($l) => $l->status_baru === 'REVISI_PPK')->last();
    $logRevisiBendahara = $logs->filter(fn($l) => $l->status_baru === 'REVISI_BENDAHARA')->last();

    // Step state
    $stepDraft = 'done'; // always done
    $stepPpk = match(true) {
        $status === 'REVISI_PPK'  => 'revision',
        $status === 'DITOLAK_PPK' => 'rejected',
        $status === 'PENDING_PPK' => 'active',
        in_array($status, ['PENDING_BENDAHARA','REVISI_BENDAHARA','DISETUJUI_PERJALDIN']) => 'done',
        default => 'pending',
    };
    $stepBendahara = match(true) {
        $status === 'REVISI_BENDAHARA'  => 'revision',
        $status === 'DITOLAK_BENDAHARA' => 'rejected',
        $status === 'PENDING_BENDAHARA' => 'active',
        $status === 'DISETUJUI_PERJALDIN' => 'done',
        default => 'pending',
    };

    $stateCfg = [
        'done'     => ['bg' => 'bg-success',   'text' => 'text-success',   'icon' => 'bi-check-circle-fill', 'label' => 'Selesai'],
        'active'   => ['bg' => 'bg-primary',   'text' => 'text-primary',   'icon' => 'bi-clock-fill',        'label' => 'Menunggu'],
        'revision' => ['bg' => 'bg-warning',   'text' => 'text-warning',   'icon' => 'bi-arrow-counterclockwise', 'label' => 'Revisi'],
        'rejected' => ['bg' => 'bg-danger',    'text' => 'text-danger',    'icon' => 'bi-x-circle-fill',     'label' => 'Ditolak'],
        'pending'  => ['bg' => 'bg-secondary', 'text' => 'text-secondary', 'icon' => 'bi-circle',            'label' => 'Belum'],
    ];

    $submitLog = $logs->firstWhere('aksi', 'SUBMIT');
    $ppkApproveLog = $logs->filter(fn($l) => $l->aksi === 'APPROVE' && str_contains($l->role_saat_itu, 'PPK'))->last();
    $bendaharaApproveLog = $logs->filter(fn($l) => $l->aksi === 'APPROVE' && str_contains($l->role_saat_itu ?? '', 'Bendahara'))->last();
@endphp

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-bottom py-3">
        <h6 class="mb-0 fw-bold"><i class="bi bi-diagram-3 text-primary me-2"></i>Posisi Verifikasi</h6>
    </div>
    <div class="card-body py-4">
        {{-- Stepper --}}
        <div class="row g-0 mb-4">
            @foreach([
                ['label' => 'Pengajuan', 'sub' => 'Operator Perjaldin', 'icon' => 'bi-person-fill-up', 'state' => $stepDraft, 'log' => $submitLog],
                ['label' => 'Verifikasi PPK', 'sub' => $tagihan->ppk_nama_snapshot ?? 'PPK', 'icon' => 'bi-clipboard2-check', 'state' => $stepPpk, 'log' => $ppkApproveLog],
                ['label' => 'Verifikasi Bendahara', 'sub' => $tagihan->bendahara_pengeluaran_nama_snapshot ?? 'Bendahara Pengeluaran', 'icon' => 'bi-bank', 'state' => $stepBendahara, 'log' => $bendaharaApproveLog],
            ] as $i => $step)
                @php $cfg = $stateCfg[$step['state']]; @endphp
                <div class="col text-center position-relative">
                    @if($i > 0)
                        <div class="position-absolute top-0 start-0 w-50" style="height:2px;top:20px;z-index:0;
                            background:{{ in_array($step['state'],['done','active','revision','rejected']) ? '#6c757d' : '#dee2e6' }};"></div>
                    @endif
                    @if($i < 2)
                        <div class="position-absolute top-0 end-0 w-50" style="height:2px;top:20px;z-index:0;
                            background:#dee2e6;"></div>
                    @endif
                    <div class="d-inline-flex align-items-center justify-content-center rounded-circle text-white {{ $cfg['bg'] }} shadow-sm mb-2 position-relative" style="width:40px;height:40px;z-index:1;">
                        <i class="bi {{ $cfg['icon'] }}"></i>
                    </div>
                    <div class="small fw-bold {{ $cfg['text'] }}">{{ $step['label'] }}</div>
                    <div style="font-size:0.72rem;" class="text-muted text-truncate px-1">{{ $step['sub'] }}</div>
                    <span class="badge {{ $cfg['bg'] }} mt-1" style="font-size:0.68rem;">{{ $cfg['label'] }}</span>
                    @if($step['log'] && $step['log']->created_at)
                        <div style="font-size:0.68rem;" class="text-muted mt-1">{{ $step['log']->created_at->format('d M, H:i') }}</div>
                    @endif
                </div>
            @endforeach
        </div>

        {{-- Revision / Rejection Alert --}}
        @if($logRevisiPpk && $status === 'REVISI_PPK')
            <div class="alert alert-warning border-start border-4 border-warning py-3 mb-0 rounded-3">
                <div class="d-flex gap-3 align-items-start">
                    <i class="bi bi-exclamation-triangle-fill text-warning fs-5 mt-1"></i>
                    <div>
                        <div class="fw-bold small mb-1">Catatan Revisi dari PPK</div>
                        <p class="mb-1 small">{{ $logRevisiPpk->catatan ?? '-' }}</p>
                        <small class="text-muted"><i class="bi bi-clock me-1"></i>{{ $logRevisiPpk->created_at->format('d M Y, H:i') }}</small>
                    </div>
                </div>
            </div>
        @elseif($logRevisiBendahara && $status === 'REVISI_BENDAHARA')
            <div class="alert alert-warning border-start border-4 border-warning py-3 mb-0 rounded-3">
                <div class="d-flex gap-3 align-items-start">
                    <i class="bi bi-exclamation-triangle-fill text-warning fs-5 mt-1"></i>
                    <div>
                        <div class="fw-bold small mb-1">Catatan Revisi dari Bendahara Pengeluaran</div>
                        <p class="mb-1 small">{{ $logRevisiBendahara->catatan ?? '-' }}</p>
                        <small class="text-muted"><i class="bi bi-clock me-1"></i>{{ $logRevisiBendahara->created_at->format('d M Y, H:i') }}</small>
                    </div>
                </div>
            </div>
        @elseif($status === 'DISETUJUI_PERJALDIN')
            <div class="alert alert-success border-0 py-2 mb-0 text-center rounded-3">
                <i class="bi bi-check-circle-fill me-2"></i><strong>Dokumen telah disetujui penuh dan siap diproses lebih lanjut.</strong>
            </div>
        @endif
    </div>
</div>
