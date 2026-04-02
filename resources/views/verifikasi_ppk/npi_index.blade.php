@extends('layouts.app')
@section('title') Verifikasi NPI — PPK (Persetujuan Akhir) @endsection
@push('css')
    <link href="{{ URL::asset('build/plugins/datatable/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet" />
@endpush
@section('content')
    <x-page-title :title="$pageTitle ?? 'Verifikasi NPI'" :subtitle="$pageSubtitle ?? 'Pejabat Pembuat Komitmen'" />

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
                    <h6 class="card-title fw-normal mb-1">Menunggu Anda (NPI)</h6>
                    <h3 class="fw-bold mb-0">{{ $pendingCount ?? 0 }}</h3>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card h-100 border-0 shadow-sm text-white" style="background-color: #20c997;">
                <div class="card-body p-3">
                    <h6 class="card-title fw-normal mb-1">NPI yang sudah diproses</h6>
                    <h3 class="fw-bold mb-0">{{ $approvedCount ?? 0 }}</h3>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h6 class="mb-3">Daftar Pengajuan NPI dari Bendahara Pengeluaran</h6>
            <div class="table-responsive">
                <table id="example" class="table table-striped table-bordered align-middle" style="width:100%">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>No. NPI / Tanggal</th>
                            <th>Dasar SPM</th>
                            <th class="text-end">Nominal (Rp)</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">Aksi PPK</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($npis as $idx => $npi)
                        <tr>
                            <td>{{ $idx + 1 }}</td>
                            <td>
                                <strong>{{ $npi->nomor_npi }}</strong><br>
                                <span class="badge bg-light text-dark border">
                                    <i class="bi bi-calendar-check"></i> {{ \Carbon\Carbon::parse($npi->tanggal_npi)->isoFormat('D MMM Y') }}
                                </span>
                            </td>
                            <td>
                                <small class="text-muted d-block">SPM Dasar:</small>
                                <strong>{{ $npi->nomor_spm ?? $npi->nomor_spp }}</strong>
                            </td>
                            <td class="text-end text-success fw-bold">
                                Rp {{ number_format($npi->jumlah_uang, 0, ',', '.') }}
                            </td>
                            <td class="text-center">
                                @if(($stage ?? 'ppk') === 'ppk' && $npi->status === \App\Models\DokumenNpi::STATUS_SUBMITTED_PPK)
                                    <span class="badge bg-warning text-dark"><i class="bi bi-hourglass-split"></i> Perlu Persetujuan Anda</span>
                                @elseif(($stage ?? 'ppk') === 'kasubbag' && $npi->status === \App\Models\DokumenNpi::STATUS_SUBMITTED_KASUBAG)
                                    <span class="badge bg-warning text-dark"><i class="bi bi-hourglass-split"></i> Menunggu Persetujuan Anda</span>
                                @elseif(in_array($npi->status, [\App\Models\DokumenNpi::STATUS_REJECTED_BENPEN, \App\Models\DokumenNpi::STATUS_REJECTED_PPK, \App\Models\DokumenNpi::STATUS_REJECTED_KASUBAG]))
                                    <span class="badge bg-danger"><i class="bi bi-x-circle"></i> Direvisi</span>
                                @elseif($npi->status === \App\Models\DokumenNpi::STATUS_APPROVED_KASUBAG)
                                    <span class="badge bg-success"><i class="bi bi-check2-all"></i> Disetujui</span>
                                @else
                                    <span class="badge bg-primary"><i class="bi bi-info-circle"></i> {{ $npi->status_spp }}</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <a href="{{ route('npis.cetak-pdf', $npi->id) }}" target="_blank" class="btn btn-sm btn-danger mb-1">
                                    <i class="bi bi-file-pdf"></i> PDF NPI
                                </a><br>
                                @if((($stage ?? 'ppk') === 'ppk' && $npi->status === \App\Models\DokumenNpi::STATUS_SUBMITTED_PPK) || (($stage ?? 'ppk') === 'kasubbag' && $npi->status === \App\Models\DokumenNpi::STATUS_SUBMITTED_KASUBAG))
                                    <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#approveModal{{ $npi->id }}">
                                        <i class="bi bi-check-lg"></i> Setujui
                                    </button>
                                    <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#revisiModal{{ $npi->id }}">
                                        <i class="bi bi-x-lg"></i> Tolak
                                    </button>
                                @else
                                    <span class="text-muted fst-italic small">Telah di-Review</span>
                                @endif

                                {{-- Approve Modal --}}
                                <div class="modal fade" id="approveModal{{ $npi->id }}" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content">
                                            <div class="modal-header bg-success text-white">
                                                <h5 class="modal-title text-white">Konfirmasi Persetujuan NPI</h5>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body text-start">
                                                <p>Setujui NPI berikut sebagai PPK?</p>
                                                <ul>
                                                    <li>Nomor NPI: <strong>{{ $npi->nomor_npi }}</strong></li>
                                                    <li>Nominal: <strong>Rp {{ number_format($npi->jumlah_uang, 0, ',', '.') }}</strong></li>
                                                </ul>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                <form action="{{ route($approveRouteName ?? 'verifikasi-ppk.npi.approve', $npi->id) }}" method="POST">
                                                    @csrf
                                                    <button type="submit" class="btn btn-success">Ya, Terbitkan NPI</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Revisi Modal --}}
                                <div class="modal fade" id="revisiModal{{ $npi->id }}" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content">
                                            <div class="modal-header bg-danger text-white">
                                                <h5 class="modal-title text-white">Kembalikan NPI ke Bendahara</h5>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                            </div>
                                            <form action="{{ route($rejectRouteName ?? 'verifikasi-ppk.npi.revisi', $npi->id) }}" method="POST">
                                                @csrf
                                                <div class="modal-body text-start">
                                                    <label class="form-label fw-bold">Alasan Penolakan <span class="text-danger">*</span></label>
                                                    <textarea name="catatan_revisi" class="form-control" rows="3" required placeholder="Jelaskan bagian yang perlu diperbaiki..."></textarea>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                    <button type="submit" class="btn btn-danger">Tolak & Kembalikan</button>
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
        </div>
    </div>
@endsection
@push('script')
    <script src="{{ URL::asset('build/plugins/datatable/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/datatable/js/dataTables.bootstrap5.min.js') }}"></script>
    <script>$(document).ready(function() { $('#example').DataTable({ "order": [] }); });</script>
@endpush
