@extends('layouts.app')
@section('title', 'Permohonan Penerbangan Non-Schedule')

@section('content')
@include('super_admin_jasa.laporan._styles')
@php
    $jenisOpt = \App\Models\PermohonanNonSchedule::JENIS_LABEL;
    $statusOpt = \App\Models\PermohonanNonSchedule::STATUS_LABEL;
@endphp

<div class="sa-report-page">
    <div class="sa-report-hero d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <div class="small text-uppercase fw-bold" style="letter-spacing:.08em;color:#fbbf24;">AMC &middot; Operasional Penerbangan</div>
            <h4 class="fw-bold mb-1"><i class="bi bi-airplane-engines me-2"></i>Permohonan Penerbangan Non-Schedule</h4>
            <p class="mb-0 small">AMC mengajukan permohonan dengan surat resmi; Admin Jasa menyetujui sebelum tagihan dibuat.</p>
        </div>
        @if(auth()->user()?->hasAnyRole(['Super Admin', 'AMC']))
            <a href="{{ route('permohonan-non-schedule.create') }}" class="btn btn-warning fw-bold">
                <i class="bi bi-plus-lg me-1"></i>Ajukan Permohonan
            </a>
        @endif
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0">{{ session('success') }}</div>
    @endif

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-uppercase">Status</label>
                    <select name="status" class="form-select">
                        <option value="">Semua</option>
                        @foreach($statusOpt as $v => $l)
                            <option value="{{ $v }}" @selected(($filters['status'] ?? '') === $v)>{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-uppercase">Jenis</label>
                    <select name="jenis_penerbangan" class="form-select">
                        <option value="">Semua</option>
                        @foreach($jenisOpt as $v => $l)
                            <option value="{{ $v }}" @selected(($filters['jenis_penerbangan'] ?? '') === $v)>{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold text-uppercase">Mitra</label>
                    <select name="mitra_jasa_id" class="form-select">
                        <option value="">Semua</option>
                        @foreach($mitraOptions as $m)
                            <option value="{{ $m->id }}" @selected(($filters['mitra_jasa_id'] ?? '') == $m->id)>{{ $m->nama_mitra }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 d-grid">
                    <button class="btn btn-primary fw-bold"><i class="bi bi-funnel me-1"></i>Filter</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="small text-uppercase">No. Surat</th>
                        <th class="small text-uppercase">Tanggal Surat</th>
                        <th class="small text-uppercase">Mitra</th>
                        <th class="small text-uppercase">Jenis</th>
                        <th class="small text-uppercase">Periode Penerbangan</th>
                        <th class="small text-uppercase">Penerbangan</th>
                        <th class="small text-uppercase">Status</th>
                        <th class="small text-uppercase text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $row)
                        <tr>
                            <td><div class="fw-bold">{{ $row->nomor_surat }}</div></td>
                            <td>{{ $row->tanggal_surat?->format('d/m/Y') }}</td>
                            <td>
                                <div class="fw-bold small">{{ $row->mitra?->nama_mitra ?? '-' }}</div>
                                <div class="small text-muted">oleh {{ $row->creator?->name ?? '-' }}</div>
                            </td>
                            <td><span class="badge bg-info-subtle text-info-emphasis">{{ $row->jenis_label }}</span></td>
                            <td class="small">
                                {{ $row->tanggal_penerbangan_dari?->format('d/m/Y') }}
                                @if($row->tanggal_penerbangan_sampai) <span class="text-muted">s.d.</span> {{ $row->tanggal_penerbangan_sampai->format('d/m/Y') }}@endif
                            </td>
                            <td class="small">
                                @if($row->nomor_penerbangan) <div><strong>{{ $row->nomor_penerbangan }}</strong></div>@endif
                                @if($row->registrasi_pesawat) <div class="text-muted">{{ $row->registrasi_pesawat }}</div>@endif
                                @if($row->rute) <div class="text-muted">{{ $row->rute }}</div>@endif
                            </td>
                            <td>
                                <span class="badge {{ $row->status_badge }}">{{ $row->status_label }}</span>
                                @if($row->reviewer)
                                    <div class="small text-muted mt-1">oleh {{ $row->reviewer->name }}<br>{{ $row->reviewed_at?->format('d/m/Y H:i') }}</div>
                                @endif
                                @if($row->catatan_review)
                                    <div class="small text-muted mt-1 fst-italic">"{{ $row->catatan_review }}"</div>
                                @endif
                            </td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('permohonan-non-schedule.file', $row->id) }}" target="_blank" class="btn btn-outline-secondary" title="Lihat Surat"><i class="bi bi-file-earmark-pdf"></i></a>
                                    @if(auth()->user()?->hasRole('Super Admin') || (auth()->id() === $row->created_by && $row->status === 'DIAJUKAN'))
                                        <a href="{{ route('permohonan-non-schedule.edit', $row->id) }}" class="btn btn-outline-primary" title="Ubah"><i class="bi bi-pencil"></i></a>
                                    @endif
                                    @if($canReview && $row->status === 'DIAJUKAN')
                                        <button type="button" class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#reviewModal-{{ $row->id }}" title="Review"><i class="bi bi-check2-circle"></i></button>
                                    @endif
                                </div>

                                @if($canReview && $row->status === 'DIAJUKAN')
                                    <div class="modal fade" id="reviewModal-{{ $row->id }}" tabindex="-1">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <form method="POST" action="{{ route('permohonan-non-schedule.review', $row->id) }}" class="modal-content">
                                                @csrf
                                                <div class="modal-header" style="background:linear-gradient(120deg,#14375d,#1d5d95);color:#fff;--bs-heading-color:#fff;">
                                                    <h6 class="modal-title fw-bold" style="color:#fff;"><i class="bi bi-clipboard2-check me-2"></i>Review Permohonan {{ $row->nomor_surat }}</h6>
                                                </div>
                                                <div class="modal-body text-start">
                                                    <div class="mb-3">
                                                        <label class="form-label fw-bold">Keputusan</label>
                                                        <select name="status" class="form-select" required>
                                                            <option value="DISETUJUI">Setujui</option>
                                                            <option value="DITOLAK">Tolak</option>
                                                        </select>
                                                    </div>
                                                    <div class="mb-2">
                                                        <label class="form-label fw-bold">Catatan</label>
                                                        <textarea name="catatan_review" rows="3" class="form-control" placeholder="Opsional, wajib bila ditolak"></textarea>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Batal</button>
                                                    <button class="btn btn-primary fw-bold">Simpan</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted py-5"><i class="bi bi-inbox fs-2 d-block mb-2 opacity-50"></i>Belum ada permohonan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($items->hasPages())
            <div class="card-footer bg-white border-0">{{ $items->links() }}</div>
        @endif
    </div>
</div>
@endsection
