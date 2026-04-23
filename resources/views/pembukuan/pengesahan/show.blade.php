@extends('layouts.app')
@section('title', 'Detail Buku Pengesahan Belanja')

@include('pembukuan.partials.styles')

@section('content')
    <x-page-title title="Pembukuan" subtitle="Detail Buku Pengesahan Belanja" />

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-1 fw-bold text-dark">{{ $report->nomor_laporan }}</h4>
            <div class="text-muted">{{ $months[$report->periode_bulan] ?? $report->periode_bulan }} {{ $report->tahun }}</div>
        </div>
        <a href="{{ route('pembukuan.pengesahan.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Kembali</a>
    </div>

    <div class="book-meta mb-4">
        <div class="book-meta-item">
            <div class="meta-label">Status Pengesahan</div>
            <div class="meta-value">@include('pembukuan.partials.status-badge', ['value' => $report->status_pengesahan])</div>
        </div>
        <div class="book-meta-item">
            <div class="meta-label">Status SP3B</div>
            <div class="meta-value">{{ $report->status_sp3b ?? '-' }}</div>
        </div>
        <div class="book-meta-item">
            <div class="meta-label">Approver / KPA</div>
            <div class="meta-value">{{ $report->approver?->name ?? '-' }}</div>
        </div>
        <div class="book-meta-item">
            <div class="meta-label">Saldo Akhir</div>
            <div class="meta-value">Rp {{ number_format($report->saldo_akhir_blu, 0, ',', '.') }}</div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-xl-7">
            <div class="card book-card mb-4">
                <div class="card-header"><h6 class="mb-0 fw-bold">Ringkasan Periode</h6></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4"><strong>Total Penerimaan</strong><div class="text-muted">Rp {{ number_format($report->total_penerimaan, 0, ',', '.') }}</div></div>
                        <div class="col-md-4"><strong>Total Pengeluaran</strong><div class="text-muted">Rp {{ number_format($report->total_pengeluaran, 0, ',', '.') }}</div></div>
                        <div class="col-md-4"><strong>Saldo Akhir BLU</strong><div class="text-muted">Rp {{ number_format($report->saldo_akhir_blu, 0, ',', '.') }}</div></div>
                        <div class="col-md-6"><strong>Periode Awal</strong><div class="text-muted">{{ $periodStart->format('d M Y') }}</div></div>
                        <div class="col-md-6"><strong>Periode Akhir</strong><div class="text-muted">{{ $periodEnd->format('d M Y') }}</div></div>
                    </div>
                </div>
            </div>

            <div class="card book-card mb-4">
                <div class="card-header"><h6 class="mb-0 fw-bold">Ringkasan Sumber Data</h6></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Sumber</th>
                                    <th class="text-end">Jumlah Data</th>
                                    <th class="text-end">Total Nilai</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($sourceSummary as $key => $item)
                                    <tr>
                                        <td>{{ \Illuminate\Support\Str::headline(str_replace('_', ' ', $key)) }}</td>
                                        <td class="text-end">{{ number_format($item['count'] ?? 0, 0, ',', '.') }}</td>
                                        <td class="text-end">Rp {{ number_format($item['total'] ?? 0, 0, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card book-card">
                <div class="card-header"><h6 class="mb-0 fw-bold">Lampiran</h6></div>
                <div class="card-body">
                    @if($report->arsipDokumen->isEmpty())
                        @include('pembukuan.partials.empty-state', ['title' => 'Belum ada lampiran', 'message' => 'Tidak ada arsip dokumen yang terhubung dengan laporan pengesahan ini.'])
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Jenis</th>
                                        <th>Nama File</th>
                                        <th>Uploader</th>
                                        <th>Waktu</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($report->arsipDokumen as $arsip)
                                        <tr>
                                            <td>{{ $arsip->jenis_dokumen }}</td>
                                            <td>{{ $arsip->nama_file_asli }}</td>
                                            <td>{{ $arsip->uploader?->name ?? '-' }}</td>
                                            <td>{{ optional($arsip->uploaded_at)->format('d M Y H:i') ?? '-' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-xl-5">
            <div class="card book-card">
                <div class="card-header"><h6 class="mb-0 fw-bold">Timeline / Riwayat Perubahan</h6></div>
                <div class="card-body">
                    @if($timeline->isEmpty())
                        @include('pembukuan.partials.empty-state', ['title' => 'Belum ada timeline', 'message' => 'Riwayat perubahan belum tersedia untuk laporan ini.'])
                    @else
                        <div class="book-timeline">
                            @foreach($timeline as $item)
                                <div class="book-timeline-item">
                                    <div class="book-timeline-dot"></div>
                                    <div class="fw-semibold">{{ $item['label'] }}</div>
                                    <div class="small text-muted">{{ $item['actor'] ?? '-' }} · {{ optional($item['time'])->format('d M Y H:i') ?? '-' }}</div>
                                    <div class="small mt-1">{{ $item['description'] ?? '-' }}</div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
