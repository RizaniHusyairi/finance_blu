@extends('layouts.app')
@section('title', 'BKU Penerimaan')
@include('pembukuan.partials.styles')

@section('content')
<div class="container-fluid">
    <x-page-title title="Pembukuan" subtitle="Buku Kas Umum" />

    @include('pembukuan.partials.bku-tabs', ['active' => 'PENERIMAAN'])

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Rekening Penerimaan</label>
                    <select name="rekening_bank_id" class="form-select form-select-sm">
                        <option value="">Semua Rekening</option>
                        @foreach($rekeningOptions as $r)
                            <option value="{{ $r->id }}" @selected((string)($filters['rekening_bank_id'] ?? '') === (string)$r->id)>{{ $r->nama_bank }} - {{ $r->nomor_rekening }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2"><label class="form-label small fw-semibold">Dari</label><input type="date" name="start_date" value="{{ $filters['start_date'] ?? '' }}" class="form-control form-control-sm"></div>
                <div class="col-md-2"><label class="form-label small fw-semibold">Sampai</label><input type="date" name="end_date" value="{{ $filters['end_date'] ?? '' }}" class="form-control form-control-sm"></div>
                <div class="col-md-5 d-flex gap-2">
                    <button class="btn btn-sm btn-primary"><i class="bi bi-funnel"></i> Filter</button>
                    <a href="{{ route('pembukuan.penerimaan.pdf', request()->query()) }}" target="_blank" class="btn btn-sm btn-outline-danger"><i class="bi bi-file-earmark-pdf"></i> PDF</a>
                    <a href="{{ route('pembukuan.penerimaan.excel', request()->query()) }}" class="btn btn-sm btn-outline-success"><i class="bi bi-file-earmark-excel"></i> Excel</a>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-2 mb-3">
        <div class="col-md-3"><div class="card"><div class="card-body py-2"><div class="small text-muted">Saldo Awal</div><div class="fw-bold">Rp {{ number_format($buku['saldo_awal'], 0, ',', '.') }}</div></div></div></div>
        <div class="col-md-3"><div class="card"><div class="card-body py-2"><div class="small text-muted">Total Penerimaan</div><div class="fw-bold text-success">Rp {{ number_format($buku['total_terima'], 0, ',', '.') }}</div></div></div></div>
        <div class="col-md-3"><div class="card"><div class="card-body py-2"><div class="small text-muted">Total Pengeluaran</div><div class="fw-bold text-danger">Rp {{ number_format($buku['total_keluar'], 0, ',', '.') }}</div></div></div></div>
        <div class="col-md-3"><div class="card"><div class="card-body py-2"><div class="small text-muted">Saldo Akhir</div><div class="fw-bold">Rp {{ number_format($buku['saldo_akhir'], 0, ',', '.') }}</div></div></div></div>
    </div>

    <div class="card">
        <div class="card-header fw-bold"><i class="bi bi-journal-text text-primary"></i> {{ $buku['nama_buku'] }} — Bendahara Penerimaan ({{ $buku['jumlah_transaksi'] }} transaksi)</div>
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Tanggal</th><th>Kode Akun &amp; Jenis Pelayanan</th><th>Uraian</th>
                        <th class="text-end">Penerimaan</th><th class="text-end">Pengeluaran</th><th class="text-end">Saldo</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="table-secondary"><td colspan="5"><em>Saldo Awal Bulan Berjalan</em></td><td class="text-end fw-semibold">{{ number_format($buku['saldo_awal'], 0, ',', '.') }}</td></tr>
                    @forelse($buku['entries'] as $e)
                        @php $masuk = $e->arus_kas === 'DEBIT_MASUK'; @endphp
                        <tr>
                            <td class="text-nowrap">{{ optional($e->tanggal_transaksi)->format('d/m/Y') }}</td>
                            <td>{{ $e->akunPendapatan ? $e->akunPendapatan->kode_gabungan.' '.$e->akunPendapatan->uraian_jenis : '—' }}</td>
                            <td>{{ \Illuminate\Support\Str::limit($e->uraian, 70) }}</td>
                            <td class="text-end text-success">{{ $masuk ? number_format($e->nominal, 0, ',', '.') : '' }}</td>
                            <td class="text-end text-danger">{{ $masuk ? '' : number_format($e->nominal, 0, ',', '.') }}</td>
                            <td class="text-end">{{ number_format($e->saldo_berjalan ?? 0, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">Belum ada transaksi penerimaan. Klasifikasikan baris rekening koran terlebih dahulu.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
