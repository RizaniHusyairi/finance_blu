@extends('layouts.app')

@section('title', 'Master COA')

@php
    $search = request('search');
    $jenisAkun = request('jenis_akun');
    $statusAktif = request('status_aktif');
@endphp

@push('css')
<style>
/* ============================================================
   MASTER COA — hero aurora · stat tiles · tabel interaktif
   ============================================================ */
.coa-list { --cl-indigo:#4f46e5; --cl-violet:#8b5cf6; --cl-cyan:#06b6d4; --cl-emerald:#10b981;
    --cl-amber:#f59e0b; --cl-ink:#0f172a; --cl-muted:#64748b; --cl-border:#e8ecf5;
    --cl-radius:1.15rem;
    --cl-shadow:0 18px 40px -22px rgba(30,27,75,.28);
    --cl-shadow-hover:0 28px 56px -24px rgba(79,70,229,.4); }

@keyframes clAurora { 0%{background-position:0% 50%} 50%{background-position:100% 50%} 100%{background-position:0% 50%} }
@keyframes clFloat  { 0%,100%{transform:translateY(0)} 50%{transform:translateY(-12px)} }
@keyframes clRise   { from{opacity:0; transform:translateY(18px)} to{opacity:1; transform:none} }
@keyframes clRowIn  { from{opacity:0; transform:translateY(10px)} to{opacity:1; transform:none} }
@keyframes clSheen  { 0%,55%{left:-70%} 85%,100%{left:140%} }
@keyframes clPulseG { 0%,100%{box-shadow:0 0 0 0 rgba(16,185,129,.5)} 50%{box-shadow:0 0 0 8px rgba(16,185,129,0)} }
@media (prefers-reduced-motion: reduce) {
    .coa-list * { animation-duration:.001s !important; animation-iteration-count:1 !important; transition-duration:.001s !important; }
}

/* ---------- HERO ---------- */
.cl-hero { position:relative; overflow:hidden; border-radius:1.5rem; padding:1.9rem 2rem;
    margin-bottom:1.4rem; color:#fff;
    background:linear-gradient(125deg,#0b1020,#1e1b4b 30%,#4338ca 62%,#7c3aed 82%,#0e7490);
    background-size:340% 340%; animation:clAurora 18s ease infinite;
    box-shadow:0 28px 56px -26px rgba(49,46,129,.65); }
.cl-hero::before, .cl-hero::after { content:''; position:absolute; border-radius:50%; pointer-events:none;
    background:radial-gradient(circle, rgba(255,255,255,.16) 0%, transparent 70%); }
.cl-hero::before { width:380px; height:380px; top:-58%; left:-3%; animation:clFloat 10s ease-in-out infinite; }
.cl-hero::after  { width:280px; height:280px; bottom:-62%; right:-2%; animation:clFloat 13s ease-in-out infinite reverse; }
.cl-hero .mesh { position:absolute; inset:0; opacity:.15; pointer-events:none;
    background-image:linear-gradient(rgba(255,255,255,.4) 1px, transparent 1px),
                     linear-gradient(90deg, rgba(255,255,255,.4) 1px, transparent 1px);
    background-size:44px 44px; mask-image:radial-gradient(ellipse at 22% 0%, #000 5%, transparent 62%); }
.cl-hero-grid { position:relative; z-index:2; display:flex; flex-wrap:wrap; justify-content:space-between;
    align-items:center; gap:1.2rem; }
.cl-hero h4 { font-weight:800; letter-spacing:-.4px; margin:0; color:#fff !important;
    font-size:clamp(1.25rem,2.4vw,1.7rem); }
.cl-hero .lead-sub { color:#fff; opacity:.78; font-weight:600; font-size:.88rem; margin-top:.3rem; }
.cl-chip { display:inline-flex; align-items:center; gap:.45rem; background:rgba(255,255,255,.13);
    border:1px solid rgba(255,255,255,.25); backdrop-filter:blur(10px); padding:.34rem .9rem;
    border-radius:999px; font-weight:700; font-size:.72rem; letter-spacing:.4px; color:#fff; }
.cl-chip .dot { width:8px; height:8px; border-radius:50%; background:#34d399; animation:clPulseG 2s infinite; }
.cl-btn-add { display:inline-flex; align-items:center; gap:.55rem; background:#fff; color:#4338ca;
    font-weight:800; font-size:.9rem; padding:.68rem 1.4rem; border-radius:999px; text-decoration:none;
    border:0; box-shadow:0 12px 28px -10px rgba(0,0,0,.5);
    transition:transform .25s ease, box-shadow .25s ease; position:relative; overflow:hidden; }
.cl-btn-add:hover { color:#4338ca; transform:translateY(-3px) scale(1.02); box-shadow:0 18px 36px -12px rgba(0,0,0,.55); }
.cl-btn-add::after { content:''; position:absolute; top:0; left:-70%; width:45%; height:100%;
    background:linear-gradient(100deg, transparent, rgba(99,102,241,.18), transparent);
    transform:skewX(-20deg); animation:clSheen 5s ease-in-out infinite; }

/* ---------- STAT TILES ---------- */
.cl-stat { position:relative; overflow:hidden; background:#fff; border:1px solid var(--cl-border);
    border-radius:var(--cl-radius); box-shadow:var(--cl-shadow); padding:1.1rem 1.2rem; height:100%;
    transition:transform .3s cubic-bezier(.25,.8,.25,1), box-shadow .3s, border-color .3s;
    animation:clRise .55s ease both; }
.cl-stat:hover { transform:translateY(-6px); box-shadow:var(--cl-shadow-hover); border-color:#c7d2fe; }
.cl-stat::before { content:''; position:absolute; top:0; left:0; right:0; height:4px;
    background:var(--g, linear-gradient(90deg,#4f46e5,#8b5cf6)); }
.cl-stat .ic { width:44px; height:44px; border-radius:13px; display:grid; place-items:center;
    color:#fff; font-size:1.15rem; flex-shrink:0;
    background:var(--g, linear-gradient(135deg,#4f46e5,#8b5cf6));
    box-shadow:0 10px 22px -8px var(--gs, rgba(99,102,241,.55));
    transition:transform .25s cubic-bezier(.34,1.56,.64,1); }
.cl-stat:hover .ic { transform:scale(1.1) rotate(-5deg); }
.cl-stat .lbl { font-size:.7rem; font-weight:800; letter-spacing:.08em; text-transform:uppercase; color:var(--cl-muted); }
.cl-stat .val { font-size:1.5rem; font-weight:800; color:var(--cl-ink); letter-spacing:-.4px;
    font-variant-numeric:tabular-nums; line-height:1.15; }

/* ---------- FILTER ---------- */
.cl-filter { background:rgba(255,255,255,.85); backdrop-filter:blur(8px); border:1px solid var(--cl-border);
    border-radius:var(--cl-radius); box-shadow:var(--cl-shadow); animation:clRise .55s .2s ease both; }
.cl-filter .form-label { font-size:.72rem; font-weight:700; letter-spacing:.06em; text-transform:uppercase; color:var(--cl-muted); }
.cl-filter .form-control, .cl-filter .form-select { border-radius:.75rem; border-color:var(--cl-border);
    transition:border-color .2s ease, box-shadow .2s ease; }
.cl-filter .form-control:focus, .cl-filter .form-select:focus { border-color:#a5b4fc;
    box-shadow:0 0 0 .22rem rgba(99,102,241,.12); }
.cl-search-wrap .search-ic { position:absolute; top:50%; left:.9rem; transform:translateY(-50%);
    color:#94a3b8; pointer-events:none; transition:color .2s ease; }
.cl-search-wrap .form-control { padding-left:2.5rem; }
.cl-search-wrap .form-control:focus ~ .search-ic { color:var(--cl-indigo); }
.cl-btn-reset { border-radius:.75rem; font-weight:700; border:1px solid var(--cl-border); color:var(--cl-muted);
    background:#fff; transition:all .22s ease; }
.cl-btn-reset:hover { color:var(--cl-indigo); border-color:#c7d2fe; background:#eef2ff; transform:translateY(-2px); }

/* ---------- TABEL ---------- */
.cl-table-card { background:#fff; border:1px solid var(--cl-border); border-radius:var(--cl-radius);
    box-shadow:var(--cl-shadow); overflow:hidden; animation:clRise .55s .28s ease both; }
#coaTableContainer { transition:opacity .25s ease, filter .25s ease; }
#coaTableContainer.cl-loading { opacity:.45; filter:blur(1.5px) saturate(.8); pointer-events:none; }
.cl-table-card .table { margin-bottom:0; }
.cl-table-card thead th { background:#f8faff !important; border-bottom:1px solid var(--cl-border) !important;
    font-size:.7rem; font-weight:800; letter-spacing:.1em; text-transform:uppercase; color:#7c8db5 !important; }
.cl-table-card tbody tr { animation:clRowIn .4s ease both; animation-delay:calc(var(--i, 0) * 40ms);
    transition:background .2s ease, box-shadow .2s ease; }
.cl-table-card tbody tr:hover { background:#f5f7ff; box-shadow:inset 3px 0 0 var(--cl-indigo); }
.cl-table-card tbody td { border-color:rgba(124,141,181,.08) !important; }

.cl-kode { font-family:SFMono-Regular,Menlo,Consolas,monospace; font-weight:800; color:#4338ca;
    font-size:.82rem; letter-spacing:-.01em; cursor:pointer; display:inline-flex; align-items:center;
    gap:.4rem; overflow-wrap:anywhere; transition:opacity .2s ease; }
.cl-kode .bi-copy { font-size:.68rem; color:#cbd5e1; transition:color .2s ease; }
.cl-kode:hover .bi-copy { color:var(--cl-indigo); }
.cl-kode.copied { color:#047857; }
.cl-kode.copied .bi-copy { color:#059669; }

.cl-badge { display:inline-flex; align-items:center; gap:.35rem; font-size:.7rem; font-weight:700;
    padding:.3rem .7rem; border-radius:999px; letter-spacing:.03em; white-space:nowrap; }
.cl-badge-akun    { background:#eef2ff; color:#4338ca; border:1px solid #e0e7ff;
    font-family:SFMono-Regular,Menlo,Consolas,monospace; }
.cl-badge-jenis   { background:#f1f5f9; color:#334155; border:1px solid #e2e8f0; }
.cl-badge-dipa    { background:#ecfeff; color:#0e7490; border:1px solid #a5f3fc; }
.cl-badge-zero    { background:#f8fafc; color:#94a3b8; border:1px solid #e2e8f0; }
.cl-badge-aktif   { background:#ecfdf5; color:#047857; border:1px solid #a7f3d0; }
.cl-badge-aktif .dot { width:7px; height:7px; border-radius:50%; background:#10b981; animation:clPulseG 2s infinite; }
.cl-badge-nonaktif { background:#f8fafc; color:#64748b; border:1px solid #e2e8f0; }

/* Tombol aksi */
.cl-act { display:inline-flex; align-items:center; justify-content:center; width:33px; height:33px;
    border-radius:11px; border:1px solid var(--cl-border); background:#fff; color:#64748b;
    transition:all .22s cubic-bezier(.25,.8,.25,1); text-decoration:none; font-size:.88rem; padding:0; }
.cl-act:hover:not(:disabled) { transform:translateY(-3px); }
.cl-act-view:hover   { background:#4f46e5; border-color:#4f46e5; color:#fff; box-shadow:0 8px 18px -6px rgba(79,70,229,.55); }
.cl-act-edit:hover   { background:#f59e0b; border-color:#f59e0b; color:#fff; box-shadow:0 8px 18px -6px rgba(245,158,11,.55); }
.cl-act-on           { color:#10b981; border-color:#a7f3d0; background:#ecfdf5; }
.cl-act-on:hover     { background:#10b981; border-color:#10b981; color:#fff; box-shadow:0 8px 18px -6px rgba(16,185,129,.55); }
.cl-act-off:hover    { background:#64748b; border-color:#64748b; color:#fff; }
.cl-act-del:hover:not(:disabled) { background:#e11d48; border-color:#e11d48; color:#fff; box-shadow:0 8px 18px -6px rgba(225,29,72,.55); }
.cl-act:disabled { opacity:.4; cursor:not-allowed; }

/* Empty state */
.cl-empty { padding:3.5rem 1rem; text-align:center; color:var(--cl-muted); }
.cl-empty .glyph { width:74px; height:74px; margin:0 auto 1rem; border-radius:22px; display:grid;
    place-items:center; font-size:2rem; color:#a5b4fc; background:linear-gradient(135deg,#eef2ff,#e0e7ff);
    animation:clFloat 5s ease-in-out infinite; }

/* Pagination */
.coa-list .pagination { --bs-pagination-border-color:var(--cl-border); --bs-pagination-color:var(--cl-muted);
    --bs-pagination-hover-bg:#eef2ff; --bs-pagination-hover-color:#4338ca; --bs-pagination-hover-border-color:#c7d2fe;
    --bs-pagination-active-bg:var(--cl-indigo); --bs-pagination-active-border-color:var(--cl-indigo);
    --bs-pagination-focus-box-shadow:none; }
</style>
@endpush

@section('content')
<div class="coa-list">

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

    @if(session('error'))
        <div class="alert alert-danger border-0 bg-danger alert-dismissible fade show shadow-sm">
            <div class="text-white">{{ session('error') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- ════════ HERO ════════ --}}
    <div class="cl-hero">
        <div class="mesh"></div>
        <div class="cl-hero-grid">
            <div>
                <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                    <span class="cl-chip"><span class="dot"></span> MASTER DATA</span>
                    <span class="cl-chip"><i class="bi bi-journal-bookmark-fill"></i> CHART OF ACCOUNT</span>
                </div>
                <h4>Master COA 🧾</h4>
                <div class="lead-sub">Kelola chart of account untuk kebutuhan DIPA dan seluruh transaksi BLU.</div>
            </div>
            <a href="{{ route('coas.create') }}" class="cl-btn-add">
                <i class="bi bi-plus-circle-fill"></i> Tambah COA
            </a>
        </div>
    </div>

    {{-- ════════ STAT TILES ════════ --}}
    <div class="row row-cols-1 row-cols-md-2 row-cols-xl-4 g-3 mb-4">
        @foreach([
            ['label' => 'Total COA',           'value' => $summary['total_coa'],           'icon' => 'bi-collection-fill',   'g' => 'linear-gradient(135deg,#6366f1,#8b5cf6)', 'gs' => 'rgba(99,102,241,.55)', 'd' => '.02s'],
            ['label' => 'COA Aktif',           'value' => $summary['coa_aktif'],           'icon' => 'bi-patch-check-fill',  'g' => 'linear-gradient(135deg,#10b981,#14b8a6)', 'gs' => 'rgba(16,185,129,.5)',  'd' => '.08s'],
            ['label' => 'Kode Akun Unik',      'value' => $summary['kode_akun_unik'],      'icon' => 'bi-hash',              'g' => 'linear-gradient(135deg,#f59e0b,#f97316)', 'gs' => 'rgba(245,158,11,.5)',  'd' => '.14s'],
            ['label' => 'Dipakai di Item DIPA','value' => $summary['coa_dipakai_di_dipa'], 'icon' => 'bi-folder2-open',      'g' => 'linear-gradient(135deg,#06b6d4,#3b82f6)', 'gs' => 'rgba(6,182,212,.5)',   'd' => '.2s'],
        ] as $stat)
            <div class="col">
                <div class="cl-stat" style="--g:{{ $stat['g'] }}; --gs:{{ $stat['gs'] }}; animation-delay:{{ $stat['d'] }};">
                    <div class="d-flex align-items-center gap-3">
                        <div class="ic"><i class="bi {{ $stat['icon'] }}"></i></div>
                        <div>
                            <div class="lbl">{{ $stat['label'] }}</div>
                            <div class="val cl-countup" data-target="{{ $stat['value'] }}">0</div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- ════════ FILTER ════════ --}}
    <div class="cl-filter mb-4">
        <div class="card-body p-4">
            <form method="GET" action="{{ route('coas.index') }}" id="coaFilterForm">
                <div class="row g-3 align-items-end">
                    <div class="col-md-5">
                        <label class="form-label" for="coaSearchInput">Cari Kode COA</label>
                        <div class="position-relative cl-search-wrap">
                            <input type="text" name="search" id="coaSearchInput" value="{{ $search }}" class="form-control pe-5" placeholder="Ketik kode MAK lengkap atau kode akun…" autocomplete="off" inputmode="search">
                            <i class="bi bi-search search-ic"></i>
                            <span class="position-absolute top-50 end-0 translate-middle-y me-3 text-muted d-none" id="coaSearchSpinner" aria-hidden="true">
                                <span class="spinner-border spinner-border-sm" role="status"></span>
                            </span>
                        </div>
                        <small class="text-muted">Pencarian hanya berdasarkan kode COA (kode MAK lengkap / kode akun).</small>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Jenis Akun</label>
                        <select name="jenis_akun" class="form-select" data-auto-submit="change">
                            <option value="">Semua</option>
                            @foreach($jenisAkunOptions as $option)
                                <option value="{{ $option }}" {{ (string) $jenisAkun === (string) $option ? 'selected' : '' }}>{{ $option }}</option>
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
                        <div class="d-grid">
                            <a href="{{ route('coas.index') }}" class="btn cl-btn-reset">
                                <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- ════════ TABEL ════════ --}}
    <div class="cl-table-card">
        <div id="coaTableContainer" data-coa-table>
            @include('coas._table')
        </div>
    </div>

    @include('layouts._partials.del-tooltip')
</div>
@endsection

@push('script')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            /* ── Count-up statistik ── */
            var reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            document.querySelectorAll('.cl-countup').forEach(function (el) {
                var target = parseInt(el.dataset.target || '0', 10);
                if (reduced || target <= 0) { el.textContent = target.toLocaleString('id-ID'); return; }
                var dur = 1100, start = performance.now();
                (function step(now) {
                    var p = Math.min((now - start) / dur, 1);
                    var eased = 1 - Math.pow(1 - p, 3);
                    el.textContent = Math.round(target * eased).toLocaleString('id-ID');
                    if (p < 1) requestAnimationFrame(step);
                })(performance.now());
            });

            /* ── Klik kode COA untuk menyalin (delegated — aman setelah swap AJAX) ── */
            document.addEventListener('click', function (e) {
                var el = e.target.closest('.cl-kode[data-copy]');
                if (!el) return;
                var text = el.getAttribute('data-copy');
                var label = el.querySelector('.cl-kode-text');
                var asli = label ? label.textContent : '';
                function done() {
                    el.classList.add('copied');
                    if (label) label.textContent = 'Tersalin!';
                    setTimeout(function () {
                        el.classList.remove('copied');
                        if (label) label.textContent = asli;
                    }, 1200);
                }
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(text).then(done);
                } else {
                    var ta = document.createElement('textarea');
                    ta.value = text; document.body.appendChild(ta);
                    ta.select(); document.execCommand('copy'); ta.remove();
                    done();
                }
            });

            /* ── Filter + pencarian AJAX (fungsional tidak berubah) ── */
            const form = document.getElementById('coaFilterForm');
            const tableContainer = document.getElementById('coaTableContainer');
            const searchInput = document.getElementById('coaSearchInput');
            const spinner = document.getElementById('coaSearchSpinner');

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
                // Reset paginator setiap kali query berubah.
                params.delete('page');
                return params;
            };

            const setLoading = function (loading) {
                if (spinner) spinner.classList.toggle('d-none', !loading);
                // Konten tabel memudar + blur halus selama data dimuat.
                tableContainer.classList.toggle('cl-loading', loading);
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
                        throw new Error('Gagal memuat data COA (HTTP ' + response.status + ').');
                    }

                    const html = await response.text();
                    tableContainer.innerHTML = html;

                    // Sinkronkan URL browser agar hasil pencarian tetap dapat disalin/dibookmark.
                    const newUrl = userQueryString
                        ? form.action + '?' + userQueryString
                        : form.action;
                    window.history.replaceState({}, '', newUrl);
                } catch (err) {
                    if (err.name === 'AbortError') {
                        return;
                    }
                    console.error(err);
                    // Reset cache supaya user bisa mencoba lagi dengan input yang sama.
                    lastQueryString = null;
                } finally {
                    setLoading(false);
                }
            };

            const triggerImmediate = function () {
                clearTimeout(debounceTimer);
                // Paksa fetch meski query string sama (mis. user klik tombol Filter ulang).
                lastQueryString = null;
                fetchTable();
            };

            searchInput.addEventListener('input', function () {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(fetchTable, DEBOUNCE_MS);
            });

            // Submit form (klik tombol Filter / Enter) — langsung jalankan tanpa menunggu debounce.
            form.addEventListener('submit', function (event) {
                event.preventDefault();
                triggerImmediate();
            });

            // Dropdown filter — jalankan langsung saat berubah.
            form.querySelectorAll('[data-auto-submit="change"]').forEach(function (field) {
                field.addEventListener('change', triggerImmediate);
            });

            // Intercept klik paginasi agar tetap AJAX (tidak full reload).
            tableContainer.addEventListener('click', async function (event) {
                const link = event.target.closest('.pagination a');
                if (!link) {
                    return;
                }
                event.preventDefault();

                const targetUrl = new URL(link.href, window.location.origin);
                const pageNumber = targetUrl.searchParams.get('page');
                if (!pageNumber) {
                    return;
                }

                if (abortController) {
                    abortController.abort();
                }
                abortController = new AbortController();

                const params = buildParams();
                params.set('page', pageNumber);
                const userQueryString = params.toString();
                lastQueryString = userQueryString;
                params.set('partial', '1');

                setLoading(true);
                try {
                    const response = await fetch(form.action + '?' + params.toString(), {
                        method: 'GET',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'text/html',
                        },
                        signal: abortController.signal,
                        credentials: 'same-origin',
                    });
                    if (!response.ok) {
                        throw new Error('Gagal memuat halaman (HTTP ' + response.status + ').');
                    }
                    tableContainer.innerHTML = await response.text();
                    window.history.replaceState({}, '', form.action + '?' + userQueryString);
                    tableContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
                } catch (err) {
                    if (err.name !== 'AbortError') {
                        console.error(err);
                        lastQueryString = null;
                    }
                } finally {
                    setLoading(false);
                }
            });
        });
    </script>
@endpush
