@extends('layouts.app')

@section('title', 'Manajemen User')

@push('css')
    @include('admin._partials.styles')
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
            <form method="GET" class="row g-2 align-items-center">
                <div class="col-md-5">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search"></i></span>
                        <input type="text" name="q" value="{{ request('q') }}" class="form-control border-start-0"
                               placeholder="Cari email…">
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
                <div class="col-md-2 d-grid">
                    <button class="btn btn-gradient"><i class="bi bi-funnel me-1"></i> Filter</button>
                </div>
            </form>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-4">User</th>
                        <th>Tipe</th>
                        <th>Tautan Profil</th>
                        <th>Roles</th>
                        <th>Status</th>
                        <th class="text-end pe-4">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $u)
                        @php
                            $tipe = is_null($u->profilable_type) ? 'sistem'
                                : (str_contains($u->profilable_type, 'MitraJasa') ? 'mitra' : 'pegawai');
                            $initial = strtoupper(mb_substr($u->name ?? $u->email, 0, 1));
                        @endphp
                        <tr>
                            <td class="ps-4">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="avatar-circle">{{ $initial }}</div>
                                    <div>
                                        <div class="fw-semibold text-dark">{{ $u->name ?? '—' }}</div>
                                        <small class="text-muted">{{ $u->email }}</small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="tipe-pill {{ $tipe }}">
                                    <i class="bi bi-{{ $tipe === 'sistem' ? 'shield-check' : ($tipe === 'mitra' ? 'shop' : 'person-badge') }}"></i>
                                    {{ ucfirst($tipe) }}
                                </span>
                            </td>
                            <td>
                                @if ($u->profilable)
                                    <span class="text-dark">{{ $u->profilable->nama_lengkap ?? $u->profilable->nama_mitra ?? '—' }}</span><br>
                                    <small class="text-muted">
                                        @if (isset($u->profilable->nip)) NIP {{ $u->profilable->nip ?: '—' }} @endif
                                        @if (isset($u->profilable->kode_mitra)) {{ $u->profilable->kode_mitra }} @endif
                                    </small>
                                @else
                                    <span class="text-muted fst-italic">akun sistem</span>
                                @endif
                            </td>
                            <td>
                                @forelse ($u->roles as $role)
                                    @php
                                        $cls = 'role-chip';
                                        if ($role->name === 'Super Admin') $cls .= ' is-superadmin';
                                        elseif (str_contains($role->name, 'Mitra')) $cls .= ' is-mitra';
                                        elseif (str_contains($role->name, 'Jasa')) $cls .= ' is-jasa';
                                        elseif (in_array($role->name, ['Admin Listrik', 'Admin Air'])) $cls .= ' is-utilitas';
                                    @endphp
                                    <span class="{{ $cls }}">{{ $role->name }}</span>
                                @empty
                                    <small class="text-muted">tanpa role</small>
                                @endforelse
                            </td>
                            <td>
                                @php $accountActive = $u->isAccountActive(); @endphp
                                <span class="badge {{ $accountActive ? 'bg-success' : 'bg-secondary' }}">
                                    {{ $accountActive ? 'Aktif' : 'Nonaktif' }}
                                </span>
                                @if ($u->active_until)
                                    <small class="text-muted d-block mt-1">
                                        s.d. {{ $u->active_until->format('d M Y') }}
                                    </small>
                                @endif
                            </td>
                            <td class="text-end pe-4">
                                <div class="btn-group">
                                    <a href="{{ route('admin.users.show', $u) }}" class="btn btn-sm btn-light text-primary" title="Detail">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="{{ route('admin.users.edit', $u) }}" class="btn btn-sm btn-light text-warning" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form method="POST" action="{{ route('admin.users.destroy', $u) }}"
                                          onsubmit="return confirm('Hapus akun {{ $u->email }}?');" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-light text-danger" title="Hapus">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5">
                                <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                Belum ada user yang cocok dengan filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white border-0 py-3">
            {{ $users->onEachSide(1)->links() }}
        </div>
    </div>
@endsection
