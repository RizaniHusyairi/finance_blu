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
                    <button type="submit" form="resetPasswordForm" class="btn btn-light text-primary border w-100"
                            onclick="return confirm('Reset password user ini? Password baru akan ditampilkan sekali.');">
                        <i class="bi bi-shield-lock me-1"></i> Reset Password
                    </button>
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

            @php $temporaryRoleSelected = in_array('PLT/PLH', (array) $current); @endphp
            <div id="temporary-role-period" class="mt-4 pt-3 border-top {{ $temporaryRoleSelected ? '' : 'd-none' }}">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <i class="bi bi-calendar-range text-primary"></i>
                    <div>
                        <div class="fw-semibold">Masa Aktif PLT/PLH</div>
                        <small class="text-muted">Akun otomatis nonaktif setelah tanggal selesai.</small>
                    </div>
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Tanggal Mulai</label>
                        <input type="date" name="active_from"
                               value="{{ old('active_from', $user->active_from?->toDateString() ?? now()->toDateString()) }}"
                               class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Tanggal Selesai</label>
                        <input type="date" name="active_until"
                               value="{{ old('active_until', $user->active_until?->toDateString()) }}"
                               class="form-control">
                    </div>
                </div>
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

    {{-- Form reset password terpisah (di luar form update agar tidak ter-nested). --}}
    <form method="POST" action="{{ route('admin.users.reset-password', $user) }}" id="resetPasswordForm" class="d-none">
        @csrf
    </form>
@endsection

@push('script')
<script>
    const roleCheckboxes = document.querySelectorAll('#role-pick input[name="roles[]"]');
    const temporaryRolePeriod = document.getElementById('temporary-role-period');
    const toggleTemporaryRolePeriod = () => {
        if (! temporaryRolePeriod) return;

        const visible = Array.from(roleCheckboxes).some(cb => cb.value === 'PLT/PLH' && cb.checked);
        temporaryRolePeriod.classList.toggle('d-none', ! visible);
        temporaryRolePeriod.querySelectorAll('input').forEach(input => {
            input.required = visible;
        });
    };

    document.querySelectorAll('#role-pick label').forEach(label => {
        const cb = label.querySelector('input[type=checkbox]');
        label.addEventListener('click', () => {
            setTimeout(() => {
                label.classList.toggle('is-active', cb.checked);
                toggleTemporaryRolePeriod();
            }, 0);
        });
    });
    roleCheckboxes.forEach(cb => cb.addEventListener('change', toggleTemporaryRolePeriod));
    toggleTemporaryRolePeriod();
</script>
@endpush
