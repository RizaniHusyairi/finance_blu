@extends('layouts.app')
@section('title', 'Realisasi Penerimaan')
@include('pembukuan.partials.styles')

@section('content')
<div class="container-fluid">
    <x-page-title title="Pembukuan" subtitle="Realisasi Penerimaan per Akun" />

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-2"><label class="form-label small fw-semibold">Tahun</label><input type="number" name="tahun" value="{{ $tahun }}" class="form-control form-control-sm"></div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Rekening</label>
                    <select name="rekening_bank_id" class="form-select form-select-sm">
                        <option value="">Semua Rekening</option>
                        @foreach($rekeningOptions as $r)
                            <option value="{{ $r->id }}" @selected((string)($filters['rekening_bank_id'] ?? '') === (string)$r->id)>{{ $r->nama_bank }} - {{ $r->nomor_rekening }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-5 d-flex gap-2">
                    <button class="btn btn-sm btn-primary"><i class="bi bi-funnel"></i> Tampilkan</button>
                    <a href="{{ route('pembukuan.realisasi.pdf', request()->query()) }}" target="_blank" class="btn btn-sm btn-outline-danger"><i class="bi bi-file-earmark-pdf"></i> PDF</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header fw-bold"><i class="bi bi-grid-3x3-gap text-primary"></i> Realisasi Penerimaan Tahun {{ $realisasi['tahun'] }}</div>
        <div class="table-responsive">
            <table class="table table-sm table-bordered align-middle mb-0" style="font-size:.8rem;">
                <thead class="table-light text-center">
                    <tr>
                        <th>Akun</th><th class="text-start">Jenis Pelayanan</th>
                        @foreach($realisasi['months'] as $m)<th>{{ $m }}</th>@endforeach
                        <th>Jumlah</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($realisasi['rows'] as $row)
                        <tr>
                            <td class="text-nowrap">{{ $row['kode'] }}</td>
                            <td>{{ $row['uraian'] }}</td>
                            @foreach($realisasi['months'] as $mi => $m)
                                <td class="text-end">{{ $row['bulan'][$mi] ? number_format($row['bulan'][$mi], 0, ',', '.') : '' }}</td>
                            @endforeach
                            <td class="text-end fw-semibold">{{ number_format($row['total'], 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="15" class="text-center text-muted py-4">Belum ada realisasi penerimaan terklasifikasi pada tahun ini.</td></tr>
                    @endforelse
                </tbody>
                @if(count($realisasi['rows']))
                <tfoot class="table-primary fw-bold">
                    <tr>
                        <td colspan="2" class="text-end">TOTAL</td>
                        @foreach($realisasi['months'] as $mi => $m)
                            <td class="text-end">{{ $realisasi['total_per_bulan'][$mi] ? number_format($realisasi['total_per_bulan'][$mi], 0, ',', '.') : '' }}</td>
                        @endforeach
                        <td class="text-end">{{ number_format($realisasi['grand_total'], 0, ',', '.') }}</td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>
@endsection
