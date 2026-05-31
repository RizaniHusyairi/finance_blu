@extends('layouts.app')
@section('title', 'Detail Buku Kas Umum')

@php
    use Illuminate\Support\Str;
    use Carbon\Carbon;

    $isDebit = $entry->arus_kas === 'DEBIT_MASUK';

    // Detail tipe pengeluaran (hanya relevan untuk KREDIT_KELUAR / $tagihan).
    $detailKontrak     = $tagihan?->detailKontrak;
    $detailPerjaldin   = $tagihan?->detailPerjaldin   ?? collect();
    $detailHonorarium  = $tagihan?->detailHonorarium  ?? collect();
    $komponenPerjaldin = $tagihan?->komponenPerjaldin ?? collect();
    $potongan          = $tagihan?->potonganTagihan   ?? collect();

    $jenisPengeluaran = $detailKontrak ? 'Kontrak / Pengadaan'
        : ($detailPerjaldin->isNotEmpty() ? 'Perjalanan Dinas'
        : ($detailHonorarium->isNotEmpty() ? 'Honorarium'
        : ($tagihan?->tipe_tagihan ? Str::headline(strtolower($tagihan->tipe_tagihan)) : 'Pengeluaran Umum')));

    $jenisIcon = $detailKontrak ? 'bi-file-earmark-ruled'
        : ($detailPerjaldin->isNotEmpty() ? 'bi-airplane'
        : ($detailHonorarium->isNotEmpty() ? 'bi-people'
        : 'bi-receipt'));

    // Helper format nilai generik untuk dump kolom detail.
    $fmtVal = function ($key, $val) {
        if (is_null($val) || $val === '') return '-';
        $k = strtolower($key);
        if (Str::contains($k, 'tanggal') || (is_string($val) && preg_match('/^\d{4}-\d{2}-\d{2}([ T]|$)/', $val))) {
            try { return Carbon::parse($val)->format('d M Y'); } catch (\Throwable $e) {}
        }
        if (preg_match('/(nominal|jumlah|total|nilai|harga|subtotal|biaya|uang|saldo|tarif_|_tarif|dibayar|denda|pagu|anggaran)/', $k) && is_numeric($val)) {
            return 'Rp ' . number_format((float) $val, 0, ',', '.');
        }
        if (Str::contains($k, 'persen') && is_numeric($val)) {
            return rtrim(rtrim(number_format((float) $val, 2, ',', '.'), '0'), ',') . '%';
        }
        return $val;
    };

    // Ambil atribut "bermakna" dari sebuah model detail (buang id/timestamp/foreign key).
    $dumpAttrs = function ($model) {
        if (! $model) return [];
        $skip = ['id', 'created_at', 'updated_at', 'deleted_at'];
        $rows = [];
        foreach ($model->getAttributes() as $k => $v) {
            if (in_array($k, $skip, true) || Str::endsWith($k, '_id')) continue;
            $rows[$k] = $v;
        }
        return $rows;
    };
@endphp

@push('css')
<style>
    .bku-detail {
        --d-emerald: #10b981;
        --d-rose: #f43f5e;
        --d-indigo: #6366f1;
        --d-ink: #0f172a;
        --d-muted: #64748b;
    }

    /* ===== entrance animations ===== */
    @keyframes bkuUp   { from { opacity:0; transform:translateY(22px); } to { opacity:1; transform:translateY(0); } }
    @keyframes bkuIn   { from { opacity:0; transform:scale(.96); }       to { opacity:1; transform:scale(1); } }
    @keyframes bkuPop  { from { opacity:0; transform:translateY(14px) scale(.97);} to { opacity:1; transform:translateY(0) scale(1);} }
    @keyframes bkuFloat{ 0%,100% { transform:translateY(0); } 50% { transform:translateY(-10px); } }
    @keyframes bkuCount{ from { opacity:0; } to { opacity:1; } }

    .bku-anim { opacity:0; animation: bkuUp .6s cubic-bezier(.22,1,.36,1) forwards; }
    .bku-d1 { animation-delay:.05s; } .bku-d2 { animation-delay:.13s; }
    .bku-d3 { animation-delay:.21s; } .bku-d4 { animation-delay:.29s; }
    .bku-d5 { animation-delay:.37s; } .bku-d6 { animation-delay:.45s; }

    /* ===== Hero ===== */
    .det-hero {
        position: relative; overflow: hidden;
        border-radius: 1.5rem; padding: 1.9rem 2rem;
        margin-bottom: 1.5rem; color: #fff;
        animation: bkuIn .6s cubic-bezier(.22,1,.36,1) both;
        background-size: 200% 200%;
    }
    .det-hero.is-debit  { background: linear-gradient(125deg,#047857 0%,#10b981 50%,#34d399 100%); box-shadow:0 22px 48px -22px rgba(16,185,129,.85); animation: bkuIn .6s cubic-bezier(.22,1,.36,1) both, bkuHeroShift 12s ease infinite; }
    .det-hero.is-kredit { background: linear-gradient(125deg,#be123c 0%,#f43f5e 50%,#fb7185 100%); box-shadow:0 22px 48px -22px rgba(244,63,94,.85); animation: bkuIn .6s cubic-bezier(.22,1,.36,1) both, bkuHeroShift 12s ease infinite; }
    @keyframes bkuHeroShift { 0%{background-position:0% 50%;} 50%{background-position:100% 50%;} 100%{background-position:0% 50%;} }

    .det-hero::after {
        content:""; position:absolute; top:-90px; right:-50px;
        width:320px; height:320px; border-radius:50%;
        background: radial-gradient(circle, rgba(255,255,255,.2) 0%, rgba(255,255,255,0) 70%);
        animation: bkuFloat 7s ease-in-out infinite;
    }
    .det-hero::before {
        content:""; position:absolute; bottom:-70px; left:-30px;
        width:200px; height:200px; border-radius:50%;
        background: radial-gradient(circle, rgba(255,255,255,.14) 0%, rgba(255,255,255,0) 70%);
        animation: bkuFloat 9s ease-in-out infinite 1s;
    }
    .det-hero .hero-z { position:relative; z-index:2; }
    .det-hero .hero-ic {
        width:62px; height:62px; display:grid; place-items:center;
        border-radius:1.1rem; font-size:1.8rem;
        background: rgba(255,255,255,.2); backdrop-filter: blur(6px);
        border:1px solid rgba(255,255,255,.25);
        animation: bkuFloat 5s ease-in-out infinite;
    }
    .det-hero h4 { color:#fff; }
    .det-hero .sub { color: rgba(255,255,255,.88); }
    .det-hero .btn-back {
        background: rgba(255,255,255,.18); color:#fff; border:1px solid rgba(255,255,255,.25);
        border-radius:.85rem; font-weight:600; padding:.5rem 1.1rem;
        transition: transform .15s ease, background .15s ease;
    }
    .det-hero .btn-back:hover { transform:translateY(-2px); background: rgba(255,255,255,.3); color:#fff; }
    .det-hero .hero-amount { font-size:2.15rem; font-weight:800; letter-spacing:-.02em; animation: bkuCount .9s ease both; }
    .det-hero .hero-pill {
        display:inline-flex; align-items:center; gap:.4rem;
        background: rgba(255,255,255,.22); border:1px solid rgba(255,255,255,.25);
        border-radius:999px; padding:.3rem .9rem; font-size:.82rem; font-weight:700;
    }

    /* ===== Stat chips ===== */
    .det-stats { display:grid; grid-template-columns: repeat(auto-fit,minmax(200px,1fr)); gap:1rem; margin-bottom:1.5rem; }
    .det-chip {
        position:relative; overflow:hidden;
        background:#fff; border:1px solid rgba(15,23,42,.06); border-radius:1.15rem;
        padding:1.05rem 1.2rem; box-shadow:0 12px 32px -28px rgba(15,23,42,.6);
        transition: transform .25s cubic-bezier(.22,1,.36,1), box-shadow .25s ease;
    }
    .det-chip::before {
        content:""; position:absolute; left:0; top:0; bottom:0; width:4px;
        background: var(--d-indigo); transform:scaleY(0); transform-origin:top; transition:transform .3s ease;
    }
    .det-chip.tone-emerald::before { background: var(--d-emerald); }
    .det-chip.tone-rose::before    { background: var(--d-rose); }
    .det-chip:hover { transform:translateY(-5px); box-shadow:0 18px 38px -22px rgba(15,23,42,.5); }
    .det-chip:hover::before { transform:scaleY(1); }
    .det-chip .lbl { font-size:.72rem; text-transform:uppercase; letter-spacing:.05em; font-weight:700; color:var(--d-muted); display:flex; align-items:center; gap:.35rem; }
    .det-chip .val { font-size:1.2rem; font-weight:800; color:var(--d-ink); margin-top:.2rem; }
    .det-chip .chip-ic { width:30px; height:30px; border-radius:.6rem; display:grid; place-items:center; background:rgba(99,102,241,.1); color:var(--d-indigo); font-size:.9rem; }
    .det-chip.tone-emerald .chip-ic { background:rgba(16,185,129,.12); color:var(--d-emerald); }
    .det-chip.tone-rose .chip-ic    { background:rgba(244,63,94,.12);  color:var(--d-rose); }

    /* ===== Section cards ===== */
    .bku-detail .book-card {
        border:1px solid rgba(15,23,42,.07); border-radius:1.25rem; background:#fff;
        box-shadow:0 14px 38px -30px rgba(15,23,42,.6);
        transition: box-shadow .3s ease, transform .3s ease;
    }
    .bku-detail .book-card:hover { box-shadow:0 20px 46px -28px rgba(15,23,42,.45); }
    .bku-detail .book-card .card-header {
        display:flex; align-items:center; gap:.6rem;
        background: linear-gradient(180deg,#fbfdff,#f4f7fc);
        border-bottom:1px solid rgba(15,23,42,.06);
        border-radius:1.25rem 1.25rem 0 0;
    }
    .sec-ic {
        width:38px; height:38px; display:grid; place-items:center; border-radius:.75rem;
        background: rgba(99,102,241,.12); color: var(--d-indigo); font-size:1.1rem;
        transition: transform .3s ease;
    }
    .book-card:hover .sec-ic { transform: rotate(-8deg) scale(1.06); }
    .sec-ic.emerald { background: rgba(16,185,129,.12); color: var(--d-emerald); }
    .sec-ic.rose    { background: rgba(244,63,94,.12);  color: var(--d-rose); }

    /* ===== Definition grid ===== */
    .def-grid { display:grid; grid-template-columns: repeat(auto-fit,minmax(210px,1fr)); gap:.85rem; }
    .def-item {
        position:relative; padding:.8rem .95rem; border-radius:.85rem;
        background:#f8fafc; border:1px solid #eef2f7;
        transition: all .25s cubic-bezier(.22,1,.36,1);
    }
    .def-item:hover { background:#fff; border-color:rgba(99,102,241,.25); transform:translateY(-3px); box-shadow:0 10px 22px -16px rgba(99,102,241,.7); }
    .def-item .k { font-size:.68rem; text-transform:uppercase; letter-spacing:.04em; font-weight:700; color:var(--d-muted); margin-bottom:.18rem; }
    .def-item .v { font-weight:700; color:var(--d-ink); word-break:break-word; }
    .def-item.span-all { grid-column:1/-1; }

    .type-badge {
        display:inline-flex; align-items:center; gap:.45rem;
        background: rgba(244,63,94,.1); color:var(--d-rose);
        border:1px solid rgba(244,63,94,.2); border-radius:999px;
        padding:.35rem .9rem; font-weight:700; font-size:.82rem;
    }
    .type-badge.emerald { background: rgba(16,185,129,.1); color:var(--d-emerald); border-color: rgba(16,185,129,.2); }

    /* big amount banner inside card */
    .amount-banner {
        display:flex; flex-wrap:wrap; gap:1rem; justify-content:space-between; align-items:center;
        padding:1rem 1.25rem; border-radius:1rem; margin-bottom:1.1rem;
        background: linear-gradient(120deg, rgba(16,185,129,.08), rgba(16,185,129,0));
        border:1px solid rgba(16,185,129,.18);
    }
    .amount-banner.rose { background: linear-gradient(120deg, rgba(244,63,94,.08), rgba(244,63,94,0)); border-color: rgba(244,63,94,.18); }
    .amount-banner .ab-amount { font-size:1.6rem; font-weight:800; letter-spacing:-.02em; }
    .amount-banner .ab-amount.emerald { color:var(--d-emerald); }
    .amount-banner .ab-amount.rose    { color:var(--d-rose); }

    /* ===== Document chain stepper ===== */
    .doc-chain { display:flex; flex-wrap:wrap; gap:.65rem; align-items:stretch; }
    .doc-step {
        flex:1 1 130px; min-width:130px; position:relative;
        border-radius:1rem; padding:.95rem 1.05rem;
        background:#f8fafc; border:1px solid #eef2f7;
        transition: transform .25s ease, box-shadow .25s ease;
        animation: bkuPop .5s cubic-bezier(.22,1,.36,1) both;
    }
    .doc-step:hover { transform:translateY(-4px); box-shadow:0 14px 28px -20px rgba(16,185,129,.7); }
    .doc-step.done { background: linear-gradient(160deg, rgba(16,185,129,.1), rgba(16,185,129,.02)); border-color: rgba(16,185,129,.3); }
    .doc-step .step-name { font-size:.7rem; font-weight:800; letter-spacing:.05em; color:var(--d-muted); }
    .doc-step.done .step-name { color: var(--d-emerald); }
    .doc-step .step-no { font-weight:700; color:var(--d-ink); font-size:.9rem; margin-top:.25rem; word-break:break-all; }
    .doc-step .step-date { font-size:.74rem; color:var(--d-muted); margin-top:.18rem; }
    .doc-step .step-check { position:absolute; top:.65rem; right:.75rem; color:var(--d-emerald); }
    .doc-arrow { display:flex; align-items:center; color:#cbd5e1; font-size:1.1rem; }

    .sub-card {
        border:1px solid #eef2f7; border-radius:1rem; padding:1.05rem 1.2rem; background:#fff;
        transition: box-shadow .25s ease, transform .25s ease;
        animation: bkuPop .5s cubic-bezier(.22,1,.36,1) both;
    }
    .sub-card:hover { box-shadow:0 14px 30px -24px rgba(15,23,42,.6); transform:translateY(-2px); }
    .sub-card + .sub-card { margin-top:.85rem; }
    .sub-card .sc-head { display:flex; align-items:center; justify-content:space-between; margin-bottom:.7rem; gap:.5rem; flex-wrap:wrap; }

    .divider-soft { height:1px; background:linear-gradient(90deg, transparent, #e2e8f0 20%, #e2e8f0 80%, transparent); border:0; margin:1.3rem 0; }
    .group-title { font-weight:800; color:var(--d-ink); display:flex; align-items:center; gap:.5rem; }
    .group-title .gt-ic { width:28px; height:28px; border-radius:.6rem; display:grid; place-items:center; background:rgba(244,63,94,.1); color:var(--d-rose); font-size:.85rem; }

    .bku-table thead th { font-size:.72rem; text-transform:uppercase; letter-spacing:.04em; color:var(--d-muted); }
    .bku-table tbody tr { transition: background .15s ease; }
    .bku-table tbody tr:hover { background:#f8fafc; }

    /* ===== Pejabat avatars ===== */
    .pejabat-row { display:flex; align-items:center; gap:.75rem; padding:.55rem 0; border-bottom:1px dashed #eef2f7; }
    .pejabat-row:last-child { border-bottom:0; }
    .pejabat-av {
        width:38px; height:38px; border-radius:50%; flex-shrink:0;
        display:grid; place-items:center; font-weight:800; font-size:.85rem; color:#fff;
        background: linear-gradient(135deg,#6366f1,#a855f7);
    }
    .pejabat-av.muted { background:#e2e8f0; color:#94a3b8; }

    /* ===== Timeline ===== */
    .det-timeline { position:relative; padding-left:1.5rem; }
    .det-timeline::before { content:""; position:absolute; left:.45rem; top:.3rem; bottom:.3rem; width:2px; background:linear-gradient(#6366f1,#e2e8f0); }
    .det-tl-item { position:relative; padding-bottom:1.15rem; animation: bkuPop .5s ease both; }
    .det-tl-item:last-child { padding-bottom:0; }
    .det-tl-dot { position:absolute; left:-1.2rem; top:.3rem; width:.78rem; height:.78rem; border-radius:999px; background:var(--d-indigo); box-shadow:0 0 0 3px rgba(99,102,241,.18); }

    .book-empty { text-align:center; color:var(--d-muted); padding:2.5rem 1rem; }
    .book-empty i { font-size:2.4rem; opacity:.3; display:block; margin-bottom:.6rem; }

    /* ===== Rekening account card ===== */
    .rek-acct {
        position:relative; overflow:hidden;
        border-radius:1rem; padding:1.1rem 1.2rem; color:#fff;
        background: linear-gradient(135deg,#334155,#1e293b);
        box-shadow:0 14px 30px -20px rgba(15,23,42,.9);
    }
    .rek-acct.emerald { background: linear-gradient(135deg,#047857,#10b981); }
    .rek-acct.rose    { background: linear-gradient(135deg,#be123c,#f43f5e); }
    .rek-acct::after {
        content:""; position:absolute; right:-30px; top:-30px; width:120px; height:120px; border-radius:50%;
        background: radial-gradient(circle, rgba(255,255,255,.18), transparent 70%);
    }
    .rek-acct .rek-bank { font-weight:700; font-size:.95rem; display:flex; align-items:center; flex-wrap:wrap; gap:.4rem; }
    .rek-acct .rek-tag {
        font-size:.62rem; font-weight:800; text-transform:uppercase; letter-spacing:.06em;
        background: rgba(255,255,255,.22); border:1px solid rgba(255,255,255,.3);
        border-radius:999px; padding:.1rem .55rem;
    }
    .rek-acct .rek-no { font-size:1.35rem; font-weight:800; letter-spacing:.08em; margin:.4rem 0 .15rem; font-variant-numeric:tabular-nums; }
    .rek-acct .rek-name { font-size:.85rem; opacity:.9; }
</style>
@endpush

@section('content')
<div class="bku-detail">
    <x-page-title title="Pembukuan" subtitle="Detail Buku Kas Umum" />

    {{-- ===== Hero ===== --}}
    <div class="det-hero {{ $isDebit ? 'is-debit' : 'is-kredit' }}">
        <div class="hero-z d-flex flex-column flex-lg-row justify-content-between gap-3">
            <div class="d-flex align-items-start gap-3">
                <div class="hero-ic"><i class="bi {{ $isDebit ? 'bi-arrow-down-left-circle-fill' : 'bi-arrow-up-right-circle-fill' }}"></i></div>
                <div>
                    <span class="hero-pill mb-2">
                        <i class="bi {{ $isDebit ? 'bi-box-arrow-in-down' : 'bi-box-arrow-up' }}"></i>
                        {{ $isDebit ? 'Debit Masuk · Penerimaan PNBP' : 'Kredit Keluar · Pengeluaran' }}
                    </span>
                    <h4 class="mb-1 fw-bold">Detail Transaksi BKU</h4>
                    <div class="sub">{{ $entry->nomor_bukti }} · {{ optional($entry->tanggal_transaksi)->translatedFormat('d F Y') }}</div>
                </div>
            </div>
            <div class="text-lg-end">
                <div class="hero-amount">{{ $isDebit ? '+' : '−' }} Rp {{ number_format($entry->nominal, 0, ',', '.') }}</div>
                <a href="{{ route('pembukuan.bku.index') }}" class="btn btn-back mt-2">
                    <i class="bi bi-arrow-left me-1"></i>Kembali ke BKU
                </a>
            </div>
        </div>
    </div>

    {{-- ===== Stat chips ===== --}}
    <div class="det-stats">
        <div class="det-chip {{ $isDebit ? 'tone-emerald' : 'tone-rose' }} bku-anim bku-d1">
            <div class="lbl"><span class="chip-ic"><i class="bi bi-arrow-left-right"></i></span> Arus Kas</div>
            <div class="val">@include('pembukuan.partials.status-badge', ['value' => $entry->arus_kas])</div>
        </div>
        <div class="det-chip {{ $isDebit ? 'tone-emerald' : 'tone-rose' }} bku-anim bku-d2">
            <div class="lbl"><span class="chip-ic"><i class="bi bi-cash-coin"></i></span> Nominal</div>
            <div class="val">Rp {{ number_format($entry->nominal, 0, ',', '.') }}</div>
        </div>
        <div class="det-chip bku-anim bku-d3">
            <div class="lbl"><span class="chip-ic"><i class="bi bi-wallet2"></i></span> Saldo Akhir</div>
            <div class="val">Rp {{ number_format($entry->saldo_akhir, 0, ',', '.') }}</div>
        </div>
        <div class="det-chip bku-anim bku-d4">
            <div class="lbl"><span class="chip-ic"><i class="bi bi-tags"></i></span> Sumber Transaksi</div>
            <div class="val">{{ $tagihan ? $jenisPengeluaran : ($penerimaan ? 'Penerimaan PNBP' : 'Sistem / Manual') }}</div>
        </div>
    </div>

    <div class="row g-4">
        {{-- ================= LEFT ================= --}}
        <div class="col-xl-8">

            {{-- Informasi BKU --}}
            <div class="card book-card mb-4 bku-anim bku-d2">
                <div class="card-header">
                    <span class="sec-ic"><i class="bi bi-journal-text"></i></span>
                    <h6 class="mb-0 fw-bold">Informasi BKU</h6>
                </div>
                <div class="card-body">
                    <div class="def-grid">
                        <div class="def-item"><div class="k">Tanggal Transaksi</div><div class="v">{{ optional($entry->tanggal_transaksi)->translatedFormat('d F Y') ?? '-' }}</div></div>
                        <div class="def-item"><div class="k">Nomor Bukti</div><div class="v">{{ $entry->nomor_bukti }}</div></div>
                        <div class="def-item span-all"><div class="k">Uraian</div><div class="v">{{ $entry->uraian }}</div></div>
                        <div class="def-item"><div class="k">Bank</div><div class="v">{{ $entry->sumberRekening?->nama_bank ?? '-' }}</div></div>
                        <div class="def-item"><div class="k">Nomor Rekening</div><div class="v">{{ $entry->sumberRekening?->nomor_rekening ?? '-' }}</div></div>
                        <div class="def-item"><div class="k">Atas Nama</div><div class="v">{{ $entry->sumberRekening?->nama_rekening ?? '-' }}</div></div>
                    </div>
                </div>
            </div>

            {{-- ============ DEBIT MASUK → Penagihan Jasa ============ --}}
            @if($isDebit && $penerimaan)
                <div class="card book-card mb-4 bku-anim bku-d3">
                    <div class="card-header">
                        <span class="sec-ic emerald"><i class="bi bi-receipt-cutoff"></i></span>
                        <div>
                            <h6 class="mb-0 fw-bold">Detail Penagihan Jasa (PNBP)</h6>
                            <small class="text-muted">Informasi invoice penerimaan yang menjadi sumber kas masuk.</small>
                        </div>
                        <span class="type-badge emerald ms-auto"><i class="bi bi-cash-stack"></i>{{ Str::headline(strtolower($penerimaan->status_pembayaran ?? 'PNBP')) }}</span>
                    </div>
                    <div class="card-body">
                        <div class="amount-banner">
                            <div>
                                <div class="k text-muted small fw-bold text-uppercase" style="letter-spacing:.05em;">Total Dibayar</div>
                                <div class="ab-amount emerald">Rp {{ number_format($penerimaan->total_dibayar ?? 0, 0, ',', '.') }}</div>
                            </div>
                            <div class="text-end">
                                <div class="small text-muted">Nominal Tagihan</div>
                                <div class="fw-bold">Rp {{ number_format($penerimaan->nominal_tagihan ?? 0, 0, ',', '.') }}</div>
                                @if(($penerimaan->nominal_denda_keterlambatan ?? 0) > 0)
                                    <div class="small text-danger mt-1">+ Denda Rp {{ number_format($penerimaan->nominal_denda_keterlambatan, 0, ',', '.') }}</div>
                                @endif
                            </div>
                        </div>
                        <div class="def-grid">
                            <div class="def-item"><div class="k">Nomor Invoice</div><div class="v">{{ $penerimaan->nomor_invoice ?? '-' }}</div></div>
                            <div class="def-item"><div class="k">Mitra / Pelanggan</div><div class="v">{{ $penerimaan->mitra?->nama_pihak ?? '-' }}</div></div>
                            <div class="def-item"><div class="k">NPWP Mitra</div><div class="v">{{ $penerimaan->mitra?->npwp ?? '-' }}</div></div>
                            <div class="def-item"><div class="k">No. Telepon Mitra</div><div class="v">{{ $penerimaan->mitra?->no_telepon ?? '-' }}</div></div>
                            <div class="def-item"><div class="k">Tanggal Invoice</div><div class="v">{{ optional($penerimaan->tanggal_invoice)->translatedFormat('d F Y') ?? '-' }}</div></div>
                            <div class="def-item"><div class="k">Jatuh Tempo</div><div class="v">{{ optional($penerimaan->tanggal_jatuh_tempo)->translatedFormat('d F Y') ?? '-' }}</div></div>
                            <div class="def-item"><div class="k">Status Pembayaran</div><div class="v">@include('pembukuan.partials.status-badge', ['value' => $penerimaan->status_pembayaran])</div></div>
                            <div class="def-item span-all"><div class="k">Akun Penerimaan (COA)</div><div class="v">{{ $penerimaan->coa?->kode_akun ?? $penerimaan->coa?->kode ?? '-' }} — {{ $penerimaan->coa?->nama_akun ?? $penerimaan->coa?->uraian ?? '-' }}</div></div>
                        </div>
                        @if(!empty($penerimaan->keterangan))
                            <hr class="divider-soft">
                            <div class="def-item span-all"><div class="k">Keterangan</div><div class="v">{{ $penerimaan->keterangan }}</div></div>
                        @endif
                    </div>
                </div>

                @if($penerimaan->mitra)
                    <div class="card book-card mb-4 bku-anim bku-d4">
                        <div class="card-header">
                            <span class="sec-ic emerald"><i class="bi bi-building"></i></span>
                            <h6 class="mb-0 fw-bold">Profil Mitra / Pelanggan</h6>
                        </div>
                        <div class="card-body">
                            <div class="def-grid">
                                <div class="def-item"><div class="k">Nama</div><div class="v">{{ $penerimaan->mitra->nama_pihak ?? '-' }}</div></div>
                                <div class="def-item"><div class="k">Penanggung Jawab</div><div class="v">{{ $penerimaan->mitra->nama_penanggung_jawab ?? '-' }}</div></div>
                                <div class="def-item"><div class="k">NPWP</div><div class="v">{{ $penerimaan->mitra->npwp ?? '-' }}</div></div>
                                <div class="def-item"><div class="k">Email</div><div class="v">{{ $penerimaan->mitra->email ?? '-' }}</div></div>
                                <div class="def-item"><div class="k">No. Telepon</div><div class="v">{{ $penerimaan->mitra->no_telepon ?? '-' }}</div></div>
                                <div class="def-item span-all"><div class="k">Alamat</div><div class="v">{{ $penerimaan->mitra->alamat ?? '-' }}</div></div>
                            </div>
                        </div>
                    </div>
                @endif
            @endif

            {{-- ============ KREDIT KELUAR → Tagihan Pengeluaran ============ --}}
            @if(!$isDebit && $tagihan)
                <div class="card book-card mb-4 bku-anim bku-d3">
                    <div class="card-header">
                        <div class="d-flex align-items-center gap-2">
                            <span class="sec-ic rose"><i class="bi {{ $jenisIcon }}"></i></span>
                            <div>
                                <h6 class="mb-0 fw-bold">Detail Tagihan Pengeluaran</h6>
                                <small class="text-muted">Rincian tagihan beserta dokumen pencairannya.</small>
                            </div>
                        </div>
                        <span class="type-badge ms-auto"><i class="bi {{ $jenisIcon }}"></i>{{ $jenisPengeluaran }}</span>
                    </div>
                    <div class="card-body">
                        <div class="amount-banner rose">
                            <div>
                                <div class="k text-muted small fw-bold text-uppercase" style="letter-spacing:.05em;">Total Netto (Dibayar)</div>
                                <div class="ab-amount rose">Rp {{ number_format($tagihan->total_netto ?? 0, 0, ',', '.') }}</div>
                            </div>
                            <div class="text-end">
                                <div class="small text-muted">Bruto Rp {{ number_format($tagihan->total_bruto ?? 0, 0, ',', '.') }}</div>
                                <div class="small text-muted">Potongan Rp {{ number_format($tagihan->total_potongan ?? 0, 0, ',', '.') }}</div>
                            </div>
                        </div>

                        <div class="def-grid mb-2">
                            <div class="def-item"><div class="k">Nomor Tagihan</div><div class="v">{{ $tagihan->nomor_tagihan ?? '-' }}</div></div>
                            <div class="def-item"><div class="k">Jenis Tagihan</div><div class="v">{{ $jenisPengeluaran }}</div></div>
                            <div class="def-item"><div class="k">Pihak / Penerima</div><div class="v">{{ $tagihan->pihak?->nama_pihak ?? '-' }}</div></div>
                            <div class="def-item"><div class="k">NPWP</div><div class="v">{{ $tagihan->pihak?->npwp ?? '-' }}</div></div>
                            <div class="def-item"><div class="k">Mekanisme Pembayaran</div><div class="v">{{ $tagihan->mekanisme_pembayaran?->value ?? '-' }}</div></div>
                            <div class="def-item"><div class="k">Status</div><div class="v">@include('pembukuan.partials.status-badge', ['value' => $tagihan->status])</div></div>
                            <div class="def-item"><div class="k">DIPA</div><div class="v">{{ $tagihan->dipa?->nomor_dipa ?? $tagihan->dipa?->tahun_anggaran ?? '-' }}</div></div>
                            <div class="def-item"><div class="k">Dibuat Oleh</div><div class="v">{{ $tagihan->creator?->name ?? '-' }}</div></div>
                        </div>
                        @if(!empty($tagihan->deskripsi))
                            <div class="def-item span-all"><div class="k">Deskripsi</div><div class="v">{{ $tagihan->deskripsi }}</div></div>
                        @endif

                        {{-- Rincian spesifik per jenis --}}
                        @if($detailKontrak)
                            <hr class="divider-soft">
                            <div class="group-title mb-2"><span class="gt-ic"><i class="bi bi-file-earmark-ruled"></i></span>Rincian Kontrak / Pengadaan</div>
                            <div class="sub-card">
                                @if($detailKontrak->kontrakTermin)
                                    <div class="mb-2"><span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill">Termin: {{ $detailKontrak->kontrakTermin->nomor_termin ?? $detailKontrak->kontrakTermin->termin_ke ?? $detailKontrak->kontrakTermin->id }}</span></div>
                                @endif
                                <div class="def-grid">
                                    @foreach($dumpAttrs($detailKontrak) as $k => $v)
                                        <div class="def-item"><div class="k">{{ Str::headline($k) }}</div><div class="v">{{ $fmtVal($k, $v) }}</div></div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        @if($detailPerjaldin->isNotEmpty())
                            <hr class="divider-soft">
                            <div class="group-title mb-2"><span class="gt-ic"><i class="bi bi-airplane"></i></span>Rincian Perjalanan Dinas <span class="badge bg-light text-dark border ms-1">{{ $detailPerjaldin->count() }} pelaksana</span></div>
                            @foreach($detailPerjaldin as $pd)
                                <div class="sub-card" style="animation-delay: {{ $loop->index * .06 }}s;">
                                    <div class="sc-head">
                                        <span class="fw-semibold"><i class="bi bi-person-badge me-1 text-danger"></i>{{ $pd->pegawai?->nama_lengkap ?? $pd->pegawai?->nama ?? 'Pelaksana #'.$loop->iteration }}</span>
                                        @if($pd->provinsi)<span class="small text-muted"><i class="bi bi-geo-alt me-1"></i>{{ $pd->provinsi->nama_provinsi ?? $pd->provinsi->provinsi ?? '' }}</span>@endif
                                    </div>
                                    <div class="def-grid">
                                        @foreach($dumpAttrs($pd) as $k => $v)
                                            <div class="def-item"><div class="k">{{ Str::headline($k) }}</div><div class="v">{{ $fmtVal($k, $v) }}</div></div>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                            @if($komponenPerjaldin->isNotEmpty())
                                <div class="sub-card">
                                    <div class="fw-semibold mb-2"><i class="bi bi-list-check me-1 text-danger"></i>Komponen Biaya</div>
                                    <div class="table-responsive">
                                        <table class="table table-sm align-middle mb-0 bku-table">
                                            <tbody>
                                                @foreach($komponenPerjaldin as $kmp)
                                                    <tr>
                                                        <td>{{ $kmp->nama_komponen ?? $kmp->komponen ?? $kmp->uraian ?? 'Komponen' }}</td>
                                                        <td class="text-end fw-semibold">Rp {{ number_format($kmp->jumlah ?? $kmp->nominal ?? $kmp->total ?? 0, 0, ',', '.') }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            @endif
                        @endif

                        @if($detailHonorarium->isNotEmpty())
                            <hr class="divider-soft">
                            <div class="group-title mb-2"><span class="gt-ic"><i class="bi bi-people"></i></span>Rincian Honorarium <span class="badge bg-light text-dark border ms-1">{{ $detailHonorarium->count() }} penerima</span></div>
                            @foreach($detailHonorarium as $dh)
                                <div class="sub-card" style="animation-delay: {{ $loop->index * .06 }}s;">
                                    <div class="def-grid">
                                        @foreach($dumpAttrs($dh) as $k => $v)
                                            <div class="def-item"><div class="k">{{ Str::headline($k) }}</div><div class="v">{{ $fmtVal($k, $v) }}</div></div>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        @endif

                        {{-- Potongan / Pajak --}}
                        @if($potongan->isNotEmpty())
                            <hr class="divider-soft">
                            <div class="group-title mb-2"><span class="gt-ic"><i class="bi bi-percent"></i></span>Potongan & Pajak</div>
                            <div class="table-responsive">
                                <table class="table table-sm align-middle mb-0 bku-table">
                                    <thead class="table-light">
                                        <tr><th>Jenis</th><th>Tarif</th><th class="text-end">Nominal</th></tr>
                                    </thead>
                                    <tbody>
                                        @foreach($potongan as $pt)
                                            <tr>
                                                <td>{{ $pt->pajak?->jenis_pajak ?? $pt->nama_pajak_snapshot ?? $pt->jenis_potongan ?? $pt->deskripsi ?? 'Potongan' }}</td>
                                                <td>{{ $pt->persentase_tarif_snapshot ? rtrim(rtrim(number_format($pt->persentase_tarif_snapshot, 2, ',', '.'), '0'), ',').'%' : '-' }}</td>
                                                <td class="text-end fw-semibold">Rp {{ number_format($pt->nominal_potongan ?? $pt->nominal ?? $pt->jumlah ?? 0, 0, ',', '.') }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr class="fw-bold">
                                            <td colspan="2" class="text-end">Total Potongan</td>
                                            <td class="text-end text-danger">Rp {{ number_format($potongan->sum(fn($p) => (float)($p->nominal_potongan ?? $p->nominal ?? 0)), 0, ',', '.') }}</td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Dokumen Pencairan (SPP → SPM → NPI → SP2D) --}}
                <div class="card book-card mb-4 bku-anim bku-d4">
                    <div class="card-header">
                        <span class="sec-ic rose"><i class="bi bi-diagram-3"></i></span>
                        <h6 class="mb-0 fw-bold">Alur Dokumen Pencairan</h6>
                    </div>
                    <div class="card-body">
                        <div class="doc-chain">
                            @php
                                $chain = [
                                    ['SPP',  $docChain['spp'] ?? null,  'nomor_spp',  'tanggal_spp'],
                                    ['SPM',  $docChain['spm'] ?? null,  'nomor_spm',  'tanggal_spm'],
                                    ['NPI',  $docChain['npi'] ?? null,  'nomor_npi',  'tanggal_npi'],
                                    ['SP2D', $docChain['sp2d'] ?? null, 'nomor_sp2d', 'tanggal_sp2d'],
                                ];
                            @endphp
                            @foreach($chain as $i => [$name, $doc, $noField, $tglField])
                                <div class="doc-step {{ $doc ? 'done' : '' }}" style="animation-delay: {{ $i * .08 }}s;">
                                    @if($doc)<i class="bi bi-check-circle-fill step-check"></i>@endif
                                    <div class="step-name">{{ $name }}</div>
                                    <div class="step-no">{{ $doc?->{$noField} ?? '—' }}</div>
                                    <div class="step-date">{{ $doc && $doc->{$tglField} ? Carbon::parse($doc->{$tglField})->translatedFormat('d M Y') : 'Belum terbit' }}</div>
                                </div>
                                @if(!$loop->last)
                                    <div class="doc-arrow"><i class="bi bi-chevron-right"></i></div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            @if(!$tagihan && !$penerimaan)
                <div class="card book-card mb-4 bku-anim bku-d3">
                    <div class="card-body">
                        <div class="book-empty">
                            <i class="bi bi-inbox"></i>
                            <div class="fw-semibold">Transaksi Manual / Sistem</div>
                            <div class="small mt-1">Transaksi ini tidak tertaut ke tagihan pengeluaran maupun penagihan penerimaan.</div>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        {{-- ================= RIGHT ================= --}}
        <div class="col-xl-4">

            {{-- Verifikator (khusus pengeluaran) --}}
            @if(!$isDebit && $tagihan)
                <div class="card book-card mb-4 bku-anim bku-d4">
                    <div class="card-header">
                        <span class="sec-ic"><i class="bi bi-person-check"></i></span>
                        <h6 class="mb-0 fw-bold">Pejabat & Verifikator</h6>
                    </div>
                    <div class="card-body">
                        @php
                            $pejabat = [
                                ['PPK', $tagihan->ppk_nama_snapshot ?? $tagihan->ppkUser?->name],
                                ['PPSPM', $tagihan->ppspm_nama_snapshot ?? $tagihan->ppspmUser?->name],
                                ['Bendahara Pengeluaran', $tagihan->bendahara_pengeluaran_nama_snapshot ?? $tagihan->bendaharaPengeluaranUser?->name],
                                ['Koordinator Keuangan', $tagihan->koordinator_keuangan_nama_snapshot ?? $tagihan->koordinatorKeuanganUser?->name],
                                ['Kasubbag', $tagihan->kasubbag_nama_snapshot ?? $tagihan->kasubbagUser?->name],
                            ];
                            $inisial = function ($nama) {
                                if (!$nama) return '?';
                                $parts = preg_split('/\s+/', trim($nama));
                                return strtoupper(mb_substr($parts[0],0,1) . (isset($parts[1]) ? mb_substr($parts[1],0,1) : ''));
                            };
                        @endphp
                        @foreach($pejabat as [$peran, $nama])
                            <div class="pejabat-row">
                                <span class="pejabat-av {{ $nama ? '' : 'muted' }}">{{ $inisial($nama) }}</span>
                                <div class="flex-grow-1">
                                    <div class="small text-muted fw-semibold">{{ $peran }}</div>
                                    <div class="fw-semibold">{{ $nama ?: '—' }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Rekening sumber/tujuan --}}
            @php
                $rek = $entry->sumberRekening;
                $rekJenis = $rek?->jenis_rekening;
                $rekJenisLabel = $rekJenis instanceof \App\Enums\JenisRekening
                    ? Str::headline(strtolower($rekJenis->value))
                    : ($rekJenis ? Str::headline(strtolower((string) $rekJenis)) : null);
                $rekPemilik = $rek?->pemilik;
                $rekPemilikNama = $rekPemilik?->name
                    ?? $rekPemilik?->nama_lengkap
                    ?? $rekPemilik?->nama_pihak
                    ?? $rekPemilik?->nama_mitra
                    ?? null;
            @endphp
            <div class="card book-card mb-4 bku-anim bku-d5">
                <div class="card-header">
                    <span class="sec-ic {{ $isDebit ? 'emerald' : 'rose' }}"><i class="bi bi-bank"></i></span>
                    <h6 class="mb-0 fw-bold">{{ $isDebit ? 'Rekening Penerima (BLU)' : 'Rekening Pembayar' }}</h6>
                </div>
                <div class="card-body">
                    <div class="rek-acct {{ $isDebit ? 'emerald' : 'rose' }} mb-3">
                        <div class="rek-bank">
                            <i class="bi bi-credit-card-2-front me-2"></i>{{ $rek?->nama_bank ?? '-' }}
                            @if($rekJenisLabel)<span class="rek-tag">{{ $rekJenisLabel }}</span>@endif
                        </div>
                        <div class="rek-no">{{ $rek?->nomor_rekening ?? '-' }}</div>
                        <div class="rek-name"><i class="bi bi-person-fill me-1"></i>{{ $rek?->nama_rekening ?? '-' }}</div>
                    </div>
                    <div class="d-flex flex-column gap-2">
                        @if($rekPemilikNama)
                            <div class="d-flex justify-content-between">
                                <span class="small text-muted fw-semibold">Pemilik Rekening</span>
                                <span class="fw-semibold text-end">{{ $rekPemilikNama }}</span>
                            </div>
                        @endif
                        @if($isDebit && $penerimaan)
                            <div class="d-flex justify-content-between">
                                <span class="small text-muted fw-semibold">Disetor Oleh</span>
                                <span class="fw-semibold text-end">{{ $penerimaan->mitra?->nama_pihak ?? '-' }}</span>
                            </div>
                        @endif
                        <div class="d-flex justify-content-between">
                            <span class="small text-muted fw-semibold">Status Rekening</span>
                            <span class="fw-semibold text-end">{{ $rek?->status_aktif ? 'Aktif' : 'Nonaktif' }}</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Keterkaitan Penagihan Jasa (khusus debit masuk) --}}
            @if($isDebit && ($tagihanJasa || $penerimaan))
                <div class="card book-card mb-4 bku-anim bku-d5">
                    <div class="card-header">
                        <span class="sec-ic emerald"><i class="bi bi-link-45deg"></i></span>
                        <h6 class="mb-0 fw-bold">Keterkaitan Penagihan Jasa</h6>
                    </div>
                    <div class="card-body">
                        <div class="d-flex flex-column gap-2">
                            <div class="d-flex justify-content-between">
                                <span class="small text-muted fw-semibold">Nomor Tagihan</span>
                                <span class="fw-semibold text-end">{{ $tagihanJasa->nomor_tagihan ?? $penerimaan->nomor_invoice ?? '-' }}</span>
                            </div>
                            @if($tagihanJasa)
                                <div class="d-flex justify-content-between">
                                    <span class="small text-muted fw-semibold">Status Tagihan</span>
                                    <span class="text-end">@include('pembukuan.partials.status-badge', ['value' => $tagihanJasa->status ?? $tagihanJasa->status_pembayaran])</span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span class="small text-muted fw-semibold">Tgl Tagihan</span>
                                    <span class="fw-semibold text-end">{{ optional($tagihanJasa->tanggal_tagihan)->translatedFormat('d M Y') ?? '-' }}</span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span class="small text-muted fw-semibold">Tgl Lunas</span>
                                    <span class="fw-semibold text-end">{{ optional($tagihanJasa->tanggal_lunas)->translatedFormat('d M Y') ?? '-' }}</span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            {{-- Log & Keterkaitan --}}
            <div class="card book-card bku-anim bku-d6">
                <div class="card-header">
                    <span class="sec-ic"><i class="bi bi-clock-history"></i></span>
                    <h6 class="mb-0 fw-bold">{{ $isDebit ? 'Log Penagihan Jasa' : 'Log & Keterkaitan' }}</h6>
                </div>
                <div class="card-body">
                    @if($relatedLogs->isEmpty())
                        <div class="book-empty">
                            <i class="bi bi-inbox"></i>
                            <div class="fw-semibold">Belum ada log tambahan</div>
                            <div class="small mt-1">
                                {{ $isDebit
                                    ? 'Penagihan jasa terkait belum memiliki riwayat log yang terekam.'
                                    : 'Transaksi ini belum memiliki log dokumen atau keterkaitan lain yang terekam.' }}
                            </div>
                        </div>
                    @else
                        <div class="det-timeline">
                            @foreach($relatedLogs as $log)
                                <div class="det-tl-item" style="animation-delay: {{ $loop->index * .06 }}s;">
                                    <div class="det-tl-dot"></div>
                                    <div class="fw-semibold">{{ $log->aksi ?? $log->status_baru ?? 'LOG' }}</div>
                                    <div class="small text-muted">
                                        {{ $log->user?->name ?? $log->role_saat_itu ?? 'Sistem' }}
                                        · {{ optional($log->created_at)->translatedFormat('d M Y H:i') }}
                                    </div>
                                    @if(!empty($log->catatan))
                                        <div class="small mt-1">{{ $log->catatan }}</div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
