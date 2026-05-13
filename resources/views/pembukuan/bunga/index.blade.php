@extends('layouts.app')
@section('title', 'Buku Pembantu Bunga Rekening')

@include('pembukuan.partials.styles')

@section('content')
    <x-page-title title="Pembukuan" subtitle="Buku Pembantu Bunga Rekening" />

    @php
        $periodeLabel = '';
        if (!empty($filters['start_date']) || !empty($filters['end_date'])) {
            $start = $filters['start_date'] ? \Carbon\Carbon::parse($filters['start_date'])->format('d M Y') : '—';
            $end   = $filters['end_date']   ? \Carbon\Carbon::parse($filters['end_date'])->format('d M Y')   : '—';
            $periodeLabel = $start . ' s.d ' . $end;
        }

        $rekeningAktif = null;
        if (!empty($filters['rekening_bank_id'])) {
            $rekeningAktif = $rekeningOptions->firstWhere('id', (int) $filters['rekening_bank_id']);
        }

        $saldoAwal = (float) ($summary['saldo_awal'] ?? 0);
        $saldoAkhir = (float) ($summary['saldo_akhir'] ?? 0);
        $totalPenerimaan = (float) ($summary['total_penerimaan'] ?? 0);
        $totalPengeluaran = (float) ($summary['total_pengeluaran'] ?? 0);
    @endphp

    <div class="book-hero">
        <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 align-items-lg-center">
            <div>
                <div class="text-muted small fw-semibold">BLU Kantor UPBU A.P.T. Pranoto Samarinda</div>
                <h4 class="mb-1 fw-bold text-dark">BUKU PEMBANTU BUNGA REKENING</h4>
                <div class="text-muted small">
                    <span class="me-3"><strong>Kode Buku:</strong> 9</span>
                    @if($periodeLabel)<span class="me-3"><strong>Periode:</strong> {{ $periodeLabel }}</span>@endif
                    @if($rekeningAktif)<span><strong>Rekening:</strong> {{ $rekeningAktif->nama_bank }} - {{ $rekeningAktif->nomor_rekening }}</span>@endif
                </div>
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
            ['label' => 'Saldo Awal',       'value' => 'Rp ' . number_format($saldoAwal, 2, ',', '.'),       'class' => 'text-secondary'],
            ['label' => 'Total Penerimaan', 'value' => 'Rp ' . number_format($totalPenerimaan, 2, ',', '.'),  'class' => 'text-success'],
            ['label' => 'Total Pengeluaran','value' => 'Rp ' . number_format($totalPengeluaran, 2, ',', '.'), 'class' => 'text-danger'],
            ['label' => 'Saldo Akhir',      'value' => 'Rp ' . number_format($saldoAkhir, 2, ',', '.'),       'class' => 'text-primary'],
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
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold">Ledger Bunga Rekening</h6>
            <span class="small text-muted">{{ $summary['jumlah_transaksi'] ?? 0 }} transaksi pada periode ini</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 book-table">
                    <thead class="table-light">
                        <tr>
                            <th style="min-width: 110px;">Tanggal</th>
                            <th>No Bukti</th>
                            <th>Uraian Transaksi</th>
                            <th class="text-end" style="min-width: 150px;">Penerimaan</th>
                            <th class="text-end" style="min-width: 150px;">Pengeluaran</th>
                            <th class="text-end" style="min-width: 150px;">Saldo</th>
                            <th style="min-width: 130px;">Status BKU</th>
                        </tr>
                    </thead>
                    <tbody>
                        {{-- Baris Saldo Awal --}}
                        <tr class="table-light fw-semibold">
                            <td>{{ $filters['start_date'] ? \Carbon\Carbon::parse($filters['start_date'])->format('d M Y') : '-' }}</td>
                            <td>—</td>
                            <td>SALDO AWAL BULAN BERJALAN</td>
                            <td class="text-end text-success">Rp {{ number_format($saldoAwal, 2, ',', '.') }}</td>
                            <td class="text-end">—</td>
                            <td class="text-end">Rp {{ number_format($saldoAwal, 2, ',', '.') }}</td>
                            <td>—</td>
                        </tr>

                        @if($entries->isEmpty())
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    Tidak ada mutasi bunga pada filter yang dipilih.
                                </td>
                            </tr>
                        @else
                            @foreach($entries as $entry)
                                @php
                                    $matchedBku = $matchedBkuMap[$entry->id] ?? null;
                                    $kategori = $entry->kategori_mutasi instanceof \App\Enums\KategoriMutasiBank
                                        ? $entry->kategori_mutasi->label()
                                        : null;
                                @endphp
                                <tr>
                                    <td>{{ optional($entry->tanggal_transaksi)->format('d M Y') }}</td>
                                    <td class="font-monospace small">{{ $entry->nomor_referensi_bank ?? '—' }}</td>
                                    <td>
                                        <div>{{ $entry->deskripsi ?? '-' }}</div>
                                        <div class="small text-muted">
                                            {{ $entry->importMutasiBank?->rekeningBank?->nama_bank ?? '' }}
                                            @if($entry->importMutasiBank?->rekeningBank?->nomor_rekening) · {{ $entry->importMutasiBank->rekeningBank->nomor_rekening }}@endif
                                            @if($kategori) · <span class="badge bg-light text-dark border">{{ $kategori }}</span>@endif
                                        </div>
                                    </td>
                                    <td class="text-end text-success">
                                        {{ $entry->nominal_penerimaan > 0 ? 'Rp ' . number_format($entry->nominal_penerimaan, 2, ',', '.') : '—' }}
                                    </td>
                                    <td class="text-end text-danger">
                                        {{ $entry->nominal_pengeluaran > 0 ? 'Rp ' . number_format($entry->nominal_pengeluaran, 2, ',', '.') : '—' }}
                                    </td>
                                    <td class="text-end fw-bold">Rp {{ number_format($entry->saldo_berjalan, 2, ',', '.') }}</td>
                                    <td>
                                        @if($matchedBku)
                                            @include('pembukuan.partials.status-badge', ['value' => 'SUDAH_MASUK_BKU'])
                                        @else
                                            @include('pembukuan.partials.status-badge', ['value' => 'BELUM_MASUK_BKU'])
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        @endif
                    </tbody>
                    <tfoot class="table-light">
                        <tr class="fw-bold">
                            <td colspan="3" class="text-end">TOTAL</td>
                            <td class="text-end text-success">Rp {{ number_format($totalPenerimaan, 2, ',', '.') }}</td>
                            <td class="text-end text-danger">Rp {{ number_format($totalPengeluaran, 2, ',', '.') }}</td>
                            <td class="text-end">Rp {{ number_format($saldoAkhir, 2, ',', '.') }}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
@endsection
