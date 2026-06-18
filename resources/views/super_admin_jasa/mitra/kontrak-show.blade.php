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
    if ($mulai && $selesai) {
        $total = max(1, $mulai->diffInDays($selesai));
        $elapsed = max(0, min($total, $mulai->diffInDays($today)));
        $progress = (int) round($elapsed / $total * 100);
    }

    $masaMeta = match ($masaState) {
        'BERJALAN' => ['Berjalan', 'kc-emerald', 'bi-broadcast'],
        'AKAN_DATANG' => ['Akan datang', 'kc-blue', 'bi-clock-history'],
        'BERAKHIR' => ['Berakhir', 'kc-slate', 'bi-calendar-x'],
        default => ['Tanggal belum diset', 'kc-slate', 'bi-dash-circle'],
    };

    $statusKontrak = strtoupper((string) $kontrak->status_kontrak);
    $statusAktif = str_contains($statusKontrak, 'AKTIF') && ! str_contains($statusKontrak, 'NON');

    $scopeCount = $kontrak->layananJasa->count();
@endphp

@push('css')
<style>
    .kc-scope { --kc-accent:#4f46e5; --kc-accent-2:#6366f1; --kc-soft:rgba(79,70,229,.12); }
    @keyframes kcReveal { from { opacity:0; transform:translateY(16px); } to { opacity:1; transform:translateY(0); } }
    @keyframes kcSweep { 0% { transform:translateX(-120%) skewX(-16deg); opacity:0; } 18% { opacity:.4; } 55%,100% { transform:translateX(240%) skewX(-16deg); opacity:0; } }

    .kc-hero {
        position:relative; overflow:hidden; border-radius:22px; padding:26px 28px; color:#fff;
        background:radial-gradient(120% 140% at 88% -10%, rgba(99,102,241,.35), transparent 46%), linear-gradient(115deg,#0b1220 0%,#1e1b4b 48%,#3730a3 100%);
        border:1px solid rgba(148,163,184,.18); border-top:2px solid var(--kc-accent-2);
        box-shadow:0 22px 56px rgba(15,23,42,.30); animation:kcReveal .5s cubic-bezier(.2,.8,.2,1) both;
    }
    .kc-hero::after { content:""; position:absolute; inset:0; width:44%; background:linear-gradient(90deg,transparent,rgba(255,255,255,.15),transparent); animation:kcSweep 4.6s ease-in-out infinite; pointer-events:none; }
    .kc-hero > * { position:relative; z-index:1; }
    .kc-hero-icon { width:56px; height:56px; flex:0 0 56px; border-radius:16px; display:inline-flex; align-items:center; justify-content:center; font-size:1.7rem; color:#1e1b4b; background:linear-gradient(140deg,#c7d2fe,#a5b4fc); box-shadow:0 14px 30px rgba(99,102,241,.4); }
    .kc-kicker { letter-spacing:.14em; font-size:10px; font-weight:900; text-transform:uppercase; color:#c7d2fe; }
    .kc-hero h4 { font-weight:900; letter-spacing:-.01em; }
    .kc-hero-btn { border:1px solid rgba(199,210,254,.4); background:rgba(255,255,255,.10); color:#fff; border-radius:11px; width:40px; height:40px; display:inline-flex; align-items:center; justify-content:center; transition:all .15s ease; }
    .kc-hero-btn:hover { background:rgba(255,255,255,.2); color:#fff; transform:translateY(-1px); }
    .kc-hero-btn.danger:hover { background:rgba(239,68,68,.85); border-color:transparent; }
    .kc-chip { display:inline-flex; align-items:center; gap:7px; padding:7px 13px; border-radius:999px; font-size:12px; font-weight:800; background:rgba(255,255,255,.1); border:1px solid rgba(255,255,255,.18); }

    .kc-stat { height:100%; border-radius:16px; background:#fff; padding:16px 18px; border:1px solid rgba(15,23,42,.07); box-shadow:0 12px 30px rgba(15,23,42,.06); position:relative; overflow:hidden; animation:kcReveal .5s ease both; }
    .kc-stat::before { content:""; position:absolute; left:0; top:0; bottom:0; width:4px; background:var(--st,#4f46e5); }
    .kc-stat-ico { width:40px; height:40px; border-radius:12px; display:inline-flex; align-items:center; justify-content:center; font-size:1.1rem; color:var(--st,#4f46e5); background:var(--st-soft,rgba(79,70,229,.12)); }
    .kc-stat-lbl { font-size:10px; font-weight:900; letter-spacing:.04em; text-transform:uppercase; color:#94a3b8; }
    .kc-stat-val { font-size:20px; font-weight:900; color:#0f172a; line-height:1.15; }

    .kc-card { border:1px solid rgba(15,23,42,.08); border-radius:18px; background:#fff; box-shadow:0 16px 40px rgba(15,23,42,.06); overflow:hidden; height:100%; }
    .kc-card-head { display:flex; align-items:center; gap:10px; padding:13px 18px; border-bottom:1px solid #eef2f7; background:linear-gradient(90deg,#eef2ff,#fff); }
    .kc-card-head .ic { width:34px; height:34px; border-radius:9px; display:inline-flex; align-items:center; justify-content:center; color:#fff; background:linear-gradient(140deg,var(--kc-accent),var(--kc-accent-2)); }
    .kc-card-head h6 { margin:0; font-weight:900; color:#1e1b4b; }

    .kc-info { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
    .kc-info-item { border:1px solid #eef2f7; border-radius:13px; background:linear-gradient(180deg,#fbfcff,#fff); padding:12px 14px; }
    .kc-info-item.kc-span { grid-column:1 / -1; }
    .kc-info-label { font-size:10px; font-weight:900; letter-spacing:.04em; text-transform:uppercase; color:#94a3b8; display:flex; align-items:center; gap:6px; }
    .kc-info-label i { color:var(--kc-accent); }
    .kc-info-value { font-weight:800; color:#0f172a; margin-top:4px; word-break:break-word; }

    .kc-badge { display:inline-flex; align-items:center; gap:.35rem; padding:5px 11px; border-radius:999px; font-size:11px; font-weight:900; }
    .kc-emerald { color:#047857; background:#d1fae5; } .kc-blue { color:#1d4ed8; background:#dbeafe; }
    .kc-slate { color:#475569; background:#e2e8f0; } .kc-amber { color:#92400e; background:#fef3c7; }

    .kc-progress { height:10px; border-radius:999px; background:#eef2f7; overflow:hidden; }
    .kc-progress span { display:block; height:100%; border-radius:999px; background:linear-gradient(90deg,var(--kc-accent),var(--kc-accent-2)); }

    .kc-file { display:flex; align-items:center; gap:14px; border:1px dashed #c7d2fe; border-radius:14px; background:#eef2ff; padding:14px 16px; }
    .kc-file-ico { width:46px; height:46px; flex:0 0 46px; border-radius:12px; display:inline-flex; align-items:center; justify-content:center; font-size:1.4rem; color:#fff; background:linear-gradient(140deg,#ef4444,#dc2626); }

    .kc-svc { display:inline-flex; align-items:center; gap:8px; border:1px solid #e2e8f0; background:#fff; border-radius:11px; padding:8px 12px; font-weight:700; color:#1e293b; }
    .kc-svc .kc-svc-code { font-family:ui-monospace,Menlo,Consolas,monospace; font-size:11px; color:#fff; background:var(--kc-accent); border-radius:6px; padding:2px 7px; }

    @media (max-width:575.98px){ .kc-info{ grid-template-columns:1fr; } }
</style>
@endpush

@section('content')
<div class="kc-scope">

    {{-- ===== Hero ===== --}}
    <div class="kc-hero mb-4">
        <div class="d-flex flex-column flex-lg-row align-items-start align-items-lg-center justify-content-between gap-3">
            <div class="d-flex align-items-start gap-3">
                <span class="kc-hero-icon"><i class="bi bi-file-earmark-text"></i></span>
                <div>
                    <div class="kc-kicker mb-1">Kontrak / Dokumen Mitra Jasa</div>
                    <h4 class="mb-1 text-white">{{ $kontrak->nama_kontrak ?: 'Kontrak Mitra Jasa' }}</h4>
                    <p class="mb-2 small fw-semibold" style="color:#c7d2fe;">
                        <i class="bi bi-building me-1"></i>{{ $mitra->nama_mitra }}
                        <span class="mx-2 opacity-50">•</span>
                        <i class="bi bi-hash"></i>{{ $kontrak->nomor_kontrak ?: '—' }}
                    </p>
                    <div class="d-flex flex-wrap gap-2">
                        <span class="kc-chip"><i class="bi {{ $statusAktif ? 'bi-check-circle-fill text-success' : 'bi-pause-circle' }}"></i>{{ $kontrak->status_kontrak ?: 'Status -' }}</span>
                        <span class="kc-chip"><i class="bi {{ $masaMeta[2] }}"></i>{{ $masaMeta[0] }}</span>
                        @if($masaState === 'BERJALAN' && $sisaHari !== null)
                            <span class="kc-chip"><i class="bi bi-hourglass-split"></i>Sisa {{ $sisaHari }} hari</span>
                        @endif
                    </div>
                </div>
            </div>
            <div class="d-flex gap-2">
                @if($canManageMitraMaster)
                    <a href="{{ route('jasa.mitra.kontrak.edit', [$mitra, $kontrak]) }}" class="kc-hero-btn" title="Edit" aria-label="Edit"><i class="bi bi-pencil"></i></a>
                    <form method="POST" action="{{ route('jasa.mitra.kontrak.destroy', [$mitra, $kontrak]) }}" onsubmit="return confirm('Hapus kontrak/dokumen ini? Data yang sudah dipakai tagihan tidak bisa dihapus.');">
                        @csrf @method('DELETE')
                        <button type="submit" class="kc-hero-btn danger" title="Hapus" aria-label="Hapus"><i class="bi bi-trash"></i></button>
                    </form>
                @endif
                <a href="{{ route('jasa.mitra.show', $mitra) }}" class="kc-hero-btn" title="Kembali" aria-label="Kembali"><i class="bi bi-arrow-left"></i></a>
            </div>
        </div>
    </div>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

    {{-- ===== Stat tiles ===== --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="kc-stat" style="--st:#4f46e5;--st-soft:rgba(79,70,229,.12);">
                <div class="d-flex align-items-start justify-content-between gap-2">
                    <div>
                        <div class="kc-stat-lbl mb-2">Masa Berlaku</div>
                        <div class="kc-stat-val">
                            @if($masaState === 'BERJALAN' && $sisaHari !== null) Sisa {{ $sisaHari }} hari
                            @elseif($masaState === 'AKAN_DATANG') Belum mulai
                            @elseif($masaState === 'BERAKHIR') Berakhir
                            @else — @endif
                        </div>
                    </div>
                    <span class="kc-stat-ico" style="--st:#4f46e5;--st-soft:rgba(79,70,229,.12);"><i class="bi bi-calendar-range"></i></span>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="kc-stat" style="--st:#0ea5e9;--st-soft:#e0f2fe;">
                <div class="d-flex align-items-start justify-content-between gap-2">
                    <div>
                        <div class="kc-stat-lbl mb-2">Scope Layanan</div>
                        <div class="kc-stat-val">{{ $scopeCount > 0 ? $scopeCount.' layanan' : 'Semua' }}</div>
                    </div>
                    <span class="kc-stat-ico" style="--st:#0ea5e9;--st-soft:#e0f2fe;"><i class="bi bi-list-check"></i></span>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="kc-stat" style="--st:#d97706;--st-soft:#fef3c7;">
                <div class="d-flex align-items-start justify-content-between gap-2">
                    <div>
                        <div class="kc-stat-lbl mb-2">Tagihan Terkait</div>
                        <div class="kc-stat-val">{{ $kontrak->tagihan_jasa_count ?? 0 }} tagihan</div>
                    </div>
                    <span class="kc-stat-ico" style="--st:#d97706;--st-soft:#fef3c7;"><i class="bi bi-receipt-cutoff"></i></span>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        {{-- ===== Informasi Kontrak ===== --}}
        <div class="col-lg-7">
            <div class="kc-card">
                <div class="kc-card-head"><span class="ic"><i class="bi bi-info-circle"></i></span><h6>Informasi Kontrak / Dokumen</h6></div>
                <div class="p-4">
                    <div class="kc-info">
                        <div class="kc-info-item">
                            <div class="kc-info-label"><i class="bi bi-hash"></i>Nomor Kontrak</div>
                            <div class="kc-info-value">{{ $kontrak->nomor_kontrak ?: '-' }}</div>
                        </div>
                        <div class="kc-info-item">
                            <div class="kc-info-label"><i class="bi bi-card-heading"></i>Nama Kontrak</div>
                            <div class="kc-info-value">{{ $kontrak->nama_kontrak ?: '-' }}</div>
                        </div>
                        <div class="kc-info-item">
                            <div class="kc-info-label"><i class="bi bi-file-earmark"></i>Jenis Dokumen</div>
                            <div class="kc-info-value">{{ $kontrak->jenis_dokumen ? str_replace('_', ' ', $kontrak->jenis_dokumen) : '-' }}</div>
                        </div>
                        <div class="kc-info-item">
                            <div class="kc-info-label"><i class="bi bi-calendar-event"></i>Tanggal Kontrak</div>
                            <div class="kc-info-value">{{ optional($kontrak->tanggal_kontrak)->translatedFormat('d F Y') ?: '-' }}</div>
                        </div>
                        <div class="kc-info-item">
                            <div class="kc-info-label"><i class="bi bi-patch-check"></i>Status</div>
                            <div class="kc-info-value">
                                <span class="kc-badge {{ $statusAktif ? 'kc-emerald' : 'kc-slate' }}"><i class="bi {{ $statusAktif ? 'bi-check-circle' : 'bi-pause-circle' }}"></i>{{ $kontrak->status_kontrak ?: '-' }}</span>
                            </div>
                        </div>
                        <div class="kc-info-item kc-span">
                            <div class="kc-info-label"><i class="bi bi-chat-left-text"></i>Keterangan</div>
                            <div class="kc-info-value fw-normal">{{ $kontrak->keterangan ?: '-' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ===== Masa berlaku + File ===== --}}
        <div class="col-lg-5">
            <div class="kc-card mb-4">
                <div class="kc-card-head"><span class="ic"><i class="bi bi-calendar-range"></i></span><h6>Masa Berlaku</h6></div>
                <div class="p-4">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="kc-badge {{ $masaMeta[1] }}"><i class="bi {{ $masaMeta[2] }}"></i>{{ $masaMeta[0] }}</span>
                        @if($masaState === 'BERJALAN' && $sisaHari !== null)
                            <span class="small fw-bold text-muted">Sisa {{ $sisaHari }} hari</span>
                        @endif
                    </div>
                    <div class="d-flex justify-content-between small fw-bold text-dark mb-1">
                        <span><i class="bi bi-play-circle text-success me-1"></i>{{ optional($mulai)->translatedFormat('d M Y') ?: '-' }}</span>
                        <span>{{ optional($selesai)->translatedFormat('d M Y') ?: '-' }}<i class="bi bi-flag-fill text-danger ms-1"></i></span>
                    </div>
                    @if($progress !== null)
                        <div class="kc-progress"><span style="width: {{ $masaState === 'BERAKHIR' ? 100 : $progress }}%"></span></div>
                        <div class="small text-muted mt-1">{{ $masaState === 'BERAKHIR' ? 'Periode telah selesai' : $progress.'% periode berjalan' }}</div>
                    @else
                        <div class="small text-muted">Rentang tanggal belum lengkap.</div>
                    @endif
                </div>
            </div>

            <div class="kc-card">
                <div class="kc-card-head"><span class="ic"><i class="bi bi-paperclip"></i></span><h6>Dokumen</h6></div>
                <div class="p-4">
                    @if($kontrak->file_kontrak)
                        <div class="kc-file">
                            <span class="kc-file-ico"><i class="bi bi-file-earmark-pdf"></i></span>
                            <div class="flex-grow-1 min-w-0">
                                <div class="fw-bold text-dark">Dokumen Kontrak</div>
                                <div class="small text-muted text-truncate">{{ basename($kontrak->file_kontrak) }}</div>
                            </div>
                            <a href="{{ route('jasa.mitra.kontrak.download', [$mitra, $kontrak]) }}" class="btn btn-primary fw-bold"><i class="bi bi-download me-1"></i>Unduh</a>
                        </div>
                    @else
                        <div class="text-center text-muted py-3">
                            <i class="bi bi-file-earmark-x fs-3 d-block mb-2 opacity-50"></i>
                            Belum ada file dokumen diunggah.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- ===== Scope Layanan ===== --}}
    <div class="kc-card mt-4">
        <div class="kc-card-head">
            <span class="ic"><i class="bi bi-list-check"></i></span>
            <h6>Scope Layanan</h6>
            <span class="ms-auto kc-badge {{ $scopeCount > 0 ? 'kc-blue' : 'kc-emerald' }}">
                {{ $scopeCount > 0 ? $scopeCount.' layanan dipilih' : 'Semua layanan aktif mitra' }}
            </span>
        </div>
        <div class="p-4">
            @if($scopeCount === 0)
                <div class="alert alert-primary border-0 bg-primary-subtle text-dark mb-0">
                    <i class="bi bi-stars me-1"></i>Kontrak ini berlaku untuk <strong>seluruh layanan aktif</strong> milik mitra (tidak dibatasi ke layanan tertentu).
                </div>
            @else
                <div class="d-flex flex-wrap gap-2">
                    @foreach($kontrak->layananJasa as $layanan)
                        <span class="kc-svc">
                            <span class="kc-svc-code">{{ $layanan->kode_layanan ?: str_pad($layanan->id, 6, '0', STR_PAD_LEFT) }}</span>
                            {{ $layanan->nama_layanan }}
                        </span>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
