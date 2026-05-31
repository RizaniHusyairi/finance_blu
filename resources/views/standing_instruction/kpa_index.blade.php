@extends('layouts.app')
@section('title', 'Standing Instruction (KPA)')

@php
    use Illuminate\Support\Str;

    $fmtRp = fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.');

    $statusMeta = [
        'PENDING_KPA' => ['label' => 'Menunggu Persetujuan', 'class' => 'si-badge-warning', 'icon' => 'bi-hourglass-split'],
        'APPROVED'    => ['label' => 'Disetujui',            'class' => 'si-badge-success', 'icon' => 'bi-check-circle-fill'],
        'REJECTED'    => ['label' => 'Ditolak',              'class' => 'si-badge-danger',  'icon' => 'bi-x-circle-fill'],
    ];
@endphp

@push('css')
<style>
    .si {
        --si-indigo:#6366f1; --si-violet:#8b5cf6; --si-emerald:#10b981;
        --si-rose:#f43f5e; --si-amber:#f59e0b; --si-ink:#0f172a; --si-muted:#64748b;
    }
    @keyframes siUp { from{opacity:0;transform:translateY(20px);} to{opacity:1;transform:translateY(0);} }
    @keyframes siIn { from{opacity:0;transform:scale(.97);} to{opacity:1;transform:scale(1);} }
    @keyframes siFloat { 0%,100%{transform:translateY(0);} 50%{transform:translateY(-10px);} }
    .si-anim { opacity:0; animation:siUp .6s cubic-bezier(.22,1,.36,1) forwards; }
    .si-d1{animation-delay:.05s;} .si-d2{animation-delay:.12s;} .si-d3{animation-delay:.19s;} .si-d4{animation-delay:.26s;}

    /* Hero */
    .si-hero {
        position:relative; overflow:hidden; border-radius:1.5rem; padding:1.9rem 2rem; color:#fff;
        background:linear-gradient(125deg,#7c2d12 0%,#b45309 40%,#f59e0b 100%);
        background-size:200% 200%; animation:siIn .6s cubic-bezier(.22,1,.36,1) both;
        box-shadow:0 22px 50px -24px rgba(180,83,9,.85);
    }
    .si-hero::before,.si-hero::after{content:"";position:absolute;border-radius:50%;pointer-events:none;}
    .si-hero::before{width:320px;height:320px;right:-90px;top:-130px;background:radial-gradient(circle,rgba(255,255,255,.22),transparent 65%);animation:siFloat 8s ease-in-out infinite;}
    .si-hero::after{width:220px;height:220px;left:-70px;bottom:-110px;background:radial-gradient(circle,rgba(255,255,255,.14),transparent 70%);animation:siFloat 10s ease-in-out infinite 1s;}
    .si-hero-z{position:relative;z-index:2;}
    .si-hero h4{color:#fff;font-weight:800;letter-spacing:-.02em;}
    .si-hero p{color:rgba(255,255,255,.9);margin:0;max-width:60ch;}
    .si-hero .si-ic{width:58px;height:58px;border-radius:1.1rem;display:grid;place-items:center;font-size:1.7rem;background:rgba(255,255,255,.2);border:1px solid rgba(255,255,255,.28);backdrop-filter:blur(6px);animation:siFloat 5s ease-in-out infinite;}

    /* KPI */
    .si-kpis{display:grid;grid-template-columns:repeat(auto-fit,minmax(210px,1fr));gap:1rem;margin:1.4rem 0;}
    .si-kpi{position:relative;overflow:hidden;background:#fff;border:1px solid rgba(15,23,42,.06);border-radius:1.2rem;padding:1.15rem 1.25rem;box-shadow:0 12px 32px -26px rgba(15,23,42,.6);transition:transform .25s cubic-bezier(.22,1,.36,1),box-shadow .25s ease;}
    .si-kpi::before{content:"";position:absolute;left:0;top:0;bottom:0;width:5px;transform:scaleY(0);transform-origin:top;transition:transform .3s ease;}
    .si-kpi:hover{transform:translateY(-5px);box-shadow:0 20px 40px -22px rgba(15,23,42,.5);}
    .si-kpi:hover::before{transform:scaleY(1);}
    .si-kpi.k-indigo::before{background:var(--si-indigo);} .si-kpi.k-amber::before{background:var(--si-amber);}
    .si-kpi.k-emerald::before{background:var(--si-emerald);} .si-kpi.k-rose::before{background:var(--si-rose);}
    .si-kpi .ic{width:48px;height:48px;border-radius:.9rem;display:grid;place-items:center;font-size:1.4rem;transition:transform .3s ease;}
    .si-kpi:hover .ic{transform:rotate(-8deg) scale(1.08);}
    .si-kpi .lbl{font-size:.73rem;text-transform:uppercase;letter-spacing:.05em;font-weight:700;color:var(--si-muted);}
    .si-kpi .val{font-size:1.8rem;font-weight:800;color:var(--si-ink);line-height:1.1;}
    .bg-indigo{background:rgba(99,102,241,.12);color:var(--si-indigo);} .bg-amber{background:rgba(245,158,11,.12);color:var(--si-amber);}
    .bg-emerald{background:rgba(16,185,129,.12);color:var(--si-emerald);} .bg-rose{background:rgba(244,63,94,.12);color:var(--si-rose);}
    .t-indigo{color:var(--si-indigo);} .t-amber{color:var(--si-amber);} .t-emerald{color:var(--si-emerald);} .t-rose{color:var(--si-rose);}

    /* Card + table */
    .si-card{background:#fff;border:1px solid rgba(15,23,42,.07);border-radius:1.3rem;box-shadow:0 14px 38px -30px rgba(15,23,42,.6);overflow:hidden;}
    .si-card-head{display:flex;align-items:center;gap:.6rem;padding:1.1rem 1.3rem;background:linear-gradient(180deg,#fffdf7,#fdf6e9);border-bottom:1px solid rgba(15,23,42,.06);flex-wrap:wrap;}
    .si-card-head .hic{width:36px;height:36px;border-radius:.7rem;display:grid;place-items:center;background:var(--si-amber);color:#fff;font-size:1.05rem;}
    .si-table thead th{font-size:.72rem;text-transform:uppercase;letter-spacing:.04em;color:var(--si-muted);background:#f8fafc;white-space:nowrap;}
    .si-table tbody tr{transition:background .12s ease;}
    .si-table tbody tr:hover{background:#fffaf2;}
    .si-table td{vertical-align:middle;}
    .si-mono{font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:.82rem;background:#f1f5f9;color:#475569;padding:.2rem .5rem;border-radius:.45rem;white-space:nowrap;}

    .si-badge{display:inline-flex;align-items:center;gap:.35rem;font-size:.72rem;font-weight:800;padding:.28rem .7rem;border-radius:999px;border:1px solid transparent;white-space:nowrap;}
    .si-badge-warning{background:rgba(245,158,11,.12);color:#b45309;border-color:rgba(245,158,11,.25);}
    .si-badge-success{background:rgba(16,185,129,.12);color:#047857;border-color:rgba(16,185,129,.25);}
    .si-badge-danger{background:rgba(244,63,94,.12);color:#be123c;border-color:rgba(244,63,94,.25);}

    /* Filter chips */
    .si-chip{display:inline-flex;align-items:center;gap:.4rem;padding:.45rem .95rem;border-radius:999px;font-weight:700;font-size:.82rem;text-decoration:none;border:1px solid #e2e8f0;background:#fff;color:var(--si-muted);transition:all .2s ease;}
    .si-chip:hover{border-color:var(--si-amber);color:#b45309;}
    .si-chip.active{background:linear-gradient(135deg,#f59e0b,#d97706);color:#fff;border-color:transparent;box-shadow:0 8px 18px -8px rgba(245,158,11,.7);}

    .si-empty{text-align:center;color:var(--si-muted);padding:3rem 1rem;}
    .si-empty i{font-size:2.6rem;opacity:.3;display:block;margin-bottom:.6rem;}
</style>
@endpush

@section('content')
<div class="si">
    <x-page-title title="Standing Instruction" subtitle="Tagihan yang Diajukan PPK ke KPA" />

    {{-- HERO --}}
    <div class="si-hero">
        <div class="si-hero-z d-flex align-items-center gap-3">
            <div class="si-ic"><i class="bi bi-clipboard2-check-fill"></i></div>
            <div>
                <h4 class="mb-1">Standing Instruction (KPA)</h4>
                <p>Seluruh tagihan/SPP yang diajukan PPK kepada KPA untuk dimintakan persetujuan terdata di sini, lengkap dengan status dan riwayatnya.</p>
            </div>
        </div>
    </div>

    {{-- KPI --}}
    <div class="si-kpis">
        <div class="si-kpi k-indigo si-anim si-d1">
            <div class="d-flex justify-content-between align-items-start">
                <div><div class="lbl mb-1">Total Pengajuan</div><div class="val t-indigo">{{ $summary['total'] }}</div></div>
                <div class="ic bg-indigo"><i class="bi bi-collection-fill"></i></div>
            </div>
        </div>
        <div class="si-kpi k-amber si-anim si-d2">
            <div class="d-flex justify-content-between align-items-start">
                <div><div class="lbl mb-1">Menunggu Persetujuan</div><div class="val t-amber">{{ $summary['pending'] }}</div></div>
                <div class="ic bg-amber"><i class="bi bi-hourglass-split"></i></div>
            </div>
        </div>
        <div class="si-kpi k-emerald si-anim si-d3">
            <div class="d-flex justify-content-between align-items-start">
                <div><div class="lbl mb-1">Disetujui</div><div class="val t-emerald">{{ $summary['approved'] }}</div></div>
                <div class="ic bg-emerald"><i class="bi bi-check-circle-fill"></i></div>
            </div>
        </div>
        <div class="si-kpi k-rose si-anim si-d4">
            <div class="d-flex justify-content-between align-items-start">
                <div><div class="lbl mb-1">Nilai Menunggu</div><div class="val t-rose" style="font-size:1.3rem;">{{ $fmtRp($summary['nominal_pending']) }}</div></div>
                <div class="ic bg-rose"><i class="bi bi-cash-stack"></i></div>
            </div>
        </div>
    </div>

    {{-- Filter + Search --}}
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('standing-instruction.index', array_filter(['search' => $search])) }}" class="si-chip {{ !$statusFilter ? 'active' : '' }}">
                <i class="bi bi-grid"></i> Semua
            </a>
            <a href="{{ route('standing-instruction.index', array_filter(['status' => 'PENDING_KPA', 'search' => $search])) }}" class="si-chip {{ $statusFilter === 'PENDING_KPA' ? 'active' : '' }}">
                <i class="bi bi-hourglass-split"></i> Menunggu
            </a>
            <a href="{{ route('standing-instruction.index', array_filter(['status' => 'APPROVED', 'search' => $search])) }}" class="si-chip {{ $statusFilter === 'APPROVED' ? 'active' : '' }}">
                <i class="bi bi-check-circle"></i> Disetujui
            </a>
            <a href="{{ route('standing-instruction.index', array_filter(['status' => 'REJECTED', 'search' => $search])) }}" class="si-chip {{ $statusFilter === 'REJECTED' ? 'active' : '' }}">
                <i class="bi bi-x-circle"></i> Ditolak
            </a>
        </div>
        <form method="GET" action="{{ route('standing-instruction.index') }}" class="d-flex gap-2" style="max-width:340px;">
            @if($statusFilter)<input type="hidden" name="status" value="{{ $statusFilter }}">@endif
            <input type="text" name="search" value="{{ $search }}" class="form-control" placeholder="Cari nomor SPP / tagihan / vendor...">
            <button class="btn btn-warning text-white"><i class="bi bi-search"></i></button>
        </form>
    </div>

    {{-- Table --}}
    <div class="si-card si-anim si-d3">
        <div class="si-card-head">
            <span class="hic"><i class="bi bi-list-ul"></i></span>
            <div>
                <h6 class="mb-0 fw-bold">Daftar Pengajuan ke KPA</h6>
                <small class="text-muted">Sumber: SPP dengan permintaan persetujuan KPA dari PPK</small>
            </div>
            <span class="badge bg-warning text-dark rounded-pill ms-auto">{{ $spps->total() }} pengajuan</span>
        </div>
        <div class="card-body p-0">
            @if($spps->isEmpty())
                <div class="si-empty">
                    <i class="bi bi-inbox"></i>
                    <div class="fw-semibold">Belum ada pengajuan ke KPA</div>
                    <div class="small">Tagihan yang dikirim PPK ke KPA akan muncul di sini.</div>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table align-middle mb-0 si-table">
                        <thead>
                            <tr>
                                <th>Nomor SPP</th>
                                <th>Tagihan / Uraian</th>
                                <th>Vendor / Penerima</th>
                                <th class="text-end">Nominal</th>
                                <th>Diajukan Oleh (PPK)</th>
                                <th>Status KPA</th>
                                <th>Waktu</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($spps as $spp)
                                @php
                                    $meta = $statusMeta[$spp->kpa_approval_status] ?? ['label' => $spp->kpa_approval_status, 'class' => 'si-badge-warning', 'icon' => 'bi-question-circle'];
                                    $vendor = $spp->tagihan?->detailKontrak?->kontrakTermin?->kontrak?->vendor?->nama_pihak
                                        ?? $spp->tagihan?->pihak?->nama_pihak
                                        ?? '-';
                                    $ppkNama = $spp->ppkVerifikator?->name ?? $spp->dibuatOleh?->name ?? '-';
                                @endphp
                                <tr>
                                    <td><span class="si-mono">{{ $spp->nomor_spp }}</span></td>
                                    <td>
                                        <div class="fw-semibold text-dark">{{ $spp->tagihan?->nomor_tagihan ?? '-' }}</div>
                                        <div class="small text-muted text-truncate" style="max-width:280px;">{{ Str::limit($spp->tagihan?->deskripsi ?? $spp->uraian ?? '-', 60) }}</div>
                                    </td>
                                    <td>{{ $vendor }}</td>
                                    <td class="text-end fw-bold">{{ $fmtRp($spp->nominal_spp) }}</td>
                                    <td>
                                        <span class="d-inline-flex align-items-center gap-1">
                                            <i class="bi bi-person-badge text-muted"></i> {{ $ppkNama }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="si-badge {{ $meta['class'] }}"><i class="bi {{ $meta['icon'] }}"></i> {{ $meta['label'] }}</span>
                                        @if($spp->kpa_approval_status !== 'PENDING_KPA' && $spp->kpaApprover)
                                            <div class="small text-muted mt-1"><i class="bi bi-person-check me-1"></i>{{ $spp->kpaApprover->name }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="small">
                                            @if($spp->kpa_approved_at)
                                                <i class="bi bi-clock-history text-muted me-1"></i>{{ \Illuminate\Support\Carbon::parse($spp->kpa_approved_at)->translatedFormat('d M Y, H:i') }}
                                            @else
                                                <span class="text-muted">Diajukan {{ optional($spp->updated_at)->diffForHumans() }}</span>
                                            @endif
                                        </div>
                                        @if($spp->kpa_approval_notes)
                                            <div class="small text-muted fst-italic mt-1" style="max-width:220px;">"{{ Str::limit($spp->kpa_approval_notes, 50) }}"</div>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
        @if($spps->hasPages())
            <div class="card-footer bg-white border-0 py-3">
                {{ $spps->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
