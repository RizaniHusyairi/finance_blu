@extends('layouts.app')

@section('title', 'Master Data DIPA')

@php
    $search = request('search');
    $tahunAnggaran = request('tahun_anggaran');
    $statusAktif = request('status_aktif');
    $revisiAktif = request('revisi_aktif_ke');
@endphp

@push('css')
<style>
/* ============================================================
   MASTER DATA DIPA — hero aurora · stat cards · glass filter
   ============================================================ */
.dipa-page { --dp-indigo:#6366f1; --dp-violet:#8b5cf6; --dp-blue:#3b82f6; --dp-cyan:#06b6d4;
    --dp-emerald:#10b981; --dp-amber:#f59e0b; --dp-ink:#0f172a; --dp-muted:#64748b;
    --dp-border:#e8ecf5; --dp-radius:1.15rem;
    --dp-shadow:0 18px 40px -22px rgba(30,27,75,.28);
    --dp-shadow-hover:0 28px 56px -24px rgba(79,70,229,.4); }

@keyframes dpAurora { 0%{background-position:0% 50%} 50%{background-position:100% 50%} 100%{background-position:0% 50%} }
@keyframes dpFloat  { 0%,100%{transform:translateY(0)} 50%{transform:translateY(-12px)} }
@keyframes dpRise   { from{opacity:0; transform:translateY(18px)} to{opacity:1; transform:none} }
@keyframes dpRowIn  { from{opacity:0; transform:translateY(10px)} to{opacity:1; transform:none} }
@keyframes dpSheen  { 0%,55%{left:-70%} 85%,100%{left:140%} }
@keyframes dpPulseG { 0%,100%{box-shadow:0 0 0 0 rgba(16,185,129,.5)} 50%{box-shadow:0 0 0 8px rgba(16,185,129,0)} }
@media (prefers-reduced-motion: reduce) {
    .dipa-page * { animation-duration:.001s !important; animation-iteration-count:1 !important; transition-duration:.001s !important; }
}

/* ---------- HERO ---------- */
.dp-hero { position:relative; overflow:hidden; border-radius:1.5rem; padding:1.9rem 2rem;
    margin-bottom:1.4rem; color:#fff;
    background:linear-gradient(125deg,#0b1020,#1e1b4b 30%,#4338ca 62%,#6d28d9 82%,#0e7490);
    background-size:340% 340%; animation:dpAurora 18s ease infinite;
    box-shadow:0 28px 56px -26px rgba(49,46,129,.65); }
.dp-hero::before, .dp-hero::after { content:''; position:absolute; border-radius:50%; pointer-events:none;
    background:radial-gradient(circle, rgba(255,255,255,.16) 0%, transparent 70%); }
.dp-hero::before { width:380px; height:380px; top:-58%; left:-3%; animation:dpFloat 10s ease-in-out infinite; }
.dp-hero::after  { width:280px; height:280px; bottom:-62%; right:-2%; animation:dpFloat 13s ease-in-out infinite reverse; }
.dp-hero .mesh { position:absolute; inset:0; opacity:.15; pointer-events:none;
    background-image:linear-gradient(rgba(255,255,255,.4) 1px, transparent 1px),
                     linear-gradient(90deg, rgba(255,255,255,.4) 1px, transparent 1px);
    background-size:44px 44px; mask-image:radial-gradient(ellipse at 22% 0%, #000 5%, transparent 62%); }
.dp-hero-grid { position:relative; z-index:2; display:flex; flex-wrap:wrap; justify-content:space-between;
    align-items:center; gap:1.2rem; }
.dp-hero h4 { font-weight:800; letter-spacing:-.4px; margin:0; font-size:clamp(1.25rem,2.4vw,1.7rem); color:#fff !important; }
.dp-hero .lead-sub, .dp-hero .dp-chip { color:#fff; }
.dp-hero .lead-sub { opacity:.78; font-weight:600; font-size:.88rem; margin-top:.3rem; }
.dp-chip { display:inline-flex; align-items:center; gap:.45rem; background:rgba(255,255,255,.13);
    border:1px solid rgba(255,255,255,.25); backdrop-filter:blur(10px); padding:.34rem .9rem;
    border-radius:999px; font-weight:700; font-size:.72rem; letter-spacing:.4px; }
.dp-chip .dot { width:8px; height:8px; border-radius:50%; background:#34d399; animation:dpPulseG 2s infinite; }
.dp-btn-add { display:inline-flex; align-items:center; gap:.55rem; background:#fff; color:#4338ca;
    font-weight:800; font-size:.9rem; padding:.68rem 1.4rem; border-radius:999px; text-decoration:none;
    border:0; box-shadow:0 12px 28px -10px rgba(0,0,0,.5);
    transition:transform .25s ease, box-shadow .25s ease; position:relative; overflow:hidden; }
.dp-btn-add:hover { color:#4338ca; transform:translateY(-3px) scale(1.02); box-shadow:0 18px 36px -12px rgba(0,0,0,.55); }
.dp-btn-add::after { content:''; position:absolute; top:0; left:-70%; width:45%; height:100%;
    background:linear-gradient(100deg, transparent, rgba(99,102,241,.18), transparent);
    transform:skewX(-20deg); animation:dpSheen 5s ease-in-out infinite; }

/* ---------- STAT CARDS ---------- */
.dp-stat { position:relative; overflow:hidden; background:#fff; border:1px solid var(--dp-border);
    border-radius:var(--dp-radius); box-shadow:var(--dp-shadow); padding:1.15rem 1.25rem; height:100%;
    transition:transform .32s cubic-bezier(.25,.8,.25,1), box-shadow .32s, border-color .32s;
    animation:dpRise .55s ease both; }
.dp-stat:nth-child(1){animation-delay:.02s} .dp-stat:nth-child(2){animation-delay:.08s}
.dp-stat:nth-child(3){animation-delay:.14s} .dp-stat:nth-child(4){animation-delay:.2s}
.dp-stat:hover { transform:translateY(-6px); box-shadow:var(--dp-shadow-hover); border-color:#c7d2fe; }
.dp-stat::before { content:''; position:absolute; top:0; left:0; right:0; height:4px;
    background:var(--g, linear-gradient(90deg, var(--dp-indigo), var(--dp-violet))); }
.dp-stat::after { content:''; position:absolute; top:0; left:-70%; width:45%; height:100%;
    background:linear-gradient(100deg, transparent, rgba(99,102,241,.06), transparent);
    transform:skewX(-20deg); animation:dpSheen 6s ease-in-out infinite; }
.dp-stat .ic { width:48px; height:48px; border-radius:14px; display:grid; place-items:center;
    color:#fff; font-size:1.3rem; flex-shrink:0;
    background:var(--g, linear-gradient(135deg, var(--dp-indigo), var(--dp-violet)));
    box-shadow:0 10px 22px -8px var(--gs, rgba(99,102,241,.55)); }
.dp-stat .lbl { font-size:.72rem; font-weight:700; letter-spacing:.08em; text-transform:uppercase;
    color:var(--dp-muted); }
.dp-stat .val { font-size:1.55rem; font-weight:800; color:var(--dp-ink); letter-spacing:-.4px;
    font-variant-numeric:tabular-nums; line-height:1.15; }
.dp-stat .sub { font-size:.74rem; color:var(--dp-muted); font-weight:600; }

/* ---------- FILTER CARD ---------- */
.dp-filter { background:rgba(255,255,255,.85); backdrop-filter:blur(8px); border:1px solid var(--dp-border);
    border-radius:var(--dp-radius); box-shadow:var(--dp-shadow); animation:dpRise .55s .22s ease both; }
.dp-filter .form-label { font-size:.72rem; font-weight:700; letter-spacing:.06em; text-transform:uppercase;
    color:var(--dp-muted); }
.dp-filter .form-control, .dp-filter .form-select { border-radius:.75rem; border-color:var(--dp-border);
    transition:border-color .2s ease, box-shadow .2s ease; }
.dp-filter .form-control:focus, .dp-filter .form-select:focus { border-color:#a5b4fc;
    box-shadow:0 0 0 .22rem rgba(99,102,241,.12); }
.dp-search-wrap .search-ic { position:absolute; top:50%; left:.9rem; transform:translateY(-50%);
    color:#94a3b8; pointer-events:none; transition:color .2s ease; }
.dp-search-wrap .form-control { padding-left:2.5rem; }
.dp-search-wrap .form-control:focus ~ .search-ic { color:var(--dp-indigo); }
.dp-btn-reset { border-radius:.75rem; font-weight:700; border:1px solid var(--dp-border); color:var(--dp-muted);
    background:#fff; transition:all .22s ease; }
.dp-btn-reset:hover { color:var(--dp-indigo); border-color:#c7d2fe; background:#eef2ff;
    transform:translateY(-2px); }

/* ---------- TABLE CARD ---------- */
.dp-table-card { background:#fff; border:1px solid var(--dp-border); border-radius:var(--dp-radius);
    box-shadow:var(--dp-shadow); overflow:hidden; animation:dpRise .55s .3s ease both; }
.dp-table-card .table { margin-bottom:0; }
.dp-table-card thead th { background:#f8faff !important; border-bottom:1px solid var(--dp-border) !important;
    font-size:.7rem; font-weight:800; letter-spacing:.1em; text-transform:uppercase; color:#7c8db5 !important; }
.dp-table-card tbody tr { animation:dpRowIn .4s ease both; transition:background .2s ease, box-shadow .2s ease; }
.dp-table-card tbody tr:nth-child(1){animation-delay:.03s} .dp-table-card tbody tr:nth-child(2){animation-delay:.08s}
.dp-table-card tbody tr:nth-child(3){animation-delay:.13s} .dp-table-card tbody tr:nth-child(4){animation-delay:.18s}
.dp-table-card tbody tr:nth-child(5){animation-delay:.23s} .dp-table-card tbody tr:nth-child(6){animation-delay:.28s}
.dp-table-card tbody tr:nth-child(7){animation-delay:.33s} .dp-table-card tbody tr:nth-child(8){animation-delay:.38s}
.dp-table-card tbody tr:hover { background:#f5f7ff; box-shadow:inset 3px 0 0 var(--dp-indigo); }
.dp-doc-tile { width:42px; height:42px; border-radius:12px; display:grid; place-items:center; flex-shrink:0;
    background:linear-gradient(135deg,#eef2ff,#e0e7ff); color:#4f46e5; font-size:1.05rem;
    transition:transform .25s ease; }
tr:hover .dp-doc-tile { transform:scale(1.08) rotate(-4deg); }
.dp-nomor { font-weight:800; color:#4338ca; letter-spacing:-.01em; }
.dp-nomor:hover { text-decoration:underline; }

/* Badge modern */
.dp-badge { display:inline-flex; align-items:center; gap:.35rem; font-size:.7rem; font-weight:700;
    padding:.32rem .7rem; border-radius:999px; letter-spacing:.03em; }
.dp-badge-year   { background:#f1f5f9; color:#334155; border:1px solid #e2e8f0; }
.dp-badge-rev    { background:#ecfeff; color:#0e7490; border:1px solid #a5f3fc; }
.dp-badge-item   { background:#eef2ff; color:#4338ca; border:1px solid #e0e7ff; }
.dp-badge-aktif  { background:#ecfdf5; color:#047857; border:1px solid #a7f3d0; }
.dp-badge-aktif .dot { width:7px; height:7px; border-radius:50%; background:#10b981; animation:dpPulseG 2s infinite; }
.dp-badge-nonaktif { background:#f8fafc; color:#64748b; border:1px solid #e2e8f0; }
.dp-pagu { font-weight:800; color:var(--dp-ink); font-variant-numeric:tabular-nums; }
.dp-pagu small { color:var(--dp-muted); font-weight:600; }

/* Tombol aksi */
.dp-act { display:inline-flex; align-items:center; justify-content:center; width:34px; height:34px;
    border-radius:11px; border:1px solid var(--dp-border); background:#fff; color:#64748b;
    transition:all .22s cubic-bezier(.25,.8,.25,1); text-decoration:none; font-size:.9rem; }
.dp-act:hover { transform:translateY(-3px); }
.dp-act-view:hover   { background:#4f46e5; border-color:#4f46e5; color:#fff; box-shadow:0 8px 18px -6px rgba(79,70,229,.55); }
.dp-act-edit:hover   { background:#f59e0b; border-color:#f59e0b; color:#fff; box-shadow:0 8px 18px -6px rgba(245,158,11,.55); }
.dp-act-rev:hover    { background:#06b6d4; border-color:#06b6d4; color:#fff; box-shadow:0 8px 18px -6px rgba(6,182,212,.55); }
.dp-act-on           { color:#10b981; border-color:#a7f3d0; background:#ecfdf5; }
.dp-act-on:hover     { background:#10b981; border-color:#10b981; color:#fff; box-shadow:0 8px 18px -6px rgba(16,185,129,.55); }
.dp-act-off          { color:#94a3b8; }
.dp-act-off:hover    { background:#64748b; border-color:#64748b; color:#fff; }
.dp-act-del:hover:not(:disabled) { background:#e11d48; border-color:#e11d48; color:#fff; box-shadow:0 8px 18px -6px rgba(225,29,72,.55); }
.dp-act:disabled     { opacity:.4; cursor:not-allowed; transform:none !important; }

/* Empty state */
.dp-empty { padding:3.5rem 1rem; text-align:center; color:var(--dp-muted); }
.dp-empty .glyph { width:74px; height:74px; margin:0 auto 1rem; border-radius:22px; display:grid;
    place-items:center; font-size:2rem; color:#a5b4fc; background:linear-gradient(135deg,#eef2ff,#e0e7ff);
    animation:dpFloat 5s ease-in-out infinite; }
</style>
@endpush

@section('content')
<div class="dipa-page">

    @if(session('success'))
        <div class="alert alert-success border-0 bg-success alert-dismissible fade show shadow-sm">
            <div class="text-white">{{ session('success') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('info'))
        <div class="alert alert-info border-0 bg-info alert-dismissible fade show shadow-sm">
            <div class="text-white">{{ session('info') }}</div>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- ════════ HERO ════════ --}}
    <div class="dp-hero">
        <div class="mesh"></div>
        <div class="dp-hero-grid">
            <div>
                <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                    <span class="dp-chip"><span class="dot"></span> MASTER DATA</span>
                    <span class="dp-chip"><i class="bi bi-journal-bookmark-fill"></i> TA {{ now()->year }}</span>
                </div>
                <h4>Master Data DIPA 📘</h4>
                <div class="lead-sub">Kelola dokumen DIPA, revisi anggaran, dan item pagu dalam satu tempat.</div>
            </div>
            <a href="{{ route('dipas.create') }}" class="dp-btn-add">
                <i class="bi bi-plus-circle-fill"></i> Tambah DIPA
            </a>
        </div>
    </div>

    {{-- ════════ STAT CARDS ════════ --}}
    <div class="row row-cols-1 row-cols-md-2 row-cols-xl-4 g-3 mb-4">
        <div class="col">
            <div class="dp-stat" style="--g:linear-gradient(135deg,#6366f1,#8b5cf6); --gs:rgba(99,102,241,.55);">
                <div class="d-flex align-items-center gap-3">
                    <div class="ic"><i class="bi bi-collection-fill"></i></div>
                    <div>
                        <div class="lbl">Total DIPA</div>
                        <div class="val dp-countup" data-target="{{ $summary['total_dipa'] }}">0</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="dp-stat" style="--g:linear-gradient(135deg,#10b981,#14b8a6); --gs:rgba(16,185,129,.5);">
                <div class="d-flex align-items-center gap-3">
                    <div class="ic"><i class="bi bi-patch-check-fill"></i></div>
                    <div>
                        <div class="lbl">DIPA Aktif</div>
                        <div class="val dp-countup" data-target="{{ $summary['dipa_aktif'] }}">0</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="dp-stat" style="--g:linear-gradient(135deg,#f59e0b,#f97316); --gs:rgba(245,158,11,.5);">
                <div class="d-flex align-items-center gap-3">
                    <div class="ic"><i class="bi bi-calendar-event-fill"></i></div>
                    <div>
                        <div class="lbl">Tahun Anggaran Berjalan</div>
                        <div class="val dp-countup" data-target="{{ $summary['tahun_berjalan'] }}">0</div>
                        <div class="sub">Tahun {{ now()->year }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="dp-stat" style="--g:linear-gradient(135deg,#06b6d4,#3b82f6); --gs:rgba(6,182,212,.5);">
                <div class="d-flex align-items-center gap-3">
                    <div class="ic"><i class="bi bi-cash-stack"></i></div>
                    <div>
                        <div class="lbl">Total Pagu Revisi Aktif</div>
                        <div class="val" style="font-size:1.2rem;">
                            Rp <span class="dp-countup" data-target="{{ (int) $summary['total_pagu_revisi_aktif'] }}">0</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ════════ FILTER ════════ --}}
    <div class="dp-filter mb-4">
        <div class="card-body p-4">
            <form method="GET" action="{{ route('dipas.index') }}" id="dipaFilterForm">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label" for="dipaSearchInput">Cari Nomor DIPA</label>
                        <div class="position-relative dp-search-wrap">
                            <input type="text" name="search" id="dipaSearchInput" value="{{ $search }}" class="form-control pe-5" placeholder="Ketik nomor DIPA…" autocomplete="off" inputmode="search">
                            <i class="bi bi-search search-ic"></i>
                            <span class="position-absolute top-50 end-0 translate-middle-y me-3 text-muted d-none" id="dipaSearchSpinner" aria-hidden="true">
                                <span class="spinner-border spinner-border-sm" role="status"></span>
                            </span>
                        </div>
                        <small class="text-muted">Pencarian hanya berdasarkan nomor DIPA.</small>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Tahun Anggaran</label>
                        <select name="tahun_anggaran" class="form-select" data-auto-submit="change">
                            <option value="">Semua</option>
                            @foreach($tahunOptions as $tahun)
                                <option value="{{ $tahun }}" {{ (string) $tahunAnggaran === (string) $tahun ? 'selected' : '' }}>{{ $tahun }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Status Aktif</label>
                        <select name="status_aktif" class="form-select" data-auto-submit="change">
                            <option value="">Semua</option>
                            <option value="aktif" {{ $statusAktif === 'aktif' ? 'selected' : '' }}>Aktif</option>
                            <option value="nonaktif" {{ $statusAktif === 'nonaktif' ? 'selected' : '' }}>Nonaktif</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Revisi Aktif</label>
                        <select name="revisi_aktif_ke" class="form-select" data-auto-submit="change">
                            <option value="">Semua</option>
                            @foreach($revisiOptions as $revisi)
                                <option value="{{ $revisi }}" {{ (string) $revisiAktif === (string) $revisi ? 'selected' : '' }}>Revisi {{ $revisi }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <div class="d-grid">
                            <a href="{{ route('dipas.index') }}" class="btn dp-btn-reset">
                                <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- ════════ TABEL ════════ --}}
    <div class="dp-table-card">
        <div id="dipaTableContainer">
            @include('dipas._table')
        </div>
    </div>

</div>
@endsection

@push('script')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            /* ── Count-up angka statistik ── */
            document.querySelectorAll('.dp-countup').forEach(function (el) {
                const target = parseInt(el.dataset.target || '0', 10);
                const dur = 1100, start = performance.now();
                function step(now) {
                    const p = Math.min((now - start) / dur, 1);
                    const eased = 1 - Math.pow(1 - p, 3);
                    el.textContent = Math.round(target * eased).toLocaleString('id-ID');
                    if (p < 1) requestAnimationFrame(step);
                }
                requestAnimationFrame(step);
            });

            /* ── Filter + pencarian AJAX (fungsional tidak berubah) ── */
            const form = document.getElementById('dipaFilterForm');
            const tableContainer = document.getElementById('dipaTableContainer');
            const searchInput = document.getElementById('dipaSearchInput');
            const spinner = document.getElementById('dipaSearchSpinner');

            if (!form || !tableContainer || !searchInput) {
                return;
            }

            const DEBOUNCE_MS = 400;

            let debounceTimer = null;
            let abortController = null;
            let lastQueryString = null;

            const buildParams = function () {
                const params = new URLSearchParams(new FormData(form));
                for (const [key, value] of Array.from(params.entries())) {
                    if (String(value).trim() === '') {
                        params.delete(key);
                    }
                }
                return params;
            };

            const setLoading = function (loading) {
                if (!spinner) return;
                spinner.classList.toggle('d-none', !loading);
            };

            const fetchTable = async function () {
                const params = buildParams();
                const userQueryString = params.toString();

                // Skip jika query sama persis dengan request terakhir (hindari request kembar).
                if (userQueryString === lastQueryString) {
                    return;
                }
                lastQueryString = userQueryString;

                // Batalkan request sebelumnya yang masih berjalan (user masih mengetik).
                if (abortController) {
                    abortController.abort();
                }
                abortController = new AbortController();

                params.set('partial', '1');
                const requestUrl = form.action + '?' + params.toString();

                setLoading(true);

                try {
                    const response = await fetch(requestUrl, {
                        method: 'GET',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'text/html',
                        },
                        signal: abortController.signal,
                        credentials: 'same-origin',
                    });

                    if (!response.ok) {
                        throw new Error('Gagal memuat data DIPA (HTTP ' + response.status + ').');
                    }

                    tableContainer.innerHTML = await response.text();

                    const newUrl = userQueryString
                        ? form.action + '?' + userQueryString
                        : form.action;
                    window.history.replaceState({}, '', newUrl);
                } catch (err) {
                    if (err.name === 'AbortError') {
                        return;
                    }
                    console.error(err);
                    lastQueryString = null;
                } finally {
                    setLoading(false);
                }
            };

            const triggerImmediate = function () {
                clearTimeout(debounceTimer);
                lastQueryString = null;
                fetchTable();
            };

            searchInput.addEventListener('input', function () {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(fetchTable, DEBOUNCE_MS);
            });

            form.addEventListener('submit', function (event) {
                event.preventDefault();
                triggerImmediate();
            });

            form.querySelectorAll('[data-auto-submit="change"]').forEach(function (field) {
                field.addEventListener('change', triggerImmediate);
            });
        });
    </script>
@endpush
