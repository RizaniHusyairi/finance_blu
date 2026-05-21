@extends('layouts.app')
@section('title', $kontrak->exists ? 'Edit Kontrak Mitra Jasa' : 'Tambah Kontrak Mitra Jasa')

@include('super_admin_jasa.partials.form-style')

@section('content')
@php
    $requiredMark = '<span class="text-danger ms-1">*</span>';
    $selectedKontrakLayananIds = collect(old('layanan_ids', $selectedLayananIds ?? []))->map(fn ($id) => (int) $id)->all();
    $layananPath = function ($layanan) {
        $names = [$layanan->nama_layanan];
        $parent = $layanan->parent;
        $guard = 0;

        while ($parent && $guard < 10) {
            array_unshift($names, $parent->nama_layanan);
            $parent = $parent->parent;
            $guard++;
        }

        return implode(' > ', $names);
    };
@endphp

<div class="jasa-form-hero mb-4 px-4 py-4">
    <div class="d-flex flex-column flex-lg-row gap-3 justify-content-between align-items-lg-center position-relative">
        <div class="d-flex gap-3 align-items-start">
            <span class="d-inline-flex align-items-center justify-content-center rounded-3 bg-white text-primary shadow-sm" style="width:44px;height:44px;">
                <i class="bi bi-file-earmark-ruled fs-5"></i>
            </span>
            <div>
                <h4 class="mb-1 fw-black">{{ $kontrak->exists ? 'Edit Kontrak Mitra Jasa' : 'Tambah Kontrak Mitra Jasa' }}</h4>
                <p class="mb-0 fw-semibold small">{{ $mitra->nama_mitra }} - dokumen dasar untuk tagihan dan layanan mitra.</p>
            </div>
        </div>
        <a href="{{ route('jasa.mitra.show', $mitra) }}" class="btn btn-light text-primary fw-bold shadow-sm jasa-icon-btn" title="Kembali" aria-label="Kembali">
            <i class="bi bi-arrow-left"></i>
        </a>
    </div>
</div>

@if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="POST" enctype="multipart/form-data" action="{{ $kontrak->exists ? route('jasa.mitra.kontrak.update', [$mitra, $kontrak]) : route('jasa.mitra.kontrak.store', $mitra) }}">
    @csrf
    @if($kontrak->exists)
        @method('PUT')
    @endif

    <div class="jasa-form-card">
        <div class="jasa-form-section">
            <div class="jasa-section-title">
                <span class="jasa-section-icon"><i class="bi bi-card-heading"></i></span>
                <div>
                    <h6>Identitas Dokumen</h6>
                    <p>Nomor, nama, jenis, dan status dokumen dasar.</p>
                </div>
            </div>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fw-bold">Nomor Kontrak {!! $requiredMark !!}</label>
                    <input type="text" name="nomor_kontrak" class="form-control" value="{{ old('nomor_kontrak', $kontrak->nomor_kontrak) }}" required>
                </div>
                <div class="col-md-8">
                    <label class="form-label fw-bold">Nama Kontrak/Dokumen {!! $requiredMark !!}</label>
                    <input type="text" name="nama_kontrak" class="form-control" value="{{ old('nama_kontrak', $kontrak->nama_kontrak) }}" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Jenis Dokumen {!! $requiredMark !!}</label>
                    <select name="jenis_dokumen" class="form-select" required>
                        <option value="">Pilih dokumen</option>
                        @foreach(['KONTRAK', 'PERJANJIAN_KERJA_SAMA', 'SURAT_PERMOHONAN', 'BERITA_ACARA', 'REKAP_PEMAKAIAN', 'DOKUMEN_LAINNYA'] as $jenis)
                            <option value="{{ $jenis }}" @selected(old('jenis_dokumen', $kontrak->jenis_dokumen) === $jenis)>{{ str_replace('_', ' ', $jenis) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Tanggal Kontrak {!! $requiredMark !!}</label>
                    <input type="date" name="tanggal_kontrak" class="form-control" value="{{ old('tanggal_kontrak', optional($kontrak->tanggal_kontrak)->format('Y-m-d')) }}" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Status Kontrak {!! $requiredMark !!}</label>
                    <select name="status_kontrak" class="form-select" required>
                        @foreach(['DRAFT', 'AKTIF', 'BERAKHIR', 'DIBATALKAN'] as $status)
                            <option value="{{ $status }}" @selected(old('status_kontrak', $kontrak->status_kontrak ?: 'AKTIF') === $status)>{{ $status }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <div class="jasa-form-section">
            <div class="jasa-section-title">
                <span class="jasa-section-icon"><i class="bi bi-calendar-range"></i></span>
                <div>
                    <h6>Masa Berlaku dan File</h6>
                    <p>Tanggal berlaku dokumen dan unggahan PDF pendukung.</p>
                </div>
            </div>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fw-bold">Tanggal Mulai {!! $requiredMark !!}</label>
                    <input type="date" name="tanggal_mulai" class="form-control" value="{{ old('tanggal_mulai', optional($kontrak->tanggal_mulai)->format('Y-m-d')) }}" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Tanggal Selesai {!! $requiredMark !!}</label>
                    <input type="date" name="tanggal_selesai" class="form-control" value="{{ old('tanggal_selesai', optional($kontrak->tanggal_selesai)->format('Y-m-d')) }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">File Kontrak PDF @unless($kontrak->exists){!! $requiredMark !!}@endunless</label>
                    <input type="file" name="file_kontrak" class="form-control" accept=".pdf" {{ $kontrak->exists ? '' : 'required' }}>
                    @if($kontrak->file_kontrak)
                        <div class="form-text">File sudah tersedia. Upload file baru untuk mengganti.</div>
                    @endif
                </div>
                <div class="col-12">
                    <label class="form-label fw-bold">Keterangan</label>
                    <textarea name="keterangan" class="form-control" rows="3">{{ old('keterangan', $kontrak->keterangan) }}</textarea>
                </div>
            </div>
        </div>

        <div class="jasa-form-section">
            <div class="jasa-section-title">
                <span class="jasa-section-icon"><i class="bi bi-diagram-3"></i></span>
                <div>
                    <h6>Scope Layanan Kontrak</h6>
                    <p>Pilih layanan yang dicakup dokumen ini. Kosongkan jika kontrak berlaku untuk semua layanan aktif mitra.</p>
                </div>
            </div>

            @if(($layanans ?? collect())->isEmpty())
                <div class="alert alert-info mb-0">Mitra belum memiliki layanan aktif. Kontrak tetap dapat disimpan sebagai dokumen umum.</div>
            @else
                <div class="row g-2">
                    @foreach($layanans as $layanan)
                        <div class="col-md-6">
                            <label class="d-flex gap-2 rounded-4 border bg-light p-3 h-100">
                                <input type="checkbox" name="layanan_ids[]" value="{{ $layanan->id }}" class="form-check-input mt-1" @checked(in_array((int) $layanan->id, $selectedKontrakLayananIds, true))>
                                <span>
                                    <span class="d-block fw-bold text-primary">{{ $layanan->kode_layanan ?: str_pad($layanan->id, 6, '0', STR_PAD_LEFT) }}</span>
                                    <span class="d-block small fw-semibold text-dark">{{ $layanan->nama_layanan }}</span>
                                    <span class="d-block small text-muted">{{ $layananPath($layanan) }}</span>
                                </span>
                            </label>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="jasa-action-footer">
            <div class="d-flex flex-column flex-sm-row justify-content-end gap-2">
                <a href="{{ route('jasa.mitra.show', $mitra) }}" class="btn btn-light fw-bold text-secondary border px-4">Batal</a>
                <button class="btn btn-primary fw-bold px-4" type="submit">
                    <i class="bi bi-save me-1"></i>Simpan Kontrak
                </button>
            </div>
        </div>
    </div>
</form>
@endsection
