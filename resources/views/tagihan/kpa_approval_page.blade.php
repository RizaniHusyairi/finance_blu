@extends('layouts.app')

@section('title', 'Persetujuan KPA — ' . $tagihan->nomor_tagihan)

@push('css')
<style>
    /* ════════════════════════════════════════════════════════════
       HALAMAN PERSETUJUAN KPA — "DECISION ROOM"
       Hero aurora gelap, kartu kaca, reveal-on-scroll, count-up,
       pipeline verifikator animasi, dock keputusan sticky.
       ════════════════════════════════════════════════════════════ */
    body { background: #f1f5f9; }
    .kpa-wrap { --ink:#0f172a; --line:#e2e8f0; --brand:#4f46e5; --brand2:#7c3aed; --ok:#10b981; --warn:#f59e0b; --bad:#ef4444; }
    .kpa-wrap .fs-7 { font-size:.8rem!important; } .kpa-wrap .fs-8 { font-size:.7rem!important; }
    .kpa-mono { font-family:'JetBrains Mono',SFMono-Regular,Menlo,Consolas,monospace; letter-spacing:-.02em; }

    @keyframes kpaAurora { 0%{background-position:0% 50%} 50%{background-position:100% 50%} 100%{background-position:0% 50%} }
    @keyframes kpaUp { from{opacity:0;transform:translateY(28px)} to{opacity:1;transform:none} }
    @keyframes kpaPop { 0%{transform:scale(.4);opacity:0} 70%{transform:scale(1.12)} 100%{transform:scale(1);opacity:1} }
    @keyframes kpaFloat { 0%,100%{transform:translateY(0)} 50%{transform:translateY(-12px)} }
    @keyframes kpaPulse { 0%,100%{box-shadow:0 0 0 0 rgba(245,158,11,.5)} 50%{box-shadow:0 0 0 12px rgba(245,158,11,0)} }
    @keyframes kpaShine { 0%{transform:translateX(-130%) skewX(-18deg)} 100%{transform:translateX(230%) skewX(-18deg)} }
    @keyframes kpaDash { to{stroke-dashoffset:0} }

    /* ── Hero ── */
    .kpa-hero {
        position:relative; overflow:hidden; color:#fff;
        border-radius:1.6rem; padding:2.6rem 2.4rem 2.3rem; margin-bottom:1.4rem;
        background:linear-gradient(-45deg,#020617,#1e1b4b,#312e81,#065f46,#1e1b4b);
        background-size:480% 480%;
        animation:kpaAurora 18s ease infinite, kpaPop .55s cubic-bezier(.16,1,.3,1) both;
        box-shadow:0 28px 60px -22px rgba(2,6,23,.65);
    }
    .kpa-hero::before, .kpa-hero::after {
        content:''; position:absolute; border-radius:50%; pointer-events:none;
        background:radial-gradient(circle, rgba(255,255,255,.14) 0%, transparent 70%);
    }
    .kpa-hero::before { width:380px;height:380px; top:-50%; left:-5%; animation:kpaFloat 10s ease-in-out infinite; }
    .kpa-hero::after { width:300px;height:300px; bottom:-60%; right:-4%; animation:kpaFloat 13s ease-in-out infinite reverse; }
    .kpa-hero .grid {
        position:absolute; inset:0; opacity:.13; pointer-events:none;
        background-image:linear-gradient(rgba(255,255,255,.4) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.4) 1px,transparent 1px);
        background-size:46px 46px;
        mask-image:radial-gradient(ellipse at 25% 0%, #000 5%, transparent 70%);
    }
    .kpa-chip {
        display:inline-flex; align-items:center; gap:.45rem;
        background:rgba(255,255,255,.14); border:1px solid rgba(255,255,255,.3);
        backdrop-filter:blur(8px); border-radius:999px; padding:.4rem 1rem;
        font-weight:700; font-size:.76rem; letter-spacing:.5px;
    }
    .kpa-chip .dot { width:8px;height:8px;border-radius:50%;background:currentColor; }
    .kpa-chip.wait { background:rgba(245,158,11,.28); border-color:rgba(253,230,138,.65); }
    .kpa-chip.wait .dot { animation:kpaPulse 2s infinite; }
    .kpa-chip.ok { background:rgba(16,185,129,.28); border-color:rgba(110,231,183,.65); }
    .kpa-chip.bad { background:rgba(239,68,68,.32); border-color:rgba(252,165,165,.65); }
    .kpa-amount { font-size:clamp(2rem,4.4vw,3rem); font-weight:800; letter-spacing:-1.5px; line-height:1.05; text-shadow:0 6px 22px rgba(0,0,0,.35); font-variant-numeric:tabular-nums; }
    .kpa-hero-stat { border-left:3px solid rgba(255,255,255,.3); padding-left:.9rem; }
    .kpa-hero-stat .l { font-size:.66rem; letter-spacing:1.2px; text-transform:uppercase; color:rgba(255,255,255,.65); font-weight:700; }
    .kpa-hero-stat .v { font-weight:800; font-size:1rem; font-variant-numeric:tabular-nums; }

    /* ── Kartu seksi ── */
    .kpa-card {
        background:#fff; border:1px solid var(--line); border-radius:1.25rem;
        box-shadow:0 12px 34px -18px rgba(15,23,42,.16);
        transition:transform .35s cubic-bezier(.16,1,.3,1), box-shadow .35s;
        margin-bottom:1.3rem; overflow:hidden;
    }
    .kpa-card:hover { transform:translateY(-3px); box-shadow:0 22px 44px -20px rgba(79,70,229,.22); }
    .kpa-card-head { display:flex; align-items:center; gap:.85rem; padding:1.1rem 1.4rem; border-bottom:1px solid #f1f5f9; }
    .kpa-card-head .ic {
        width:42px;height:42px;border-radius:.9rem; display:flex;align-items:center;justify-content:center;
        font-size:1.15rem; color:var(--tone,#4f46e5); background:var(--tone-soft,rgba(79,70,229,.1));
        transition:transform .35s cubic-bezier(.34,1.56,.64,1);
    }
    .kpa-card:hover .ic { transform:scale(1.12) rotate(-6deg); }
    .kpa-card-head h6 { margin:0; font-weight:800; color:var(--ink); }
    .kpa-card-head .sub { font-size:.74rem; color:#64748b; }
    .kpa-card-body { padding:1.25rem 1.4rem; }

    /* ── Reveal ── */
    .kpa-reveal { opacity:0; transform:translateY(26px); transition:opacity .7s cubic-bezier(.16,1,.3,1), transform .7s cubic-bezier(.16,1,.3,1); }
    .kpa-reveal.in { opacity:1; transform:none; }
    @media (prefers-reduced-motion: reduce) {
        .kpa-reveal { opacity:1!important; transform:none!important; transition:none!important; }
        * { animation-duration:.001s!important; animation-iteration-count:1!important; }
    }

    /* ── Pipeline verifikator ── */
    .kpa-verif { display:flex; flex-wrap:wrap; gap:.6rem; }
    .kpa-verif .node {
        display:flex; align-items:center; gap:.55rem;
        border:1.5px solid var(--line); border-radius:999px; padding:.4rem .95rem .4rem .45rem;
        background:#f8fafc; transition:all .3s;
    }
    .kpa-verif .node.show { animation:kpaPop .5s cubic-bezier(.34,1.56,.64,1) both; }
    .kpa-verif .node:hover { transform:translateY(-2px); border-color:#a5b4fc; background:#eef2ff; }
    .kpa-verif .ava {
        width:30px;height:30px;border-radius:50%; display:flex;align-items:center;justify-content:center; font-size:.85rem;
    }
    .kpa-verif .node.ok .ava { background:#d1fae5; color:#059669; }
    .kpa-verif .node.idle .ava { background:#e2e8f0; color:#94a3b8; }
    .kpa-verif .nm { font-weight:700; font-size:.76rem; color:var(--ink); line-height:1.1; }
    .kpa-verif .rl { font-size:.62rem; color:#64748b; }
    .kpa-verif .tm { font-size:.6rem; color:#94a3b8; }

    /* ── Tabel rincian ── */
    .kpa-table { width:100%; border-collapse:separate; border-spacing:0; }
    .kpa-table th { font-size:.66rem; letter-spacing:1px; text-transform:uppercase; color:#64748b; padding:.55rem .8rem; background:#f8fafc; border-bottom:1px solid var(--line); }
    .kpa-table td { padding:.7rem .8rem; font-size:.82rem; border-bottom:1px solid #f1f5f9; vertical-align:middle; }
    .kpa-table tbody tr { transition:background .2s, transform .2s; }
    .kpa-table tbody tr:hover { background:#f8faff; }
    .kpa-table .num { text-align:right; font-variant-numeric:tabular-nums; font-weight:700; white-space:nowrap; }

    /* ── Nominal flow ── */
    .kpa-money-row { display:flex; justify-content:space-between; align-items:center; padding:.65rem 0; border-bottom:1px dashed #e2e8f0; font-size:.88rem; }
    .kpa-money-final {
        margin-top:.8rem; border-radius:1rem; padding:1.1rem 1.3rem; color:#fff;
        background:linear-gradient(120deg,#047857,#10b981); position:relative; overflow:hidden;
        display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:.5rem;
        box-shadow:0 14px 30px -14px rgba(16,185,129,.5);
    }
    .kpa-money-final::after {
        content:''; position:absolute; top:0; bottom:0; width:46%;
        background:linear-gradient(105deg,transparent,rgba(255,255,255,.28),transparent);
        animation:kpaShine 3.2s ease-in-out infinite;
    }
    .kpa-terbilang { font-size:.72rem; font-style:italic; color:#475569; margin-top:.6rem; }

    /* ── Dokumen grid ── */
    .kpa-doc {
        display:flex; align-items:center; gap:.8rem; border:1.5px solid var(--line); border-radius:1rem;
        padding:.8rem .95rem; background:#fff; text-decoration:none; height:100%;
        transition:all .3s cubic-bezier(.16,1,.3,1);
    }
    .kpa-doc:hover { transform:translateY(-4px) scale(1.015); border-color:#a5b4fc; box-shadow:0 16px 30px -16px rgba(79,70,229,.35); }
    .kpa-doc .dic { width:40px;height:40px; border-radius:.8rem; display:flex;align-items:center;justify-content:center; font-size:1.1rem; flex-shrink:0; }
    .kpa-doc .t { font-weight:700; font-size:.78rem; color:var(--ink); }
    .kpa-doc .s { font-size:.64rem; color:#94a3b8; }
    .kpa-doc .go { margin-left:auto; color:#cbd5e1; transition:all .3s; }
    .kpa-doc:hover .go { color:var(--brand); transform:translateX(3px); }

    /* ── Dock keputusan ── */
    .kpa-dock { position:sticky; top:90px; }
    .kpa-dock-card {
        background:linear-gradient(160deg,#0f172a,#1e1b4b); color:#fff;
        border-radius:1.4rem; padding:1.5rem; overflow:hidden; position:relative;
        box-shadow:0 26px 50px -20px rgba(30,27,75,.6);
        animation:kpaUp .6s .25s cubic-bezier(.16,1,.3,1) both;
    }
    .kpa-dock-card .glow { position:absolute; width:240px;height:240px; border-radius:50%; top:-35%; right:-20%; background:radial-gradient(circle, rgba(99,102,241,.4), transparent 70%); pointer-events:none; }
    .kpa-dock textarea {
        background:rgba(255,255,255,.08); border:1px solid rgba(255,255,255,.18); color:#fff;
        border-radius:.9rem; resize:none; font-size:.85rem;
    }
    .kpa-dock textarea:focus { background:rgba(255,255,255,.12); color:#fff; border-color:#818cf8; box-shadow:0 0 0 .2rem rgba(129,140,248,.2); }
    .kpa-dock textarea::placeholder { color:rgba(255,255,255,.4); }
    .btn-kpa-approve {
        position:relative; overflow:hidden; border:0; width:100%;
        background:linear-gradient(120deg,#059669,#10b981); color:#fff; font-weight:800;
        border-radius:.95rem; padding:.85rem; font-size:.95rem;
        box-shadow:0 12px 26px -10px rgba(16,185,129,.6); transition:transform .2s, box-shadow .2s;
    }
    .btn-kpa-approve:hover { transform:translateY(-2px); color:#fff; box-shadow:0 18px 32px -12px rgba(16,185,129,.7); }
    .btn-kpa-approve::after { content:''; position:absolute; top:0; bottom:0; width:40%; background:linear-gradient(105deg,transparent,rgba(255,255,255,.35),transparent); animation:kpaShine 2.8s ease-in-out infinite; }
    .btn-kpa-reject {
        width:100%; background:transparent; border:1.5px solid rgba(252,165,165,.55); color:#fca5a5;
        font-weight:700; border-radius:.95rem; padding:.7rem; font-size:.88rem; transition:all .25s;
    }
    .btn-kpa-reject:hover { background:rgba(239,68,68,.16); color:#fecaca; border-color:#f87171; }

    /* ── Modal konfirmasi kustom ── */
    .kpa-modal-bg {
        position:fixed; inset:0; z-index:1080; background:rgba(2,6,23,.6); backdrop-filter:blur(5px);
        display:none; align-items:center; justify-content:center; padding:1rem;
    }
    .kpa-modal-bg.open { display:flex; animation:kpaUp .001s; }
    .kpa-modal {
        background:#fff; border-radius:1.4rem; max-width:430px; width:100%; padding:1.8rem;
        animation:kpaPop .35s cubic-bezier(.34,1.56,.64,1) both; text-align:center;
    }
    .kpa-modal .big-ic { width:72px;height:72px; border-radius:50%; margin:0 auto 1rem; display:flex;align-items:center;justify-content:center; font-size:2rem; }

    /* ── Hasil keputusan ── */
    .kpa-result { border-radius:1.4rem; padding:1.6rem; color:#fff; position:relative; overflow:hidden; animation:kpaUp .6s .2s both; }
    .kpa-result.ok { background:linear-gradient(150deg,#047857,#10b981); box-shadow:0 24px 46px -18px rgba(16,185,129,.55); }
    .kpa-result.bad { background:linear-gradient(150deg,#b91c1c,#ef4444); box-shadow:0 24px 46px -18px rgba(239,68,68,.5); }
    .kpa-result .wm { position:absolute; right:-18px; bottom:-26px; font-size:7rem; opacity:.16; pointer-events:none; }

    /* ── SVG ring progress verifikasi ── */
    .kpa-ring-wrap { position:relative; width:78px; height:78px; }
    .kpa-ring-wrap svg { transform:rotate(-90deg); }
    .kpa-ring-bg { fill:none; stroke:rgba(255,255,255,.18); stroke-width:7; }
    .kpa-ring-fg { fill:none; stroke:#34d399; stroke-width:7; stroke-linecap:round; stroke-dasharray:201; stroke-dashoffset:var(--off,201); animation:kpaDash 1.4s .5s cubic-bezier(.16,1,.3,1) both; }
    .kpa-ring-txt { position:absolute; inset:0; display:flex; flex-direction:column; align-items:center; justify-content:center; font-weight:800; font-size:.95rem; }
    .kpa-ring-txt small { font-size:.55rem; font-weight:700; color:rgba(255,255,255,.6); letter-spacing:.5px; }
</style>
@endpush

@section('content')
@php
    $status = $tagihan->kpa_approval_status;
    $sudahDiputus = in_array($status, ['APPROVED', 'REJECTED'], true);
    $tipe = $tagihan->tipe_tagihan;
    $tipeLabel = ['KONTRAK' => 'Kontrak Pengadaan', 'PERJALDIN' => 'Perjalanan Dinas', 'HONORARIUM' => 'Honorarium'][$tipe] ?? $tipe;

    $potonganPajak = $tagihan->potonganTagihan ?? collect();
    $netto = (float) ($tagihan->total_netto ?? 0);
    $bruto = (float) ($tagihan->total_bruto ?? 0);
    $terbilang = function_exists('terbilang_rupiah') ? ucwords(strtolower(terbilang_rupiah($netto))) : null;

    // Pipeline verifikator internal (6 verifikator tagihan).
    $approvals = $tagihan->workflowInstance?->approvals
        ?->sortBy([['urutan_step', 'asc'], ['id', 'asc']])->values() ?? collect();
    $approvedCount = $approvals->where('status', 'APPROVED')->count();
    $verifTotal = max($approvals->count(), 1);
    $ringOffset = 201 - round(201 * ($approvedCount / $verifTotal));

    // Sumber dana (COA) — perjaldin per komponen, lainnya tunggal.
    $coaRows = collect();
    if ($tipe === 'PERJALDIN') {
        foreach ($tagihan->komponenPerjaldin ?? collect() as $k) {
            if ($k->dipaRevisionItem) {
                $coaRows->push(['label' => $k->nama_komponen, 'item' => $k->dipaRevisionItem, 'nominal' => (float) $k->total_nominal]);
            }
        }
    } elseif ($tagihan->dipaRevisionItem) {
        $coaRows->push(['label' => 'Pembebanan Tagihan', 'item' => $tagihan->dipaRevisionItem, 'nominal' => $netto]);
    }

    $vendorNama = $tagihan->detailKontrak?->kontrakTermin?->kontrak?->vendor?->nama_pihak ?? $tagihan->pihak?->nama_pihak;
    $kontrak = $tagihan->detailKontrak?->kontrakTermin?->kontrak;
    $mekanisme = $tagihan->mekanisme_pembayaran?->label() ?? str_replace('_', ' ', (string) ($tagihan->mekanisme_pembayaran?->value ?? '-'));

    $tipePerjalananMap = ['luar_kota' => 'Luar Kota', 'dalam_kota_lebih_8_jam' => 'Dalam Kota > 8 Jam', 'diklat' => 'Diklat'];
@endphp

<div class="kpa-wrap container-fluid py-3 px-lg-4">

    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm rounded-4 d-flex align-items-center gap-2"><i class="bi bi-check-circle-fill"></i>{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger border-0 shadow-sm rounded-4 d-flex align-items-center gap-2"><i class="bi bi-exclamation-triangle-fill"></i>{{ session('error') }}</div>
    @endif

    {{-- ════════ HERO ════════ --}}
    <div class="kpa-hero">
        <div class="grid"></div>
        <div class="position-relative d-flex flex-wrap justify-content-between align-items-end gap-4" style="z-index:2;">
            <div style="max-width: 640px;">
                @if($sudahDiputus)
                    <span class="kpa-chip {{ $status === 'APPROVED' ? 'ok' : 'bad' }}">
                        <span class="dot"></span>
                        {{ $status === 'APPROVED' ? 'TELAH DISETUJUI' : 'DITOLAK' }} ·
                        {{ $tagihan->kpa_approved_at ? \Carbon\Carbon::parse($tagihan->kpa_approved_at)->translatedFormat('d M Y H:i') : '' }}
                    </span>
                @else
                    <span class="kpa-chip wait"><span class="dot"></span> MENUNGGU KEPUTUSAN ANDA</span>
                @endif

                <h2 class="fw-bolder text-white mt-3 mb-1" style="letter-spacing:-.5px;">Persetujuan Standing Instruction</h2>
                <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                    <span class="kpa-mono fw-bold" style="color:#a5b4fc;">{{ $tagihan->nomor_tagihan }}</span>
                    <span class="kpa-chip" style="font-size:.66rem; padding:.2rem .7rem;">{{ $tipeLabel }}</span>
                </div>
                <p class="mb-0 fw-medium" style="color:rgba(255,255,255,.85); font-size:.95rem;">
                    <i class="bi bi-quote me-1 opacity-50"></i>{{ $tagihan->deskripsi ?: 'Tanpa uraian.' }}
                </p>

                <div class="d-flex flex-wrap gap-4 mt-4">
                    <div class="kpa-hero-stat">
                        <div class="l">Bruto</div>
                        <div class="v kpa-mono">Rp <span data-count="{{ $bruto }}">0</span></div>
                    </div>
                    <div class="kpa-hero-stat">
                        <div class="l">Potongan</div>
                        <div class="v kpa-mono" style="color:#fca5a5;">- Rp <span data-count="{{ (float) ($tagihan->total_potongan ?? 0) }}">0</span></div>
                    </div>
                    <div class="kpa-hero-stat">
                        <div class="l">{{ $tipe === 'KONTRAK' ? 'Vendor' : ($tipe === 'PERJALDIN' ? 'Peserta' : 'Personel') }}</div>
                        <div class="v">
                            @if($tipe === 'KONTRAK')
                                {{ \Illuminate\Support\Str::limit($vendorNama ?? '-', 26) }}
                            @elseif($tipe === 'PERJALDIN')
                                {{ $tagihan->detailPerjaldin?->count() ?? 0 }} pegawai
                            @else
                                {{ $tagihan->detailHonorarium?->count() ?? 0 }} orang
                            @endif
                        </div>
                    </div>
                    <div class="kpa-hero-stat">
                        <div class="l">Mekanisme</div>
                        <div class="v">{{ $mekanisme }}</div>
                    </div>
                </div>
            </div>

            <div class="text-lg-end d-flex flex-column align-items-lg-end gap-3">
                <div>
                    <div class="fs-8 fw-bold text-uppercase" style="letter-spacing:1.5px; color:rgba(255,255,255,.6);">Netto Dibayarkan</div>
                    <div class="kpa-amount kpa-mono" style="color:#6ee7b7;">Rp <span data-count="{{ $netto }}">0</span></div>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <div class="kpa-ring-wrap">
                        <svg width="78" height="78" viewBox="0 0 78 78">
                            <circle class="kpa-ring-bg" cx="39" cy="39" r="32"></circle>
                            <circle class="kpa-ring-fg" cx="39" cy="39" r="32" style="--off: {{ $ringOffset }};"></circle>
                        </svg>
                        <div class="kpa-ring-txt"><span>{{ $approvedCount }}/{{ $verifTotal }}</span><small>VERIFIKASI</small></div>
                    </div>
                    <div class="text-start" style="font-size:.72rem; color:rgba(255,255,255,.75); max-width:150px;">
                        Tagihan telah melewati <strong class="text-white">{{ $approvedCount }} verifikator internal</strong> sebelum sampai ke Anda.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        {{-- ════════ KOLOM KIRI: DETAIL ════════ --}}
        <div class="col-lg-8">

            {{-- Pipeline verifikator --}}
            <div class="kpa-card kpa-reveal" style="--tone:#10b981; --tone-soft:rgba(16,185,129,.1);">
                <div class="kpa-card-head">
                    <div class="ic"><i class="bi bi-shield-check"></i></div>
                    <div>
                        <h6>Jejak Verifikasi Internal</h6>
                        <div class="sub">Seluruh verifikator yang telah memeriksa tagihan ini sebelum Anda.</div>
                    </div>
                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill ms-auto px-3">{{ $approvedCount }}/{{ $verifTotal }} setuju</span>
                </div>
                <div class="kpa-card-body">
                    <div class="kpa-verif">
                        @forelse($approvals as $appr)
                            <div class="node {{ $appr->status === 'APPROVED' ? 'ok' : 'idle' }}" data-stagger>
                                <div class="ava"><i class="bi {{ $appr->status === 'APPROVED' ? 'bi-check-lg' : 'bi-hourglass-split' }}"></i></div>
                                <div>
                                    <div class="nm">{{ $appr->actedByUser?->name ?? $appr->assignedUser?->name ?? $appr->role_code }}</div>
                                    <div class="rl">{{ $appr->role_code }}
                                        @if($appr->acted_at)
                                            <span class="tm kpa-mono"> · {{ \Carbon\Carbon::parse($appr->acted_at)->format('d/m H:i') }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="text-muted small fst-italic">Riwayat verifikasi tidak tersedia.</div>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- Sumber dana --}}
            @if($coaRows->isNotEmpty())
                <div class="kpa-card kpa-reveal" style="--tone:#4f46e5; --tone-soft:rgba(79,70,229,.1);">
                    <div class="kpa-card-head">
                        <div class="ic"><i class="bi bi-bank"></i></div>
                        <div>
                            <h6>Sumber Dana (COA / DIPA)</h6>
                            <div class="sub">Item anggaran yang akan dibebani pembayaran ini — dipilih PPK.</div>
                        </div>
                    </div>
                    <div class="kpa-card-body p-0">
                        <table class="kpa-table">
                            <thead><tr>
                                <th>Pembebanan</th><th>Kode MAK</th><th>Uraian</th><th class="num">Nominal</th><th class="num">Sisa Pagu</th>
                            </tr></thead>
                            <tbody>
                                @foreach($coaRows as $row)
                                    <tr>
                                        <td class="fw-semibold">{{ $row['label'] }}</td>
                                        <td><span class="kpa-mono fw-bold" style="color:var(--brand); font-size:.76rem;">{{ $row['item']->coa?->kode_mak_lengkap ?? '-' }}</span></td>
                                        <td class="text-secondary" style="font-size:.76rem;">{{ \Illuminate\Support\Str::limit($row['item']->coa?->uraian, 55) }}</td>
                                        <td class="num">Rp {{ number_format($row['nominal'], 0, ',', '.') }}</td>
                                        <td class="num text-success">Rp {{ number_format((float) $row['item']->sisa_pagu, 0, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            {{-- Rincian per tipe --}}
            <div class="kpa-card kpa-reveal" style="--tone:#0891b2; --tone-soft:rgba(8,145,178,.1);">
                <div class="kpa-card-head">
                    <div class="ic"><i class="bi {{ $tipe === 'PERJALDIN' ? 'bi-airplane-engines' : ($tipe === 'HONORARIUM' ? 'bi-people' : 'bi-briefcase') }}"></i></div>
                    <div>
                        <h6>Rincian {{ $tipeLabel }}</h6>
                        <div class="sub">
                            @if($tipe === 'PERJALDIN') Peserta perjalanan dinas beserta biaya masing-masing.
                            @elseif($tipe === 'HONORARIUM') Personel penerima honor periode
                                {{ $tagihan->periode_bulan && $tagihan->periode_tahun ? \Carbon\Carbon::createFromDate($tagihan->periode_tahun, $tagihan->periode_bulan, 1)->translatedFormat('F Y') : '-' }}.
                            @else Informasi kontrak dan termin yang ditagihkan.
                            @endif
                        </div>
                    </div>
                </div>
                <div class="kpa-card-body p-0">
                    @if($tipe === 'PERJALDIN')
                        <table class="kpa-table">
                            <thead><tr><th style="width:36px;">#</th><th>Pegawai</th><th>Tujuan / Tipe</th><th>Berangkat</th><th class="num">Subtotal</th></tr></thead>
                            <tbody>
                                @foreach($tagihan->detailPerjaldin as $i => $d)
                                    @php
                                        $sub = (float)($d->biaya_tiket ?? 0) + (float)($d->biaya_transport ?? 0) + (float)($d->biaya_penginapan ?? 0)
                                             + (float)($d->uang_harian ?? 0) + (float)($d->uang_representasi ?? 0) + (float)($d->uang_rapat ?? 0);
                                    @endphp
                                    <tr>
                                        <td class="text-secondary">{{ $i + 1 }}</td>
                                        <td>
                                            <div class="fw-bold">{{ $d->nama_pegawai ?? $d->pegawai?->nama_lengkap ?? '-' }}</div>
                                            <div class="kpa-mono text-secondary" style="font-size:.66rem;">{{ $d->nip ?? $d->pegawai?->nip ?? '' }}</div>
                                        </td>
                                        <td>
                                            <div style="font-size:.78rem;">{{ $d->tujuan ?: ($d->provinsi?->provinsi ?? '-') }}</div>
                                            <span class="badge bg-light text-secondary border rounded-pill fs-8">{{ $tipePerjalananMap[$d->tipe_perjalanan ?? ''] ?? '-' }}</span>
                                        </td>
                                        <td style="font-size:.78rem;">
                                            {{ $d->tgl_berangkat ? \Carbon\Carbon::parse($d->tgl_berangkat)->translatedFormat('d M Y') : '-' }}
                                            <div class="text-secondary fs-8">{{ $d->lama_hari ?? 0 }} hari</div>
                                        </td>
                                        <td class="num">Rp {{ number_format($sub, 0, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @elseif($tipe === 'HONORARIUM')
                        <div style="max-height:340px; overflow-y:auto;">
                            <table class="kpa-table">
                                <thead><tr><th style="width:36px;">#</th><th>Personel</th><th>Jabatan</th><th class="num">Honor</th><th class="num">PPh 21</th><th class="num">Diterima</th></tr></thead>
                                <tbody>
                                    @foreach($tagihan->detailHonorarium as $i => $d)
                                        <tr>
                                            <td class="text-secondary">{{ $i + 1 }}</td>
                                            <td>
                                                <div class="fw-bold">{{ $d->nama_personel }}</div>
                                                <div class="kpa-mono text-secondary" style="font-size:.66rem;">{{ $d->nrp_nip }}</div>
                                            </td>
                                            <td style="font-size:.76rem;">{{ $d->jabatan ?: '-' }}</td>
                                            <td class="num">Rp {{ number_format((float) $d->nilai_honor, 0, ',', '.') }}</td>
                                            <td class="num text-danger">- Rp {{ number_format((float) $d->pph, 0, ',', '.') }}</td>
                                            <td class="num text-success">Rp {{ number_format((float) $d->nilai_honor - (float) $d->pph, 0, ',', '.') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="kpa-card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="fs-8 text-secondary text-uppercase fw-bold">Vendor / Rekanan</div>
                                    <div class="fw-bold">{{ $vendorNama ?? '-' }}</div>
                                </div>
                                <div class="col-md-6">
                                    <div class="fs-8 text-secondary text-uppercase fw-bold">Nama Pekerjaan</div>
                                    <div class="fw-semibold" style="font-size:.85rem;">{{ $kontrak?->nama_pekerjaan ?? '-' }}</div>
                                </div>
                                <div class="col-md-4">
                                    <div class="fs-8 text-secondary text-uppercase fw-bold">Nomor SPK</div>
                                    <div class="kpa-mono fw-bold" style="font-size:.78rem;">{{ $kontrak?->nomor_spk ?? '-' }}</div>
                                </div>
                                <div class="col-md-4">
                                    <div class="fs-8 text-secondary text-uppercase fw-bold">Nilai Total Kontrak</div>
                                    <div class="fw-bold">Rp {{ number_format((float) ($kontrak?->nilai_total_kontrak ?? 0), 0, ',', '.') }}</div>
                                </div>
                                <div class="col-md-4">
                                    <div class="fs-8 text-secondary text-uppercase fw-bold">Termin Ditagihkan</div>
                                    <div class="fw-semibold" style="font-size:.85rem;">
                                        {{ $tagihan->detailKontrak?->kontrakTermin?->nama_termin
                                            ?? str_replace('_', ' ', (string) ($tagihan->detailKontrak?->kontrakTermin?->jenis_termin ?? '-')) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Rincian nominal --}}
            <div class="kpa-card kpa-reveal" style="--tone:#059669; --tone-soft:rgba(5,150,105,.1);">
                <div class="kpa-card-head">
                    <div class="ic"><i class="bi bi-calculator"></i></div>
                    <div>
                        <h6>Kalkulasi Pembayaran</h6>
                        <div class="sub">Bruto dikurangi potongan/pajak — nilai inilah yang akan ditransfer.</div>
                    </div>
                </div>
                <div class="kpa-card-body">
                    <div class="kpa-money-row">
                        <span class="text-secondary">Nilai Tagihan (Bruto)</span>
                        <span class="fw-bold kpa-mono">Rp {{ number_format($bruto, 0, ',', '.') }}</span>
                    </div>
                    @forelse($potonganPajak as $pot)
                        <div class="kpa-money-row">
                            <span class="text-secondary fs-7"><i class="bi bi-arrow-return-right me-1 text-danger"></i>{{ $pot->nama_pajak_snapshot ?? $pot->deskripsi ?? $pot->jenis_potongan }}</span>
                            <span class="fw-semibold text-danger kpa-mono fs-7">- Rp {{ number_format((float) $pot->nominal_potongan, 0, ',', '.') }}</span>
                        </div>
                    @empty
                        <div class="kpa-money-row"><span class="text-muted fst-italic fs-7">Tidak ada potongan/pajak.</span><span></span></div>
                    @endforelse

                    <div class="kpa-money-final">
                        <div>
                            <div class="fs-8 fw-bold text-uppercase" style="letter-spacing:1px; opacity:.8;">Netto Dibayarkan</div>
                            <div class="fw-bolder fs-3 kpa-mono">Rp <span data-count="{{ $netto }}">0</span></div>
                        </div>
                        <i class="bi bi-send-check" style="font-size:2.2rem; opacity:.5;"></i>
                    </div>
                    @if($terbilang)
                        <div class="kpa-terbilang"><i class="bi bi-blockquote-left me-1"></i><strong>Terbilang:</strong> {{ $terbilang }}</div>
                    @endif
                </div>
            </div>

            {{-- Dokumen pendukung --}}
            <div class="kpa-card kpa-reveal" style="--tone:#d97706; --tone-soft:rgba(217,119,6,.12);">
                <div class="kpa-card-head">
                    <div class="ic"><i class="bi bi-folder2-open"></i></div>
                    <div>
                        <h6>Dokumen Pendukung</h6>
                        <div class="sub">Klik untuk membuka di tab baru sebelum memberi keputusan.</div>
                    </div>
                    <span class="badge bg-light text-secondary border rounded-pill ms-auto px-3">{{ $dokumenItems->count() }} berkas</span>
                </div>
                <div class="kpa-card-body">
                    <div class="row g-2">
                        @forelse($dokumenItems as $item)
                            <div class="col-md-6">
                                <a href="{{ $item['url'] }}" target="_blank" class="kpa-doc" data-stagger>
                                    <div class="dic {{ !empty($item['is_generated']) ? 'bg-info-subtle text-info' : 'bg-danger-subtle text-danger' }}">
                                        <i class="bi {{ !empty($item['is_generated']) ? 'bi-file-earmark-code-fill' : 'bi-file-earmark-pdf-fill' }}"></i>
                                    </div>
                                    <div style="min-width:0;">
                                        <div class="t text-truncate" title="{{ $item['title'] }}">{{ $item['title'] }}</div>
                                        <div class="s">{{ $item['source'] ?? '' }}{{ !empty($item['is_generated']) ? ' · Generate Sistem' : '' }}</div>
                                    </div>
                                    <i class="bi bi-arrow-up-right go"></i>
                                </a>
                            </div>
                        @empty
                            <div class="col-12 text-center text-muted py-3"><i class="bi bi-folder-x fs-3 d-block mb-1"></i>Tidak ada lampiran dokumen.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        {{-- ════════ KOLOM KANAN: DOCK KEPUTUSAN ════════ --}}
        <div class="col-lg-4">
            <div class="kpa-dock">
                @if(! $sudahDiputus)
                    <div class="kpa-dock-card">
                        <div class="glow"></div>
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <i class="bi bi-pen" style="color:#a5b4fc;"></i>
                            <h6 class="fw-bolder mb-0 text-white">Keputusan Anda</h6>
                        </div>
                        <p class="fs-7 mb-3" style="color:rgba(255,255,255,.6);">
                            Dengan menyetujui, Anda menerbitkan Standing Instruction senilai
                            <strong style="color:#6ee7b7;">Rp {{ number_format($netto, 0, ',', '.') }}</strong>
                            dan draft SPP/SPM/NPI akan dibuat otomatis.
                        </p>

                        <form action="{{ route('kpa.approval.process', $tagihan->id) }}" method="POST" id="kpaForm">
                            @csrf
                            <input type="hidden" name="action" id="kpaAction" value="">
                            <textarea name="notes" id="kpaNotes" rows="3" class="form-control mb-2" placeholder="Catatan (opsional untuk persetujuan, wajib bila menolak)..."></textarea>
                            <div class="fs-8 text-danger mb-3 d-none" id="kpaNotesErr"><i class="bi bi-exclamation-circle me-1"></i>Catatan wajib diisi saat menolak tagihan.</div>

                            <button type="button" class="btn-kpa-approve mb-2" data-kpa="approve">
                                <i class="bi bi-check-circle-fill me-1"></i> Setujui &amp; Terbitkan SI
                            </button>
                            <button type="button" class="btn-kpa-reject" data-kpa="reject">
                                <i class="bi bi-x-circle me-1"></i> Tolak Tagihan
                            </button>
                        </form>
                    </div>
                @else
                    <div class="kpa-result {{ $status === 'APPROVED' ? 'ok' : 'bad' }}">
                        <i class="bi {{ $status === 'APPROVED' ? 'bi-patch-check-fill' : 'bi-x-octagon-fill' }} wm"></i>
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <i class="bi {{ $status === 'APPROVED' ? 'bi-check-circle-fill' : 'bi-x-circle-fill' }} fs-4"></i>
                            <h6 class="fw-bolder mb-0 text-white">{{ $status === 'APPROVED' ? 'Tagihan Disetujui' : 'Tagihan Ditolak' }}</h6>
                        </div>
                        <div class="fs-7" style="color:rgba(255,255,255,.85);">
                            Diputuskan {{ $tagihan->kpa_approved_at ? \Carbon\Carbon::parse($tagihan->kpa_approved_at)->translatedFormat('d F Y, H:i') : '-' }}
                        </div>
                        <div class="mt-3 p-3 rounded-3" style="background:rgba(255,255,255,.14); font-size:.8rem;">
                            <div class="fs-8 fw-bold text-uppercase mb-1" style="opacity:.7;">Catatan</div>
                            <em>"{{ $tagihan->kpa_approval_notes ?: 'Tanpa catatan.' }}"</em>
                        </div>
                        @if($status === 'APPROVED')
                            <div class="mt-3 fs-8" style="color:rgba(255,255,255,.8);">
                                <i class="bi bi-magic me-1"></i>Draft SPP/SPM/NPI/SP2D dibuat otomatis dan diproses tim keuangan.
                            </div>
                        @endif
                    </div>
                @endif

                <div class="text-center text-muted fs-8 mt-3">
                    <i class="bi bi-person-badge me-1"></i>Masuk sebagai <strong>{{ $user->name }}</strong> (KPA) · SIKEREN-BLU
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ════════ MODAL KONFIRMASI ════════ --}}
<div class="kpa-modal-bg" id="kpaModalBg">
    <div class="kpa-modal">
        <div class="big-ic" id="kpaModalIc"></div>
        <h5 class="fw-bolder" id="kpaModalTitle"></h5>
        <p class="text-secondary fs-7 mb-1">{{ $tagihan->nomor_tagihan }} — {{ $tipeLabel }}</p>
        <div class="fw-bolder fs-4 kpa-mono mb-3" id="kpaModalAmount">Rp {{ number_format($netto, 0, ',', '.') }}</div>
        <p class="text-secondary fs-7" id="kpaModalDesc"></p>
        <div class="d-flex gap-2 mt-4">
            <button type="button" class="btn btn-light border rounded-pill flex-fill fw-bold" id="kpaModalCancel">Batal</button>
            <button type="button" class="btn rounded-pill flex-fill fw-bold text-white" id="kpaModalConfirm"></button>
        </div>
    </div>
</div>
@endsection

@push('script')
<script>
(function () {
    'use strict';
    var reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    /* ── Reveal on scroll + stagger ── */
    var reveals = document.querySelectorAll('.kpa-reveal');
    function staggerChildren(card) {
        card.querySelectorAll('[data-stagger]').forEach(function (el, i) {
            setTimeout(function () { el.classList.add('show'); el.style.opacity = 1; el.style.transform = 'none'; }, 90 * i);
        });
    }
    if ('IntersectionObserver' in window && !reduced) {
        var io = new IntersectionObserver(function (entries) {
            entries.forEach(function (e) {
                if (e.isIntersecting) { e.target.classList.add('in'); staggerChildren(e.target); io.unobserve(e.target); }
            });
        }, { threshold: 0.12 });
        reveals.forEach(function (el) { io.observe(el); });
    } else {
        reveals.forEach(function (el) { el.classList.add('in'); staggerChildren(el); });
    }

    /* ── Count-up nominal ── */
    var fmt = new Intl.NumberFormat('id-ID');
    document.querySelectorAll('[data-count]').forEach(function (el) {
        var target = parseFloat(el.dataset.count) || 0;
        if (reduced || target === 0) { el.textContent = fmt.format(Math.round(target)); return; }
        var t0 = null, dur = 1300;
        function tick(ts) {
            if (!t0) t0 = ts;
            var p = Math.min((ts - t0) / dur, 1);
            var eased = 1 - Math.pow(1 - p, 4);
            el.textContent = fmt.format(Math.round(target * eased));
            if (p < 1) requestAnimationFrame(tick);
        }
        requestAnimationFrame(tick);
    });

    /* ── Modal keputusan ── */
    var bg = document.getElementById('kpaModalBg');
    if (!bg) return;
    var form = document.getElementById('kpaForm');
    if (!form) return;

    var actionInput = document.getElementById('kpaAction');
    var notes = document.getElementById('kpaNotes');
    var notesErr = document.getElementById('kpaNotesErr');
    var ic = document.getElementById('kpaModalIc');
    var title = document.getElementById('kpaModalTitle');
    var desc = document.getElementById('kpaModalDesc');
    var confirmBtn = document.getElementById('kpaModalConfirm');
    var pending = null;

    function openModal(action) {
        pending = action;
        if (action === 'approve') {
            ic.className = 'big-ic bg-success-subtle text-success'; ic.innerHTML = '<i class="bi bi-check-circle-fill"></i>';
            title.textContent = 'Setujui Standing Instruction?';
            desc.textContent = 'Persetujuan ini final — draft dokumen pencairan (SPP/SPM/NPI) akan langsung dibuat oleh sistem.';
            confirmBtn.className = 'btn btn-success rounded-pill flex-fill fw-bold text-white';
            confirmBtn.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i> Ya, Setujui';
        } else {
            ic.className = 'big-ic bg-danger-subtle text-danger'; ic.innerHTML = '<i class="bi bi-x-octagon-fill"></i>';
            title.textContent = 'Tolak Tagihan Ini?';
            desc.textContent = 'Tagihan dikembalikan dengan catatan Anda dan PPK harus mengajukan ulang setelah diperbaiki.';
            confirmBtn.className = 'btn btn-danger rounded-pill flex-fill fw-bold text-white';
            confirmBtn.innerHTML = '<i class="bi bi-x-circle me-1"></i> Ya, Tolak';
        }
        bg.classList.add('open');
    }
    function closeModal() { bg.classList.remove('open'); pending = null; }

    document.querySelectorAll('[data-kpa]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var action = btn.dataset.kpa;
            notesErr.classList.add('d-none');
            if (action === 'reject' && !notes.value.trim()) {
                notesErr.classList.remove('d-none');
                notes.focus();
                return;
            }
            openModal(action);
        });
    });

    confirmBtn.addEventListener('click', function () {
        if (!pending) return;
        actionInput.value = pending;
        confirmBtn.disabled = true;
        confirmBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Memproses...';
        form.submit();
    });
    document.getElementById('kpaModalCancel').addEventListener('click', closeModal);
    bg.addEventListener('click', function (e) { if (e.target === bg) closeModal(); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeModal(); });
})();
</script>
@endpush
