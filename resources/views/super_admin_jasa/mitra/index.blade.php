@extends('layouts.app')
@section('title', 'Mitra Jasa')

@section('content')
@php
    $canManageMitraMaster = auth()->user()?->hasAnyRole(['Super Admin', 'Super Admin Jasa', 'Koordinator Jasa']) === true;

    // Warna aksen per jenis mitra (avatar, chip jenis, garis atas kartu).
    $warnaJenis = [
        'BADAN_USAHA' => '#2563eb', 'PERORANGAN' => '#0d9488', 'INSTANSI' => '#7c3aed',
        'MASKAPAI' => '#0284c7', 'TENANT' => '#d97706', 'OPERATOR' => '#4f46e5', 'LAINNYA' => '#475569',
    ];
    // Inisial nama tanpa awalan badan usaha (PT/CV/UD/...).
    $inisialMitra = function (?string $nama): string {
        $bersih = preg_replace('/^(PT|CV|UD|PD|KOPERASI|YAYASAN)\.?\s+/i', '', trim((string) $nama));
        $kata = preg_split('/\s+/', $bersih ?: '?', -1, PREG_SPLIT_NO_EMPTY);

        return strtoupper(mb_substr($kata[0] ?? '?', 0, 1) . mb_substr($kata[1] ?? '', 0, 1));
    };
    // Nomor WA untuk tautan wa.me (08xx -> 628xx).
    $nomorWa = function (?string $no): ?string {
        $digit = preg_replace('/\D+/', '', (string) $no);
        if ($digit === '') {
            return null;
        }

        return str_starts_with($digit, '0') ? '62' . substr($digit, 1) : $digit;
    };
@endphp

<style>
    @keyframes blueHeaderGlow {
        0%, 100% { opacity: .55; transform: translate3d(-28px, 0, 0) scale(1); }
        50% { opacity: .95; transform: translate3d(72px, -18px, 0) scale(1.12); }
    }
    @keyframes blueHeaderSweep {
        0% { transform: translateX(-120%) skewX(-18deg); opacity: 0; }
        18% { opacity: .35; }
        45%, 100% { transform: translateX(220%) skewX(-18deg); opacity: 0; }
    }
    .sa-hero {
        position: relative;
        isolation: isolate;
        overflow: hidden;
        border: 1px solid rgba(30, 64, 175, .20);
        border-radius: 18px;
        background: linear-gradient(110deg, #12355c, #174f86 55%, #1d65a6);
        color: #fff;
        padding: 24px;
        box-shadow: 0 18px 50px rgba(18, 53, 92, .22);
    }
    .sa-hero::before,
    .sa-hero::after {
        content: "";
        position: absolute;
        pointer-events: none;
        z-index: -1;
    }
    .sa-hero::before {
        width: 360px;
        height: 360px;
        right: 8%;
        top: -170px;
        border-radius: 999px;
        background: radial-gradient(circle, rgba(125, 211, 252, .32), rgba(59, 130, 246, .22) 42%, transparent 68%);
        animation: blueHeaderGlow 4.5s ease-in-out infinite;
    }
    .sa-hero::after {
        inset: 0;
        width: 48%;
        background: linear-gradient(90deg, transparent, rgba(125,211,252,.16), rgba(255,255,255,.24), rgba(96,165,250,.14), transparent);
        animation: blueHeaderSweep 3.8s ease-in-out infinite;
    }
    .sa-hero-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 46px;
        height: 46px;
        flex: 0 0 46px;
        border-radius: 14px;
        background: #2563eb;
        color: #fff;
        box-shadow: 0 14px 28px rgba(37, 99, 235, .26);
    }
    .sa-card {
        overflow: hidden;
        border: 1px solid rgba(37, 99, 235, .12);
        border-radius: 18px;
        background: #fff;
        box-shadow: 0 16px 42px rgba(37, 99, 235, .08);
    }
    .sa-table-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        padding: 10px 14px;
        border-bottom: 1px solid #bfdbfe;
        background: linear-gradient(90deg, #eff6ff 0%, #f8fbff 58%, #ffffff 100%);
    }
    .sa-table-title {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .sa-table-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 34px;
        height: 34px;
        flex: 0 0 34px;
        border-radius: 9px;
        color: #fff;
        background: #1d4ed8;
        box-shadow: 0 10px 20px rgba(37, 99, 235, .18);
    }
    .sa-table-title h6 {
        margin: 0;
        color: #1e3a8a;
        font-weight: 800;
    }
    .sa-table-title small {
        color: #64748b;
        font-weight: 700;
    }
    .sa-table {
        border-collapse: separate;
        border-spacing: 0;
    }
    .sa-table thead th {
        padding: 13px 16px;
        border-bottom: 1px solid rgba(148, 163, 184, .20);
        color: #64748b;
        background: rgba(248, 250, 252, .86);
        font-size: 11px;
        font-weight: 900;
        letter-spacing: .03em;
        text-transform: uppercase;
        white-space: nowrap;
    }
    .sa-table tbody td {
        padding: 14px 16px;
        border-bottom: 1px solid rgba(226, 232, 240, .88);
        color: #475569;
        vertical-align: middle;
    }
    .sa-table tbody tr:hover {
        background: rgba(239, 246, 255, .72);
    }
    .sa-table tbody tr:last-child td {
        border-bottom: 0;
    }
    .sa-name {
        color: #12355c;
        font-weight: 900;
        letter-spacing: .02em;
    }
    .sa-soft-badge {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        padding: 6px 10px;
        border-radius: 999px;
        font-size: 10px;
        font-weight: 900;
        letter-spacing: .02em;
        white-space: nowrap;
    }
    .sa-soft-badge.success {
        color: #047857;
        background: #d1fae5;
    }
    .sa-soft-badge.warning {
        color: #92400e;
        background: #fef3c7;
    }
    .sa-soft-badge.muted {
        color: #475569;
        background: #e2e8f0;
    }
    .sa-action {
        border-radius: 10px;
        font-weight: 800;
    }

    /* ===== Pemilih tampilan ===== */
    .mj-toolbar { display: flex; align-items: center; gap: .6rem; flex-wrap: wrap; }
    .mj-switch { display: inline-flex; padding: 3px; border-radius: 12px; background: #e0ecff; border: 1px solid #bfdbfe; }
    .mj-switch button {
        display: inline-flex; align-items: center; gap: .35rem; border: 0; background: transparent;
        padding: 6px 12px; border-radius: 9px; color: #1e40af; font-size: 13px; font-weight: 800;
    }
    .mj-switch button.active { background: #fff; color: #1d4ed8; box-shadow: 0 4px 12px rgba(37, 99, 235, .18); }
    #mitraList[data-view="table"] .mj-view-card,
    #mitraList[data-view="card"] .mj-view-table { display: none !important; }

    /* ===== Elemen bersama ===== */
    .mj-avatar {
        display: inline-flex; align-items: center; justify-content: center; flex: none;
        width: 42px; height: 42px; border-radius: 13px; color: #fff; font-weight: 900; font-size: 15px;
        background: var(--mj-c, #2563eb); box-shadow: 0 8px 18px color-mix(in srgb, var(--mj-c, #2563eb) 28%, transparent);
    }
    .mj-chip {
        display: inline-flex; align-items: center; gap: .3rem; padding: 3px 8px; border-radius: 7px;
        font-size: 11px; font-weight: 800; background: #f1f5f9; color: #475569; white-space: nowrap;
    }
    .mj-chip.kode { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; background: #eef2ff; color: #3730a3; }
    .mj-chip.jenis { background: color-mix(in srgb, var(--mj-c, #2563eb) 12%, #fff); color: var(--mj-c, #2563eb); }
    .mj-contact { display: flex; align-items: center; gap: .4rem; color: #334155; font-size: 13px; text-decoration: none; min-width: 0; }
    .mj-contact i { color: #64748b; flex: none; }
    .mj-contact span { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    a.mj-contact:hover { color: #1d4ed8; }
    .mj-muted { color: #94a3b8; font-size: 12px; }
    .mj-stat {
        display: inline-flex; flex-direction: column; align-items: center; justify-content: center;
        min-width: 58px; padding: 5px 5px; white-space: nowrap; border-radius: 10px; background: #f8fafc; border: 1px solid #e2e8f0;
    }
    .mj-stat b { font-size: 15px; line-height: 1.1; color: #0f172a; }
    .mj-stat small { font-size: 9.5px; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0; }
    .mj-stat.zero b { color: #cbd5e1; }
    .mj-stat.warn { background: #fffbeb; border-color: #fde68a; }

    /* ===== Tabel ===== */
    .mj-table td:first-child, .mj-table th:first-child {
        position: sticky; left: 0; z-index: 1; background: #fff; box-shadow: 1px 0 0 rgba(226, 232, 240, .9);
    }
    .mj-table thead th:first-child { background: rgba(248, 250, 252, 1); z-index: 2; }
    .mj-table tbody tr:hover td:first-child { background: #f5f9ff; }
    .mj-table thead th, .mj-table tbody td { padding-left: 12px !important; padding-right: 12px !important; }
    .mj-table .mj-name-cell { min-width: 230px; }
    .mj-table .mj-contact-cell { min-width: 200px; max-width: 228px; }
    .mj-table .mj-npwp { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 12px; color: #475569; white-space: nowrap; }
    .mj-table .mj-action-cell { white-space: nowrap; }

    /* ===== Kartu ===== */
    .mj-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 16px; padding: 16px; background: #f8fafc; }
    .mj-card {
        position: relative; display: flex; flex-direction: column; overflow: hidden;
        border: 1px solid #e2e8f0; border-radius: 18px; background: #fff;
        box-shadow: 0 8px 22px rgba(15, 23, 42, .05); transition: transform .18s ease, box-shadow .18s ease;
    }
    .mj-card:hover { transform: translateY(-2px); box-shadow: 0 16px 34px rgba(37, 99, 235, .12); }
    .mj-card::before { content: ""; height: 5px; background: linear-gradient(90deg, var(--mj-c, #2563eb), color-mix(in srgb, var(--mj-c, #2563eb) 45%, #fff)); }
    .mj-card.nonaktif { opacity: .82; }
    .mj-card.nonaktif::before { background: #cbd5e1; }
    .mj-card-head { display: flex; gap: 12px; padding: 16px 16px 10px; }
    .mj-card-head .mj-avatar { width: 50px; height: 50px; font-size: 17px; border-radius: 15px; }
    .mj-card-name { margin: 0; font-size: 16px; font-weight: 900; color: #12355c; line-height: 1.25; overflow-wrap: anywhere; }
    .mj-card-name a { color: inherit; text-decoration: none; }
    .mj-card-name a:hover { color: #1d4ed8; }
    .mj-card-tags { display: flex; flex-wrap: wrap; gap: 5px; margin-top: 6px; }
    .mj-card-body { display: grid; gap: 7px; padding: 4px 16px 12px; }
    .mj-card-row { display: grid; grid-template-columns: 22px minmax(0, 1fr); align-items: center; gap: 6px; font-size: 13px; color: #334155; }
    .mj-card-row i { color: #64748b; }
    .mj-card-row .mj-contact { gap: 0; }
    .mj-card-stats { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 6px; padding: 0 16px 14px; }
    .mj-card-stats .mj-stat { min-width: 0; }
    .mj-card-alert { margin: 0 16px 12px; padding: 7px 10px; border-radius: 10px; font-size: 12px; font-weight: 700; background: #fffbeb; color: #92400e; border: 1px solid #fde68a; }
    .mj-card-foot {
        display: flex; align-items: center; justify-content: space-between; gap: 8px; margin-top: auto;
        padding: 10px 16px; border-top: 1px solid #eef2f7; background: #fbfdff;
    }
    .mj-empty { padding: 48px 16px; text-align: center; color: #64748b; }
    #mitraList .min-w-0 { min-width: 0; }
    #mitraList .flex-none { flex: none; }
    .mj-empty i { font-size: 40px; color: #93c5fd; }
    @media (max-width: 575.98px) {
        .mj-grid { grid-template-columns: 1fr; padding: 12px; gap: 12px; }
        .sa-table-header { flex-wrap: wrap; }
    }
</style>

<div class="sa-hero mb-4">
    <div class="d-flex flex-column flex-lg-row align-items-start align-items-lg-center justify-content-between gap-3">
        <div class="d-flex align-items-start gap-3">
            <span class="sa-hero-icon"><i class="bi bi-building-check fs-4"></i></span>
            <div>
                <h4 class="mb-1 fw-black text-white">Mitra Jasa</h4>
                <p class="mb-0 small fw-semibold text-white-50">Kelola mitra penerimaan jasa/PNBP, akun mitra, layanan, dan laporan penjualan.</p>
            </div>
        </div>
        @if($canManageMitraMaster)
            <a href="{{ route('jasa.mitra.create') }}" class="btn btn-light fw-bold text-primary shadow-sm jasa-icon-btn" title="Tambah mitra" aria-label="Tambah mitra">
                <i class="bi bi-plus-lg"></i>
            </a>
        @endif
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<div class="sa-card" id="mitraList" data-view="table">
    <div class="sa-table-header">
        <div class="sa-table-title">
            <span class="sa-table-icon"><i class="bi bi-buildings"></i></span>
            <div>
                <h6>Daftar Mitra Jasa</h6>
                <small>Mitra, akun, layanan aktif, kontrak, dan laporan penjualan.</small>
            </div>
        </div>
        <div class="mj-toolbar">
            <span class="badge rounded-pill bg-primary-subtle text-primary px-3 py-2">{{ number_format($mitras->total(), 0, ',', '.') }} mitra</span>
            <div class="mj-switch" role="group" aria-label="Pilih tampilan daftar mitra">
                <button type="button" data-mj-view="table" aria-pressed="true" title="Tampilan tabel"><i class="bi bi-table"></i> Tabel</button>
                <button type="button" data-mj-view="card" aria-pressed="false" title="Tampilan kartu"><i class="bi bi-grid-3x2-gap-fill"></i> Kartu</button>
            </div>
        </div>
    </div>

    {{-- ===== Tampilan kartu ===== --}}
    <div class="mj-view-card">
        @if($mitras->isEmpty())
            <div class="mj-empty"><i class="bi bi-buildings"></i><div class="mt-2 fw-semibold">Belum ada mitra jasa.</div></div>
        @else
            <div class="mj-grid">
                @foreach($mitras as $mitra)
                    @php
                        $warna = $warnaJenis[$mitra->jenis_mitra] ?? '#2563eb';
                        $wa = $nomorWa($mitra->no_telepon);
                        $kontrakAktif = $mitra->kontrakAktif->count();
                        $layananAktif = $mitra->layananJasa->count();
                        $adaLegacy = $mitra->kontrakAktif->contains(fn ($k) => \Illuminate\Support\Str::startsWith((string) $k->nomor_kontrak, 'LEGACY-'));
                    @endphp
                    <article class="mj-card {{ $mitra->status_aktif ? '' : 'nonaktif' }}" style="--mj-c: {{ $warna }}">
                        <div class="mj-card-head">
                            <span class="mj-avatar" aria-hidden="true">{{ $inisialMitra($mitra->nama_mitra) }}</span>
                            <div class="flex-grow-1 min-w-0">
                                <div class="d-flex align-items-start justify-content-between gap-2">
                                    <h3 class="mj-card-name"><a href="{{ route('jasa.mitra.show', $mitra) }}">{{ $mitra->nama_mitra }}</a></h3>
                                    <span class="sa-soft-badge {{ $mitra->status_aktif ? 'success' : 'muted' }} flex-none">{{ $mitra->status_aktif ? 'Aktif' : 'Nonaktif' }}</span>
                                </div>
                                <div class="mj-card-tags">
                                    <span class="mj-chip kode"><i class="bi bi-hash"></i>{{ $mitra->kode_mitra ?: 'Tanpa kode' }}</span>
                                    @if($mitra->jenis_mitra)
                                        <span class="mj-chip jenis">{{ str_replace('_', ' ', $mitra->jenis_mitra) }}</span>
                                    @endif
                                    @if($mitra->user)
                                        <span class="mj-chip" style="background:#d1fae5;color:#047857"><i class="bi bi-person-check-fill"></i>Akun portal</span>
                                    @else
                                        <span class="mj-chip" style="background:#fef3c7;color:#92400e"><i class="bi bi-person-x"></i>Belum ada akun</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="mj-card-body">
                            <div class="mj-card-row" title="NPWP"><i class="bi bi-card-text"></i><span class="font-monospace">{{ $mitra->npwp ?: '— NPWP belum diisi' }}</span></div>
                            <div class="mj-card-row" title="Email">
                                <i class="bi bi-envelope"></i>
                                @if($mitra->email)
                                    <a class="mj-contact" href="mailto:{{ $mitra->email }}" title="{{ $mitra->email }}"><span>{{ $mitra->email }}</span></a>
                                @else
                                    <span class="mj-muted">Email belum diisi</span>
                                @endif
                            </div>
                            <div class="mj-card-row" title="No HP/WhatsApp">
                                <i class="bi bi-whatsapp"></i>
                                @if($wa)
                                    <a class="mj-contact" href="https://wa.me/{{ $wa }}" target="_blank" rel="noopener"><span>{{ $mitra->no_telepon }}</span></a>
                                @else
                                    <span class="mj-muted">No. WA belum diisi</span>
                                @endif
                            </div>
                            <div class="mj-card-row" title="Penanggung jawab">
                                <i class="bi bi-person-badge"></i>
                                <span class="text-truncate">
                                    {{ $mitra->nama_penanggung_jawab ?: 'PIC belum diisi' }}
                                    @if($mitra->jabatan_penanggung_jawab)<span class="mj-muted">· {{ $mitra->jabatan_penanggung_jawab }}</span>@endif
                                </span>
                            </div>
                        </div>
                        <div class="mj-card-stats">
                            <span class="mj-stat {{ $kontrakAktif ? '' : 'zero' }}" title="Kontrak aktif"><b>{{ $kontrakAktif }}</b><small>Kontrak</small></span>
                            <span class="mj-stat {{ $layananAktif ? '' : 'zero' }}" title="Layanan aktif"><b>{{ $layananAktif }}</b><small>Layanan</small></span>
                            <span class="mj-stat {{ $mitra->konsesi_aktif_count ? '' : 'zero' }}" title="Konsesi aktif"><b>{{ $mitra->konsesi_aktif_count }}</b><small>Konsesi</small></span>
                            <span class="mj-stat {{ $mitra->laporan_menunggu_count ? 'warn' : ($mitra->laporan_penjualan_count ? '' : 'zero') }}" title="{{ $mitra->laporan_menunggu_count }} laporan menunggu verifikasi"><b>{{ $mitra->laporan_penjualan_count }}</b><small>Laporan</small></span>
                        </div>
                        @if($mitra->laporan_menunggu_count > 0)
                            <div class="mj-card-alert"><i class="bi bi-hourglass-split me-1"></i>{{ $mitra->laporan_menunggu_count }} laporan menunggu verifikasi</div>
                        @elseif(! $kontrakAktif)
                            <div class="mj-card-alert"><i class="bi bi-exclamation-triangle me-1"></i>Belum ada kontrak aktif — layanan & konsesi masih terkunci</div>
                        @elseif($adaLegacy)
                            <div class="mj-card-alert"><i class="bi bi-exclamation-triangle me-1"></i>Kontrak legacy hasil migrasi perlu dilengkapi</div>
                        @endif
                        <div class="mj-card-foot">
                            <a href="{{ route('jasa.mitra.show', $mitra) }}" class="btn btn-sm btn-primary fw-bold px-3"><i class="bi bi-eye me-1"></i>Detail</a>
                            @if($canManageMitraMaster)
                                <div class="d-flex gap-1">
                                    <a href="{{ route('jasa.mitra.edit', $mitra) }}" class="btn btn-sm btn-light border sa-action jasa-icon-btn" title="Edit" aria-label="Edit"><i class="bi bi-pencil"></i></a>
                                    <a href="{{ route('jasa.mitra.kontrak.create', $mitra) }}" class="btn btn-sm btn-light border sa-action jasa-icon-btn" title="Kelola kontrak (layanan mengikuti kontrak)" aria-label="Kelola kontrak"><i class="bi bi-file-earmark-text"></i></a>
                                    <form method="POST" action="{{ route('jasa.mitra.destroy', $mitra) }}" class="d-inline" onsubmit="return confirm('Hapus mitra jasa ini? Data yang sudah dipakai laporan, kontrak, atau tagihan tidak bisa dihapus.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-light border text-danger sa-action jasa-icon-btn" title="Hapus" aria-label="Hapus"><i class="bi bi-trash"></i></button>
                                    </form>
                                </div>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </div>

    {{-- ===== Tampilan tabel ===== --}}
    <div class="table-responsive mj-view-table">
            <table class="table sa-table mj-table mb-0">
                <thead>
                    <tr>
                        <th>Mitra</th>
                        <th>Kontak &amp; PIC</th>
                        <th class="d-none d-xl-table-cell">Akun Portal</th>
                        <th class="text-center" title="Kontrak aktif · layanan aktif · laporan penjualan">Ringkasan</th>
                        <th>Status</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($mitras as $mitra)
                        @php
                            $warna = $warnaJenis[$mitra->jenis_mitra] ?? '#2563eb';
                            $wa = $nomorWa($mitra->no_telepon);
                            $kontrakAktif = $mitra->kontrakAktif->count();
                            $layananAktif = $mitra->layananJasa->count();
                            $adaLegacy = $mitra->kontrakAktif->contains(fn ($k) => \Illuminate\Support\Str::startsWith((string) $k->nomor_kontrak, 'LEGACY-'));
                        @endphp
                        <tr style="--mj-c: {{ $warna }}">
                            <td class="mj-name-cell">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="mj-avatar" aria-hidden="true">{{ $inisialMitra($mitra->nama_mitra) }}</span>
                                    <div class="min-w-0">
                                        <a href="{{ route('jasa.mitra.show', $mitra) }}" class="sa-name text-decoration-none d-block">{{ $mitra->nama_mitra }}</a>
                                        <div class="d-flex flex-wrap gap-1 mt-1">
                                            <span class="mj-chip kode">{{ $mitra->kode_mitra ?: 'Tanpa kode' }}</span>
                                            @if($mitra->jenis_mitra)
                                                <span class="mj-chip jenis">{{ str_replace('_', ' ', $mitra->jenis_mitra) }}</span>
                                            @endif
                                        </div>
                                        <div class="mj-npwp mt-1"><i class="bi bi-card-text me-1 text-secondary"></i>{{ $mitra->npwp ?: 'NPWP belum diisi' }}</div>
                                        <div class="mt-1 d-xl-none">
                                            @if($mitra->user)
                                                <span class="sa-soft-badge success"><i class="bi bi-person-check-fill"></i>Akun Aktif</span>
                                            @else
                                                <span class="sa-soft-badge warning"><i class="bi bi-person-x"></i>Belum Ada Akun</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="mj-contact-cell">
                                @if($mitra->email)
                                    <a class="mj-contact" href="mailto:{{ $mitra->email }}" title="{{ $mitra->email }}"><i class="bi bi-envelope"></i><span>{{ $mitra->email }}</span></a>
                                @else
                                    <div class="mj-muted"><i class="bi bi-envelope me-1"></i>Email belum diisi</div>
                                @endif
                                @if($wa)
                                    <a class="mj-contact mt-1" href="https://wa.me/{{ $wa }}" target="_blank" rel="noopener"><i class="bi bi-whatsapp"></i><span>{{ $mitra->no_telepon }}</span></a>
                                @else
                                    <div class="mj-muted mt-1"><i class="bi bi-whatsapp me-1"></i>WA belum diisi</div>
                                @endif
                                @if($mitra->nama_penanggung_jawab)
                                    <div class="mj-contact mt-1" title="{{ trim($mitra->nama_penanggung_jawab . ' · ' . $mitra->jabatan_penanggung_jawab, ' ·') }}"><i class="bi bi-person-badge"></i><span>{{ $mitra->nama_penanggung_jawab }}@if($mitra->jabatan_penanggung_jawab) <span class="mj-muted">· {{ $mitra->jabatan_penanggung_jawab }}</span>@endif</span></div>
                                @endif
                            </td>
                            <td class="d-none d-xl-table-cell">
                                @if($mitra->user)
                                    <span class="sa-soft-badge success">Akun Aktif</span>
                                @else
                                    <span class="sa-soft-badge warning">Belum Ada Akun</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <div class="d-inline-flex gap-1">
                                    <span class="mj-stat {{ $kontrakAktif ? '' : 'zero' }}" title="Kontrak aktif"><b>{{ $kontrakAktif }}</b><small>Kontrak</small></span>
                                    <span class="mj-stat {{ $layananAktif ? '' : 'zero' }}" title="Layanan aktif"><b>{{ $layananAktif }}</b><small>Layanan</small></span>
                                    <span class="mj-stat {{ $mitra->laporan_menunggu_count ? 'warn' : ($mitra->laporan_penjualan_count ? '' : 'zero') }}" title="{{ $mitra->laporan_menunggu_count }} laporan menunggu verifikasi"><b>{{ $mitra->laporan_penjualan_count }}</b><small>Laporan</small></span>
                                </div>
                                @if($mitra->laporan_menunggu_count > 0)
                                    <div class="mt-1"><small class="badge bg-warning text-dark">{{ $mitra->laporan_menunggu_count }} menunggu verifikasi</small></div>
                                @endif
                                @if($adaLegacy)
                                    <div class="mt-1"><small class="badge bg-warning text-dark" title="Kontrak legacy hasil migrasi otomatis — mohon lengkapi data kontrak yang sebenarnya">Legacy perlu dilengkapi</small></div>
                                @endif
                            </td>
                            <td>
                                <span class="sa-soft-badge {{ $mitra->status_aktif ? 'success' : 'muted' }}">
                                    {{ $mitra->status_aktif ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td class="text-center mj-action-cell">
                                <div class="d-flex justify-content-center gap-1">
                                    <a href="{{ route('jasa.mitra.show', $mitra) }}" class="btn btn-sm btn-light border sa-action jasa-icon-btn" title="Detail" aria-label="Detail"><i class="bi bi-eye"></i></a>
                                    @if($canManageMitraMaster)
                                        <a href="{{ route('jasa.mitra.edit', $mitra) }}" class="btn btn-sm btn-light border sa-action jasa-icon-btn" title="Edit" aria-label="Edit"><i class="bi bi-pencil"></i></a>
                                        <a href="{{ route('jasa.mitra.kontrak.create', $mitra) }}" class="btn btn-sm btn-primary sa-action jasa-icon-btn" title="Kelola kontrak (layanan mengikuti kontrak)" aria-label="Kelola kontrak"><i class="bi bi-file-earmark-text"></i></a>
                                        <form method="POST" action="{{ route('jasa.mitra.destroy', $mitra) }}" class="d-inline" onsubmit="return confirm('Hapus mitra jasa ini? Data yang sudah dipakai laporan, kontrak, atau tagihan tidak bisa dihapus.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-light border text-danger sa-action jasa-icon-btn" title="Hapus" aria-label="Hapus"><i class="bi bi-trash"></i></button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="mj-empty"><i class="bi bi-buildings d-block"></i><div class="mt-2 fw-semibold">Belum ada mitra jasa.</div></td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
    </div>
    @if($mitras->hasPages())
        <div class="px-3 py-2 border-top">
            {{ $mitras->withQueryString()->links() }}
        </div>
    @endif
</div>

<script>
// Pemilih tampilan Tabel/Kartu — pilihan diingat per browser; di layar HP tanpa
// pilihan tersimpan, default ke kartu.
(function () {
    const list = document.getElementById('mitraList');
    if (!list) return;
    const KEY = 'sikeren.mitraJasa.tampilan';
    const buttons = list.querySelectorAll('[data-mj-view]');

    function apply(view) {
        list.dataset.view = view;
        buttons.forEach(function (b) {
            const on = b.dataset.mjView === view;
            b.classList.toggle('active', on);
            b.setAttribute('aria-pressed', on ? 'true' : 'false');
        });
    }

    let saved = null;
    try { saved = localStorage.getItem(KEY); } catch (e) {}
    apply(saved === 'card' || saved === 'table' ? saved : (window.matchMedia('(max-width: 767.98px)').matches ? 'card' : 'table'));

    buttons.forEach(function (b) {
        b.addEventListener('click', function () {
            apply(b.dataset.mjView);
            try { localStorage.setItem(KEY, b.dataset.mjView); } catch (e) {}
        });
    });
})();
</script>
@endsection
