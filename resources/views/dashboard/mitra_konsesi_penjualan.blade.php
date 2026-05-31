@extends('layouts.app')
@section('title', 'Konsesi & Penjualan')

@push('css')
    @include('dashboard.partials.mitra-ui')
@endpush

@section('content')
@php
    $rupiah = fn ($value) => 'Rp ' . number_format((float) $value, 0, ',', '.');
    $tanggal = fn ($value) => $value ? \Carbon\Carbon::parse($value)->format('d/m/Y') : '-';
    $persen = fn ($value) => $value !== null ? rtrim(rtrim(number_format((float) $value, 4, ',', '.'), '0'), ',') . '%' : '-';
    $statusClass = fn ($status) => match ($status) {
        'diajukan' => 'bg-warning text-dark',
        'diverifikasi' => 'bg-success',
        'ditolak' => 'bg-danger',
        'ditagihkan' => 'bg-primary',
        default => 'bg-secondary',
    };
@endphp

<div class="mp-hero mb-4">
    <div class="d-flex flex-column flex-lg-row align-items-start align-items-lg-center justify-content-between gap-3">
        <div class="d-flex align-items-start gap-3">
            <span class="mp-hero-icon"><i class="bi bi-graph-up-arrow fs-4"></i></span>
            <div>
                <h4 class="mb-1 fw-bold text-white">Laporan Konsesi</h4>
                <p class="mb-0 small fw-semibold text-white-50">{{ $mitra->nama_mitra }}</p>
            </div>
        </div>
        <a href="{{ route('mitra.penjualan.create') }}" class="btn btn-light fw-bold text-primary shadow-sm">
            <i class="bi bi-plus-lg me-1"></i>Input Laporan
        </a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<div class="alert alert-info border-0">
    Pilih layanan konsesi saat input laporan, isi omzet dan dokumen pendukung. Sistem menghitung nilai tagihan dari persentase layanan yang dipilih.
</div>

<div class="mp-card mb-4">
    <div class="mp-card-header">
        <div class="mp-card-title">
            <span class="mp-card-icon"><i class="bi bi-table"></i></span>
            <div>
                <h6>Riwayat Laporan Pendapatan/Penjualan</h6>
                <small>Laporan omzet yang sudah dibuat dan diajukan.</small>
            </div>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table mp-table mb-0">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Layanan</th>
                    <th>Bulan</th>
                    <th>Laporan</th>
                    <th>Total Omzet</th>
                    <th>Nilai Tagihan</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($penjualans as $penjualan)
                    <tr>
                        <td>{{ $penjualans->firstItem() + $loop->index }}</td>
                        <td>
                            <a href="{{ route('mitra.penjualan.show', $penjualan) }}" class="text-decoration-none fw-semibold">
                                {{ $penjualan->layananJasa->nama_layanan ?? '-' }}
                            </a>
                        </td>
                        <td>{{ str_pad($penjualan->bulan, 2, '0', STR_PAD_LEFT) }}/{{ $penjualan->tahun }}</td>
                        <td>
                            <span class="badge bg-info text-dark">{{ $penjualan->details_count ?? $penjualan->details()->count() }} laporan</span>
                        </td>
                        <td>{{ $rupiah($penjualan->total_omzet) }}</td>
                        <td class="fw-bold text-success">{{ $rupiah($penjualan->nilai_tagihan) }}</td>
                        <td><span class="mp-soft-badge info">{{ ucfirst($penjualan->status) }}</span></td>
                        <td>
                            <div class="d-flex gap-1 align-items-center">
                                <a href="{{ route('mitra.penjualan.show', $penjualan) }}" class="btn btn-sm btn-primary mp-action" title="Detail"><i class="bi bi-eye"></i></a>
                                @if($penjualan->status === 'draft')
                                    <form action="{{ route('mitra.penjualan.submit', $penjualan) }}" method="POST" class="d-inline" onsubmit="return confirm('Ajukan laporan bulan ini untuk verifikasi?');">
                                        @csrf
                                        <button class="btn btn-sm btn-warning mp-action" title="Ajukan"><i class="bi bi-send"></i></button>
                                    </form>
                                @endif
                                @if(!in_array($penjualan->status, ['diverifikasi', 'ditagihkan']))
                                    <form action="{{ route('mitra.penjualan.destroy', $penjualan) }}" method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus semua laporan bulan ini?');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-danger mp-action" title="Hapus"><i class="bi bi-trash"></i></button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @if($penjualan->catatan_verifikator)
                        <tr>
                            <td></td>
                            <td colspan="7" class="small text-danger">Catatan verifikator: {{ $penjualan->catatan_verifikator }}</td>
                        </tr>
                    @endif
                @empty
                    <tr>
                        <td colspan="8" class="text-center mp-empty py-4">
                            <span class="mp-empty-icon"><i class="bi bi-folder2-open"></i></span>
                            <div class="fw-bold">Belum ada laporan pendapatan/penjualan</div>
                            <div class="small">Gunakan tombol Input Laporan untuk menambahkan laporan baru.</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($penjualans->hasPages())
    <div class="card-footer bg-white">{{ $penjualans->links() }}</div>
    @endif
</div>

@if($tagihanKonsesi->isNotEmpty())
    <div class="mp-card">
        <div class="mp-card-header">
            <div class="mp-card-title">
                <span class="mp-card-icon"><i class="bi bi-receipt-cutoff"></i></span>
                <div>
                    <h6>Riwayat Tagihan Konsesi</h6>
                    <small>Tagihan yang terbentuk dari laporan konsesi.</small>
                </div>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table mp-table mb-0">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>No Tagihan</th>
                        <th>Layanan</th>
                        <th>Periode Laporan</th>
                        <th>Total Tagihan</th>
                        <th>Status</th>
                        <th>Jatuh Tempo</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($tagihanKonsesi as $item)
                        @php($tagihan = $item->tagihanJasa)
                        @php($tagihanBisaDibuka = $tagihan && in_array($tagihan->status, ['PUBLISHED', 'LUNAS'], true))
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $tagihan->nomor_tagihan ?? '-' }}</td>
                            <td>{{ $item->layananJasa->nama_layanan ?? '-' }}</td>
                            <td>{{ $tanggal($item->periode_mulai) }} s.d. {{ $tanggal($item->periode_selesai) }}</td>
                            <td class="fw-bold text-success">{{ $rupiah($tagihan->total_tagihan ?? $item->nilai_tagihan) }}</td>
                            <td><span class="badge bg-primary">{{ $tagihan->status ?? $item->status }}</span></td>
                            <td>{{ $tanggal($tagihan?->tanggal_jatuh_tempo) }}</td>
                                <td>
                                    @if($tagihanBisaDibuka)
                                        <a href="{{ route('mitra.tagihan-jasa.show', $tagihan) }}" class="btn btn-sm btn-primary mp-action" title="Detail Tagihan">
                                            <i class="bi bi-receipt me-1"></i>Tagihan
                                        </a>
                                    @elseif($tagihan)
                                        <span class="badge bg-warning text-dark">Diproses</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif
@endsection
