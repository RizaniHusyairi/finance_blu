@extends('layouts.app')
@section('title', 'Manajemen Multi-SPP Perjaldin')

@push('css')
<style>
    body[data-bs-theme="blue-theme"] .main-content { background: #f6f7fb; }

    /* ============ ANIMATIONS ============ */
    @keyframes mspHeroIn { from { opacity: 0; transform: translateY(-14px); } to { opacity: 1; transform: none; } }
    @keyframes mspIn      { from { opacity: 0; transform: translateY(16px); } to { opacity: 1; transform: none; } }
    @keyframes mspPulse   { 0%,100% { box-shadow: 0 0 0 0 rgba(255,255,255,.45); } 50% { box-shadow: 0 0 0 8px rgba(255,255,255,0); } }
    @keyframes mspBar     { from { width: 0; } }

    /* ============ HERO ============ */
    .msp-hero {
        position: relative; overflow: hidden;
        border-radius: 1.25rem;
        padding: 1.65rem 2rem;
        color: #fff;
        margin-bottom: 1.25rem;
        box-shadow: 0 16px 34px rgba(15,23,42,.18);
        animation: mspHeroIn .55s cubic-bezier(.22,1,.36,1) both;
    }
    .msp-hero::before, .msp-hero::after { content: ''; position: absolute; border-radius: 50%; }
    .msp-hero::before { right: -90px; top: -90px; width: 280px; height: 280px; background: rgba(255,255,255,.10); }
    .msp-hero::after  { right: 70px; bottom: -80px; width: 190px; height: 190px; background: rgba(255,255,255,.07); }
    .msp-hero > * { position: relative; z-index: 1; }
    .hero-draft     { background: linear-gradient(135deg, #475569 0%, #334155 100%); }
    .hero-pending   { background: linear-gradient(135deg, #6366f1 0%, #4f46e5 50%, #2563eb 100%); }
    .hero-approved  { background: linear-gradient(135deg, #10b981 0%, #059669 50%, #047857 100%); }
    .hero-rejected  { background: linear-gradient(135deg, #f43f5e 0%, #e11d48 50%, #be123c 100%); }
    .hero-warning   { background: linear-gradient(135deg, #f59e0b 0%, #d97706 50%, #b45309 100%); }
    .hero-info      { background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 50%, #0369a1 100%); }

    .plane-illust { position: absolute; right: 1.5rem; top: 50%; transform: translateY(-50%) rotate(-15deg); font-size: 8rem; opacity: .12; z-index: 0; }
    .hero-doc-badge {
        display: inline-flex; align-items: center;
        background: rgba(255,255,255,.92); color: #0f172a;
        font-weight: 800; font-size: .8rem;
        padding: .4rem .9rem; border-radius: 999px;
    }
    .hero-status-pill {
        display: inline-flex; align-items: center; gap: .45rem;
        background: rgba(255,255,255,.20); backdrop-filter: blur(8px);
        border: 1px solid rgba(255,255,255,.30);
        font-weight: 700; font-size: .76rem;
        padding: .4rem .9rem; border-radius: 999px;
        text-transform: uppercase; letter-spacing: .03em; color: #fff;
    }
    .hero-title { font-weight: 800; font-size: 1.5rem; color: #fff; margin: 0 0 .35rem; letter-spacing: -.01em; }
    .hero-desc  { color: rgba(255,255,255,.90); margin: 0; max-width: 640px; font-size: .9rem; line-height: 1.55; }
    .hero-meta  { display: flex; gap: .85rem 1.75rem; flex-wrap: wrap; margin-top: 1.1rem; font-size: .8rem; }
    .hero-meta .meta-item { display: inline-flex; align-items: center; gap: .45rem; opacity: .95; color: #fff; }

    .hero-total-card {
        background: rgba(255,255,255,.16); backdrop-filter: blur(10px);
        border: 1px solid rgba(255,255,255,.28); border-radius: 1rem;
        padding: 1.1rem 1.25rem;
    }
    .hero-total-card .htc-label { font-size: .68rem; color: rgba(255,255,255,.88); font-weight: 700; text-transform: uppercase; letter-spacing: .06em; margin-bottom: .2rem; }
    .hero-total-card .htc-value { font-size: 1.7rem; font-weight: 800; color: #fff; line-height: 1.05; font-variant-numeric: tabular-nums; text-shadow: 0 1px 2px rgba(0,0,0,.12); }
    .htc-progress { margin-top: .9rem; }
    .htc-progress-top { display: flex; justify-content: space-between; font-size: .7rem; color: rgba(255,255,255,.92); margin-bottom: .35rem; }
    .htc-bar { height: 8px; border-radius: 999px; background: rgba(255,255,255,.25); overflow: hidden; }
    .htc-bar span { display: block; height: 100%; border-radius: 999px; background: #fff; box-shadow: 0 0 10px rgba(255,255,255,.6); animation: mspBar 1.1s cubic-bezier(.22,1,.36,1) both; }

    /* ============ STAT CARDS ============ */
    .stat-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); gap: 1rem; margin-bottom: 1.25rem; }
    .stat-card {
        background: #fff; border: 1px solid #eef0f4; border-radius: 1rem;
        padding: 1.1rem 1.25rem; position: relative; overflow: hidden;
        animation: mspIn .5s cubic-bezier(.22,1,.36,1) both; animation-delay: calc(.06s * var(--i, 0));
        transition: transform .25s ease, box-shadow .25s ease;
    }
    .stat-card:hover { transform: translateY(-3px); box-shadow: 0 14px 28px rgba(15,23,42,.08); }
    .stat-card::before { content: ''; position: absolute; inset: 0 auto 0 0; width: 4px; background: var(--sc, #6366f1); }
    .stat-card .sc-top { display: flex; align-items: center; justify-content: space-between; gap: .5rem; margin-bottom: .55rem; }
    .stat-card .sc-label { font-size: .68rem; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: #94a3b8; }
    .stat-card .sc-ico { width: 38px; height: 38px; border-radius: 11px; display: inline-flex; align-items: center; justify-content: center; font-size: 1.1rem; color: #fff; background: var(--sc, #6366f1); box-shadow: 0 6px 14px var(--scs, rgba(99,102,241,.3)); }
    .stat-card .sc-value { font-size: 1.35rem; font-weight: 800; color: #0f172a; line-height: 1.1; font-variant-numeric: tabular-nums; }
    .stat-card .sc-foot { font-size: .72rem; color: #94a3b8; margin-top: .25rem; }
    .sc-primary { --sc: #6366f1; --scs: rgba(99,102,241,.3); }
    .sc-danger  { --sc: #f43f5e; --scs: rgba(244,63,94,.3); }
    .sc-success { --sc: #10b981; --scs: rgba(16,185,129,.3); }
    .sc-info    { --sc: #0ea5e9; --scs: rgba(14,165,233,.3); }

    /* ============ MODERN CARD ============ */
    .msp-card {
        background: #fff; border: 1px solid #eef0f4; border-radius: 1.25rem; overflow: hidden;
        box-shadow: 0 4px 12px rgba(15,23,42,.04);
        animation: mspIn .55s cubic-bezier(.22,1,.36,1) both; animation-delay: var(--d, .2s);
        transition: box-shadow .3s ease;
    }
    .msp-card:hover { box-shadow: 0 14px 32px rgba(15,23,42,.08); }
    .msp-card-head {
        padding: 1.1rem 1.5rem; border-bottom: 1px solid #f1f3f7;
        display: flex; align-items: center; justify-content: space-between; gap: 1rem;
        background: linear-gradient(180deg, #fafbff 0%, #fff 100%);
    }
    .mc-head-left { display: flex; align-items: center; gap: .85rem; }
    .mc-icon { width: 46px; height: 46px; border-radius: 14px; display: inline-flex; align-items: center; justify-content: center; font-size: 1.25rem; color: #fff; flex-shrink: 0; transition: transform .3s ease; }
    .msp-card:hover .mc-icon { transform: rotate(-6deg) scale(1.05); }
    .mc-icon-info    { background: linear-gradient(135deg, #38bdf8, #0ea5e9); box-shadow: 0 8px 18px rgba(14,165,233,.32); }
    .mc-icon-primary { background: linear-gradient(135deg, #818cf8, #6366f1); box-shadow: 0 8px 18px rgba(99,102,241,.32); }
    .mc-icon-success { background: linear-gradient(135deg, #34d399, #10b981); box-shadow: 0 8px 18px rgba(16,185,129,.32); }
    .mc-title { font-size: 1.02rem; font-weight: 800; color: #0f172a; margin: 0; letter-spacing: -.01em; }
    .mc-sub   { font-size: .78rem; color: #64748b; margin: .15rem 0 0; }
    .msp-card-body { padding: 1.5rem; }
    .mc-pill { display: inline-flex; align-items: center; gap: .35rem; font-size: .75rem; font-weight: 700; padding: .35rem .85rem; border-radius: 999px; white-space: nowrap; }
    .mc-pill-info    { background: rgba(14,165,233,.12); color: #0369a1; }
    .mc-pill-primary { background: rgba(99,102,241,.12); color: #4338ca; }
    .mc-pill-success { background: rgba(16,185,129,.14); color: #047857; }
    .mc-pill-warning { background: rgba(245,158,11,.14); color: #b45309; }

    /* ============ INFO GRID ============ */
    .info-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: 1rem; }
    .info-cell { background: #f8fafc; border: 1px solid #eef0f4; border-radius: .85rem; padding: .85rem 1rem; position: relative; overflow: hidden; transition: all .25s ease; }
    .info-cell::before { content: ''; position: absolute; inset: 0 0 auto 0; height: 3px; background: var(--ic, #6366f1); opacity: 0; transition: opacity .25s ease; }
    .info-cell:hover { background: #fff; border-color: var(--ic, #c7d2fe); transform: translateY(-2px); box-shadow: 0 8px 18px rgba(99,102,241,.08); }
    .info-cell:hover::before { opacity: 1; }
    .info-cell .ic-label { display: inline-flex; align-items: center; gap: .35rem; font-size: .66rem; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: #94a3b8; margin-bottom: .35rem; }
    .info-cell .ic-label i { color: var(--ic, #6366f1); }
    .info-cell .ic-value { display: block; font-size: .92rem; font-weight: 700; color: #1e293b; line-height: 1.3; word-break: break-word; }
    .info-cell .ic-sub { display: block; font-size: .72rem; font-weight: 500; color: #94a3b8; margin-top: .15rem; }
    .info-cell .ic-value.is-money { color: #047857; }
    .ic-primary { --ic: #6366f1; } .ic-info { --ic: #0ea5e9; } .ic-success { --ic: #10b981; }
    .ic-warning { --ic: #f59e0b; } .ic-violet { --ic: #8b5cf6; } .ic-rose { --ic: #f43f5e; }

    /* ============ PESERTA LIST ============ */
    .peserta-list { display: flex; flex-direction: column; gap: .75rem; }
    .peserta-item { background: #fff; border: 1px solid #eef0f4; border-radius: 1rem; overflow: hidden; transition: all .3s cubic-bezier(.22,1,.36,1); animation: mspIn .4s cubic-bezier(.22,1,.36,1) both; animation-delay: calc(.05s * var(--i, 0)); }
    .peserta-item:hover { border-color: #c7d2fe; box-shadow: 0 10px 20px rgba(99,102,241,.08); }
    .peserta-toggle { width: 100%; background: linear-gradient(180deg, #fafbff 0%, #fff 100%); border: 0; padding: .9rem 1.1rem; display: flex; align-items: center; gap: 1rem; cursor: pointer; text-align: left; transition: background .2s ease; }
    .peserta-toggle:hover { background: linear-gradient(180deg, #f1f5f9 0%, #fafbff 100%); }
    .peserta-item.is-open .peserta-toggle { background: linear-gradient(135deg, rgba(99,102,241,.08), rgba(14,165,233,.04)); }
    .peserta-num { width: 34px; height: 34px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #818cf8, #6366f1); color: #fff; font-size: .82rem; font-weight: 800; flex-shrink: 0; box-shadow: 0 4px 10px rgba(99,102,241,.35); }
    .peserta-info { flex: 1; min-width: 0; }
    .peserta-name { display: block; font-weight: 700; color: #0f172a; font-size: .95rem; margin-bottom: .1rem; }
    .peserta-meta { display: block; font-size: .75rem; color: #64748b; }
    .peserta-quick { display: none; gap: .35rem; flex-shrink: 0; }
    @media (min-width: 768px) { .peserta-quick { display: flex; } }
    .pq-pill { display: inline-flex; align-items: center; gap: .3rem; font-size: .7rem; font-weight: 600; padding: .25rem .65rem; border-radius: 999px; background: #f1f5f9; color: #475569; }
    .pq-pill.pq-money { background: rgba(16,185,129,.12); color: #047857; font-weight: 700; }
    .peserta-chevron { color: #94a3b8; font-size: 1.2rem; transition: transform .3s ease; flex-shrink: 0; }
    .peserta-item.is-open .peserta-chevron { transform: rotate(180deg); color: #4f46e5; }
    .peserta-body { max-height: 0; overflow: hidden; transition: max-height .4s ease; }
    .peserta-item.is-open .peserta-body { max-height: 1500px; }
    .peserta-body-inner { padding: 1.1rem 1.25rem 1.35rem; border-top: 1px solid #f1f3f7; }
    .pi-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(170px, 1fr)); gap: .65rem; }
    .pi-cell { background: #f8fafc; border: 1px solid #eef0f4; border-radius: .65rem; padding: .6rem .8rem; }
    .pi-cell .pi-label { display: inline-flex; align-items: center; gap: .3rem; font-size: .63rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: #94a3b8; margin-bottom: .2rem; }
    .pi-cell .pi-value { display: block; font-size: .82rem; font-weight: 700; color: #1e293b; word-break: break-word; }
    .biaya-card { background: linear-gradient(135deg, rgba(99,102,241,.05), rgba(14,165,233,.03)); border: 1px solid rgba(99,102,241,.18); border-radius: 1rem; padding: 1rem 1.1rem; }
    .biaya-title { display: inline-flex; align-items: center; gap: .35rem; font-size: .72rem; font-weight: 800; text-transform: uppercase; letter-spacing: .06em; color: #4338ca; margin-bottom: .75rem; }
    .biaya-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: .55rem; }
    .biaya-cell { background: #fff; border-radius: .65rem; padding: .55rem .75rem; border: 1px solid rgba(99,102,241,.15); transition: all .2s ease; }
    .biaya-cell:hover { border-color: #6366f1; transform: translateY(-2px); }
    .biaya-cell.subtotal { background: linear-gradient(135deg, #10b981, #059669); color: #fff; border-color: transparent; box-shadow: 0 8px 18px rgba(16,185,129,.3); }
    .biaya-cell.subtotal .b-label, .biaya-cell.subtotal .b-value { color: #fff !important; }
    .biaya-cell .b-label { display: block; font-size: .63rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: #94a3b8; margin-bottom: .15rem; }
    .biaya-cell .b-value { display: block; font-size: .82rem; font-weight: 700; color: #1e293b; font-variant-numeric: tabular-nums; }

    /* ============ BUKTI DUKUNG ============ */
    .bukti-card { background: #fff; border: 1px solid #eef0f4; border-radius: 1rem; padding: 1rem 1.1rem; }
    .bukti-card .biaya-title { color: #0f766e; }
    .bukti-count { margin-left: auto; font-size: .65rem; font-weight: 700; color: #0f766e; background: rgba(16,185,129,.12); border-radius: 999px; padding: .15rem .6rem; letter-spacing: 0; text-transform: none; }
    .bukti-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); gap: .55rem; }
    .bukti-cell { display: flex; align-items: center; gap: .6rem; background: #f8fafc; border: 1px solid #eef0f4; border-radius: .7rem; padding: .55rem .7rem; transition: all .2s ease; }
    .bukti-cell.has-file { border-color: rgba(16,185,129,.3); background: linear-gradient(135deg, rgba(16,185,129,.06), rgba(16,185,129,.02)); }
    .bukti-cell.has-file:hover { border-color: #10b981; transform: translateY(-2px); box-shadow: 0 6px 14px rgba(16,185,129,.16); }
    .bukti-cell .bukti-ic { width: 30px; height: 30px; flex-shrink: 0; border-radius: .55rem; display: flex; align-items: center; justify-content: center; font-size: .9rem; background: #e2e8f0; color: #64748b; }
    .bukti-cell.has-file .bukti-ic { background: rgba(16,185,129,.15); color: #059669; }
    .bukti-cell .bukti-text { min-width: 0; flex: 1 1 auto; display: flex; flex-direction: column; }
    .bukti-cell .bukti-label { font-size: .7rem; font-weight: 800; text-transform: uppercase; letter-spacing: .04em; color: #475569; }
    .bukti-cell .bukti-name { font-size: .7rem; color: #94a3b8; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .bukti-cell .bukti-link { flex-shrink: 0; width: 30px; height: 30px; border-radius: .55rem; display: flex; align-items: center; justify-content: center; background: #10b981; color: #fff; font-size: .85rem; text-decoration: none; transition: all .2s ease; }
    .bukti-cell .bukti-link:hover { background: #059669; transform: scale(1.08); }
    .bukti-cell .bukti-none { flex-shrink: 0; width: 30px; height: 30px; display: flex; align-items: center; justify-content: center; color: #cbd5e1; }

    /* ============ DOKUMEN TTE ============ */
    .tte-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1rem; }
    .tte-doc { display: flex; align-items: center; gap: .9rem; background: #f8fafc; border: 1px solid #eef0f4; border-radius: 1rem; padding: 1rem 1.1rem; transition: all .2s ease; }
    .tte-doc.is-signed { border-color: rgba(16,185,129,.32); background: linear-gradient(135deg, rgba(16,185,129,.07), rgba(16,185,129,.02)); }
    .tte-doc:hover { transform: translateY(-2px); box-shadow: 0 8px 18px rgba(15,23,42,.07); }
    .tte-doc .tte-ic { width: 46px; height: 46px; flex-shrink: 0; border-radius: .8rem; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; background: #e2e8f0; color: #64748b; }
    .tte-doc.is-signed .tte-ic { background: rgba(16,185,129,.15); color: #059669; }
    .tte-doc .tte-body { min-width: 0; flex: 1 1 auto; }
    .tte-doc .tte-name { display: block; font-size: .88rem; font-weight: 800; color: #1e293b; margin-bottom: .15rem; }
    .tte-doc .tte-status { display: inline-flex; align-items: center; gap: .3rem; font-size: .68rem; font-weight: 700; padding: .18rem .55rem; border-radius: 999px; }
    .tte-doc .tte-status.signed { background: rgba(16,185,129,.14); color: #047857; }
    .tte-doc .tte-status.unsigned { background: rgba(245,158,11,.14); color: #b45309; }
    .tte-doc .tte-action { flex-shrink: 0; }
    .tte-note { display: flex; gap: .55rem; align-items: flex-start; margin-top: 1rem; font-size: .78rem; color: #64748b; background: #f8fafc; border: 1px dashed #e2e8f0; border-radius: .7rem; padding: .65rem .85rem; }
    .tte-note i { color: #0ea5e9; margin-top: 1px; flex-shrink: 0; }

    /* ============ REKAP HEADER ============ */
    .rekap-wrap { animation: mspIn .55s cubic-bezier(.22,1,.36,1) both; animation-delay: .38s; }

    @media (prefers-reduced-motion: reduce) {
        .msp-hero, .stat-card, .msp-card, .peserta-item, .rekap-wrap, .htc-bar span { animation: none !important; }
    }
</style>
@endpush

@section('content')
    <x-page-title title="Pembuatan SPP" subtitle="Detail Multi-SPP Perjaldin" />

    @if(session('success'))
        <div class="alert alert-success border-0 bg-success alert-dismissible fade show text-white shadow-sm">
            <i class="bi bi-check-circle me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error') || $errors->any())
        <div class="alert alert-danger border-0 bg-danger alert-dismissible fade show shadow-sm text-white">
            <i class="bi bi-exclamation-triangle me-2"></i>
            {{ session('error') ?? $errors->first() }}
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @php
        $bulanList = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];
        $periodeLabel = ($bulanList[(int) $tagihan->periode_bulan] ?? $tagihan->periode_bulan) . ' ' . $tagihan->periode_tahun;
        $komponensAktif = $tagihan->komponenPerjaldin->where('total_nominal', '>', 0);
        $totalKomponen  = $komponensAktif->count();
        $komponenSudahSpp = $komponensAktif->filter(fn ($k) => $k->hasDokumenTurunan())->count();
        $komponenAdaCoa = $komponensAktif->filter(fn ($k) => !empty($k->dipa_revision_item_id))->count();

        $ppkNama = $tagihan->ppk_nama_snapshot ?: ($ppkUser->name ?? '-');
        $ppkNip  = $tagihan->ppk_nip_snapshot ?: '-';
        $koorNama = $tagihan->koordinator_keuangan_nama_snapshot ?: ($koordinatorUser->name ?? '-');
        $kasubbagNama = $tagihan->kasubbag_nama_snapshot ?: ($kasubbagUser->name ?? '-');
        $mekanisme = $tagihan->mekanisme_pembayaran
            ? ($tagihan->mekanisme_pembayaran instanceof \App\Enums\MekanismePembayaran
                ? $tagihan->mekanisme_pembayaran->label()
                : ucwords(str_replace('_', ' ', (string) $tagihan->mekanisme_pembayaran)))
            : '-';
    @endphp

    <!-- Hero Header -->
    @include('spps.partials.perjaldin_detail_hero')

    <!-- Ringkasan Finansial -->
    <div class="stat-row">
        <div class="stat-card sc-primary" style="--i:0;">
            <div class="sc-top"><span class="sc-label">Total Bruto</span><span class="sc-ico"><i class="bi bi-cash-stack"></i></span></div>
            <div class="sc-value">Rp {{ number_format($tagihan->total_bruto, 0, ',', '.') }}</div>
            <div class="sc-foot">Nilai kotor seluruh peserta</div>
        </div>
        <div class="stat-card sc-success" style="--i:1;">
            <div class="sc-top"><span class="sc-label">Total Netto</span><span class="sc-ico"><i class="bi bi-wallet2"></i></span></div>
            <div class="sc-value">Rp {{ number_format($tagihan->total_netto, 0, ',', '.') }}</div>
            <div class="sc-foot">Nilai bersih dibayarkan</div>
        </div>
        <div class="stat-card sc-info" style="--i:2;">
            <div class="sc-top"><span class="sc-label">Progres SPP</span><span class="sc-ico"><i class="bi bi-check2-circle"></i></span></div>
            <div class="sc-value">{{ $komponenSudahSpp }} / {{ $totalKomponen }}</div>
            <div class="sc-foot">{{ $komponenAdaCoa }} komponen telah ber-COA</div>
        </div>
    </div>

    <!-- Informasi Dokumen -->
    <div class="msp-card mb-4" style="--d: .2s;">
        <div class="msp-card-head">
            <div class="mc-head-left">
                <span class="mc-icon mc-icon-primary"><i class="bi bi-info-circle-fill"></i></span>
                <div>
                    <h5 class="mc-title">Informasi Dokumen Perjalanan Dinas</h5>
                    <p class="mc-sub">Ringkasan identitas, periode, dan pejabat terkait.</p>
                </div>
            </div>
        </div>
        <div class="msp-card-body">
            <div class="info-grid">
                <div class="info-cell ic-primary"><span class="ic-label"><i class="bi bi-receipt"></i>Nomor Tagihan</span><span class="ic-value">{{ $tagihan->nomor_tagihan }}</span></div>
                <div class="info-cell ic-info"><span class="ic-label"><i class="bi bi-calendar3"></i>Periode</span><span class="ic-value">{{ $periodeLabel }}</span></div>
                <div class="info-cell ic-violet"><span class="ic-label"><i class="bi bi-credit-card-2-front"></i>Mekanisme Pembayaran</span><span class="ic-value">{{ $mekanisme }}</span></div>
                <div class="info-cell ic-warning"><span class="ic-label"><i class="bi bi-geo-alt"></i>Kota & Tgl TTD</span><span class="ic-value">{{ $tagihan->kota_ttd ?: '-' }}</span><span class="ic-sub">{{ $tagihan->tanggal_ttd ? \Carbon\Carbon::parse($tagihan->tanggal_ttd)->translatedFormat('d M Y') : '-' }}</span></div>
                <div class="info-cell ic-success"><span class="ic-label"><i class="bi bi-people"></i>Jumlah Peserta</span><span class="ic-value">{{ $tagihan->detailPerjaldin->count() }} Orang</span></div>
                <div class="info-cell ic-primary"><span class="ic-label"><i class="bi bi-person-badge"></i>PPK</span><span class="ic-value">{{ $ppkNama }}</span><span class="ic-sub">NIP {{ $ppkNip }}</span></div>
                <div class="info-cell ic-info"><span class="ic-label"><i class="bi bi-person-workspace"></i>Koordinator Keuangan</span><span class="ic-value">{{ $koorNama }}</span></div>
                <div class="info-cell ic-violet"><span class="ic-label"><i class="bi bi-person-gear"></i>Kasubbag Keuangan</span><span class="ic-value">{{ $kasubbagNama }}</span></div>
            </div>
        </div>
    </div>

    <!-- Peserta Summary -->
    @include('spps.partials.perjaldin_peserta_summary')

    <!-- Dokumen ber-TTE -->
    @php
        $tteSigned = \App\Support\TagihanDocumentTte::isApproved($tagihan);
        $tteDocs = [
            ['label' => 'Nominatif Perjaldin', 'icon' => 'bi-file-earmark-text', 'route' => route('spps.perjaldin.pdf-nominatif', $tagihan->id)],
            ['label' => 'Daftar Nominatif Pembayaran Perjaldin', 'icon' => 'bi-file-earmark-spreadsheet', 'route' => route('spps.perjaldin.pdf-lampiran', $tagihan->id)],
        ];
    @endphp
    <div class="msp-card mb-4" style="--d: .34s;">
        <div class="msp-card-head">
            <div class="mc-head-left">
                <span class="mc-icon {{ $tteSigned ? 'mc-icon-success' : 'mc-icon-primary' }}"><i class="bi bi-patch-check-fill"></i></span>
                <div>
                    <h5 class="mc-title">Dokumen Bertanda Tangan Elektronik (TTE)</h5>
                    <p class="mc-sub">Status TTE dokumen nominatif &amp; daftar pembayaran perjaldin.</p>
                </div>
            </div>
            @if($tteSigned)
                <span class="mc-pill mc-pill-success"><i class="bi bi-shield-check me-1"></i>Ber-TTE</span>
            @else
                <span class="mc-pill mc-pill-warning"><i class="bi bi-hourglass-split me-1"></i>Belum Ber-TTE</span>
            @endif
        </div>
        <div class="msp-card-body">
            <div class="tte-grid">
                @foreach($tteDocs as $doc)
                    <div class="tte-doc {{ $tteSigned ? 'is-signed' : '' }}">
                        <span class="tte-ic"><i class="bi {{ $doc['icon'] }}"></i></span>
                        <div class="tte-body">
                            <span class="tte-name">{{ $doc['label'] }}</span>
                            @if($tteSigned)
                                <span class="tte-status signed"><i class="bi bi-check-circle-fill"></i> QR TTE aktif</span>
                            @else
                                <span class="tte-status unsigned"><i class="bi bi-exclamation-triangle-fill"></i> Menunggu verifikasi</span>
                            @endif
                        </div>
                        <div class="tte-action">
                            <a href="{{ $doc['route'] }}" target="_blank" class="btn btn-sm {{ $tteSigned ? 'btn-success' : 'btn-outline-secondary' }} rounded-pill px-3 fw-bold">
                                <i class="bi bi-eye me-1"></i> Lihat
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
            @unless($tteSigned)
                <div class="tte-note">
                    <i class="bi bi-info-circle-fill"></i>
                    <span>Dokumen akan otomatis menampilkan QR Tanda Tangan Elektronik setelah seluruh proses verifikasi tagihan perjaldin ini disetujui. Sebelum itu, dokumen dicetak tanpa QR sebagai ruang tanda tangan basah.</span>
                </div>
            @endunless
        </div>
    </div>

    <!-- Rekap Item Biaya -->
    <div class="rekap-wrap">
        <div class="msp-card">
            <div class="msp-card-head">
                <div class="mc-head-left">
                    <span class="mc-icon mc-icon-success"><i class="bi bi-ui-radios-grid"></i></span>
                    <div>
                        <h5 class="mc-title">Rekap Item Biaya Perjaldin</h5>
                        <p class="mc-sub">Pilih COA dan kelola SPP untuk setiap item biaya secara terpisah.</p>
                    </div>
                </div>
                <span class="mc-pill mc-pill-primary">{{ $totalKomponen }} Item</span>
            </div>
            <div class="msp-card-body" style="background: #f6f8fc;">
                @php $komponens = $tagihan->komponenPerjaldin->where('total_nominal', '>', 0); @endphp

                @forelse($komponens as $komponen)
                    @include('spps.partials.perjaldin_komponen_card')
                @empty
                    <div class="text-center py-5">
                        <img src="{{ URL::asset('build/images/no-data.svg') }}" alt="No Data" class="mb-3" style="width: 120px; opacity: 0.5;">
                        <h6 class="text-muted fw-normal">Tidak ada item biaya yang tercatat untuk Perjaldin ini.</h6>
                    </div>
                @endforelse

                <div class="mt-4 d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <a href="{{ route('spps.perjaldin.index') }}" class="btn btn-outline-secondary px-4 rounded-pill">
                        <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar
                    </a>

                    @if(in_array($tagihan->status, ['DISETUJUI_PERJALDIN', 'PROSES_SPP']))
                    <button type="button" class="btn btn-danger px-4 rounded-pill shadow-sm" data-bs-toggle="modal" data-bs-target="#modalKembalikanRevisi">
                        <i class="bi bi-arrow-return-left me-1"></i> Kembalikan untuk Revisi
                    </button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if(in_array($tagihan->status, ['DISETUJUI_PERJALDIN', 'PROSES_SPP']))
    <!-- Modal Kembalikan untuk Revisi -->
    <div class="modal fade" id="modalKembalikanRevisi" tabindex="-1" aria-labelledby="modalKembalikanRevisiLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 rounded-4 shadow">
                <form action="{{ route('spps.perjaldin.return-revision', $tagihan->id) }}" method="POST">
                    @csrf
                    <div class="modal-header bg-danger text-white" style="border-radius: var(--bs-border-radius-xl) var(--bs-border-radius-xl) 0 0;">
                        <h5 class="modal-title fw-bold" id="modalKembalikanRevisiLabel"><i class="bi bi-exclamation-triangle-fill me-2"></i>Kembalikan Dokumen untuk Revisi</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="alert alert-light border-danger text-danger border-start border-4 mb-4" role="alert">
                            <strong>Perhatian!</strong> Mengembalikan dokumen ini akan mengubah statusnya menjadi DIKEMBALIKAN dan memberitahukan kepada Operator Perjaldin/PPK. Lakukan ini jika sisa pagu COA tidak mencukupi atau terdapat kesalahan nominal.
                        </div>
                        <div class="mb-3">
                            <label for="catatan_revisi" class="form-label fw-bold">Catatan Revisi <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="catatan_revisi" name="catatan_revisi" rows="4" placeholder="Contoh: Pagu COA 524111 tidak mencukupi, mohon kurangi rincian perjaldin atau tunggu revisi DIPA." required></textarea>
                            <div class="form-text">Mohon sertakan alasan yang jelas agar pembuat dokumen dapat memahami bagian yang harus direvisi.</div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light border-0" style="border-radius: 0 0 var(--bs-border-radius-xl) var(--bs-border-radius-xl);">
                        <button type="button" class="btn btn-outline-secondary px-4 rounded-pill" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-danger px-4 rounded-pill"><i class="bi bi-send-fill me-1"></i> Kembalikan Sekarang</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

@endsection
