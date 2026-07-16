@extends('layouts.app')

@section('title', 'Master Pajak')

@php
    $search = request('search');
    $statusFilter = request('status_aktif');
    $berlakuFilter = request('berlaku');
    $today = \Carbon\Carbon::today();
@endphp

@push('css')
<style>
/* ============================================================
   MASTER PAJAK — hero aurora · stat tiles · tabel interaktif
   ============================================================ */
.pajak-list { --tp-emerald:#10b981; --tp-teal:#14b8a6; --tp-cyan:#06b6d4; --tp-amber:#f59e0b;
    --tp-rose:#f43f5e; --tp-ink:#0f172a; --tp-muted:#64748b; --tp-border:#e6efec;
    --tp-radius:1.15rem;
    --tp-shadow:0 18px 40px -22px rgba(6,78,59,.26);
    --tp-shadow-hover:0 28px 56px -24px rgba(13,148,136,.4); }

@keyframes tpAurora { 0%{background-position:0% 50%} 50%{background-position:100% 50%} 100%{background-position:0% 50%} }
@keyframes tpFloat  { 0%,100%{transform:translateY(0)} 50%{transform:translateY(-12px)} }
@keyframes tpRise   { from{opacity:0; transform:translateY(18px)} to{opacity:1; transform:none} }
@keyframes tpRowIn  { from{opacity:0; transform:translateY(10px)} to{opacity:1; transform:none} }
@keyframes tpSheen  { 0%,55%{left:-70%} 85%,100%{left:140%} }
@keyframes tpPulseG { 0%,100%{box-shadow:0 0 0 0 rgba(16,185,129,.5)} 50%{box-shadow:0 0 0 8px rgba(16,185,129,0)} }
@keyframes tpPulseA { 0%,100%{box-shadow:0 0 0 0 rgba(245,158,11,.45)} 50%{box-shadow:0 0 0 8px rgba(245,158,11,0)} }
@keyframes tpSpinSlow { from{transform:rotate(0)} to{transform:rotate(360deg)} }
@media (prefers-reduced-motion: reduce) {
    .pajak-list * { animation-duration:.001s !important; animation-iteration-count:1 !important; transition-duration:.001s !important; }
}

/* ---------- HERO ---------- */
.tp-hero { position:relative; overflow:hidden; border-radius:1.5rem; padding:1.9rem 2rem;
    margin-bottom:1.4rem; color:#fff;
    background:linear-gradient(125deg,#022c22,#064e3b 30%,#0f766e 60%,#0e7490 82%,#155e75);
    background-size:340% 340%; animation:tpAurora 18s ease infinite;
    box-shadow:0 28px 56px -26px rgba(6,78,59,.65); }
.tp-hero::before, .tp-hero::after { content:''; position:absolute; border-radius:50%; pointer-events:none;
    background:radial-gradient(circle, rgba(255,255,255,.16) 0%, transparent 70%); }
.tp-hero::before { width:380px; height:380px; top:-58%; left:-3%; animation:tpFloat 10s ease-in-out infinite; }
.tp-hero::after  { width:280px; height:280px; bottom:-62%; right:-2%; animation:tpFloat 13s ease-in-out infinite reverse; }
.tp-hero .mesh { position:absolute; inset:0; opacity:.15; pointer-events:none;
    background-image:linear-gradient(rgba(255,255,255,.4) 1px, transparent 1px),
                     linear-gradient(90deg, rgba(255,255,255,.4) 1px, transparent 1px);
    background-size:44px 44px; mask-image:radial-gradient(ellipse at 22% 0%, #000 5%, transparent 62%); }
.tp-hero .pct-glyph { position:absolute; right:2.4rem; bottom:-1.4rem; font-size:7rem; font-weight:900;
    color:rgba(255,255,255,.08); pointer-events:none; user-select:none; line-height:1;
    animation:tpFloat 8s ease-in-out infinite; }
.tp-hero-grid { position:relative; z-index:2; display:flex; flex-wrap:wrap; justify-content:space-between;
    align-items:center; gap:1.2rem; }
.tp-hero h4 { font-weight:800; letter-spacing:-.4px; margin:0; color:#fff !important;
    font-size:clamp(1.25rem,2.4vw,1.7rem); }
.tp-hero .lead-sub { color:#fff; opacity:.78; font-weight:600; font-size:.88rem; margin-top:.3rem; }
.tp-chip { display:inline-flex; align-items:center; gap:.45rem; background:rgba(255,255,255,.13);
    border:1px solid rgba(255,255,255,.25); backdrop-filter:blur(10px); padding:.34rem .9rem;
    border-radius:999px; font-weight:700; font-size:.72rem; letter-spacing:.4px; color:#fff; }
.tp-chip .dot { width:8px; height:8px; border-radius:50%; background:#34d399; animation:tpPulseG 2s infinite; }
.tp-chip .bi-gear-fill { animation:tpSpinSlow 9s linear infinite; }
.tp-btn-add { display:inline-flex; align-items:center; gap:.55rem; background:#fff; color:#0f766e;
    font-weight:800; font-size:.9rem; padding:.68rem 1.4rem; border-radius:999px; text-decoration:none;
    border:0; box-shadow:0 12px 28px -10px rgba(0,0,0,.5);
    transition:transform .25s ease, box-shadow .25s ease; position:relative; overflow:hidden; }
.tp-btn-add:hover { color:#0f766e; transform:translateY(-3px) scale(1.02); box-shadow:0 18px 36px -12px rgba(0,0,0,.55); }
.tp-btn-add::after { content:''; position:absolute; top:0; left:-70%; width:45%; height:100%;
    background:linear-gradient(100deg, transparent, rgba(20,184,166,.18), transparent);
    transform:skewX(-20deg); animation:tpSheen 5s ease-in-out infinite; }

/* ---------- STAT TILES ---------- */
.tp-stat { position:relative; overflow:hidden; background:#fff; border:1px solid var(--tp-border);
    border-radius:var(--tp-radius); box-shadow:var(--tp-shadow); padding:1.1rem 1.2rem; height:100%;
    transition:transform .3s cubic-bezier(.25,.8,.25,1), box-shadow .3s, border-color .3s;
    animation:tpRise .55s ease both; }
.tp-stat:hover { transform:translateY(-6px); box-shadow:var(--tp-shadow-hover); border-color:#99f6e4; }
.tp-stat::before { content:''; position:absolute; top:0; left:0; right:0; height:4px;
    background:var(--g, linear-gradient(90deg,#10b981,#14b8a6)); }
.tp-stat .ic { width:44px; height:44px; border-radius:13px; display:grid; place-items:center;
    color:#fff; font-size:1.15rem; flex-shrink:0;
    background:var(--g, linear-gradient(135deg,#10b981,#14b8a6));
    box-shadow:0 10px 22px -8px var(--gs, rgba(16,185,129,.55));
    transition:transform .25s cubic-bezier(.34,1.56,.64,1); }
.tp-stat:hover .ic { transform:scale(1.1) rotate(-5deg); }
.tp-stat .lbl { font-size:.7rem; font-weight:800; letter-spacing:.08em; text-transform:uppercase; color:var(--tp-muted); }
.tp-stat .val { font-size:1.5rem; font-weight:800; color:var(--tp-ink); letter-spacing:-.4px;
    font-variant-numeric:tabular-nums; line-height:1.15; }

/* ---------- FILTER ---------- */
.tp-filter { background:rgba(255,255,255,.85); backdrop-filter:blur(8px); border:1px solid var(--tp-border);
    border-radius:var(--tp-radius); box-shadow:var(--tp-shadow); animation:tpRise .55s .2s ease both; }
.tp-filter .form-label { font-size:.72rem; font-weight:700; letter-spacing:.06em; text-transform:uppercase; color:var(--tp-muted); }
.tp-filter .form-control, .tp-filter .form-select { border-radius:.75rem; border-color:var(--tp-border);
    transition:border-color .2s ease, box-shadow .2s ease; }
.tp-filter .form-control:focus, .tp-filter .form-select:focus { border-color:#5eead4;
    box-shadow:0 0 0 .22rem rgba(20,184,166,.12); }
.tp-search-wrap .search-ic { position:absolute; top:50%; left:.9rem; transform:translateY(-50%);
    color:#94a3b8; pointer-events:none; transition:color .2s ease; }
.tp-search-wrap .form-control { padding-left:2.5rem; }
.tp-search-wrap .form-control:focus ~ .search-ic { color:var(--tp-teal); }
.tp-btn-reset { border-radius:.75rem; font-weight:700; border:1px solid var(--tp-border); color:var(--tp-muted);
    background:#fff; transition:all .22s ease; }
.tp-btn-reset:hover { color:#0f766e; border-color:#99f6e4; background:#f0fdfa; transform:translateY(-2px); }

/* ---------- TABEL ---------- */
.tp-table-card { background:#fff; border:1px solid var(--tp-border); border-radius:var(--tp-radius);
    box-shadow:var(--tp-shadow); overflow:hidden; animation:tpRise .55s .28s ease both; }
#pajakTableContainer { transition:opacity .25s ease, filter .25s ease; }
#pajakTableContainer.tp-loading { opacity:.45; filter:blur(1.5px) saturate(.8); pointer-events:none; }
.tp-table-card .table { margin-bottom:0; }
.tp-table-card thead th { background:#f6fdfa !important; border-bottom:1px solid var(--tp-border) !important;
    font-size:.7rem; font-weight:800; letter-spacing:.1em; text-transform:uppercase; color:#6b9c8f !important; }
.tp-table-card tbody tr { animation:tpRowIn .4s ease both; animation-delay:calc(var(--i, 0) * 40ms);
    transition:background .2s ease, box-shadow .2s ease; }
.tp-table-card tbody tr:hover { background:#f2fbf7; box-shadow:inset 3px 0 0 var(--tp-teal); }
.tp-table-card tbody td { border-color:rgba(107,156,143,.1) !important; }

.tp-kode { font-family:SFMono-Regular,Menlo,Consolas,monospace; font-weight:800; color:#0f766e;
    font-size:.82rem; letter-spacing:-.01em; cursor:pointer; display:inline-flex; align-items:center;
    gap:.4rem; overflow-wrap:anywhere; transition:opacity .2s ease; }
.tp-kode .bi-copy { font-size:.68rem; color:#cbd5e1; transition:color .2s ease; }
.tp-kode:hover .bi-copy { color:var(--tp-teal); }
.tp-kode.copied { color:#047857; }
.tp-kode.copied .bi-copy { color:#059669; }

.tp-persen { display:inline-flex; align-items:baseline; gap:.1rem; font-weight:900; font-size:1.02rem;
    color:#0f766e; font-variant-numeric:tabular-nums; }
.tp-persen small { font-size:.68rem; font-weight:800; color:#5eead4; }

.tp-badge { display:inline-flex; align-items:center; gap:.35rem; font-size:.7rem; font-weight:700;
    padding:.3rem .7rem; border-radius:999px; letter-spacing:.03em; white-space:nowrap; }
.tp-badge-jenis    { background:#f0fdfa; color:#0f766e; border:1px solid #ccfbf1; }
.tp-badge-kap      { background:#f8fafc; color:#475569; border:1px solid #e2e8f0;
    font-family:SFMono-Regular,Menlo,Consolas,monospace; }
.tp-badge-berlaku  { background:#ecfdf5; color:#047857; border:1px solid #a7f3d0; }
.tp-badge-berlaku .dot { width:7px; height:7px; border-radius:50%; background:#10b981; animation:tpPulseG 2s infinite; }
.tp-badge-segera   { background:#fffbeb; color:#b45309; border:1px solid #fde68a; animation:tpPulseA 2.4s infinite; }
.tp-badge-belum    { background:#fefce8; color:#a16207; border:1px solid #fef08a; }
.tp-badge-expired  { background:#fff1f2; color:#be123c; border:1px solid #fecdd3; }
.tp-badge-aktif    { background:#ecfdf5; color:#047857; border:1px solid #a7f3d0; }
.tp-badge-aktif .dot { width:7px; height:7px; border-radius:50%; background:#10b981; animation:tpPulseG 2s infinite; }
.tp-badge-nonaktif { background:#f8fafc; color:#64748b; border:1px solid #e2e8f0; }

.tp-rumus { font-size:.76rem; color:var(--tp-muted); font-family:SFMono-Regular,Menlo,Consolas,monospace;
    background:#f8fafc; border:1px dashed #e2e8f0; border-radius:.55rem; padding:.22rem .55rem;
    display:inline-block; max-width:220px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }

/* Tombol aksi */
.tp-act { display:inline-flex; align-items:center; justify-content:center; width:33px; height:33px;
    border-radius:11px; border:1px solid var(--tp-border); background:#fff; color:#64748b;
    transition:all .22s cubic-bezier(.25,.8,.25,1); text-decoration:none; font-size:.88rem; padding:0; }
.tp-act:hover { transform:translateY(-3px); }
.tp-act-view:hover { background:#0f766e; border-color:#0f766e; color:#fff; box-shadow:0 8px 18px -6px rgba(15,118,110,.55); }
.tp-act-edit:hover { background:#f59e0b; border-color:#f59e0b; color:#fff; box-shadow:0 8px 18px -6px rgba(245,158,11,.55); }
.tp-act-on         { color:#10b981; border-color:#a7f3d0; background:#ecfdf5; }
.tp-act-on:hover   { background:#10b981; border-color:#10b981; color:#fff; box-shadow:0 8px 18px -6px rgba(16,185,129,.55); }
.tp-act-off:hover  { background:#64748b; border-color:#64748b; color:#fff; }

/* Empty state */
.tp-empty { padding:3.5rem 1rem; text-align:center; color:var(--tp-muted); }
.tp-empty .glyph { width:74px; height:74px; margin:0 auto 1rem; border-radius:22px; display:grid;
    place-items:center; font-size:2rem; color:#5eead4; background:linear-gradient(135deg,#f0fdfa,#ccfbf1);
    animation:tpFloat 5s ease-in-out infinite; }

/* Pagination */
.pajak-list .pagination { --bs-pagination-border-color:var(--tp-border); --bs-pagination-color:var(--tp-muted);
    --bs-pagination-hover-bg:#f0fdfa; --bs-pagination-hover-color:#0f766e; --bs-pagination-hover-border-color:#99f6e4;
    --bs-pagination-active-bg:var(--tp-teal); --bs-pagination-active-border-color:var(--tp-teal);
    --bs-pagination-focus-box-shadow:none; }
</style>
@endpush

@section('content')
<div class="pajak-list">

    @if(session('success'))
        <div class="alert alert-success border-0 bg-success alert-dismissible fade show shadow-sm">
            <div class="text-white">{{ session('success') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger border-0 bg-danger alert-dismissible fade show shadow-sm">
            <div class="text-white">{{ session('error') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- ════════ HERO ════════ --}}
    <div class="tp-hero">
        <div class="mesh"></div>
        <span class="pct-glyph">%</span>
        <div class="tp-hero-grid">
            <div>
                <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                    <span class="tp-chip"><span class="dot"></span> MASTER DATA</span>
                    <span class="tp-chip"><i class="bi bi-gear-fill"></i> TARIF PAJAK</span>
                </div>
                <h4>Master Pajak 💰</h4>
                <div class="lead-sub">Kelola tarif pajak sebagai acuan potongan pada SPP, SPM, dan dokumen pencairan lainnya.</div>
            </div>
            <a href="{{ route('master-pajak.create') }}" class="tp-btn-add">
                <i class="bi bi-plus-circle-fill"></i> Tambah Pajak
            </a>
        </div>
    </div>

    {{-- ════════ STAT TILES ════════ --}}
    <div class="row row-cols-1 row-cols-md-2 row-cols-xl-4 g-3 mb-4">
        @foreach([
            ['label' => 'Total Tarif',      'value' => $summary['total'],            'icon' => 'bi-collection-fill',  'g' => 'linear-gradient(135deg,#0f766e,#14b8a6)', 'gs' => 'rgba(15,118,110,.55)', 'd' => '.02s'],
            ['label' => 'Tarif Aktif',      'value' => $summary['aktif'],            'icon' => 'bi-patch-check-fill', 'g' => 'linear-gradient(135deg,#10b981,#34d399)', 'gs' => 'rgba(16,185,129,.5)',  'd' => '.08s'],
            ['label' => 'Tarif Nonaktif',   'value' => $summary['nonaktif'],         'icon' => 'bi-pause-circle-fill','g' => 'linear-gradient(135deg,#64748b,#94a3b8)', 'gs' => 'rgba(100,116,139,.5)', 'd' => '.14s'],
            ['label' => 'Berlaku Saat Ini', 'value' => $summary['berlaku_sekarang'], 'icon' => 'bi-calendar2-check-fill', 'g' => 'linear-gradient(135deg,#06b6d4,#0e7490)', 'gs' => 'rgba(6,182,212,.5)', 'd' => '.2s'],
        ] as $stat)
            <div class="col">
                <div class="tp-stat" style="--g:{{ $stat['g'] }}; --gs:{{ $stat['gs'] }}; animation-delay:{{ $stat['d'] }};">
                    <div class="d-flex align-items-center gap-3">
                        <div class="ic"><i class="bi {{ $stat['icon'] }}"></i></div>
                        <div>
                            <div class="lbl">{{ $stat['label'] }}</div>
                            <div class="val tp-countup" data-target="{{ $stat['value'] }}">0</div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- ════════ FILTER ════════ --}}
    <div class="tp-filter mb-4">
        <div class="card-body p-4">
            <form method="GET" action="{{ route('master-pajak.index') }}" id="pajakFilterForm">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label" for="pajakSearchInput">Cari Kode Pajak</label>
                        <div class="position-relative tp-search-wrap">
                            <input type="text" name="search" id="pajakSearchInput" value="{{ $search }}" class="form-control pe-5" placeholder="Ketik kode pajak…" autocomplete="off" inputmode="search">
                            <i class="bi bi-search search-ic"></i>
                            <span class="position-absolute top-50 end-0 translate-middle-y me-3 text-muted d-none" id="pajakSearchSpinner" aria-hidden="true">
                                <span class="spinner-border spinner-border-sm" role="status"></span>
                            </span>
                        </div>
                        <small class="text-muted">Pencarian hanya berdasarkan kode pajak.</small>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Status</label>
                        <select name="status_aktif" class="form-select" data-auto-submit="change">
                            <option value="">Semua</option>
                            <option value="aktif" {{ $statusFilter === 'aktif' ? 'selected' : '' }}>Aktif</option>
                            <option value="nonaktif" {{ $statusFilter === 'nonaktif' ? 'selected' : '' }}>Nonaktif</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Masa Berlaku</label>
                        <select name="berlaku" class="form-select" data-auto-submit="change">
                            <option value="">Semua</option>
                            <option value="berlaku" {{ $berlakuFilter === 'berlaku' ? 'selected' : '' }}>Berlaku Saat Ini</option>
                            <option value="belum" {{ $berlakuFilter === 'belum' ? 'selected' : '' }}>Belum Berlaku</option>
                            <option value="expired" {{ $berlakuFilter === 'expired' ? 'selected' : '' }}>Sudah Berakhir</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <div class="d-grid">
                            <a href="{{ route('master-pajak.index') }}" class="btn tp-btn-reset">
                                <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- ════════ TABEL ════════ --}}
    <div class="tp-table-card">
        <div id="pajakTableContainer">
            @include('master-pajak._table')
        </div>
    </div>

</div>
@endsection

@push('script')
    <script>
        (function () {
            // Jalankan init apa pun kondisi timing-nya: saat parsing masih berjalan
            // tunggu DOMContentLoaded, selain itu langsung eksekusi.
            function onReady(fn) {
                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', fn);
                } else {
                    fn();
                }
            }

            onReady(function () {
            /* ── Count-up statistik ── */
            var reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            document.querySelectorAll('.tp-countup').forEach(function (el) {
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

            /* ── Klik kode pajak untuk menyalin (delegated — aman setelah swap AJAX) ── */
            document.addEventListener('click', function (e) {
                var el = e.target.closest('.tp-kode[data-copy]');
                if (!el) return;
                var text = el.getAttribute('data-copy');
                var label = el.querySelector('.tp-kode-text');
                var asli = label ? label.textContent : '';
                function done() {
                    el.classList.add('copied');
                    if (label) label.textContent = 'Tersalin!';
                    setTimeout(function () {
                        el.classList.remove('copied');
                        if (label) label.textContent = asli;
                    }, 1200);
                }
                var fallbackCopy = function () {
                    var ta = document.createElement('textarea');
                    ta.value = text; document.body.appendChild(ta);
                    ta.select(); document.execCommand('copy'); ta.remove();
                    done();
                };
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(text).then(done).catch(fallbackCopy);
                } else {
                    fallbackCopy();
                }
            });

            /* ── Filter + pencarian AJAX (fungsional tidak berubah) ── */
            const form = document.getElementById('pajakFilterForm');
            const tableContainer = document.getElementById('pajakTableContainer');
            const searchInput = document.getElementById('pajakSearchInput');
            const spinner = document.getElementById('pajakSearchSpinner');

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
                tableContainer.classList.toggle('tp-loading', loading);
            };

            const fetchTable = async function () {
                const params = buildParams();
                const userQueryString = params.toString();

                if (userQueryString === lastQueryString) {
                    return;
                }
                lastQueryString = userQueryString;

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
                        throw new Error('Gagal memuat data pajak (HTTP ' + response.status + ').');
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

            // Intercept klik paginasi agar tetap AJAX.
            tableContainer.addEventListener('click', async function (event) {
                const link = event.target.closest('.pagination a');
                if (!link) return;
                event.preventDefault();

                const targetUrl = new URL(link.href, window.location.origin);
                const pageNumber = targetUrl.searchParams.get('page');
                if (!pageNumber) return;

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
        })();
    </script>
@endpush
