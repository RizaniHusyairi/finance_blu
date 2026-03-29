@extends('layouts.app')
@section('title') TTD NPI — Bendahara Penerimaan @endsection
@push('css')
    <link href="{{ URL::asset('build/plugins/datatable/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet" />
@endpush
@section('content')
    <x-page-title title="Tanda Tangan NPI" subtitle="Bendahara Penerimaan" />

    @if(session('success'))
        <div class="alert alert-success border-0 bg-success alert-dismissible fade show">
            <div class="text-white">{{ session('success') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row row-cols-1 row-cols-md-2 g-3 mb-4 mt-2">
        <div class="col">
            <div class="card h-100 border-0 shadow-sm bg-danger text-white">
                <div class="card-body p-3">
                    <h6 class="card-title fw-normal mb-1">NPI Menunggu TTD Anda</h6>
                    <h3 class="fw-bold mb-0">{{ \App\Models\Spp::where('status_spp', 'Menunggu TTD Bendahara Penerimaan')->count() }}</h3>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card h-100 border-0 shadow-sm text-white" style="background-color: #20c997;">
                <div class="card-body p-3">
                    <h6 class="card-title fw-normal mb-1">NPI Yang Sudah Di-TTD</h6>
                    <h3 class="fw-bold mb-0">{{ \App\Models\Spp::where('status_spp', 'NPI Terbit')->count() }}</h3>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h6 class="mb-3">Daftar NPI yang Memerlukan Tanda Tangan Anda</h6>
            <div class="table-responsive">
                <table id="example" class="table table-striped table-bordered align-middle" style="width:100%">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>No. NPI / Tanggal</th>
                            <th>Dasar SPM</th>
                            <th class="text-end">Nominal (Rp)</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($spms as $idx => $spm)
                        <tr>
                            <td>{{ $idx + 1 }}</td>
                            <td>
                                <strong>{{ $spm->nomor_npi }}</strong><br>
                                <span class="badge bg-light text-dark border">
                                    <i class="bi bi-calendar-check"></i>
                                    {{ \Carbon\Carbon::parse($spm->tanggal_npi)->isoFormat('D MMM Y') }}
                                </span>
                            </td>
                            <td>
                                <small class="text-muted d-block">SPM Dasar:</small>
                                <strong>{{ $spm->nomor_spm ?? $spm->nomor_spp }}</strong>
                            </td>
                            <td class="text-end text-success fw-bold">
                                Rp {{ number_format($spm->jumlah_uang, 0, ',', '.') }}
                            </td>
                            <td class="text-center">
                                @if($spm->status_spp == 'Menunggu TTD Bendahara Penerimaan')
                                    <span class="badge bg-warning text-dark">
                                        <i class="bi bi-pen"></i> Perlu TTD Anda
                                    </span>
                                @elseif($spm->status_spp == 'NPI Terbit')
                                    <span class="badge bg-success"><i class="bi bi-check2-all"></i> Selesai</span>
                                @else
                                    <span class="badge bg-info text-dark">{{ $spm->status_spp }}</span>
                                @endif
                            </td>
                            <td class="text-center">
                                {{-- Tombol PDF selalu tersedia --}}
                                <a href="{{ route('npis.cetak-pdf', $spm->spp_id) }}" target="_blank" class="btn btn-sm btn-danger mb-1">
                                    <i class="bi bi-file-pdf"></i> PDF NPI
                                </a>

                                {{-- Tombol TTD hanya jika status menunggu --}}
                                @if($spm->status_spp == 'Menunggu TTD Bendahara Penerimaan')
                                    <button type="button" class="btn btn-sm btn-success mb-1" data-bs-toggle="modal" data-bs-target="#ttdModal{{ $spm->spp_id }}">
                                        <i class="bi bi-pen-fill"></i> TTD & Setujui
                                    </button>
                                @endif

                                {{-- Modal Konfirmasi TTD --}}
                                <div class="modal fade" id="ttdModal{{ $spm->spp_id }}" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content">
                                            <div class="modal-header bg-success text-white">
                                                <h5 class="modal-title text-white"><i class="bi bi-pen-fill"></i> Konfirmasi Tanda Tangan NPI</h5>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body text-start">
                                                <p>Anda akan menandatangani NPI berikut sebagai <strong>Bendahara Penerimaan</strong>:</p>
                                                <ul>
                                                    <li>Nomor NPI: <strong>{{ $spm->nomor_npi }}</strong></li>
                                                    <li>Tanggal: <strong>{{ \Carbon\Carbon::parse($spm->tanggal_npi)->locale('id')->isoFormat('D MMMM Y') }}</strong></li>
                                                    <li>Nominal: <strong>Rp {{ number_format($spm->jumlah_uang, 0, ',', '.') }}</strong></li>
                                                </ul>
                                                <div class="alert alert-info py-2">
                                                    Setelah disetujui, NPI akan diteruskan ke <strong>PPK</strong> untuk persetujuan akhir.
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                <form action="{{ route('verifikasi-bendahara-penerimaan.npi.approve', $spm->spp_id) }}" method="POST">
                                                    @csrf
                                                    <button type="submit" class="btn btn-success">
                                                        <i class="bi bi-check-circle-fill"></i> Ya, Tandatangani NPI
                                                    </button>
                                                </form>
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
    <script>$(document).ready(function() { $('#example').DataTable({ "order": [] }); });</script>
@endpush
