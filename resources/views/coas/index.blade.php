@extends('layouts.app')

@section('title', 'Master COA')

@php
    $search = request('search');
    $jenisAkun = request('jenis_akun');
    $statusAktif = request('status_aktif');
@endphp

@section('content')
    <x-page-title title="Master COA" subtitle="Pengelolaan chart of account untuk kebutuhan DIPA dan transaksi BLU" />

    @if(session('success'))
        <div class="alert alert-success border-0 bg-success alert-dismissible fade show shadow-sm">
            <div class="text-white">{{ session('success') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('info'))
        <div class="alert alert-info border-0 bg-info alert-dismissible fade show shadow-sm">
            <div class="text-white">{{ session('info') }}</div>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger border-0 bg-danger alert-dismissible fade show shadow-sm">
            <div class="text-white">{{ session('error') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <div>
            <h5 class="mb-1 fw-bold">Master COA</h5>
            <p class="text-muted mb-0">Daftar COA lengkap beserta pemakaian pada item anggaran DIPA.</p>
        </div>
        <a href="{{ route('coas.create') }}" class="btn btn-primary shadow-sm">
            <i class="bi bi-plus-lg me-1"></i> Tambah COA
        </a>
    </div>

    <div class="row row-cols-1 row-cols-md-2 row-cols-xl-4 g-3 mb-4">
        <div class="col">
            <div class="card rounded-4 h-100 shadow-sm border-0">
                <div class="card-body p-3 border-start border-4 border-primary rounded-4">
                    <p class="mb-1 small text-muted">Total COA</p>
                    <h4 class="mb-0 fw-bold">{{ number_format($summary['total_coa']) }}</h4>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card rounded-4 h-100 shadow-sm border-0">
                <div class="card-body p-3 border-start border-4 border-success rounded-4">
                    <p class="mb-1 small text-muted">COA Aktif</p>
                    <h4 class="mb-0 fw-bold">{{ number_format($summary['coa_aktif']) }}</h4>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card rounded-4 h-100 shadow-sm border-0">
                <div class="card-body p-3 border-start border-4 border-warning rounded-4">
                    <p class="mb-1 small text-muted">Kode Akun Unik</p>
                    <h4 class="mb-0 fw-bold">{{ number_format($summary['kode_akun_unik']) }}</h4>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card rounded-4 h-100 shadow-sm border-0">
                <div class="card-body p-3 border-start border-4 border-info rounded-4">
                    <p class="mb-1 small text-muted">Dipakai di Item DIPA</p>
                    <h4 class="mb-0 fw-bold">{{ number_format($summary['coa_dipakai_di_dipa']) }}</h4>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0 rounded-4 mb-4">
        <div class="card-body p-4">
            <form method="GET" action="{{ route('coas.index') }}">
                <div class="row g-3 align-items-end">
                    <div class="col-md-5">
                        <label class="form-label fw-semibold">Cari COA</label>
                        <input type="text" name="search" value="{{ $search }}" class="form-control" placeholder="Kode MAK lengkap, kode akun, atau nama akun">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Jenis Akun</label>
                        <select name="jenis_akun" class="form-select">
                            <option value="">Semua</option>
                            @foreach($jenisAkunOptions as $option)
                                <option value="{{ $option }}" {{ (string) $jenisAkun === (string) $option ? 'selected' : '' }}>{{ $option }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold">Status Aktif</label>
                        <select name="status_aktif" class="form-select">
                            <option value="">Semua</option>
                            <option value="aktif" {{ $statusAktif === 'aktif' ? 'selected' : '' }}>Aktif</option>
                            <option value="nonaktif" {{ $statusAktif === 'nonaktif' ? 'selected' : '' }}>Nonaktif</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-funnel me-1"></i> Filter
                            </button>
                            <a href="{{ route('coas.index') }}" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm border-0 rounded-4">
        <div class="card-body p-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center" width="5%">No</th>
                            <th width="24%">COA Lengkap</th>
                            <th width="11%">Kode Akun</th>
                            <th width="22%">Nama Akun</th>
                            <th width="12%">Jenis Akun</th>
                            <th width="10%" class="text-center">Dipakai di DIPA</th>
                            <th width="8%" class="text-center">Status</th>
                            <th width="18%" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($coas as $coa)
                            <tr>
                                <td class="text-center">{{ $coas->firstItem() + $loop->index }}</td>
                                <td>
                                    <div class="fw-bold text-primary">{{ $coa->kode_mak_lengkap ?: '-' }}</div>
                                    <div class="small text-muted">{{ $coa->kd_program ?: '-' }} / {{ $coa->kd_giat ?: '-' }} / {{ $coa->kd_output ?: '-' }}</div>
                                </td>
                                <td><span class="badge bg-light text-dark border">{{ $coa->kd_akun ?: '-' }}</span></td>
                                <td class="fw-semibold">{{ $coa->nama_akun }}</td>
                                <td>{{ $coa->jenis_akun ?: '-' }}</td>
                                <td class="text-center">
                                    <span class="badge {{ $coa->dipa_revision_items_count > 0 ? 'bg-info text-dark' : 'bg-light text-dark border' }}">
                                        {{ number_format($coa->dipa_revision_items_count) }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="badge {{ $coa->status_aktif ? 'bg-success' : 'bg-secondary' }}">
                                        {{ $coa->status_aktif ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group">
                                        <a href="{{ route('coas.show', $coa) }}" class="btn btn-sm btn-primary">Detail</a>
                                        <button type="button" class="btn btn-sm btn-primary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false">
                                            <span class="visually-hidden">Toggle Dropdown</span>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                            <li><a class="dropdown-item" href="{{ route('coas.edit', $coa) }}">Edit</a></li>
                                            <li>
                                                <form action="{{ route('coas.toggle', $coa) }}" method="POST">
                                                    @csrf
                                                    <button type="submit" class="dropdown-item">
                                                        {{ $coa->status_aktif ? 'Nonaktifkan' : 'Aktifkan' }}
                                                    </button>
                                                </form>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                @if($coa->dipa_revision_items_count > 0)
                                                    <button type="button" class="dropdown-item text-muted" disabled>Tidak bisa dihapus (sudah dipakai)</button>
                                                @else
                                                    <form action="{{ route('coas.destroy', $coa) }}" method="POST" onsubmit="return confirm('Hapus COA ini secara permanen?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="dropdown-item text-danger">Hapus</button>
                                                    </form>
                                                @endif
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">Belum ada data COA yang sesuai dengan filter.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($coas->hasPages())
                <div class="mt-4 d-flex justify-content-end">
                    {{ $coas->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
