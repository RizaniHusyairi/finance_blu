@extends('layouts.app')
@section('title', 'BKU Pengeluaran')
@include('pembukuan.partials.styles')

@section('content')
<div class="container-fluid">
    <x-page-title title="Pembukuan" subtitle="Buku Kas Umum" />

    @include('pembukuan.partials.bku-tabs', ['active' => 'PENGELUARAN'])

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Rekening Pengeluaran</label>
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
                    <a href="{{ route('pembukuan.pengeluaran.pdf', request()->query()) }}" target="_blank" class="btn btn-sm btn-outline-danger"><i class="bi bi-file-earmark-pdf"></i> PDF</a>
                    <a href="{{ route('pembukuan.pengeluaran.excel', request()->query()) }}" class="btn btn-sm btn-outline-success"><i class="bi bi-file-earmark-excel"></i> Excel</a>
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

    <div class="alert {{ $invariant['seimbang'] ? 'alert-success' : 'alert-warning' }} py-2 small d-flex justify-content-between">
        <span><i class="bi {{ $invariant['seimbang'] ? 'bi-check-circle' : 'bi-exclamation-triangle' }}"></i>
            Kontrol kas: Saldo BKU (Rp {{ number_format($invariant['saldo_bku'], 0, ',', '.') }})
            = Kas Tunai (Rp {{ number_format($invariant['saldo_tunai'], 0, ',', '.') }})
            + Bank (Rp {{ number_format($invariant['saldo_bank'], 0, ',', '.') }})</span>
        <span class="fw-bold">{{ $invariant['seimbang'] ? 'SEIMBANG' : 'Selisih Rp ' . number_format($invariant['selisih'], 0, ',', '.') }}</span>
    </div>

    @include('pembukuan.pengeluaran.partials.input-transaksi')

    @php
        $curSort = $sort ?? 'tanggal';
        $curDir = ($dir ?? 'asc') === 'desc' ? 'desc' : 'asc';
        // Sortir berbasis tautan GET — query filter (rekening/tanggal) ikut dipertahankan.
        $sortLink = function (string $key, string $label) use ($curSort, $curDir) {
            $active = $curSort === $key;
            $next = $active && $curDir === 'asc' ? 'desc' : 'asc';
            $ico = ! $active ? 'bi-arrow-down-up text-muted' : ($curDir === 'asc' ? 'bi-caret-up-fill' : 'bi-caret-down-fill');
            $url = request()->fullUrlWithQuery(['sort' => $key, 'dir' => $next]);

            return '<a href="' . e($url) . '" class="text-decoration-none ' . ($active ? 'fw-bold text-primary' : 'text-reset')
                . '">' . e($label) . ' <i class="bi ' . $ico . ' small"></i></a>';
        };
    @endphp

    <div class="card">
        <div class="card-header fw-bold"><i class="bi bi-journal-text text-primary"></i> {{ $buku['nama_buku'] }} — Bendahara Pengeluaran ({{ $buku['jumlah_transaksi'] }} transaksi)</div>
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>{!! $sortLink('tanggal', 'Tanggal') !!}</th>
                        <th>{!! $sortLink('kode', 'Kode Transaksi') !!}</th>
                        <th>{!! $sortLink('uraian', 'Uraian') !!}</th>
                        <th class="text-end">{!! $sortLink('penerimaan', 'Penerimaan') !!}</th>
                        <th class="text-end">{!! $sortLink('pengeluaran', 'Pengeluaran') !!}</th>
                        <th class="text-end">{!! $sortLink('saldo', 'Saldo') !!}</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="table-secondary">
                        <td colspan="5">
                            <em>Saldo Awal Bulan Berjalan</em>
                            <a href="{{ route('pembukuan.setup.edit') }}" class="ms-2 small text-decoration-none" title="Atur saldo awal di Setup Pembukuan"><i class="bi bi-gear"></i> Atur</a>
                        </td>
                        <td class="text-end fw-semibold">{{ number_format($buku['saldo_awal'], 0, ',', '.') }}</td>
                        <td></td>
                    </tr>
                    @forelse($buku['entries'] as $e)
                        @php $masuk = $e->arus_kas === 'DEBIT_MASUK'; @endphp
                        <tr>
                            <td class="text-nowrap">{{ optional($e->tanggal_transaksi)->format('d/m/Y') }}</td>
                            <td><span class="badge bg-light text-dark" title="{{ $e->kodeTransaksiRef?->uraian }}">{{ $e->kode_transaksi ?? '—' }}</span></td>
                            <td>{{ \Illuminate\Support\Str::limit($e->uraian, 70) }}</td>
                            <td class="text-end text-success">{{ $masuk ? number_format($e->nominal, 0, ',', '.') : '' }}</td>
                            <td class="text-end text-danger">{{ $masuk ? '' : number_format($e->nominal, 0, ',', '.') }}</td>
                            <td class="text-end">{{ number_format($e->saldo_berjalan ?? 0, 0, ',', '.') }}</td>
                            <td class="text-center">
                                @unless(\Illuminate\Support\Str::startsWith($e->nomor_bukti, 'SALDO-AWAL/'))
                                    <a href="{{ route('pembukuan.bku.show', $e->id) }}" class="btn btn-sm btn-outline-primary py-0 px-1" title="Detail"><i class="bi bi-eye"></i></a>
                                @endunless
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-4">Belum ada transaksi pengeluaran. Catat lewat <b>Input Transaksi</b> atau jalankan pencairan SP2D.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
