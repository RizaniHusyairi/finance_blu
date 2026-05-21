@extends('layouts.app')
@section('title', 'Mitra Jasa Terkait')

@section('content')
@php
    $rupiah = fn ($value) => 'Rp ' . number_format((float) $value, 0, ',', '.');
@endphp

<style>
    .aj-page-head { background: linear-gradient(110deg, #0f2f57, #165d9f); color: #fff; border-radius: 14px; padding: 22px 24px; box-shadow: 0 12px 30px rgba(15,47,87,.18); }
    .aj-page-head h1, .aj-page-head h2, .aj-page-head h3, .aj-page-head h4, .aj-page-head p { color: #fff !important; }
    .aj-page-head p { opacity: .86; }
    .aj-card { border: 0; border-radius: 14px; box-shadow: 0 10px 26px rgba(15,23,42,.08); }
    .aj-table th { font-size: 12px; text-transform: uppercase; color: #64748b; white-space: nowrap; }
    .aj-table td { vertical-align: middle; }
    .soft-table-header { display:flex; align-items:center; gap:10px; padding:10px 14px; border-bottom:1px solid #bfdbfe; background:linear-gradient(90deg,#eff6ff 0%,#f8fbff 58%,#fff 100%); }
    .soft-table-icon { display:inline-flex; align-items:center; justify-content:center; width:34px; height:34px; border-radius:9px; color:#fff; background:#1d4ed8; box-shadow:0 10px 20px rgba(37,99,235,.18); }
    .soft-table-title { margin:0; color:#1e3a8a; font-weight:800; }
    .soft-table-subtitle { color:#64748b; font-size:12px; font-weight:700; }
</style>

<div class="aj-page-head mb-4">
    <h3 class="fw-bold mb-1">Mitra Jasa Terkait</h3>
    <p class="mb-0 opacity-75">Mitra, laporan penjualan, dan tagihan sesuai scope layanan Admin Jasa.</p>
</div>

<div class="card aj-card mb-4">
    <div class="soft-table-header">
        <span class="soft-table-icon"><i class="bi bi-funnel"></i></span>
        <div>
            <div class="soft-table-title">Filter Mitra</div>
            <div class="soft-table-subtitle">Saring mitra berdasarkan periode laporan dan tagihan.</div>
        </div>
    </div>
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-2">
                <label class="form-label small fw-bold">Bulan</label>
                <select name="month" class="form-select form-select-sm">
                    @for($m = 1; $m <= 12; $m++)
                        <option value="{{ $m }}" {{ (int)($filters['month'] ?? now()->month) === $m ? 'selected' : '' }}>{{ \Carbon\Carbon::create()->month($m)->translatedFormat('F') }}</option>
                    @endfor
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-bold">Tahun</label>
                <input type="number" name="year" value="{{ $filters['year'] ?? now()->year }}" class="form-control form-control-sm">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-bold">Dari Tanggal</label>
                <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="form-control form-control-sm">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-bold">Sampai Tanggal</label>
                <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="form-control form-control-sm">
            </div>
            <div class="col-md-2 d-flex align-items-end gap-2">
                <button class="btn btn-primary btn-sm fw-bold">Filter</button>
                <a href="{{ route('admin-jasa.mitra') }}" class="btn btn-light border btn-sm fw-bold">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card aj-card overflow-hidden">
    <div class="soft-table-header">
        <span class="soft-table-icon"><i class="bi bi-building"></i></span>
        <div>
            <div class="soft-table-title">Daftar Mitra Jasa</div>
            <div class="soft-table-subtitle">Mitra yang terkait dengan scope layanan Admin Jasa.</div>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table aj-table mb-0">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Kode</th>
                    <th>Nama Mitra</th>
                    <th>Kontak</th>
                    <th>PJ</th>
                    <th>Status</th>
                    <th>Laporan</th>
                    <th>Total Tagihan</th>
                    <th>Belum Lunas</th>
                    <th>Nominal</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($mitras as $mitra)
                    <tr>
                        <td>{{ $mitras->firstItem() + $loop->index }}</td>
                        <td class="fw-semibold">{{ $mitra->kode_mitra ?? '-' }}</td>
                        <td>{{ $mitra->nama_mitra }}</td>
                        <td>
                            <div>{{ $mitra->email ?? '-' }}</div>
                            <small class="text-muted">{{ $mitra->no_telepon ?? '-' }}</small>
                        </td>
                        <td>
                            <div>{{ $mitra->nama_penanggung_jawab ?? '-' }}</div>
                            <small class="text-muted">{{ $mitra->jabatan_penanggung_jawab ?? '-' }}</small>
                        </td>
                        <td><span class="badge {{ $mitra->status_aktif ? 'bg-success' : 'bg-secondary' }}">{{ $mitra->status_aktif ? 'Aktif' : 'Nonaktif' }}</span></td>
                        <td>
                            <div>{{ number_format($mitra->laporan_penjualan_count, 0, ',', '.') }} laporan</div>
                            @if($mitra->laporan_menunggu_count > 0)
                                <small class="badge bg-warning text-dark">{{ $mitra->laporan_menunggu_count }} menunggu</small>
                            @endif
                        </td>
                        <td>{{ number_format($mitra->total_tagihan_jasa, 0, ',', '.') }}</td>
                        <td>{{ number_format($mitra->total_belum_lunas, 0, ',', '.') }}</td>
                        <td>{{ $rupiah($mitra->nominal_tagihan_jasa ?? 0) }}</td>
                        <td>
                            <div class="d-flex gap-1 flex-wrap">
                                <a href="{{ route('jasa.mitra.show', $mitra->id) }}" class="btn btn-sm btn-light border">Detail</a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="11" class="text-center text-muted py-4">Belum ada mitra terkait untuk scope layanan Admin Jasa.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($mitras->hasPages())
        <div class="card-footer bg-white">
            {{ $mitras->links() }}
        </div>
    @endif
</div>
@endsection
