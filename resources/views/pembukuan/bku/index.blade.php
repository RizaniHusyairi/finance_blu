@extends('layouts.app')
@section('title', 'Buku Kas Umum')

@include('pembukuan.partials.styles')

@section('content')
    <x-page-title title="Pembukuan" subtitle="Buku Kas Umum" />

    <div class="book-hero">
        <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 align-items-lg-center">
            <div>
                <h4 class="mb-1 fw-bold text-dark">Buku Kas Umum</h4>
                <div class="text-muted">Ledger arus kas masuk dan keluar berbasis `buku_kas_umum` dengan saldo berjalan per rekening sumber.</div>
            </div>
            <div>
                <a href="{{ route('pembukuan.bku.pdf', request()->query()) }}" target="_blank" class="btn btn-outline-danger">
                    <i class="bi bi-file-earmark-pdf me-1"></i> Export PDF
                </a>
            </div>
        </div>
    </div>

    @php
        $cards = [
            ['label' => 'Total Debit', 'value' => 'Rp ' . number_format($summary['total_debit'] ?? 0, 0, ',', '.'), 'class' => 'text-success'],
            ['label' => 'Total Kredit', 'value' => 'Rp ' . number_format($summary['total_kredit'] ?? 0, 0, ',', '.'), 'class' => 'text-danger'],
            ['label' => 'Saldo Akhir', 'value' => 'Rp ' . number_format($summary['saldo_akhir'] ?? 0, 0, ',', '.'), 'class' => 'text-primary'],
            ['label' => 'Jumlah Transaksi', 'value' => number_format($summary['jumlah_transaksi'] ?? 0, 0, ',', '.'), 'class' => 'text-dark'],
        ];
    @endphp
    @include('pembukuan.partials.summary-cards', ['cards' => $cards])

    <div class="book-filter">
        <form method="GET" action="{{ route('pembukuan.bku.index') }}" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label fw-semibold small">Tanggal Awal</label>
                <input type="date" name="start_date" class="form-control" value="{{ $filters['start_date'] }}">
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold small">Tanggal Akhir</label>
                <input type="date" name="end_date" class="form-control" value="{{ $filters['end_date'] }}">
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold small">Rekening Bank</label>
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
                <label class="form-label fw-semibold small">Arus Kas</label>
                <select name="arus_kas" class="form-select">
                    <option value="">Semua</option>
                    <option value="DEBIT_MASUK" @selected($filters['arus_kas'] === 'DEBIT_MASUK')>Debit Masuk</option>
                    <option value="KREDIT_KELUAR" @selected($filters['arus_kas'] === 'KREDIT_KELUAR')>Kredit Keluar</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold small">Sumber Transaksi</label>
                <select name="sumber_transaksi" class="form-select">
                    <option value="">Semua</option>
                    <option value="pengeluaran" @selected($filters['sumber_transaksi'] === 'pengeluaran')>Pengeluaran</option>
                    <option value="penerimaan" @selected($filters['sumber_transaksi'] === 'penerimaan')>Penerimaan</option>
                </select>
            </div>
            <div class="col-12 d-flex gap-2">
                <button class="btn btn-primary"><i class="bi bi-funnel me-1"></i>Filter</button>
                <a href="{{ route('pembukuan.bku.index') }}" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>

    <div class="card book-card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <h6 class="mb-0 fw-bold">Daftar Transaksi BKU</h6>
                <small class="text-muted">Data langsung dari tabel `buku_kas_umum` beserta referensi pengeluaran / penerimaan.</small>
            </div>
            <span class="badge bg-primary rounded-pill">{{ $entries->count() }} baris</span>
        </div>
        <div class="card-body p-0">
            @if($entries->isEmpty())
                @include('pembukuan.partials.empty-state', ['title' => 'Belum ada transaksi BKU', 'message' => 'Ubah filter atau pastikan data BKU sudah tercatat.'])
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 book-table">
                        <thead class="table-light">
                            <tr>
                                <th>Tanggal</th>
                                <th>Nomor Bukti</th>
                                <th>Uraian</th>
                                <th>Rekening Sumber</th>
                                <th>Arus Kas</th>
                                <th class="text-end">Nominal</th>
                                <th class="text-end">Saldo Akhir</th>
                                <th>Referensi</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($entries as $entry)
                                <tr>
                                    <td>{{ optional($entry->tanggal_transaksi)->format('d M Y') }}</td>
                                    <td class="fw-semibold">{{ $entry->nomor_bukti }}</td>
                                    <td>
                                        <div class="fw-semibold text-dark">{{ $entry->uraian }}</div>
                                        <div class="small text-muted">
                                            {{ $entry->referensiPengeluaran ? 'Sumber: Pengeluaran' : ($entry->referensiPenerimaan ? 'Sumber: Penerimaan' : 'Sumber: Manual/Sistem') }}
                                        </div>
                                    </td>
                                    <td>
                                        <div>{{ $entry->sumberRekening?->nama_bank ?? '-' }}</div>
                                        <div class="small text-muted">{{ $entry->sumberRekening?->nomor_rekening ?? '-' }}</div>
                                    </td>
                                    <td>@include('pembukuan.partials.status-badge', ['value' => $entry->arus_kas])</td>
                                    <td class="text-end fw-bold">Rp {{ number_format($entry->nominal, 0, ',', '.') }}</td>
                                    <td class="text-end">Rp {{ number_format($entry->saldo_akhir, 0, ',', '.') }}</td>
                                    <td>
                                        @if($entry->referensiPengeluaran)
                                            <div class="fw-semibold">{{ $entry->referensiPengeluaran->nomor_tagihan ?? '-' }}</div>
                                            <div class="small text-muted">{{ $entry->referensiPengeluaran->pihak?->nama_pihak ?? 'Tagihan pengeluaran' }}</div>
                                        @elseif($entry->referensiPenerimaan)
                                            <div class="fw-semibold">{{ $entry->referensiPenerimaan->nomor_invoice ?? '-' }}</div>
                                            <div class="small text-muted">{{ $entry->referensiPenerimaan->mitra?->nama_pihak ?? 'Transaksi penerimaan' }}</div>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <a href="{{ route('pembukuan.bku.show', $entry->id) }}" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-eye me-1"></i>Detail
                                        </a>
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
