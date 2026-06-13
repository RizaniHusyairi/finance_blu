<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dokumen Berhasil Disetujui</title>

    <link rel="icon" type="image/png" href="{{ asset('public/logo/minilogo-sikeren.png') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        :root {
            --success: #10b981;
            --success-dark: #059669;
            --text-main: #1e2235;
            --text-muted: #6b7280;
            --border-soft: rgba(17, 24, 39, 0.08);
        }
        * { box-sizing: border-box; }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #eef1f8;
            background-image:
                radial-gradient(circle at 0% 0%, rgba(16,185,129,0.10), transparent 40%),
                radial-gradient(circle at 100% 100%, rgba(6,182,212,0.10), transparent 42%);
            background-attachment: fixed;
            color: var(--text-main);
            min-height: 100vh;
            display: flex; flex-direction: column;
            margin: 0;
        }

        .wrap { flex: 1; display: flex; align-items: center; }

        .success-card {
            background: #fff;
            border-radius: 1.75rem;
            border: 1px solid var(--border-soft);
            box-shadow: 0 30px 70px -25px rgba(30,34,53,0.35);
            overflow: hidden;
            animation: pop .6s cubic-bezier(.16,1,.3,1) both;
        }
        .success-head {
            background: linear-gradient(135deg, var(--success) 0%, var(--success-dark) 100%);
            padding: 2.75rem 2rem 2.25rem;
            text-align: center;
            color: #fff;
            position: relative;
            overflow: hidden;
        }
        .success-head::after {
            content: '';
            position: absolute; inset: 0;
            background-image: radial-gradient(circle at 25% 20%, rgba(255,255,255,.18), transparent 30%);
        }
        .check-badge {
            width: 96px; height: 96px;
            border-radius: 50%;
            background: rgba(255,255,255,0.18);
            border: 1px solid rgba(255,255,255,0.35);
            display: inline-flex; align-items: center; justify-content: center;
            position: relative; z-index: 1;
            animation: pulseRing 2.4s infinite;
        }
        .check-badge i { font-size: 3.4rem; animation: drawIn .6s .25s both; }
        .success-head h3 { font-weight: 800; margin: 1rem 0 .25rem; position: relative; z-index: 1; }
        .success-head p { color: rgba(255,255,255,0.85); margin: 0; position: relative; z-index: 1; }

        .card-body-pad { padding: 1.75rem; }
        @media (min-width: 768px){ .card-body-pad { padding: 2.25rem; } }

        .doc-list-item {
            display: flex; align-items: center; gap: .75rem;
            padding: .75rem 1rem;
            border: 1px solid var(--border-soft);
            border-radius: 1rem;
            background: #f9fafc;
        }
        .doc-list-item + .doc-list-item { margin-top: .6rem; }
        .doc-ico {
            width: 38px; height: 38px; border-radius: 11px;
            background: rgba(16,185,129,.12); color: var(--success-dark);
            display: inline-flex; align-items: center; justify-content: center;
            font-size: 1.15rem; flex-shrink: 0;
        }

        .receipt {
            background: #f9fafc;
            border: 1px dashed var(--border-soft);
            border-radius: 1rem;
            padding: 1.1rem 1.25rem;
        }
        .receipt-row { display: flex; align-items: center; gap: .6rem; font-size: .9rem; color: var(--text-main); }
        .receipt-row + .receipt-row { margin-top: .55rem; }
        .receipt-row i { color: var(--success-dark); }
        .receipt-row .lbl { color: var(--text-muted); min-width: 110px; }

        @keyframes pop { from { transform: translateY(24px) scale(.98); opacity: 0; } to { transform: translateY(0) scale(1); opacity: 1; } }
        @keyframes drawIn { from { transform: scale(0) rotate(-30deg); opacity: 0; } to { transform: scale(1) rotate(0); opacity: 1; } }
        @keyframes pulseRing { 0% { box-shadow: 0 0 0 0 rgba(255,255,255,.4); } 70% { box-shadow: 0 0 0 18px rgba(255,255,255,0); } 100% { box-shadow: 0 0 0 0 rgba(255,255,255,0); } }
        
        /* ===== Unified Document Card ===== */
        .unified-doc-card {
            background: #fff;
            border: 1px solid var(--border-soft);
            border-radius: 1rem;
            padding: 1.25rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 0.75rem;
            transition: all 0.3s ease;
        }

        .unified-doc-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px -8px rgba(30, 34, 53, 0.12);
            border-color: rgba(16, 185, 129, 0.4);
        }

        .udc-icon-wrap {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: rgba(16, 185, 129, 0.1);
            color: var(--success);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            flex-shrink: 0;
        }

        @media (max-width: 768px) {
            .unified-doc-card {
                flex-wrap: wrap;
                padding: 1rem;
            }
            .udc-action {
                width: 100%;
                margin-top: 0.5rem;
                text-align: right;
            }
        }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="container py-5">
            <div class="row justify-content-center">
                <div class="col-lg-6 col-md-8">
                    <div class="success-card">
                        <div class="success-head">
                            <div class="check-badge"><i class="bi bi-check-lg"></i></div>
                            <h3>Berhasil Disetujui!</h3>
                            <p>Tanda Tangan Elektronik Anda telah tercatat.</p>
                        </div>

                        <div class="card-body-pad">
                            <p class="text-center text-muted mb-4">
                                Terima kasih <strong class="text-dark">{{ $signature->signer_name }}</strong>,
                                berikut status penyelesaian dokumen Anda:
                            </p>

                            @php 
                                $docList = isset($signatures) ? $signatures : collect([$signature]); 
                                $isVendor = ($signature->role === 'vendor');
                                $tagihan = $signature->documentable;
                            @endphp
                            
                            <div class="mb-4">
                                @foreach($docList as $doc)
                                    @php
                                        $jenis = $doc->document_label . '_FINAL_TTD';
                                        $arsip = $isVendor ? $tagihan->detailKontrak->arsipDokumen->where('jenis_dokumen', $jenis)->where('is_active', true)->first() : null;
                                    @endphp
                                    <div class="unified-doc-card stagger-1">
                                        <div class="udc-icon-wrap">
                                            <i class="bi bi-file-earmark-check-fill"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="fw-bold mb-1 text-dark">Berita Acara {{ $doc->document_label }}</div>
                                            <div class="d-flex flex-wrap gap-2">
                                                <span class="badge bg-success-subtle text-success border border-success-subtle"><i class="bi bi-pen-fill me-1"></i>TTE Disetujui</span>
                                                @if($isVendor && $arsip)
                                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle"><i class="bi bi-cloud-check-fill me-1"></i>File Final Terunggah</span>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="udc-action ms-auto">
                                            @if($isVendor && $arsip)
                                                <a href="{{ Storage::disk($arsip->disk)->url($arsip->path_file) }}" target="_blank" class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-bold">
                                                    <i class="bi bi-eye-fill me-1"></i> Lihat Berkas
                                                </a>
                                            @else
                                                <i class="bi bi-patch-check-fill text-success fs-3"></i>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <div class="receipt">
                                <div class="receipt-row">
                                    <i class="bi bi-calendar-check"></i>
                                    <span class="lbl">Waktu TTD</span>
                                    <span class="fw-semibold">{{ optional($signature->signed_at)->format('d M Y, H:i:s') ?? '-' }} WITA</span>
                                </div>
                                <div class="receipt-row">
                                    <i class="bi bi-hdd-network"></i>
                                    <span class="lbl">Alamat IP</span>
                                    <span class="fw-semibold">{{ $signature->ip_address ?? '-' }}</span>
                                </div>
                                <div class="receipt-row">
                                    <i class="bi bi-receipt"></i>
                                    <span class="lbl">Referensi</span>
                                    <span class="fw-semibold">{{ optional($signature->documentable)->nomor_tagihan ?? '-' }}</span>
                                </div>
                            </div>

                            <div class="text-center mt-4">
                                <p class="small text-muted mb-0">
                                    <i class="bi bi-info-circle me-1"></i>
                                    Jendela ini sudah bisa Anda tutup dengan aman.
                                </p>
                            </div>
                        </div>
                    </div>

                    <p class="text-center text-muted small mt-3 mb-0">
                        &copy; {{ date('Y') }} SIKEREN &middot; UPBU Kelas I APT Pranoto Samarinda
                    </p>
                </div>
            </div>
        </div>
        </div>
    </div>
</body>
</html>
