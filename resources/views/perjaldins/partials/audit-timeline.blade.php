{{-- partial: audit-timeline.blade.php --}}
{{-- Variables: $tagihan --}}
@php
    $aksiMap = [
        'CREATE'   => ['icon' => 'bi-plus-circle-fill', 'cls' => 'tm-primary'],
        'SUBMIT'   => ['icon' => 'bi-send-fill',         'cls' => 'tm-primary'],
        'APPROVE'  => ['icon' => 'bi-check-circle-fill', 'cls' => 'tm-success'],
        'REVISION' => ['icon' => 'bi-arrow-counterclockwise', 'cls' => 'tm-warning'],
        'REJECT'   => ['icon' => 'bi-x-circle-fill',     'cls' => 'tm-danger'],
        'UPDATE'   => ['icon' => 'bi-pencil-fill',       'cls' => 'tm-info'],
    ];
@endphp

<div class="modern-card">
    <div class="mc-head mc-icon-info">
        <div class="mc-head-left">
            <div class="mc-icon"><i class="bi bi-clock-history"></i></div>
            <div>
                <h6 class="mc-title">Jejak Proses (Audit Trail)</h6>
                <p class="mc-sub">Riwayat seluruh aktivitas pada dokumen perjalanan dinas ini</p>
            </div>
        </div>
        <span class="mc-pill mc-pill-info">
            <i class="bi bi-journal-text"></i> {{ $tagihan->logs->count() }} aktivitas
        </span>
    </div>
    <div class="mc-body">
        @if($tagihan->logs->isEmpty())
            <div class="empty-state-modern">
                <i class="bi bi-journal-x"></i>
                <h6 class="text-secondary fw-bold mb-1">Belum ada aktivitas</h6>
                <small>Riwayat aktivitas dokumen akan muncul di sini.</small>
            </div>
        @else
            <div class="timeline-modern">
                @foreach($tagihan->logs as $log)
                    @php
                        $aksi = strtoupper($log->aksi ?? 'UPDATE');
                        $meta = $aksiMap[$aksi] ?? ['icon' => 'bi-circle-fill', 'cls' => 'tm-primary'];
                    @endphp
                    <div class="tm-item {{ $meta['cls'] }}">
                        <span class="tm-dot"><i class="bi" style="color:#fff; font-size:.7rem; margin: 6px 0 0 0; display:none;"></i></span>
                        <div class="tm-card">
                            <div class="tm-row">
                                <div>
                                    <span class="tm-aksi-pill"><i class="bi {{ $meta['icon'] }}"></i> {{ $log->aksi ?? 'UPDATE' }}</span>
                                </div>
                                <span class="tm-time"><i class="bi bi-clock"></i> {{ $log->created_at->isoFormat('D MMM YYYY, HH:mm') }}</span>
                            </div>
                            <div class="tm-status-flow mt-1">
                                @if($log->status_sebelumnya)
                                    <span class="tm-prev">{{ \Illuminate\Support\Str::title(strtolower(str_replace('_', ' ', $log->status_sebelumnya))) }}</span>
                                    <i class="bi bi-arrow-right tm-arrow"></i>
                                @endif
                                <span class="tm-new">{{ \Illuminate\Support\Str::title(strtolower(str_replace('_', ' ', $log->status_baru))) }}</span>
                            </div>
                            @if($log->catatan)
                                <div class="tm-note">
                                    <i class="bi bi-chat-quote-fill"></i>{{ $log->catatan }}
                                </div>
                            @endif
                            @if($log->user)
                                <div class="tm-user">
                                    <i class="bi bi-person-circle"></i> {{ $log->user->name ?? '-' }}
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
