{{-- Reusable styles for "SA Jasa" style chart & calendar cards --}}
<style>
    /* ============== Chart & Calendar Section ============== */
    @keyframes saChartReveal {
        from { opacity: 0; transform: translateY(20px) scale(.98); }
        to   { opacity: 1; transform: translateY(0) scale(1); }
    }
    @keyframes saChartShine {
        0%        { transform: translateX(-130%) skewX(-22deg); opacity: 0; }
        24%       { opacity: .55; }
        58%, 100% { transform: translateX(220%) skewX(-22deg); opacity: 0; }
    }
    @keyframes saGaugeIn {
        from { stroke-dashoffset: var(--gauge-circ, 339); }
        to   { stroke-dashoffset: var(--gauge-target, 339); }
    }
    @keyframes saCalDayPop {
        0%   { opacity: 0; transform: scale(.6); }
        80%  { opacity: 1; transform: scale(1.08); }
        100% { transform: scale(1); }
    }
    @keyframes saDotPing {
        0%   { box-shadow: 0 0 0 0 var(--dot-color, rgba(37, 99, 235, .55)); }
        70%  { box-shadow: 0 0 0 8px transparent; }
        100% { box-shadow: 0 0 0 0 transparent; }
    }

    .sa-chart-card {
        position: relative;
        isolation: isolate;
        overflow: hidden;
        border-radius: 18px;
        background: #ffffff;
        border: 1px solid rgba(15, 23, 42, .06);
        box-shadow: 0 14px 32px rgba(15, 23, 42, .08);
        animation: saChartReveal .6s cubic-bezier(.2,.8,.2,1) both;
    }
    .row.sa-chart-row > div:nth-child(1) .sa-chart-card { animation-delay: .00s; }
    .row.sa-chart-row > div:nth-child(2) .sa-chart-card { animation-delay: .08s; }
    .row.sa-chart-row > div:nth-child(3) .sa-chart-card { animation-delay: .16s; }
    .row.sa-chart-row > div:nth-child(4) .sa-chart-card { animation-delay: .24s; }
    .row.sa-chart-row > div:nth-child(5) .sa-chart-card { animation-delay: .32s; }
    .sa-chart-card::after {
        content: "";
        position: absolute;
        inset: 0;
        width: 38%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, .55), rgba(251, 191, 36, .12), rgba(255, 255, 255, .55), transparent);
        pointer-events: none;
        animation: saChartShine 6.4s ease-in-out infinite;
    }
    .sa-chart-card .sa-chart-body { position: relative; z-index: 1; padding: 18px 20px; }
    .sa-chart-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px; gap: .75rem; }
    .sa-chart-title { display: flex; align-items: center; gap: .65rem; }
    .sa-chart-title h6 { margin: 0; color: #0f2f57; font-weight: 800; font-size: 14px; }
    .sa-chart-title small { display: block; color: #64748b; font-weight: 600; font-size: 12px; }
    .sa-chart-icon {
        display: inline-flex; align-items: center; justify-content: center;
        width: 36px; height: 36px; flex: 0 0 36px;
        border-radius: 11px;
        background: linear-gradient(135deg, #1d4ed8, #38bdf8);
        color: #fff;
        box-shadow: 0 12px 24px rgba(37, 99, 235, .22);
    }
    .sa-chart-pill {
        display: inline-flex; align-items: center; gap: .35rem;
        padding: 5px 10px;
        border-radius: 999px;
        background: #fef3c7;
        color: #b45309;
        font-size: 11px;
        font-weight: 800;
    }
    .sa-chart-canvas { position: relative; height: 280px; }
    .sa-chart-canvas.sa-chart-sm { height: 220px; }

    /* Gauge */
    .sa-gauge { position: relative; width: 200px; height: 200px; margin: 8px auto 14px; }
    .sa-gauge svg { width: 100%; height: 100%; transform: rotate(-90deg); }
    .sa-gauge .sa-gauge-track { fill: none; stroke: #e2e8f0; stroke-width: 14; }
    .sa-gauge .sa-gauge-bar {
        fill: none;
        stroke: url(#saGaugeGradient);
        stroke-width: 14;
        stroke-linecap: round;
        stroke-dasharray: var(--gauge-circ, 339);
        stroke-dashoffset: var(--gauge-circ, 339);
        animation: saGaugeIn 1.6s cubic-bezier(.2,.8,.2,1) forwards;
        filter: drop-shadow(0 6px 14px rgba(37, 99, 235, .25));
    }
    .sa-gauge-text {
        position: absolute;
        inset: 0;
        display: flex; flex-direction: column; align-items: center; justify-content: center;
    }
    .sa-gauge-text .val { font-size: 32px; font-weight: 800; color: #0f2f57; line-height: 1; }
    .sa-gauge-text .lbl { font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: .04em; margin-top: 6px; }

    /* Calendar */
    .sa-cal-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 6px; }
    .sa-cal-grid .sa-cal-h {
        text-align: center;
        font-size: 11px;
        font-weight: 800;
        color: #94a3b8;
        text-transform: uppercase;
        letter-spacing: .04em;
        padding: 6px 0;
    }
    .sa-cal-cell {
        position: relative;
        aspect-ratio: 1 / 1;
        display: flex; align-items: center; justify-content: center;
        font-size: 13px;
        font-weight: 700;
        color: #334155;
        border-radius: 10px;
        background: #f8fafc;
        cursor: default;
        transition: transform .2s ease, box-shadow .2s ease, background-color .2s ease;
        animation: saCalDayPop .4s cubic-bezier(.2,.8,.2,1) both;
    }
    .sa-cal-cell.sa-cal-empty { background: transparent; box-shadow: none; }
    .sa-cal-cell:hover { transform: translateY(-2px); box-shadow: 0 10px 18px rgba(15, 23, 42, .10); background: #fff; }
    .sa-cal-cell.sa-cal-today {
        color: #fff;
        background: linear-gradient(135deg, #1d4ed8, #38bdf8);
        box-shadow: 0 10px 22px rgba(37, 99, 235, .35);
    }
    .sa-cal-cell .sa-cal-dots {
        position: absolute;
        bottom: 5px;
        left: 50%;
        transform: translateX(-50%);
        display: flex;
        gap: 3px;
    }
    .sa-cal-cell .sa-cal-dot {
        width: 5px;
        height: 5px;
        border-radius: 999px;
        background: var(--dot-color, #64748b);
        animation: saDotPing 1.8s ease-in-out infinite;
    }
    .sa-cal-cell .sa-cal-dot.sa-dot-terbit { --dot-color: #2563eb; }
    .sa-cal-cell .sa-cal-dot.sa-dot-jt     { --dot-color: #ef4444; }
    .sa-cal-cell .sa-cal-dot.sa-dot-lunas  { --dot-color: #16a34a; }
    .sa-cal-cell.sa-has-event::after {
        content: "";
        position: absolute;
        inset: 0;
        border-radius: 10px;
        border: 1px solid rgba(37, 99, 235, .25);
        pointer-events: none;
    }
    .sa-cal-tooltip {
        position: absolute;
        bottom: calc(100% + 8px);
        left: 50%;
        transform: translateX(-50%) translateY(6px);
        min-width: 180px;
        background: #0f172a;
        color: #f8fafc;
        font-size: 11px;
        font-weight: 600;
        padding: 8px 10px;
        border-radius: 10px;
        box-shadow: 0 14px 28px rgba(15, 23, 42, .35);
        opacity: 0;
        visibility: hidden;
        transition: opacity .2s ease, transform .2s ease;
        z-index: 5;
        pointer-events: none;
        text-align: left;
    }
    .sa-cal-tooltip strong { color: #fbbf24; }
    .sa-cal-cell:hover .sa-cal-tooltip {
        opacity: 1;
        visibility: visible;
        transform: translateX(-50%) translateY(0);
    }
    .sa-cal-legend { display: flex; gap: 12px; flex-wrap: wrap; margin-top: 12px; font-size: 11px; color: #475569; font-weight: 700; }
    .sa-cal-legend span { display: inline-flex; align-items: center; gap: 5px; }
    .sa-cal-legend i { width: 8px; height: 8px; border-radius: 999px; display: inline-block; }
    .sa-cal-legend i.terbit { background: #2563eb; }
    .sa-cal-legend i.jt     { background: #ef4444; }
    .sa-cal-legend i.lunas  { background: #16a34a; }

    @media (prefers-reduced-motion: reduce) {
        .sa-chart-card,
        .sa-chart-card::after,
        .sa-gauge .sa-gauge-bar,
        .sa-cal-cell,
        .sa-cal-cell .sa-cal-dot { animation: none !important; }
    }
</style>
