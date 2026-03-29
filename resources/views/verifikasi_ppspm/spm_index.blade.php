@extends('layouts.app')
@section('title')
    Verifikasi SPM Perjaldin
@endsection
@push('css')
    <link href="{{ URL::asset('build/plugins/datatable/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet" />
@endpush
@section('content')
    <x-page-title title="Verifikasi SPM" subtitle="Pejabat Penandatangan SPM" />

    @if(session('success'))
        <div class="alert alert-success border-0 bg-success alert-dismissible fade show">
            <div class="text-white">{{ session('success') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    
    <div class="row row-cols-1 row-cols-md-2 row-cols-xl-4 g-3 mb-4 mt-2">
        <div class="col">
            <div class="card h-100 border-0 shadow-sm text-white" style="background-color: #dc3545;">
                <div class="card-body p-3">
                    <h6 class="card-title fw-normal mb-1">Menunggu Anda (SPM)</h6>
                    <h3 class="fw-bold mb-0 text-white">{{ \App\Models\Spp::where('status_spp', 'Menunggu Verifikasi SPM')->count() }}</h3>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card h-100 border-0 shadow-sm bg-white text-dark">
                <div class="card-body p-3">
                    <h6 class="card-title fw-normal text-muted mb-1">Telah Disetujui (Terbit)</h6>
                    <h3 class="fw-bold mb-0">{{ \App\Models\Spp::where('status_spp', 'SPM Terbit')->count() }}</h3>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h6 class="mb-3">Daftar Pengajuan SPM Perjaldin</h6>
            <div class="table-responsive">
                <table id="example" class="table table-striped table-bordered align-middle" style="width:100%">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>No. SPM / Tanggal</th>
                            <th>No. Referensi SPP</th>
                            <th>Detail Tagihan (Rp)</th>
                            <th>Status Verifikasi</th>
                            <th class="text-center">Aksi PPSPM</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($spms as $idx => $spm)
                        <tr>
                            <td>{{ $idx + 1 }}</td>
                            <td>
                                <strong>{{ $spm->nomor_spm }}</strong><br>
                                <span class="badge bg-light text-dark border">
                                    <i class="bi bi-calendar-check"></i> {{ \Carbon\Carbon::parse($spm->tanggal_spm)->isoFormat('D MMMM Y') }}
                                </span>
                            </td>
                            <td>
                                <small class="text-muted d-block">Dasar:</small>
                                <strong>{{ $spm->nomor_spp }}</strong>
                            </td>
                            <td class="text-end text-success fw-bold">
                                Rp {{ number_format($spm->jumlah_uang, 0, ',', '.') }}
                            </td>
                            <td>
                                @if($spm->status_spp == 'Menunggu Verifikasi SPM')
                                    <span class="badge bg-warning text-dark"><i class="bi bi-hourglass-split"></i> Perlu TTE Anda</span>
                                @elseif($spm->status_spp == 'Revisi SPM')
                                    <span class="badge bg-danger"><i class="bi bi-x-circle"></i> Direvisi</span>
                                @elseif($spm->status_spp == 'SPM Terbit')
                                    <span class="badge bg-success"><i class="bi bi-check2-all"></i> Sah / Terbit</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($spm->status_spp == 'Menunggu Verifikasi SPM')
                                    <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#approveModal{{ $spm->spp_id }}">
                                        <i class="bi bi-check-lg"></i> Setujui
                                    </button>
                                    <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#revisiModal{{ $spm->spp_id }}">
                                        <i class="bi bi-x-lg"></i> Tolak
                                    </button>
                                @else
                                    <span class="text-muted fst-italic">Telah di-Review</span>
                                @endif
                                <!-- Tombol Preview PDF SPM (Meskipun belum disetujui, bisa lihat drafnya) -->
                                <a href="{{ route('spms.cetak-pdf', $spm->spp_id) }}" target="_blank" class="btn btn-sm btn-outline-secondary ms-1" title="Lihat Draf PDF">
                                    <i class="bi bi-file-earmark-pdf"></i>
                                </a>

                                <!-- Approve Modal -->
                                <div class="modal fade" id="approveModal{{ $spm->spp_id }}" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content">
                                            <div class="modal-header bg-success text-white">
                                                <h5 class="modal-title text-white">Konfirmasi Persetujuan SPM</h5>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body text-start">
                                                <p>Anda bersedia menandatangani SPM berikut secara digital?</p>
                                                <ul>
                                                    <li>Nomor: <strong>{{ $spm->nomor_spm }}</strong></li>
                                                    <li>Nominal: <strong>Rp {{ number_format($spm->jumlah_uang, 0, ',', '.') }}</strong></li>
                                                </ul>
                                                <div class="alert alert-warning"><i class="bi bi-info-circle"></i> Pastikan Anda sudah memeriksa draf PDF (kebenaran nomor rekening dan uang).</div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                <form action="{{ route('verifikasi-ppspm.spm.approve', $spm->spp_id) }}" method="POST">
                                                    @csrf
                                                    <button type="submit" class="btn btn-success">Ya, Terbitkan SPM</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Revisi Modal -->
                                <div class="modal fade" id="revisiModal{{ $spm->spp_id }}" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content">
                                            <div class="modal-header bg-danger text-white">
                                                <h5 class="modal-title text-white">Kembalikan SPM ke Operator</h5>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                            </div>
                                            <form action="{{ route('verifikasi-ppspm.spm.revisi', $spm->spp_id) }}" method="POST">
                                                @csrf
                                                <div class="modal-body text-start">
                                                    <div class="mb-3">
                                                        <label class="form-label fw-bold">Alasan Penolakan / Revisi <span class="text-danger">*</span></label>
                                                        <textarea class="form-control" name="catatan_revisi" rows="3" required placeholder="Jelaskan bagian mana yang perlu diperbaiki (contoh: Salah input tanggal / nomor rekening)..."></textarea>
                                                    </div>
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
