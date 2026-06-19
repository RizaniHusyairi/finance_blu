@extends('layouts.app')
@section('title', 'Klasifikasi Penerimaan')
@include('pembukuan.partials.styles')

@section('content')
<style>
    .klas-page{ --klas-accent:#0891b2; }
    .klas-hero{ position:relative; overflow:hidden; border-radius:20px; padding:26px 28px; color:#fff;
        background:linear-gradient(135deg,#0e7490 0%,#0891b2 52%,#06b6d4 100%);
        box-shadow:0 18px 40px -18px rgba(8,145,178,.6); display:flex; flex-wrap:wrap; gap:18px;
        align-items:center; justify-content:space-between; margin-bottom:18px; animation:klasFadeUp .5s both; }
    .klas-hero::after{ content:""; position:absolute; right:-70px; top:-80px; width:280px; height:280px;
        background:radial-gradient(circle,rgba(255,255,255,.18),transparent 70%); pointer-events:none; }
    .klas-hero__eyebrow{ text-transform:uppercase; letter-spacing:.12em; font-size:11px; font-weight:700; opacity:.9; color:#fff; }
    .klas-hero__title{ font-weight:800; font-size:1.5rem; margin:.15rem 0 .4rem; color:#fff; }
    .klas-hero__rek{ display:inline-flex; align-items:center; gap:8px; background:rgba(255,255,255,.18);
        padding:6px 13px; border-radius:999px; font-size:.83rem; font-weight:600; color:#fff; }
    .klas-hero__rek i{ color:#fff; }
    .klas-hero__actions{ display:flex; flex-wrap:wrap; gap:10px; position:relative; z-index:1; }
    .klas-btn{ display:inline-flex; align-items:center; gap:7px; border:0; cursor:pointer; padding:10px 16px;
        border-radius:12px; font-weight:700; font-size:.85rem; text-decoration:none;
        transition:transform .15s ease, box-shadow .15s ease, background .15s ease; }
    .klas-btn--light{ background:#fff; color:#0e7490; box-shadow:0 8px 18px -8px rgba(0,0,0,.4); }
    .klas-btn--light:hover{ transform:translateY(-2px); box-shadow:0 12px 22px -8px rgba(0,0,0,.5); color:#155e75; }
    .klas-btn--ghost{ background:rgba(255,255,255,.16); color:#fff; }
    .klas-btn--ghost:hover{ background:rgba(255,255,255,.30); color:#fff; transform:translateY(-2px); }
    .klas-btn:disabled{ opacity:.7; cursor:default; transform:none; }

    .klas-toolbar{ display:flex; flex-wrap:wrap; gap:12px; align-items:flex-end; margin-bottom:18px;
        background:#fff; border:1px solid #eef0f5; border-radius:16px; padding:16px 18px; box-shadow:0 10px 26px -18px rgba(15,23,42,.4); }
    .klas-search{ position:relative; flex:1 1 300px; }
    .klas-search > i{ position:absolute; left:16px; top:50%; transform:translateY(-50%); color:#9aa1b2; font-size:1.05rem; transition:color .2s; }
    .klas-search:focus-within > i{ color:var(--klas-accent); }
    .klas-search input{ width:100%; border:1.5px solid #e4e7ef; border-radius:14px; padding:13px 42px 13px 44px;
        font-size:.92rem; background:#fff; outline:none; transition:border-color .2s, box-shadow .2s; }
    .klas-search input:focus{ border-color:var(--klas-accent); box-shadow:0 0 0 4px rgba(8,145,178,.16); }
    .klas-search__clear{ position:absolute; right:12px; top:50%; transform:translateY(-50%); border:0; background:#eef0f6;
        width:24px; height:24px; border-radius:50%; color:#6b7280; cursor:pointer; line-height:1; font-size:1rem; display:none; place-items:center; }
    .klas-search__clear:hover{ background:#e0e3ec; color:#374151; }
    .klas-search__clear:focus-visible{ outline:2px solid var(--klas-accent); outline-offset:2px; }
    .klas-field{ display:flex; flex-direction:column; }
    .klas-field--imp{ flex:1 1 240px; }
    .klas-field label{ font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.05em; color:#8a90a2; margin-bottom:4px; }
    .klas-field input, .klas-field select{ border:1.5px solid #e4e7ef; border-radius:12px; padding:11px 12px; font-size:.86rem;
        outline:none; background:#fff; transition:border-color .2s, box-shadow .2s; }
    .klas-field input:focus, .klas-field select:focus{ border-color:var(--klas-accent); box-shadow:0 0 0 4px rgba(8,145,178,.14); }

    .klas-stats{ display:grid; grid-template-columns:repeat(auto-fit,minmax(190px,1fr)); gap:14px; margin-bottom:18px; }
    .klas-stat{ display:flex; align-items:center; gap:14px; background:#fff; border-radius:16px; padding:16px 18px;
        border:1.5px solid #eef0f5; box-shadow:0 10px 26px -18px rgba(15,23,42,.4); cursor:pointer; text-align:left;
        animation:klasFadeUp .5s both; animation-delay:calc(var(--i)*70ms); transition:transform .18s ease, box-shadow .18s ease, border-color .18s ease; }
    .klas-stat:hover{ transform:translateY(-4px); box-shadow:0 18px 34px -18px rgba(15,23,42,.45); }
    .klas-stat.is-active{ border-color:var(--klas-accent); box-shadow:0 0 0 3px rgba(8,145,178,.15), 0 14px 30px -16px rgba(8,145,178,.5); }
    .klas-stat:focus-visible{ outline:none; border-color:var(--klas-accent); box-shadow:0 0 0 3px rgba(8,145,178,.3); }
    .klas-stat__icon{ width:46px; height:46px; border-radius:13px; display:grid; place-items:center; font-size:1.3rem; color:#fff; flex:none; }
    .klas-stat__body{ display:flex; flex-direction:column; min-width:0; }
    .klas-stat__num{ font-size:1.35rem; font-weight:800; color:#1f2535; letter-spacing:-.01em; line-height:1.1; }
    .klas-stat__label{ font-size:.74rem; font-weight:600; color:#8a90a2; }
    .klas-stat--total .klas-stat__icon{ background:linear-gradient(135deg,#0891b2,#06b6d4); }
    .klas-stat--pending .klas-stat__icon{ background:linear-gradient(135deg,#94a3b8,#64748b); }
    .klas-stat--posted .klas-stat__icon{ background:linear-gradient(135deg,#38bdf8,#0ea5e9); }
    .klas-stat--ok .klas-stat__icon{ background:linear-gradient(135deg,#34d399,#10b981); }

    .klas-card{ background:#fff; border-radius:18px; border:1px solid #eef0f5; overflow:hidden;
        box-shadow:0 14px 36px -22px rgba(15,23,42,.4); animation:klasFadeUp .5s .1s both; }
    .klas-card__head{ display:flex; align-items:center; justify-content:space-between; padding:16px 20px; border-bottom:1px solid #f0f1f6; }
    .klas-card__title{ font-weight:800; color:#1f2535; display:inline-flex; align-items:center; gap:8px; }
    .klas-card__title i{ color:var(--klas-accent); }
    .klas-chip-count{ font-size:.78rem; font-weight:700; color:#0e7490; background:#cffafe; padding:5px 12px; border-radius:999px; }

    .klas-table-wrap{ overflow-x:auto; }
    .klas-table{ width:100%; border-collapse:separate; border-spacing:0; }
    .klas-table thead th{ text-align:left; font-size:.72rem; font-weight:700; text-transform:uppercase; letter-spacing:.06em;
        color:#8a90a2; padding:12px 16px; background:#fafbfc; border-bottom:1px solid #eef0f5; white-space:nowrap; }
    .klas-table th.ta-end{ text-align:right; } .klas-table th.ta-center{ text-align:center; }
    .klas-table td{ padding:10px 16px; border-bottom:1px solid #f3f4f8; vertical-align:middle; }
    .klas-table td.ta-end{ text-align:right; } .klas-table td.ta-center{ text-align:center; }
    .klas-table tbody tr:last-child td{ border-bottom:0; }
    .klas-row{ animation:klasFadeUp .4s both; animation-delay:calc(min(var(--r),22)*16ms); }
    .klas-row:hover td{ background:#f3fbfd; }

    .klas-date{ display:inline-flex; flex-direction:column; line-height:1.12; }
    .klas-date__d{ font-size:1.05rem; font-weight:800; color:#1f2535; }
    .klas-date__m{ font-size:.7rem; font-weight:600; color:#9aa1b2; text-transform:uppercase; letter-spacing:.04em; }
    .klas-desc{ color:#3a4051; font-size:.85rem; }
    .klas-muted{ color:#b0b6c4; font-size:.8rem; font-style:italic; }
    .klas-num{ font-variant-numeric:tabular-nums; font-weight:700; font-size:.88rem; white-space:nowrap; }
    .klas-num--in{ color:#059669; } .klas-num--out{ color:#dc2626; }
    .klas-num--in::before, .klas-num--out::before{ content:"Rp "; opacity:.5; font-weight:600; font-size:.78em; }
    .klas-num--in:empty::before, .klas-num--out:empty::before{ content:""; }

    .klas-akun{ position:relative; max-width:340px; }
    .klas-select{ width:100%; appearance:none; -webkit-appearance:none; border:1.5px solid #e2e8f0; border-radius:10px;
        padding:8px 44px 8px 12px; font-size:.81rem; font-weight:600; color:#334155; background-color:#fff; cursor:pointer;
        background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='none' stroke='%2364748b' stroke-width='2.4' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='m3 5 4 4 4-4'/%3E%3C/svg%3E");
        background-repeat:no-repeat; background-position:right 12px center; transition:border-color .18s, box-shadow .18s, background-color .35s; outline:none; }
    .klas-select:hover{ border-color:#cbd5e1; }
    .klas-select:focus{ border-color:var(--klas-accent); box-shadow:0 0 0 4px rgba(8,145,178,.14); }
    .klas-akun__ok{ position:absolute; right:30px; top:50%; transform:translateY(-50%) scale(.4); color:#10b981; opacity:0; transition:transform .25s, opacity .25s; pointer-events:none; }
    .klas-akun.is-saving .klas-select{ opacity:.55; }
    .klas-akun.is-saved .klas-select{ border-color:#34d399; background-color:#f0fdf4; }
    .klas-akun.is-saved .klas-akun__ok{ opacity:1; transform:translateY(-50%) scale(1); }

    .klas-status{ display:inline-flex; align-items:center; gap:5px; font-size:.72rem; font-weight:700; padding:5px 11px; border-radius:999px; white-space:nowrap; }
    .klas-status--ok{ background:#dcfce7; color:#15803d; }
    .klas-status--posted{ background:#e0f2fe; color:#0369a1; }
    .klas-status--pending{ background:#f1f5f9; color:#64748b; }
    .klas-status--ok i{ animation:klasPop .4s both; }

    .klas-empty{ text-align:center; padding:48px 16px; color:#9aa1b2; }
    .klas-empty i{ font-size:2.6rem; opacity:.4; } .klas-empty p{ margin:14px 0 4px; font-weight:700; color:#6b7280; } .klas-empty span{ font-size:.83rem; }
    .klas-pagination{ padding:12px 18px; border-top:1px solid #f0f1f6; }
    .klas-pagination .pagination{ margin:0; justify-content:flex-end; }

    #klasifikasi-content{ transition:opacity .2s; } #klasifikasi-content.is-loading{ opacity:.45; pointer-events:none; }
    #klasifikasi-flash:empty{ display:none; }
    .klas-alert{ display:flex; align-items:center; justify-content:space-between; gap:12px; padding:12px 16px; border-radius:13px;
        font-size:.86rem; font-weight:600; margin-bottom:14px; animation:klasFadeUp .3s both; }
    .klas-alert--ok{ background:#ecfdf5; color:#047857; border:1px solid #a7f3d0; }
    .klas-alert--err{ background:#fef2f2; color:#b91c1c; border:1px solid #fecaca; }
    .klas-alert button{ border:0; background:transparent; font-size:1.2rem; line-height:1; color:inherit; cursor:pointer; opacity:.6; }
    .klas-alert button:hover{ opacity:1; }

    .klas-modal{ border:0; border-radius:18px; overflow:hidden; box-shadow:0 30px 60px -20px rgba(15,23,42,.5); }
    .klas-modal__head{ background:linear-gradient(135deg,#0e7490,#06b6d4); color:#fff; border:0; padding:20px 22px; align-items:flex-start; }
    .klas-modal__head .modal-title{ font-weight:800; }
    .klas-modal__sub{ margin:.3rem 0 0; font-size:.78rem; opacity:.92; }
    .klas-modal .modal-body{ padding:22px; }
    .klas-upload{ border:2px dashed #cbd5e1; border-radius:14px; padding:26px 18px; text-align:center; background:#f8fafc; transition:border-color .2s, background .2s; }
    .klas-upload:hover{ border-color:var(--klas-accent); background:#ecfeff; }
    .klas-upload > i{ font-size:2.4rem; color:var(--klas-accent); display:block; margin-bottom:8px; }
    .klas-upload span{ display:block; color:#64748b; font-size:.82rem; font-weight:600; margin-bottom:10px; }
    .klas-upload input[type=file]{ display:block; margin:0 auto; max-width:280px; font-size:.82rem; }
    .klas-modal__foot{ border:0; padding:0 22px 22px; }
    .klas-btn-primary{ background:linear-gradient(135deg,#0e7490,#06b6d4); color:#fff; font-weight:700; border:0; padding:10px 20px; border-radius:11px; }
    .klas-btn-primary:hover{ color:#fff; filter:brightness(1.08); }

    @keyframes klasFadeUp{ from{ opacity:0; transform:translateY(12px); } to{ opacity:1; transform:none; } }
    @keyframes klasPop{ 0%{ transform:scale(0); } 70%{ transform:scale(1.3); } 100%{ transform:scale(1); } }
    @media (max-width:640px){ .klas-hero{ padding:20px; } .klas-hero__title{ font-size:1.25rem; } }
    @media (prefers-reduced-motion:reduce){ .klas-hero,.klas-stat,.klas-card,.klas-row,.klas-alert{ animation:none !important; } }
</style>

<div class="container-fluid klas-page">

    @if(session('success'))<div class="klas-alert klas-alert--ok"><span><i class="bi bi-check-circle me-1"></i>{{ session('success') }}</span><button type="button" onclick="this.closest('.klas-alert').remove()">&times;</button></div>@endif
    @if(session('error'))<div class="klas-alert klas-alert--err"><span><i class="bi bi-exclamation-triangle me-1"></i>{{ session('error') }}</span><button type="button" onclick="this.closest('.klas-alert').remove()">&times;</button></div>@endif

    <div class="klas-hero">
        <div class="klas-hero__info">
            <div class="klas-hero__eyebrow">Pembukuan · Rekening Koran</div>
            <h3 class="klas-hero__title">Klasifikasi Penerimaan</h3>
            @if($rekening)
                <span class="klas-hero__rek"><i class="bi bi-bank2"></i> {{ $rekening->nama_bank }} · {{ $rekening->nomor_rekening }}</span>
            @else
                <span class="klas-hero__rek"><i class="bi bi-exclamation-circle"></i> Belum ada rekening Penerimaan — atur di Setup</span>
            @endif
        </div>
        <div class="klas-hero__actions">
            <button type="button" class="klas-btn klas-btn--light" data-bs-toggle="modal" data-bs-target="#modalImpor"><i class="bi bi-cloud-upload"></i> Impor Rekening Koran</button>
            @if($rekening)
                <button type="button" id="btn-posting-massal" class="klas-btn klas-btn--light"><i class="bi bi-magic"></i> Klasifikasi &amp; Posting Massal</button>
            @endif
            <a href="{{ route('pembukuan.penerimaan.index') }}" class="klas-btn klas-btn--ghost"><i class="bi bi-arrow-down-left-circle"></i> BKU Penerimaan</a>
        </div>
    </div>

    <form id="klasifikasi-filter" method="GET" class="klas-toolbar">
        <input type="hidden" name="status" value="{{ $filters['status'] ?? '' }}">
        <div class="klas-search">
            <i class="bi bi-search"></i>
            <input type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Cari deskripsi, nama, kode VA, nomor referensi, atau nominal…" autocomplete="off">
            <button type="button" class="klas-search__clear" aria-label="Hapus pencarian">&times;</button>
        </div>
        <div class="klas-field klas-field--imp">
            <label>Impor (berkas)</label>
            <select name="import_id">
                <option value="">Semua impor</option>
                @foreach($importOptions as $imp)
                    <option value="{{ $imp->id }}" @selected((string)($filters['import_id'] ?? '') === (string)$imp->id)>{{ \Illuminate\Support\Str::limit($imp->nama_file_asli, 22) }} · {{ $imp->detail_mutasi_banks_count }} baris</option>
                @endforeach
            </select>
        </div>
        <div class="klas-field"><label>Dari</label><input type="date" name="start_date" value="{{ $filters['start_date'] ?? '' }}"></div>
        <div class="klas-field"><label>Sampai</label><input type="date" name="end_date" value="{{ $filters['end_date'] ?? '' }}"></div>
    </form>

    <div id="klasifikasi-flash"></div>

    <div id="klasifikasi-content">
        @include('pembukuan.klasifikasi._content')
    </div>
</div>

{{-- Modal impor rekening koran --}}
<div class="modal fade" id="modalImpor" tabindex="-1" aria-labelledby="modalImporLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content klas-modal">
            <form method="POST" action="{{ route('pembukuan.klasifikasi.import') }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-header klas-modal__head">
                    <div>
                        <h5 class="modal-title" id="modalImporLabel"><i class="bi bi-cloud-upload me-1"></i> Impor Rekening Koran</h5>
                        <p class="klas-modal__sub">Format CMS BTN (.xls / .xlsx). Rekening dikenali otomatis dari nomor akun pada berkas.</p>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <label class="klas-upload">
                        <i class="bi bi-filetype-xls"></i>
                        <span>Pilih berkas rekening koran (.xls / .xlsx)</span>
                        <input type="file" name="file_koran" accept=".xls,.xlsx" required>
                    </label>
                    <p class="text-muted small mt-3 mb-0">Tiap baris mutasi menjadi data penyanding rekonsiliasi. Setelah impor, jalankan
                        <b>Klasifikasi &amp; Posting Massal</b>; baris yang cocok dengan tagihan otomatis tertaut. Baris yang sudah pernah diimpor (duplikat) dilewati.</p>
                </div>
                <div class="modal-footer klas-modal__foot">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn klas-btn-primary"><i class="bi bi-upload me-1"></i> Unggah &amp; Impor</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('script')
<script>
    (function () {
        'use strict';
        var TOKEN = '{{ csrf_token() }}';
        var REK = '{{ $rekening->id ?? '' }}';
        var URL_INDEX = '{{ route('pembukuan.klasifikasi.index') }}';
        var URL_POST = '{{ route('pembukuan.klasifikasi.post-batch') }}';
        var URL_AKUN = '{{ url('pembukuan/klasifikasi-penerimaan') }}';

        var form = document.getElementById('klasifikasi-filter');
        var box = document.getElementById('klasifikasi-content');
        var flash = document.getElementById('klasifikasi-flash');
        var statusInput = form ? form.querySelector('[name=status]') : null;
        var clearBtn = form ? form.querySelector('.klas-search__clear') : null;
        var searchInput = form ? form.querySelector('[name=search]') : null;
        if (!form || !box) return;

        function params() {
            var p = new URLSearchParams();
            new FormData(form).forEach(function (v, k) { if (v) p.set(k, v); });
            return p;
        }
        function notify(msg, ok) {
            flash.innerHTML = '<div class="klas-alert klas-alert--' + (ok ? 'ok' : 'err') + '"><span>' + msg + '</span><button type="button" aria-label="Tutup">&times;</button></div>';
        }
        function swap(url) {
            box.classList.add('is-loading');
            return fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (r) { if (!r.ok) throw new Error('http ' + r.status); return r.text(); })
                .then(function (html) { box.innerHTML = html; })
                .catch(function () { notify('Gagal memuat data — coba muat ulang halaman.', false); })
                .then(function () { box.classList.remove('is-loading'); });
        }
        function reload() { var p = params(); p.set('partial', '1'); return swap(URL_INDEX + '?' + p.toString()); }

        function action(url, btn, confirmMsg) {
            if (confirmMsg && !window.confirm(confirmMsg)) return;
            var label = btn.innerHTML; btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Memproses…';
            var body = params(); body.set('rekening_bank_id', REK);
            fetch(url, { method: 'POST', headers: { 'X-CSRF-TOKEN': TOKEN, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }, body: body })
                .then(function (r) { return r.json().then(function (j) { return { ok: r.ok, j: j }; }); })
                .then(function (res) { notify(res.j.message || (res.ok ? 'Selesai.' : 'Gagal.'), res.ok && res.j.success !== false); return reload(); })
                .catch(function () { notify('Terjadi kesalahan jaringan.', false); })
                .then(function () { btn.disabled = false; btn.innerHTML = label; });
        }

        function toggleClear() { if (clearBtn) clearBtn.style.display = (searchInput && searchInput.value) ? 'grid' : 'none'; }

        // Filter auto-jalan (impor/tanggal) + pencarian debounce — tanpa tombol & tanpa reload halaman.
        form.addEventListener('submit', function (e) { e.preventDefault(); reload(); });
        form.addEventListener('change', function (e) {
            if (e.target.matches('[name=import_id], input[type=date]')) reload();
        });
        var st;
        if (searchInput) searchInput.addEventListener('input', function () { toggleClear(); clearTimeout(st); st = setTimeout(reload, 300); });
        if (clearBtn) clearBtn.addEventListener('click', function () { clearTimeout(st); searchInput.value = ''; toggleClear(); reload(); searchInput.focus(); });
        toggleClear();

        // Posting massal (tombol di hero).
        var bPost = document.getElementById('btn-posting-massal');
        if (bPost) bPost.addEventListener('click', function () { action(URL_POST, bPost, 'Klasifikasi otomatis & posting semua baris ke BKU Penerimaan?'); });

        // Tutup notifikasi AJAX.
        flash.addEventListener('click', function (e) { if (e.target.closest('button')) flash.innerHTML = ''; });

        // Delegasi di dalam konten (tetap hidup setelah konten diganti):
        box.addEventListener('click', function (e) {
            var stat = e.target.closest('.klas-stat');
            if (stat) { if (statusInput) statusInput.value = stat.getAttribute('data-status') || ''; reload(); return; }
            var a = e.target.closest('.pagination a');
            if (a) { e.preventDefault(); var u = new URL(a.href); u.searchParams.set('partial', '1'); swap(u.toString()); }
        });
        box.addEventListener('change', function (e) {
            var sel = e.target.closest('select.js-akun');
            if (!sel) return;
            var wrap = sel.closest('.klas-akun');
            if (wrap) { wrap.classList.add('is-saving'); wrap.classList.remove('is-saved'); }
            var b = new URLSearchParams(); b.set('akun_pendapatan_id', sel.value);
            fetch(URL_AKUN + '/' + sel.dataset.id + '/akun', { method: 'POST', headers: { 'X-CSRF-TOKEN': TOKEN, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }, body: b })
                .then(function (r) { return r.json().then(function (j) { return { ok: r.ok, j: j }; }); })
                .then(function (res) {
                    var ok = res.ok && res.j.success !== false;
                    if (wrap) {
                        wrap.classList.remove('is-saving');
                        if (ok) { wrap.classList.add('is-saved'); setTimeout(function () { wrap.classList.remove('is-saved'); }, 1500); }
                    }
                    notify(res.j.message || (ok ? 'Akun disimpan.' : 'Gagal menyimpan akun.'), ok);
                })
                .catch(function () { if (wrap) wrap.classList.remove('is-saving'); notify('Gagal menyimpan akun.', false); });
        });

        @if($errors->any())
        window.addEventListener('load', function () {
            var m = document.getElementById('modalImpor');
            if (m && window.bootstrap && bootstrap.Modal) bootstrap.Modal.getOrCreateInstance(m).show();
        });
        @endif
    })();
</script>
@endpush
