{{-- partial: audit-timeline.blade.php --}}
{{-- Variables: $tagihan --}}
@php
    $aksiColorMap = [
        'CREATE' => ['icon' => 'bi-plus-circle-fill', 'color' => 'primary'],
        'SUBMIT' => ['icon' => 'bi-send-fill', 'color' => 'primary'],
        'APPROVE' => ['icon' => 'bi-check-circle-fill', 'color' => 'success'],
        'REVISION' => ['icon' => 'bi-arrow-counterclockwise', 'color' => 'warning'],
        'REJECT' => ['icon' => 'bi-x-circle-fill', 'color' => 'danger'],
        'UPDATE' => ['icon' => 'bi-pencil-fill', 'color' => 'info'],
    ];
@endphp

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-bottom py-3">
        <h6 class="mb-0 fw-bold text-dark">
            <i class="bi bi-clock-history text-primary me-2"></i>Jejak Proses (Audit Trail)
        </h6>
    </div>
    <div class="card-body py-3">
        @if($tagihan->logs->isEmpty())
            <div class="text-center text-muted py-5">
                <i class="bi bi-clock display-6 d-block mb-2"></i>
                <small>Belum ada rekaman aktivitas.</small>
            </div>
        @else
            <div class="position-relative ps-4" style="border-left: 2px solid #dee2e6;">
                @foreach($tagihan->logs as $log)
                    @php
                        $aksi = strtoupper($log->aksi ?? '');
                        $meta = $aksiColorMap[$aksi] ?? ['icon' => 'bi-circle-fill', 'color' => 'secondary'];
                    @endphp
                    <div class="position-relative mb-4">
                        {{-- Circle dot --}}
                        <span class="position-absolute d-flex align-items-center justify-content-center bg-{{ $meta['color'] }} text-white rounded-circle shadow-sm"
                              style="width:30px;height:30px;left:-43px;top:0;font-size:0.75rem;">
                            <i class="bi {{ $meta['icon'] }}"></i>
                        </span>

                        <div class="card border-0 bg-light rounded-3 px-3 py-2 ms-1">
                            <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap">
                                <div>
                                    <span class="badge bg-{{ $meta['color'] }}-subtle text-{{ $meta['color'] }} border border-{{ $meta['color'] }}-subtle rounded-pill me-2 small">
                                        {{ $log->aksi ?? 'UPDATE' }}
                                    </span>
                                    @if($log->status_sebelumnya)
                                        <span class="small text-muted">{{ $log->status_sebelumnya }}</span>
                                        <i class="bi bi-arrow-right small text-muted mx-1"></i>
                                    @endif
                                    <span class="fw-semibold text-{{ $meta['color'] }} small">{{ $log->status_baru }}</span>
                                </div>
                                <span class="text-muted small text-nowrap">
                                    <i class="bi bi-clock me-1"></i>{{ $log->created_at->format('d M Y, H:i') }}
                                </span>
                            </div>
                            @if($log->catatan)
                                <div class="mt-2 small text-dark border-start border-3 border-secondary ps-2 bg-white rounded py-1">
                                    <i class="bi bi-chat-quote text-muted me-1"></i>{{ $log->catatan }}
                                </div>
                            @endif
                            @if($log->user)
                                <div class="mt-1">
                                    <small class="text-muted">
                                        <i class="bi bi-person-circle me-1"></i>{{ $log->user->name ?? '-' }}
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
