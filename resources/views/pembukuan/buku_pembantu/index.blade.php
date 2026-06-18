@extends('layouts.app')
@section('title', 'Buku Pembantu')
@include('pembukuan.partials.styles')

@section('content')
@php $isPenerimaan = $peran === 'PENERIMAAN'; @endphp
<div class="container-fluid">
    <x-page-title title="Pembukuan" subtitle="Buku Pembantu (Partisi BKU)" />

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Buku</label>
                    <select name="kode_buku" class="form-select form-select-sm" onchange="this.form.submit()">
                        @foreach($bukuOptions as $kode => $label)
                            <option value="{{ $kode }}" @selected($kodeBuku == $kode)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Rekening</label>
                    <select name="rekening_bank_id" class="form-select form-select-sm">
                        <option value="">Semua Rekening</option>
                        @foreach($rekeningOptions as $r)
                            <option value="{{ $r->id }}" @selected((string)($filters['rekening_bank_id'] ?? '') === (string)$r->id)>{{ $r->nama_bank }} - {{ $r->nomor_rekening }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2"><label class="form-label small fw-semibold">Dari</label><input type="date" name="start_date" value="{{ $filters['start_date'] ?? '' }}" class="form-control form-control-sm"></div>
                <div class="col-md-2"><label class="form-label small fw-semibold">Sampai</label><input type="date" name="end_date" value="{{ $filters['end_date'] ?? '' }}" class="form-control form-control-sm"></div>
                <div class="col-md-2 d-flex gap-1">
                    <button class="btn btn-sm btn-primary"><i class="bi bi-funnel"></i></button>
                    <a href="{{ route('pembukuan.buku-pembantu.pdf', request()->query()) }}" target="_blank" class="btn btn-sm btn-outline-danger"><i class="bi bi-file-earmark-pdf"></i></a>
                    <a href="{{ route('pembukuan.buku-pembantu.excel', request()->query()) }}" class="btn btn-sm btn-outline-success"><i class="bi bi-file-earmark-excel"></i></a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header fw-bold d-flex justify-content-between">
            <span><i class="bi bi-journal text-primary"></i> {{ $buku['nama_buku'] }}</span>
            <span class="small">Saldo Awal: <b>Rp {{ number_format($buku['saldo_awal'], 0, ',', '.') }}</b> · Saldo Akhir: <b>Rp {{ number_format($buku['saldo_akhir'], 0, ',', '.') }}</b></span>
        </div>
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Tanggal</th><th>{{ $isPenerimaan ? 'Kode Akun & Jenis' : 'Kode Transaksi' }}</th><th>Uraian</th>
                        <th class="text-end">Penerimaan</th><th class="text-end">Pengeluaran</th><th class="text-end">Saldo</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="table-secondary"><td colspan="5"><em>Saldo Awal Bulan Berjalan</em></td><td class="text-end fw-semibold">{{ number_format($buku['saldo_awal'], 0, ',', '.') }}</td></tr>
                    @forelse($buku['entries'] as $e)
                        @php $masuk = $e->arus_kas === 'DEBIT_MASUK'; @endphp
                        <tr>
                            <td class="text-nowrap">{{ optional($e->tanggal_transaksi)->format('d/m/Y') }}</td>
                            <td>{{ $isPenerimaan ? ($e->akunPendapatan?->kode_gabungan ?? '—') : ($e->kode_transaksi ?? '—') }}</td>
                            <td>{{ \Illuminate\Support\Str::limit($e->uraian, 60) }}</td>
                            <td class="text-end text-success">{{ $masuk ? number_format($e->nominal, 0, ',', '.') : '' }}</td>
                            <td class="text-end text-danger">{{ $masuk ? '' : number_format($e->nominal, 0, ',', '.') }}</td>
                            <td class="text-end">{{ number_format($e->saldo_berjalan ?? 0, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">Belum ada transaksi pada buku ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
