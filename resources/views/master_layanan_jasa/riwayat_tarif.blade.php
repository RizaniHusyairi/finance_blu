@extends('layouts.app')
@section('title', 'Riwayat Tarif PJP2U - ' . $layanan->nama_layanan)

@section('content')
@include('super_admin_jasa.laporan._styles')
@php
    $rupiah = fn ($v) => 'Rp ' . number_format((float) $v, 0, ',', '.');
    $angka = fn ($v) => number_format((float) $v, 0, ',', '.');

    $summary = [
        'total' => $logs->total(),
        'revisi_resmi' => $logs->getCollection()->where('tipe_perubahan', 'revisi_resmi')->count(),
        'diskon' => $logs->getCollection()->where('tipe_perubahan', 'diskon')->count(),
        'koreksi' => $logs->getCollection()->where('tipe_perubahan', 'koreksi')->count(),
    ];
    $today = now()->startOfDay();
    $diskonAktif = $logs->getCollection()->first(function ($l) use ($today) {
        return $l->tipe_perubahan === 'diskon'
            && $l->berlaku_mulai && $l->berlaku_mulai->lte($today)
            && (!$l->berlaku_sampai || $l->berlaku_sampai->gte($today));
    });

    // Tarif efektif hari ini (memperhitungkan periode diskon; otomatis kembali
    // ke tarif normal setelah periode diskon berakhir).
    $tarifNormalKini = (float) $layanan->tarif_dasar;
    $tarifEfektifKini = (float) $layanan->tarifEfektif();
    $adaDiskonBerjalan = abs($tarifEfektifKini - $tarifNormalKini) > 0.001;
@endphp

<div class="sa-report-page">
    <div class="sa-report-hero d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <div class="small text-uppercase fw-bold" style="letter-spacing:.08em;color:#fbbf24;">{{ $layanan->nama_lengkap }}</div>
            <h4 class="fw-bold mb-1"><i class="bi bi-clock-history me-2"></i>Riwayat Perubahan Tarif PJP2U</h4>
            <p class="mb-0 small">
                Tarif berlaku saat ini: <strong>{{ $rupiah($tarifEfektifKini) }}</strong> / {{ $layanan->satuan ?? '-' }}
                @if($adaDiskonBerjalan)
                    <span class="badge bg-warning text-dark ms-1">diskon aktif &middot; normal {{ $rupiah($tarifNormalKini) }}</span>
                @else
                    <span class="text-white-50">(tarif normal)</span>
                @endif
            </p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('master-layanan-jasa.edit', $layanan->id) }}" class="btn btn-warning fw-bold">
                <i class="bi bi-pencil-square me-1"></i>Ubah Tarif
            </a>
            <a href="{{ route('master-layanan-jasa.index') }}" class="btn btn-light border fw-bold">
                <i class="bi bi-arrow-left me-1"></i>Kembali
            </a>
        </div>
    </div>

    @if($diskonAktif)
        <div class="alert border-0 mb-4 d-flex align-items-start gap-3" style="background:#fff8e1;border-left:4px solid #f59e0b !important;border-radius:14px;">
            <i class="bi bi-megaphone-fill fs-3 text-warning"></i>
            <div>
                <div class="fw-bold text-warning-emphasis">Diskon / Penyesuaian Periode Aktif</div>
                <div class="small text-secondary mt-1">
                    Berlaku <strong>{{ $diskonAktif->berlaku_mulai?->format('d/m/Y') }}</strong>
                    @if($diskonAktif->berlaku_sampai) s.d. <strong>{{ $diskonAktif->berlaku_sampai->format('d/m/Y') }}</strong> @endif
                    &middot; Tarif: <strong>{{ $rupiah($diskonAktif->tarif_baru) }}</strong>
                    @if($diskonAktif->nomor_referensi) &middot; Ref: <strong>{{ $diskonAktif->nomor_referensi }}</strong> @endif
                </div>
            </div>
        </div>
    @endif

    <div class="row g-3 mb-4">
        <div class="col-md-3 col-sm-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="small text-uppercase text-muted fw-bold"><i class="bi bi-list-check me-1"></i>Total Perubahan</div>
                    <div class="fs-3 fw-bold text-dark mt-1">{{ $angka($summary['total']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="small text-uppercase text-muted fw-bold"><i class="bi bi-shield-check me-1"></i>Revisi Resmi</div>
                    <div class="fs-3 fw-bold text-primary mt-1">{{ $angka($summary['revisi_resmi']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="small text-uppercase text-muted fw-bold"><i class="bi bi-percent me-1"></i>Diskon / Penyesuaian</div>
                    <div class="fs-3 fw-bold text-warning mt-1">{{ $angka($summary['diskon']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="small text-uppercase text-muted fw-bold"><i class="bi bi-eraser me-1"></i>Koreksi</div>
                    <div class="fs-3 fw-bold text-secondary mt-1">{{ $angka($summary['koreksi']) }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-0">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h6 class="fw-bold mb-0"><i class="bi bi-list-columns-reverse me-2 text-primary"></i>Daftar Perubahan</h6>
                <span class="badge bg-light text-secondary border">{{ $logs->total() }} catatan</span>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="small text-uppercase">Berlaku</th>
                        <th class="small text-uppercase text-end">Tarif Lama</th>
                        <th class="small text-uppercase text-end">Tarif Baru</th>
                        <th class="small text-uppercase text-end">Selisih</th>
                        <th class="small text-uppercase">Tipe</th>
                        <th class="small text-uppercase">No. Referensi</th>
                        <th class="small text-uppercase">Alasan</th>
                        <th class="small text-uppercase">Diubah Oleh</th>
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
                            $isAktif = $log->tipe_perubahan === 'diskon'
                                && $log->berlaku_mulai && $log->berlaku_mulai->lte($today)
                                && (!$log->berlaku_sampai || $log->berlaku_sampai->gte($today));

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
                        <tr @class(['table-warning' => $isAktif])>
                            <td>
                                <div class="fw-bold">{{ $log->berlaku_mulai?->format('d/m/Y') }}</div>
                                @if($log->berlaku_sampai)
                                    <div class="small text-muted">s.d. {{ $log->berlaku_sampai->format('d/m/Y') }}</div>
                                @endif
                                @if($statusDiskon)
                                    <span class="badge {{ $statusDiskon[1] }} mt-1"><i class="bi {{ $statusDiskon[2] }} me-1"></i>{{ $statusDiskon[0] }}</span>
                                @endif
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
                            <td>
                                <div class="small" style="max-width:280px;">{{ $log->alasan }}</div>
                            </td>
                            <td>
                                <div class="small">{{ $log->creator?->name ?? '-' }}</div>
                                <div class="small text-muted">{{ $log->created_at?->format('d/m/Y H:i') }}</div>
                            </td>
                            <td class="text-center">
                                @if($log->file_pendukung)
                                    <a href="{{ route('super-admin-jasa.laporan.log-tarif-pjp2u.file', $log->id) }}" class="btn btn-sm btn-outline-secondary" target="_blank" title="Unduh">
                                        <i class="bi bi-paperclip"></i>
                                    </a>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-5">
                                <i class="bi bi-inbox fs-2 d-block mb-2 opacity-50"></i>
                                Belum ada perubahan tarif yang tercatat untuk layanan ini.
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
