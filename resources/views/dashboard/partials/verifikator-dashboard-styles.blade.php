@push('css')
<style>
    .vdash {
        --v-indigo:#6366f1; --v-violet:#8b5cf6; --v-emerald:#10b981;
        --v-rose:#f43f5e; --v-amber:#f59e0b; --v-sky:#0ea5e9;
        --v-ink:#0f172a; --v-muted:#64748b;
    }

    /* ===== entrance animations ===== */
    @keyframes vUp   { from{opacity:0;transform:translateY(22px);} to{opacity:1;transform:translateY(0);} }
    @keyframes vIn   { from{opacity:0;transform:scale(.96);} to{opacity:1;transform:scale(1);} }
    @keyframes vPop  { from{opacity:0;transform:translateY(14px) scale(.97);} to{opacity:1;transform:translateY(0) scale(1);} }
    @keyframes vFloat{ 0%,100%{transform:translateY(0);} 50%{transform:translateY(-12px);} }
    @keyframes vCount{ from{opacity:0;transform:translateY(8px);} to{opacity:1;transform:translateY(0);} }
    @keyframes vShine{ 0%{background-position:0% 50%;} 50%{background-position:100% 50%;} 100%{background-position:0% 50%;} }
    @keyframes vRing { to{ transform:rotate(360deg);} }

    .v-anim { opacity:0; animation:vUp .6s cubic-bezier(.22,1,.36,1) forwards; }
    .v-d1{animation-delay:.05s;} .v-d2{animation-delay:.12s;} .v-d3{animation-delay:.19s;}
    .v-d4{animation-delay:.26s;} .v-d5{animation-delay:.33s;} .v-d6{animation-delay:.40s;}

    /* ===== Hero ===== */
    .v-hero {
        position:relative; overflow:hidden;
        border-radius:1.6rem; padding:2rem 2.2rem; color:#fff;
        background-size:220% 220%;
        animation: vIn .6s cubic-bezier(.22,1,.36,1) both, vShine 13s ease infinite;
        box-shadow:0 24px 55px -24px rgba(79,70,229,.85);
    }
    .v-hero::before, .v-hero::after { content:""; position:absolute; border-radius:50%; pointer-events:none; }
    .v-hero::before { width:340px;height:340px; right:-90px; top:-130px; background:radial-gradient(circle,rgba(255,255,255,.22),transparent 65%); animation:vFloat 8s ease-in-out infinite; }
    .v-hero::after  { width:230px;height:230px; left:-70px; bottom:-110px; background:radial-gradient(circle,rgba(255,255,255,.14),transparent 70%); animation:vFloat 10s ease-in-out infinite 1s; }
    .v-hero-z { position:relative; z-index:2; }
    .v-hero h3 { color:#fff; font-weight:800; letter-spacing:-.02em; text-shadow:0 2px 10px rgba(0,0,0,.18); }
    .v-hero p  { color:rgba(255,255,255,.9); margin:0; }
    .v-hero .v-avatar {
        width:64px;height:64px;border-radius:1.2rem;display:grid;place-items:center;
        font-size:1.7rem;background:rgba(255,255,255,.2);border:1px solid rgba(255,255,255,.28);
        backdrop-filter:blur(6px); animation:vFloat 5s ease-in-out infinite;
    }
    .v-hero .v-chip {
        display:inline-flex;align-items:center;gap:.45rem;
        background:rgba(255,255,255,.2);border:1px solid rgba(255,255,255,.3);
        border-radius:999px;padding:.35rem .9rem;font-size:.8rem;font-weight:700;
    }
    .v-hero .v-bigtask { font-size:2.6rem; font-weight:800; line-height:1; animation:vCount .9s ease both; }

    /* ===== KPI cards ===== */
    .v-kpis { display:grid; grid-template-columns:repeat(auto-fit,minmax(210px,1fr)); gap:1rem; margin:1.4rem 0; }
    .v-kpi {
        position:relative; overflow:hidden; background:#fff;
        border:1px solid rgba(15,23,42,.06); border-radius:1.2rem; padding:1.2rem 1.3rem;
        box-shadow:0 12px 32px -26px rgba(15,23,42,.6);
        transition:transform .25s cubic-bezier(.22,1,.36,1), box-shadow .25s ease;
    }
    .v-kpi::before { content:""; position:absolute; left:0;top:0;bottom:0;width:5px; background:var(--v-indigo); transform:scaleY(0); transform-origin:top; transition:transform .3s ease; }
    .v-kpi:hover { transform:translateY(-6px); box-shadow:0 20px 40px -22px rgba(15,23,42,.5); }
    .v-kpi:hover::before { transform:scaleY(1); }
    .v-kpi .v-ic { width:50px;height:50px;border-radius:.95rem;display:grid;place-items:center;font-size:1.45rem; transition:transform .3s ease; }
    .v-kpi:hover .v-ic { transform:rotate(-8deg) scale(1.08); }
    .v-kpi .v-lbl { font-size:.74rem;text-transform:uppercase;letter-spacing:.05em;font-weight:700;color:var(--v-muted); }
    .v-kpi .v-val { font-size:1.9rem;font-weight:800;color:var(--v-ink);line-height:1.1; animation:vCount .8s ease both; }
    .v-kpi .v-sub { font-size:.78rem;color:var(--v-muted); }

    .t-indigo{color:var(--v-indigo);} .t-violet{color:var(--v-violet);} .t-emerald{color:var(--v-emerald);}
    .t-rose{color:var(--v-rose);} .t-amber{color:var(--v-amber);} .t-sky{color:var(--v-sky);}
    .bg-indigo{background:rgba(99,102,241,.12);color:var(--v-indigo);} .bar-indigo::before{background:var(--v-indigo);}
    .bg-violet{background:rgba(139,92,246,.12);color:var(--v-violet);}
    .bg-emerald{background:rgba(16,185,129,.12);color:var(--v-emerald);}
    .bg-rose{background:rgba(244,63,94,.12);color:var(--v-rose);}
    .bg-amber{background:rgba(245,158,11,.12);color:var(--v-amber);}
    .bg-sky{background:rgba(14,165,233,.12);color:var(--v-sky);}
    .v-kpi.k-indigo::before{background:var(--v-indigo);} .v-kpi.k-violet::before{background:var(--v-violet);}
    .v-kpi.k-emerald::before{background:var(--v-emerald);} .v-kpi.k-rose::before{background:var(--v-rose);}
    .v-kpi.k-amber::before{background:var(--v-amber);} .v-kpi.k-sky::before{background:var(--v-sky);}

    /* ===== Cards ===== */
    .v-card {
        background:#fff; border:1px solid rgba(15,23,42,.07); border-radius:1.3rem;
        box-shadow:0 14px 38px -30px rgba(15,23,42,.6); overflow:hidden;
        transition:box-shadow .3s ease;
    }
    .v-card:hover { box-shadow:0 20px 46px -28px rgba(15,23,42,.45); }
    .v-card-head { display:flex;align-items:center;gap:.6rem; padding:1.1rem 1.3rem;
        background:linear-gradient(180deg,#fbfdff,#f4f7fc); border-bottom:1px solid rgba(15,23,42,.06); }
    .v-card-head .hic { width:36px;height:36px;border-radius:.7rem;display:grid;place-items:center;background:var(--v-indigo);color:#fff;font-size:1.05rem; }
    .v-card-head h6 { margin:0;font-weight:800;color:var(--v-ink); }

    /* ===== Task queue ===== */
    .v-task {
        display:flex;align-items:center;gap:.9rem; padding:.85rem 1rem; border-radius:1rem;
        border:1px solid #eef2f7; background:#f8fafc; margin-bottom:.7rem;
        transition:all .25s cubic-bezier(.22,1,.36,1); animation:vPop .5s ease both;
    }
    .v-task:hover { background:#fff; border-color:rgba(99,102,241,.25); transform:translateX(4px); box-shadow:0 12px 26px -18px rgba(99,102,241,.7); }
    .v-task .tk-ic { width:42px;height:42px;border-radius:.8rem;display:grid;place-items:center;font-size:1.2rem;flex-shrink:0; }
    .v-task .tk-no { font-weight:700;color:var(--v-ink); }
    .v-task .tk-meta { font-size:.78rem;color:var(--v-muted); }
    .v-task .tk-badge { font-size:.66rem;font-weight:800;text-transform:uppercase;letter-spacing:.05em;
        padding:.2rem .6rem;border-radius:999px; }

    .tone-indigo{background:rgba(99,102,241,.12);color:var(--v-indigo);}
    .tone-violet{background:rgba(139,92,246,.12);color:var(--v-violet);}
    .tone-emerald{background:rgba(16,185,129,.12);color:var(--v-emerald);}
    .tone-amber{background:rgba(245,158,11,.12);color:var(--v-amber);}
    .tone-rose{background:rgba(244,63,94,.12);color:var(--v-rose);}
    .tone-sky{background:rgba(14,165,233,.12);color:var(--v-sky);}

    /* ===== Quick links ===== */
    .v-quick {
        display:flex;align-items:center;gap:.8rem; padding:1rem 1.1rem; border-radius:1rem;
        border:1px solid #eef2f7; background:#fff; text-decoration:none; color:inherit;
        transition:all .25s cubic-bezier(.22,1,.36,1); height:100%;
    }
    .v-quick:hover { transform:translateY(-4px); border-color:rgba(99,102,241,.3); box-shadow:0 16px 32px -22px rgba(99,102,241,.7); color:inherit; }
    .v-quick .q-ic { width:46px;height:46px;border-radius:.85rem;display:grid;place-items:center;font-size:1.3rem;flex-shrink:0; }
    .v-quick .q-title { font-weight:700;color:var(--v-ink); }
    .v-quick .q-sub { font-size:.76rem;color:var(--v-muted); }
    .v-quick .q-arrow { margin-left:auto;color:#cbd5e1;transition:transform .25s ease; }
    .v-quick:hover .q-arrow { transform:translateX(4px);color:var(--v-indigo); }

    .v-empty { text-align:center;color:var(--v-muted);padding:2.5rem 1rem; }
    .v-empty i { font-size:2.6rem;opacity:.3;display:block;margin-bottom:.6rem; }

    /* chart containers — kunci tinggi supaya canvas tidak melar */
    .v-chart-wrap { position:relative; height:240px; width:100%; }
    .v-chart-wrap canvas { max-height:240px; }
    .v-chart-center {
        position:absolute; top:46%; left:50%; transform:translate(-50%,-50%);
        text-align:center; pointer-events:none;
    }
    .v-chart-total { font-size:1.9rem; font-weight:800; color:var(--v-ink); line-height:1; }
    .v-chart-cap { font-size:.72rem; text-transform:uppercase; letter-spacing:.06em; font-weight:700; color:var(--v-muted); margin-top:.15rem; }

    /* progress ring container */
    .v-ringwrap { position:relative; width:160px; height:160px; margin:0 auto; }
</style>
@endpush
