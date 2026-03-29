@extends('layouts.app')

@section('title', 'Detail Honorarium')

@section('content')
<x-page-title title="Manajemen Honor" subtitle="Detail Honorarium" />

<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <a href="{{ route('honorarium.index') }}" class="btn btn-secondary btn-sm"><i class="bi bi-arrow-left"></i> Kembali</a>
            <span class="badge fs-6
                @switch($tagihan->status)
                    @case('DRAFT') bg-secondary @break
                    @case('PENDING_PPK') bg-primary @break
                    @case('DISETUJUI_PPK') bg-success @break
                    @case('DITOLAK_PPK') bg-warning text-dark @break
                    @default bg-info text-dark
                @endswitch
            ">{{ $tagihan->status }}</span>
        </div>

        <div class="row mb-4">
            <div class="col-md-4"><strong>No Tagihan:</strong> {{ $tagihan->nomor_tagihan }}</div>
            <div class="col-md-4"><strong>Uraian:</strong> {{ $tagihan->deskripsi }}</div>
            <div class="col-md-4"><strong>Total Netto:</strong> <span class="text-success fw-bold">Rp {{ number_format($tagihan->total_netto, 0, ',', '.') }}</span></div>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama</th>
                        <th>NRP/NIK</th>
                        <th>Pangkat</th>
                        <th>Jabatan</th>
                        <th>Honor</th>
                        <th>PPh</th>
                        <th>Netto</th>
                        <th>No Rekening</th>
                        <th>Bank</th>
                        <th>Nama Rekening</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($tagihan->detailHonorarium as $detail)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $detail->personel->nama_lengkap ?? '-' }}</td>
                            <td>{{ $detail->personel->nrp_nik ?? '-' }}</td>
                            <td>{{ $detail->personel->pangkat ?? '-' }}</td>
                            <td>{{ $detail->personel->jabatan ?? '-' }}</td>
                            <td>Rp {{ number_format($detail->nilai_honor, 0, ',', '.') }}</td>
                            <td>Rp {{ number_format($detail->pph, 0, ',', '.') }}</td>
                            <td class="fw-bold">Rp {{ number_format($detail->nilai_honor - $detail->pph, 0, ',', '.') }}</td>
                            <td>{{ $detail->rekening }}</td>
                            <td>{{ $detail->jenis_bank }}</td>
                            <td>{{ $detail->nama_rekening }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="fw-bold">
                        <td colspan="5" class="text-end">TOTAL</td>
                        <td>Rp {{ number_format($tagihan->detailHonorarium->sum('nilai_honor'), 0, ',', '.') }}</td>
                        <td>Rp {{ number_format($tagihan->detailHonorarium->sum('pph'), 0, ',', '.') }}</td>
                        <td>Rp {{ number_format($tagihan->total_netto, 0, ',', '.') }}</td>
                        <td colspan="3"></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <hr>
        <h6 class="mb-3 mt-4">Jejak Proses (Audit Trail)</h6>
        @if($tagihan->logs->isEmpty())
            <div class="alert alert-secondary py-2 text-center text-muted mb-0"><small>Belum ada rekaman aktivitas.</small></div>
        @else
            <ul class="list-group list-group-flush border-start border-2 border-primary ms-3">
                @foreach($tagihan->logs as $log)
                    <li class="list-group-item bg-transparent border-0 position-relative pb-4">
                        <span class="position-absolute bg-primary rounded-circle border border-white border-2"
                              style="width: 14px; height: 14px; left: -8px; top: 18px;"></span>
                        <div class="ps-2 pt-1">
                            <div class="d-flex justify-content-between align-items-center">
                                <strong class="text-primary">{{ $log->status_baru }}</strong>
                                <span class="badge bg-light text-secondary border"><i class="bi bi-clock"></i> {{ $log->created_at->format('d M Y, H:i') }}</span>
                            </div>
                            @if($log->catatan)
                                <div class="mt-2 p-2 bg-light border-start border-3 border-secondary rounded small text-dark">
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
@endsection