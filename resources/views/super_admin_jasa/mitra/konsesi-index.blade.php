@extends('layouts.app')
@section('title', 'Manajemen Hak Kelola Konsesi')

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
<div class="tw-scope">
<div class="blue-animated-header mb-4 overflow-hidden rounded-3xl border border-blue-900/20 bg-gradient-to-r from-[#12355c] via-[#174f86] to-[#1d65a6] px-5 py-5 shadow-[0_18px_50px_rgba(18,53,92,.22)] sm:px-6">
    <span class="blue-header-wave"></span>
    <div class="flex items-start gap-3">
        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-blue-600 text-white shadow-lg shadow-blue-600/20">
            <i class="bi bi-percent text-xl"></i>
        </div>
        <div>
            <h4 class="mb-1 text-xl font-black text-white lg:text-2xl">Manajemen Hak Kelola Konsesi</h4>
            <p class="mb-0 text-sm font-semibold text-blue-100/80">Daftar layanan yang dikelola oleh mitra dengan skema persentase konsesi.</p>
        </div>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="overflow-hidden rounded-3xl border border-blue-100 bg-white shadow-[0_16px_42px_rgba(37,99,235,.08)] mb-4">
    <div class="border-bottom border-primary-subtle bg-gradient-to-r from-blue-50 via-sky-50 to-white px-3 py-2">
        <div class="d-flex align-items-center gap-2">
            <span class="d-inline-flex align-items-center justify-content-center rounded-3 bg-primary text-white shadow-sm" style="width:34px;height:34px;"><i class="bi bi-funnel"></i></span>
            <div>
                <div class="fw-bold" style="color:#1e3a8a;">Filter Konsesi</div>
                <div class="small fw-semibold text-muted">Cari hak kelola berdasarkan mitra, layanan, kontrak, dan status.</div>
            </div>
        </div>
    </div>
    <div class="p-4">
        <form method="GET" action="{{ route('jasa.konsesi.index') }}" class="row g-3">
            <div class="col-md-4">
                <label class="form-label small text-muted fw-bold">Pencarian</label>
                <input type="text" name="q" class="form-control form-control-sm" placeholder="Nama mitra, layanan, atau kontrak..." value="{{ request('q') }}">
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted fw-bold">Mitra Jasa</label>
                <select name="mitra_jasa_id" class="form-select form-select-sm">
                    <option value="">— Semua Mitra —</option>
                    @foreach($mitras as $m)
                        <option value="{{ $m->id }}" {{ request('mitra_jasa_id') == $m->id ? 'selected' : '' }}>{{ $m->nama_mitra }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted fw-bold">Status</label>
                <select name="status_aktif" class="form-select form-select-sm">
                    <option value="">— Semua Status —</option>
                    <option value="1" {{ request('status_aktif') === '1' ? 'selected' : '' }}>Aktif</option>
                    <option value="0" {{ request('status_aktif') === '0' ? 'selected' : '' }}>Nonaktif</option>
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-primary btn-sm fw-bold jasa-icon-btn" title="Filter" aria-label="Filter"><i class="bi bi-check2-circle"></i></button>
                @if(request()->anyFilled(['q', 'mitra_jasa_id', 'status_aktif']))
                    <a href="{{ route('jasa.konsesi.index') }}" class="btn btn-light btn-sm border jasa-icon-btn" title="Reset filter" aria-label="Reset filter"><i class="bi bi-arrow-counterclockwise"></i></a>
                @endif
            </div>
        </form>
    </div>
</div>

<div class="overflow-hidden rounded-3xl border border-blue-100 bg-white shadow-[0_16px_42px_rgba(37,99,235,.08)]">
    <div class="border-bottom border-primary-subtle bg-gradient-to-r from-blue-50 via-sky-50 to-white px-3 py-2">
        <div class="d-flex align-items-center gap-2">
            <span class="d-inline-flex align-items-center justify-content-center rounded-3 bg-primary text-white shadow-sm" style="width:34px;height:34px;"><i class="bi bi-table"></i></span>
            <div>
                <div class="fw-bold" style="color:#1e3a8a;">Daftar Hak Konsesi</div>
                <div class="small fw-semibold text-muted">Layanan konsesi yang aktif maupun nonaktif per mitra.</div>
            </div>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead style="background:#eff6ff;color:#1d4ed8;">
                <tr>
                    <th class="ps-4">No</th>
                    <th>Mitra Jasa</th>
                    <th>Layanan Konsesi</th>
                    <th>Skema</th>
                    <th>Periode</th>
                    <th>Status</th>
                    <th class="pe-4">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($konsesis as $konsesi)
                    <tr>
                        <td class="ps-4">{{ $konsesis->firstItem() + $loop->index }}</td>
                        <td>
                            <a href="{{ route('jasa.mitra.show', $konsesi->mitraJasa) }}" class="fw-semibold text-decoration-none">
                                {{ $konsesi->mitraJasa->nama_mitra ?? '-' }}
                            </a>
                            <div class="small text-muted">{{ $konsesi->kontrakMitraJasa->nomor_kontrak ?? 'Tanpa Kontrak' }}</div>
                        </td>
                        <td>
                            <div class="fw-semibold">{{ $konsesi->layananJasa->nama_layanan ?? '-' }}</div>
                            <div class="small text-muted">{{ $konsesi->tanggal_mulai ? \Carbon\Carbon::parse($konsesi->tanggal_mulai)->format('d/m/Y') : '-' }} s.d. {{ $konsesi->tanggal_selesai ? \Carbon\Carbon::parse($konsesi->tanggal_selesai)->format('d/m/Y') : 'Selamanya' }}</div>
                        </td>
                        <td>
                            @if($konsesi->jenis_konsesi === 'persen_omzet')
                                <span class="badge bg-success">{{ floatval($konsesi->persentase_konsesi) }}% Omzet</span>
                            @elseif($konsesi->jenis_konsesi === 'nilai_tetap')
                                <span class="badge bg-primary">Tetap: Rp {{ number_format($konsesi->nilai_tetap, 0, ',', '.') }}</span>
                            @elseif($konsesi->jenis_konsesi === 'minimum_guarantee')
                                <span class="badge bg-info text-dark">MAG: Rp {{ number_format($konsesi->nilai_minimum_guarantee, 0, ',', '.') }}</span>
                            @else
                                <span class="badge bg-warning text-dark">Kombinasi</span>
                            @endif
                        </td>
                        <td>{{ ucfirst($konsesi->periode_pelaporan ?? 'Bulanan') }}</td>
                        <td>
                            <span class="badge {{ $konsesi->status_aktif ? 'bg-success' : 'bg-secondary' }}">
                                {{ $konsesi->status_aktif ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </td>
                        <td class="pe-4">
                            <a href="{{ route('jasa.mitra.konsesi.edit', [$konsesi->mitraJasa, $konsesi]) }}" class="btn btn-sm btn-light border text-primary fw-semibold jasa-icon-btn" title="Edit" aria-label="Edit"><i class="bi bi-pencil"></i></a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">Belum ada data hak konsesi.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($konsesis->hasPages())
        <div class="card-footer bg-white">
            {{ $konsesis->links() }}
        </div>
    @endif
</div>
</div>
@endsection
