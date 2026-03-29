@extends('layouts.app')

@section('title', 'Dashboard - Pejabat Pengadaan')

@push('css')
    <!-- CSS tambahan untuk grafik dan estetika -->
    <style>
        .card-stats { transition: all 0.3s ease; }
        .card-stats:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important; }
        .icon-circle { width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 24px; }
    </style>
@endpush

@section('content')

{{-- 1. HEADER & WELCOME BANNER --}}
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 pb-3 border-bottom">
    <div class="mb-3 mb-md-0">
        <h4 class="mb-1 fw-bold text-dark">Selamat Datang, {{ Auth::user()->name }}!</h4>
        <p class="text-muted mb-0">
            Ada <strong class="text-danger">{{ $kpi['tagihan_revisi'] }} Tagihan</strong> yang butuh perhatian atau revisi hari ini.
        </p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('suppliers.create') }}" class="btn btn-outline-primary fw-bold shadow-sm">
            <i class="bi bi-person-plus me-1"></i> Mitra/Vendor
        </a>
        <a href="{{ route('contracts.create') }}" class="btn btn-primary fw-bold shadow-sm">
            <i class="bi bi-file-earmark-plus me-1"></i> Kontrak (SPK)
        </a>
    </div>
</div>

{{-- 2. KPI CARDS (METRIKS UTAMA) --}}
<div class="row g-4 mb-4">
    <!-- Kontrak Aktif -->
    <div class="col-sm-6 col-xl-3">
        <div class="card card-stats border-0 shadow-sm rounded-4 h-100">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="icon-circle bg-primary bg-opacity-10 text-primary">
                        <i class="bi bi-briefcase-fill"></i>
                    </div>
                    <span class="badge bg-light text-primary border border-primary-subtle">Berjalan</span>
                </div>
                <h3 class="fw-bold mb-1">{{ $kpi['kontrak_aktif'] }} <span class="fs-6 text-muted fw-normal">Proyek</span></h3>
                <p class="text-muted small mb-0"><span class="text-success fw-bold">{{ $kpi['selesai_bulan_ini'] }}</span> Selesai bulan ini.</p>
            </div>
        </div>
    </div>
    
    <!-- Total Nilai Kontrak Berjalan -->
    <div class="col-sm-6 col-xl-3">
        <div class="card card-stats border-0 shadow-sm rounded-4 h-100">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="icon-circle bg-success bg-opacity-10 text-success">
                        <i class="bi bi-cash-stack"></i>
                    </div>
                    <span class="badge bg-light text-success border border-success-subtle">Rupiah Aktif</span>
                </div>
                <h3 class="fw-bold mb-1 fs-4 text-truncate" title="Rp {{ number_format($kpi['nilai_kontrak_aktif'], 0, ',', '.') }}">
                    Rp {{ number_format($kpi['nilai_kontrak_aktif'], 0, ',', '.') }}
                </h3>
                <p class="text-muted small mb-0">Total nilai proyek berjalan.</p>
            </div>
        </div>
    </div>
    
    <!-- Tagihan Menunggu Proses -->
    <div class="col-sm-6 col-xl-3">
        <div class="card card-stats border-0 shadow-sm rounded-4 h-100">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="icon-circle bg-warning bg-opacity-10 text-warning">
                        <i class="bi bi-hourglass-split"></i>
                    </div>
                    <span class="badge bg-light text-warning border border-warning-subtle">Pending</span>
                </div>
                <h3 class="fw-bold mb-1">{{ $kpi['tagihan_menunggu'] }} <span class="fs-6 text-muted fw-normal">Dokumen</span></h3>
                <p class="text-muted small mb-0">Menunggu PPK / Bendahara.</p>
            </div>
        </div>
    </div>

    <!-- Butuh Perhatian (Revisi) -->
    <div class="col-sm-6 col-xl-3">
        <div class="card card-stats border-0 shadow-sm rounded-4 h-100 border-bottom border-danger border-4">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="icon-circle bg-danger bg-opacity-10 text-danger">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                    </div>
                    <span class="badge bg-danger">Action Required</span>
                </div>
                <h3 class="fw-bold text-danger mb-1">{{ $kpi['tagihan_revisi'] }} <span class="fs-6 text-muted fw-normal">Dokumen</span></h3>
                <p class="text-muted small mb-0">Dikembalikan atau direvisi.</p>
            </div>
        </div>
    </div>
</div>

{{-- 3. VISUALISASI DATA (GRAFIK) --}}
<div class="row g-4 mb-4">
    <!-- Kolom Kiri: Doughnut Chart 60% -->
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-header bg-white p-4 border-bottom rounded-top-4">
                <h6 class="mb-0 fw-bold"><i class="bi bi-pie-chart text-success me-2"></i> Serapan Anggaran Kontrak</h6>
            </div>
            <div class="card-body p-4 d-flex justify-content-center align-items-center">
                <div style="height: 300px; width: 100%;">
                    <canvas id="serapanChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Kolom Kanan: Pie Chart 40% -->
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-header bg-white p-4 border-bottom rounded-top-4">
                <h6 class="mb-0 fw-bold"><i class="bi bi-diagram-3 text-primary me-2"></i> Distribusi Status Tagihan</h6>
            </div>
            <div class="card-body p-4 d-flex justify-content-center align-items-center">
                <div style="height: 300px; width: 100%;">
                    <canvas id="statusChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- 4. ACTIONABLE TABLES --}}
<div class="row g-4 mb-4">
    
    <!-- Tabel Kiri: Jatuh Tempo Kontrak -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-4 h-100 border-start border-warning border-4">
            <div class="card-header bg-white p-3 border-bottom d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-alarm text-warning me-2"></i> Peringatan Jatuh Tempo Kontrak (H-14)</h6>
                <a href="{{ route('contracts.index') }}" class="btn btn-sm btn-light">Lihat Semua</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light text-muted small">
                            <tr>
                                <th class="ps-4">No. SPK & Pekerjaan</th>
                                <th>Tgl Selesai</th>
                                <th>Sisa Waktu</th>
                                <th class="text-center pe-4">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($table_jatuh_tempo as $k)
                                @php
                                    $diff = \Carbon\Carbon::parse($k->tanggal_selesai)->diffInDays(now());
                                    $isLate = \Carbon\Carbon::parse($k->tanggal_selesai)->isPast();
                                @endphp
                                <tr>
                                    <td class="ps-4">
                                        <div class="fw-bold">{{ $k->nomor_spk }}</div>
                                        <small class="text-muted">{{ Str::limit($k->nama_pekerjaan, 30) }}</small>
                                    </td>
                                    <td>{{ \Carbon\Carbon::parse($k->tanggal_selesai)->format('d M Y') }}</td>
                                    <td>
                                        @if($isLate)
                                            <span class="badge bg-danger">Terlambat {{ $diff }} Hari</span>
                                        @else
                                            <span class="badge bg-warning text-dark">{{ $diff }} Hari Lagi</span>
                                        @endif
                                    </td>
                                    <td class="text-center pe-4">
                                        <a href="{{ route('contracts.show', $k->id) }}" class="btn btn-sm btn-outline-primary" title="Tinjau Kontrak">
                                            <i class="bi bi-search"></i>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">Maneh aman hari ini! Tidak ada kontrak yang mendesak.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabel Kanan: Tagihan Bemasalah / Revisi -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-4 h-100 border-start border-danger border-4">
            <div class="card-header bg-white p-3 border-bottom d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-x-octagon text-danger me-2"></i> Tagihan Dikembalikan / Revisi</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light text-muted small">
                            <tr>
                                <th class="ps-4">No. Tagihan</th>
                                <th>Posisi Terakhir</th>
                                <th>Catatan Penolakan</th>
                                <th class="text-center pe-4">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($table_tagihan_revisi as $tag)
                                <tr>
                                    <td class="ps-4">
                                        <div class="fw-bold text-primary">{{ $tag->nomor_tagihan }}</div>
                                        <small class="text-muted fw-bold">Rp {{ number_format($tag->total_netto,0,',','.') }}</small>
                                    </td>
                                    <td>
                                        <span class="badge bg-danger">{{ str_replace('_', ' ', $tag->status) }}</span>
                                    </td>
                                    <td>
                                        <small class="text-muted fst-italic">
                                            "{{ Str::limit($tag->logs->first()->catatan ?? 'Tidak ada catatan.', 40) }}"
                                        </small>
                                    </td>
                                    <td class="text-center pe-4">
                                        <button class="btn btn-sm btn-outline-danger" title="Perbaiki Tagihan">
                                            <i class="bi bi-pencil-square"></i> Revisi
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">Luar biasa! Tidak ada tagihan yang direvisi.</td>
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

@push('script')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function () {
        // Doughnut Chart (Serapan Anggaran)
        const ctxSerapan = document.getElementById('serapanChart').getContext('2d');
        new Chart(ctxSerapan, {
            type: 'doughnut',
            data: {
                labels: {!! json_encode($chart_serapan_labels) !!},
                datasets: [{
                    data: {!! json_encode($chart_serapan_data) !!},
                    backgroundColor: ['#198754', '#dee2e6'], // Success Green, Light Gray
                    borderWidth: 0,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.label || '';
                                let value = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(context.raw);
                                return label + ': ' + value;
                            }
                        }
                    }
                },
                cutout: '70%'
            }
        });

        // Pie Chart (Distribusi Status Tagihan)
        const ctxStatus = document.getElementById('statusChart').getContext('2d');
        
        // Buat mapping warna semantic otomatis
        const basicLabels = {!! json_encode($chart_status_labels) !!};
        const basicData = {!! json_encode($chart_status_data) !!};
        const bgColors = basicLabels.map(label => {
            if(label.includes('REVISI') || label.includes('TOLAK')) return '#dc3545'; // Danger
            if(label.includes('PENDING')) return '#ffc107'; // Warning
            if(label.includes('READY')) return '#0d6efd'; // Primary
            if(label.includes('CAIR') || label.includes('SP2D')) return '#198754'; // Success
            return '#6c757d'; // Default secondary
        });

        new Chart(ctxStatus, {
            type: 'pie',
            data: {
                labels: basicLabels.map(l => l.replace(/_/g, ' ')),
                datasets: [{
                    data: basicData,
                    backgroundColor: bgColors,
                    borderWidth: 1,
                    borderColor: '#fff',
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'right' }
                }
            }
        });
    });
</script>
@endpush
