@extends('layouts.app')

@section('title', 'Detail Honorarium')

@section('content')
<x-page-title title="Manajemen Honor" subtitle="Detail Honorarium" />

<div class="card">
    <div class="card-body">
        <div class="mb-3">
            <a href="{{ route('honorarium.index') }}" class="btn btn-secondary btn-sm">Kembali</a>
        </div>

        <div class="row mb-4">
            <div class="col-md-6"><strong>No Honorarium:</strong> {{ $honorarium->transaction_number }}</div>
            <div class="col-md-6"><strong>Status:</strong> {{ $honorarium->status }}</div>
            <div class="col-md-6"><strong>Tanggal:</strong> {{ optional($honorarium->date)->format('d/m/Y') }}</div>
            <div class="col-md-6"><strong>No SPP:</strong> {{ $honorarium->spp_number }}</div>
            <div class="col-md-6"><strong>No BAST:</strong> {{ $honorarium->bast_number }}</div>
            <div class="col-md-6"><strong>No Kegiatan:</strong> {{ $honorarium->activity_number }}</div>
            <div class="col-md-12 mt-2"><strong>Uraian:</strong> {{ $honorarium->description }}</div>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama</th>
                        <th>NRP</th>
                        <th>Pangkat/Korp</th>
                        <th>Jabatan</th>
                        <th>Honor</th>
                        <th>PPh</th>
                        <th>Jumlah</th>
                        <th>No Rekening</th>
                        <th>Bank</th>
                        <th>Nama Rekening</th>
                        <th>No HP</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($honorarium->honorariumItems as $detail)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $detail->name }}</td>
                            <td>{{ $detail->nrp }}</td>
                            <td>{{ $detail->rank_corps }}</td>
                            <td>{{ $detail->position }}</td>
                            <td>Rp {{ number_format($detail->honor_amount, 0, ',', '.') }}</td>
                            <td>Rp {{ number_format($detail->pph_amount, 0, ',', '.') }}</td>
                            <td>Rp {{ number_format($detail->net_amount, 0, ',', '.') }}</td>
                            <td>{{ $detail->bank_account_number }}</td>
                            <td>{{ $detail->bank_name }}</td>
                            <td>{{ $detail->bank_account_name }}</td>
                            <td>{{ $detail->phone_number }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="fw-bold">
                        <td colspan="5" class="text-end">TOTAL</td>
                        <td>Rp {{ number_format($honorarium->honorariumItems->sum('honor_amount'), 0, ',', '.') }}</td>
                        <td>Rp {{ number_format($honorarium->honorariumItems->sum('pph_amount'), 0, ',', '.') }}</td>
                        <td>Rp {{ number_format($honorarium->honorariumItems->sum('net_amount'), 0, ',', '.') }}</td>
                        <td colspan="4"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
@endsection