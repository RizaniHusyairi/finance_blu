@extends('layouts.app')
@section('title', 'Detail Buku Kas Umum')

@include('pembukuan.partials.styles')

@section('content')
    <x-page-title title="Pembukuan" subtitle="Detail Buku Kas Umum" />

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-1 fw-bold text-dark">Detail Transaksi BKU</h4>
            <div class="text-muted">{{ $entry->nomor_bukti }} · {{ optional($entry->tanggal_transaksi)->format('d M Y') }}</div>
        </div>
        <a href="{{ route('pembukuan.bku.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Kembali
        </a>
    </div>

    <div class="book-meta mb-4">
        <div class="book-meta-item">
            <div class="meta-label">Arus Kas</div>
            <div class="meta-value">@include('pembukuan.partials.status-badge', ['value' => $entry->arus_kas])</div>
        </div>
        <div class="book-meta-item">
            <div class="meta-label">Nominal</div>
            <div class="meta-value">Rp {{ number_format($entry->nominal, 0, ',', '.') }}</div>
        </div>
        <div class="book-meta-item">
            <div class="meta-label">Saldo Akhir</div>
            <div class="meta-value">Rp {{ number_format($entry->saldo_akhir, 0, ',', '.') }}</div>
        </div>
        <div class="book-meta-item">
            <div class="meta-label">Sumber Transaksi</div>
            <div class="meta-value">{{ $tagihan ? 'Pengeluaran' : ($penerimaan ? 'Penerimaan' : 'Sistem') }}</div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-xl-7">
            <div class="card book-card mb-4">
                <div class="card-header"><h6 class="mb-0 fw-bold">Informasi BKU</h6></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6"><strong>Tanggal Transaksi</strong><div class="text-muted">{{ optional($entry->tanggal_transaksi)->format('d M Y') ?? '-' }}</div></div>
                        <div class="col-md-6"><strong>Nomor Bukti</strong><div class="text-muted">{{ $entry->nomor_bukti }}</div></div>
                        <div class="col-md-12"><strong>Uraian</strong><div class="text-muted">{{ $entry->uraian }}</div></div>
                    </div>
                </div>
            </div>

            <div class="card book-card mb-4">
                <div class="card-header"><h6 class="mb-0 fw-bold">Informasi Rekening</h6></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4"><strong>Bank</strong><div class="text-muted">{{ $entry->sumberRekening?->nama_bank ?? '-' }}</div></div>
                        <div class="col-md-4"><strong>Nomor Rekening</strong><div class="text-muted">{{ $entry->sumberRekening?->nomor_rekening ?? '-' }}</div></div>
                        <div class="col-md-4"><strong>Atas Nama</strong><div class="text-muted">{{ $entry->sumberRekening?->nama_rekening ?? '-' }}</div></div>
                    </div>
                </div>
            </div>

            <div class="card book-card mb-4">
                <div class="card-header"><h6 class="mb-0 fw-bold">Referensi dan Dokumen Pencairan</h6></div>
                <div class="card-body">
                    @if($tagihan)
                        <div class="row g-3">
                            <div class="col-md-6"><strong>Nomor Tagihan</strong><div class="text-muted">{{ $tagihan->nomor_tagihan ?? '-' }}</div></div>
                            <div class="col-md-6"><strong>Pihak</strong><div class="text-muted">{{ $tagihan->pihak?->nama_pihak ?? '-' }}</div></div>
                            <div class="col-md-12"><strong>Deskripsi</strong><div class="text-muted">{{ $tagihan->deskripsi ?? '-' }}</div></div>
                            <div class="col-md-3"><strong>SPP</strong><div class="text-muted">{{ $docChain['spp']?->nomor_spp ?? '-' }}</div></div>
                            <div class="col-md-3"><strong>SPM</strong><div class="text-muted">{{ $docChain['spm']?->nomor_spm ?? '-' }}</div></div>
                            <div class="col-md-3"><strong>NPI</strong><div class="text-muted">{{ $docChain['npi']?->nomor_npi ?? '-' }}</div></div>
                            <div class="col-md-3"><strong>SP2D</strong><div class="text-muted">{{ $docChain['sp2d']?->nomor_sp2d ?? '-' }}</div></div>
                        </div>
                    @elseif($penerimaan)
                        <div class="row g-3">
                            <div class="col-md-6"><strong>Nomor Invoice</strong><div class="text-muted">{{ $penerimaan->nomor_invoice ?? '-' }}</div></div>
                            <div class="col-md-6"><strong>Mitra</strong><div class="text-muted">{{ $penerimaan->mitra?->nama_pihak ?? '-' }}</div></div>
                            <div class="col-md-12"><strong>Keterangan</strong><div class="text-muted">{{ $penerimaan->keterangan ?? '-' }}</div></div>
                        </div>
                    @else
                        <div class="text-muted">Tidak ada referensi pengeluaran / penerimaan yang tertaut pada transaksi ini.</div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-xl-5">
            <div class="card book-card">
                <div class="card-header"><h6 class="mb-0 fw-bold">Log dan Keterkaitan</h6></div>
                <div class="card-body">
                    @if($relatedLogs->isEmpty())
                        @include('pembukuan.partials.empty-state', ['title' => 'Belum ada log tambahan', 'message' => 'Transaksi ini belum memiliki log dokumen atau keterkaitan lain yang terekam.'])
                    @else
                        <div class="book-timeline">
                            @foreach($relatedLogs as $log)
                                <div class="book-timeline-item">
                                    <div class="book-timeline-dot"></div>
                                    <div class="fw-semibold">{{ $log->aksi ?? 'LOG' }}</div>
                                    <div class="small text-muted">
                                        {{ $log->user?->name ?? $log->role_saat_itu ?? 'Sistem' }}
                                        · {{ optional($log->created_at)->format('d M Y H:i') }}
                                    </div>
                                    <div class="small mt-1">{{ $log->catatan ?? $log->status_baru ?? '-' }}</div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
