@extends('layouts.app')
@section('title') Verifikasi Tagihan Perjaldin @endsection
@push('css')
    <link href="{{ URL::asset('build/plugins/datatable/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet" />
    <style>
        /* Import JetBrains Mono for clean monospaced digits */
        @import url('https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600;700&display=swap');

        /* Color Tokens & Variables */
        :root {
            --primary-gradient: linear-gradient(135deg, #2563eb, #1d4ed8);
            --dark-gradient: linear-gradient(135deg, #1e293b, #0f172a);
            --light-gradient: linear-gradient(135deg, #f8fafc, #f1f5f9);
            --hover-row-bg: #f8fafc;
            --soft-shadow: 0 10px 30px rgba(15, 23, 42, 0.03);
            --glow-shadow: 0 4px 15px rgba(37, 99, 235, 0.18);
        }

        /* elegant custom scrollbar */
        .table-responsive::-webkit-scrollbar {
            height: 6px;
        }
        .table-responsive::-webkit-scrollbar-track {
            background: #f1f5f9;
        }
        .table-responsive::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }

        /* Modern Table Card Wrapper */
        .card-modern-table {
            border-radius: 16px !important;
            border: 1px solid rgba(226, 232, 240, 0.8) !important;
            box-shadow: var(--soft-shadow) !important;
            overflow: hidden !important;
            background: #ffffff !important;
            margin-bottom: 2rem !important;
        }
        .card-modern-table .card-header {
            background-color: #ffffff !important;
            border-bottom: 1px solid #f1f5f9 !important;
            padding: 24px 28px 20px 28px !important;
        }

        /* Segmented Capsule Tabs */
        .modern-tabs-container {
            background: #f1f5f9;
            padding: 5px;
            border-radius: 30px;
            display: inline-flex;
            gap: 2px;
            border: 1px solid #e2e8f0;
        }
        .modern-tab-btn {
            border: none;
            background: transparent;
            padding: 8px 20px;
            border-radius: 20px;
            color: #64748b;
            font-size: 0.875rem;
            font-weight: 550;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .modern-tab-btn.active {
            background: #ffffff;
            color: #1e293b;
            font-weight: 700;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }
        .badge-count {
            background: #2563eb;
            color: #ffffff;
            font-size: 0.72rem;
            font-weight: 700;
            padding: 2px 7px;
            border-radius: 10px;
            transition: all 0.2s ease;
        }
        .modern-tab-btn.active .badge-count {
            background: #2563eb;
            color: #ffffff;
        }

        /* Elegant Table Styling */
        .modern-table {
            border-collapse: separate !important;
            border-spacing: 0 !important;
            width: 100% !important;
        }
        .modern-table thead {
            background: #f8fafc !important;
        }
        .modern-table thead th {
            background: #f8fafc !important;
            color: #64748b !important;
            font-size: 0.75rem !important;
            font-weight: 700 !important;
            letter-spacing: 0.06em !important;
            text-transform: uppercase !important;
            padding: 16px 20px !important;
            border-bottom: 2px solid #e2e8f0 !important;
            border-top: none !important;
            border-left: none !important;
            border-right: none !important;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1) !important;
        }
        .modern-table thead th:first-child {
            padding-left: 28px !important;
            border-top-left-radius: 12px !important;
        }
        .modern-table thead th:last-child {
            padding-right: 28px !important;
            border-top-right-radius: 12px !important;
        }
        .modern-table thead th:hover {
            background: #f1f5f9 !important;
            color: #2563eb !important;
        }
        .modern-table thead th i {
            font-size: 0.85rem !important;
            margin-right: 6px !important;
            vertical-align: -1px !important;
            color: #94a3b8 !important;
            transition: color 0.25s ease !important;
        }
        .modern-table thead th:hover i {
            color: #2563eb !important;
        }

        /* Animated Hover Row */
        .doc-row {
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1) !important;
            position: relative;
            background-color: #ffffff !important;
        }
        .doc-row:hover {
            background-color: var(--hover-row-bg) !important;
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(15, 23, 42, 0.04) !important;
            z-index: 2;
        }
        .doc-row td {
            padding: 20px 20px !important;
            border-bottom: 1px solid #f1f5f9 !important;
            color: #334155 !important;
            font-size: 0.9rem;
            vertical-align: middle !important;
        }
        .doc-row td:first-child {
            padding-left: 28px !important;
            position: relative;
        }
        .doc-row td:last-child {
            padding-right: 28px !important;
        }

        /* Left visual accent indicator on hover */
        .doc-row td:first-child::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 0px;
            background: linear-gradient(to bottom, #3b82f6, #1d4ed8);
            transition: width 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .doc-row:hover td:first-child::before {
            width: 4px;
        }

        /* Typography spacing & format */
        .font-monospace-spk {
            font-family: 'JetBrains Mono', 'Fira Code', 'Courier New', monospace !important;
            font-size: 0.72rem !important;
            letter-spacing: -0.01em !important;
            padding: 3px 8px !important;
            border-radius: 6px !important;
            background-color: rgba(37, 99, 235, 0.06) !important;
            color: #2563eb !important;
            border: 1px solid rgba(37, 99, 235, 0.12) !important;
            display: inline-block;
        }

        /* Custom Soft UI Badges Override locally */
        .doc-row .badge, .table .badge {
            font-weight: 700 !important;
            font-size: 0.75rem !important;
            letter-spacing: 0.02em;
            padding: 5px 12px !important;
            border-radius: 20px !important;
            box-shadow: none !important;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .doc-row .badge.bg-primary {
            background-color: rgba(37, 99, 235, 0.09) !important;
            color: #1d4ed8 !important;
            border: 1px solid rgba(37, 99, 235, 0.18) !important;
        }
        .doc-row .badge.bg-light {
            background-color: rgba(241, 245, 249, 0.8) !important;
            color: #475569 !important;
            border: 1px solid rgba(226, 232, 240, 1) !important;
        }

        /* Monospace Rupiah Formatting */
        .rp-nominal {
            font-family: 'JetBrains Mono', 'Fira Code', 'Courier New', monospace !important;
            font-size: 0.95rem !important;
            font-weight: 700 !important;
            color: #1e293b !important;
            letter-spacing: -0.02em !important;
        }
        .rp-nominal.text-success {
            color: #10b981 !important;
        }

        /* Premium Buttons */
        .btn-verify-modern {
            background: var(--primary-gradient) !important;
            border: none !important;
            color: #ffffff !important;
            font-weight: 600 !important;
            font-size: 0.82rem !important;
            padding: 8px 18px !important;
            border-radius: 30px !important;
            box-shadow: var(--glow-shadow) !important;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1) !important;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none !important;
        }
        .btn-verify-modern:hover {
            transform: translateY(-2px) !important;
            box-shadow: 0 6px 20px rgba(37, 99, 235, 0.35) !important;
            color: #ffffff !important;
        }
        .btn-detail-modern {
            border: 1px solid #cbd5e1 !important;
            background: #ffffff !important;
            color: #475569 !important;
            font-weight: 600 !important;
            font-size: 0.82rem !important;
            padding: 8px 18px !important;
            border-radius: 30px !important;
            transition: all 0.25s ease !important;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none !important;
        }
        .btn-detail-modern:hover {
            background: #f8fafc !important;
            color: #0f172a !important;
            border-color: #94a3b8 !important;
            transform: translateY(-1px) !important;
        }

        /* Modern Filter Form Inputs */
        .modern-filter-input {
            border-radius: 30px !important;
            border: 1px solid #cbd5e1 !important;
            padding: 7px 16px !important;
            font-size: 0.85rem !important;
            transition: all 0.2s ease !important;
            color: #1e293b !important;
            background-color: #f8fafc !important;
        }
        .modern-filter-input:focus {
            border-color: #2563eb !important;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12) !important;
            background-color: #ffffff !important;
            outline: none !important;
        }
        .btn-modern-search {
            border-radius: 30px !important;
            padding: 7px 14px !important;
            background: var(--primary-gradient) !important;
            border: none !important;
            color: #ffffff !important;
            font-weight: 600 !important;
            font-size: 0.85rem !important;
            box-shadow: var(--glow-shadow) !important;
            transition: all 0.2s ease !important;
        }
        .btn-modern-search:hover {
            transform: translateY(-1px) !important;
            box-shadow: 0 6px 16px rgba(37, 99, 235, 0.3) !important;
        }
        .btn-modern-reset {
            border-radius: 30px !important;
            padding: 7px 14px !important;
            border: 1px solid #e2e8f0 !important;
            background: #ffffff !important;
            color: #64748b !important;
            font-size: 0.85rem !important;
            transition: all 0.2s ease !important;
            font-weight: 550 !important;
            text-decoration: none !important;
            display: inline-flex;
            align-items: center;
        }
        .btn-modern-reset:hover {
            background: #f1f5f9 !important;
            color: #334155 !important;
        }

        /* Summary Cards Interactive Styling */
        .card[style*="border-left"] {
            border-radius: 14px !important;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
            border-top: none !important;
            border-right: none !important;
            border-bottom: none !important;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.02) !important;
            background: #ffffff !important;
        }
        .card[style*="border-left"]:hover {
            transform: translateY(-4px) !important;
            box-shadow: 0 12px 25px rgba(15, 23, 42, 0.07) !important;
        }
        .card[style*="border-left"] .rounded-circle {
            transition: all 0.3s ease !important;
        }
        .card[style*="border-left"]:hover .rounded-circle {
            transform: scale(1.1) rotate(3deg) !important;
        }

        /* Page Load Animation */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(15px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .animate-fade-in-up {
            animation: fadeInUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) both;
        }

        /* Animated Title Header */
        .page-header-container {
            transition: all 0.3s ease;
        }
        .page-title-gradient {
            background: linear-gradient(135deg, #0f172a 30%, #2563eb 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            display: inline-block;
        }
        .title-icon-box {
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1) !important;
        }
        .page-header-container:hover .title-icon-box {
            background: #2563eb !important;
            color: #ffffff !important;
            transform: translateY(-2px) scale(1.05);
            box-shadow: 0 8px 20px rgba(37, 99, 235, 0.25) !important;
        }
        .title-icon-animate {
            animation: floatTitleIcon 3s infinite alternate ease-in-out;
            display: inline-block;
        }
        @keyframes floatTitleIcon {
            0% { transform: translateY(0) rotate(0deg); }
            100% { transform: translateY(-3px) rotate(8deg); }
        }
    </style>
@endpush
@section('content')
{{-- Animated Title Header --}}
<div class="page-header-container d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4 animate-fade-in-up">
    <div class="d-flex align-items-center gap-3">
        <div class="title-icon-box shadow-sm d-flex align-items-center justify-content-center" style="width: 48px; height: 48px; background: rgba(37, 99, 235, 0.08); border: 1px solid rgba(37, 99, 235, 0.15); border-radius: 12px; color: #2563eb; font-size: 1.35rem;">
            <i class="bi bi-shield-check title-icon-animate"></i>
        </div>
        <div>
            <h3 class="fw-bold mb-1 text-dark page-title-gradient" style="font-size: 1.55rem; letter-spacing: -0.02em;">Verifikasi Tagihan Perjaldin</h3>
            <div class="d-flex align-items-center gap-2 text-muted small">
                <a href="javascript:;" class="text-secondary text-decoration-none"><i class="bi bi-house-door"></i></a>
                <span class="opacity-50">/</span>
                <span class="text-secondary fw-medium">Daftar Pengajuan Perjalanan Dinas</span>
            </div>
        </div>
    </div>
</div>

{{-- Flash --}}
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show shadow-sm border-0 mb-3 animate-fade-in-up">
        <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show shadow-sm border-0 mb-3 animate-fade-in-up">
        <i class="bi bi-x-circle-fill me-2"></i>{{ $errors->first() }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

{{-- Summary Cards --}}
@include('verifikasi_perjaldin.partials.list-summary', [
    'tagihans' => $tagihans,
    'pendingStatuses' => $pendingStatuses,
    'revisiStatuses'  => $revisiStatuses,
    'selesaiStatuses' => $selesaiStatuses,
])

{{-- Role Info Banner --}}
<div class="alert alert-light border shadow-sm d-flex align-items-center gap-3 mb-4 py-2 animate-fade-in-up" style="background-color: #ffffff !important; border: 1px solid rgba(226, 232, 240, 0.8) !important; border-radius: 12px !important; box-shadow: 0 4px 12px rgba(15, 23, 42, 0.01) !important;">
    <i class="bi bi-person-badge fs-4 text-primary"></i>
    <div class="small text-secondary">
        Anda login sebagai <strong class="text-dark">{{ $userRole }}</strong>.
        Dokumen yang ditampilkan adalah yang relevan untuk tahap verifikasi Anda.
    </div>
</div>

{{-- Tabs --}}
<div class="card card-modern-table border-0 shadow-sm animate-fade-in-up">
    <div class="card-header bg-white border-bottom px-4 pt-3 pb-3">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div class="modern-tabs-container mb-2 mb-md-0">
                <button class="modern-tab-btn active" id="tab-perlu" onclick="switchTab('perlu')">
                    <i class="bi bi-hourglass-split me-1"></i>Perlu Aksi Saya
                    @if($tagihansPerlu->count() > 0)
                        <span class="badge-count">{{ $tagihansPerlu->count() }}</span>
                    @endif
                </button>
                <button class="modern-tab-btn" id="tab-riwayat" onclick="switchTab('riwayat')">
                    <i class="bi bi-clock-history me-1"></i>Riwayat Verifikasi
                </button>
            </div>
            {{-- Filter --}}
            <form method="GET" class="d-flex gap-2 align-items-center flex-wrap">
                <input type="text" name="search" class="modern-filter-input" style="width:180px;"
                       placeholder="Cari nomor / uraian..." value="{{ request('search') }}">
                <input type="month" name="periode" class="modern-filter-input" style="width:140px;"
                       value="{{ request('periode') }}">
                <button type="submit" class="btn btn-sm btn-modern-search"><i class="bi bi-search"></i></button>
                @if(request()->hasAny(['search','periode']))
                    <a href="{{ request()->url() }}" class="btn-modern-reset"><i class="bi bi-x"></i> Reset</a>
                @endif
            </form>
        </div>
    </div>

    {{-- Tab: Perlu Aksi --}}
    <div id="pane-perlu">
        <div class="card-body p-0">
            @if($tagihansPerlu->isEmpty())
                <div class="text-center text-muted py-5 animate-fade-in-up">
                    <div class="mb-3 d-inline-flex align-items-center justify-content-center rounded-circle" style="width: 80px; height: 80px; background: #f8fafc; border: 1px solid #f1f5f9;">
                        <i class="bi bi-inbox display-6 text-secondary"></i>
                    </div>
                    <h6 class="fw-bold text-dark mb-1">Tidak ada dokumen yang perlu aksi Anda saat ini</h6>
                    <p class="small text-muted">Dokumen akan muncul di sini ketika diajukan oleh Operator Perjaldin.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table modern-table table-hover align-middle mb-0" id="tablePerlu">
                        <thead>
                            <tr>
                                <th class="ps-4" style="width:60px;"><i class="bi bi-hash"></i>No</th>
                                <th><i class="bi bi-file-earmark-text"></i>Dokumen</th>
                                <th><i class="bi bi-people"></i>Peserta</th>
                                <th><i class="bi bi-cash-stack"></i>Total Bruto</th>
                                <th><i class="bi bi-calendar-event"></i>Diajukan</th>
                                <th><i class="bi bi-info-circle"></i>Status</th>
                                <th class="text-center" style="width:160px;"><i class="bi bi-lightning-charge"></i>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($tagihansPerlu as $i => $tagihan)
                                <tr class="doc-row">
                                    <td class="ps-4 text-muted small">{{ $i + 1 }}</td>
                                    <td>
                                        <div class="fw-semibold text-dark">{{ $tagihan->nomor_tagihan }}</div>
                                        <div class="text-muted small text-truncate" style="max-width:260px;">{{ $tagihan->deskripsi }}</div>
                                        @if($tagihan->periode_bulan && $tagihan->periode_tahun)
                                            <span class="badge bg-light text-secondary border small mt-1">
                                                <i class="bi bi-calendar3 me-1"></i>
                                                {{ \Carbon\Carbon::createFromDate($tagihan->periode_tahun, $tagihan->periode_bulan, 1)->format('M Y') }}
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill">
                                            {{ $tagihan->detailPerjaldin->count() }} orang
                                        </span>
                                        <div class="mt-1">
                                            @foreach($tagihan->detailPerjaldin->take(2) as $dp)
                                                <span class="badge bg-light text-dark border small">{{ $dp->pegawai?->nama_lengkap ?? $dp->nama_pegawai ?? '-' }}</span>
                                            @endforeach
                                            @if($tagihan->detailPerjaldin->count() > 2)
                                                <span class="text-muted small">+{{ $tagihan->detailPerjaldin->count() - 2 }} lainnya</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="rp-nominal text-success">Rp {{ number_format($tagihan->total_bruto, 0, ',', '.') }}</td>
                                    <td>
                                        @php $submitLog = $tagihan->logs->firstWhere('aksi', 'SUBMIT'); @endphp
                                        <span class="small fw-medium text-dark">{{ $submitLog ? $submitLog->created_at->format('d M Y') : '-' }}</span>
                                        @if($submitLog)
                                            <div class="text-muted font-monospace" style="font-size:.70rem;">{{ $submitLog->created_at->format('H:i') }}</div>
                                        @endif
                                    </td>
                                    <td>@include('verifikasi_perjaldin.partials.status-badge', ['status' => $tagihan->status])</td>
                                    <td class="text-center">
                                        <a href="{{ route($detailRoute, $tagihan->id) }}" class="btn-verify-modern">
                                            <i class="bi bi-shield-check"></i>Verifikasi
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    {{-- Tab: Riwayat --}}
    <div id="pane-riwayat" style="display:none;">
        <div class="card-body p-0">
            @if($tagihansRiwayat->isEmpty())
                <div class="text-center text-muted py-5 animate-fade-in-up">
                    <div class="mb-3 d-inline-flex align-items-center justify-content-center rounded-circle" style="width: 80px; height: 80px; background: #f8fafc; border: 1px solid #f1f5f9;">
                        <i class="bi bi-clock-history display-6 text-secondary"></i>
                    </div>
                    <h6 class="fw-bold text-dark mb-1">Belum ada riwayat verifikasi</h6>
                    <p class="small text-muted">Belum ada dokumen yang pernah Anda proses.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table modern-table table-hover align-middle mb-0" id="tableRiwayat">
                        <thead>
                            <tr>
                                <th class="ps-4" style="width:60px;"><i class="bi bi-hash"></i>No</th>
                                <th><i class="bi bi-file-earmark-text"></i>Dokumen</th>
                                <th><i class="bi bi-people"></i>Peserta</th>
                                <th><i class="bi bi-cash-stack"></i>Total Bruto</th>
                                <th><i class="bi bi-info-circle"></i>Status</th>
                                <th class="text-center" style="width:120px;"><i class="bi bi-lightning-charge"></i>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($tagihansRiwayat as $i => $tagihan)
                                <tr class="doc-row">
                                    <td class="ps-4 text-muted small">{{ $i + 1 }}</td>
                                    <td>
                                        <div class="fw-semibold text-dark">{{ $tagihan->nomor_tagihan }}</div>
                                        <div class="text-muted small text-truncate" style="max-width:260px;">{{ $tagihan->deskripsi }}</div>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border rounded-pill">{{ $tagihan->detailPerjaldin->count() }} orang</span>
                                    </td>
                                    <td class="rp-nominal">Rp {{ number_format($tagihan->total_bruto, 0, ',', '.') }}</td>
                                    <td>@include('verifikasi_perjaldin.partials.status-badge', ['status' => $tagihan->status])</td>
                                    <td class="text-center">
                                        <a href="{{ route($detailRoute, $tagihan->id) }}" class="btn-detail-modern">
                                            <i class="bi bi-eye"></i>Detail
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('script')
<script src="{{ URL::asset('build/plugins/datatable/js/jquery.dataTables.min.js') }}"></script>
<script src="{{ URL::asset('build/plugins/datatable/js/dataTables.bootstrap5.min.js') }}"></script>
<script>
function switchTab(tab) {
    document.getElementById('pane-perlu').style.display = tab === 'perlu' ? '' : 'none';
    document.getElementById('pane-riwayat').style.display = tab === 'riwayat' ? '' : 'none';
    document.getElementById('tab-perlu').classList.toggle('active', tab === 'perlu');
    document.getElementById('tab-riwayat').classList.toggle('active', tab === 'riwayat');
}
$(document).ready(function() {
    $('#tablePerlu').DataTable({ paging: false, searching: false, info: false, order: [] });
    $('#tableRiwayat').DataTable({ paging: false, searching: false, info: false, order: [] });
});
</script>
@endpush
