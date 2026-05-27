<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @php
        $documentLabel = $documentLabel ?? 'SPP';
        $documentNumber = $documentNumber ?? ($spp?->nomor_spp ?? '-');
        $documentType = $documentType ?? strtolower($documentLabel);
        $documentDate = $documentDate ?? ($spp?->tanggal_spp ?? null);
        $documentAmount = $documentAmount ?? (float) ($spp?->nominal_spp ?? 0);
        $document = $document ?? $spp;
        $documentKind = $spp?->jenis_tagihan ?? $spp?->tagihan?->tipe_tagihan ?? $document?->spp?->tagihan?->tipe_tagihan ?? $document?->spm?->spp?->tagihan?->tipe_tagihan ?? $document?->npi?->spm?->spp?->tagihan?->tipe_tagihan ?? '-';
    @endphp
    <title>TTE {{ $documentLabel }} {{ $documentNumber }}</title>
    <style>
        :root {
            --ink: #172033;
            --muted: #667085;
            --line: #d8e0ec;
            --blue: #2563eb;
            --cyan: #0891b2;
            --green: #059669;
            --rose: #e11d48;
            --paper: #ffffff;
            --wash: #f5f8fc;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            color: var(--ink);
            background:
                linear-gradient(120deg, rgba(37, 99, 235, .10), rgba(8, 145, 178, .08) 38%, rgba(5, 150, 105, .10)),
                var(--wash);
        }

        .hero {
            position: relative;
            overflow: hidden;
            padding: 34px 18px 28px;
            color: #fff;
            background: linear-gradient(135deg, #153e8a 0%, #087d97 52%, #087044 100%);
        }

        .hero::after {
            content: "";
            position: absolute;
            inset: auto 0 0;
            height: 4px;
            background: linear-gradient(90deg, #38bdf8, #22c55e, #f43f5e, #38bdf8);
            background-size: 220% 100%;
            animation: ribbon 5s linear infinite;
        }

        .wrap {
            width: min(1080px, 100%);
            margin: 0 auto;
        }

        .hero-grid {
            position: relative;
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 22px;
            align-items: center;
            z-index: 1;
        }

        .eyebrow {
            margin: 0 0 7px;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: .12em;
            text-transform: uppercase;
            opacity: .82;
        }

        h1 {
            margin: 0;
            max-width: 720px;
            font-size: clamp(28px, 4vw, 46px);
            line-height: 1.05;
            letter-spacing: 0;
        }

        .subtitle {
            margin: 12px 0 0;
            max-width: 760px;
            color: rgba(255, 255, 255, .82);
            line-height: 1.6;
        }

        .actionbar {
            margin-top: 18px;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .doc-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 42px;
            padding: 10px 16px;
            border: 1px solid rgba(255, 255, 255, .42);
            border-radius: 8px;
            color: #0f172a;
            background: #fff;
            text-decoration: none;
            font-size: 14px;
            font-weight: 850;
            box-shadow: 0 12px 24px rgba(15, 23, 42, .18);
            transition: transform .2s ease, box-shadow .2s ease;
        }

        .doc-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 16px 30px rgba(15, 23, 42, .22);
        }

        .seal {
            position: relative;
            width: 132px;
            aspect-ratio: 1;
            border: 1px solid rgba(255, 255, 255, .36);
            border-radius: 50%;
            display: grid;
            place-items: center;
            background: rgba(255, 255, 255, .12);
            box-shadow: inset 0 0 0 8px rgba(255, 255, 255, .08);
            animation: floaty 4s ease-in-out infinite;
        }

        .seal::before,
        .seal::after {
            content: "";
            position: absolute;
            inset: 14px;
            border-radius: 50%;
            border: 1px dashed rgba(255, 255, 255, .44);
            animation: spin 14s linear infinite;
        }

        .seal::after {
            inset: 28px;
            animation-duration: 9s;
            animation-direction: reverse;
        }

        .seal strong {
            position: relative;
            font-size: 29px;
            letter-spacing: 0;
            z-index: 1;
        }

        main {
            width: min(1080px, calc(100% - 36px));
            margin: -20px auto 42px;
            position: relative;
        }

        .panel {
            border: 1px solid rgba(23, 32, 51, .08);
            border-radius: 8px;
            background: var(--paper);
            box-shadow: 0 16px 42px rgba(15, 23, 42, .10);
            overflow: hidden;
        }

        .scan-strip {
            position: relative;
            padding: 14px 18px;
            background: #0f172a;
            color: #dbeafe;
            font-size: 13px;
            overflow: hidden;
        }

        .scan-strip::after {
            content: "";
            position: absolute;
            top: 0;
            bottom: 0;
            width: 84px;
            left: -90px;
            background: linear-gradient(90deg, transparent, rgba(125, 211, 252, .32), transparent);
            animation: scanline 3.2s ease-in-out infinite;
        }

        .content {
            display: grid;
            grid-template-columns: 1fr 340px;
            gap: 0;
        }

        .section {
            padding: 24px;
            border-bottom: 1px solid var(--line);
        }

        .section:last-child { border-bottom: 0; }

        .side {
            border-left: 1px solid var(--line);
            background: #f8fbff;
        }

        .label {
            margin: 0 0 6px;
            color: var(--muted);
            font-size: 12px;
            font-weight: 800;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .value {
            margin: 0;
            font-weight: 750;
            line-height: 1.35;
            word-break: break-word;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px;
        }

        .meta-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 1px;
            background: var(--line);
        }

        .meta-cell {
            padding: 16px;
            background: #fff;
        }

        .status {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            border-radius: 999px;
            color: #ecfdf5;
            background: var(--green);
            font-size: 13px;
            font-weight: 800;
        }

        .signature-panel {
            position: relative;
            overflow: hidden;
            border: 1px solid #b9d6ff;
            border-radius: 8px;
            background: linear-gradient(135deg, #f8fbff 0%, #ecfeff 52%, #f0fdf4 100%);
        }

        .signature-panel::before {
            content: "";
            position: absolute;
            inset: 0 0 auto;
            height: 3px;
            background: linear-gradient(90deg, var(--blue), var(--cyan), var(--green));
            background-size: 180% 100%;
            animation: ribbon 4s linear infinite;
        }

        .signature-inner {
            position: relative;
            padding: 18px;
        }

        .signature-grid {
            display: grid;
            grid-template-columns: 1.05fr .95fr;
            gap: 16px;
            align-items: start;
        }

        .signature-list {
            display: grid;
            gap: 10px;
        }

        .signature-item {
            display: grid;
            grid-template-columns: 128px 1fr;
            gap: 10px;
            padding: 10px 0;
            border-bottom: 1px solid rgba(23, 32, 51, .08);
        }

        .signature-item:last-child {
            border-bottom: 0;
        }

        .hash-box {
            padding: 15px;
            border: 1px solid rgba(8, 145, 178, .25);
            border-radius: 8px;
            background: rgba(255, 255, 255, .72);
        }

        .hash-state {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 7px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 850;
        }

        .hash-state-ok {
            color: #047857;
            background: #d1fae5;
        }

        .hash-state-bad {
            color: #be123c;
            background: #ffe4e6;
        }

        .hash-value {
            margin-top: 12px;
            padding: 11px;
            border-radius: 8px;
            color: #0f172a;
            background: #eef6ff;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono", monospace;
            font-size: 12px;
            line-height: 1.55;
            word-break: break-all;
        }

        .pulse {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: currentColor;
            box-shadow: 0 0 0 0 rgba(236, 253, 245, .9);
            animation: pulse 1.8s ease-out infinite;
        }

        .timeline {
            display: grid;
            gap: 14px;
        }

        .verify-row {
            display: grid;
            grid-template-columns: 42px 1fr;
            gap: 14px;
            align-items: start;
            animation: rise .55s ease both;
        }

        .verify-row:nth-child(2) { animation-delay: .08s; }
        .verify-row:nth-child(3) { animation-delay: .16s; }
        .verify-row:nth-child(4) { animation-delay: .24s; }

        .step-dot {
            width: 42px;
            aspect-ratio: 1;
            border-radius: 50%;
            display: grid;
            place-items: center;
            color: #fff;
            background: linear-gradient(135deg, var(--blue), var(--cyan));
            font-weight: 850;
            box-shadow: 0 8px 16px rgba(37, 99, 235, .24);
        }

        .verify-box {
            padding: 15px;
            border: 1px solid var(--line);
            border-radius: 8px;
            background: #fff;
        }

        .verify-top {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            align-items: flex-start;
        }

        .role {
            color: var(--cyan);
            font-size: 13px;
            font-weight: 850;
        }

        .small {
            color: var(--muted);
            font-size: 13px;
            line-height: 1.55;
        }

        .pill {
            padding: 5px 9px;
            border-radius: 999px;
            color: var(--green);
            background: #dcfce7;
            font-size: 11px;
            font-weight: 850;
            white-space: nowrap;
        }

        .identity {
            display: grid;
            gap: 12px;
        }

        .identity-row {
            padding: 14px;
            border: 1px solid var(--line);
            border-radius: 8px;
            background: #fff;
        }

        .footer-note {
            padding: 18px 24px;
            color: #475467;
            background: #f8fafc;
            border-top: 1px solid var(--line);
            font-size: 13px;
            line-height: 1.6;
        }

        @keyframes ribbon {
            to { background-position: 220% 0; }
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        @keyframes floaty {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-7px); }
        }

        @keyframes scanline {
            0% { left: -90px; }
            55%, 100% { left: 100%; }
        }

        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(236, 253, 245, .9); }
            70% { box-shadow: 0 0 0 11px rgba(236, 253, 245, 0); }
            100% { box-shadow: 0 0 0 0 rgba(236, 253, 245, 0); }
        }

        @keyframes rise {
            from { opacity: 0; transform: translateY(14px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 840px) {
            .hero-grid,
            .content,
            .grid,
            .meta-grid,
            .signature-grid,
            .signature-item {
                grid-template-columns: 1fr;
            }

            .seal {
                width: 104px;
            }

            .side {
                border-left: 0;
                border-top: 1px solid var(--line);
            }

            .verify-top {
                display: grid;
            }
        }
    </style>
</head>
<body>
    <header class="hero">
        <div class="wrap hero-grid">
            <div>
                <p class="eyebrow">Tanda Tangan Elektronik {{ $documentLabel }}</p>
                <h1>Dokumen {{ $documentLabel }} telah terverifikasi penuh</h1>
                <p class="subtitle">Halaman ini dibuka dari QR TTE yang diterbitkan sistem setelah seluruh verifikator menyetujui dokumen.</p>
                <div class="actionbar">
                    <a class="doc-button" href="{{ $documentUrl }}" target="_blank" rel="noopener">Lihat Dokumen {{ $documentLabel }}</a>
                </div>
            </div>
            <div class="seal" aria-label="Stempel TTE sah">
                <strong>TTE</strong>
            </div>
        </div>
    </header>

    <main>
        <div class="panel">
            <div class="scan-strip">
                Validasi scan: user {{ $scanInfo['user_id'] }} | IP {{ $scanInfo['ip_address'] }} | {{ $scanInfo['timestamp']->format('d M Y H:i:s') }}
            </div>

            <div class="content">
                <div>
                    <section class="section">
                        <div class="grid">
                            <div>
                                <p class="label">Nomor {{ $documentLabel }}</p>
                                <p class="value">{{ $documentNumber }}</p>
                            </div>
                            <div>
                                <p class="label">Status TTE</p>
                                <span class="status"><span class="pulse"></span> Sah Terverifikasi</span>
                            </div>
                            <div>
                                <p class="label">Jenis Tagihan</p>
                                <p class="value">{{ $documentKind }}</p>
                            </div>
                            <div>
                                <p class="label">Nilai {{ $documentLabel }}</p>
                                <p class="value">Rp {{ number_format((float) $documentAmount, 0, ',', '.') }}</p>
                            </div>
                        </div>
                    </section>

                    <section class="section">
                        <div class="signature-panel">
                            <div class="signature-inner">
                                <p class="label">Telah Ditandatangani Oleh</p>
                                <div class="signature-grid">
                                    <div class="signature-list">
                                        <div class="signature-item">
                                            <div class="small">Nama</div>
                                            <div class="value">{{ $signerInfo['nama'] }}</div>
                                        </div>
                                        <div class="signature-item">
                                            <div class="small">NIP</div>
                                            <div class="value">{{ $signerInfo['nip'] }}</div>
                                        </div>
                                        <div class="signature-item">
                                            <div class="small">Jabatan</div>
                                            <div class="value">{{ $signerInfo['jabatan'] }}</div>
                                        </div>
                                        <div class="signature-item">
                                            <div class="small">Unit Kerja</div>
                                            <div class="value">{{ $signerInfo['unit_kerja'] }}</div>
                                        </div>
                                        <div class="signature-item">
                                            <div class="small">Instansi</div>
                                            <div class="value">{{ $signerInfo['instansi'] }}</div>
                                        </div>
                                        <div class="signature-item">
                                            <div class="small">Ditandatangani pada</div>
                                            <div class="value">{{ optional($signerInfo['signed_at'])->format('d M Y H:i:s') ?? '-' }}</div>
                                        </div>
                                    </div>

                                    <div class="hash-box">
                                        <p class="label">Hash Dokumen</p>
                                        <span class="hash-state {{ $hashStatus === 'cocok' ? 'hash-state-ok' : 'hash-state-bad' }}">
                                            <span class="pulse"></span>
                                            {{ $hashStatus === 'cocok' ? 'Cocok' : 'Tidak Cocok' }}
                                        </span>
                                        <div class="hash-value">{{ $documentHash }}</div>
                                        <p class="small" style="margin: 10px 0 0;">
                                            Nilai hash ini dihitung dari identitas {{ $documentLabel }} dan riwayat approval saat halaman dibuka.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="section">
                        <p class="label">Informasi Verifikator</p>
                        <div class="timeline">
                            @foreach($verifikators as $verifikator)
                                <div class="verify-row">
                                    <div class="step-dot">{{ $verifikator['step'] }}</div>
                                    <div class="verify-box">
                                        <div class="verify-top">
                                            <div>
                                                <div class="role">{{ $verifikator['role'] }}</div>
                                                <div class="value">{{ $verifikator['name'] }}</div>
                                            </div>
                                            <span class="pill">{{ $verifikator['status'] }}</span>
                                        </div>
                                        <div class="small" style="margin-top: 10px;">
                                            ID user: {{ $verifikator['user_id'] ?? '-' }}<br>
                                            Waktu verifikasi: {{ optional($verifikator['acted_at'])->format('d M Y H:i:s') ?? '-' }}<br>
                                            IP verifikasi: {{ $verifikator['ip_address'] ?? '-' }}
                                            @if($verifikator['catatan'])
                                                <br>Catatan: {{ $verifikator['catatan'] }}
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </section>
                </div>

                <aside class="side">
                    <section class="section">
                        <p class="label">Identitas Scan</p>
                        <div class="identity">
                            <div class="identity-row">
                                <p class="label">ID User</p>
                                <p class="value">{{ $scanInfo['user_id'] }}</p>
                            </div>
                            <div class="identity-row">
                                <p class="label">Timestamp</p>
                                <p class="value">{{ $scanInfo['timestamp']->format('d M Y H:i:s') }}</p>
                            </div>
                            <div class="identity-row">
                                <p class="label">IP Address</p>
                                <p class="value">{{ $scanInfo['ip_address'] }}</p>
                            </div>
                            <div class="identity-row">
                                <p class="label">ID Dokumen</p>
                                <p class="value">{{ $scanInfo['dokumen_id'] }}</p>
                            </div>
                        </div>
                    </section>

                    <section class="section">
                        <p class="label">Dokumen</p>
                        <p class="small">
                            Dibuat oleh {{ $document?->dibuatOleh?->name ?? '-' }} pada
                            {{ optional($document?->created_at)->format('d M Y H:i') ?? '-' }}.
                        </p>
                        <p class="small">
                            Workflow: {{ $workflow->definition?->nama ?? $workflow->definition?->kode ?? '-' }}.
                        </p>
                    </section>
                </aside>
            </div>

            <div class="meta-grid">
                <div class="meta-cell">
                    <p class="label">ID Dokumen</p>
                    <p class="value">{{ $documentLabel }}-{{ $document?->getKey() ?? $scanInfo['dokumen_id'] }}</p>
                </div>
                <div class="meta-cell">
                    <p class="label">Tanggal {{ $documentLabel }}</p>
                    <p class="value">{{ optional($documentDate)->format('d M Y') ?? '-' }}</p>
                </div>
                <div class="meta-cell">
                    <p class="label">Status Workflow</p>
                    <p class="value">{{ $workflow->status }}</p>
                </div>
                <div class="meta-cell">
                    <p class="label">Total Verifikator</p>
                    <p class="value">{{ $verifikators->count() }} orang</p>
                </div>
            </div>

            <div class="footer-note">
                Tautan ini menggunakan signed URL sistem. Perubahan status workflow setelah dokumen tidak final akan membuat halaman TTE tidak dapat ditampilkan.
            </div>
        </div>
    </main>
</body>
</html>
