@extends('layouts.app')
@section('title', 'Detail Pemakaian Garbarata ' . $tanggal->format('d M Y'))

@section('content')
@include('super_admin_jasa.laporan._styles')

<style>
    .amc-table th { background:#f8fafc; color:#475569; font-size:.72rem; letter-spacing:.04em; text-transform:uppercase; white-space:nowrap; }
    .amc-table td { vertical-align:middle; }
    .amc-cell { min-width:88px; border-radius:12px; border:1px solid #e2e8f0; background:#fff; padding:.45rem .65rem; display:inline-block; font-weight:600; color:#0f172a; width:100%; }
    .amc-cell.amc-small { min-width:72px; }
    .amc-cell.text-end { text-align:right; }
    .amc-total-pill { display:inline-flex; align-items:center; justify-content:center; min-width:92px; border:1px solid #bfdbfe; border-radius:12px; background:#eff6ff; padding:.45rem .65rem; color:#1d4ed8; font-weight:800; }
    .amc-total-pill.success { border-color:#bbf7d0; background:#ecfdf5; color:#059669; }
    @media print {
        .no-print { display: none !important; }
        .sa-report-hero { background: linear-gradient(135deg, #0f2f57, #1d4ed8 62%, #38bdf8) !important; -webkit-print-color-adjust:exact; print-color-adjust:exact; }
        .amc-total-pill { -webkit-print-color-adjust:exact; print-color-adjust:exact; }
        .card { break-inside: avoid; }
    }
</style>

<div class="sa-report-page">
    <div class="d-flex gap-2 mb-3 no-print">
        <a href="{{ route('pemakaian-garbarata.rekap-harian', ['tanggal_dari' => $tanggal->toDateString(), 'tanggal_sampai' => $tanggal->toDateString()]) }}" class="btn btn-light border fw-bold"><i class="bi bi-arrow-left me-1"></i>Kembali ke Rekap</a>
        <a href="{{ route('pemakaian-garbarata.index') }}" class="btn btn-outline-primary fw-bold"><i class="bi bi-list-ul me-1"></i>Daftar Pemakaian</a>
        <button onclick="window.print()" class="btn btn-outline-secondary fw-bold ms-auto"><i class="bi bi-printer me-1"></i>Cetak</button>
    </div>

    <div class="sa-report-hero d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <div class="small text-uppercase fw-bold" style="letter-spacing:.08em;color:#fbbf24;">AMC &middot; Pemakaian Garbarata</div>
            <h4 class="fw-bold mb-1"><i class="bi bi-bridge me-2"></i>Detail Batch Pemakaian Garbarata</h4>
            <p class="mb-0 small">{{ $tanggal->isoFormat('dddd, D MMMM Y') }} &middot; {{ $summary['jumlah_mitra'] }} maskapai &middot; {{ $summary['jumlah_flight'] }} penerbangan &middot; {{ $summary['total_rentang'] }} rentang</p>
        </div>
    </div>

    @forelse($byMitra as $group)
        @php
            $first = $group['rows']->first();
            $layanan = $first->layanan;
            $permohonan = $first->permohonan;
            $creator = $first->creator;
            $periode = $first->tanggal?->format('Y-m');
            $keterangan = $group['rows']->pluck('keterangan')->filter()->unique()->implode(' • ');
            $files = $group['rows']->filter(fn($r) => $r->file_pendukung);
        @endphp

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">

                {{-- Header fields (mirip form input) --}}
                <div class="row g-3 mb-4">
                    <div class="col-lg-4">
                        <label class="form-label fw-bold">Mitra / Maskapai</label>
                        <div class="amc-cell"><i class="bi bi-airplane-fill text-primary me-1"></i>{{ $group['mitra']?->nama_mitra ?? '-' }}</div>
                    </div>
                    <div class="col-lg-3">
                        <label class="form-label fw-bold">Periode Bulan</label>
                        <div class="amc-cell">{{ $periode ? \Carbon\Carbon::parse($periode . '-01')->isoFormat('MMMM Y') : '-' }}</div>
                    </div>
                    <div class="col-lg-5">
                        <label class="form-label fw-bold">Layanan Tarif Garbarata</label>
                        <div class="amc-cell">{{ $layanan?->nama_layanan ?? '— Tentukan saat tagihan —' }}</div>
                    </div>
                    <div class="col-lg-6">
                        <label class="form-label fw-bold">Permohonan Non-Schedule</label>
                        <div class="amc-cell">
                            @if($permohonan)
                                <i class="bi bi-paperclip text-info"></i> {{ $permohonan->nomor_surat }} ({{ $permohonan->tanggal_surat?->format('d/m/Y') }})
                            @else
                                <span class="text-muted">— Tidak terkait —</span>
                            @endif
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <label class="form-label fw-bold">File Pendukung</label>
                        <div class="amc-cell">
                            @if($files->count())
                                @foreach($files as $rf)
                                    <a href="{{ route('pemakaian-garbarata.file', $rf->id) }}" target="_blank" class="badge bg-info-subtle text-info-emphasis text-decoration-none me-1"><i class="bi bi-paperclip"></i> {{ $rf->nomor_penerbangan ?? $rf->flight_arr }}</a>
                                @endforeach
                            @else
                                <span class="text-muted">— Tidak ada file —</span>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Rincian header --}}
                <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2 mb-2">
                    <div>
                        <div class="fw-bold text-primary"><i class="bi bi-airplane-engines me-1"></i>Rincian Pemakaian Garbarata</div>
                        <div class="small text-muted">Format mengikuti rekap bulanan: tanggal, reg, ARR/DEP, route, docking, undocking, type, bobot, waktu, rentang, total.</div>
                    </div>
                    <div class="d-flex gap-2">
                        <span class="badge bg-primary-subtle text-primary-emphasis fw-bold">{{ $group['jumlah_flight'] }} flight</span>
                        <span class="badge bg-warning-subtle text-warning-emphasis fw-bold">{{ $group['total_rentang'] }} rentang</span>
                    </div>
                </div>

                {{-- Rincian table --}}
                <div class="table-responsive border rounded-4">
                    <table class="table table-bordered amc-table align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="text-center">No</th>
                                <th>Tanggal</th>
                                <th>Reg</th>
                                <th>ARR</th>
                                <th>DEP</th>
                                <th>Route</th>
                                <th>Docking</th>
                                <th>Undocking</th>
                                <th>Type</th>
                                <th>Bobot Ton</th>
                                <th>Waktu</th>
                                <th>Avio</th>
                                <th>Rentang</th>
                                <th class="no-print">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($group['rows'] as $i => $row)
                                @php
                                    $durasiMenit = (int) $row->durasi_menit;
                                    $jam = intdiv($durasiMenit, 60);
                                    $menit = $durasiMenit % 60;
                                    $waktuStr = sprintf('%02d:%02d', $jam, $menit);
                                @endphp
                                <tr>
                                    <td class="text-center fw-bold">{{ $i + 1 }}</td>
                                    <td><div class="amc-cell">{{ $row->tanggal?->format('d/m/Y') }}</div></td>
                                    <td><div class="amc-cell">{{ $row->registrasi_pesawat ?? '-' }}</div></td>
                                    <td><div class="amc-cell amc-small">{{ $row->flight_arr ?? '-' }}</div></td>
                                    <td><div class="amc-cell amc-small">{{ $row->flight_dep ?? '-' }}</div></td>
                                    <td><div class="amc-cell">{{ $row->route ?? '-' }}</div></td>
                                    <td><div class="amc-cell amc-small">{{ $row->docking_at?->format('H:i') ?? '-' }}</div></td>
                                    <td><div class="amc-cell amc-small">{{ $row->undocking_at?->format('H:i') ?? '-' }}</div></td>
                                    <td><div class="amc-cell amc-small">{{ $row->type_pesawat ?? '-' }}</div></td>
                                    <td><div class="amc-cell amc-small text-end">{{ $row->bobot_ton ? rtrim(rtrim(number_format((float)$row->bobot_ton, 2, ',', '.'), '0'), ',') : '-' }}</div></td>
                                    <td class="text-center"><span class="amc-total-pill">{{ $waktuStr }}</span></td>
                                    <td><div class="amc-cell amc-small text-center">{{ $row->nomor_avio ?? '-' }}</div></td>
                                    <td class="text-center"><span class="amc-total-pill">{{ $row->jumlah_rentang }}</span></td>
                                    <td class="text-center no-print">
                                        <div class="btn-group btn-group-sm">
                                            @if($row->file_pendukung)
                                                <a href="{{ route('pemakaian-garbarata.file', $row->id) }}" target="_blank" class="btn btn-outline-secondary" title="File"><i class="bi bi-paperclip"></i></a>
                                            @endif
                                            <a href="{{ route('pemakaian-garbarata.edit', $row->id) }}" class="btn btn-outline-warning" title="Ubah"><i class="bi bi-pencil"></i></a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Keterangan + Total Batch (mirip form) --}}
                <div class="row g-3 mt-3 align-items-end">
                    <div class="col-lg-8">
                        <label class="form-label fw-bold">Keterangan Batch</label>
                        <div class="amc-cell" style="min-height:56px;">{{ $keterangan ?: '—' }}</div>
                        @if($creator)
                            <div class="small text-muted mt-2"><i class="bi bi-person"></i> Dicatat oleh: <b>{{ $creator->name }}</b></div>
                        @endif
                    </div>
                    <div class="col-lg-4">
                        <div class="rounded-4 border bg-light p-3">
                            <div class="small fw-bold text-muted text-uppercase">Ringkasan Batch {{ $group['mitra']?->nama_mitra }}</div>
                            <div class="fs-5 fw-black text-primary">{{ $group['jumlah_flight'] }} flight &middot; {{ $group['total_rentang'] }} rentang</div>
                            <div class="small text-muted mt-1">Durasi total {{ sprintf('%02d:%02d', intdiv($group['total_durasi'], 60), $group['total_durasi'] % 60) }} jam</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center text-muted py-5">
                <i class="bi bi-inbox fs-1 d-block mb-2 opacity-50"></i>
                Belum ada pemakaian garbarata pada tanggal ini.
            </div>
        </div>
    @endforelse

    @if($byMitra->isNotEmpty())
        <div class="card border-0 shadow-sm mt-3" style="background: linear-gradient(135deg, #0f2f57, #1d4ed8);">
            <div class="card-body text-white d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <div class="small text-uppercase fw-bold" style="letter-spacing:.08em;color:#fde68a;">Grand Total {{ $tanggal->isoFormat('D MMM Y') }}</div>
                    <div class="fs-5 fw-bold">{{ $summary['jumlah_mitra'] }} maskapai &middot; {{ $summary['jumlah_flight'] }} penerbangan &middot; {{ sprintf('%02d:%02d', intdiv($summary['total_durasi'], 60), $summary['total_durasi'] % 60) }} jam</div>
                </div>
                <div class="fs-3 fw-bold">{{ $summary['total_rentang'] }} rentang</div>
            </div>
        </div>
    @endif
</div>
@endsection
