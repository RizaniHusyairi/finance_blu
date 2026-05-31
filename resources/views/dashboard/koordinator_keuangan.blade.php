@extends('layouts.app')
@section('title', 'Dashboard Koordinator Keuangan')

@include('dashboard.partials.verifikator-dashboard-styles')

@php
    $fmtRp = fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.');
@endphp

@section('content')
<div class="vdash">
    <x-page-title title="Dashboard Koordinator Keuangan" subtitle="Koordinasi & Verifikasi Alur Dokumen Keuangan" />

    {{-- ===== HERO ===== --}}
    <div class="v-hero" style="background:linear-gradient(125deg,#0369a1 0%,#0ea5e9 45%,#06b6d4 100%);box-shadow:0 24px 55px -24px rgba(14,165,233,.85);">
        <div class="v-hero-z d-flex flex-column flex-lg-row justify-content-between gap-3 align-items-lg-center">
            <div class="d-flex align-items-center gap-3">
                <div class="v-avatar"><i class="bi bi-diagram-3-fill"></i></div>
                <div>
                    <span class="v-chip mb-2"><i class="bi bi-compass"></i> Koordinator Keuangan</span>
                    <h3 class="mb-1">Halo, {{ auth()->user()->name }} 👋</h3>
                    <p>Koordinasikan alur SPP → SPM → NPI → SP2D. Pantau verifikasi & serapan anggaran secara menyeluruh.</p>
                </div>
            </div>
            <div class="text-lg-end">
                <div class="v-bigtask">{{ $kpi['total_tugas'] }}</div>
                <div style="opacity:.9;font-weight:600;">Tugas menunggu Anda</div>
            </div>
        </div>
    </div>

    {{-- ===== KPI funnel per tahap ===== --}}
    <div class="v-kpis">
        <div class="v-kpi k-indigo v-anim v-d1">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="v-lbl mb-1">SPP Menunggu</div>
                    <div class="v-val t-indigo">{{ $kpi['spp_pending'] }}</div>
                    <div class="v-sub">Surat Permintaan Pembayaran</div>
                </div>
                <div class="v-ic bg-indigo"><i class="bi bi-file-earmark-text"></i></div>
            </div>
        </div>
        <div class="v-kpi k-violet v-anim v-d2">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="v-lbl mb-1">SPM Menunggu</div>
                    <div class="v-val t-violet">{{ $kpi['spm_pending'] }}</div>
                    <div class="v-sub">Surat Perintah Membayar</div>
                </div>
                <div class="v-ic bg-violet"><i class="bi bi-file-earmark-check"></i></div>
            </div>
        </div>
        <div class="v-kpi k-amber v-anim v-d3">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="v-lbl mb-1">NPI Menunggu</div>
                    <div class="v-val t-amber">{{ $kpi['npi_pending'] }}</div>
                    <div class="v-sub">Nota Pencairan Internal</div>
                </div>
                <div class="v-ic bg-amber"><i class="bi bi-file-earmark-ruled"></i></div>
            </div>
        </div>
        <div class="v-kpi k-emerald v-anim v-d4">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="v-lbl mb-1">SP2D Menunggu</div>
                    <div class="v-val t-emerald">{{ $kpi['sp2d_pending'] }}</div>
                    <div class="v-sub">Surat Perintah Pencairan</div>
                </div>
                <div class="v-ic bg-emerald"><i class="bi bi-cash-coin"></i></div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        {{-- ===== LEFT: Task queue ===== --}}
        <div class="col-xl-8">
            <div class="v-card v-anim v-d3">
                <div class="v-card-head">
                    <span class="hic" style="background:var(--v-sky);"><i class="bi bi-list-task"></i></span>
                    <div>
                        <h6>Antrian Verifikasi Lintas Dokumen</h6>
                        <small class="text-muted">SPP, SPM, NPI, SP2D & tagihan yang menunggu persetujuan Anda</small>
                    </div>
                    <span class="badge bg-primary rounded-pill ms-auto">{{ $tasks->count() }}</span>
                </div>
                <div class="card-body p-3">
                    @forelse($tasks as $task)
                        <div class="v-task" style="animation-delay: {{ $loop->index * .05 }}s;">
                            <span class="tk-ic tone-{{ $task->tone }}"><i class="bi {{ $task->icon }}"></i></span>
                            <div class="flex-grow-1">
                                <div class="tk-no">{{ $task->nomor }}</div>
                                <div class="tk-meta">
                                    <i class="bi bi-clock me-1"></i>{{ $task->tanggal?->diffForHumans() ?? '-' }}
                                </div>
                            </div>
                            <span class="tk-badge tone-{{ $task->tone }}">{{ $task->jenis }}</span>
                        </div>
                    @empty
                        <div class="v-empty">
                            <i class="bi bi-check2-circle"></i>
                            <div class="fw-semibold">Tidak ada tugas tertunda</div>
                            <div class="small">Seluruh alur dokumen sudah terkoordinasi dengan baik. 🎉</div>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- ===== RIGHT: funnel + serapan ===== --}}
        <div class="col-xl-4">
            <div class="v-card v-anim v-d4 mb-4">
                <div class="v-card-head">
                    <span class="hic" style="background:var(--v-indigo);"><i class="bi bi-funnel-fill"></i></span>
                    <h6>Beban per Tahap</h6>
                </div>
                <div class="card-body">
                    <div class="v-chart-wrap">
                        <canvas id="koorFunnelChart"></canvas>
                    </div>
                </div>
            </div>

            <div class="v-card v-anim v-d5">
                <div class="v-card-head">
                    <span class="hic" style="background:var(--v-emerald);"><i class="bi bi-pie-chart-fill"></i></span>
                    <h6>Serapan Anggaran</h6>
                </div>
                <div class="card-body text-center">
                    <div class="v-ringwrap mb-3">
                        <canvas id="koorSerapanChart"></canvas>
                        <div class="v-chart-center">
                            <div class="v-chart-total" style="font-size:1.4rem;">{{ $kpi['persen_realisasi'] }}%</div>
                            <div class="v-chart-cap">Realisasi</div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between py-2 border-top">
                        <span class="text-muted">Total Pagu</span>
                        <span class="fw-bold">{{ $fmtRp($kpi['total_pagu']) }}</span>
                    </div>
                    <div class="d-flex justify-content-between py-2 border-top">
                        <span class="text-muted">Realisasi</span>
                        <span class="fw-bold text-success">{{ $fmtRp($kpi['total_realisasi']) }}</span>
                    </div>
                    <div class="d-flex justify-content-between py-2 border-top">
                        <span class="text-muted">Sisa Pagu</span>
                        <span class="fw-bold text-primary">{{ $fmtRp($kpi['sisa_pagu']) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== Rekap kinerja + Tren ===== --}}
    <div class="row g-4 mt-1">
        <div class="col-lg-4">
            <div class="v-card v-anim v-d4 h-100">
                <div class="v-card-head">
                    <span class="hic" style="background:var(--v-violet);"><i class="bi bi-award-fill"></i></span>
                    <h6>Rekap Kinerja</h6>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-muted"><i class="bi bi-check-circle-fill text-success me-1"></i>Sudah Disetujui</span>
                        <span class="fw-bold">{{ $kpi['sudah_disetujui'] }}</span>
                    </div>
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-muted"><i class="bi bi-arrow-counterclockwise text-danger me-1"></i>Dikembalikan/Revisi</span>
                        <span class="fw-bold">{{ $kpi['di_revisi'] }}</span>
                    </div>
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-muted"><i class="bi bi-receipt text-danger me-1"></i>Tagihan Menunggu</span>
                        <span class="fw-bold">{{ $kpi['tagihan_pending'] }}</span>
                    </div>
                    <div class="d-flex justify-content-between py-2">
                        <span class="text-muted"><i class="bi bi-bar-chart text-primary me-1"></i>Realisasi</span>
                        <span class="fw-bold text-primary">{{ $kpi['persen_realisasi'] }}%</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="v-card v-anim v-d5 h-100">
                <div class="v-card-head">
                    <span class="hic" style="background:var(--v-sky);"><i class="bi bi-graph-up-arrow"></i></span>
                    <div>
                        <h6>Tren Persetujuan 6 Bulan Terakhir</h6>
                        <small class="text-muted">Dokumen yang Anda setujui per bulan</small>
                    </div>
                </div>
                <div class="card-body">
                    <canvas id="koorTrenChart" height="110"></canvas>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== Quick access ===== --}}
    <div class="row g-3 mt-1">
        <div class="col-md-3 col-sm-6">
            <a href="{{ route('verifikasi-koordinator.spp.index') }}" class="v-quick v-anim v-d3">
                <span class="q-ic bg-indigo"><i class="bi bi-file-earmark-text"></i></span>
                <div>
                    <div class="q-title">Verifikasi SPP</div>
                    <div class="q-sub">Kontrak</div>
                </div>
                <i class="bi bi-arrow-right q-arrow"></i>
            </a>
        </div>
        <div class="col-md-3 col-sm-6">
            <a href="{{ route('verifikasi-koordinator.spm.kontrak.index') }}" class="v-quick v-anim v-d4">
                <span class="q-ic bg-violet"><i class="bi bi-file-earmark-check"></i></span>
                <div>
                    <div class="q-title">Verifikasi SPM</div>
                    <div class="q-sub">Kontrak</div>
                </div>
                <i class="bi bi-arrow-right q-arrow"></i>
            </a>
        </div>
        <div class="col-md-3 col-sm-6">
            <a href="{{ route('verifikasi-koordinator.npi.kontrak.index') }}" class="v-quick v-anim v-d5">
                <span class="q-ic bg-amber"><i class="bi bi-file-earmark-ruled"></i></span>
                <div>
                    <div class="q-title">Verifikasi NPI</div>
                    <div class="q-sub">Kontrak</div>
                </div>
                <i class="bi bi-arrow-right q-arrow"></i>
            </a>
        </div>
        <div class="col-md-3 col-sm-6">
            <a href="{{ route('verifikasi-koordinator.sp2d.kontrak.index') }}" class="v-quick v-anim v-d6">
                <span class="q-ic bg-emerald"><i class="bi bi-cash-coin"></i></span>
                <div>
                    <div class="q-title">Verifikasi SP2D</div>
                    <div class="q-sub">Kontrak</div>
                </div>
                <i class="bi bi-arrow-right q-arrow"></i>
            </a>
        </div>
    </div>
</div>
@endsection

@push('script')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Funnel bar
    const funnelEl = document.getElementById('koorFunnelChart');
    if (funnelEl) {
        new Chart(funnelEl, {
            type: 'bar',
            data: {
                labels: @json($chart_funnel_labels),
                datasets: [{
                    label: 'Menunggu',
                    data: @json($chart_funnel_data),
                    backgroundColor: ['#6366f1', '#8b5cf6', '#f59e0b', '#10b981'],
                    borderRadius: 8,
                    maxBarThickness: 46
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { x: { beginAtZero: true, ticks: { precision: 0 } } }
            }
        });
    }

    // Serapan doughnut
    const serEl = document.getElementById('koorSerapanChart');
    if (serEl) {
        new Chart(serEl, {
            type: 'doughnut',
            data: {
                labels: ['Realisasi', 'Sisa Pagu'],
                datasets: [{
                    data: [{{ (float) $kpi['total_realisasi'] }}, {{ (float) $kpi['sisa_pagu'] }}],
                    backgroundColor: ['#10b981', '#e2e8f0'],
                    borderWidth: 0,
                    hoverOffset: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '74%',
                plugins: { legend: { display: false } },
                animation: { animateScale: true }
            }
        });
    }

    // Tren line
    const trenEl = document.getElementById('koorTrenChart');
    if (trenEl) {
        const ctx = trenEl.getContext('2d');
        const grad = ctx.createLinearGradient(0, 0, 0, 260);
        grad.addColorStop(0, 'rgba(14,165,233,.35)');
        grad.addColorStop(1, 'rgba(14,165,233,0)');
        new Chart(trenEl, {
            type: 'line',
            data: {
                labels: @json($tren_labels),
                datasets: [{
                    label: 'Disetujui',
                    data: @json($tren_approve),
                    borderColor: '#0ea5e9',
                    backgroundColor: grad,
                    fill: true,
                    tension: .4,
                    pointRadius: 4,
                    pointBackgroundColor: '#0ea5e9',
                    pointHoverRadius: 6
                }]
            },
            options: {
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
            }
        });
    }
});
</script>
@endpush
