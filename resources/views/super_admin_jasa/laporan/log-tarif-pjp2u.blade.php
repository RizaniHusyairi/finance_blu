@extends('layouts.app')
@section('title', 'Log Perubahan Tarif PJP2U')

@section('content')
@include('super_admin_jasa.laporan._styles')
@php
    $rupiah = fn ($v) => 'Rp ' . number_format((float) $v, 0, ',', '.');
    $angka = fn ($v) => number_format((float) $v, 0, ',', '.');
    $tipeOptions = [
        'revisi_resmi' => 'Revisi Resmi',
        'diskon' => 'Diskon / Penyesuaian',
        'koreksi' => 'Koreksi',
    ];
@endphp

<div class="sa-report-page">
<div class="sa-report-hero d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-journal-text me-2 text-primary"></i>Log Perubahan Tarif PJP2U</h4>
        <p class="text-muted mb-0 small">Audit trail seluruh perubahan tarif layanan PJP2U: revisi resmi, diskon periode, dan koreksi.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('super-admin-jasa.laporan.log-tarif-pjp2u.export', ['format' => 'pdf'] + request()->query()) }}" class="btn btn-outline-danger fw-bold">
            <i class="bi bi-filetype-pdf me-1"></i>PDF
        </a>
        <a href="{{ route('super-admin-jasa.laporan.log-tarif-pjp2u.export', ['format' => 'excel'] + request()->query()) }}" class="btn btn-outline-success fw-bold">
            <i class="bi bi-filetype-xls me-1"></i>Excel
        </a>
    </div>
</div>

<div class="card border-0 shadow-sm mb-3" style="border-radius:1rem;">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-bold text-uppercase">Tanggal Dari</label>
                <input type="date" name="tanggal_dari" class="form-control" value="{{ $filters['tanggal_dari'] }}">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-bold text-uppercase">Tanggal Sampai</label>
                <input type="date" name="tanggal_sampai" class="form-control" value="{{ $filters['tanggal_sampai'] }}">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-bold text-uppercase">Layanan</label>
                <select name="layanan_id" class="form-select">
                    <option value="">Semua</option>
                    @foreach($layananOptions as $opt)
                        <option value="{{ $opt->id }}" @selected($filters['layanan_id'] == $opt->id)>{{ $opt->nama_layanan }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-bold text-uppercase">Tipe</label>
                <select name="tipe_perubahan" class="form-select">
                    <option value="">Semua</option>
                    @foreach($tipeOptions as $val => $label)
                        <option value="{{ $val }}" @selected($filters['tipe_perubahan'] === $val)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-1 d-grid">
                <button type="submit" class="btn btn-primary fw-bold"><i class="bi bi-funnel"></i></button>
            </div>
        </form>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100" style="border-radius:1rem;">
            <div class="card-body">
                <div class="small text-uppercase text-muted fw-bold"><i class="bi bi-list-check me-1"></i>Total Perubahan</div>
                <div class="fs-3 fw-bold text-dark mt-1">{{ $angka($summary['total_perubahan']) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100" style="border-radius:1rem;">
            <div class="card-body">
                <div class="small text-uppercase text-muted fw-bold"><i class="bi bi-shield-check me-1"></i>Revisi Resmi</div>
                <div class="fs-3 fw-bold text-primary mt-1">{{ $angka($summary['revisi_resmi']) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100" style="border-radius:1rem;">
            <div class="card-body">
                <div class="small text-uppercase text-muted fw-bold"><i class="bi bi-percent me-1"></i>Diskon / Penyesuaian</div>
                <div class="fs-3 fw-bold text-warning mt-1">{{ $angka($summary['diskon']) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100" style="border-radius:1rem;">
            <div class="card-body">
                <div class="small text-uppercase text-muted fw-bold"><i class="bi bi-eraser me-1"></i>Koreksi</div>
                <div class="fs-3 fw-bold text-secondary mt-1">{{ $angka($summary['koreksi']) }}</div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm" style="border-radius:1rem;">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="small text-uppercase">Berlaku</th>
                    <th class="small text-uppercase">Layanan</th>
                    <th class="small text-uppercase text-end">Tarif Lama</th>
                    <th class="small text-uppercase text-end">Tarif Baru</th>
                    <th class="small text-uppercase text-end">Selisih</th>
                    <th class="small text-uppercase">Tipe</th>
                    <th class="small text-uppercase">Referensi</th>
                    <th class="small text-uppercase">Alasan</th>
                    <th class="small text-uppercase">Diubah</th>
                    <th class="small text-uppercase text-center">File</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                    @php
                        $selisih = $log->selisih;
                        $pct = $log->persentase_selisih;
                        $color = $selisih >= 0 ? 'text-success' : 'text-danger';
                        $tipeBadge = match($log->tipe_perubahan) {
                            'revisi_resmi' => 'bg-primary',
                            'diskon' => 'bg-warning text-dark',
                            'koreksi' => 'bg-secondary',
                            default => 'bg-light text-dark',
                        };
                        $today = now()->startOfDay();
                        $statusDiskon = null;
                        if ($log->tipe_perubahan === 'diskon') {
                            if ($log->berlaku_mulai && $log->berlaku_mulai->gt($today)) {
                                $statusDiskon = ['Akan datang', 'bg-info text-dark', 'bi-clock'];
                            } elseif ($log->berlaku_sampai && $log->berlaku_sampai->lt($today)) {
                                $statusDiskon = ['Berakhir — tarif kembali normal', 'bg-secondary', 'bi-arrow-counterclockwise'];
                            } else {
                                $statusDiskon = ['Aktif', 'bg-success', 'bi-check2-circle'];
                            }
                        }
                    @endphp
                    <tr>
                        <td>
                            <div class="fw-bold">{{ $log->berlaku_mulai?->format('d/m/Y') }}</div>
                            @if($log->berlaku_sampai)
                                <div class="small text-muted">s.d. {{ $log->berlaku_sampai->format('d/m/Y') }}</div>
                            @endif
                            @if($statusDiskon)
                                <span class="badge {{ $statusDiskon[1] }} mt-1"><i class="bi {{ $statusDiskon[2] }} me-1"></i>{{ $statusDiskon[0] }}</span>
                            @endif
                        </td>
                        <td>
                            <div class="fw-bold small">{{ $log->layananJasa?->nama_layanan ?? '-' }}</div>
                            <div class="small text-muted">{{ $log->layananJasa?->nama_lengkap }}</div>
                        </td>
                        <td class="text-end">{{ $rupiah($log->tarif_lama) }}</td>
                        <td class="text-end fw-bold">{{ $rupiah($log->tarif_baru) }}</td>
                        <td class="text-end {{ $color }} fw-bold">
                            {{ ($selisih >= 0 ? '+' : '') . $rupiah($selisih) }}
                            @if($pct !== null)
                                <div class="small">({{ ($selisih >= 0 ? '+' : '') . number_format($pct, 2, ',', '.') }}%)</div>
                            @endif
                        </td>
                        <td><span class="badge {{ $tipeBadge }}">{{ $log->tipe_label }}</span></td>
                        <td>{{ $log->nomor_referensi ?? '-' }}</td>
                        <td><div class="small" style="max-width:240px;">{{ $log->alasan }}</div></td>
                        <td>
                            <div class="small">{{ $log->creator?->name ?? '-' }}</div>
                            <div class="small text-muted">{{ $log->created_at?->format('d/m/Y H:i') }}</div>
                        </td>
                        <td class="text-center">
                            @if($log->file_pendukung)
                                <a href="{{ route('super-admin-jasa.laporan.log-tarif-pjp2u.file', $log->id) }}" class="btn btn-sm btn-outline-secondary" title="Unduh" target="_blank">
                                    <i class="bi bi-paperclip"></i>
                                </a>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="text-center text-muted py-4">
                            Tidak ada perubahan tarif PJP2U yang cocok dengan filter.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($logs->hasPages())
        <div class="card-footer bg-white border-0">{{ $logs->links() }}</div>
    @endif
</div>
</div>
@endsection
