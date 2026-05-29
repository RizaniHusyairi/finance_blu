@extends('layouts.guest', ['themeOverride' => 'light'])
@section('title')
    Login
@endsection

@section('content')
<style>
    :root {
        --lg-ink: #0b1b3b;
        --lg-muted: #64748b;
        --lg-line: #e6ecf6;
        --lg-primary: #2563eb;
        --lg-primary-2: #1d4ed8;
        --lg-cyan: #0891b2;
        --lg-accent: #38bdf8;
    }

    .lg-wrap {
        min-height: 100vh;
        display: grid;
        grid-template-columns: 1.15fr 1fr;
        background:
            radial-gradient(1200px 600px at 110% -10%, rgba(56,189,248,.18), transparent 60%),
            linear-gradient(135deg, #f6f9ff 0%, #eef3fb 100%);
        overflow: hidden;
    }

    /* ===== LEFT — VIDEO STAGE ===== */
    .lg-stage {
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 40px;
        overflow: hidden;
        color: #fff;
        background: linear-gradient(150deg, #0a1f4d 0%, #103a86 45%, #0e6fae 100%);
    }
    .lg-stage::before,
    .lg-stage::after {
        content: "";
        position: absolute;
        border-radius: 50%;
        background: radial-gradient(circle at center, rgba(56,189,248,.30), transparent 70%);
        pointer-events: none;
    }
    .lg-stage::before { width: 460px; height: 460px; top: -160px; left: -120px; animation: lg-float 9s ease-in-out infinite; }
    .lg-stage::after  { width: 360px; height: 360px; bottom: -150px; right: -110px; animation: lg-float 11s ease-in-out infinite reverse; }

    /* animated aurora grid lines */
    .lg-grid {
        position: absolute; inset: 0;
        background-image:
            linear-gradient(rgba(255,255,255,.05) 1px, transparent 1px),
            linear-gradient(90deg, rgba(255,255,255,.05) 1px, transparent 1px);
        background-size: 44px 44px;
        mask-image: radial-gradient(circle at 50% 45%, #000 30%, transparent 78%);
        animation: lg-grid-pan 18s linear infinite;
    }

    .lg-stage__inner {
        position: relative;
        z-index: 2;
        width: min(560px, 100%);
        text-align: center;
        animation: lg-rise .8s ease both;
    }
    .lg-videoframe {
        position: relative;
        border-radius: 26px;
        padding: 10px;
        background: linear-gradient(135deg, rgba(255,255,255,.18), rgba(255,255,255,.04));
        border: 1px solid rgba(255,255,255,.22);
        box-shadow: 0 30px 70px -30px rgba(2, 12, 35, .8);
        backdrop-filter: blur(4px);
    }
    .lg-videoframe::after {
        content: "";
        position: absolute; inset: 0;
        border-radius: 26px;
        padding: 1px;
        background: linear-gradient(120deg, var(--lg-accent), transparent 40%, transparent 60%, #22c55e);
        -webkit-mask: linear-gradient(#000 0 0) content-box, linear-gradient(#000 0 0);
        -webkit-mask-composite: xor; mask-composite: exclude;
        opacity: .7;
        animation: lg-border-rot 6s linear infinite;
    }
    .lg-videoframe video {
        width: 100%;
        display: block;
        border-radius: 18px;
        object-fit: cover;
    }

    .lg-stage__title {
        margin: 26px 0 6px;
        font-size: clamp(20px, 2.4vw, 28px);
        font-weight: 800;
        letter-spacing: -.01em;
        line-height: 1.18;
        color:palegoldenrod;
    }
    .lg-stage__sub {
        margin: 0 auto;
        max-width: 440px;
        color: rgba(255,255,255,.78);
        font-size: 14px;
        line-height: 1.6;
    }
    .lg-badges { display: flex; flex-wrap: wrap; gap: 10px; justify-content: center; margin-top: 22px; }
    .lg-badge {
        display: inline-flex; align-items: center; gap: 7px;
        padding: 7px 14px; border-radius: 999px;
        background: rgba(255,255,255,.10); border: 1px solid rgba(255,255,255,.20);
        font-size: 12.5px; font-weight: 600;
        animation: lg-rise .8s ease both;
    }
    .lg-badge:nth-child(2) { animation-delay: .1s; }
    .lg-badge:nth-child(3) { animation-delay: .2s; }
    .lg-badge i { color: var(--lg-accent); }

    /* ===== RIGHT — FORM ===== */
    .lg-panel {
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 40px 24px;
    }
    .lg-card {
        width: min(440px, 100%);
        background: #fff;
        border: 1px solid var(--lg-line);
        border-radius: 22px;
        padding: 38px 34px;
        box-shadow: 0 30px 70px -34px rgba(15, 27, 51, .35);
        animation: lg-rise .7s ease both .1s;
    }
    .lg-logo {
            width: 105px;
            height: 105px;
            border-radius: 50px;
            display: grid;
            place-items: center;
            margin: 0 auto 16px;
            background: linear-gradient(135deg, #eff6ff, #e0f2fe);
            border: 1px solid #f1f1f1;
            box-shadow: 0 14px 23px -18px rgb(145 106 46);
            animation: lg-pop .7s cubic-bezier(.2, .9, .3, 1.2) both .15s;
    }
    .lg-logo img { width: 100px; height: auto; }
    .lg-card h4 { text-align: center; font-weight: 800; color: var(--lg-ink); font-size: 19px; line-height: 1.3; margin-bottom: 4px; }
    .lg-card .lg-tagline { text-align: center; color: var(--lg-muted); font-size: 12.5px; line-height: 1.5; margin-bottom: 24px; }

    .lg-field { margin-bottom: 16px; animation: lg-rise .6s ease both; }
    .lg-field:nth-child(1) { animation-delay: .18s; }
    .lg-field:nth-child(2) { animation-delay: .26s; }
    .lg-label { display: block; font-size: 12.5px; font-weight: 700; color: var(--lg-ink); margin-bottom: 6px; }
    .lg-label .req { color: #ef4444; }
    .lg-input-wrap { position: relative; }
    .lg-input-wrap > .lg-ic {
        position: absolute; left: 14px; top: 50%; transform: translateY(-50%);
        color: var(--lg-muted); font-size: 17px; transition: color .2s ease;
    }
    .lg-input {
        width: 100%; height: 50px;
        padding: 0 46px 0 42px;
        border: 1.5px solid var(--lg-line);
        border-radius: 13px;
        background: #f8fafc;
        font-size: 14.5px; color: var(--lg-ink);
        transition: border-color .2s ease, box-shadow .2s ease, background .2s ease;
    }
    .lg-input::placeholder { color: #aeb8c9; }
    .lg-input:focus {
        outline: none; background: #fff;
        border-color: var(--lg-primary);
        box-shadow: 0 0 0 4px rgba(37,99,235,.14);
    }
    .lg-input:focus ~ .lg-ic { color: var(--lg-primary); }
    .lg-input.is-invalid { border-color: #ef4444; box-shadow: 0 0 0 4px rgba(239,68,68,.12); }
    .lg-eye {
        position: absolute; right: 8px; top: 50%; transform: translateY(-50%);
        width: 34px; height: 34px; border: none; background: transparent;
        color: var(--lg-muted); cursor: pointer; border-radius: 9px; transition: color .2s ease, background .2s ease;
    }
    .lg-eye:hover { color: var(--lg-primary); background: #eff4ff; }

    .lg-row { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; font-size: 13px; }
    .lg-row a { color: var(--lg-primary); text-decoration: none; font-weight: 600; }
    .lg-row a:hover { text-decoration: underline; }

    .lg-btn {
        position: relative; overflow: hidden;
        width: 100%; height: 50px; border: none; border-radius: 13px;
        font-size: 15px; font-weight: 800; color: #fff; cursor: pointer;
        background: linear-gradient(120deg, var(--lg-primary), var(--lg-cyan));
        box-shadow: 0 16px 30px -12px rgba(37,99,235,.65);
        transition: transform .2s ease, box-shadow .2s ease, filter .2s ease;
        display: inline-flex; align-items: center; justify-content: center; gap: 8px;
    }
    .lg-btn:hover { transform: translateY(-2px); filter: brightness(1.05); box-shadow: 0 22px 38px -14px rgba(37,99,235,.75); }
    .lg-btn:active { transform: translateY(0); }
    .lg-btn::after {
        content: ""; position: absolute; top: 0; bottom: 0; width: 60px; left: -90px;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,.4), transparent);
        animation: lg-sheen 3.4s ease-in-out infinite;
    }

    .lg-back { text-align: center; margin-top: 18px; }
    .lg-back a { color: var(--lg-muted); text-decoration: none; font-size: 13px; display: inline-flex; align-items: center; gap: 6px; transition: color .2s ease; }
    .lg-back a:hover { color: var(--lg-primary); }

    .lg-alert {
        display: flex; gap: 10px; align-items: flex-start;
        padding: 12px 14px; border-radius: 13px; margin-bottom: 18px;
        background: #fef2f2; border: 1px solid #fecaca; color: #b91c1c;
        animation: lg-shake .5s ease;
    }
    .lg-alert i { font-size: 18px; margin-top: 1px; }
    .lg-alert ul { margin: 2px 0 0; padding-left: 18px; font-size: 13px; }
    .lg-alert strong { font-size: 13.5px; }

    /* ===== keyframes ===== */
    @keyframes lg-float { 0%,100% { transform: translateY(0); } 50% { transform: translateY(-22px); } }
    @keyframes lg-grid-pan { to { background-position: 44px 44px; } }
    @keyframes lg-border-rot { to { transform: rotate(360deg); } }
    @keyframes lg-rise { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
    @keyframes lg-pop { from { opacity: 0; transform: scale(.7); } to { opacity: 1; transform: scale(1); } }
    @keyframes lg-sheen { 0% { left: -90px; } 55%, 100% { left: 120%; } }
    @keyframes lg-shake { 10%,90% { transform: translateX(-1px); } 20%,80% { transform: translateX(2px); } 30%,50%,70% { transform: translateX(-4px); } 40%,60% { transform: translateX(4px); } }

    @media (max-width: 991px) {
        .lg-wrap { grid-template-columns: 1fr; }
        .lg-stage { display: none; }
        .lg-panel { min-height: 100vh; }
    }
    @media (prefers-reduced-motion: reduce) {
        .lg-stage::before, .lg-stage::after, .lg-grid, .lg-videoframe::after,
        .lg-stage__inner, .lg-card, .lg-logo, .lg-field, .lg-badge, .lg-btn::after, .lg-alert { animation: none !important; }
    }
</style>

<div class="lg-wrap">
    {{-- ===== LEFT: VIDEO STAGE ===== --}}
    <div class="lg-stage">
        <span class="lg-grid"></span>
        <div class="lg-stage__inner">
            <div class="lg-videoframe">
                <video autoplay muted loop playsinline>
                    <source src="{{ URL::asset('logo/animasi-logo.mp4') }}" type="video/mp4">
                </video>
            </div>
            <h2 class="lg-stage__title">Sistem Informasi Keuangan &amp; Penagihan Terpadu</h2>
            <p class="lg-stage__sub">BLU Kantor UPBU Kelas 1 Aji Pangeran Tumenggung Pranoto &mdash; Samarinda</p>
            <div class="lg-badges">
                <span class="lg-badge"><i class="bi bi-clipboard-check-fill"></i> Akuntabel</span>
                <span class="lg-badge"><i class="bi bi-eye-fill"></i> Transparan</span>
                <span class="lg-badge"><i class="bi bi-diagram-3-fill"></i> Terintegrasi</span>
            </div>
        </div>
    </div>

    {{-- ===== RIGHT: FORM ===== --}}
    <div class="lg-panel">
        <div class="lg-card">
            <div class="lg-logo">
                <img src="{{ URL::asset('logo/minilogo-sikeren.png') }}" alt="SIKEREN">
            </div>
            <h4>Selamat Datang</h4>
            <p class="lg-tagline">Masuk untuk melanjutkan ke dasbor SIKEREN BLU APT Pranoto.</p>

            @if ($errors->any())
                <div class="lg-alert" role="alert">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <div>
                        <strong>Login gagal!</strong>
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf

                <div class="lg-field">
                    <label for="email" class="lg-label">Email <span class="req">*</span></label>
                    <div class="lg-input-wrap">
                        <input type="email" id="email" name="email"
                               class="lg-input @error('email') is-invalid @enderror"
                               placeholder="nama@email.go.id" value="{{ old('email') }}"
                               required autocomplete="email" autofocus>
                        <i class="bi bi-envelope-fill lg-ic"></i>
                    </div>
                </div>

                <div class="lg-field">
                    <label for="password" class="lg-label">Password <span class="req">*</span></label>
                    <div class="lg-input-wrap" id="show_hide_password">
                        <input id="password" type="password" name="password"
                               class="lg-input @error('password') is-invalid @enderror"
                               placeholder="Masukkan password" required autocomplete="current-password">
                        <i class="bi bi-lock-fill lg-ic"></i>
                        <button type="button" class="lg-eye" aria-label="Tampilkan password">
                            <i class="bi bi-eye-slash-fill"></i>
                        </button>
                    </div>
                </div>

                <div class="lg-row">
                    <span></span>
                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}">Lupa Password?</a>
                    @endif
                </div>

                <button type="submit" class="lg-btn">
                    <i class="bi bi-box-arrow-in-right"></i> Masuk
                </button>

                <div class="lg-back">
                    <a href="https://aptpairport.id"><i class="bi bi-arrow-left"></i> Kembali ke aptpairport.id</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('script')
<script>
    $(document).ready(function () {
        $("#show_hide_password .lg-eye").on('click', function (event) {
            event.preventDefault();
            var $input = $('#show_hide_password input');
            var $icon = $('#show_hide_password .lg-eye i');
            if ($input.attr("type") === "password") {
                $input.attr('type', 'text');
                $icon.removeClass("bi-eye-slash-fill").addClass("bi-eye-fill");
            } else {
                $input.attr('type', 'password');
                $icon.removeClass("bi-eye-fill").addClass("bi-eye-slash-fill");
            }
        });
    });
</script>
@endpush
