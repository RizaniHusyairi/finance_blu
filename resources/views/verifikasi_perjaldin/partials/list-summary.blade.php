{{-- list-summary.blade.php --}}
{{-- Variables: $tagihans, $roleLabel, $pendingStatus --}}
@php
    $totalPending  = isset($tagihansPerlu) ? $tagihansPerlu->count() : $tagihans->whereIn('status', $pendingStatuses)->count();
    $totalRevisi   = $tagihans->whereIn('status', $revisiStatuses)->count();
    $totalSelesai  = isset($tagihansRiwayat) ? $tagihansRiwayat->count() : $tagihans->whereIn('status', $selesaiStatuses)->count();
    $totalNominal  = isset($tagihansPerlu) ? $tagihansPerlu->sum('total_bruto') : $tagihans->whereIn('status', $pendingStatuses)->sum('total_bruto');
@endphp

<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100" style="border-left: 4px solid #0d6efd !important;">
            <div class="card-body p-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center" style="width:46px;height:46px;flex-shrink:0;">
                        <i class="bi bi-hourglass-split text-primary fs-5"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Menunggu Saya</div>
                        <div class="fw-bold fs-4 text-primary lh-1">{{ $totalPending }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100" style="border-left: 4px solid #ffc107 !important;">
            <div class="card-body p-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-warning bg-opacity-10 d-flex align-items-center justify-content-center" style="width:46px;height:46px;flex-shrink:0;">
                        <i class="bi bi-arrow-counterclockwise text-warning fs-5"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Perlu Revisi</div>
                        <div class="fw-bold fs-4 text-warning lh-1">{{ $totalRevisi }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100" style="border-left: 4px solid #198754 !important;">
            <div class="card-body p-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-success bg-opacity-10 d-flex align-items-center justify-content-center" style="width:46px;height:46px;flex-shrink:0;">
                        <i class="bi bi-check-circle text-success fs-5"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Sudah Diproses</div>
                        <div class="fw-bold fs-4 text-success lh-1">{{ $totalSelesai }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100" style="border-left: 4px solid #6f42c1 !important;">
            <div class="card-body p-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle" style="width:46px;height:46px;flex-shrink:0;background:rgba(111,66,193,0.1);display:flex;align-items:center;justify-content:center;">
                        <i class="bi bi-cash-stack fs-5" style="color:#6f42c1;"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Total Nominal Pending</div>
                        <div class="fw-bold text-purple lh-1" style="font-size:0.95rem;color:#6f42c1;">
                            Rp {{ number_format($totalNominal, 0, ',', '.') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
