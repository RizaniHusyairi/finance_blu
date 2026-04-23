@extends('layouts.app')
@section('title', 'Rekonsiliasi Buku Pembantu Bank')

@include('pembukuan.partials.styles')

@section('content')
    <x-page-title title="Pembukuan" subtitle="Rekonsiliasi Bank" />

    @php
        $cards = [
            ['label' => 'Matched', 'value' => number_format($summary['matched'] ?? 0, 0, ',', '.'), 'class' => 'text-success'],
            ['label' => 'Partial', 'value' => number_format($summary['partial'] ?? 0, 0, ',', '.'), 'class' => 'text-warning'],
            ['label' => 'Selisih', 'value' => number_format($summary['selisih'] ?? 0, 0, ',', '.'), 'class' => 'text-danger'],
            ['label' => 'Manual Override', 'value' => number_format($summary['manual'] ?? 0, 0, ',', '.'), 'class' => 'text-primary'],
        ];
    @endphp
    @include('pembukuan.partials.summary-cards', ['cards' => $cards])

    <div class="book-filter">
        <form method="GET" action="{{ route('pembukuan.bank.rekonsiliasi') }}" class="row g-3 align-items-end">
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
            <div class="col-md-2"><label class="form-label small fw-semibold">Tanggal Awal</label><input type="date" name="start_date" class="form-control" value="{{ $filters['start_date'] }}"></div>
            <div class="col-md-2"><label class="form-label small fw-semibold">Tanggal Akhir</label><input type="date" name="end_date" class="form-control" value="{{ $filters['end_date'] }}"></div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Status</label>
                <select name="status" class="form-select">
                    <option value="">Semua</option>
                    <option value="MATCHED" @selected($filters['status'] === 'MATCHED')>Matched</option>
                    <option value="PARTIAL" @selected($filters['status'] === 'PARTIAL')>Partial</option>
                    <option value="SELISIH" @selected($filters['status'] === 'SELISIH')>Selisih</option>
                    <option value="MANUAL_OVERRIDE" @selected($filters['status'] === 'MANUAL_OVERRIDE')>Manual Override</option>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button class="btn btn-primary"><i class="bi bi-funnel me-1"></i>Filter</button>
                <a href="{{ route('pembukuan.bank.rekonsiliasi') }}" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>

    <div class="card book-card">
        <div class="card-header"><h6 class="mb-0 fw-bold">Pasangan Rekonsiliasi Mutasi - BKU - Tagihan</h6></div>
        <div class="card-body p-0">
            @if($reconciliations->isEmpty())
                @include('pembukuan.partials.empty-state', ['title' => 'Belum ada data rekonsiliasi', 'message' => 'Tidak ada pasangan rekonsiliasi yang cocok dengan filter saat ini.'])
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 book-table">
                        <thead class="table-light">
                            <tr>
                                <th>Mutasi Bank</th>
                                <th>BKU</th>
                                <th>Tagihan / Penerimaan</th>
                                <th class="text-end">Nominal Mutasi</th>
                                <th class="text-end">Nominal Sistem</th>
                                <th class="text-end">Selisih</th>
                                <th>Status</th>
                                <th>Rekonsiliator</th>
                                <th>Histori</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($reconciliations as $item)
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ optional($item->detailMutasiBank?->tanggal_transaksi)->format('d M Y') }}</div>
                                        <div class="small text-muted">{{ $item->detailMutasiBank?->deskripsi ?? '-' }}</div>
                                        <div class="small text-muted">{{ $item->detailMutasiBank?->importMutasiBank?->rekeningBank?->nomor_rekening ?? '-' }}</div>
                                    </td>
                                    <td>
                                        <div>{{ $item->bku?->nomor_bukti ?? '-' }}</div>
                                        <div class="small text-muted">{{ $item->bku?->uraian ?? '-' }}</div>
                                    </td>
                                    <td>
                                        @if($item->tagihan)
                                            <div>{{ $item->tagihan->nomor_tagihan ?? '-' }}</div>
                                            <div class="small text-muted">{{ $item->tagihan->pihak?->nama_pihak ?? '-' }}</div>
                                        @elseif($item->transaksiPenerimaan)
                                            <div>{{ $item->transaksiPenerimaan->nomor_invoice ?? '-' }}</div>
                                            <div class="small text-muted">{{ $item->transaksiPenerimaan->mitra?->nama_pihak ?? '-' }}</div>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-end">Rp {{ number_format($item->nominal_mutasi, 0, ',', '.') }}</td>
                                    <td class="text-end">Rp {{ number_format($item->nominal_sistem, 0, ',', '.') }}</td>
                                    <td class="text-end {{ (float) $item->selisih === 0.0 ? 'text-success' : 'text-danger' }}">Rp {{ number_format($item->selisih, 0, ',', '.') }}</td>
                                    <td>@include('pembukuan.partials.status-badge', ['value' => $item->status])</td>
                                    <td>
                                        <div>{{ $item->direkonsiliasiOleh?->name ?? '-' }}</div>
                                        <div class="small text-muted">{{ optional($item->direkonsiliasi_pada)->format('d M Y H:i') ?? '-' }}</div>
                                    </td>
                                    <td class="small text-muted">
                                        @forelse($item->logs as $log)
                                            <div>{{ optional($log->created_at)->format('d/m H:i') }} · {{ $log->aksi }} · {{ $log->user?->name ?? 'Sistem' }}</div>
                                        @empty
                                            Belum ada histori
                                        @endforelse
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
