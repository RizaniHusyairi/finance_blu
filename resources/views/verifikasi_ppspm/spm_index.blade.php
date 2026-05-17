@extends('layouts.app')
@section('title')
    Verifikasi SPM Perjaldin
@endsection
@push('css')
    <link href="{{ URL::asset('build/plugins/datatable/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet" />
@endpush
@section('content')
    <x-page-title :title="$pageTitle ?? 'Verifikasi SPM'" :subtitle="$pageSubtitle ?? 'Pejabat Penandatangan SPM'" />

    @if(session('success'))
        <div class="alert alert-success border-0 bg-success alert-dismissible fade show">
            <div class="text-white">{{ session('success') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    
    <style>
        .stat-card {
            background: #fff;
            border: 1px solid #e9ecef;
            border-left: 4px solid var(--accent, #6c757d);
            border-radius: .5rem;
            transition: transform .15s ease, box-shadow .15s ease;
        }
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 .5rem 1rem rgba(0,0,0,.075) !important;
        }
        .stat-card .stat-icon {
            width: 36px; height: 36px;
            display: inline-flex; align-items: center; justify-content: center;
            border-radius: .5rem;
            background: var(--accent-bg, rgba(108,117,125,.1));
            color: var(--accent, #6c757d);
            font-size: 1.1rem;
        }
        .stat-card .stat-label { font-size: .8rem; color: #6c757d; text-transform: uppercase; letter-spacing: .03em; font-weight: 600; }
        .stat-card .stat-value { font-size: 1.85rem; font-weight: 700; line-height: 1.1; color: #212529; }
        .stat-card .stat-sub   { font-size: .75rem; color: #adb5bd; }
    </style>

    <div class="row row-cols-1 row-cols-md-2 g-3 mb-4 mt-2">
        <div class="col">
            <div class="card stat-card h-100 shadow-sm" style="--accent: #f59f00; --accent-bg: rgba(245,159,0,.12);">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <span class="stat-icon"><i class="bi bi-hourglass-split"></i></span>
                        <span class="stat-label">Menunggu Anda (SPM)</span>
                    </div>
                    <div class="stat-value">{{ $pendingCount ?? 0 }}</div>
                    <div class="stat-sub mt-1">Perlu TTE / persetujuan Anda</div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card stat-card h-100 shadow-sm" style="--accent: #20c997; --accent-bg: rgba(32,201,151,.12);">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <span class="stat-icon"><i class="bi bi-check2-all"></i></span>
                        <span class="stat-label">Telah Diproses</span>
                    </div>
                    <div class="stat-value">{{ $approvedCount ?? 0 }}</div>
                    <div class="stat-sub mt-1">Sudah ditindaklanjuti</div>
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
                                @if(($stage ?? 'ppspm') === 'ppspm' && $spm->status === \App\Models\DokumenSpm::STATUS_SUBMITTED_PPSPM)
                                    <span class="badge bg-warning text-dark"><i class="bi bi-hourglass-split"></i> Perlu TTE Anda</span>
                                @elseif(($stage ?? 'ppspm') === 'kasubbag' && $spm->status === \App\Models\DokumenSpm::STATUS_SUBMITTED_KASUBAG)
                                    <span class="badge bg-warning text-dark"><i class="bi bi-hourglass-split"></i> Perlu Persetujuan Anda</span>
                                @elseif(in_array($spm->status, [\App\Models\DokumenSpm::STATUS_REJECTED_PPSPM, \App\Models\DokumenSpm::STATUS_REJECTED_KASUBAG]))
                                    <span class="badge bg-danger"><i class="bi bi-x-circle"></i> Direvisi</span>
                                @elseif($spm->status === \App\Models\DokumenSpm::STATUS_SUBMITTED_KASUBAG)
                                    <span class="badge bg-info text-dark"><i class="bi bi-send-check"></i> Menunggu Kasubbag</span>
                                @elseif($spm->status === \App\Models\DokumenSpm::STATUS_APPROVED_KASUBAG)
                                    <span class="badge bg-success"><i class="bi bi-check2-all"></i> Sah / Terbit</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if((($stage ?? 'ppspm') === 'ppspm' && $spm->status === \App\Models\DokumenSpm::STATUS_SUBMITTED_PPSPM) || (($stage ?? 'ppspm') === 'kasubbag' && $spm->status === \App\Models\DokumenSpm::STATUS_SUBMITTED_KASUBAG))
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
                                <a href="{{ route('spms.cetak-pdf', $spm->id) }}" target="_blank" class="btn btn-sm btn-outline-secondary ms-1" title="Lihat Draf PDF">
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
                                                <form action="{{ route($approveRouteName ?? 'verifikasi-ppspm.spm.approve', $spm->id) }}" method="POST">
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
                                            <form action="{{ route($rejectRouteName ?? 'verifikasi-ppspm.spm.revisi', $spm->id) }}" method="POST">
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
