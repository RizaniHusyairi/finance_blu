@extends('layouts.app')
@section('title')
    Portal Mitra
@endsection
@section('content')
    <x-page-title title="Portal Mitra" subtitle="Status Kontrak & Pembayaran" />

    @if(!$supplier)
        <div class="alert alert-warning border-0 bg-warning alert-dismissible fade show">
            <div class="text-dark">
                <i class="bi bi-exclamation-triangle"></i> <strong>Akun Anda belum terhubung dengan data Mitra/Penyedia.</strong>
                Hubungi Operator BLU untuk menghubungkan akun Anda.
            </div>
        </div>
    @else
        {{-- Summary Cards --}}
        <div class="row">
            <div class="col-xl-3 col-md-6 d-flex align-items-stretch">
                <div class="card w-100 rounded-4">
                    <div class="card-body">
                        <div class="d-flex align-items-center gap-3 mb-2">
                            <div class="wh-42 d-flex align-items-center justify-content-center rounded-circle bg-primary bg-opacity-10 text-primary">
                                <span class="material-icons-outlined fs-5">business</span>
                            </div>
                            <div>
                                <p class="mb-0 text-muted small">Total Kontrak</p>
                                <h4 class="mb-0 fw-bold">{{ $totalKontrak }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 d-flex align-items-stretch">
                <div class="card w-100 rounded-4">
                    <div class="card-body">
                        <div class="d-flex align-items-center gap-3 mb-2">
                            <div class="wh-42 d-flex align-items-center justify-content-center rounded-circle bg-info bg-opacity-10 text-info">
                                <span class="material-icons-outlined fs-5">request_quote</span>
                            </div>
                            <div>
                                <p class="mb-0 text-muted small">Nilai Kontrak</p>
                                <h4 class="mb-0 fw-bold">Rp {{ number_format($totalNilaiKontrak, 0, ',', '.') }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 d-flex align-items-stretch">
                <div class="card w-100 rounded-4">
                    <div class="card-body">
                        <div class="d-flex align-items-center gap-3 mb-2">
                            <div class="wh-42 d-flex align-items-center justify-content-center rounded-circle bg-success bg-opacity-10 text-success">
                                <span class="material-icons-outlined fs-5">paid</span>
                            </div>
                            <div>
                                <p class="mb-0 text-muted small">Sudah Dibayar</p>
                                <h4 class="mb-0 fw-bold text-success">Rp {{ number_format($totalDibayar, 0, ',', '.') }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 d-flex align-items-stretch">
                <div class="card w-100 rounded-4">
                    <div class="card-body">
                        <div class="d-flex align-items-center gap-3 mb-2">
                            <div class="wh-42 d-flex align-items-center justify-content-center rounded-circle bg-warning bg-opacity-10 text-warning">
                                <span class="material-icons-outlined fs-5">hourglass_top</span>
                            </div>
                            <div>
                                <p class="mb-0 text-muted small">Dalam Proses</p>
                                <h4 class="mb-0 fw-bold text-warning">Rp {{ number_format($totalPending, 0, ',', '.') }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Contracts --}}
        <div class="card rounded-4">
            <div class="card-header bg-light">
                <h6 class="mb-0 fw-bold"><i class="bi bi-file-earmark-text"></i> Daftar Kontrak Anda</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>No. Kontrak</th>
                                <th>Judul Pekerjaan</th>
                                <th>Masa Kontrak</th>
                                <th class="text-end">Nilai Kontrak</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($contracts as $c)
                            <tr>
                                <td>{{ $c->contract_number }}</td>
                                <td>{{ $c->description }}</td>
                                <td>{{ \Carbon\Carbon::parse($c->start_date)->format('d/m/Y') }} – {{ \Carbon\Carbon::parse($c->end_date)->format('d/m/Y') }}</td>
                                <td class="text-end">Rp {{ number_format($c->total_amount, 0, ',', '.') }}</td>
                                <td><span class="badge bg-{{ $c->status == 'Active' ? 'success' : 'secondary' }}">{{ $c->status }}</span></td>
                            </tr>
                            @empty
                            <tr><td colspan="5" class="text-center text-muted py-3">Tidak ada kontrak.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Transactions / Payment Timeline --}}
        <div class="card rounded-4">
            <div class="card-header bg-light">
                <h6 class="mb-0 fw-bold"><i class="bi bi-receipt-cutoff"></i> Status Pembayaran Anda</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>No. Transaksi</th>
                                <th>Tanggal</th>
                                <th>Uraian</th>
                                <th>Status</th>
                                <th class="text-end">Bruto</th>
                                <th class="text-end">Potongan Pajak</th>
                                <th class="text-end">Netto</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($transactions as $t)
                            @php
                                $taxTotal = $t->taxes->sum('tax_amount');
                                $netto = $t->amount - $taxTotal;
                                $sc = match($t->status) {
                                    'Draft' => 'warning', 'Verified' => 'info',
                                    'Approved SPP', 'Approved SPM' => 'primary',
                                    'Paid SP2D' => 'success', 'Rejected' => 'danger',
                                    default => 'secondary'
                                };
                            @endphp
                            <tr>
                                <td>{{ $t->transaction_number }}</td>
                                <td>{{ \Carbon\Carbon::parse($t->date)->format('d/m/Y') }}</td>
                                <td>{{ Str::limit($t->description, 40) }}</td>
                                <td>
                                    <span class="badge bg-{{ $sc }}">{{ $t->status }}</span>
                                    @if($t->status == 'Paid SP2D')
                                        <i class="bi bi-check-circle-fill text-success ms-1"></i>
                                    @endif
                                </td>
                                <td class="text-end">Rp {{ number_format($t->amount, 0, ',', '.') }}</td>
                                <td class="text-end text-danger">{{ $taxTotal > 0 ? '- Rp ' . number_format($taxTotal, 0, ',', '.') : '-' }}</td>
                                <td class="text-end fw-bold">Rp {{ number_format($netto, 0, ',', '.') }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="7" class="text-center text-muted py-3">Belum ada transaksi pembayaran.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Simple info box --}}
        <div class="alert alert-info border-0 bg-light-info">
            <i class="bi bi-info-circle"></i> Halaman ini menampilkan data kontrak dan pembayaran yang terkait dengan akun Anda (<strong>{{ $supplier->name }}</strong>). 
            Untuk pertanyaan lebih lanjut, silakan hubungi Operator BLU.
        </div>
    @endif
@endsection
