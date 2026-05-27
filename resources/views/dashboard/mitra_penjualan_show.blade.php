@extends('layouts.app')
@section('title', 'Detail Laporan Penjualan')

@push('css')
    @include('dashboard.partials.mitra-ui')
@endpush

@section('content')
@php
    $rupiah = fn ($value) => 'Rp ' . number_format((float) $value, 0, ',', '.');
    $tanggal = fn ($value) => $value ? \Carbon\Carbon::parse($value)->format('d/m/Y') : '-';
    $tanggalWaktu = fn ($value) => $value ? \Carbon\Carbon::parse($value)->format('d/m/Y H:i') : '-';
    $persen = fn ($value) => $value !== null ? rtrim(rtrim(number_format((float) $value, 4, ',', '.'), '0'), ',') . '%' : '-';

    $layananPath = function ($layanan) {
        if (! $layanan) return collect();
        $items = collect([$layanan]);
        $parent = $layanan->parent;
        $guard = 0;
        while ($parent && $guard < 10) {
            $items->prepend($parent);
            $parent = $parent->parent;
            $guard++;
        }
        return $items;
    };
@endphp

<style>
    @keyframes mpTimelineIn {
        from { opacity: 0; transform: translateY(12px); }
        to { opacity: 1; transform: translateY(0); }
    }
    @keyframes mpTimelineFlow {
        0% { background-position: 0 0; }
        100% { background-position: 0 140px; }
    }
    @keyframes mpTimelinePulse {
        0%, 100% {
            transform: scale(1);
            box-shadow: 0 12px 22px rgba(15, 23, 42, .14), 0 0 0 0 rgba(37, 99, 235, .22);
        }
        50% {
            transform: scale(1.08);
            box-shadow: 0 16px 28px rgba(15, 23, 42, .18), 0 0 0 10px rgba(37, 99, 235, 0);
        }
    }
    @keyframes mpTimelineRing {
        0% { opacity: .5; transform: scale(.75); }
        70%, 100% { opacity: 0; transform: scale(1.65); }
    }
    @keyframes mpTimelineIconFloat {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-2px); }
    }
    @keyframes mpTimelineShine {
        0% { transform: translateX(-130%) skewX(-18deg); opacity: 0; }
        18% { opacity: .5; }
        55%, 100% { transform: translateX(180%) skewX(-18deg); opacity: 0; }
    }
    .mp-timeline-card {
        overflow: hidden;
        border: 1px solid rgba(37, 99, 235, .12) !important;
        background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
        box-shadow: 0 18px 44px rgba(15, 23, 42, .09) !important;
    }
    .mp-timeline-header {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 14px 16px;
        border-bottom: 1px solid #bfdbfe;
        background: linear-gradient(90deg, #eff6ff 0%, #f8fbff 58%, #ffffff 100%);
    }
    .mp-timeline-header-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 36px;
        height: 36px;
        border-radius: 11px;
        color: #fff;
        background: #1d4ed8;
        box-shadow: 0 12px 24px rgba(37, 99, 235, .22);
    }
    .mp-timeline-header-title {
        margin: 0;
        color: #1e3a8a;
        font-size: 15px;
        font-weight: 900;
    }
    .mp-timeline-header-subtitle {
        color: #64748b;
        font-size: 12px;
        font-weight: 700;
    }
    .mp-timeline {
        position: relative;
        padding: 16px 16px 18px 18px;
    }
    .mp-timeline::before {
        content: "";
        position: absolute;
        left: 35px;
        top: 24px;
        bottom: 28px;
        width: 2px;
        border-radius: 999px;
        background: linear-gradient(180deg, #cbd5e1, #93c5fd, #60a5fa, #2563eb, #93c5fd);
        background-size: 100% 140px;
        animation: mpTimelineFlow 2.8s linear infinite;
        box-shadow: 0 0 18px rgba(37, 99, 235, .2);
    }
    .mp-timeline-item {
        position: relative;
        display: grid;
        grid-template-columns: 36px minmax(0, 1fr);
        gap: 12px;
        margin-bottom: 14px;
        animation: mpTimelineIn .38s ease both;
    }
    .mp-timeline-item:nth-child(2) { animation-delay: .05s; }
    .mp-timeline-item:nth-child(3) { animation-delay: .1s; }
    .mp-timeline-item:nth-child(4) { animation-delay: .15s; }
    .mp-timeline-item:last-child {
        margin-bottom: 0;
    }
    .mp-timeline-dot {
        position: relative;
        z-index: 1;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 36px;
        height: 36px;
        border: 4px solid #fff;
        border-radius: 999px;
        color: #fff;
        box-shadow: 0 12px 22px rgba(15, 23, 42, .14);
        animation: mpTimelinePulse 2.4s ease-in-out infinite;
    }
    .mp-timeline-dot::after {
        content: "";
        position: absolute;
        inset: -7px;
        z-index: -1;
        border-radius: inherit;
        border: 1px solid currentColor;
        animation: mpTimelineRing 2.4s ease-out infinite;
    }
    .mp-timeline-dot i {
        animation: mpTimelineIconFloat 1.8s ease-in-out infinite;
    }
    .mp-timeline-dot.secondary { background: #64748b; }
    .mp-timeline-dot.warning { background: #f59e0b; }
    .mp-timeline-dot.success { background: #10b981; }
    .mp-timeline-dot.danger { background: #ef4444; }
    .mp-timeline-dot.primary { background: #2563eb; }
    .mp-timeline-content {
        position: relative;
        overflow: hidden;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        background: rgba(255, 255, 255, .92);
        padding: 11px 12px;
        box-shadow: 0 10px 26px rgba(15, 23, 42, .05);
        transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease;
    }
    .mp-timeline-content::before {
        content: "";
        position: absolute;
        inset: 0;
        width: 44%;
        background: linear-gradient(90deg, transparent, rgba(96, 165, 250, .16), rgba(255, 255, 255, .5), transparent);
        animation: mpTimelineShine 3.6s ease-in-out infinite;
        pointer-events: none;
    }
    .mp-timeline-content:hover {
        transform: translateY(-2px);
        border-color: #bfdbfe;
        box-shadow: 0 16px 32px rgba(37, 99, 235, .11);
    }
    @media (prefers-reduced-motion: reduce) {
        .mp-timeline::before,
        .mp-timeline-dot,
        .mp-timeline-dot::after,
        .mp-timeline-dot i,
        .mp-timeline-content::before,
        .mp-timeline-item {
            animation: none !important;
        }
    }
    .mp-timeline-title {
        color: #0f172a;
        font-size: 13px;
        font-weight: 900;
        letter-spacing: .01em;
    }
    .mp-timeline-time {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        margin-top: 5px;
        color: #475569;
        font-size: 12px;
        font-weight: 700;
    }
    .mp-timeline-note {
        margin-top: 5px;
        color: #64748b;
        font-size: 12px;
        font-weight: 600;
    }
    .mp-timeline-link {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        margin-top: 8px;
        border-radius: 10px;
        font-size: 12px;
        font-weight: 900;
    }
</style>

<div class="mp-hero mb-4">
    <div class="d-flex flex-column flex-lg-row align-items-start align-items-lg-center justify-content-between gap-3">
        <div class="d-flex align-items-start gap-3">
            <span class="mp-hero-icon"><i class="bi bi-graph-up-arrow fs-4"></i></span>
            <div>
                <div class="small text-white-50 fw-bold text-uppercase mb-1">Detail Laporan</div>
                <h4 class="mb-1 fw-bold text-white">Detail Laporan Penjualan</h4>
                <p class="mb-0 small fw-semibold text-white-50">{{ $mitra->nama_mitra }}</p>
            </div>
        </div>
        <a href="{{ route('mitra.konsesi-penjualan') }}" class="btn btn-light fw-bold shadow-sm">
            <i class="bi bi-arrow-left me-1"></i>Kembali
        </a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<div class="row g-4">
    {{-- Card Utama --}}
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="p-4 text-white" style="background: linear-gradient(135deg, #0f2f57, #1d6fb8);">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="small text-white-50 fw-bold text-uppercase mb-1">Laporan Pendapatan Konsesi</div>
                        <h5 class="fw-bold mb-1 text-white">
                            {{ $penjualan->layananJasa->nama_layanan ?? 'Layanan tidak diketahui' }}
                        </h5>
                        <div class="small text-white-50">
                            Periode: {{ $tanggal($penjualan->periode_mulai) }} s.d. {{ $tanggal($penjualan->periode_selesai) }}
                        </div>
                    </div>
                    <span class="badge bg-{{ $penjualan->status_color }} px-3 py-2 fs-6">
                        {{ $penjualan->label_status }}
                    </span>
                </div>
            </div>

            <div class="card-body p-4">
                {{-- Breadcrumb Layanan --}}
                @if($penjualan->layananJasa)
                    <div class="mb-4">
                        <div class="small text-muted fw-bold mb-2">Hierarki Layanan</div>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0" style="font-size: 13px;">
                                @foreach($layananPath($penjualan->layananJasa) as $node)
                                    @if($loop->last)
                                        <li class="breadcrumb-item active fw-bold">{{ $node->nama_layanan }}</li>
                                    @else
                                        <li class="breadcrumb-item text-muted">{{ $node->nama_layanan }}</li>
                                    @endif
                                @endforeach
                            </ol>
                        </nav>
                    </div>
                @endif

                {{-- Detail Angka --}}
                @php
                    $details = $penjualan->penerbangan_details ?? [];
                    $grandTotalPax = 0;
                    $billablePax = 0;
                    foreach ($details as $f) {
                        $d = (int) ($f['pax_dewasa'] ?? 0);
                        $a = (int) ($f['pax_anak'] ?? 0);
                        $b = (int) ($f['pax_bayi'] ?? 0);
                        $t = (int) ($f['pax_transit'] ?? 0);
                        $billablePax += $d + $a + $b;
                        $grandTotalPax += $d + $a + $b + $t;
                    }
                @endphp
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="p-3 rounded-3 border bg-light">
                            @if($penjualan->penerbangan_details)
                                <div class="small text-muted fw-bold"><i class="bi bi-people me-1 text-primary"></i>Total Pax</div>
                                <div class="fs-5 fw-bold text-dark">{{ number_format($grandTotalPax, 0, ',', '.') }} Pax</div>
                                @if($grandTotalPax !== $billablePax)
                                    <div class="small text-muted">{{ number_format($billablePax, 0, ',', '.') }} kena tagihan (tanpa transit)</div>
                                @endif
                            @else
                                <div class="small text-muted fw-bold"><i class="bi bi-cash-stack me-1 text-primary"></i>Total Omzet</div>
                                <div class="fs-5 fw-bold text-dark">{{ $rupiah($penjualan->total_omzet) }}</div>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 rounded-3 border bg-light">
                            @if($penjualan->penerbangan_details)
                                <div class="small text-muted fw-bold"><i class="bi bi-tag me-1 text-primary"></i>Tarif Dasar Layanan</div>
                                <div class="fs-5 fw-bold text-dark">{{ $rupiah($penjualan->layananJasa->tarif_dasar ?? 0) }} / Pax</div>
                            @else
                                <div class="small text-muted fw-bold"><i class="bi bi-percent me-1 text-primary"></i>Persentase Konsesi</div>
                                <div class="fs-5 fw-bold text-dark">{{ $persen($penjualan->persentase_konsesi) }}</div>
                            @endif
                        </div>
                    </div>
                    @if(!$penjualan->penerbangan_details)
                    <div class="col-md-6">
                        <div class="p-3 rounded-3 border bg-light">
                            <div class="small text-muted fw-bold"><i class="bi bi-calculator me-1 text-primary"></i>Nilai Konsesi</div>
                            <div class="fs-5 fw-bold text-dark">{{ $rupiah($penjualan->nilai_konsesi) }}</div>
                        </div>
                    </div>
                    @endif
                    <div class="col-md-6">
                        <div class="p-3 rounded-3 border" style="background: #e8f5e9;">
                            <div class="small text-muted fw-bold"><i class="bi bi-receipt me-1 text-success"></i>Nilai Tagihan</div>
                            @php
                                $tarifDasar = (float) ($penjualan->layananJasa->tarif_dasar ?? 0);
                                $nilaiTagihanDisplay = $penjualan->penerbangan_details
                                    ? $billablePax * $tarifDasar
                                    : (float) $penjualan->nilai_tagihan;
                            @endphp
                            <div class="fs-5 fw-bold text-success">{{ $rupiah($nilaiTagihanDisplay) }}</div>
                            @if($penjualan->penerbangan_details)
                                <div class="small text-muted">{{ number_format($billablePax, 0, ',', '.') }} Pax &times; {{ $rupiah($tarifDasar) }}</div>
                            @endif
                        </div>
                    </div>
                    @if($penjualan->nilai_minimum_guarantee)
                        <div class="col-md-6">
                            <div class="p-3 rounded-3 border bg-light">
                                <div class="small text-muted fw-bold"><i class="bi bi-shield-check me-1 text-primary"></i>Minimum Guarantee</div>
                                <div class="fs-5 fw-bold text-dark">{{ $rupiah($penjualan->nilai_minimum_guarantee) }}</div>
                            </div>
                        </div>
                    @endif
                    @if($penjualan->total_transaksi)
                        <div class="col-md-6">
                            <div class="p-3 rounded-3 border bg-light">
                                <div class="small text-muted fw-bold"><i class="bi bi-hash me-1 text-primary"></i>Total Transaksi</div>
                                <div class="fs-5 fw-bold text-dark">{{ number_format($penjualan->total_transaksi, 0, ',', '.') }}</div>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Catatan Mitra --}}
                @if($penjualan->catatan_mitra)
                    <div class="mt-4 p-3 rounded-3 border bg-light">
                        <div class="small text-muted fw-bold mb-1"><i class="bi bi-chat-left-text me-1"></i>Catatan Anda</div>
                        <div>{{ $penjualan->catatan_mitra }}</div>
                    </div>
                @endif

                {{-- Detail Penerbangan --}}
                @if($penjualan->penerbangan_details)
                    <div class="mt-4 p-3 rounded-3 border bg-light">
                        <div class="small text-muted fw-bold mb-3"><i class="bi bi-airplane-engines me-1 text-primary"></i>Detail Penerbangan (Pax)</div>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered bg-white mb-0 text-center">
                                <thead class="table-light">
                                    <tr>
                                        <th class="small text-uppercase">Nomor Penerbangan</th>
                                        <th class="small text-uppercase">Dewasa</th>
                                        <th class="small text-uppercase">Anak</th>
                                        <th class="small text-uppercase">Bayi</th>
                                        <th class="small text-uppercase">Transit</th>
                                        <th class="small text-uppercase">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $gtDewasa = 0; $gtAnak = 0; $gtBayi = 0; $gtTransit = 0;
                                    @endphp
                                    @foreach($penjualan->penerbangan_details as $flight)
                                        @php
                                            $gtDewasa += (int)($flight['pax_dewasa'] ?? 0);
                                            $gtAnak += (int)($flight['pax_anak'] ?? 0);
                                            $gtBayi += (int)($flight['pax_bayi'] ?? 0);
                                            $gtTransit += (int)($flight['pax_transit'] ?? 0);
                                        @endphp
                                        <tr>
                                            <td class="fw-bold">{{ $flight['nomor_penerbangan'] ?? '-' }}</td>
                                            <td>{{ $flight['pax_dewasa'] ?? 0 }}</td>
                                            <td>{{ $flight['pax_anak'] ?? 0 }}</td>
                                            <td>{{ $flight['pax_bayi'] ?? 0 }}</td>
                                            <td>{{ $flight['pax_transit'] ?? 0 }}</td>
                                            <td class="fw-bold bg-light">{{ ($flight['pax_dewasa'] ?? 0) + ($flight['pax_anak'] ?? 0) + ($flight['pax_bayi'] ?? 0) + ($flight['pax_transit'] ?? 0) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr class="fw-bold bg-light">
                                        <td class="text-end">GRAND TOTAL:</td>
                                        <td>{{ $gtDewasa }}</td>
                                        <td>{{ $gtAnak }}</td>
                                        <td>{{ $gtBayi }}</td>
                                        <td>{{ $gtTransit }}</td>
                                        <td>{{ $gtDewasa + $gtAnak + $gtBayi + $gtTransit }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        <div class="small text-muted mt-2">
                            <i class="bi bi-info-circle me-1"></i>
                            Total tagihan dihitung dari (Dewasa + Anak + Bayi) &times; tarif dasar. <strong>Pax Transit tidak dikenakan tagihan</strong>.
                        </div>
                    </div>
                @endif

                {{-- Catatan Verifikator --}}
                @if($penjualan->catatan_verifikator)
                    <div class="mt-3 p-3 rounded-3 border border-danger bg-danger bg-opacity-10">
                        <div class="small text-danger fw-bold mb-1"><i class="bi bi-exclamation-triangle me-1"></i>Catatan Verifikator</div>
                        <div class="text-danger">{{ $penjualan->catatan_verifikator }}</div>
                    </div>
                @endif

                {{-- File Laporan (legacy — for old records without details) --}}
                @if($penjualan->file_laporan)
                    <div class="mt-4 p-3 rounded-3 border">
                        <div class="small text-muted fw-bold mb-2"><i class="bi bi-file-earmark me-1 text-primary"></i>File Laporan</div>
                        <a href="{{ asset('storage/' . $penjualan->file_laporan) }}" target="_blank" class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-download me-1"></i>Lihat / Download File
                        </a>
                    </div>
                @endif

                {{-- Detail Laporan per Periode --}}
                @if($penjualan->details && $penjualan->details->count() > 0)
                    <div class="mt-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="small text-muted fw-bold"><i class="bi bi-list-check me-1 text-primary"></i>Rincian Laporan ({{ $penjualan->details->count() }} entri)</div>
                            @if($penjualan->status === 'draft')
                                <a href="{{ route('mitra.penjualan.create') }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-plus-lg me-1"></i>Tambah Laporan
                                </a>
                            @endif
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered bg-white mb-0 align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th class="small text-uppercase" width="5%">No</th>
                                        <th class="small text-uppercase">Periode</th>
                                        <th class="small text-uppercase text-end">Omzet</th>
                                        <th class="small text-uppercase text-center">Transaksi</th>
                                        <th class="small text-uppercase">File</th>
                                        <th class="small text-uppercase">Catatan</th>
                                        <th class="small text-uppercase">Dikirim</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($penjualan->details as $detail)
                                        <tr>
                                            <td class="text-center">{{ $loop->iteration }}</td>
                                            <td>{{ $tanggal($detail->periode_mulai) }} s.d. {{ $tanggal($detail->periode_selesai) }}</td>
                                            <td class="text-end fw-bold">{{ $rupiah($detail->total_omzet) }}</td>
                                            <td class="text-center">{{ $detail->total_transaksi ? number_format($detail->total_transaksi, 0, ',', '.') : '-' }}</td>
                                            <td>
                                                @if($detail->file_laporan)
                                                    <a href="{{ asset('storage/' . $detail->file_laporan) }}" target="_blank" class="btn btn-sm btn-light border py-0 px-2">
                                                        <i class="bi bi-file-earmark me-1"></i>Lihat
                                                    </a>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td class="small">{{ $detail->catatan_mitra ?? '-' }}</td>
                                            <td class="small text-muted">{{ $tanggalWaktu($detail->submitted_at) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="table-light">
                                    <tr class="fw-bold">
                                        <td colspan="2" class="text-end">TOTAL:</td>
                                        <td class="text-end text-success">{{ $rupiah($penjualan->details->sum('total_omzet')) }}</td>
                                        <td class="text-center">{{ $penjualan->details->sum('total_transaksi') ?: '-' }}</td>
                                        <td colspan="3"></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                @endif

                {{-- Submit Button for Draft --}}
                @if($penjualan->status === 'draft')
                    <div class="mt-4 d-flex gap-2 justify-content-end">
                        <a href="{{ route('mitra.penjualan.create') }}" class="btn btn-outline-primary fw-bold">
                            <i class="bi bi-plus-lg me-1"></i>Tambah Laporan
                        </a>
                        <form action="{{ route('mitra.penjualan.submit', $penjualan) }}" method="POST" onsubmit="return confirm('Ajukan laporan bulan ini untuk verifikasi? Setelah diajukan, Anda tidak bisa menambah laporan lagi.');">
                            @csrf
                            <button class="btn btn-warning fw-bold">
                                <i class="bi bi-send me-1"></i>Ajukan Verifikasi
                            </button>
                        </form>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Sidebar: Timeline --}}
    <div class="col-lg-4">
        <div class="card border-0 rounded-4 mb-4 mp-timeline-card">
            <div class="mp-timeline-header">
                <span class="mp-timeline-header-icon"><i class="bi bi-activity"></i></span>
                <div>
                    <h6 class="mp-timeline-header-title">Timeline Aktivitas</h6>
                    <div class="mp-timeline-header-subtitle">Riwayat proses laporan sampai tagihan.</div>
                </div>
            </div>
            <div class="mp-timeline">
                    {{-- Dibuat --}}
                    <div class="mp-timeline-item">
                        <div class="mp-timeline-dot secondary">
                            <i class="bi bi-file-earmark-plus"></i>
                        </div>
                        <div class="mp-timeline-content">
                            <div class="mp-timeline-title">Laporan Dibuat</div>
                            <div class="mp-timeline-time"><i class="bi bi-calendar2-check text-primary"></i>{{ $tanggalWaktu($penjualan->created_at) }}</div>
                            <div class="mp-timeline-note">Draft awal laporan berhasil tercatat di sistem.</div>
                        </div>
                    </div>

                    {{-- Diajukan --}}
                    @if($penjualan->submitted_at)
                        <div class="mp-timeline-item">
                            <div class="mp-timeline-dot warning">
                                <i class="bi bi-send"></i>
                            </div>
                            <div class="mp-timeline-content">
                                <div class="mp-timeline-title">Diajukan untuk Verifikasi</div>
                                <div class="mp-timeline-time"><i class="bi bi-clock text-warning"></i>{{ $tanggalWaktu($penjualan->submitted_at) }}</div>
                                <div class="mp-timeline-note">Laporan sudah dikirim dan menunggu pemeriksaan admin.</div>
                            </div>
                        </div>
                    @endif

                    {{-- Diverifikasi / Ditolak --}}
                    @if($penjualan->verified_at)
                        <div class="mp-timeline-item">
                            <div class="mp-timeline-dot {{ $penjualan->status === 'ditolak' ? 'danger' : 'success' }}">
                                <i class="bi {{ $penjualan->status === 'ditolak' ? 'bi-x-lg' : 'bi-check2' }}"></i>
                            </div>
                            <div class="mp-timeline-content">
                                <div class="mp-timeline-title">{{ $penjualan->status === 'ditolak' ? 'Laporan Ditolak' : 'Laporan Diverifikasi' }}</div>
                                <div class="mp-timeline-time">
                                    <i class="bi {{ $penjualan->status === 'ditolak' ? 'bi-exclamation-circle text-danger' : 'bi-patch-check text-success' }}"></i>
                                    {{ $tanggalWaktu($penjualan->verified_at) }}
                                </div>
                                @if($penjualan->verifiedByUser)
                                    <div class="mp-timeline-note">oleh {{ $penjualan->verifiedByUser->name ?? $penjualan->verifiedByUser->email }}</div>
                                @endif
                            </div>
                        </div>
                    @endif

                    {{-- Ditagihkan --}}
                    @if($penjualan->tagihan_jasa_id && $penjualan->tagihanJasa)
                        <div class="mp-timeline-item">
                            <div class="mp-timeline-dot primary">
                                <i class="bi bi-receipt"></i>
                            </div>
                            <div class="mp-timeline-content">
                                <div class="mp-timeline-title">Tagihan Dibuat</div>
                                <div class="mp-timeline-time"><i class="bi bi-calendar2-check text-primary"></i>{{ $tanggalWaktu($penjualan->tagihanJasa->created_at) }}</div>
                                <div class="mp-timeline-note">Tagihan telah diterbitkan berdasarkan laporan ini.</div>
                                <a href="{{ route('mitra.tagihan-jasa.show', $penjualan->tagihanJasa) }}" class="btn btn-sm btn-outline-primary mp-timeline-link">
                                    <i class="bi bi-box-arrow-up-right"></i>{{ $penjualan->tagihanJasa->nomor_tagihan ?? 'Lihat Tagihan' }}
                                </a>
                            </div>
                        </div>
                    @endif
            </div>
        </div>

        {{-- Info Status --}}
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white fw-bold">
                <i class="bi bi-info-circle me-1"></i>Status Laporan
            </div>
            <div class="card-body">
                @if($penjualan->status === 'diajukan')
                    <div class="alert alert-warning mb-0">
                        <i class="bi bi-hourglass-split me-1"></i>
                        <strong>Menunggu Verifikasi</strong>
                        <div class="small mt-1">Laporan Anda sedang dalam proses verifikasi oleh admin.</div>
                    </div>
                @elseif($penjualan->status === 'diverifikasi')
                    <div class="alert alert-success mb-0">
                        <i class="bi bi-check-circle me-1"></i>
                        <strong>Laporan Terverifikasi</strong>
                        <div class="small mt-1">Laporan Anda telah diverifikasi. Tagihan akan segera dibuat.</div>
                    </div>
                @elseif($penjualan->status === 'ditolak')
                    <div class="alert alert-danger mb-0">
                        <i class="bi bi-x-circle me-1"></i>
                        <strong>Laporan Ditolak</strong>
                        <div class="small mt-1">Silakan perbaiki laporan sesuai catatan verifikator.</div>
                    </div>
                @elseif($penjualan->status === 'ditagihkan')
                    <div class="alert alert-primary mb-0">
                        <i class="bi bi-receipt me-1"></i>
                        <strong>Tagihan Sudah Dibuat</strong>
                        <div class="small mt-1">Tagihan telah diterbitkan berdasarkan laporan ini.</div>
                    </div>
                @else
                    <div class="alert alert-secondary mb-0">
                        <i class="bi bi-file-earmark me-1"></i>
                        <strong>Draft</strong>
                        <div class="small mt-1">Laporan masih dalam bentuk draft, belum diajukan.</div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
