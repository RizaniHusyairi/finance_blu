{{-- Style + animasi reusable untuk modul Administrasi.
     Include via @include('admin._partials.styles') di @push('css'). --}}
<style>
    /* === Hero header === */
    .admin-hero {
        position: relative;
        overflow: hidden;
        border-radius: 1.25rem;
        padding: 1.5rem 1.75rem;
        color: #fff;
        background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 50%, #ec4899 100%);
        box-shadow: 0 18px 40px -20px rgba(79, 70, 229, 0.55);
        animation: adminFadeIn 0.6s ease both;
    }
    .admin-hero::before {
        content: "";
        position: absolute;
        inset: -40% -10% auto auto;
        width: 320px;
        height: 320px;
        background: radial-gradient(closest-side, rgba(255,255,255,.25), transparent);
        animation: adminFloat 8s ease-in-out infinite;
        pointer-events: none;
    }
    .admin-hero h1 {
        font-size: 1.55rem;
        font-weight: 700;
        margin: 0 0 .35rem;
        letter-spacing: -.02em;
    }
    .admin-hero p {
        margin: 0;
        opacity: .92;
        font-size: .95rem;
        max-width: 64ch;
    }
    .admin-hero .hero-icon {
        width: 56px;
        height: 56px;
        border-radius: 16px;
        background: rgba(255,255,255,.18);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        backdrop-filter: blur(6px);
    }
    .admin-hero .hero-icon i { font-size: 28px; }

    /* === Stat cards === */
    .stat-card {
        position: relative;
        border: 0;
        border-radius: 1rem;
        background: #fff;
        box-shadow: 0 6px 22px -16px rgba(15, 23, 42, .35);
        overflow: hidden;
        transition: transform .25s ease, box-shadow .25s ease;
        animation: adminRise .55s ease both;
    }
    .stat-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 18px 36px -22px rgba(79, 70, 229, .45);
    }
    .stat-card .stat-bar {
        position: absolute;
        inset: 0 auto 0 0;
        width: 5px;
        background: linear-gradient(180deg, var(--c1, #4f46e5), var(--c2, #7c3aed));
    }
    .stat-card .stat-icon {
        width: 42px;
        height: 42px;
        border-radius: 12px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: var(--c-bg, rgba(79, 70, 229, .12));
        color: var(--c-fg, #4f46e5);
    }
    .stat-card h6 { font-size: .82rem; color: #64748b; margin: 0; font-weight: 500; }
    .stat-card .stat-value { font-size: 1.6rem; font-weight: 700; letter-spacing: -.02em; color: #0f172a; }

    /* === Cards & tables === */
    .surface-card {
        border: 0;
        border-radius: 1rem;
        background: #fff;
        box-shadow: 0 6px 22px -16px rgba(15, 23, 42, .25);
        overflow: hidden;
        animation: adminFadeIn .5s ease both;
    }
    .surface-card .card-header {
        background: transparent;
        border: 0;
        padding: 1.1rem 1.4rem .25rem;
    }
    .surface-card .table thead th {
        text-transform: uppercase;
        letter-spacing: .04em;
        font-size: .72rem;
        color: #475569;
        background: #f8fafc;
        border: 0;
    }
    .surface-card .table > :not(caption) > * > th,
    .surface-card .table > :not(caption) > * > td {
        border-color: #f1f5f9;
    }
    .surface-card .table tbody tr {
        transition: background .2s ease;
    }
    .surface-card .table tbody tr:hover {
        background: linear-gradient(90deg, rgba(79,70,229,.05), rgba(236,72,153,.02));
    }

    /* === Avatar / chip === */
    .avatar-circle {
        width: 38px;
        height: 38px;
        border-radius: 50%;
        background: linear-gradient(135deg, #4f46e5, #ec4899);
        color: #fff;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .role-chip {
        display: inline-flex;
        align-items: center;
        gap: .25rem;
        padding: .15rem .5rem;
        border-radius: 999px;
        font-size: .7rem;
        font-weight: 600;
        background: rgba(79, 70, 229, .1);
        color: #4338ca;
    }
    .role-chip + .role-chip { margin-left: .25rem; }
    .role-chip.is-superadmin { background: rgba(217, 70, 239, .14); color: #a21caf; }
    .role-chip.is-mitra { background: rgba(14, 165, 233, .14); color: #0369a1; }
    .role-chip.is-jasa { background: rgba(34, 197, 94, .14); color: #15803d; }
    .role-chip.is-utilitas { background: rgba(245, 158, 11, .14); color: #b45309; }

    .tipe-pill {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        padding: .2rem .6rem;
        border-radius: 999px;
        font-size: .72rem;
        font-weight: 600;
    }
    .tipe-pill.pegawai { background: rgba(34, 197, 94, .14); color: #15803d; }
    .tipe-pill.mitra { background: rgba(14, 165, 233, .14); color: #0369a1; }
    .tipe-pill.sistem { background: rgba(217, 70, 239, .14); color: #a21caf; }

    /* === Tipe akun radio cards === */
    .tipe-akun-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 1rem;
    }
    .tipe-akun-card {
        position: relative;
        border-radius: 1rem;
        border: 2px solid #e2e8f0;
        padding: 1.1rem;
        cursor: pointer;
        transition: all .2s ease;
        background: #fff;
    }
    .tipe-akun-card .tipe-akun-icon {
        width: 44px; height: 44px; border-radius: 12px;
        display: inline-flex; align-items: center; justify-content: center;
        background: rgba(79, 70, 229, .1); color: #4f46e5; margin-bottom: .65rem;
        transition: all .2s ease;
    }
    .tipe-akun-card input { position: absolute; opacity: 0; pointer-events: none; }
    .tipe-akun-card:hover { transform: translateY(-2px); border-color: #c7d2fe; }
    .tipe-akun-card.is-active {
        border-color: #4f46e5;
        box-shadow: 0 12px 28px -18px rgba(79, 70, 229, .55);
    }
    .tipe-akun-card.is-active .tipe-akun-icon {
        background: linear-gradient(135deg, #4f46e5, #7c3aed);
        color: #fff;
    }
    .tipe-akun-card h6 { margin: 0 0 .15rem; font-weight: 700; color: #0f172a; }
    .tipe-akun-card small { color: #64748b; }

    .role-pick {
        display: flex;
        flex-wrap: wrap;
        gap: .5rem;
    }
    .role-pick label {
        cursor: pointer;
        padding: .35rem .8rem;
        border-radius: 999px;
        background: #f1f5f9;
        color: #334155;
        font-size: .8rem;
        font-weight: 600;
        transition: all .2s ease;
        user-select: none;
    }
    .role-pick input { display: none; }
    .role-pick label:hover { background: #e0e7ff; color: #3730a3; }
    .role-pick input:checked + span { }
    .role-pick label.is-active {
        background: linear-gradient(135deg, #4f46e5, #7c3aed);
        color: #fff;
        box-shadow: 0 6px 16px -10px rgba(79, 70, 229, .55);
    }

    /* === Buttons === */
    .btn-gradient {
        background: linear-gradient(135deg, #4f46e5, #7c3aed);
        color: #fff;
        border: 0;
        font-weight: 600;
        transition: all .2s ease;
    }
    .btn-gradient:hover {
        background: linear-gradient(135deg, #4338ca, #6d28d9);
        color: #fff;
        transform: translateY(-1px);
        box-shadow: 0 10px 24px -14px rgba(79, 70, 229, .55);
    }

    /* === Animations === */
    @keyframes adminFadeIn {
        from { opacity: 0; transform: translateY(8px); }
        to { opacity: 1; transform: translateY(0); }
    }
    @keyframes adminRise {
        from { opacity: 0; transform: translateY(14px); }
        to { opacity: 1; transform: translateY(0); }
    }
    @keyframes adminFloat {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-12px); }
    }
    .stagger > * { animation: adminRise .5s ease both; }
    .stagger > *:nth-child(1) { animation-delay: .04s; }
    .stagger > *:nth-child(2) { animation-delay: .08s; }
    .stagger > *:nth-child(3) { animation-delay: .12s; }
    .stagger > *:nth-child(4) { animation-delay: .16s; }
    .stagger > *:nth-child(5) { animation-delay: .20s; }
</style>
