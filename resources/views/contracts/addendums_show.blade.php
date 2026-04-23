@extends('layouts.app')

@section('title', 'Detail Addendum Kontrak')

@section('content')
@php
    $statusClasses = [
        \App\Models\KontrakAddendum::STATUS_DRAFT => 'bg-secondary',
        \App\Models\KontrakAddendum::STATUS_SUBMITTED => 'bg-warning text-dark',
        \App\Models\KontrakAddendum::STATUS_APPROVED => 'bg-success',
        \App\Models\KontrakAddendum::STATUS_REJECTED => 'bg-danger',
    ];
    $documentLabels = [
        'FILE_ADDENDUM' => 'File Addendum',
        'NOTA_DINAS' => 'Nota Dinas / Justifikasi',
        'DOKUMEN_PENDUKUNG_TEKNIS' => 'Dokumen Pendukung Teknis',
        'LAMPIRAN_SPESIFIKASI' => 'Lampiran Perubahan Spesifikasi',
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

@if ($errors->any())
    <div class="alert alert-danger border-0 shadow-sm rounded-4">{{ $errors->first() }}</div>
@endif

<div class="d-flex flex-column flex-lg-row justify-content-between gap-3 align-items-lg-center mb-4">
    <div>
        <a href="{{ route('addendums.index', $contract) }}" class="btn btn-outline-secondary btn-sm mb-2">
            <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar Addendum
        </a>
        <h4 class="fw-bold mb-1">{{ $addendum->nomor_addendum }}</h4>
        <div class="text-muted">Workspace detail addendum untuk meninjau perubahan, dokumen, dan status persetujuan.</div>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <span class="badge {{ $statusClasses[$addendum->status_workflow] ?? 'bg-secondary' }} px-3 py-2">
            {{ str_replace('_', ' ', $addendum->status_workflow) }}
        </span>
        <span class="badge bg-light text-dark border px-3 py-2">
            {{ $addendum->jenis_label }}
        </span>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="small text-muted">Tanggal Addendum</div>
                        <div class="fw-bold">{{ optional($addendum->tanggal_addendum)->translatedFormat('d M Y') ?? '-' }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="small text-muted">Kontrak Sumber</div>
                        <div class="fw-bold">{{ $contract->nomor_spk ?? '-' }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="small text-muted">Nama Pekerjaan</div>
                        <div class="fw-bold">{{ $contract->nama_pekerjaan ?? '-' }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="small text-muted">Vendor</div>
                        <div class="fw-bold">{{ optional($contract->vendor)->nama_pihak ?? optional($contract->vendor)->nama_perusahaan ?? '-' }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="small text-muted">Nilai Kontrak Aktif Saat Ini</div>
                        <div class="fw-bold text-success">Rp {{ number_format((float) ($contract->nilai_total_kontrak ?? 0), 0, ',', '.') }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="small text-muted">Tanggal Selesai Aktif Saat Ini</div>
                        <div class="fw-bold">{{ $contract->tanggal_selesai ? \Carbon\Carbon::parse($contract->tanggal_selesai)->translatedFormat('d M Y') : '-' }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-white border-0 p-4 pb-0">
                <h6 class="fw-bold mb-1">Perbandingan Sebelum dan Sesudah</h6>
                <div class="text-muted small">Data lama tetap tersimpan sebagai histori, sementara data baru menjadi dasar perubahan saat addendum disetujui.</div>
            </div>
            <div class="card-body p-4">
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Aspek</th>
                                <th>Sebelum</th>
                                <th>Sesudah</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="fw-semibold">Nilai Kontrak</td>
                                <td>Rp {{ number_format((float) $addendum->nilai_kontrak_lama, 0, ',', '.') }}</td>
                                <td>Rp {{ number_format((float) $addendum->nilai_kontrak_baru, 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td class="fw-semibold">Tanggal Selesai</td>
                                <td>{{ optional($addendum->tanggal_selesai_lama)->translatedFormat('d M Y') ?? '-' }}</td>
                                <td>{{ optional($addendum->tanggal_selesai_baru)->translatedFormat('d M Y') ?? '-' }}</td>
                            </tr>
                            <tr>
                                <td class="fw-semibold">Jangka Waktu</td>
                                <td>{{ number_format((int) $addendum->jangka_waktu_lama, 0, ',', '.') }} {{ strtolower($contract->satuan_waktu ?? 'hari') }}</td>
                                <td>{{ number_format((int) $addendum->jangka_waktu_baru, 0, ',', '.') }} {{ strtolower($contract->satuan_waktu ?? 'hari') }}</td>
                            </tr>
                            <tr>
                                <td class="fw-semibold">Alasan / Keterangan</td>
                                <td colspan="2">{{ $addendum->keterangan_alasan ?: '-' }}</td>
                            </tr>
                            <tr>
                                <td class="fw-semibold">Catatan Perubahan Spesifikasi</td>
                                <td colspan="2">{{ $addendum->catatan_perubahan_spesifikasi ?: '-' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-white border-0 p-4 pb-0">
                <h6 class="fw-bold mb-1">Dampak ke Kontrak</h6>
                <div class="text-muted small">Sistem tidak mengubah termin atau jaminan otomatis, tetapi menampilkan indikator bila perlu ditinjau ulang.</div>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="border rounded-4 p-3 h-100 bg-light">
                            <div class="small text-muted mb-1">Perubahan Nilai</div>
                            <div class="fw-bold {{ $impactSummary['nilai_berubah'] ? 'text-success' : 'text-dark' }}">{{ $impactSummary['nilai_berubah'] ? 'Ya, nilai berubah' : 'Tidak berubah' }}</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="border rounded-4 p-3 h-100 bg-light">
                            <div class="small text-muted mb-1">Perubahan Waktu</div>
                            <div class="fw-bold {{ $impactSummary['waktu_berubah'] ? 'text-warning' : 'text-dark' }}">{{ $impactSummary['waktu_berubah'] ? 'Ya, waktu berubah' : 'Tidak berubah' }}</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="border rounded-4 p-3 h-100 bg-light">
                            <div class="small text-muted mb-1">Perubahan Spesifikasi</div>
                            <div class="fw-bold {{ $impactSummary['spesifikasi_berubah'] ? 'text-info' : 'text-dark' }}">{{ $impactSummary['spesifikasi_berubah'] ? 'Ya, spesifikasi berubah' : 'Tidak berubah' }}</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="border rounded-4 p-3 h-100 bg-light">
                            <div class="small text-muted mb-1">Termin</div>
                            <div class="fw-bold {{ $impactSummary['termin_perlu_ditinjau'] ? 'text-danger' : 'text-dark' }}">
                                {{ $impactSummary['termin_perlu_ditinjau'] ? 'Termin perlu ditinjau ulang' : 'Tidak ada indikator konflik otomatis' }}
                            </div>
                            <div class="small text-muted mt-2">Total termin saat ini Rp {{ number_format((float) $terminSummary['total'], 0, ',', '.') }}</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="border rounded-4 p-3 h-100 bg-light">
                            <div class="small text-muted mb-1">Jaminan</div>
                            <div class="fw-bold {{ $impactSummary['jaminan_perlu_ditinjau'] ? 'text-danger' : 'text-dark' }}">
                                {{ $impactSummary['jaminan_perlu_ditinjau'] ? 'Jaminan perlu ditinjau ulang' : 'Tidak ada indikator konflik otomatis' }}
                            </div>
                            <div class="small text-muted mt-2">Jumlah jaminan terhubung: {{ $jaminanSummary['jumlah'] }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-white border-0 p-4 pb-0">
                <h6 class="fw-bold mb-1">Dokumen Pendukung</h6>
                <div class="text-muted small">Gunakan dokumen ini untuk memverifikasi dasar perubahan addendum.</div>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    @forelse($addendum->arsipDokumen->where('is_active', true) as $arsip)
                        <div class="col-md-6">
                            <div class="border rounded-4 p-3 h-100 bg-light">
                                <div class="small text-muted mb-1">{{ $documentLabels[$arsip->jenis_dokumen] ?? $arsip->jenis_dokumen }}</div>
                                <div class="fw-semibold text-truncate">{{ $arsip->nama_file_asli }}</div>
                                <div class="small text-muted mt-1">{{ optional($arsip->uploaded_at)->translatedFormat('d M Y H:i') ?? optional($arsip->created_at)->translatedFormat('d M Y H:i') }}</div>
                                <a href="{{ Storage::url($arsip->path_file) }}" target="_blank" class="btn btn-sm btn-outline-primary mt-3">
                                    <i class="bi bi-eye me-1"></i> Lihat / Unduh
                                </a>
                            </div>
                        </div>
                    @empty
                        <div class="col-12">
                            <div class="text-center text-muted py-4 border rounded-4 bg-light">Belum ada dokumen pendukung yang diunggah.</div>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-white border-0 p-4 pb-0">
                <h6 class="fw-bold mb-1">Aksi Addendum</h6>
                <div class="text-muted small">Gunakan aksi sesuai status addendum saat ini.</div>
            </div>
            <div class="card-body p-4 d-grid gap-2">
                @if($canManageDraft && in_array($addendum->status_workflow, [\App\Models\KontrakAddendum::STATUS_DRAFT, \App\Models\KontrakAddendum::STATUS_REJECTED], true))
                    <a href="{{ route('addendums.edit', [$contract, $addendum]) }}" class="btn btn-warning fw-bold text-dark">
                        <i class="bi bi-pencil-square me-1"></i> Ubah Draft
                    </a>
                    <form action="{{ route('addendums.submit', [$contract, $addendum]) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-success w-100 fw-bold" onclick="return confirm('Ajukan addendum ini untuk persetujuan?')">
                            <i class="bi bi-send-check me-1"></i> Ajukan Addendum
                        </button>
                    </form>
                @endif

                @if($canReview && $addendum->status_workflow === \App\Models\KontrakAddendum::STATUS_SUBMITTED)
                    <form action="{{ route('addendums.approve', [$contract, $addendum]) }}" method="POST" class="border rounded-4 p-3 bg-light">
                        @csrf
                        <label class="form-label fw-semibold">Catatan persetujuan</label>
                        <textarea name="approval_note" class="form-control mb-3" rows="3" placeholder="Opsional, misalnya catatan persetujuan addendum."></textarea>
                        <button type="submit" class="btn btn-success w-100 fw-bold" onclick="return confirm('Setujui addendum ini dan perbarui kontrak utama?')">
                            <i class="bi bi-check2-circle me-1"></i> Setujui Addendum
                        </button>
                    </form>

                    <form action="{{ route('addendums.reject', [$contract, $addendum]) }}" method="POST" class="border rounded-4 p-3 bg-light">
                        @csrf
                        <label class="form-label fw-semibold">Catatan revisi <span class="text-danger">*</span></label>
                        <textarea name="rejection_note" class="form-control mb-3" rows="4" minlength="10" required placeholder="Jelaskan bagian yang harus diperbaiki sebelum addendum diajukan kembali."></textarea>
                        <button type="submit" class="btn btn-danger w-100 fw-bold">
                            <i class="bi bi-x-circle me-1"></i> Kembalikan untuk Revisi
                        </button>
                    </form>
                @endif
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-white border-0 p-4 pb-0">
                <h6 class="fw-bold mb-1">Timeline / Status</h6>
                <div class="text-muted small">Riwayat status addendum dan siapa yang memprosesnya.</div>
            </div>
            <div class="card-body p-4">
                @forelse($timeline as $log)
                    <div class="d-flex gap-3 mb-4">
                        <div class="pt-1">
                            <span class="badge bg-light text-primary border rounded-pill px-2 py-2">
                                <i class="bi bi-clock-history"></i>
                            </span>
                        </div>
                        <div>
                            <div class="fw-semibold">{{ str_replace('_', ' ', $log->status_baru ?? $log->aksi) }}</div>
                            <div class="small text-muted">{{ optional($log->created_at)->translatedFormat('d M Y H:i') }} oleh {{ optional($log->user)->name ?? 'Sistem' }}</div>
                            <div class="small text-muted">{{ $log->catatan ?: '-' }}</div>
                        </div>
                    </div>
                @empty
                    <div class="text-center text-muted py-4">Belum ada riwayat status addendum.</div>
                @endforelse
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white border-0 p-4 pb-0">
                <h6 class="fw-bold mb-1">Riwayat Kontrak Terkait</h6>
                <div class="text-muted small">Menunjukkan addendum lain pada kontrak yang sama dan versi kontrak aktif saat ini.</div>
            </div>
            <div class="card-body p-4">
                <div class="border rounded-4 p-3 bg-light mb-3">
                    <div class="small text-muted mb-1">Versi kontrak aktif</div>
                    <div class="fw-bold">Rp {{ number_format((float) ($contract->nilai_total_kontrak ?? 0), 0, ',', '.') }}</div>
                    <div class="small text-muted">Selesai {{ $contract->tanggal_selesai ? \Carbon\Carbon::parse($contract->tanggal_selesai)->translatedFormat('d M Y') : '-' }}</div>
                </div>

                @forelse($previousAddendums as $item)
                    <a href="{{ route('addendums.show', [$contract, $item]) }}" class="text-decoration-none">
                        <div class="border rounded-4 p-3 mb-3">
                            <div class="fw-semibold text-dark">{{ $item->nomor_addendum }}</div>
                            <div class="small text-muted">{{ optional($item->tanggal_addendum)->translatedFormat('d M Y') }} • {{ str_replace('_', ' ', $item->status_workflow) }}</div>
                        </div>
                    </a>
                @empty
                    <div class="text-muted small">Belum ada addendum sebelumnya pada kontrak ini.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
