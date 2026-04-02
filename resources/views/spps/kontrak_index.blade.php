@extends('layouts.app')
@section('title', 'SPP Kontrak')

@push('css')
    <link href="{{ URL::asset('build/plugins/datatable/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet" />
@endpush

@section('content')
    <x-page-title title="Pembuatan SPP" subtitle="Kontrak" />

    @if(session('success'))
        <div class="alert alert-success border-0 bg-success alert-dismissible fade show">
            <div class="text-white">{{ session('success') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @php
        $belumSpp = $contracts->where('status', 'READY_FOR_SPP')->count();
        $prosesSpp = $contracts->where('status', 'PROSES_SPP')->count();
        $selesaiSpp = $contracts->where('status', 'SPP_TERBIT')->count();
        $tertahan = $contracts->filter(fn ($item) => $item->spps->isNotEmpty() && optional($item->spps->first())->status_spp === 'Revisi')->count();
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
                    <h6 class="card-title fw-normal mb-1">SPP Terbit</h6>
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
            <h6 class="mb-3">Daftar Tagihan Kontrak Siap SPP</h6>
            <div class="table-responsive">
                <table id="example" class="table table-striped table-bordered align-middle" style="width:100%">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>No Tagihan</th>
                            <th>Kontrak / Pekerjaan</th>
                            <th>Vendor</th>
                            <th class="text-end">Nilai Netto (Rp)</th>
                            <th class="text-center">Status SPP</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($contracts as $idx => $contractTagihan)
                            @php
                                $kontrak = $contractTagihan->detailKontrak?->kontrakTermin?->kontrak;
                            @endphp
                            <tr>
                                <td>{{ $idx + 1 }}</td>
                                <td><strong class="text-primary">{{ $contractTagihan->nomor_tagihan }}</strong></td>
                                <td>
                                    <strong>{{ $kontrak->nomor_spk ?? '-' }}</strong><br>
                                    <small>{{ Str::limit($kontrak->nama_pekerjaan ?? $contractTagihan->deskripsi, 60) }}</small>
                                </td>
                                <td>{{ $kontrak?->vendor?->nama_pihak ?? '-' }}</td>
                                <td class="text-end"><strong>Rp {{ number_format($contractTagihan->total_netto, 0, ',', '.') }}</strong></td>
                                <td class="text-center">
                                    @if($contractTagihan->status === 'READY_FOR_SPP' && $contractTagihan->spps->isEmpty())
                                        <span class="badge bg-warning text-dark">Belum Dibuat SPP</span>
                                    @elseif($contractTagihan->status === 'PROSES_SPP' || $contractTagihan->spps->isNotEmpty())
                                        <span class="badge bg-info">SPP Dibuat</span>
                                    @elseif($contractTagihan->status === 'SPP_TERBIT')
                                        <span class="badge bg-success">SPP Terbit</span>
                                    @else
                                        <span class="badge bg-secondary">{{ $contractTagihan->status }}</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($kontrak)
                                        <a href="{{ route('spps.kontrak.detail', $contractTagihan->id) }}" class="btn btn-sm btn-primary">
                                            <i class="bi bi-gear"></i> Kelola SPP
                                        </a>
                                    @else
                                        <span class="badge bg-light text-dark border">Relasi kontrak tidak ditemukan</span>
                                    @endif
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
