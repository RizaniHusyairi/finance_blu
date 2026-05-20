@extends('layouts.app')
@section('title')
    Verifikasi Perjalanan Dinas
@endsection
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

        /* Modal Premium Overrides */
        .modal-content-modern {
            border-radius: 20px !important;
            border: 1px solid rgba(226, 232, 240, 0.8) !important;
            box-shadow: 0 15px 50px rgba(15, 23, 42, 0.1) !important;
            overflow: hidden;
        }
        .modal-header-modern {
            background-color: #ffffff !important;
            border-bottom: 1px solid #f1f5f9 !important;
            padding: 20px 28px !important;
        }
        .modal-body-modern {
            padding: 28px !important;
            color: #334155 !important;
        }
        .modal-footer-modern {
            background-color: #f8fafc !important;
            border-top: 1px solid #f1f5f9 !important;
            padding: 16px 28px !important;
        }
        .form-control-modern {
            border-radius: 12px !important;
            border: 1px solid #cbd5e1 !important;
            padding: 12px 16px !important;
            font-size: 0.9rem !important;
            transition: all 0.2s ease !important;
        }
        .form-control-modern:focus {
            border-color: #ef4444 !important;
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.12) !important;
            outline: none !important;
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
                <h3 class="fw-bold mb-1 text-dark page-title-gradient" style="font-size: 1.55rem; letter-spacing: -0.02em;">Verifikasi Perjalanan Dinas</h3>
                <div class="d-flex align-items-center gap-2 text-muted small">
                    <a href="javascript:;" class="text-secondary text-decoration-none"><i class="bi bi-house-door"></i></a>
                    <span class="opacity-50">/</span>
                    <span class="text-secondary fw-medium">Daftar Pengajuan Perjaldin</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Flash Notifications --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm border-0 mb-4 animate-fade-in-up">
            <div class="d-flex align-items-center">
                <i class="bi bi-check-circle-fill me-2 fs-5"></i>
                <div>{{ session('success') }}</div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show shadow-sm border-0 mb-4 animate-fade-in-up">
            <div class="d-flex align-items-start">
                <i class="bi bi-x-circle-fill me-2 fs-5 mt-0.5"></i>
                <div>
                    <ul class="mb-0 ps-3">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- Table Card --}}
    <div class="card card-modern-table border-0 shadow-sm animate-fade-in-up">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table id="example" class="table modern-table table-hover align-middle mb-0" style="width:100%">
                    <thead>
                        <tr>
                            <th class="ps-4" style="width:50px;">NO</th>
                            <th><i class="bi bi-person"></i>Nama (NIP)</th>
                            <th><i class="bi bi-file-earmark-text"></i>No SPT</th>
                            <th><i class="bi bi-file-earmark-medical"></i>No SPPD</th>
                            <th><i class="bi bi-geo-alt"></i>Tujuan</th>
                            <th><i class="bi bi-calendar-event"></i>Keberangkatan</th>
                            <th><i class="bi bi-clock"></i>Lama</th>
                            <th><i class="bi bi-calendar-check"></i>Tgl Pengajuan</th>
                            <th class="text-center" style="width:260px;"><i class="bi bi-lightning-charge"></i>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($pejabats as $index => $pejabat)
                            <tr class="doc-row">
                                <td class="ps-4 text-muted small fw-medium">{{ $index + 1 }}</td>
                                <td>
                                    <div class="fw-semibold text-dark">{{ $pejabat->nama_pejabat }}</div>
                                    <small class="text-muted font-monospace-spk" style="background-color: rgba(100, 116, 139, 0.06) !important; color: #475569 !important; border: 1px solid rgba(100, 116, 139, 0.12) !important;">NIP. {{ $pejabat->nip ?? '-' }}</small>
                                </td>
                                <td>
                                    <span class="font-monospace-spk">{{ $pejabat->no_spt }}</span>
                                </td>
                                <td>
                                    <span class="font-monospace-spk" style="background-color: rgba(6, 182, 212, 0.06) !important; color: #0891b2 !important; border: 1px solid rgba(6, 182, 212, 0.12) !important;">{{ $pejabat->no_sppd }}</span>
                                </td>
                                <td>
                                    <div class="fw-medium text-truncate" style="max-width: 150px;" title="{{ $pejabat->tujuan }}"><i class="bi bi-geo-alt text-primary me-1"></i>{{ $pejabat->tujuan }}</div>
                                </td>
                                <td>
                                    <div class="fw-semibold small"><i class="bi bi-calendar-check text-secondary me-1"></i>{{ \Carbon\Carbon::parse($pejabat->tanggal_berangkat)->format('d M Y') }}</div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-secondary border small">
                                        <i class="bi bi-hourglass-split me-1"></i>{{ $pejabat->lama_perjalanan_dinas }} Hari
                                    </span>
                                </td>
                                <td>
                                    <div class="small fw-semibold">{{ \Carbon\Carbon::parse($pejabat->updated_at)->format('d M Y') }}</div>
                                    <div class="text-muted" style="font-size: .7rem;"><i class="bi bi-clock me-1"></i>{{ \Carbon\Carbon::parse($pejabat->updated_at)->format('H:i') }}</div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-2 justify-content-center">
                                        <!-- Lihat Detail -->
                                        <a href="{{ route('perjaldin-blu.show', $pejabat->pejabat_id) }}" class="btn-detail-modern py-1.5 px-3" title="Lihat Detail" style="font-size: 0.8rem; padding: 6px 14px !important;">
                                            <i class="bi bi-eye"></i> Detail
                                        </a>

                                        <!-- Approve -->
                                        <form action="{{ route('perjaldin-blu.approve', $pejabat->pejabat_id) }}" method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menyetujui data Perjaldin ini?');">
                                            @csrf
                                            <button type="submit" class="btn-verify-modern py-1.5 px-3" title="Setujui" style="background: linear-gradient(135deg, #10b981, #059669) !important; box-shadow: 0 4px 15px rgba(16, 185, 129, 0.18) !important; font-size: 0.8rem; padding: 6px 14px !important;">
                                                <i class="bi bi-check-circle"></i> Setujui
                                            </button>
                                        </form>

                                        <!-- Reject Trigger Modal -->
                                        <button type="button" class="btn-detail-modern py-1.5 px-3" title="Tolak" data-bs-toggle="modal" data-bs-target="#rejectModal{{ $pejabat->pejabat_id }}" style="color: #ef4444 !important; border-color: rgba(239, 68, 68, 0.2) !important; font-size: 0.8rem; padding: 6px 14px !important;">
                                            <i class="bi bi-x-circle"></i> Tolak
                                        </button>
                                    </div>
                                    
                                    <!-- Reject Modal -->
                                    <div class="modal fade" id="rejectModal{{ $pejabat->pejabat_id }}" tabindex="-1" aria-labelledby="rejectModalLabel{{ $pejabat->pejabat_id }}" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content modal-content-modern border-0">
                                                <div class="modal-header modal-header-modern">
                                                    <h5 class="modal-title fw-bold text-dark" id="rejectModalLabel{{ $pejabat->pejabat_id }}"><i class="bi bi-x-circle text-danger me-2"></i>Penolakan Perjaldin</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <form action="{{ route('perjaldin-blu.reject', $pejabat->pejabat_id) }}" method="POST">
                                                    @csrf
                                                    <div class="modal-body modal-body-modern">
                                                        <p>Anda akan menolak pengajuan perjalanan dinas atas nama <strong class="text-dark">{{ $pejabat->nama_pejabat }}</strong>.</p>
                                                        <div class="mb-0">
                                                            <label for="alasan_penolakan" class="form-label fw-semibold text-secondary">Silakan berikan Alasan Penolakan <span class="text-danger">*</span></label>
                                                            <textarea class="form-control form-control-modern" name="alasan_penolakan" rows="3" required placeholder="Contoh: Dokumen SPT tidak valid atau tanda tangan kurang lengkap..."></textarea>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer modal-footer-modern">
                                                        <button type="button" class="btn-detail-modern" data-bs-dismiss="modal">Batal</button>
                                                        <button type="submit" class="btn-verify-modern" style="background: linear-gradient(135deg, #ef4444, #dc2626) !important; box-shadow: 0 4px 15px rgba(239, 68, 68, 0.18) !important;">Tolak Pengajuan</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- End Modal -->
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
            $('#example').DataTable();
        });
    </script>
@endpush
