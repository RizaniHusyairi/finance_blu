@extends('layouts.app')
@section('title')
    Detail Perjalanan Dinas
@endsection
@section('content')
    <x-page-title title="Manajemen Perjaldin" subtitle="Lihat Detail" />

    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h6 class="mb-0">Detail Tagihan Perjalanan Dinas</h6>
                <span class="badge fs-6
                    @switch($tagihan->status)
                        @case('DRAFT') bg-secondary @break
                        @case('PENDING_PPK') bg-primary @break
                        @case('REVISI_PPK') @case('DITOLAK_PPK') bg-warning text-dark @break
                        @case('DISETUJUI_PPK') bg-success @break
                        @default bg-info text-dark
                    @endswitch
                ">{{ $tagihan->status }}</span>
            </div>

            <div class="row mb-4">
                <div class="col-md-4 mb-3">
                    <label class="text-muted small">Nomor Tagihan</label>
                    <p class="mb-0 fw-bold text-primary">{{ $tagihan->nomor_tagihan }}</p>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="text-muted small">Uraian / Deskripsi</label>
                    <p class="mb-0 fw-bold">{{ $tagihan->deskripsi }}</p>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="text-muted small">Total Bruto</label>
                    <p class="mb-0 fw-bold text-success fs-5">Rp {{ number_format($tagihan->total_bruto, 0, ',', '.') }}</p>
                </div>
            </div>

            <hr>

            <h6 class="mb-3 mt-4">Daftar Peserta Perjalanan Dinas</h6>
            <div class="table-responsive mb-4">
                <table class="table table-bordered table-striped align-middle">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center" width="5%">No</th>
                            <th>Nama Pegawai</th>
                            <th>NIP</th>
                            <th>No SPT</th>
                            <th>Tujuan</th>
                            <th>Tgl Berangkat</th>
                            <th class="text-center">Lama</th>
                            <th class="text-end">Tiket</th>
                            <th class="text-end">Transport</th>
                            <th class="text-end">Penginapan</th>
                            <th class="text-end">UH</th>
                            <th class="text-end">Representasi</th>
                            <th class="text-end fw-bold">Subtotal</th>
                            <th>Rekening</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($tagihan->detailPerjaldin as $idx => $detail)
                            @php
                                $sub = $detail->biaya_tiket + $detail->biaya_transport + $detail->biaya_penginapan + $detail->uang_harian + $detail->uang_representasi;
                            @endphp
                            <tr>
                                <td class="text-center">{{ $idx + 1 }}</td>
                                <td class="fw-bold">{{ $detail->pegawai->nama_lengkap ?? '-' }}</td>
                                <td>{{ $detail->pegawai->nip ?? '-' }}</td>
                                <td>{{ $detail->no_spt }}</td>
                                <td>{{ $detail->tujuan }}</td>
                                <td>{{ \Carbon\Carbon::parse($detail->tgl_berangkat)->format('d/m/Y') }}</td>
                                <td class="text-center">{{ $detail->lama_hari }} Hr</td>
                                <td class="text-end">{{ number_format($detail->biaya_tiket, 0, ',', '.') }}</td>
                                <td class="text-end">{{ number_format($detail->biaya_transport, 0, ',', '.') }}</td>
                                <td class="text-end">{{ number_format($detail->biaya_penginapan, 0, ',', '.') }}</td>
                                <td class="text-end">{{ number_format($detail->uang_harian, 0, ',', '.') }}</td>
                                <td class="text-end">{{ number_format($detail->uang_representasi, 0, ',', '.') }}</td>
                                <td class="text-end fw-bold">{{ number_format($sub, 0, ',', '.') }}</td>
                                <td>{{ $detail->rekening ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="12" class="text-end">TOTAL BRUTO</th>
                            <th class="text-end bg-light fs-6 text-success">Rp {{ number_format($tagihan->total_bruto, 0, ',', '.') }}</th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <hr>

            <h6 class="mb-3 mt-4">Jejak Proses (Audit Trail)</h6>
            <div class="row mb-5">
                <div class="col-md-12">
                    @if($tagihan->logs->isEmpty())
                        <div class="alert alert-secondary py-2 text-center text-muted mb-0"><small>Belum ada rekaman aktivitas.</small></div>
                    @else
                        <ul class="list-group list-group-flush border-start border-2 border-primary ms-3">
                            @foreach($tagihan->logs as $log)
                                <li class="list-group-item bg-transparent border-0 position-relative pb-4">
                                    <span class="position-absolute bg-primary rounded-circle border border-white border-2"
                                          style="width: 14px; height: 14px; left: -8px; top: 18px;">
                                    </span>
                                    <div class="ps-2 pt-1">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <strong class="text-primary">{{ $log->status_baru }}</strong>
                                            <span class="badge bg-light text-secondary border"><i class="bi bi-clock"></i> {{ $log->created_at->format('d M Y, H:i') }}</span>
                                        </div>
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

            <div class="mt-4">
                <a href="{{ route('perjaldins.index') }}" class="btn btn-secondary px-4"><i class="bi bi-arrow-left"></i> Kembali</a>
            </div>
        </div>
    </div>
@endsection
