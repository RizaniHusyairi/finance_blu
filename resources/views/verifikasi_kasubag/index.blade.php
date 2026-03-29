@extends('layouts.app')
@section('title') Verifikasi Perjaldin - Kasubag @endsection
@push('css')
    <link href="{{ URL::asset('build/plugins/datatable/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet" />
@endpush
@section('content')
    <x-page-title title="Verifikasi Kasubag" subtitle="Perjalanan Dinas" />

    @if(session('success'))
        <div class="alert alert-success border-0 bg-success alert-dismissible fade show">
            <div class="text-white">{{ session('success') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger border-0 bg-danger alert-dismissible fade show">
            <ul class="text-white mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row row-cols-1 row-cols-md-2 row-cols-xl-4 g-3 mb-4 mt-2">
        <div class="col">
            <div class="card h-100 border-0 shadow-sm bg-warning text-dark">
                <div class="card-body p-3">
                    <h6 class="card-title fw-normal mb-1">Menunggu Review</h6>
                    <h3 class="fw-bold mb-0">{{ $tagihans->where('status', 'PENDING_PPK')->count() }}</h3>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card h-100 border-0 shadow-sm bg-white text-dark">
                <div class="card-body p-3">
                    <h6 class="card-title fw-normal text-muted mb-1">Disetujui PPK</h6>
                    <h3 class="fw-bold mb-0">{{ $tagihans->where('status', 'DISETUJUI_PPK')->count() }}</h3>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card h-100 border-0 shadow-sm bg-danger text-white">
                <div class="card-body p-3">
                    <h6 class="card-title fw-normal mb-1">Dikembalikan (Revisi)</h6>
                    <h3 class="fw-bold mb-0">{{ $tagihans->where('status', 'DITOLAK_PPK')->count() }}</h3>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card h-100 border-0 shadow-sm text-white" style="background-color: #20c997;">
                <div class="card-body p-3">
                    <h6 class="card-title fw-normal mb-1">Proses Lanjutan</h6>
                    <h3 class="fw-bold mb-0 text-white">{{ $tagihans->whereIn('status', ['PROSES_SPP', 'SPP_TERBIT'])->count() }}</h3>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h6 class="mb-3">Daftar Tagihan Perjaldin (View Only)</h6>
            <div class="table-responsive">
                <table id="example" class="table table-striped table-bordered" style="width:100%">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>No. Tagihan & Uraian</th>
                            <th>Peserta</th>
                            <th>Total Bruto</th>
                            <th>Status</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($tagihans as $idx => $tagihan)
                        <tr>
                            <td>{{ $idx + 1 }}</td>
                            <td>
                                <strong>{{ $tagihan->nomor_tagihan }}</strong><br>
                                <small>{{ $tagihan->deskripsi }}</small>
                            </td>
                            <td>
                                <small>
                                @foreach($tagihan->detailPerjaldin as $detail)
                                    <span class="badge bg-light text-dark border">{{ $detail->pegawai->nama_lengkap ?? '-' }}</span>
                                @endforeach
                                </small>
                            </td>
                            <td class="fw-bold">Rp {{ number_format($tagihan->total_bruto, 0, ',', '.') }}</td>
                            <td>
                                @switch($tagihan->status)
                                    @case('PENDING_PPK')
                                        <span class="badge bg-warning text-dark"><i class="bi bi-clock"></i> Menunggu PPK</span>
                                        @break
                                    @case('DISETUJUI_PPK')
                                        <span class="badge bg-success"><i class="bi bi-check-circle"></i> Disetujui PPK</span>
                                        @break
                                    @case('DITOLAK_PPK')
                                        <span class="badge bg-danger"><i class="bi bi-x-circle"></i> Dikembalikan</span>
                                        @break
                                    @default
                                        <span class="badge bg-info text-dark">{{ $tagihan->status }}</span>
                                @endswitch
                            </td>
                            <td>
                                <div class="d-flex gap-2 justify-content-center">
                                    <button type="button" class="btn btn-sm btn-info text-white" data-bs-toggle="modal" data-bs-target="#riwayatModal{{ $tagihan->id }}">
                                        <i class="bi bi-clock-history"></i> Riwayat
                                    </button>
                                </div>

                                {{-- Modal Riwayat --}}
                                <div class="modal fade" id="riwayatModal{{ $tagihan->id }}" tabindex="-1">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content">
                                            <div class="modal-header bg-light">
                                                <h5 class="modal-title"><i class="bi bi-clock-history"></i> Log Aktivitas</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body text-start pb-4">
                                                <h6 class="fw-bold mb-4 text-center">{{ $tagihan->deskripsi }}</h6>
                                                @if($tagihan->logs->isEmpty())
                                                    <div class="alert alert-secondary text-center"><small>Belum ada riwayat.</small></div>
                                                @else
                                                    <ul class="list-group list-group-flush border-start border-2 border-info ms-3">
                                                        @foreach($tagihan->logs as $log)
                                                            <li class="list-group-item bg-transparent border-0 position-relative pb-4">
                                                                <span class="position-absolute bg-info rounded-circle border border-white border-2"
                                                                      style="width: 14px; height: 14px; left: -24px; top: 12px;"></span>
                                                                <div class="ps-2">
                                                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                                                        <strong class="text-info">{{ $log->status_baru }}</strong>
                                                                        <small class="text-muted">{{ $log->created_at->format('d M Y, H:i') }}</small>
                                                                    </div>
                                                                    @if($log->catatan)
                                                                        <div class="mt-2 bg-light p-2 rounded small border-start border-3 border-secondary">
                                                                            {{ $log->catatan }}
                                                                        </div>
                                                                    @endif
                                                                </div>
                                                            </li>
                                                        @endforeach
                                                    </ul>
                                                @endif
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
@push('script')
    <script src="{{ URL::asset('build/plugins/datatable/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/datatable/js/dataTables.bootstrap5.min.js') }}"></script>
    <script>$(document).ready(function() { $('#example').DataTable(); });</script>
@endpush
