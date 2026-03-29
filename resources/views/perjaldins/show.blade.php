@extends('layouts.app')
@section('title')
    Detail Perjalanan Dinas
@endsection
@section('content')
    <x-page-title title="Manajemen Perjaldin" subtitle="Lihat Detail" />

    <div class="card">
        <div class="card-body">
            <h6 class="mb-4">Detail Perjalanan Dinas Pekerja</h6>
            
            <div class="row mb-4">
                <div class="col-md-6 mb-3">
                    <label class="text-muted small">Maksud / Uraian Perjalanan Dinas</label>
                    <p class="mb-0 fw-bold">{{ $pejabat->perjaldin->uraian }}</p>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="text-muted small">No BAST</label>
                    <p class="mb-0 fw-bold">{{ $pejabat->perjaldin->no_bast ?: '-' }}</p>
                </div>
            </div>

            <hr>

            <h6 class="mb-3 mt-4">Informasi Pegawai & Dokumen</h6>
            <div class="row mb-4">
                <div class="col-md-4 mb-3">
                    <label class="text-muted small">Nama Pegawai</label>
                    <p class="mb-0 fw-bold">{{ $pejabat->nama_pejabat }}</p>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="text-muted small">NIP</label>
                    <p class="mb-0 fw-bold">{{ $pejabat->nip ?: '-' }}</p>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="text-muted small">Status Perjaldin</label>
                    <p class="mb-0">
                        @php $status = $pejabat->perjaldin->status; @endphp
                        @if($status == 'Draft')
                            <span class="badge bg-secondary"><i class="bi bi-pencil"></i> Draf</span>
                        @elseif($status == 'Proses Verifikasi')
                            <span class="badge bg-warning text-dark"><i class="bi bi-hourglass-split"></i> Proses Verifikasi</span>
                        @elseif($status == 'Revisi')
                            <span class="badge bg-danger"><i class="bi bi-exclamation-triangle"></i> Revisi</span>
                        @elseif($status == 'Disetujui')
                            <span class="badge bg-success"><i class="bi bi-check-circle"></i> Disetujui Penuh (Siap Terbit SPP)</span>
                        @elseif($status == 'Proses SPP' || $status == 'SPP Terbit')
                            @php
                                $latestSpp = $pejabat->perjaldin->spps->sortByDesc('updated_at')->first();
                                $lbl = 'Proses / SPP Terbit';
                                $cls = 'bg-info bg-opacity-25 text-info border border-info';
                                
                                if ($latestSpp) {
                                    if ($latestSpp->status_spp == 'Lunas') {
                                        $lbl = 'Pencairan Lunas';
                                        $cls = 'bg-success text-white';
                                    } elseif ($latestSpp->status_spp == 'SP2D Terbit') {
                                        $lbl = 'SP2D Terbit';
                                        $cls = 'bg-success text-white';
                                    } elseif (strpos($latestSpp->status_spp, 'NPI') !== false) {
                                        $lbl = 'NPI Terbit';
                                        $cls = 'bg-primary text-white';
                                    } elseif (strpos($latestSpp->status_spp, 'SPM') !== false) {
                                        $lbl = 'SPM Terbit';
                                        $cls = 'bg-primary text-white';
                                    }
                                }
                            @endphp
                            <span class="badge {{ $cls }}"><i class="bi bi-file-earmark-check"></i> {{ $lbl }}</span>
                        @else
                            <span class="badge bg-primary">{{ $status }}</span>
                        @endif
                    </p>
                </div>

                <div class="col-md-3 mb-3">
                    <label class="text-muted small">No SPT</label>
                    <p class="mb-0 fw-bold">{{ $pejabat->no_spt }}</p>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="text-muted small">No SPPD</label>
                    <p class="mb-0 fw-bold">{{ $pejabat->no_sppd }}</p>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="text-muted small">Tujuan</label>
                    <p class="mb-0 fw-bold">{{ $pejabat->tujuan }}</p>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="text-muted small">Tanggal Berangkat</label>
                    <p class="mb-0 fw-bold">{{ \Carbon\Carbon::parse($pejabat->tanggal_berangkat)->format('d F Y') }} ({{ $pejabat->lama_perjalanan_dinas }} Hari)</p>
                </div>
            </div>

            <hr>

            <h6 class="mb-3 mt-4">Rincian Biaya</h6>
            <div class="table-responsive mb-4">
                <table class="table table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th>Jenis Biaya</th>
                            <th class="text-end">Nominal (Rp)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Tiket</td>
                            <td class="text-end">{{ number_format($pejabat->tiket, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td>Transport</td>
                            <td class="text-end">{{ number_format($pejabat->transport, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td>Uang Harian</td>
                            <td class="text-end">{{ number_format($pejabat->uang_harian, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td>Penginapan</td>
                            <td class="text-end">{{ number_format($pejabat->penginapan, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td>Uang Representasi</td>
                            <td class="text-end">{{ number_format($pejabat->uang_representasi, 0, ',', '.') }}</td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th class="text-end fw-bold">JUMLAH KESELURUHAN</th>
                            <th class="text-end fw-bold bg-light">
                                <?php
                                $total = $pejabat->tiket + $pejabat->transport + $pejabat->uang_harian + $pejabat->penginapan + $pejabat->uang_representasi;
                                echo number_format($total, 0, ',', '.');
                                ?>
                            </th>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="row">
                <div class="col-md-12 text-muted small">
                    <strong>Informasi Rekening:</strong> {{ $pejabat->rekening ?: 'Tidak ada informasi rekening.' }}
                </div>
            </div>

            <hr>

            <h6 class="mb-3 mt-4">Jejak Proses Verifikasi (Audit Trail)</h6>
            <div class="row mb-5">
                <div class="col-md-12">
                    @if($pejabat->perjaldin->logs->isEmpty())
                        <div class="alert alert-secondary py-2 text-center text-muted mb-0"><small>Belum ada rekaman aktivitas diajukan.</small></div>
                    @else
                        <ul class="list-group list-group-flush border-start border-2 border-primary ms-3">
                            @foreach($pejabat->perjaldin->logs as $log)
                                <li class="list-group-item bg-transparent border-0 position-relative pb-4">
                                    <span class="position-absolute bg-primary rounded-circle border border-white border-2" 
                                          style="width: 14px; height: 14px; left: -8px; top: 18px;">
                                    </span>
                                    <div class="ps-2 pt-1">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <strong class="text-primary">{{ $log->action }}</strong>
                                            <span class="badge bg-light text-secondary border"><i class="bi bi-clock"></i> {{ $log->created_at->format('d M Y, H:i') }}</span>
                                        </div>
                                        <div class="small fw-bold mt-1 text-dark"><i class="bi bi-person-circle text-muted"></i> {{ $log->user_name }}</div>
                                        @if($log->catatan)
                                            <div class="mt-2 p-2 bg-light border-start border-3 border-secondary rounded small text-dark" style="line-height:1.2;">
                                                <i class="bi bi-quote text-muted"></i> {{ $log->catatan }}
                                            </div>
                                        @endif
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
                </div>
            </div>

            <hr>

            <h6 class="mb-3 mt-4">Informasi Pencairan (Status SPP)</h6>
            <div class="row mb-5">
                <div class="col-md-12">
                    @if($pejabat->perjaldin->spps->isEmpty())
                        <div class="alert alert-warning py-2 text-center text-dark mb-0">
                            <i class="bi bi-clock-history"></i> Operator Keuangan BLU belum menerbitkan dokumen SPP.
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 15%;">No SPP</th>
                                        <th style="width: 15%;">Dasar (SPM/NPI)</th>
                                        <th style="width: 15%;">Realisasi SP2D</th>
                                        <th style="width: 15%;" class="text-end">Jumlah Nominal</th>
                                        <th style="width: 15%;" class="text-center">Status Pencairan</th>
                                        <th style="width: 10%;" class="text-center">BKU</th>
                                        <th style="width: 5%;" class="text-center"><i class="bi bi-file-pdf"></i></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($pejabat->perjaldin->spps as $spp)
                                        <tr>
                                            <td>
                                                <strong class="text-primary">{{ $spp->nomor_spp }}</strong><br>
                                                <small class="text-muted">{{ $spp->kategori_biaya }}</small>
                                            </td>
                                            <td>
                                                @if($spp->nomor_spm)
                                                    <div class="small fw-bold">SPM: {{ $spp->nomor_spm }}</div>
                                                @endif
                                                @if($spp->nomor_npi)
                                                    <div class="small text-success">NPI: {{ $spp->nomor_npi }}</div>
                                                @endif
                                                @if(!$spp->nomor_spm && !$spp->nomor_npi)
                                                    <span class="text-muted small">-</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($spp->nomor_sp2d)
                                                    <div class="fw-bold text-success">{{ $spp->nomor_sp2d }}</div>
                                                    <small class="text-muted">{{ \Carbon\Carbon::parse($spp->tanggal_sp2d)->format('d/m/Y') }}</small>
                                                @else
                                                    <span class="text-muted small">Menunggu SP2D...</span>
                                                @endif
                                            </td>
                                            <td class="text-end fw-bold text-dark">Rp {{ number_format($spp->jumlah_uang, 0, ',', '.') }}</td>
                                            <td class="text-center">
                                                @if($spp->status_spp == 'Menunggu Verifikasi')
                                                    <span class="badge bg-warning text-dark"><i class="bi bi-clock"></i> Verif PPK</span>
                                                @elseif($spp->status_spp == 'Revisi')
                                                    <span class="badge bg-danger">Revisi</span>
                                                @elseif($spp->status_spp == 'Disetujui PPK')
                                                    <span class="badge bg-info text-dark">Siap SPM</span>
                                                @elseif($spp->status_spp == 'SPM Terbit' || strpos($spp->status_spp, 'SPM') !== false)
                                                    <span class="badge bg-primary">SPM Sah</span>
                                                @elseif($spp->status_spp == 'NPI Terbit' || strpos($spp->status_spp, 'NPI') !== false)
                                                    <span class="badge bg-primary">NPI Terbit</span>
                                                @elseif($spp->status_spp == 'SP2D Terbit')
                                                    <span class="badge bg-success">SP2D Cair</span>
                                                @elseif($spp->status_spp == 'Lunas')
                                                    <span class="badge bg-success"><i class="bi bi-check2-all"></i> Lunas</span>
                                                @else
                                                    <span class="badge bg-secondary">{{ $spp->status_spp }}</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                @if($spp->status_spp == 'Lunas')
                                                    <i class="bi bi-journal-check text-success fs-5" title="{{ $spp->catatan_bku }}"></i>
                                                @else
                                                    <i class="bi bi-journal text-muted fs-5"></i>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                <a href="{{ route('spps.cetak-pdf', $spp->spp_id) }}" target="_blank" class="btn btn-sm btn-outline-danger py-0 px-2" title="Pratinjau Dokumen PDF">
                                                    <i class="bi bi-file-earmark-pdf fs-6"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

            <div class="mt-4">
                <a href="{{ route('perjaldins.index') }}" class="btn btn-secondary px-4"><i class="bi bi-arrow-left"></i> Kembali</a>
            </div>
        </div>
    </div>
@endsection
