<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi {{ $tagihan->nomor_tagihan }} &middot; SIKEREN</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(180deg, #f1f5f9 0%, #ffffff 52%);
            min-height: 100vh;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            color: #0f172a;
        }
        .page-header {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 50%, #db2777 100%);
            color: #fff;
            border-radius: 0 0 1.4rem 1.4rem;
            box-shadow: 0 12px 28px rgba(79, 70, 229, .25);
        }
        .badge-public {
            background: rgba(255, 255, 255, .18);
            border: 1px solid rgba(255, 255, 255, .30);
            color: #fff;
            font-weight: 700;
            padding: .35rem .85rem;
            border-radius: 999px;
            font-size: .72rem;
            letter-spacing: .04em;
            text-transform: uppercase;
            backdrop-filter: blur(8px);
        }
        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: .45rem;
            border-radius: 999px;
            padding: .55rem .95rem;
            font-weight: 800;
            box-shadow: 0 10px 24px rgba(15, 23, 42, .12);
        }
        .status-valid { background: #dcfce7; color: #166534; }
        .status-invalid { background: #fee2e2; color: #991b1b; }
        .info-card {
            background: #fff;
            border: 1px solid #eef0f4;
            border-radius: 1rem;
            box-shadow: 0 4px 12px rgba(15, 23, 42, .04);
        }
        .info-card .ic-head {
            padding: .85rem 1.25rem;
            border-bottom: 1px solid #f1f3f7;
            font-weight: 700;
            color: #0f172a;
            display: flex;
            align-items: center;
            gap: .5rem;
        }
        .info-card .ic-head i { color: #4f46e5; }
        .info-card .ic-body { padding: 1.15rem 1.25rem; }
        .label-soft {
            font-size: .68rem;
            font-weight: 800;
            letter-spacing: .06em;
            text-transform: uppercase;
            color: #94a3b8;
        }
        .value-strong {
            font-weight: 700;
            color: #0f172a;
            font-size: .92rem;
        }
        .seal-box {
            border: 1px solid rgba(16, 185, 129, .25);
            background: linear-gradient(135deg, rgba(16, 185, 129, .12), rgba(16, 185, 129, .03));
            border-radius: .9rem;
            padding: 1rem;
        }
        .seal-box.invalid {
            border-color: rgba(239, 68, 68, .25);
            background: linear-gradient(135deg, rgba(239, 68, 68, .12), rgba(239, 68, 68, .03));
        }
        .signature-card {
            background: #fff;
            border: 1px solid rgba(16, 185, 129, .28);
            color: #0f172a;
            border-radius: 1rem;
            padding: 1.25rem;
            position: relative;
            overflow: hidden;
            box-shadow: 0 8px 22px rgba(16, 185, 129, .10);
        }
        .signature-card::after {
            content: "";
            position: absolute;
            right: -65px;
            top: -65px;
            width: 180px;
            height: 180px;
            border-radius: 999px;
            background: rgba(16, 185, 129, .08);
        }
        .signature-card .label-soft { color: #64748b; }
        .signature-card .value-strong { color: #0f172a; }
        .doc-valid-banner {
            border: 1px solid rgba(16, 185, 129, .26);
            background: linear-gradient(135deg, rgba(16, 185, 129, .14), rgba(16, 185, 129, .04));
            border-radius: .85rem;
            padding: .85rem 1rem;
            color: #065f46;
        }
        .btn-doc {
            border-radius: .75rem;
            font-weight: 800;
            padding: .75rem 1rem;
        }
        .btn-doc-primary {
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            color: #fff;
            border: 0;
            box-shadow: 0 8px 20px rgba(79, 70, 229, .24);
        }
        .btn-doc-primary:hover { color: #fff; transform: translateY(-1px); }
        .table-rincian thead th {
            background: #f8fafc;
            font-size: .68rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .05em;
            color: #64748b;
            border-bottom: 1px solid #eef0f4;
            white-space: nowrap;
        }
        .table-rincian tbody td {
            border-bottom: 1px solid #f1f3f7;
            font-size: .88rem;
            vertical-align: middle;
        }
        .metric-card {
            border: 1px solid #eef0f4;
            border-radius: .9rem;
            background: #f8fafc;
            padding: .95rem;
            height: 100%;
        }
        .total-row {
            background: linear-gradient(135deg, rgba(16, 185, 129, .08), rgba(16, 185, 129, .02));
            font-weight: 800;
        }
        .total-row td {
            border-top: 2px solid rgba(16, 185, 129, .25) !important;
            color: #0f172a;
        }
        .total-row .total-amount {
            color: #047857;
            font-size: 1.05rem;
            font-variant-numeric: tabular-nums;
        }
        .hash {
            font-family: ui-monospace, "SF Mono", Menlo, Consolas, monospace;
            font-size: .75rem;
            word-break: break-all;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: .85rem;
            border-radius: .75rem;
        }
        .timeline { list-style: none; padding: 0; margin: 0; }
        .timeline li { display: flex; gap: .7rem; padding: .75rem 0; border-bottom: 1px dashed #e2e8f0; }
        .timeline li:last-child { border-bottom: 0; }
        .timeline i { font-size: 1.05rem; margin-top: .1rem; }
        .footer-note {
            font-size: .8rem;
            color: #94a3b8;
            text-align: center;
            margin-top: 2rem;
            padding: 1rem 0;
        }
    </style>
</head>
<body>
@php
    $rupiah = fn ($value) => 'Rp ' . number_format((float) $value, 0, ',', '.');
    $tanggal = fn ($value) => $value ? \Carbon\Carbon::parse($value)->translatedFormat('d F Y') : '-';
    $tanggalJam = fn ($value) => $value ? \Carbon\Carbon::parse($value)->translatedFormat('d F Y H:i') . ' WITA' : '-';
    $statusPembayaran = $tagihan->status_pembayaran ?: ($tagihan->status === 'LUNAS' ? 'lunas' : 'belum_dibayar');
    $isSignedFinal = $tagihan->status_dokumen_pengantar === 'SUDAH_DITANDATANGANI' && !empty($tagihan->file_surat_pengantar_final);
@endphp

<header class="page-header mb-4">
    <div class="container py-4 py-md-5">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
            <div>
                <span class="badge-public d-inline-flex align-items-center gap-2 mb-3">
                    <i class="bi bi-shield-check"></i> Verifikasi Dokumen
                </span>
                <h1 class="h3 fw-bold mb-2">Surat Pengantar Tagihan Jasa</h1>
                <div class="opacity-75">Kode Verifikasi: <strong>{{ $tagihan->kode_verifikasi_digital }}</strong></div>
            </div>
            <span class="status-pill {{ $isValid ? 'status-valid' : 'status-invalid' }}">
                <i class="bi {{ $isValid ? 'bi-shield-check' : 'bi-shield-x' }}"></i>
                {{ $isValid ? 'Dokumen Valid' : 'Dokumen Tidak Valid' }}
            </span>
        </div>
    </div>
</header>

<main class="container pb-5">
    <div class="seal-box {{ $isValid ? '' : 'invalid' }} mb-4">
        <div class="d-flex gap-3 align-items-start">
            <i class="bi {{ $isValid ? 'bi-check-circle-fill text-success' : 'bi-exclamation-triangle-fill text-danger' }} fs-3"></i>
            <div>
                <div class="fw-bold mb-1">{{ $isValid ? 'Data dokumen cocok dengan database.' : 'Segel digital tidak cocok.' }}</div>
                <div class="small text-muted">
                    {{ $isValid ? 'Dokumen ini dapat diverifikasi dari sistem SIKEREN-BLU.' : 'Dokumen mungkin berubah setelah QR diterbitkan atau link verifikasi tidak sesuai.' }}
                </div>
            </div>
        </div>
    </div>

    @if($isSignedFinal)
        <div class="signature-card mb-4">
            <div class="row g-3 align-items-center position-relative" style="z-index:1;">
                <div class="col-lg-8">
                    <div class="doc-valid-banner d-flex align-items-start gap-2 mb-3">
                        <i class="bi bi-patch-check-fill fs-4"></i>
                        <div>
                            <div class="fw-bold">Dokumen valid.</div>
                            <div class="small">Dokumen telah ditandatangani secara elektronik melalui sistem dan data tanda tangan cocok dengan database.</div>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <i class="bi bi-qr-code fs-4 text-success"></i>
                        <h2 class="h5 fw-bold mb-0">Tanda Tangan Elektronik</h2>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="label-soft">Ditandatangani oleh</div>
                            <div class="value-strong">{{ $tagihan->pejabat_penandatangan_nama ?: ($tagihan->suratPengantarSigner->name ?? '-') }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="label-soft">Tanggal tanda tangan</div>
                            <div class="value-strong">{{ $tanggalJam($tagihan->uploaded_surat_pengantar_at) }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="label-soft">Jabatan</div>
                            <div class="value-strong">{{ $tagihan->pejabat_penandatangan_jabatan ?: '-' }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="label-soft">Hash dokumen</div>
                            <div class="value-strong">{{ $isValid ? 'Cocok' : 'Tidak cocok' }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 text-lg-end">
                    <div class="mb-3">
                        <div class="label-soft">Status workflow</div>
                        <div class="value-strong">{{ ($tagihan->workflowInstance?->status === 'APPROVED') ? 'Disetujui final' : str_replace('_', ' ', $tagihan->workflowInstance?->status ?? $tagihan->status) }}</div>
                    </div>
                    @if($tagihan->file_surat_pengantar_final)
                        <a href="{{ \Illuminate\Support\Facades\Storage::url($tagihan->file_surat_pengantar_final) }}" target="_blank" class="btn btn-doc btn-doc-primary w-100">
                            <i class="bi bi-file-earmark-pdf me-1"></i> Lihat Dokumen Surat Pengantar
                        </a>
                    @endif
                </div>
            </div>
        </div>
    @endif

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="info-card mb-4">
                <div class="ic-head"><i class="bi bi-envelope-paper"></i>Identitas Surat Pengantar</div>
                <div class="ic-body">
                    <div class="row g-3">
                        <div class="col-md-6"><div class="label-soft">Nomor Surat</div><div class="value-strong">{{ $tagihan->nomor_surat_pengantar ?: '-' }}</div></div>
                        <div class="col-md-6"><div class="label-soft">Tanggal Surat</div><div class="value-strong">{{ $tanggal($tagihan->tanggal_surat_pengantar ?: $tagihan->tanggal_tagihan) }}</div></div>
                        <div class="col-md-8"><div class="label-soft">Perihal</div><div class="value-strong">{{ $tagihan->perihal_surat_pengantar ?: 'Penyampaian Tagihan PNBP Jasa' }}</div></div>
                        <div class="col-md-4"><div class="label-soft">Lampiran</div><div class="value-strong">1 berkas Nota Tagihan</div></div>
                    </div>
                </div>
            </div>

            <div class="info-card mb-4">
                <div class="ic-head"><i class="bi bi-list-check"></i>Ringkasan Rincian Layanan</div>
                <div class="ic-body">
                    <div class="table-responsive">
                        <table class="table table-rincian align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Layanan</th>
                                    <th>Kode</th>
                                    <th class="text-end">Qty</th>
                                    <th class="text-end">Tarif</th>
                                    <th class="text-end">Jumlah</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($tagihan->details as $detail)
                                    <tr>
                                        <td>{{ $detail->layananJasa->nama_lengkap ?? $detail->layananJasa->nama_layanan ?? '-' }}</td>
                                        <td>{{ $detail->kode_akun ?: '-' }}</td>
                                        <td class="text-end">{{ rtrim(rtrim(number_format($detail->qty, 2, ',', '.'), '0'), ',') }}</td>
                                        <td class="text-end">{{ $rupiah($detail->harga_satuan) }}</td>
                                        <td class="text-end fw-bold">{{ $rupiah($detail->subtotal) }}</td>
                                    </tr>
                                @endforeach
                                <tr class="total-row">
                                    <td colspan="4" class="text-end">Total Tagihan</td>
                                    <td class="text-end total-amount">{{ $rupiah($tagihan->total_tagihan) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="info-card">
                <div class="ic-head"><i class="bi bi-clock-history"></i>Riwayat Workflow</div>
                <div class="ic-body">
                    <ul class="timeline">
                        <li><i class="bi bi-file-earmark-plus text-primary"></i><div><strong>Dibuat</strong><br><span class="text-muted small">{{ $tagihan->creator->name ?? 'Admin' }} - {{ optional($tagihan->created_at)->format('d M Y H:i') }}</span></div></li>
                        @foreach(($tagihan->workflowInstance?->approvals ?? collect()) as $approval)
                            <li>
                                <i class="bi {{ $approval->status === 'APPROVED' ? 'bi-check-circle text-success' : ($approval->status === 'REVISION' ? 'bi-arrow-counterclockwise text-warning' : ($approval->status === 'REJECTED' ? 'bi-x-circle text-danger' : 'bi-hourglass text-secondary')) }}"></i>
                                <div>
                                    <strong>{{ $approval->nama_step }}</strong> - {{ $approval->status }}
                                    <br><span class="text-muted small">{{ $approval->actedByUser->name ?? $approval->role_code }} @if($approval->acted_at) - {{ \Carbon\Carbon::parse($approval->acted_at)->format('d M Y H:i') }} @endif</span>
                                    @if($approval->catatan)
                                        <br><span class="small">{{ $approval->catatan }}</span>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="info-card mb-4">
                <div class="ic-head"><i class="bi bi-receipt"></i>Identitas Tagihan</div>
                <div class="ic-body">
                    <div class="row g-3">
                        <div class="col-12"><div class="label-soft">Nomor Tagihan</div><div class="value-strong text-primary">{{ $tagihan->nomor_tagihan }}</div></div>
                        <div class="col-6"><div class="label-soft">Tanggal Tagihan</div><div class="value-strong">{{ $tanggal($tagihan->tanggal_tagihan) }}</div></div>
                        <div class="col-6"><div class="label-soft">Jatuh Tempo</div><div class="value-strong">{{ $tanggal($tagihan->tanggal_jatuh_tempo) }}</div></div>
                        <div class="col-6"><div class="label-soft">Status Pembayaran</div><div class="value-strong">{{ str_replace('_', ' ', strtoupper($statusPembayaran)) }}</div></div>
                        <div class="col-6"><div class="label-soft">Tanggal Publish</div><div class="value-strong">{{ $tanggal($tagihan->tanggal_publish) }}</div></div>
                    </div>
                    <div class="row g-3 mt-1">
                        <div class="col-md-6"><div class="metric-card"><div class="label-soft">Pokok</div><div class="value-strong">{{ $rupiah($tagihan->total_tagihan) }}</div></div></div>
                        <div class="col-md-6"><div class="metric-card"><div class="label-soft">Total Bayar</div><div class="value-strong text-success">{{ $rupiah($tagihan->total_dengan_denda) }}</div></div></div>
                    </div>
                </div>
            </div>

            <div class="info-card mb-4">
                <div class="ic-head"><i class="bi bi-building"></i>Wajib Bayar / Mitra</div>
                <div class="ic-body">
                    <div class="row g-3">
                        <div class="col-12"><div class="label-soft">Nama Mitra</div><div class="value-strong">{{ $mitraTagihan->nama_mitra ?? $mitraTagihan->nama_pihak ?? '-' }}</div></div>
                        <div class="col-md-6"><div class="label-soft">NPWP</div><div class="value-strong">{{ $mitraTagihan->npwp ?? '-' }}</div></div>
                        <div class="col-md-6"><div class="label-soft">Email</div><div class="value-strong">{{ $mitraTagihan->email ?? '-' }}</div></div>
                        <div class="col-12"><div class="label-soft">Alamat</div><div class="value-strong">{{ $mitraTagihan->alamat ?? '-' }}</div></div>
                    </div>
                </div>
            </div>

            <div class="info-card mb-4">
                <div class="ic-head"><i class="bi bi-credit-card"></i>Informasi Pembayaran</div>
                <div class="ic-body">
                    <div class="row g-3">
                        <div class="col-md-6"><div class="label-soft">Bank</div><div class="value-strong">BTN</div></div>
                        <div class="col-md-6"><div class="label-soft">Virtual Account</div><div class="value-strong">{{ $tagihan->nomor_va ?: 'Belum tersedia' }}</div></div>
                        <div class="col-md-6"><div class="label-soft">Jumlah Dibayar</div><div class="value-strong">{{ $rupiah($tagihan->jumlah_dibayar) }}</div></div>
                        <div class="col-md-6"><div class="label-soft">Sisa Tagihan</div><div class="value-strong text-danger">{{ $rupiah($tagihan->sisa_tagihan_berjalan) }}</div></div>
                    </div>
                </div>
            </div>

            <div class="info-card">
                <div class="ic-head"><i class="bi bi-fingerprint"></i>Segel Digital Sistem</div>
                <div class="ic-body">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6"><div class="label-soft">Kode Verifikasi</div><div class="value-strong">{{ $tagihan->kode_verifikasi_digital }}</div></div>
                        <div class="col-md-6"><div class="label-soft">Status Integritas</div><div class="value-strong">{{ $isValid ? 'Valid' : 'Tidak Valid' }}</div></div>
                    </div>
                    <div class="hash">Hash Dokumen: {{ $providedSeal ?: '-' }}</div>
                    <div class="small text-muted mt-2">Segel digital memastikan data pokok surat pengantar dan tagihan cocok dengan data sistem.</div>
                </div>
            </div>
        </div>
    </div>

    <div class="footer-note">
        Halaman ini hanya menampilkan hasil verifikasi dokumen berdasarkan QR pada surat pengantar tagihan jasa.
    </div>
</main>
</body>
</html>
