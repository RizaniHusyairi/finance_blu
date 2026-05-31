@php
    $r = $rekening ?? null;
    $val = fn (string $field, $default = null) => old($field, $r?->{$field} ?? $default);
    $jenisVal = old('jenis_rekening', $r?->jenis_rekening?->value ?? 'LAINNYA');
    $statusVal = old('status_aktif', $r ? ($r->status_aktif ? '1' : '0') : '1');
    $defaultVal = old('is_default', $r ? ($r->is_default ? '1' : '0') : '0');
@endphp

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

<div class="card shadow-sm border-0 rounded-4 mb-4">
    <div class="card-header bg-white border-bottom-0 pt-4 px-4">
        <h5 class="mb-1 fw-bold">Informasi Rekening</h5>
        <p class="text-muted small mb-0">Data bank dan pemilik rekening.</p>
    </div>
    <div class="card-body px-4 pb-4">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label fw-semibold">Nama Bank <span class="text-danger">*</span></label>
                <input type="text" name="nama_bank" class="form-control" value="{{ $val('nama_bank') }}" maxlength="100" placeholder="Contoh: BTN" required>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold">Nomor Rekening <span class="text-danger">*</span></label>
                <input type="text" name="nomor_rekening" class="form-control" value="{{ $val('nomor_rekening') }}" maxlength="50" required>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold">Atas Nama (Nama Rekening) <span class="text-danger">*</span></label>
                <input type="text" name="nama_rekening" class="form-control" value="{{ $val('nama_rekening') }}" maxlength="150" required>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold">Kode Bank</label>
                <input type="text" name="kode_bank" class="form-control" value="{{ $val('kode_bank') }}" maxlength="20" placeholder="Contoh: 200">
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold">Pemilik (Bendahara) <span class="text-danger">*</span></label>
                <select name="pemilik_id" class="form-select" required>
                    <option value="">-- Pilih --</option>
                    @foreach($pemilikOptions as $pemilik)
                        <option value="{{ $pemilik->id }}" @selected((string) $val('pemilik_id') === (string) $pemilik->id)>{{ $pemilik->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm border-0 rounded-4 mb-4">
    <div class="card-header bg-white border-bottom-0 pt-4 px-4">
        <h5 class="mb-1 fw-bold">Jenis Rekening</h5>
        <p class="text-muted small mb-0">Jenis menentukan peran rekening pada pembukuan. Saldo awal dicatat lewat menu Buku Kas Umum, bukan di sini.</p>
    </div>
    <div class="card-body px-4 pb-4">
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label fw-semibold">Jenis Rekening <span class="text-danger">*</span></label>
                <select name="jenis_rekening" class="form-select" required>
                    @foreach($jenisOptions as $v => $label)
                        <option value="{{ $v }}" @selected($jenisVal === $v)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold d-block">Default per Jenis</label>
                <div class="border rounded-4 p-3 mt-1">
                    <input type="hidden" name="is_default" value="0">
                    <div class="form-check form-switch d-flex align-items-center gap-2 ps-0">
                        <input class="form-check-input m-0 flex-shrink-0" type="checkbox" role="switch" id="is_default" name="is_default" value="1" {{ (string) $defaultVal === '1' ? 'checked' : '' }}>
                        <label class="form-check-label fw-semibold mb-0" for="is_default">Jadikan rekening default untuk jenis ini</label>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold d-block">Status Aktif</label>
                <div class="border rounded-4 p-3 mt-1">
                    <input type="hidden" name="status_aktif" value="0">
                    <div class="form-check form-switch d-flex align-items-center gap-2 ps-0">
                        <input class="form-check-input m-0 flex-shrink-0" type="checkbox" role="switch" id="status_aktif" name="status_aktif" value="1" {{ (string) $statusVal === '1' ? 'checked' : '' }}>
                        <label class="form-check-label fw-semibold mb-0" for="status_aktif">Rekening aktif</label>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
