@extends('layouts.app')
@section('title', 'BKU Pengeluaran')
@include('pembukuan.partials.styles')

@section('content')
@php
    $bln = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

    $curSort = $sort ?? 'tanggal';
    $curDir = ($dir ?? 'asc') === 'desc' ? 'desc' : 'asc';
    // Sortir berbasis tautan GET — query filter (rekening/tanggal) ikut dipertahankan.
    $sortLink = function (string $key, string $label) use ($curSort, $curDir) {
        $active = $curSort === $key;
        $next = $active && $curDir === 'asc' ? 'desc' : 'asc';
        $ico = ! $active ? 'bi-arrow-down-up' : ($curDir === 'asc' ? 'bi-caret-up-fill' : 'bi-caret-down-fill');
        $url = request()->fullUrlWithQuery(['sort' => $key, 'dir' => $next]);

        return '<a href="' . e($url) . '" class="bku-sort' . ($active ? ' is-active' : '') . '">'
            . e($label) . ' <i class="bi ' . $ico . '"></i></a>';
    };
@endphp

<div class="container-fluid bku-page">

    <div class="bku-segment">
        @hasanyrole('Bendahara Pengeluaran|Super Admin')
            <a href="{{ route('pembukuan.pengeluaran.index') }}" class="is-active"><i class="bi bi-arrow-up-right-circle"></i> Pengeluaran</a>
        @endhasanyrole
        @hasanyrole('Bendahara Penerimaan|Super Admin')
            <a href="{{ route('pembukuan.penerimaan.index') }}"><i class="bi bi-arrow-down-left-circle"></i> Penerimaan</a>
        @endhasanyrole
    </div>

    @if(session('success'))<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif
    @if(session('error'))<div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif

    <div class="bku-hero">
        <div class="bku-hero__info">
            <div class="bku-hero__eyebrow">Buku Kas Umum · Bendahara Pengeluaran</div>
            <h3 class="bku-hero__title">{{ $buku['nama_buku'] }}</h3>
            <span class="bku-hero__rek"><i class="bi bi-journal-text"></i> {{ $buku['jumlah_transaksi'] }} transaksi</span>
        </div>
        <div class="bku-hero__actions">
            <button type="button" class="bku-btn bku-btn--light" data-bs-toggle="modal" data-bs-target="#modalInputTransaksi"><i class="bi bi-pencil-square"></i> Input Transaksi</button>
            <a href="{{ route('pembukuan.pengeluaran.pdf', request()->query()) }}" target="_blank" class="bku-btn bku-btn--ghost" title="Ekspor PDF"><i class="bi bi-filetype-pdf"></i></a>
            <a href="{{ route('pembukuan.pengeluaran.excel', request()->query()) }}" class="bku-btn bku-btn--ghost" title="Ekspor Excel"><i class="bi bi-filetype-xlsx"></i></a>
        </div>
    </div>

    <div class="book-filter">
        <form method="GET" action="{{ route('pembukuan.pengeluaran.index') }}" class="row g-3 align-items-end">
            <input type="hidden" name="sort" value="{{ $sort }}">
            <input type="hidden" name="dir" value="{{ $dir }}">
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Rekening Pengeluaran</label>
                <select name="rekening_bank_id" class="form-select">
                    <option value="">Semua Rekening</option>
                    @foreach($rekeningOptions as $r)
                        <option value="{{ $r->id }}" @selected((string)($filters['rekening_bank_id'] ?? '') === (string)$r->id)>{{ $r->nama_bank }} - {{ $r->nomor_rekening }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2"><label class="form-label small fw-semibold">Dari</label><input type="date" name="start_date" value="{{ $filters['start_date'] ?? '' }}" class="form-control"></div>
            <div class="col-md-2"><label class="form-label small fw-semibold">Sampai</label><input type="date" name="end_date" value="{{ $filters['end_date'] ?? '' }}" class="form-control"></div>
            <div class="col-md-3 d-flex gap-2">
                <button class="btn btn-primary"><i class="bi bi-funnel me-1"></i>Filter</button>
                <a href="{{ route('pembukuan.pengeluaran.index') }}" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>

    <div class="bku-stats">
        <div class="bku-stat bku-stat--slate" style="--i:0">
            <div class="bku-stat__icon"><i class="bi bi-wallet2"></i></div>
            <div class="bku-stat__body"><span class="bku-stat__label">Saldo Awal</span><span class="bku-stat__value">Rp {{ number_format($buku['saldo_awal'], 0, ',', '.') }}</span></div>
        </div>
        <div class="bku-stat bku-stat--green" style="--i:1">
            <div class="bku-stat__icon"><i class="bi bi-arrow-down-left"></i></div>
            <div class="bku-stat__body"><span class="bku-stat__label">Total Penerimaan</span><span class="bku-stat__value">Rp {{ number_format($buku['total_terima'], 0, ',', '.') }}</span></div>
        </div>
        <div class="bku-stat bku-stat--red" style="--i:2">
            <div class="bku-stat__icon"><i class="bi bi-arrow-up-right"></i></div>
            <div class="bku-stat__body"><span class="bku-stat__label">Total Pengeluaran</span><span class="bku-stat__value">Rp {{ number_format($buku['total_keluar'], 0, ',', '.') }}</span></div>
        </div>
        <div class="bku-stat bku-stat--indigo" style="--i:3">
            <div class="bku-stat__icon"><i class="bi bi-cash-stack"></i></div>
            <div class="bku-stat__body"><span class="bku-stat__label">Saldo Akhir</span><span class="bku-stat__value">Rp {{ number_format($buku['saldo_akhir'], 0, ',', '.') }}</span></div>
        </div>
    </div>

    <div class="alert {{ $invariant['seimbang'] ? 'alert-success' : 'alert-warning' }} border-0 rounded-3 py-2 small d-flex flex-wrap justify-content-between gap-2">
        <span><i class="bi {{ $invariant['seimbang'] ? 'bi-check-circle' : 'bi-exclamation-triangle' }}"></i>
            Kontrol kas: Saldo BKU (Rp {{ number_format($invariant['saldo_bku'], 0, ',', '.') }})
            = Kas Tunai (Rp {{ number_format($invariant['saldo_tunai'], 0, ',', '.') }})
            + Bank (Rp {{ number_format($invariant['saldo_bank'], 0, ',', '.') }})</span>
        <span class="fw-bold">{{ $invariant['seimbang'] ? 'SEIMBANG' : 'Selisih Rp ' . number_format($invariant['selisih'], 0, ',', '.') }}</span>
    </div>

    <div class="bku-card">
        <div class="bku-card__head">
            <div class="bku-card__title"><i class="bi bi-journal-text"></i> Rincian Transaksi</div>
            <span class="bku-chip-count">{{ $buku['jumlah_transaksi'] }} transaksi</span>
        </div>
        <div class="bku-table-wrap">
            <table class="bku-table">
                <thead>
                    <tr>
                        <th>{!! $sortLink('tanggal', 'Tanggal') !!}</th>
                        <th>{!! $sortLink('kode', 'Kode Transaksi') !!}</th>
                        <th>{!! $sortLink('uraian', 'Uraian') !!}</th>
                        <th class="ta-end">{!! $sortLink('penerimaan', 'Penerimaan') !!}</th>
                        <th class="ta-end">{!! $sortLink('pengeluaran', 'Pengeluaran') !!}</th>
                        <th class="ta-end">{!! $sortLink('saldo', 'Saldo') !!}</th>
                        <th class="ta-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="bku-row-saldoawal">
                        <td colspan="5"><i class="bi bi-flag-fill"></i> Saldo Awal Bulan Berjalan
                            <a href="{{ route('pembukuan.setup.edit') }}" class="bku-link-mini" title="Atur saldo awal di Setup Pembukuan">Atur</a>
                        </td>
                        <td class="ta-end bku-num bku-num--saldo">{{ number_format($buku['saldo_awal'], 0, ',', '.') }}</td>
                        <td></td>
                    </tr>

                    @forelse($buku['entries'] as $e)
                        @php $masuk = $e->arus_kas === 'DEBIT_MASUK'; @endphp
                        <tr class="bku-row" style="--r:{{ $loop->index }}">
                            <td>
                                <span class="bku-date">
                                    <span class="bku-date__d">{{ optional($e->tanggal_transaksi)->format('d') }}</span>
                                    <span class="bku-date__m">{{ $bln[(int) optional($e->tanggal_transaksi)->format('n')] ?? '' }} {{ optional($e->tanggal_transaksi)->format('Y') }}</span>
                                </span>
                            </td>
                            <td>
                                @if($e->kode_transaksi)
                                    <span class="bku-pill bku-pill--akun"><span class="bku-pill__code">{{ $e->kode_transaksi }}</span>{{ \Illuminate\Support\Str::limit($e->kodeTransaksiRef?->uraian, 24) }}</span>
                                @else
                                    <span class="bku-muted">—</span>
                                @endif
                            </td>
                            <td><span class="bku-uraian">{{ \Illuminate\Support\Str::limit($e->uraian, 64) }}</span></td>
                            <td class="ta-end bku-num bku-num--in">{{ $masuk ? number_format($e->nominal, 0, ',', '.') : '' }}</td>
                            <td class="ta-end bku-num bku-num--out">{{ $masuk ? '' : number_format($e->nominal, 0, ',', '.') }}</td>
                            <td class="ta-end bku-num bku-num--saldo">{{ number_format($e->saldo_berjalan ?? 0, 0, ',', '.') }}</td>
                            <td class="ta-center">
                                <div class="bku-actions">
                                    @unless(\Illuminate\Support\Str::startsWith($e->nomor_bukti, 'SALDO-AWAL/'))
                                        <a href="{{ route('pembukuan.bku.show', $e->id) }}" class="bku-ico" title="Detail"><i class="bi bi-eye"></i></a>
                                    @endunless
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <div class="bku-empty">
                                    <i class="bi bi-inbox"></i>
                                    <p>Belum ada transaksi pengeluaran.</p>
                                    <span>Catat lewat Input Transaksi atau jalankan pencairan SP2D.</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@include('pembukuan.pengeluaran.partials.input-transaksi')
@endsection

@push('script')
<script>
    @if($errors->any())
    window.addEventListener('load', function () {
        var m = document.getElementById('modalInputTransaksi');
        if (m && window.bootstrap && bootstrap.Modal) bootstrap.Modal.getOrCreateInstance(m).show();
    });
    @endif
</script>
@endpush
