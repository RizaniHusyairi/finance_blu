@extends('layouts.app')
@section('title', 'Rekap Performa Pembayaran Mitra')

@section('content')
@include('super_admin_jasa.laporan._styles')
@php
    $rupiah = fn ($v) => 'Rp ' . number_format((float) $v, 0, ',', '.');

    $statusConfig = [
        'BARU' => ['label' => 'Baru', 'class' => 'bg-secondary', 'desc' => 'Belum punya riwayat tagihan'],
        'LANCAR' => ['label' => 'Lancar', 'class' => 'bg-success', 'desc' => 'Tidak ada keterlambatan'],
        'CUKUP_LANCAR' => ['label' => 'Cukup Lancar', 'class' => 'bg-info text-dark', 'desc' => 'Telat ≤ 7 hari'],
        'PERLU_PERHATIAN' => ['label' => 'Perlu Perhatian', 'class' => 'bg-warning text-dark', 'desc' => 'Telat 8–30 hari'],
        'MACET' => ['label' => 'Macet', 'class' => 'bg-danger', 'desc' => 'Telat > 30 hari'],
    ];
@endphp

<div class="sa-report-page">
<div class="sa-report-hero d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-graph-up-arrow me-2 text-primary"></i>Rekap Performa Pembayaran Mitra</h4>
        <p class="text-muted mb-0 small">Klasifikasi mitra berdasarkan ketepatan pembayaran tagihan jasa.</p>
    </div>
</div>

@include('super_admin_jasa.laporan._filters', [
    'filters' => $filters,
    'filterOptions' => $filterOptions,
    'showMonth' => false,
    'showTipePnbp' => true,
    'showMitra' => true,
    'showStatusPerforma' => true,
    'extraNotes' => 'Klasifikasi: Baru (belum bertagihan), Lancar (selalu tepat waktu), Cukup Lancar (telat ≤7 hari), Perlu Perhatian (telat 8–30 hari), Macet (telat >30 hari).',
])

{{-- Ringkasan status --}}
<div class="row g-3 mb-4">
    @foreach($statusConfig as $key => $cfg)
        <div class="col">
            <div class="card border-0 shadow-sm h-100" style="border-radius:1rem;">
                <div class="card-body">
                    <span class="badge {{ $cfg['class'] }} mb-2">{{ $cfg['label'] }}</span>
                    <div class="fs-2 fw-bold text-dark">{{ $statusCount[$key] ?? 0 }}</div>
                    <div class="small text-muted">{{ $cfg['desc'] }}</div>
                </div>
            </div>
        </div>
    @endforeach
</div>

{{-- Daftar Mitra --}}
<div class="card border-0 shadow-sm" style="border-radius:1rem;">
    <div class="card-header bg-white border-0 pt-3">
        <h6 class="fw-bold mb-0"><i class="bi bi-people me-2 text-primary"></i>Daftar Mitra & Status Performa</h6>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="small text-uppercase">No</th>
                    <th class="small text-uppercase">Mitra</th>
                    <th class="small text-uppercase text-center">Status</th>
                    <th class="small text-uppercase text-center">Jml Tagihan</th>
                    <th class="small text-uppercase text-center">Lunas</th>
                    <th class="small text-uppercase text-center">Outstanding</th>
                    <th class="small text-uppercase text-end">Sisa Piutang</th>
                    <th class="small text-uppercase text-center">Telat Saat Ini</th>
                    <th class="small text-uppercase text-center">Rata-rata Telat (Lunas)</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $row)
                    @php $cfg = $statusConfig[$row->status_performa] ?? ['label' => $row->status_performa, 'class' => 'bg-secondary']; @endphp
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td class="fw-bold">{{ $row->nama_mitra }}</td>
                        <td class="text-center"><span class="badge {{ $cfg['class'] }}">{{ $cfg['label'] }}</span></td>
                        <td class="text-center">{{ $row->jumlah_tagihan }}</td>
                        <td class="text-center"><span class="text-success fw-bold">{{ $row->jumlah_lunas }}</span></td>
                        <td class="text-center">
                            @if($row->jumlah_outstanding > 0)
                                <span class="text-danger fw-bold">{{ $row->jumlah_outstanding }}</span>
                            @else
                                <span class="text-muted">0</span>
                            @endif
                        </td>
                        <td class="text-end fw-bold {{ $row->sisa_outstanding > 0 ? 'text-danger' : 'text-muted' }}">
                            {{ $rupiah($row->sisa_outstanding) }}
                        </td>
                        <td class="text-center">
                            @if($row->outstanding_max_overdue > 30)
                                <span class="badge bg-danger">+{{ $row->outstanding_max_overdue }} hari</span>
                            @elseif($row->outstanding_max_overdue > 7)
                                <span class="badge bg-warning text-dark">+{{ $row->outstanding_max_overdue }} hari</span>
                            @elseif($row->outstanding_max_overdue > 0)
                                <span class="badge bg-info text-dark">+{{ $row->outstanding_max_overdue }} hari</span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @if($row->jumlah_lunas > 0)
                                {{ $row->rata_late }} hari
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="text-center text-muted py-4"><i class="bi bi-inbox me-1"></i>Tidak ada mitra yang cocok dengan filter.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
</div>
@endsection
