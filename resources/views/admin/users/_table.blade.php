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
<div class="card-footer bg-white border-0 py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
    <small class="text-muted">
        Menampilkan <strong>{{ $users->firstItem() ?? 0 }}–{{ $users->lastItem() ?? 0 }}</strong>
        dari <strong>{{ number_format($users->total()) }}</strong> user
    </small>
    <div>{{ $users->onEachSide(1)->links() }}</div>
</div>
