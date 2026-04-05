@extends('layouts.app')

@section('title', 'Detail COA')

@section('content')
    <x-page-title title="Detail COA" subtitle="Ringkasan struktur kode akun dan pemakaiannya pada item anggaran DIPA" />

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
            <h5 class="mb-1 fw-bold">{{ $coa->kode_mak_lengkap ?: 'Detail COA' }}</h5>
            <p class="text-muted mb-0">Informasi lengkap master COA dan jejak pemakaiannya pada modul DIPA.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            @if($statistics['jumlah_item_dipa'] === 0)
                <form action="{{ route('coas.destroy', $coa) }}" method="POST" onsubmit="return confirm('Hapus COA ini secara permanen?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger">
                        <i class="bi bi-trash me-1"></i> Hapus COA
                    </button>
                </form>
            @else
                <button type="button" class="btn btn-outline-danger" disabled>Hapus COA</button>
            @endif
            <a href="{{ route('coas.edit', $coa) }}" class="btn btn-outline-primary">
                <i class="bi bi-pencil-square me-1"></i> Edit COA
            </a>
            <a href="{{ route('coas.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar COA
            </a>
        </div>
    </div>

    <div class="card shadow-sm border-0 rounded-4 mb-4">
        <div class="card-body p-4">
            <div class="row g-3">
                <div class="col-md-5">
                    <div class="border rounded-4 p-3 h-100 bg-light-primary">
                        <div class="small text-muted mb-1">COA Lengkap</div>
                        <div class="fw-bold fs-4 text-primary">{{ $coa->kode_mak_lengkap ?: '-' }}</div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="border rounded-4 p-3 h-100">
                        <div class="small text-muted mb-1">Kode Akun</div>
                        <div class="fw-bold fs-5">{{ $coa->kd_akun ?: '-' }}</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="border rounded-4 p-3 h-100">
                        <div class="small text-muted mb-1">Jenis Akun</div>
                        <div class="fw-bold fs-5">{{ $coa->jenis_akun ?: '-' }}</div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="border rounded-4 p-3 h-100">
                        <div class="small text-muted mb-1">Status</div>
                        <span class="badge {{ $coa->status_aktif ? 'bg-success' : 'bg-secondary' }}">
                            {{ $coa->status_aktif ? 'Aktif' : 'Nonaktif' }}
                        </span>
                    </div>
                </div>
                <div class="col-12">
                    <div class="border rounded-4 p-3">
                        <div class="small text-muted mb-1">Nama Akun</div>
                        <div class="fw-bold fs-5">{{ $coa->nama_akun }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-7">
            <div class="card shadow-sm border-0 rounded-4 h-100">
                <div class="card-header bg-white border-bottom-0 pt-4 px-4">
                    <h5 class="mb-1 fw-bold">Struktur Kode COA</h5>
                    <p class="text-muted small mb-0">Komponen pembentuk kode MAK lengkap untuk akun ini.</p>
                </div>
                <div class="card-body px-4 pb-4">
                    <div class="row g-3">
                        <div class="col-md-6"><div class="border rounded-4 p-3 h-100"><div class="small text-muted mb-1">Kode Program</div><div class="fw-semibold">{{ $coa->kd_program ?: '-' }}</div></div></div>
                        <div class="col-md-6"><div class="border rounded-4 p-3 h-100"><div class="small text-muted mb-1">Kode Giat</div><div class="fw-semibold">{{ $coa->kd_giat ?: '-' }}</div></div></div>
                        <div class="col-md-6"><div class="border rounded-4 p-3 h-100"><div class="small text-muted mb-1">Kode Output</div><div class="fw-semibold">{{ $coa->kd_output ?: '-' }}</div></div></div>
                        <div class="col-md-6"><div class="border rounded-4 p-3 h-100"><div class="small text-muted mb-1">Kode Suboutput</div><div class="fw-semibold">{{ $coa->kd_suboutput ?: '-' }}</div></div></div>
                        <div class="col-md-6"><div class="border rounded-4 p-3 h-100"><div class="small text-muted mb-1">Kode Komponen</div><div class="fw-semibold">{{ $coa->kd_komponen ?: '-' }}</div></div></div>
                        <div class="col-md-6"><div class="border rounded-4 p-3 h-100"><div class="small text-muted mb-1">Kode Subkomponen</div><div class="fw-semibold">{{ $coa->kd_subkomponen ?: '-' }}</div></div></div>
                        <div class="col-md-6"><div class="border rounded-4 p-3 h-100"><div class="small text-muted mb-1">Kode Akun</div><div class="fw-semibold">{{ $coa->kd_akun ?: '-' }}</div></div></div>
                        <div class="col-md-6"><div class="border rounded-4 p-3 h-100"><div class="small text-muted mb-1">Kode Item</div><div class="fw-semibold">{{ $coa->kd_item ?: '-' }}</div></div></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card shadow-sm border-0 rounded-4 h-100">
                <div class="card-header bg-white border-bottom-0 pt-4 px-4">
                    <h5 class="mb-1 fw-bold">Statistik Penggunaan COA</h5>
                    <p class="text-muted small mb-0">Ringkasan pemakaian COA ini pada item anggaran DIPA.</p>
                </div>
                <div class="card-body px-4 pb-4">
                    <div class="row g-3">
                        <div class="col-md-6"><div class="border rounded-4 p-3 h-100"><div class="small text-muted mb-1">Dipakai di Item DIPA</div><div class="fw-bold fs-4">{{ number_format($statistics['jumlah_item_dipa']) }}</div></div></div>
                        <div class="col-md-6"><div class="border rounded-4 p-3 h-100"><div class="small text-muted mb-1">Dipakai di Revisi DIPA</div><div class="fw-bold fs-4">{{ number_format($statistics['jumlah_revisi_dipa']) }}</div></div></div>
                        <div class="col-md-6"><div class="border rounded-4 p-3 h-100"><div class="small text-muted mb-1">Dipakai di DIPA</div><div class="fw-bold fs-4">{{ number_format($statistics['jumlah_dipa']) }}</div></div></div>
                        <div class="col-md-6"><div class="border rounded-4 p-3 h-100 bg-light-success"><div class="small text-muted mb-1">Total Nilai Pagu</div><div class="fw-bold fs-5 text-success">Rp {{ number_format($statistics['total_nilai_pagu'], 0, ',', '.') }}</div></div></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0 rounded-4">
        <div class="card-header bg-white border-bottom-0 pt-4 px-4">
            <h5 class="mb-1 fw-bold">Pemakaian COA pada DIPA</h5>
            <p class="text-muted small mb-0">Daftar item anggaran DIPA yang menggunakan COA ini.</p>
        </div>
        <div class="card-body px-4 pb-4">
            @if($usageItems->isEmpty())
                <div class="border rounded-4 p-5 text-center bg-light">
                    <div class="mb-3"><i class="bi bi-inbox fs-1 text-muted"></i></div>
                    <h6 class="fw-bold mb-2">COA ini belum dipakai pada item DIPA mana pun</h6>
                    <p class="text-muted mb-0">Karena belum dipakai, COA ini aman untuk dihapus bila memang tidak diperlukan.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="text-center" width="5%">No</th>
                                <th width="22%">Nomor DIPA</th>
                                <th width="10%">Tahun Anggaran</th>
                                <th width="11%">Nomor Revisi</th>
                                <th width="16%" class="text-end">Nilai Pagu</th>
                                <th width="13%" class="text-center">Status Revisi</th>
                                <th width="13%" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($usageItems as $item)
                                @php
                                    $revision = $item->dipaRevision;
                                    $dipa = $revision?->masterDipa;
                                @endphp
                                <tr>
                                    <td class="text-center">{{ $loop->iteration }}</td>
                                    <td><div class="fw-bold text-primary">{{ $dipa?->nomor_dipa ?: '-' }}</div><div class="small text-muted">{{ $dipa?->status_aktif ? 'DIPA aktif' : 'DIPA nonaktif' }}</div></td>
                                    <td><span class="badge bg-light text-dark border">{{ $dipa?->tahun_anggaran ?: '-' }}</span></td>
                                    <td><span class="badge bg-info text-dark">Revisi {{ $revision?->nomor_revisi ?? '-' }}</span></td>
                                    <td class="text-end fw-semibold">Rp {{ number_format($item->nilai_pagu, 0, ',', '.') }}</td>
                                    <td class="text-center"><span class="badge {{ $revision?->is_active ? 'bg-success' : 'bg-secondary' }}">{{ $revision?->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
                                    <td class="text-center">
                                        @if($dipa)
                                            <a href="{{ route('dipas.show', $dipa) }}" class="btn btn-sm btn-outline-primary">Lihat Detail DIPA</a>
                                        @else
                                            <span class="text-muted small">Data tidak tersedia</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
@endsection
