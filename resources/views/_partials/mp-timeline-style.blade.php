{{-- Reusable timeline style identical to mitra detail --}}
<style>
    @keyframes mpTimelineIn {
        from { opacity: 0; transform: translateY(12px); }
        to { opacity: 1; transform: translateY(0); }
    }
    @keyframes mpTimelineFlow {
        0% { background-position: 0 0; }
        100% { background-position: 0 140px; }
    }
    @keyframes mpTimelinePulse {
        0%, 100% {
            transform: scale(1);
            box-shadow: 0 12px 22px rgba(15, 23, 42, .14), 0 0 0 0 rgba(37, 99, 235, .22);
        }
        50% {
            transform: scale(1.08);
            box-shadow: 0 16px 28px rgba(15, 23, 42, .18), 0 0 0 10px rgba(37, 99, 235, 0);
        }
    }
    @keyframes mpTimelineRing {
        0% { opacity: .5; transform: scale(.75); }
        70%, 100% { opacity: 0; transform: scale(1.65); }
    }
    @keyframes mpTimelineIconFloat {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-2px); }
    }
    @keyframes mpTimelineShine {
        0% { transform: translateX(-130%) skewX(-18deg); opacity: 0; }
        18% { opacity: .5; }
        55%, 100% { transform: translateX(180%) skewX(-18deg); opacity: 0; }
    }
    .mp-timeline-card {
        overflow: hidden;
        border: 1px solid rgba(37, 99, 235, .12) !important;
        background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
        box-shadow: 0 18px 44px rgba(15, 23, 42, .09) !important;
    }
    .mp-timeline-header {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 14px 16px;
        border-bottom: 1px solid #bfdbfe;
        background: linear-gradient(90deg, #eff6ff 0%, #f8fbff 58%, #ffffff 100%);
    }
    .mp-timeline-header-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 36px;
        height: 36px;
        border-radius: 11px;
        color: #fff;
        background: #1d4ed8;
        box-shadow: 0 12px 24px rgba(37, 99, 235, .22);
    }
    .mp-timeline-header-title {
        margin: 0;
        color: #1e3a8a;
        font-size: 15px;
        font-weight: 900;
    }
    .mp-timeline-header-subtitle {
        color: #64748b;
        font-size: 12px;
        font-weight: 700;
    }
    .mp-timeline {
        position: relative;
        padding: 16px 16px 18px 18px;
    }
    .mp-timeline::before {
        content: "";
        position: absolute;
        left: 35px;
        top: 24px;
        bottom: 28px;
        width: 2px;
        border-radius: 999px;
        background: linear-gradient(180deg, #cbd5e1, #93c5fd, #60a5fa, #2563eb, #93c5fd);
        background-size: 100% 140px;
        animation: mpTimelineFlow 2.8s linear infinite;
        box-shadow: 0 0 18px rgba(37, 99, 235, .2);
    }
    .mp-timeline-item {
        position: relative;
        display: grid;
        grid-template-columns: 36px minmax(0, 1fr);
        gap: 12px;
        margin-bottom: 14px;
        animation: mpTimelineIn .38s ease both;
    }
    .mp-timeline-item:nth-child(2) { animation-delay: .05s; }
    .mp-timeline-item:nth-child(3) { animation-delay: .1s; }
    .mp-timeline-item:nth-child(4) { animation-delay: .15s; }
    .mp-timeline-item:nth-child(5) { animation-delay: .2s; }
    .mp-timeline-item:last-child { margin-bottom: 0; }
    .mp-timeline-dot {
        position: relative;
        z-index: 1;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 36px;
        height: 36px;
        border: 4px solid #fff;
        border-radius: 999px;
        color: #fff;
        box-shadow: 0 12px 22px rgba(15, 23, 42, .14);
        animation: mpTimelinePulse 2.4s ease-in-out infinite;
    }
    .mp-timeline-dot::after {
        content: "";
        position: absolute;
        inset: -7px;
        z-index: -1;
        border-radius: inherit;
        border: 1px solid currentColor;
        animation: mpTimelineRing 2.4s ease-out infinite;
    }
    .mp-timeline-dot i { animation: mpTimelineIconFloat 1.8s ease-in-out infinite; }
    .mp-timeline-dot.secondary { background: #64748b; }
    .mp-timeline-dot.warning   { background: #f59e0b; }
    .mp-timeline-dot.success   { background: #10b981; }
    .mp-timeline-dot.danger    { background: #ef4444; }
    .mp-timeline-dot.primary   { background: #2563eb; }
    .mp-timeline-dot.info      { background: #0ea5e9; }
    .mp-timeline-content {
        position: relative;
        overflow: hidden;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        background: rgba(255, 255, 255, .92);
        padding: 11px 12px;
        box-shadow: 0 10px 26px rgba(15, 23, 42, .05);
        transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease;
    }
    .mp-timeline-content::before {
        content: "";
        position: absolute;
        inset: 0;
        width: 44%;
        background: linear-gradient(90deg, transparent, rgba(96, 165, 250, .16), rgba(255, 255, 255, .5), transparent);
        animation: mpTimelineShine 3.6s ease-in-out infinite;
        pointer-events: none;
    }
    .mp-timeline-content:hover {
        transform: translateY(-2px);
        border-color: #bfdbfe;
        box-shadow: 0 16px 32px rgba(37, 99, 235, .11);
    }
    @media (prefers-reduced-motion: reduce) {
        .mp-timeline::before,
        .mp-timeline-dot,
        .mp-timeline-dot::after,
        .mp-timeline-dot i,
        .mp-timeline-content::before,
        .mp-timeline-item { animation: none !important; }
    }
    .mp-timeline-title {
        color: #0f172a;
        font-size: 13px;
        font-weight: 900;
        letter-spacing: .01em;
    }
    .mp-timeline-time {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        margin-top: 5px;
        color: #475569;
        font-size: 12px;
        font-weight: 700;
    }
    .mp-timeline-note {
        margin-top: 5px;
        color: #64748b;
        font-size: 12px;
        font-weight: 600;
    }
    .mp-timeline-link {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        margin-top: 8px;
        border-radius: 10px;
        font-size: 12px;
        font-weight: 900;
    }
</style>
