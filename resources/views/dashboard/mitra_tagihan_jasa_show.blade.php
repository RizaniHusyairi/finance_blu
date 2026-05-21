@extends('layouts.app')
@section('title', 'Detail Tagihan Jasa')

@push('css')
    @include('dashboard.partials.mitra-ui')
@endpush

@section('content')
@php
    $statusClass = match($tagihan->status) {
        'LUNAS' => 'success',
        'PUBLISHED' => 'warning',
        default => 'secondary',
    };
    $dueLabel = match($tagihan->status_jatuh_tempo) {
        'LEWAT_JATUH_TEMPO' => ['Lewat Jatuh Tempo', 'bg-danger'],
        'JATUH_TEMPO_HARI_INI' => ['Jatuh Tempo Hari Ini', 'bg-dark'],
        'MENDEKATI_JATUH_TEMPO' => ['Mendekati Jatuh Tempo', 'bg-warning text-dark'],
        'LUNAS' => ['Lunas', 'bg-success'],
        default => ['Normal', 'bg-success'],
    };
@endphp

<div class="mp-hero mb-4">
    <div class="d-flex flex-column flex-xl-row align-items-start align-items-xl-center justify-content-between gap-3">
        <div class="d-flex align-items-start gap-3">
            <span class="mp-hero-icon"><i class="bi bi-receipt fs-4"></i></span>
            <div>
                <div class="small text-white-50 fw-bold text-uppercase mb-1">Detail Tagihan</div>
                <h4 class="mb-1 fw-bold text-white">Detail Tagihan Jasa</h4>
                <p class="mb-0 small fw-semibold text-white-50">No. Tagihan: {{ $tagihan->nomor_tagihan }}</p>
            </div>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('mitra.tagihan-jasa.pdf', $tagihan->id) }}" target="_blank" class="btn btn-outline-light fw-bold">
                <i class="bi bi-file-pdf me-1"></i>Nota Tagihan
            </a>
            @if($tagihan->file_surat_pengantar_final)
                <a href="{{ route('mitra.tagihan-jasa.surat-final', $tagihan->id) }}" class="btn btn-outline-light fw-bold">
                    <i class="bi bi-download me-1"></i>Surat Pengantar
                </a>
            @endif
            <a href="{{ route('mitra.dashboard') }}" class="btn btn-light fw-bold">Kembali</a>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-white p-4 border-bottom rounded-top-4 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold"><i class="bi bi-receipt text-primary me-2"></i>Informasi Tagihan</h5>
                <span class="badge bg-{{ $statusClass }} px-3 py-2">{{ str_replace('_', ' ', $tagihan->status) }}</span>
            </div>
            <div class="card-body p-4">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="small text-muted fw-bold">Mitra</div>
                        <div class="fw-semibold">{{ $mitra->nama_mitra }}</div>
                        <div class="small text-muted">{{ $mitra->alamat ?: '-' }}</div>
                        <div class="small text-muted">NPWP: {{ $mitra->npwp ?: '-' }}</div>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <div class="small text-muted fw-bold">Tanggal Tagihan</div>
                        <div class="fw-semibold">{{ \Carbon\Carbon::parse($tagihan->tanggal_tagihan)->format('d F Y') }}</div>
                        <div class="small text-muted fw-bold mt-3">Dokumen Dasar</div>
                        <div class="fw-semibold">{{ $tagihan->nomor_kontrak ?: '-' }}</div>
                    </div>
                </div>

                <h6 class="fw-bold mb-3">Rincian Layanan</h6>
                <div class="table-responsive">
                    <table class="table table-bordered align-middle">
                        <thead class="table-light">
                            <tr>
                                <th class="text-center" width="5%">No</th>
                                <th>Deskripsi Layanan</th>
                                <th>Kode Akun</th>
                                <th class="text-center">Volume</th>
                                <th class="text-end">Tarif</th>
                                <th class="text-end">Jumlah</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($tagihan->details as $detail)
                                @php
                                    $layanan = $detail->layananJasa;
                                    $expectedPercentageSubtotal = ((float) $detail->qty * (float) $detail->harga_satuan / 100) * (float) ($detail->kurs ?? 1);
                                    $isPercentageDetail = ($layanan?->tipe_layanan === 'KONSESI')
                                        || str_contains((string) ($layanan?->satuan), '%')
                                        || ((bool) ($layanan?->mendukung_konsesi) && abs($expectedPercentageSubtotal - (float) $detail->subtotal) < 0.01);
                                @endphp
                                <tr>
                                    <td class="text-center">{{ $loop->iteration }}</td>
                                    <td>
                                        <div class="fw-semibold">{{ $detail->layananJasa->nama_lengkap ?? $detail->layananJasa->nama_layanan ?? '-' }}</div>
                                        @if($detail->layananJasa?->satuan)
                                            <div class="small text-muted">Satuan: {{ $detail->layananJasa->satuan }}</div>
                                        @endif
                                        @if($detail->keterangan)
                                            <div class="small text-muted">Keterangan: {{ $detail->keterangan }}</div>
                                        @endif
                                    </td>
                                    <td>{{ $detail->kode_akun ?: ($detail->layananJasa->kode_akun ?? '-') }}</td>
                                    <td class="text-center">{{ rtrim(rtrim(number_format($detail->qty, 2, ',', '.'), '0'), ',') }}</td>
                                    <td class="text-end">
                                        @if($isPercentageDetail)
                                            {{ rtrim(rtrim(number_format((float) $detail->harga_satuan, 4, ',', '.'), '0'), ',') }}%
                                        @else
                                            Rp {{ number_format((float) $detail->harga_satuan, 0, ',', '.') }}
                                        @endif
                                    </td>
                                    <td class="text-end fw-bold">Rp {{ number_format((float) $detail->subtotal, 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="5" class="text-end">Total Tagihan</th>
                                <th class="text-end text-success fs-5">Rp {{ number_format((float) $tagihan->total_tagihan, 0, ',', '.') }}</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-white fw-bold">Pembayaran</div>
            <div class="card-body">
                <div class="small text-muted fw-bold">Nomor Virtual Account</div>
                <div class="fs-4 fw-bold text-primary">{{ $tagihan->nomor_va ?: '-' }}</div>
                <div class="small text-muted mt-2">Bank BTN</div>
                <hr>
                <div class="small text-muted fw-bold">Tanggal Jatuh Tempo</div>
                <div class="fw-semibold">{{ $tagihan->tanggal_jatuh_tempo ? \Carbon\Carbon::parse($tagihan->tanggal_jatuh_tempo)->format('d F Y') : '-' }}</div>
                <div class="mt-2"><span class="badge {{ $dueLabel[1] }}">{{ $dueLabel[0] }}</span></div>
                @if($tagihan->status_jatuh_tempo === 'LEWAT_JATUH_TEMPO')
                    <div class="small text-danger mt-2">Terlambat {{ $tagihan->hari_terlambat }} hari.</div>
                @elseif($tagihan->status !== 'LUNAS')
                    <div class="small text-muted mt-2">Umur piutang {{ $tagihan->umur_piutang_hari }} hari.</div>
                @endif
                @if($tagihan->status === 'PUBLISHED')
                    <div class="alert alert-warning small mt-3 mb-0">
                        Tagihan masih menunggu pembayaran.
                    </div>
                @else
                    <div class="alert alert-success small mt-3 mb-0">
                        Tagihan sudah lunas.
                    </div>
                @endif
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white fw-bold">Dokumen</div>
            <div class="card-body d-grid gap-2">
                <a href="{{ route('mitra.tagihan-jasa.pdf', ['id' => $tagihan->id, 'download' => 1]) }}" class="btn btn-danger fw-bold">
                    <i class="bi bi-download me-1"></i> Download Nota Tagihan
                </a>
                @if($tagihan->file_surat_pengantar_final)
                    <a href="{{ route('mitra.tagihan-jasa.surat-final', $tagihan->id) }}" class="btn btn-primary fw-bold">
                        <i class="bi bi-download me-1"></i> Download Surat Pengantar TTD
                    </a>
                @else
                    <div class="alert alert-light border small mb-0">
                        Surat pengantar final belum tersedia.
                    </div>
                @endif
                @if($tagihan->kontrakMitraJasa?->file_kontrak)
                    <a href="{{ route('mitra.kontrak-jasa.download', $tagihan->kontrakMitraJasa) }}" class="btn btn-outline-secondary fw-bold">
                        <i class="bi bi-download me-1"></i> Download Dokumen Dasar
                    </a>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
