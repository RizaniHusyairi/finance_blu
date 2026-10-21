@extends('layouts.app')
@section('title', 'Detail Kontrak Mitra Jasa')

@php
    use Illuminate\Support\Carbon;

    $canManageMitraMaster = auth()->user()?->hasAnyRole(['Super Admin', 'Super Admin Jasa', 'Koordinator Jasa']) === true;

    $mulai = $kontrak->tanggal_mulai;
    $selesai = $kontrak->tanggal_selesai;
    $today = now()->startOfDay();

    // Status masa berlaku berdasarkan tanggal (terpisah dari status_kontrak tersimpan).
    $masaState = 'TIDAK_DISET';
    $sisaHari = null;
    if ($selesai) {
        $sisaHari = (int) $today->diffInDays($selesai, false);
    }
    if ($mulai && $mulai->gt($today)) {
        $masaState = 'AKAN_DATANG';
    } elseif ($selesai && $selesai->lt($today)) {
        $masaState = 'BERAKHIR';
    } elseif ($mulai || $selesai) {
        $masaState = 'BERJALAN';
    }

    // Progres masa berlaku (% periode yang sudah berjalan).
    $progress = null;
    $durasiHari = null;
    if ($mulai && $selesai) {
        $total = max(1, $mulai->diffInDays($selesai));
        $durasiHari = (int) $total + 1;
        $elapsed = max(0, min($total, $mulai->diffInDays($today)));
        $progress = (int) round($elapsed / $total * 100);
    }
    $segeraBerakhir = $masaState === 'BERJALAN' && $sisaHari !== null && $sisaHari <= 30;

    $masaMeta = match ($masaState) {
        'BERJALAN' => $segeraBerakhir ? ['Segera berakhir', 'kc-amber', 'bi-hourglass-bottom'] : ['Berjalan', 'kc-emerald', 'bi-broadcast'],
        'AKAN_DATANG' => ['Belum mulai', 'kc-blue', 'bi-clock-history'],
        'BERAKHIR' => ['Masa berakhir', 'kc-rose', 'bi-calendar-x'],
        default => ['Tanggal belum diset', 'kc-slate', 'bi-dash-circle'],
    };

    $statusKontrak = strtoupper((string) $kontrak->status_kontrak);
    $statusAktif = $statusKontrak === 'AKTIF';
    $statusMeta = match ($statusKontrak) {
        'AKTIF' => ['Aktif', 'kc-emerald', 'bi-check-circle-fill', 'Layanan pada scope kontrak ini menjadi layanan aktif mitra.'],
        'DRAFT' => ['Draft', 'kc-slate', 'bi-pencil-square', 'Belum berlaku; layanan pada scope belum diaktifkan untuk mitra.'],
        'BERAKHIR' => ['Berakhir', 'kc-slate', 'bi-calendar-x', 'Kontrak sudah ditutup; layanannya tidak lagi aktif dari kontrak ini.'],
        'DIBATALKAN' => ['Dibatalkan', 'kc-rose', 'bi-x-circle', 'Kontrak dibatalkan; layanannya tidak lagi aktif dari kontrak ini.'],
        default => [$kontrak->status_kontrak ?: '-', 'kc-slate', 'bi-question-circle', ''],
    };

    $scopeCount = $kontrak->layananJasa->count();
    $tagihanCount = (int) ($kontrak->tagihan_jasa_count ?? 0);
    $dataTerkait = [
        'Konsesi' => (int) ($kontrak->konsesi_count ?? 0),
        'Penjualan' => (int) ($kontrak->penjualan_count ?? 0),
        'PJP2U' => (int) ($kontrak->pjp2u_count ?? 0),
    ];
    $dataTerkaitTotal = array_sum($dataTerkait);

    // Scope dikelompokkan per kategori puncak, dengan jalur sub-kategori sebagai konteks.
    $grupScope = $kontrak->layananJasa
        ->map(function ($layanan) {
            $jalur = [];
            for ($p = $layanan->parent; $p; $p = $p->parent) {
                array_unshift($jalur, $p->nama_layanan);
            }
            return [
                'layanan' => $layanan,
                'root' => $jalur[0] ?? 'Tanpa kategori',
                'sub' => implode(' › ', array_slice($jalur, 1)),
            ];
        })
        ->sortBy(fn ($r) => $r['root'].'|'.$r['sub'].'|'.$r['layanan']->nama_layanan)
        ->groupBy('root');

    // Hal yang perlu ditindaklanjuti admin, paling penting di atas.
    $peringatan = [];
    if ($statusAktif && $masaState === 'BERAKHIR') {
        $peringatan[] = ['danger', 'bi-exclamation-octagon', 'Masa berlaku sudah lewat, tetapi status masih AKTIF.', 'Layanan dari kontrak ini masih dianggap aktif. Perbarui tanggal (perpanjangan) atau ubah status menjadi BERAKHIR.'];
    }
    if ($statusAktif && $scopeCount === 0) {
        $peringatan[] = ['warning', 'bi-diagram-3', 'Scope layanan masih kosong.', 'Kontrak aktif tanpa scope tidak memberi layanan apa pun ke mitra, sehingga mitra belum bisa ditagih dari kontrak ini.'];
    }
    if ($segeraBerakhir) {
        $peringatan[] = ['warning', 'bi-hourglass-bottom', "Kontrak berakhir dalam {$sisaHari} hari.", 'Siapkan perpanjangan atau addendum agar penagihan tidak terputus.'];
    }
    if (! $kontrak->file_kontrak) {
        $peringatan[] = ['info', 'bi-file-earmark-arrow-up', 'Dokumen kontrak belum diunggah.', 'Unggah berkas PDF kontrak sebagai arsip dan dasar penagihan.'];
    }

    $fileExt = $kontrak->file_kontrak ? strtoupper(pathinfo($kontrak->file_kontrak, PATHINFO_EXTENSION) ?: 'PDF') : null;
    $rupiah = fn ($n) => 'Rp '.number_format((float) $n, 0, ',', '.');
    $statusTagihanKelas = fn ($s) => match (strtoupper((string) $s)) {
        'LUNAS' => 'kc-emerald',
        'DRAFT', 'BATAL' => 'kc-slate',
        'REJECTED', 'REVISI' => 'kc-rose',
        'APPROVED', 'TERBIT', 'PUBLISHED' => 'kc-blue',
        default => 'kc-amber',
    };
@endphp

@push('css')
<style>
    .kc-scope { --kc-accent:#4f46e5; --kc-accent-2:#6366f1; --kc-soft:rgba(79,70,229,.12); }
    @keyframes kcReveal { from { opacity:0; transform:translateY(16px); } to { opacity:1; transform:translateY(0); } }

    .kc-hero {
        position:relative; overflow:hidden; border-radius:22px; padding:24px 26px; color:#fff;
        background:radial-gradient(120% 140% at 88% -10%, rgba(99,102,241,.35), transparent 46%), linear-gradient(115deg,#0b1220 0%,#1e1b4b 48%,#3730a3 100%);
        border:1px solid rgba(148,163,184,.18); border-top:2px solid var(--kc-accent-2);
        box-shadow:0 22px 56px rgba(15,23,42,.30); animation:kcReveal .5s cubic-bezier(.2,.8,.2,1) both;
    }
    .kc-hero-icon { width:56px; height:56px; flex:0 0 56px; border-radius:16px; display:inline-flex; align-items:center; justify-content:center; font-size:1.7rem; color:#1e1b4b; background:linear-gradient(140deg,#c7d2fe,#a5b4fc); box-shadow:0 14px 30px rgba(99,102,241,.4); }
    .kc-kicker { letter-spacing:.14em; font-size:10px; font-weight:900; text-transform:uppercase; color:#c7d2fe; }
    .kc-kicker a { color:inherit; text-decoration:none; } .kc-kicker a:hover { color:#fff; }
    .kc-hero h4 { font-weight:900; letter-spacing:-.01em; }
    .kc-scope .min-w-0 { min-width:0; }
    .kc-hero-actions { display:flex; flex-wrap:wrap; gap:8px; }
    .kc-hero-btn { border:1px solid rgba(199,210,254,.4); background:rgba(255,255,255,.10); color:#fff; border-radius:11px; height:40px; padding:0 14px; display:inline-flex; align-items:center; justify-content:center; gap:7px; font-size:13px; font-weight:800; white-space:nowrap; transition:all .15s ease; }
    .kc-hero-btn:hover { background:rgba(255,255,255,.2); color:#fff; transform:translateY(-1px); }
    .kc-hero-btn.primary { background:#fff; color:#1e1b4b; border-color:#fff; }
    .kc-hero-btn.primary:hover { background:#e0e7ff; color:#1e1b4b; }
    .kc-hero-btn.icon { width:40px; padding:0; }
    .kc-hero-btn.danger:hover { background:rgba(239,68,68,.85); border-color:transparent; }
    .kc-hero-btn:disabled { opacity:.45; cursor:not-allowed; transform:none; }
    .kc-chip { display:inline-flex; align-items:center; gap:7px; padding:6px 12px; border-radius:999px; font-size:12px; font-weight:800; background:rgba(255,255,255,.1); border:1px solid rgba(255,255,255,.18); }
    .kc-chip.ok i { color:#34d399; } .kc-chip.warn { background:rgba(251,191,36,.18); border-color:rgba(251,191,36,.45); color:#fde68a; } .kc-chip.bad { background:rgba(244,63,94,.2); border-color:rgba(244,63,94,.5); color:#fecdd3; }

    .kc-alert { display:flex; gap:12px; align-items:flex-start; border-radius:14px; padding:12px 16px; border:1px solid; }
    .kc-alert + .kc-alert { margin-top:10px; }
    .kc-alert i.lead { font-size:1.25rem; line-height:1.2; }
    .kc-alert.danger { background:#fff1f2; border-color:#fecdd3; color:#9f1239; }
    .kc-alert.warning { background:#fffbeb; border-color:#fde68a; color:#92400e; }
    .kc-alert.info { background:#eff6ff; border-color:#bfdbfe; color:#1e40af; }
    .kc-alert .t { font-weight:900; } .kc-alert .d { font-size:13px; color:#334155; }
    .kc-alert .btn { flex-shrink:0; }

    .kc-stat { display:block; height:100%; border-radius:16px; background:#fff; padding:15px 16px; border:1px solid rgba(15,23,42,.07); box-shadow:0 12px 30px rgba(15,23,42,.06); position:relative; overflow:hidden; color:inherit; text-decoration:none; animation:kcReveal .5s ease both; transition:transform .15s ease, box-shadow .15s ease; }
    a.kc-stat:hover { transform:translateY(-2px); box-shadow:0 16px 36px rgba(15,23,42,.10); color:inherit; }
    .kc-stat::before { content:""; position:absolute; left:0; top:0; bottom:0; width:4px; background:var(--st,#4f46e5); }
    .kc-stat-ico { width:38px; height:38px; flex-shrink:0; border-radius:12px; display:inline-flex; align-items:center; justify-content:center; font-size:1.05rem; color:var(--st,#4f46e5); background:var(--st-soft,rgba(79,70,229,.12)); }
    .kc-stat-lbl { font-size:10px; font-weight:900; letter-spacing:.04em; text-transform:uppercase; color:#94a3b8; }
    .kc-stat-val { font-size:20px; font-weight:900; color:#0f172a; line-height:1.15; }
    .kc-stat-sub { font-size:12px; color:#64748b; margin-top:4px; }

    .kc-card { border:1px solid rgba(15,23,42,.08); border-radius:18px; background:#fff; box-shadow:0 16px 40px rgba(15,23,42,.06); overflow:hidden; }
    .kc-card-head { display:flex; flex-wrap:wrap; align-items:center; gap:10px; padding:13px 18px; border-bottom:1px solid #eef2f7; background:linear-gradient(90deg,#eef2ff,#fff); }
    .kc-card-head .ic { width:34px; height:34px; flex-shrink:0; border-radius:9px; display:inline-flex; align-items:center; justify-content:center; color:#fff; background:linear-gradient(140deg,var(--kc-accent),var(--kc-accent-2)); }
    .kc-card-head h6 { margin:0; font-weight:900; color:#1e1b4b; }
    .kc-card-head .sub { font-size:12px; color:#64748b; }
    .kc-card-head .tools { margin-left:auto; display:flex; gap:8px; align-items:center; flex-wrap:wrap; }

    .kc-info { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
    .kc-info-item { border:1px solid #eef2f7; border-radius:13px; background:linear-gradient(180deg,#fbfcff,#fff); padding:12px 14px; }
    .kc-info-item.kc-span { grid-column:1 / -1; }
    .kc-info-label { font-size:10px; font-weight:900; letter-spacing:.04em; text-transform:uppercase; color:#94a3b8; display:flex; align-items:center; gap:6px; }
    .kc-info-label i { color:var(--kc-accent); }
    .kc-info-value { font-weight:800; color:#0f172a; margin-top:4px; word-break:break-word; }
    .kc-info-hint { font-size:12px; color:#64748b; margin-top:4px; }

    .kc-badge { display:inline-flex; align-items:center; gap:.35rem; padding:5px 11px; border-radius:999px; font-size:11px; font-weight:900; white-space:nowrap; }
    .kc-emerald { color:#047857; background:#d1fae5; } .kc-blue { color:#1d4ed8; background:#dbeafe; }
    .kc-slate { color:#475569; background:#e2e8f0; } .kc-amber { color:#92400e; background:#fef3c7; }
    .kc-rose { color:#be123c; background:#ffe4e6; }

    .kc-timeline { display:grid; grid-template-columns:1fr auto 1fr; align-items:center; gap:10px; }
    .kc-tl-pt .lbl { font-size:10px; font-weight:900; text-transform:uppercase; letter-spacing:.04em; color:#94a3b8; }
    .kc-tl-pt .val { font-weight:900; color:#0f172a; }
    .kc-tl-mid { font-size:11px; font-weight:800; color:#4f46e5; background:#eef2ff; border-radius:999px; padding:4px 10px; white-space:nowrap; }
    .kc-progress { position:relative; height:10px; border-radius:999px; background:#eef2f7; overflow:hidden; }
    .kc-progress span { display:block; height:100%; border-radius:999px; background:linear-gradient(90deg,var(--kc-accent),var(--kc-accent-2)); }
    .kc-progress.is-warn span { background:linear-gradient(90deg,#f59e0b,#d97706); }
    .kc-progress.is-end span { background:linear-gradient(90deg,#94a3b8,#64748b); }

    .kc-file { display:flex; flex-wrap:wrap; align-items:center; gap:12px 14px; border:1px dashed #c7d2fe; border-radius:14px; background:#eef2ff; padding:14px 16px; }
    .kc-file-ico { width:46px; height:46px; flex:0 0 46px; border-radius:12px; display:inline-flex; align-items:center; justify-content:center; font-size:1.4rem; color:#fff; background:linear-gradient(140deg,#ef4444,#dc2626); }
    .kc-file-info { flex:1; min-width:0; }
    .kc-file-info .nm { font-weight:800; color:#0f172a; overflow-wrap:anywhere; line-height:1.3; }
    .kc-file-acts { display:flex; gap:8px; width:100%; }
    .kc-file-acts .btn { flex:1; }

    .kc-group { border:1px solid #e0e7ff; border-radius:16px; background:linear-gradient(180deg,#f8faff,#fff 70%); overflow:hidden; }
    .kc-group + .kc-group { margin-top:14px; }
    .kc-group-head { display:flex; align-items:center; gap:10px; padding:12px 16px; font-weight:900; color:#1e1b4b; font-size:13.5px; border-bottom:1px solid #eef2ff; }
    .kc-group-head .fi { width:30px; height:30px; flex-shrink:0; border-radius:9px; display:inline-flex; align-items:center; justify-content:center; color:#4f46e5; background:#e0e7ff; }
    .kc-group-head .tt { flex:1; min-width:0; overflow-wrap:anywhere; }
    .kc-group-head .n { flex-shrink:0; font-size:11px; font-weight:900; color:#fff; background:#4f46e5; border-radius:999px; padding:3px 10px; }
    .kc-group-body { padding:12px 16px 16px; }
    .kc-group-sub { display:flex; align-items:center; gap:6px; font-size:11.5px; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:.03em; margin:12px 0 8px; }
    .kc-group-sub:first-child { margin-top:0; }
    .kc-group-sub::after { content:""; flex:1; height:1px; background:#e2e8f0; }
    .kc-svc-list { display:grid; grid-template-columns:repeat(auto-fill,minmax(280px,1fr)); gap:10px; }
    .kc-svc { display:flex; gap:12px; align-items:flex-start; border:1px solid #e2e8f0; border-left:3px solid var(--kc-accent); background:#fff; border-radius:12px; padding:12px 14px; min-width:0; transition:box-shadow .15s ease, transform .15s ease; }
    .kc-svc:hover { box-shadow:0 10px 24px rgba(79,70,229,.10); transform:translateY(-1px); }
    .kc-svc-ico { width:32px; height:32px; flex-shrink:0; border-radius:50%; display:inline-flex; align-items:center; justify-content:center; color:#047857; background:#d1fae5; }
    .kc-svc-body { flex:1; min-width:0; }
    .kc-svc-name { font-weight:800; color:#1e293b; overflow-wrap:anywhere; line-height:1.35; }
    .kc-svc-tags { display:flex; flex-wrap:wrap; gap:6px; margin-top:6px; min-width:0; }
    .kc-svc-code { max-width:100%; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; font-family:ui-monospace,Menlo,Consolas,monospace; font-size:10.5px; font-weight:700; color:#4338ca; background:#eef2ff; border:1px solid #c7d2fe; border-radius:6px; padding:2px 7px; }
    .kc-svc-meta { display:inline-flex; align-items:center; gap:4px; font-size:11px; font-weight:700; color:#475569; background:#f1f5f9; border-radius:6px; padding:2px 7px; white-space:nowrap; }

    .kc-empty { text-align:center; padding:22px 12px; color:#64748b; }
    .kc-empty i.big { font-size:2rem; display:block; margin-bottom:6px; opacity:.55; }

    .kc-tagihan { width:100%; border-collapse:separate; border-spacing:0; }
    .kc-tagihan th { font-size:10px; font-weight:900; letter-spacing:.04em; text-transform:uppercase; color:#94a3b8; padding:0 12px 8px; white-space:nowrap; }
    .kc-tagihan td { padding:10px 12px; border-top:1px solid #eef2f7; vertical-align:middle; }
    .kc-tagihan tr:hover td { background:#f8fafc; }
    .kc-tagihan .no { font-weight:900; color:#1e1b4b; text-decoration:none; }
    .kc-tagihan .no:hover { color:var(--kc-accent); text-decoration:underline; }

    @media (max-width:575.98px){
        .kc-hero { padding:20px 18px; }
        .kc-hero-btn .lbl { display:none; }
        .kc-hero-btn { width:40px; padding:0; }
        .kc-info{ grid-template-columns:1fr; }
        .kc-alert { flex-wrap:wrap; } .kc-alert .btn { width:100%; }
        .kc-stat-val { font-size:17px; }
        .kc-svc-list { grid-template-columns:1fr; }
        .kc-tagihan thead { display:none; }
        .kc-tagihan tr { display:grid; grid-template-columns:1fr auto; gap:2px 10px; padding:10px 0; border-top:1px solid #eef2f7; }
        .kc-tagihan td { border:0; padding:0 4px; }
        .kc-tagihan td.tgl { grid-column:1; font-size:12px; color:#64748b; }
        .kc-tagihan td.nil { grid-column:2; grid-row:1; text-align:right; }
        .kc-tagihan td.st { grid-column:2; grid-row:2; text-align:right; }
    }
</style>
@endpush

@section('content')
<div class="kc-scope">

    {{-- ===== Hero ===== --}}
    <div class="kc-hero mb-4">
        <div class="d-flex flex-column flex-lg-row align-items-start align-items-lg-center justify-content-between gap-3">
            <div class="d-flex align-items-start gap-3 min-w-0">
                <span class="kc-hero-icon"><i class="bi bi-file-earmark-text"></i></span>
                <div class="min-w-0">
                    <div class="kc-kicker mb-1"><a href="{{ route('jasa.mitra.show', $mitra) }}">{{ $mitra->nama_mitra }}</a> / Kontrak</div>
                    <h4 class="mb-1 text-white">{{ $kontrak->nama_kontrak ?: 'Kontrak Mitra Jasa' }}</h4>
                    <p class="mb-2 small fw-semibold" style="color:#c7d2fe;">
                        <i class="bi bi-hash"></i>{{ $kontrak->nomor_kontrak ?: 'Nomor belum diisi' }}
                        @if($kontrak->jenis_dokumen)
                            <span class="mx-2 opacity-50">•</span>{{ ucwords(strtolower(str_replace('_', ' ', $kontrak->jenis_dokumen))) }}
                        @endif
                    </p>
                    <div class="d-flex flex-wrap gap-2">
                        <span class="kc-chip {{ $statusAktif ? 'ok' : '' }}" title="Status kontrak"><i class="bi {{ $statusMeta[2] }}"></i>Status: {{ $statusMeta[0] }}</span>
                        <span class="kc-chip {{ $masaState === 'BERAKHIR' ? 'bad' : ($segeraBerakhir ? 'warn' : '') }}" title="Masa berlaku menurut tanggal">
                            <i class="bi {{ $masaMeta[2] }}"></i>{{ $masaMeta[0] }}@if($masaState === 'BERJALAN' && $sisaHari !== null) · sisa {{ $sisaHari }} hari @elseif($masaState === 'BERAKHIR' && $sisaHari !== null) · {{ abs($sisaHari) }} hari lalu @endif
                        </span>
                    </div>
                </div>
            </div>
            <div class="kc-hero-actions">
                @if($canManageMitraMaster)
                    <a href="{{ route('jasa.mitra.kontrak.edit', [$mitra, $kontrak]) }}" class="kc-hero-btn primary" title="Edit kontrak"><i class="bi bi-pencil"></i><span class="lbl">Edit kontrak</span></a>
                    <a href="{{ route('jasa.mitra.kontrak.edit', [$mitra, $kontrak]) }}#scope-layanan" class="kc-hero-btn" title="Edit layanan (scope kontrak)"><i class="bi bi-diagram-3"></i><span class="lbl">Edit layanan</span></a>
                    <form method="POST" action="{{ route('jasa.mitra.kontrak.destroy', [$mitra, $kontrak]) }}" onsubmit="return confirm('Hapus kontrak/dokumen ini? Tindakan ini tidak dapat dibatalkan.');">
                        @csrf @method('DELETE')
                        <button type="submit" class="kc-hero-btn icon danger" @disabled($tagihanCount > 0)
                            title="{{ $tagihanCount > 0 ? 'Tidak bisa dihapus: sudah dipakai '.$tagihanCount.' tagihan' : 'Hapus kontrak' }}" aria-label="Hapus kontrak"><i class="bi bi-trash"></i></button>
                    </form>
                @endif
                <a href="{{ route('jasa.mitra.show', $mitra) }}" class="kc-hero-btn icon" title="Kembali ke detail mitra" aria-label="Kembali"><i class="bi bi-arrow-left"></i></a>
            </div>
        </div>
    </div>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

    {{-- ===== Perlu perhatian ===== --}}
    @if($peringatan)
        <div class="mb-4" id="kcPerhatian">
            @foreach($peringatan as [$jenis, $ikon, $judul, $detail])
                <div class="kc-alert {{ $jenis }}" role="alert">
                    <i class="bi {{ $ikon }} lead"></i>
                    <div class="flex-grow-1">
                        <div class="t">{{ $judul }}</div>
                        <div class="d">{{ $detail }}</div>
                    </div>
                    @if($canManageMitraMaster)
                        <a href="{{ route('jasa.mitra.kontrak.edit', [$mitra, $kontrak]) }}{{ $ikon === 'bi-diagram-3' ? '#scope-layanan' : '' }}" class="btn btn-sm btn-light border fw-bold">
                            {{ $ikon === 'bi-diagram-3' ? 'Atur scope' : ($ikon === 'bi-file-earmark-arrow-up' ? 'Unggah dokumen' : 'Perbarui kontrak') }}
                        </a>
                    @endif
                </div>
            @endforeach
        </div>
    @endif

    {{-- ===== Ringkasan ===== --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-3">
            <a href="#kcMasa" class="kc-stat" style="--st:#4f46e5;--st-soft:rgba(79,70,229,.12);">
                <div class="d-flex align-items-start justify-content-between gap-2">
                    <div class="min-w-0">
                        <div class="kc-stat-lbl mb-2">Masa Berlaku</div>
                        <div class="kc-stat-val">
                            @if($masaState === 'BERJALAN' && $sisaHari !== null) Sisa {{ $sisaHari }} hari
                            @elseif($masaState === 'AKAN_DATANG') Belum mulai
                            @elseif($masaState === 'BERAKHIR') Berakhir
                            @else — @endif
                        </div>
                        <div class="kc-stat-sub">
                            @if($selesai) s.d. {{ $selesai->translatedFormat('d M Y') }} @else Tanggal belum lengkap @endif
                        </div>
                    </div>
                    <span class="kc-stat-ico"><i class="bi bi-calendar-range"></i></span>
                </div>
            </a>
        </div>
        <div class="col-6 col-xl-3">
            <a href="#kcScope" class="kc-stat" style="--st:#0ea5e9;--st-soft:#e0f2fe;">
                <div class="d-flex align-items-start justify-content-between gap-2">
                    <div class="min-w-0">
                        <div class="kc-stat-lbl mb-2">Scope Layanan</div>
                        <div class="kc-stat-val">{{ $scopeCount > 0 ? $scopeCount.' layanan' : 'Belum ada' }}</div>
                        <div class="kc-stat-sub">{{ $scopeCount > 0 ? $grupScope->count().' kategori' : 'Mitra belum dapat layanan' }}</div>
                    </div>
                    <span class="kc-stat-ico"><i class="bi bi-diagram-3"></i></span>
                </div>
            </a>
        </div>
        <div class="col-6 col-xl-3">
            <a href="#kcTagihan" class="kc-stat" style="--st:#d97706;--st-soft:#fef3c7;">
                <div class="d-flex align-items-start justify-content-between gap-2">
                    <div class="min-w-0">
                        <div class="kc-stat-lbl mb-2">Tagihan Terkait</div>
                        <div class="kc-stat-val">{{ $tagihanCount }} tagihan</div>
                        <div class="kc-stat-sub">{{ $tagihanCount > 0 ? 'Total '.$rupiah($totalNilaiTagihan) : 'Belum pernah ditagih' }}</div>
                    </div>
                    <span class="kc-stat-ico"><i class="bi bi-receipt-cutoff"></i></span>
                </div>
            </a>
        </div>
        <div class="col-6 col-xl-3">
            <div class="kc-stat" style="--st:#10b981;--st-soft:#d1fae5;">
                <div class="d-flex align-items-start justify-content-between gap-2">
                    <div class="min-w-0">
                        <div class="kc-stat-lbl mb-2">Data Operasional</div>
                        <div class="kc-stat-val">{{ $dataTerkaitTotal }} data</div>
                        <div class="kc-stat-sub">
                            @if($dataTerkaitTotal === 0) Belum ada konsesi/penjualan
                            @else @foreach(array_filter($dataTerkait) as $label => $jumlah){{ $label }} {{ $jumlah }}@if(! $loop->last) · @endif @endforeach @endif
                        </div>
                    </div>
                    <span class="kc-stat-ico"><i class="bi bi-collection"></i></span>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        {{-- ===== Informasi Kontrak ===== --}}
        <div class="col-lg-7">
            <div class="kc-card h-100">
                <div class="kc-card-head"><span class="ic"><i class="bi bi-info-circle"></i></span><h6>Informasi Kontrak</h6></div>
                <div class="p-4">
                    <div class="kc-info">
                        <div class="kc-info-item">
                            <div class="kc-info-label"><i class="bi bi-hash"></i>Nomor Kontrak</div>
                            <div class="kc-info-value">{{ $kontrak->nomor_kontrak ?: '-' }}</div>
                        </div>
                        <div class="kc-info-item">
                            <div class="kc-info-label"><i class="bi bi-file-earmark"></i>Jenis Dokumen</div>
                            <div class="kc-info-value">{{ $kontrak->jenis_dokumen ? ucwords(strtolower(str_replace('_', ' ', $kontrak->jenis_dokumen))) : '-' }}</div>
                        </div>
                        <div class="kc-info-item">
                            <div class="kc-info-label"><i class="bi bi-calendar-event"></i>Tanggal Ditandatangani</div>
                            <div class="kc-info-value">{{ optional($kontrak->tanggal_kontrak)->translatedFormat('d F Y') ?: '-' }}</div>
                        </div>
                        <div class="kc-info-item">
                            <div class="kc-info-label"><i class="bi bi-patch-check"></i>Status</div>
                            <div class="kc-info-value">
                                <span class="kc-badge {{ $statusMeta[1] }}"><i class="bi {{ $statusMeta[2] }}"></i>{{ $statusMeta[0] }}</span>
                            </div>
                            @if($statusMeta[3])<div class="kc-info-hint">{{ $statusMeta[3] }}</div>@endif
                        </div>
                        <div class="kc-info-item kc-span">
                            <div class="kc-info-label"><i class="bi bi-building"></i>Mitra</div>
                            <div class="kc-info-value"><a href="{{ route('jasa.mitra.show', $mitra) }}" class="text-decoration-none">{{ $mitra->nama_mitra }}</a></div>
                        </div>
                        <div class="kc-info-item kc-span">
                            <div class="kc-info-label"><i class="bi bi-chat-left-text"></i>Keterangan</div>
                            <div class="kc-info-value fw-normal">{{ $kontrak->keterangan ?: 'Tidak ada keterangan.' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ===== Masa berlaku + File ===== --}}
        <div class="col-lg-5">
            <div class="kc-card mb-4" id="kcMasa">
                <div class="kc-card-head">
                    <span class="ic"><i class="bi bi-calendar-range"></i></span><h6>Masa Berlaku</h6>
                    <span class="tools"><span class="kc-badge {{ $masaMeta[1] }}"><i class="bi {{ $masaMeta[2] }}"></i>{{ $masaMeta[0] }}</span></span>
                </div>
                <div class="p-4">
                    <div class="kc-timeline mb-3">
                        <div class="kc-tl-pt">
                            <div class="lbl"><i class="bi bi-play-circle text-success me-1"></i>Mulai</div>
                            <div class="val">{{ optional($mulai)->translatedFormat('d M Y') ?: '-' }}</div>
                        </div>
                        <span class="kc-tl-mid">{{ $durasiHari ? $durasiHari.' hari' : '—' }}</span>
                        <div class="kc-tl-pt text-end">
                            <div class="lbl">Selesai<i class="bi bi-flag-fill text-danger ms-1"></i></div>
                            <div class="val">{{ optional($selesai)->translatedFormat('d M Y') ?: '-' }}</div>
                        </div>
                    </div>
                    @if($progress !== null)
                        <div class="kc-progress {{ $masaState === 'BERAKHIR' ? 'is-end' : ($segeraBerakhir ? 'is-warn' : '') }}"><span style="width: {{ $masaState === 'BERAKHIR' ? 100 : $progress }}%"></span></div>
                        <div class="d-flex justify-content-between small text-muted mt-1">
                            <span>
                                @if($masaState === 'BERAKHIR') Periode selesai {{ abs($sisaHari) }} hari lalu
                                @elseif($masaState === 'AKAN_DATANG') Mulai dalam {{ (int) $today->diffInDays($mulai) }} hari
                                @else {{ $progress }}% periode berjalan @endif
                            </span>
                            @if($masaState === 'BERJALAN')<span class="fw-bold {{ $segeraBerakhir ? 'text-warning' : '' }}">Sisa {{ $sisaHari }} hari</span>@endif
                        </div>
                    @else
                        <div class="small text-muted">Tanggal mulai/selesai belum lengkap, sehingga masa berlaku tidak dapat dihitung.</div>
                    @endif
                </div>
            </div>

            <div class="kc-card">
                <div class="kc-card-head"><span class="ic"><i class="bi bi-paperclip"></i></span><h6>Dokumen Kontrak</h6></div>
                <div class="p-4">
                    @if($kontrak->file_kontrak)
                        <div class="kc-file">
                            <span class="kc-file-ico"><i class="bi bi-file-earmark-pdf"></i></span>
                            <div class="kc-file-info">
                                <div class="nm">Kontrak {{ $kontrak->nomor_kontrak ?: $kontrak->nama_kontrak }}</div>
                                <div class="small text-muted">{{ $fileExt }}@if($ukuranFileKontrak) · {{ $ukuranFileKontrak }}@endif</div>
                            </div>
                            <div class="kc-file-acts">
                                <a href="{{ route('jasa.mitra.kontrak.download', [$mitra, $kontrak]) }}?inline=1" target="_blank" rel="noopener" class="btn btn-light border fw-bold"><i class="bi bi-eye me-1"></i>Lihat</a>
                                <a href="{{ route('jasa.mitra.kontrak.download', [$mitra, $kontrak]) }}" class="btn btn-primary fw-bold"><i class="bi bi-download me-1"></i>Unduh</a>
                            </div>
                        </div>
                    @else
                        <div class="kc-empty">
                            <i class="bi bi-file-earmark-x big"></i>
                            Belum ada file dokumen diunggah.
                            @if($canManageMitraMaster)
                                <div class="mt-2"><a href="{{ route('jasa.mitra.kontrak.edit', [$mitra, $kontrak]) }}" class="btn btn-sm btn-outline-primary fw-bold"><i class="bi bi-upload me-1"></i>Unggah dokumen</a></div>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- ===== Scope Layanan ===== --}}
    <div class="kc-card mt-4" id="kcScope">
        <div class="kc-card-head">
            <span class="ic"><i class="bi bi-diagram-3"></i></span>
            <div>
                <h6>Scope Layanan</h6>
                <div class="sub">Layanan yang boleh ditagihkan ke mitra selama kontrak ini aktif.</div>
            </div>
            <span class="tools">
                <span class="kc-badge {{ $scopeCount > 0 ? 'kc-blue' : 'kc-amber' }}">{{ $scopeCount > 0 ? $scopeCount.' layanan' : 'Belum ada layanan' }}</span>
                @if($canManageMitraMaster)
                    <a href="{{ route('jasa.mitra.kontrak.edit', [$mitra, $kontrak]) }}#scope-layanan" class="btn btn-sm btn-light border text-primary fw-bold"><i class="bi bi-pencil me-1"></i>Edit layanan</a>
                @endif
            </span>
        </div>
        <div class="p-4">
            @if($scopeCount === 0)
                <div class="kc-empty">
                    <i class="bi bi-diagram-3 big"></i>
                    <div class="fw-bold text-dark">Belum ada layanan dalam scope kontrak ini.</div>
                    <div class="small">Mitra tidak memperoleh layanan apa pun dari kontrak ini sampai layanan dipilih.</div>
                </div>
            @else
                @foreach($grupScope as $root => $items)
                    <div class="kc-group">
                        <div class="kc-group-head">
                            <span class="fi"><i class="bi bi-folder2-open"></i></span>
                            <span class="tt">{{ $root }}</span>
                            <span class="n">{{ $items->count() }} layanan</span>
                        </div>
                        <div class="kc-group-body">
                            @foreach($items->groupBy('sub') as $sub => $rows)
                                @if($sub !== '')<div class="kc-group-sub"><i class="bi bi-diagram-2"></i>{{ $sub }}</div>@endif
                                <div class="kc-svc-list">
                                    @foreach($rows as $row)
                                        @php
                                            $layanan = $row['layanan'];
                                            $kode = $layanan->kode_layanan ?: str_pad($layanan->id, 6, '0', STR_PAD_LEFT);
                                        @endphp
                                        <div class="kc-svc">
                                            <span class="kc-svc-ico"><i class="bi bi-check2"></i></span>
                                            <div class="kc-svc-body">
                                                <div class="kc-svc-name">{{ $layanan->nama_layanan }}</div>
                                                <div class="kc-svc-tags">
                                                    <span class="kc-svc-code" title="{{ $kode }}">{{ $kode }}</span>
                                                    @if($layanan->satuan)<span class="kc-svc-meta"><i class="bi bi-rulers"></i>{{ $layanan->satuan }}</span>@endif
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            @endif
        </div>
    </div>

    {{-- ===== Tagihan terkait ===== --}}
    <div class="kc-card mt-4" id="kcTagihan">
        <div class="kc-card-head">
            <span class="ic"><i class="bi bi-receipt-cutoff"></i></span>
            <div>
                <h6>Tagihan dari Kontrak Ini</h6>
                <div class="sub">{{ $tagihanCount > 5 ? '5 tagihan terbaru dari '.$tagihanCount : 'Tagihan jasa yang memakai kontrak ini' }}</div>
            </div>
            @if($tagihanCount > 0)
                <span class="tools"><span class="kc-badge kc-amber">Total {{ $rupiah($totalNilaiTagihan) }}</span></span>
            @endif
        </div>
        <div class="p-3 p-md-4">
            @if($tagihanTerbaru->isEmpty())
                <div class="kc-empty">
                    <i class="bi bi-receipt big"></i>
                    Belum ada tagihan yang memakai kontrak ini.
                </div>
            @else
                <table class="kc-tagihan">
                    <thead><tr><th>Nomor Tagihan</th><th>Tanggal</th><th class="text-end">Nilai</th><th class="text-end">Status</th></tr></thead>
                    <tbody>
                        @foreach($tagihanTerbaru as $tagihan)
                            <tr>
                                <td><a href="{{ route('tagihan-jasa.show', $tagihan->id) }}" class="no">{{ $tagihan->nomor_tagihan }}</a></td>
                                <td class="tgl">{{ optional($tagihan->tanggal_tagihan)->translatedFormat('d M Y') ?: '-' }}</td>
                                <td class="nil text-end fw-bold">{{ $rupiah($tagihan->total_tagihan) }}</td>
                                <td class="st text-end"><span class="kc-badge {{ $statusTagihanKelas($tagihan->status) }}">{{ str_replace('_', ' ', $tagihan->status ?: '-') }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
</div>
@endsection
