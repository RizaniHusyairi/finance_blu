@extends('layouts.app')
@section('title', 'Tagihan Kontrak')

@push('css')
<style>
    :root {
        --ke-primary: #4f46e5;
        --ke-primary-2: #a855f7;
        --ke-ink: #0f172a;
    }

    /* ===== Animasi dasar ===== */
    @keyframes keIn {
        from { opacity: 0; transform: translateY(14px); }
        to   { opacity: 1; transform: translateY(0); }
    }
    @keyframes keShine {
        0%   { transform: translateX(-130%) skewX(-18deg); }
        100% { transform: translateX(230%) skewX(-18deg); }
    }
    @keyframes keFloat {
        0%, 100% { transform: translateY(0) rotate(0deg); }
        50%      { transform: translateY(-8px) rotate(3deg); }
    }
    @keyframes kePulse {
        0%, 100% { box-shadow: 0 0 0 0 rgba(59, 130, 246, .35); }
        60%      { box-shadow: 0 0 0 9px rgba(59, 130, 246, 0); }
    }
    .ke-reveal { opacity: 0; animation: keIn .55s cubic-bezier(.22, 1, .36, 1) forwards; animation-delay: var(--d, 0s); }

    /* ===== Hero ===== */
    .ke-hero {
        position: relative;
        overflow: hidden;
        border-radius: 1.4rem;
        padding: 1.9rem 2rem;
        color: #fff;
        background: linear-gradient(120deg, #312e81 0%, var(--ke-primary) 45%, var(--ke-primary-2) 100%);
        box-shadow: 0 18px 40px -18px rgba(79, 70, 229, .55);
    }
    .ke-hero::before, .ke-hero::after {
        content: '';
        position: absolute;
        border-radius: 50%;
        background: rgba(255, 255, 255, .08);
        pointer-events: none;
    }
    .ke-hero::before { width: 280px; height: 280px; top: -130px; right: -60px; }
    .ke-hero::after  { width: 180px; height: 180px; bottom: -100px; right: 190px; background: rgba(255,255,255,.06); }
    .ke-hero-icon {
        width: 62px; height: 62px;
        display: inline-flex; align-items: center; justify-content: center;
        border-radius: 1.1rem;
        background: rgba(255, 255, 255, .16);
        border: 1px solid rgba(255, 255, 255, .25);
        font-size: 1.7rem;
        backdrop-filter: blur(4px);
        animation: keFloat 5.5s ease-in-out infinite;
    }
    .ke-hero-title { font-weight: 800; letter-spacing: -.5px; margin-bottom: .2rem; color: #fff !important; }
    .ke-hero-sub { color: rgba(255, 255, 255, .82); font-size: .87rem; max-width: 620px; }
    .ke-hero-chip {
        display: inline-flex; align-items: center; gap: .4rem;
        padding: .3rem .75rem;
        border-radius: 999px;
        background: rgba(255, 255, 255, .14);
        border: 1px solid rgba(255, 255, 255, .22);
        font-size: .72rem; font-weight: 700; letter-spacing: .3px;
    }
    .ke-btn-create {
        position: relative; overflow: hidden;
        border: 0; border-radius: .9rem;
        padding: .75rem 1.4rem;
        font-weight: 700;
        color: var(--ke-primary);
        background: #fff;
        box-shadow: 0 10px 24px -8px rgba(0, 0, 0, .35);
        transition: transform .2s ease, box-shadow .2s ease;
    }
    .ke-btn-create:hover { transform: translateY(-2px); box-shadow: 0 16px 30px -10px rgba(0, 0, 0, .4); color: var(--ke-primary); }
    .ke-btn-create::after {
        content: '';
        position: absolute; inset: 0; width: 45%;
        background: linear-gradient(90deg, transparent, rgba(79, 70, 229, .16), transparent);
        animation: keShine 3.2s ease-in-out infinite;
    }

    /* ===== Stat cards ===== */
    .ke-stat {
        position: relative; overflow: hidden;
        border-radius: 1.1rem;
        border: 1px solid #eef0f4;
        background: #fff;
        padding: 1.05rem 1.2rem;
        height: 100%;
        box-shadow: 0 2px 8px rgba(15, 23, 42, .04);
        transition: transform .22s ease, box-shadow .22s ease, border-color .22s ease;
    }
    .ke-stat:hover { transform: translateY(-4px); box-shadow: 0 16px 30px rgba(15, 23, 42, .09); border-color: #e2e8f0; }
    .ke-stat::before {
        content: '';
        position: absolute; inset: 0 0 auto 0; height: 3px;
        background: var(--tone, var(--ke-primary));
        opacity: .9;
    }
    .ke-stat .ic {
        width: 44px; height: 44px;
        display: inline-flex; align-items: center; justify-content: center;
        border-radius: .85rem;
        font-size: 1.25rem;
        color: var(--tone, var(--ke-primary));
        background: var(--tone-soft, rgba(79, 70, 229, .1));
        transition: transform .25s ease;
    }
    .ke-stat:hover .ic { transform: scale(1.1) rotate(-4deg); }
    .ke-stat .lbl { font-size: .7rem; font-weight: 800; text-transform: uppercase; letter-spacing: .06em; color: #64748b; }
    .ke-stat .val { font-size: 1.45rem; font-weight: 800; color: var(--ke-ink); letter-spacing: -.5px; line-height: 1.15; }
    .ke-stat .sub { font-size: .72rem; color: #94a3b8; }

    /* ===== Panel daftar ===== */
    .ke-panel {
        border: 1px solid #eef0f4;
        border-radius: 1.25rem;
        background: #fff;
        box-shadow: 0 2px 10px rgba(15, 23, 42, .04);
        overflow: hidden;
    }
    .ke-panel-head {
        display: flex; flex-wrap: wrap; gap: .75rem;
        align-items: center; justify-content: space-between;
        padding: 1.1rem 1.35rem;
        border-bottom: 1px solid #f1f5f9;
    }
    .ke-search {
        position: relative; min-width: 260px;
    }
    .ke-search input {
        border-radius: .8rem;
        border: 1px solid #e2e8f0;
        padding: .55rem .9rem .55rem 2.3rem;
        font-size: .85rem;
        width: 100%;
        transition: border-color .2s ease, box-shadow .2s ease;
    }
    .ke-search input:focus { outline: 0; border-color: var(--ke-primary); box-shadow: 0 0 0 .2rem rgba(79, 70, 229, .12); }
    .ke-search i { position: absolute; left: .85rem; top: 50%; transform: translateY(-50%); color: #94a3b8; }

    /* ===== Baris tagihan ===== */
    .ke-row {
        display: flex; flex-wrap: wrap; gap: 1rem;
        align-items: center;
        padding: 1.05rem 1.35rem;
        border-bottom: 1px solid #f1f5f9;
        position: relative;
        transition: background .2s ease, transform .2s ease, box-shadow .2s ease;
    }
    .ke-row:last-child { border-bottom: 0; }
    .ke-row::before {
        content: '';
        position: absolute; left: 0; top: 12%; bottom: 12%;
        width: 3px; border-radius: 0 4px 4px 0;
        background: var(--row-tone, #cbd5e1);
        opacity: 0;
        transition: opacity .2s ease;
    }
    .ke-row:hover { background: #fafbff; transform: translateX(3px); }
    .ke-row:hover::before { opacity: 1; }
    .ke-row-icon {
        width: 48px; height: 48px; flex-shrink: 0;
        display: inline-flex; align-items: center; justify-content: center;
        border-radius: 1rem;
        font-size: 1.3rem;
        color: var(--row-tone, var(--ke-primary));
        background: color-mix(in srgb, var(--row-tone, var(--ke-primary)) 10%, #fff);
        border: 1px solid color-mix(in srgb, var(--row-tone, var(--ke-primary)) 18%, #fff);
        transition: transform .25s ease;
    }
    .ke-row:hover .ke-row-icon { transform: scale(1.08) rotate(-5deg); }
    .ke-row-main { flex: 1 1 300px; min-width: 0; }
    .ke-row-title { font-weight: 700; color: var(--ke-ink); font-size: .95rem; margin-bottom: .15rem; overflow-wrap: anywhere; }
    .ke-row-meta { display: flex; flex-wrap: wrap; gap: .35rem .5rem; align-items: center; }
    .ke-chip {
        display: inline-flex; align-items: center; gap: .3rem;
        padding: .18rem .6rem;
        border-radius: 999px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        font-size: .7rem; font-weight: 600; color: #475569;
        max-width: 100%;
    }
    .ke-chip .txt { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 260px; }
    .ke-chip.copyable { cursor: pointer; transition: border-color .2s ease, background .2s ease; }
    .ke-chip.copyable:hover { border-color: var(--ke-primary); background: #eef2ff; }
    .ke-row-value { text-align: right; flex: 0 0 auto; }
    .ke-row-value .rp { font-weight: 800; font-size: 1rem; color: var(--ke-ink); font-variant-numeric: tabular-nums; letter-spacing: -.3px; }
    .ke-row-value .netto { font-size: .7rem; color: #10b981; font-weight: 700; }
    .ke-status {
        display: inline-flex; align-items: center; gap: .35rem;
        padding: .3rem .7rem;
        border-radius: 999px;
        font-size: .7rem; font-weight: 800; letter-spacing: .03em;
        border: 1px solid transparent;
        white-space: nowrap;
    }
    .ke-status .dot { width: 7px; height: 7px; border-radius: 50%; background: currentColor; }
    .ke-status.proses .dot { animation: kePulse 1.8s ease-out infinite; }
    .ke-actions { display: flex; gap: .4rem; }
    .ke-actions .btn {
        border-radius: .7rem;
        border: 1px solid #e2e8f0;
        background: #fff;
        color: #475569;
        transition: transform .18s ease, border-color .18s ease, color .18s ease, background .18s ease;
    }
    .ke-actions .btn:hover { transform: translateY(-2px); border-color: var(--ke-primary); color: var(--ke-primary); background: #eef2ff; }

    /* ===== Empty state ===== */
    .ke-empty { text-align: center; padding: 3.5rem 1.5rem; }
    .ke-empty .bubble {
        width: 88px; height: 88px; margin: 0 auto 1rem;
        display: flex; align-items: center; justify-content: center;
        border-radius: 50%;
        font-size: 2.2rem; color: var(--ke-primary);
        background: linear-gradient(135deg, #eef2ff, #faf5ff);
        border: 1px dashed #c7d2fe;
        animation: keFloat 4.5s ease-in-out infinite;
    }

    @media (prefers-reduced-motion: reduce) {
        .ke-reveal { animation: none; opacity: 1; }
        .ke-hero-icon, .ke-btn-create::after, .ke-empty .bubble, .ke-status.proses .dot { animation: none; }
        .ke-stat, .ke-row, .ke-row-icon, .ke-actions .btn { transition: none; }
    }
</style>
@endpush

@section('content')
<div class="page-content">
    @php
        $statusMeta = function (string $status): array {
            return match (true) {
                $status === 'DRAFT' => ['label' => 'Draft', 'tone' => '#64748b', 'bg' => '#f1f5f9', 'icon' => 'bi-pencil', 'pulse' => false],
                str_starts_with($status, 'REVISI_') => ['label' => 'Perlu Revisi', 'tone' => '#b45309', 'bg' => '#fef3c7', 'icon' => 'bi-arrow-counterclockwise', 'pulse' => true],
                $status === 'READY_FOR_SPP' => ['label' => 'Siap Diproses', 'tone' => '#1d4ed8', 'bg' => '#dbeafe', 'icon' => 'bi-lightning-charge', 'pulse' => true],
                $status === 'PROSES_SPP' => ['label' => 'Proses Pencairan', 'tone' => '#7c3aed', 'bg' => '#ede9fe', 'icon' => 'bi-diagram-3', 'pulse' => true],
                $status === 'SELESAI' => ['label' => 'Selesai', 'tone' => '#047857', 'bg' => '#d1fae5', 'icon' => 'bi-check-circle', 'pulse' => false],
                default => ['label' => ucwords(strtolower(str_replace('_', ' ', $status))), 'tone' => '#475569', 'bg' => '#f1f5f9', 'icon' => 'bi-circle', 'pulse' => false],
            };
        };

        $totalNilai = (float) $tagihans->sum('total_bruto');
        $jumlahDraft = $tagihans->filter(fn ($t) => $t->status === 'DRAFT' || str_starts_with((string) $t->status, 'REVISI_'))->count();
        $jumlahSelesai = $tagihans->where('status', 'SELESAI')->count();
        $jumlahProses = $tagihans->count() - $jumlahDraft - $jumlahSelesai;
    @endphp

    {{-- ===== Hero ===== --}}
    <div class="ke-hero mb-4 ke-reveal" style="--d:.02s;">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 position-relative" style="z-index:1;">
            <div class="d-flex align-items-center gap-3">
                <span class="ke-hero-icon"><i class="bi bi-file-earmark-check"></i></span>
                <div>
                    <h4 class="ke-hero-title mb-1">Tagihan Kontrak</h4>
                    <div class="ke-hero-sub">
                        Penagihan kontrak yang dibuat &amp; ditandatangani di luar sistem — cukup unggah
                        PDF Surat Pesanan ber-TTE, tagihan langsung siap diproses sampai SP2D &amp; BKU.
                    </div>
                    <div class="d-flex flex-wrap gap-2 mt-2">
                        <span class="ke-hero-chip"><i class="bi bi-cart-check"></i> e-Purchasing / INAPROC</span>
                        <span class="ke-hero-chip"><i class="bi bi-shield-check"></i> TTE BSrE &amp; Privy</span>
                        <span class="ke-hero-chip"><i class="bi bi-lightning-charge"></i> Tanpa verifikasi berlapis</span>
                    </div>
                </div>
            </div>
            <a href="{{ route('kontrak-eksternal.index') }}" class="btn ke-btn-create">
                <i class="bi bi-journal-bookmark me-1"></i> Kelola Kontrak
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success rounded-4 ke-reveal" style="--d:.05s;" data-sky-ignore>
            <i class="bi bi-check-circle-fill me-1"></i> {{ session('success') }}
        </div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger rounded-4 ke-reveal" style="--d:.05s;" data-sky-ignore>
            <ul class="mb-0">@foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach</ul>
        </div>
    @endif

    {{-- ===== Statistik ===== --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-3 ke-reveal" style="--d:.08s;">
            <div class="ke-stat" style="--tone:#4f46e5; --tone-soft:rgba(79,70,229,.1);">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="lbl">Total Tagihan</div>
                        <div class="val"><span data-ke-count="{{ $tagihans->count() }}">0</span></div>
                        <div class="sub">seluruh tagihan kontrak</div>
                    </div>
                    <span class="ic"><i class="bi bi-collection"></i></span>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3 ke-reveal" style="--d:.13s;">
            <div class="ke-stat" style="--tone:#f59e0b; --tone-soft:rgba(245,158,11,.12);">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="lbl">Draft / Revisi</div>
                        <div class="val"><span data-ke-count="{{ $jumlahDraft }}">0</span></div>
                        <div class="sub">belum diajukan</div>
                    </div>
                    <span class="ic"><i class="bi bi-pencil-square"></i></span>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3 ke-reveal" style="--d:.18s;">
            <div class="ke-stat" style="--tone:#8b5cf6; --tone-soft:rgba(139,92,246,.12);">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="lbl">Dalam Proses</div>
                        <div class="val"><span data-ke-count="{{ $jumlahProses }}">0</span></div>
                        <div class="sub">menuju SP2D &amp; BKU</div>
                    </div>
                    <span class="ic"><i class="bi bi-diagram-3"></i></span>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3 ke-reveal" style="--d:.23s;">
            <div class="ke-stat" style="--tone:#10b981; --tone-soft:rgba(16,185,129,.12);">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="lbl">Total Nilai (Bruto)</div>
                        <div class="val" style="font-size:1.15rem;">Rp <span data-ke-count="{{ (int) $totalNilai }}">0</span></div>
                        <div class="sub">{{ $jumlahSelesai }} tagihan selesai</div>
                    </div>
                    <span class="ic"><i class="bi bi-cash-stack"></i></span>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== Daftar tagihan ===== --}}
    <div class="ke-panel ke-reveal" style="--d:.28s;">
        <div class="ke-panel-head">
            <div class="d-flex align-items-center gap-2">
                <h6 class="mb-0 fw-bold"><i class="bi bi-list-ul me-1 text-primary"></i> Daftar Tagihan</h6>
                <span class="badge bg-light text-secondary border rounded-pill" id="keRowCount">{{ $tagihans->count() }}</span>
            </div>
            <div class="ke-search">
                <i class="bi bi-search"></i>
                <input type="text" id="keSearch" placeholder="Cari nomor, pekerjaan, atau penyedia..." autocomplete="off">
            </div>
        </div>

        <div id="keRows">
            @forelse($tagihans as $i => $t)
                @php
                    $meta = $statusMeta((string) $t->status);
                    $detail = $t->detailKontrakEksternal;
                    $isDraft = $t->status === 'DRAFT' || str_starts_with((string) $t->status, 'REVISI_');
                    $haystack = strtolower(implode(' ', [
                        $t->nomor_tagihan,
                        $detail?->nomor_surat_pesanan,
                        $detail?->nama_pekerjaan,
                        $t->pihak?->nama_pihak,
                        $meta['label'],
                    ]));
                @endphp
                <div class="ke-row ke-reveal" style="--row-tone: {{ $meta['tone'] }}; --d: {{ .32 + min($i, 10) * .05 }}s;" data-haystack="{{ $haystack }}">
                    <span class="ke-row-icon"><i class="bi {{ $meta['icon'] }}"></i></span>

                    <div class="ke-row-main">
                        <div class="ke-row-title">{{ $detail?->nama_pekerjaan ?? $t->deskripsi }}</div>
                        <div class="ke-row-meta">
                            <span class="ke-chip"><i class="bi bi-receipt"></i> <span class="txt">{{ $t->nomor_tagihan }}</span></span>
                            @if($detail?->nomor_surat_pesanan)
                                <span class="ke-chip copyable" data-copy="{{ $detail->nomor_surat_pesanan }}" title="Klik untuk menyalin nomor Surat Pesanan">
                                    <i class="bi bi-hash"></i> <span class="txt">{{ $detail->nomor_surat_pesanan }}</span>
                                    <i class="bi bi-copy" style="color:#94a3b8; font-size:.65rem;"></i>
                                </span>
                            @endif
                            @if($detail?->sumber)
                                <span class="ke-chip"><i class="bi bi-cart-check"></i> {{ $detail->sumber }}</span>
                            @endif
                            @if($t->pihak)
                                <span class="ke-chip"><i class="bi bi-shop"></i> <span class="txt">{{ $t->pihak->nama_pihak }}</span></span>
                            @endif
                            @if($detail?->termin_ke)
                                <span class="ke-chip"><i class="bi bi-collection"></i> Termin {{ $detail->termin_ke }}{{ $detail->total_termin ? '/' . $detail->total_termin : '' }}</span>
                            @endif
                        </div>
                    </div>

                    <div class="ke-row-value">
                        <div class="rp">Rp {{ number_format((float) $t->total_bruto, 0, ',', '.') }}</div>
                        @if((float) $t->total_potongan > 0)
                            <div class="netto"><i class="bi bi-wallet2"></i> Netto Rp {{ number_format((float) $t->total_netto, 0, ',', '.') }}</div>
                        @endif
                    </div>

                    <span class="ke-status {{ $meta['pulse'] ? 'proses' : '' }}" style="color: {{ $meta['tone'] }}; background: {{ $meta['bg'] }};">
                        <span class="dot"></span> {{ $meta['label'] }}
                    </span>

                    <div class="ke-actions">
                        <a class="btn btn-sm" href="{{ route('tagihan-kontrak-eksternal.show', $t->id) }}" title="Detail tagihan">
                            <i class="bi bi-eye"></i>
                        </a>
                        @if($isDraft)
                            <a class="btn btn-sm" href="{{ route('tagihan-kontrak-eksternal.edit', $t->id) }}" title="Edit draft">
                                <i class="bi bi-pencil-square"></i>
                            </a>
                        @else
                            <a class="btn btn-sm" href="{{ route('proses-tagihan.show', $t->id) }}" title="Proses Tagihan">
                                <i class="bi bi-diagram-3"></i>
                            </a>
                        @endif
                    </div>
                </div>
            @empty
                <div class="ke-empty">
                    <div class="bubble"><i class="bi bi-file-earmark-plus"></i></div>
                    <h6 class="fw-bold mb-1">Belum ada tagihan kontrak</h6>
                    <div class="text-secondary fs-7 mb-3">Daftarkan master Kontrak terlebih dahulu, lalu tagih tiap termin dari halaman kontrak.</div>
                    <a href="{{ route('kontrak-eksternal.index') }}" class="btn btn-primary rounded-3">
                        <i class="bi bi-journal-bookmark"></i> Buka Halaman Kontrak
                    </a>
                </div>
            @endforelse

            <div class="ke-empty d-none" id="keNoResult">
                <div class="bubble"><i class="bi bi-search"></i></div>
                <h6 class="fw-bold mb-1">Tidak ada hasil</h6>
                <div class="text-secondary fs-7">Coba kata kunci lain — nomor tagihan, Surat Pesanan, pekerjaan, atau penyedia.</div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('script')
<script>
    (function () {
        const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        // Count-up angka statistik.
        document.querySelectorAll('[data-ke-count]').forEach(function (el) {
            const target = parseInt(el.getAttribute('data-ke-count'), 10) || 0;
            const format = (n) => n.toLocaleString('id-ID');
            if (reduceMotion || target === 0) { el.textContent = format(target); return; }

            const duration = 900;
            const start = performance.now();
            const tick = (now) => {
                const p = Math.min((now - start) / duration, 1);
                el.textContent = format(Math.round(target * (1 - Math.pow(1 - p, 3))));
                if (p < 1) requestAnimationFrame(tick);
            };
            requestAnimationFrame(tick);
        });

        // Pencarian instan pada daftar.
        const search = document.getElementById('keSearch');
        const rows = Array.from(document.querySelectorAll('#keRows .ke-row'));
        const counter = document.getElementById('keRowCount');
        const noResult = document.getElementById('keNoResult');
        if (search) {
            search.addEventListener('input', function () {
                const q = this.value.trim().toLowerCase();
                let visible = 0;
                rows.forEach(function (row) {
                    const hit = !q || (row.dataset.haystack || '').includes(q);
                    row.style.display = hit ? '' : 'none';
                    if (hit) visible++;
                });
                if (counter) counter.textContent = visible;
                if (noResult) noResult.classList.toggle('d-none', visible > 0 || rows.length === 0);
            });
        }

        // Chip salin nomor Surat Pesanan.
        document.querySelectorAll('.ke-chip.copyable').forEach(function (chip) {
            chip.addEventListener('click', function () {
                const text = chip.getAttribute('data-copy') || '';
                const done = function () {
                    const icon = chip.querySelector('.bi-copy, .bi-check-lg');
                    if (icon) { icon.classList.remove('bi-copy'); icon.classList.add('bi-check-lg'); icon.style.color = '#10b981'; }
                    setTimeout(function () {
                        if (icon) { icon.classList.remove('bi-check-lg'); icon.classList.add('bi-copy'); icon.style.color = '#94a3b8'; }
                    }, 1400);
                };
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(text).then(done).catch(function () {});
                } else {
                    const ta = document.createElement('textarea');
                    ta.value = text; document.body.appendChild(ta); ta.select();
                    try { document.execCommand('copy'); done(); } catch (e) {}
                    document.body.removeChild(ta);
                }
            });
        });
    })();
</script>
@endpush
