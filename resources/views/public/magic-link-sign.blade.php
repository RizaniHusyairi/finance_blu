<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Persetujuan TTE - {{ $signature->signer_name }}</title>

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('public/logo/minilogo-sikeren.png') }}">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        :root {
            --primary: #4f46e5;
            --primary-dark: #3730a3;
            --primary-soft: rgba(79, 70, 229, 0.08);
            --accent: #06b6d4;
            --success: #10b981;
            --success-dark: #059669;
            --bg-color: #eef1f8;
            --surface: #ffffff;
            --text-main: #1e2235;
            --text-muted: #6b7280;
            --border-soft: rgba(17, 24, 39, 0.08);
        }

        * { box-sizing: border-box; }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg-color);
            background-image:
                radial-gradient(circle at 0% 0%, rgba(79,70,229,0.10), transparent 38%),
                radial-gradient(circle at 100% 0%, rgba(6,182,212,0.10), transparent 42%),
                radial-gradient(circle at 50% 120%, rgba(79,70,229,0.06), transparent 50%);
            background-attachment: fixed;
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            margin: 0;
            padding-bottom: 110px; /* ruang untuk action bar */
        }

        /* ===== Top Brand Bar ===== */
        .brand-bar {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 60%, #312e81 100%);
            color: #fff;
            position: relative;
            overflow: hidden;
        }
        .brand-bar::after {
            content: '';
            position: absolute;
            inset: 0;
            background-image: radial-gradient(circle at 20% 20%, rgba(255,255,255,0.12), transparent 25%),
                              radial-gradient(circle at 85% 30%, rgba(6,182,212,0.25), transparent 30%);
            pointer-events: none;
        }
        .brand-inner {
            position: relative;
            z-index: 1;
            padding: 1.5rem 0 4.5rem;
            text-align: center;
        }
        .brand-logo {
            width: 54px; height: 54px;
            border-radius: 16px;
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(6px);
            border: 1px solid rgba(255,255,255,0.25);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: .9rem;
            box-shadow: 0 8px 24px rgba(0,0,0,0.15);
        }
        .brand-logo i { font-size: 1.7rem; }
        .brand-title { font-weight: 800; letter-spacing: -.5px; margin: 0; font-size: 1.55rem; }
        .brand-sub { color: rgba(255,255,255,0.75); font-size: .9rem; margin-top: .25rem; }
        .trust-row { margin-top: 1rem; display: flex; gap: .5rem; justify-content: center; flex-wrap: wrap; }
        .trust-pill {
            background: rgba(255,255,255,0.12);
            border: 1px solid rgba(255,255,255,0.2);
            color: #fff;
            font-size: .72rem;
            font-weight: 600;
            padding: .35rem .75rem;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            gap: .35rem;
        }

        /* ===== Main Card ===== */
        .main-wrap { margin-top: -3.2rem; animation: fadeUp .7s cubic-bezier(.16,1,.3,1) both; }
        .surface-card {
            background: var(--surface);
            border-radius: 1.5rem;
            border: 1px solid var(--border-soft);
            box-shadow: 0 24px 60px -20px rgba(30,34,53,0.25);
            overflow: hidden;
        }

        .card-pad { padding: 1.75rem; }
        @media (min-width: 768px) { .card-pad { padding: 2.25rem; } }

        .greeting-icon {
            width: 60px; height: 60px;
            border-radius: 18px;
            background: var(--primary-soft);
            color: var(--primary);
            display: inline-flex; align-items: center; justify-content: center;
            font-size: 1.7rem;
        }

        .chip-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: .75rem; }
        .meta-chip {
            background: #f7f8fc;
            border: 1px solid var(--border-soft);
            border-radius: 1rem;
            padding: .85rem 1rem;
            transition: transform .25s ease, box-shadow .25s ease;
        }
        .meta-chip:hover { transform: translateY(-2px); box-shadow: 0 10px 24px -12px rgba(30,34,53,0.25); }
        .meta-label { font-size: .68rem; text-transform: uppercase; letter-spacing: 1px; color: var(--text-muted); font-weight: 700; }
        .meta-value { font-size: .98rem; font-weight: 700; color: var(--text-main); margin-top: .15rem; }

        /* ===== Document Tabs ===== */
        .doc-tabs { display: flex; gap: .5rem; flex-wrap: wrap; }
        .doc-tab {
            border: 1px solid var(--border-soft);
            background: #fff;
            border-radius: 12px;
            padding: .6rem .95rem;
            font-size: .85rem;
            font-weight: 700;
            color: var(--text-muted);
            cursor: pointer;
            display: inline-flex; align-items: center; gap: .5rem;
            transition: all .2s ease;
        }
        .doc-tab:hover { border-color: var(--primary); color: var(--primary); }
        .doc-tab.active {
            background: var(--primary);
            border-color: var(--primary);
            color: #fff;
            box-shadow: 0 8px 18px -8px rgba(79,70,229,.6);
        }
        .doc-tab .num {
            width: 20px; height: 20px; border-radius: 6px;
            background: rgba(0,0,0,.06);
            display: inline-flex; align-items: center; justify-content: center;
            font-size: .72rem;
        }
        .doc-tab.active .num { background: rgba(255,255,255,.22); }

        /* ===== Viewer ===== */
        .viewer-shell {
            border: 1px solid var(--border-soft);
            border-radius: 1.1rem;
            overflow: hidden;
            background: #fff;
            box-shadow: 0 14px 40px -22px rgba(30,34,53,0.4);
        }
        .viewer-toolbar {
            display: flex; align-items: center; justify-content: space-between;
            gap: .75rem;
            padding: .7rem 1rem;
            background: #fbfbfe;
            border-bottom: 1px solid var(--border-soft);
        }
        .viewer-toolbar .doc-name { font-weight: 700; font-size: .92rem; display: inline-flex; align-items: center; gap: .5rem; }
        .pdf-frame { width: 100%; height: 64vh; min-height: 460px; border: none; display: block; background: #f1f2f7; }
        .doc-pane { display: none; }
        .doc-pane.active { display: block; animation: fadeIn .3s ease; }

        /* ===== Agreement / Action ===== */
        .agree-box {
            background: linear-gradient(135deg, #f7f8fc, #eef1f8);
            border: 1px dashed rgba(79,70,229,.35);
            border-radius: 1.1rem;
            padding: 1.1rem 1.25rem;
        }
        .form-check-input { width: 1.25em; height: 1.25em; margin-top: .15em; cursor: pointer; }
        .form-check-input:checked { background-color: var(--primary); border-color: var(--primary); }

        /* ===== Sticky Action Bar ===== */
        .action-bar {
            position: fixed; left: 0; right: 0; bottom: 0;
            background: rgba(255,255,255,0.85);
            backdrop-filter: blur(14px);
            border-top: 1px solid var(--border-soft);
            box-shadow: 0 -10px 40px -20px rgba(30,34,53,0.4);
            z-index: 30;
        }
        .action-bar-inner { padding: .85rem 0; display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap; }
        .progress-mini { font-size: .82rem; color: var(--text-muted); font-weight: 600; }

        .btn-sign {
            background: linear-gradient(135deg, var(--success) 0%, var(--success-dark) 100%);
            border: none; color: #fff;
            padding: .85rem 2rem;
            font-size: 1.02rem; font-weight: 700;
            border-radius: 14px;
            box-shadow: 0 12px 26px -10px rgba(16,185,129,.7);
            transition: all .25s cubic-bezier(.16,1,.3,1);
            display: inline-flex; align-items: center; gap: .6rem;
            position: relative; overflow: hidden;
        }
        .btn-sign:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 16px 30px -10px rgba(16,185,129,.8); color:#fff; }
        .btn-sign:active:not(:disabled) { transform: translateY(0); }
        .btn-sign:disabled { background: #cbd2e0; box-shadow: none; cursor: not-allowed; }
        .btn-sign::after {
            content: ''; position: absolute; top: 0; left: -120%;
            width: 60%; height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,.35), transparent);
            transform: skewX(-20deg);
        }
        .btn-sign:not(:disabled)::after { animation: shine 3.2s infinite; }

        .icon-pulse { animation: pulseIcon 2s infinite; }

        /* ===== Animations ===== */
        @keyframes fadeUp { from { transform: translateY(28px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes pulseIcon { 0%,100% { transform: scale(1); } 50% { transform: scale(1.15); } }
        @keyframes shine { 0% { left: -120%; } 35% { left: 130%; } 100% { left: 130%; } }
        .stagger-1 { animation: fadeUp .6s cubic-bezier(.16,1,.3,1) .15s both; }
        .stagger-2 { animation: fadeUp .6s cubic-bezier(.16,1,.3,1) .28s both; }
        .stagger-3 { animation: fadeUp .6s cubic-bezier(.16,1,.3,1) .4s both; }
    </style>
</head>
<body>

    <!-- Brand Bar -->
    <header class="brand-bar">
        <div class="container brand-inner">
            <div class="brand-logo"><i class="bi bi-patch-check-fill"></i></div>
            <h1 class="brand-title">Tanda Tangan Elektronik</h1>
            <div class="brand-sub">Sistem Informasi Keuangan (SIKEREN)</div>
            <div class="trust-row">
                <span class="trust-pill"><i class="bi bi-shield-lock-fill"></i> Tautan Aman</span>
                <span class="trust-pill"><i class="bi bi-clock-history"></i> Tercatat &amp; Sah</span>
                <span class="trust-pill"><i class="bi bi-fingerprint"></i> Verifikasi Identitas</span>
            </div>
        </div>
    </header>

    <!-- Main -->
    <main class="container main-wrap pb-5 flex-grow-1">
        <div class="row justify-content-center">
            <div class="col-xl-9 col-lg-11">
                <div class="surface-card">
                    <div class="card-pad">

                        <!-- Greeting -->
                        <div class="d-flex align-items-center gap-3 mb-4 stagger-1">
                            <div class="greeting-icon"><i class="bi bi-hand-index-thumb"></i></div>
                            <div>
                                <h4 class="fw-bold mb-1">Halo, {{ $signature->signer_name }} 👋</h4>
                                <p class="text-muted mb-0">Mohon tinjau {{ $documents->count() > 1 ? $documents->count().' dokumen' : 'dokumen' }} berikut, lalu berikan persetujuan Tanda Tangan Elektronik Anda.</p>
                            </div>
                        </div>

                        @if(session('error'))
                            <div class="alert alert-danger border-0 shadow-sm d-flex align-items-center gap-2 stagger-1">
                                <i class="bi bi-exclamation-octagon-fill fs-5"></i>
                                <div>{{ session('error') }}</div>
                            </div>
                        @endif

                        <!-- Meta Chips -->
                        <div class="chip-grid mb-4 stagger-2">
                            <div class="meta-chip">
                                <div class="meta-label">Referensi Tagihan</div>
                                <div class="meta-value">{{ $tagihan->nomor_tagihan }}</div>
                            </div>
                            <div class="meta-chip">
                                <div class="meta-label">Peran Anda</div>
                                <div class="meta-value text-primary">{{ ucwords(str_replace('_', ' ', $signature->role)) }}</div>
                            </div>
                            <div class="meta-chip">
                                <div class="meta-label">Jumlah Dokumen</div>
                                <div class="meta-value">{{ $documents->count() }} Berkas</div>
                            </div>
                        </div>

                        <!-- Document Switcher -->
                        @if($documents->count() > 1)
                            <div class="mb-3 stagger-2">
                                <div class="meta-label mb-2">Pilih Dokumen untuk Ditinjau</div>
                                <div class="doc-tabs" id="docTabs">
                                    @foreach($documents as $i => $doc)
                                        <button type="button" class="doc-tab {{ $i === 0 ? 'active' : '' }}" data-target="pane-{{ $i }}">
                                            <span class="num">{{ $i + 1 }}</span>
                                            Berita Acara {{ $doc['signature']->document_label }}
                                            @if($doc['signature']->status === 'signed')<i class="bi bi-check-circle-fill text-success"></i>@endif
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <!-- Viewer Panes -->
                        <div class="stagger-3">
                            @foreach($documents as $i => $doc)
                                <div class="doc-pane {{ $i === 0 ? 'active' : '' }}" id="pane-{{ $i }}">
                                    <div class="viewer-shell mb-2">
                                        <div class="viewer-toolbar">
                                            <span class="doc-name">
                                                <i class="bi bi-file-earmark-pdf-fill text-danger"></i>
                                                Berita Acara {{ $doc['signature']->document_label }}
                                                @if($doc['signature']->status === 'signed')
                                                    <span class="badge bg-success-subtle text-success ms-1"><i class="bi bi-check-circle me-1"></i>Disetujui</span>
                                                @endif
                                            </span>
                                            <a href="{{ $doc['pdfUrl'] }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-box-arrow-up-right me-1"></i> Buka di Tab Baru
                                            </a>
                                        </div>
                                        <iframe src="{{ $doc['pdfUrl'] }}" class="pdf-frame" loading="lazy"></iframe>
                                    </div>
                                    <p class="text-muted small text-center mb-0">
                                        <i class="bi bi-info-circle me-1"></i>
                                        Gulir di dalam pratinjau untuk membaca seluruh halaman dokumen.
                                    </p>
                                </div>
                            @endforeach
                        </div>

                        <!-- Agreement -->
                        <div class="agree-box mt-4 stagger-3">
                            <div class="form-check d-flex align-items-start gap-2 m-0">
                                <input class="form-check-input flex-shrink-0" type="checkbox" id="agreeCheck">
                                <label class="form-check-label small" for="agreeCheck">
                                    Saya menyatakan telah <strong>membaca, memahami, dan menyetujui</strong> seluruh isi
                                    {{ $documents->count() > 1 ? 'dokumen di atas' : 'dokumen tersebut' }}, serta membubuhkan
                                    <strong>Tanda Tangan Elektronik</strong> secara sah dan sadar tanpa paksaan.
                                </label>
                            </div>
                        </div>

                    </div>
                </div>

                <p class="text-center text-muted small mt-3 mb-0">
                    <i class="bi bi-shield-check me-1"></i>
                    Persetujuan Anda akan dicatat beserta waktu dan alamat IP sebagai bukti sah.
                </p>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="text-center py-3 text-muted small">
        &copy; {{ date('Y') }} SIKEREN &middot; UPBU Kelas I APT Pranoto Samarinda
    </footer>

    <!-- Sticky Action Bar -->
    <div class="action-bar">
        <div class="container action-bar-inner">
            <div class="progress-mini">
                <i class="bi bi-collection me-1"></i>
                {{ $documents->count() }} dokumen siap ditandatangani sekaligus
            </div>
            <form action="{{ route('public.magic-link.sign', $token) }}" method="POST" id="signForm" class="m-0">
                @csrf
                <button type="submit" class="btn-sign" id="signBtn" disabled>
                    <i class="bi bi-pen-fill fs-5 icon-pulse"></i>
                    <span>Setujui &amp; Tanda Tangani Semua</span>
                </button>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Tab switching
        document.querySelectorAll('.doc-tab').forEach(function (tab) {
            tab.addEventListener('click', function () {
                document.querySelectorAll('.doc-tab').forEach(t => t.classList.remove('active'));
                document.querySelectorAll('.doc-pane').forEach(p => p.classList.remove('active'));
                tab.classList.add('active');
                var pane = document.getElementById(tab.dataset.target);
                if (pane) pane.classList.add('active');
            });
        });

        // Enable sign button only after agreement
        var agree = document.getElementById('agreeCheck');
        var signBtn = document.getElementById('signBtn');
        agree.addEventListener('change', function () {
            signBtn.disabled = !this.checked;
        });

        // Prevent double submit
        document.getElementById('signForm').addEventListener('submit', function () {
            signBtn.disabled = true;
            signBtn.querySelector('span').textContent = 'Memproses...';
        });
    </script>
</body>
</html>
