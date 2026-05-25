{{--
    Sky Alerts — global notification system bertema penerbangan.

    Menyediakan:
      - window.SkyAlert.show({ type, title, message, duration })
      - Override window.alert dan window.confirm
      - Auto-render Bootstrap .alert (.alert-success/danger/warning/info)
        menjadi toast keren saat halaman ter-load
      - Render pesan dari session('success'|'error'|'warning'|'info'|'status')

    Tema visual:
      - Gradient langit (sky/indigo) dengan sapuan awan animatif
      - Ikon pesawat yang terbang & meninggalkan vapor trail
      - Auto-dismiss 6 detik (8 detik untuk error)
      - Stack di pojok kanan-atas, slide-in dari kanan
--}}

<style>
    .sky-alert-stack {
        position: fixed;
        top: 1.15rem;
        right: 1.15rem;
        z-index: 9999;
        display: flex;
        flex-direction: column;
        gap: .75rem;
        max-width: min(420px, calc(100vw - 2rem));
        pointer-events: none;
    }

    .sky-alert {
        --sa-grad: linear-gradient(135deg, #38bdf8 0%, #6366f1 50%, #8b5cf6 100%);
        --sa-accent: #4f46e5;
        --sa-icon: '\F1AC'; /* bi-info-circle-fill */
        position: relative;
        background: #ffffff;
        border-radius: 1rem;
        padding: 0;
        overflow: hidden;
        pointer-events: all;
        box-shadow: 0 18px 44px rgba(15, 23, 42, .18), 0 4px 12px rgba(15, 23, 42, .06);
        animation: skyAlertIn .55s cubic-bezier(.22, 1, .36, 1) both;
        will-change: transform, opacity;
        min-width: 320px;
    }

    .sky-alert.is-leaving {
        animation: skyAlertOut .35s cubic-bezier(.4, 0, 1, 1) forwards;
    }

    @keyframes skyAlertIn {
        0%   { transform: translateX(110%) scale(.95); opacity: 0; }
        65%  { transform: translateX(-6px) scale(1.02); opacity: 1; }
        100% { transform: translateX(0) scale(1); opacity: 1; }
    }
    @keyframes skyAlertOut {
        to { transform: translateX(120%) scale(.95); opacity: 0; }
    }

    /* Sky background bar */
    .sky-alert .sa-sky {
        position: relative;
        height: 56px;
        background: var(--sa-grad);
        overflow: hidden;
        display: flex;
        align-items: center;
        padding: 0 1rem 0 1.1rem;
    }

    /* Cloud streaks */
    .sky-alert .sa-sky::before,
    .sky-alert .sa-sky::after {
        content: '';
        position: absolute;
        background: rgba(255, 255, 255, .25);
        border-radius: 999px;
        pointer-events: none;
    }
    .sky-alert .sa-sky::before {
        width: 70px; height: 8px;
        top: 14px; left: -80px;
        filter: blur(1px);
        animation: saCloud 7s linear infinite;
    }
    .sky-alert .sa-sky::after {
        width: 100px; height: 6px;
        top: 32px; left: -120px;
        filter: blur(1.5px);
        animation: saCloud 9s linear infinite 1.2s;
        opacity: .55;
    }
    @keyframes saCloud {
        from { transform: translateX(0); }
        to   { transform: translateX(440px); }
    }

    /* Plane icon flying across */
    .sky-alert .sa-plane {
        position: absolute;
        top: 50%;
        left: -30px;
        transform: translateY(-50%) rotate(-8deg);
        color: #fff;
        font-size: 1.35rem;
        animation: saPlane 3.2s cubic-bezier(.22, 1, .36, 1) forwards;
        filter: drop-shadow(0 2px 4px rgba(0, 0, 0, .15));
    }
    @keyframes saPlane {
        0%   { left: -30px; opacity: 0; transform: translateY(-50%) rotate(-12deg) scale(.85); }
        15%  { opacity: 1; }
        70%  { left: calc(100% - 60px); opacity: 1; transform: translateY(-50%) rotate(-6deg) scale(1.1); }
        100% { left: calc(100% + 30px); opacity: 0; transform: translateY(-65%) rotate(-12deg) scale(.9); }
    }

    /* Vapor trail */
    .sky-alert .sa-vapor {
        position: absolute;
        top: 50%;
        left: 0;
        width: 0;
        height: 3px;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, .85) 60%, rgba(255, 255, 255, 0));
        transform: translateY(-50%);
        animation: saVapor 3.2s cubic-bezier(.22, 1, .36, 1) forwards;
        pointer-events: none;
        border-radius: 999px;
    }
    @keyframes saVapor {
        0%   { width: 0; opacity: 0; }
        20%  { width: 50px; opacity: .6; }
        70%  { width: calc(100% - 50px); opacity: .9; }
        100% { width: calc(100% + 60px); opacity: 0; }
    }

    /* Type tag (top-right of sky) */
    .sky-alert .sa-tag {
        margin-left: auto;
        position: relative;
        z-index: 2;
        background: rgba(255, 255, 255, .2);
        border: 1px solid rgba(255, 255, 255, .35);
        backdrop-filter: blur(8px);
        color: #fff;
        font-size: .68rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .08em;
        padding: .25rem .65rem;
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
        gap: .35rem;
    }
    .sky-alert .sa-tag i { font-size: .82rem; }

    /* Body */
    .sky-alert .sa-body {
        display: flex;
        align-items: flex-start;
        gap: .85rem;
        padding: .9rem 1rem 1rem;
    }
    .sky-alert .sa-icon {
        width: 38px; height: 38px;
        border-radius: 12px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: var(--sa-grad);
        color: #fff;
        font-size: 1.1rem;
        flex-shrink: 0;
        margin-top: -22px;
        border: 3px solid #fff;
        box-shadow: 0 6px 14px rgba(0, 0, 0, .15);
    }
    .sky-alert .sa-icon::before {
        font-family: 'bootstrap-icons';
        content: var(--sa-icon);
    }
    .sky-alert .sa-content { flex: 1 1 auto; min-width: 0; }
    .sky-alert .sa-title {
        font-weight: 800;
        color: #0f172a;
        font-size: .95rem;
        line-height: 1.25;
        margin: 0 0 .15rem;
        letter-spacing: -.01em;
    }
    .sky-alert .sa-message {
        color: #475569;
        font-size: .85rem;
        line-height: 1.5;
        margin: 0;
        word-wrap: break-word;
        white-space: pre-line;
    }
    .sky-alert .sa-message a {
        color: var(--sa-accent);
        font-weight: 600;
        text-decoration: underline;
    }

    /* Close button */
    .sky-alert .sa-close {
        background: transparent;
        border: 0;
        color: #94a3b8;
        font-size: 1.05rem;
        line-height: 1;
        padding: .15rem .35rem;
        margin: -.15rem -.25rem 0 .25rem;
        border-radius: 8px;
        cursor: pointer;
        transition: all .15s ease;
        align-self: flex-start;
    }
    .sky-alert .sa-close:hover {
        background: rgba(15, 23, 42, .06);
        color: #1e293b;
        transform: rotate(90deg);
    }

    /* Progress bar */
    .sky-alert .sa-progress {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        height: 3px;
        background: rgba(15, 23, 42, .06);
    }
    .sky-alert .sa-progress > span {
        display: block;
        height: 100%;
        background: var(--sa-grad);
        animation: saProgress var(--sa-duration, 6s) linear forwards;
        transform-origin: left center;
    }
    @keyframes saProgress {
        from { width: 100%; }
        to   { width: 0%; }
    }
    .sky-alert.is-paused .sa-progress > span { animation-play-state: paused; }

    /* Action buttons (confirm dialog) */
    .sky-alert .sa-actions {
        display: flex;
        gap: .5rem;
        justify-content: flex-end;
        margin-top: .85rem;
    }
    .sky-alert .sa-actions button {
        font-size: .8rem;
        font-weight: 700;
        padding: .45rem 1rem;
        border-radius: .55rem;
        border: 0;
        cursor: pointer;
        transition: all .18s ease;
    }
    .sky-alert .sa-btn-cancel {
        background: #f1f5f9;
        color: #475569;
    }
    .sky-alert .sa-btn-cancel:hover {
        background: #e2e8f0;
        color: #1e293b;
    }
    .sky-alert .sa-btn-confirm {
        background: var(--sa-grad);
        color: #fff;
        box-shadow: 0 6px 14px rgba(99, 102, 241, .25);
    }
    .sky-alert .sa-btn-confirm:hover {
        transform: translateY(-1px);
        box-shadow: 0 10px 20px rgba(99, 102, 241, .35);
    }

    /* ============ Type variants ============ */
    .sky-alert.sa-success {
        --sa-grad: linear-gradient(135deg, #34d399 0%, #10b981 50%, #059669 100%);
        --sa-accent: #047857;
        --sa-icon: '\F26B'; /* bi-check-circle-fill (lookup) */
    }
    .sky-alert.sa-success .sa-icon::before { content: '\F26B'; }

    .sky-alert.sa-danger {
        --sa-grad: linear-gradient(135deg, #fb7185 0%, #f43f5e 50%, #e11d48 100%);
        --sa-accent: #be123c;
    }
    .sky-alert.sa-danger .sa-icon::before { content: '\F623'; } /* bi-exclamation-octagon-fill */

    .sky-alert.sa-warning {
        --sa-grad: linear-gradient(135deg, #fbbf24 0%, #f59e0b 50%, #d97706 100%);
        --sa-accent: #b45309;
    }
    .sky-alert.sa-warning .sa-icon::before { content: '\F33B'; } /* bi-exclamation-triangle-fill */

    .sky-alert.sa-info {
        --sa-grad: linear-gradient(135deg, #38bdf8 0%, #0ea5e9 50%, #0284c7 100%);
        --sa-accent: #0369a1;
    }
    .sky-alert.sa-info .sa-icon::before { content: '\F431'; } /* bi-info-circle-fill */

    .sky-alert.sa-confirm {
        --sa-grad: linear-gradient(135deg, #818cf8 0%, #6366f1 50%, #4f46e5 100%);
        --sa-accent: #4338ca;
    }
    .sky-alert.sa-confirm .sa-icon::before { content: '\F505'; } /* bi-question-circle-fill */

    /* Confirm/alert overlay (modal style for confirm()) */
    .sky-alert-overlay {
        position: fixed;
        inset: 0;
        background: rgba(15, 23, 42, .55);
        backdrop-filter: blur(6px);
        z-index: 9998;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 1rem;
        animation: saFadeIn .25s ease both;
    }
    @keyframes saFadeIn {
        from { opacity: 0; }
        to   { opacity: 1; }
    }
    .sky-alert-overlay.is-leaving { animation: saFadeOut .22s ease forwards; }
    @keyframes saFadeOut {
        to { opacity: 0; }
    }
    .sky-alert-overlay .sky-alert {
        max-width: 440px;
        width: 100%;
    }

    /* ============ Sky Confirm — premium cockpit panel ============ */
    .sky-confirm {
        --sc-grad: linear-gradient(135deg, #6366f1 0%, #8b5cf6 50%, #ec4899 100%);
        --sc-accent: #4338ca;
        --sc-icon-bg: linear-gradient(135deg, #818cf8, #6366f1);
        --sc-icon: '\F505'; /* question-circle-fill */
        position: relative;
        width: min(440px, calc(100vw - 2rem));
        background: #ffffff;
        border-radius: 1.4rem;
        overflow: hidden;
        box-shadow: 0 30px 60px rgba(15, 23, 42, .35), 0 8px 18px rgba(15, 23, 42, .15);
        animation: scIn .55s cubic-bezier(.22, 1, .36, 1) both;
        will-change: transform, opacity;
    }
    .sky-confirm.is-leaving { animation: scOut .35s cubic-bezier(.4, 0, 1, 1) forwards; }
    @keyframes scIn {
        0%   { transform: translateY(40px) scale(.92); opacity: 0; }
        70%  { transform: translateY(-6px) scale(1.01); opacity: 1; }
        100% { transform: translateY(0) scale(1); opacity: 1; }
    }
    @keyframes scOut {
        to { transform: translateY(20px) scale(.96); opacity: 0; }
    }

    /* Hero cockpit panel */
    .sky-confirm .sc-hero {
        position: relative;
        height: 130px;
        background: var(--sc-grad);
        overflow: hidden;
    }
    .sky-confirm .sc-hero::before,
    .sky-confirm .sc-hero::after {
        content: '';
        position: absolute;
        border-radius: 50%;
        pointer-events: none;
    }
    .sky-confirm .sc-hero::before {
        width: 220px; height: 220px;
        right: -80px; top: -80px;
        background: radial-gradient(circle, rgba(255,255,255,.30), transparent 70%);
    }
    .sky-confirm .sc-hero::after {
        width: 160px; height: 160px;
        left: -60px; bottom: -60px;
        background: radial-gradient(circle, rgba(255,255,255,.18), transparent 70%);
    }

    /* Cloud streaks */
    .sky-confirm .sc-cloud {
        position: absolute;
        background: rgba(255, 255, 255, .35);
        border-radius: 999px;
        filter: blur(2px);
        pointer-events: none;
    }
    .sky-confirm .sc-cloud.c1 { width: 90px; height: 8px; top: 20px; left: -100px; animation: scCloud 6s linear infinite; }
    .sky-confirm .sc-cloud.c2 { width: 120px; height: 6px; top: 50px; left: -140px; animation: scCloud 8s linear infinite 1s; opacity: .6; }
    .sky-confirm .sc-cloud.c3 { width: 70px; height: 7px; top: 80px; left: -90px; animation: scCloud 7s linear infinite .6s; opacity: .8; }
    @keyframes scCloud {
        from { transform: translateX(0); }
        to   { transform: translateX(560px); }
    }

    /* Runway lights at bottom of hero */
    .sky-confirm .sc-runway {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        height: 22px;
        background: linear-gradient(180deg, transparent, rgba(255, 255, 255, .15));
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 18px;
        padding: 0 28px 4px;
    }
    .sky-confirm .sc-runway span {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: rgba(255, 255, 255, .85);
        box-shadow: 0 0 8px rgba(255, 255, 255, .9);
        animation: scLight 1.4s ease-in-out infinite;
    }
    .sky-confirm .sc-runway span:nth-child(1) { animation-delay: 0s; }
    .sky-confirm .sc-runway span:nth-child(2) { animation-delay: .1s; }
    .sky-confirm .sc-runway span:nth-child(3) { animation-delay: .2s; }
    .sky-confirm .sc-runway span:nth-child(4) { animation-delay: .3s; }
    .sky-confirm .sc-runway span:nth-child(5) { animation-delay: .4s; }
    .sky-confirm .sc-runway span:nth-child(6) { animation-delay: .5s; }
    .sky-confirm .sc-runway span:nth-child(7) { animation-delay: .6s; }
    .sky-confirm .sc-runway span:nth-child(8) { animation-delay: .7s; }
    @keyframes scLight {
        0%, 100% { opacity: .35; transform: scale(.85); }
        50%      { opacity: 1; transform: scale(1.2); }
    }

    /* Plane taking off */
    .sky-confirm .sc-plane {
        position: absolute;
        left: -40px;
        bottom: 14px;
        font-size: 2rem;
        color: #ffffff;
        animation: scPlaneTakeoff 2.4s cubic-bezier(.4, 0, .2, 1) forwards;
        filter: drop-shadow(0 4px 8px rgba(0, 0, 0, .25));
        z-index: 2;
    }
    @keyframes scPlaneTakeoff {
        0%   { left: -40px; bottom: 14px; transform: rotate(0); opacity: 0; }
        15%  { left: 60px; bottom: 14px; transform: rotate(-5deg); opacity: 1; }
        45%  { left: 200px; bottom: 38px; transform: rotate(-22deg); opacity: 1; }
        70%  { left: 340px; bottom: 70px; transform: rotate(-18deg); opacity: 1; }
        100% { left: 480px; bottom: 95px; transform: rotate(-15deg); opacity: 0; }
    }

    /* Tag */
    .sky-confirm .sc-tag {
        position: absolute;
        top: 16px;
        left: 18px;
        z-index: 3;
        background: rgba(255, 255, 255, .22);
        border: 1px solid rgba(255, 255, 255, .35);
        backdrop-filter: blur(8px);
        color: #ffffff;
        font-size: .68rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .1em;
        padding: .35rem .75rem;
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        animation: scTagBounce 1.8s ease-in-out infinite;
    }
    @keyframes scTagBounce {
        0%, 100% { transform: translateY(0); }
        50%      { transform: translateY(-3px); }
    }

    /* Big icon overlap */
    .sky-confirm .sc-icon {
        position: absolute;
        top: 95px;
        left: 50%;
        transform: translateX(-50%);
        width: 68px;
        height: 68px;
        border-radius: 22px;
        background: var(--sc-icon-bg);
        color: #ffffff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.85rem;
        border: 4px solid #ffffff;
        box-shadow: 0 10px 24px rgba(15, 23, 42, .25);
        z-index: 3;
        animation: scIconPop .65s cubic-bezier(.22, 1, .36, 1) .15s both;
    }
    .sky-confirm .sc-icon::before {
        font-family: 'bootstrap-icons';
        content: var(--sc-icon);
    }
    @keyframes scIconPop {
        0%   { transform: translate(-50%, 12px) scale(.5) rotate(-12deg); opacity: 0; }
        70%  { transform: translate(-50%, 0) scale(1.08) rotate(0); opacity: 1; }
        100% { transform: translate(-50%, 0) scale(1); }
    }

    .sky-confirm .sc-body {
        padding: 3rem 1.75rem 1.6rem;
        text-align: center;
    }
    .sky-confirm .sc-title {
        font-size: 1.2rem;
        font-weight: 800;
        color: #0f172a;
        letter-spacing: -.01em;
        margin: 0 0 .55rem;
    }
    .sky-confirm .sc-message {
        font-size: .9rem;
        line-height: 1.55;
        color: #475569;
        margin: 0;
        white-space: pre-line;
    }
    .sky-confirm .sc-message strong { color: #0f172a; }

    /* Optional input (prompt-like) */
    .sky-confirm .sc-input {
        margin-top: 1rem;
        width: 100%;
        border: 1px solid #e2e8f0;
        background: #f8fafc;
        border-radius: .65rem;
        padding: .7rem .9rem;
        font-size: .9rem;
        transition: all .18s ease;
        outline: 0;
        font-family: inherit;
    }
    .sky-confirm .sc-input:focus {
        border-color: var(--sc-accent);
        background: #ffffff;
        box-shadow: 0 0 0 4px rgba(99, 102, 241, .12);
    }

    .sky-confirm .sc-actions {
        display: flex;
        gap: .65rem;
        padding: 0 1.75rem 1.5rem;
    }
    .sky-confirm .sc-actions button {
        flex: 1 1 0;
        padding: .8rem 1rem;
        border-radius: .8rem;
        border: 0;
        font-weight: 700;
        font-size: .9rem;
        cursor: pointer;
        transition: all .22s ease;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: .45rem;
    }
    .sky-confirm .sc-btn-cancel {
        background: #f1f5f9;
        color: #475569;
        border: 1px solid #e2e8f0;
    }
    .sky-confirm .sc-btn-cancel:hover {
        background: #e2e8f0;
        color: #1e293b;
        transform: translateY(-1px);
    }
    .sky-confirm .sc-btn-confirm {
        background: var(--sc-grad);
        background-size: 200% 100%;
        background-position: 0% 0%;
        color: #ffffff;
        box-shadow: 0 8px 22px rgba(99, 102, 241, .32);
    }
    .sky-confirm .sc-btn-confirm:hover {
        background-position: 100% 0%;
        transform: translateY(-2px);
        box-shadow: 0 14px 30px rgba(99, 102, 241, .45);
    }
    .sky-confirm .sc-btn-confirm:focus-visible,
    .sky-confirm .sc-btn-cancel:focus-visible {
        outline: 3px solid rgba(99, 102, 241, .5);
        outline-offset: 2px;
    }

    /* ============ Sky Confirm variants ============ */
    .sky-confirm.sc-danger {
        --sc-grad: linear-gradient(135deg, #fb7185 0%, #f43f5e 50%, #e11d48 100%);
        --sc-accent: #b91c1c;
        --sc-icon-bg: linear-gradient(135deg, #fb7185, #ef4444);
        --sc-icon: '\F33A'; /* exclamation-triangle */
    }
    .sky-confirm.sc-danger .sc-icon::before { content: '\F33B'; }

    .sky-confirm.sc-warning {
        --sc-grad: linear-gradient(135deg, #fbbf24 0%, #f59e0b 50%, #d97706 100%);
        --sc-accent: #b45309;
        --sc-icon-bg: linear-gradient(135deg, #fbbf24, #f59e0b);
    }
    .sky-confirm.sc-warning .sc-icon::before { content: '\F33B'; }

    .sky-confirm.sc-success {
        --sc-grad: linear-gradient(135deg, #34d399 0%, #10b981 50%, #059669 100%);
        --sc-accent: #047857;
        --sc-icon-bg: linear-gradient(135deg, #34d399, #10b981);
    }
    .sky-confirm.sc-success .sc-icon::before { content: '\F26B'; }

    .sky-confirm.sc-info {
        --sc-grad: linear-gradient(135deg, #38bdf8 0%, #0ea5e9 50%, #0284c7 100%);
        --sc-accent: #0369a1;
        --sc-icon-bg: linear-gradient(135deg, #38bdf8, #0ea5e9);
    }
    .sky-confirm.sc-info .sc-icon::before { content: '\F431'; }

    @media (max-width: 575.98px) {
        .sky-alert-stack {
            top: .75rem;
            right: .75rem;
            left: .75rem;
            max-width: none;
        }
        .sky-alert {
            min-width: 0;
        }
    }
</style>

<div class="sky-alert-stack" id="sky-alert-stack"></div>

@php
    $skyMessages = [];
    foreach (['success' => 'success', 'error' => 'danger', 'warning' => 'warning', 'info' => 'info', 'status' => 'success'] as $key => $type) {
        if (session()->has($key)) {
            $skyMessages[] = ['type' => $type, 'message' => (string) session($key)];
        }
    }
    if ($errors->any()) {
        $skyMessages[] = ['type' => 'danger', 'message' => $errors->first(), 'title' => 'Validasi gagal'];
    }
@endphp

<script>
    (function () {
        const stack = document.getElementById('sky-alert-stack');
        if (! stack) return;

        const ICON_MAP = {
            success: { tag: 'Selesai',    icon: 'bi-check-circle-fill' },
            danger:  { tag: 'Kesalahan',  icon: 'bi-exclamation-octagon-fill' },
            error:   { tag: 'Kesalahan',  icon: 'bi-exclamation-octagon-fill' },
            warning: { tag: 'Perhatian',  icon: 'bi-exclamation-triangle-fill' },
            info:    { tag: 'Informasi',  icon: 'bi-info-circle-fill' },
            confirm: { tag: 'Konfirmasi', icon: 'bi-question-circle-fill' },
        };

        const escapeHtml = (str) => String(str ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');

        function buildAlert(opts) {
            const type   = (opts.type || 'info').replace('error', 'danger');
            const meta   = ICON_MAP[type] || ICON_MAP.info;
            const title  = opts.title  || (type === 'success' ? 'Berhasil!' :
                                           type === 'danger'  ? 'Gagal' :
                                           type === 'warning' ? 'Perhatian' :
                                           type === 'confirm' ? 'Konfirmasi' : 'Informasi');
            const message = opts.message || '';
            const duration = opts.duration ?? (type === 'danger' ? 8000 : 6000);

            const card = document.createElement('div');
            card.className = `sky-alert sa-${type}`;
            card.style.setProperty('--sa-duration', `${duration}ms`);

            card.innerHTML = `
                <div class="sa-sky">
                    <div class="sa-vapor"></div>
                    <i class="bi bi-airplane-fill sa-plane"></i>
                    <span class="sa-tag"><i class="bi ${meta.icon}"></i> ${escapeHtml(meta.tag)}</span>
                </div>
                <div class="sa-body">
                    <span class="sa-icon" aria-hidden="true"></span>
                    <div class="sa-content">
                        <div class="sa-title">${escapeHtml(title)}</div>
                        <div class="sa-message">${escapeHtml(message)}</div>
                        ${opts.actions ? `<div class="sa-actions"></div>` : ''}
                    </div>
                    ${opts.dismissible !== false ? '<button type="button" class="sa-close" aria-label="Tutup">&times;</button>' : ''}
                </div>
                ${opts.persistent ? '' : '<div class="sa-progress"><span></span></div>'}
            `;
            return { card, duration };
        }

        function dismiss(card) {
            if (! card || card.classList.contains('is-leaving')) return;
            card.classList.add('is-leaving');
            card.addEventListener('animationend', () => card.remove(), { once: true });
        }

        const SkyAlert = {
            // Internal: track recently shown messages to dedupe duplicate calls
            // (mis. flash session + Bootstrap .alert dengan teks yang sama).
            _recent: new Map(),
            _dedupeKey(opts) {
                const type = (opts.type || 'info').replace('error', 'danger');
                const text = String(opts.message || '').trim().replace(/\s+/g, ' ');
                return `${type}::${text}`;
            },
            _isDuplicate(opts) {
                if (! opts || ! opts.message) return false;
                const key = this._dedupeKey(opts);
                const seen = this._recent.get(key);
                // Anggap duplikat kalau pesan identik muncul dalam 1.5 detik terakhir.
                return seen && (Date.now() - seen) < 1500;
            },
            _markShown(opts) {
                if (! opts || ! opts.message) return;
                const key = this._dedupeKey(opts);
                this._recent.set(key, Date.now());
                // Bersihkan entry lama secara periodik.
                if (this._recent.size > 50) {
                    const cutoff = Date.now() - 5000;
                    for (const [k, t] of this._recent) {
                        if (t < cutoff) this._recent.delete(k);
                    }
                }
            },

            show(opts = {}) {
                if (this._isDuplicate(opts)) return null;
                this._markShown(opts);

                const { card, duration } = buildAlert(opts);
                stack.appendChild(card);

                const closeBtn = card.querySelector('.sa-close');
                if (closeBtn) closeBtn.addEventListener('click', () => dismiss(card));

                if (! opts.persistent) {
                    let timer = setTimeout(() => dismiss(card), duration);
                    card.addEventListener('mouseenter', () => {
                        card.classList.add('is-paused');
                        clearTimeout(timer);
                    });
                    card.addEventListener('mouseleave', () => {
                        card.classList.remove('is-paused');
                        timer = setTimeout(() => dismiss(card), 1500);
                    });
                }
                return card;
            },
            success(message, title) { return this.show({ type: 'success', message, title }); },
            error(message, title)   { return this.show({ type: 'danger',  message, title }); },
            warning(message, title) { return this.show({ type: 'warning', message, title }); },
            info(message, title)    { return this.show({ type: 'info',    message, title }); },

            // Modal overlay: alert (one button) & confirm (yes/no) — pakai SkyConfirm cockpit panel
            alert(message, title = 'Pemberitahuan', opts = {}) {
                return SkyConfirm.show({
                    type: opts.type || 'info',
                    title,
                    message,
                    cancelText: null,
                    confirmText: opts.confirmText || 'OK',
                    icon: opts.icon,
                });
            },

            confirm(message, title = 'Konfirmasi', opts = {}) {
                return SkyConfirm.show({
                    type: opts.type || 'confirm',
                    title,
                    message,
                    cancelText: opts.cancelText || 'Batal',
                    confirmText: opts.confirmText || 'Lanjutkan',
                    icon: opts.icon,
                });
            },
        };

        // ============ SkyConfirm — premium cockpit modal ============
        const TYPE_META = {
            confirm: { tag: 'Konfirmasi', defaultType: 'confirm' },
            danger:  { tag: 'Tindakan Berbahaya' },
            warning: { tag: 'Perhatian' },
            success: { tag: 'Selesai' },
            info:    { tag: 'Informasi' },
        };

        const SkyConfirm = {
            show(opts = {}) {
                return new Promise((resolve) => {
                    const type = (opts.type || 'confirm').replace('error', 'danger');
                    const meta = TYPE_META[type] || TYPE_META.confirm;
                    const title = opts.title || 'Konfirmasi';
                    const message = opts.message || '';
                    const confirmText = opts.confirmText ?? 'Lanjutkan';
                    const cancelText = opts.cancelText; // bila null, tombol cancel disembunyikan (alert mode)

                    const overlay = document.createElement('div');
                    overlay.className = 'sky-alert-overlay';

                    const card = document.createElement('div');
                    card.className = `sky-confirm sc-${type}`;
                    card.setAttribute('role', 'alertdialog');
                    card.setAttribute('aria-modal', 'true');

                    card.innerHTML = `
                        <div class="sc-hero">
                            <span class="sc-cloud c1"></span>
                            <span class="sc-cloud c2"></span>
                            <span class="sc-cloud c3"></span>
                            <span class="sc-tag"><i class="bi bi-airplane-fill"></i> ${escapeHtml(meta.tag)}</span>
                            <i class="bi bi-airplane-fill sc-plane"></i>
                            <div class="sc-runway">
                                <span></span><span></span><span></span><span></span>
                                <span></span><span></span><span></span><span></span>
                            </div>
                        </div>
                        <div class="sc-icon"></div>
                        <div class="sc-body">
                            <h3 class="sc-title">${escapeHtml(title)}</h3>
                            <p class="sc-message">${escapeHtml(message)}</p>
                        </div>
                        <div class="sc-actions">
                            ${cancelText ? `<button type="button" class="sc-btn-cancel"><i class="bi bi-x-circle"></i> ${escapeHtml(cancelText)}</button>` : ''}
                            <button type="button" class="sc-btn-confirm"><i class="bi bi-check2-circle"></i> ${escapeHtml(confirmText)}</button>
                        </div>
                    `;

                    overlay.appendChild(card);
                    document.body.appendChild(overlay);

                    // Lock body scroll
                    const prevOverflow = document.body.style.overflow;
                    document.body.style.overflow = 'hidden';

                    const close = (result) => {
                        if (overlay.classList.contains('is-leaving')) return;
                        overlay.classList.add('is-leaving');
                        card.classList.add('is-leaving');
                        document.body.style.overflow = prevOverflow;
                        document.removeEventListener('keydown', onKey);
                        setTimeout(() => overlay.remove(), 320);
                        resolve(result);
                    };

                    const cancelBtn = card.querySelector('.sc-btn-cancel');
                    const confirmBtn = card.querySelector('.sc-btn-confirm');
                    cancelBtn?.addEventListener('click', () => close(false));
                    confirmBtn.addEventListener('click', () => close(true));

                    overlay.addEventListener('click', (e) => {
                        if (e.target === overlay) close(false);
                    });

                    const onKey = (e) => {
                        if (e.key === 'Escape') close(false);
                        if (e.key === 'Enter') { e.preventDefault(); close(true); }
                    };
                    document.addEventListener('keydown', onKey);

                    // Auto-focus tombol konfirmasi
                    setTimeout(() => confirmBtn.focus(), 50);
                });
            },
        };

        window.SkyAlert = SkyAlert;
        window.SkyConfirm = SkyConfirm;

        // Override native alert (non-blocking, pakai overlay cockpit)
        const nativeAlert = window.alert.bind(window);
        const nativeConfirm = window.confirm.bind(window);

        window.alert = function (message) {
            // Tampilkan toast info (non-blocking) untuk pengalaman cepat.
            try { SkyAlert.info(String(message)); }
            catch (_) { nativeAlert(message); }
        };

        // window.confirm() spec adalah BLOCKING (sync). Modal kita async, jadi
        // override window.confirm akan break form yang punya onsubmit="return confirm()".
        // Solusinya: kita TIDAK ganti window.confirm secara native, tapi pasang
        // submit-interceptor yang menangkap form dengan onsubmit confirm() lalu
        // menggantinya dengan SkyConfirm async.
        installConfirmInterceptor(nativeConfirm);

        function installConfirmInterceptor(nativeConfirmFn) {
            // ============ FORM SUBMIT INTERCEPTOR ============
            // Saat form di-submit, inspect attribute onsubmit asli. Bila berisi
            // 'confirm(' (regex), tangkap pesannya, batalkan submit, tampilkan
            // SkyConfirm, dan resubmit form bila user klik Lanjutkan.
            document.addEventListener('submit', function (e) {
                const form = e.target;
                if (! (form instanceof HTMLFormElement)) return;

                // Sudah pernah di-konfirm via SkyConfirm? Lewati.
                if (form.dataset.skyConfirmed === '1') {
                    form.dataset.skyConfirmed = '';
                    return;
                }

                // Cek atribut data-sky-confirm (markup eksplisit) ATAU onsubmit="return confirm('...')"
                let message = form.dataset.skyConfirm || null;
                let title   = form.dataset.skyConfirmTitle || null;
                let type    = form.dataset.skyConfirmType || null;
                let confirmText = form.dataset.skyConfirmText || null;
                let cancelText  = form.dataset.skyConfirmCancel || null;

                if (! message) {
                    const onsubmit = form.getAttribute('onsubmit') || '';
                    const m = extractConfirmMessage(onsubmit);
                    if (m) {
                        message = m;
                        // Strip onsubmit agar tidak memicu native confirm lagi setelah resubmit.
                        form.removeAttribute('onsubmit');
                    }
                }

                if (! message) return;

                e.preventDefault();
                e.stopPropagation();

                showConfirmAndResubmit(form, message, { title, type, confirmText, cancelText });
            }, true);

            // ============ BUTTON/ANCHOR onclick="...confirm(...)..." INTERCEPTOR ============
            // Jaring lebar: tangkap semua klik di phase capture, cek apakah elemen yang
            // diklik (atau ancestornya) punya atribut onclick yang memanggil confirm().
            document.addEventListener('click', function (e) {
                const target = e.target.closest('[onclick]');
                if (target && target.dataset.skyOnclickProcessed !== '1') {
                    const onclickAttr = target.getAttribute('onclick') || '';
                    const message = extractConfirmMessage(onclickAttr);
                    if (message) {
                        // Strip onclick supaya tidak men-trigger native confirm lagi
                        // saat kita simulasikan klik ulang setelah konfirmasi.
                        target.removeAttribute('onclick');
                        target.dataset.skyOnclickProcessed = '1';

                        e.preventDefault();
                        e.stopPropagation();

                        const isDangerSubmit = isDangerMessage(message, target);

                        SkyConfirm.show({
                            type: isDangerSubmit ? 'danger' : 'confirm',
                            title: isDangerSubmit ? 'Konfirmasi Tindakan' : 'Konfirmasi',
                            message,
                            confirmText: isDangerSubmit ? 'Ya, Lanjutkan' : 'Lanjutkan',
                            cancelText: 'Batal',
                        }).then((ok) => {
                            if (! ok) return;

                            // Jika tombol di dalam form, submit form-nya pakai requestSubmit
                            // (preserve formaction/formmethod kalau ada).
                            const ownerForm = target.closest('form');
                            if (ownerForm) {
                                ownerForm.dataset.skyConfirmed = '1';
                                if (typeof ownerForm.requestSubmit === 'function') {
                                    try { ownerForm.requestSubmit(target); }
                                    catch (_) { ownerForm.requestSubmit(); }
                                } else {
                                    ownerForm.submit();
                                }
                            } else {
                                // Untuk anchor / non-form button: trigger native click.
                                const tag = target.tagName.toLowerCase();
                                if (tag === 'a' && target.href) {
                                    window.location.href = target.href;
                                } else {
                                    target.click();
                                }
                            }
                        });
                        return;
                    }
                }

                // ============ data-sky-confirm INTERCEPTOR (markup eksplisit) ============
                const trigger = e.target.closest('[data-sky-confirm]');
                if (! trigger) return;
                if (trigger.dataset.skyConfirmed === '1') {
                    trigger.dataset.skyConfirmed = '';
                    return;
                }
                if (trigger.closest('form')) return; // submit handler sudah pegang

                e.preventDefault();
                SkyConfirm.show({
                    type: trigger.dataset.skyConfirmType || 'confirm',
                    title: trigger.dataset.skyConfirmTitle || 'Konfirmasi',
                    message: trigger.dataset.skyConfirm,
                    confirmText: trigger.dataset.skyConfirmText || 'Lanjutkan',
                    cancelText: trigger.dataset.skyConfirmCancel || 'Batal',
                }).then((ok) => {
                    if (! ok) return;
                    trigger.dataset.skyConfirmed = '1';
                    trigger.click();
                });
            }, true);

            // Helpers
            function extractConfirmMessage(source) {
                if (! source || typeof source !== 'string') return null;
                const m = source.match(/confirm\s*\(\s*(['"`])([\s\S]*?)\1\s*\)/);
                return m ? unescapeJsString(m[2]) : null;
            }

            function unescapeJsString(s) {
                return String(s)
                    .replace(/\\n/g, '\n')
                    .replace(/\\r/g, '\r')
                    .replace(/\\t/g, '\t')
                    .replace(/\\(['"`\\])/g, '$1');
            }

            function isDangerMessage(message, ctx) {
                const text = String(message || '').toLowerCase();
                if (/\b(hapus|delete|destroy|nonaktifkan|tolak|reject|batal\s*\w+kan)\b/.test(text)) return true;
                const form = ctx?.closest?.('form');
                if (form) {
                    if (/\b(hapus|delete|destroy|nonaktif)/i.test(form.action || '')) return true;
                    if (form.querySelector('input[name=_method][value=DELETE]')) return true;
                }
                return false;
            }

            function showConfirmAndResubmit(form, message, opts) {
                const isDangerSubmit = isDangerMessage(message, form);
                SkyConfirm.show({
                    type: opts.type || (isDangerSubmit ? 'danger' : 'confirm'),
                    title: opts.title || (isDangerSubmit ? 'Konfirmasi Tindakan' : 'Konfirmasi'),
                    message,
                    confirmText: opts.confirmText || (isDangerSubmit ? 'Ya, Lanjutkan' : 'Lanjutkan'),
                    cancelText: opts.cancelText || 'Batal',
                }).then((ok) => {
                    if (! ok) return;
                    form.dataset.skyConfirmed = '1';
                    if (typeof form.requestSubmit === 'function') {
                        form.requestSubmit();
                    } else {
                        form.submit();
                    }
                });
            }
        }

        // Auto-render Bootstrap .alert.* yang ada di halaman menjadi toast,
        // lalu sembunyikan elemen aslinya.
        function renderBootstrapAlerts() {
            const map = { 'alert-success': 'success', 'alert-danger': 'danger', 'alert-warning': 'warning', 'alert-info': 'info' };
            document.querySelectorAll('.alert').forEach((el) => {
                if (el.dataset.skyHandled) return;
                if (el.closest('.sky-alert')) return;

                let type = null;
                for (const cls of Object.keys(map)) {
                    if (el.classList.contains(cls)) { type = map[cls]; break; }
                }
                if (! type) return;

                const text = (el.textContent || '').trim();
                if (! text) return;

                el.dataset.skyHandled = '1';
                el.style.display = 'none';

                SkyAlert.show({ type, message: text });
            });
        }

        // Render Bootstrap alerts duluan, BARU render server flash messages.
        // Urutan ini penting agar dedupe bekerja:
        // - Jika halaman sudah punya .alert.alert-success dengan pesan X (yang sebenarnya
        //   ditambahkan dari $errors atau alert blade lama), toast pertama akan muncul.
        // - Lalu server message dengan teks X yang sama akan di-skip oleh _isDuplicate.
        document.addEventListener('DOMContentLoaded', () => {
            renderBootstrapAlerts();

            const serverMessages = @json($skyMessages);
            serverMessages.forEach((m, idx) => {
                setTimeout(() => SkyAlert.show({
                    type: m.type,
                    title: m.title || undefined,
                    message: m.message,
                }), idx * 250);
            });
        });
    })();
</script>
