@push('css')
<style>
    .book-hero {
        background: linear-gradient(135deg, #f8fbff, #eef4ff);
        border: 1px solid rgba(15, 23, 42, 0.08);
        border-radius: 1rem;
        padding: 1.5rem;
        margin-bottom: 1.25rem;
    }

    .book-card {
        border: 1px solid rgba(15, 23, 42, 0.08);
        border-radius: 1rem;
        box-shadow: 0 0.35rem 1rem rgba(15, 23, 42, 0.04);
        overflow: hidden;
        background: #fff;
    }

    .book-card .card-header {
        background: rgba(248, 250, 252, 0.9);
        border-bottom: 1px solid rgba(15, 23, 42, 0.06);
    }

    .book-summary {
        border: 1px solid rgba(15, 23, 42, 0.08);
        border-radius: 1rem;
        padding: 1rem 1.1rem;
        background: #fff;
        height: 100%;
    }

    .book-summary .label {
        font-size: .75rem;
        text-transform: uppercase;
        letter-spacing: .05em;
        color: #64748b;
        font-weight: 700;
        margin-bottom: .35rem;
    }

    .book-summary .value {
        font-size: 1.35rem;
        font-weight: 800;
        color: #0f172a;
        line-height: 1.15;
    }

    .book-filter {
        border: 1px solid rgba(15, 23, 42, 0.08);
        border-radius: 1rem;
        background: #fff;
        padding: 1rem 1.25rem;
        margin-bottom: 1.25rem;
    }

    .book-table th {
        font-size: .75rem;
        text-transform: uppercase;
        letter-spacing: .04em;
        color: #64748b;
        white-space: nowrap;
    }

    .book-table td {
        vertical-align: middle;
        font-size: .9rem;
    }

    .book-meta {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: .9rem;
    }

    .book-meta-item {
        background: #fff;
        border: 1px solid rgba(15, 23, 42, 0.08);
        border-radius: .9rem;
        padding: .9rem 1rem;
    }

    .book-meta-item .meta-label {
        font-size: .72rem;
        text-transform: uppercase;
        color: #64748b;
        letter-spacing: .05em;
        font-weight: 700;
        margin-bottom: .3rem;
    }

    .book-meta-item .meta-value {
        font-weight: 700;
        color: #0f172a;
        line-height: 1.35;
    }

    .book-empty {
        text-align: center;
        color: #64748b;
        padding: 3rem 1rem;
    }

    .book-empty i {
        font-size: 2.4rem;
        opacity: .3;
        display: block;
        margin-bottom: .75rem;
    }

    .book-timeline {
        position: relative;
        padding-left: 1.4rem;
    }

    .book-timeline::before {
        content: "";
        position: absolute;
        left: .4rem;
        top: .25rem;
        bottom: .25rem;
        width: 2px;
        background: #e2e8f0;
    }

    .book-timeline-item {
        position: relative;
        padding-bottom: 1rem;
    }

    .book-timeline-item:last-child {
        padding-bottom: 0;
    }

    .book-timeline-dot {
        position: absolute;
        left: -1.15rem;
        top: .32rem;
        width: .7rem;
        height: .7rem;
        border-radius: 999px;
        background: #2563eb;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
    }

    /* ════════════════════════════════════════════════════════════════
       BKU DESIGN SYSTEM — bahasa desain bersama untuk seluruh halaman
       Pembukuan (BKU Penerimaan/Pengeluaran + buku pembantu). Dulu inline
       di penerimaan/index; dipindah ke sini agar satu sumber kebenaran.
       ════════════════════════════════════════════════════════════════ */
    .bku-page{ --bku-accent:#6366f1; }
    .bku-hero{ position:relative; overflow:hidden; border-radius:20px; padding:26px 28px; color:#fff;
        background:linear-gradient(135deg,#4f46e5 0%,#7c3aed 55%,#4f46e5 100%);
        box-shadow:0 18px 40px -18px rgba(79,70,229,.6); display:flex; flex-wrap:wrap; gap:18px;
        align-items:center; justify-content:space-between; margin-bottom:18px; animation:bkuFadeUp .5s both; }
    .bku-hero::after{ content:""; position:absolute; right:-60px; top:-70px; width:260px; height:260px;
        background:radial-gradient(circle,rgba(255,255,255,.18),transparent 70%); pointer-events:none; }
    .bku-hero__eyebrow{ text-transform:uppercase; letter-spacing:.12em; font-size:11px; font-weight:700; opacity:.9; color:#fff; }
    .bku-hero__title{ font-weight:800; font-size:1.55rem; margin:.15rem 0 .4rem; color:#fff; }
    .bku-hero__rek{ display:inline-flex; align-items:center; gap:8px; background:rgba(255,255,255,.16);
        padding:6px 13px; border-radius:999px; font-size:.84rem; font-weight:600; color:#fff; }
    .bku-hero__rek i{ color:#fff; }
    .bku-hero__actions{ display:flex; flex-wrap:wrap; gap:10px; position:relative; z-index:1; }
    .bku-btn{ display:inline-flex; align-items:center; gap:7px; border:0; cursor:pointer; padding:10px 16px;
        border-radius:12px; font-weight:700; font-size:.85rem; text-decoration:none;
        transition:transform .15s ease, box-shadow .15s ease, background .15s ease; }
    .bku-btn--light{ background:#fff; color:#4f46e5; box-shadow:0 8px 18px -8px rgba(0,0,0,.4); }
    .bku-btn--light:hover{ transform:translateY(-2px); box-shadow:0 12px 22px -8px rgba(0,0,0,.5); color:#4338ca; }
    .bku-btn--ghost{ background:rgba(255,255,255,.16); color:#fff; }
    .bku-btn--ghost:hover{ background:rgba(255,255,255,.30); color:#fff; transform:translateY(-2px); }
    .bku-btn:disabled{ opacity:.7; cursor:default; transform:none; }

    .bku-segment{ display:inline-flex; gap:4px; background:#eef0f6; padding:5px; border-radius:14px; margin-bottom:16px; }
    .bku-segment a{ display:inline-flex; align-items:center; gap:7px; padding:9px 18px; border-radius:10px;
        font-weight:700; font-size:.85rem; color:#5b6172; text-decoration:none; transition:all .18s ease; }
    .bku-segment a:hover{ color:#4f46e5; }
    .bku-segment a.is-active{ background:#fff; color:#4f46e5; box-shadow:0 4px 12px -4px rgba(79,70,229,.4); }

    .bku-toolbar{ display:flex; flex-wrap:wrap; gap:12px; align-items:flex-end; margin-bottom:18px;
        background:#fff; border:1px solid #eef0f5; border-radius:16px; padding:16px 18px; box-shadow:0 10px 26px -18px rgba(15,23,42,.4); }
    .bku-search{ position:relative; flex:1 1 320px; }
    .bku-search > i{ position:absolute; left:16px; top:50%; transform:translateY(-50%); color:#9aa1b2; font-size:1.05rem; transition:color .2s; }
    .bku-search:focus-within > i{ color:var(--bku-accent); }
    .bku-search input{ width:100%; border:1.5px solid #e4e7ef; border-radius:14px; padding:13px 42px 13px 44px;
        font-size:.92rem; background:#fff; outline:none; transition:border-color .2s, box-shadow .2s; }
    .bku-search input:focus{ border-color:var(--bku-accent); box-shadow:0 0 0 4px rgba(99,102,241,.16); }
    .bku-search__clear{ position:absolute; right:12px; top:50%; transform:translateY(-50%); border:0; background:#eef0f6;
        width:24px; height:24px; border-radius:50%; color:#6b7280; cursor:pointer; line-height:1; font-size:1rem;
        display:none; place-items:center; }
    .bku-search__clear:hover{ background:#e0e3ec; color:#374151; }
    .bku-search__clear:focus-visible{ outline:2px solid var(--bku-accent); outline-offset:2px; }
    .bku-field{ display:flex; flex-direction:column; }
    .bku-field label{ font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.05em; color:#8a90a2; margin-bottom:4px; }
    .bku-field input, .bku-field select{ border:1.5px solid #e4e7ef; border-radius:12px; padding:11px 12px; font-size:.88rem; outline:none;
        background:#fff; transition:border-color .2s, box-shadow .2s; }
    .bku-field input:focus, .bku-field select:focus{ border-color:var(--bku-accent); box-shadow:0 0 0 4px rgba(99,102,241,.14); }

    .bku-stats{ display:grid; grid-template-columns:repeat(auto-fit,minmax(210px,1fr)); gap:14px; margin-bottom:18px; }
    .bku-stat{ display:flex; align-items:center; gap:14px; background:#fff; border-radius:16px; padding:18px;
        border:1px solid #eef0f5; box-shadow:0 10px 26px -18px rgba(15,23,42,.4); animation:bkuFadeUp .5s both;
        animation-delay:calc(var(--i)*80ms); transition:transform .18s ease, box-shadow .18s ease; }
    .bku-stat:hover{ transform:translateY(-4px); box-shadow:0 18px 34px -18px rgba(15,23,42,.45); }
    .bku-stat__icon{ width:48px; height:48px; border-radius:14px; display:grid; place-items:center; font-size:1.35rem; color:#fff; flex:none; }
    .bku-stat__body{ display:flex; flex-direction:column; min-width:0; }
    .bku-stat__label{ font-size:.78rem; font-weight:600; color:#8a90a2; }
    .bku-stat__value{ font-size:1.2rem; font-weight:800; color:#1f2535; letter-spacing:-.01em; white-space:nowrap; }
    .bku-stat__hint{ font-size:.72rem; color:#9aa1b2; margin-top:3px; white-space:normal; }
    .bku-stat--slate .bku-stat__icon{ background:linear-gradient(135deg,#64748b,#475569); }
    .bku-stat--green .bku-stat__icon{ background:linear-gradient(135deg,#10b981,#059669); }
    .bku-stat--green .bku-stat__value{ color:#059669; }
    .bku-stat--red .bku-stat__icon{ background:linear-gradient(135deg,#f87171,#ef4444); }
    .bku-stat--red .bku-stat__value{ color:#dc2626; }
    .bku-stat--indigo .bku-stat__icon{ background:linear-gradient(135deg,#6366f1,#8b5cf6); }
    .bku-stat--indigo .bku-stat__value{ color:#4f46e5; }
    .bku-stat--amber .bku-stat__icon{ background:linear-gradient(135deg,#fbbf24,#f59e0b); }
    .bku-stat--amber .bku-stat__value{ color:#d97706; }
    .bku-stat--cyan .bku-stat__icon{ background:linear-gradient(135deg,#22d3ee,#06b6d4); }
    .bku-stat--cyan .bku-stat__value{ color:#0891b2; }

    .bku-card{ background:#fff; border-radius:18px; border:1px solid #eef0f5; overflow:hidden;
        box-shadow:0 14px 36px -22px rgba(15,23,42,.4); animation:bkuFadeUp .5s .1s both; }
    .bku-card__head{ display:flex; align-items:center; justify-content:space-between; gap:12px; padding:16px 20px; border-bottom:1px solid #f0f1f6; }
    .bku-card__title{ font-weight:800; color:#1f2535; display:inline-flex; align-items:center; gap:8px; }
    .bku-card__title i{ color:var(--bku-accent); }
    .bku-chip-count{ font-size:.78rem; font-weight:700; color:#6b7280; background:#f1f2f7; padding:5px 12px; border-radius:999px; }

    .bku-table-wrap{ overflow-x:auto; }
    .bku-table{ width:100%; border-collapse:separate; border-spacing:0; }
    .bku-table thead th{ text-align:left; font-size:.72rem; font-weight:700; text-transform:uppercase; letter-spacing:.06em;
        color:#8a90a2; padding:12px 16px; background:#fafbfc; border-bottom:1px solid #eef0f5; white-space:nowrap; }
    .bku-table th.ta-end{ text-align:right; } .bku-table th.ta-center{ text-align:center; }
    .bku-sort{ display:inline-flex; align-items:center; gap:6px; padding:0; border:0; background:none; cursor:pointer;
        font:inherit; color:inherit; text-transform:inherit; letter-spacing:inherit; line-height:inherit; }
    .bku-table th.ta-end .bku-sort{ flex-direction:row-reverse; }
    .bku-sort i{ font-size:.82em; opacity:.45; transition:opacity .15s, color .15s; }
    .bku-sort:hover{ color:var(--bku-accent); } .bku-sort:hover i{ opacity:.8; }
    .bku-sort.is-active{ color:var(--bku-accent); } .bku-sort.is-active i{ opacity:1; }
    .bku-sort:focus-visible{ outline:2px solid var(--bku-accent); outline-offset:3px; border-radius:4px; }
    .bku-table td{ padding:11px 16px; border-bottom:1px solid #f3f4f8; vertical-align:middle; }
    .bku-table td.ta-end{ text-align:right; } .bku-table td.ta-center{ text-align:center; }
    .bku-table tbody tr:last-child td{ border-bottom:0; }
    .bku-table tfoot td{ padding:12px 16px; border-top:2px solid #eef0f5; font-weight:800; color:#1f2535; background:#fafbfc; }
    .bku-row{ animation:bkuFadeUp .4s both; animation-delay:calc(min(var(--r),22)*18ms); }
    .bku-row:hover td{ background:#f7f8ff; }
    .bku-row-saldoawal td{ background:linear-gradient(90deg,#eef2ff,#faf5ff); font-style:italic; color:#5b6172; font-weight:600; }
    .bku-row-saldoawal i{ color:#a78bfa; margin-right:4px; }

    .bku-date{ display:inline-flex; flex-direction:column; line-height:1.12; }
    .bku-date__d{ font-size:1.05rem; font-weight:800; color:#1f2535; }
    .bku-date__m{ font-size:.7rem; font-weight:600; color:#9aa1b2; text-transform:uppercase; letter-spacing:.04em; }
    .bku-pill{ display:inline-flex; align-items:center; gap:6px; max-width:300px; font-size:.8rem; font-weight:600; }
    .bku-pill--akun{ color:#3730a3; }
    .bku-pill__code{ background:#e0e7ff; color:#4338ca; font-weight:800; font-size:.72rem; padding:2px 7px; border-radius:6px; font-variant-numeric:tabular-nums; }
    .bku-pill--jenis{ background:#f1f5f9; color:#475569; border:1px solid #e2e8f0; padding:4px 10px; border-radius:999px; }
    .bku-muted{ color:#c2c7d4; }
    .bku-uraian{ color:#3a4051; font-size:.88rem; }
    .bku-tag{ display:inline-flex; align-items:center; gap:4px; font-size:.68rem; font-weight:700; padding:3px 8px; border-radius:999px; margin-left:6px; white-space:nowrap; }
    .bku-tag--ok{ background:#dcfce7; color:#15803d; } .bku-tag--warn{ background:#fef3c7; color:#b45309; } .bku-tag--info{ background:#e0f2fe; color:#0369a1; }
    .bku-num{ font-variant-numeric:tabular-nums; font-weight:700; font-size:.9rem; white-space:nowrap; }
    .bku-num--in{ color:#059669; } .bku-num--out{ color:#dc2626; } .bku-num--saldo{ color:#1f2535; }
    .bku-num--in::before, .bku-num--out::before{ content:"Rp "; opacity:.5; font-weight:600; font-size:.78em; }
    .bku-num--in:empty::before, .bku-num--out:empty::before{ content:""; }
    .bku-actions{ display:inline-flex; gap:6px; justify-content:center; }
    .bku-actions form{ display:inline; margin:0; }
    .bku-ico{ display:inline-grid; place-items:center; width:30px; height:30px; border-radius:9px; border:1px solid #e4e7ef;
        background:#fff; color:#5b6172; cursor:pointer; transition:all .15s ease; text-decoration:none; }
    .bku-ico:hover{ background:var(--bku-accent); color:#fff; border-color:var(--bku-accent); transform:translateY(-1px); }
    .bku-ico--danger:hover{ background:#ef4444; border-color:#ef4444; }
    .bku-empty{ text-align:center; padding:46px 16px; color:#9aa1b2; }
    .bku-empty i{ font-size:2.4rem; opacity:.4; } .bku-empty p{ margin:12px 0 0; font-weight:600; }
    .bku-empty span{ display:block; font-size:.83rem; font-weight:500; margin-top:4px; color:#aab0bd; }
    .bku-link-mini{ font-size:.72rem; font-weight:700; text-decoration:none; color:var(--bku-accent); margin-left:8px; }
    #bku-content{ transition:opacity .2s; } #bku-content.is-loading{ opacity:.45; pointer-events:none; }

    .bku-modal{ border:0; border-radius:18px; overflow:hidden; box-shadow:0 30px 60px -20px rgba(15,23,42,.5); }
    .bku-modal__head{ background:linear-gradient(135deg,#4f46e5,#7c3aed); color:#fff; border:0; padding:20px 22px; align-items:flex-start; }
    .bku-modal__head .modal-title{ font-weight:800; }
    .bku-modal__sub{ margin:.3rem 0 0; font-size:.78rem; opacity:.92; }
    .bku-modal .modal-body{ padding:22px; }
    .bku-flabel{ font-size:.74rem; font-weight:700; text-transform:uppercase; letter-spacing:.04em; color:#7a808f; margin-bottom:5px; display:block; }
    .bku-modal .form-control, .bku-modal .form-select{ border:1.5px solid #e4e7ef; border-radius:11px; padding:10px 12px; font-size:.9rem; }
    .bku-modal .form-control:focus, .bku-modal .form-select:focus{ border-color:var(--bku-accent); box-shadow:0 0 0 4px rgba(99,102,241,.14); }
    .bku-modal__foot{ border:0; padding:0 22px 22px; }
    .bku-btn-primary{ background:linear-gradient(135deg,#4f46e5,#7c3aed); color:#fff; font-weight:700; border:0; padding:10px 20px; border-radius:11px; }
    .bku-btn-primary:hover{ color:#fff; filter:brightness(1.08); }

    @keyframes bkuFadeUp{ from{ opacity:0; transform:translateY(12px); } to{ opacity:1; transform:none; } }
    @media (max-width:640px){ .bku-hero{ padding:20px; } .bku-hero__title{ font-size:1.3rem; } }
    @media (prefers-reduced-motion:reduce){ .bku-hero,.bku-stat,.bku-card,.bku-row{ animation:none !important; } }

    /* ════════════════════════════════════════════════════════════════
       Selaraskan komponen lama buku pembantu (book-*) ke desain BKU,
       tanpa mengubah markup tiap halaman.
       ════════════════════════════════════════════════════════════════ */
    .book-hero{ position:relative; overflow:hidden; border:0; border-radius:20px; padding:26px 28px; color:#fff;
        background:linear-gradient(135deg,#4f46e5 0%,#7c3aed 55%,#4f46e5 100%);
        box-shadow:0 18px 40px -18px rgba(79,70,229,.6); margin-bottom:18px; animation:bkuFadeUp .5s both; }
    .book-hero::after{ content:""; position:absolute; right:-60px; top:-70px; width:260px; height:260px;
        background:radial-gradient(circle,rgba(255,255,255,.18),transparent 70%); pointer-events:none; }
    .book-hero > *{ position:relative; z-index:1; }
    .book-hero h4{ color:#fff; font-weight:800; }
    .book-hero .text-dark{ color:#fff !important; }
    .book-hero .text-muted{ color:rgba(255,255,255,.85) !important; }
    .book-hero strong{ color:#fff; }
    .book-hero .badge.bg-light{ background:rgba(255,255,255,.18) !important; color:#fff !important; border-color:transparent !important; }
    .book-hero .btn{ border-radius:12px; font-weight:700; }
    .book-hero .btn-outline-danger, .book-hero .btn-outline-secondary, .book-hero .btn-outline-primary{
        background:rgba(255,255,255,.16); color:#fff; border-color:transparent; }
    .book-hero .btn-outline-danger:hover, .book-hero .btn-outline-secondary:hover, .book-hero .btn-outline-primary:hover{
        background:rgba(255,255,255,.30); color:#fff; border-color:transparent; }
    .book-hero .btn-success, .book-hero .btn-primary{ background:#fff; color:#4f46e5; border:0; }
    .book-hero .btn-success:hover, .book-hero .btn-primary:hover{ background:#fff; color:#4338ca; }

    .book-filter{ background:#fff; border:1px solid #eef0f5; border-radius:16px; padding:16px 18px;
        box-shadow:0 10px 26px -18px rgba(15,23,42,.4); margin-bottom:18px; }
    .book-filter .form-label{ font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.05em; color:#8a90a2; }
    .book-filter .form-control, .book-filter .form-select{ border:1.5px solid #e4e7ef; border-radius:12px; font-size:.88rem; }
    .book-filter .form-control:focus, .book-filter .form-select:focus{ border-color:var(--bku-accent); box-shadow:0 0 0 4px rgba(99,102,241,.14); }
    .book-filter .input-group-text{ border:1.5px solid #e4e7ef; border-right:0; border-radius:12px 0 0 12px; background:#f8fafc; color:#9aa1b2; }
    .book-filter .input-group .form-control{ border-top-left-radius:0; border-bottom-left-radius:0; }
    .book-filter .btn-primary{ background:linear-gradient(135deg,#4f46e5,#7c3aed); border:0; border-radius:11px; font-weight:700; }
    .book-filter .btn-outline-secondary{ border-radius:11px; font-weight:700; }

    .card.book-card{ border:1px solid #eef0f5; border-radius:18px; box-shadow:0 14px 36px -22px rgba(15,23,42,.4); overflow:hidden; }
    .card.book-card > .card-header{ background:#fff; border-bottom:1px solid #f0f1f6; padding:15px 20px; }
    .card.book-card > .card-header h6{ color:#1f2535; font-weight:800; }
    .book-table thead th{ background:#fafbfc !important; color:#8a90a2; border-bottom:1px solid #eef0f5; padding:12px 16px; }
    .book-table td{ border-color:#f3f4f8; padding:11px 16px; }
    .book-table.table-hover tbody tr:hover > *{ background:#f7f8ff; }

    .book-summary{ border:1px solid #eef0f5; border-radius:16px; box-shadow:0 10px 26px -18px rgba(15,23,42,.4); }
</style>
@endpush
