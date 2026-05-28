<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Persetujuan TTE - Dokumen {{ $signature->document_label }}</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('public/logo/minilogo-sikeren.png') }}">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        :root {
            --primary: #4361ee;
            --primary-dark: #3a0ca3;
            --success: #2ec4b6;
            --surface: #ffffff;
            --bg-color: #f8f9fa;
            --text-main: #2b2d42;
            --text-muted: #8d99ae;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg-color);
            background-image: 
                radial-gradient(at 0% 0%, hsla(253,16%,7%,0.03) 0, transparent 50%), 
                radial-gradient(at 50% 0%, hsla(225,39%,30%,0.03) 0, transparent 50%), 
                radial-gradient(at 100% 0%, hsla(339,49%,30%,0.03) 0, transparent 50%);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .hero-section {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            padding: 3rem 0 6rem 0;
            color: white;
            text-align: center;
            clip-path: polygon(0 0, 100% 0, 100% 85%, 0% 100%);
            animation: slideDown 0.8s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .main-container {
            margin-top: -4rem;
            animation: fadeUp 0.8s cubic-bezier(0.16, 1, 0.3, 1) 0.2s both;
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 1.5rem;
            border: 1px solid rgba(255, 255, 255, 0.5);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }

        .info-box {
            background: rgba(67, 97, 238, 0.05);
            border-left: 4px solid var(--primary);
            border-radius: 0.75rem;
            padding: 1.25rem;
            margin-bottom: 2rem;
            transition: transform 0.3s ease;
        }
        
        .info-box:hover {
            transform: translateX(5px);
        }

        .info-label {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--text-muted);
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .info-value {
            font-size: 1rem;
            font-weight: 600;
            color: var(--text-main);
        }

        .pdf-container {
            border-radius: 1rem;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            border: 1px solid rgba(0,0,0,0.05);
            background: #fff;
            height: 70vh;
            min-height: 500px;
            position: relative;
        }

        .pdf-container::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            box-shadow: inset 0 0 20px rgba(0,0,0,0.02);
            pointer-events: none;
            z-index: 10;
            border-radius: 1rem;
        }

        .btn-sign {
            background: linear-gradient(135deg, var(--success) 0%, #20a498 100%);
            border: none;
            color: white;
            padding: 1rem 2.5rem;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 50px;
            box-shadow: 0 10px 20px rgba(46, 196, 182, 0.3);
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            position: relative;
            overflow: hidden;
        }

        .btn-sign::after {
            content: '';
            position: absolute;
            top: 0; left: -100%;
            width: 50%; height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transform: skewX(-20deg);
            animation: shine 3s infinite;
        }

        .btn-sign:hover {
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 15px 25px rgba(46, 196, 182, 0.4);
            color: white;
        }

        .btn-sign:active {
            transform: translateY(1px);
        }

        .icon-pulse {
            animation: pulseIcon 2s infinite;
        }

        /* Animations */
        @keyframes slideDown {
            from { transform: translateY(-20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        @keyframes fadeUp {
            from { transform: translateY(30px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        @keyframes pulseIcon {
            0% { transform: scale(1); }
            50% { transform: scale(1.15); }
            100% { transform: scale(1); }
        }

        @keyframes shine {
            0% { left: -100%; }
            20% { left: 200%; }
            100% { left: 200%; }
        }
        
        .stagger-1 { animation: fadeUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) 0.3s both; }
        .stagger-2 { animation: fadeUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) 0.4s both; }
        .stagger-3 { animation: fadeUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) 0.5s both; }
    </style>
</head>
<body>

    <!-- Hero Header -->
    <div class="hero-section">
        <div class="container">
            <h2 class="fw-bold mb-2">Tanda Tangan Elektronik</h2>
            <p class="text-white-50 mb-0">Sistem Informasi Keuangan (SIKEREN)</p>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container main-container pb-5 flex-grow-1">
        <div class="row justify-content-center">
            <div class="col-xl-9 col-lg-10">
                <div class="glass-card p-4 p-md-5">
                    
                    <div class="text-center mb-4 stagger-1">
                        <div class="d-inline-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded-circle mb-3" style="width: 64px; height: 64px;">
                            <i class="bi bi-shield-check fs-2"></i>
                        </div>
                        <h4 class="fw-bold text-dark">Mohon Persetujuan Anda</h4>
                        <p class="text-muted">Harap tinjau dokumen di bawah ini dengan saksama sebelum memberikan persetujuan.</p>
                    </div>

                    @if(session('error'))
                        <div class="alert alert-danger border-0 shadow-sm alert-dismissible bg-danger text-white stagger-1">
                            <i class="bi bi-exclamation-octagon me-2"></i> {{ session('error') }}
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <!-- Info Box -->
                    <div class="info-box stagger-2">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="info-label">Dokumen</div>
                                <div class="info-value">Berita Acara {{ $signature->document_label }}</div>
                            </div>
                            <div class="col-md-4">
                                <div class="info-label">Referensi Tagihan</div>
                                <div class="info-value">{{ $tagihan->nomor_tagihan }}</div>
                            </div>
                            <div class="col-md-4">
                                <div class="info-label">Peran Anda</div>
                                <div class="info-value text-primary">{{ $signature->signer_name }} <span class="badge bg-primary bg-opacity-10 text-primary ms-1">{{ ucfirst(str_replace('_', ' ', $signature->role)) }}</span></div>
                            </div>
                        </div>
                    </div>

                    <!-- PDF Viewer -->
                    <div class="pdf-container stagger-3 mb-5">
                        <iframe src="{{ $pdfUrl }}" width="100%" height="100%" style="border: none;"></iframe>
                    </div>

                    <!-- Action Area -->
                    <div class="text-center stagger-3 pt-3 border-top">
                        <p class="text-muted mb-4">Dengan menekan tombol di bawah ini, saya menyatakan telah membaca, memahami, dan menyetujui seluruh isi dokumen tersebut, serta membubuhkan Tanda Tangan Elektronik secara sah.</p>
                        
                        <form action="{{ route('public.magic-link.sign', $signature->magic_token) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn-sign">
                                <i class="bi bi-check2-circle fs-4 icon-pulse"></i>
                                <span>Saya Setuju & Tanda Tangani</span>
                            </button>
                        </form>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="text-center py-4 text-muted small mt-auto">
        &copy; {{ date('Y') }} Sistem Informasi Keuangan SIKEREN - UPBU Kelas I APT Pranoto Samarinda
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
