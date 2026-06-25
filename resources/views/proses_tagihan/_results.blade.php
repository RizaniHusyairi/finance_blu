@php
    $fmt = fn ($n) => number_format((float) $n, 0, ',', '.');

    $toneByTipe = [
        'KONTRAK' => ['tone' => 'var(--tone-indigo)', 'soft' => 'var(--tone-indigo-soft)', 'icon' => 'bi-file-earmark-text'],
        'PERJALDIN' => ['tone' => 'var(--tone-info)', 'soft' => 'var(--tone-info-soft)', 'icon' => 'bi-airplane'],
        'HONORARIUM' => ['tone' => 'var(--tone-violet)', 'soft' => 'var(--tone-violet-soft)', 'icon' => 'bi-cash-coin'],
    ];

    // Pemetaan tahap → indeks stepper mini (0..4)
    $stageMap = [
        'Menunggu COA & KPA' => 0,
        'Proses SPP/SPM/NPI' => 1,
        'Proses Dokumen (Alur Lama)' => 1,
        'Menunggu Penerbitan SP2D' => 2,
        'SP2D Terbit' => 3,
        'Selesai' => 4,
    ];
    $stageLabels = ['COA & KPA', 'SPP/SPM/NPI', 'SP2D', 'Terbit', 'Selesai'];
@endphp

{{-- Data ringkasan untuk sinkronisasi hero (dibaca JS setelah swap AJAX) --}}
<span id="ptResultsData" hidden
      data-nominal="{{ (int) $summary['nominal'] }}"
      data-total="{{ $summary['total'] }}"
      data-perlu="{{ $perluAksiCount ?? 0 }}"></span>

{{-- ============ STAT CARDS ============ --}}
<div class="pt-stats">
    <div class="pt-stat reveal" style="--tone: var(--tone-indigo); --tone-soft: var(--tone-indigo-soft); --d: .05s;">
        <div class="ico"><i class="bi bi-collection"></i></div>
        <div>
            <div class="num" data-countup data-target="{{ $summary['total'] }}">0</div>
            <div class="lbl">Total Tagihan</div>
        </div>
    </div>
    <div class="pt-stat reveal" style="--tone: var(--tone-amber); --tone-soft: var(--tone-amber-soft); --d: .12s;">
        <div class="ico"><i class="bi bi-hourglass-split"></i></div>
        <div>
            <div class="num" data-countup data-target="{{ $summary['proses'] }}">0</div>
            <div class="lbl">Dalam Proses</div>
        </div>
    </div>
    <div class="pt-stat reveal" style="--tone: var(--tone-emerald); --tone-soft: var(--tone-emerald-soft); --d: .19s;">
        <div class="ico"><i class="bi bi-patch-check"></i></div>
        <div>
            <div class="num" data-countup data-target="{{ $summary['selesai'] }}">0</div>
            <div class="lbl">Selesai</div>
        </div>
    </div>
    <div class="pt-stat reveal" style="--tone: var(--tone-violet); --tone-soft: var(--tone-violet-soft); --d: .26s;">
        <div class="ico"><i class="bi bi-lightning-charge"></i></div>
        <div>
            <div class="num" data-countup data-target="{{ $perluAksiCount ?? 0 }}">0</div>
            <div class="lbl">Perlu Tindakan Saya</div>
        </div>
    </div>
</div>

{{-- ============ TABS + INFO ============ --}}
<div class="pt-toolbar">
    <div class="pt-seg">
        <a data-tab="perlu-saya" class="{{ $tab === 'perlu-saya' ? 'active' : '' }}" href="{{ route('proses-tagihan.index', array_filter(['tab' => 'perlu-saya', 'search' => $search, 'tipe' => $tipeFilter])) }}">
            <i class="bi bi-person-check"></i> Perlu Tindakan Saya
            @if(($perluAksiCount ?? 0) > 0)<span class="cnt">{{ $perluAksiCount }}</span>@endif
        </a>
        <a data-tab="semua" class="{{ $tab !== 'perlu-saya' ? 'active' : '' }}" href="{{ route('proses-tagihan.index', array_filter(['tab' => 'semua', 'search' => $search, 'tipe' => $tipeFilter])) }}">
            <i class="bi bi-grid"></i> Semua
        </a>
    </div>
    <div class="pt-result-info">
        <i class="bi bi-list-check me-1"></i>Menampilkan <strong>{{ $tagihans->count() }}</strong> dari <strong>{{ $tab === 'perlu-saya' ? $tagihans->count() : $tagihans->total() }}</strong> tagihan
    </div>
</div>

{{-- ============ DAFTAR TAGIHAN ============ --}}
<div class="tg-list" id="ptList">
    @forelse($tagihans as $tagihan)
        @php
            $state = $tagihan->proses_state ?? [];
            $tahap = data_get($state, 'tahap', '-');
            $perluSaya = (bool) data_get($state, 'perluSaya');
            $stageIdx = $stageMap[$tahap] ?? 0;
            $isSelesai = $tahap === 'Selesai';

            $pihak = $tagihan->detailKontrak?->kontrakTermin?->kontrak?->vendor?->nama_pihak
                ?? $tagihan->pihak?->nama_pihak
                ?? $tagihan->nama_supplier
                ?? '-';

            $tone = $toneByTipe[$tagihan->tipe_tagihan] ?? ['tone' => 'var(--tone-slate)', 'soft' => 'var(--tone-slate-soft)', 'icon' => 'bi-receipt'];

            $statusTone = match (true) {
                $tagihan->status === 'SELESAI' => 'success',
                str_contains((string) $tagihan->status, 'TOLAK') || str_contains((string) $tagihan->status, 'BATAL') => 'danger',
                str_contains((string) $tagihan->status, 'PROSES') => 'info',
                default => 'neutral',
            };

            $searchHaystack = strtolower($tagihan->nomor_tagihan . ' ' . $tagihan->deskripsi . ' ' . $pihak . ' ' . $tagihan->tipe_tagihan);
        @endphp
        <div class="tg-card reveal {{ $perluSaya ? 'attn' : '' }}"
             style="--tone: {{ $tone['tone'] }}; --tone-soft: {{ $tone['soft'] }}; --d: {{ min($loop->index * 0.07, 0.6) }}s;"
             data-search="{{ $searchHaystack }}">
            <a class="stretched" href="{{ route('proses-tagihan.show', $tagihan->id) }}" aria-label="Buka {{ $tagihan->nomor_tagihan }}"></a>
            <div class="tg-grid">
                {{-- identitas --}}
                <div class="tg-ident">
                    <div class="tg-icon"><i class="bi {{ $tone['icon'] }}"></i></div>
                    <div class="min-w-0">
                        <div class="tg-no">{{ $tagihan->nomor_tagihan }}</div>
                        <div class="tg-desc">{{ \Illuminate\Support\Str::limit($tagihan->deskripsi, 110) }}</div>
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <span class="tg-pihak"><i class="bi bi-building"></i>{{ $pihak }}</span>
                            <span class="tg-tipe"><i class="bi {{ $tone['icon'] }}"></i>{{ $tagihan->tipe_tagihan }}</span>
                        </div>
                    </div>
                </div>

                {{-- nominal --}}
                <div>
                    <div class="tg-nominal-lbl">Nominal</div>
                    <div class="tg-nominal">Rp {{ $fmt($tagihan->total_netto) }}</div>
                    <div class="text-muted" style="font-size: .72rem;">
                        <i class="bi bi-clock-history me-1"></i>{{ $tagihan->updated_at?->diffForHumans() }}
                    </div>
                </div>

                {{-- mini pipeline --}}
                <div class="tg-steps">
                    <div class="track">
                        @foreach($stageLabels as $i => $lbl)
                            @php
                                $cls = $isSelesai || $i < $stageIdx ? 'done' : ($i === $stageIdx ? 'current' : '');
                            @endphp
                            <span class="nd {{ $cls }}" title="{{ $lbl }}">
                                @if($cls === 'done')<i class="bi bi-check"></i>@else{{ $i + 1 }}@endif
                            </span>
                            @if(! $loop->last)
                                <span class="ln {{ $isSelesai || $i < $stageIdx ? 'fill' : '' }}" style="--ld: {{ .15 + $i * .12 }}s;"><i></i></span>
                            @endif
                        @endforeach
                    </div>
                    <span class="tahap {{ $isSelesai ? 'ok' : '' }}">
                        <span class="spin"></span>{{ $tahap }}
                    </span>
                </div>

                {{-- status + aksi --}}
                <div class="tg-actions">
                    <div class="d-flex flex-wrap gap-1 justify-content-end">
                        <span class="pt-status {{ $statusTone }}">{{ str_replace('_', ' ', $tagihan->status) }}</span>
                        @if($perluSaya)
                            <span class="pt-status warning shimmer"><i class="bi bi-bell-fill"></i>Perlu aksi</span>
                        @endif
                    </div>
                    <a href="{{ route('proses-tagihan.show', $tagihan->id) }}" class="btn-pt-action" data-ripple>
                        Buka <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>
    @empty
        <div class="pt-empty reveal">
            @if($tab === 'perlu-saya')
                <div class="big" style="background: var(--tone-emerald-soft); color: var(--pt-success);"><i class="bi bi-emoji-sunglasses"></i></div>
                <h5>Tidak ada yang menunggu Anda 🎉</h5>
                <p>Semua tagihan pada filter ini sudah ditindaklanjuti. Cek tab <strong>Semua</strong> untuk memantau progres keseluruhan.</p>
                <a href="{{ route('proses-tagihan.index', array_filter(['search' => $search, 'tipe' => $tipeFilter])) }}" data-tab="semua" class="btn-pt-action"><i class="bi bi-grid"></i> Lihat Semua Tagihan</a>
            @else
                <div class="big"><i class="bi bi-inbox"></i></div>
                <h5>Belum ada tagihan</h5>
                <p>Tidak ditemukan tagihan pada filter ini. Coba ubah kata kunci pencarian atau reset filter.</p>
                <a href="{{ route('proses-tagihan.index') }}" data-pt-reset class="btn-pt-action"><i class="bi bi-arrow-counterclockwise"></i> Reset Filter</a>
            @endif
        </div>
    @endforelse
</div>

@if($tagihans->hasPages())
    <div class="pt-pagination">
        {{ $tagihans->links() }}
    </div>
@endif
