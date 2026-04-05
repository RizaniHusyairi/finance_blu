@extends('layouts.app')

@section('title', 'Master Data DIPA')

@php
    $search = request('search');
    $tahunAnggaran = request('tahun_anggaran');
    $statusAktif = request('status_aktif');
    $revisiAktif = request('revisi_aktif_ke');
@endphp

@section('content')
    <x-page-title title="Master Data DIPA" subtitle="Pengelolaan dokumen DIPA, revisi, dan item anggaran" />

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

    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <div>
            <h5 class="mb-1 fw-bold">Master Data DIPA</h5>
            <p class="text-muted mb-0">Daftar seluruh DIPA beserta revisi aktif dan ringkasan item anggarannya.</p>
        </div>
        <a href="{{ route('dipas.create') }}" class="btn btn-primary shadow-sm">
            <i class="bi bi-plus-lg me-1"></i> Tambah DIPA
        </a>
    </div>

    <div class="row row-cols-1 row-cols-md-2 row-cols-xl-4 g-3 mb-4">
        <div class="col">
            <div class="card rounded-4 h-100 shadow-sm border-0">
                <div class="card-body p-3 border-start border-4 border-primary rounded-4">
                    <p class="mb-1 small text-muted">Total DIPA</p>
                    <h4 class="mb-0 fw-bold">{{ number_format($summary['total_dipa']) }}</h4>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card rounded-4 h-100 shadow-sm border-0">
                <div class="card-body p-3 border-start border-4 border-success rounded-4">
                    <p class="mb-1 small text-muted">DIPA Aktif</p>
                    <h4 class="mb-0 fw-bold">{{ number_format($summary['dipa_aktif']) }}</h4>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card rounded-4 h-100 shadow-sm border-0">
                <div class="card-body p-3 border-start border-4 border-warning rounded-4">
                    <p class="mb-1 small text-muted">Tahun Anggaran Berjalan</p>
                    <h4 class="mb-0 fw-bold">{{ number_format($summary['tahun_berjalan']) }}</h4>
                    <small class="text-muted">{{ now()->year }}</small>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card rounded-4 h-100 shadow-sm border-0">
                <div class="card-body p-3 border-start border-4 border-info rounded-4">
                    <p class="mb-1 small text-muted">Total Pagu Revisi Aktif</p>
                    <h5 class="mb-0 fw-bold">Rp {{ number_format($summary['total_pagu_revisi_aktif'], 0, ',', '.') }}</h5>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0 rounded-4 mb-4">
        <div class="card-body p-4">
            <form method="GET" action="{{ route('dipas.index') }}">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Cari Nomor DIPA</label>
                        <input type="text" name="search" value="{{ $search }}" class="form-control" placeholder="Ketik nomor DIPA">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold">Tahun Anggaran</label>
                        <select name="tahun_anggaran" class="form-select">
                            <option value="">Semua</option>
                            @foreach($tahunOptions as $tahun)
                                <option value="{{ $tahun }}" {{ (string) $tahunAnggaran === (string) $tahun ? 'selected' : '' }}>{{ $tahun }}</option>
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
                        <label class="form-label fw-semibold">Revisi Aktif</label>
                        <select name="revisi_aktif_ke" class="form-select">
                            <option value="">Semua</option>
                            @foreach($revisiOptions as $revisi)
                                <option value="{{ $revisi }}" {{ (string) $revisiAktif === (string) $revisi ? 'selected' : '' }}>Revisi {{ $revisi }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-funnel me-1"></i> Filter
                            </button>
                            <a href="{{ route('dipas.index') }}" class="btn btn-outline-secondary">
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
                            <th width="22%">Nomor DIPA</th>
                            <th width="10%">Tahun</th>
                            <th width="12%">Tanggal Disahkan</th>
                            <th width="10%">Revisi Aktif</th>
                            <th width="16%" class="text-end">Total Pagu Revisi Aktif</th>
                            <th width="10%" class="text-center">Status</th>
                            <th width="10%" class="text-center">Jumlah Item</th>
                            <th width="15%" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($dipas as $dipa)
                            @php
                                $activeRevision = $dipa->activeRevision;
                                $activeItems = collect(optional($activeRevision)->items)->where('status_aktif', true);
                            @endphp
                            <tr>
                                <td class="text-center">{{ $loop->iteration }}</td>
                                <td>
                                    <div class="fw-bold text-primary">{{ $dipa->nomor_dipa }}</div>
                                    <div class="small text-muted">Dokumen induk DIPA</div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">{{ $dipa->tahun_anggaran }}</span>
                                </td>
                                <td>{{ optional($dipa->tanggal_disahkan)->format('d M Y') ?? '-' }}</td>
                                <td>
                                    <span class="badge bg-info text-dark">Revisi {{ $dipa->revisi_aktif_ke ?? 0 }}</span>
                                </td>
                                <td class="text-end fw-bold">
                                    Rp {{ number_format(optional($activeRevision)->total_pagu ?? 0, 0, ',', '.') }}
                                </td>
                                <td class="text-center">
                                    @if($dipa->status_aktif)
                                        <span class="badge bg-success">Aktif</span>
                                    @else
                                        <span class="badge bg-secondary">Nonaktif</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-light text-dark border">{{ $activeItems->count() }} Item</span>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group">
                                        <a href="{{ route('dipas.show', $dipa) }}" class="btn btn-sm btn-primary">
                                            Detail
                                        </a>
                                        <button type="button" class="btn btn-sm btn-primary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false">
                                            <span class="visually-hidden">Toggle Dropdown</span>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                            <li><a class="dropdown-item" href="{{ route('dipas.edit', $dipa) }}">Edit Header</a></li>
                                            <li><a class="dropdown-item" href="{{ route('dipas.revisions.create', $dipa) }}">Tambah Revisi</a></li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <form action="{{ route('dipas.toggle', $dipa) }}" method="POST">
                                                    @csrf
                                                    <button type="submit" class="dropdown-item">
                                                        {{ $dipa->status_aktif ? 'Nonaktifkan' : 'Aktifkan' }}
                                                    </button>
                                                </form>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-5 text-muted">
                                    Belum ada data DIPA yang sesuai dengan filter.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
