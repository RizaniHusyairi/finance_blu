@extends('layouts.app')
@section('title', 'Buku Pembantu Bendahara')

@include('pembukuan.partials.styles')

@section('content')
    <x-page-title title="Pembukuan" subtitle="Buku Pembantu Bendahara" />

    <div class="book-hero">
        <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 align-items-lg-center">
            <div>
                <h4 class="mb-1 fw-bold text-dark">Buku Pembantu Bendahara</h4>
                <div class="text-muted">Ledger operasional turunan dari BKU dengan fokus saldo awal, penerimaan, pengeluaran, dan referensi dokumen bendahara.</div>
            </div>
            <div>
                <a href="{{ route('pembukuan.bendahara.pdf', request()->query()) }}" target="_blank" class="btn btn-outline-danger">
                    <i class="bi bi-file-earmark-pdf me-1"></i> Export PDF
                </a>
            </div>
        </div>
    </div>

    @php
        $cards = [
            ['label' => 'Saldo Awal', 'value' => 'Rp ' . number_format($summary['saldo_awal'] ?? 0, 0, ',', '.'), 'class' => 'text-secondary'],
            ['label' => 'Total Penerimaan', 'value' => 'Rp ' . number_format($summary['total_penerimaan'] ?? 0, 0, ',', '.'), 'class' => 'text-success'],
            ['label' => 'Total Pengeluaran', 'value' => 'Rp ' . number_format($summary['total_pengeluaran'] ?? 0, 0, ',', '.'), 'class' => 'text-danger'],
            ['label' => 'Saldo Akhir', 'value' => 'Rp ' . number_format($summary['saldo_akhir'] ?? 0, 0, ',', '.'), 'class' => 'text-primary'],
        ];
    @endphp
    @include('pembukuan.partials.summary-cards', ['cards' => $cards])

    <div class="book-filter">
        <form method="GET" action="{{ route('pembukuan.bendahara.index') }}" class="row g-3 align-items-end">
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Tanggal Awal</label>
                <input type="date" name="start_date" class="form-control" value="{{ $filters['start_date'] }}">
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Tanggal Akhir</label>
                <input type="date" name="end_date" class="form-control" value="{{ $filters['end_date'] }}">
            </div>
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
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Jenis Transaksi</label>
                <select name="jenis_transaksi" class="form-select">
                    <option value="">Semua</option>
                    <option value="penerimaan" @selected($filters['jenis_transaksi'] === 'penerimaan')>Penerimaan</option>
                    <option value="pengeluaran" @selected($filters['jenis_transaksi'] === 'pengeluaran')>Pengeluaran</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Referensi Tagihan</label>
                <select name="tagihan_id" class="form-select">
                    <option value="">Semua Tagihan</option>
                    @foreach($tagihanOptions as $tagihan)
                        <option value="{{ $tagihan->id }}" @selected((string) $filters['tagihan_id'] === (string) $tagihan->id)>
                            {{ $tagihan->nomor_tagihan }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 d-flex gap-2">
                <button class="btn btn-primary"><i class="bi bi-funnel me-1"></i>Filter</button>
                <a href="{{ route('pembukuan.bendahara.index') }}" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>

    <div class="card book-card">
        <div class="card-header"><h6 class="mb-0 fw-bold">Ledger Operasional Bendahara</h6></div>
        <div class="card-body p-0">
            @if($entries->isEmpty())
                @include('pembukuan.partials.empty-state', ['title' => 'Belum ada ledger bendahara', 'message' => 'Tidak ada baris BKU yang sesuai filter.'])
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 book-table">
                        <thead class="table-light">
                            <tr>
                                <th>Tanggal</th>
                                <th>Nomor Bukti</th>
                                <th>Uraian</th>
                                <th>Sumber / Tujuan</th>
                                <th class="text-end">Masuk</th>
                                <th class="text-end">Keluar</th>
                                <th class="text-end">Saldo Berjalan</th>
                                <th>Referensi Dokumen</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($entries as $entry)
                                @php
                                    $doc = $entry->referensiPengeluaran?->spps?->sortByDesc('tanggal_spp')->first();
                                    $target = $entry->referensiPengeluaran?->pihak?->nama_pihak
                                        ?? $entry->referensiPenerimaan?->mitra?->nama_pihak
                                        ?? $entry->sumberRekening?->nama_rekening
                                        ?? '-';
                                @endphp
                                <tr>
                                    <td>{{ optional($entry->tanggal_transaksi)->format('d M Y') }}</td>
                                    <td class="fw-semibold">{{ $entry->nomor_bukti }}</td>
                                    <td>{{ $entry->uraian }}</td>
                                    <td>{{ $target }}</td>
                                    <td class="text-end text-success">
                                        {{ $entry->arus_kas === 'DEBIT_MASUK' ? 'Rp ' . number_format($entry->nominal, 0, ',', '.') : '-' }}
                                    </td>
                                    <td class="text-end text-danger">
                                        {{ $entry->arus_kas === 'KREDIT_KELUAR' ? 'Rp ' . number_format($entry->nominal, 0, ',', '.') : '-' }}
                                    </td>
                                    <td class="text-end fw-bold">Rp {{ number_format($entry->saldo_akhir, 0, ',', '.') }}</td>
                                    <td>
                                        @if($entry->referensiPengeluaran)
                                            <div>{{ $entry->referensiPengeluaran->nomor_tagihan ?? '-' }}</div>
                                            <div class="small text-muted">
                                                {{ $doc?->nomor_spp ?? '-' }}
                                                @if($doc?->spm?->nomor_spm)
                                                    · {{ $doc->spm->nomor_spm }}
                                                @endif
                                            </div>
                                        @elseif($entry->referensiPenerimaan)
                                            <div>{{ $entry->referensiPenerimaan->nomor_invoice ?? '-' }}</div>
                                            <div class="small text-muted">Penerimaan BLU</div>
                                        @else
                                            <span class="text-muted">-</span>
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
