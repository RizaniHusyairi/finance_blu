{{-- Gaya bersama form Tambah/Edit DIPA (df-*) — dipakai create & edit. --}}
<style>
/* ============================================================
   FORM TAMBAH DIPA — hero aurora · step cards · live preview
   ============================================================ */
.dipa-form { --df-indigo:#4f46e5; --df-violet:#8b5cf6; --df-emerald:#10b981; --df-ink:#0f172a;
    --df-muted:#64748b; --df-border:#e8ecf5; --df-radius:1.15rem;
    --df-shadow:0 18px 40px -22px rgba(30,27,75,.28);
    --df-shadow-hover:0 28px 56px -24px rgba(79,70,229,.4); }

@keyframes dfAurora { 0%{background-position:0% 50%} 50%{background-position:100% 50%} 100%{background-position:0% 50%} }
@keyframes dfFloat  { 0%,100%{transform:translateY(0)} 50%{transform:translateY(-11px)} }
@keyframes dfRise   { from{opacity:0; transform:translateY(18px)} to{opacity:1; transform:none} }
@keyframes dfSheen  { 0%,55%{left:-70%} 85%,100%{left:140%} }
@keyframes dfPulse  { 0%,100%{box-shadow:0 0 0 0 rgba(79,70,229,.4)} 50%{box-shadow:0 0 0 9px rgba(79,70,229,0)} }
@keyframes dfBlink  { 0%,100%{opacity:1} 50%{opacity:.35} }
@media (prefers-reduced-motion: reduce) {
    .dipa-form * { animation-duration:.001s !important; animation-iteration-count:1 !important; transition-duration:.001s !important; }
}

/* ---------- HERO ---------- */
.df-hero { position:relative; overflow:hidden; border-radius:1.4rem; padding:1.6rem 1.9rem;
    margin-bottom:1.4rem; color:#fff;
    background:linear-gradient(125deg,#0b1020,#1e1b4b 30%,#4338ca 62%,#7c3aed 82%,#0e7490);
    background-size:340% 340%; animation:dfAurora 18s ease infinite;
    box-shadow:0 26px 52px -24px rgba(49,46,129,.6); }
.df-hero::before { content:''; position:absolute; width:320px; height:320px; top:-55%; right:-4%;
    border-radius:50%; background:radial-gradient(circle, rgba(255,255,255,.15) 0%, transparent 70%);
    animation:dfFloat 10s ease-in-out infinite; pointer-events:none; }
.df-hero .mesh { position:absolute; inset:0; opacity:.14; pointer-events:none;
    background-image:linear-gradient(rgba(255,255,255,.4) 1px, transparent 1px),
                     linear-gradient(90deg, rgba(255,255,255,.4) 1px, transparent 1px);
    background-size:42px 42px; mask-image:radial-gradient(ellipse at 25% 0%, #000 5%, transparent 62%); }
.df-hero h4 { font-weight:800; letter-spacing:-.4px; margin:0; color:#fff !important; }
.df-hero .sub { color:#fff; opacity:.78; font-weight:600; font-size:.86rem; margin-top:.25rem; }
.df-chip { display:inline-flex; align-items:center; gap:.4rem; background:rgba(255,255,255,.13);
    border:1px solid rgba(255,255,255,.25); backdrop-filter:blur(8px); padding:.3rem .85rem;
    border-radius:999px; font-weight:700; font-size:.7rem; letter-spacing:.4px; color:#fff; }
.df-btn-back { display:inline-flex; align-items:center; gap:.45rem; border-radius:999px; font-weight:700;
    font-size:.82rem; padding:.5rem 1.2rem; border:1px solid rgba(255,255,255,.35); color:#fff;
    background:rgba(255,255,255,.12); backdrop-filter:blur(8px); text-decoration:none;
    transition:transform .2s ease, background .2s ease; }
.df-btn-back:hover { color:#fff; background:rgba(255,255,255,.24); transform:translateY(-2px); }

/* ---------- KARTU STEP ---------- */
.df-card { background:#fff; border:1px solid var(--df-border); border-radius:var(--df-radius);
    box-shadow:var(--df-shadow); overflow:hidden; animation:dfRise .55s ease both;
    animation-delay:var(--d, 0s); transition:box-shadow .3s ease, border-color .3s ease; }
.df-card:focus-within { border-color:#c7d2fe; box-shadow:var(--df-shadow-hover); }
.df-card-head { display:flex; align-items:center; gap:.9rem; padding:1.15rem 1.4rem .9rem; }
.df-step { width:44px; height:44px; border-radius:13px; flex-shrink:0; display:grid; place-items:center;
    color:#fff; font-weight:800; font-size:.95rem; letter-spacing:.02em;
    background:linear-gradient(135deg, var(--t,#4f46e5), var(--t2,#818cf8));
    box-shadow:0 10px 22px -8px var(--t,#4f46e5); }
.df-card-title { font-weight:800; margin:0; letter-spacing:-.01em; color:var(--df-ink); }
.df-card-sub { font-size:.76rem; color:var(--df-muted); font-weight:600; }

/* ---------- INPUT ---------- */
.dipa-form .form-label { font-size:.72rem; font-weight:800; letter-spacing:.06em; text-transform:uppercase;
    color:var(--df-muted); margin-bottom:.35rem; }
.dipa-form .form-control, .dipa-form .form-select { border-radius:.75rem; border-color:var(--df-border);
    transition:border-color .2s ease, box-shadow .2s ease, transform .2s ease; }
.dipa-form .form-control:focus, .dipa-form .form-select:focus { border-color:#a5b4fc;
    box-shadow:0 0 0 .22rem rgba(99,102,241,.12); }
.dipa-form .input-group-text { border-radius:.75rem 0 0 .75rem; border-color:var(--df-border);
    background:#f8faff; color:#7c8db5; }
.dipa-form .input-group .form-control { border-radius:0 .75rem .75rem 0; }
.dipa-form .form-control[readonly] { background:repeating-linear-gradient(-45deg,#f8fafc 0 10px,#f1f5f9 10px 20px);
    color:#64748b; font-weight:700; }

/* ---------- DROPZONE ---------- */
.df-drop { position:relative; border:2px dashed #c7d2fe; border-radius:1rem; padding:1.2rem;
    background:linear-gradient(180deg,#f8faff,#eef2ff55); text-align:center; cursor:pointer;
    transition:border-color .25s ease, background .25s ease, transform .25s ease; }
.df-drop:hover, .df-drop.dragover { border-color:var(--df-indigo); transform:translateY(-2px);
    background:linear-gradient(180deg,#f4f6ff,#e0e7ff66); }
.df-drop input[type=file] { position:absolute; inset:0; opacity:0; cursor:pointer; }
.df-drop .df-drop-ic { width:46px; height:46px; margin:0 auto .5rem; border-radius:14px; display:grid;
    place-items:center; font-size:1.25rem; color:#4f46e5; background:#eef2ff;
    transition:transform .25s cubic-bezier(.34,1.56,.64,1); }
.df-drop:hover .df-drop-ic { transform:scale(1.12) rotate(-6deg); }
.df-drop.has-file { border-style:solid; border-color:#a7f3d0; background:linear-gradient(180deg,#f0fdf4,#ecfdf5); }
.df-drop.has-file .df-drop-ic { color:#059669; background:#d1fae5; }

/* ---------- PRATINJAU LIVE ---------- */
.df-preview { position:sticky; top:88px; animation:dfRise .55s .18s ease both; }
.df-doc { position:relative; overflow:hidden; border-radius:1.1rem; color:#fff;
    background:linear-gradient(140deg,#1e1b4b,#4338ca 55%,#6d28d9);
    box-shadow:0 22px 44px -20px rgba(49,46,129,.65); padding:1.4rem 1.5rem; }
.df-doc::after { content:''; position:absolute; top:0; bottom:0; width:40%; left:-60%;
    background:linear-gradient(100deg, transparent, rgba(255,255,255,.14), transparent);
    transform:skewX(-18deg); animation:dfSheen 4.5s ease-in-out 1s infinite; }
.df-doc .df-doc-lbl { font-size:.62rem; font-weight:800; letter-spacing:.14em; text-transform:uppercase;
    color:rgba(255,255,255,.65); }
.df-doc .df-doc-val { font-weight:800; font-size:.95rem; overflow-wrap:anywhere; min-height:1.3em; }
.df-doc .df-doc-val.mono { font-family:SFMono-Regular,Menlo,Consolas,monospace; letter-spacing:-.01em; }
.df-doc .df-cursor { display:inline-block; width:2px; height:1em; background:#a5b4fc; vertical-align:-2px;
    animation:dfBlink 1.1s step-end infinite; }
.df-doc .df-pagu { font-size:1.35rem; font-weight:800; font-variant-numeric:tabular-nums; letter-spacing:-.02em; }
.df-doc-badge { display:inline-flex; align-items:center; gap:.35rem; font-size:.66rem; font-weight:800;
    padding:.28rem .7rem; border-radius:999px; letter-spacing:.05em;
    background:rgba(52,211,153,.22); border:1px solid rgba(110,231,183,.5); color:#a7f3d0; }
.df-doc-badge.off { background:rgba(148,163,184,.25); border-color:rgba(203,213,225,.4); color:#e2e8f0; }
.df-doc-sep { border-top:1px dashed rgba(255,255,255,.25); margin:.9rem 0; }
.df-hint { font-size:.74rem; color:var(--df-muted); font-weight:600; }

/* ---------- TOMBOL SUBMIT ---------- */
.df-actions { display:flex; justify-content:flex-end; flex-wrap:wrap; gap:.6rem; }
.df-btn { position:relative; overflow:hidden; display:inline-flex; align-items:center; gap:.5rem;
    border-radius:999px; font-weight:800; font-size:.86rem; padding:.62rem 1.5rem; border:0;
    transition:transform .22s ease, box-shadow .22s ease; }
.df-btn:hover:not(:disabled) { transform:translateY(-3px); }
.df-btn:active:not(:disabled) { transform:scale(.97); }
.df-btn-ghost { background:#fff; color:var(--df-muted); border:1px solid var(--df-border); }
.df-btn-ghost:hover { color:var(--df-indigo); border-color:#c7d2fe; background:#eef2ff; }
.df-btn-primary { color:#fff; background:linear-gradient(135deg,#4f46e5,#7c3aed);
    box-shadow:0 12px 26px -10px rgba(79,70,229,.7); }
.df-btn-primary:hover { color:#fff; box-shadow:0 18px 34px -12px rgba(79,70,229,.8); }
.df-btn-success { color:#fff; background:linear-gradient(135deg,#059669,#10b981);
    box-shadow:0 12px 26px -10px rgba(16,185,129,.7); }
.df-btn-success:hover { color:#fff; box-shadow:0 18px 34px -12px rgba(16,185,129,.8); }
.df-btn-primary::after, .df-btn-success::after { content:''; position:absolute; top:0; bottom:0;
    width:34%; left:-50%; background:linear-gradient(100deg, transparent, rgba(255,255,255,.45), transparent);
    transform:skewX(-18deg); animation:dfSheen 3.2s ease-in-out infinite; }
.df-btn:disabled { opacity:.85; pointer-events:none; }
</style>
