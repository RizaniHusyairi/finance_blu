@extends('layouts.app')
@section('title') Manajemen SPM Perjaldin @endsection
@section('content')
    <x-page-title title="Pembuatan SPM" subtitle="Detail Multi-SPM Perjaldin" />

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

    {{-- Info Perjaldin --}}
    <div class="card mb-4">
        <div class="card-body bg-light">
            <h5 class="card-title">{{ $perjaldin->uraian }}</h5>
            <div class="d-flex justify-content-between align-items-end flex-wrap gap-2">
                <div>
                    <p class="mb-1"><strong>No BAST:</strong> {{ $perjaldin->no_bast ?: '-' }}</p>
                    <p class="mb-0"><strong>Jumlah Pegawai:</strong> {{ $perjaldin->pejabats->count() }} Orang</p>
                </div>
                <div class="text-end">
                    <div class="small text-muted fw-bold mb-1">Status Dokumen Perjaldin:</div>
                    <span class="badge bg-primary px-3 py-2"><i class="bi bi-file-earmark-text"></i> {{ $perjaldin->status }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabel SPP per Kategori --}}
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h6 class="mb-0 text-white">Daftar SPP yang Siap Dibuatkan SPM</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped align-middle">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 5%;">No</th>
                            <th style="width: 20%;">No. SPP (Dasar)</th>
                            <th style="width: 20%;">Kategori Biaya</th>
                            <th style="width: 15%;" class="text-end">Nominal (Rp)</th>
                            <th style="width: 20%;" class="text-center">Status SPM</th>
                            <th style="width: 20%;" class="text-center">Aksi / Dokumen</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $no = 1; @endphp
                        @foreach($perjaldin->spps->whereIn('status_spp', ['Disetujui PPK', 'Menunggu Verifikasi SPM', 'Revisi SPM', 'SPM Terbit']) as $spp)
                        @php $slug = 'spm' . $spp->spp_id; @endphp
                        <tr>
                            <td>{{ $no++ }}</td>
                            <td>
                                <strong>{{ $spp->nomor_spp }}</strong><br>
                                <small class="text-muted">{{ \Carbon\Carbon::parse($spp->tanggal_spp)->isoFormat('D MMM Y') }}</small>
                            </td>
                            <td class="fw-bold text-primary">{{ $spp->kategori_biaya }}</td>
                            <td class="text-end fw-bold">Rp {{ number_format($spp->jumlah_uang, 0, ',', '.') }}</td>
                            <td class="text-center">
                                @if($spp->status_spp == 'Disetujui PPK')
                                    <span class="badge bg-warning text-dark"><i class="bi bi-hourglass-split"></i> Perlu Dibuat SPM</span>
                                @elseif($spp->status_spp == 'Menunggu Verifikasi SPM')
                                    <span class="badge bg-info text-dark"><i class="bi bi-person-badge"></i> Menunggu TTD PPSPM</span>
                                    @if($spp->nomor_spm)
                                        <br><small class="text-muted">{{ $spp->nomor_spm }}</small>
                                    @endif
                                @elseif($spp->status_spp == 'Revisi SPM')
                                    <span class="badge bg-danger"><i class="bi bi-x-circle"></i> Revisi dari PPSPM</span>
                                    @if($spp->catatan_revisi)
                                        <div class="text-danger small mt-1 fw-bold">"{{ $spp->catatan_revisi }}"</div>
                                    @endif
                                @elseif($spp->status_spp == 'SPM Terbit')
                                    <span class="badge bg-success"><i class="bi bi-check2-all"></i> SPM Terbit</span>
                                    @if($spp->nomor_spm)
                                        <br><small class="text-muted">{{ $spp->nomor_spm }}</small>
                                    @endif
                                @endif
                            </td>
                            <td class="text-center">
                                {{-- Tombol Buat / Edit SPM --}}
                                @if(in_array($spp->status_spp, ['Disetujui PPK', 'Revisi SPM']))
                                    <button class="btn btn-sm btn-primary mb-1" data-bs-toggle="modal" data-bs-target="#modalSpm{{ $slug }}">
                                        <i class="bi bi-pen"></i> {{ $spp->status_spp == 'Revisi SPM' ? 'Perbaiki SPM' : 'Buat SPM' }}
                                    </button>
                                @endif

                                {{-- Tombol PDF --}}
                                @if(in_array($spp->status_spp, ['Menunggu Verifikasi SPM', 'SPM Terbit']))
                                    <a href="{{ route('spms.cetak-pdf', $spp->spp_id) }}" target="_blank" class="btn btn-sm btn-danger mb-1">
                                        <i class="bi bi-file-pdf"></i> PDF SPM
                                    </a>
                                @endif

                                {{-- Modal Form SPM --}}
                                <div class="modal fade" id="modalSpm{{ $slug }}" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-lg text-start">
                                        <div class="modal-content">
                                            <form action="{{ route('spms.store', $spp->spp_id) }}" method="POST">
                                                @csrf
                                                <div class="modal-header bg-primary text-white">
                                                    <h5 class="modal-title text-white">
                                                        <i class="bi bi-pen"></i>
                                                        {{ $spp->status_spp == 'Revisi SPM' ? 'Perbaiki SPM' : 'Terbitkan SPM' }}: {{ $spp->kategori_biaya }}
                                                    </h5>
                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">

                                                    @if($spp->status_spp == 'Revisi SPM' && $spp->catatan_revisi)
                                                        <div class="alert alert-danger border-start border-4 border-danger px-3 py-2 mb-3">
                                                            <strong><i class="bi bi-exclamation-triangle-fill"></i> Catatan Revisi PPSPM:</strong>
                                                            <p class="mb-0 mt-1 fst-italic">{{ $spp->catatan_revisi }}</p>
                                                        </div>
                                                    @endif

                                                    <div class="alert alert-info py-2 mb-3">
                                                        Nominal SPP Dasar: <strong>Rp {{ number_format($spp->jumlah_uang, 0, ',', '.') }}</strong>
                                                        &nbsp;|&nbsp; No. SPP: <strong>{{ $spp->nomor_spp }}</strong>
                                                    </div>

                                                    <div class="row mb-3">
                                                        <div class="col-md-6">
                                                            <label class="form-label fw-bold">Nomor SPM <span class="text-danger">*</span></label>
                                                            <input type="text" name="nomor_spm" class="form-control" required
                                                                   placeholder="SPM-BLU/APTP-{{ date('Y') }}/..."
                                                                   value="{{ $spp->nomor_spm ?? str_replace('SPP', 'SPM', $spp->nomor_spp) }}">
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label class="form-label fw-bold">Tanggal SPM <span class="text-danger">*</span></label>
                                                            <input type="date" name="tanggal_spm" class="form-control" required
                                                                   value="{{ $spp->tanggal_spm ?? date('Y-m-d') }}">
                                                        </div>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label class="form-label fw-bold">Pilih Pejabat Penandatangan SPM (PPSPM/KPA) <span class="text-danger">*</span></label>
                                                        <select name="ppspm_id" class="form-select" required>
                                                            <option value="">-- Pilih Pejabat Berwenang --</option>
                                                            @foreach($ppspms as $pejabat)
                                                                <option value="{{ $pejabat->id }}"
                                                                    {{ $spp->penandatangan_spm_nama == $pejabat->name ? 'selected' : '' }}>
                                                                    {{ $pejabat->name }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                                                    <button type="submit" class="btn btn-success"
                                                        onclick="return confirm('Ajukan SPM ini ke meja PPSPM?')">
                                                        <i class="bi bi-send-check"></i> Ajukan SPM ke PPSPM
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                {{-- End Modal --}}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                <a href="{{ route('spms.index') }}" class="btn btn-secondary px-4">
                    <i class="bi bi-arrow-left"></i> Kembali ke Daftar Perjaldin
                </a>
            </div>
        </div>
    </div>
@endsection
