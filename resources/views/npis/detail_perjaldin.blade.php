@extends('layouts.app')
@section('title') Manajemen NPI Perjaldin @endsection
@section('content')
    <x-page-title title="Pembuatan NPI" subtitle="Detail Nota Pemindahbukuan Internal" />

    @if(session('success'))
        <div class="alert alert-success border-0 bg-success alert-dismissible fade show">
            <div class="text-white">{{ session('success') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger border-0 bg-danger alert-dismissible fade show">
            <div class="text-white">{{ session('error') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card mb-4">
        <div class="card-body bg-light">
            <h5 class="card-title">{{ $perjaldin->uraian }}</h5>
            <div class="d-flex justify-content-between align-items-end flex-wrap gap-2">
                <div>
                    <p class="mb-1"><strong>No BAST:</strong> {{ $perjaldin->no_bast ?: '-' }}</p>
                    <p class="mb-0"><strong>Jumlah Pegawai:</strong> {{ $perjaldin->pejabats->count() }} Orang</p>
                </div>
                <div class="text-end">
                    <span class="badge bg-success px-3 py-2"><i class="bi bi-patch-check-fill"></i> SPM Terbit — Siap NPI</span>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header bg-primary text-white">
            <h6 class="mb-0 text-white"><i class="bi bi-bank"></i> Daftar SPM yang Siap Dibuatkan NPI</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>No. SPM</th>
                            <th>Kategori Biaya</th>
                            <th class="text-end">Nominal (Rp)</th>
                            <th class="text-center">Status NPI</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $no = 1; @endphp
                        @foreach($perjaldin->spps->whereIn('status_spp', ['SPM Terbit', 'Menunggu TTD Bendahara Penerimaan', 'Menunggu Verifikasi PPK NPI', 'Revisi NPI', 'NPI Terbit']) as $spp)
                        @php $slug = 'npi' . $spp->spp_id; @endphp
                        <tr>
                            <td>{{ $no++ }}</td>
                            <td>
                                <strong>{{ $spp->nomor_spm ?? $spp->nomor_spp }}</strong><br>
                                <small class="text-muted">SPP: {{ $spp->nomor_spp }}</small>
                            </td>
                            <td class="fw-bold text-primary">{{ $spp->kategori_biaya }}</td>
                            <td class="text-end fw-bold">Rp {{ number_format($spp->jumlah_uang, 0, ',', '.') }}</td>
                            <td class="text-center">
                                @if($spp->status_spp == 'SPM Terbit')
                                    <span class="badge bg-warning text-dark"><i class="bi bi-hourglass-split"></i> Perlu Dibuat NPI</span>
                                @elseif(in_array($spp->status_spp, ['Menunggu TTD Bendahara Penerimaan', 'Menunggu Verifikasi PPK NPI']))
                                    <span class="badge bg-info text-dark">
                                        <i class="bi bi-person-badge"></i> 
                                        {{ $spp->status_spp == 'Menunggu TTD Bendahara Penerimaan' ? 'Menunggu Bendahara Penerimaan' : 'Menunggu PPK' }}
                                    </span>
                                    @if($spp->nomor_npi)
                                        <br><small class="text-muted">{{ $spp->nomor_npi }}</small>
                                    @endif
                                @elseif($spp->status_spp == 'Revisi NPI')
                                    <span class="badge bg-danger"><i class="bi bi-x-circle"></i> Revisi dari PPK</span>
                                    @if($spp->catatan_revisi)
                                        <div class="text-danger small mt-1 fw-bold">"{{ $spp->catatan_revisi }}"</div>
                                    @endif
                                @elseif($spp->status_spp == 'NPI Terbit')
                                    <span class="badge bg-success"><i class="bi bi-check2-all"></i> NPI Terbit</span>
                                    <br><small class="text-muted">{{ $spp->nomor_npi }}</small>
                                @endif
                            </td>
                            <td class="text-center">
                                @if(in_array($spp->status_spp, ['SPM Terbit', 'Revisi NPI']))
                                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modal{{ $slug }}">
                                        <i class="bi bi-pen"></i> {{ $spp->status_spp == 'Revisi NPI' ? 'Perbaiki NPI' : 'Buat NPI' }}
                                    </button>
                                @elseif(in_array($spp->status_spp, ['Menunggu TTD Bendahara Penerimaan', 'Menunggu Verifikasi PPK NPI']))
                                    <a href="{{ route('npis.cetak-pdf', $spp->spp_id) }}" target="_blank" class="btn btn-sm btn-danger">
                                        <i class="bi bi-file-pdf"></i> PDF NPI
                                    </a>
                                    <span class="d-block text-muted fst-italic small mt-1">
                                        {{ $spp->status_spp == 'Menunggu TTD Bendahara Penerimaan' ? 'Menunggu TTD' : 'Menunggu PPK' }}
                                    </span>
                                @elseif($spp->status_spp == 'NPI Terbit')
                                    <a href="{{ route('npis.cetak-pdf', $spp->spp_id) }}" target="_blank" class="btn btn-sm btn-danger">
                                        <i class="bi bi-file-pdf"></i> PDF NPI
                                    </a>
                                @endif

                                {{-- Modal Form NPI --}}
                                <div class="modal fade" id="modal{{ $slug }}" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-lg text-start">
                                        <div class="modal-content">
                                            <form action="{{ route('npis.store', $spp->spp_id) }}" method="POST">
                                                @csrf
                                                <div class="modal-header bg-primary text-white">
                                                    <h5 class="modal-title text-white">
                                                        <i class="bi bi-bank"></i>
                                                        {{ $spp->status_spp == 'Revisi NPI' ? 'Perbaiki NPI' : 'Buat NPI' }}: {{ $spp->kategori_biaya }}
                                                    </h5>
                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    @if($spp->status_spp == 'Revisi NPI' && $spp->catatan_revisi)
                                                        <div class="alert alert-danger border-start border-4 border-danger px-3 py-2 mb-3">
                                                            <strong><i class="bi bi-exclamation-triangle-fill"></i> Catatan Revisi PPK:</strong>
                                                            <p class="mb-0 mt-1 fst-italic">{{ $spp->catatan_revisi }}</p>
                                                        </div>
                                                    @endif

                                                    <div class="alert alert-info py-2 mb-3">
                                                        Dasar SPM: <strong>{{ $spp->nomor_spm ?? $spp->nomor_spp }}</strong>
                                                        &nbsp;|&nbsp; Nominal: <strong>Rp {{ number_format($spp->jumlah_uang, 0, ',', '.') }}</strong>
                                                    </div>

                                                    <div class="row mb-3">
                                                        <div class="col-md-6">
                                                            <label class="form-label fw-bold">Nomor NPI <span class="text-danger">*</span></label>
                                                            <input type="text" name="nomor_npi" class="form-control" required
                                                                   placeholder="NPI-BLU/APTP-{{ date('Y') }}/..."
                                                                   value="{{ $spp->nomor_npi ?? '' }}">
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label class="form-label fw-bold">Tanggal NPI <span class="text-danger">*</span></label>
                                                            <input type="date" name="tanggal_npi" class="form-control" required
                                                                   value="{{ $spp->tanggal_npi ?? date('Y-m-d') }}">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                                                    <button type="submit" class="btn btn-success">
                                                        <i class="bi bi-send-check"></i> Ajukan NPI ke PPK
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                <a href="{{ route('npis.index') }}" class="btn btn-secondary px-4">
                    <i class="bi bi-arrow-left"></i> Kembali ke Daftar Perjaldin
                </a>
            </div>
        </div>
    </div>
@endsection
