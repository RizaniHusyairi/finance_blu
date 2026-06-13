@php
    $items = [
        ['label' => 'Tagihan diverifikasi',  'done' => $state['tagihanApproved']],
        ['label' => 'COA dibebankan',        'done' => $state['coaDone']],
        ['label' => 'KPA menyetujui',        'done' => $state['kpaDone']],
        ...($state['pajakKontrak'] ?? false ? [
            ['label' => 'Pajak & faktur diisi', 'done' => $state['pajakKontrakDone'] || (bool) $state['spp']],
        ] : []),
        ['label' => 'Draft dokumen terbit',  'done' => (bool) $state['spp']],
        ['label' => 'SPP disetujui',         'done' => $state['sppApproved']],
        ['label' => 'SPM disetujui',         'done' => $state['spmApproved']],
        ['label' => 'NPI disetujui',         'done' => $state['npiApproved']],
        ['label' => 'Bukti transfer',        'done' => (bool) $state['buktiTransfer']],
        ['label' => 'SP2D terbit',           'done' => $state['sp2dTerbit']],
        ['label' => 'Tercatat di BKU',       'done' => $state['bkuPosted']],
    ];

    $totalDone = collect($items)->filter(fn ($i) => $i['done'])->count();
    $progress = count($items) > 0 ? (int) round(($totalDone / count($items)) * 100) : 0;

    // Parameter ring SVG
    $radius = 62;
    $circ = round(2 * M_PI * $radius, 2);
    $offset = round($circ * (1 - $progress / 100), 2);

    $logs = $tagihan->logs->sortByDesc('created_at')->values();
@endphp

{{-- ===== Ring Progres ===== --}}
<div class="process-card mb-4 overflow-visible">
    <div class="process-card-body text-center position-relative">
        <div class="text-secondary fw-bold fs-8 text-uppercase letter-spacing-1 mb-3">Progres Pencairan</div>

        <div class="pt-ring-wrap" style="--ring-circ: {{ $circ }}; --ring-offset: {{ $offset }};">
            <svg width="150" height="150" viewBox="0 0 150 150">
                <defs>
                    <linearGradient id="ptRingGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                        @if($progress >= 100)
                            <stop offset="0%" stop-color="#10b981"/>
                            <stop offset="100%" stop-color="#34d399"/>
                        @else
                            <stop offset="0%" stop-color="#4f46e5"/>
                            <stop offset="100%" stop-color="#a855f7"/>
                        @endif
                    </linearGradient>
                </defs>
                <circle class="pt-ring-bg" cx="75" cy="75" r="{{ $radius }}"></circle>
                <circle class="pt-ring-bar" cx="75" cy="75" r="{{ $radius }}"></circle>
            </svg>
            <div class="pt-ring-label">
                <div class="fw-bolder {{ $progress >= 100 ? 'text-success' : 'text-primary' }}" style="font-size: 1.9rem; letter-spacing: -1px;">
                    <span data-countup="{{ $progress }}">{{ $progress }}</span><span style="font-size:.55em;">%</span>
                </div>
                <div class="text-secondary fs-8 fw-bold">{{ $totalDone }}/{{ count($items) }} tahap</div>
            </div>
        </div>

        @if($progress >= 100)
            <div class="d-inline-flex align-items-center gap-2 mt-3 px-3 py-2 rounded-pill bg-success bg-opacity-10 text-success fw-bold small">
                <i class="bi bi-stars"></i> Seluruh proses selesai!
            </div>
        @endif

        {{-- Checklist --}}
        <div class="pt-check text-start mt-4">
            @foreach($items as $index => $item)
                @php $isNext = !$item['done'] && ($index === 0 || $items[$index - 1]['done']); @endphp
                <div class="pt-check-item {{ $item['done'] ? 'done' : ($isNext ? 'next' : '') }}">
                    <span class="pin">
                        @if($item['done'])
                            <i class="bi bi-check-lg"></i>
                        @elseif($isNext)
                            <i class="bi bi-arrow-down"></i>
                        @else
                            <i class="bi bi-circle"></i>
                        @endif
                    </span>
                    <span class="txt">{{ $item['label'] }}</span>
                    @if($isNext)
                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle rounded-pill fs-8 ms-auto">Saat ini</span>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</div>

{{-- ===== Aktivitas Terakhir ===== --}}
<div class="process-card">
    <div class="process-card-header">
        <div class="doc-icon-tile" style="--tone: var(--tone-slate); --tone-soft: var(--tone-slate-soft); width: 38px; height: 38px; font-size: 1rem;">
            <i class="bi bi-activity"></i>
        </div>
        <h6 class="mb-0 fw-bold text-dark">Aktivitas Terakhir</h6>
        <span class="badge bg-light text-secondary border rounded-pill ms-auto fs-8">{{ $logs->count() }}</span>
    </div>
    <div class="process-card-body">
        @if($logs->isEmpty())
            <div class="text-center text-muted py-3">
                <i class="bi bi-journal-x fs-3 d-block mb-2 opacity-50"></i>
                <small>Belum ada aktivitas tercatat.</small>
            </div>
        @else
            <div class="pt-log">
                @foreach($logs->take(6) as $log)
                    <div class="pt-log-item">
                        <div class="d-flex justify-content-between align-items-center gap-2">
                            <span class="fw-bold fs-7 text-dark">{{ str_replace('_', ' ', $log->aksi) }}</span>
                            <span class="text-muted fs-8 text-nowrap">{{ optional($log->created_at)->diffForHumans(short: true) }}</span>
                        </div>
                        <div class="text-secondary fs-8 d-flex align-items-center gap-1 mt-1">
                            <i class="bi bi-person-circle"></i> {{ $log->user?->name ?? 'Sistem' }}
                        </div>
                        @if($log->catatan)
                            <div class="bg-light rounded-3 p-2 fs-8 text-dark fst-italic mt-1 border-start border-2 border-primary-subtle">
                                "{{ \Illuminate\Support\Str::limit($log->catatan, 140) }}"
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>

            @if($logs->count() > 6)
                <div class="collapse" id="ptLogMore">
                    <div class="pt-log">
                        @foreach($logs->slice(6, 20) as $log)
                            <div class="pt-log-item">
                                <div class="d-flex justify-content-between align-items-center gap-2">
                                    <span class="fw-bold fs-7 text-dark">{{ str_replace('_', ' ', $log->aksi) }}</span>
                                    <span class="text-muted fs-8 text-nowrap">{{ optional($log->created_at)->diffForHumans(short: true) }}</span>
                                </div>
                                <div class="text-secondary fs-8 d-flex align-items-center gap-1 mt-1">
                                    <i class="bi bi-person-circle"></i> {{ $log->user?->name ?? 'Sistem' }}
                                </div>
                                @if($log->catatan)
                                    <div class="bg-light rounded-3 p-2 fs-8 text-dark fst-italic mt-1 border-start border-2 border-primary-subtle">
                                        "{{ \Illuminate\Support\Str::limit($log->catatan, 140) }}"
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
                <button class="btn btn-light border w-100 btn-sm fw-bold mt-2 btn-pt-action justify-content-center collapsed" type="button"
                        data-bs-toggle="collapse" data-bs-target="#ptLogMore"
                        onclick="this.querySelector('span').textContent = this.classList.contains('collapsed') ? 'Lihat Semua Aktivitas' : 'Sembunyikan';">
                    <i class="bi bi-chevron-expand"></i> <span>Lihat Semua Aktivitas</span>
                </button>
            @endif
        @endif
    </div>
</div>
