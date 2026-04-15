{{-- partial: workflow-progress.blade.php --}}
{{-- Variables: $tagihan --}}
@php
    $status = $tagihan->status;

    // Step states: 'done', 'active', 'revision', 'rejected', 'pending'
    $steps = [
        [
            'label' => 'Pengajuan',
            'sublabel' => 'Operator Perjaldin',
            'icon' => 'bi-person-fill-up',
            'state' => 'done', // always done if exists
        ],
        [
            'label' => 'Verifikasi PPK',
            'sublabel' => $tagihan->ppk_nama_snapshot ?? 'PPK',
            'icon' => 'bi-clipboard2-check',
            'state' => match(true) {
                in_array($status, ['REVISI_PPK']) => 'revision',
                in_array($status, ['DITOLAK_PPK']) => 'rejected',
                in_array($status, ['PENDING_PPK']) => 'active',
                in_array($status, ['DISETUJUI_PPK', 'PENDING_BENDAHARA', 'REVISI_BENDAHARA', 'DITOLAK_BENDAHARA', 'DISETUJUI_PERJALDIN']) => 'done',
                default => 'pending',
            },
        ],
        [
            'label' => 'Verifikasi Bendahara',
            'sublabel' => $tagihan->bendahara_pengeluaran_nama_snapshot ?? 'Bendahara Pengeluaran',
            'icon' => 'bi-bank',
            'state' => match(true) {
                in_array($status, ['REVISI_BENDAHARA']) => 'revision',
                in_array($status, ['DITOLAK_BENDAHARA']) => 'rejected',
                in_array($status, ['PENDING_BENDAHARA']) => 'active',
                in_array($status, ['DISETUJUI_PERJALDIN']) => 'done',
                default => 'pending',
            },
        ],
    ];

    $stateColors = [
        'done' => ['bg' => 'bg-success', 'text' => 'text-success', 'border' => 'border-success'],
        'active' => ['bg' => 'bg-primary', 'text' => 'text-primary', 'border' => 'border-primary'],
        'revision' => ['bg' => 'bg-warning', 'text' => 'text-warning', 'border' => 'border-warning'],
        'rejected' => ['bg' => 'bg-danger', 'text' => 'text-danger', 'border' => 'border-danger'],
        'pending' => ['bg' => 'bg-secondary', 'text' => 'text-secondary', 'border' => 'border-secondary'],
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

    // Last revision note
    $lastRevisionLog = $tagihan->logs
        ->filter(fn($l) => str_contains(strtolower($l->status_baru ?? ''), 'revisi') || str_contains(strtolower($l->aksi ?? ''), 'revisi'))
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
        {{-- Stepper --}}
        <div class="d-flex align-items-start position-relative px-2">
            @foreach($steps as $i => $step)
                @php $c = $stateColors[$step['state']]; @endphp
                <div class="flex-fill text-center position-relative" style="min-width:0">
                    {{-- Connector line before (not for first) --}}
                    @if($i > 0)
                        <div class="position-absolute top-0 start-0 w-50 {{ $step['state'] === 'done' ? 'border-success' : 'border-secondary' }}"
                             style="height:2px; border-top: 2px {{ $step['state'] !== 'pending' ? 'solid' : 'dashed' }} currentColor; top: 20px; margin-top:0; z-index:0;"></div>
                    @endif
                    {{-- Connector line after (not for last) --}}
                    @if(!$loop->last)
                        <div class="position-absolute top-0 end-0 w-50"
                             style="height:2px; border-top: 2px {{ $steps[$i+1]['state'] !== 'pending' ? 'solid #6c757d' : 'dashed #dee2e6' }}; top: 20px; z-index:0;"></div>
                    @endif

                    {{-- Circle Icon --}}
                    <div class="d-inline-flex align-items-center justify-content-center rounded-circle text-white {{ $c['bg'] }} shadow-sm mb-2 position-relative" style="width:40px;height:40px;z-index:1;">
                        <i class="bi {{ $stateIcons[$step['state']] }} fs-6"></i>
                    </div>
                    <div class="small fw-bold {{ $c['text'] }}">{{ $step['label'] }}</div>
                    <div style="font-size:0.72rem;" class="text-muted text-truncate px-1">{{ $step['sublabel'] }}</div>
                    <span class="badge mt-1 {{ $c['bg'] }}" style="font-size:0.68rem;">{{ $stateLabels[$step['state']] }}</span>
                </div>
            @endforeach
        </div>

        {{-- Revision Alert --}}
        @if($lastRevisionLog && in_array($status, ['REVISI_PPK', 'REVISI_BENDAHARA']))
            <div class="alert alert-warning border-start border-4 border-warning mt-4 mb-0 py-3 rounded-3">
                <div class="d-flex gap-3 align-items-start">
                    <i class="bi bi-exclamation-triangle-fill text-warning fs-5 mt-1"></i>
                    <div>
                        <div class="fw-bold mb-1">Catatan Revisi</div>
                        <p class="mb-1 small">{{ $lastRevisionLog->catatan ?? 'Tidak ada catatan khusus.' }}</p>
                        <small class="text-muted">
                            <i class="bi bi-clock me-1"></i>{{ $lastRevisionLog->created_at->format('d M Y, H:i') }}
                        </small>
                        <div class="mt-2 small text-warning fw-semibold">
                            <i class="bi bi-arrow-right-circle me-1"></i>Silakan perbaiki dokumen lalu ajukan kembali.
                        </div>
                    </div>
                </div>
            </div>
        @elseif(in_array($status, ['DITOLAK_PPK', 'DITOLAK_BENDAHARA']))
            @php
                $rejectedLog = $tagihan->logs->sortByDesc('created_at')->first();
            @endphp
            <div class="alert alert-danger border-start border-4 border-danger mt-4 mb-0 py-3 rounded-3">
                <div class="d-flex gap-3 align-items-start">
                    <i class="bi bi-x-octagon-fill text-danger fs-5 mt-1"></i>
                    <div>
                        <div class="fw-bold mb-1">Dokumen Ditolak</div>
                        <p class="mb-1 small">{{ $rejectedLog->catatan ?? 'Tidak ada keterangan.' }}</p>
                        <small class="text-muted"><i class="bi bi-clock me-1"></i>{{ $rejectedLog?->created_at?->format('d M Y, H:i') }}</small>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
