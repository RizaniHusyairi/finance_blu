@extends('layouts.app')

@section('title')
    Detail Pengajuan Pembayaran BLU
@endsection

@push('css')
    <style>
        .detail-hero {
            background: linear-gradient(135deg, rgba(13, 110, 253, 0.12), rgba(25, 135, 84, 0.08));
            border: 1px solid rgba(13, 110, 253, 0.08);
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.85rem 1rem;
        }

        .info-item {
            padding: 0.9rem 1rem;
            border-radius: 1rem;
            background: #f8f9fb;
            border: 1px solid #edf1f6;
        }

        .info-item .label {
            display: block;
            font-size: 0.78rem;
            color: #6c757d;
            margin-bottom: 0.3rem;
        }

        .info-item .value {
            font-weight: 600;
            color: #243244;
        }

        .doc-item {
            border: 1px solid #eef2f7;
            border-radius: 1rem;
            padding: 1rem;
            background: #fff;
        }

        .stepper {
            position: relative;
            padding-left: 1.2rem;
        }

        .stepper::before {
            content: "";
            position: absolute;
            left: 9px;
            top: 10px;
            bottom: 10px;
            width: 2px;
            background: #dce6f4;
        }

        .stepper-item {
            position: relative;
            padding-left: 1.4rem;
            padding-bottom: 1.4rem;
        }

        .stepper-item:last-child {
            padding-bottom: 0;
        }

        .stepper-dot {
            position: absolute;
            left: -1px;
            top: 0.25rem;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            border: 3px solid #fff;
            box-shadow: 0 0 0 1px #dce6f4;
            background: #adb5bd;
        }

        .stepper-dot.done { background: #198754; }
        .stepper-dot.waiting { background: #0d6efd; }
        .stepper-dot.revision { background: #dc3545; }
        .stepper-dot.progress { background: #ffc107; }

        .sticky-summary {
            position: sticky;
            top: 90px;
        }

        @media (max-width: 991.98px) {
            .info-grid {
                grid-template-columns: 1fr;
            }

            .sticky-summary {
                position: static;
            }
        }
    </style>
@endpush

@section('content')
    <div class="card border-0 shadow-sm detail-hero rounded-4 mb-4">
        <div class="card-body p-4 p-xl-5">
            <div class="d-flex flex-column flex-xl-row justify-content-between align-items-xl-start gap-3">
                <div>
                    <div class="d-flex flex-wrap gap-2 mb-3">
                        <a href="{{ route('blu-payment-submissions.index') }}" class="btn btn-sm btn-light border">
                            <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar
                        </a>
                        <span class="badge bg-{{ $statusClasses[$submission['status']] ?? 'secondary' }}">{{ $submission['status'] }}</span>
                        <span class="badge bg-light text-dark border">{{ $submission['contract_type'] }}</span>
                        <span class="badge bg-light text-dark border">{{ $submission['payment_type'] }}</span>
                    </div>
                    <h2 class="mb-2 fw-bold">{{ $submission['submission_number'] }}</h2>
                    <p class="mb-1 text-secondary">{{ $submission['title'] }}</p>
                    <p class="mb-0 text-muted small">NPI: {{ $submission['npi_number'] ?? '-' }} | Tanggal pengajuan {{ $submission['date_label'] }}</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <button type="button" class="btn btn-outline-primary js-demo-action" data-message="Mode edit pengajuan akan dihubungkan ke form transaksi atau dokumen pada tahap implementasi berikutnya.">
                        <i class="bi bi-pencil-square me-1"></i> Edit Pengajuan
                    </button>
                    <button type="button" class="btn btn-primary js-demo-action" data-message="Pengajuan ini akan dikirimkan ke tahapan verifikasi pada implementasi backend.">
                        <i class="bi bi-send-check me-1"></i> Kirim Verifikasi
                    </button>
                </div>
            </div>
        </div>
    </div>

    @if (! empty($submission['alerts']))
        <div class="row g-3 mb-4">
            @foreach ($submission['alerts'] as $alert)
                <div class="col-12 col-xl-6">
                    <div class="alert alert-{{ $alertClasses[$alert['type']] ?? 'warning' }} border-0 shadow-sm mb-0">
                        <div class="fw-semibold mb-1">{{ $alert['title'] }}</div>
                        <div class="small">{{ $alert['message'] }}</div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <div class="row g-4">
        <div class="col-12 col-xl-8">
            <div class="card rounded-4 border-0 shadow-sm mb-4">
                <div class="card-header bg-transparent border-0 pb-0"><h5 class="mb-0">A. Informasi Umum</h5></div>
                <div class="card-body">
                    <div class="info-grid">
                        <div class="info-item"><span class="label">Nomor Pengajuan</span><span class="value">{{ $submission['submission_number'] }}</span></div>
                        <div class="info-item"><span class="label">Tanggal Pengajuan</span><span class="value">{{ $submission['date_label'] }}</span></div>
                        <div class="info-item"><span class="label">Nomor NPI</span><span class="value">{{ $submission['npi_number'] ?? '-' }}</span></div>
                        <div class="info-item"><span class="label">Jenis Pembayaran</span><span class="value">{{ $submission['payment_type'] }}</span></div>
                        <div class="info-item"><span class="label">Kontrak / Non-Kontrak</span><span class="value">{{ $submission['contract_type'] }}</span></div>
                        <div class="info-item"><span class="label">Supplier / Mitra</span><span class="value">{{ $submission['supplier'] }}</span></div>
                        <div class="info-item"><span class="label">Operator BLU</span><span class="value">{{ $submission['operator'] }}</span></div>
                        <div class="info-item"><span class="label">PPK</span><span class="value">{{ $submission['ppk'] }}</span></div>
                        <div class="info-item"><span class="label">PPSPM</span><span class="value">{{ $submission['ppspm'] }}</span></div>
                    </div>
                </div>
            </div>

            <div class="card rounded-4 border-0 shadow-sm mb-4">
                <div class="card-header bg-transparent border-0 pb-0"><h5 class="mb-0">B. Informasi Pembayaran</h5></div>
                <div class="card-body">
                    <div class="info-grid">
                        <div class="info-item"><span class="label">Tanggal BAST</span><span class="value">{{ $submission['bast_date'] }}</span></div>
                        <div class="info-item"><span class="label">Cara Bayar</span><span class="value">{{ $submission['payment_method'] }}</span></div>
                        <div class="info-item"><span class="label">Nilai Bruto</span><span class="value">Rp {{ number_format($submission['gross_amount'], 0, ',', '.') }}</span></div>
                        <div class="info-item"><span class="label">Rincian Pajak</span><span class="value">PPN Rp {{ number_format($submission['ppn'], 0, ',', '.') }} + PPh Rp {{ number_format($submission['pph'], 0, ',', '.') }}</span></div>
                        <div class="info-item"><span class="label">Denda</span><span class="value">Rp {{ number_format($submission['penalty'], 0, ',', '.') }}</span></div>
                        <div class="info-item"><span class="label">Nilai Netto</span><span class="value text-success">Rp {{ number_format($submission['net_amount'], 0, ',', '.') }}</span></div>
                        <div class="info-item"><span class="label">Termin</span><span class="value">{{ $submission['contract_term_label'] ?? '-' }}</span></div>
                        <div class="info-item" style="grid-column: 1 / -1;"><span class="label">Keterangan Pembayaran</span><span class="value">{{ $submission['payment_note'] }}</span></div>
                    </div>
                </div>
            </div>

            <div class="card rounded-4 border-0 shadow-sm mb-4">
                <div class="card-header bg-transparent border-0 pb-0"><h5 class="mb-0">C. Informasi Kontrak Terkait</h5></div>
                <div class="card-body">
                    @if ($submission['contract_info'])
                        <div class="info-grid">
                            <div class="info-item"><span class="label">Nomor Kontrak</span><span class="value">{{ $submission['contract_info']['number'] }}</span></div>
                            <div class="info-item"><span class="label">Status Kontrak</span><span class="value">{{ $submission['contract_info']['status'] }}</span></div>
                            <div class="info-item" style="grid-column: 1 / -1;"><span class="label">Uraian Kontrak</span><span class="value">{{ $submission['contract_info']['description'] }}</span></div>
                            <div class="info-item"><span class="label">Nilai Kontrak</span><span class="value">Rp {{ number_format($submission['contract_info']['total_amount'], 0, ',', '.') }}</span></div>
                            <div class="info-item"><span class="label">Total Realisasi Sebelumnya</span><span class="value">Rp {{ number_format($submission['contract_info']['previous_realization'], 0, ',', '.') }}</span></div>
                            <div class="info-item"><span class="label">Sisa Kontrak</span><span class="value">Rp {{ number_format($submission['contract_info']['remaining'], 0, ',', '.') }}</span></div>
                        </div>
                    @else
                        <div class="alert alert-light border mb-0">
                            <span class="badge bg-light text-dark border me-2">Non-Kontrak</span>
                            Pengajuan ini tidak terhubung dengan kontrak.
                        </div>
                    @endif
                </div>
            </div>

            <div class="card rounded-4 border-0 shadow-sm mb-4">
                <div class="card-header bg-transparent border-0 pb-0"><h5 class="mb-0">D. Dokumen Pendukung</h5></div>
                <div class="card-body">
                    <div class="row g-3">
                        @foreach ($submission['documents'] as $document)
                            <div class="col-12 col-md-6">
                                <div class="doc-item h-100">
                                    <div class="d-flex justify-content-between gap-3">
                                        <div class="d-flex gap-3">
                                            <div class="d-flex align-items-center justify-content-center rounded-circle bg-primary bg-opacity-10 text-primary" style="width:44px;height:44px;">
                                                <i class="bi bi-file-earmark-text"></i>
                                            </div>
                                            <div>
                                                <div class="fw-semibold">{{ $document['name'] }}</div>
                                                <div class="small text-muted">{{ $document['size'] }}</div>
                                            </div>
                                        </div>
                                        <span class="badge bg-{{ $document['status'] === 'Lengkap' ? 'success' : 'warning text-dark' }}">{{ $document['status'] }}</span>
                                    </div>
                                    <div class="d-flex gap-2 mt-3">
                                        <button type="button" class="btn btn-sm btn-outline-primary js-demo-action" data-message="Preview dokumen akan dihubungkan ke penyimpanan file pada tahap backend.">Preview</button>
                                        <button type="button" class="btn btn-sm btn-outline-secondary js-demo-action" data-message="Unduh dokumen merupakan placeholder UI.">Download</button>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-4">
            <div class="sticky-summary">
                <div class="card rounded-4 border-0 shadow-sm mb-4">
                    <div class="card-header bg-transparent border-0 pb-0"><h5 class="mb-0">Ringkasan Pengajuan</h5></div>
                    <div class="card-body">
                        <div class="info-item mb-3"><span class="label">Status Proses</span><span class="value">{{ $submission['status'] }}</span></div>
                        <div class="info-item mb-3"><span class="label">Status Pencairan</span><span class="value">{{ $submission['disbursement_status'] }}</span></div>
                        <div class="info-item mb-3"><span class="label">Nilai Netto</span><span class="value text-success">Rp {{ number_format($submission['net_amount'], 0, ',', '.') }}</span></div>
                        <div class="info-item"><span class="label">Supplier</span><span class="value">{{ $submission['supplier_short'] }}</span></div>
                    </div>
                </div>

                <div class="card rounded-4 border-0 shadow-sm mb-4">
                    <div class="card-header bg-transparent border-0 pb-0"><h5 class="mb-0">E. Tracking Approval</h5></div>
                    <div class="card-body">
                        <div class="stepper">
                            @foreach ($submission['timeline'] as $step)
                                @php
                                    $dotClass = 'waiting';
                                    if ($step['state'] === 'Selesai') {
                                        $dotClass = 'done';
                                    } elseif ($step['state'] === 'Revisi') {
                                        $dotClass = 'revision';
                                    } elseif ($step['state'] === 'Berjalan') {
                                        $dotClass = 'progress';
                                    }
                                @endphp
                                <div class="stepper-item">
                                    <span class="stepper-dot {{ $dotClass }}"></span>
                                    <div class="d-flex flex-column gap-1">
                                        <div class="fw-semibold">{{ $step['step'] }}</div>
                                        <div class="text-muted small">{{ $step['role'] }}</div>
                                        <div><span class="badge bg-light text-dark border">{{ $step['state'] }}</span></div>
                                        <div class="small text-muted">{{ $step['datetime'] }}</div>
                                        @if ($step['note'])
                                            <div class="small">{{ $step['note'] }}</div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="card rounded-4 border-0 shadow-sm">
                    <div class="card-header bg-transparent border-0 pb-0"><h5 class="mb-0">F. Informasi Pencairan</h5></div>
                    <div class="card-body">
                        <div class="info-item mb-3"><span class="label">Nomor SP2D</span><span class="value">{{ $submission['sp2d_number'] }}</span></div>
                        <div class="info-item mb-3"><span class="label">Tanggal SP2D</span><span class="value">{{ $submission['sp2d_date'] }}</span></div>
                        <div class="info-item mb-3"><span class="label">Tanggal Transfer</span><span class="value">{{ $submission['transfer_date'] }}</span></div>
                        <div class="info-item"><span class="label">Catatan Pencairan</span><span class="value">{{ $submission['disbursement_note'] }}</span></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.js-demo-action').forEach((button) => {
                button.addEventListener('click', function () {
                    window.alert(button.dataset.message || 'Aksi ini masih berupa rancangan antarmuka.');
                });
            });
        });
    </script>
@endpush
