@extends('layouts.app')
@section('title', 'Detail Laporan Penjualan')

@section('content')
@php
    $rupiah = fn ($value) => 'Rp ' . number_format((float) $value, 0, ',', '.');
    $tanggal = fn ($value) => $value ? \Carbon\Carbon::parse($value)->format('d/m/Y') : '-';
    $tanggalWaktu = fn ($value) => $value ? \Carbon\Carbon::parse($value)->format('d/m/Y H:i') : '-';
    $persen = fn ($value) => $value !== null ? rtrim(rtrim(number_format((float) $value, 4, ',', '.'), '0'), ',') . '%' : '-';

    $layananPath = function ($layanan) {
        if (! $layanan) return collect();
        $items = collect([$layanan]);
        $parent = $layanan->parent;
        $guard = 0;
        while ($parent && $guard < 10) {
            $items->prepend($parent);
            $parent = $parent->parent;
            $guard++;
        }
        return $items;
    };
@endphp

<div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
    <div>
        <h4 class="mb-0 fw-bold">Detail Laporan Penjualan</h4>
        <p class="mb-0 small text-muted">{{ $mitra->nama_mitra }}</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('jasa.mitra.show', $mitra) }}" class="btn btn-secondary fw-bold jasa-icon-btn" title="Kembali ke mitra" aria-label="Kembali ke mitra"><i class="bi bi-arrow-left"></i></a>
        <a href="{{ route('jasa.mitra.penjualan.index') }}" class="btn btn-light border fw-bold jasa-icon-btn" title="Semua laporan" aria-label="Semua laporan"><i class="bi bi-list-ul"></i></a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<div class="row g-4">
    {{-- Card Utama: Info Laporan --}}
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="p-4 text-white" style="background: linear-gradient(135deg, #0f2f57, #1d6fb8);">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="small text-white-50 fw-bold text-uppercase mb-1">Laporan Pendapatan Konsesi</div>
                        <h5 class="fw-bold mb-1 text-white">
                            {{ $penjualan->layananJasa->nama_layanan ?? 'Layanan tidak diketahui' }}
                        </h5>
                        <div class="small text-white-50">
                            Periode: {{ $tanggal($penjualan->periode_mulai) }} s.d. {{ $tanggal($penjualan->periode_selesai) }}
                        </div>
                    </div>
                    <span class="badge bg-{{ $penjualan->status_color }} px-3 py-2 fs-6">
                        {{ $penjualan->label_status }}
                    </span>
                </div>
            </div>

            <div class="card-body p-4">
                {{-- Breadcrumb Layanan --}}
                @if($penjualan->layananJasa)
                    <div class="mb-4">
                        <div class="small text-muted fw-bold mb-2">Hierarki Layanan</div>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0" style="font-size: 13px;">
                                @foreach($layananPath($penjualan->layananJasa) as $node)
                                    @if($loop->last)
                                        <li class="breadcrumb-item active fw-bold">{{ $node->nama_layanan }}</li>
                                    @else
                                        <li class="breadcrumb-item text-muted">{{ $node->nama_layanan }}</li>
                                    @endif
                                @endforeach
                            </ol>
                        </nav>
                    </div>
                @endif

                {{-- Detail Angka --}}
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="p-3 rounded-3 border bg-light">
                            @if($penjualan->penerbangan_details)
                                <div class="small text-muted fw-bold"><i class="bi bi-people me-1 text-primary"></i>Total Pax</div>
                                <div class="fs-5 fw-bold text-dark">{{ number_format($penjualan->total_omzet, 0, ',', '.') }} Pax</div>
                            @else
                                <div class="small text-muted fw-bold"><i class="bi bi-cash-stack me-1 text-primary"></i>Total Omzet</div>
                                <div class="fs-5 fw-bold text-dark">{{ $rupiah($penjualan->total_omzet) }}</div>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 rounded-3 border bg-light">
                            @if($penjualan->penerbangan_details)
                                <div class="small text-muted fw-bold"><i class="bi bi-tag me-1 text-primary"></i>Tarif Dasar Layanan</div>
                                <div class="fs-5 fw-bold text-dark">{{ $rupiah($penjualan->layananJasa->tarif_dasar ?? 0) }} / Pax</div>
                            @else
                                <div class="small text-muted fw-bold"><i class="bi bi-percent me-1 text-primary"></i>Persentase Konsesi</div>
                                <div class="fs-5 fw-bold text-dark">{{ $persen($penjualan->persentase_konsesi) }}</div>
                            @endif
                        </div>
                    </div>
                    @if(!$penjualan->penerbangan_details)
                    <div class="col-md-6">
                        <div class="p-3 rounded-3 border bg-light">
                            <div class="small text-muted fw-bold"><i class="bi bi-calculator me-1 text-primary"></i>Nilai Konsesi</div>
                            <div class="fs-5 fw-bold text-dark">{{ $rupiah($penjualan->nilai_konsesi) }}</div>
                        </div>
                    </div>
                    @endif
                    <div class="col-md-6">
                        <div class="p-3 rounded-3 border" style="background: #e8f5e9;">
                            <div class="small text-muted fw-bold"><i class="bi bi-receipt me-1 text-success"></i>Nilai Tagihan</div>
                            <div class="fs-5 fw-bold text-success">{{ $rupiah($penjualan->nilai_tagihan) }}</div>
                        </div>
                    </div>
                    @if($penjualan->nilai_minimum_guarantee)
                        <div class="col-md-6">
                            <div class="p-3 rounded-3 border bg-light">
                                <div class="small text-muted fw-bold"><i class="bi bi-shield-check me-1 text-primary"></i>Minimum Guarantee</div>
                                <div class="fs-5 fw-bold text-dark">{{ $rupiah($penjualan->nilai_minimum_guarantee) }}</div>
                            </div>
                        </div>
                    @endif
                    @if($penjualan->total_transaksi)
                        <div class="col-md-6">
                            <div class="p-3 rounded-3 border bg-light">
                                <div class="small text-muted fw-bold"><i class="bi bi-hash me-1 text-primary"></i>Total Transaksi</div>
                                <div class="fs-5 fw-bold text-dark">{{ number_format($penjualan->total_transaksi, 0, ',', '.') }}</div>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Catatan Mitra --}}
                @if($penjualan->catatan_mitra)
                    <div class="mt-4 p-3 rounded-3 border bg-light">
                        <div class="small text-muted fw-bold mb-1"><i class="bi bi-chat-left-text me-1"></i>Catatan dari Mitra</div>
                        <div>{{ $penjualan->catatan_mitra }}</div>
                    </div>
                @endif

                {{-- Detail Penerbangan --}}
                @if($penjualan->penerbangan_details)
                    <div class="mt-4 p-3 rounded-3 border bg-light">
                        <div class="small text-muted fw-bold mb-3"><i class="bi bi-airplane-engines me-1 text-primary"></i>Detail Penerbangan (Pax)</div>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered bg-white mb-0 text-center">
                                <thead class="table-light">
                                    <tr>
                                        <th class="small text-uppercase">Nomor Penerbangan</th>
                                        <th class="small text-uppercase">Dewasa</th>
                                        <th class="small text-uppercase">Anak</th>
                                        <th class="small text-uppercase">Bayi</th>
                                        <th class="small text-uppercase">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $gtDewasa = 0; $gtAnak = 0; $gtBayi = 0;
                                    @endphp
                                    @foreach($penjualan->penerbangan_details as $flight)
                                        @php
                                            $gtDewasa += (int)($flight['pax_dewasa'] ?? 0);
                                            $gtAnak += (int)($flight['pax_anak'] ?? 0);
                                            $gtBayi += (int)($flight['pax_bayi'] ?? 0);
                                        @endphp
                                        <tr>
                                            <td class="fw-bold">{{ $flight['nomor_penerbangan'] ?? '-' }}</td>
                                            <td>{{ $flight['pax_dewasa'] ?? 0 }}</td>
                                            <td>{{ $flight['pax_anak'] ?? 0 }}</td>
                                            <td>{{ $flight['pax_bayi'] ?? 0 }}</td>
                                            <td class="fw-bold bg-light">{{ ($flight['pax_dewasa'] ?? 0) + ($flight['pax_anak'] ?? 0) + ($flight['pax_bayi'] ?? 0) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr class="fw-bold bg-light">
                                        <td class="text-end">GRAND TOTAL:</td>
                                        <td>{{ $gtDewasa }}</td>
                                        <td>{{ $gtAnak }}</td>
                                        <td>{{ $gtBayi }}</td>
                                        <td>{{ $gtDewasa + $gtAnak + $gtBayi }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                @endif

                {{-- Catatan Verifikator --}}
                @if($penjualan->catatan_verifikator)
                    <div class="mt-3 p-3 rounded-3 border border-danger bg-danger bg-opacity-10">
                        <div class="small text-danger fw-bold mb-1"><i class="bi bi-exclamation-triangle me-1"></i>Catatan Verifikator</div>
                        <div class="text-danger">{{ $penjualan->catatan_verifikator }}</div>
                    </div>
                @endif

                {{-- File Laporan (legacy) --}}
                @if($penjualan->file_laporan)
                    <div class="mt-4 p-3 rounded-3 border">
                        <div class="small text-muted fw-bold mb-2"><i class="bi bi-file-earmark me-1 text-primary"></i>File Laporan</div>
                        <a href="{{ asset('storage/' . $penjualan->file_laporan) }}" target="_blank" class="btn btn-outline-primary btn-sm jasa-icon-btn" title="Lihat/download file" aria-label="Lihat/download file">
                            <i class="bi bi-download"></i>
                        </a>
                    </div>
                @endif

                {{-- Detail Laporan per Periode --}}
                @if($penjualan->details && $penjualan->details->count() > 0)
                    <div class="mt-4">
                        <div class="small text-muted fw-bold mb-3"><i class="bi bi-list-check me-1 text-primary"></i>Rincian Laporan ({{ $penjualan->details->count() }} entri)</div>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered bg-white mb-0 align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th class="small text-uppercase" width="5%">No</th>
                                        <th class="small text-uppercase">Periode</th>
                                        <th class="small text-uppercase text-end">Omzet</th>
                                        <th class="small text-uppercase text-center">Transaksi</th>
                                        <th class="small text-uppercase">File</th>
                                        <th class="small text-uppercase">Catatan</th>
                                        <th class="small text-uppercase">Dikirim</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($penjualan->details as $detail)
                                        <tr>
                                            <td class="text-center">{{ $loop->iteration }}</td>
                                            <td>{{ $tanggal($detail->periode_mulai) }} s.d. {{ $tanggal($detail->periode_selesai) }}</td>
                                            <td class="text-end fw-bold">{{ $rupiah($detail->total_omzet) }}</td>
                                            <td class="text-center">{{ $detail->total_transaksi ? number_format($detail->total_transaksi, 0, ',', '.') : '-' }}</td>
                                            <td>
                                                @if($detail->file_laporan)
                                                    <a href="{{ asset('storage/' . $detail->file_laporan) }}" target="_blank" class="btn btn-sm btn-light border jasa-icon-btn" title="Lihat file" aria-label="Lihat file">
                                                        <i class="bi bi-file-earmark"></i>
                                                    </a>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td class="small">{{ $detail->catatan_mitra ?? '-' }}</td>
                                            <td class="small text-muted">{{ $tanggalWaktu($detail->submitted_at) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="table-light">
                                    <tr class="fw-bold">
                                        <td colspan="2" class="text-end">TOTAL:</td>
                                        <td class="text-end text-success">{{ $rupiah($penjualan->details->sum('total_omzet')) }}</td>
                                        <td class="text-center">{{ $penjualan->details->sum('total_transaksi') ?: '-' }}</td>
                                        <td colspan="3"></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                @endif

                {{-- Kontrak Terkait --}}
                @if($penjualan->kontrakMitraJasa)
                    <div class="mt-3 p-3 rounded-3 border bg-light">
                        <div class="small text-muted fw-bold mb-1"><i class="bi bi-file-earmark-ruled me-1 text-primary"></i>Kontrak/Dokumen Dasar</div>
                        <div class="fw-semibold">{{ $penjualan->kontrakMitraJasa->nomor_kontrak ?? '-' }}</div>
                        <div class="small text-muted">{{ $penjualan->kontrakMitraJasa->nama_kontrak ?? '' }}</div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Sidebar: Timeline & Aksi --}}
    <div class="col-lg-4">
        @include('_partials.mp-timeline-style')
        @include('_partials.mp-timeline-penjualan', [
            'penjualan' => $penjualan,
            'tanggalWaktu' => $tanggalWaktu,
            'tagihanRoute' => 'tagihan-jasa.show',
        ])

        {{-- Aksi --}}
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white fw-bold">
                <i class="bi bi-lightning me-1"></i>Aksi
            </div>
            <div class="card-body">
                {{-- Status: Diajukan --}}
                @if($penjualan->status === 'diajukan')
                    @if($penjualan->can_be_verified)
                        <form method="POST" action="{{ route('jasa.mitra.penjualan.verify', [$mitra, $penjualan]) }}" class="mb-2">
                            @csrf
                            <button class="btn btn-success fw-bold jasa-icon-btn" onclick="return confirm('Verifikasi laporan penjualan ini?')" title="Verifikasi laporan" aria-label="Verifikasi laporan">
                                <i class="bi bi-check-circle"></i>
                            </button>
                        </form>
                        <form method="POST" action="{{ route('jasa.mitra.penjualan.reject', [$mitra, $penjualan]) }}">
                            @csrf
                            <div class="mb-2">
                                <textarea name="catatan_verifikator" class="form-control form-control-sm" placeholder="Catatan penolakan (wajib)" rows="2" required></textarea>
                            </div>
                            <button class="btn btn-danger fw-bold jasa-icon-btn" onclick="return confirm('Tolak laporan ini? Pastikan catatan sudah diisi.')" title="Tolak laporan" aria-label="Tolak laporan">
                                <i class="bi bi-x-circle"></i>
                            </button>
                        </form>
                    @else
                        <div class="alert alert-warning mb-0">
                            <i class="bi bi-hourglass-split me-1"></i>
                            <strong>Menunggu Pergantian Bulan</strong>
                            <div class="small mt-1">Laporan periode <strong>{{ \Carbon\Carbon::create()->month($penjualan->bulan)->translatedFormat('F') }} {{ $penjualan->tahun }}</strong> baru dapat diverifikasi setelah bulan tersebut berakhir.</div>
                        </div>
                    @endif
                @endif

                {{-- Status: Draft / Ditolak --}}
                @if(in_array($penjualan->status, ['draft', 'ditolak'], true))
                    <a href="{{ route('jasa.mitra.penjualan.edit', [$mitra, $penjualan]) }}" class="btn btn-light border fw-bold mb-2 jasa-icon-btn" title="Edit laporan" aria-label="Edit laporan">
                        <i class="bi bi-pencil"></i>
                    </a>
                    <form method="POST" action="{{ route('jasa.mitra.penjualan.submit', [$mitra, $penjualan]) }}">
                        @csrf
                        <button class="btn btn-primary fw-bold jasa-icon-btn" title="Ajukan verifikasi" aria-label="Ajukan verifikasi">
                            <i class="bi bi-send"></i>
                        </button>
                    </form>
                @endif

                {{-- Status: Diverifikasi --}}
                @if($penjualan->status === 'diverifikasi' && ! $penjualan->tagihan_jasa_id && $penjualan->layanan_jasa_id)
                    @if($penjualan->can_create_tagihan)
                        <a href="{{ route('tagihan-jasa.create', ['penjualan_id' => $penjualan->id]) }}" class="btn btn-primary fw-bold jasa-icon-btn" title="Buat tagihan" aria-label="Buat tagihan">
                            <i class="bi bi-receipt"></i>
                        </a>
                    @else
                        <div class="alert alert-info mb-0">
                            <i class="bi bi-calendar-check me-1"></i>
                            <strong>Tagihan Belum Tersedia</strong>
                            <div class="small mt-1">Tagihan dapat dibuat mulai <strong>{{ $penjualan->tagihan_available_date }}</strong> (1 bulan setelah laporan diajukan).</div>
                        </div>
                    @endif
                @endif

                {{-- Status: Ditagihkan --}}
                @if($penjualan->status === 'ditagihkan' && $penjualan->tagihanJasa)
                    <a href="{{ route('tagihan-jasa.show', $penjualan->tagihanJasa) }}" class="btn btn-outline-primary fw-bold jasa-icon-btn" title="Lihat tagihan" aria-label="Lihat tagihan">
                        <i class="bi bi-eye"></i>
                    </a>
                @endif

                {{-- Jika tidak ada aksi --}}
                @if($penjualan->status === 'diverifikasi' && $penjualan->tagihan_jasa_id)
                    <div class="text-muted text-center small">
                        <i class="bi bi-check-circle text-success me-1"></i>Tagihan sudah dibuat dari laporan ini.
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
