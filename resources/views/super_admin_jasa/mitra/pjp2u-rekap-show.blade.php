@extends('layouts.app')
@section('title', 'Detail Rekap PAX PJP2U')

@section('content')
@php
    $canCreateTagihanJasa = auth()->user()?->hasRole('Super Admin') === true
        || (auth()->user()?->hasAnyRole(['Admin Jasa', 'Admin Konsesi']) === true && ! auth()->user()?->hasRole('Super Admin Jasa'));
    $rupiah = fn ($value) => 'Rp ' . number_format((float) $value, 0, ',', '.');
    $angka = fn ($value) => number_format((float) $value, 0, ',', '.');
    $tanggal = fn ($value) => $value ? \Carbon\Carbon::parse($value)->format('d/m/Y') : '-';
    $periodeLabel = \Carbon\Carbon::create($tahun, $bulan, 1)->translatedFormat('F Y');
    $tarifEfektif = function ($penjualan) {
        $pax = (float) ($penjualan->total_omzet ?? 0);
        if ($pax > 0) {
            return (float) $penjualan->nilai_tagihan / $pax;
        }

        return (float) ($penjualan->layananJasa->tarif_dasar ?? 0);
    };
@endphp

<div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
    <div>
        <h4 class="mb-0 fw-bold">Detail Rekap PAX PJP2U</h4>
        <p class="mb-0 small text-muted">{{ $mitra->nama_mitra }} - {{ $periodeLabel }}</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('jasa.mitra.pjp2u.index', request()->query()) }}" class="btn btn-secondary fw-bold jasa-icon-btn" title="Kembali ke rekap" aria-label="Kembali ke rekap"><i class="bi bi-arrow-left"></i></a>
        <a href="{{ route('jasa.mitra.show', $mitra) }}#riwayat-pax-pjp2u" class="btn btn-light border fw-bold jasa-icon-btn" title="Detail mitra" aria-label="Detail mitra"><i class="bi bi-building"></i></a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
    <div class="p-4 text-white" style="background: linear-gradient(135deg, #12355c, #1d65a6);">
        <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <div class="small text-white-50 fw-bold text-uppercase mb-1">Layanan PJP2U</div>
                <h5 class="fw-bold mb-1 text-white">{{ $layanan->nama_layanan }}</h5>
                <div class="small text-white-50">Laporan harian dalam rekap {{ $periodeLabel }}</div>
            </div>
            <span class="badge {{ $rekap->status_class }} px-3 py-2 fs-6">{{ $rekap->status_label }}</span>
        </div>
    </div>
    <div class="card-body p-4">
        <div class="row g-3">
            <div class="col-md-3">
                <div class="border rounded-4 p-3 h-100">
                    <div class="small text-muted fw-bold">Laporan Harian</div>
                    <div class="fs-4 fw-black">{{ $angka($rekap->jumlah_laporan) }}</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="border rounded-4 p-3 h-100">
                    <div class="small text-muted fw-bold">Total Pax</div>
                    <div class="fs-4 fw-black">{{ $angka($rekap->total_pax) }}</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="border rounded-4 p-3 h-100">
                    <div class="small text-muted fw-bold">Total Tagihan</div>
                    <div class="fs-4 fw-black text-success">{{ $rupiah($rekap->nilai_tagihan) }}</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="border rounded-4 p-3 h-100">
                    <div class="small text-muted fw-bold">Tagihan Terbit</div>
                    <div class="fs-4 fw-black">{{ $angka($rekap->tagihan_count) }}</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 overflow-hidden">
    <div class="card-header bg-white d-flex justify-content-between align-items-center gap-3 flex-wrap">
        <div>
            <h5 class="mb-0 fw-bold">Data Laporan Harian</h5>
            <div class="small text-muted">Tagihan PJP2U tetap dibuat per laporan harian dari tabel ini.</div>
        </div>
        <span class="badge bg-light text-dark border">{{ $periodeLabel }}</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead style="background:#eff6ff;color:#1d4ed8;">
                <tr>
                    <th>No</th>
                    <th>Tanggal Laporan</th>
                    <th>Pax</th>
                    <th>Detail Penerbangan</th>
                    <th>Tarif Efektif</th>
                    <th>Nilai Tagihan</th>
                    <th>Status</th>
                    <th>Tagihan</th>
                    <th>File</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($penjualans as $penjualan)
                    @php
                        $detailPenerbanganCount = count($penjualan->penerbangan_details ?? []);
                    @endphp
                    <tr>
                        <td>{{ $penjualans->firstItem() + $loop->index }}</td>
                        <td>
                            <div class="fw-semibold">{{ $tanggal($penjualan->periode_mulai) }}</div>
                            <div class="small text-muted">s.d. {{ $tanggal($penjualan->periode_selesai) }}</div>
                        </td>
                        <td class="fw-semibold">{{ $angka($penjualan->total_omzet) }} pax</td>
                        <td>
                            @if($detailPenerbanganCount > 0)
                                <span class="badge bg-light text-dark border">{{ $detailPenerbanganCount }} baris</span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td>{{ $rupiah($tarifEfektif($penjualan)) }}</td>
                        <td class="fw-bold text-success">{{ $rupiah($penjualan->nilai_tagihan) }}</td>
                        <td><span class="badge bg-{{ $penjualan->status_color }}">{{ $penjualan->label_status }}</span></td>
                        <td>
                            @if($penjualan->tagihanJasa)
                                <a href="{{ route('tagihan-jasa.show', $penjualan->tagihanJasa) }}" class="btn btn-sm btn-light border">{{ $penjualan->tagihanJasa->nomor_tagihan }}</a>
                            @else
                                <span class="text-muted">Belum</span>
                            @endif
                        </td>
                        <td>
                            @if($penjualan->file_laporan)
                                <a href="{{ asset('storage/' . $penjualan->file_laporan) }}" target="_blank" class="btn btn-sm btn-light border jasa-icon-btn" title="File laporan" aria-label="File laporan"><i class="bi bi-paperclip"></i></a>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td>
                            <div class="d-flex gap-1 flex-wrap align-items-center">
                                <a href="{{ route('jasa.mitra.penjualan.show', [$mitra, $penjualan]) }}" class="btn btn-sm btn-light border text-primary fw-semibold jasa-icon-btn" title="Detail laporan" aria-label="Detail laporan"><i class="bi bi-eye"></i></a>
                                @if($penjualan->status === 'diajukan' && $penjualan->can_be_verified)
                                    <form method="POST" action="{{ route('jasa.mitra.penjualan.verify', [$mitra, $penjualan]) }}">
                                        @csrf
                                        <button class="btn btn-sm btn-success jasa-icon-btn" title="Verifikasi" aria-label="Verifikasi"><i class="bi bi-check2-circle"></i></button>
                                    </form>
                                    <form method="POST" action="{{ route('jasa.mitra.penjualan.reject', [$mitra, $penjualan]) }}" onsubmit="return confirm('Tolak laporan ini? Pastikan catatan sudah diisi.');" class="d-flex gap-1">
                                        @csrf
                                        <input type="text" name="catatan_verifikator" class="form-control form-control-sm" placeholder="Catatan" required style="width: 130px;">
                                        <button class="btn btn-sm btn-danger jasa-icon-btn" title="Tolak" aria-label="Tolak"><i class="bi bi-x-circle"></i></button>
                                    </form>
                                @endif
                                @if($penjualan->status === 'diajukan' && ! $penjualan->can_be_verified)
                                    <span class="badge bg-warning text-dark" title="Menunggu syarat verifikasi laporan">
                                        <i class="bi bi-hourglass-split"></i> Tunggu
                                    </span>
                                @endif
                                @if($penjualan->status === 'diverifikasi' && ! $penjualan->tagihan_jasa_id && $penjualan->layanan_jasa_id)
                                    @if($canCreateTagihanJasa && $penjualan->can_create_tagihan)
                                        <a href="{{ route('tagihan-jasa.create', ['penjualan_id' => $penjualan->id]) }}" class="btn btn-sm btn-primary fw-semibold">
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
                    <tr>
                        <td colspan="10" class="text-center text-muted py-4">Belum ada laporan harian pada rekap ini.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($penjualans->hasPages())
        <div class="card-footer bg-white">{{ $penjualans->links() }}</div>
    @endif
</div>
@endsection
