@extends('layouts.app')

@section('title', 'Tambah User')

@push('css')
    @include('admin._partials.styles')
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
                <label class="form-label fw-semibold">Pegawai</label>
                <select name="pegawai_id" class="form-select">
                    <option value="">— Pilih pegawai —</option>
                    @foreach ($pegawaiOptions as $p)
                        <option value="{{ $p->id }}" @selected(old('pegawai_id') == $p->id)
                                data-name="{{ $p->nama_lengkap }}"
                                data-email-suggest="{{ \Illuminate\Support\Str::slug($p->nama_lengkap, '.') }}@sikeren.id">
                            {{ $p->nama_lengkap }} @if ($p->nip) — NIP {{ $p->nip }} @endif
                        </option>
                    @endforeach
                </select>
                <small class="text-muted">Hanya pegawai yang belum memiliki akun yang ditampilkan.</small>
            </div>

            <div data-tipe-section="mitra" class="d-none">
                <label class="form-label fw-semibold">Mitra Jasa</label>
                <select name="mitra_id" class="form-select">
                    <option value="">— Pilih mitra —</option>
                    @foreach ($mitraOptions as $m)
                        <option value="{{ $m->id }}" @selected(old('mitra_id') == $m->id)>
                            {{ $m->nama_mitra }} @if ($m->kode_mitra) ({{ $m->kode_mitra }}) @endif
                        </option>
                    @endforeach
                </select>
                <small class="text-muted">Hanya mitra yang belum memiliki akun yang ditampilkan.</small>
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
</script>
@endpush
