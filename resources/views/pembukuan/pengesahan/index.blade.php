@extends('layouts.app')
@section('title', 'Buku Pengesahan Belanja')

@include('pembukuan.partials.styles')

@section('content')
    <x-page-title title="Pembukuan" subtitle="Buku Pengesahan Belanja" />

    <div class="book-hero">
        <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 align-items-lg-center">
            <div>
                <h4 class="mb-1 fw-bold text-dark">Buku Pengesahan Belanja</h4>
                <div class="text-muted">Laporan pengesahan belanja BLU per periode berdasarkan `laporan_pengesahan_blu` dan ringkasan data sumber existing.</div>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalGeneratePengesahan">
                    <i class="bi bi-journal-plus me-1"></i> Buat Laporan Periode
                </button>
                <a href="{{ route('pembukuan.pengesahan.pdf', request()->query()) }}" target="_blank" class="btn btn-outline-danger">
                    <i class="bi bi-file-earmark-pdf me-1"></i> Export PDF
                </a>
            </div>
        </div>
    </div>

    @php
        $cards = [
            ['label' => 'Total Laporan', 'value' => number_format($summary['total_laporan'] ?? 0, 0, ',', '.'), 'class' => 'text-dark'],
            ['label' => 'Draft', 'value' => number_format($summary['draft'] ?? 0, 0, ',', '.'), 'class' => 'text-secondary'],
            ['label' => 'Verifikasi KPPN', 'value' => number_format($summary['verifikasi_kppn'] ?? 0, 0, ',', '.'), 'class' => 'text-warning'],
            ['label' => 'Disahkan', 'value' => number_format($summary['disahkan'] ?? 0, 0, ',', '.'), 'class' => 'text-success'],
        ];
    @endphp
    @include('pembukuan.partials.summary-cards', ['cards' => $cards])

    <div class="book-filter">
        <form method="GET" action="{{ route('pembukuan.pengesahan.index') }}" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Bulan</label>
                <select name="bulan" class="form-select">
                    <option value="">Semua Bulan</option>
                    @foreach($months as $number => $label)
                        <option value="{{ $number }}" @selected((string) $filters['bulan'] === (string) $number)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3"><label class="form-label small fw-semibold">Tahun</label><input type="number" name="tahun" class="form-control" value="{{ $filters['tahun'] }}"></div>
            <div class="col-md-4">
                <label class="form-label small fw-semibold">Status Pengesahan</label>
                <select name="status_pengesahan" class="form-select">
                    <option value="">Semua</option>
                    <option value="DRAFT" @selected($filters['status_pengesahan'] === 'DRAFT')>Draft</option>
                    <option value="VERIFIKASI_KPPN" @selected($filters['status_pengesahan'] === 'VERIFIKASI_KPPN')>Verifikasi KPPN</option>
                    <option value="DISAHKAN" @selected($filters['status_pengesahan'] === 'DISAHKAN')>Disahkan</option>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button class="btn btn-primary"><i class="bi bi-funnel me-1"></i>Filter</button>
                <a href="{{ route('pembukuan.pengesahan.index') }}" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>

    <div class="card book-card">
        <div class="card-header"><h6 class="mb-0 fw-bold">Daftar Laporan Pengesahan BLU</h6></div>
        <div class="card-body p-0">
            @if($reports->isEmpty())
                @include('pembukuan.partials.empty-state', ['title' => 'Belum ada laporan pengesahan', 'message' => 'Tidak ada laporan pengesahan BLU yang sesuai filter.'])
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 book-table">
                        <thead class="table-light">
                            <tr>
                                <th>Nomor Laporan</th>
                                <th>Periode</th>
                                <th class="text-end">Total Penerimaan</th>
                                <th class="text-end">Total Pengeluaran</th>
                                <th class="text-end">Saldo Akhir BLU</th>
                                <th>Status Pengesahan</th>
                                <th>Status SP3B</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($reports as $report)
                                <tr>
                                    <td>{{ $report->nomor_laporan }}</td>
                                    <td>{{ $months[$report->periode_bulan] ?? $report->periode_bulan }} {{ $report->tahun }}</td>
                                    <td class="text-end">Rp {{ number_format($report->total_penerimaan, 0, ',', '.') }}</td>
                                    <td class="text-end">Rp {{ number_format($report->total_pengeluaran, 0, ',', '.') }}</td>
                                    <td class="text-end fw-bold">Rp {{ number_format($report->saldo_akhir_blu, 0, ',', '.') }}</td>
                                    <td>@include('pembukuan.partials.status-badge', ['value' => $report->status_pengesahan])</td>
                                    <td>{{ $report->status_sp3b ?? '-' }}</td>
                                    <td class="text-center"><a href="{{ route('pembukuan.pengesahan.show', $report->id) }}" class="btn btn-sm btn-outline-primary">Detail</a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    @include('pembukuan.partials.modal-generate-pengesahan', ['months' => $months])
@endsection
