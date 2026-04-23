@extends('layouts.app')
@section('title', 'Pengecekan Pembayaran Piutang')

@include('pembukuan.partials.styles')

@section('content')
    <x-page-title title="Pembukuan" subtitle="Pengecekan Pembayaran (Piutang)" />

    <div class="book-hero">
        <h4 class="mb-1 fw-bold text-dark">Pengecekan Pembayaran (Piutang)</h4>
        <div class="text-muted">Monitoring piutang dan status pembayaran berbasis `transaksi_penerimaan` untuk membantu Bendahara Penerimaan mengecek pelunasan invoice dan jatuh tempo.</div>
    </div>

    @php
        $cards = [
            ['label' => 'Jumlah Piutang', 'value' => number_format($summary['jumlah_piutang'] ?? 0, 0, ',', '.'), 'class' => 'text-dark'],
            ['label' => 'Total Tagihan', 'value' => 'Rp ' . number_format($summary['total_tagihan'] ?? 0, 0, ',', '.'), 'class' => 'text-primary'],
            ['label' => 'Total Dibayar', 'value' => 'Rp ' . number_format($summary['total_dibayar'] ?? 0, 0, ',', '.'), 'class' => 'text-success'],
            ['label' => 'Sisa Piutang', 'value' => 'Rp ' . number_format($summary['total_sisa'] ?? 0, 0, ',', '.'), 'class' => 'text-danger'],
        ];
    @endphp
    @include('pembukuan.partials.summary-cards', ['cards' => $cards])

    <div class="book-filter">
        <form method="GET" action="{{ route('pembukuan.piutang.index') }}" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Cari</label>
                <input type="text" name="search" class="form-control" value="{{ $filters['search'] }}" placeholder="Nomor invoice / mitra">
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Tanggal Awal</label>
                <input type="date" name="start_date" class="form-control" value="{{ $filters['start_date'] }}">
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Tanggal Akhir</label>
                <input type="date" name="end_date" class="form-control" value="{{ $filters['end_date'] }}">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Status Pembayaran</label>
                <select name="status_pembayaran" class="form-select">
                    <option value="">Semua</option>
                    <option value="UNPAID" @selected($filters['status_pembayaran'] === 'UNPAID')>Belum Bayar</option>
                    <option value="PARTIAL" @selected($filters['status_pembayaran'] === 'PARTIAL')>Sebagian</option>
                    <option value="PAID" @selected($filters['status_pembayaran'] === 'PAID')>Lunas</option>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button class="btn btn-primary"><i class="bi bi-funnel me-1"></i>Filter</button>
                <a href="{{ route('pembukuan.piutang.index') }}" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>

    <div class="card book-card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold">Daftar Piutang / Invoice</h6>
            <span class="badge bg-warning text-dark rounded-pill">{{ number_format($summary['jatuh_tempo'] ?? 0, 0, ',', '.') }} jatuh tempo</span>
        </div>
        <div class="card-body p-0">
            @if($entries->isEmpty())
                @include('pembukuan.partials.empty-state', ['title' => 'Belum ada data piutang', 'message' => 'Tidak ada transaksi penerimaan yang sesuai filter saat ini.'])
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 book-table">
                        <thead class="table-light">
                            <tr>
                                <th>Nomor Invoice</th>
                                <th>Mitra</th>
                                <th>Tanggal Invoice</th>
                                <th>Jatuh Tempo</th>
                                <th class="text-end">Nominal Tagihan</th>
                                <th class="text-end">Total Dibayar</th>
                                <th class="text-end">Sisa</th>
                                <th>Status</th>
                                <th>Keterkaitan BKU</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($entries as $entry)
                                @php
                                    $sisa = max((float) $entry->nominal_tagihan - (float) $entry->total_dibayar, 0);
                                    $isOverdue = $entry->tanggal_jatuh_tempo && $entry->tanggal_jatuh_tempo->isPast() && $entry->status_pembayaran !== 'PAID';
                                @endphp
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $entry->nomor_invoice }}</div>
                                        <div class="small text-muted">{{ $entry->keterangan ?? '-' }}</div>
                                    </td>
                                    <td>{{ $entry->mitra?->nama_pihak ?? '-' }}</td>
                                    <td>{{ optional($entry->tanggal_invoice)->format('d M Y') ?? '-' }}</td>
                                    <td>
                                        <div>{{ optional($entry->tanggal_jatuh_tempo)->format('d M Y') ?? '-' }}</div>
                                        @if($isOverdue)
                                            <div class="small text-danger">Lewat jatuh tempo</div>
                                        @endif
                                    </td>
                                    <td class="text-end">Rp {{ number_format($entry->nominal_tagihan, 0, ',', '.') }}</td>
                                    <td class="text-end text-success">Rp {{ number_format($entry->total_dibayar, 0, ',', '.') }}</td>
                                    <td class="text-end text-danger fw-bold">Rp {{ number_format($sisa, 0, ',', '.') }}</td>
                                    <td>@include('pembukuan.partials.status-badge', ['value' => $entry->status_pembayaran])</td>
                                    <td class="small text-muted">
                                        @if($entry->bukuKasUmums->isNotEmpty())
                                            {{ $entry->bukuKasUmums->count() }} transaksi BKU
                                        @else
                                            Belum tercatat di BKU
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
@endsection
