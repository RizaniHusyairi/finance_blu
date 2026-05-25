@extends('layouts.app')

@section('title', 'Tambah User')

@push('css')
    @include('admin._partials.styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        /* ===== Pegawai/Mitra premium select ===== */
        .au-pick {
            position: relative;
        }
        .au-pick .au-pick-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #6366f1;
            pointer-events: none;
            z-index: 3;
            font-size: 1.05rem;
        }
        .au-pick .select2-container .select2-selection--single {
            height: 52px !important;
            border: 1px solid #c7d2fe !important;
            background: linear-gradient(135deg, #fafbff 0%, #ffffff 100%) !important;
            border-radius: 0.85rem !important;
            padding: 0 0.4rem 0 2.6rem !important;
            transition: all .2s ease !important;
            box-shadow: 0 2px 6px rgba(99, 102, 241, .05);
        }
        .au-pick .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 50px !important;
            color: #1e293b;
            font-size: 0.95rem;
            font-weight: 500;
            padding-left: 0 !important;
            padding-right: 2.5rem !important;
        }
        .au-pick .select2-container--default .select2-selection--single .select2-selection__placeholder {
            color: #94a3b8 !important;
            font-weight: 400;
        }
        .au-pick .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 50px !important;
            width: 36px !important;
            right: 8px !important;
        }
        .au-pick .select2-container--default .select2-selection--single .select2-selection__arrow b {
            border-color: #6366f1 transparent transparent transparent !important;
            border-width: 6px 5px 0 5px !important;
            transition: transform .2s ease;
        }
        .au-pick .select2-container--default.select2-container--open .select2-selection--single .select2-selection__arrow b {
            transform: rotate(180deg);
            margin-top: -1px !important;
        }
        .au-pick .select2-container--default .select2-selection--single:hover {
            border-color: #818cf8 !important;
            background: #ffffff !important;
        }
        .au-pick .select2-container--default.select2-container--focus .select2-selection--single,
        .au-pick .select2-container--default.select2-container--open .select2-selection--single {
            border-color: #6366f1 !important;
            background: #ffffff !important;
            box-shadow: 0 0 0 4px rgba(99, 102, 241, .12), 0 6px 16px rgba(99, 102, 241, .15) !important;
        }

        /* Dropdown panel */
        .select2-container--au-pick .select2-dropdown {
            border: 1px solid #c7d2fe !important;
            border-radius: 0.85rem !important;
            box-shadow: 0 18px 44px rgba(15, 23, 42, .14), 0 4px 12px rgba(99, 102, 241, .12) !important;
            overflow: hidden;
            background: #ffffff;
            margin-top: 6px;
            animation: auPickDropdownIn .22s cubic-bezier(.22,1,.36,1) both;
        }
        @keyframes auPickDropdownIn {
            from { opacity: 0; transform: translateY(-6px) scale(.98); }
            to   { opacity: 1; transform: translateY(0) scale(1); }
        }
        .select2-container--au-pick .select2-search--dropdown {
            padding: .65rem !important;
            background: linear-gradient(180deg, #fafbff 0%, #ffffff 100%);
            border-bottom: 1px solid #f1f3f7;
        }
        .select2-container--au-pick .select2-search--dropdown .select2-search__field {
            border: 1px solid #e2e8f0 !important;
            border-radius: 0.65rem !important;
            padding: 0.55rem 0.85rem 0.55rem 2.4rem !important;
            font-size: 0.88rem !important;
            background: #f8fafc url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' fill='%2394a3b8' viewBox='0 0 16 16'%3E%3Cpath d='M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z'/%3E%3C/svg%3E") no-repeat .9rem center !important;
            transition: all .18s ease;
            outline: 0 !important;
        }
        .select2-container--au-pick .select2-search--dropdown .select2-search__field:focus {
            border-color: #6366f1 !important;
            background-color: #ffffff !important;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, .12) !important;
        }
        .select2-container--au-pick .select2-results__options {
            padding: 0.4rem !important;
            max-height: 320px !important;
            overflow-y: auto !important;
        }
        .select2-container--au-pick .select2-results__option {
            padding: 0.65rem 0.85rem !important;
            font-size: 0.88rem;
            color: #334155;
            border-radius: 0.55rem;
            margin: 1px 0;
            transition: all .14s ease;
            cursor: pointer;
            line-height: 1.45;
        }
        .select2-container--au-pick .select2-results__option--highlighted[aria-selected],
        .select2-container--au-pick .select2-results__option--highlighted {
            background: linear-gradient(135deg, rgba(99, 102, 241, .12), rgba(139, 92, 246, .08)) !important;
            color: #4338ca !important;
            font-weight: 600;
            transform: translateX(2px);
        }
        .select2-container--au-pick .select2-results__option[aria-selected=true] {
            background: linear-gradient(135deg, #6366f1, #8b5cf6) !important;
            color: #ffffff !important;
            font-weight: 600;
            box-shadow: 0 4px 10px rgba(99, 102, 241, .30);
        }
        .select2-container--au-pick .select2-results__option[aria-selected=true]::after {
            content: '\F26B';
            font-family: 'bootstrap-icons';
            margin-left: 0.65rem;
            float: right;
            font-size: 0.85rem;
        }
        .select2-container--au-pick .select2-results__options::-webkit-scrollbar { width: 8px; }
        .select2-container--au-pick .select2-results__options::-webkit-scrollbar-track { background: #f8fafc; }
        .select2-container--au-pick .select2-results__options::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 999px; }

        /* Custom row in dropdown — name + NIP/code subtitle */
        .au-pick-row {
            display: flex;
            align-items: center;
            gap: 0.7rem;
        }
        .au-pick-row .au-pick-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 0.86rem;
            color: #ffffff;
            background: linear-gradient(135deg, #818cf8, #6366f1);
            box-shadow: 0 4px 10px rgba(99, 102, 241, .25);
            flex-shrink: 0;
        }
        .au-pick-row.is-mitra .au-pick-avatar {
            background: linear-gradient(135deg, #34d399, #10b981);
            box-shadow: 0 4px 10px rgba(16, 185, 129, .25);
        }
        .au-pick-row .au-pick-info { flex: 1 1 auto; min-width: 0; }
        .au-pick-row .au-pick-name {
            font-weight: 700;
            color: #0f172a;
            font-size: 0.9rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .select2-container--au-pick .select2-results__option--highlighted .au-pick-name { color: #4338ca; }
        .select2-container--au-pick .select2-results__option[aria-selected=true] .au-pick-name { color: #ffffff; }
        .au-pick-row .au-pick-meta {
            font-size: 0.72rem;
            color: #64748b;
            display: flex;
            gap: 0.55rem;
            align-items: center;
            margin-top: 1px;
            font-family: ui-monospace, "SF Mono", Menlo, Consolas, monospace;
        }
        .select2-container--au-pick .select2-results__option[aria-selected=true] .au-pick-meta { color: rgba(255,255,255,.85); }
        .au-pick-row .au-pick-badge {
            font-size: 0.62rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .04em;
            padding: 0.12rem 0.45rem;
            border-radius: 999px;
            background: rgba(99, 102, 241, .12);
            color: #4338ca;
        }
        .au-pick-row.is-mitra .au-pick-badge {
            background: rgba(16, 185, 129, .14);
            color: #047857;
        }
        .select2-container--au-pick .select2-results__option[aria-selected=true] .au-pick-badge {
            background: rgba(255,255,255,.22);
            color: #ffffff;
        }
    </style>
@endpush

@section('content')
    <x-page-title title="Manajemen User" subtitle="Tambah User" />

    <div class="admin-hero d-flex align-items-center gap-3 mb-4">
        <div class="hero-icon"><i class="material-icons-outlined">person_add</i></div>
        <div>
            <h1>Tambah Akun Baru</h1>
            <p>Pilih tipe akun, tautkan ke pegawai/mitra (jika ada), lalu tentukan role yang sesuai.</p>
        </div>
    </div>

    @include('admin._partials.flash')

    <form method="POST" action="{{ route('admin.users.store') }}" id="user-create-form">
        @csrf

        <div class="surface-card p-4 mb-4">
            <h6 class="fw-bold text-uppercase text-muted mb-3" style="letter-spacing: .08em; font-size: .75rem;">
                1. Tipe Akun
            </h6>
            <div class="tipe-akun-grid">
                @php
                    $tipeAkunOpts = [
                        ['pegawai', 'Pegawai', 'Akun untuk pegawai internal yang sudah ada di master pegawai.', 'badge'],
                        ['mitra',   'Mitra Jasa', 'Akun untuk mitra konsesi/PJP2U yang sudah terdaftar.', 'storefront'],
                        ['sistem',  'Sistem',  'Akun teknis tanpa pegawai. Hanya untuk role Super Admin.', 'shield'],
                    ];
                    $current = old('tipe_akun', 'pegawai');
                @endphp
                @foreach ($tipeAkunOpts as [$val, $label, $desc, $icon])
                    <label class="tipe-akun-card {{ $current === $val ? 'is-active' : '' }}" data-tipe="{{ $val }}">
                        <input type="radio" name="tipe_akun" value="{{ $val }}" @checked($current === $val)>
                        <div class="tipe-akun-icon"><i class="material-icons-outlined">{{ $icon }}</i></div>
                        <h6>{{ $label }}</h6>
                        <small>{{ $desc }}</small>
                    </label>
                @endforeach
            </div>
        </div>

        <div class="surface-card p-4 mb-4">
            <h6 class="fw-bold text-uppercase text-muted mb-3" style="letter-spacing: .08em; font-size: .75rem;">
                2. Tautan Profil
            </h6>

            <div data-tipe-section="pegawai">
                <label class="form-label fw-semibold" for="pegawai_id_select">Pegawai</label>
                <div class="au-pick">
                    <i class="bi bi-person-badge au-pick-icon"></i>
                    <select name="pegawai_id" id="pegawai_id_select" class="form-select au-pick-select" data-pick="pegawai">
                        <option value="">— Pilih pegawai —</option>
                        @foreach ($pegawaiOptions as $p)
                            @php
                                $initial = strtoupper(mb_substr($p->nama_lengkap, 0, 1));
                            @endphp
                            <option value="{{ $p->id }}" @selected(old('pegawai_id') == $p->id)
                                    data-name="{{ $p->nama_lengkap }}"
                                    data-nip="{{ $p->nip }}"
                                    data-jabatan="{{ $p->jabatan ?? '' }}"
                                    data-initial="{{ $initial }}"
                                    data-email-suggest="{{ \Illuminate\Support\Str::slug($p->nama_lengkap, '.') }}@sikeren.id">
                                {{ $p->nama_lengkap }} @if ($p->nip) — NIP {{ $p->nip }} @endif
                            </option>
                        @endforeach
                    </select>
                </div>
                <small class="text-muted d-block mt-2">
                    <i class="bi bi-search me-1"></i>Ketik nama atau NIP untuk mencari. Hanya pegawai yang belum memiliki akun yang ditampilkan.
                </small>
            </div>

            <div data-tipe-section="mitra" class="d-none">
                <label class="form-label fw-semibold" for="mitra_id_select">Mitra Jasa</label>
                <div class="au-pick">
                    <i class="bi bi-building au-pick-icon"></i>
                    <select name="mitra_id" id="mitra_id_select" class="form-select au-pick-select" data-pick="mitra">
                        <option value="">— Pilih mitra —</option>
                        @foreach ($mitraOptions as $m)
                            @php
                                $initial = strtoupper(mb_substr($m->nama_mitra, 0, 1));
                            @endphp
                            <option value="{{ $m->id }}" @selected(old('mitra_id') == $m->id)
                                    data-name="{{ $m->nama_mitra }}"
                                    data-kode="{{ $m->kode_mitra ?? '' }}"
                                    data-initial="{{ $initial }}">
                                {{ $m->nama_mitra }} @if ($m->kode_mitra) ({{ $m->kode_mitra }}) @endif
                            </option>
                        @endforeach
                    </select>
                </div>
                <small class="text-muted d-block mt-2">
                    <i class="bi bi-search me-1"></i>Ketik nama atau kode mitra untuk mencari. Hanya mitra yang belum memiliki akun yang ditampilkan.
                </small>
            </div>

            <div data-tipe-section="sistem" class="d-none">
                <div class="alert alert-warning d-flex align-items-start gap-2 mb-0">
                    <i class="bi bi-shield-exclamation fs-5"></i>
                    <div>
                        Akun sistem tidak terhubung ke pegawai atau mitra. Hanya gunakan untuk Super Admin teknis.
                    </div>
                </div>
            </div>
        </div>

        <div class="surface-card p-4 mb-4">
            <h6 class="fw-bold text-uppercase text-muted mb-3" style="letter-spacing: .08em; font-size: .75rem;">
                3. Kredensial
            </h6>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Email</label>
                    <input type="email" name="email" id="email-input" value="{{ old('email') }}"
                           class="form-control" required>
                    <small class="text-muted">Disarankan menggunakan domain @sikeren.id.</small>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Password</label>
                    <input type="text" name="password" value="{{ old('password') }}" class="form-control"
                           placeholder="Kosongkan untuk acak">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Konfirmasi Password</label>
                    <input type="text" name="password_confirmation" class="form-control"
                           placeholder="Kosongkan untuk acak">
                </div>
            </div>
        </div>

        <div class="surface-card p-4 mb-4">
            <h6 class="fw-bold text-uppercase text-muted mb-3" style="letter-spacing: .08em; font-size: .75rem;">
                4. Role
            </h6>
            <div class="role-pick" id="role-pick">
                @foreach ($roles as $r)
                    @php $checked = in_array($r, (array) old('roles', [])); @endphp
                    <label class="{{ $checked ? 'is-active' : '' }}">
                        <input type="checkbox" name="roles[]" value="{{ $r }}" @checked($checked)>
                        <span>{{ $r }}</span>
                    </label>
                @endforeach
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center">
            <a href="{{ route('admin.users.index') }}" class="btn btn-light">
                <i class="bi bi-arrow-left me-1"></i> Batal
            </a>
            <button class="btn btn-gradient px-4">
                <i class="bi bi-check2-circle me-1"></i> Simpan Akun
            </button>
        </div>
    </form>
@endsection

@push('script')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    (function () {
        const cards = document.querySelectorAll('.tipe-akun-card');
        const sections = document.querySelectorAll('[data-tipe-section]');
        const emailInput = document.getElementById('email-input');

        const setTipe = (tipe) => {
            cards.forEach(c => c.classList.toggle('is-active', c.dataset.tipe === tipe));
            sections.forEach(s => s.classList.toggle('d-none', s.dataset.tipeSection !== tipe));
        };

        cards.forEach(c => {
            c.addEventListener('click', () => {
                const radio = c.querySelector('input[type=radio]');
                if (radio) radio.checked = true;
                setTipe(c.dataset.tipe);
            });
        });

        // Auto-suggest email saat pilih pegawai (kalau email kosong)
        const pegawaiSelect = document.querySelector('select[name=pegawai_id]');
        if (pegawaiSelect) {
            pegawaiSelect.addEventListener('change', () => {
                const opt = pegawaiSelect.options[pegawaiSelect.selectedIndex];
                if (opt && opt.dataset.emailSuggest && !emailInput.value) {
                    emailInput.value = opt.dataset.emailSuggest;
                }
            });
        }

        // Role chip toggle highlight
        document.querySelectorAll('#role-pick label').forEach(label => {
            const cb = label.querySelector('input[type=checkbox]');
            label.addEventListener('click', (e) => {
                // jika label, browser sudah toggle checkbox sebelum event
                setTimeout(() => label.classList.toggle('is-active', cb.checked), 0);
            });
        });

        // Init
        setTipe(document.querySelector('input[name=tipe_akun]:checked')?.value || 'pegawai');
    })();

    // ===== Select2 premium dropdown for Pegawai/Mitra =====
    (function ($) {
        if (! window.jQuery) return;

        const escapeHtml = (str) => String(str ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');

        const formatRow = (option) => {
            if (! option.id) return option.text;

            const $opt   = $(option.element);
            const kind   = $opt.closest('select').data('pick') || 'pegawai';
            const name   = escapeHtml($opt.data('name') || option.text);
            const nip    = escapeHtml($opt.data('nip') || '');
            const kode   = escapeHtml($opt.data('kode') || '');
            const initial = escapeHtml($opt.data('initial') || (name.charAt(0) || '?').toUpperCase());

            const isMitra = kind === 'mitra';
            const badge = isMitra ? 'Mitra' : 'Pegawai';
            const meta  = isMitra
                ? (kode ? `<i class="bi bi-tag-fill"></i> ${kode}` : '<span class="text-muted">Tanpa kode</span>')
                : (nip  ? `<i class="bi bi-credit-card-2-front"></i> ${nip}` : '<span class="text-muted">NIP belum diisi</span>');

            return $(`
                <div class="au-pick-row ${isMitra ? 'is-mitra' : ''}">
                    <span class="au-pick-avatar">${initial}</span>
                    <div class="au-pick-info">
                        <div class="au-pick-name">${name}</div>
                        <div class="au-pick-meta">${meta}</div>
                    </div>
                    <span class="au-pick-badge">${badge}</span>
                </div>
            `);
        };

        const formatSelection = (option) => {
            if (! option.id) return option.text || '— Pilih —';
            const $opt = $(option.element);
            return $opt.data('name') || option.text;
        };

        $('.au-pick-select').each(function () {
            const $sel = $(this);
            const placeholder = $sel.find('option[value=""]').first().text() || 'Cari…';

            $sel.select2({
                theme: 'au-pick',
                width: '100%',
                placeholder,
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
        });
    })(window.jQuery);
</script>
@endpush
