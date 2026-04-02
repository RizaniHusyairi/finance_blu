@extends('layouts.app')
@section('title')
    Laporan BKU
@endsection
@section('content')
    <x-page-title title="Buku Kas Umum (BKU)" subtitle="Laporan" />

    {{-- Filter Bar --}}
    <div class="card rounded-4">
        <div class="card-body">
            <form action="{{ route('reports.bku') }}" method="GET" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label fw-bold">Tahun</label>
                    <select name="year" class="form-select">
                        @for($y = date('Y'); $y >= 2020; $y--)
                            <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endfor
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Bulan</label>
                    <select name="month" class="form-select">
                        <option value="">-- Semua Bulan --</option>
                        @foreach($filterMonths as $num => $name)
                            <option value="{{ $num }}" {{ $month == $num ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Akun / MAK</label>
                    <select name="budget_id" class="form-select">
                        <option value="">-- Semua Akun --</option>
                        @foreach($budgets as $b)
                            <option value="{{ $b->id }}" {{ $budgetId == $b->id ? 'selected' : '' }}>{{ $b->coa }} - {{ $b->description }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary me-2"><i class="bi bi-funnel"></i> Filter</button>
                    <a href="{{ route('reports.bku.pdf', request()->query()) }}" target="_blank" class="btn btn-outline-danger"><i class="bi bi-file-earmark-pdf"></i> Export PDF</a>
                </div>
            </form>
        </div>
    </div>

    {{-- Summary Cards --}}
    <div class="row">
        <div class="col-md-4">
            <div class="card rounded-4 border-start border-4 border-primary">
                <div class="card-body">
                    <p class="text-muted mb-1 small">Pagu Anggaran</p>
                    <h4 class="fw-bold text-primary mb-0">Rp {{ number_format($totalPagu, 0, ',', '.') }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card rounded-4 border-start border-4 border-success">
                <div class="card-body">
                    <p class="text-muted mb-1 small">Total Pencairan (Netto)</p>
                    <h4 class="fw-bold text-success mb-0">Rp {{ number_format($runningDebit, 0, ',', '.') }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card rounded-4 border-start border-4 border-warning">
                <div class="card-body">
                    <p class="text-muted mb-1 small">Sisa Anggaran</p>
                    <h4 class="fw-bold text-warning mb-0">Rp {{ number_format($runningSaldo, 0, ',', '.') }}</h4>
                </div>
            </div>
        </div>
    </div>

    <div class="card rounded-4">
        <div class="card-body">
            <h6 class="fw-bold mb-3">Ringkasan Sumber Data V2</h6>
            <div class="row g-3">
                @foreach($sourceSummary as $label => $summary)
                    <div class="col-md-4">
                        <div class="border rounded-3 p-3 h-100 bg-light">
                            <div class="text-muted small text-uppercase">{{ str_replace('_', ' ', $label) }}</div>
                            <div class="fw-bold fs-5">{{ number_format($summary['count'] ?? 0, 0, ',', '.') }} data</div>
                            <div class="small">
                                Nilai:
                                Rp {{ number_format(($summary['total'] ?? $summary['total_selisih'] ?? 0), 0, ',', '.') }}
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- BKU Table --}}
    <div class="card rounded-4">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold"><i class="bi bi-journal-text"></i> Buku Kas Umum — {{ $month ? $filterMonths[(int)$month] : 'Semua Bulan' }} {{ $year }}</h6>
            <span class="badge bg-primary">{{ count($bkuRows) }} transaksi</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table align-middle mb-0 table-hover table-striped" id="bkuTable">
                    <thead class="table-dark">
                        <tr>
                            <th class="text-center" style="width:40px">No</th>
                            <th>Tanggal</th>
                            <th>No. Transaksi</th>
                            <th>Uraian</th>
                            <th>Penyedia</th>
                            <th>Akun</th>
                            <th class="text-end">Bruto (Rp)</th>
                            <th class="text-end">Pajak (Rp)</th>
                            <th class="text-end">Netto (Rp)</th>
                            <th class="text-end">Saldo (Rp)</th>
                        </tr>
                    </thead>
                    <tbody>
                        {{-- Opening balance row --}}
                        <tr class="table-info">
                            <td colspan="9" class="fw-bold">Saldo Awal (Pagu)</td>
                            <td class="text-end fw-bold">{{ number_format($totalPagu, 0, ',', '.') }}</td>
                        </tr>
                        @forelse($bkuRows as $i => $row)
                        <tr>
                            <td class="text-center">{{ $i + 1 }}</td>
                            <td>{{ \Carbon\Carbon::parse($row['date'])->format('d/m/Y') }}</td>
                            <td><span class="badge bg-light text-dark">{{ $row['transaction_number'] }}</span></td>
                            <td>{{ Str::limit($row['description'], 35) }}</td>
                            <td>{{ $row['supplier'] }}</td>
                            <td><small>{{ $row['budget_coa'] }}</small></td>
                            <td class="text-end">{{ number_format($row['bruto'], 0, ',', '.') }}</td>
                            <td class="text-end text-danger">{{ $row['tax'] > 0 ? number_format($row['tax'], 0, ',', '.') : '-' }}</td>
                            <td class="text-end fw-bold">{{ number_format($row['netto'], 0, ',', '.') }}</td>
                            <td class="text-end">{{ number_format($row['saldo'], 0, ',', '.') }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="10" class="text-center text-muted py-4">
                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                Tidak ada transaksi SP2D pada periode ini.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                    @if(count($bkuRows) > 0)
                    <tfoot class="table-dark">
                        <tr>
                            <th colspan="6" class="text-end">TOTAL</th>
                            <th class="text-end">{{ number_format(collect($bkuRows)->sum('bruto'), 0, ',', '.') }}</th>
                            <th class="text-end">{{ number_format(collect($bkuRows)->sum('tax'), 0, ',', '.') }}</th>
                            <th class="text-end">{{ number_format(collect($bkuRows)->sum('netto'), 0, ',', '.') }}</th>
                            <th class="text-end">{{ number_format($runningSaldo, 0, ',', '.') }}</th>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>
@endsection
