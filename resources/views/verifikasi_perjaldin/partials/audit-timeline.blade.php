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

<div class="card info-doc-card shadow-sm mb-4">
    <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
        <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-clock-history text-primary me-2"></i>Jejak Proses</h6>
        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle rounded-pill small px-3">
            {{ $tagihan->logs->count() }} Entri
        </span>
    </div>
    <div class="card-body py-3">
        @if($tagihan->logs->isEmpty())
            <div class="text-center text-muted py-5">
                <i class="bi bi-clock display-5 d-block mb-3 text-secondary"></i>
                <p class="small mb-0">Belum ada rekaman aktivitas.</p>
            </div>
        @else
            <div class="position-relative ps-5" style="padding-left: 48px !important;">
                {{-- Vertical Timeline Line --}}
                <div class="timeline-line"></div>

                @foreach($tagihan->logs->sortByDesc('created_at') as $log)
                    @php $m = $aksiMap[strtoupper($log->aksi ?? '')] ?? ['color' => 'secondary', 'icon' => 'bi-circle-fill']; @endphp
                    <div class="timeline-item position-relative mb-4">
                        {{-- Timeline Node --}}
                        <span class="timeline-node position-absolute"
                              style="left: -44px; top: 4px; border-color: var(--bs-{{ $m['color'] }});">
                            <i class="bi {{ $m['icon'] }} text-{{ $m['color'] }}" style="font-size: 0.72rem;"></i>
                        </span>

                        {{-- Timeline Content --}}
                        <div class="chat-bubble-timeline ms-1">
                            <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap">
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    <span class="badge bg-{{ $m['color'] }}-subtle text-{{ $m['color'] }} border border-{{ $m['color'] }}-subtle rounded-pill small px-2 py-1">{{ $log->aksi ?? '-' }}</span>
                                    @if($log->status_sebelumnya)
                                        <span class="small text-muted font-mono-premium" style="font-size: 0.68rem;">{{ $log->status_sebelumnya }}</span>
                                        <i class="bi bi-arrow-right small text-muted" style="font-size: 0.6rem;"></i>
                                    @endif
                                    <span class="fw-semibold small text-{{ $m['color'] }}">{{ $log->status_baru }}</span>
                                </div>
                                <span class="text-muted small text-nowrap font-mono-premium" style="font-size: 0.68rem;">{{ $log->created_at->format('d M Y, H:i') }}</span>
                            </div>

                            {{-- Comment Bubble --}}
                            @if($log->catatan)
                                <div class="mt-2 small border-start border-3 border-{{ $m['color'] }} ps-3 py-2 text-dark bg-{{ $m['color'] }}-subtle bg-opacity-25 rounded-end-3" style="font-size: 0.82rem;">
                                    <i class="bi bi-chat-quote text-{{ $m['color'] }} me-1"></i>{{ $log->catatan }}
                                </div>
                            @endif

                            {{-- User Info --}}
                            @if($log->user)
                                <div class="mt-2 d-flex align-items-center gap-2">
                                    <div class="rounded-circle bg-light d-flex align-items-center justify-content-center" style="width: 22px; height: 22px;">
                                        <i class="bi bi-person-fill text-muted" style="font-size: 0.65rem;"></i>
                                    </div>
                                    <small class="text-muted" style="font-size: 0.72rem;">{{ $log->user->name ?? '-' }}
                                        @if($log->role_saat_itu)
                                            <span class="badge bg-light text-secondary border ms-1 rounded-pill" style="font-size:.58rem;">{{ $log->role_saat_itu }}</span>
                                        @endif
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
