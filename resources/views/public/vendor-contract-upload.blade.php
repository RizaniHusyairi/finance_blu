<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Unggah Dokumen Kontrak Vendor &middot; SIKEREN</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
<style>
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
</style>
</head>
<body>

<div class="hero-section">
    <div class="container">
        <h2>Portal Vendor BLU</h2>
        <p>Unggah Dokumen Kontrak Final (Tanda Tangan Basah & Stempel)</p>
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

                    <div class="alert alert-info border-0 rounded-3 mb-4 shadow-sm" style="background-color: #eff6ff; color: #1e40af;">
                        <h6 class="fw-bold"><i class="bi bi-info-circle-fill me-2"></i>Instruksi untuk Vendor</h6>
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

                        <div class="mt-4 pt-3 border-top">
                            <button type="submit" class="btn btn-upload btn-lg">
                                <i class="bi bi-cloud-arrow-up-fill me-2"></i> Unggah Dokumen Terpilih
                            </button>
                            <div class="text-center mt-2 small text-muted">
                                Anda dapat mengunggah dokumen satu per satu atau sekaligus.
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <div class="text-center text-muted small mt-4">
                &copy; {{ date('Y') }} Sistem Informasi Keuangan BLU. Hak Cipta Dilindungi.
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
