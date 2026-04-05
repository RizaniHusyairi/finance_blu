@extends('layouts.app')

@section('title', 'Tambah DIPA')

@section('content')
    <x-page-title title="Master Data DIPA" subtitle="Tambah DIPA dan buat revisi awal aktif" />

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

    <form action="{{ route('dipas.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
            <div>
                <h5 class="mb-1 fw-bold">Form Tambah DIPA</h5>
                <p class="text-muted mb-0">Header DIPA dan revisi awal akan dibuat dalam satu proses penyimpanan.</p>
            </div>
            <a href="{{ route('dipas.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Batal
            </a>
        </div>

        <div class="card shadow-sm border-0 rounded-4 mb-4">
            <div class="card-header bg-white border-bottom-0 pt-4 px-4">
                <h5 class="mb-1 fw-bold">Data Header DIPA</h5>
                <p class="text-muted small mb-0">Informasi utama dokumen induk DIPA.</p>
            </div>
            <div class="card-body px-4 pb-4">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Nomor DIPA</label>
                        <input type="text" name="nomor_dipa" class="form-control" value="{{ old('nomor_dipa') }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Tahun Anggaran</label>
                        <input type="number" name="tahun_anggaran" class="form-control" value="{{ old('tahun_anggaran', now()->year) }}" min="2000" max="2100" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Tanggal Disahkan</label>
                        <input type="date" name="tanggal_disahkan" class="form-control" value="{{ old('tanggal_disahkan', now()->format('Y-m-d')) }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Status Aktif</label>
                        <select name="status_aktif" class="form-select" required>
                            <option value="1" {{ old('status_aktif', '1') === '1' ? 'selected' : '' }}>Aktif</option>
                            <option value="0" {{ old('status_aktif') === '0' ? 'selected' : '' }}>Nonaktif</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0 rounded-4 mb-4">
            <div class="card-header bg-white border-bottom-0 pt-4 px-4">
                <h5 class="mb-1 fw-bold">Revisi Awal</h5>
                <p class="text-muted small mb-0">Saat disimpan, sistem otomatis membuat revisi awal aktif dengan nomor revisi 0.</p>
            </div>
            <div class="card-body px-4 pb-4">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Nomor Revisi</label>
                        <input type="text" class="form-control" value="0" readonly>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Status Revisi</label>
                        <input type="text" class="form-control" value="Aktif" readonly>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Tanggal Revisi</label>
                        <input type="date" name="tanggal_revisi" class="form-control" value="{{ old('tanggal_revisi', now()->format('Y-m-d')) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Total Pagu Awal</label>
                        <input type="number" step="0.01" min="0" name="total_pagu" class="form-control" value="{{ old('total_pagu', 0) }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Dokumen DIPA</label>
                        <input type="file" name="file_dokumen_dipa" class="form-control" accept=".pdf">
                        <div class="form-text">Opsional, format PDF maksimal 5MB.</div>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Keterangan</label>
                        <textarea name="keterangan" class="form-control" rows="4" placeholder="Tambahkan catatan revisi awal bila diperlukan">{{ old('keterangan') }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-body p-4 d-flex justify-content-end flex-wrap gap-2">
                <a href="{{ route('dipas.index') }}" class="btn btn-outline-secondary px-4">Batal</a>
                <button type="submit" name="redirect_action" value="save" class="btn btn-primary px-4">
                    <i class="bi bi-save me-1"></i> Simpan
                </button>
                <button type="submit" name="redirect_action" value="save_and_detail" class="btn btn-success px-4">
                    <i class="bi bi-arrow-right-circle me-1"></i> Simpan &amp; Lanjut ke Detail DIPA
                </button>
            </div>
        </div>
    </form>
@endsection
