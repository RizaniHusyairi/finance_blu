@extends('layouts.app')
@section('title', 'Rekap Harian Pemakaian Garbarata')

@section('content')
@include('super_admin_jasa.laporan._styles')

<style>
    @media (prefers-reduced-motion: reduce) { *, *::before, *::after { animation-duration: .001s !important; animation-delay: 0s !important; animation-iteration-count: 1 !important; transition-duration: .001s !important; } }
    .rekap-day-card { border-radius: 14px; overflow: hidden; box-shadow: 0 8px 22px rgba(15, 47, 87, .08); }
    .rekap-day-header { background: linear-gradient(135deg, #0f2f57, #1d4ed8); color: #fff; padding: 14px 18px; cursor: pointer; --bs-heading-color: #fff; }
    .rekap-day-header.collapsed { background: linear-gradient(135deg, #1e293b, #334155); }
    .rekap-day-header h5 { margin: 0; font-weight: 700; color: #fff; }
    .rekap-day-header .day-meta { color: rgba(255,255,255,.85); font-size: .82rem; }
    .rekap-mitra-card { border: 1px solid #e5e7eb; border-radius: 12px; margin-bottom: 12px; background: #fff; }
    .rekap-mitra-header { padding: 10px 14px; background: #f8fafc; border-bottom: 1px solid #e5e7eb; cursor: pointer; display: flex; align-items: center; gap: 8px; }
    .rekap-mitra-header .mitra-title { font-weight: 700; color: #0f172a; flex: 1; }
    .rekap-mitra-header .badge-soft { background: #e0f2fe; color: #0369a1; font-weight: 600; }
    .rekap-mitra-body { padding: 10px 14px; }
    .rekap-mitra-body table { font-size: .85rem; }
    .rekap-mitra-body thead th { background: #f1f5f9; color: #334155; text-transform: uppercase; font-size: .72rem; letter-spacing: .04em; }
    .rekap-summary-pill { display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; border-radius: 999px; background: rgba(255,255,255,.18); font-weight: 600; font-size: .78rem; }
    .rekap-stat-card { border: 0; border-radius: 14px; box-shadow: 0 8px 22px rgba(15, 47, 87, .08); }
    .rekap-stat-card .label { font-size: .72rem; letter-spacing: .08em; text-transform: uppercase; color: #64748b; }
    .rekap-stat-card .value { font-weight: 800; font-size: 1.4rem; color: #0f172a; }
    .rekap-stat-card .value.text-success { color: #059669 !important; }
    .chevron { transition: transform .2s; }
    .chevron.collapsed { transform: rotate(-90deg); }
</style>

<div class="sa-report-page">
    <div class="sa-report-hero d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <div class="small text-uppercase fw-bold" style="letter-spacing:.08em;color:#fbbf24;">AMC &middot; Rekap Operasional</div>
            <h4 class="fw-bold mb-1"><i class="bi bi-calendar-week me-2"></i>Rekap Harian Pemakaian Garbarata</h4>
            <p class="mb-0 small">Ringkasan pemakaian garbarata per hari, dipecah per maskapai/mitra dengan detail tiap penerbangan.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('pemakaian-garbarata.index') }}" class="btn btn-outline-light btn-sm fw-bold">
                <i class="bi bi-list-ul me-1"></i>Daftar Pemakaian
            </a>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-2">
                    <label class="form-label small fw-bold text-uppercase">Tgl Dari</label>
                    <input type="date" name="tanggal_dari" class="form-control" value="{{ $filters['tanggal_dari'] }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold text-uppercase">Tgl Sampai</label>
                    <input type="date" name="tanggal_sampai" class="form-control" value="{{ $filters['tanggal_sampai'] }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-uppercase">Mitra</label>
                    <select name="mitra_jasa_id" class="form-select">
                        <option value="">Semua Mitra</option>
                        @foreach($mitraOptions as $m)
                            <option value="{{ $m->id }}" @selected(($filters['mitra_jasa_id'] ?? '') == $m->id)>{{ $m->nama_mitra }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold text-uppercase">Status</label>
                    <select name="status" class="form-select">
                        <option value="">Semua</option>
                        @foreach($statusOpt as $v => $l)
                            <option value="{{ $v }}" @selected(($filters['status'] ?? '') === $v)>{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 d-grid">
                    <button class="btn btn-primary fw-bold"><i class="bi bi-funnel me-1"></i>Tampilkan</button>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3 col-6">
            <div class="card rekap-stat-card p-3">
                <div class="label">Total Hari</div>
                <div class="value">{{ $grand['jumlah_hari'] }}</div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card rekap-stat-card p-3">
                <div class="label">Total Mitra</div>
                <div class="value">{{ $grand['jumlah_mitra'] }}</div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card rekap-stat-card p-3">
                <div class="label">Total Penerbangan</div>
                <div class="value">{{ $grand['jumlah_flight'] }}</div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card rekap-stat-card p-3">
                <div class="label">Total Rentang</div>
                <div class="value text-primary">{{ $grand['total_rentang'] }}</div>
            </div>
        </div>
    </div>

    @if($grouped->isEmpty())
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center text-muted py-5">
                <i class="bi bi-inbox fs-1 d-block mb-2 opacity-50"></i>
                Belum ada pemakaian garbarata pada rentang tanggal ini.
            </div>
        </div>
    @else
        @foreach($grouped as $tanggal => $perHari)
            @php
                $tanggalCarbon = \Carbon\Carbon::parse($tanggal);
                $dayId = 'day-' . str_replace('-', '', $tanggal);
            @endphp
            <div class="rekap-day-card mb-3">
                <div class="rekap-day-header d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-3 flex-grow-1" data-bs-toggle="collapse" data-bs-target="#{{ $dayId }}" aria-expanded="true" style="cursor:pointer;">
                        <i class="bi bi-chevron-down chevron"></i>
                        <div>
                            <h5><i class="bi bi-calendar3 me-2"></i>{{ $tanggalCarbon->isoFormat('dddd, D MMMM Y') }}</h5>
                            <div class="day-meta">{{ count($perHari['mitras']) }} mitra &middot; {{ $perHari['jumlah_flight'] }} penerbangan &middot; {{ $perHari['total_rentang'] }} rentang</div>
                        </div>
                    </div>
                    <div class="text-end d-flex align-items-center gap-2 flex-wrap">
                        <span class="rekap-summary-pill"><i class="bi bi-airplane"></i> {{ $perHari['jumlah_flight'] }} flight &middot; {{ $perHari['total_rentang'] }} rentang</span>
                        <a href="{{ route('pemakaian-garbarata.detail-hari', ['tanggal' => $tanggal]) }}" class="btn btn-sm btn-warning fw-bold" title="Lihat detail seluruh maskapai hari ini">
                            <i class="bi bi-eye me-1"></i>Detail Hari
                        </a>
                    </div>
                </div>
                <div id="{{ $dayId }}" class="collapse show">
                    <div class="p-3 bg-light">
                        @foreach($perHari['mitras'] as $idx => $mitraData)
                            @php $mitraId = 'mitra-' . str_replace('-', '', $tanggal) . '-' . $idx; @endphp
                            <div class="rekap-mitra-card">
                                <div class="rekap-mitra-header" data-bs-toggle="collapse" data-bs-target="#{{ $mitraId }}" aria-expanded="true">
                                    <i class="bi bi-chevron-down chevron"></i>
                                    <div class="mitra-title">
                                        <i class="bi bi-airplane-fill text-primary me-1"></i>
                                        {{ $mitraData['mitra']?->nama_mitra ?? 'Mitra tidak diketahui' }}
                                    </div>
                                    <span class="badge badge-soft">{{ $mitraData['jumlah_flight'] }} flight</span>
                                    <span class="badge bg-warning-subtle text-warning-emphasis fw-bold">{{ $mitraData['total_rentang'] }} rentang</span>
                                </div>
                                <div id="{{ $mitraId }}" class="collapse show">
                                    <div class="rekap-mitra-body">
                                        <div class="table-responsive">
                                            <table class="table table-bordered table-sm align-middle mb-0">
                                                <thead>
                                                    <tr>
                                                        <th class="text-center" style="width:36px;">#</th>
                                                        <th>Flight</th>
                                                        <th>Reg</th>
                                                        <th>Route</th>
                                                        <th>Type</th>
                                                        <th class="text-center">Docking</th>
                                                        <th class="text-center">Undocking</th>
                                                        <th class="text-end">Durasi</th>
                                                        <th class="text-center">Rentang</th>
                                                        <th class="text-center">Avio</th>
                                                        @if($canSeeBilling)
                                                            <th class="text-center">Status</th>
                                                        @endif
                                                        <th class="text-center" style="width:96px;">Aksi</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($mitraData['rows'] as $i => $row)
                                                        <tr>
                                                            <td class="text-center fw-bold">{{ $i + 1 }}</td>
                                                            <td class="fw-bold">{{ $row->nomor_penerbangan ?? '-' }}</td>
                                                            <td>{{ $row->registrasi_pesawat ?? '-' }}</td>
                                                            <td class="small">{{ $row->route ?? '-' }}</td>
                                                            <td>{{ $row->type_pesawat ?? '-' }}</td>
                                                            <td class="text-center small">{{ $row->docking_at?->format('H:i') }}</td>
                                                            <td class="text-center small">{{ $row->undocking_at?->format('H:i') }}</td>
                                                            <td class="text-end">{{ $row->durasi_menit }} mnt</td>
                                                            <td class="text-center fw-bold text-primary">{{ $row->jumlah_rentang }}</td>
                                                            <td class="text-center">{{ $row->nomor_avio ?? '-' }}</td>
                                                            @if($canSeeBilling)
                                                                <td class="text-center"><span class="badge {{ $row->status_badge }} small">{{ $row->status_label }}</span></td>
                                                            @endif
                                                            <td class="text-center">
                                                                <div class="btn-group btn-group-sm">
                                                                    <button type="button" class="btn btn-outline-primary btn-detail" data-id="{{ $row->id }}" title="Detail"><i class="bi bi-eye"></i></button>
                                                                    @if($row->file_pendukung)
                                                                        <a href="{{ route('pemakaian-garbarata.file', $row->id) }}" target="_blank" class="btn btn-outline-secondary" title="Dokumen Pendukung"><i class="bi bi-paperclip"></i></a>
                                                                    @endif
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                                <tfoot class="table-light">
                                                    <tr>
                                                        <th colspan="7" class="text-end">Subtotal {{ $mitraData['mitra']?->nama_mitra }}</th>
                                                        <th class="text-end">{{ $mitraData['total_durasi'] }} mnt</th>
                                                        <th class="text-center text-primary">{{ $mitraData['total_rentang'] }}</th>
                                                        <th></th>{{-- Avio --}}
                                                        @if($canSeeBilling)
                                                            <th></th>{{-- Status --}}
                                                        @endif
                                                        <th></th>{{-- Aksi --}}
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endforeach

        <div class="card border-0 shadow-sm mt-4" style="background: linear-gradient(135deg, #0f2f57, #1d4ed8);">
            <div class="card-body text-white d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <div class="small text-uppercase fw-bold" style="letter-spacing:.08em;color:#fde68a;">Grand Total Operasional</div>
                    <div class="fs-5 fw-bold">{{ $grand['jumlah_hari'] }} hari &middot; {{ $grand['jumlah_mitra'] }} mitra &middot; {{ $grand['jumlah_flight'] }} penerbangan</div>
                </div>
                <div class="fs-3 fw-bold">{{ $grand['total_rentang'] }} rentang</div>
            </div>
        </div>
    @endif
</div>

@php
    $detailMap = [];
    foreach ($grouped as $perHari) {
        foreach ($perHari['mitras'] as $mitraData) {
            foreach ($mitraData['rows'] as $row) {
                $detailMap[$row->id] = [
                    'tanggal' => $row->tanggal?->isoFormat('dddd, D MMMM Y'),
                    'mitra' => $mitraData['mitra']?->nama_mitra,
                    'layanan' => $row->layanan?->nama_layanan,
                    'flight_arr' => $row->flight_arr,
                    'flight_dep' => $row->flight_dep,
                    'nomor_penerbangan' => $row->nomor_penerbangan,
                    'registrasi_pesawat' => $row->registrasi_pesawat,
                    'route' => $row->route,
                    'type_pesawat' => $row->type_pesawat,
                    'bobot_ton' => $row->bobot_ton,
                    'docking_at' => $row->docking_at?->format('d/m/Y H:i'),
                    'undocking_at' => $row->undocking_at?->format('d/m/Y H:i'),
                    'durasi_menit' => $row->durasi_menit,
                    'jumlah_rentang' => $row->jumlah_rentang,
                    'nomor_avio' => $row->nomor_avio,
                    'tarif_garbarata' => $canSeeBilling ? (float) $row->tarif_garbarata : null,
                    'total_garbarata' => $canSeeBilling ? (float) $row->total_garbarata : null,
                    'keterangan' => $row->keterangan,
                    'status_label' => $row->status_label,
                    'status_badge' => $row->status_badge,
                    'tagihan' => $canSeeBilling ? $row->tagihan?->nomor_tagihan : null,
                    'permohonan' => $row->permohonan?->nomor_surat,
                    'creator' => $row->creator?->name,
                    'created_at' => $row->created_at?->format('d/m/Y H:i'),
                    'file_url' => $row->file_pendukung ? route('pemakaian-garbarata.file', $row->id) : null,
                    'edit_url' => route('pemakaian-garbarata.edit', $row->id),
                    'editable' => auth()->user()?->hasRole('Super Admin') || (auth()->id() === $row->created_by && $row->status !== 'TERTAGIH'),
                ];
            }
        }
    }
@endphp

<style>
    /* ═══════ Detail Pemakaian Modal — Design + Animations ═══════ */
    #detailModal .modal-dialog { max-width: 820px; }
    #detailModal .modal-content {
        border: 0; border-radius: 22px; overflow: hidden;
        box-shadow: 0 25px 70px rgba(15, 47, 87, .35);
        background: #fff;
    }
    #detailModal.fade .modal-dialog {
        transform: translateY(40px) scale(.96);
        transition: transform .45s cubic-bezier(.16,1,.3,1), opacity .35s ease;
        opacity: 0;
    }
    #detailModal.show .modal-dialog { transform: translateY(0) scale(1); opacity: 1; }

    /* Hero */
    .dm-hero {
        position: relative;
        background: linear-gradient(135deg, #0a1f3c 0%, #11366b 45%, #1d4ed8 100%);
        color: #fff; padding: 24px 28px 26px; overflow: hidden;
    }
    .dm-hero::before {
        content: ''; position: absolute; inset: 0;
        background: radial-gradient(circle at 85% 50%, rgba(96,165,250,.25), transparent 55%);
        pointer-events: none;
    }
    .dm-hero-decor {
        position: absolute; right: -10px; top: 50%; transform: translateY(-50%);
        width: 240px; height: 130px; opacity: .55; pointer-events: none;
    }
    .dm-hero-decor .dm-plane {
        animation: dmPlaneFloat 5s ease-in-out infinite;
        transform-origin: center;
    }
    @keyframes dmPlaneFloat {
        0%,100% { transform: translate(0,0) rotate(-6deg); }
        50%     { transform: translate(-12px,-6px) rotate(-2deg); }
    }
    .dm-hero-label {
        font-size: .68rem; letter-spacing: .12em; font-weight: 800;
        color: #fbbf24; text-transform: uppercase;
    }
    .dm-hero-title {
        font-size: 1.65rem; font-weight: 800; margin-top: 2px;
        background: linear-gradient(180deg,#fff,#cfe1ff);
        -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent;
        position: relative; z-index: 1;
    }
    .dm-hero-date {
        font-size: .82rem; color: rgba(255,255,255,.85); margin-top: 6px;
        display: inline-flex; align-items: center; gap: 6px; position: relative; z-index: 1;
    }
    .dm-hero-close {
        position: absolute; top: 18px; right: 18px;
        width: 34px; height: 34px; border-radius: 50%;
        background: rgba(255,255,255,.12); color: #fff; border: 0;
        display: inline-flex; align-items: center; justify-content: center;
        transition: background .2s ease, transform .2s ease; z-index: 2;
    }
    .dm-hero-close:hover { background: rgba(255,255,255,.25); transform: rotate(90deg); }

    /* Body */
    .dm-body { padding: 22px 24px 8px; background: #f8fafc; }
    .dm-section { background: #fff; border-radius: 14px; padding: 14px 16px; }
    .dm-section + .dm-section { margin-top: 12px; }
    .dm-grid { display: grid; gap: 14px 18px; }
    .dm-grid-2 { grid-template-columns: 1fr 1fr; }
    .dm-grid-4 { grid-template-columns: repeat(4, 1fr); }
    @media (max-width: 575.98px) {
        .dm-grid-2, .dm-grid-4 { grid-template-columns: 1fr 1fr; }
    }

    .dm-field { display: flex; align-items: flex-start; gap: 10px; min-width: 0; }
    .dm-field-icon {
        flex-shrink: 0; width: 32px; height: 32px; border-radius: 10px;
        display: inline-flex; align-items: center; justify-content: center;
        background: #eff6ff; color: #1d4ed8; font-size: .95rem;
    }
    .dm-field-body { min-width: 0; flex: 1; }
    .dm-field-label {
        font-size: .64rem; letter-spacing: .08em; font-weight: 700;
        color: #94a3b8; text-transform: uppercase;
    }
    .dm-field-value {
        font-weight: 700; color: #0f2f57; font-size: .95rem;
        line-height: 1.3; margin-top: 2px;
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }
    .dm-field-value.wrap { white-space: normal; }

    /* Docking / Undocking accent cards */
    .dm-dock-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
    @media (max-width: 575.98px) { .dm-dock-row { grid-template-columns: 1fr; } }
    .dm-dock-card {
        position: relative; border-radius: 14px; padding: 14px 16px;
        display: flex; align-items: center; gap: 14px;
        border: 1.5px solid transparent;
        transition: transform .3s ease, box-shadow .3s ease;
    }
    .dm-dock-card:hover { transform: translateY(-2px); }
    .dm-dock-card.dock {
        background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
        border-color: #bfdbfe;
        box-shadow: 0 4px 12px rgba(29,78,216,.08);
    }
    .dm-dock-card.undock {
        background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
        border-color: #fde68a;
        box-shadow: 0 4px 12px rgba(217,119,6,.08);
    }
    .dm-dock-icon {
        width: 44px; height: 44px; border-radius: 50%;
        display: inline-flex; align-items: center; justify-content: center;
        color: #fff; font-size: 1.15rem; flex-shrink: 0;
        box-shadow: 0 6px 14px rgba(0,0,0,.15);
    }
    .dm-dock-card.dock .dm-dock-icon {
        background: linear-gradient(135deg,#3b82f6,#1d4ed8);
        animation: dmPulseBlue 2.6s ease-in-out infinite;
    }
    .dm-dock-card.undock .dm-dock-icon {
        background: linear-gradient(135deg,#f59e0b,#d97706);
        animation: dmPulseAmber 2.6s ease-in-out infinite;
    }
    @keyframes dmPulseBlue {
        0%,100% { box-shadow: 0 6px 14px rgba(29,78,216,.25), 0 0 0 0 rgba(59,130,246,.4); }
        50%     { box-shadow: 0 6px 14px rgba(29,78,216,.35), 0 0 0 8px rgba(59,130,246,0); }
    }
    @keyframes dmPulseAmber {
        0%,100% { box-shadow: 0 6px 14px rgba(217,119,6,.25), 0 0 0 0 rgba(245,158,11,.4); }
        50%     { box-shadow: 0 6px 14px rgba(217,119,6,.35), 0 0 0 8px rgba(245,158,11,0); }
    }
    .dm-dock-label {
        font-size: .68rem; letter-spacing: .08em; font-weight: 800;
        text-transform: uppercase;
    }
    .dm-dock-card.dock .dm-dock-label { color: #1d4ed8; }
    .dm-dock-card.undock .dm-dock-label { color: #b45309; }
    .dm-dock-value { font-size: 1.18rem; font-weight: 800; color: #0f2f57; line-height: 1.2; margin-top: 2px; }

    /* Stat tiles row */
    .dm-stat-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; }
    @media (max-width: 575.98px) { .dm-stat-row { grid-template-columns: 1fr 1fr; } }
    .dm-stat-tile {
        background: #fff; border: 1px solid #e2e8f0; border-radius: 12px;
        padding: 10px 12px; display: flex; align-items: center; gap: 10px;
        transition: border-color .2s ease, transform .2s ease;
    }
    .dm-stat-tile:hover { border-color: #94a3b8; transform: translateY(-1px); }
    .dm-stat-icon {
        width: 28px; height: 28px; border-radius: 8px; flex-shrink: 0;
        display: inline-flex; align-items: center; justify-content: center;
        background: #f1f5f9; color: #475569; font-size: .85rem;
    }
    .dm-stat-body { min-width: 0; }
    .dm-stat-label {
        font-size: .6rem; letter-spacing: .08em; font-weight: 700;
        color: #94a3b8; text-transform: uppercase;
    }
    .dm-stat-value { font-weight: 800; color: #0f2f57; font-size: .9rem; line-height: 1.2; margin-top: 1px; }

    /* Footer */
    .dm-footer {
        background: #fff; padding: 16px 24px;
        display: flex; gap: 10px; justify-content: flex-end;
        border-top: 1px solid #e2e8f0;
    }
    .dm-btn {
        border-radius: 10px; font-weight: 700; padding: .55rem 1.1rem;
        display: inline-flex; align-items: center; gap: 8px;
        transition: transform .15s ease, box-shadow .15s ease;
    }
    .dm-btn:hover { transform: translateY(-1px); }
    .dm-btn-edit {
        background: #fff; color: #1d4ed8; border: 1.5px solid #1d4ed8;
    }
    .dm-btn-edit:hover { background: #eff6ff; color: #1d4ed8; box-shadow: 0 6px 16px rgba(29,78,216,.18); }
    .dm-btn-close {
        background: #475569; color: #fff; border: 0;
    }
    .dm-btn-close:hover { background: #334155; color: #fff; }

    /* Stagger fade-in for sections when modal opens */
    #detailModal.show .dm-section,
    #detailModal.show .dm-dock-row,
    #detailModal.show .dm-stat-row {
        animation: dmFadeUp .5s cubic-bezier(.16,1,.3,1) both;
    }
    #detailModal.show .dm-section:nth-of-type(1) { animation-delay: .05s; }
    #detailModal.show .dm-dock-row              { animation-delay: .15s; }
    #detailModal.show .dm-stat-row              { animation-delay: .25s; }
    #detailModal.show .dm-section:nth-of-type(2) { animation-delay: .35s; }
    @keyframes dmFadeUp {
        from { opacity: 0; transform: translateY(14px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    .dm-badge {
        display: inline-flex; align-items: center; padding: 3px 10px;
        border-radius: 999px; font-size: .72rem; font-weight: 700;
    }
    .dm-file-btn {
        background: #fff; color: #1d4ed8; border: 1.5px solid #bfdbfe;
        border-radius: 10px; padding: .4rem .9rem; font-weight: 700; font-size: .82rem;
        display: inline-flex; align-items: center; gap: 6px;
        transition: background .2s ease, border-color .2s ease;
    }
    .dm-file-btn:hover { background: #eff6ff; border-color: #60a5fa; color: #1d4ed8; }
</style>

<div class="modal fade" id="detailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">

            {{-- ═════ Hero ═════ --}}
            <div class="dm-hero">
                <button type="button" class="dm-hero-close" data-bs-dismiss="modal" aria-label="Tutup">
                    <i class="bi bi-x-lg"></i>
                </button>

                {{-- Decorative airplane + airport silhouette --}}
                <svg class="dm-hero-decor" viewBox="0 0 240 130" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <g stroke="#cfe1ff" stroke-width="1.2" opacity=".55" fill="none">
                        <path d="M0 105 L240 105"/>
                        <path d="M150 105 L160 80 L175 80 L180 105"/>
                        <path d="M195 105 L200 70 L208 70 L213 105"/>
                        <circle cx="167" cy="74" r="2"/>
                        <circle cx="204" cy="64" r="2"/>
                    </g>
                    <g class="dm-plane" fill="#fff" opacity=".9">
                        <path d="M40 55 L110 50 L120 40 L130 50 L165 48 L168 56 L130 60 L120 70 L110 60 L40 58 Z"/>
                        <path d="M60 56 L72 70 L78 70 L72 56 Z" opacity=".75"/>
                    </g>
                </svg>

                <div class="dm-hero-label">Detail Pemakaian Garbarata</div>
                <div class="dm-hero-title" id="dm-flight">—</div>
                <div class="dm-hero-date"><i class="bi bi-calendar3"></i><span id="dm-tanggal">—</span></div>
            </div>

            {{-- ═════ Body ═════ --}}
            <div class="modal-body p-0">
                <div class="dm-body">

                    {{-- Identitas penerbangan & mitra --}}
                    <div class="dm-section">
                        <div class="dm-grid dm-grid-2">
                            <div class="dm-field">
                                <span class="dm-field-icon"><i class="bi bi-people-fill"></i></span>
                                <div class="dm-field-body">
                                    <div class="dm-field-label">Mitra / Maskapai</div>
                                    <div class="dm-field-value" id="dm-mitra">—</div>
                                </div>
                            </div>
                            <div class="dm-field">
                                <span class="dm-field-icon"><i class="bi bi-headset"></i></span>
                                <div class="dm-field-body">
                                    <div class="dm-field-label">Layanan</div>
                                    <div class="dm-field-value" id="dm-layanan">—</div>
                                </div>
                            </div>
                        </div>
                        <hr class="my-3" style="border-color:#e2e8f0;">
                        <div class="dm-grid dm-grid-4">
                            <div class="dm-field">
                                <span class="dm-field-icon"><i class="bi bi-airplane"></i></span>
                                <div class="dm-field-body">
                                    <div class="dm-field-label">Flight ARR</div>
                                    <div class="dm-field-value" id="dm-arr">—</div>
                                </div>
                            </div>
                            <div class="dm-field">
                                <span class="dm-field-icon" style="background:#fef3c7;color:#b45309;"><i class="bi bi-airplane-fill"></i></span>
                                <div class="dm-field-body">
                                    <div class="dm-field-label">Flight DEP</div>
                                    <div class="dm-field-value" id="dm-dep">—</div>
                                </div>
                            </div>
                            <div class="dm-field">
                                <span class="dm-field-icon" style="background:#ecfdf5;color:#059669;"><i class="bi bi-shield-check"></i></span>
                                <div class="dm-field-body">
                                    <div class="dm-field-label">Registrasi</div>
                                    <div class="dm-field-value" id="dm-reg">—</div>
                                </div>
                            </div>
                            <div class="dm-field">
                                <span class="dm-field-icon" style="background:#f5f3ff;color:#7c3aed;"><i class="bi bi-airplane-engines"></i></span>
                                <div class="dm-field-body">
                                    <div class="dm-field-label">Type Pesawat</div>
                                    <div class="dm-field-value" id="dm-type">—</div>
                                </div>
                            </div>
                            <div class="dm-field">
                                <span class="dm-field-icon" style="background:#fef2f2;color:#dc2626;"><i class="bi bi-geo-alt-fill"></i></span>
                                <div class="dm-field-body">
                                    <div class="dm-field-label">Route</div>
                                    <div class="dm-field-value" id="dm-route">—</div>
                                </div>
                            </div>
                            <div class="dm-field">
                                <span class="dm-field-icon" style="background:#f1f5f9;color:#475569;"><i class="bi bi-speedometer2"></i></span>
                                <div class="dm-field-body">
                                    <div class="dm-field-label">Bobot</div>
                                    <div class="dm-field-value" id="dm-bobot">—</div>
                                </div>
                            </div>
                            <div class="dm-field">
                                <span class="dm-field-icon" style="background:#fff7ed;color:#c2410c;"><i class="bi bi-bridge"></i></span>
                                <div class="dm-field-body">
                                    <div class="dm-field-label">Avio</div>
                                    <div class="dm-field-value" id="dm-avio">—</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Docking / Undocking --}}
                    <div class="dm-dock-row mt-3">
                        <div class="dm-dock-card dock">
                            <div class="dm-dock-icon"><i class="bi bi-arrow-down-circle-fill"></i></div>
                            <div>
                                <div class="dm-dock-label">Docking</div>
                                <div class="dm-dock-value" id="dm-docking">—</div>
                            </div>
                        </div>
                        <div class="dm-dock-card undock">
                            <div class="dm-dock-icon"><i class="bi bi-arrow-up-circle-fill"></i></div>
                            <div>
                                <div class="dm-dock-label">Undocking</div>
                                <div class="dm-dock-value" id="dm-undocking">—</div>
                            </div>
                        </div>
                    </div>

                    {{-- Stat row: Durasi, Rentang, Status, Permohonan/Tagihan --}}
                    <div class="dm-stat-row mt-3">
                        <div class="dm-stat-tile">
                            <span class="dm-stat-icon" style="background:#eff6ff;color:#1d4ed8;"><i class="bi bi-clock-history"></i></span>
                            <div class="dm-stat-body">
                                <div class="dm-stat-label">Durasi</div>
                                <div class="dm-stat-value" id="dm-durasi">—</div>
                            </div>
                        </div>
                        <div class="dm-stat-tile">
                            <span class="dm-stat-icon" style="background:#fef3c7;color:#b45309;"><i class="bi bi-calendar3-range"></i></span>
                            <div class="dm-stat-body">
                                <div class="dm-stat-label">Rentang</div>
                                <div class="dm-stat-value" id="dm-rentang">—</div>
                            </div>
                        </div>
                        @if($canSeeBilling)
                        <div class="dm-stat-tile">
                            <span class="dm-stat-icon" style="background:#f5f3ff;color:#7c3aed;"><i class="bi bi-flag-fill"></i></span>
                            <div class="dm-stat-body">
                                <div class="dm-stat-label">Status</div>
                                <div class="dm-stat-value" id="dm-status">—</div>
                            </div>
                        </div>
                        <div class="dm-stat-tile">
                            <span class="dm-stat-icon" style="background:#ecfdf5;color:#059669;"><i class="bi bi-receipt"></i></span>
                            <div class="dm-stat-body">
                                <div class="dm-stat-label">Permohonan / Tagihan</div>
                                <div class="dm-stat-value" id="dm-link">—</div>
                            </div>
                        </div>
                        @endif
                    </div>

                    {{-- Keterangan + Audit --}}
                    <div class="dm-section mt-3">
                        <div class="dm-grid dm-grid-2">
                            <div class="dm-field">
                                <span class="dm-field-icon" style="background:#f8fafc;color:#475569;"><i class="bi bi-file-text"></i></span>
                                <div class="dm-field-body">
                                    <div class="dm-field-label">Keterangan</div>
                                    <div class="dm-field-value wrap" id="dm-keterangan">—</div>
                                </div>
                            </div>
                            <div class="dm-field">
                                <span class="dm-field-icon" style="background:#eff6ff;color:#1d4ed8;"><i class="bi bi-person-circle"></i></span>
                                <div class="dm-field-body">
                                    <div class="dm-field-label">Dicatat Oleh</div>
                                    <div class="dm-field-value" id="dm-creator">—</div>
                                </div>
                            </div>
                            <div class="dm-field">
                                <span class="dm-field-icon" style="background:#f1f5f9;color:#475569;"><i class="bi bi-clock"></i></span>
                                <div class="dm-field-body">
                                    <div class="dm-field-label">Waktu Catat</div>
                                    <div class="dm-field-value" id="dm-created">—</div>
                                </div>
                            </div>
                            <div class="dm-field" id="dm-file-wrap">
                                <span class="dm-field-icon" style="background:#eff6ff;color:#1d4ed8;"><i class="bi bi-paperclip"></i></span>
                                <div class="dm-field-body">
                                    <div class="dm-field-label">Dokumen Pendukung</div>
                                    <div class="dm-field-value wrap mt-1" id="dm-file-slot">
                                        <span class="text-muted small">—</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            {{-- ═════ Footer ═════ --}}
            <div class="dm-footer">
                <a id="dm-edit" href="#" class="dm-btn dm-btn-edit"><i class="bi bi-pencil"></i>Ubah</a>
                <button type="button" class="dm-btn dm-btn-close" data-bs-dismiss="modal"><i class="bi bi-x-lg"></i>Tutup</button>
            </div>
        </div>
    </div>
</div>

@push('script')
<script>
    const rekapDetailMap = @json($detailMap);

    document.querySelectorAll('.rekap-day-header, .rekap-mitra-header').forEach(function (el) {
        const targetSel = el.getAttribute('data-bs-target');
        if (!targetSel) return;
        const target = document.querySelector(targetSel);
        const chev = el.querySelector('.chevron');
        if (!target || !chev) return;
        target.addEventListener('hide.bs.collapse', function () { chev.classList.add('collapsed'); el.classList.add('collapsed'); });
        target.addEventListener('show.bs.collapse', function () { chev.classList.remove('collapsed'); el.classList.remove('collapsed'); });
    });

    function rupiah(v) {
        return 'Rp ' + (Number(v) || 0).toLocaleString('id-ID');
    }

    function setText(id, val) {
        const el = document.getElementById(id);
        if (el) el.textContent = (val === null || val === undefined || val === '') ? '-' : val;
    }

    function setHtml(id, val) {
        const el = document.getElementById(id);
        if (el) el.innerHTML = val;
    }

    const detailModalEl = document.getElementById('detailModal');
    const detailModal = detailModalEl ? new bootstrap.Modal(detailModalEl) : null;

    document.querySelectorAll('.btn-detail').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const id = btn.getAttribute('data-id');
            const d = rekapDetailMap[id];
            if (!d || !detailModal) return;

            // Hero title: ARR / DEP combination (fallback to nomor_penerbangan)
            const arr = d.flight_arr || '';
            const dep = d.flight_dep || '';
            let heroTitle = '—';
            if (arr && dep) heroTitle = `${arr} / ${dep}`;
            else if (arr || dep) heroTitle = arr || dep;
            else if (d.nomor_penerbangan) heroTitle = d.nomor_penerbangan;
            setText('dm-flight', heroTitle);

            setText('dm-tanggal', d.tanggal || '—');
            setText('dm-mitra', d.mitra || '—');
            setText('dm-layanan', d.layanan || '—');
            setText('dm-arr', arr || '—');
            setText('dm-dep', dep || '—');
            setText('dm-reg', d.registrasi_pesawat || '—');
            setText('dm-type', d.type_pesawat || '—');
            setText('dm-route', d.route || '—');
            setText('dm-bobot', d.bobot_ton ? (Number(d.bobot_ton).toLocaleString('id-ID', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' ton') : '—');
            setText('dm-avio', d.nomor_avio ? ('Avio ' + d.nomor_avio) : '—');
            setText('dm-docking', d.docking_at || '—');
            setText('dm-undocking', d.undocking_at || '—');
            setText('dm-durasi', (d.durasi_menit || 0) + ' menit');
            setText('dm-rentang', d.jumlah_rentang || 0);

            // Status as soft badge
            const statusMap = {
                'bg-warning text-dark': 'background:#fef3c7;color:#b45309;',
                'bg-success': 'background:#dcfce7;color:#15803d;',
                'bg-danger': 'background:#fee2e2;color:#b91c1c;',
                'bg-info': 'background:#dbeafe;color:#1d4ed8;',
                'bg-secondary': 'background:#f1f5f9;color:#475569;',
            };
            const stStyle = statusMap[d.status_badge] || 'background:#f1f5f9;color:#475569;';
            setHtml('dm-status', `<span class="dm-badge" style="${stStyle}">${d.status_label || '—'}</span>`);

            setText('dm-keterangan', d.keterangan || '—');
            setText('dm-creator', d.creator || '—');
            setText('dm-created', d.created_at || '—');

            // Permohonan / Tagihan link
            const linkParts = [];
            if (d.permohonan) linkParts.push(`<span class="dm-badge" style="background:#dbeafe;color:#1d4ed8;"><i class="bi bi-paperclip me-1"></i>${d.permohonan}</span>`);
            if (d.tagihan)    linkParts.push(`<span class="dm-badge" style="background:#dcfce7;color:#15803d;"><i class="bi bi-receipt me-1"></i>${d.tagihan}</span>`);
            setHtml('dm-link', linkParts.length ? linkParts.join(' ') : '—');

            // Dokumen pendukung
            const fileSlot = document.getElementById('dm-file-slot');
            if (d.file_url) {
                fileSlot.innerHTML = `<a href="${d.file_url}" target="_blank" class="dm-file-btn"><i class="bi bi-box-arrow-up-right"></i>Buka / Unduh File</a>`;
            } else {
                fileSlot.innerHTML = '<span class="text-muted small">— Tidak ada file —</span>';
            }

            const dmEdit = document.getElementById('dm-edit');
            if (d.editable) {
                dmEdit.style.display = '';
                dmEdit.setAttribute('href', d.edit_url);
            } else {
                dmEdit.style.display = 'none';
            }

            detailModal.show();
        });
    });
</script>
@endpush
@endsection
