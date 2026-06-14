@extends('layouts.app')
@section('title', 'Monitoring Pelaporan Mitra')

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
        .blue-animated-header .blue-header-wave { content: ""; position: absolute; pointer-events: none; z-index: -1; }
        .blue-animated-header::before {
            width: 360px; height: 360px; right: 8%; top: -170px; border-radius: 999px;
            background: radial-gradient(circle, rgba(125, 211, 252, .32), rgba(59, 130, 246, .22) 42%, transparent 68%);
            animation: blueHeaderGlow 4.5s ease-in-out infinite;
        }
        .blue-animated-header::after {
            inset: 0; width: 48%;
            background: linear-gradient(90deg, transparent, rgba(125,211,252,.16), rgba(255,255,255,.24), rgba(96,165,250,.14), transparent);
            animation: blueHeaderSweep 3.8s ease-in-out infinite;
        }
        .blue-animated-header .blue-header-wave {
            left: -90px; bottom: -120px; width: 420px; height: 230px; border-radius: 999px;
            background: radial-gradient(circle, rgba(125, 211, 252, .22), transparent 65%);
            animation: blueHeaderGlow 5.2s ease-in-out infinite reverse;
        }
    </style>
@endpush

@section('content')
@php
    $bulanNama = fn ($m) => \Carbon\Carbon::create()->month((int) $m)->translatedFormat('F');
    $statusMeta = fn ($s) => match ($s) {
        'belum' => ['Belum Lapor', 'bg-danger'],
        'draft' => ['Draft', 'bg-secondary'],
        'diajukan' => ['Diajukan', 'bg-warning text-dark'],
        'diverifikasi' => ['Terverifikasi', 'bg-success'],
        'ditagihkan' => ['Ditagihkan', 'bg-primary'],
        'ditolak' => ['Ditolak', 'bg-dark'],
        default => [ucfirst((string) $s), 'bg-secondary'],
    };
    // Hanya pelaku verifikasi laporan yang boleh beraksi (lihat detail & ingatkan).
    // Verifikator tagihan (Kasi/Kasubag/KPA) memantau read-only.
    $canAct = auth()->user()?->hasAnyRole(['Super Admin', 'Super Admin Jasa', 'Koordinator Jasa', 'Admin Jasa']) === true;
@endphp

<div class="tw-scope">

{{-- Hero --}}
<div class="blue-animated-header mb-4 overflow-hidden rounded-3xl border border-blue-900/20 bg-gradient-to-r from-[#12355c] via-[#174f86] to-[#1d65a6] px-5 py-5 shadow-[0_18px_50px_rgba(18,53,92,.22)] sm:px-6">
    <span class="blue-header-wave"></span>
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div class="flex items-start gap-3">
            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-blue-600 text-white shadow-lg shadow-blue-600/20">
                <i class="bi bi-clipboard-check text-xl"></i>
            </div>
            <div>
                <h4 class="mb-1 text-xl font-black text-white lg:text-2xl">Monitoring Pelaporan Mitra</h4>
                <p class="mb-0 text-sm font-semibold text-blue-100/80">Pantau mitra yang sudah & belum melaporkan konsesi / PAX PJP2U &middot; Periode {{ $bulanNama($filters['bulan']) }} {{ $filters['tahun'] }}.</p>
            </div>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('jasa.monitoring-pelaporan.export', array_merge(request()->query(), ['format' => 'pdf'])) }}" class="inline-flex items-center justify-center rounded-xl bg-white px-3 py-2 text-sm font-bold text-rose-600 shadow-lg shadow-slate-950/15 transition hover:-translate-y-0.5 hover:bg-rose-50">
                <i class="bi bi-file-earmark-pdf me-1"></i>PDF
            </a>
            <a href="{{ route('jasa.monitoring-pelaporan.export', array_merge(request()->query(), ['format' => 'excel'])) }}" class="inline-flex items-center justify-center rounded-xl bg-white px-3 py-2 text-sm font-bold text-emerald-600 shadow-lg shadow-slate-950/15 transition hover:-translate-y-0.5 hover:bg-emerald-50">
                <i class="bi bi-file-earmark-spreadsheet me-1"></i>Excel
            </a>
            @if($canAct && $summary['belum'] > 0)
                <form method="POST" action="{{ route('jasa.monitoring-pelaporan.remind-all') }}"
                      onsubmit="return confirm('Kirim pengingat WhatsApp & email ke SEMUA mitra yang belum lapor pada periode/filter ini ({{ $summary['belum'] }} baris)?')">
                    @csrf
                    <input type="hidden" name="bulan" value="{{ $filters['bulan'] }}">
                    <input type="hidden" name="tahun" value="{{ $filters['tahun'] }}">
                    <input type="hidden" name="jenis" value="{{ $filters['jenis'] }}">
                    <input type="hidden" name="mitra_jasa_id" value="{{ $filters['mitra_jasa_id'] }}">
                    <input type="hidden" name="layanan_jasa_id" value="{{ $filters['layanan_jasa_id'] }}">
                    <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-white px-4 py-2 text-sm font-bold text-amber-600 shadow-lg shadow-slate-950/15 transition hover:-translate-y-0.5 hover:bg-amber-50">
                        <i class="bi bi-bell me-2"></i>Ingatkan Semua yang Belum Lapor
                    </button>
                </form>
            @endif
        </div>
    </div>
</div>

{{-- Ringkasan --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="rounded-3xl border border-blue-100 bg-white p-4 shadow-[0_16px_42px_rgba(37,99,235,.08)] h-100">
            <div class="small fw-bold text-uppercase" style="color:#2563eb;letter-spacing:.02em;">Wajib Lapor</div>
            <div class="fs-2 fw-black text-slate-900">{{ number_format($summary['total'], 0, ',', '.') }}</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="rounded-3xl border border-rose-100 bg-white p-4 shadow-[0_16px_42px_rgba(244,63,94,.08)] h-100" style="border-left:4px solid #f43f5e;">
            <div class="small fw-bold text-uppercase text-danger" style="letter-spacing:.02em;">Belum Lapor</div>
            <div class="fs-2 fw-black text-danger">{{ number_format($summary['belum'], 0, ',', '.') }}</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="rounded-3xl border border-emerald-100 bg-white p-4 shadow-[0_16px_42px_rgba(16,185,129,.08)] h-100" style="border-left:4px solid #10b981;">
            <div class="small fw-bold text-uppercase text-success" style="letter-spacing:.02em;">Sudah Lapor</div>
            <div class="fs-2 fw-black text-success">{{ number_format($summary['sudah'], 0, ',', '.') }}</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="rounded-3xl border border-amber-100 bg-white p-4 shadow-[0_16px_42px_rgba(245,158,11,.08)] h-100" style="border-left:4px solid #f59e0b;">
            <div class="small fw-bold text-uppercase text-warning" style="letter-spacing:.02em;">Menunggu Verifikasi</div>
            <div class="fs-2 fw-black text-warning">{{ number_format($summary['diajukan'], 0, ',', '.') }}</div>
        </div>
    </div>
</div>

{{-- Filter --}}
<div class="overflow-hidden rounded-3xl border border-blue-100 bg-white shadow-[0_16px_42px_rgba(37,99,235,.08)] mb-4">
    <div class="border-bottom border-primary-subtle bg-gradient-to-r from-blue-50 via-sky-50 to-white px-3 py-2">
        <div class="d-flex align-items-center gap-2">
            <span class="d-inline-flex align-items-center justify-content-center rounded-3 bg-primary text-white shadow-sm" style="width:34px;height:34px;"><i class="bi bi-funnel"></i></span>
            <div>
                <div class="fw-bold" style="color:#1e3a8a;">Filter Pelaporan</div>
                <div class="small fw-semibold text-muted">Saring berdasarkan periode, jenis, status, mitra, dan layanan.</div>
            </div>
        </div>
    </div>
    <div class="p-4">
        <form method="GET" class="row g-3">
            <div class="col-6 col-md-2">
                <label class="form-label small fw-bold">Bulan</label>
                <select name="bulan" class="form-select">
                    @for($m = 1; $m <= 12; $m++)
                        <option value="{{ $m }}" {{ (int) $filters['bulan'] === $m ? 'selected' : '' }}>{{ $bulanNama($m) }}</option>
                    @endfor
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small fw-bold">Tahun</label>
                <input type="number" name="tahun" value="{{ $filters['tahun'] }}" class="form-control">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small fw-bold">Jenis</label>
                <select name="jenis" class="form-select">
                    <option value="">Semua</option>
                    <option value="konsesi" {{ $filters['jenis'] === 'konsesi' ? 'selected' : '' }}>Konsesi</option>
                    <option value="pjp2u" {{ $filters['jenis'] === 'pjp2u' ? 'selected' : '' }}>PAX PJP2U</option>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small fw-bold">Status</label>
                <select name="status" class="form-select">
                    <option value="">Semua</option>
                    <option value="belum" {{ $filters['status'] === 'belum' ? 'selected' : '' }}>Belum Lapor</option>
                    <option value="sudah" {{ $filters['status'] === 'sudah' ? 'selected' : '' }}>Sudah Lapor</option>
                    <option value="diajukan" {{ $filters['status'] === 'diajukan' ? 'selected' : '' }}>Diajukan</option>
                    <option value="diverifikasi" {{ $filters['status'] === 'diverifikasi' ? 'selected' : '' }}>Terverifikasi</option>
                    <option value="ditagihkan" {{ $filters['status'] === 'ditagihkan' ? 'selected' : '' }}>Ditagihkan</option>
                    <option value="ditolak" {{ $filters['status'] === 'ditolak' ? 'selected' : '' }}>Ditolak</option>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small fw-bold">Mitra</label>
                <select name="mitra_jasa_id" class="form-select">
                    <option value="">Semua</option>
                    @foreach($filterOptions['mitras'] as $mitra)
                        <option value="{{ $mitra->id }}" {{ (string) $filters['mitra_jasa_id'] === (string) $mitra->id ? 'selected' : '' }}>{{ $mitra->nama_mitra }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small fw-bold">Layanan</label>
                <select name="layanan_jasa_id" class="form-select">
                    <option value="">Semua</option>
                    @foreach($filterOptions['layanans'] as $layanan)
                        <option value="{{ $layanan->id }}" {{ (string) $filters['layanan_jasa_id'] === (string) $layanan->id ? 'selected' : '' }}>{{ $layanan->nama_layanan }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 d-flex gap-2">
                <button class="btn btn-primary fw-bold jasa-icon-btn" title="Terapkan filter" aria-label="Terapkan filter"><i class="bi bi-check2-circle"></i></button>
                <a href="{{ route('jasa.monitoring-pelaporan.index') }}" class="btn btn-light border fw-bold jasa-icon-btn" title="Reset filter" aria-label="Reset filter"><i class="bi bi-arrow-counterclockwise"></i></a>
            </div>
        </form>
    </div>
</div>

@if((int) $filters['bulan'] === now()->month && (int) $filters['tahun'] === now()->year)
    <div class="alert alert-info border-0 small d-flex align-items-start gap-2">
        <i class="bi bi-info-circle mt-1"></i>
        <span>Anda melihat <strong>bulan berjalan</strong>. Laporan <strong>konsesi</strong> baru bisa diverifikasi setelah bulan ini berakhir, jadi wajar bila masih "Belum Lapor". Pilih <strong>bulan lalu</strong> untuk memantau tunggakan pelaporan yang benar-benar telat.</span>
    </div>
@endif

{{-- Tabel --}}
<div class="overflow-hidden rounded-3xl border border-blue-100 bg-white shadow-[0_16px_42px_rgba(37,99,235,.08)]">
    <div class="border-bottom border-primary-subtle bg-gradient-to-r from-blue-50 via-sky-50 to-white px-3 py-2">
        <div class="d-flex align-items-center gap-2">
            <span class="d-inline-flex align-items-center justify-content-center rounded-3 bg-primary text-white shadow-sm" style="width:34px;height:34px;"><i class="bi bi-table"></i></span>
            <div>
                <div class="fw-bold" style="color:#1e3a8a;">Daftar Pelaporan</div>
                <div class="small fw-semibold text-muted">Status pelaporan tiap konsesi/PJP2U aktif pada periode terpilih.</div>
            </div>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead style="background:#eff6ff;color:#1d4ed8;">
                <tr>
                    <th>Mitra</th>
                    <th>Layanan</th>
                    <th>Jenis</th>
                    <th>Status Pelaporan</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $row)
                    @php([$label, $class] = $statusMeta($row['status']))
                    <tr>
                        <td class="fw-semibold">{{ $row['mitra_nama'] }}</td>
                        <td>{{ $row['layanan_nama'] }}</td>
                        <td>
                            <span class="badge {{ $row['jenis'] === 'konsesi' ? 'bg-info text-dark' : 'bg-primary-subtle text-primary' }}">
                                {{ $row['jenis'] === 'konsesi' ? 'Konsesi' : 'PAX PJP2U' }}
                            </span>
                        </td>
                        <td><span class="badge {{ $class }}">{{ $label }}</span></td>
                        <td class="text-end">
                            @if(! $canAct)
                                <span class="text-muted small">&mdash;</span>
                            @elseif($row['status'] !== 'belum' && $row['report_id'])
                                @if($row['jenis'] === 'konsesi')
                                    <a href="{{ route('jasa.mitra.penjualan.show', [$row['mitra_id'], $row['report_id']]) }}" class="btn btn-sm btn-light border text-primary fw-semibold jasa-icon-btn" title="Lihat laporan" aria-label="Lihat laporan"><i class="bi bi-eye"></i></a>
                                @else
                                    <a href="{{ route('jasa.mitra.pjp2u.rekap.show', [$row['mitra_id'], $row['layanan_id'], $filters['tahun'], $filters['bulan']]) }}" class="btn btn-sm btn-light border text-primary fw-semibold jasa-icon-btn" title="Lihat rekap" aria-label="Lihat rekap"><i class="bi bi-eye"></i></a>
                                @endif
                            @else
                                <form method="POST" action="{{ route('jasa.monitoring-pelaporan.remind') }}" class="d-inline"
                                      onsubmit="return confirm('Kirim pengingat ke {{ $row['mitra_nama'] }} untuk melaporkan {{ $row['jenis'] === 'konsesi' ? 'omzet konsesi' : 'PAX PJP2U' }}?')">
                                    @csrf
                                    <input type="hidden" name="mitra_jasa_id" value="{{ $row['mitra_id'] }}">
                                    <input type="hidden" name="layanan_jasa_id" value="{{ $row['layanan_id'] }}">
                                    <input type="hidden" name="jenis" value="{{ $row['jenis'] }}">
                                    <input type="hidden" name="bulan" value="{{ $filters['bulan'] }}">
                                    <input type="hidden" name="tahun" value="{{ $filters['tahun'] }}">
                                    <button type="submit" class="btn btn-sm btn-warning fw-semibold" title="Kirim pengingat WA & email">
                                        <i class="bi bi-bell me-1"></i>Ingatkan
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">Tidak ada data sesuai filter. Pastikan ada mitra dengan konsesi/PJP2U aktif pada periode ini.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($items->hasPages())
        <div class="card-footer bg-white">{{ $items->links() }}</div>
    @endif
</div>
</div>
@endsection
