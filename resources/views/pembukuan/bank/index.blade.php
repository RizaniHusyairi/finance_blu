@extends('layouts.app')
@section('title', 'Buku Pembantu Bank')

@include('pembukuan.partials.styles')

@section('content')
    <x-page-title title="Pembukuan" subtitle="Buku Pembantu Bank" />

    <div class="book-hero">
        <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 align-items-lg-center">
            <div>
                <h4 class="mb-1 fw-bold text-dark">Buku Pembantu Bank</h4>
                <div class="text-muted">Log pembayaran internal dan pembanding rekening koran berbasis rekening bank, mutasi, dan data rekonsiliasi existing.</div>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('pembukuan.bank.mutasi', request()->query()) }}" class="btn btn-outline-primary">
                    <i class="bi bi-list-ul me-1"></i> Mutasi Global
                </a>
                <a href="{{ route('pembukuan.bank.rekonsiliasi', request()->query()) }}" class="btn btn-outline-secondary">
                    <i class="bi bi-shuffle me-1"></i> Rekonsiliasi
                </a>
            </div>
        </div>
    </div>

    @php
        $cards = [
            ['label' => 'Total Rekening Aktif', 'value' => number_format($summary['rekening_aktif'] ?? 0, 0, ',', '.'), 'class' => 'text-dark'],
            ['label' => 'Mutasi Belum Rekonsiliasi', 'value' => number_format($summary['belum'] ?? 0, 0, ',', '.'), 'class' => 'text-secondary'],
            ['label' => 'Mutasi Matched', 'value' => number_format($summary['matched'] ?? 0, 0, ',', '.'), 'class' => 'text-success'],
            ['label' => 'Mutasi Selisih', 'value' => number_format($summary['selisih'] ?? 0, 0, ',', '.'), 'class' => 'text-danger'],
        ];
    @endphp
    @include('pembukuan.partials.summary-cards', ['cards' => $cards])

    <div class="book-filter">
        <form method="GET" action="{{ route('pembukuan.bank.index') }}" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Tanggal Awal</label>
                <input type="date" name="start_date" class="form-control" value="{{ $filters['start_date'] }}">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Tanggal Akhir</label>
                <input type="date" name="end_date" class="form-control" value="{{ $filters['end_date'] }}">
            </div>
            <div class="col-md-4">
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
            <div class="col-md-2 d-flex gap-2">
                <button class="btn btn-primary"><i class="bi bi-funnel me-1"></i>Filter</button>
                <a href="{{ route('pembukuan.bank.index') }}" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>

    <div class="card book-card">
        <div class="card-header"><h6 class="mb-0 fw-bold">Daftar Rekening Bank</h6></div>
        <div class="card-body p-0">
            @if($rekeningList->isEmpty())
                @include('pembukuan.partials.empty-state', ['title' => 'Belum ada rekening aktif', 'message' => 'Periksa master rekening bank atau ubah filter yang sedang dipakai.'])
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 book-table">
                        <thead class="table-light">
                            <tr>
                                <th>Nama Bank</th>
                                <th>Nomor Rekening</th>
                                <th>Atas Nama</th>
                                <th class="text-end">Jumlah Mutasi</th>
                                <th>Status Rekonsiliasi Terakhir</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($rekeningList as $rekening)
                                <tr>
                                    <td>{{ $rekening->nama_bank }}</td>
                                    <td class="fw-semibold">{{ $rekening->nomor_rekening }}</td>
                                    <td>{{ $rekening->nama_rekening }}</td>
                                    <td class="text-end">{{ number_format($rekening->jumlah_mutasi ?? 0, 0, ',', '.') }}</td>
                                    <td>@include('pembukuan.partials.status-badge', ['value' => $rekening->status_rekonsiliasi_terakhir])</td>
                                    <td class="text-center">
                                        <div class="d-flex justify-content-center gap-2">
                                            <a href="{{ route('pembukuan.bank.show', $rekening->id) }}" class="btn btn-sm btn-outline-primary">Detail</a>
                                            <a href="{{ route('pembukuan.bank.rekonsiliasi', array_merge(request()->query(), ['rekening_bank_id' => $rekening->id])) }}" class="btn btn-sm btn-outline-secondary">Rekonsiliasi</a>
                                        </div>
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
