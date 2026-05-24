@extends('layouts.app')

@section('title', 'Edit User')

@push('css')
    @include('admin._partials.styles')
@endpush

@section('content')
    <x-page-title title="Manajemen User" subtitle="Edit User" />

    <div class="admin-hero d-flex align-items-center gap-3 mb-4">
        <div class="hero-icon"><i class="material-icons-outlined">edit</i></div>
        <div>
            <h1>Edit Akun</h1>
            <p>{{ $user->email }} — {{ $user->name ?? 'akun sistem' }}</p>
        </div>
    </div>

    @include('admin._partials.flash')

    <form method="POST" action="{{ route('admin.users.update', $user) }}">
        @csrf
        @method('PUT')

        <div class="surface-card p-4 mb-4">
            <h6 class="fw-bold text-uppercase text-muted mb-3" style="letter-spacing: .08em; font-size: .75rem;">
                Kredensial
            </h6>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Email</label>
                    <input type="email" name="email" value="{{ old('email', $user->email) }}" class="form-control" required>
                </div>
                <div class="col-md-6 d-flex align-items-end">
                    <form method="POST" action="{{ route('admin.users.reset-password', $user) }}"
                          onsubmit="return confirm('Reset password user ini? Password baru akan ditampilkan sekali.');"
                          class="w-100 d-grid">
                        @csrf
                        <button type="submit" class="btn btn-light text-primary border">
                            <i class="bi bi-shield-lock me-1"></i> Reset Password
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="surface-card p-4 mb-4">
            <h6 class="fw-bold text-uppercase text-muted mb-3" style="letter-spacing: .08em; font-size: .75rem;">
                Roles
            </h6>
            <div class="role-pick" id="role-pick">
                @php $current = old('roles', $user->roles->pluck('name')->all()); @endphp
                @foreach ($roles as $r)
                    @php $checked = in_array($r, (array) $current); @endphp
                    <label class="{{ $checked ? 'is-active' : '' }}">
                        <input type="checkbox" name="roles[]" value="{{ $r }}" @checked($checked)>
                        <span>{{ $r }}</span>
                    </label>
                @endforeach
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center">
            <a href="{{ route('admin.users.show', $user) }}" class="btn btn-light">
                <i class="bi bi-arrow-left me-1"></i> Kembali
            </a>
            <button class="btn btn-gradient px-4">
                <i class="bi bi-save me-1"></i> Simpan Perubahan
            </button>
        </div>
    </form>
@endsection

@push('script')
<script>
    document.querySelectorAll('#role-pick label').forEach(label => {
        const cb = label.querySelector('input[type=checkbox]');
        label.addEventListener('click', () => {
            setTimeout(() => label.classList.toggle('is-active', cb.checked), 0);
        });
    });
</script>
@endpush
