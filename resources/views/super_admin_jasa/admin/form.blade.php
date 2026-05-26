@extends('layouts.app')
@section('title', $admin->exists ? 'Edit Admin Jasa' : 'Tambah Admin Jasa')

@include('super_admin_jasa.partials.form-style')

@if(!$admin->exists)
@push('css')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
<style>
    /* Premium pegawai picker (Select2 — theme: jasa-pick) */
    .jasa-pick { position: relative; }
    .jasa-pick .jasa-pick-icon {
        position: absolute;
        left: 16px; top: 50%; transform: translateY(-50%);
        color: #1d4ed8; pointer-events: none; z-index: 3;
        font-size: 1rem;
    }
    .jasa-pick .select2-container .select2-selection--single {
        height: 54px !important;
        border: 1px solid #c7d8f5 !important;
        background: linear-gradient(135deg, #f6f9ff 0%, #ffffff 100%) !important;
        border-radius: 14px !important;
        padding: 0 0.5rem 0 2.8rem !important;
        transition: all .2s ease !important;
        box-shadow: 0 2px 6px rgba(37, 99, 235, .06);
    }
    .jasa-pick .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 52px !important;
        color: #0f172a; font-size: .95rem; font-weight: 600;
        padding-left: 0 !important; padding-right: 2.5rem !important;
    }
    .jasa-pick .select2-container--default .select2-selection--single .select2-selection__placeholder {
        color: #94a3b8 !important; font-weight: 500;
    }
    .jasa-pick .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 52px !important; width: 38px !important; right: 8px !important;
    }
    .jasa-pick .select2-container--default .select2-selection--single .select2-selection__arrow b {
        border-color: #1d4ed8 transparent transparent transparent !important;
        border-width: 6px 5px 0 5px !important; transition: transform .2s ease;
    }
    .jasa-pick .select2-container--default.select2-container--open .select2-selection--single .select2-selection__arrow b {
        transform: rotate(180deg); margin-top: -1px !important;
    }
    .jasa-pick .select2-container--default .select2-selection--single:hover {
        border-color: #93b4ee !important; background: #ffffff !important;
    }
    .jasa-pick .select2-container--default.select2-container--focus .select2-selection--single,
    .jasa-pick .select2-container--default.select2-container--open .select2-selection--single {
        border-color: #2563eb !important; background: #ffffff !important;
        box-shadow: 0 0 0 4px rgba(37, 99, 235, .14), 0 8px 18px rgba(37, 99, 235, .14) !important;
    }

    .select2-container--jasa-pick .select2-dropdown {
        border: 1px solid #c7d8f5 !important;
        border-radius: 14px !important;
        box-shadow: 0 20px 50px rgba(15, 23, 42, .16), 0 4px 14px rgba(37, 99, 235, .12) !important;
        overflow: hidden; background: #fff; margin-top: 6px;
        animation: jasaPickIn .22s cubic-bezier(.22,1,.36,1) both;
    }
    @keyframes jasaPickIn {
        from { opacity: 0; transform: translateY(-6px) scale(.98); }
        to   { opacity: 1; transform: translateY(0) scale(1); }
    }
    .select2-container--jasa-pick .select2-search--dropdown {
        padding: .7rem !important;
        background: linear-gradient(180deg, #f6f9ff 0%, #ffffff 100%);
        border-bottom: 1px solid #eef2f7;
    }
    .select2-container--jasa-pick .select2-search--dropdown .select2-search__field {
        border: 1px solid #e2e8f0 !important;
        border-radius: 10px !important;
        padding: .55rem .85rem .55rem 2.4rem !important;
        font-size: .88rem !important;
        background: #f8fafc url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' fill='%2394a3b8' viewBox='0 0 16 16'%3E%3Cpath d='M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z'/%3E%3C/svg%3E") no-repeat .9rem center !important;
        outline: 0 !important; transition: all .18s ease;
    }
    .select2-container--jasa-pick .select2-search--dropdown .select2-search__field:focus {
        border-color: #2563eb !important; background-color: #fff !important;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, .14) !important;
    }
    .select2-container--jasa-pick .select2-results__options {
        padding: .4rem !important; max-height: 340px !important; overflow-y: auto !important;
    }
    .select2-container--jasa-pick .select2-results__option {
        padding: .6rem .8rem !important; font-size: .88rem; color: #334155;
        border-radius: 10px; margin: 1px 0; transition: all .14s ease; cursor: pointer; line-height: 1.45;
    }
    .select2-container--jasa-pick .select2-results__option--highlighted[aria-selected],
    .select2-container--jasa-pick .select2-results__option--highlighted {
        background: linear-gradient(135deg, rgba(37, 99, 235, .10), rgba(125, 211, 252, .14)) !important;
        color: #1e3a8a !important; transform: translateX(2px);
    }
    .select2-container--jasa-pick .select2-results__option[aria-selected=true] {
        background: linear-gradient(135deg, #12355c, #1d65a6) !important;
        color: #fff !important; font-weight: 600;
        box-shadow: 0 6px 14px rgba(18, 53, 92, .30);
    }
    .select2-container--jasa-pick .select2-results__options::-webkit-scrollbar { width: 8px; }
    .select2-container--jasa-pick .select2-results__options::-webkit-scrollbar-track { background: #f8fafc; }
    .select2-container--jasa-pick .select2-results__options::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 999px; }

    .jasa-pick-row { display: flex; align-items: center; gap: .75rem; }
    .jasa-pick-row .jasa-pick-avatar {
        width: 38px; height: 38px; border-radius: 50%;
        display: inline-flex; align-items: center; justify-content: center;
        font-weight: 800; font-size: .88rem; color: #fff;
        background: linear-gradient(135deg, #1d65a6, #12355c);
        box-shadow: 0 4px 10px rgba(18, 53, 92, .25); flex-shrink: 0;
    }
    .select2-container--jasa-pick .select2-results__option[aria-selected=true] .jasa-pick-avatar {
        background: rgba(255, 255, 255, .22);
        box-shadow: inset 0 0 0 1px rgba(255, 255, 255, .35);
    }
    .jasa-pick-row .jasa-pick-info { flex: 1 1 auto; min-width: 0; }
    .jasa-pick-row .jasa-pick-name {
        font-weight: 700; color: #0f172a; font-size: .92rem;
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }
    .select2-container--jasa-pick .select2-results__option[aria-selected=true] .jasa-pick-name { color: #fff; }
    .jasa-pick-row .jasa-pick-meta {
        font-size: .72rem; color: #64748b;
        display: flex; gap: .55rem; align-items: center; margin-top: 1px;
        font-family: ui-monospace, "SF Mono", Menlo, Consolas, monospace;
    }
    .select2-container--jasa-pick .select2-results__option[aria-selected=true] .jasa-pick-meta { color: rgba(255,255,255,.85); }
    .jasa-pick-row .jasa-pick-badge {
        font-size: .62rem; font-weight: 800; text-transform: uppercase; letter-spacing: .04em;
        padding: .15rem .5rem; border-radius: 999px;
        background: rgba(37, 99, 235, .12); color: #1e40af;
    }
    .select2-container--jasa-pick .select2-results__option[aria-selected=true] .jasa-pick-badge {
        background: rgba(255,255,255,.22); color: #fff;
    }

    /* Live preview card */
    .pegawai-preview {
        position: relative; overflow: hidden;
        border: 1px solid #c7d8f5; border-radius: 16px;
        background: linear-gradient(135deg, #f6f9ff 0%, #ffffff 65%);
        padding: 18px 20px;
        display: grid; gap: 14px;
        grid-template-columns: 56px 1fr;
        align-items: center;
        box-shadow: 0 6px 18px rgba(37, 99, 235, .06);
        transition: all .25s ease;
    }
    .pegawai-preview::before {
        content: ""; position: absolute; inset: 0 auto auto 0;
        width: 4px; height: 100%;
        background: linear-gradient(180deg, #1d65a6, #12355c);
    }
    .pegawai-preview.is-empty {
        background: repeating-linear-gradient(135deg, #f8fafc, #f8fafc 12px, #f1f5f9 12px, #f1f5f9 24px);
        border-style: dashed; color: #94a3b8;
        grid-template-columns: 1fr;
        text-align: center; padding: 22px;
    }
    .pegawai-preview.is-empty::before { display: none; }
    .pegawai-preview .pp-avatar {
        width: 56px; height: 56px; border-radius: 50%;
        background: linear-gradient(135deg, #1d65a6, #12355c);
        color: #fff; font-weight: 900; font-size: 1.35rem;
        display: inline-flex; align-items: center; justify-content: center;
        box-shadow: 0 8px 20px rgba(18, 53, 92, .25);
    }
    .pegawai-preview .pp-name {
        font-weight: 800; color: #0f172a; font-size: 1.02rem; margin-bottom: 6px;
    }
    .pegawai-preview .pp-chips { display: flex; flex-wrap: wrap; gap: 8px; }
    .pegawai-preview .pp-chip {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 4px 10px; border-radius: 999px;
        background: #fff; border: 1px solid #dbe7fb;
        color: #1e3a8a; font-size: .75rem; font-weight: 700;
    }
    .pegawai-preview .pp-chip i { color: #2563eb; font-size: .85rem; }
    .pegawai-preview .pp-chip.is-muted { color: #94a3b8; background: #f8fafc; border-style: dashed; }
    .pegawai-preview .pp-chip.is-muted i { color: #94a3b8; }
</style>
@endpush
@endif

@section('content')
<div class="jasa-form-hero mb-4 px-4 py-4">
    <div class="d-flex flex-column flex-lg-row gap-3 justify-content-between align-items-lg-center position-relative">
        <div class="d-flex gap-3 align-items-start">
            <span class="d-inline-flex align-items-center justify-content-center rounded-3 bg-white text-primary shadow-sm" style="width:44px;height:44px;">
                <i class="bi bi-person-badge fs-5"></i>
            </span>
            <div>
                <h4 class="mb-1 fw-black">{{ $admin->exists ? 'Edit Admin Jasa' : 'Tambah Admin Jasa' }}</h4>
                <p class="mb-0 fw-semibold small">Admin Jasa mengelola layanan, mitra terkait, dan pembuatan tagihan sesuai scope tugas.</p>
            </div>
        </div>
        <a href="{{ route('jasa.admin.index') }}" class="btn btn-light text-primary fw-bold shadow-sm jasa-icon-btn" title="Kembali" aria-label="Kembali">
            <i class="bi bi-arrow-left"></i>
        </a>
    </div>
</div>

@if($errors->any())
    <div class="alert alert-danger rounded-4">
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ $admin->exists ? route('jasa.admin.update', $admin) : route('jasa.admin.store') }}">
    @csrf
    @if($admin->exists)
        @method('PUT')
    @endif

    <div class="jasa-form-card">
        <div class="jasa-form-section">
            <div class="jasa-section-title">
                <span class="jasa-section-icon"><i class="bi bi-person-vcard"></i></span>
                <div>
                    <h6>Profil Pegawai</h6>
                    <p>Identitas admin yang akan diberi akses pada modul jasa.</p>
                </div>
            </div>

            <div class="row g-3">
                @if(!$admin->exists)
                    <div class="col-12">
                        <label class="form-label">Nama Admin Jasa <span class="text-danger">*</span></label>
                        <div class="jasa-pick">
                            <i class="bi bi-person-badge jasa-pick-icon"></i>
                            <select name="pegawai_id" id="pegawai-select" class="form-select" required>
                                <option value="">— Pilih pegawai —</option>
                                @foreach($pegawaiOptions as $p)
                                    @php $initial = strtoupper(mb_substr($p->nama_lengkap, 0, 1)); @endphp
                                    <option value="{{ $p->id }}"
                                            data-name="{{ $p->nama_lengkap }}"
                                            data-nip="{{ $p->nip }}"
                                            data-jabatan="{{ $p->jabatan }}"
                                            data-npwp="{{ $p->npwp }}"
                                            data-initial="{{ $initial }}"
                                            @selected(old('pegawai_id') == $p->id)>
                                        {{ $p->nama_lengkap }}@if($p->nip) — NIP {{ $p->nip }}@endif
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-text mt-2">
                            <i class="bi bi-search me-1"></i>Ketik nama atau NIP untuk mencari. Hanya pegawai aktif yang belum memiliki akun yang ditampilkan.
                        </div>
                    </div>
                    <div class="col-12">
                        <div id="pegawai-preview" class="pegawai-preview is-empty">
                            <div>
                                <i class="bi bi-person-lines-fill fs-4 d-block mb-1"></i>
                                <div class="small fw-semibold">Belum ada pegawai dipilih</div>
                                <div class="small">Pilih nama di atas untuk melihat detail pegawai.</div>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="col-lg-8">
                        <label class="form-label">Nama Admin Jasa <span class="text-danger">*</span></label>
                        <input type="text" name="nama_lengkap" class="form-control" value="{{ old('nama_lengkap', $pegawai->nama_lengkap) }}" required placeholder="Nama lengkap admin">
                    </div>
                    <div class="col-lg-4">
                        <label class="form-label">NIP</label>
                        <input type="text" name="nip" class="form-control" value="{{ old('nip', $pegawai->nip) }}" placeholder="NIP pegawai">
                    </div>
                    <div class="col-lg-4">
                        <label class="form-label">Jabatan</label>
                        <input type="text" name="jabatan" class="form-control" value="{{ old('jabatan', $pegawai->jabatan ?: 'Admin Jasa') }}" placeholder="Admin Jasa">
                    </div>
                    <div class="col-lg-4">
                        <label class="form-label">NPWP</label>
                        <input type="text" name="npwp" class="form-control" value="{{ old('npwp', $pegawai->npwp) }}" placeholder="NPWP">
                    </div>
                    <div class="col-lg-4">
                        <label class="form-label">Status Profil</label>
                        <select name="status_aktif" class="form-select">
                            <option value="1" @selected(old('status_aktif', $pegawai->status_aktif ?? true))>Aktif</option>
                            <option value="0" @selected(old('status_aktif', $admin->exists ? !($pegawai->status_aktif ?? true) : false))>Nonaktif</option>
                        </select>
                    </div>
                @endif
            </div>
        </div>

        <div class="jasa-form-section">
            <div class="jasa-section-title">
                <span class="jasa-section-icon"><i class="bi bi-shield-lock"></i></span>
                <div>
                    <h6>Akun Login</h6>
                    <p>Email dan password untuk masuk sebagai Admin Jasa.</p>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-lg-6">
                    <label class="form-label">Email Login <span class="text-danger">*</span></label>
                    <input type="email" name="email" class="form-control" value="{{ old('email', $admin->email) }}" required placeholder="admin@domain.go.id">
                </div>
                <div class="col-lg-6">
                    <label class="form-label">{{ $admin->exists ? 'Password Baru' : 'Password Awal' }} @if(!$admin->exists)<span class="text-danger">*</span>@endif</label>
                    <input type="password" name="password" class="form-control" {{ $admin->exists ? '' : 'required' }} placeholder="{{ $admin->exists ? 'Kosongkan jika tidak diubah' : 'Masukkan password awal' }}">
                    @if($admin->exists)
                        <div class="form-text">Kosongkan jika password tidak diubah.</div>
                    @endif
                </div>
                <div class="col-12">
                    <div class="jasa-helper-panel">
                        <strong>Scope layanan</strong>
                        <div class="small mt-1">Setelah akun dibuat, atur layanan yang dikelola admin melalui halaman detail Admin Jasa.</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="jasa-action-footer">
            <div class="d-flex flex-column flex-sm-row justify-content-end gap-2">
                <a href="{{ route('jasa.admin.index') }}" class="btn btn-light fw-bold text-secondary border px-4">Batal</a>
                <button class="btn btn-primary fw-bold px-4" type="submit">
                    <i class="bi bi-save me-1"></i>Simpan Admin Jasa
                </button>
            </div>
        </div>
    </div>
</form>

@if(!$admin->exists)
@push('script')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    (function ($) {
        if (!window.jQuery) return;

        const escapeHtml = (str) => String(str ?? '')
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');

        const formatRow = (option) => {
            if (!option.id) return option.text;
            const $opt = $(option.element);
            const name    = escapeHtml($opt.data('name') || option.text);
            const nip     = escapeHtml($opt.data('nip') || '');
            const jabatan = escapeHtml($opt.data('jabatan') || '');
            const initial = escapeHtml($opt.data('initial') || (name.charAt(0) || '?').toUpperCase());

            const metaParts = [];
            if (nip)     metaParts.push(`<i class="bi bi-credit-card-2-front"></i> ${nip}`);
            if (jabatan) metaParts.push(`<i class="bi bi-briefcase"></i> ${jabatan}`);
            const meta = metaParts.length ? metaParts.join('<span class="text-muted">·</span>') : '<span class="text-muted">Tanpa NIP / jabatan</span>';

            return $(`
                <div class="jasa-pick-row">
                    <span class="jasa-pick-avatar">${initial}</span>
                    <div class="jasa-pick-info">
                        <div class="jasa-pick-name">${name}</div>
                        <div class="jasa-pick-meta">${meta}</div>
                    </div>
                    <span class="jasa-pick-badge">Pegawai</span>
                </div>
            `);
        };

        const formatSelection = (option) => {
            if (!option.id) return option.text || '— Pilih pegawai —';
            const $opt = $(option.element);
            return $opt.data('name') || option.text;
        };

        const $sel = $('#pegawai-select');
        $sel.select2({
            theme: 'jasa-pick',
            width: '100%',
            placeholder: '— Pilih pegawai —',
            allowClear: false,
            dropdownAutoWidth: true,
            templateResult: formatRow,
            templateSelection: formatSelection,
            escapeMarkup: (m) => m,
            language: {
                noResults: () => 'Tidak ditemukan',
                searching: () => 'Mencari…',
                inputTooShort: () => 'Ketik untuk mencari',
            },
        });

        const $preview = $('#pegawai-preview');
        const renderPreview = () => {
            const opt = $sel[0].options[$sel[0].selectedIndex];
            const data = (opt && opt.value) ? opt.dataset : null;

            if (!data) {
                $preview.addClass('is-empty').html(`
                    <div>
                        <i class="bi bi-person-lines-fill fs-4 d-block mb-1"></i>
                        <div class="small fw-semibold">Belum ada pegawai dipilih</div>
                        <div class="small">Pilih nama di atas untuk melihat detail pegawai.</div>
                    </div>
                `);
                return;
            }

            const name    = escapeHtml(data.name || '—');
            const initial = escapeHtml(data.initial || (name.charAt(0) || '?'));
            const nip     = data.nip     ? `<span class="pp-chip"><i class="bi bi-credit-card-2-front"></i> NIP ${escapeHtml(data.nip)}</span>`
                                         : `<span class="pp-chip is-muted"><i class="bi bi-credit-card-2-front"></i> NIP belum diisi</span>`;
            const jabatan = data.jabatan ? `<span class="pp-chip"><i class="bi bi-briefcase"></i> ${escapeHtml(data.jabatan)}</span>`
                                         : `<span class="pp-chip is-muted"><i class="bi bi-briefcase"></i> Jabatan belum diisi</span>`;
            const npwp    = data.npwp    ? `<span class="pp-chip"><i class="bi bi-file-earmark-text"></i> NPWP ${escapeHtml(data.npwp)}</span>`
                                         : `<span class="pp-chip is-muted"><i class="bi bi-file-earmark-text"></i> NPWP belum diisi</span>`;

            $preview.removeClass('is-empty').html(`
                <div class="pp-avatar">${initial}</div>
                <div>
                    <div class="pp-name">${name}</div>
                    <div class="pp-chips">${nip}${jabatan}${npwp}</div>
                </div>
            `);
        };

        $sel.on('change', renderPreview);
        renderPreview();
    })(window.jQuery);
</script>
@endpush
@endif
@endsection
