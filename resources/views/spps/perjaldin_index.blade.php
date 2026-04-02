@extends('layouts.app')
@section('title', 'SPP Perjaldin')

@push('css')
    <link href="{{ URL::asset('build/plugins/datatable/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet" />
@endpush

@section('content')
    <x-page-title title="Pembuatan SPP" subtitle="Perjalanan Dinas" />

    @if(session('success'))
        <div class="alert alert-success border-0 bg-success alert-dismissible fade show">
            <div class="text-white">{{ session('success') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @php
        $belumSpp = $perjaldins->where('status', 'DISETUJUI_PPK')->count();
        $prosesSpp = $perjaldins->where('status', 'PROSES_SPP')->count();
        $selesaiSpp = $perjaldins->where('status', 'SPP_TERBIT')->count();
        $tertahan = $perjaldins->filter(fn ($item) => $item->spps->isNotEmpty() && optional($item->spps->first())->status_spp === 'Revisi')->count();
    @endphp

    <div class="row row-cols-1 row-cols-md-2 row-cols-xl-4 g-3 mb-4 mt-2">
        <div class="col">
            <div class="card h-100 border-0 shadow-sm bg-secondary text-white">
                <div class="card-body p-3">
                    <h6 class="card-title fw-normal mb-1">Belum Ada SPP</h6>
                    <h3 class="fw-bold mb-0 text-white">{{ $belumSpp }}</h3>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card h-100 border-0 shadow-sm text-white" style="background-color: #0dcaf0;">
                <div class="card-body p-3">
                    <h6 class="card-title fw-normal mb-1">Sedang Proses SPP</h6>
                    <h3 class="fw-bold mb-0 text-white">{{ $prosesSpp }}</h3>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card h-100 border-0 shadow-sm text-white" style="background-color: #20c997;">
                <div class="card-body p-3">
                    <h6 class="card-title fw-normal mb-1">Selesai SPP Terbit</h6>
                    <h3 class="fw-bold mb-0 text-white">{{ $selesaiSpp }}</h3>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card h-100 border-0 shadow-sm bg-warning text-dark">
                <div class="card-body p-3">
                    <h6 class="card-title fw-normal mb-1">Perlu Tindak Lanjut</h6>
                    <h3 class="fw-bold mb-0">{{ $tertahan }} Lembar</h3>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h6 class="mb-3">Daftar Tagihan Perjaldin Siap SPP</h6>
            <div class="table-responsive">
                <table id="example" class="table table-striped table-bordered align-middle" style="width:100%">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>No Tagihan / Uraian</th>
                            <th>Waktu Disetujui PPK</th>
                            <th>Total Netto</th>
                            <th>Pegawai</th>
                            <th>Status SPP</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($perjaldins as $idx => $perjaldin)
                            <tr>
                                <td>{{ $idx + 1 }}</td>
                                <td>
                                    <strong>{{ $perjaldin->nomor_tagihan }}</strong><br>
                                    <small>{{ Str::limit($perjaldin->deskripsi, 70) }}</small>
                                </td>
                                <td>
                                    @if($perjaldin->waktu_verifikasi_ppk)
                                        <strong>{{ $perjaldin->waktu_verifikasi_ppk->format('d M Y') }}</strong><br>
                                        <small class="text-muted">{{ $perjaldin->waktu_verifikasi_ppk->format('H:i') }}</small>
                                    @else
                                        <small class="text-muted">-</small>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <strong>Rp {{ number_format($perjaldin->total_netto, 0, ',', '.') }}</strong>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-light border text-dark">{{ $perjaldin->detailPerjaldin->count() }} Pegawai</span>
                                </td>
                                <td>
                                    @if($perjaldin->status === 'DISETUJUI_PPK' && $perjaldin->spps->isEmpty())
                                        <span class="badge bg-warning text-dark">Belum Dibuat SPP</span>
                                    @elseif($perjaldin->status === 'PROSES_SPP' || $perjaldin->spps->isNotEmpty())
                                        <span class="badge bg-info">SPP Dibuat</span>
                                    @elseif($perjaldin->status === 'SPP_TERBIT')
                                        <span class="badge bg-success">SPP Terbit</span>
                                    @else
                                        <span class="badge bg-secondary">{{ $perjaldin->status }}</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('spps.perjaldin.detail', $perjaldin->id) }}" class="btn btn-sm btn-primary">
                                        <i class="bi bi-gear"></i> Kelola SPP
                                    </a>
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
