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
                    <h6 class="card-title fw-normal mb-1">Menunggu Anda (Kasubag)</h6>
                    <h3 class="fw-bold mb-0">{{ \App\Models\Perjaldin::where('status', 'Proses Verifikasi')->where('is_kasubag_approved', false)->count() }}</h3>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card h-100 border-0 shadow-sm bg-white text-dark">
                <div class="card-body p-3">
                    <h6 class="card-title fw-normal text-muted mb-1">Sudah Anda Setujui</h6>
                    <h3 class="fw-bold mb-0">{{ \App\Models\Perjaldin::where('status', 'Proses Verifikasi')->where('is_kasubag_approved', true)->count() }}</h3>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card h-100 border-0 shadow-sm bg-danger text-white">
                <div class="card-body p-3">
                    <h6 class="card-title fw-normal mb-1">Dikembalikan (Revisi)</h6>
                    <h3 class="fw-bold mb-0">{{ \App\Models\Perjaldin::where('status', 'Revisi')->count() }}</h3>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card h-100 border-0 shadow-sm text-white" style="background-color: #20c997;">
                <div class="card-body p-3">
                    <h6 class="card-title fw-normal mb-1">Disetujui Penuh (Selesai)</h6>
                    <h3 class="fw-bold mb-0 text-white">{{ \App\Models\Perjaldin::whereNotIn('status', ['Draft', 'Proses Verifikasi', 'Revisi'])->count() }}</h3>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h6 class="mb-3">Daftar Perjaldin Menunggu Verifikasi Kasubag</h6>
            <div class="table-responsive">
                <table id="example" class="table table-striped table-bordered" style="width:100%">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>Uraian</th>
                            <th>Peserta</th>
                            <th>Status Persetujuan</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($perjaldins as $idx => $perjaldin)
                        <tr>
                            <td>{{ $idx + 1 }}</td>
                            <td>
                                <strong>{{ $perjaldin->uraian }}</strong><br>
                                <small class="text-muted">No BAST: {{ $perjaldin->no_bast ?: '-' }}</small>
                            </td>
                            <td>
                                <small>
                                @foreach($perjaldin->pejabats as $p)
                                    <span class="badge bg-light text-dark border">{{ $p->nama_pejabat }}</span>
                                @endforeach
                                </small>
                            </td>
                            <td>
                                <div class="d-flex gap-2 flex-wrap">
                                    @if($perjaldin->status == 'Proses Verifikasi' || $perjaldin->status == 'Revisi')
                                        @if($perjaldin->is_ppk_approved)
                                            <span class="badge bg-success"><i class="bi bi-check-circle"></i> PPK: Disetujui</span>
                                        @else
                                            <span class="badge bg-secondary"><i class="bi bi-clock"></i> PPK: Menunggu</span>
                                        @endif
                                        @if($perjaldin->is_kasubag_approved)
                                            <span class="badge bg-success"><i class="bi bi-check-circle"></i> Kasubag: Disetujui</span>
                                        @else
                                            <span class="badge bg-warning text-dark"><i class="bi bi-clock"></i> Kasubag: Menunggu</span>
                                        @endif
                                    @else
                                        @php
                                            $latestSpp = $perjaldin->spps->sortByDesc('updated_at')->first();
                                            $finalLbl = 'Selesai Verifikasi';
                                            $finalCls = 'bg-success';
                                            if ($latestSpp) {
                                                if ($latestSpp->status_spp == 'Lunas') $finalLbl = 'Lunas (BKU)';
                                                elseif ($latestSpp->status_spp == 'SP2D Terbit') $finalLbl = 'Sudah Cair';
                                                elseif (strpos($latestSpp->status_spp, 'NPI') !== false) $finalLbl = 'Proses NPI';
                                                elseif (strpos($latestSpp->status_spp, 'SPM') !== false) $finalLbl = 'Proses SPM';
                                                elseif ($latestSpp->status_spp == 'Disetujui PPK') $finalLbl = 'Siap SPM';
                                            }
                                        @endphp
                                        <span class="badge {{ $finalCls }} pe-3"><i class="bi bi-check-all"></i> {{ $finalLbl }}</span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <div class="d-flex gap-2 justify-content-center flex-wrap">
                                    @if(!$perjaldin->is_kasubag_approved)
                                    <form action="{{ route('verifikasi-kasubag.approve', $perjaldin->perjaldin_id) }}" method="POST" onsubmit="return confirm('Setujui Perjaldin ini?')">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-success"><i class="bi bi-check-circle-fill"></i> Setujui</button>
                                    </form>
                                    @else
                                        <span class="badge bg-success py-2"><i class="bi bi-check-all"></i> Sudah Disetujui</span>
                                    @endif
                                    @if($perjaldin->status == 'Proses Verifikasi')
                                        <button type="button" class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#revisiModal{{ $perjaldin->perjaldin_id }}">
                                            <i class="bi bi-pencil-square"></i> Revisi
                                        </button>
                                    @endif
                                    <button type="button" class="btn btn-sm btn-info text-white" data-bs-toggle="modal" data-bs-target="#riwayatModal{{ $perjaldin->perjaldin_id }}">
                                        <i class="bi bi-clock-history"></i> Riwayat
                                    </button>
                                </div>

                                {{-- Modal Riwayat Aktivitas --}}
                                <div class="modal fade" id="riwayatModal{{ $perjaldin->perjaldin_id }}" tabindex="-1">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content">
                                            <div class="modal-header bg-light">
                                                <h5 class="modal-title"><i class="bi bi-clock-history"></i> Log Aktivitas Dokumen</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body text-start pb-4">
                                                <h6 class="fw-bold mb-4 text-center">{{ $perjaldin->uraian }}</h6>
                                                
                                                @if($perjaldin->logs->isEmpty())
                                                    <div class="alert alert-secondary text-center"><small>Belum ada riwayat tercatat.</small></div>
                                                @else
                                                    <ul class="list-group list-group-flush border-start border-2 border-info ms-3">
                                                        @foreach($perjaldin->logs as $log)
                                                            <li class="list-group-item bg-transparent border-0 position-relative pb-4">
                                                                <span class="position-absolute bg-info rounded-circle border border-white border-2" 
                                                                      style="width: 14px; height: 14px; left: -24px; top: 12px;"></span>
                                                                <div class="ps-2">
                                                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                                                        <strong class="text-info">{{ $log->action }}</strong>
                                                                        <small class="text-muted">{{ $log->created_at->format('d M Y, H:i') }}</small>
                                                                    </div>
                                                                    <div class="small fw-semibold text-dark"><i class="bi bi-person"></i> {{ $log->user_name }}</div>
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

                                {{-- Modal Revisi --}}
                                <div class="modal fade" id="revisiModal{{ $perjaldin->perjaldin_id }}" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Catatan Revisi</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <form action="{{ route('verifikasi-kasubag.revisi', $perjaldin->perjaldin_id) }}" method="POST">
                                                @csrf
                                                <div class="modal-body">
                                                    <p>Anda akan mengembalikan Perjaldin <strong>"{{ $perjaldin->uraian }}"</strong> untuk direvisi oleh Operator Perjaldin.</p>
                                                    <div class="mb-3">
                                                        <label class="form-label fw-bold">Catatan Revisi <span class="text-danger">*</span></label>
                                                        <textarea class="form-control" name="catatan_revisi" rows="4" required placeholder="Tuliskan hal yang perlu diperbaiki..."></textarea>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                    <button type="submit" class="btn btn-warning">Kembalikan untuk Revisi</button>
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
    <script>$(document).ready(function() { $('#example').DataTable(); });</script>
@endpush
