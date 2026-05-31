@extends('layouts.app')
@section('title', 'Verifikasi Laporan PAX PJP2U')

@push('css')
    <script>
        window.tailwind = window.tailwind || {};
        window.tailwind.config = { corePlugins: { preflight: false } };
    </script>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @keyframes blueHeaderGlow {
            0%, 100% { opacity: .55; transform: translate3d(-28px, 0, 0) scale(1); }
            50% { opacity: .95; transform: translate3d(72px, -18px, 0) scale(1.12); }
        }
        @keyframes blueHeaderSweep {
            0% { transform: translateX(-120%) skewX(-18deg); opacity: 0; }
            18% { opacity: .35; }
            45%, 100% { transform: translateX(220%) skewX(-18deg); opacity: 0; }
        }
        .blue-animated-header { position: relative; isolation: isolate; }
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
    $angka = fn ($value) => number_format((float) $value, 0, ',', '.');
    $tanggal = fn ($value) => $value ? \Carbon\Carbon::parse($value)->format('d/m/Y') : '-';
@endphp

<div class="tw-scope">
<div class="blue-animated-header mb-4 overflow-hidden rounded-3xl border border-blue-900/20 bg-gradient-to-r from-[#12355c] via-[#174f86] to-[#1d65a6] px-5 py-5 shadow-[0_18px_50px_rgba(18,53,92,.22)] sm:px-6">
    <span class="blue-header-wave"></span>
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div class="flex items-start gap-3">
            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-blue-600 text-white shadow-lg shadow-blue-600/20">
                <i class="bi bi-people text-xl"></i>
            </div>
            <div>
                <h4 class="mb-1 text-xl font-black text-white lg:text-2xl">Verifikasi Laporan PAX PJP2U</h4>
                <p class="mb-0 text-sm font-semibold text-blue-100/80">Rekap bulanan laporan penumpang PJP2U dari mitra untuk dasar penagihan.</p>
            </div>
        </div>
        <a href="{{ route('jasa.mitra.index') }}" class="inline-flex items-center justify-center rounded-xl bg-white px-4 py-2 text-sm font-bold text-blue-700 shadow-lg shadow-slate-950/15 transition hover:-translate-y-0.5 hover:bg-blue-50">
            <i class="bi bi-plus-lg me-2"></i>Pilih Mitra
        </a>
    </div>
</div>

<div class="overflow-hidden rounded-3xl border border-blue-100 bg-white shadow-[0_16px_42px_rgba(37,99,235,.08)] mb-4">
    <div class="border-bottom border-primary-subtle bg-gradient-to-r from-blue-50 via-sky-50 to-white px-3 py-2">
        <div class="d-flex align-items-center gap-2">
            <span class="d-inline-flex align-items-center justify-content-center rounded-3 bg-primary text-white shadow-sm" style="width:34px;height:34px;"><i class="bi bi-funnel"></i></span>
            <div>
                <div class="fw-bold" style="color:#1e3a8a;">Filter Laporan</div>
                <div class="small fw-semibold text-muted">Saring laporan PAX berdasarkan mitra, status, periode, dan pencarian.</div>
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
                <a href="{{ route('jasa.mitra.pjp2u.index') }}" class="btn btn-light border fw-bold jasa-icon-btn" title="Reset filter" aria-label="Reset filter"><i class="bi bi-arrow-counterclockwise"></i></a>
            </div>
        </form>
    </div>
</div>

<div class="overflow-hidden rounded-3xl border border-blue-100 bg-white shadow-[0_16px_42px_rgba(37,99,235,.08)]">
    <div class="border-bottom border-primary-subtle bg-gradient-to-r from-blue-50 via-sky-50 to-white px-3 py-2">
        <div class="d-flex align-items-center gap-2">
            <span class="d-inline-flex align-items-center justify-content-center rounded-3 bg-primary text-white shadow-sm" style="width:34px;height:34px;"><i class="bi bi-table"></i></span>
            <div>
                <div class="fw-bold" style="color:#1e3a8a;">Rekap Laporan PAX PJP2U</div>
                <div class="small fw-semibold text-muted">Data utama diringkas per mitra, layanan, dan bulan. Detail harian tersedia di detail mitra.</div>
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
                    <th>Periode</th>
                    <th>Laporan Harian</th>
                    <th>Total Pax</th>
                    <th>Total Tagihan</th>
                    <th>Status Rekap</th>
                    <th>Tagihan</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rekapPjp2u as $row)
                    <tr>
                        <td>{{ $rekapPjp2u->firstItem() + $loop->index }}</td>
                        <td>
                            <a href="{{ route('jasa.mitra.pjp2u.rekap.show', [$row->mitra_jasa_id, $row->layanan_jasa_id, $row->tahun, $row->bulan]) }}" class="fw-semibold text-decoration-none">
                                {{ $row->mitra->nama_mitra ?? '-' }}
                            </a>
                        </td>
                        <td>
                            <div class="fw-semibold">{{ $row->layanan->nama_layanan ?? '-' }}</div>
                            <div class="small text-muted">{{ $row->layanan->kode_layanan ?? '' }}</div>
                        </td>
                        <td>
                            <div class="fw-semibold">{{ \Carbon\Carbon::create($row->tahun, $row->bulan, 1)->translatedFormat('F Y') }}</div>
                            <div class="small text-muted">{{ $tanggal($row->periode_mulai) }} s.d. {{ $tanggal($row->periode_selesai) }}</div>
                        </td>
                        <td>
                            <div class="fw-bold">{{ $row->jumlah_laporan }} laporan</div>
                            <div class="small text-muted">{{ $row->file_count }} file terlampir</div>
                        </td>
                        <td class="fw-semibold">{{ $angka($row->total_pax) }} pax</td>
                        <td>
                            <div class="fw-bold text-success">{{ $rupiah($row->nilai_tagihan) }}</div>
                            <div class="small text-muted">{{ $angka($row->total_pax) }} pax tercatat</div>
                        </td>
                        <td>
                            <span class="badge {{ $row->status_class }}">{{ $row->status_label }}</span>
                            <div class="small text-muted mt-1">
                                @foreach($row->status_counts as $status => $count)
                                    <span class="me-2">{{ ucfirst($status) }}: {{ $count }}</span>
                                @endforeach
                            </div>
                        </td>
                        <td>
                            @if($row->tagihan_count > 0)
                                @if($row->tagihan_count === 1 && $row->first_tagihan)
                                    <a href="{{ route('tagihan-jasa.show', $row->first_tagihan) }}" class="btn btn-sm btn-light border">{{ $row->first_tagihan->nomor_tagihan }}</a>
                                @else
                                    <span class="badge bg-primary">{{ $row->tagihan_count }} tagihan</span>
                                @endif
                            @else
                                <span class="text-muted">Belum</span>
                            @endif
                        </td>
                        <td>
                            <div class="d-flex gap-1 flex-wrap align-items-center">
                                <a href="{{ route('jasa.mitra.pjp2u.rekap.show', [$row->mitra_jasa_id, $row->layanan_jasa_id, $row->tahun, $row->bulan]) }}" class="btn btn-sm btn-light border text-primary fw-semibold jasa-icon-btn" title="Detail rekap harian" aria-label="Detail rekap harian"><i class="bi bi-list-ul"></i></a>
                                @if($row->latest_report)
                                    <a href="{{ route('jasa.mitra.penjualan.show', [$row->mitra_jasa_id, $row->latest_report]) }}" class="btn btn-sm btn-light border text-primary fw-semibold jasa-icon-btn" title="Laporan terbaru" aria-label="Laporan terbaru"><i class="bi bi-eye"></i></a>
                                @endif
                                @if($row->needs_verification_count > 0)
                                    <span class="badge bg-warning text-dark" title="Buka detail mitra untuk verifikasi per laporan">{{ $row->needs_verification_count }} perlu verifikasi</span>
                                @endif
                                @if($row->waiting_verification_count > 0)
                                    <span class="badge bg-info text-dark" title="Menunggu syarat verifikasi laporan">{{ $row->waiting_verification_count }} tunggu</span>
                                @endif
                                @if($row->can_create_tagihan_count > 0 && $row->createable_report)
                                    <a href="{{ route('jasa.mitra.pjp2u.rekap.show', [$row->mitra_jasa_id, $row->layanan_jasa_id, $row->tahun, $row->bulan]) }}" class="btn btn-sm btn-primary fw-semibold" title="Buka daftar tagihan harian">
                                        <i class="bi bi-receipt me-1"></i>Tagihan {{ $row->can_create_tagihan_count }}
                                    </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="10" class="text-center text-muted py-4">Belum ada rekap laporan PAX PJP2U mitra.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($rekapPjp2u->hasPages())
        <div class="card-footer bg-white">{{ $rekapPjp2u->links() }}</div>
    @endif
</div>
</div>
@endsection
