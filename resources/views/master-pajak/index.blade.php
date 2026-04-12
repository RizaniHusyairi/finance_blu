@extends('layouts.app')

@section('title', 'Master Pajak')

@php
    $search = request('search');
    $statusFilter = request('status_aktif');
    $berlakuFilter = request('berlaku');
    $today = \Carbon\Carbon::today();
@endphp

@section('content')
    <x-page-title title="Master Pajak" subtitle="Daftar tarif pajak yang digunakan dalam perhitungan dokumen" />

    @if(session('success'))
        <div class="alert alert-success border-0 bg-success alert-dismissible fade show shadow-sm">
            <div class="text-white">{{ session('success') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
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
            <h5 class="mb-1 fw-bold">Master Pajak</h5>
            <p class="text-muted mb-0">Kelola tarif pajak untuk referensi perhitungan SPP, SPM, dan dokumen lainnya.</p>
        </div>
        <a href="{{ route('master-pajak.create') }}" class="btn btn-primary shadow-sm">
            <i class="bi bi-plus-lg me-1"></i> Tambah Pajak
        </a>
    </div>

    {{-- Summary Cards --}}
    <div class="row row-cols-1 row-cols-md-2 row-cols-xl-4 g-3 mb-4">
        <div class="col">
            <div class="card rounded-4 h-100 shadow-sm border-0">
                <div class="card-body p-3 border-start border-4 border-primary rounded-4">
                    <p class="mb-1 small text-muted">Total Pajak</p>
                    <h4 class="mb-0 fw-bold">{{ number_format($summary['total']) }}</h4>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card rounded-4 h-100 shadow-sm border-0">
                <div class="card-body p-3 border-start border-4 border-success rounded-4">
                    <p class="mb-1 small text-muted">Pajak Aktif</p>
                    <h4 class="mb-0 fw-bold">{{ number_format($summary['aktif']) }}</h4>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card rounded-4 h-100 shadow-sm border-0">
                <div class="card-body p-3 border-start border-4 border-secondary rounded-4">
                    <p class="mb-1 small text-muted">Pajak Nonaktif</p>
                    <h4 class="mb-0 fw-bold">{{ number_format($summary['nonaktif']) }}</h4>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card rounded-4 h-100 shadow-sm border-0">
                <div class="card-body p-3 border-start border-4 border-info rounded-4">
                    <p class="mb-1 small text-muted">Berlaku Saat Ini</p>
                    <h4 class="mb-0 fw-bold">{{ number_format($summary['berlaku_sekarang']) }}</h4>
                </div>
            </div>
        </div>
    </div>

    {{-- Filter Bar --}}
    <div class="card shadow-sm border-0 rounded-4 mb-4">
        <div class="card-body p-4">
            <form method="GET" action="{{ route('master-pajak.index') }}">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Cari Pajak</label>
                        <input type="text" name="search" value="{{ $search }}" class="form-control" placeholder="Kode pajak atau jenis pajak...">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold">Status</label>
                        <select name="status_aktif" class="form-select">
                            <option value="">Semua</option>
                            <option value="aktif" {{ $statusFilter === 'aktif' ? 'selected' : '' }}>Aktif</option>
                            <option value="nonaktif" {{ $statusFilter === 'nonaktif' ? 'selected' : '' }}>Nonaktif</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Masa Berlaku</label>
                        <select name="berlaku" class="form-select">
                            <option value="">Semua</option>
                            <option value="berlaku" {{ $berlakuFilter === 'berlaku' ? 'selected' : '' }}>Berlaku Saat Ini</option>
                            <option value="belum" {{ $berlakuFilter === 'belum' ? 'selected' : '' }}>Belum Berlaku</option>
                            <option value="expired" {{ $berlakuFilter === 'expired' ? 'selected' : '' }}>Sudah Berakhir</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-funnel me-1"></i> Filter
                            </button>
                            <a href="{{ route('master-pajak.index') }}" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Tabel Daftar Pajak --}}
    <div class="card shadow-sm border-0 rounded-4">
        <div class="card-body p-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center" width="5%">No</th>
                            <th width="12%">Kode Pajak</th>
                            <th width="18%">Jenis Pajak</th>
                            <th width="10%" class="text-center">Persentase</th>
                            <th width="18%">Rumus</th>
                            <th width="17%">Periode Berlaku</th>
                            <th width="10%" class="text-center">Status</th>
                            <th width="10%" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($pajaks as $pajak)
                            @php
                                $mulai = $pajak->berlaku_mulai ? \Carbon\Carbon::parse($pajak->berlaku_mulai) : null;
                                $sampai = $pajak->berlaku_sampai ? \Carbon\Carbon::parse($pajak->berlaku_sampai) : null;

                                $periodLabel = '-';
                                if ($mulai && $sampai) {
                                    $periodLabel = $mulai->format('d-m-Y') . ' s/d ' . $sampai->format('d-m-Y');
                                } elseif ($mulai && !$sampai) {
                                    $periodLabel = 'Mulai ' . $mulai->format('d-m-Y');
                                }

                                // Determine validity badge
                                $validityBadge = null;
                                if ($pajak->status_aktif) {
                                    if ($mulai && $mulai->gt($today)) {
                                        $validityBadge = ['label' => 'Belum Berlaku', 'class' => 'bg-warning text-dark'];
                                    } elseif ($sampai && $sampai->lt($today)) {
                                        $validityBadge = ['label' => 'Expired', 'class' => 'bg-danger'];
                                    } else {
                                        $validityBadge = ['label' => 'Berlaku', 'class' => 'bg-info text-dark'];
                                    }
                                }
                            @endphp
                            <tr>
                                <td class="text-center">{{ $pajaks->firstItem() + $loop->index }}</td>
                                <td>
                                    <span class="badge bg-light text-dark border fw-semibold">{{ $pajak->kode_pajak ?? '-' }}</span>
                                </td>
                                <td class="fw-semibold">{{ $pajak->jenis_pajak }}</td>
                                <td class="text-center">
                                    <span class="fw-bold text-primary">{{ rtrim(rtrim(number_format($pajak->persentase, 4, ',', '.'), '0'), ',') }}%</span>
                                </td>
                                <td>
                                    @if($pajak->rumus)
                                        <span class="text-muted" title="{{ $pajak->rumus }}">{{ \Illuminate\Support\Str::limit($pajak->rumus, 40) }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    <div>{{ $periodLabel }}</div>
                                    @if($validityBadge)
                                        <span class="badge {{ $validityBadge['class'] }} mt-1" style="font-size: 10px;">{{ $validityBadge['label'] }}</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <span class="badge {{ $pajak->status_aktif ? 'bg-success' : 'bg-secondary' }}">
                                        {{ $pajak->status_aktif ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group">
                                        <a href="{{ route('master-pajak.show', $pajak) }}" class="btn btn-sm btn-primary">Detail</a>
                                        <button type="button" class="btn btn-sm btn-primary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false">
                                            <span class="visually-hidden">Toggle Dropdown</span>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                            <li><a class="dropdown-item" href="{{ route('master-pajak.edit', $pajak) }}">Edit</a></li>
                                            <li>
                                                <form action="{{ route('master-pajak.toggle', $pajak) }}" method="POST">
                                                    @csrf
                                                    <button type="submit" class="dropdown-item">
                                                        {{ $pajak->status_aktif ? 'Nonaktifkan' : 'Aktifkan' }}
                                                    </button>
                                                </form>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">Belum ada data tarif pajak yang sesuai dengan filter.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($pajaks->hasPages())
                <div class="mt-4 d-flex justify-content-end">
                    {{ $pajaks->withQueryString()->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
