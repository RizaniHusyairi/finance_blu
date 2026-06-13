@extends('layouts.app')
@section('title', 'Review Kontrak PPK')

@push('css')
<style>
    /* Premium UI Styles */
    :root {
        --primary: #4f46e5;
        --primary-light: #818cf8;
        --secondary: #ec4899;
        --success: #10b981;
        --warning: #f59e0b;
        --danger: #ef4444;
        --dark: #0f172a;
        --gray: #64748b;
        --light: #f8fafc;
        --glass-bg: rgba(255, 255, 255, 0.85);
        --glass-border: rgba(255, 255, 255, 0.3);
    }
    
    .review-hero {
        background: linear-gradient(135deg, var(--dark) 0%, #1e293b 100%);
        border-radius: 20px;
        padding: 40px;
        color: white;
        margin-bottom: 30px;
        position: relative;
        overflow: hidden;
        box-shadow: 0 20px 40px -10px rgba(15, 23, 42, 0.4);
    }
    .review-hero::after {
        content: '';
        position: absolute;
        top: -50%; right: -10%;
        width: 400px; height: 400px;
        background: radial-gradient(circle, var(--primary) 0%, transparent 70%);
        opacity: 0.3;
        filter: blur(50px);
    }

    .status-badge-lg {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: rgba(245, 158, 11, 0.2);
        color: #fbbf24;
        border: 1px solid rgba(245, 158, 11, 0.3);
        padding: 8px 20px;
        border-radius: 30px;
        font-weight: 700;
        letter-spacing: 1px;
        font-size: 14px;
        backdrop-filter: blur(10px);
        margin-bottom: 20px;
        animation: pulseStatus 2s infinite;
    }

    .status-badge-lg.approved {
        background: rgba(16, 185, 129, 0.18);
        color: #86efac;
        border-color: rgba(16, 185, 129, 0.35);
        animation: none;
    }

    .status-badge-lg.neutral {
        background: rgba(148, 163, 184, 0.18);
        color: #e2e8f0;
        border-color: rgba(148, 163, 184, 0.35);
        animation: none;
    }

    @keyframes pulseStatus {
        0% { box-shadow: 0 0 0 0 rgba(245, 158, 11, 0.4); }
        70% { box-shadow: 0 0 0 10px rgba(245, 158, 11, 0); }
        100% { box-shadow: 0 0 0 0 rgba(245, 158, 11, 0); }
    }

    .contract-title {
        font-size: 28px;
        font-weight: 800;
        margin-bottom: 10px;
        line-height: 1.3;
        z-index: 2;
        position: relative;
        color: #ffffff;
    }

    .contract-meta {
        display: flex;
        gap: 20px;
        color: #f8fafc;
        font-size: 15px;
        z-index: 2;
        position: relative;
        flex-wrap: wrap;
    }
    
    .meta-item i { color: var(--primary-light); margin-right: 6px; }

    .card-glass {
        background: var(--glass-bg);
        backdrop-filter: blur(16px);
        border: 1px solid var(--glass-border);
        border-radius: 20px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.05);
        padding: 30px;
        margin-bottom: 30px;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    .card-glass:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 35px rgba(0,0,0,0.08);
    }

    .section-title {
        font-size: 18px;
        font-weight: 800;
        color: var(--dark);
        margin-bottom: 24px;
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .section-icon {
        width: 40px; height: 40px;
        background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
        color: white;
        border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        font-size: 18px;
        box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
    }

    .info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 24px;
    }
    .info-box {
        background: #f8fafc;
        padding: 16px;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        transition: all 0.3s;
    }
    .info-box:hover {
        border-color: var(--primary-light);
        background: white;
    }
    .info-label {
        font-size: 12px;
        text-transform: uppercase;
        color: var(--gray);
        font-weight: 700;
        margin-bottom: 6px;
        letter-spacing: 0.5px;
    }
    .info-value {
        font-size: 15px;
        color: var(--dark);
        font-weight: 600;
        word-break: break-word;
        overflow-wrap: break-word;
    }

    .budget-progress {
        margin-top: 20px;
        background: #f1f5f9;
        height: 12px;
        border-radius: 6px;
        overflow: hidden;
        display: flex;
    }
    .budget-used { background: var(--gray); transition: width 1s ease-in-out; }
    .budget-current { background: var(--warning); transition: width 1s ease-in-out; }
    .budget-remaining { background: var(--success); transition: width 1s ease-in-out; }

    .budget-legends {
        display: flex; gap: 20px; margin-top: 12px; font-size: 13px; font-weight: 600;
    }
    .legend-dot {
        width: 10px; height: 10px; border-radius: 50%; display: inline-block; margin-right: 6px;
    }

    .termin-table th {
        background: #f8fafc;
        font-weight: 700;
        color: var(--gray);
        text-transform: uppercase;
        font-size: 12px;
        letter-spacing: 0.5px;
        padding: 16px;
        border-bottom: 2px solid #e2e8f0;
    }
    .termin-table td {
        padding: 16px;
        vertical-align: middle;
        font-weight: 500;
        color: var(--dark);
    }
    .termin-table tr:hover { background: #f8fafc; }

    .action-bar {
        position: sticky;
        bottom: 30px;
        background: var(--glass-bg);
        backdrop-filter: blur(20px);
        border: 1px solid var(--glass-border);
        padding: 20px 30px;
        border-radius: 24px;
        box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        display: flex;
        justify-content: space-between;
        align-items: center;
        z-index: 100;
    }

    .btn-approve {
        background: linear-gradient(135deg, var(--success) 0%, #059669 100%);
        color: white !important;
        border: none;
        padding: 14px 32px;
        border-radius: 16px;
        font-weight: 800;
        font-size: 16px;
        box-shadow: 0 10px 20px rgba(16, 185, 129, 0.3);
        transition: all 0.3s;
    }
    .btn-approve:hover {
        transform: translateY(-3px);
        box-shadow: 0 15px 25px rgba(16, 185, 129, 0.4);
    }

    .btn-reject {
        background: white;
        color: var(--danger) !important;
        border: 2px solid var(--danger);
        padding: 12px 28px;
        border-radius: 16px;
        font-weight: 700;
        font-size: 15px;
        transition: all 0.3s;
    }
    .btn-reject:hover {
        background: var(--danger);
        color: white !important;
        box-shadow: 0 10px 20px rgba(239, 68, 68, 0.2);
    }

    .tte-document-bar {
        background: linear-gradient(135deg, rgba(16, 185, 129, 0.12), rgba(79, 70, 229, 0.10));
        border: 1px solid rgba(16, 185, 129, 0.22);
        padding: 24px 28px;
        border-radius: 24px;
        box-shadow: 0 18px 36px rgba(15, 23, 42, 0.08);
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 20px;
        flex-wrap: wrap;
    }

    .tte-document-title {
        color: var(--dark);
        font-weight: 800;
        margin-bottom: 4px;
    }

    .tte-document-subtitle {
        color: var(--gray);
        font-size: 13px;
    }

    .tte-document-actions {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
    }

    .btn-tte-doc {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: white;
        color: var(--dark) !important;
        border: 1px solid rgba(15, 23, 42, 0.08);
        border-radius: 14px;
        padding: 12px 18px;
        font-weight: 800;
        font-size: 14px;
        text-decoration: none;
        box-shadow: 0 10px 20px rgba(15, 23, 42, 0.08);
        transition: all 0.25s ease;
    }

    .btn-tte-doc i {
        color: var(--success);
        font-size: 16px;
    }

    .btn-tte-doc:hover {
        transform: translateY(-2px);
        border-color: rgba(16, 185, 129, 0.35);
        box-shadow: 0 14px 24px rgba(15, 23, 42, 0.12);
    }
</style>
@endpush

@section('content')
@php
    $isPendingReview = $kontrak->status_kontrak === 'PENDING_REVIEW';
    $isApproved = method_exists($kontrak, 'isTteApproved') ? $kontrak->isTteApproved() : !empty($kontrak->ppk_approved_at);
@endphp

<div class="container-fluid pb-5">
    
    <div class="review-hero">
        @if($isPendingReview)
            <div class="status-badge-lg">
                <i class="bi bi-hourglass-split"></i> MENUNGGU REVIEW PPK
            </div>
        @elseif($isApproved)
            <div class="status-badge-lg approved">
                <i class="bi bi-check2-circle"></i> SUDAH DISETUJUI PPK
            </div>
        @else
            <div class="status-badge-lg neutral">
                <i class="bi bi-info-circle"></i> {{ str_replace('_', ' ', $kontrak->status_kontrak) }}
            </div>
        @endif
        <h1 class="contract-title">{{ $kontrak->nama_pekerjaan }}</h1>
        <div class="contract-meta">
            <span class="meta-item"><i class="bi bi-file-earmark-text"></i> SPK: {{ $kontrak->nomor_spk }}</span>
            <span class="meta-item"><i class="bi bi-building"></i> Vendor: {{ $kontrak->vendor->nama_pihak ?? $kontrak->vendor->nama_perusahaan ?? 'N/A' }}</span>
            <span class="meta-item"><i class="bi bi-cash-stack"></i> Nilai: Rp {{ number_format($kontrak->nilai_total_kontrak, 0, ',', '.') }}</span>
            @if($isApproved && $kontrak->ppk_approved_at)
                <span class="meta-item"><i class="bi bi-calendar-check"></i> Disetujui: {{ \Carbon\Carbon::parse($kontrak->ppk_approved_at)->translatedFormat('d F Y H:i') }}</span>
            @endif
        </div>
    </div>

    @if(session('error'))
    <div class="alert alert-danger border-0 shadow-sm alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    <div class="row">
        <div class="col-xl-8">
            <!-- Informasi Pekerjaan -->
            <div class="card-glass">
                <div class="section-title">
                    <div class="section-icon"><i class="bi bi-briefcase-fill"></i></div>
                    Informasi Pekerjaan & Waktu
                </div>
                <div class="info-grid">
                    <div class="info-box">
                        <div class="info-label">No. SPMK</div>
                        <div class="info-value">{{ $kontrak->nomor_spmk ?: '-' }}</div>
                    </div>
                    <div class="info-box">
                        <div class="info-label">Tanggal SPK</div>
                        <div class="info-value">{{ $kontrak->tanggal_spk ? \Carbon\Carbon::parse($kontrak->tanggal_spk)->translatedFormat('d F Y') : '-' }}</div>
                    </div>
                    <div class="info-box">
                        <div class="info-label">Jangka Waktu</div>
                        <div class="info-value">{{ $kontrak->jangka_waktu }} {{ ucfirst(strtolower($kontrak->satuan_waktu)) }}</div>
                    </div>
                    <div class="info-box">
                        <div class="info-label">Periode Pelaksanaan</div>
                        <div class="info-value">
                            {{ $kontrak->tanggal_mulai ? \Carbon\Carbon::parse($kontrak->tanggal_mulai)->format('d/m/Y') : '-' }} 
                            s.d 
                            {{ $kontrak->tanggal_selesai ? \Carbon\Carbon::parse($kontrak->tanggal_selesai)->format('d/m/Y') : '-' }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Skema Pembayaran -->
            <div class="card-glass">
                <div class="section-title">
                    <div class="section-icon" style="background: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%);"><i class="bi bi-wallet2"></i></div>
                    Skema Pembayaran ({{ $kontrak->metode_pembayaran }})
                </div>
                
                @if($kontrak->ada_uang_muka)
                <div class="alert alert-info border-0 bg-info bg-opacity-10 d-flex align-items-center mb-4">
                    <i class="bi bi-info-circle-fill fs-4 text-info me-3"></i>
                    <div>
                        <div class="fw-bold text-dark mb-1">Kontrak ini memiliki Uang Muka</div>
                        <div class="text-muted">Senilai <strong>Rp {{ number_format($kontrak->nilai_uang_muka, 0, ',', '.') }}</strong> yang akan dipotong secara proporsional pada tiap termin pembayaran.</div>
                    </div>
                </div>
                @endif

                <div class="table-responsive rounded-3 border border-light">
                    <table class="table mb-0 termin-table">
                        <thead>
                            <tr>
                                <th>Termin</th>
                                <th>Keterangan</th>
                                <th class="text-end">Nilai Bruto</th>
                                <th class="text-end">Potongan UM</th>
                                <th class="text-end">Bersih (Net)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($kontrak->termin as $t)
                            <tr>
                                <td>
                                    <span class="badge bg-light text-dark border px-2 py-1">Ke-{{ $t->termin_ke }}</span>
                                </td>
                                <td>{{ $t->keterangan_termin }} ({{ floatval($t->persentase) }}%)</td>
                                <td class="text-end fw-bold">Rp {{ number_format($t->nilai_bruto_termin, 0, ',', '.') }}</td>
                                <td class="text-end text-danger">-Rp {{ number_format($t->potongan_angsuran_uang_muka, 0, ',', '.') }}</td>
                                <td class="text-end text-success fw-bold">Rp {{ number_format($t->nilai_bruto_termin - $t->potongan_angsuran_uang_muka, 0, ',', '.') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot style="background: #f8fafc;">
                            <tr>
                                <th colspan="2" class="text-end py-3">Total Kontrak:</th>
                                <th class="text-end fw-bolder fs-5 text-dark py-3">Rp {{ number_format($kontrak->nilai_total_kontrak, 0, ',', '.') }}</th>
                                <th colspan="2"></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <!-- Pagu DIPA -->
            <div class="card-glass">
                <div class="section-title">
                    <div class="section-icon" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);"><i class="bi bi-safe"></i></div>
                    Kesiapan Pagu DIPA
                </div>

                @if($sisaPagu !== null)
                    @php
                        $totalPagu = $kontrak->dipa->total_pagu ?? 0;
                        $pctTerpakai = $totalPagu > 0 ? ($terpakai / $totalPagu) * 100 : 0;
                        $pctKontrakIni = $totalPagu > 0 ? ($kontrak->nilai_total_kontrak / $totalPagu) * 100 : 0;
                        $pctSisa = 100 - $pctTerpakai - $pctKontrakIni;
                    @endphp

                    <div class="mb-3 d-flex justify-content-between align-items-center">
                        <span class="text-muted fw-bold">Pagu Tersedia</span>
                        <span class="fs-4 fw-bolder text-dark">Rp {{ number_format($sisaPagu, 0, ',', '.') }}</span>
                    </div>

                    <div class="budget-progress">
                        <div class="budget-used" style="width: {{ $pctTerpakai }}%" title="Sudah Terpakai: Rp {{ number_format($terpakai,0,',','.') }}"></div>
                        <div class="budget-current" style="width: {{ $pctKontrakIni }}%" title="Kontrak Ini: Rp {{ number_format($kontrak->nilai_total_kontrak,0,',','.') }}"></div>
                        <div class="budget-remaining" style="width: {{ $pctSisa }}%" title="Sisa Nanti: Rp {{ number_format($sisaPaguNanti,0,',','.') }}"></div>
                    </div>

                    <div class="budget-legends">
                        <div><span class="legend-dot budget-used"></span>Terpakai</div>
                        <div><span class="legend-dot budget-current"></span>Kontrak Ini</div>
                        <div><span class="legend-dot budget-remaining"></span>Sisa Nanti</div>
                    </div>

                    <hr class="my-4 border-light">

                    <div class="info-box mb-3">
                        <div class="info-label">COA (Mata Anggaran)</div>
                        <div class="info-value text-break">
                            {{ $kontrak->dipaRevisionItem->coa->kode_mak_lengkap ?? 'N/A' }} -
                            {{ $kontrak->dipaRevisionItem->coa->nama_akun ?? 'N/A' }}
                        </div>
                    </div>
                @else
                    <div class="alert alert-light border d-flex align-items-start gap-2 mb-0">
                        <i class="bi bi-info-circle text-primary mt-1"></i>
                        <div class="small text-muted">
                            Pembebanan COA/mata anggaran untuk kontrak ini akan dipilih oleh <strong>PPK</strong>
                            di halaman <strong>Proses Tagihan</strong> setelah tagihan disetujui verifikator.
                            Validasi sisa pagu dilakukan pada tahap tersebut.
                        </div>
                    </div>
                @endif
            </div>

            <!-- Vendor -->
            <div class="card-glass">
                <div class="section-title">
                    <div class="section-icon" style="background: linear-gradient(135deg, #f59e0b 0%, #ea580c 100%);"><i class="bi bi-buildings"></i></div>
                    Detail Vendor
                </div>
                
                <div class="d-flex align-items-center mb-4">
                    <div class="bg-light rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 50px; height: 50px; font-size: 24px; color: var(--warning);">
                        <i class="bi bi-shop"></i>
                    </div>
                    <div>
                        <div class="fw-bolder fs-5">{{ $kontrak->vendor->nama_pihak ?? $kontrak->vendor->nama_perusahaan ?? 'N/A' }}</div>
                        <div class="text-muted small">NPWP: {{ $kontrak->vendor->npwp ?? '-' }}</div>
                    </div>
                </div>

                @php $rekening = optional($kontrak->vendor)->rekening ? $kontrak->vendor->rekening->first() : null; @endphp
                @if($rekening)
                <div class="info-box">
                    <div class="info-label">Rekening Pembayaran</div>
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="fw-bold">{{ $rekening->nama_bank }}</div>
                        <div class="text-muted">{{ $rekening->nomor_rekening }}</div>
                    </div>
                    <div class="text-muted small mt-1">a.n {{ $rekening->nama_pemilik }}</div>
                </div>
                @else
                <div class="alert alert-warning mb-0 border-0 bg-warning bg-opacity-10 py-2">
                    <i class="bi bi-exclamation-circle me-1"></i> Data rekening vendor belum tersedia.
                </div>
                @endif
            </div>
        </div>
    </div>

    @if($isPendingReview)
        <!-- Action Bar Sticky -->
        <div class="action-bar mt-4">
            <div>
                <h5 class="mb-1 fw-bold">Keputusan PPK</h5>
                <div class="text-muted small">Tinjau seluruh data dengan teliti sebelum menyetujui kontrak ini.</div>
            </div>
            <div class="d-flex gap-3">
                <button type="button" class="btn btn-reject" data-bs-toggle="modal" data-bs-target="#rejectModal">
                    <i class="bi bi-x-circle me-2"></i> Kembalikan / Revisi
                </button>
                <form action="{{ route('contracts.approve', $kontrak->id) }}" method="POST" class="d-inline" id="formApprove">
                    @csrf
                    <button type="button" class="btn btn-approve" onclick="confirmApprove()">
                        <i class="bi bi-check2-circle me-2"></i> Setujui Kontrak
                    </button>
                </form>
            </div>
        </div>
    @elseif($isApproved)
        <div class="tte-document-bar mt-4">
            <div>
                <h5 class="tte-document-title"><i class="bi bi-patch-check-fill text-success me-2"></i>Dokumen Kontrak Ber-TTE</h5>
                <div class="tte-document-subtitle">SPK, SPMK, dan Ringkasan Kontrak sudah memuat QR TTE persetujuan PPK.</div>
            </div>
            <div class="tte-document-actions">
                <a href="{{ route('contracts.spk.export-pdf', $kontrak->id) }}" target="_blank" class="btn-tte-doc">
                    <i class="bi bi-file-earmark-check"></i> Lihat SPK
                </a>
                <a href="{{ route('contracts.spmk.export-pdf', $kontrak->id) }}" target="_blank" class="btn-tte-doc">
                    <i class="bi bi-file-earmark-text"></i> Lihat SPMK
                </a>
                <a href="{{ route('contracts.ringkasan.export-pdf', $kontrak->id) }}" target="_blank" class="btn-tte-doc">
                    <i class="bi bi-journal-richtext"></i> Lihat Ringkasan Kontrak
                </a>
            </div>
        </div>
    @endif

</div>

<!-- Modal Reject -->
@if($isPendingReview)
    <div class="modal fade" id="rejectModal" tabindex="-1" aria-labelledby="rejectModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form action="{{ route('contracts.reject', $kontrak->id) }}" method="POST" class="modal-content border-0 shadow-lg" style="border-radius: 20px; overflow: hidden;">
                @csrf
                <div class="modal-header bg-danger text-white border-0 py-3">
                    <h5 class="modal-title fw-bold" id="rejectModalLabel"><i class="bi bi-exclamation-octagon me-2"></i>Kembalikan Kontrak (Revisi)</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="text-muted mb-3">Tuliskan catatan atau alasan mengapa kontrak ini dikembalikan ke Pejabat Pengadaan untuk direvisi.</p>
                    <div class="form-group">
                        <label for="notes" class="fw-bold mb-2">Catatan Revisi <span class="text-danger">*</span></label>
                        <textarea name="notes" id="notes" rows="4" class="form-control" style="border-radius: 12px; resize: none;" required placeholder="Contoh: Nilai kontrak tidak sesuai, atau DIPA salah sasaran..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger rounded-pill px-4 fw-bold shadow-sm">Kirim Catatan</button>
                </div>
            </form>
        </div>
    </div>
@endif

@endsection

@push('script')
@if($isPendingReview)
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function confirmApprove() {
            Swal.fire({
                title: 'Setujui Kontrak?',
                text: "Anda yakin semua data kontrak ini sudah benar? Setelah disetujui, kontrak akan aktif.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#10b981',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Ya, Setujui',
                cancelButtonText: 'Periksa Lagi',
                reverseButtons: true,
                customClass: {
                    confirmButton: 'rounded-pill px-4 fw-bold',
                    cancelButton: 'rounded-pill px-4'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Memproses...',
                        text: 'Mohon tunggu, sistem sedang menyiapkan persetujuan kontrak.',
                        allowOutsideClick: false,
                        showConfirmButton: false,
                        didOpen: () => {
                            Swal.showLoading();
                            document.getElementById('formApprove').submit();
                        }
                    });
                }
            });
        }
    </script>
@endif
@endpush
