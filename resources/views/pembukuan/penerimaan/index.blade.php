@extends('layouts.app')
@section('title', 'BKU Penerimaan')
@include('pembukuan.partials.styles')

@section('content')
<style>
    .bku-page{ --bku-accent:#6366f1; }
    .bku-hero{ position:relative; overflow:hidden; border-radius:20px; padding:26px 28px; color:#fff;
        background:linear-gradient(135deg,#4f46e5 0%,#7c3aed 55%,#4f46e5 100%);
        box-shadow:0 18px 40px -18px rgba(79,70,229,.6); display:flex; flex-wrap:wrap; gap:18px;
        align-items:center; justify-content:space-between; margin-bottom:18px; animation:bkuFadeUp .5s both; }
    .bku-hero::after{ content:""; position:absolute; right:-60px; top:-70px; width:260px; height:260px;
        background:radial-gradient(circle,rgba(255,255,255,.18),transparent 70%); pointer-events:none; }
    .bku-hero__eyebrow{ text-transform:uppercase; letter-spacing:.12em; font-size:11px; font-weight:700; opacity:.9; color:#fff; }
    .bku-hero__title{ font-weight:800; font-size:1.55rem; margin:.15rem 0 .4rem; color:#fff; }
    .bku-hero__rek{ display:inline-flex; align-items:center; gap:8px; background:rgba(255,255,255,.16);
        padding:6px 13px; border-radius:999px; font-size:.84rem; font-weight:600; color:#fff; }
    .bku-hero__rek i{ color:#fff; }
    .bku-hero__actions{ display:flex; flex-wrap:wrap; gap:10px; position:relative; z-index:1; }
    .bku-btn{ display:inline-flex; align-items:center; gap:7px; border:0; cursor:pointer; padding:10px 16px;
        border-radius:12px; font-weight:700; font-size:.85rem; text-decoration:none;
        transition:transform .15s ease, box-shadow .15s ease, background .15s ease; }
    .bku-btn--light{ background:#fff; color:#4f46e5; box-shadow:0 8px 18px -8px rgba(0,0,0,.4); }
    .bku-btn--light:hover{ transform:translateY(-2px); box-shadow:0 12px 22px -8px rgba(0,0,0,.5); color:#4338ca; }
    .bku-btn--ghost{ background:rgba(255,255,255,.16); color:#fff; }
    .bku-btn--ghost:hover{ background:rgba(255,255,255,.30); color:#fff; transform:translateY(-2px); }

    .bku-segment{ display:inline-flex; gap:4px; background:#eef0f6; padding:5px; border-radius:14px; margin-bottom:16px; }
    .bku-segment a{ display:inline-flex; align-items:center; gap:7px; padding:9px 18px; border-radius:10px;
        font-weight:700; font-size:.85rem; color:#5b6172; text-decoration:none; transition:all .18s ease; }
    .bku-segment a:hover{ color:#4f46e5; }
    .bku-segment a.is-active{ background:#fff; color:#4f46e5; box-shadow:0 4px 12px -4px rgba(79,70,229,.4); }

    .bku-toolbar{ display:flex; flex-wrap:wrap; gap:12px; align-items:flex-end; margin-bottom:18px;
        background:#fff; border:1px solid #eef0f5; border-radius:16px; padding:16px 18px; box-shadow:0 10px 26px -18px rgba(15,23,42,.4); }
    .bku-search{ position:relative; flex:1 1 320px; }
    .bku-search > i{ position:absolute; left:16px; top:50%; transform:translateY(-50%); color:#9aa1b2; font-size:1.05rem; transition:color .2s; }
    .bku-search:focus-within > i{ color:var(--bku-accent); }
    .bku-search input{ width:100%; border:1.5px solid #e4e7ef; border-radius:14px; padding:13px 42px 13px 44px;
        font-size:.92rem; background:#fff; outline:none; transition:border-color .2s, box-shadow .2s; }
    .bku-search input:focus{ border-color:var(--bku-accent); box-shadow:0 0 0 4px rgba(99,102,241,.16); }
    .bku-search__clear{ position:absolute; right:12px; top:50%; transform:translateY(-50%); border:0; background:#eef0f6;
        width:24px; height:24px; border-radius:50%; color:#6b7280; cursor:pointer; line-height:1; font-size:1rem;
        display:none; place-items:center; }
    .bku-search__clear:hover{ background:#e0e3ec; color:#374151; }
    .bku-search__clear:focus-visible{ outline:2px solid var(--bku-accent); outline-offset:2px; }
    .bku-field{ display:flex; flex-direction:column; }
    .bku-field label{ font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.05em; color:#8a90a2; margin-bottom:4px; }
    .bku-field input{ border:1.5px solid #e4e7ef; border-radius:12px; padding:11px 12px; font-size:.88rem; outline:none;
        background:#fff; transition:border-color .2s, box-shadow .2s; }
    .bku-field input:focus{ border-color:var(--bku-accent); box-shadow:0 0 0 4px rgba(99,102,241,.14); }

    .bku-stats{ display:grid; grid-template-columns:repeat(auto-fit,minmax(210px,1fr)); gap:14px; margin-bottom:18px; }
    .bku-stat{ display:flex; align-items:center; gap:14px; background:#fff; border-radius:16px; padding:18px;
        border:1px solid #eef0f5; box-shadow:0 10px 26px -18px rgba(15,23,42,.4); animation:bkuFadeUp .5s both;
        animation-delay:calc(var(--i)*80ms); transition:transform .18s ease, box-shadow .18s ease; }
    .bku-stat:hover{ transform:translateY(-4px); box-shadow:0 18px 34px -18px rgba(15,23,42,.45); }
    .bku-stat__icon{ width:48px; height:48px; border-radius:14px; display:grid; place-items:center; font-size:1.35rem; color:#fff; flex:none; }
    .bku-stat__body{ display:flex; flex-direction:column; min-width:0; }
    .bku-stat__label{ font-size:.78rem; font-weight:600; color:#8a90a2; }
    .bku-stat__value{ font-size:1.2rem; font-weight:800; color:#1f2535; letter-spacing:-.01em; white-space:nowrap; }
    .bku-stat--slate .bku-stat__icon{ background:linear-gradient(135deg,#64748b,#475569); }
    .bku-stat--green .bku-stat__icon{ background:linear-gradient(135deg,#10b981,#059669); }
    .bku-stat--green .bku-stat__value{ color:#059669; }
    .bku-stat--red .bku-stat__icon{ background:linear-gradient(135deg,#f87171,#ef4444); }
    .bku-stat--red .bku-stat__value{ color:#dc2626; }
    .bku-stat--indigo .bku-stat__icon{ background:linear-gradient(135deg,#6366f1,#8b5cf6); }
    .bku-stat--indigo .bku-stat__value{ color:#4f46e5; }

    .bku-card{ background:#fff; border-radius:18px; border:1px solid #eef0f5; overflow:hidden;
        box-shadow:0 14px 36px -22px rgba(15,23,42,.4); animation:bkuFadeUp .5s .1s both; }
    .bku-card__head{ display:flex; align-items:center; justify-content:space-between; padding:16px 20px; border-bottom:1px solid #f0f1f6; }
    .bku-card__title{ font-weight:800; color:#1f2535; display:inline-flex; align-items:center; gap:8px; }
    .bku-card__title i{ color:var(--bku-accent); }
    .bku-chip-count{ font-size:.78rem; font-weight:700; color:#6b7280; background:#f1f2f7; padding:5px 12px; border-radius:999px; }

    .bku-table-wrap{ overflow-x:auto; }
    .bku-table{ width:100%; border-collapse:separate; border-spacing:0; }
    .bku-table thead th{ text-align:left; font-size:.72rem; font-weight:700; text-transform:uppercase; letter-spacing:.06em;
        color:#8a90a2; padding:12px 16px; background:#fafbfc; border-bottom:1px solid #eef0f5; white-space:nowrap; }
    .bku-table th.ta-end{ text-align:right; } .bku-table th.ta-center{ text-align:center; }
    .bku-table td{ padding:11px 16px; border-bottom:1px solid #f3f4f8; vertical-align:middle; }
    .bku-table td.ta-end{ text-align:right; } .bku-table td.ta-center{ text-align:center; }
    .bku-table tbody tr:last-child td{ border-bottom:0; }
    .bku-row{ animation:bkuFadeUp .4s both; animation-delay:calc(min(var(--r),22)*18ms); }
    .bku-row:hover td{ background:#f7f8ff; }
    .bku-row-saldoawal td{ background:linear-gradient(90deg,#eef2ff,#faf5ff); font-style:italic; color:#5b6172; font-weight:600; }
    .bku-row-saldoawal i{ color:#a78bfa; margin-right:4px; }

    .bku-date{ display:inline-flex; flex-direction:column; line-height:1.12; }
    .bku-date__d{ font-size:1.05rem; font-weight:800; color:#1f2535; }
    .bku-date__m{ font-size:.7rem; font-weight:600; color:#9aa1b2; text-transform:uppercase; letter-spacing:.04em; }
    .bku-pill{ display:inline-flex; align-items:center; gap:6px; max-width:300px; font-size:.8rem; font-weight:600; }
    .bku-pill--akun{ color:#3730a3; }
    .bku-pill__code{ background:#e0e7ff; color:#4338ca; font-weight:800; font-size:.72rem; padding:2px 7px; border-radius:6px; font-variant-numeric:tabular-nums; }
    .bku-pill--jenis{ background:#f1f5f9; color:#475569; border:1px solid #e2e8f0; padding:4px 10px; border-radius:999px; }
    .bku-muted{ color:#c2c7d4; }
    .bku-uraian{ color:#3a4051; font-size:.88rem; }
    .bku-tag{ display:inline-flex; align-items:center; gap:4px; font-size:.68rem; font-weight:700; padding:3px 8px; border-radius:999px; margin-left:6px; white-space:nowrap; }
    .bku-tag--ok{ background:#dcfce7; color:#15803d; } .bku-tag--warn{ background:#fef3c7; color:#b45309; } .bku-tag--info{ background:#e0f2fe; color:#0369a1; }
    .bku-num{ font-variant-numeric:tabular-nums; font-weight:700; font-size:.9rem; white-space:nowrap; }
    .bku-num--in{ color:#059669; } .bku-num--out{ color:#dc2626; } .bku-num--saldo{ color:#1f2535; }
    .bku-num--in::before, .bku-num--out::before{ content:"Rp "; opacity:.5; font-weight:600; font-size:.78em; }
    .bku-num--in:empty::before, .bku-num--out:empty::before{ content:""; }
    .bku-actions{ display:inline-flex; gap:6px; justify-content:center; }
    .bku-actions form{ display:inline; margin:0; }
    .bku-ico{ display:inline-grid; place-items:center; width:30px; height:30px; border-radius:9px; border:1px solid #e4e7ef;
        background:#fff; color:#5b6172; cursor:pointer; transition:all .15s ease; text-decoration:none; }
    .bku-ico:hover{ background:var(--bku-accent); color:#fff; border-color:var(--bku-accent); transform:translateY(-1px); }
    .bku-ico--danger:hover{ background:#ef4444; border-color:#ef4444; }
    .bku-empty{ text-align:center; padding:46px 16px; color:#9aa1b2; }
    .bku-empty i{ font-size:2.4rem; opacity:.4; } .bku-empty p{ margin:12px 0 0; font-weight:600; }
    .bku-link-mini{ font-size:.72rem; font-weight:700; text-decoration:none; color:var(--bku-accent); margin-left:8px; }
    #bku-content{ transition:opacity .2s; } #bku-content.is-loading{ opacity:.45; pointer-events:none; }

    .bku-modal{ border:0; border-radius:18px; overflow:hidden; box-shadow:0 30px 60px -20px rgba(15,23,42,.5); }
    .bku-modal__head{ background:linear-gradient(135deg,#4f46e5,#7c3aed); color:#fff; border:0; padding:20px 22px; align-items:flex-start; }
    .bku-modal__head .modal-title{ font-weight:800; }
    .bku-modal__sub{ margin:.3rem 0 0; font-size:.78rem; opacity:.92; }
    .bku-modal .modal-body{ padding:22px; }
    .bku-flabel{ font-size:.74rem; font-weight:700; text-transform:uppercase; letter-spacing:.04em; color:#7a808f; margin-bottom:5px; display:block; }
    .bku-modal .form-control, .bku-modal .form-select{ border:1.5px solid #e4e7ef; border-radius:11px; padding:10px 12px; font-size:.9rem; }
    .bku-modal .form-control:focus, .bku-modal .form-select:focus{ border-color:var(--bku-accent); box-shadow:0 0 0 4px rgba(99,102,241,.14); }
    .bku-modal__foot{ border:0; padding:0 22px 22px; }
    .bku-btn-primary{ background:linear-gradient(135deg,#4f46e5,#7c3aed); color:#fff; font-weight:700; border:0; padding:10px 20px; border-radius:11px; }
    .bku-btn-primary:hover{ color:#fff; filter:brightness(1.08); }

    @keyframes bkuFadeUp{ from{ opacity:0; transform:translateY(12px); } to{ opacity:1; transform:none; } }
    @media (max-width:640px){ .bku-hero{ padding:20px; } .bku-hero__title{ font-size:1.3rem; } }
    @media (prefers-reduced-motion:reduce){ .bku-hero,.bku-stat,.bku-card,.bku-row{ animation:none !important; } }
</style>

<div class="container-fluid bku-page">

    <div class="bku-segment">
        @hasanyrole('Bendahara Pengeluaran|Super Admin')
            <a href="{{ route('pembukuan.pengeluaran.index') }}"><i class="bi bi-arrow-up-right-circle"></i> Pengeluaran</a>
        @endhasanyrole
        @hasanyrole('Bendahara Penerimaan|Super Admin')
            <a href="{{ route('pembukuan.penerimaan.index') }}" class="is-active"><i class="bi bi-arrow-down-left-circle"></i> Penerimaan</a>
        @endhasanyrole
    </div>

    @if(session('success'))<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif
    @if(session('error'))<div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif

    <div class="bku-hero">
        <div class="bku-hero__info">
            <div class="bku-hero__eyebrow">Buku Kas Umum · Bendahara Penerimaan</div>
            <h3 class="bku-hero__title">{{ $buku['nama_buku'] }}</h3>
            @if($rekening)
                <span class="bku-hero__rek"><i class="bi bi-bank2"></i> {{ $rekening->nama_bank }} · {{ $rekening->nomor_rekening }}</span>
            @endif
        </div>
        <div class="bku-hero__actions">
            <button type="button" class="bku-btn bku-btn--light" data-bs-toggle="modal" data-bs-target="#modalMutasi"><i class="bi bi-plus-lg"></i> Catat Mutasi</button>
            <a href="{{ route('pembukuan.klasifikasi.index') }}" class="bku-btn bku-btn--ghost"><i class="bi bi-bank"></i> Impor Koran</a>
            <a href="{{ route('pembukuan.penerimaan.pdf', request()->query()) }}" target="_blank" class="bku-btn bku-btn--ghost" title="Ekspor PDF"><i class="bi bi-filetype-pdf"></i></a>
            <a href="{{ route('pembukuan.penerimaan.excel', request()->query()) }}" class="bku-btn bku-btn--ghost" title="Ekspor Excel"><i class="bi bi-filetype-xlsx"></i></a>
        </div>
    </div>

    <div class="bku-toolbar">
        <div class="bku-search">
            <i class="bi bi-search"></i>
            <input type="search" id="bku-search" value="{{ $filters['search'] ?? '' }}" placeholder="Cari uraian, kode akun, jenis, atau nominal…" autocomplete="off">
            <button type="button" class="bku-search__clear" aria-label="Hapus pencarian">&times;</button>
        </div>
        <div class="bku-field"><label>Dari</label><input type="date" id="bku-start" value="{{ $filters['start_date'] ?? '' }}"></div>
        <div class="bku-field"><label>Sampai</label><input type="date" id="bku-end" value="{{ $filters['end_date'] ?? '' }}"></div>
    </div>

    <div id="bku-content">
        @include('pembukuan.penerimaan._content')
    </div>
</div>

@include('pembukuan.penerimaan.partials.input-manual')
@endsection

@push('script')
<script>
    (function () {
        'use strict';
        var URL = '{{ route('pembukuan.penerimaan.index') }}';
        var content = document.getElementById('bku-content');
        var search = document.getElementById('bku-search');
        var start = document.getElementById('bku-start');
        var end = document.getElementById('bku-end');
        var clearBtn = document.querySelector('.bku-search__clear');
        if (!content) return;

        function countUp(el) {
            var target = parseInt(el.getAttribute('data-value') || '0', 10);
            if (isNaN(target)) return;
            var dur = 850, t0 = null;
            function fmt(n) { return 'Rp ' + Math.round(n).toLocaleString('id-ID'); }
            function step(ts) {
                if (!t0) t0 = ts;
                var p = Math.min((ts - t0) / dur, 1), e = 1 - Math.pow(1 - p, 3);
                el.textContent = fmt(target * e);
                if (p < 1) requestAnimationFrame(step);
            }
            requestAnimationFrame(step);
        }

        function initContent() { content.querySelectorAll('.bku-stat__value[data-value]').forEach(countUp); }

        function qs() {
            var p = new URLSearchParams();
            if (search && search.value.trim()) p.set('search', search.value.trim());
            if (start && start.value) p.set('start_date', start.value);
            if (end && end.value) p.set('end_date', end.value);
            p.set('partial', '1');
            return p.toString();
        }

        function reload() {
            content.classList.add('is-loading');
            fetch(URL + '?' + qs(), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (r) { if (!r.ok) throw new Error('http ' + r.status); return r.text(); })
                .then(function (html) { content.innerHTML = html; initContent(); })
                .catch(function () {})
                .then(function () { content.classList.remove('is-loading'); });
        }

        function toggleClear() { if (clearBtn) clearBtn.style.display = (search && search.value) ? 'grid' : 'none'; }

        var timer;
        if (search) search.addEventListener('input', function () { toggleClear(); clearTimeout(timer); timer = setTimeout(reload, 300); });
        if (start) start.addEventListener('change', reload);
        if (end) end.addEventListener('change', reload);
        if (clearBtn) clearBtn.addEventListener('click', function () { clearTimeout(timer); search.value = ''; toggleClear(); reload(); search.focus(); });

        toggleClear();
        initContent();

        @if($errors->any())
        window.addEventListener('load', function () {
            var m = document.getElementById('modalMutasi');
            if (m && window.bootstrap && bootstrap.Modal) bootstrap.Modal.getOrCreateInstance(m).show();
        });
        @endif
    })();
</script>
@endpush
