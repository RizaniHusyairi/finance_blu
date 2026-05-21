{{-- Reusable stat-card style: white card + gold diagonal stripes + glow/shine/icon animation --}}
<style>
    @keyframes saStatReveal {
        from { opacity: 0; transform: translateY(20px) scale(.96); }
        to   { opacity: 1; transform: translateY(0) scale(1); }
    }
    @keyframes saStatGoldShift {
        0%, 100% { transform: translate(0, 0) skewX(-22deg); opacity: .55; }
        50%      { transform: translate(8px, -6px) skewX(-22deg); opacity: .9; }
    }
    @keyframes saStatShine {
        0%        { transform: translateX(-130%) skewX(-22deg); opacity: 0; }
        24%       { opacity: .55; }
        58%, 100% { transform: translateX(220%) skewX(-22deg); opacity: 0; }
    }
    @keyframes saStatGlow {
        0%, 100% { opacity: .35; transform: translate3d(-12px, 6px, 0) scale(1); }
        50%      { opacity: .85; transform: translate3d(28px, -16px, 0) scale(1.12); }
    }
    @keyframes saStatIconFloat {
        0%, 100% { transform: translateY(0) rotate(0deg); }
        50%      { transform: translateY(-5px) rotate(2deg); }
    }
    @keyframes saStatIconPulse {
        0%, 100% { box-shadow: 0 10px 22px rgba(15, 23, 42, .14), 0 0 0 0 var(--accent-soft, rgba(251, 191, 36, .35)); }
        50%      { box-shadow: 0 16px 32px rgba(15, 23, 42, .20), 0 0 0 12px transparent; }
    }
    @keyframes saStatIconWiggle {
        0%, 100% { transform: scale(1) rotate(0); }
        45%      { transform: scale(1.12) rotate(-6deg); }
        65%      { transform: scale(.96) rotate(4deg); }
    }
    @keyframes saStatValueIn {
        from { opacity: 0; transform: translateY(8px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    .sa-stat-card {
        position: relative;
        isolation: isolate;
        overflow: hidden;
        border-radius: 16px !important;
        background: #ffffff !important;
        border: 1px solid rgba(15, 23, 42, .06) !important;
        box-shadow: 0 10px 26px rgba(15, 23, 42, .08) !important;
        animation: saStatReveal .55s cubic-bezier(.2,.8,.2,1) both;
        transition: transform .25s ease, box-shadow .25s ease, border-color .25s ease;
    }
    .sa-stat-card .card-body { position: relative; z-index: 2; }

    /* staggered reveal: works on any direct row */
    .row.sa-stat-row > div:nth-child(1)  .sa-stat-card { animation-delay: .00s; }
    .row.sa-stat-row > div:nth-child(2)  .sa-stat-card { animation-delay: .06s; }
    .row.sa-stat-row > div:nth-child(3)  .sa-stat-card { animation-delay: .12s; }
    .row.sa-stat-row > div:nth-child(4)  .sa-stat-card { animation-delay: .18s; }
    .row.sa-stat-row > div:nth-child(5)  .sa-stat-card { animation-delay: .24s; }
    .row.sa-stat-row > div:nth-child(6)  .sa-stat-card { animation-delay: .30s; }
    .row.sa-stat-row > div:nth-child(7)  .sa-stat-card { animation-delay: .36s; }
    .row.sa-stat-row > div:nth-child(8)  .sa-stat-card { animation-delay: .42s; }
    .row.sa-stat-row > div:nth-child(9)  .sa-stat-card { animation-delay: .48s; }
    .row.sa-stat-row > div:nth-child(10) .sa-stat-card { animation-delay: .54s; }

    /* gold diagonal stripes */
    .sa-stat-card::before,
    .sa-stat-card::after {
        content: "";
        position: absolute;
        pointer-events: none;
        z-index: 1;
        top: -40%;
        height: 200%;
        background: linear-gradient(180deg, rgba(217, 119, 6, .55), rgba(251, 191, 36, .35) 60%, rgba(251, 191, 36, 0));
        animation: saStatGoldShift 6s ease-in-out infinite;
    }
    .sa-stat-card::before {
        right: 18%;
        width: 1.5px;
        filter: drop-shadow(0 0 4px rgba(251, 191, 36, .35));
    }
    .sa-stat-card::after {
        right: 6%;
        width: 3px;
        opacity: .35;
        filter: drop-shadow(0 0 6px rgba(251, 191, 36, .25));
        animation-delay: -1.4s;
    }

    .sa-stat-card .stat-glow,
    .sa-stat-card .stat-shine {
        position: absolute;
        pointer-events: none;
        z-index: 0;
    }
    .sa-stat-card .stat-glow {
        right: -90px;
        top: -110px;
        width: 240px;
        height: 240px;
        border-radius: 999px;
        background: radial-gradient(circle, var(--accent-glow, rgba(96, 165, 250, .25)), transparent 70%);
        animation: saStatGlow 5.4s ease-in-out infinite;
    }
    .sa-stat-card .stat-shine {
        inset: 0;
        width: 38%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, .55), rgba(251, 191, 36, .15), rgba(255, 255, 255, .55), transparent);
        animation: saStatShine 5.2s ease-in-out infinite;
    }
    .sa-stat-card .stat-ribbon {
        position: absolute;
        top: 18px;
        left: -60px;
        width: 160px;
        height: 1px;
        transform: rotate(-38deg);
        background: linear-gradient(90deg, transparent, rgba(217, 119, 6, .65), transparent);
        z-index: 1;
        opacity: .55;
    }

    .sa-stat-card:hover {
        transform: translateY(-6px);
        box-shadow: 0 20px 40px rgba(15, 23, 42, .14), 0 0 0 1px rgba(251, 191, 36, .35) inset !important;
        border-color: rgba(251, 191, 36, .35) !important;
    }
    .sa-stat-card:hover::before { animation-duration: 3.4s; }
    .sa-stat-card:hover .stat-icon { animation: saStatIconWiggle .65s ease both, saStatIconPulse 2.4s ease-in-out infinite; }

    .sa-stat-card .small.text-muted.fw-bold,
    .sa-stat-card .stat-label { letter-spacing: .04em; text-transform: uppercase; color: #64748b !important; }
    .sa-stat-card .text-muted { color: #64748b !important; }

    .sa-stat-card .stat-icon {
        width: 48px;
        height: 48px;
        border-radius: 14px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 22px;
        position: relative;
        z-index: 2;
        background: var(--accent-bg, #eaf2ff) !important;
        color: var(--accent, #0d6efd) !important;
        border: 1px solid var(--accent-soft, rgba(13, 110, 253, .25));
        box-shadow: 0 10px 22px rgba(15, 23, 42, .12), 0 0 0 0 var(--accent-soft, rgba(13, 110, 253, .25));
        animation: saStatIconFloat 3.2s ease-in-out infinite, saStatIconPulse 2.6s ease-in-out infinite;
        transform-origin: center;
    }
    .sa-stat-card .stat-icon i { filter: drop-shadow(0 2px 4px rgba(15, 23, 42, .12)); }

    .sa-stat-card .stat-value {
        position: relative;
        z-index: 2;
        animation: saStatValueIn .6s ease both;
        animation-delay: .25s;
    }

    .sa-stat-card .stat-accent {
        position: absolute;
        inset: 0 auto 0 0;
        width: 4px;
        z-index: 2;
        background: linear-gradient(180deg, var(--accent, #fbbf24), var(--accent-soft, rgba(251, 191, 36, .15)));
        box-shadow: 0 0 12px var(--accent-soft, rgba(251, 191, 36, .35));
    }

    @media (prefers-reduced-motion: reduce) {
        .sa-stat-card,
        .sa-stat-card::before,
        .sa-stat-card::after,
        .sa-stat-card .stat-glow,
        .sa-stat-card .stat-shine,
        .sa-stat-card .stat-icon,
        .sa-stat-card .stat-value { animation: none !important; }
        .sa-stat-card:hover { transform: none; }
    }
</style>
