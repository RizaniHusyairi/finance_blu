@extends('layouts.app')
@section('title', 'Dashboard PLT/PLH')

@push('css')
<style>
    .plt-banner {
        background: linear-gradient(135deg, #0f2027, #203a43, #2c5364);
        color: white;
        transition: all 0.4s ease;
    }
    .plt-banner:hover {
        background: linear-gradient(135deg, #122831, #264650, #35657a);
        box-shadow: 0 15px 25px rgba(0,0,0,0.15) !important;
    }
    .card-kpi {
        transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        border: 1px solid rgba(0,0,0,0.05);
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(10px);
    }
    .card-kpi:hover {
        transform: translateY(-5px);
        box-shadow: 0 12px 20px rgba(0,0,0,0.08) !important;
        border-color: rgba(0,0,0,0.1);
    }
    .kpi-icon {
        width: 60px;
        height: 60px;
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 28px;
        transition: all 0.3s ease;
    }
    .card-kpi:hover .kpi-icon {
        transform: scale(1.1) rotate(5deg);
    }
    .live-dot { 
        display: inline-block; 
        width: 8px; 
        height: 8px; 
        border-radius: 50%; 
        background-color: #0dcaf0; 
        animation: livePulse 1.5s infinite; 
        margin-right: 6px; 
    }
    @keyframes livePulse { 
        0%,100% { opacity: 1; transform: scale(1); } 
        50% { opacity: 0.5; transform: scale(1.3); } 
    }
    .table-hover tbody tr {
        transition: all 0.2s ease;
    }
    .table-hover tbody tr:hover {
        background-color: rgba(13, 110, 253, 0.03);
        transform: scale(1.002);
    }
    .badge-modern {
        padding: 0.5em 0.8em;
        font-weight: 600;
        letter-spacing: 0.3px;
        border-radius: 6px;
    }
</style>
@endpush

@section('content')
<x-page-title title="Dashboard PLT/PLH" subtitle="Ringkasan Tugas & Persetujuan" />

{{-- WELCOME BANNER --}}
<div class="row mb-4">
    <div class="col-12">
        <div class="card plt-banner rounded-4 border-0 shadow-sm overflow-hidden">
            <div class="card-body p-4 d-flex align-items-center justify-content-between position-relative">
                <div style="z-index: 2;">
                    <h4 class="mb-2 fw-bold text-white" style="letter-spacing: -0.5px;">Selamat Datang, {{ auth()->user()->name }}! 👋</h4>
                    <p class="mb-0 text-white-50" style="opacity: 0.9;">
                        <span class="live-dot"></span>
                        Menampilkan ringkasan tugas dan alur kerja yang membutuhkan persetujuan Anda sebagai PLT/PLH.
                    </p>
                </div>
                <div class="d-none d-md-flex align-items-center text-white-50" style="z-index: 2; opacity: 0.8;">
                    <i class="bi bi-calendar-event me-2 fs-5"></i> 
                    <span class="fs-6">{{ now()->translatedFormat('l, d F Y') }}</span>
                </div>
                <!-- Decorative element -->
                <div class="position-absolute" style="right: -20px; top: -40px; opacity: 0.05; font-size: 150px; z-index: 1;">
                    <i class="bi bi-shield-check"></i>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- KPI CARDS --}}
<div class="row g-4 mb-4">
    <div class="col-md-6 col-xl-4">
        <div class="card card-kpi w-100 rounded-4 shadow-sm h-100">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <h6 class="text-muted fw-semibold mb-1 text-uppercase" style="letter-spacing: 1px; font-size: 0.8rem;">Menunggu Persetujuan</h6>
                        <h3 class="fw-bold mb-0 text-dark">{{ $totalPending }}</h3>
                    </div>
                    <div class="kpi-icon bg-warning bg-opacity-10 text-warning shadow-sm">
                        <i class="bi bi-hourglass-split"></i>
                    </div>
                </div>
                <div class="d-flex align-items-center mt-3">
                    <span class="badge bg-warning text-dark border-0 rounded-pill px-3 py-2 fw-semibold">
                        <i class="bi bi-file-earmark-text me-1"></i> Tagihan Jasa
                    </span>
                    <small class="text-muted ms-2">Perlu diverifikasi segera.</small>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-4">
        <div class="card card-kpi w-100 rounded-4 shadow-sm h-100">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <h6 class="text-muted fw-semibold mb-1 text-uppercase" style="letter-spacing: 1px; font-size: 0.8rem;">Telah Disetujui</h6>
                        <h3 class="fw-bold mb-0 text-dark">{{ $totalApproved }}</h3>
                    </div>
                    <div class="kpi-icon bg-success bg-opacity-10 text-success shadow-sm">
                        <i class="bi bi-check-circle-fill"></i>
                    </div>
                </div>
                <div class="d-flex align-items-center mt-3">
                    <span class="badge bg-success border-0 rounded-pill px-3 py-2 fw-semibold">
                        <i class="bi bi-graph-up me-1"></i> Total Kinerja
                    </span>
                    <small class="text-muted ms-2">Dokumen telah diproses.</small>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- DATA TABLES --}}
<div class="row g-4 mb-4">
    {{-- Pending Tagihan --}}
    <div class="col-xl-6">
        <div class="card w-100 rounded-4 border-0 shadow-sm h-100 border-top border-warning border-4">
            <div class="card-header bg-white p-4 border-bottom-0 d-flex justify-content-between align-items-center rounded-top-4">
                <h5 class="mb-0 fw-bold d-flex align-items-center">
                    <div class="bg-warning bg-opacity-10 text-warning rounded-circle p-2 me-3 d-inline-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                        <i class="bi bi-clock-history fs-5"></i>
                    </div>
                    Tagihan Menunggu Verifikasi
                </h5>
                @if($pendingTagihan->count() > 0)
                <span class="badge bg-warning text-dark badge-modern shadow-sm">{{ $pendingTagihan->count() }} Baru</span>
                @endif
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 border-top">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4 text-muted fw-semibold" style="font-size: 0.85rem; text-transform: uppercase;">No. Tagihan</th>
                                <th class="text-muted fw-semibold" style="font-size: 0.85rem; text-transform: uppercase;">Mitra / Vendor</th>
                                <th class="text-end pe-4 text-muted fw-semibold" style="font-size: 0.85rem; text-transform: uppercase;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($pendingTagihan as $t)
                            <tr>
                                <td class="ps-4 py-3">
                                    <div class="fw-bold text-dark">{{ $t->nomor_tagihan }}</div>
                                    <small class="text-muted"><i class="bi bi-calendar-date me-1"></i>{{ $t->tanggal_tagihan ? \Carbon\Carbon::parse($t->tanggal_tagihan)->format('d/m/Y') : '-' }}</small>
                                </td>
                                <td>
                                    <div class="fw-semibold text-secondary">{{ $t->mitra->nama_mitra ?? ($t->mitraLegacy->nama_perusahaan ?? 'Tanpa Mitra') }}</div>
                                    <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 mt-1" style="font-size: 0.7rem;">{{ $t->tipe_tagihan }}</span>
                                </td>
                                <td class="text-end pe-4">
                                    <a href="{{ route('verifikasi-tagihan-jasa.show', $t->id) }}" class="btn btn-sm btn-primary rounded-pill px-3 py-1 fw-semibold shadow-sm" style="transition: all 0.2s;">
                                        Tinjau <i class="bi bi-arrow-right ms-1"></i>
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="text-center py-5">
                                    <div class="d-flex flex-column align-items-center justify-content-center text-muted">
                                        <div class="bg-light rounded-circle d-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                                            <i class="bi bi-check2-all fs-1 text-success opacity-50"></i>
                                        </div>
                                        <h6 class="fw-bold text-dark">Luar biasa!</h6>
                                        <p class="mb-0">Tidak ada tagihan yang menunggu verifikasi Anda saat ini.</p>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($pendingTagihan->count() > 0)
            <div class="card-footer bg-white border-top text-center p-3 rounded-bottom-4">
                <a href="{{ route('verifikasi-tagihan-jasa.index') }}" class="text-decoration-none fw-semibold text-primary">Lihat Semua Tagihan <i class="bi bi-arrow-right"></i></a>
            </div>
            @endif
        </div>
    </div>

    {{-- Approved Tagihan (Recent) --}}
    <div class="col-xl-6">
        <div class="card w-100 rounded-4 border-0 shadow-sm h-100 border-top border-success border-4">
            <div class="card-header bg-white p-4 border-bottom-0 d-flex justify-content-between align-items-center rounded-top-4">
                <h5 class="mb-0 fw-bold d-flex align-items-center">
                    <div class="bg-success bg-opacity-10 text-success rounded-circle p-2 me-3 d-inline-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                        <i class="bi bi-check2-square fs-5"></i>
                    </div>
                    Tagihan Terakhir Disetujui
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 border-top">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4 text-muted fw-semibold" style="font-size: 0.85rem; text-transform: uppercase;">No. Tagihan</th>
                                <th class="text-muted fw-semibold" style="font-size: 0.85rem; text-transform: uppercase;">Tipe & Mitra</th>
                                <th class="text-end pe-4 text-muted fw-semibold" style="font-size: 0.85rem; text-transform: uppercase;">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($approvedTagihan as $t)
                            <tr>
                                <td class="ps-4 py-3">
                                    <div class="fw-bold text-dark">{{ $t->nomor_tagihan }}</div>
                                    <small class="text-muted">Netto: Rp {{ number_format($t->total_netto, 0, ',', '.') }}</small>
                                </td>
                                <td>
                                    <div class="fw-semibold text-secondary">{{ $t->mitra->nama_mitra ?? ($t->mitraLegacy->nama_perusahaan ?? 'Tanpa Mitra') }}</div>
                                    <span class="badge bg-light text-secondary border mt-1" style="font-size: 0.7rem;">{{ $t->tipe_tagihan }}</span>
                                </td>
                                <td class="text-end pe-4">
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 badge-modern">
                                        <i class="bi bi-check-lg me-1"></i> Disetujui
                                    </span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="text-center py-5">
                                    <div class="d-flex flex-column align-items-center justify-content-center text-muted">
                                        <div class="bg-light rounded-circle d-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                                            <i class="bi bi-clock-history fs-1 text-secondary opacity-50"></i>
                                        </div>
                                        <p class="mb-0">Belum ada riwayat persetujuan tagihan terbaru.</p>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
