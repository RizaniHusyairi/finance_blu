@extends('layouts.app')

@section('title', 'Detail Role')

@push('css')
    @include('admin._partials.styles')
@endpush

@section('content')
    <x-page-title title="Manajemen Role" subtitle="{{ $role->name }}" />

    <div class="admin-hero d-flex align-items-center gap-3 mb-4">
        <div class="hero-icon"><i class="material-icons-outlined">workspace_premium</i></div>
        <div class="flex-grow-1">
            <h1>{{ $role->name }}</h1>
            <p>{{ $role->users_count }} user aktif memiliki role ini.</p>
        </div>
        <a href="{{ route('admin.roles.index') }}" class="btn btn-light">
            <i class="bi bi-arrow-left me-1"></i> Kembali
        </a>
    </div>

    @include('admin._partials.flash')

    <div class="surface-card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-4">User</th>
                        <th>Profil Tertaut</th>
                        <th>Roles Lain</th>
                        <th class="text-end pe-4">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $u)
                        @php $initial = strtoupper(mb_substr($u->name ?? $u->email, 0, 1)); @endphp
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
                                @if ($u->profilable)
                                    {{ $u->profilable->nama_lengkap ?? $u->profilable->nama_mitra ?? '—' }}
                                @else
                                    <span class="text-muted fst-italic">akun sistem</span>
                                @endif
                            </td>
                            <td>
                                @foreach ($u->roles as $r)
                                    @if ($r->name !== $role->name)
                                        <span class="role-chip">{{ $r->name }}</span>
                                    @endif
                                @endforeach
                            </td>
                            <td class="text-end pe-4">
                                <a href="{{ route('admin.users.show', $u) }}" class="btn btn-sm btn-light text-primary">
                                    <i class="bi bi-eye"></i> Detail
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-5">
                                Belum ada user dengan role ini.
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
