@extends('layouts.app')
@section('title', $hak->exists ? 'Edit Hak PJP2U' : 'Tambah Hak PJP2U')

@include('super_admin_jasa.partials.form-style')

@section('content')
@php
    $path = function ($layanan) {
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
                <i class="bi bi-airplane fs-5"></i>
            </span>
            <div>
                <h4 class="mb-1 fw-black">{{ $hak->exists ? 'Edit Hak PJP2U' : 'Tambah Hak PJP2U' }}</h4>
                <p class="mb-0 fw-semibold small">{{ $mitra->nama_mitra }} - hak input laporan PAX berdasarkan layanan dan kontrak.</p>
            </div>
        </div>
        <a href="{{ route('jasa.mitra.show', $mitra) }}" class="btn btn-light text-primary fw-bold shadow-sm jasa-icon-btn" title="Kembali" aria-label="Kembali">
            <i class="bi bi-arrow-left"></i>
        </a>
    </div>
</div>

@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ $hak->exists ? route('jasa.mitra.pjp2u.update', [$mitra, $hak]) : route('jasa.mitra.pjp2u.store', $mitra) }}">
    @csrf
    @if($hak->exists)
        @method('PUT')
    @endif

    <div class="jasa-form-card">
        <div class="jasa-form-section">
            <div class="jasa-section-title">
                <span class="jasa-section-icon"><i class="bi bi-list-check"></i></span>
                <div>
                    <h6>Layanan PJP2U dan Kontrak</h6>
                    <p>Pilih item layanan PJP2U yang boleh dilaporkan oleh mitra.</p>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-lg-7">
                    <label class="form-label fw-bold">Layanan PJP2U <span class="text-danger">*</span></label>
                    <select name="layanan_jasa_id" id="layananPjp2u" class="form-select" required>
                        <option value="">Pilih layanan PJP2U</option>
                        @foreach($layanans as $layanan)
                            <option value="{{ $layanan->id }}" @selected((int) old('layanan_jasa_id', $hak->layanan_jasa_id) === (int) $layanan->id)>
                                {{ $layanan->kode_layanan ?: str_pad($layanan->id, 6, '0', STR_PAD_LEFT) }} - {{ $layanan->nama_layanan }}
                            </option>
                        @endforeach
                    </select>
                    <div class="form-text">Jika daftar kosong, aktifkan layanan PJP2U dulu di menu Atur Layanan Mitra.</div>
                </div>
                <div class="col-lg-5">
                    <label class="form-label fw-bold">Kontrak Dasar (Opsional)</label>
                    <select name="kontrak_mitra_jasa_id" id="kontrakPjp2u" class="form-select">
                        <option value="">Tanpa Kontrak</option>
                        @foreach($kontraks as $kontrak)
                            @php
                                $scopeIds = $kontrak->layananJasa->pluck('id')->implode(',');
                                $scopeText = $kontrak->layananJasa->isEmpty() ? 'Semua layanan' : $kontrak->layananJasa->pluck('kode_layanan')->filter()->join(', ');
                            @endphp
                            <option value="{{ $kontrak->id }}" data-layanan-ids="{{ $scopeIds }}" @selected((int) old('kontrak_mitra_jasa_id', $hak->kontrak_mitra_jasa_id) === (int) $kontrak->id)>
                                {{ $kontrak->nomor_kontrak ?: 'Tanpa Nomor' }} - {{ $kontrak->nama_kontrak ?: 'Dokumen Mitra Jasa' }} ({{ $scopeText }})
                            </option>
                        @endforeach
                    </select>
                    <div class="form-text">Kontrak yang sudah di-scope hanya valid untuk layanan yang dicakupnya.</div>
                </div>
            </div>

            @if($layanans->isNotEmpty())
                <div class="mt-3 rounded-4 border bg-light p-3">
                    <div class="small fw-bold text-muted mb-2">Layanan PJP2U aktif mitra</div>
                    <div class="d-flex flex-column gap-2">
                        @foreach($layanans as $layanan)
                            <div class="rounded-3 bg-white border p-2">
                                <div class="fw-bold text-primary">{{ $layanan->kode_layanan ?: str_pad($layanan->id, 6, '0', STR_PAD_LEFT) }} - {{ $layanan->nama_layanan }}</div>
                                <div class="small text-muted">{{ $path($layanan) }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <div class="jasa-form-section">
            <div class="jasa-section-title">
                <span class="jasa-section-icon"><i class="bi bi-calendar-check"></i></span>
                <div>
                    <h6>Masa Berlaku dan Status</h6>
                    <p>Kontrol kapan hak input laporan PAX aktif di portal mitra.</p>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-lg-4">
                    <label class="form-label fw-bold">Tanggal Mulai</label>
                    <input type="date" name="tanggal_mulai" class="form-control" value="{{ old('tanggal_mulai', $hak->tanggal_mulai ? \Carbon\Carbon::parse($hak->tanggal_mulai)->format('Y-m-d') : '') }}">
                </div>
                <div class="col-lg-4">
                    <label class="form-label fw-bold">Tanggal Selesai</label>
                    <input type="date" name="tanggal_selesai" class="form-control" value="{{ old('tanggal_selesai', $hak->tanggal_selesai ? \Carbon\Carbon::parse($hak->tanggal_selesai)->format('Y-m-d') : '') }}">
                </div>
                <div class="col-lg-4">
                    <div class="jasa-check-card">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" id="status_aktif" name="status_aktif" value="1" {{ old('status_aktif', $hak->status_aktif ?? true) ? 'checked' : '' }}>
                            <label class="form-check-label fw-bold" for="status_aktif">Status Aktif</label>
                        </div>
                        <div class="form-text">Jika nonaktif, menu input PAX tidak terbuka untuk layanan ini.</div>
                    </div>
                </div>
                <div class="col-12">
                    <label class="form-label fw-bold">Keterangan</label>
                    <textarea name="keterangan" class="form-control" rows="2">{{ old('keterangan', $hak->keterangan) }}</textarea>
                </div>
            </div>
        </div>

        <div class="jasa-action-footer">
            <div class="d-flex flex-column flex-sm-row justify-content-end gap-2">
                <a href="{{ route('jasa.mitra.show', $mitra) }}" class="btn btn-light fw-bold text-secondary border px-4">Batal</a>
                <button type="submit" class="btn btn-primary fw-bold px-4">
                    <i class="bi bi-save me-1"></i>Simpan Hak PJP2U
                </button>
            </div>
        </div>
    </div>
</form>
@endsection
