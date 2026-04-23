@extends('layouts.app')
@section('title', 'Buku Pembantu Bunga Rekening')

@include('pembukuan.partials.styles')

@section('content')
    <x-page-title title="Pembukuan" subtitle="Buku Pembantu Bunga Rekening" />

    <div class="book-hero">
        <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 align-items-lg-center">
            <div>
                <h4 class="mb-1 fw-bold text-dark">Buku Pembantu Bunga Rekening</h4>
                <div class="text-muted">Laporan turunan mutasi bank untuk transaksi bunga rekening yang terklasifikasi dari deskripsi mutasi sistem existing.</div>
            </div>
            <div>
                <a href="{{ route('pembukuan.bunga.pdf', request()->query()) }}" target="_blank" class="btn btn-outline-danger">
                    <i class="bi bi-file-earmark-pdf me-1"></i> Export PDF
                </a>
            </div>
        </div>
    </div>

    @php
        $cards = [
            ['label' => 'Bunga Bulan Ini', 'value' => 'Rp ' . number_format($summary['bulan_ini'] ?? 0, 0, ',', '.'), 'class' => 'text-success'],
            ['label' => 'Bunga Tahun Berjalan', 'value' => 'Rp ' . number_format($summary['tahun_berjalan'] ?? 0, 0, ',', '.'), 'class' => 'text-primary'],
            ['label' => 'Jumlah Transaksi', 'value' => number_format($summary['jumlah_transaksi'] ?? 0, 0, ',', '.'), 'class' => 'text-dark'],
            ['label' => 'Catatan', 'value' => 'Klasifikasi dari deskripsi mutasi', 'class' => 'fs-6 text-muted'],
        ];
    @endphp
    @include('pembukuan.partials.summary-cards', ['cards' => $cards])

    <div class="book-filter">
        <form method="GET" action="{{ route('pembukuan.bunga.index') }}" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Rekening</label>
                <select name="rekening_bank_id" class="form-select">
                    <option value="">Semua Rekening</option>
                    @foreach($rekeningOptions as $rekening)
                        <option value="{{ $rekening->id }}" @selected((string) $filters['rekening_bank_id'] === (string) $rekening->id)>
                            {{ $rekening->nama_bank }} - {{ $rekening->nomor_rekening }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Tanggal Awal</label>
                <input type="date" name="start_date" class="form-control" value="{{ $filters['start_date'] }}">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Tanggal Akhir</label>
                <input type="date" name="end_date" class="form-control" value="{{ $filters['end_date'] }}">
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button class="btn btn-primary"><i class="bi bi-funnel me-1"></i>Filter</button>
                <a href="{{ route('pembukuan.bunga.index') }}" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>

    <div class="card book-card">
        <div class="card-header"><h6 class="mb-0 fw-bold">Daftar Transaksi Bunga Rekening</h6></div>
        <div class="card-body p-0">
            @if($entries->isEmpty())
                @include('pembukuan.partials.empty-state', ['title' => 'Belum ada transaksi bunga', 'message' => 'Tidak ditemukan mutasi dengan kata kunci bunga / jasa giro pada filter ini.'])
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 book-table">
                        <thead class="table-light">
                            <tr>
                                <th>Tanggal</th>
                                <th>Rekening Bank</th>
                                <th>Deskripsi Mutasi</th>
                                <th>Referensi Bank</th>
                                <th class="text-end">Nominal Bunga</th>
                                <th>Status Rekonsiliasi</th>
                                <th>Status BKU</th>
                                <th>Catatan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($entries as $entry)
                                @php $matchedBku = $matchedBkuMap[$entry->id] ?? null; @endphp
                                <tr>
                                    <td>{{ optional($entry->tanggal_transaksi)->format('d M Y') }}</td>
                                    <td>
                                        <div>{{ $entry->importMutasiBank?->rekeningBank?->nama_bank ?? '-' }}</div>
                                        <div class="small text-muted">{{ $entry->importMutasiBank?->rekeningBank?->nomor_rekening ?? '-' }}</div>
                                    </td>
                                    <td>{{ $entry->deskripsi ?? '-' }}</td>
                                    <td>{{ $entry->nomor_referensi_bank ?? '-' }}</td>
                                    <td class="text-end fw-bold text-success">Rp {{ number_format($entry->debit, 0, ',', '.') }}</td>
                                    <td>@include('pembukuan.partials.status-badge', ['value' => $entry->status_rekonsiliasi])</td>
                                    <td>
                                        @if($matchedBku)
                                            @include('pembukuan.partials.status-badge', ['value' => 'SUDAH_MASUK_BKU'])
                                        @else
                                            @include('pembukuan.partials.status-badge', ['value' => 'BELUM_MASUK_BKU'])
                                        @endif
                                    </td>
                                    <td class="small text-muted">
                                        @if($matchedBku)
                                            Tercocokkan ke BKU {{ $matchedBku->nomor_bukti ?? '-' }}
                                        @else
                                            Belum ditemukan pencatatan BKU yang sepadan.
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
