@extends('layouts.app')

@section('title', 'Data Pegawai')

@push('css')
    @include('admin._partials.styles')
@endpush

@section('content')
    <x-page-title title="Administrasi" subtitle="Data Pegawai" />

    <div class="admin-hero d-flex align-items-center gap-3 mb-4"
         style="background: linear-gradient(135deg, #0ea5e9 0%, #6366f1 50%, #8b5cf6 100%);">
        <div class="hero-icon"><i class="material-icons-outlined">badge</i></div>
        <div class="flex-grow-1">
            <h1>Data Pegawai</h1>
            <p>Kelola master pegawai yang menjadi sumber data untuk akun, perjalanan dinas, dan honor.</p>
        </div>
        <a href="{{ route('admin.pegawai.create') }}" class="btn btn-light fw-semibold shadow-sm">
            <i class="bi bi-plus-lg me-1"></i> Tambah Pegawai
        </a>
    </div>

    <div class="row g-3 mb-4 stagger">
        @php
            $cards = [
                ['Total Pegawai', $stats['total'], 'people', '14,165,233'],
                ['Aktif', $stats['aktif'], 'verified', '34,197,94'],
                ['Nonaktif', $stats['nonaktif'], 'block', '244,63,94'],
                ['Memiliki Akun', $stats['punya_user'], 'verified_user', '79,70,229'],
            ];
        @endphp
        @foreach ($cards as [$label, $value, $icon, $c])
            <div class="col-6 col-md-3">
                <div class="stat-card p-3 h-100"
                     style="--c1: rgb({{ $c }}); --c2: rgb({{ $c }}); --c-bg: rgba({{ $c }}, .12); --c-fg: rgb({{ $c }});">
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

    <div class="surface-card mb-4">
        <div class="card-header">
            <form method="GET" class="row g-2 align-items-center">
                <div class="col-md-6">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search"></i></span>
                        <input type="text" name="q" value="{{ request('q') }}" class="form-control border-start-0"
                               placeholder="Cari nama, NIP, atau jabatan…">
                    </div>
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="">Semua Status</option>
                        <option value="1" @selected(request('status') === '1')>Aktif</option>
                        <option value="0" @selected(request('status') === '0')>Nonaktif</option>
                    </select>
                </div>
                <div class="col-md-3 d-grid">
                    <button class="btn btn-gradient"><i class="bi bi-funnel me-1"></i> Filter</button>
                </div>
            </form>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-4">Pegawai</th>
                        <th>NIP / NIK</th>
                        <th>Jabatan</th>
                        <th>Akun</th>
                        <th class="text-center">Status</th>
                        <th class="text-end pe-4">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($pegawai as $p)
                        <tr>
                            <td class="ps-4">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="avatar-circle">{{ strtoupper(mb_substr($p->nama_lengkap, 0, 1)) }}</div>
                                    <div>
                                        <div class="fw-semibold text-dark">{{ $p->nama_lengkap }}</div>
                                        <small class="text-muted">{{ $p->nama_bank ?: '—' }} • {{ $p->nomor_rekening ?: '—' }}</small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="font-monospace">{{ $p->nip ?: '—' }}</span><br>
                                <small class="text-muted font-monospace">{{ $p->nik ?: '—' }}</small>
                            </td>
                            <td><small>{{ $p->jabatan ?: '-' }}</small></td>
                            <td>
                                @if ($p->user)
                                    <span class="tipe-pill pegawai">
                                        <i class="bi bi-check-circle"></i> {{ $p->user->email }}
                                    </span>
                                @else
                                    <span class="text-muted small fst-italic">belum punya akun</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <form method="POST" action="{{ route('admin.pegawai.toggle', $p) }}" class="d-inline">
                                    @csrf
                                    @method('PATCH')
                                    <button class="btn btn-sm border-0 {{ $p->status_aktif ? 'text-success' : 'text-secondary' }}"
                                            title="{{ $p->status_aktif ? 'Nonaktifkan' : 'Aktifkan' }}">
                                        <i class="bi {{ $p->status_aktif ? 'bi-toggle-on fs-4' : 'bi-toggle-off fs-4' }}"></i>
                                    </button>
                                </form>
                            </td>
                            <td class="text-end pe-4">
                                <div class="btn-group">
                                    <a href="{{ route('admin.pegawai.show', $p) }}" class="btn btn-sm btn-light text-primary" title="Detail">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="{{ route('admin.pegawai.edit', $p) }}" class="btn btn-sm btn-light text-warning" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form method="POST" action="{{ route('admin.pegawai.destroy', $p) }}"
                                          onsubmit="return confirm('Hapus pegawai {{ $p->nama_lengkap }}?');" class="d-inline">
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
                                Tidak ada pegawai yang cocok dengan filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white border-0 py-3">
            {{ $pegawai->onEachSide(1)->links() }}
        </div>
    </div>
@endsection
