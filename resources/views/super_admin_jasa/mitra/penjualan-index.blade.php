@extends('layouts.app')
@section('title', 'Laporan Penjualan Mitra')
@php
    $canCreateTagihanJasa = auth()->user()?->hasRole('Super Admin') === true
        || (auth()->user()?->hasAnyRole(['Admin Jasa', 'Admin Konsesi']) === true && ! auth()->user()?->hasRole('Super Admin Jasa'));
@endphp

@push('css')
    <script>
        window.tailwind = window.tailwind || {};
        window.tailwind.config = { corePlugins: { preflight: false } };
    </script>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @keyframes blueHeaderGlow {
            0%, 100% {
                opacity: .55;
                transform: translate3d(-28px, 0, 0) scale(1);
            }
            50% {
                opacity: .95;
                transform: translate3d(72px, -18px, 0) scale(1.12);
            }
        }
        @keyframes blueHeaderSweep {
            0% {
                transform: translateX(-120%) skewX(-18deg);
                opacity: 0;
            }
            18% {
                opacity: .35;
            }
            45%, 100% {
                transform: translateX(220%) skewX(-18deg);
                opacity: 0;
            }
        }
        .blue-animated-header {
            position: relative;
            isolation: isolate;
        }
        .blue-animated-header::before,
        .blue-animated-header::after,
        .blue-animated-header .blue-header-wave {
            content: "";
            position: absolute;
            pointer-events: none;
            z-index: -1;
        }
        .blue-animated-header::before {
            width: 360px;
            height: 360px;
            right: 8%;
            top: -170px;
            border-radius: 999px;
            background: radial-gradient(circle, rgba(125, 211, 252, .32), rgba(59, 130, 246, .22) 42%, transparent 68%);
            animation: blueHeaderGlow 4.5s ease-in-out infinite;
        }
        .blue-animated-header::after {
            inset: 0;
            width: 48%;
            background: linear-gradient(90deg, transparent, rgba(125,211,252,.16), rgba(255,255,255,.24), rgba(96,165,250,.14), transparent);
            animation: blueHeaderSweep 3.8s ease-in-out infinite;
        }
        .blue-animated-header .blue-header-wave {
            left: -90px;
            bottom: -120px;
            width: 420px;
            height: 230px;
            border-radius: 999px;
            background: radial-gradient(circle, rgba(125, 211, 252, .22), transparent 65%);
            animation: blueHeaderGlow 5.2s ease-in-out infinite reverse;
        }
    </style>
@endpush

@section('content')
@php
    $rupiah = fn ($value) => 'Rp ' . number_format((float) $value, 0, ',', '.');
    $tanggal = fn ($value) => $value ? \Carbon\Carbon::parse($value)->format('d/m/Y') : '-';
    $statusClass = fn ($status) => match ($status) {
        'diajukan' => 'bg-warning text-dark',
        'diverifikasi' => 'bg-success',
        'ditolak' => 'bg-danger',
        'ditagihkan' => 'bg-primary',
        default => 'bg-secondary',
    };
@endphp

<div class="tw-scope">
<div class="blue-animated-header mb-4 overflow-hidden rounded-3xl border border-blue-900/20 bg-gradient-to-r from-[#12355c] via-[#174f86] to-[#1d65a6] px-5 py-5 shadow-[0_18px_50px_rgba(18,53,92,.22)] sm:px-6">
    <span class="blue-header-wave"></span>
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div class="flex items-start gap-3">
            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-blue-600 text-white shadow-lg shadow-blue-600/20">
                <i class="bi bi-graph-up-arrow text-xl"></i>
            </div>
            <div>
                <h4 class="mb-1 text-xl font-black text-white lg:text-2xl">Laporan Penjualan Mitra</h4>
                <p class="mb-0 text-sm font-semibold text-blue-100/80">Daftar laporan omzet/penjualan mitra untuk dasar tagihan konsesi.</p>
            </div>
        </div>
        <a href="{{ route('jasa.mitra.index') }}" class="inline-flex items-center justify-center rounded-xl bg-white px-4 py-2 text-sm font-bold text-blue-700 shadow-lg shadow-slate-950/15 transition hover:-translate-y-0.5 hover:bg-blue-50">
            <i class="bi bi-plus-lg me-2"></i>Pilih Mitra untuk Tambah Laporan
        </a>
    </div>
</div>

<div class="overflow-hidden rounded-3xl border border-blue-100 bg-white shadow-[0_16px_42px_rgba(37,99,235,.08)] mb-4">
    <div class="border-bottom border-primary-subtle bg-gradient-to-r from-blue-50 via-sky-50 to-white px-3 py-2">
        <div class="d-flex align-items-center gap-2">
            <span class="d-inline-flex align-items-center justify-content-center rounded-3 bg-primary text-white shadow-sm" style="width:34px;height:34px;"><i class="bi bi-funnel"></i></span>
            <div>
                <div class="fw-bold" style="color:#1e3a8a;">Filter Laporan</div>
                <div class="small fw-semibold text-muted">Saring laporan omzet berdasarkan mitra, status, periode, dan pencarian.</div>
            </div>
        </div>
    </div>
    <div class="p-4">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label class="form-label small fw-bold">Mitra</label>
                <select name="mitra_jasa_id" class="form-select">
                    <option value="">Semua Mitra</option>
                    @foreach($mitras as $mitra)
                        <option value="{{ $mitra->id }}" {{ (string)($filters['mitra_jasa_id'] ?? '') === (string)$mitra->id ? 'selected' : '' }}>{{ $mitra->nama_mitra }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-bold">Status</label>
                <select name="status" class="form-select">
                    <option value="">Semua</option>
                    @foreach(['draft','diajukan','diverifikasi','ditolak','ditagihkan'] as $status)
                        <option value="{{ $status }}" {{ ($filters['status'] ?? '') === $status ? 'selected' : '' }}>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-bold">Bulan</label>
                <select name="bulan" class="form-select">
                    <option value="">Semua</option>
                    @for($m = 1; $m <= 12; $m++)
                        <option value="{{ $m }}" {{ (string)($filters['bulan'] ?? '') === (string)$m ? 'selected' : '' }}>{{ \Carbon\Carbon::create()->month($m)->translatedFormat('F') }}</option>
                    @endfor
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-bold">Tahun</label>
                <input type="number" name="tahun" value="{{ $filters['tahun'] ?? '' }}" class="form-control" placeholder="{{ now()->year }}">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-bold">Pencarian</label>
                <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control" placeholder="Mitra atau layanan">
            </div>
            <div class="col-12 d-flex gap-2">
                <button class="btn btn-primary fw-bold jasa-icon-btn" title="Filter" aria-label="Filter"><i class="bi bi-check2-circle"></i></button>
                <a href="{{ route('jasa.mitra.penjualan.index') }}" class="btn btn-light border fw-bold jasa-icon-btn" title="Reset filter" aria-label="Reset filter"><i class="bi bi-arrow-counterclockwise"></i></a>
            </div>
        </form>
    </div>
</div>

<div class="overflow-hidden rounded-3xl border border-blue-100 bg-white shadow-[0_16px_42px_rgba(37,99,235,.08)]">
    <div class="border-bottom border-primary-subtle bg-gradient-to-r from-blue-50 via-sky-50 to-white px-3 py-2">
        <div class="d-flex align-items-center gap-2">
            <span class="d-inline-flex align-items-center justify-content-center rounded-3 bg-primary text-white shadow-sm" style="width:34px;height:34px;"><i class="bi bi-table"></i></span>
            <div>
                <div class="fw-bold" style="color:#1e3a8a;">Daftar Laporan Penjualan</div>
                <div class="small fw-semibold text-muted">Laporan omzet/penjualan mitra untuk dasar tagihan konsesi.</div>
            </div>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead style="background:#eff6ff;color:#1d4ed8;">
                <tr>
                    <th>No</th>
                    <th>Mitra</th>
                    <th>Layanan</th>
                    <th>Bulan</th>
                    <th>Laporan</th>
                    <th>Total Omzet</th>
                    <th>Nilai Konsesi</th>
                    <th>Nilai Tagihan</th>
                    <th>Status</th>
                    <th>Tagihan</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($penjualans as $penjualan)
                    <tr>
                        <td>{{ $penjualans->firstItem() + $loop->index }}</td>
                        <td class="fw-semibold">{{ $penjualan->mitraJasa->nama_mitra ?? '-' }}</td>
                        <td>
                            <a href="{{ route('jasa.mitra.penjualan.show', [$penjualan->mitra_jasa_id, $penjualan]) }}" class="text-decoration-none">
                                {{ $penjualan->layananJasa->nama_layanan ?? '-' }}
                            </a>
                        </td>
                        <td>{{ str_pad($penjualan->bulan, 2, '0', STR_PAD_LEFT) }}/{{ $penjualan->tahun }}</td>
                        <td>
                            <span class="badge bg-info text-dark">{{ $penjualan->details_count ?? $penjualan->details()->count() }} laporan</span>
                        </td>
                        <td>{{ $rupiah($penjualan->total_omzet) }}</td>
                        <td>{{ $rupiah($penjualan->nilai_konsesi) }}</td>
                        <td class="fw-bold text-success">{{ $rupiah($penjualan->nilai_tagihan) }}</td>
                        <td><span class="badge {{ $statusClass($penjualan->status) }}">{{ ucfirst($penjualan->status) }}</span></td>
                        <td>
                            @if($penjualan->tagihanJasa)
                                <a href="{{ route('tagihan-jasa.show', $penjualan->tagihanJasa) }}" class="btn btn-sm btn-light border">{{ $penjualan->tagihanJasa->nomor_tagihan }}</a>
                            @else
                                <span class="text-muted">Belum</span>
                            @endif
                        </td>
                        <td>
                            <div class="d-flex gap-1 flex-wrap align-items-center">
                                <a href="{{ route('jasa.mitra.penjualan.show', [$penjualan->mitra_jasa_id, $penjualan]) }}" class="btn btn-sm btn-light border text-primary fw-semibold jasa-icon-btn" title="Detail" aria-label="Detail"><i class="bi bi-eye"></i></a>
                                @if($penjualan->status === 'diajukan' && $penjualan->can_be_verified)
                                    <form method="POST" action="{{ route('jasa.mitra.penjualan.verify', [$penjualan->mitra_jasa_id, $penjualan]) }}">
                                        @csrf
                                        <button class="btn btn-sm btn-success jasa-icon-btn" title="Verifikasi" aria-label="Verifikasi"><i class="bi bi-check2-circle"></i></button>
                                    </form>
                                    <form method="POST" action="{{ route('jasa.mitra.penjualan.reject', [$penjualan->mitra_jasa_id, $penjualan]) }}" onsubmit="return confirm('Tolak laporan ini? Pastikan catatan sudah diisi.');" class="d-flex gap-1">
                                        @csrf
                                        <input type="text" name="catatan_verifikator" class="form-control form-control-sm" placeholder="Catatan" required style="width: 130px;">
                                        <button class="btn btn-sm btn-danger jasa-icon-btn" title="Tolak" aria-label="Tolak"><i class="bi bi-x-circle"></i></button>
                                    </form>
                                @endif
                                @if($penjualan->status === 'diajukan' && ! $penjualan->can_be_verified)
                                    <span class="badge bg-warning text-dark" title="Verifikasi setelah bulan pelaporan berakhir">
                                        <i class="bi bi-hourglass-split"></i> Tunggu
                                    </span>
                                @endif
                                @if($penjualan->status === 'diverifikasi' && ! $penjualan->tagihan_jasa_id && $penjualan->layanan_jasa_id)
                                    @if($canCreateTagihanJasa && $penjualan->can_create_tagihan)
                                        <a href="{{ route('tagihan-jasa.create', ['penjualan_id' => $penjualan->id]) }}" class="btn btn-sm btn-primary fw-semibold" title="Buat tagihan" aria-label="Buat tagihan">
                                            <i class="bi bi-receipt me-1"></i>Tagihan
                                        </a>
                                    @elseif($canCreateTagihanJasa)
                                        <span class="badge bg-info text-dark" title="Tagihan tersedia mulai {{ $penjualan->tagihan_available_date }}">
                                            <i class="bi bi-calendar-check"></i> {{ $penjualan->tagihan_available_date }}
                                        </span>
                                    @endif
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="11" class="text-center text-muted py-4">Belum ada laporan penjualan mitra.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($penjualans->hasPages())
        <div class="card-footer bg-white">{{ $penjualans->links() }}</div>
    @endif
</div>
</div>
@endsection
