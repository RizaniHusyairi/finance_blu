@extends('layouts.app')

@section('title', 'Proses Tagihan - ' . $tagihan->nomor_tagihan)

@push('css')
<style>
    .process-card { border: 1px solid #e5e7eb; border-radius: 8px; }
    .process-section-title { font-size: .85rem; letter-spacing: .04em; text-transform: uppercase; color: #64748b; font-weight: 700; }
    .process-value { font-weight: 650; color: #0f172a; }
    .process-muted { color: #64748b; }
    .process-actions { gap: .5rem; }
    .process-timeline-item { border-left: 2px solid #e5e7eb; padding-left: 1rem; margin-left: .35rem; padding-bottom: 1rem; }
    .process-timeline-dot { width: .7rem; height: .7rem; border-radius: 50%; background: #2563eb; margin-left: -1.43rem; margin-top: .25rem; float: left; }
    @media (max-width: 991.98px) {
        .process-sticky { position: static !important; }
    }
</style>
@endpush

@section('content')
@php
    $pihak = $tagihan->detailKontrak?->kontrakTermin?->kontrak?->vendor?->nama_pihak
        ?? $tagihan->pihak?->nama_pihak
        ?? $tagihan->nama_supplier
        ?? '-';
@endphp

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <a href="{{ route('proses-tagihan.index') }}" class="btn btn-sm btn-outline-secondary mb-2">
            <i class="bi bi-arrow-left me-1"></i>Kembali
        </a>
        <h4 class="mb-1">{{ $tagihan->nomor_tagihan }}</h4>
        <div class="text-muted">{{ $tagihan->tipe_tagihan }} - {{ $pihak }}</div>
    </div>
    <div class="text-end">
        <span class="badge bg-secondary">{{ $tagihan->status }}</span>
        <div class="fw-semibold mt-2">Rp {{ number_format((float) $tagihan->total_netto, 0, ',', '.') }}</div>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success border-0 shadow-sm">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger border-0 shadow-sm">{{ session('error') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-danger border-0 shadow-sm">
        {{ $errors->first() }}
    </div>
@endif

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card process-card shadow-sm mb-3">
            <div class="card-body">
                <div class="process-section-title mb-2">Ringkasan</div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="process-muted small">Uraian</div>
                        <div class="process-value">{{ $tagihan->deskripsi }}</div>
                    </div>
                    <div class="col-md-3">
                        <div class="process-muted small">Bruto</div>
                        <div class="process-value">Rp {{ number_format((float) $tagihan->total_bruto, 0, ',', '.') }}</div>
                    </div>
                    <div class="col-md-3">
                        <div class="process-muted small">Potongan</div>
                        <div class="process-value">Rp {{ number_format((float) $tagihan->total_potongan, 0, ',', '.') }}</div>
                    </div>
                </div>
            </div>
        </div>

        @include('proses_tagihan._coa_card', ['tagihan' => $tagihan, 'state' => $state, 'coaOptions' => $coaOptions])
        @include('proses_tagihan._kpa_card', ['tagihan' => $tagihan])

        @if($state['missingPrereqs'])
            <div class="alert alert-warning border-0 shadow-sm">
                <div class="fw-semibold mb-1">Draft belum dapat dibuat</div>
                <ul class="mb-0">
                    @foreach($state['missingPrereqs'] as $item)
                        <li>{{ $item }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @include('proses_tagihan._dokumen_card', [
            'tagihan' => $tagihan,
            'jenis' => 'spp',
            'label' => 'SPP',
            'document' => $state['spp'],
            'instance' => $state['sppInstance'],
            'myApprovals' => $state['myApprovals']['spp'],
            'canSubmit' => auth()->user()?->hasAnyRole(['Operator BLU', 'Super Admin']),
            'submitRoute' => $state['spp'] ? route('proses-tagihan.spp.ajukan', $tagihan->id) : null,
            'pdfRoute' => $state['spp'] ? route('spps.cetak-pdf', $state['spp']->id) : null,
        ])

        @include('proses_tagihan._dokumen_card', [
            'tagihan' => $tagihan,
            'jenis' => 'spm',
            'label' => 'SPM',
            'document' => $state['spm'],
            'instance' => $state['spmInstance'],
            'myApprovals' => $state['myApprovals']['spm'],
            'canSubmit' => auth()->user()?->hasAnyRole(['Operator BLU', 'Super Admin']),
            'submitRoute' => $state['spm'] ? route('proses-tagihan.spm.ajukan', $tagihan->id) : null,
            'pdfRoute' => $state['spm'] ? route('spms.cetak-pdf', $state['spm']->id) : null,
        ])

        @include('proses_tagihan._dokumen_card', [
            'tagihan' => $tagihan,
            'jenis' => 'npi',
            'label' => 'NPI',
            'document' => $state['npi'],
            'instance' => $state['npiInstance'],
            'myApprovals' => $state['myApprovals']['npi'],
            'canSubmit' => auth()->user()?->hasAnyRole(['Bendahara Pengeluaran', 'Super Admin']),
            'submitRoute' => $state['npi'] ? route('proses-tagihan.npi.ajukan', $tagihan->id) : null,
            'pdfRoute' => $state['npi'] ? route('npis.cetak-pdf', $state['npi']->id) : null,
        ])

        @include('proses_tagihan._bukti_transfer_card', ['tagihan' => $tagihan, 'state' => $state])
        @include('proses_tagihan._sp2d_card', ['tagihan' => $tagihan, 'state' => $state])
        @include('proses_tagihan._pajak_card', ['tagihan' => $tagihan, 'state' => $state])
    </div>

    <div class="col-lg-4">
        <div class="process-sticky" style="position: sticky; top: 90px;">
            @include('proses_tagihan._timeline', ['tagihan' => $tagihan, 'state' => $state])
        </div>
    </div>
</div>
@endsection
