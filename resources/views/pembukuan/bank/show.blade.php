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
            ['label' => 'Jumlah Mutasi', 'value' => number_format($summary['jumlah_mutasi'] ?? 0, 0, ',', '.'), 'class' => 'text-dark'],
            ['label' => 'Total Masuk', 'value' => 'Rp ' . number_format($summary['total_masuk'] ?? 0, 0, ',', '.'), 'class' => 'text-success'],
            ['label' => 'Total Keluar', 'value' => 'Rp ' . number_format($summary['total_keluar'] ?? 0, 0, ',', '.'), 'class' => 'text-danger'],
            ['label' => 'Matched', 'value' => number_format($summary['matched'] ?? 0, 0, ',', '.'), 'class' => 'text-primary'],
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
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Status Rekonsiliasi</label>
                <select name="status_rekonsiliasi" class="form-select">
                    <option value="">Semua</option>
                    <option value="BELUM" @selected($filters['status_rekonsiliasi'] === 'BELUM')>Belum</option>
                    <option value="PARTIAL" @selected($filters['status_rekonsiliasi'] === 'PARTIAL')>Partial</option>
                    <option value="MATCHED" @selected($filters['status_rekonsiliasi'] === 'MATCHED')>Matched</option>
                    <option value="SELISIH" @selected($filters['status_rekonsiliasi'] === 'SELISIH')>Selisih</option>
                </select>
            </div>
            <div class="col-12 d-flex gap-2">
                <button class="btn btn-primary"><i class="bi bi-funnel me-1"></i>Filter</button>
                <a href="{{ route('pembukuan.bank.show', $rekening->id) }}" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>

    <div class="card book-card">
        <div class="card-header"><h6 class="mb-0 fw-bold">Mutasi Per Rekening</h6></div>
        <div class="card-body p-0">
            @if($mutasi->isEmpty())
                @include('pembukuan.partials.empty-state', ['title' => 'Belum ada mutasi', 'message' => 'Tidak ada mutasi bank yang sesuai filter untuk rekening ini.'])
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 book-table">
                        <thead class="table-light">
                            <tr>
                                <th>Tanggal</th>
                                <th>Deskripsi</th>
                                <th>Nomor Referensi</th>
                                <th class="text-end">Debit</th>
                                <th class="text-end">Kredit</th>
                                <th class="text-end">Saldo</th>
                                <th>Status Rekonsiliasi</th>
                                <th>Detail</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($mutasi as $item)
                                @php $rek = $item->rekonsiliasiBanks->first(); @endphp
                                <tr>
                                    <td>{{ optional($item->tanggal_transaksi)->format('d M Y') }}</td>
                                    <td>{{ $item->deskripsi ?? '-' }}</td>
                                    <td>{{ $item->nomor_referensi_bank ?? '-' }}</td>
                                    <td class="text-end text-success">{{ $item->debit > 0 ? 'Rp ' . number_format($item->debit, 0, ',', '.') : '-' }}</td>
                                    <td class="text-end text-danger">{{ $item->kredit > 0 ? 'Rp ' . number_format($item->kredit, 0, ',', '.') : '-' }}</td>
                                    <td class="text-end">{{ $item->saldo !== null ? 'Rp ' . number_format($item->saldo, 0, ',', '.') : '-' }}</td>
                                    <td>@include('pembukuan.partials.status-badge', ['value' => $item->status_rekonsiliasi])</td>
                                    <td class="small text-muted">
                                        @if($rek)
                                            BKU: {{ $rek->bku?->nomor_bukti ?? '-' }}<br>
                                            Tagihan: {{ $rek->tagihan?->nomor_tagihan ?? '-' }}
                                        @else
                                            Belum ada pasangan rekonsiliasi
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
