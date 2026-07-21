@extends('layouts.app')
@section('title')
    Master Data Mitra & Vendor
@endsection
@push('css')
    <link href="{{ URL::asset('build/plugins/datatable/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet" />
<style>
.vendor-page { --vd-ink:#1e1b4b; --vd-border:#e2e8f0; }

/* ── animasi ── */
@keyframes vdAurora { 0%{background-position:0% 50%} 50%{background-position:100% 50%} 100%{background-position:0% 50%} }
@keyframes vdFloat { 0%,100%{transform:translateY(0)} 50%{transform:translateY(-11px)} }
@keyframes vdRise { from{opacity:0; transform:translateY(16px)} to{opacity:1; transform:none} }
@keyframes vdRowIn { from{opacity:0; transform:translateY(8px)} to{opacity:1; transform:none} }
@media (prefers-reduced-motion: reduce) {
    .vendor-page *, .vendor-page *::before, .vendor-page *::after { animation-duration:.001s !important; animation-delay:0s !important; animation-iteration-count:1 !important; transition-duration:.001s !important; }
}

/* ── hero ── */
.vd-hero { position:relative; overflow:hidden; border-radius:1.5rem; padding:1.9rem 2.1rem; margin-bottom:1.4rem; color:#fff;
    background:linear-gradient(125deg,#0b1020,#1e1b4b 35%,#4338ca 65%,#0e7490 95%);
    background-size:320% 320%; animation:vdAurora 18s ease infinite;
    box-shadow:0 28px 56px -26px rgba(30,27,75,.6); }
.vd-hero::before, .vd-hero::after { content:''; position:absolute; border-radius:50%; pointer-events:none;
    background:radial-gradient(circle, rgba(255,255,255,.15) 0%, transparent 70%); }
.vd-hero::before { width:340px; height:340px; top:-55%; left:-4%; animation:vdFloat 9s ease-in-out infinite; }
.vd-hero::after { width:260px; height:260px; bottom:-58%; right:-2%; animation:vdFloat 12s ease-in-out infinite reverse; }
.vd-hero .mesh { position:absolute; inset:0; opacity:.14; pointer-events:none;
    background-image:linear-gradient(rgba(255,255,255,.4) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,.4) 1px, transparent 1px);
    background-size:42px 42px; mask-image:radial-gradient(ellipse at 20% 0%, #000 5%, transparent 60%); }
.vd-hero .kicker { font-size:.72rem; letter-spacing:2.5px; color:#a5b4fc; font-weight:800; }
.vd-hero h4 { color:#fff !important; font-weight:800; letter-spacing:-.4px; margin:0; font-size:clamp(1.25rem,2.3vw,1.65rem); }
.vd-hero .sub { color:#c7d2fe; font-size:.86rem; max-width:620px; }
.vd-btn-add { position:relative; z-index:2; display:inline-flex; align-items:center; gap:.5rem; border-radius:999px;
    padding:.6rem 1.3rem; font-weight:800; color:#1e1b4b; background:#fff; border:0; text-decoration:none;
    box-shadow:0 14px 28px -12px rgba(0,0,0,.5); transition:transform .2s, box-shadow .2s; }
.vd-btn-add:hover { transform:translateY(-2px); color:#4338ca; box-shadow:0 20px 36px -12px rgba(0,0,0,.55); }

/* ── kartu statistik ── */
.vd-stat { position:relative; overflow:hidden; background:#fff; border:1px solid #e0e7ff; border-radius:1.05rem;
    padding:1rem 1.15rem; height:100%; box-shadow:0 16px 36px -26px rgba(30,27,75,.5);
    opacity:0; animation:vdRise .5s cubic-bezier(.22,.61,.36,1) both; animation-delay:var(--d,0s);
    transition:transform .25s ease, box-shadow .25s ease; }
.vd-stat:hover { transform:translateY(-4px); box-shadow:0 24px 44px -24px rgba(67,56,202,.45); }
.vd-stat::after { content:''; position:absolute; top:0; left:0; right:0; height:4px;
    background:linear-gradient(90deg,var(--a),var(--b)); }
.vd-stat .ic { display:inline-grid; place-items:center; width:40px; height:40px; border-radius:.85rem; float:right;
    background:linear-gradient(135deg,var(--a),var(--b)); color:#fff; font-size:1.05rem;
    box-shadow:0 10px 22px -10px var(--a); }
.vd-stat .lbl { font-size:.68rem; letter-spacing:1.2px; text-transform:uppercase; color:#64748b; font-weight:800; }
.vd-stat .val { font-size:1.55rem; font-weight:800; color:var(--vd-ink); letter-spacing:-.5px; }
.vd-stat .hint { font-size:.72rem; color:#94a3b8; }

/* ── kartu tabel + skin DataTables ── */
.vd-table-card { background:#fff; border:1px solid #e0e7ff; border-radius:1.15rem; overflow:hidden;
    box-shadow:0 20px 44px -30px rgba(30,27,75,.5); opacity:0; animation:vdRise .55s .18s cubic-bezier(.22,.61,.36,1) both; }
.vd-table-card .head { display:flex; flex-wrap:wrap; align-items:center; gap:.7rem; padding:1rem 1.35rem; border-bottom:1px solid #eef2ff;
    background:linear-gradient(180deg,#fafbff,#fff); }
.vd-table-card .head .ic { display:inline-grid; place-items:center; width:30px; height:30px; border-radius:.65rem;
    background:linear-gradient(135deg,#4338ca,#0e7490); color:#fff; font-size:.85rem; }
.vd-table-card .head .ttl { font-weight:800; color:var(--vd-ink); font-size:.95rem; }
.vd-table-card .head .hint { margin-left:auto; font-size:.74rem; color:#94a3b8; }
.vd-table-card .card-body { padding:1.1rem 1.35rem 1.35rem; }

.vendor-page div.dataTables_wrapper div.dataTables_filter input {
    border-radius:999px; border:1.5px solid #e0e7ff; padding:.45rem 1rem; min-width:260px; transition:border-color .2s, box-shadow .2s; }
.vendor-page div.dataTables_wrapper div.dataTables_filter input:focus { outline:0; border-color:#818cf8; box-shadow:0 0 0 .2rem rgba(99,102,241,.12); }
.vendor-page div.dataTables_wrapper div.dataTables_length select { border-radius:.6rem; border:1.5px solid #e0e7ff; padding:.35rem 1.8rem .35rem .7rem; }
.vendor-page table.dataTable thead th { background:#f8faff !important; border-bottom:2px solid #e0e7ff !important;
    font-size:.68rem; letter-spacing:.8px; text-transform:uppercase; color:#475569; font-weight:800; }
.vendor-page table.dataTable tbody tr { transition:background .2s ease, box-shadow .2s ease; }
.vendor-page table.dataTable tbody tr.vd-in { animation:vdRowIn .38s ease both; animation-delay:calc(var(--i,0) * 40ms); }
.vendor-page table.dataTable tbody tr:hover { background:#f5f7ff !important; box-shadow:inset 3px 0 0 #4f46e5; }
.vendor-page .dataTables_paginate .page-link { border-radius:.55rem !important; margin:0 2px; border:1px solid #e0e7ff; color:#4338ca; }
.vendor-page .dataTables_paginate .page-item.active .page-link { background:linear-gradient(120deg,#4338ca,#7c3aed); border-color:transparent; }
.vendor-page .dataTables_processing { border-radius:.8rem; box-shadow:0 10px 30px -12px rgba(30,27,75,.35); }
</style>
@endpush
@section('content')
<div class="vendor-page">

    {{-- ════════ HERO ════════ --}}
    <div class="vd-hero">
        <div class="mesh"></div>
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3" style="position:relative; z-index:2;">
            <div>
                <div class="kicker"><i class="bi bi-buildings me-1"></i>MASTER DATA</div>
                <h4>Mitra &amp; Vendor 🏢</h4>
                <div class="sub mt-1">Rujukan penyedia untuk SPK, kontrak eksternal, dan seluruh tagihan — lengkap dengan NPWP, penanggung jawab, dan rekening pembayarannya.</div>
            </div>
            <a href="{{ route('suppliers.create') }}" class="vd-btn-add"><i class="bi bi-plus-circle-fill"></i> Tambah Mitra/Vendor</a>
        </div>
    </div>

    {{-- ════════ STATISTIK ════════ --}}
    <div class="row row-cols-1 row-cols-sm-2 row-cols-md-4 g-3 mb-4">
        <div class="col">
            <div class="vd-stat" style="--a:#4338ca; --b:#818cf8; --d:.05s;">
                <span class="ic"><i class="bi bi-buildings"></i></span>
                <div class="lbl">Total Mitra/Vendor</div>
                <div class="val vd-countup" data-target="{{ $totalSupplier }}">0</div>
                <div class="hint">terdaftar di master</div>
            </div>
        </div>
        <div class="col">
            <div class="vd-stat" style="--a:#e11d48; --b:#fb7185; --d:.11s;">
                <span class="ic"><i class="bi bi-cash-coin"></i></span>
                <div class="lbl">Vendor Pengeluaran</div>
                <div class="val vd-countup" data-target="{{ $supplierAktif }}">0</div>
                <div class="hint">penerima pembayaran belanja</div>
            </div>
        </div>
        <div class="col">
            <div class="vd-stat" style="--a:#047857; --b:#34d399; --d:.17s;">
                <span class="ic"><i class="bi bi-patch-check"></i></span>
                <div class="lbl">Penyedia Badan Usaha</div>
                <div class="val vd-countup" data-target="{{ $penyediaBarangJasa }}">0</div>
                <div class="hint">CV / PT / badan hukum</div>
            </div>
        </div>
        <div class="col">
            <div class="vd-stat" style="--a:#b45309; --b:#fbbf24; --d:.23s;">
                <span class="ic"><i class="bi bi-exclamation-triangle"></i></span>
                <div class="lbl">Tanpa NPWP</div>
                <div class="val vd-countup" data-target="{{ $dataBelumLengkap }}">0</div>
                <div class="hint">perlu dilengkapi datanya</div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0 bg-success text-white alert-dismissible fade show shadow-sm" style="border-radius:.9rem;">
            <i class="bi bi-check-circle me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger border-0 bg-danger text-white alert-dismissible fade show shadow-sm" style="border-radius:.9rem;">
            <i class="bi bi-exclamation-octagon me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- ════════ TABEL ════════ --}}
    <div class="vd-table-card">
        <div class="head">
            <span class="ic"><i class="bi bi-table"></i></span>
            <span class="ttl">Daftar Mitra &amp; Vendor</span>
            <span class="hint"><i class="bi bi-lightning-charge me-1"></i>pencarian &amp; halaman diproses server</span>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="tableMitra" class="table table-hover align-middle" style="width:100%">
                    <thead>
                        <tr>
                            <th class="text-center" width="5%">No</th>
                            <th width="30%">Nama Perusahaan / Mitra</th>
                            <th width="20%">NPWP</th>
                            <th width="20%">Informasi Bank</th>
                            <th class="text-center" width="10%">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        {{-- Performa: baris diisi SERVER-SIDE via DataTables AJAX (route suppliers.index-data). --}}
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
@push('script')
    <script src="{{ URL::asset('build/plugins/datatable/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/datatable/js/dataTables.bootstrap5.min.js') }}"></script>
    <script>
        $(document).ready(function() {
            var reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

            /* ── Count-up statistik (nilai final langsung saat reduced motion) ── */
            document.querySelectorAll('.vd-countup').forEach(function (el) {
                var target = parseInt(el.dataset.target || '0', 10);
                if (reducedMotion || target <= 0) { el.textContent = target.toLocaleString('id-ID'); return; }
                var dur = 1000, start = performance.now();
                function step(now) {
                    var p = Math.min((now - start) / dur, 1);
                    var eased = 1 - Math.pow(1 - p, 3);
                    el.textContent = Math.round(target * eased).toLocaleString('id-ID');
                    if (p < 1) requestAnimationFrame(step);
                }
                requestAnimationFrame(step);
            });

            // Performa: tabel SERVER-SIDE (pencarian/urut/paginate di DB).
            $('#tableMitra').DataTable({
                serverSide: true,
                processing: true,
                ajax: "{{ route('suppliers.index-data') }}",
                order: [],
                columns: [
                    { data: 0, orderable: false, searchable: false, className: 'text-center' },
                    { data: 1 },
                    { data: 2 },
                    { data: 3, orderable: false },
                    { data: 4, orderable: false, searchable: false, className: 'text-center' }
                ],
                language: {
                    url: "https://cdn.datatables.net/plug-ins/1.13.6/i18n/id.json"
                },
                drawCallback: function () {
                    /* ── baris masuk berjenjang tiap draw (search/paginate) ── */
                    if (reducedMotion) return;
                    $('#tableMitra tbody tr').each(function (i) {
                        this.style.setProperty('--i', Math.min(i, 12));
                        this.classList.add('vd-in');
                    });
                }
            });
        });
    </script>
@endpush
