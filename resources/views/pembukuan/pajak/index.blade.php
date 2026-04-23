@extends('layouts.app')
@section('title', 'Buku Pembantu Pajak')

@include('pembukuan.partials.styles')

@section('content')
    <x-page-title title="Pembukuan" subtitle="Buku Pembantu Pajak" />

    <div class="book-hero">
        <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 align-items-lg-center">
            <div>
                <h4 class="mb-1 fw-bold text-dark">Buku Pembantu Pajak</h4>
                <div class="text-muted">Ledger pajak yang dipotong dari tagihan, termasuk status billing, NTPN, dan jejak dokumen pencairan terkait.</div>
            </div>
            <div>
                <a href="{{ route('pembukuan.pajak.pdf', request()->query()) }}" target="_blank" class="btn btn-outline-danger">
                    <i class="bi bi-file-earmark-pdf me-1"></i> Export PDF
                </a>
            </div>
        </div>
    </div>

    @php
        $cards = [
            ['label' => 'Total Potongan Pajak', 'value' => 'Rp ' . number_format($summary['total_potongan'] ?? 0, 0, ',', '.'), 'class' => 'text-danger'],
            ['label' => 'Total Sudah Billing', 'value' => 'Rp ' . number_format($summary['sudah_billing'] ?? 0, 0, ',', '.'), 'class' => 'text-warning'],
            ['label' => 'Total Sudah Setor', 'value' => 'Rp ' . number_format($summary['sudah_setor'] ?? 0, 0, ',', '.'), 'class' => 'text-success'],
            ['label' => 'Total Belum Setor', 'value' => 'Rp ' . number_format($summary['belum_setor'] ?? 0, 0, ',', '.'), 'class' => 'text-secondary'],
        ];
    @endphp
    @include('pembukuan.partials.summary-cards', ['cards' => $cards])

    <div class="book-filter">
        <form method="GET" action="{{ route('pembukuan.pajak.index') }}" class="row g-3 align-items-end">
            <div class="col-md-2"><label class="form-label small fw-semibold">Tanggal Awal</label><input type="date" name="start_date" class="form-control" value="{{ $filters['start_date'] }}"></div>
            <div class="col-md-2"><label class="form-label small fw-semibold">Tanggal Akhir</label><input type="date" name="end_date" class="form-control" value="{{ $filters['end_date'] }}"></div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Jenis Tagihan</label>
                <select name="jenis_tagihan" class="form-select">
                    <option value="">Semua</option>
                    @foreach($jenisTagihanOptions as $jenis)
                        <option value="{{ $jenis }}" @selected($filters['jenis_tagihan'] === $jenis)>{{ $jenis }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3"><label class="form-label small fw-semibold">Jenis Pajak / Potongan</label><input type="text" name="jenis_pajak" class="form-control" value="{{ $filters['jenis_pajak'] }}" placeholder="PPN, PPh, dll"></div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Status Billing / Setor</label>
                <select name="status_billing_setor" class="form-select">
                    <option value="">Semua</option>
                    <option value="BELUM_SETOR" @selected($filters['status_billing_setor'] === 'BELUM_SETOR')>Belum Setor</option>
                    <option value="SUDAH_BILLING" @selected($filters['status_billing_setor'] === 'SUDAH_BILLING')>Sudah Billing</option>
                    <option value="SUDAH_SETOR" @selected($filters['status_billing_setor'] === 'SUDAH_SETOR')>Sudah Setor</option>
                </select>
            </div>
            <div class="col-12 d-flex gap-2">
                <button class="btn btn-primary"><i class="bi bi-funnel me-1"></i>Filter</button>
                <a href="{{ route('pembukuan.pajak.index') }}" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>

    <div class="card book-card">
        <div class="card-header"><h6 class="mb-0 fw-bold">Daftar Ledger Pajak</h6></div>
        <div class="card-body p-0">
            @if($entries->isEmpty())
                @include('pembukuan.partials.empty-state', ['title' => 'Belum ada data pajak', 'message' => 'Tidak ada potongan tagihan yang sesuai filter.'])
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 book-table">
                        <thead class="table-light">
                            <tr>
                                <th>Nomor Tagihan</th>
                                <th>Tipe Tagihan</th>
                                <th>Jenis Pajak</th>
                                <th class="text-end">Nominal Potongan</th>
                                <th>Nomor SP2D</th>
                                <th>Kode Billing</th>
                                <th>NTPN</th>
                                <th>Status</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($entries as $entry)
                                @php
                                    $spp = $entry->tagihan?->spps?->sortByDesc('tanggal_spp')->first();
                                    $sp2d = $spp?->spm?->npi?->sp2d;
                                    $statusLabel = $entry->ntpn ? 'SUDAH_SETOR' : ($entry->kode_billing ? 'SUDAH_BILLING' : 'BELUM_SETOR');
                                @endphp
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $entry->tagihan?->nomor_tagihan ?? '-' }}</div>
                                        <div class="small text-muted">{{ optional($entry->created_at)->format('d M Y') }}</div>
                                    </td>
                                    <td>{{ $entry->tagihan?->tipe_tagihan ?? '-' }}</td>
                                    <td>
                                        <div>{{ $entry->nama_pajak_snapshot ?? $entry->jenis_potongan }}</div>
                                        <div class="small text-muted">{{ $entry->jenis_potongan }}</div>
                                    </td>
                                    <td class="text-end fw-bold">Rp {{ number_format($entry->nominal_potongan, 0, ',', '.') }}</td>
                                    <td>{{ $sp2d?->nomor_sp2d ?? '-' }}</td>
                                    <td>{{ $entry->kode_billing ?? '-' }}</td>
                                    <td>{{ $entry->ntpn ?? '-' }}</td>
                                    <td>@include('pembukuan.partials.status-badge', ['value' => $statusLabel])</td>
                                    <td class="text-center">
                                        <a href="{{ route('pembukuan.pajak.show', $entry->id) }}" class="btn btn-sm btn-outline-primary">Detail</a>
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
