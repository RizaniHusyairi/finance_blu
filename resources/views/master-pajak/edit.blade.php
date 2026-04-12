@extends('layouts.app')

@section('title', 'Edit Pajak — ' . $pajak->kode_pajak)

@section('content')
    <x-page-title title="Edit Pajak" subtitle="Perbarui informasi tarif pajak {{ $pajak->kode_pajak }}" />

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

    <form action="{{ route('master-pajak.update', $pajak) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
            <div>
                <h5 class="mb-1 fw-bold">Form Edit Pajak</h5>
                <p class="text-muted mb-0">Perbarui data tarif pajak <strong>{{ $pajak->kode_pajak }}</strong>.</p>
            </div>
            <a href="{{ route('master-pajak.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar Pajak
            </a>
        </div>

        {{-- Section 1: Informasi Utama --}}
        <div class="card shadow-sm border-0 rounded-4 mb-4">
            <div class="card-header bg-white border-bottom-0 pt-4 px-4">
                <h5 class="mb-1 fw-bold">Informasi Utama</h5>
                <p class="text-muted small mb-0">Kode, jenis, dan persentase tarif pajak.</p>
            </div>
            <div class="card-body px-4 pb-4">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Kode Pajak <span class="text-danger">*</span></label>
                        <input type="text" name="kode_pajak" class="form-control @error('kode_pajak') is-invalid @enderror" value="{{ old('kode_pajak', $pajak->kode_pajak) }}" maxlength="30" required>
                        @error('kode_pajak')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Jenis Pajak <span class="text-danger">*</span></label>
                        <input type="text" name="jenis_pajak" class="form-control @error('jenis_pajak') is-invalid @enderror" value="{{ old('jenis_pajak', $pajak->jenis_pajak) }}" maxlength="50" required>
                        @error('jenis_pajak')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Persentase (%) <span class="text-danger">*</span></label>
                        <input type="number" name="persentase" class="form-control @error('persentase') is-invalid @enderror" value="{{ old('persentase', $pajak->persentase) }}" step="0.0001" min="0" required>
                        @error('persentase')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text text-muted">Masukkan angka persentase tanpa simbol %.</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Section 2: Rumus --}}
        <div class="card shadow-sm border-0 rounded-4 mb-4">
            <div class="card-header bg-white border-bottom-0 pt-4 px-4">
                <h5 class="mb-1 fw-bold">Rumus Perhitungan</h5>
                <p class="text-muted small mb-0">Catatan referensi rumus perhitungan pajak (opsional).</p>
            </div>
            <div class="card-body px-4 pb-4">
                <div class="col-12">
                    <label class="form-label fw-semibold">Rumus</label>
                    <textarea name="rumus" class="form-control @error('rumus') is-invalid @enderror" rows="3">{{ old('rumus', $pajak->rumus) }}</textarea>
                    @error('rumus')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>

        {{-- Section 3: Masa Berlaku --}}
        <div class="card shadow-sm border-0 rounded-4 mb-4">
            <div class="card-header bg-white border-bottom-0 pt-4 px-4">
                <h5 class="mb-1 fw-bold">Masa Berlaku</h5>
                <p class="text-muted small mb-0">Periode berlaku tarif pajak ini.</p>
            </div>
            <div class="card-body px-4 pb-4">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Berlaku Mulai</label>
                        <input type="date" name="berlaku_mulai" class="form-control @error('berlaku_mulai') is-invalid @enderror" value="{{ old('berlaku_mulai', $pajak->berlaku_mulai ? \Carbon\Carbon::parse($pajak->berlaku_mulai)->format('Y-m-d') : '') }}">
                        @error('berlaku_mulai')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Berlaku Sampai</label>
                        <input type="date" name="berlaku_sampai" class="form-control @error('berlaku_sampai') is-invalid @enderror" value="{{ old('berlaku_sampai', $pajak->berlaku_sampai ? \Carbon\Carbon::parse($pajak->berlaku_sampai)->format('Y-m-d') : '') }}">
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
            </div>
            <div class="card-body px-4 pb-4">
                <div class="col-md-6">
                    <div class="form-check form-switch border rounded-4 px-3 py-3">
                        <input class="form-check-input" type="checkbox" role="switch" id="status_aktif" name="status_aktif" value="1" {{ old('status_aktif', $pajak->status_aktif) ? 'checked' : '' }}>
                        <label class="form-check-label ms-2" for="status_aktif">
                            Tarif pajak aktif dan siap digunakan
                        </label>
                    </div>
                </div>
            </div>
        </div>

        {{-- Action Buttons --}}
        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-body p-4 d-flex justify-content-end flex-wrap gap-2">
                <a href="{{ route('master-pajak.index') }}" class="btn btn-outline-secondary px-4">Kembali</a>
                <button type="submit" class="btn btn-primary px-4">
                    <i class="bi bi-save me-1"></i> Simpan Perubahan
                </button>
            </div>
        </div>
    </form>
@endsection
