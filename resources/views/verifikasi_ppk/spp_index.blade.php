@extends('layouts.app')
@section('title') Verifikasi SPP @endsection

@section('content')
<x-page-title title="Verifikasi Dokumen" subtitle="Daftar SPP Menunggu Verifikasi PPK" />

@php
    $menungguCount = $spps->filter(fn($spp) => $spp->status_spp === 'Menunggu Verifikasi')->count();
    $disetujuiCount = $spps->filter(fn($spp) => !in_array($spp->status_spp, ['Menunggu Verifikasi', 'Revisi'], true))->count();
    $revisiCount = $spps->filter(fn($spp) => $spp->status_spp === 'Revisi')->count();
    $totalCount = $spps->count();
@endphp

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif
@if(session('warning'))
<div class="alert alert-warning alert-dismissible fade show" role="alert">
    {{ session('warning') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif
<style>
    .stat-card {
        background: #fff;
        border: 1px solid #e9ecef;
        border-left: 4px solid var(--accent, #6c757d);
        border-radius: .5rem;
        transition: transform .15s ease, box-shadow .15s ease;
    }
    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 .5rem 1rem rgba(0,0,0,.075) !important;
    }
    .stat-card .stat-icon {
        width: 36px; height: 36px;
        display: inline-flex; align-items: center; justify-content: center;
        border-radius: .5rem;
        background: var(--accent-bg, rgba(108,117,125,.1));
        color: var(--accent, #6c757d);
        font-size: 1.1rem;
    }
    .stat-card .stat-label { font-size: .8rem; color: #6c757d; text-transform: uppercase; letter-spacing: .03em; font-weight: 600; }
    .stat-card .stat-value { font-size: 1.85rem; font-weight: 700; line-height: 1.1; color: #212529; }
    .stat-card .stat-sub   { font-size: .75rem; color: #adb5bd; }
</style>

<div class="row row-cols-1 row-cols-md-2 row-cols-xl-4 g-3 mb-4 mt-2">
    <div class="col">
        <div class="card stat-card h-100 shadow-sm" style="--accent: #f59f00; --accent-bg: rgba(245,159,0,.12);">
            <div class="card-body p-3">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="stat-icon"><i class="bi bi-hourglass-split"></i></span>
                    <span class="stat-label">Menunggu Anda (PPK)</span>
                </div>
                <div class="stat-value">{{ $menungguCount }}</div>
                <div class="stat-sub mt-1">SPP perlu direviu</div>
            </div>
        </div>
    </div>
    <div class="col">
        <div class="card stat-card h-100 shadow-sm" style="--accent: #20c997; --accent-bg: rgba(32,201,151,.12);">
            <div class="card-body p-3">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="stat-icon"><i class="bi bi-check2-circle"></i></span>
                    <span class="stat-label">Telah Disetujui (PPK)</span>
                </div>
                <div class="stat-value">{{ $disetujuiCount }}</div>
                <div class="stat-sub mt-1">Sudah Anda setujui</div>
            </div>
        </div>
    </div>
    <div class="col">
        <div class="card stat-card h-100 shadow-sm" style="--accent: #e03131; --accent-bg: rgba(224,49,49,.12);">
            <div class="card-body p-3">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="stat-icon"><i class="bi bi-arrow-counterclockwise"></i></span>
                    <span class="stat-label">Dikembalikan (Revisi)</span>
                </div>
                <div class="stat-value">{{ $revisiCount }}</div>
                <div class="stat-sub mt-1">Menunggu operator perbaiki</div>
            </div>
        </div>
    </div>
    <div class="col">
        <div class="card stat-card h-100 shadow-sm" style="--accent: #1c7ed6; --accent-bg: rgba(28,126,214,.12);">
            <div class="card-body p-3">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="stat-icon"><i class="bi bi-files"></i></span>
                    <span class="stat-label">Total SPP Terbit</span>
                </div>
                <div class="stat-value">{{ $totalCount }}</div>
                <div class="stat-sub mt-1">Semua pengajuan masuk</div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-striped align-middle">
                <thead>
                    <tr>
                        <th class="text-center">No</th>
                        <th>Nomor & Tanggal SPP</th>
                        <th>Kategori & Uraian</th>
                        <th class="text-end">Nominal</th>
                        <th class="text-center">Status</th>
                        <th class="text-center" style="width: 25%;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($spps as $spp)
                    <tr>
                        <td class="text-center">{{ $loop->iteration }}</td>
                        <td>
                            <strong>{{ $spp->nomor_spp }}</strong><br>
                            <small class="text-muted">{{ \Carbon\Carbon::parse($spp->tanggal_spp)->locale('id')->isoFormat('D MMMM Y') }}</small>
                        </td>
                        <td>
                            <span class="badge bg-primary mb-1">{{ $spp->tagihan->tipe_tagihan ?? 'SPP' }}</span><br>
                            <small>{{ $spp->tagihan->deskripsi ?? '-' }}</small>
                        </td>
                        <td class="text-end fw-bold">
                            Rp {{ number_format($spp->nominal_spp, 0, ',', '.') }}
                        </td>
                        <td class="text-center">
                            @if($spp->status_spp == 'Menunggu Verifikasi')
                                <span class="badge bg-warning text-dark"><i class="bi bi-clock"></i> Perlu Direviu</span>
                            @elseif($spp->status_spp == 'Revisi')
                                <span class="badge bg-danger"><i class="bi bi-x-circle"></i> Dikembalikan</span>
                            @elseif($spp->status_spp == 'Disetujui PPK')
                                <span class="badge bg-success"><i class="bi bi-check-circle"></i> Disetujui PPK</span>
                            @else
                                <span class="badge bg-primary"><i class="bi bi-info-circle"></i> {{ $spp->status_spp }}</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <a href="{{ route('spps.cetak-pdf', $spp->spp_id) }}" target="_blank" class="btn btn-sm btn-info mb-1" title="Lihat Dokumen">
                                <i class="bi bi-eye"></i> Cek PDF
                            </a>

                            @if($spp->status_spp == 'Menunggu Verifikasi')
                                <!-- Tombol Setujui -->
                                <form action="{{ route('verifikasi-ppk.spp.approve', $spp->spp_id) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button class="btn btn-sm btn-success mb-1" onclick="return confirm('Anda yakin menyetujui dokumen SPP ini secara definitif?')">
                                        <i class="bi bi-check2"></i> Setujui
                                    </button>
                                </form>

                                <!-- Tombol Revisi (Trigger Modal) -->
                                <button type="button" class="btn btn-sm btn-danger mb-1" data-bs-toggle="modal" data-bs-target="#modalRevisi{{ $spp->spp_id }}">
                                    <i class="bi bi-pencil"></i> Revisi
                                </button>

                                <!-- Modal Catatan Revisi -->
                                <div class="modal fade" id="modalRevisi{{ $spp->spp_id }}" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog text-start">
                                        <div class="modal-content">
                                            <form action="{{ route('verifikasi-ppk.spp.revisi', $spp->spp_id) }}" method="POST">
                                                @csrf
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Tolak dan Berikan Catatan Revisi</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <p class="text-danger"><small>Catatan ini akan muncul di layar Operator agar segera diperbaiki.</small></p>
                                                    <div class="form-group">
                                                        <label class="form-label fw-bold">Catatan Revisi:</label>
                                                        <textarea name="catatan_revisi" rows="4" class="form-control" placeholder="Contoh: Nomor SPP salah / MAK tidak pas" required></textarea>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                    <button type="submit" class="btn btn-danger">Kirim Catatan</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">Belum ada pengajuan SPP yang masuk.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
