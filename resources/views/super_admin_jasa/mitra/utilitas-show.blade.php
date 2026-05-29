@extends('layouts.app')
@section('title', 'Detail Laporan Utilitas')

@section('content')
@php
    $canCreateTagihanJasa = auth()->user()?->hasRole('Super Admin') === true
        || (auth()->user()?->hasAnyRole(['Admin Jasa', 'Admin Konsesi']) === true && ! auth()->user()?->hasRole('Super Admin Jasa'));
    $rupiah = fn ($value) => $value !== null ? 'Rp ' . number_format((float) $value, 0, ',', '.') : '-';
    $angka = fn ($value) => $value !== null ? number_format((float) $value, 2, ',', '.') : '-';
    $tanggal = fn ($value) => $value ? \Carbon\Carbon::parse($value)->format('d/m/Y') : '-';
    $tanggalWaktu = fn ($value) => $value ? \Carbon\Carbon::parse($value)->format('d/m/Y H:i') : '-';
    $satuanText = $laporan->jenis === 'listrik' ? 'kWh' : 'm³';
    $statusBadge = match ($laporan->status) {
        'menunggu_input_pencatat' => ['bg-secondary', 'Menunggu Input'],
        'draft' => ['bg-secondary', 'Draft'],
        'dikirim_ke_admin_jasa' => ['bg-warning text-dark', 'Menunggu Verifikasi'],
        'ditagihkan' => ['bg-primary', 'Ditagihkan'],
        'ditolak' => ['bg-danger', 'Ditolak'],
        default => ['bg-secondary', $laporan->status],
    };
@endphp

<div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
    <div>
        <h4 class="mb-0 fw-bold">Detail Laporan Utilitas</h4>
        <p class="mb-0 small text-muted">{{ $laporan->mitraJasa->nama_mitra ?? '-' }} &middot; Periode {{ str_pad($laporan->bulan, 2, '0', STR_PAD_LEFT) }}/{{ $laporan->tahun }}</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('jasa.utilitas.index') }}" class="btn btn-light border fw-bold jasa-icon-btn" title="Kembali" aria-label="Kembali"><i class="bi bi-arrow-left"></i></a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <div>
                    <div class="small text-muted fw-bold text-uppercase mb-1">Laporan Pemakaian {{ ucfirst($laporan->jenis) }}</div>
                    <h5 class="fw-bold mb-0">{{ $laporan->layananJasa->nama_layanan ?? 'Layanan tidak diketahui' }}</h5>
                </div>
                <span class="badge {{ $statusBadge[0] }} px-3 py-2 fs-6">{{ $statusBadge[1] }}</span>
            </div>
            <div class="card-body p-4">
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <div class="p-3 rounded-3 border bg-light">
                            <div class="small text-muted fw-bold">Mitra</div>
                            <div class="fw-bold">{{ $laporan->mitraJasa->nama_mitra ?? '-' }}</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 rounded-3 border bg-light">
                            <div class="small text-muted fw-bold">Periode</div>
                            <div class="fw-bold">{{ \Carbon\Carbon::create()->month($laporan->bulan)->translatedFormat('F') }} {{ $laporan->tahun }}</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 rounded-3 border bg-light">
                            <div class="small text-muted fw-bold">Jenis Utilitas</div>
                            <div class="fw-bold text-capitalize">{{ $laporan->jenis }}</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 rounded-3 border bg-light">
                            <div class="small text-muted fw-bold">Tipe Perhitungan</div>
                            <div class="fw-bold text-uppercase">{{ $laporan->tipe_perhitungan ?? '-' }}</div>
                        </div>
                    </div>
                    @if($laporan->nomor_meter)
                        <div class="col-md-6">
                            <div class="p-3 rounded-3 border bg-light">
                                <div class="small text-muted fw-bold">Nomor Meter</div>
                                <div class="fw-bold">{{ $laporan->nomor_meter }}</div>
                            </div>
                        </div>
                    @endif
                </div>

                <h6 class="fw-bold mb-3"><i class="bi bi-speedometer2 text-primary me-1"></i>Detail Pemakaian</h6>
                <div class="row g-3 mb-4">
                    @if($laporan->tipe_perhitungan === 'kwh')
                        <div class="col-md-4">
                            <div class="p-3 rounded-3 border">
                                <div class="small text-muted fw-bold">Stan Awal</div>
                                <div class="fw-bold fs-5">{{ $angka($laporan->stan_awal) }} <span class="text-muted small">{{ $satuanText }}</span></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3 rounded-3 border">
                                <div class="small text-muted fw-bold">Stan Akhir</div>
                                <div class="fw-bold fs-5">{{ $angka($laporan->stan_akhir) }} <span class="text-muted small">{{ $satuanText }}</span></div>
                            </div>
                        </div>
                    @endif
                    <div class="col-md-4">
                        <div class="p-3 rounded-3 bg-primary-subtle border border-primary-subtle">
                            <div class="small text-primary fw-bold">Pemakaian</div>
                            <div class="fw-bold fs-5 text-primary">{{ $angka($laporan->pemakaian) }} <span class="small">{{ $satuanText }}</span></div>
                        </div>
                    </div>
                </div>

                <h6 class="fw-bold mb-3"><i class="bi bi-cash-stack text-success me-1"></i>Tarif & Biaya</h6>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <div class="p-3 rounded-3 border">
                            <div class="small text-muted fw-bold">Tarif per Unit</div>
                            <div class="fw-bold fs-5">{{ $rupiah($laporan->tarif_per_unit) }}</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 rounded-3 bg-success-subtle border border-success-subtle">
                            <div class="small text-success fw-bold">Total Biaya</div>
                            <div class="fw-bold fs-5 text-success">{{ $rupiah($laporan->total_biaya) }}</div>
                        </div>
                    </div>
                </div>

                @if($laporan->file_bukti_awal || $laporan->file_bukti)
                    <h6 class="fw-bold mb-3"><i class="bi bi-paperclip text-warning me-1"></i>Bukti Lampiran</h6>
                    <div class="row g-2 mb-3">
                        @if($laporan->file_bukti_awal)
                            <div class="col-md-6">
                                <a href="{{ asset('storage/' . $laporan->file_bukti_awal) }}" target="_blank" class="btn btn-light border fw-semibold jasa-icon-btn" title="Bukti stan awal" aria-label="Bukti stan awal">
                                    <i class="bi bi-file-earmark-image"></i>
                                </a>
                            </div>
                        @endif
                        @if($laporan->file_bukti)
                            <div class="col-md-6">
                                <a href="{{ asset('storage/' . $laporan->file_bukti) }}" target="_blank" class="btn btn-light border fw-semibold jasa-icon-btn" title="Bukti stan akhir" aria-label="Bukti stan akhir">
                                    <i class="bi bi-file-earmark-image"></i>
                                </a>
                            </div>
                        @endif
                    </div>
                @endif

                @if($laporan->catatan_admin_jasa)
                    <div class="alert alert-warning mb-0">
                        <strong><i class="bi bi-chat-left-text me-1"></i>Catatan Admin Jasa:</strong>
                        <div class="mt-1">{{ $laporan->catatan_admin_jasa }}</div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        @include('_partials.mp-timeline-style')
        @include('_partials.mp-timeline-utilitas', [
            'laporan' => $laporan,
            'tanggalWaktu' => $tanggalWaktu,
            'tagihanRoute' => 'tagihan-jasa.show',
        ])

        @if($laporan->status === 'dikirim_ke_admin_jasa')
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white fw-bold">
                    <i class="bi bi-lightning me-1"></i>Aksi
                </div>
                <div class="card-body d-grid gap-2">
                    @if($canCreateTagihanJasa)
                        <a href="{{ route('tagihan-jasa.create', ['utilitas_id' => $laporan->id]) }}" class="btn btn-primary fw-bold jasa-icon-btn" title="Buat tagihan" aria-label="Buat tagihan">
                            <i class="bi bi-receipt"></i>
                        </a>
                    @endif
                    <button type="button" class="btn btn-outline-danger fw-bold jasa-icon-btn" data-bs-toggle="modal" data-bs-target="#modalTolak" title="Tolak laporan" aria-label="Tolak laporan">
                        <i class="bi bi-x-circle"></i>
                    </button>
                </div>
            </div>

            {{-- Modal Tolak --}}
            <div class="modal fade" id="modalTolak" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content rounded-4">
                        <form action="{{ route('jasa.utilitas.tolak', $laporan->id) }}" method="POST">
                            @csrf
                            <div class="modal-header border-0 pb-0">
                                <h5 class="modal-title fw-bold text-danger">Tolak Laporan</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <p class="small text-muted">Laporan akan dikembalikan ke pencatat. Mohon isi alasan penolakan.</p>
                                <div class="mb-2">
                                    <label class="form-label fw-bold">Alasan Penolakan <span class="text-danger">*</span></label>
                                    <textarea name="catatan" rows="3" class="form-control" required></textarea>
                                </div>
                            </div>
                            <div class="modal-footer border-0">
                                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Batal</button>
                                <button type="submit" class="btn btn-danger fw-bold">Tolak Laporan</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
