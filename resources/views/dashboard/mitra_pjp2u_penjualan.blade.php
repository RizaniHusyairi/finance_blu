@extends('layouts.app')
@section('title', 'Riwayat PAX PJP2U')

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
            <span class="mp-hero-icon"><i class="bi bi-people fs-4"></i></span>
            <div>
                <h4 class="mb-1 fw-bold text-white">Riwayat PAX PJP2U</h4>
                <p class="mb-0 small fw-semibold text-white-50">Daftar laporan penumpang yang telah Anda ajukan untuk ditagihkan.</p>
            </div>
        </div>
        <a href="{{ route('mitra.pax.create') }}" class="btn btn-light fw-bold text-primary shadow-sm">
            <i class="bi bi-plus-circle me-1"></i>Input Laporan PAX
        </a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<div class="mp-card mb-4">
    <div class="mp-card-header">
        <div class="mp-card-title">
            <span class="mp-card-icon"><i class="bi bi-table"></i></span>
            <div>
                <h6>Riwayat Laporan PAX PJP2U</h6>
                <small>Laporan penumpang yang menjadi dasar tagihan PJP2U.</small>
            </div>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table mp-table mb-0">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Layanan</th>
                    <th>Periode</th>
                    <th>Total Pax</th>
                    <th>Tarif Dasar</th>
                    <th>Nilai Tagihan</th>
                    <th>Status</th>
                    <th>File</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($penjualans as $penjualan)
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
                        if ($grandTotalPax === 0) {
                            $grandTotalPax = (int) $penjualan->total_omzet;
                            $billablePax = (int) $penjualan->total_omzet;
                        }
                        $tarifDasar = (float) ($penjualan->layananJasa->tarif_dasar ?? 0);
                        $nilaiTagihanRecalc = $billablePax * $tarifDasar;
                    @endphp
                    <tr>
                        <td>{{ $penjualans->firstItem() + $loop->index }}</td>
                        <td>
                            <a href="{{ route('mitra.penjualan.show', $penjualan) }}" class="text-decoration-none fw-semibold">
                                {{ $penjualan->layananJasa->nama_layanan ?? '-' }}
                            </a>
                        </td>
                        <td>{{ $tanggal($penjualan->periode_mulai) }} s.d. {{ $tanggal($penjualan->periode_selesai) }}</td>
                        <td class="fw-bold">
                            {{ number_format($grandTotalPax, 0, ',', '.') }} Pax
                            @if($grandTotalPax !== $billablePax)
                                <div class="small text-muted fw-normal">{{ number_format($billablePax, 0, ',', '.') }} kena tagihan</div>
                            @endif
                        </td>
                        <td>{{ $rupiah($tarifDasar) }}</td>
                        <td class="fw-bold text-success">{{ $rupiah($nilaiTagihanRecalc) }}</td>
                        <td><span class="badge rounded-pill {{ $statusClass($penjualan->status) }} px-3 py-2 fw-medium">{{ ucfirst($penjualan->status) }}</span></td>
                        <td>
                            @if($penjualan->file_laporan)
                                <a href="{{ asset('storage/' . $penjualan->file_laporan) }}" target="_blank" class="btn btn-sm btn-light border">Lihat</a>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td>
                            <div class="d-flex gap-1 align-items-center">
                                <a href="{{ route('mitra.penjualan.show', $penjualan) }}" class="btn btn-sm btn-primary mp-action" title="Detail"><i class="bi bi-eye"></i></a>
                                @if(!in_array($penjualan->status, ['diverifikasi', 'ditagihkan']))
                                    <form action="{{ route('mitra.penjualan.destroy', $penjualan) }}" method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus laporan ini?');">
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
                            <td colspan="8" class="small text-danger">Catatan verifikator: {{ $penjualan->catatan_verifikator }}</td>
                        </tr>
                    @endif
                @empty
                    <tr>
                        <td colspan="9" class="text-center mp-empty py-4">
                            <span class="mp-empty-icon"><i class="bi bi-folder2-open"></i></span>
                            <div class="fw-bold">Belum ada laporan PAX PJP2U</div>
                            <div class="small">Gunakan tombol Input Laporan PAX untuk menambahkan laporan.</div>
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

@if($tagihanPjp2u->isNotEmpty())
    <div class="mp-card">
        <div class="mp-card-header">
            <div class="mp-card-title">
                <span class="mp-card-icon"><i class="bi bi-receipt-cutoff"></i></span>
                <div>
                    <h6>Riwayat Tagihan PJP2U</h6>
                    <small>Tagihan yang terbentuk dari laporan penumpang.</small>
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
                    @foreach($tagihanPjp2u as $item)
                        @php($tagihan = $item->tagihanJasa)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $tagihan->nomor_tagihan ?? '-' }}</td>
                            <td>{{ $item->layananJasa->nama_layanan ?? '-' }}</td>
                            <td>{{ $tanggal($item->periode_mulai) }} s.d. {{ $tanggal($item->periode_selesai) }}</td>
                            <td class="fw-bold text-success">{{ $rupiah($tagihan->total_tagihan ?? $item->nilai_tagihan) }}</td>
                            <td><span class="badge bg-primary">{{ $tagihan->status ?? $item->status }}</span></td>
                            <td>{{ $tanggal($tagihan?->tanggal_jatuh_tempo) }}</td>
                                <td>
                                    @if($tagihan)
                                        <a href="{{ route('mitra.tagihan-jasa.show', $tagihan) }}" class="btn btn-sm btn-primary mp-action" title="Detail Tagihan">
                                            <i class="bi bi-receipt me-1"></i>Tagihan
                                        </a>
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
