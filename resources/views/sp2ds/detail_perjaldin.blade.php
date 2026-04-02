@extends('layouts.app')
@section('title') Detail SP2D & BKU Perjaldin @endsection
@section('content')
    <x-page-title title="Pencatatan SP2D & BKU" subtitle="Surat Perintah Pencairan Dana" />

    @if(session('success'))
        <div class="alert alert-success border-0 bg-success alert-dismissible fade show">
            <div class="text-white">{{ session('success') }}</div>
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
                    <span class="badge bg-success px-3 py-2"><i class="bi bi-patch-check-fill"></i> NPI Terbit — Siap SP2D</span>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header bg-primary text-white">
            <h6 class="mb-0 text-white"><i class="bi bi-cash-stack"></i> Daftar NPI yang Siap Dicatat SP2D & BKU</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>No. NPI / SPM</th>
                            <th>Kategori</th>
                            <th class="text-end">Nominal (Rp)</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $no = 1; @endphp
                        @foreach($perjaldin->spps->filter(fn($spp) => optional($spp->spm?->npi)->status === \App\Models\DokumenNpi::STATUS_APPROVED_KASUBAG || $spp->spm?->npi?->sp2d) as $spp)
                        @php $slug = 'sp2d' . $spp->spp_id; @endphp
                        <tr>
                            <td>{{ $no++ }}</td>
                            <td>
                                @if($spp->nomor_npi)
                                    <strong>{{ $spp->nomor_npi }}</strong><br>
                                @endif
                                <small class="text-muted">SPM: {{ $spp->nomor_spm ?? $spp->nomor_spp }}</small>
                                @if($spp->nomor_sp2d)
                                    <br><span class="badge bg-info text-dark mt-1">SP2D: {{ $spp->nomor_sp2d }}</span>
                                @endif
                            </td>
                            <td class="fw-bold text-primary">{{ $spp->kategori_biaya }}</td>
                            <td class="text-end fw-bold">Rp {{ number_format($spp->jumlah_uang, 0, ',', '.') }}</td>
                            <td class="text-center">
                                @if(optional($spp->spm?->npi)->status === \App\Models\DokumenNpi::STATUS_APPROVED_KASUBAG && !$spp->spm?->npi?->sp2d)
                                    <span class="badge bg-warning text-dark"><i class="bi bi-bank"></i> Perlu Catat SP2D</span>
                                @elseif(optional($spp->spm?->npi?->sp2d)->status === \App\Models\DokumenSp2d::STATUS_DRAFT)
                                    <span class="badge bg-secondary"><i class="bi bi-file-earmark-text"></i> Draft SP2D</span>
                                @elseif(optional($spp->spm?->npi?->sp2d)->status === \App\Models\DokumenSp2d::STATUS_APPROVED)
                                    <span class="badge bg-info text-dark"><i class="bi bi-book"></i> Perlu Dieksekusi</span>
                                @elseif(optional($spp->spm?->npi?->sp2d)->status === \App\Models\DokumenSp2d::STATUS_EXECUTED)
                                    <span class="badge bg-success"><i class="bi bi-check2-all"></i> LUNAS</span>
                                    @if($spp->catatan_bku)
                                        <br><small class="text-muted">{{ $spp->catatan_bku }}</small>
                                    @endif
                                @endif
                            </td>
                            <td class="text-center">
                                {{-- Tombol Catat SP2D --}}
                                @if(optional($spp->spm?->npi)->status === \App\Models\DokumenNpi::STATUS_APPROVED_KASUBAG && !$spp->spm?->npi?->sp2d)
                                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#sp2dModal{{ $slug }}">
                                        <i class="bi bi-pen"></i> Generate SP2D
                                    </button>
                                @endif

                                @if(optional($spp->spm?->npi?->sp2d)->status === \App\Models\DokumenSp2d::STATUS_DRAFT)
                                    <form action="{{ route('sp2ds.approve', $spp->spm->npi->sp2d->id) }}" method="POST" class="d-inline-block">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-check2-square"></i> Approve SP2D
                                        </button>
                                    </form>
                                @endif

                                {{-- Tombol Eksekusi --}}
                                @if(in_array(optional($spp->spm?->npi?->sp2d)->status, [\App\Models\DokumenSp2d::STATUS_DRAFT, \App\Models\DokumenSp2d::STATUS_APPROVED], true))
                                    <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#bkuModal{{ $slug }}">
                                        <i class="bi bi-book-half"></i> Eksekusi & BKU
                                    </button>
                                @endif

                                @if(optional($spp->spm?->npi?->sp2d)->status === \App\Models\DokumenSp2d::STATUS_EXECUTED)
                                    <span class="text-success fw-bold"><i class="bi bi-check-circle-fill"></i> Selesai</span>
                                @endif

                                {{-- Modal SP2D --}}
                                <div class="modal fade" id="sp2dModal{{ $slug }}" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-lg text-start">
                                        <div class="modal-content">
                                            <form action="{{ route('sp2ds.store', $spp->spm->npi->id) }}" method="POST">
                                                @csrf
                                                <div class="modal-header bg-primary text-white">
                                                    <h5 class="modal-title text-white"><i class="bi bi-cash"></i> Catat Nomor SP2D</h5>
                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="alert alert-info py-2 mb-3">
                                                        Dasar NPI: <strong>{{ $spp->nomor_npi }}</strong>
                                                        &nbsp;|&nbsp; Nominal: <strong>Rp {{ number_format($spp->jumlah_uang, 0, ',', '.') }}</strong>
                                                    </div>
                                                    <div class="row mb-3">
                                                        <div class="col-md-6">
                                                            <label class="form-label fw-bold">Nomor SP2D <span class="text-danger">*</span></label>
                                                            <input type="text" name="nomor_sp2d" class="form-control" required
                                                                   placeholder="SP2D-BLU/{{ date('Y') }}/..."
                                                                   value="{{ $spp->nomor_sp2d ?? '' }}">
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label class="form-label fw-bold">Tanggal SP2D <span class="text-danger">*</span></label>
                                                            <input type="date" name="tanggal_sp2d" class="form-control" required
                                                                   value="{{ optional($spp->tanggal_sp2d)->format('Y-m-d') ?? date('Y-m-d') }}">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                                                    <button type="submit" class="btn btn-primary">
                                                        <i class="bi bi-save"></i> Simpan SP2D
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                                {{-- Modal BKU --}}
                                <div class="modal fade" id="bkuModal{{ $slug }}" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered text-start">
                                        <div class="modal-content">
                                            <form action="{{ route('sp2ds.catat-bku', $spp->spm->npi->sp2d->id) }}" method="POST" enctype="multipart/form-data">
                                                @csrf
                                                <div class="modal-header bg-success text-white">
                                                    <h5 class="modal-title text-white"><i class="bi bi-book-half"></i> Catat Realisasi ke BKU</h5>
                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="alert alert-success py-2 mb-3">
                                                        SP2D: <strong>{{ $spp->nomor_sp2d }}</strong>
                                                        &nbsp;|&nbsp; Nominal: <strong>Rp {{ number_format($spp->jumlah_uang, 0, ',', '.') }}</strong>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label fw-bold">Keterangan BKU (Opsional)</label>
                                                        <textarea name="catatan_bku" class="form-control" rows="3"
                                                                  placeholder="Mis: Realisasi belanja perjalanan dinas pegawai - {{ $spp->kategori_biaya }}...">Realisasi belanja perjalanan dinas - {{ $spp->kategori_biaya }}</textarea>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label fw-bold">Bukti Transfer <span class="text-danger">*</span></label>
                                                        <input type="file" name="bukti_transfer" class="form-control" accept=".pdf,.jpg,.jpeg,.png" required>
                                                        @if($spp->spm?->npi?->sp2d?->bukti_transfer)
                                                            <small class="text-muted d-block mt-2">Bukti aktif: {{ $spp->spm->npi->sp2d->bukti_transfer->nama_file_asli }}</small>
                                                        @endif
                                                    </div>
                                                    <div class="alert alert-warning py-2">
                                                        <i class="bi bi-exclamation-triangle"></i> <strong>Perhatian:</strong> Setelah ini status akan menjadi <strong>LUNAS</strong> dan tidak bisa diubah.
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                    <button type="submit" class="btn btn-success">
                                                        <i class="bi bi-check2-circle"></i> Catat ke BKU (Lunas)
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
                <a href="{{ route('sp2ds.index') }}" class="btn btn-secondary px-4">
                    <i class="bi bi-arrow-left"></i> Kembali ke Daftar Perjaldin
                </a>
            </div>
        </div>
    </div>
@endsection
