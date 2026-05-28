<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TTE {{ $documentLabel }} {{ $documentNumber }}</title>
    <link rel="icon" href="{{ asset('logo/minilogo-sikeren.png') }}" type="image/png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-0: #070b18;
            --ink: #eaf0fb;
            --muted: #94a3c4;
            --faint: #5f6b8a;
            --line: rgba(148, 163, 196, .14);
            --blue: #38bdf8;
            --indigo: #818cf8;
            --green: #34d399;
            --gold: #fbbf24;
            --card: rgba(255, 255, 255, .045);
        }
        * { box-sizing: border-box; }
        body {
            margin: 0; min-height: 100vh; color: var(--ink); background: var(--bg-0);
            font-family: 'Inter', ui-sans-serif, system-ui, -apple-system, "Segoe UI", sans-serif;
            -webkit-font-smoothing: antialiased; position: relative; overflow-x: hidden;
        }
        .aurora { position: fixed; inset: 0; z-index: 0; overflow: hidden; pointer-events: none; }
        .aurora span { position: absolute; border-radius: 50%; filter: blur(90px); opacity: .55; mix-blend-mode: screen; animation: drift 22s ease-in-out infinite; }
        .aurora .a1 { width: 540px; height: 540px; top: -160px; left: -120px; background: radial-gradient(circle, #2563eb, transparent 70%); }
        .aurora .a2 { width: 480px; height: 480px; top: -120px; right: -120px; background: radial-gradient(circle, #0891b2, transparent 70%); animation-delay: -6s; }
        .aurora .a3 { width: 520px; height: 520px; bottom: -200px; left: 30%; background: radial-gradient(circle, #059669, transparent 70%); animation-delay: -12s; }
        .grid-overlay { position: fixed; inset: 0; z-index: 0; pointer-events: none; opacity: .35;
            background-image: linear-gradient(rgba(148,163,196,.06) 1px, transparent 1px), linear-gradient(90deg, rgba(148,163,196,.06) 1px, transparent 1px);
            background-size: 46px 46px;
            mask-image: radial-gradient(ellipse 80% 60% at 50% 0%, #000 30%, transparent 75%);
            -webkit-mask-image: radial-gradient(ellipse 80% 60% at 50% 0%, #000 30%, transparent 75%); }
        .wrap { width: min(1120px, calc(100% - 36px)); margin: 0 auto; position: relative; z-index: 1; }

        .hero { padding: 46px 0 0; }
        .hero-card { position: relative; overflow: hidden; border: 1px solid var(--line); border-radius: 26px;
            background: linear-gradient(160deg, rgba(56,189,248,.10), rgba(129,140,248,.06) 45%, rgba(52,211,153,.08));
            backdrop-filter: blur(14px); box-shadow: 0 30px 80px rgba(2, 6, 23, .55), inset 0 1px 0 rgba(255,255,255,.06);
            padding: 36px; display: grid; grid-template-columns: 1fr auto; gap: 28px; align-items: center;
            animation: rise .7s cubic-bezier(.22,1,.36,1) both; }
        .hero-card::before { content: ""; position: absolute; inset: 0 0 auto; height: 2px;
            background: linear-gradient(90deg, transparent, var(--blue), var(--indigo), var(--green), transparent);
            background-size: 220% 100%; animation: ribbon 5s linear infinite; }
        .chip { display: inline-flex; align-items: center; gap: 8px; padding: 7px 13px; border-radius: 999px;
            font-size: 12px; font-weight: 800; letter-spacing: .12em; text-transform: uppercase;
            color: #bfe3ff; background: rgba(56,189,248,.12); border: 1px solid rgba(56,189,248,.28); }
        .chip .dot { width: 7px; height: 7px; border-radius: 50%; background: var(--blue); animation: pulse 1.9s ease-out infinite; }
        h1 { margin: 16px 0 0; font-size: clamp(26px, 4vw, 42px); line-height: 1.05; font-weight: 900; letter-spacing: -.02em;
            background: linear-gradient(100deg, #fff 10%, #bfe3ff 50%, #c7f9e9 90%);
            -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent; }
        .subtitle { margin: 14px 0 0; max-width: 560px; color: var(--muted); line-height: 1.65; font-size: 15px; }
        .actionbar { margin-top: 24px; display: flex; flex-wrap: wrap; gap: 12px; }
        .btn { display: inline-flex; align-items: center; gap: 9px; min-height: 46px; padding: 0 20px;
            border-radius: 12px; text-decoration: none; font-size: 14px; font-weight: 800; cursor: pointer;
            border: 1px solid transparent; transition: transform .2s ease, box-shadow .2s ease, background .2s ease; }
        .btn-primary { color: #04122b; background: linear-gradient(135deg, #7dd3fc, #38bdf8 55%, #34d399); box-shadow: 0 14px 34px rgba(56,189,248,.32); }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 20px 44px rgba(56,189,248,.45); }
        .btn-ghost { color: var(--ink); background: rgba(255,255,255,.05); border-color: var(--line); }
        .btn-ghost:hover { background: rgba(255,255,255,.1); transform: translateY(-2px); }

        .seal-wrap { position: relative; width: 170px; height: 170px; display: grid; place-items: center; }
        .seal-ring { position: absolute; inset: 0; border-radius: 50%; border: 1px dashed rgba(56,189,248,.4); animation: spin 16s linear infinite; }
        .seal-ring.r2 { inset: 16px; border-color: rgba(52,211,153,.35); animation-duration: 11s; animation-direction: reverse; }
        .seal-core { width: 116px; height: 116px; border-radius: 50%; display: grid; place-items: center;
            background: radial-gradient(circle at 30% 25%, rgba(56,189,248,.35), rgba(13,20,38,.9) 70%);
            border: 1px solid rgba(56,189,248,.4);
            box-shadow: 0 0 0 6px rgba(56,189,248,.06), 0 18px 50px rgba(56,189,248,.30), inset 0 0 22px rgba(56,189,248,.25);
            animation: floaty 4.5s ease-in-out infinite; }
        .seal-core svg { width: 58px; height: 58px; }
        .seal-core .check { stroke: var(--green); stroke-width: 7; fill: none; stroke-linecap: round; stroke-linejoin: round;
            stroke-dasharray: 80; stroke-dashoffset: 80; animation: draw 1s ease .35s forwards; filter: drop-shadow(0 0 6px rgba(52,211,153,.7)); }
        .seal-badge { position: absolute; bottom: 4px; left: 50%; transform: translateX(-50%);
            font-size: 11px; font-weight: 900; letter-spacing: .18em; color: #d7f6ff;
            background: rgba(7,11,24,.7); padding: 4px 11px; border-radius: 999px; border: 1px solid rgba(56,189,248,.3); }

        .trust { margin-top: 18px; display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; animation: rise .7s cubic-bezier(.22,1,.36,1) .1s both; }
        .trust-card { border: 1px solid var(--line); border-radius: 16px; padding: 16px 18px; background: var(--card); backdrop-filter: blur(8px); display: flex; align-items: center; gap: 13px; }
        .trust-ico { width: 42px; height: 42px; border-radius: 12px; display: grid; place-items: center; flex-shrink: 0; }
        .ico-green { background: rgba(52,211,153,.14); color: var(--green); border: 1px solid rgba(52,211,153,.28); }
        .ico-blue { background: rgba(56,189,248,.14); color: var(--blue); border: 1px solid rgba(56,189,248,.28); }
        .ico-gold { background: rgba(251,191,36,.14); color: var(--gold); border: 1px solid rgba(251,191,36,.28); }
        .t-lbl { font-size: 11px; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; color: var(--faint); }
        .t-val { font-size: 15px; font-weight: 800; margin-top: 2px; }

        main { padding: 22px 0 56px; }
        .layout { display: grid; grid-template-columns: 1fr 350px; gap: 18px; }
        .card { border: 1px solid var(--line); border-radius: 20px; background: var(--card); backdrop-filter: blur(10px);
            box-shadow: 0 18px 50px rgba(2,6,23,.4); overflow: hidden; animation: rise .7s cubic-bezier(.22,1,.36,1) .15s both; }
        .card + .card { margin-top: 18px; }
        .card-head { display: flex; align-items: center; gap: 11px; padding: 18px 22px; border-bottom: 1px solid var(--line); }
        .ch-ico { width: 34px; height: 34px; border-radius: 10px; display: grid; place-items: center; background: rgba(129,140,248,.14); color: var(--indigo); border: 1px solid rgba(129,140,248,.28); }
        .card-head h2 { margin: 0; font-size: 15px; font-weight: 800; letter-spacing: -.01em; }
        .card-body { padding: 22px; }

        .grid2 { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; }
        .field { padding: 14px 16px; border: 1px solid var(--line); border-radius: 14px; background: rgba(255,255,255,.025); transition: border-color .2s ease, transform .2s ease; }
        .field:hover { border-color: rgba(56,189,248,.3); transform: translateY(-2px); }
        .label { margin: 0 0 6px; color: var(--faint); font-size: 11px; font-weight: 800; letter-spacing: .09em; text-transform: uppercase; }
        .value { margin: 0; font-weight: 700; line-height: 1.4; word-break: break-word; color: var(--ink); }
        .value.big { font-size: 18px; }
        .value.money { font-size: 20px; font-weight: 900; background: linear-gradient(100deg, #7dd3fc, #34d399); -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent; }
        .status { display: inline-flex; align-items: center; gap: 8px; padding: 8px 13px; border-radius: 999px;
            color: #062b1e; background: linear-gradient(135deg, #6ee7b7, #34d399); font-size: 12.5px; font-weight: 900; }
        .status .dot { width: 7px; height: 7px; border-radius: 50%; background: #04130c; animation: pulse 1.8s ease-out infinite; }

        .signer-table { width: 100%; border-collapse: collapse; }
        .signer-table th { text-align: left; font-size: 11px; font-weight: 800; letter-spacing: .08em;
            text-transform: uppercase; color: var(--faint); padding: 10px 12px; border-bottom: 1px solid var(--line); }
        .signer-table td { padding: 12px; border-bottom: 1px dashed var(--line); font-size: 13.5px; vertical-align: top; }
        .signer-table tr:last-child td { border-bottom: 0; }
        .signer-table .name { font-weight: 750; }
        .signer-table .meta { color: var(--muted); font-size: 12px; margin-top: 3px; }
        .signer-table .role-pill { display: inline-flex; padding: 4px 10px; border-radius: 999px; font-size: 11px; font-weight: 800;
            color: #04130c; background: linear-gradient(135deg, #6ee7b7, #34d399); }

        .hash-box { padding: 16px; border: 1px solid rgba(56,189,248,.22); border-radius: 16px;
            background: linear-gradient(160deg, rgba(56,189,248,.07), rgba(52,211,153,.04)); margin-top: 16px; }
        .hash-state { display: inline-flex; align-items: center; gap: 8px; padding: 7px 12px; border-radius: 999px; font-size: 12px; font-weight: 900; }
        .hash-ok { color: #04130c; background: linear-gradient(135deg, #6ee7b7, #34d399); }
        .hash-bad { color: #fff; background: linear-gradient(135deg, #fb7185, #e11d48); }
        .hash-value { margin-top: 12px; padding: 12px; border-radius: 12px; color: #cfe6ff; background: rgba(2,6,23,.55);
            border: 1px solid var(--line); font-family: 'JetBrains Mono', monospace; font-size: 11.5px; line-height: 1.6; word-break: break-all; }
        .small { color: var(--muted); font-size: 13px; line-height: 1.6; }

        .id-row { padding: 13px 15px; border: 1px solid var(--line); border-radius: 13px; background: rgba(255,255,255,.025); }
        .id-row + .id-row { margin-top: 10px; }

        .footer-note { padding: 16px 22px; color: var(--faint); background: rgba(2,6,23,.4); border-top: 1px solid var(--line); font-size: 12.5px; line-height: 1.7; }
        .page-foot { text-align: center; color: var(--faint); font-size: 12px; padding: 8px 0 32px; }

        @keyframes ribbon { to { background-position: 220% 0; } }
        @keyframes spin { to { transform: rotate(360deg); } }
        @keyframes floaty { 0%,100% { transform: translateY(0); } 50% { transform: translateY(-8px); } }
        @keyframes pulse { 0% { box-shadow: 0 0 0 0 currentColor; opacity: 1; } 70% { box-shadow: 0 0 0 9px transparent; } 100% { box-shadow: 0 0 0 0 transparent; } }
        @keyframes draw { to { stroke-dashoffset: 0; } }
        @keyframes drift { 0%,100% { transform: translate(0,0) scale(1); } 33% { transform: translate(40px,30px) scale(1.08); } 66% { transform: translate(-30px,20px) scale(.95); } }
        @keyframes rise { from { opacity: 0; transform: translateY(18px); } to { opacity: 1; transform: translateY(0); } }

        @media (max-width: 900px) {
            .hero-card { grid-template-columns: 1fr; gap: 22px; padding: 26px; }
            .seal-wrap { justify-self: center; }
            .trust, .layout, .grid2 { grid-template-columns: 1fr; }
        }
        @media (prefers-reduced-motion: reduce) {
            .aurora span, .hero-card::before, .seal-ring, .seal-core, .chip .dot, .status .dot, .seal-core .check { animation: none !important; }
            .seal-core .check { stroke-dashoffset: 0; }
        }
    </style>
</head>
<body>
    <div class="aurora"><span class="a1"></span><span class="a2"></span><span class="a3"></span></div>
    <div class="grid-overlay"></div>

    @php
        $tipeLabel = $tagihan->tipe_tagihan === 'PERJALDIN' ? 'Perjalanan Dinas' : 'Honorarium';
        $bulanMap = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];
        $periode = ($tagihan->periode_bulan && $tagihan->periode_tahun)
            ? ($bulanMap[(int) $tagihan->periode_bulan] ?? '-') . ' ' . $tagihan->periode_tahun
            : '-';
    @endphp

    <header class="hero">
        <div class="wrap">
            <div class="hero-card">
                <div>
                    <span class="chip"><span class="dot"></span> Tanda Tangan Elektronik · {{ $documentLabel }}</span>
                    <h1>Dokumen {{ $documentLabel }} Terverifikasi</h1>
                    <p class="subtitle">Halaman ini terbit dari QR TTE resmi sistem setelah seluruh pejabat berwenang menyetujui tagihan {{ strtolower($tipeLabel) }} pada alur workflow. Keaslian dijamin lewat signed URL &amp; hash dokumen.</p>
                    <div class="actionbar">
                        <a class="btn btn-primary" href="{{ $documentUrl }}" target="_blank" rel="noopener">
                            <svg width="17" height="17" viewBox="0 0 24 24" fill="none"><path d="M14 3h7v7M21 3l-9 9M21 14v5a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            Lihat Dokumen {{ $documentLabel }}
                        </a>
                        <a class="btn btn-ghost" href="#detail">
                            <svg width="17" height="17" viewBox="0 0 24 24" fill="none"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                            Lihat Detail Verifikasi
                        </a>
                    </div>
                </div>
                <div class="seal-wrap" aria-label="Tanda tangan elektronik sah">
                    <div class="seal-ring"></div>
                    <div class="seal-ring r2"></div>
                    <div class="seal-core">
                        <svg viewBox="0 0 64 64" aria-hidden="true"><path class="check" d="M16 33 L28 45 L49 20"/></svg>
                    </div>
                    <span class="seal-badge">TTE SAH</span>
                </div>
            </div>

            <div class="trust">
                <div class="trust-card">
                    <div class="trust-ico ico-green">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M12 2 4 5v6c0 5 3.4 8.5 8 11 4.6-2.5 8-6 8-11V5l-8-3Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><path d="m9 12 2 2 4-4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </div>
                    <div>
                        <div class="t-lbl">Status TTE</div>
                        <div class="t-val">Disetujui Workflow</div>
                    </div>
                </div>
                <div class="trust-card">
                    <div class="trust-ico {{ $hashStatus === 'cocok' ? 'ico-green' : 'ico-gold' }}">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M9 12h6M9 16h6M9 8h2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M7 3h7l5 5v11a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg>
                    </div>
                    <div>
                        <div class="t-lbl">Integritas Hash</div>
                        <div class="t-val">{{ $hashStatus === 'cocok' ? 'Cocok & Valid' : 'Tidak Cocok' }}</div>
                    </div>
                </div>
                <div class="trust-card">
                    <div class="trust-ico ico-blue">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="8" r="4" stroke="currentColor" stroke-width="2"/><path d="M4 21c0-4 3.6-7 8-7s8 3 8 7" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                    </div>
                    <div>
                        <div class="t-lbl">Penandatangan Akhir</div>
                        <div class="t-val">{{ \Illuminate\Support\Str::limit($primarySigner['nama'], 22) }}</div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main id="detail">
        <div class="wrap layout">
            <div>
                <div class="card">
                    <div class="card-head">
                        <span class="ch-ico">
                            <svg width="17" height="17" viewBox="0 0 24 24" fill="none"><path d="M7 3h7l5 5v11a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><path d="M14 3v5h5" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg>
                        </span>
                        <h2>Ringkasan Tagihan {{ $tipeLabel }}</h2>
                    </div>
                    <div class="card-body">
                        <div class="grid2">
                            <div class="field">
                                <p class="label">Nomor Tagihan</p>
                                <p class="value big">{{ $documentNumber ?? '-' }}</p>
                            </div>
                            <div class="field">
                                <p class="label">Status TTE</p>
                                <span class="status"><span class="dot"></span> Disetujui Workflow</span>
                            </div>
                            <div class="field">
                                <p class="label">Periode</p>
                                <p class="value">{{ $periode }}</p>
                            </div>
                            <div class="field">
                                <p class="label">Total Bruto</p>
                                <p class="value money">Rp {{ number_format((float) $tagihan->total_bruto, 0, ',', '.') }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-head">
                        <span class="ch-ico">
                            <svg width="17" height="17" viewBox="0 0 24 24" fill="none"><path d="M3 17c3-1 4-4 7-4s4 3 7 1M14 5l5 5M4 20l4-1 9.5-9.5a2.1 2.1 0 0 0-3-3L5 16l-1 4Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </span>
                        <h2>Riwayat Persetujuan ({{ count($signers) }} Pejabat)</h2>
                    </div>
                    <div class="card-body">
                        <table class="signer-table">
                            <thead>
                                <tr>
                                    <th style="width:42px;">#</th>
                                    <th>Penandatangan</th>
                                    <th style="width:180px;">Peran</th>
                                    <th style="width:170px;">Disetujui Pada</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($signers as $idx => $s)
                                    <tr>
                                        <td>{{ $idx + 1 }}</td>
                                        <td>
                                            <div class="name">{{ $s['nama'] }}</div>
                                            <div class="meta">NIP {{ $s['nip'] }}</div>
                                        </td>
                                        <td><span class="role-pill">{{ $s['jabatan'] }}</span></td>
                                        <td>{{ optional($s['acted_at'])->format('d M Y H:i') ?? '-' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="small">Belum ada riwayat persetujuan.</td></tr>
                                @endforelse
                            </tbody>
                        </table>

                        <div class="hash-box">
                            <p class="label">Hash Dokumen (SHA-256)</p>
                            <span class="hash-state {{ $hashStatus === 'cocok' ? 'hash-ok' : 'hash-bad' }}">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none">
                                    @if($hashStatus === 'cocok')
                                        <path d="m5 13 4 4L19 7" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                                    @else
                                        <path d="M6 6l12 12M18 6 6 18" stroke="currentColor" stroke-width="3" stroke-linecap="round"/>
                                    @endif
                                </svg>
                                {{ $hashStatus === 'cocok' ? 'Cocok' : 'Tidak Cocok' }}
                            </span>
                            <div class="hash-value">{{ $documentHash }}</div>
                            <p class="small" style="margin: 11px 0 0;">Hash dihitung dari identitas tagihan dan seluruh data persetujuan workflow saat halaman dibuka. Perubahan substansi tagihan pasca-approval otomatis membuat hash tidak cocok.</p>
                        </div>
                    </div>
                </div>
            </div>

            <aside>
                <div class="card">
                    <div class="card-head">
                        <span class="ch-ico">
                            <svg width="17" height="17" viewBox="0 0 24 24" fill="none"><path d="M3 7V5a2 2 0 0 1 2-2h2M17 3h2a2 2 0 0 1 2 2v2M21 17v2a2 2 0 0 1-2 2h-2M7 21H5a2 2 0 0 1-2-2v-2M7 8h10v8H7z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </span>
                        <h2>Identitas Scan</h2>
                    </div>
                    <div class="card-body">
                        <div class="id-row"><p class="label">ID User</p><p class="value">{{ $scanInfo['user_id'] }}</p></div>
                        <div class="id-row"><p class="label">Timestamp</p><p class="value">{{ $scanInfo['timestamp']->format('d M Y H:i:s') }}</p></div>
                        <div class="id-row"><p class="label">IP Address</p><p class="value">{{ $scanInfo['ip_address'] }}</p></div>
                        <div class="id-row"><p class="label">ID Tagihan</p><p class="value">{{ $scanInfo['dokumen_id'] }}</p></div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-head">
                        <span class="ch-ico">
                            <svg width="17" height="17" viewBox="0 0 24 24" fill="none"><path d="M3 21h18M5 21V7l8-4 8 4v14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                        </span>
                        <h2>Instansi</h2>
                    </div>
                    <div class="card-body">
                        <p class="value" style="font-size: 15px;">Kantor UPBU Aji Pangeran Tumenggung Pranoto</p>
                        <p class="small" style="margin-top: 8px;">Kementerian Perhubungan · Direktorat Jenderal Perhubungan Udara</p>
                        <div class="id-row" style="margin-top: 14px;">
                            <p class="label">Tagihan dibuat</p>
                            <p class="value">{{ optional($tagihan->created_at)->format('d M Y H:i') ?? '-' }}</p>
                        </div>
                    </div>
                </div>
            </aside>
        </div>

        <div class="wrap" style="margin-top: 18px;">
            <div class="card" style="animation-delay:.2s;">
                <div class="footer-note">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" style="vertical-align:-2px; margin-right:6px;"><path d="M12 2 4 5v6c0 5 3.4 8.5 8 11 4.6-2.5 8-6 8-11V5l-8-3Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg>
                    Tautan ini menggunakan <strong>signed URL</strong> sistem. Perubahan data tagihan atau status persetujuan akan membuat hash tidak cocok sehingga halaman TTE tidak dapat ditampilkan.
                </div>
            </div>
            <p class="page-foot">SIKEREN-BLU · Kantor UPBU Aji Pangeran Tumenggung Pranoto — Sistem Verifikasi Tanda Tangan Elektronik</p>
        </div>
    </main>
</body>
</html>
