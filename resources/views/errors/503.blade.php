<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>503 — Layanan Tidak Tersedia | SIKEREN BLU</title>
    <link rel="icon" href="{{ URL::asset('logo/minilogo-sikeren.png') }}">
    <style>
        :root {
            --sky-1: #0a1f4d;
            --sky-2: #103a86;
            --sky-3: #0e6fae;
            --accent: #38bdf8;
            --gold: #fcd34d;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        html, body { height: 100%; }

        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            color: #fff;
            overflow: hidden;
            position: relative;
            background: linear-gradient(160deg, var(--sky-1) 0%, var(--sky-2) 45%, var(--sky-3) 100%);
        }

        /* ===== STARS ===== */
        .e503-stars {
            position: fixed; inset: 0;
            background-image:
                radial-gradient(1.5px 1.5px at 20% 30%, #fff, transparent),
                radial-gradient(1.5px 1.5px at 70% 20%, #fff, transparent),
                radial-gradient(2px 2px at 40% 60%, rgba(255,255,255,.9), transparent),
                radial-gradient(1.5px 1.5px at 85% 70%, #fff, transparent),
                radial-gradient(1px 1px at 55% 85%, #fff, transparent),
                radial-gradient(1.5px 1.5px at 10% 80%, #fff, transparent),
                radial-gradient(2px 2px at 90% 40%, rgba(255,255,255,.85), transparent),
                radial-gradient(1px 1px at 33% 15%, #fff, transparent);
            opacity: .55;
            animation: e503-twinkle 4s ease-in-out infinite;
        }

        /* ===== CLOUDS ===== */
        .e503-cloud {
            position: fixed;
            background: rgba(255,255,255,.12);
            border-radius: 100px;
            filter: blur(2px);
            box-shadow:
                40px 10px 0 -6px rgba(255,255,255,.10),
                -40px 12px 0 -4px rgba(255,255,255,.08);
        }
        .e503-cloud::after {
            content: ""; position: absolute;
            top: -28px; left: 40px;
            width: 70px; height: 70px;
            background: rgba(255,255,255,.12);
            border-radius: 50%;
        }
        .e503-cloud.c1 { width: 160px; height: 46px; top: 18%; left: -200px; animation: e503-drift 26s linear infinite; }
        .e503-cloud.c2 { width: 110px; height: 34px; top: 62%; left: -200px; animation: e503-drift 34s linear infinite 6s; opacity: .8; }
        .e503-cloud.c3 { width: 200px; height: 56px; top: 78%; left: -260px; animation: e503-drift 44s linear infinite 12s; opacity: .65; }

        /* ===== LAYOUT ===== */
        .e503-wrap {
            position: relative; z-index: 5;
            min-height: 100vh;
            display: flex; flex-direction: column;
            align-items: center; justify-content: center;
            text-align: center;
            padding: 32px 20px;
        }

        /* ===== PLANE + BANNER ===== */
        .e503-scene {
            position: relative;
            width: min(560px, 92vw);
            height: 150px;
            margin-bottom: 8px;
        }
        .e503-flightpath {
            position: absolute; left: 0; right: 0; top: 64px;
            height: 2px;
            background: repeating-linear-gradient(90deg, rgba(255,255,255,.5) 0 12px, transparent 12px 24px);
            animation: e503-dash 1.4s linear infinite;
        }
        .e503-plane {
            position: absolute; top: 18px; left: 50%;
            transform: translateX(-50%);
            font-size: 64px; line-height: 1;
            filter: drop-shadow(0 8px 14px rgba(0,0,0,.4));
            animation: e503-bob 3.2s ease-in-out infinite;
        }
        .e503-plane svg { display: block; }
        .e503-banner {
            position: absolute; top: 30px; right: calc(50% + 58px);
            display: flex; align-items: center;
            transform-origin: right center;
            animation: e503-wave 2.4s ease-in-out infinite;
        }
        .e503-banner .rope {
            width: 26px; height: 2px;
            background: rgba(255,255,255,.6);
        }
        .e503-banner .flag {
            background: linear-gradient(135deg, var(--gold), #f59e0b);
            color: #0a1f4d;
            font-weight: 900;
            font-size: 26px;
            letter-spacing: 2px;
            padding: 8px 18px;
            border-radius: 8px;
            clip-path: polygon(0 0, 100% 0, 100% 100%, 0 100%, 8px 50%);
            box-shadow: 0 10px 22px -8px rgba(0,0,0,.5);
        }

        /* ===== TEXT ===== */
        .e503-title {
            font-size: clamp(26px, 5vw, 42px);
            font-weight: 800;
            letter-spacing: -.5px;
            margin-bottom: 12px;
            animation: e503-rise .8s ease both .1s;
        }
        .e503-sub {
            max-width: 520px;
            color: rgba(255,255,255,.82);
            font-size: clamp(14px, 2.2vw, 16px);
            line-height: 1.65;
            margin-bottom: 26px;
            animation: e503-rise .8s ease both .25s;
        }
        .e503-sub strong { color: var(--accent); }

        .e503-actions {
            display: flex; flex-wrap: wrap; gap: 12px; justify-content: center;
            animation: e503-rise .8s ease both .4s;
        }
        .e503-btn {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 12px 22px; border-radius: 12px;
            font-size: 14.5px; font-weight: 700; cursor: pointer;
            text-decoration: none; border: none;
            transition: transform .2s ease, box-shadow .2s ease, filter .2s ease;
        }
        .e503-btn--primary {
            color: #0a1f4d;
            background: linear-gradient(120deg, var(--accent), #0891b2);
            box-shadow: 0 16px 30px -12px rgba(56,189,248,.7);
        }
        .e503-btn--ghost {
            color: #fff;
            background: rgba(255,255,255,.10);
            border: 1px solid rgba(255,255,255,.22);
        }
        .e503-btn:hover { transform: translateY(-2px); filter: brightness(1.06); }
        .e503-btn:active { transform: translateY(0); }

        .e503-foot {
            margin-top: 36px;
            display: flex; align-items: center; gap: 10px;
            color: rgba(255,255,255,.7);
            font-size: 12.5px;
            animation: e503-rise .8s ease both .55s;
        }
        .e503-foot img { width: 26px; height: 26px; border-radius: 6px; background: #fff; padding: 3px; }

        /* ===== KEYFRAMES ===== */
        @keyframes e503-twinkle { 0%,100% { opacity: .35; } 50% { opacity: .7; } }
        @keyframes e503-drift { to { transform: translateX(calc(100vw + 320px)); } }
        @keyframes e503-dash { to { background-position: 24px 0; } }
        @keyframes e503-bob { 0%,100% { transform: translateX(-50%) translateY(0); } 50% { transform: translateX(-50%) translateY(-10px); } }
        @keyframes e503-wave { 0%,100% { transform: rotate(-2deg); } 50% { transform: rotate(3deg); } }
        @keyframes e503-rise { from { opacity: 0; transform: translateY(18px); } to { opacity: 1; transform: translateY(0); } }

        @media (prefers-reduced-motion: reduce) {
            .e503-stars, .e503-cloud, .e503-flightpath, .e503-plane,
            .e503-banner, .e503-title, .e503-sub, .e503-actions, .e503-foot { animation: none !important; }
        }
    </style>
</head>
<body>
    <div class="e503-stars"></div>
    <span class="e503-cloud c1"></span>
    <span class="e503-cloud c2"></span>
    <span class="e503-cloud c3"></span>

    <div class="e503-wrap">
        <div class="e503-scene">
            <div class="e503-flightpath"></div>

            {{-- Banner "503" yang ditarik pesawat --}}
            <div class="e503-banner">
                <span class="flag">503</span>
                <span class="rope"></span>
            </div>

            {{-- Pesawat --}}
            <div class="e503-plane" aria-hidden="true">
                <svg width="64" height="64" viewBox="0 0 24 24" fill="#e8f1ff" xmlns="http://www.w3.org/2000/svg">
                    <path d="M21 16.5v-2l-8-5V3.5a1.5 1.5 0 0 0-3 0V9.5l-8 5v2l8-2.5V18l-2 1.5V21l3.5-1 3.5 1v-1.5L11 18v-4l10 2.5z"/>
                </svg>
            </div>
        </div>

        <h1 class="e503-title">Sebentar, kami sedang terbang lebih tinggi ✈️</h1>
        <p class="e503-sub">
            <strong>503 — Layanan Sedang Tidak Tersedia.</strong><br>
            SIKEREN BLU sedang dalam pemeliharaan untuk memberikan pengalaman yang lebih baik.
            Mohon mendarat sejenak dan coba lagi beberapa saat lagi.
        </p>

        <div class="e503-actions">
            <a href="javascript:location.reload()" class="e503-btn e503-btn--primary">
                ↻ Coba Lagi
            </a>
            <a href="https://aptpairport.id" class="e503-btn e503-btn--ghost">
                ← Kembali ke aptpairport.id
            </a>
        </div>

        <div class="e503-foot">
            <img src="{{ URL::asset('logo/minilogo-sikeren.png') }}" alt="SIKEREN">
            <span>SIKEREN BLU &mdash; Kantor UPBU Kelas 1 APT Pranoto, Samarinda</span>
        </div>
    </div>
</body>
</html>
