@extends('layouts.app')
@section('title') Verifikasi SPP @endsection

@section('content')
<x-page-title title="Verifikasi Dokumen" subtitle="Daftar SPP Menunggu Verifikasi PPK" />

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
<div class="row row-cols-1 row-cols-md-2 row-cols-xl-4 g-3 mb-4 mt-2">
    <div class="col">
        <div class="card h-100 border-0 shadow-sm text-white" style="background-color: #0dcaf0;">
            <div class="card-body p-3">
                <h6 class="card-title fw-normal mb-1">Menunggu Anda (PPK)</h6>
                <h3 class="fw-bold mb-0 text-white">{{ \App\Models\Spp::where('status_spp', 'Menunggu Verifikasi')->count() }}</h3>
            </div>
        </div>
    </div>
    <div class="col">
        <div class="card h-100 border-0 shadow-sm bg-white text-dark">
            <div class="card-body p-3">
                <h6 class="card-title fw-normal text-muted mb-1">Telah Disetujui (PPK)</h6>
                <h3 class="fw-bold mb-0">{{ \App\Models\Spp::whereNotIn('status_spp', ['Menunggu Verifikasi', 'Revisi'])->count() }}</h3>
            </div>
        </div>
    </div>
    <div class="col">
        <div class="card h-100 border-0 shadow-sm bg-danger text-white">
            <div class="card-body p-3">
                <h6 class="card-title fw-normal mb-1">Dikembalikan (Revisi)</h6>
                <h3 class="fw-bold mb-0">{{ \App\Models\Spp::where('status_spp', 'Revisi')->count() }}</h3>
            </div>
        </div>
    </div>
    <div class="col">
        <div class="card h-100 border-0 shadow-sm text-white" style="background-color: #20c997;">
            <div class="card-body p-3">
                <h6 class="card-title fw-normal mb-1">Total Semua SPP Terbit</h6>
                <h3 class="fw-bold mb-0 text-white">{{ \App\Models\Spp::count() }}</h3>
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
                            <span class="badge bg-primary mb-1">{{ $spp->kategori_biaya }}</span><br>
                            <small>{{ $spp->uraian }}</small>
                        </td>
                        <td class="text-end fw-bold">
                            Rp {{ number_format($spp->jumlah_uang, 0, ',', '.') }}
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
