@extends('layouts.app')

@section('title', 'Tambah Pajak')

@php
    $oldStatusAktif = old('status_aktif', '1');
@endphp

@section('content')
    <x-page-title title="Tambah Pajak" subtitle="Menambahkan tarif pajak baru ke master data" />

    @if ($errors->any())
        <div class="alert alert-danger border-0 bg-danger alert-dismissible fade show shadow-sm">
            <ul class="text-white mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <form action="{{ route('master-pajak.store') }}" method="POST">
        @csrf

        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
            <div>
                <h5 class="mb-1 fw-bold">Form Tambah Pajak</h5>
                <p class="text-muted mb-0">Lengkapi informasi tarif pajak yang akan menjadi referensi pada dokumen SPP/SPM.</p>
            </div>
            <a href="{{ route('master-pajak.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar Pajak
            </a>
        </div>

        {{-- Section 1: Informasi Utama --}}
        <div class="card shadow-sm border-0 rounded-4 mb-4">
            <div class="card-header bg-white border-bottom-0 pt-4 px-4">
                <h5 class="mb-1 fw-bold">Informasi Utama</h5>
                <p class="text-muted small mb-0">Masukkan kode, jenis, dan persentase tarif pajak.</p>
            </div>
            <div class="card-body px-4 pb-4">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Kode Pajak <span class="text-danger">*</span></label>
                        <input type="text" name="kode_pajak" class="form-control @error('kode_pajak') is-invalid @enderror" value="{{ old('kode_pajak') }}" maxlength="30" placeholder="Contoh: PPN11, PPh23-JASA" required>
                        @error('kode_pajak')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text text-muted">Kode unik untuk identifikasi tarif pajak. Akan disimpan dalam huruf kapital.</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Jenis Pajak <span class="text-danger">*</span></label>
                        <input type="text" name="jenis_pajak" class="form-control @error('jenis_pajak') is-invalid @enderror" value="{{ old('jenis_pajak') }}" maxlength="50" placeholder="Contoh: PPN, PPh Pasal 23, PPh Final" required>
                        @error('jenis_pajak')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Persentase (%) <span class="text-danger">*</span></label>
                        <input type="number" name="persentase" class="form-control @error('persentase') is-invalid @enderror" value="{{ old('persentase') }}" step="0.0001" min="0" placeholder="Contoh: 11, 1.5, 2" required>
                        @error('persentase')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text text-muted">Masukkan angka persentase tanpa simbol %. Contoh: 11 untuk 11%.</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Kode Akun Pajak (KAP)</label>
                        <input type="text" name="kode_akun_pajak" class="form-control @error('kode_akun_pajak') is-invalid @enderror" value="{{ old('kode_akun_pajak') }}" maxlength="6" inputmode="numeric" pattern="[0-9]{6}" placeholder="Contoh: 411211, 411122">
                        @error('kode_akun_pajak')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text text-muted">6 digit kode akun untuk kode billing/SSP. Contoh: PPN 411211, PPh 22 411122, PPh 23 411124, PPh 4(2) 411128.</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Kode Jenis Setoran (KJS)</label>
                        <input type="text" name="kode_jenis_setoran" class="form-control @error('kode_jenis_setoran') is-invalid @enderror" value="{{ old('kode_jenis_setoran') }}" maxlength="3" inputmode="numeric" pattern="[0-9]{3}" placeholder="Contoh: 900, 100">
                        @error('kode_jenis_setoran')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text text-muted">3 digit kode jenis setoran. Contoh: 900 (bendahara pemungut), 100 (masa).</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Section 2: Rumus Perhitungan --}}
        <div class="card shadow-sm border-0 rounded-4 mb-4">
            <div class="card-header bg-white border-bottom-0 pt-4 px-4">
                <h5 class="mb-1 fw-bold">Rumus Perhitungan</h5>
                <p class="text-muted small mb-0">Catatan referensi rumus perhitungan pajak (opsional).</p>
            </div>
            <div class="card-body px-4 pb-4">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label fw-semibold">Rumus</label>
                        <textarea name="rumus" class="form-control @error('rumus') is-invalid @enderror" rows="3" placeholder="Contoh: DPP x 11%, Nilai bruto x 2%">{{ old('rumus') }}</textarea>
                        @error('rumus')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text text-muted">Berfungsi sebagai catatan referensi, tidak harus dieksekusi langsung oleh sistem.</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Section 3: Masa Berlaku --}}
        <div class="card shadow-sm border-0 rounded-4 mb-4">
            <div class="card-header bg-white border-bottom-0 pt-4 px-4">
                <h5 class="mb-1 fw-bold">Masa Berlaku</h5>
                <p class="text-muted small mb-0">Tentukan periode berlaku tarif pajak ini.</p>
            </div>
            <div class="card-body px-4 pb-4">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Berlaku Mulai</label>
                        <input type="date" name="berlaku_mulai" class="form-control @error('berlaku_mulai') is-invalid @enderror" value="{{ old('berlaku_mulai') }}">
                        @error('berlaku_mulai')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Berlaku Sampai</label>
                        <input type="date" name="berlaku_sampai" class="form-control @error('berlaku_sampai') is-invalid @enderror" value="{{ old('berlaku_sampai') }}">
                        @error('berlaku_sampai')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text text-muted">Kosongkan jika tarif berlaku tanpa batas akhir.</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Section 4: Status --}}
        <div class="card shadow-sm border-0 rounded-4 mb-4">
            <div class="card-header bg-white border-bottom-0 pt-4 px-4">
                <h5 class="mb-1 fw-bold">Status</h5>
                <p class="text-muted small mb-0">Atur status aktif tarif pajak ini.</p>
            </div>
            <div class="card-body px-4 pb-4">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="form-check form-switch border rounded-4 px-3 py-3">
                            <input class="form-check-input" type="checkbox" role="switch" id="status_aktif" name="status_aktif" value="1" {{ (string) $oldStatusAktif === '1' ? 'checked' : '' }} style="float: right;">
                            <label class="form-check-label" for="status_aktif">
                                Tarif pajak aktif dan siap digunakan
                            </label>
                            <div class="small text-muted mt-1">Nonaktifkan jika tarif ini belum atau sudah tidak berlaku lagi.</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Action Buttons --}}
        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-body p-4 d-flex justify-content-end flex-wrap gap-2">
                <a href="{{ route('master-pajak.index') }}" class="btn btn-outline-secondary px-4">Kembali</a>
                <button type="submit" class="btn btn-primary px-4">
                    <i class="bi bi-save me-1"></i> Simpan Pajak
                </button>
            </div>
        </div>
    </form>
@endsection
