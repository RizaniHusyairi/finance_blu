@extends('layouts.app')

@section('title', 'Manajemen User')

@push('css')
    @include('admin._partials.styles')
    <style>
        #user-results { transition: opacity .15s ease; }
        #user-results.dn-loading { opacity: .45; pointer-events: none; }
        #user-results.dn-loading::after {
            content: ""; position: absolute; top: .5rem; right: .75rem;
            width: 1.3rem; height: 1.3rem;
            border: 2px solid #7c3aed; border-right-color: transparent; border-radius: 50%;
            animation: uspin .6s linear infinite;
        }
        @keyframes uspin { to { transform: rotate(360deg); } }
    </style>
@endpush

@section('content')
    <x-page-title title="Administrasi" subtitle="Manajemen User" />

    {{-- Hero --}}
    <div class="admin-hero d-flex align-items-center gap-3 mb-4">
        <div class="hero-icon"><i class="material-icons-outlined">manage_accounts</i></div>
        <div class="flex-grow-1">
            <h1>Manajemen User</h1>
            <p>Kelola akun login, peran, dan tautan ke pegawai/mitra. Hanya Super Admin yang dapat mengakses halaman ini.</p>
        </div>
        <a href="{{ route('admin.users.create') }}" class="btn btn-light fw-semibold shadow-sm">
            <i class="bi bi-plus-lg me-1"></i> Tambah User
        </a>
    </div>

    {{-- Stat cards --}}
    <div class="row g-3 mb-4 stagger">
        @php
            $cards = [
                ['Total User', $stats['total'], 'people', '79,70,229', '124,58,237'],
                ['Akun Pegawai', $stats['pegawai'], 'badge', '34,197,94', '21,128,61'],
                ['Akun Mitra', $stats['mitra'], 'storefront', '14,165,233', '3,105,161'],
                ['Akun Sistem', $stats['sistem'], 'shield', '217,70,239', '162,28,175'],
            ];
        @endphp
        @foreach ($cards as [$label, $value, $icon, $c1, $c2])
            <div class="col-6 col-md-3">
                <div class="stat-card p-3 h-100"
                     style="--c1: rgb({{ $c1 }}); --c2: rgb({{ $c2 }}); --c-bg: rgba({{ $c1 }}, .12); --c-fg: rgb({{ $c1 }});">
                    <span class="stat-bar"></span>
                    <div class="d-flex align-items-center gap-2 ps-2">
                        <div class="stat-icon"><i class="material-icons-outlined">{{ $icon }}</i></div>
                        <h6>{{ $label }}</h6>
                    </div>
                    <div class="stat-value ps-2 mt-2">{{ number_format($value) }}</div>
                </div>
            </div>
        @endforeach
    </div>

    @include('admin._partials.flash')

    {{-- Filter + Tabel --}}
    <div class="surface-card mb-4">
        <div class="card-header">
            <form method="GET" id="userFilterForm" class="row g-2 align-items-center">
                <div class="col-md-5">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search"></i></span>
                        <input type="search" name="q" id="userSearch" value="{{ request('q') }}"
                               class="form-control border-start-0" autocomplete="off"
                               placeholder="Cari email, nama, NIP, atau kode mitra…">
                    </div>
                </div>
                <div class="col-md-3">
                    <select name="role" class="form-select">
                        <option value="">Semua Role</option>
                        @foreach ($roleList as $r)
                            <option value="{{ $r }}" @selected(request('role') === $r)>{{ $r }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="tipe" class="form-select">
                        <option value="">Semua Tipe</option>
                        <option value="pegawai" @selected(request('tipe') === 'pegawai')>Pegawai</option>
                        <option value="mitra" @selected(request('tipe') === 'mitra')>Mitra</option>
                        <option value="sistem" @selected(request('tipe') === 'sistem')>Sistem</option>
                    </select>
                </div>
                <div class="col-md-2 d-grid gap-2">
                    <button class="btn btn-gradient"><i class="bi bi-funnel me-1"></i> Filter</button>
                    <a href="{{ route('admin.users.index') }}" data-user-reset class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
                    </a>
                </div>
            </form>
        </div>
        <div id="user-results" class="position-relative">
            @include('admin.users._table')
        </div>
    </div>
@endsection

@push('script')
<script>
    // ── Live-search AJAX Manajemen User: filter & paginasi tanpa reload ──
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('userFilterForm');
        const container = document.getElementById('user-results');
        if (!form || !container) return;

        const baseUrl = "{{ route('admin.users.index') }}";
        const search = document.getElementById('userSearch');
        let debounce, controller;

        function buildUrl() {
            const params = new URLSearchParams();
            new FormData(form).forEach(function (value, key) {
                if (String(value).trim() !== '') params.append(key, value);
            });
            const qs = params.toString();
            return qs ? (baseUrl + '?' + qs) : baseUrl;
        }

        function load(url) {
            if (controller) controller.abort();
            controller = new AbortController();
            container.classList.add('dn-loading');

            fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' },
                signal: controller.signal,
            })
                .then(function (res) { return res.text(); })
                .then(function (html) {
                    container.innerHTML = html;
                    container.classList.remove('dn-loading');
                    window.history.replaceState(null, '', url);
                })
                .catch(function (err) {
                    if (err.name !== 'AbortError') container.classList.remove('dn-loading');
                });
        }

        function refresh() { load(buildUrl()); }

        // Ketik di kotak cari → debounce 300ms (form di luar #user-results → fokus tetap).
        if (search) {
            search.addEventListener('input', function () {
                clearTimeout(debounce);
                debounce = setTimeout(refresh, 300);
            });
        }

        // Dropdown role/tipe → filter instan.
        form.querySelectorAll('select').forEach(function (el) {
            el.addEventListener('change', refresh);
        });

        // Tombol Filter / Enter → AJAX, bukan reload penuh.
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            clearTimeout(debounce);
            refresh();
        });

        // Klik paginasi di dalam hasil → muat via AJAX.
        container.addEventListener('click', function (e) {
            const link = e.target.closest('.pagination a');
            if (link && link.getAttribute('href')) {
                e.preventDefault();
                load(link.getAttribute('href'));
            }
        });

        // Tombol Reset → kosongkan filter lalu muat ulang (tanpa reload).
        document.addEventListener('click', function (e) {
            if (!e.target.closest('[data-user-reset]')) return;
            e.preventDefault();
            if (search) search.value = '';
            form.querySelectorAll('select').forEach(function (s) { s.selectedIndex = 0; });
            clearTimeout(debounce);
            refresh();
            if (search) search.focus();
        });
    });
</script>
@endpush
