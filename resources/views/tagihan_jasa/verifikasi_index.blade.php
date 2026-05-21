@extends('layouts.app')
@section('title', 'Verifikasi Tagihan Jasa')

@push('css')
    <link href="{{ URL::asset('build/plugins/datatable/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet" />
    <style>
        @keyframes vjReveal { from { opacity: 0; transform: translateY(16px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes vjSweep { 0% { transform: translateX(-120%) skewX(-18deg); opacity: 0; } 28% { opacity: .35; } 70%, 100% { transform: translateX(220%) skewX(-18deg); opacity: 0; } }
        @keyframes vjFloat { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-5px); } }
        .vj-hero {
            position: relative;
            overflow: hidden;
            border-radius: 18px;
            padding: 26px 28px;
            color: #fff;
            background: linear-gradient(120deg, #102a43 0%, #155c99 58%, #0f766e 100%);
            box-shadow: 0 18px 44px rgba(15, 47, 84, .20);
            animation: vjReveal .45s ease both;
        }
        .vj-hero::before {
            content: "";
            position: absolute;
            inset: 0;
            width: 42%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,.22), transparent);
            animation: vjSweep 4.8s ease-in-out infinite;
        }
        .vj-hero::after {
            content: "";
            position: absolute;
            right: -80px;
            top: -90px;
            width: 360px;
            height: 230px;
            border-radius: 0 0 0 999px;
            border-left: 2px solid rgba(251, 191, 36, .34);
            border-bottom: 2px solid rgba(191, 219, 254, .22);
        }
        .vj-hero > * { position: relative; z-index: 1; }
        .vj-pill {
            border: 1px solid rgba(255,255,255,.24);
            border-radius: 999px;
            padding: 8px 14px;
            background: rgba(15, 23, 42, .24);
            color: rgba(255,255,255,.92);
            font-weight: 800;
        }
        .vj-stat {
            border: 1px solid rgba(148, 163, 184, .22);
            border-radius: 16px;
            background: #fff;
            box-shadow: 0 12px 30px rgba(15, 23, 42, .07);
            animation: vjReveal .5s ease both;
        }
        .vj-stat-icon {
            width: 42px;
            height: 42px;
            border-radius: 13px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #eff6ff;
            color: #2563eb;
            animation: vjFloat 3.2s ease-in-out infinite;
        }
        .vj-card {
            border-radius: 18px !important;
            box-shadow: 0 16px 38px rgba(15, 23, 42, .08) !important;
        }
        .vj-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: 15px 18px;
            border-bottom: 1px solid #bfdbfe;
            background: linear-gradient(90deg, #eff6ff 0%, #f8fbff 58%, #fff 100%);
        }
        .vj-header-title {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .vj-header-icon {
            width: 38px;
            height: 38px;
            border-radius: 11px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            background: #1d4ed8;
            box-shadow: 0 10px 20px rgba(37, 99, 235, .18);
        }
        .vj-table {
            border-collapse: separate;
            border-spacing: 0 10px;
        }
        .vj-table thead th {
            border: 0 !important;
            color: #64748b;
            font-size: .75rem;
            font-weight: 900;
            letter-spacing: .03em;
            text-transform: uppercase;
            background: transparent;
            padding: 12px 16px !important;
        }
        .vj-table tbody tr {
            background: #fff;
            box-shadow: 0 8px 20px rgba(15, 23, 42, .05);
            transition: transform .2s ease, box-shadow .2s ease;
        }
        .vj-table tbody tr:hover {
            transform: translateY(-2px);
            box-shadow: 0 14px 28px rgba(37, 99, 235, .10);
        }
        .vj-table tbody td {
            border-top: 1px solid #e2e8f0;
            border-bottom: 1px solid #e2e8f0;
            padding: 18px 16px !important;
        }
        .vj-table tbody td:first-child {
            border-left: 1px solid #e2e8f0;
            border-radius: 14px 0 0 14px;
        }
        .vj-table tbody td:last-child {
            border-right: 1px solid #e2e8f0;
            border-radius: 0 14px 14px 0;
        }
        .vj-number {
            display: inline-flex;
            width: 30px;
            height: 30px;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            background: #eff6ff;
            color: #1d4ed8;
            font-weight: 900;
        }
        .vj-invoice {
            color: #0f2f54;
            font-weight: 900;
        }
        .vj-amount {
            color: #059669;
            font-size: 1rem;
            font-weight: 900;
        }
        .vj-action {
            border-radius: 12px;
            font-weight: 900;
        }
        div.dataTables_wrapper div.dataTables_length select,
        div.dataTables_wrapper div.dataTables_filter input {
            border-radius: 10px;
            border-color: #cbd5e1;
            padding: 7px 12px;
        }
    </style>
@endpush

@section('content')
    @php
        $totalAntrean = $tagihans->count();
        $totalNominal = $tagihans->sum(fn ($tagihan) => (float) $tagihan->total_tagihan);
        $mitraCount = $tagihans->map(fn ($tagihan) => ($tagihan->mitra ?? $tagihan->mitraLegacy)?->nama_pihak)->filter()->unique()->count();
    @endphp

    <div class="vj-hero mb-4 d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div>
            <div class="small text-white-50 fw-bold text-uppercase mb-1">Antrean Koordinator Jasa</div>
            <h3 class="mb-1 fw-bold text-white">Verifikasi Tagihan Jasa</h3>
            <p class="mb-0 text-white-50">Tinjau tagihan jasa yang menunggu persetujuan Anda sebelum diteruskan ke proses berikutnya.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <span class="vj-pill"><i class="bi bi-list-check me-1"></i>{{ number_format($totalAntrean, 0, ',', '.') }} antrean</span>
            <span class="vj-pill"><i class="bi bi-calendar3 me-1"></i>{{ now()->format('d/m/Y') }}</span>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0 bg-success text-white alert-dismissible fade show shadow-sm">
            <i class="bi bi-check-circle me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger border-0 bg-danger text-white alert-dismissible fade show shadow-sm">
            <i class="bi bi-x-circle me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="vj-stat p-3 h-100 d-flex align-items-center justify-content-between gap-3">
                <div>
                    <div class="small text-muted fw-bold text-uppercase">Menunggu Verifikasi</div>
                    <div class="fs-4 fw-bold text-primary">{{ number_format($totalAntrean, 0, ',', '.') }}</div>
                </div>
                <span class="vj-stat-icon"><i class="bi bi-shield-exclamation fs-5"></i></span>
            </div>
        </div>
        <div class="col-md-4">
            <div class="vj-stat p-3 h-100 d-flex align-items-center justify-content-between gap-3">
                <div>
                    <div class="small text-muted fw-bold text-uppercase">Total Nominal</div>
                    <div class="fs-4 fw-bold text-success">Rp {{ number_format($totalNominal, 0, ',', '.') }}</div>
                </div>
                <span class="vj-stat-icon"><i class="bi bi-cash-coin fs-5"></i></span>
            </div>
        </div>
        <div class="col-md-4">
            <div class="vj-stat p-3 h-100 d-flex align-items-center justify-content-between gap-3">
                <div>
                    <div class="small text-muted fw-bold text-uppercase">Mitra Terkait</div>
                    <div class="fs-4 fw-bold text-info">{{ number_format($mitraCount, 0, ',', '.') }}</div>
                </div>
                <span class="vj-stat-icon"><i class="bi bi-buildings fs-5"></i></span>
            </div>
        </div>
    </div>

    <div class="card border-0 vj-card overflow-hidden">
        <div class="vj-header">
            <div class="vj-header-title">
                <span class="vj-header-icon"><i class="bi bi-check2-square"></i></span>
                <div>
                    <h6 class="mb-0 fw-bold text-primary">Daftar Verifikasi Tagihan Jasa</h6>
                    <small class="text-muted fw-bold">Tagihan jasa yang menunggu proses persetujuan.</small>
                </div>
            </div>
        </div>
        <div class="card-body p-4">
            <div class="table-responsive">
                <table id="tableTagihanJasa" class="table align-middle w-100 vj-table">
                    <thead>
                        <tr>
                            <th width="5%" class="text-center">No</th>
                            <th width="22%">No. Tagihan</th>
                            <th width="18%">Mitra</th>
                            <th width="18%">Total & Tgl Tagihan</th>
                            <th width="12%">Status</th>
                            <th width="20%" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($tagihans as $tagihan)
                            @php($mitraTagihan = $tagihan->mitra ?? $tagihan->mitraLegacy)
                            <tr>
                                <td class="text-center"><span class="vj-number">{{ $loop->iteration }}</span></td>
                                <td>
                                    <span class="vj-invoice">{{ $tagihan->nomor_tagihan }}</span><br>
                                    @if($tagihan->nomor_kontrak)
                                        <small class="text-muted"><i class="bi bi-file-earmark-text me-1"></i>{{ $tagihan->nomor_kontrak }}</small>
                                    @endif
                                </td>
                                <td>
                                    <span class="fw-bold text-slate">{{ $mitraTagihan->nama_pihak ?? 'N/A' }}</span>
                                </td>
                                <td>
                                    <span class="vj-amount">Rp {{ number_format($tagihan->total_tagihan, 0, ',', '.') }}</span><br>
                                    <small class="text-muted"><i class="bi bi-calendar-event me-1"></i>{{ \Carbon\Carbon::parse($tagihan->tanggal_tagihan)->format('d M Y') }}</small>
                                </td>
                                <td>
                                    <span class="badge {{ match($tagihan->status) {
                                        'PUBLISHED', 'LUNAS' => 'bg-success',
                                        'DRAFT' => 'bg-secondary',
                                        'DITOLAK' => 'bg-danger',
                                        default => 'bg-warning text-dark',
                                    } }}">{{ str_replace('_', ' ', $tagihan->status) }}</span>
                                </td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-2">
                                        <a href="{{ route('verifikasi-tagihan-jasa.show', $tagihan->id) }}" class="btn btn-sm btn-primary vj-action shadow-sm" title="Proses Verifikasi">
                                            <i class="bi bi-check2-square me-1"></i> Proses Verifikasi
                                        </a>
                                        @if(in_array($tagihan->status, ['PUBLISHED', 'LUNAS', 'VERIFIKASI_KABANDARA']))
                                            <a href="{{ route('tagihan-jasa.pdf', $tagihan->id) }}" target="_blank" class="btn btn-sm btn-light text-danger border shadow-sm vj-action" title="Cetak PDF">
                                                <i class="bi bi-file-pdf"></i>
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script src="{{ URL::asset('build/plugins/datatable/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/datatable/js/dataTables.bootstrap5.min.js') }}"></script>
    <script>
        $(document).ready(function() {
            $('#tableTagihanJasa').DataTable({
                language: {
                    url: "https://cdn.datatables.net/plug-ins/1.13.6/i18n/id.json"
                }
            });
        });
    </script>
@endpush
