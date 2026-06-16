<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#1d4ed8">
    <title>Portal Unggah Dokumen Kontrak Vendor &middot; SIKEREN</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
@php
    $docs = collect([$spkFinal, $spmkFinal, $ringkasanFinal]);
    $uploadedCount = $docs->filter()->count();
    $totalDocs = $docs->count();
    $progressPct = $totalDocs ? round($uploadedCount / $totalDocs * 100) : 0;
@endphp
<style>
    :root {
        --vp-primary: #2563eb;
        --vp-primary-dark: #1d4ed8;
    }
    body {
        background-color: #f4f7f6;
        font-family: 'Inter', sans-serif;
    }
    .hero-section {
        background: linear-gradient(135deg, #2563eb, #1d4ed8);
        color: white;
        padding: 3rem 1.5rem 6rem;
        text-align: center;
        border-radius: 0 0 2rem 2rem;
        margin-bottom: -3rem;
    }
    .hero-section h2 {
        font-weight: 800;
        margin-bottom: 0.5rem;
    }
    .hero-section p {
        opacity: 0.85;
    }
    .vendor-card {
        background: white;
        border-radius: 1.25rem;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
        border: none;
        margin-bottom: 2rem;
        overflow: hidden;
    }
    .vendor-card-header {
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        padding: 1.5rem;
    }
    .vendor-card-body {
        padding: 2rem 1.5rem;
    }
    /* Progress unggah */
    .upload-progress-wrap {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 1rem;
        padding: 1rem 1.25rem;
        margin-bottom: 1.5rem;
    }
    .upload-progress-bar {
        height: 8px;
        border-radius: 999px;
        background: #e2e8f0;
        overflow: hidden;
    }
    .upload-progress-bar > span {
        display: block;
        height: 100%;
        border-radius: 999px;
        background: linear-gradient(90deg, #22c55e, #16a34a);
        transition: width .4s ease;
    }
    .doc-section {
        border: 1px solid #e2e8f0;
        border-radius: 1rem;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        background: #fafafa;
        transition: all 0.3s ease;
    }
    .doc-section:hover {
        border-color: #cbd5e1;
        box-shadow: 0 4px 12px rgba(0,0,0,0.03);
    }
    .doc-section.is-uploaded {
        border-color: #86efac;
        background: #f0fdf4;
    }
    .step-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: #2563eb;
        color: white;
        font-weight: bold;
        margin-right: 0.75rem;
        flex-shrink: 0;
    }
    .is-uploaded .step-badge {
        background: #22c55e;
    }
    .file-input-wrapper {
        position: relative;
        overflow: hidden;
        display: inline-block;
        width: 100%;
    }
    .file-input-wrapper input[type=file] {
        font-size: 100px;
        position: absolute;
        left: 0;
        top: 0;
        opacity: 0;
        cursor: pointer;
        height: 100%;
    }
    .btn-download {
        background: #eff6ff;
        color: #2563eb;
        border: 1px solid #bfdbfe;
        font-weight: 600;
        padding: 0.6rem 1rem;
        border-radius: 0.5rem;
        transition: all 0.2s;
    }
    .btn-download:hover {
        background: #dbeafe;
        color: #1d4ed8;
    }
    .btn-upload {
        background: #2563eb;
        color: white;
        border: none;
        font-weight: 600;
        padding: 0.8rem 1.5rem;
        border-radius: 0.5rem;
        width: 100%;
        transition: all 0.2s;
    }
    .btn-upload:hover {
        background: #1d4ed8;
        transform: translateY(-1px);
        box-shadow: 0 4px 6px rgba(37, 99, 235, 0.2);
    }
    .instruction-list li {
        margin-bottom: 0.5rem;
        color: #475569;
    }
    /* Instruksi yang bisa dilipat (default terbuka di desktop) */
    .instruction-toggle { display: none; }

    /* ============================================================
       TAMPILAN KHUSUS HP  (viewport <= 575px)
       ============================================================ */
    @media (max-width: 575.98px) {
        body {
            /* beri ruang untuk tombol unggah sticky di bawah */
            padding-bottom: 5.5rem;
        }
        .hero-section {
            padding: 1.75rem 1.25rem 4.5rem;
            border-radius: 0 0 1.5rem 1.5rem;
        }
        .hero-section h2 { font-size: 1.4rem; }
        .hero-section p { font-size: .85rem; }

        .container.pb-5 { padding-left: .75rem; padding-right: .75rem; }

        .vendor-card {
            border-radius: 1rem;
            margin-bottom: 1rem;
        }
        .vendor-card-header { padding: 1.1rem; }
        .vendor-card-body { padding: 1.25rem 1rem; }
        .vendor-card-header h5 { font-size: 1.05rem; }

        /* badge status pindah ke bawah judul, rata kiri */
        .vendor-card-header.d-flex {
            flex-direction: column;
            align-items: flex-start !important;
            gap: .65rem;
        }

        .doc-section {
            padding: 1.1rem;
            border-radius: .85rem;
            margin-bottom: 1rem;
        }

        /* Instruksi dapat dilipat pada HP untuk hemat ruang */
        .instruction-toggle {
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
            background: #eff6ff;
            color: #1e40af;
            border: none;
            border-radius: .75rem;
            padding: .85rem 1rem;
            font-weight: 700;
            font-size: .9rem;
        }
        .instruction-toggle .bi-chevron-down { transition: transform .25s; }
        .instruction-toggle[aria-expanded="true"] .bi-chevron-down { transform: rotate(180deg); }
        .instruction-body { margin-top: .75rem; }

        /* tombol lebih besar & ramah-sentuh */
        .btn-download,
        .file-input-wrapper .btn {
            padding: .85rem 1rem !important;
            font-size: .95rem;
        }

        /* tombol unggah utama menempel di bawah layar */
        .submit-bar {
            position: fixed;
            left: 0; right: 0; bottom: 0;
            z-index: 1030;
            background: rgba(255,255,255,.96);
            backdrop-filter: blur(8px);
            border-top: 1px solid #e2e8f0;
            padding: .75rem 1rem calc(.75rem + env(safe-area-inset-bottom));
            box-shadow: 0 -4px 16px rgba(0,0,0,.06);
            margin: 0 !important;
        }
        .submit-bar.border-top { border-top: 1px solid #e2e8f0 !important; }
        .submit-bar .submit-hint { display: none; }
        .btn-upload { padding: .95rem 1.5rem; font-size: 1rem; }

        .footer-credit { margin-bottom: 1rem; }
    }
</style>
</head>
<body>

<div class="hero-section">
    <div class="container">
        <h2>Portal Vendor BLU</h2>
        <p>Unggah Dokumen Kontrak Final (Tanda Tangan Basah &amp; Stempel)</p>
    </div>
</div>

<div class="container pb-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="vendor-card">
                <div class="vendor-card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="fw-bold mb-1 text-dark">{{ $kontrak->nama_pekerjaan }}</h5>
                        <div class="text-muted small"><strong>Vendor:</strong> {{ $kontrak->vendor->nama_pihak ?? $kontrak->vendor->nama_perusahaan ?? '-' }}</div>
                    </div>
                    @if($isComplete)
                        <span class="badge bg-success rounded-pill px-3 py-2 fs-6"><i class="bi bi-check-circle me-1"></i> Semua Lengkap</span>
                    @else
                        <span class="badge bg-warning text-dark rounded-pill px-3 py-2 fs-6"><i class="bi bi-hourglass-split me-1"></i> Menunggu Upload</span>
                    @endif
                </div>

                <div class="vendor-card-body">
                    @if(session('success'))
                        <div class="alert alert-success border-0 shadow-sm rounded-3 mb-4">
                            <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
                        </div>
                    @endif
                    @if(session('error'))
                        <div class="alert alert-danger border-0 shadow-sm rounded-3 mb-4">
                            <i class="bi bi-exclamation-circle-fill me-2"></i> {{ session('error') }}
                        </div>
                    @endif
                    @if($errors->any())
                        <div class="alert alert-danger border-0 shadow-sm rounded-3 mb-4">
                            <ul class="mb-0">
                                @foreach($errors->all() as $err)
                                    <li>{{ $err }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    {{-- Progress unggah (ringkas, sangat membantu di layar HP) --}}
                    <div class="upload-progress-wrap">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="fw-bold text-dark small"><i class="bi bi-list-check me-1 text-primary"></i> Progres Unggah Dokumen</span>
                            <span class="fw-bold small {{ $isComplete ? 'text-success' : 'text-primary' }}">{{ $uploadedCount }}/{{ $totalDocs }} dokumen</span>
                        </div>
                        <div class="upload-progress-bar">
                            <span style="width: {{ $progressPct }}%"></span>
                        </div>
                    </div>

                    {{-- Instruksi: tombol toggle hanya tampil di HP --}}
                    <button type="button" class="instruction-toggle mb-2" data-bs-toggle="collapse" data-bs-target="#instruksiVendor" aria-expanded="false" aria-controls="instruksiVendor">
                        <span><i class="bi bi-info-circle-fill me-2"></i>Lihat Instruksi untuk Vendor</span>
                        <i class="bi bi-chevron-down"></i>
                    </button>
                    <div class="alert alert-info border-0 rounded-3 mb-4 shadow-sm collapse show instruction-body" id="instruksiVendor" style="background-color: #eff6ff; color: #1e40af;">
                        <h6 class="fw-bold d-none d-sm-block"><i class="bi bi-info-circle-fill me-2"></i>Instruksi untuk Vendor</h6>
                        <ul class="instruction-list ps-3 mb-0 mt-2 small">
                            <li><strong>Unduh</strong> dokumen draf final yang telah memiliki <em>QR Code</em> TTE dari PPK.</li>
                            <li><strong>Cetak (Print)</strong> dokumen tersebut.</li>
                            <li><strong>Tandatangan basah, beri stempel perusahaan</strong> (Serta <strong>Materai</strong> khusus untuk SPK).</li>
                            <li><strong>Scan</strong> menjadi format PDF (maks 10MB) lalu unggah kembali pada form di bawah ini.</li>
                            <li>Setelah semua dokumen diunggah, tagihan termin dapat mulai diproses.</li>
                        </ul>
                    </div>

                    <form action="{{ \Illuminate\Support\Facades\URL::signedRoute('public.vendor.contract-upload.store', ['id' => $kontrak->id]) }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        {{-- 1. SPK --}}
                        <div class="doc-section {{ $spkFinal ? 'is-uploaded' : '' }}">
                            <div class="d-flex align-items-center mb-3">
                                <span class="step-badge">1</span>
                                <div>
                                    <h6 class="fw-bold mb-0">Surat Perintah Kerja (SPK)</h6>
                                    <small class="{{ $spkFinal ? 'text-success fw-bold' : 'text-muted' }}">
                                        {{ $spkFinal ? '✓ Sudah Diunggah' : 'Wajib diunggah (dengan materai)' }}
                                    </small>
                                </div>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <a href="{{ \Illuminate\Support\Facades\URL::signedRoute('public.contract-tte.document', ['type' => 'spk', 'id' => $kontrak->id]) }}" class="btn btn-download w-100 d-flex align-items-center justify-content-center" target="_blank">
                                        <i class="bi bi-cloud-download me-2 fs-5"></i> Unduh SPK (TTE PPK)
                                    </a>
                                </div>
                                <div class="col-md-6">
                                    <div class="file-input-wrapper">
                                        <button type="button" class="btn btn-outline-secondary w-100" style="border-style: dashed; padding: 0.6rem 1rem;">
                                            <i class="bi bi-file-earmark-arrow-up me-2"></i> Pilih File Scan (PDF)
                                        </button>
                                        <input type="file" name="file_spk_final" accept="application/pdf" onchange="this.previousElementSibling.innerHTML = '<i class=\'bi bi-check-circle-fill text-success me-2\'></i>' + this.files[0].name">
                                    </div>
                                    @if($spkFinal)
                                        <div class="small text-success mt-1 text-truncate"><i class="bi bi-file-earmark-check"></i> File aktif: {{ $spkFinal->nama_file_asli }}</div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- 2. SPMK --}}
                        <div class="doc-section {{ $spmkFinal ? 'is-uploaded' : '' }}">
                            <div class="d-flex align-items-center mb-3">
                                <span class="step-badge">2</span>
                                <div>
                                    <h6 class="fw-bold mb-0">Surat Perintah Mulai Kerja (SPMK)</h6>
                                    <small class="{{ $spmkFinal ? 'text-success fw-bold' : 'text-muted' }}">
                                        {{ $spmkFinal ? '✓ Sudah Diunggah' : 'Wajib diunggah' }}
                                    </small>
                                </div>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <a href="{{ \Illuminate\Support\Facades\URL::signedRoute('public.contract-tte.document', ['type' => 'spmk', 'id' => $kontrak->id]) }}" class="btn btn-download w-100 d-flex align-items-center justify-content-center" target="_blank">
                                        <i class="bi bi-cloud-download me-2 fs-5"></i> Unduh SPMK (TTE PPK)
                                    </a>
                                </div>
                                <div class="col-md-6">
                                    <div class="file-input-wrapper">
                                        <button type="button" class="btn btn-outline-secondary w-100" style="border-style: dashed; padding: 0.6rem 1rem;">
                                            <i class="bi bi-file-earmark-arrow-up me-2"></i> Pilih File Scan (PDF)
                                        </button>
                                        <input type="file" name="file_spmk_final" accept="application/pdf" onchange="this.previousElementSibling.innerHTML = '<i class=\'bi bi-check-circle-fill text-success me-2\'></i>' + this.files[0].name">
                                    </div>
                                    @if($spmkFinal)
                                        <div class="small text-success mt-1 text-truncate"><i class="bi bi-file-earmark-check"></i> File aktif: {{ $spmkFinal->nama_file_asli }}</div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- 3. Ringkasan Kontrak --}}
                        <div class="doc-section {{ $ringkasanFinal ? 'is-uploaded' : '' }}">
                            <div class="d-flex align-items-center mb-3">
                                <span class="step-badge">3</span>
                                <div>
                                    <h6 class="fw-bold mb-0">Ringkasan Kontrak</h6>
                                    <small class="{{ $ringkasanFinal ? 'text-success fw-bold' : 'text-muted' }}">
                                        {{ $ringkasanFinal ? '✓ Sudah Diunggah' : 'Wajib diunggah' }}
                                    </small>
                                </div>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <a href="{{ \Illuminate\Support\Facades\URL::signedRoute('public.contract-tte.document', ['type' => 'ringkasan_kontrak', 'id' => $kontrak->id]) }}" class="btn btn-download w-100 d-flex align-items-center justify-content-center" target="_blank">
                                        <i class="bi bi-cloud-download me-2 fs-5"></i> Unduh Ringkasan (TTE PPK)
                                    </a>
                                </div>
                                <div class="col-md-6">
                                    <div class="file-input-wrapper">
                                        <button type="button" class="btn btn-outline-secondary w-100" style="border-style: dashed; padding: 0.6rem 1rem;">
                                            <i class="bi bi-file-earmark-arrow-up me-2"></i> Pilih File Scan (PDF)
                                        </button>
                                        <input type="file" name="file_ringkasan_final" accept="application/pdf" onchange="this.previousElementSibling.innerHTML = '<i class=\'bi bi-check-circle-fill text-success me-2\'></i>' + this.files[0].name">
                                    </div>
                                    @if($ringkasanFinal)
                                        <div class="small text-success mt-1 text-truncate"><i class="bi bi-file-earmark-check"></i> File aktif: {{ $ringkasanFinal->nama_file_asli }}</div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="submit-bar mt-4 pt-3 border-top">
                            <button type="submit" class="btn btn-upload btn-lg">
                                <i class="bi bi-cloud-arrow-up-fill me-2"></i> Unggah Dokumen Terpilih
                            </button>
                            <div class="text-center mt-2 small text-muted submit-hint">
                                Anda dapat mengunggah dokumen satu per satu atau sekaligus.
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <div class="text-center text-muted small mt-4 footer-credit">
                &copy; {{ date('Y') }} Sistem Informasi Keuangan BLU. Hak Cipta Dilindungi.
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Pada HP, instruksi tampil terlipat secara default; di layar lebar tetap terbuka.
    document.addEventListener('DOMContentLoaded', function () {
        var instruksi = document.getElementById('instruksiVendor');
        var toggle = document.querySelector('.instruction-toggle');
        if (!instruksi || !toggle) return;

        var mobile = window.matchMedia('(max-width: 575.98px)');
        function sync(e) {
            if (e.matches) {
                instruksi.classList.remove('show');
                toggle.setAttribute('aria-expanded', 'false');
            } else {
                instruksi.classList.add('show');
            }
        }
        sync(mobile);
        mobile.addEventListener('change', sync);

        instruksi.addEventListener('shown.bs.collapse', function () { toggle.setAttribute('aria-expanded', 'true'); });
        instruksi.addEventListener('hidden.bs.collapse', function () { toggle.setAttribute('aria-expanded', 'false'); });
    });
</script>
</body>
</html>
