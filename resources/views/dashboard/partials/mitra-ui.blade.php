<style>
    @keyframes mpHeroReveal {
        from { opacity: 0; transform: translateY(16px) scale(.985); }
        to { opacity: 1; transform: translateY(0) scale(1); }
    }

    @keyframes mpHeroGlow {
        0%, 100% { opacity: .6; transform: translate3d(0, 0, 0) rotate(0deg); }
        50% { opacity: .96; transform: translate3d(-14px, 10px, 0) rotate(2deg); }
    }

    @keyframes mpHeroSweep {
        0% { transform: translateX(-120%) skewX(-18deg); opacity: 0; }
        20% { opacity: .28; }
        55%, 100% { transform: translateX(220%) skewX(-18deg); opacity: 0; }
    }

    @keyframes mpHeroIconFloat {
        0%, 100% { transform: translateY(0) rotate(0deg); }
        50% { transform: translateY(-4px) rotate(2deg); }
    }

    @keyframes mpHeroIconPulse {
        0%, 100% { box-shadow: 0 14px 28px rgba(37, 99, 235, .26), 0 0 0 0 rgba(147, 197, 253, .22); }
        50% { box-shadow: 0 18px 34px rgba(37, 99, 235, .34), 0 0 0 12px rgba(147, 197, 253, 0); }
    }

    .mp-hero {
        position: relative;
        isolation: isolate;
        overflow: hidden;
        border: 1px solid rgba(147, 197, 253, .28);
        border-top: 1px solid rgba(251, 191, 36, .42);
        border-radius: 0 24px 24px 0;
        background:
            radial-gradient(circle at 18% 30%, rgba(96, 165, 250, .28), transparent 28%),
            linear-gradient(110deg, #071421 0%, #0d2744 42%, #174f86 100%);
        color: #fff;
        padding: 28px 30px;
        box-shadow: 0 18px 50px rgba(18, 53, 92, .22);
        animation: mpHeroReveal .55s cubic-bezier(.2,.8,.2,1) both;
    }

    .mp-hero::before,
    .mp-hero::after {
        content: "";
        position: absolute;
        pointer-events: none;
        z-index: -1;
    }

    .mp-hero::before {
        width: 430px;
        height: 220px;
        right: -90px;
        top: -80px;
        border-radius: 0 0 0 999px;
        border-left: 2px solid rgba(251, 191, 36, .44);
        border-bottom: 2px solid rgba(147, 197, 253, .24);
        background: radial-gradient(circle at 50% 30%, rgba(96, 165, 250, .18), transparent 62%);
        animation: mpHeroGlow 5.6s ease-in-out infinite;
    }

    .mp-hero::after {
        inset: 0;
        width: 46%;
        background: linear-gradient(90deg, transparent, rgba(125,211,252,.12), rgba(255,255,255,.20), rgba(96,165,250,.10), transparent);
        animation: mpHeroSweep 4.2s ease-in-out infinite;
    }

    .mp-hero > * {
        position: relative;
        z-index: 1;
    }

    .mp-hero .btn-light,
    .mp-hero .btn-outline-light {
        border-color: rgba(191, 219, 254, .55) !important;
        background: rgba(255, 255, 255, .10) !important;
        color: #fff !important;
        box-shadow: 0 10px 24px rgba(15, 23, 42, .18);
        backdrop-filter: blur(8px);
    }

    .mp-hero .btn-light:hover,
    .mp-hero .btn-outline-light:hover {
        background: rgba(255, 255, 255, .18) !important;
        transform: translateY(-1px);
    }

    .mp-hero-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 46px;
        height: 46px;
        flex: 0 0 46px;
        border-radius: 14px;
        background: #2563eb;
        color: #fff;
        box-shadow: 0 14px 28px rgba(37, 99, 235, .26);
        animation: mpHeroIconFloat 3s ease-in-out infinite, mpHeroIconPulse 2.6s ease-in-out infinite;
    }

    .mp-hero-icon i {
        filter: drop-shadow(0 5px 9px rgba(15, 23, 42, .2));
    }

    .mp-card,
    .section-card {
        overflow: hidden;
        border: 1px solid rgba(37, 99, 235, .12) !important;
        border-radius: 18px !important;
        background: #fff;
        box-shadow: 0 16px 42px rgba(37, 99, 235, .08) !important;
    }

    .mp-card > .card-body,
    .section-card > .card-body {
        padding: 1.25rem !important;
    }

    .mp-card-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        padding: 10px 14px;
        border-bottom: 1px solid #bfdbfe;
        background: linear-gradient(90deg, #eff6ff 0%, #f8fbff 58%, #ffffff 100%);
    }

    .mp-card-title {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .mp-card-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 34px;
        height: 34px;
        flex: 0 0 34px;
        border-radius: 9px;
        color: #fff;
        background: #1d4ed8;
        box-shadow: 0 10px 20px rgba(37, 99, 235, .18);
    }

    .mp-card-title h6 {
        margin: 0;
        color: #1e3a8a;
        font-weight: 800;
    }

    .mp-card-title small {
        color: #64748b;
        font-weight: 700;
    }

    .mp-table {
        border-collapse: separate;
        border-spacing: 0;
        margin-bottom: 0;
    }

    .mp-table thead th {
        padding: 13px 16px;
        border-bottom: 1px solid rgba(148, 163, 184, .20);
        color: #64748b;
        background: rgba(248, 250, 252, .86);
        font-size: 11px;
        font-weight: 900;
        letter-spacing: .03em;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .mp-table tbody td {
        padding: 14px 16px;
        border-bottom: 1px solid rgba(226, 232, 240, .88);
        color: #475569;
        vertical-align: middle;
    }

    .mp-table tbody tr:hover {
        background: rgba(239, 246, 255, .72);
    }

    .mp-table tbody tr:last-child td {
        border-bottom: 0;
    }

    .mp-form :is(.form-control, .form-select) {
        border-color: #dbeafe;
        border-radius: .85rem;
        min-height: 42px;
        box-shadow: 0 1px 2px rgba(15, 23, 42, .03);
    }

    .mp-form :is(.form-control:focus, .form-select:focus) {
        border-color: #3b82f6;
        box-shadow: 0 0 0 4px rgba(59, 130, 246, .12);
    }

    .mp-form .form-label {
        color: #475569;
        font-weight: 800;
        margin-bottom: .45rem;
    }

    .mp-info-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 12px;
    }

    .mp-info-item {
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        background: #f8fbff;
        padding: 12px 14px;
    }

    .mp-info-label {
        color: #64748b;
        font-size: 11px;
        font-weight: 900;
        letter-spacing: .03em;
        text-transform: uppercase;
    }

    .mp-info-value {
        color: #12355c;
        font-weight: 800;
        margin-top: 4px;
        word-break: break-word;
    }

    .mp-soft-badge {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        padding: 6px 10px;
        border-radius: 999px;
        font-size: 10px;
        font-weight: 900;
        letter-spacing: .02em;
    }

    .mp-soft-badge.success { color: #047857; background: #d1fae5; }
    .mp-soft-badge.warning { color: #92400e; background: #fef3c7; }
    .mp-soft-badge.danger { color: #be123c; background: #ffe4e6; }
    .mp-soft-badge.info { color: #1d4ed8; background: #dbeafe; }
    .mp-soft-badge.muted { color: #475569; background: #e2e8f0; }

    .mp-action {
        border-radius: 10px;
        font-weight: 800;
    }

    .mp-empty {
        min-height: 118px;
        color: #64748b;
    }

    .mp-empty-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 40px;
        height: 40px;
        margin-bottom: 8px;
        border-radius: 12px;
        color: #2563eb;
        background: #dbeafe;
    }

    .file-drop {
        border-color: #bfdbfe !important;
        background: linear-gradient(180deg, #f8fbff, #fff) !important;
    }

    .file-drop:hover,
    .file-drop.dragover {
        border-color: #2563eb !important;
        background: #eff6ff !important;
    }

    .tipe-card {
        border-color: #dbeafe !important;
        background: #fff;
    }

    .tipe-card:hover,
    .tipe-card.selected {
        border-color: #2563eb !important;
        background: #eff6ff !important;
        box-shadow: 0 10px 24px rgba(37, 99, 235, .10) !important;
    }

    @media (max-width: 768px) {
        .mp-hero {
            border-radius: 0 18px 18px 0;
            padding: 22px;
        }

        .mp-info-grid {
            grid-template-columns: 1fr;
        }

        .mp-card-header {
            align-items: flex-start;
            flex-direction: column;
        }
    }

    @media (prefers-reduced-motion: reduce) {
        .mp-hero,
        .mp-hero::before,
        .mp-hero::after,
        .mp-hero-icon {
            animation: none !important;
        }
    }
</style>
