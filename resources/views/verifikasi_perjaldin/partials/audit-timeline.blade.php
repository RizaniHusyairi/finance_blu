{{-- audit-timeline.blade.php for verifikasi --}}
{{-- Variables: $tagihan --}}
@php
    $aksiMap = [
        'SUBMIT'   => ['color' => 'primary', 'icon' => 'bi-send-fill'],
        'APPROVE'  => ['color' => 'success', 'icon' => 'bi-check-circle-fill'],
        'REVISION' => ['color' => 'warning', 'icon' => 'bi-arrow-counterclockwise'],
        'REJECT'   => ['color' => 'danger',  'icon' => 'bi-x-circle-fill'],
        'UPDATE'   => ['color' => 'info',    'icon' => 'bi-pencil-fill'],
        'CREATE'   => ['color' => 'secondary','icon' => 'bi-plus-circle-fill'],
    ];
@endphp

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-bottom py-3">
        <h6 class="mb-0 fw-bold"><i class="bi bi-clock-history text-primary me-2"></i>Jejak Proses</h6>
    </div>
    <div class="card-body py-3">
        @if($tagihan->logs->isEmpty())
            <div class="text-center text-muted py-4">
                <i class="bi bi-clock display-6 d-block mb-2"></i>
                <small>Belum ada rekaman aktivitas.</small>
            </div>
        @else
            <div class="position-relative ps-4" style="border-left:2px solid #dee2e6;">
                @foreach($tagihan->logs->sortByDesc('created_at') as $log)
                    @php $m = $aksiMap[strtoupper($log->aksi ?? '')] ?? ['color' => 'secondary', 'icon' => 'bi-circle-fill']; @endphp
                    <div class="position-relative mb-4">
                        <span class="position-absolute d-flex align-items-center justify-content-center bg-{{ $m['color'] }} text-white rounded-circle shadow-sm"
                              style="width:28px;height:28px;left:-42px;top:2px;font-size:0.72rem;">
                            <i class="bi {{ $m['icon'] }}"></i>
                        </span>
                        <div class="card border-0 bg-light rounded-3 px-3 py-2 ms-1">
                            <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap">
                                <div>
                                    <span class="badge bg-{{ $m['color'] }}-subtle text-{{ $m['color'] }} border border-{{ $m['color'] }}-subtle rounded-pill me-1 small">{{ $log->aksi ?? '-' }}</span>
                                    @if($log->status_sebelumnya)
                                        <span class="small text-muted">{{ $log->status_sebelumnya }}</span>
                                        <i class="bi bi-arrow-right small text-muted mx-1"></i>
                                    @endif
                                    <span class="fw-semibold small text-{{ $m['color'] }}">{{ $log->status_baru }}</span>
                                </div>
                                <span class="text-muted small text-nowrap">{{ $log->created_at->format('d M Y, H:i') }}</span>
                            </div>
                            @if($log->catatan)
                                <div class="mt-2 small border-start border-3 border-secondary ps-2 text-dark bg-white rounded py-1">
                                    <i class="bi bi-chat-quote text-muted me-1"></i>{{ $log->catatan }}
                                </div>
                            @endif
                            @if($log->user)
                                <div class="mt-1">
                                    <small class="text-muted"><i class="bi bi-person-circle me-1"></i>{{ $log->user->name ?? '-' }}
                                        @if($log->role_saat_itu)<span class="badge bg-light text-secondary border ms-1" style="font-size:.65rem;">{{ $log->role_saat_itu }}</span>@endif
                                    </small>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
