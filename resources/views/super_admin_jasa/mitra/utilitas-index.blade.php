@extends('layouts.app')
@section('title', 'Verifikasi & Tagihan Utilitas')

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
            <i class="bi bi-lightning-charge text-xl"></i>
        </div>
        <div>
            <h4 class="mb-1 text-xl font-black text-white lg:text-2xl">Verifikasi & Tagihan Utilitas</h4>
            <p class="mb-0 text-sm font-semibold text-blue-100/80">Review laporan meteran dari Admin Listrik/Air, tentukan tarif, dan terbitkan tagihan resmi.</p>
        </div>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

{{-- Filter --}}
<div class="overflow-hidden rounded-3xl border border-blue-100 bg-white shadow-[0_16px_42px_rgba(37,99,235,.08)] mb-4">
    <div class="border-bottom border-primary-subtle bg-gradient-to-r from-blue-50 via-sky-50 to-white px-3 py-2">
        <div class="d-flex align-items-center gap-2">
            <span class="d-inline-flex align-items-center justify-content-center rounded-3 bg-primary text-white shadow-sm" style="width:34px;height:34px;"><i class="bi bi-funnel"></i></span>
            <div>
                <div class="fw-bold" style="color:#1e3a8a;">Filter Utilitas</div>
                <div class="small fw-semibold text-muted">Pilih jenis utilitas untuk menampilkan laporan terkait.</div>
            </div>
        </div>
    </div>
    <div class="p-4">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label class="form-label small fw-bold">Jenis Utilitas</label>
                <select name="jenis" class="form-select" onchange="this.form.submit()">
                    <option value="">Semua Jenis</option>
                    <option value="listrik" {{ request('jenis') == 'listrik' ? 'selected' : '' }}>Listrik</option>
                    <option value="air" {{ request('jenis') == 'air' ? 'selected' : '' }}>Air</option>
                </select>
            </div>
        </form>
    </div>
</div>

{{-- Tabel Laporan --}}
<div class="overflow-hidden rounded-3xl border border-blue-100 bg-white shadow-[0_16px_42px_rgba(37,99,235,.08)]">
    <div class="border-bottom border-primary-subtle bg-gradient-to-r from-blue-50 via-sky-50 to-white px-3 py-2">
        <div class="d-flex align-items-center gap-2">
            <span class="d-inline-flex align-items-center justify-content-center rounded-3 bg-primary text-white shadow-sm" style="width:34px;height:34px;"><i class="bi bi-table"></i></span>
            <div>
                <div class="fw-bold" style="color:#1e3a8a;">Daftar Laporan Utilitas</div>
                <div class="small fw-semibold text-muted">Laporan listrik dan air yang menunggu verifikasi atau tagihan.</div>
            </div>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead style="background:#eff6ff;color:#1d4ed8;">
                <tr>
                    <th class="ps-3">Periode</th>
                    <th>Jenis</th>
                    <th>Mitra</th>
                    <th>Tipe</th>
                    <th>Pemakaian</th>
                    <th>Bukti</th>
                    <th>Pencatat</th>
                    <th>Status</th>
                    <th class="pe-3 text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($laporans as $lap)
                    <tr>
                        <td class="ps-3 fw-semibold">{{ str_pad($lap->bulan, 2, '0', STR_PAD_LEFT) }}/{{ $lap->tahun }}</td>
                        <td>
                            <span class="badge {{ $lap->jenis == 'listrik' ? 'bg-warning text-dark' : 'bg-info' }}">
                                {{ ucfirst($lap->jenis) }}
                            </span>
                        </td>
                        <td>{{ $lap->mitraJasa->nama_mitra ?? '-' }}</td>
                        <td><span class="badge bg-light text-dark border">{{ $lap->tipe_perhitungan == 'kwh' ? ($lap->jenis == 'listrik' ? 'KWH' : 'M³') : 'FLAT' }}</span></td>
                        <td>
                            @if($lap->tipe_perhitungan == 'kwh')
                                <strong>{{ $lap->pemakaian }}</strong> unit<br>
                                <small class="text-muted">({{ $lap->stan_awal }} → {{ $lap->stan_akhir }})</small>
                            @else
                                <strong>{{ $lap->pemakaian }}</strong> unit <small class="text-muted">(flat)</small>
                            @endif
                        </td>
                        <td>
                            @if($lap->file_bukti_awal)
                                <a href="{{ asset('storage/' . $lap->file_bukti_awal) }}" target="_blank" class="btn btn-sm btn-outline-primary jasa-icon-btn" title="Bukti Awal" aria-label="Bukti Awal"><i class="bi bi-image"></i></a>
                            @endif
                            @if($lap->file_bukti)
                                <a href="{{ asset('storage/' . $lap->file_bukti) }}" target="_blank" class="btn btn-sm btn-outline-primary jasa-icon-btn" title="Bukti Akhir" aria-label="Bukti Akhir"><i class="bi bi-images"></i></a>
                            @endif
                            @if(!$lap->file_bukti && !$lap->file_bukti_awal)
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td>{{ $lap->createdByUser->pegawai->nama_lengkap ?? 'Admin' }}</td>
                        <td>
                            @if($lap->status == 'dikirim_ke_admin_jasa')
                                <span class="badge bg-warning text-dark">Menunggu Review</span>
                            @elseif($lap->status == 'ditagihkan')
                                <span class="badge bg-success">Ditagihkan</span>
                                @if($lap->total_biaya)
                                    <br><small class="text-success fw-bold">Rp {{ number_format($lap->total_biaya, 0, ',', '.') }}</small>
                                @endif
                            @endif
                        </td>
                        <td class="pe-3 text-end">
                            <a href="{{ route('jasa.utilitas.show', $lap->id) }}" class="btn btn-sm btn-light border text-primary fw-semibold me-1 jasa-icon-btn" title="Detail" aria-label="Detail"><i class="bi bi-eye"></i></a>
                            @if($lap->status == 'dikirim_ke_admin_jasa')
                                <a href="{{ route('tagihan-jasa.create', ['utilitas_id' => $lap->id]) }}" class="btn btn-sm btn-primary jasa-icon-btn" title="Buat tagihan" aria-label="Buat tagihan"><i class="bi bi-receipt"></i></a>
                                <button class="btn btn-sm btn-outline-danger jasa-icon-btn" data-bs-toggle="modal" data-bs-target="#modalTolak-{{ $lap->id }}" title="Tolak" aria-label="Tolak"><i class="bi bi-x-circle"></i></button>
                            @elseif($lap->status == 'ditagihkan' && $lap->tagihan_jasa_id)
                                <a href="{{ route('tagihan-jasa.show', $lap->tagihan_jasa_id) }}" class="btn btn-sm btn-light border text-primary fw-semibold jasa-icon-btn" title="Lihat tagihan" aria-label="Lihat tagihan"><i class="bi bi-receipt"></i></a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center py-4 text-muted">Belum ada laporan utilitas yang perlu direview.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($laporans->hasPages())
        <div class="card-footer bg-white">
            {{ $laporans->links() }}
        </div>
    @endif
</div>

{{-- MODALS --}}
@foreach($laporans->where('status', 'dikirim_ke_admin_jasa') as $lap)
    {{-- Modal Tolak --}}
    <div class="modal fade" id="modalTolak-{{ $lap->id }}" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content rounded-4">
                <form action="{{ route('jasa.utilitas.tolak', $lap->id) }}" method="POST">
                    @csrf
                    <div class="modal-header border-0 pb-0">
                        <h5 class="modal-title fw-bold text-danger">Tolak Laporan</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p class="small text-muted">Laporan dari <strong>{{ $lap->mitraJasa->nama_mitra ?? '-' }}</strong> periode {{ str_pad($lap->bulan, 2, '0', STR_PAD_LEFT) }}/{{ $lap->tahun }} akan dikembalikan ke pencatat.</p>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Alasan Penolakan <span class="text-danger">*</span></label>
                            <textarea name="catatan" class="form-control" rows="3" required placeholder="Misal: Stan akhir terlihat salah, tolong cek ulang meterannya."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-danger fw-bold">Tolak & Kembalikan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endforeach

</div>
@endsection
