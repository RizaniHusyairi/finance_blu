@extends('layouts.app')
@section('title', 'Dashboard PPSPM')

@include('dashboard.partials.verifikator-dashboard-styles')

@php
    $fmtRp = fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.');
@endphp

@section('content')
<div class="vdash">
    <x-page-title title="Dashboard PPSPM" subtitle="Pejabat Penanda Tangan Surat Perintah Membayar" />

    {{-- ===== HERO ===== --}}
    <div class="v-hero" style="background:linear-gradient(125deg,#4338ca 0%,#6366f1 45%,#8b5cf6 100%);">
        <div class="v-hero-z d-flex flex-column flex-lg-row justify-content-between gap-3 align-items-lg-center">
            <div class="d-flex align-items-center gap-3">
                <div class="v-avatar"><i class="bi bi-pen-fill"></i></div>
                <div>
                    <span class="v-chip mb-2"><i class="bi bi-shield-check"></i> PPSPM</span>
                    <h3 class="mb-1">Halo, {{ auth()->user()->name }} 👋</h3>
                    <p>Uji & tanda tangani SPM, terbitkan SP2D. Pastikan setiap dokumen pembayaran sah dan tepat.</p>
                </div>
            </div>
            <div class="text-lg-end">
                <div class="v-bigtask">{{ $kpi['total_tugas'] }}</div>
                <div style="opacity:.9;font-weight:600;">Tugas menunggu Anda</div>
            </div>
        </div>
    </div>

    {{-- ===== KPI ===== --}}
    <div class="v-kpis">
        <div class="v-kpi k-violet v-anim v-d1">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="v-lbl mb-1">SPM Menunggu</div>
                    <div class="v-val t-violet">{{ $kpi['spm_pending'] }}</div>
                    <div class="v-sub">Perlu diuji & ditandatangani</div>
                </div>
                <div class="v-ic bg-violet"><i class="bi bi-file-earmark-check"></i></div>
            </div>
        </div>
        <div class="v-kpi k-emerald v-anim v-d2">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="v-lbl mb-1">SP2D Menunggu</div>
                    <div class="v-val t-emerald">{{ $kpi['sp2d_pending'] }}</div>
                    <div class="v-sub">Verifikasi pencairan</div>
                </div>
                <div class="v-ic bg-emerald"><i class="bi bi-cash-coin"></i></div>
            </div>
        </div>
        <div class="v-kpi k-rose v-anim v-d3">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="v-lbl mb-1">Tagihan Menunggu</div>
                    <div class="v-val t-rose">{{ $kpi['tagihan_pending'] }}</div>
                    <div class="v-sub">Verifikasi tagihan</div>
                </div>
                <div class="v-ic bg-rose"><i class="bi bi-receipt"></i></div>
            </div>
        </div>
        <div class="v-kpi k-amber v-anim v-d4">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="v-lbl mb-1">Nilai SPM Antri</div>
                    <div class="v-val t-amber" style="font-size:1.35rem;">{{ $fmtRp($kpi['nominal_spm_pending']) }}</div>
                    <div class="v-sub">Total nominal menunggu TTD</div>
                </div>
                <div class="v-ic bg-amber"><i class="bi bi-coin"></i></div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        {{-- ===== LEFT: Task queue ===== --}}
        <div class="col-xl-8">
            <div class="v-card v-anim v-d3">
                <div class="v-card-head">
                    <span class="hic" style="background:var(--v-violet);"><i class="bi bi-list-task"></i></span>
                    <div>
                        <h6>Antrian Tugas Verifikasi</h6>
                        <small class="text-muted">Dokumen yang menunggu tanda tangan / persetujuan Anda</small>
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
                            <div class="small">Semua dokumen sudah Anda proses. Kerja bagus! 🎉</div>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- ===== RIGHT: charts & stats ===== --}}
        <div class="col-xl-4">
            <div class="v-card v-anim v-d4 mb-4">
                <div class="v-card-head">
                    <span class="hic" style="background:var(--v-indigo);"><i class="bi bi-pie-chart-fill"></i></span>
                    <h6>Komposisi Tugas</h6>
                </div>
                <div class="card-body">
                    <div class="v-chart-wrap">
                        <canvas id="ppspmTugasChart"></canvas>
                        <div class="v-chart-center">
                            <div class="v-chart-total">{{ $kpi['total_tugas'] }}</div>
                            <div class="v-chart-cap">Total Tugas</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="v-card v-anim v-d5">
                <div class="v-card-head">
                    <span class="hic" style="background:var(--v-emerald);"><i class="bi bi-award-fill"></i></span>
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
                        <span class="text-muted"><i class="bi bi-file-earmark-check text-primary me-1"></i>SPM Terbit</span>
                        <span class="fw-bold">{{ $kpi['spm_terbit'] }}</span>
                    </div>
                    <div class="d-flex justify-content-between py-2">
                        <span class="text-muted"><i class="bi bi-cash-coin text-success me-1"></i>SP2D Terbit</span>
                        <span class="fw-bold">{{ $kpi['sp2d_terbit'] }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== Tren approval ===== --}}
    <div class="v-card v-anim v-d5 mt-4">
        <div class="v-card-head">
            <span class="hic" style="background:var(--v-sky);"><i class="bi bi-graph-up-arrow"></i></span>
            <div>
                <h6>Tren Persetujuan 6 Bulan Terakhir</h6>
                <small class="text-muted">Jumlah SPM/SP2D/Tagihan yang Anda setujui per bulan</small>
            </div>
        </div>
        <div class="card-body">
            <canvas id="ppspmTrenChart" height="90"></canvas>
        </div>
    </div>

    {{-- ===== Quick access ===== --}}
    <div class="row g-3 mt-1">
        <div class="col-md-4">
            <a href="{{ route('verifikasi-ppspm.spm.kontrak.index') }}" class="v-quick v-anim v-d4">
                <span class="q-ic bg-violet"><i class="bi bi-file-earmark-check"></i></span>
                <div>
                    <div class="q-title">Verifikasi SPM Kontrak</div>
                    <div class="q-sub">Uji & tanda tangani SPM kontrak</div>
                </div>
                <i class="bi bi-arrow-right q-arrow"></i>
            </a>
        </div>
        <div class="col-md-4">
            <a href="{{ route('verifikasi-spm.honor.index') }}" class="v-quick v-anim v-d5">
                <span class="q-ic bg-amber"><i class="bi bi-people-fill"></i></span>
                <div>
                    <div class="q-title">Verifikasi SPM Honorarium</div>
                    <div class="q-sub">Periksa SPM honorarium</div>
                </div>
                <i class="bi bi-arrow-right q-arrow"></i>
            </a>
        </div>
        <div class="col-md-4">
            <a href="{{ route('verifikasi-sp2d.honor.index') }}" class="v-quick v-anim v-d6">
                <span class="q-ic bg-emerald"><i class="bi bi-cash-coin"></i></span>
                <div>
                    <div class="q-title">Verifikasi SP2D</div>
                    <div class="q-sub">Honor & perjalanan dinas</div>
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
    const tugasEl = document.getElementById('ppspmTugasChart');
    if (tugasEl) {
        new Chart(tugasEl, {
            type: 'doughnut',
            data: {
                labels: @json($chart_tugas_labels),
                datasets: [{
                    data: @json($chart_tugas_data),
                    backgroundColor: ['#8b5cf6', '#10b981', '#f43f5e'],
                    borderWidth: 0,
                    hoverOffset: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, padding: 16 } } },
                animation: { animateScale: true, animateRotate: true }
            }
        });
    }

    const trenEl = document.getElementById('ppspmTrenChart');
    if (trenEl) {
        const ctx = trenEl.getContext('2d');
        const grad = ctx.createLinearGradient(0, 0, 0, 240);
        grad.addColorStop(0, 'rgba(99,102,241,.35)');
        grad.addColorStop(1, 'rgba(99,102,241,0)');
        new Chart(trenEl, {
            type: 'line',
            data: {
                labels: @json($tren_labels),
                datasets: [{
                    label: 'Disetujui',
                    data: @json($tren_approve),
                    borderColor: '#6366f1',
                    backgroundColor: grad,
                    fill: true,
                    tension: .4,
                    pointRadius: 4,
                    pointBackgroundColor: '#6366f1',
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
