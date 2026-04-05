@extends('layouts.app')

@section('title', 'Edit COA')

@php
    $oldStatusAktif = old('status_aktif', $coa->status_aktif ? '1' : '0');
@endphp

@section('content')
    <x-page-title title="Edit COA" subtitle="Perbarui struktur kode dan informasi akun tanpa mengubah jejak penggunaan COA" />

    @if(session('success'))
        <div class="alert alert-success border-0 bg-success alert-dismissible fade show shadow-sm">
            <div class="text-white">{{ session('success') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

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

    <form action="{{ route('coas.update', $coa) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
            <div>
                <h5 class="mb-1 fw-bold">Form Edit COA</h5>
                <p class="text-muted mb-0">Perubahan kode lengkap akan dibentuk ulang dari input terbaru dan diverifikasi ulang saat disimpan.</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('coas.show', $coa) }}" class="btn btn-outline-primary">
                    <i class="bi bi-eye me-1"></i> Lihat Detail COA
                </a>
                <a href="{{ route('coas.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar COA
                </a>
            </div>
        </div>

        <div class="card shadow-sm border-0 rounded-4 mb-4">
            <div class="card-header bg-white border-bottom-0 pt-4 px-4">
                <h5 class="mb-1 fw-bold">Struktur Kode COA</h5>
                <p class="text-muted small mb-0">Perbarui setiap segmen kode bila memang ada koreksi atau penyesuaian struktur MAK.</p>
            </div>
            <div class="card-body px-4 pb-4">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Kode Program</label>
                        <input type="text" name="kd_program" class="form-control coa-segment" value="{{ old('kd_program', $coa->kd_program) }}" maxlength="10" placeholder="Contoh: 01">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Kode Giat</label>
                        <input type="text" name="kd_giat" class="form-control coa-segment" value="{{ old('kd_giat', $coa->kd_giat) }}" maxlength="10" placeholder="Contoh: 1234">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Kode Output</label>
                        <input type="text" name="kd_output" class="form-control coa-segment" value="{{ old('kd_output', $coa->kd_output) }}" maxlength="10" placeholder="Contoh: 001">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Kode Suboutput</label>
                        <input type="text" name="kd_suboutput" class="form-control coa-segment" value="{{ old('kd_suboutput', $coa->kd_suboutput) }}" maxlength="10" placeholder="Contoh: A">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Kode Komponen</label>
                        <input type="text" name="kd_komponen" class="form-control coa-segment" value="{{ old('kd_komponen', $coa->kd_komponen) }}" maxlength="10" placeholder="Contoh: 051">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Kode Subkomponen</label>
                        <input type="text" name="kd_subkomponen" class="form-control coa-segment" value="{{ old('kd_subkomponen', $coa->kd_subkomponen) }}" maxlength="10" placeholder="Contoh: 01">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Kode Akun <span class="text-danger">*</span></label>
                        <input type="text" name="kd_akun" class="form-control coa-segment" value="{{ old('kd_akun', $coa->kd_akun) }}" maxlength="20" placeholder="Contoh: 521219" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Kode Item</label>
                        <input type="text" name="kd_item" class="form-control coa-segment" value="{{ old('kd_item', $coa->kd_item) }}" maxlength="20" placeholder="Contoh: 0001">
                    </div>
                    <div class="col-12">
                        <div class="border rounded-4 p-3 bg-light">
                            <div class="small text-muted mb-1">Preview Kode Lengkap</div>
                            <div class="fw-bold fs-5 text-primary" id="kode_mak_preview">{{ collect([old('kd_program', $coa->kd_program), old('kd_giat', $coa->kd_giat), old('kd_output', $coa->kd_output), old('kd_suboutput', $coa->kd_suboutput), old('kd_komponen', $coa->kd_komponen), old('kd_subkomponen', $coa->kd_subkomponen), old('kd_akun', $coa->kd_akun), old('kd_item', $coa->kd_item)])->filter()->map(fn($item) => strtoupper(trim($item)))->implode('.') ?: '-' }}</div>
                            <div class="small text-muted mt-1">Sistem akan membentuk ulang kode lengkap saat update disimpan.</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0 rounded-4 mb-4">
            <div class="card-header bg-white border-bottom-0 pt-4 px-4">
                <h5 class="mb-1 fw-bold">Informasi Akun</h5>
                <p class="text-muted small mb-0">Perbarui nama akun, jenis akun, atau status aktif COA.</p>
            </div>
            <div class="card-body px-4 pb-4">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Nama Akun <span class="text-danger">*</span></label>
                        <input type="text" name="nama_akun" class="form-control" value="{{ old('nama_akun', $coa->nama_akun) }}" maxlength="150" placeholder="Contoh: Belanja Honorarium Narasumber" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Jenis Akun</label>
                        <input type="text" name="jenis_akun" class="form-control" value="{{ old('jenis_akun', $coa->jenis_akun) }}" maxlength="50" placeholder="Contoh: BELANJA BARANG">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold d-block">Status Aktif</label>
                        <div class="form-check form-switch border rounded-4 px-3 py-3 mt-1">
                            <input class="form-check-input" type="checkbox" role="switch" id="status_aktif" name="status_aktif" value="1" {{ (string) $oldStatusAktif === '1' ? 'checked' : '' }}>
                            <label class="form-check-label ms-2" for="status_aktif">
                                COA aktif dan siap dipakai
                            </label>
                            <div class="small text-muted mt-1">Matikan jika COA tidak lagi ingin dipakai pada input baru.</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0 rounded-4 mb-4">
            <div class="card-header bg-white border-bottom-0 pt-4 px-4">
                <h5 class="mb-1 fw-bold">Preview COA</h5>
                <p class="text-muted small mb-0">Ringkasan akhir sebelum perubahan disimpan ke master COA.</p>
            </div>
            <div class="card-body px-4 pb-4">
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="border rounded-4 p-3 h-100">
                            <div class="small text-muted mb-1">Kode Akun</div>
                            <div class="fw-bold fs-5" id="preview_kd_akun">{{ strtoupper(trim(old('kd_akun', $coa->kd_akun))) ?: '-' }}</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border rounded-4 p-3 h-100 bg-light-primary">
                            <div class="small text-muted mb-1">COA Lengkap</div>
                            <div class="fw-bold fs-5 text-primary" id="preview_kode_lengkap">{{ collect([old('kd_program', $coa->kd_program), old('kd_giat', $coa->kd_giat), old('kd_output', $coa->kd_output), old('kd_suboutput', $coa->kd_suboutput), old('kd_komponen', $coa->kd_komponen), old('kd_subkomponen', $coa->kd_subkomponen), old('kd_akun', $coa->kd_akun), old('kd_item', $coa->kd_item)])->filter()->map(fn($item) => strtoupper(trim($item)))->implode('.') ?: '-' }}</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border rounded-4 p-3 h-100">
                            <div class="small text-muted mb-1">Nama Akun</div>
                            <div class="fw-bold fs-5" id="preview_nama_akun">{{ old('nama_akun', $coa->nama_akun) ?: '-' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-body p-4 d-flex justify-content-end flex-wrap gap-2">
                <a href="{{ route('coas.show', $coa) }}" class="btn btn-outline-secondary px-4">Batal</a>
                <button type="submit" class="btn btn-primary px-4">
                    <i class="bi bi-save me-1"></i> Simpan Perubahan
                </button>
            </div>
        </div>
    </form>
@endsection

@push('script')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const segmentInputs = document.querySelectorAll('.coa-segment');
        const namaAkunInput = document.querySelector('input[name="nama_akun"]');
        const kodeMakPreview = document.getElementById('kode_mak_preview');
        const previewKodeLengkap = document.getElementById('preview_kode_lengkap');
        const previewKdAkun = document.getElementById('preview_kd_akun');
        const previewNamaAkun = document.getElementById('preview_nama_akun');
        const kdAkunInput = document.querySelector('input[name="kd_akun"]');

        const normalizeValue = (value) => {
            return (value || '').trim().toUpperCase();
        };

        const renderPreview = () => {
            const segments = Array.from(segmentInputs)
                .map((input) => normalizeValue(input.value))
                .filter((value) => value !== '');

            const fullCode = segments.join('.');
            const akunCode = normalizeValue(kdAkunInput ? kdAkunInput.value : '');
            const akunName = (namaAkunInput ? namaAkunInput.value : '').trim();

            kodeMakPreview.textContent = fullCode || '-';
            previewKodeLengkap.textContent = fullCode || '-';
            previewKdAkun.textContent = akunCode || '-';
            previewNamaAkun.textContent = akunName || '-';
        };

        segmentInputs.forEach((input) => {
            input.addEventListener('input', renderPreview);
        });

        if (namaAkunInput) {
            namaAkunInput.addEventListener('input', renderPreview);
        }

        renderPreview();
    });
</script>
@endpush
