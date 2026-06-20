@extends('layouts.app')
@section('title', 'Realisasi Penerimaan')
@include('pembukuan.partials.styles')

@section('content')
<div class="container-fluid bku-page">

    <div class="bku-hero">
        <div class="bku-hero__info">
            <div class="bku-hero__eyebrow">Pembukuan · Bendahara Penerimaan</div>
            <h3 class="bku-hero__title">Realisasi Penerimaan per Akun</h3>
            <span class="bku-hero__rek"><i class="bi bi-calendar3"></i> Tahun {{ $realisasi['tahun'] }}</span>
        </div>
        <div class="bku-hero__actions">
            <a href="{{ route('pembukuan.realisasi.pdf', request()->query()) }}" target="_blank" class="bku-btn bku-btn--ghost" title="Ekspor PDF"><i class="bi bi-filetype-pdf"></i> Export PDF</a>
        </div>
    </div>

    <div class="book-filter">
        <form method="GET" action="{{ route('pembukuan.realisasi.index') }}" class="row g-3 align-items-end">
            <div class="col-md-2"><label class="form-label small fw-semibold">Tahun</label><input type="number" name="tahun" value="{{ $tahun }}" class="form-control"></div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Rekening</label>
                <select name="rekening_bank_id" class="form-select">
                    <option value="">Semua Rekening</option>
                    @foreach($rekeningOptions as $r)
                        <option value="{{ $r->id }}" @selected((string)($filters['rekening_bank_id'] ?? '') === (string)$r->id)>{{ $r->nama_bank }} - {{ $r->nomor_rekening }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button class="btn btn-primary"><i class="bi bi-funnel me-1"></i>Tampilkan</button>
            </div>
        </form>
    </div>

    <div class="card book-card">
        <div class="card-header"><h6 class="mb-0 fw-bold"><i class="bi bi-grid-3x3-gap text-primary me-1"></i> Realisasi Penerimaan Tahun {{ $realisasi['tahun'] }}</h6></div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table align-middle mb-0 book-table" style="font-size:.8rem;">
                    <thead>
                        <tr>
                            <th>Akun</th><th>Jenis Pelayanan</th>
                            @foreach($realisasi['months'] as $m)<th class="text-end">{{ $m }}</th>@endforeach
                            <th class="text-end">Jumlah</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($realisasi['rows'] as $row)
                            <tr>
                                <td class="text-nowrap fw-semibold">{{ $row['kode'] }}</td>
                                <td>{{ $row['uraian'] }}</td>
                                @foreach($realisasi['months'] as $mi => $m)
                                    <td class="text-end">{{ $row['bulan'][$mi] ? number_format($row['bulan'][$mi], 0, ',', '.') : '' }}</td>
                                @endforeach
                                <td class="text-end fw-semibold">{{ number_format($row['total'], 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="15"><div class="bku-empty"><i class="bi bi-inbox"></i><p>Belum ada realisasi penerimaan terklasifikasi pada tahun ini.</p></div></td></tr>
                        @endforelse
                    </tbody>
                    @if(count($realisasi['rows']))
                    <tfoot>
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
</div>
@endsection
