@extends('layouts.app')

@section('title', 'Dashboard KPA')

@push('css')
<style>
    /* ════════════════════════════════════════════════════════════
       DASHBOARD KPA — "COMMAND CENTER"
       Bento-grid terang, mesh gradient, gauge anggaran conic,
       antrean keputusan dengan urgensi, count-up & reveal.
       ════════════════════════════════════════════════════════════ */
    body { background: #eef1f7; }
    .kd { --ink:#0f172a; --mut:#64748b; --line:#e2e8f0; --brand:#4f46e5; --ok:#10b981; --warn:#f59e0b; --bad:#ef4444; }
    .kd .fs-7{font-size:.8rem!important} .kd .fs-8{font-size:.7rem!important}
    .kd-mono { font-family:'JetBrains Mono',SFMono-Regular,Menlo,Consolas,monospace; letter-spacing:-.02em; font-variant-numeric:tabular-nums; }

    @keyframes kdUp { from{opacity:0;transform:translateY(26px)} to{opacity:1;transform:none} }
    @keyframes kdPop { 0%{transform:scale(.5);opacity:0} 70%{transform:scale(1.08)} 100%{transform:scale(1);opacity:1} }
    @keyframes kdPulse { 0%,100%{box-shadow:0 0 0 0 rgba(245,158,11,.45)} 50%{box-shadow:0 0 0 11px rgba(245,158,11,0)} }
    @keyframes kdBlob { 0%,100%{transform:translate(0,0) scale(1)} 50%{transform:translate(26px,-18px) scale(1.12)} }
    @keyframes kdTick { 0%,100%{opacity:1} 50%{opacity:.25} }
    @keyframes kdSweep { 0%{transform:translateX(-130%) skewX(-16deg)} 100%{transform:translateX(240%) skewX(-16deg)} }

    .kd-reveal { opacity:0; transform:translateY(26px); transition:opacity .7s cubic-bezier(.16,1,.3,1), transform .7s cubic-bezier(.16,1,.3,1); }
    .kd-reveal.in { opacity:1; transform:none; }
    @media (prefers-reduced-motion: reduce) {
        .kd-reveal{opacity:1!important;transform:none!important;transition:none!important}
        *{animation-duration:.001s!important;animation-iteration-count:1!important}
    }

    /* ── Hero mesh ── */
    .kd-hero {
        position:relative; overflow:hidden; border-radius:1.8rem; margin-bottom:1.4rem;
        padding:2.4rem 2.5rem; background:#fff; border:1px solid #fff;
        box-shadow:0 24px 55px -28px rgba(79,70,229,.35);
        animation:kdPop .55s cubic-bezier(.16,1,.3,1) both;
    }
    .kd-hero .blob { position:absolute; border-radius:50%; filter:blur(60px); opacity:.5; pointer-events:none; }
    .kd-hero .b1 { width:340px;height:340px; top:-160px; left:-80px; background:#c7d2fe; animation:kdBlob 11s ease-in-out infinite; }
    .kd-hero .b2 { width:280px;height:280px; bottom:-150px; right:8%; background:#a7f3d0; animation:kdBlob 14s ease-in-out infinite reverse; }
    .kd-hero .b3 { width:200px;height:200px; top:-90px; right:-60px; background:#fde68a; animation:kdBlob 12s 2s ease-in-out infinite; }
    .kd-clock { font-size:2.2rem; font-weight:800; letter-spacing:-1px; color:var(--ink); line-height:1; }
    .kd-clock .sep { animation:kdTick 1s step-start infinite; }
    .kd-badge-role {
        display:inline-flex; align-items:center; gap:.4rem; font-size:.7rem; font-weight:800; letter-spacing:1.2px;
        color:var(--brand); background:rgba(79,70,229,.08); border:1px solid rgba(79,70,229,.25);
        padding:.35rem .9rem; border-radius:999px; text-transform:uppercase;
    }

    /* ── KPI tiles ── */
    .kd-tile {
        position:relative; overflow:hidden; background:#fff; border:1px solid var(--line);
        border-radius:1.3rem; padding:1.25rem 1.35rem; height:100%;
        transition:transform .35s cubic-bezier(.16,1,.3,1), box-shadow .35s;
    }
    .kd-tile:hover { transform:translateY(-5px); box-shadow:0 20px 38px -18px rgba(15,23,42,.22); }
    .kd-tile .tic {
        width:44px;height:44px; border-radius:1rem; display:flex;align-items:center;justify-content:center;
        font-size:1.25rem; margin-bottom:.8rem; transition:transform .35s cubic-bezier(.34,1.56,.64,1);
    }
    .kd-tile:hover .tic { transform:scale(1.15) rotate(-8deg); }
    .kd-tile .lv { font-size:1.55rem; font-weight:800; color:var(--ink); letter-spacing:-.5px; line-height:1.1; }
    .kd-tile .ll { font-size:.7rem; font-weight:700; letter-spacing:1px; text-transform:uppercase; color:var(--mut); }
    .kd-tile .sub { font-size:.72rem; color:var(--mut); margin-top:.25rem; }
    .kd-tile.hot { border-color:#fcd34d; background:linear-gradient(150deg,#fffbeb,#fff); }
    .kd-tile.hot .tic { animation:kdPulse 2.2s infinite; }

    /* ── Kartu seksi ── */
    .kd-card { background:#fff; border:1px solid var(--line); border-radius:1.4rem; overflow:hidden; margin-bottom:1.3rem; }
    .kd-card-head { display:flex; align-items:center; gap:.7rem; padding:1.05rem 1.35rem; border-bottom:1px solid #f1f5f9; }
    .kd-card-head h6 { margin:0; font-weight:800; color:var(--ink); }
    .kd-card-head .sub { font-size:.72rem; color:var(--mut); }

    /* ── Antrean ── */
    .kd-queue-item {
        display:flex; gap:1rem; align-items:center; padding:1rem 1.35rem;
        border-bottom:1px solid #f1f5f9; transition:background .25s, transform .25s;
    }
    .kd-queue-item:last-child { border-bottom:0; }
    .kd-queue-item:hover { background:#f8faff; transform:translateX(4px); }
    .kd-queue-item .qic {
        width:46px;height:46px; flex-shrink:0; border-radius:1rem;
        display:flex;align-items:center;justify-content:center; font-size:1.2rem;
    }
    .kd-age { font-size:.62rem; font-weight:800; border-radius:999px; padding:.18rem .6rem; letter-spacing:.4px; }
    .kd-age.ok { background:#ecfdf5; color:#059669; border:1px solid #a7f3d0; }
    .kd-age.warn { background:#fffbeb; color:#b45309; border:1px solid #fde68a; }
    .kd-age.late { background:#fef2f2; color:#b91c1c; border:1px solid #fecaca; animation:kdPulse 2.4s infinite; }
    .btn-kd-review {
        position:relative; overflow:hidden; white-space:nowrap;
        background:linear-gradient(120deg,#4f46e5,#7c3aed); color:#fff; border:0;
        font-weight:700; font-size:.76rem; border-radius:999px; padding:.5rem 1.1rem;
        box-shadow:0 8px 18px -8px rgba(79,70,229,.6); transition:transform .2s, box-shadow .2s;
    }
    .btn-kd-review:hover { color:#fff; transform:translateY(-2px); box-shadow:0 12px 24px -10px rgba(79,70,229,.7); }
    .btn-kd-review::after { content:''; position:absolute; top:0;bottom:0; width:45%; background:linear-gradient(105deg,transparent,rgba(255,255,255,.35),transparent); animation:kdSweep 3s ease-in-out infinite; }

    .kd-empty { text-align:center; padding:3rem 1rem; }
    .kd-empty .big {
        width:84px;height:84px; margin:0 auto 1rem; border-radius:50%;
        background:linear-gradient(140deg,#d1fae5,#a7f3d0); color:#059669;
        display:flex;align-items:center;justify-content:center; font-size:2.4rem;
        animation:kdPop .6s .2s cubic-bezier(.34,1.56,.64,1) both;
    }

    /* ── Gauge anggaran (conic) ── */
    .kd-gauge { position:relative; width:168px; height:168px; margin:0 auto; }
    .kd-gauge .ring {
        width:100%; height:100%; border-radius:50%;
        background:conic-gradient(#4f46e5 calc(var(--p,0)*1%), #e8ecf6 0);
        transition:background .2s;
        -webkit-mask:radial-gradient(circle, transparent 56%, #000 57%);
                mask:radial-gradient(circle, transparent 56%, #000 57%);
    }
    .kd-gauge .mid { position:absolute; inset:0; display:flex; flex-direction:column; align-items:center; justify-content:center; }
    .kd-gauge .pc { font-size:1.7rem; font-weight:800; color:var(--ink); letter-spacing:-1px; }
    .kd-gauge .pl { font-size:.62rem; font-weight:700; letter-spacing:1px; color:var(--mut); text-transform:uppercase; }

    /* ── Bar komposisi ── */
    .kd-mix-bar { display:flex; height:14px; border-radius:999px; overflow:hidden; background:#eef1f7; }
    .kd-mix-bar span { display:block; height:100%; width:0; transition:width 1.2s cubic-bezier(.16,1,.3,1); }
    .kd-legend { display:flex; align-items:center; gap:.45rem; font-size:.74rem; color:var(--mut); }
    .kd-legend .sw { width:10px;height:10px;border-radius:3px; }

    /* ── Riwayat ── */
    .kd-hist { position:relative; padding-left:1.35rem; }
    .kd-hist::before { content:''; position:absolute; left:7px; top:6px; bottom:6px; width:2px; background:linear-gradient(#e2e8f0, #f1f5f9); border-radius:2px; }
    .kd-hist-item { position:relative; padding-bottom:1rem; }
    .kd-hist-item:last-child { padding-bottom:0; }
    .kd-hist-item .dot {
        position:absolute; left:-1.35rem; top:.2rem; width:16px;height:16px; border-radius:50%;
        border:3px solid #fff; box-shadow:0 0 0 2px var(--line);
    }
    .kd-hist-item.ok .dot { background:#10b981; box-shadow:0 0 0 2px #a7f3d0; }
    .kd-hist-item.bad .dot { background:#ef4444; box-shadow:0 0 0 2px #fecaca; }
</style>
@endpush

@section('content')
@php
    $tipeMeta = [
        'KONTRAK' => ['Kontrak', 'bi-briefcase-fill', '#4f46e5', 'rgba(79,70,229,.1)'],
        'PERJALDIN' => ['Perjaldin', 'bi-airplane-engines-fill', '#0891b2', 'rgba(8,145,178,.1)'],
        'HONORARIUM' => ['Honorarium', 'bi-people-fill', '#d97706', 'rgba(217,119,6,.12)'],
    ];
    $totalNominalTipe = max((float) $perTipe->sum('nominal'), 1);
@endphp

<div class="kd container-fluid py-3 px-lg-4">

    {{-- ════════ HERO ════════ --}}
    <div class="kd-hero">
        <div class="blob b1"></div><div class="blob b2"></div><div class="blob b3"></div>
        <div class="position-relative d-flex flex-wrap justify-content-between align-items-center gap-4" style="z-index:2;">
            <div>
                <span class="kd-badge-role"><i class="bi bi-shield-fill-check"></i> Kuasa Pengguna Anggaran</span>
                <h3 class="fw-bolder mt-3 mb-1" style="letter-spacing:-.5px; color:var(--ink);">
                    {{ now()->hour < 11 ? 'Selamat pagi' : (now()->hour < 15 ? 'Selamat siang' : (now()->hour < 18 ? 'Selamat sore' : 'Selamat malam')) }}, {{ $user->name }} 👋
                </h3>
                <div class="text-secondary fs-7">
                    @if($stats['pending'] > 0)
                        Ada <strong class="text-dark">{{ $stats['pending'] }} Standing Instruction</strong> senilai
                        <strong class="text-dark">Rp {{ number_format($stats['pendingNominal'], 0, ',', '.') }}</strong> menunggu keputusan Anda.
                    @else
                        Tidak ada antrean persetujuan — semua Standing Instruction telah Anda tindak lanjuti. 🎉
                    @endif
                </div>
            </div>
            <div class="text-lg-end">
                <div class="kd-clock kd-mono"><span id="kdJam">--</span><span class="sep">:</span><span id="kdMenit">--</span></div>
                <div class="text-secondary fs-7 mt-1">{{ now()->translatedFormat('l, d F Y') }}</div>
            </div>
        </div>
    </div>

    {{-- ════════ KPI TILES ════════ --}}
    <div class="row g-3 mb-1">
        <div class="col-6 col-xl-3 kd-reveal">
            <div class="kd-tile {{ $stats['pending'] > 0 ? 'hot' : '' }}">
                <div class="tic" style="background:#fef3c7; color:#d97706;"><i class="bi bi-hourglass-split"></i></div>
                <div class="lv" data-count="{{ $stats['pending'] }}">0</div>
                <div class="ll">Menunggu Keputusan</div>
                <div class="sub kd-mono">Rp {{ number_format($stats['pendingNominal'], 0, ',', '.') }}</div>
            </div>
        </div>
        <div class="col-6 col-xl-3 kd-reveal">
            <div class="kd-tile">
                <div class="tic" style="background:#d1fae5; color:#059669;"><i class="bi bi-check2-circle"></i></div>
                <div class="lv" data-count="{{ $stats['approvedBulanIni'] }}">0</div>
                <div class="ll">Disetujui Bulan Ini</div>
                <div class="sub kd-mono">Rp {{ number_format($stats['approvedNominalBulanIni'], 0, ',', '.') }}</div>
            </div>
        </div>
        <div class="col-6 col-xl-3 kd-reveal">
            <div class="kd-tile">
                <div class="tic" style="background:#e0e7ff; color:#4f46e5;"><i class="bi bi-collection-fill"></i></div>
                <div class="lv" data-count="{{ $stats['approvedTotal'] }}">0</div>
                <div class="ll">Total Disetujui</div>
                <div class="sub">{{ $pasca['selesai'] }} selesai dicairkan · {{ $pasca['proses'] }} dalam proses</div>
            </div>
        </div>
        <div class="col-6 col-xl-3 kd-reveal">
            <div class="kd-tile">
                <div class="tic" style="background:#fee2e2; color:#dc2626;"><i class="bi bi-x-octagon"></i></div>
                <div class="lv" data-count="{{ $stats['rejectedTotal'] }}">0</div>
                <div class="ll">Ditolak</div>
                <div class="sub">Dikembalikan untuk diperbaiki PPK</div>
            </div>
        </div>
    </div>

    <div class="row g-4 mt-0">
        {{-- ════════ KIRI: ANTREAN ════════ --}}
        <div class="col-xl-7">
            <div class="kd-card kd-reveal">
                <div class="kd-card-head">
                    <div class="tic" style="width:40px;height:40px;border-radius:.9rem;display:flex;align-items:center;justify-content:center;background:#fef3c7;color:#d97706;font-size:1.1rem;"><i class="bi bi-inboxes-fill"></i></div>
                    <div>
                        <h6>Antrean Persetujuan</h6>
                        <div class="sub">Terlama menunggu tampil paling atas — klik untuk meninjau dan memutuskan.</div>
                    </div>
                    @if($stats['pending'] > 0)
                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill ms-auto px-3 py-2 fw-bold">{{ $stats['pending'] }} tagihan</span>
                    @endif
                </div>

                @forelse($antrean as $t)
                    @php
                        [$tLabel, $tIcon, $tColor, $tSoft] = $tipeMeta[$t->tipe_tagihan] ?? ['Tagihan', 'bi-receipt', '#64748b', '#f1f5f9'];
                        $vendor = $t->detailKontrak?->kontrakTermin?->kontrak?->vendor?->nama_pihak ?? $t->pihak?->nama_pihak;
                        $subjek = match ($t->tipe_tagihan) {
                            'PERJALDIN' => ($t->detailPerjaldin?->count() ?? 0) . ' pegawai',
                            'HONORARIUM' => ($t->detailHonorarium?->count() ?? 0) . ' personel',
                            default => \Illuminate\Support\Str::limit($vendor ?? '-', 30),
                        };
                        $umurJam = now()->diffInHours($t->updated_at);
                        $umurLabel = $umurJam < 24 ? $umurJam . ' jam' : now()->diffInDays($t->updated_at) . ' hari';
                        $umurClass = $umurJam < 24 ? 'ok' : ($umurJam < 72 ? 'warn' : 'late');
                    @endphp
                    <div class="kd-queue-item">
                        <div class="qic" style="background: {{ $tSoft }}; color: {{ $tColor }};"><i class="bi {{ $tIcon }}"></i></div>
                        <div class="flex-grow-1" style="min-width:0;">
                            <div class="d-flex flex-wrap align-items-center gap-2">
                                <span class="kd-mono fw-bold fs-7" style="color: {{ $tColor }};">{{ $t->nomor_tagihan }}</span>
                                <span class="badge bg-light text-secondary border rounded-pill fs-8">{{ $tLabel }}</span>
                                <span class="kd-age {{ $umurClass }}"><i class="bi bi-clock me-1"></i>{{ $umurLabel }}</span>
                            </div>
                            <div class="fw-semibold text-dark fs-7 text-truncate mt-1">{{ $t->deskripsi ?: '-' }}</div>
                            <div class="text-secondary fs-8"><i class="bi bi-person me-1"></i>{{ $subjek }} · diajukan {{ $t->ppkUser?->name ?? 'PPK' }}</div>
                        </div>
                        <div class="text-end flex-shrink-0">
                            <div class="fw-bolder kd-mono text-dark mb-2">Rp {{ number_format((float) $t->total_netto, 0, ',', '.') }}</div>
                            <a href="{{ route('kpa.approval.show', ['tagihanId' => $t->id]) }}" class="btn-kd-review btn btn-sm">
                                Tinjau &amp; Putuskan <i class="bi bi-arrow-right ms-1"></i>
                            </a>
                        </div>
                    </div>
                @empty
                    <div class="kd-empty">
                        <div class="big"><i class="bi bi-check2-all"></i></div>
                        <h6 class="fw-bolder text-dark">Antrean kosong — kerja bagus! ✨</h6>
                        <div class="text-secondary fs-7">Semua pengajuan Standing Instruction telah Anda putuskan.<br>Notifikasi WhatsApp akan masuk saat ada pengajuan baru dari PPK.</div>
                    </div>
                @endforelse
            </div>

            {{-- Riwayat keputusan --}}
            <div class="kd-card kd-reveal">
                <div class="kd-card-head">
                    <div class="tic" style="width:40px;height:40px;border-radius:.9rem;display:flex;align-items:center;justify-content:center;background:#e0e7ff;color:#4f46e5;font-size:1.1rem;"><i class="bi bi-journal-check"></i></div>
                    <div>
                        <h6>Riwayat Keputusan Terakhir</h6>
                        <div class="sub">8 keputusan terbaru Anda beserta catatannya.</div>
                    </div>
                    <a href="{{ route('standing-instruction.index') }}" class="btn btn-sm btn-light border rounded-pill fw-bold ms-auto fs-8">
                        Lihat Semua <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
                <div class="p-4">
                    @if($riwayat->isEmpty())
                        <div class="text-center text-muted fs-7 py-3"><i class="bi bi-journal-x fs-3 d-block mb-2"></i>Belum ada keputusan yang tercatat.</div>
                    @else
                        <div class="kd-hist">
                            @foreach($riwayat as $r)
                                @php $rOk = $r->kpa_approval_status === 'APPROVED'; @endphp
                                <div class="kd-hist-item {{ $rOk ? 'ok' : 'bad' }}">
                                    <div class="dot"></div>
                                    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
                                        <div style="min-width:0;">
                                            <span class="badge {{ $rOk ? 'bg-success-subtle text-success border-success-subtle' : 'bg-danger-subtle text-danger border-danger-subtle' }} border rounded-pill fs-8 me-1">
                                                {{ $rOk ? 'Disetujui' : 'Ditolak' }}
                                            </span>
                                            <span class="kd-mono fw-bold fs-8 text-dark">{{ $r->nomor_tagihan }}</span>
                                            <div class="text-secondary fs-8 text-truncate mt-1" style="max-width:380px;">{{ $r->deskripsi ?: '-' }}</div>
                                            @if($r->kpa_approval_notes)
                                                <div class="fst-italic fs-8 mt-1" style="color:#94a3b8;">"{{ \Illuminate\Support\Str::limit($r->kpa_approval_notes, 80) }}"</div>
                                            @endif
                                        </div>
                                        <div class="text-end flex-shrink-0">
                                            <div class="fw-bold kd-mono fs-8 text-dark">Rp {{ number_format((float) $r->total_netto, 0, ',', '.') }}</div>
                                            <div class="text-secondary fs-8">{{ $r->kpa_approved_at ? \Carbon\Carbon::parse($r->kpa_approved_at)->translatedFormat('d M Y, H:i') : '-' }}</div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- ════════ KANAN: KONTEKS ANGGARAN ════════ --}}
        <div class="col-xl-5">
            {{-- Gauge anggaran --}}
            <div class="kd-card kd-reveal">
                <div class="kd-card-head">
                    <div class="tic" style="width:40px;height:40px;border-radius:.9rem;display:flex;align-items:center;justify-content:center;background:#d1fae5;color:#059669;font-size:1.1rem;"><i class="bi bi-pie-chart-fill"></i></div>
                    <div>
                        <h6>Realisasi Anggaran DIPA</h6>
                        <div class="sub">Konteks pagu sebelum Anda menyetujui pembebanan baru.</div>
                    </div>
                </div>
                <div class="p-4">
                    <div class="kd-gauge" data-gauge="{{ min($anggaran['persen'], 100) }}">
                        <div class="ring" style="--p:0;"></div>
                        <div class="mid">
                            <div class="pc"><span data-count="{{ $anggaran['persen'] }}" data-decimal="1">0</span>%</div>
                            <div class="pl">Terealisasi</div>
                        </div>
                    </div>
                    <div class="row text-center g-2 mt-3">
                        <div class="col-4">
                            <div class="fs-8 fw-bold text-secondary text-uppercase">Pagu</div>
                            <div class="fw-bold fs-7 kd-mono">{{ number_format($anggaran['pagu'] / 1_000_000, 0, ',', '.') }} jt</div>
                        </div>
                        <div class="col-4">
                            <div class="fs-8 fw-bold text-secondary text-uppercase">Realisasi</div>
                            <div class="fw-bold fs-7 kd-mono text-primary">{{ number_format($anggaran['realisasi'] / 1_000_000, 0, ',', '.') }} jt</div>
                        </div>
                        <div class="col-4">
                            <div class="fs-8 fw-bold text-secondary text-uppercase">Sisa</div>
                            <div class="fw-bold fs-7 kd-mono text-success">{{ number_format($anggaran['sisa'] / 1_000_000, 0, ',', '.') }} jt</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Komposisi persetujuan --}}
            <div class="kd-card kd-reveal">
                <div class="kd-card-head">
                    <div class="tic" style="width:40px;height:40px;border-radius:.9rem;display:flex;align-items:center;justify-content:center;background:#e0f2fe;color:#0284c7;font-size:1.1rem;"><i class="bi bi-bar-chart-steps"></i></div>
                    <div>
                        <h6>Komposisi Persetujuan {{ now()->year }}</h6>
                        <div class="sub">Nominal yang Anda setujui per jenis tagihan tahun ini.</div>
                    </div>
                </div>
                <div class="p-4">
                    @if($perTipe->isEmpty())
                        <div class="text-center text-muted fs-7 py-3">Belum ada persetujuan tahun ini.</div>
                    @else
                        <div class="kd-mix-bar mb-3">
                            @foreach($perTipe as $pt)
                                @php [$mLabel, , $mColor] = $tipeMeta[$pt->tipe_tagihan] ?? ['Lainnya', '', '#94a3b8']; @endphp
                                <span data-width="{{ round((float) $pt->nominal / $totalNominalTipe * 100, 1) }}" style="background: {{ $mColor }};" title="{{ $mLabel }}"></span>
                            @endforeach
                        </div>
                        <div class="d-flex flex-column gap-2">
                            @foreach($perTipe as $pt)
                                @php [$mLabel, $mIcon, $mColor] = $tipeMeta[$pt->tipe_tagihan] ?? ['Lainnya', 'bi-receipt', '#94a3b8']; @endphp
                                <div class="kd-legend justify-content-between">
                                    <span class="d-flex align-items-center gap-2">
                                        <span class="sw" style="background: {{ $mColor }};"></span>
                                        {{ $mLabel }} <span class="text-muted">({{ $pt->jumlah }} tagihan)</span>
                                    </span>
                                    <span class="fw-bold kd-mono text-dark">Rp {{ number_format((float) $pt->nominal, 0, ',', '.') }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            {{-- Pasca persetujuan + pintasan --}}
            <div class="kd-card kd-reveal">
                <div class="kd-card-head">
                    <div class="tic" style="width:40px;height:40px;border-radius:.9rem;display:flex;align-items:center;justify-content:center;background:#fae8ff;color:#a21caf;font-size:1.1rem;"><i class="bi bi-rocket-takeoff"></i></div>
                    <div>
                        <h6>Setelah Persetujuan Anda</h6>
                        <div class="sub">Progres pencairan tagihan yang sudah Anda setujui.</div>
                    </div>
                </div>
                <div class="p-4">
                    <div class="row g-2 text-center mb-3">
                        <div class="col-6">
                            <div class="border rounded-4 p-3" style="background:#f8faff;">
                                <div class="fw-bolder fs-4 text-primary" data-count="{{ $pasca['proses'] }}">0</div>
                                <div class="fs-8 fw-bold text-secondary text-uppercase">Dalam Proses Pencairan</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="border rounded-4 p-3" style="background:#f0fdf4;">
                                <div class="fw-bolder fs-4 text-success" data-count="{{ $pasca['selesai'] }}">0</div>
                                <div class="fs-8 fw-bold text-secondary text-uppercase">Selesai Dibayar</div>
                            </div>
                        </div>
                    </div>
                    <div class="d-grid gap-2">
                        <a href="{{ route('standing-instruction.index') }}" class="btn btn-light border rounded-pill fw-bold fs-7 d-flex align-items-center justify-content-center gap-2">
                            <i class="bi bi-journal-text text-primary"></i> Register Standing Instruction
                        </a>
                        <a href="{{ route('proses-tagihan.index') }}" class="btn btn-light border rounded-pill fw-bold fs-7 d-flex align-items-center justify-content-center gap-2">
                            <i class="bi bi-diagram-3 text-primary"></i> Pantau Proses Tagihan
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('script')
<script>
(function () {
    'use strict';
    var reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    /* ── Jam hidup ── */
    function tickClock() {
        var d = new Date();
        document.getElementById('kdJam').textContent = String(d.getHours()).padStart(2, '0');
        document.getElementById('kdMenit').textContent = String(d.getMinutes()).padStart(2, '0');
    }
    tickClock(); setInterval(tickClock, 5000);

    /* ── Reveal on scroll ── */
    var reveals = document.querySelectorAll('.kd-reveal');
    function activate(el) {
        el.classList.add('in');
        el.querySelectorAll('.kd-mix-bar span').forEach(function (s) { s.style.width = s.dataset.width + '%'; });
        el.querySelectorAll('.kd-gauge').forEach(animateGauge);
        el.querySelectorAll('[data-count]').forEach(countUp);
    }
    if ('IntersectionObserver' in window && !reduced) {
        var io = new IntersectionObserver(function (entries) {
            entries.forEach(function (e) { if (e.isIntersecting) { activate(e.target); io.unobserve(e.target); } });
        }, { threshold: 0.15 });
        reveals.forEach(function (el) { io.observe(el); });
    } else {
        reveals.forEach(activate);
    }

    /* ── Count-up ── */
    var fmt = new Intl.NumberFormat('id-ID');
    function countUp(el) {
        if (el.dataset.done) return;
        el.dataset.done = '1';
        var target = parseFloat(el.dataset.count) || 0;
        var dec = parseInt(el.dataset.decimal || '0', 10);
        if (reduced || target === 0) { el.textContent = dec ? target.toFixed(dec).replace('.', ',') : fmt.format(target); return; }
        var t0 = null, dur = 1100;
        function step(ts) {
            if (!t0) t0 = ts;
            var p = Math.min((ts - t0) / dur, 1);
            var eased = 1 - Math.pow(1 - p, 4);
            var val = target * eased;
            el.textContent = dec ? val.toFixed(dec).replace('.', ',') : fmt.format(Math.round(val));
            if (p < 1) requestAnimationFrame(step);
        }
        requestAnimationFrame(step);
    }

    /* ── Gauge conic ── */
    function animateGauge(g) {
        if (g.dataset.done) return;
        g.dataset.done = '1';
        var ring = g.querySelector('.ring');
        var target = parseFloat(g.dataset.gauge) || 0;
        if (reduced) { ring.style.setProperty('--p', target); return; }
        var t0 = null, dur = 1300;
        function step(ts) {
            if (!t0) t0 = ts;
            var p = Math.min((ts - t0) / dur, 1);
            var eased = 1 - Math.pow(1 - p, 3);
            ring.style.setProperty('--p', (target * eased).toFixed(1));
            if (p < 1) requestAnimationFrame(step);
        }
        requestAnimationFrame(step);
    }
})();
</script>
@endpush
