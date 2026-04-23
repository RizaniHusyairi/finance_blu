@extends('layouts.app')

@section('title', 'Daftar Addendum Kontrak')

@section('content')
@php
    $statusClasses = [
        \App\Models\KontrakAddendum::STATUS_DRAFT => 'bg-secondary',
        \App\Models\KontrakAddendum::STATUS_SUBMITTED => 'bg-warning text-dark',
        \App\Models\KontrakAddendum::STATUS_APPROVED => 'bg-success',
        \App\Models\KontrakAddendum::STATUS_REJECTED => 'bg-danger',
    ];
@endphp

@if(session('success'))
    <div class="alert alert-success border-0 shadow-sm rounded-4">{{ session('success') }}</div>
@endif

@if(session('error'))
    <div class="alert alert-danger border-0 shadow-sm rounded-4">{{ session('error') }}</div>
@endif

@if(session('warning'))
    <div class="alert alert-warning border-0 shadow-sm rounded-4 text-dark">{{ session('warning') }}</div>
@endif

<div class="d-flex flex-column flex-lg-row justify-content-between gap-3 align-items-lg-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Daftar Addendum Kontrak</h4>
        <div class="text-muted">Riwayat perubahan terhadap kontrak pengadaan.</div>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('contracts.show', $contract) }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Detail Kontrak
        </a>
        @if($canManageDraft)
            <a href="{{ route('addendums.create', $contract) }}" class="btn btn-primary fw-bold">
                <i class="bi bi-plus-lg me-1"></i> Buat Addendum
            </a>
        @endif
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-body p-4">
        <div class="row g-3">
            <div class="col-md-4">
                <div class="border rounded-4 p-3 h-100 bg-light">
                    <div class="small text-muted mb-1">Nomor SPK</div>
                    <div class="fw-bold">{{ $contract->nomor_spk ?? '-' }}</div>
                    <div class="small text-muted mt-2">Status: {{ $contract->status_kontrak ?? '-' }}</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="border rounded-4 p-3 h-100 bg-light">
                    <div class="small text-muted mb-1">Nama Pekerjaan / Vendor</div>
                    <div class="fw-bold">{{ $contract->nama_pekerjaan ?? '-' }}</div>
                    <div class="small text-muted mt-2">{{ optional($contract->vendor)->nama_pihak ?? optional($contract->vendor)->nama_perusahaan ?? 'Vendor belum terhubung' }}</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="border rounded-4 p-3 h-100 bg-light">
                    <div class="small text-muted mb-1">Nilai Kontrak Aktif</div>
                    <div class="fw-bold text-success">Rp {{ number_format((float) ($contract->nilai_total_kontrak ?? 0), 0, ',', '.') }}</div>
                    <div class="small text-muted mt-2">Tanggal selesai: {{ $contract->tanggal_selesai ? \Carbon\Carbon::parse($contract->tanggal_selesai)->translatedFormat('d M Y') : '-' }}</div>
                </div>
            </div>
        </div>

        @if($terminSummary['requires_review'])
            <div class="alert alert-warning border-0 rounded-4 mt-4 mb-0 text-dark">
                <div class="fw-semibold">Termin perlu ditinjau ulang</div>
                <div class="small">
                    Total termin saat ini Rp {{ number_format($terminSummary['total'], 0, ',', '.') }} belum sama dengan nilai kontrak aktif.
                </div>
            </div>
        @endif
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-6 col-xl-2">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body">
                <div class="small text-muted">Total Addendum</div>
                <div class="fw-bold fs-4">{{ $summary['total'] }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-2">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body">
                <div class="small text-muted">Draft</div>
                <div class="fw-bold fs-4 text-secondary">{{ $summary['draft'] }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body">
                <div class="small text-muted">Menunggu Persetujuan</div>
                <div class="fw-bold fs-4 text-warning">{{ $summary['submitted'] }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-2">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body">
                <div class="small text-muted">Disetujui</div>
                <div class="fw-bold fs-4 text-success">{{ $summary['approved'] }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body">
                <div class="small text-muted">Ditolak / Revisi</div>
                <div class="fw-bold fs-4 text-danger">{{ $summary['rejected'] }}</div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Nomor Addendum</th>
                        <th>Tanggal</th>
                        <th>Jenis</th>
                        <th>Nilai Lama</th>
                        <th>Nilai Baru</th>
                        <th>Tgl Selesai Lama</th>
                        <th>Tgl Selesai Baru</th>
                        <th>Status</th>
                        <th class="text-end pe-4">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($addendums as $addendum)
                        <tr>
                            <td class="ps-4">
                                <div class="fw-bold">{{ $addendum->nomor_addendum }}</div>
                                <div class="small text-muted">{{ \Illuminate\Support\Str::limit($addendum->keterangan_alasan, 60) }}</div>
                            </td>
                            <td>{{ optional($addendum->tanggal_addendum)->translatedFormat('d M Y') ?? '-' }}</td>
                            <td><span class="badge bg-light text-dark border">{{ $addendum->jenis_label }}</span></td>
                            <td>Rp {{ number_format((float) $addendum->nilai_kontrak_lama, 0, ',', '.') }}</td>
                            <td>Rp {{ number_format((float) $addendum->nilai_kontrak_baru, 0, ',', '.') }}</td>
                            <td>{{ optional($addendum->tanggal_selesai_lama)->translatedFormat('d M Y') ?? '-' }}</td>
                            <td>{{ optional($addendum->tanggal_selesai_baru)->translatedFormat('d M Y') ?? '-' }}</td>
                            <td>
                                <span class="badge {{ $statusClasses[$addendum->status_workflow] ?? 'bg-secondary' }}">
                                    {{ str_replace('_', ' ', $addendum->status_workflow) }}
                                </span>
                            </td>
                            <td class="text-end pe-4">
                                <div class="d-flex flex-wrap justify-content-end gap-1">
                                    <a href="{{ route('addendums.show', [$contract, $addendum]) }}" class="btn btn-sm btn-light border text-primary">
                                        Detail
                                    </a>

                                    @if($canManageDraft && in_array($addendum->status_workflow, [\App\Models\KontrakAddendum::STATUS_DRAFT, \App\Models\KontrakAddendum::STATUS_REJECTED], true))
                                        <a href="{{ route('addendums.edit', [$contract, $addendum]) }}" class="btn btn-sm btn-light border text-warning">
                                            Edit
                                        </a>
                                        <form action="{{ route('addendums.submit', [$contract, $addendum]) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-light border text-success" onclick="return confirm('Ajukan addendum ini untuk persetujuan?')">
                                                Submit
                                            </button>
                                        </form>
                                    @endif

                                    @if($canManageDraft && $addendum->status_workflow === \App\Models\KontrakAddendum::STATUS_DRAFT)
                                        <form action="{{ route('addendums.destroy', [$contract, $addendum]) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus draft addendum ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-light border text-danger">
                                                Hapus
                                            </button>
                                        </form>
                                    @endif

                                    @if($canReview && $addendum->status_workflow === \App\Models\KontrakAddendum::STATUS_SUBMITTED)
                                        <form action="{{ route('addendums.approve', [$contract, $addendum]) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Setujui addendum ini dan perbarui kontrak utama?')">
                                                Approve
                                            </button>
                                        </form>
                                        <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#rejectModal{{ $addendum->id }}">
                                            Reject
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-5 text-muted">
                                Belum ada addendum untuk kontrak ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@foreach($addendums->where('status_workflow', \App\Models\KontrakAddendum::STATUS_SUBMITTED) as $addendum)
    <div class="modal fade" id="rejectModal{{ $addendum->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form action="{{ route('addendums.reject', [$contract, $addendum]) }}" method="POST" class="modal-content border-0 shadow rounded-4">
                @csrf
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold">Tolak / Kembalikan Addendum</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="small text-muted mb-2">{{ $addendum->nomor_addendum }}</div>
                    <label class="form-label fw-semibold">Catatan revisi <span class="text-danger">*</span></label>
                    <textarea name="rejection_note" class="form-control" rows="4" minlength="10" required placeholder="Jelaskan alasan addendum ini dikembalikan untuk revisi."></textarea>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger">Kembalikan untuk Revisi</button>
                </div>
            </form>
        </div>
    </div>
@endforeach
@endsection
