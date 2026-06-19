@extends('layouts.app')
@section('title', 'Detail Buku Pembantu Bank')

@include('pembukuan.partials.styles')

@section('content')
    <x-page-title title="Pembukuan" subtitle="Detail Buku Pembantu Bank" />

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-1 fw-bold text-dark">{{ $rekening->nama_bank }} · {{ $rekening->nomor_rekening }}</h4>
            <div class="text-muted">{{ $rekening->nama_rekening ?? '-' }}</div>
        </div>
        <a href="{{ route('pembukuan.bank.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Kembali</a>
    </div>

    @php
        $cards = [
            ['label' => 'Jumlah Transaksi', 'value' => number_format($summary['jumlah_transaksi'] ?? 0, 0, ',', '.'), 'class' => 'text-dark'],
            ['label' => 'Total Masuk', 'value' => 'Rp ' . number_format($summary['total_masuk'] ?? 0, 0, ',', '.'), 'class' => 'text-success'],
            ['label' => 'Total Keluar', 'value' => 'Rp ' . number_format($summary['total_keluar'] ?? 0, 0, ',', '.'), 'class' => 'text-danger'],
        ];
    @endphp
    @include('pembukuan.partials.summary-cards', ['cards' => $cards])

    <div class="book-filter">
        <form method="GET" action="{{ route('pembukuan.bank.show', $rekening->id) }}" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Tanggal Awal</label>
                <input type="date" name="start_date" class="form-control" value="{{ $filters['start_date'] }}">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Tanggal Akhir</label>
                <input type="date" name="end_date" class="form-control" value="{{ $filters['end_date'] }}">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Arah Mutasi</label>
                <select name="arah_mutasi" class="form-select">
                    <option value="">Semua</option>
                    <option value="MASUK" @selected($filters['arah_mutasi'] === 'MASUK')>Masuk</option>
                    <option value="KELUAR" @selected($filters['arah_mutasi'] === 'KELUAR')>Keluar</option>
                </select>
            </div>
            <div class="col-12 d-flex gap-2">
                <button class="btn btn-primary"><i class="bi bi-funnel me-1"></i>Filter</button>
                <a href="{{ route('pembukuan.bank.show', $rekening->id) }}" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>

    <div class="card book-card">
        <div class="card-header"><h6 class="mb-0 fw-bold">Transaksi BKU Rekening Ini</h6></div>
        <div class="card-body p-0">
            @if($bku->isEmpty())
                @include('pembukuan.partials.empty-state', ['title' => 'Belum ada transaksi', 'message' => 'Tidak ada transaksi BKU yang sesuai filter untuk rekening ini.'])
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 book-table">
                        <thead class="table-light">
                            <tr>
                                <th>Tanggal</th>
                                <th>Nomor Bukti</th>
                                <th>Uraian</th>
                                <th class="text-end">Masuk</th>
                                <th class="text-end">Keluar</th>
                                <th class="text-end">Saldo</th>
                                <th>Referensi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($bku as $item)
                                @php
                                    $masuk = $item->arus_kas === 'DEBIT_MASUK';
                                @endphp
                                <tr>
                                    <td>{{ optional($item->tanggal_transaksi)->format('d M Y') }}</td>
                                    <td><span class="fw-semibold small">{{ $item->nomor_bukti ?? '-' }}</span></td>
                                    <td class="small">{{ $item->uraian ?? '-' }}</td>
                                    <td class="text-end text-success">{{ $masuk ? 'Rp ' . number_format($item->nominal, 0, ',', '.') : '-' }}</td>
                                    <td class="text-end text-danger">{{ ! $masuk ? 'Rp ' . number_format($item->nominal, 0, ',', '.') : '-' }}</td>
                                    <td class="text-end">Rp {{ number_format($item->saldo_akhir, 0, ',', '.') }}</td>
                                    <td class="small text-muted">
                                        @if($item->referensiPengeluaran)
                                            Tagihan: {{ $item->referensiPengeluaran->nomor_tagihan ?? '-' }}
                                        @elseif($item->referensiPenerimaan)
                                            Invoice: {{ $item->referensiPenerimaan->nomor_invoice ?? '-' }}
                                        @else
                                            -
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
