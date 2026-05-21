@extends('layouts.app')
@section('title', 'Detail Kontrak Mitra Jasa')

@section('content')
@php
    $canManageMitraMaster = auth()->user()?->hasAnyRole(['Super Admin', 'Super Admin Jasa', 'Koordinator Jasa']) === true;
@endphp
<div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
    <div>
        <h4 class="mb-0 fw-bold">Detail Kontrak Mitra Jasa</h4>
        <p class="mb-0 small">{{ $mitra->nama_mitra }}</p>
    </div>
    <div class="d-flex gap-2">
        @if($canManageMitraMaster)
            <a href="{{ route('jasa.mitra.kontrak.edit', [$mitra, $kontrak]) }}" class="btn btn-light border fw-bold jasa-icon-btn" title="Edit" aria-label="Edit"><i class="bi bi-pencil"></i></a>
            <form method="POST" action="{{ route('jasa.mitra.kontrak.destroy', [$mitra, $kontrak]) }}" onsubmit="return confirm('Hapus kontrak/dokumen ini? Data yang sudah dipakai tagihan tidak bisa dihapus.');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-light border text-danger fw-bold jasa-icon-btn" title="Hapus" aria-label="Hapus"><i class="bi bi-trash"></i></button>
            </form>
        @endif
        <a href="{{ route('jasa.mitra.show', $mitra) }}" class="btn btn-secondary fw-bold jasa-icon-btn" title="Kembali" aria-label="Kembali"><i class="bi bi-arrow-left"></i></a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<div class="card border-0 shadow-sm rounded-4">
    <div class="card-header bg-white fw-bold">Informasi Kontrak/Dokumen</div>
    <div class="card-body">
        <dl class="row mb-0">
            <dt class="col-sm-3">Nomor Kontrak</dt><dd class="col-sm-9">{{ $kontrak->nomor_kontrak ?: '-' }}</dd>
            <dt class="col-sm-3">Nama Kontrak</dt><dd class="col-sm-9">{{ $kontrak->nama_kontrak ?: '-' }}</dd>
            <dt class="col-sm-3">Jenis Dokumen</dt><dd class="col-sm-9">{{ str_replace('_', ' ', $kontrak->jenis_dokumen ?: '-') }}</dd>
            <dt class="col-sm-3">Tanggal Kontrak</dt><dd class="col-sm-9">{{ optional($kontrak->tanggal_kontrak)->format('d/m/Y') ?: '-' }}</dd>
            <dt class="col-sm-3">Masa Berlaku</dt><dd class="col-sm-9">{{ optional($kontrak->tanggal_mulai)->format('d/m/Y') ?: '-' }} s.d. {{ optional($kontrak->tanggal_selesai)->format('d/m/Y') ?: '-' }}</dd>
            <dt class="col-sm-3">Status</dt><dd class="col-sm-9"><span class="badge bg-primary">{{ $kontrak->status_kontrak }}</span></dd>
            <dt class="col-sm-3">File</dt>
            <dd class="col-sm-9">
                @if($kontrak->file_kontrak)
                    <a href="{{ route('jasa.mitra.kontrak.download', [$mitra, $kontrak]) }}" class="btn btn-sm btn-outline-primary jasa-icon-btn" title="Download PDF" aria-label="Download PDF"><i class="bi bi-download"></i></a>
                @else
                    -
                @endif
            </dd>
            <dt class="col-sm-3">Scope Layanan</dt>
            <dd class="col-sm-9">
                @if($kontrak->layananJasa->isEmpty())
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle">Semua layanan aktif mitra</span>
                @else
                    <div class="d-flex flex-wrap gap-2">
                        @foreach($kontrak->layananJasa as $layanan)
                            <span class="badge bg-light text-dark border">
                                {{ $layanan->kode_layanan ?: str_pad($layanan->id, 6, '0', STR_PAD_LEFT) }} - {{ $layanan->nama_layanan }}
                            </span>
                        @endforeach
                    </div>
                @endif
            </dd>
            <dt class="col-sm-3">Keterangan</dt><dd class="col-sm-9">{{ $kontrak->keterangan ?: '-' }}</dd>
        </dl>
    </div>
</div>
@endsection
