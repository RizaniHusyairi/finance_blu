@php
    $isEdit = isset($pegawai) && $pegawai->exists;
@endphp

<div class="surface-card p-4 mb-4">
    <h6 class="fw-bold text-uppercase text-muted mb-3" style="letter-spacing: .08em; font-size: .75rem;">
        Identitas Pegawai
    </h6>
    <div class="row g-3">
        <div class="col-md-8">
            <label class="form-label fw-semibold">Nama Lengkap <span class="text-danger">*</span></label>
            <input type="text" name="nama_lengkap" value="{{ old('nama_lengkap', $pegawai->nama_lengkap) }}"
                   class="form-control" required>
        </div>
        <div class="col-md-4">
            <label class="form-label fw-semibold">Status</label>
            <div class="form-check form-switch mt-2">
                <input class="form-check-input" type="checkbox" name="status_aktif" value="1" id="statusAktif"
                       @checked(old('status_aktif', $pegawai->status_aktif ?? true))>
                <label class="form-check-label" for="statusAktif">Aktif</label>
            </div>
        </div>
        <div class="col-md-4">
            <label class="form-label fw-semibold">NIP</label>
            <input type="text" name="nip" value="{{ old('nip', $pegawai->nip) }}" class="form-control font-monospace">
        </div>
        <div class="col-md-4">
            <label class="form-label fw-semibold">NIK</label>
            <input type="text" name="nik" value="{{ old('nik', $pegawai->nik) }}" class="form-control font-monospace">
        </div>
        <div class="col-md-4">
            <label class="form-label fw-semibold">Nomor HP</label>
            <input type="text" name="nomor_hp" value="{{ old('nomor_hp', $pegawai->nomor_hp) }}" class="form-control">
        </div>
        <div class="col-md-12">
            <label class="form-label fw-semibold">Jabatan</label>
            <input type="text" name="jabatan" value="{{ old('jabatan', $pegawai->jabatan) }}" class="form-control">
        </div>
    </div>
</div>

<div class="surface-card p-4 mb-4">
    <h6 class="fw-bold text-uppercase text-muted mb-3" style="letter-spacing: .08em; font-size: .75rem;">
        Informasi Bank
    </h6>
    <div class="row g-3">
        <div class="col-md-4">
            <label class="form-label fw-semibold">Nama Bank</label>
            <input type="text" name="nama_bank" value="{{ old('nama_bank', $pegawai->nama_bank) }}" class="form-control">
        </div>
        <div class="col-md-4">
            <label class="form-label fw-semibold">Nomor Rekening</label>
            <input type="text" name="nomor_rekening" value="{{ old('nomor_rekening', $pegawai->nomor_rekening) }}"
                   class="form-control font-monospace">
        </div>
        <div class="col-md-4">
            <label class="form-label fw-semibold">Nama Pemilik Rekening</label>
            <input type="text" name="nama_rekening" value="{{ old('nama_rekening', $pegawai->nama_rekening) }}"
                   class="form-control">
        </div>
    </div>
</div>

<div class="d-flex justify-content-between align-items-center">
    <a href="{{ route('admin.pegawai.index') }}" class="btn btn-light">
        <i class="bi bi-arrow-left me-1"></i> Batal
    </a>
    <button class="btn btn-gradient px-4">
        <i class="bi bi-check2-circle me-1"></i> {{ $isEdit ? 'Simpan Perubahan' : 'Simpan Pegawai' }}
    </button>
</div>
