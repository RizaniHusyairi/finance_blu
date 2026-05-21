@extends('layouts.app')

@section('title', 'Tambah COA')

@php
    $oldStatusAktif = old('status_aktif', '1');
@endphp

@section('content')
    <x-page-title title="Tambah COA" subtitle="Lengkapi struktur kode dan informasi akun untuk menambahkan master COA baru" />

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

    <form action="{{ route('coas.store') }}" method="POST">
        @csrf

        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
            <div>
                <h5 class="mb-1 fw-bold">Form Tambah COA</h5>
                <p class="text-muted mb-0">Kode lengkap akan dibentuk otomatis dari struktur kode yang Anda isi, lalu diverifikasi ulang saat disimpan.</p>
            </div>
            <a href="{{ route('coas.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar COA
            </a>
        </div>

        <div class="card shadow-sm border-0 rounded-4 mb-4">
            <div class="card-header bg-white border-bottom-0 pt-4 px-4">
                <h5 class="mb-1 fw-bold">Struktur Kode COA</h5>
                <p class="text-muted small mb-0">Isi setiap segmen kode sesuai struktur COA yang berlaku agar sistem membentuk kode MAK lengkap dengan benar.</p>
            </div>
            <div class="card-body px-4 pb-4">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Kode Program</label>
                        <input type="text" name="kd_program" class="form-control coa-segment" value="{{ old('kd_program') }}" maxlength="10" placeholder="Contoh: 01">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Kode Giat</label>
                        <input type="text" name="kd_giat" class="form-control coa-segment" value="{{ old('kd_giat') }}" maxlength="10" placeholder="Contoh: 1234">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Kode Output</label>
                        <input type="text" name="kd_output" class="form-control coa-segment" value="{{ old('kd_output') }}" maxlength="10" placeholder="Contoh: 001">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Kode Suboutput</label>
                        <input type="text" name="kd_suboutput" class="form-control coa-segment" value="{{ old('kd_suboutput') }}" maxlength="10" placeholder="Contoh: A">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Kode Komponen</label>
                        <input type="text" name="kd_komponen" class="form-control coa-segment" value="{{ old('kd_komponen') }}" maxlength="10" placeholder="Contoh: 051">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Kode Subkomponen</label>
                        <input type="text" name="kd_subkomponen" class="form-control coa-segment" value="{{ old('kd_subkomponen') }}" maxlength="10" placeholder="Contoh: 01">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Kode Akun <span class="text-danger">*</span></label>
                        <input type="text" name="kd_akun" class="form-control coa-segment" value="{{ old('kd_akun') }}" maxlength="20" placeholder="Contoh: 521219" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Kode Item</label>
                        <input type="text" name="kd_item" class="form-control coa-segment" value="{{ old('kd_item') }}" maxlength="20" placeholder="Contoh: 0001">
                    </div>
                    <div class="col-12">
                        <div class="border rounded-4 p-3 bg-light">
                            <div class="small text-muted mb-1">Preview Kode Lengkap</div>
                            <div class="fw-bold fs-5 text-primary" id="kode_mak_preview">{{ collect([old('kd_program'), old('kd_giat'), old('kd_output'), old('kd_suboutput'), old('kd_komponen'), old('kd_subkomponen'), old('kd_akun'), old('kd_item')])->filter()->map(fn($item) => strtoupper(trim($item)))->implode('.') ?: '-' }}</div>
                            <div class="small text-muted mt-1">Preview ini hanya panduan. Sistem akan membentuk ulang kode lengkap saat proses simpan.</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0 rounded-4 mb-4">
            <div class="card-header bg-white border-bottom-0 pt-4 px-4">
                <h5 class="mb-1 fw-bold">Informasi Akun</h5>
                <p class="text-muted small mb-0">Lengkapi nama akun, jenis akun, dan status aktif COA.</p>
            </div>
            <div class="card-body px-4 pb-4">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Nama Akun <span class="text-danger">*</span></label>
                        <input type="text" name="nama_akun" class="form-control" value="{{ old('nama_akun') }}" maxlength="150" placeholder="Contoh: Belanja Honorarium Narasumber" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Jenis Akun</label>
                        <input type="text" name="jenis_akun" class="form-control" value="{{ old('jenis_akun') }}" maxlength="50" placeholder="Contoh: BELANJA BARANG">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold d-block">Status Aktif</label>
                        <div class="border rounded-4 p-3 mt-1">
                            <input type="hidden" name="status_aktif" value="0">
                            <div class="form-check form-switch d-flex align-items-center gap-2 ps-0 mb-1">
                                <input class="form-check-input m-0 flex-shrink-0" type="checkbox" role="switch" id="status_aktif" name="status_aktif" value="1" aria-describedby="status_aktif_help" {{ (string) $oldStatusAktif === '1' ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold mb-0" for="status_aktif">
                                    COA aktif
                                </label>
                            </div>
                            <div class="small text-muted" id="status_aktif_help">Aktifkan agar COA siap dipakai. Matikan bila COA hanya ingin disimpan sebagai referensi.</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0 rounded-4 mb-4">
            <div class="card-header bg-white border-bottom-0 pt-4 px-4">
                <h5 class="mb-1 fw-bold">Preview COA</h5>
                <p class="text-muted small mb-0">Ringkasan akhir untuk membantu memastikan kombinasi kode dan nama akun sudah benar.</p>
            </div>
            <div class="card-body px-4 pb-4">
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="border rounded-4 p-3 h-100">
                            <div class="small text-muted mb-1">Kode Akun</div>
                            <div class="fw-bold fs-5" id="preview_kd_akun">{{ strtoupper(trim(old('kd_akun', ''))) ?: '-' }}</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border rounded-4 p-3 h-100 bg-light-primary">
                            <div class="small text-muted mb-1">COA Lengkap</div>
                            <div class="fw-bold fs-5 text-primary" id="preview_kode_lengkap">{{ collect([old('kd_program'), old('kd_giat'), old('kd_output'), old('kd_suboutput'), old('kd_komponen'), old('kd_subkomponen'), old('kd_akun'), old('kd_item')])->filter()->map(fn($item) => strtoupper(trim($item)))->implode('.') ?: '-' }}</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border rounded-4 p-3 h-100">
                            <div class="small text-muted mb-1">Nama Akun</div>
                            <div class="fw-bold fs-5" id="preview_nama_akun">{{ old('nama_akun') ?: '-' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-body p-4 d-flex justify-content-end flex-wrap gap-2">
                <a href="{{ route('coas.index') }}" class="btn btn-outline-secondary px-4">Batal</a>
                <button type="submit" name="redirect_action" value="save" class="btn btn-primary px-4">
                    <i class="bi bi-save me-1"></i> Simpan
                </button>
                <button type="submit" name="redirect_action" value="save_and_create" class="btn btn-success px-4">
                    <i class="bi bi-plus-circle me-1"></i> Simpan &amp; Tambah Lagi
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
