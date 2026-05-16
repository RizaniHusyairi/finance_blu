{{--
    Partial: Konten aktivitas tagihan (timeline 5 tahap).
    Dipakai oleh modal di show_kontrak.blade.php dan halaman publik public/tagihan-aktivitas.blade.php.

    Variabel yang dibutuhkan:
      $tagihan (App\Models\Tagihan) — sudah eager-load spps.spm.npi.sp2d + workflowInstance.approvals
--}}

@php
    $approvalMetaMap = [
        'APPROVED' => ['icon' => 'check-circle-fill',    'class' => 'success', 'label' => 'Disetujui'],
        'PENDING'  => ['icon' => 'hourglass-split',      'class' => 'warning text-dark', 'label' => 'Menunggu'],
        'WAITING'  => ['icon' => 'clock-history',        'class' => 'secondary',         'label' => 'Belum aktif'],
        'REVISION' => ['icon' => 'arrow-counterclockwise','class' => 'warning text-dark', 'label' => 'Revisi diminta'],
        'REJECTED' => ['icon' => 'x-circle-fill',        'class' => 'danger',  'label' => 'Ditolak'],
    ];

    $renderDocBadge = function ($status) {
        if ($status === null || $status === '') {
            return ['cls' => 'bg-light text-muted border', 'label' => 'Belum dibuat'];
        }
        $up = strtoupper(str_replace(' ', '_', (string) $status));
        if (str_contains($up, 'TERBIT') || str_contains($up, 'DISETUJUI') || str_contains($up, 'APPROVED') || $up === 'EXECUTED') {
            return ['cls' => 'bg-success', 'label' => $status];
        }
        if (str_contains($up, 'REVISI') || str_contains($up, 'REVISION')) {
            return ['cls' => 'bg-warning text-dark', 'label' => $status];
        }
        if (str_contains($up, 'TOLAK') || str_contains($up, 'REJECT')) {
            return ['cls' => 'bg-danger', 'label' => $status];
        }
        if (str_contains($up, 'PENDING') || str_contains($up, 'MENUNGGU')) {
            return ['cls' => 'bg-info text-white', 'label' => $status];
        }
        if (str_contains($up, 'DRAFT')) {
            return ['cls' => 'bg-secondary', 'label' => $status];
        }
        return ['cls' => 'bg-primary', 'label' => $status];
    };

    $renderApprovals = function ($workflowInstance) use ($approvalMetaMap) {
        if (!$workflowInstance) {
            return '<div class="text-muted small fst-italic"><i class="bi bi-info-circle me-1"></i>Workflow belum dimulai.</div>';
        }
        $approvals = $workflowInstance->approvals ?? collect();
        if ($approvals->isEmpty()) {
            return '<div class="text-muted small fst-italic">Belum ada step verifikasi.</div>';
        }
        $html = '<div class="list-group list-group-flush">';
        foreach ($approvals as $a) {
            $meta = $approvalMetaMap[$a->status] ?? ['icon' => 'circle', 'class' => 'secondary', 'label' => $a->status];
            $assigned = $a->assignedUser?->name ?? null;
            $acted = $a->actedByUser?->name ?? null;
            $when = $a->acted_at ? \Carbon\Carbon::parse($a->acted_at)->format('d M Y, H:i') : null;
            $note = $a->catatan ? e($a->catatan) : null;

            $html .= '<div class="list-group-item px-3 py-2 border-0 border-bottom">';
            $html .= '<div class="d-flex justify-content-between align-items-start gap-2 flex-wrap">';
            $html .= '<div class="flex-grow-1 min-width-0">';
            $html .= '<div class="d-flex align-items-center gap-2 flex-wrap mb-1">';
            $html .= '<span class="badge bg-light text-dark border" style="font-size:.7rem;">Step ' . e($a->urutan_step) . '</span>';
            $html .= '<span class="fw-semibold small">' . e($a->nama_step ?? $a->role_code) . '</span>';
            $html .= '<span class="text-muted small">·</span>';
            $html .= '<span class="text-muted small font-monospace" style="font-size:.7rem;">' . e($a->role_code) . '</span>';
            $html .= '</div>';
            if ($assigned) {
                $html .= '<div class="small"><i class="bi bi-person me-1 text-muted"></i>Verifikator: <span class="fw-semibold">' . e($assigned) . '</span></div>';
            } else {
                $html .= '<div class="small text-muted fst-italic"><i class="bi bi-person me-1"></i>Verifikator belum di-assign</div>';
            }
            if ($a->status === 'APPROVED' || $a->status === 'REJECTED' || $a->status === 'REVISION') {
                if ($acted) {
                    $html .= '<div class="small text-muted"><i class="bi bi-pen me-1"></i>Diproses oleh: ' . e($acted);
                    if ($when) $html .= ' · ' . $when;
                    $html .= '</div>';
                }
                if ($note) {
                    $html .= '<div class="small fst-italic mt-1 text-secondary">"' . $note . '"</div>';
                }
            } elseif ($a->status === 'PENDING') {
                $html .= '<div class="small text-info"><i class="bi bi-hourglass-split me-1"></i>Menunggu tindakan</div>';
            }
            $html .= '</div>';
            $html .= '<span class="badge bg-' . $meta['class'] . '" style="height:fit-content;"><i class="bi bi-' . $meta['icon'] . ' me-1"></i>' . $meta['label'] . '</span>';
            $html .= '</div></div>';
        }
        $html .= '</div>';
        return $html;
    };

    $tagihanWf = $tagihan->workflowInstance;
    $spps = $tagihan->spps ?? collect();
@endphp

{{-- ── TAHAP 1: Verifikasi Tagihan ───────────────────── --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white py-3 px-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div class="d-flex align-items-center gap-3">
            <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width:42px; height:42px;">
                <i class="bi bi-file-earmark-check"></i>
            </div>
            <div>
                <h6 class="fw-bold mb-0">Tahap 1 · Verifikasi Tagihan</h6>
                <small class="text-muted">Status: <span class="fw-semibold">{{ $tagihan->status }}</span></small>
            </div>
        </div>
        @php $b = $renderDocBadge($tagihan->status); @endphp
        <span class="badge {{ $b['cls'] }} px-3 py-2">{{ $b['label'] }}</span>
    </div>
    <div class="card-body p-2">
        {!! $renderApprovals($tagihanWf) !!}
    </div>
</div>

{{-- ── TAHAP 2+: SPP / SPM / NPI / SP2D per dokumen SPP ────── --}}
@if($spps->isEmpty())
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body text-center text-muted py-4">
            <i class="bi bi-file-earmark-x fs-3 d-block mb-2 text-secondary"></i>
            <div class="fw-semibold">SPP belum dibuat.</div>
            <small>Operator BLU akan membuat SPP setelah Tagihan disetujui Kasubbag.</small>
        </div>
    </div>
@else
    @foreach($spps as $sppIdx => $spp)
        @php
            $spm  = $spp->spm;
            $npi  = $spm?->npi;
            $sp2d = $npi?->sp2d;
        @endphp

        {{-- SPP --}}
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white py-3 px-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-info text-white d-flex align-items-center justify-content-center" style="width:42px; height:42px;">
                        <i class="bi bi-file-earmark-text"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-0">Tahap 2 · SPP{{ $spps->count() > 1 ? ' #' . ($sppIdx + 1) : '' }}</h6>
                        <small class="text-muted">
                            {{ $spp->nomor_spp ?? 'Belum bernomor' }}
                            @if($spp->tanggal_spp) · {{ \Carbon\Carbon::parse($spp->tanggal_spp)->format('d M Y') }} @endif
                        </small>
                    </div>
                </div>
                @php $b = $renderDocBadge($spp->status); @endphp
                <span class="badge {{ $b['cls'] }} px-3 py-2">{{ $b['label'] }}</span>
            </div>
            <div class="card-body p-2">
                {!! $renderApprovals($spp->workflowInstance) !!}
            </div>
        </div>

        {{-- SPM --}}
        <div class="card border-0 shadow-sm mb-3 {{ !$spm ? 'opacity-75' : '' }}">
            <div class="card-header bg-white py-3 px-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle text-white d-flex align-items-center justify-content-center" style="width:42px; height:42px; background-color: {{ $spm ? '#6610f2' : '#adb5bd' }};">
                        <i class="bi bi-card-checklist"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-0">Tahap 3 · SPM</h6>
                        <small class="text-muted">
                            @if($spm)
                                {{ $spm->nomor_spm ?? 'Belum bernomor' }}
                                @if($spm->tanggal_spm) · {{ \Carbon\Carbon::parse($spm->tanggal_spm)->format('d M Y') }} @endif
                            @else
                                Belum dibuat
                            @endif
                        </small>
                    </div>
                </div>
                @php $b = $renderDocBadge($spm?->status); @endphp
                <span class="badge {{ $b['cls'] }} px-3 py-2">{{ $b['label'] }}</span>
            </div>
            @if($spm)
                <div class="card-body p-2">
                    {!! $renderApprovals($spm->workflowInstance) !!}
                </div>
            @endif
        </div>

        {{-- NPI --}}
        <div class="card border-0 shadow-sm mb-3 {{ !$npi ? 'opacity-75' : '' }}">
            <div class="card-header bg-white py-3 px-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle text-white d-flex align-items-center justify-content-center" style="width:42px; height:42px; background-color: {{ $npi ? '#fd7e14' : '#adb5bd' }};">
                        <i class="bi bi-bank"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-0">Tahap 4 · NPI</h6>
                        <small class="text-muted">
                            @if($npi)
                                {{ $npi->nomor_npi ?? 'Belum bernomor' }}
                                @if($npi->tanggal_npi) · {{ \Carbon\Carbon::parse($npi->tanggal_npi)->format('d M Y') }} @endif
                            @else
                                Belum dibuat
                            @endif
                        </small>
                    </div>
                </div>
                @php $b = $renderDocBadge($npi?->status); @endphp
                <span class="badge {{ $b['cls'] }} px-3 py-2">{{ $b['label'] }}</span>
            </div>
            @if($npi)
                <div class="card-body p-2">
                    {!! $renderApprovals($npi->workflowInstance) !!}
                </div>
            @endif
        </div>

        {{-- SP2D --}}
        <div class="card border-0 shadow-sm mb-3 {{ !$sp2d ? 'opacity-75' : '' }}">
            <div class="card-header bg-white py-3 px-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle text-white d-flex align-items-center justify-content-center" style="width:42px; height:42px; background-color: {{ $sp2d ? '#198754' : '#adb5bd' }};">
                        <i class="bi bi-cash-coin"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-0">Tahap 5 · SP2D</h6>
                        <small class="text-muted">
                            @if($sp2d)
                                {{ $sp2d->nomor_sp2d ?? 'Belum bernomor' }}
                                @if($sp2d->tanggal_sp2d) · {{ \Carbon\Carbon::parse($sp2d->tanggal_sp2d)->format('d M Y') }} @endif
                            @else
                                Belum dibuat
                            @endif
                        </small>
                    </div>
                </div>
                @php $b = $renderDocBadge($sp2d?->status); @endphp
                <span class="badge {{ $b['cls'] }} px-3 py-2">{{ $b['label'] }}</span>
            </div>
            @if($sp2d)
                <div class="card-body p-2">
                    {!! $renderApprovals($sp2d->workflowInstance) !!}
                </div>
            @endif
        </div>

        @if(!$loop->last)
            <hr class="my-4">
        @endif
    @endforeach
@endif
