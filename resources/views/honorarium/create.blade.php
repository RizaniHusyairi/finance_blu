@extends('layouts.app')

@section('title')
    Tambah Honorarium
@endsection

@push('css')
<style>
    /* ============================================================
       PAGE BACKGROUND & GLOBAL
       ============================================================ */
    body[data-bs-theme="blue-theme"] .main-content { background: #f6f7fb; }

    /* ============================================================
       PAGE HEADER (Hero)
       ============================================================ */
    .form-hero {
        background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 50%, #ec4899 100%);
        border-radius: 1.25rem;
        padding: 1.5rem 2rem;
        color: #fff;
        position: relative;
        overflow: hidden;
        box-shadow: 0 12px 32px rgba(79, 70, 229, .25);
        margin-bottom: 1.5rem;
        animation: fadeSlideDown .55s cubic-bezier(.22,1,.36,1) both;
    }
    .form-hero::before {
        content: '';
        position: absolute;
        right: -90px; top: -90px;
        width: 280px; height: 280px;
        border-radius: 50%;
        background: rgba(255,255,255,0.10);
    }
    .form-hero::after {
        content: '';
        position: absolute;
        right: 80px; bottom: -70px;
        width: 180px; height: 180px;
        border-radius: 50%;
        background: rgba(255,255,255,0.07);
    }
    .form-hero > * { position: relative; z-index: 1; }
    .form-hero h4 {
        font-weight: 800;
        margin: 0 0 .35rem;
        color: #fff !important;
        letter-spacing: -.01em;
        text-shadow: 0 1px 2px rgba(0,0,0,.12);
    }
    .form-hero p { color: rgba(255,255,255,.92) !important; margin: 0; }
    .form-hero .btn-back {
        background: rgba(255,255,255,.18);
        backdrop-filter: blur(8px);
        border: 1px solid rgba(255,255,255,.30);
        color: #fff;
        font-weight: 600;
        padding: .55rem 1.1rem;
        border-radius: 999px;
        transition: all .2s ease;
        font-size: .85rem;
    }
    .form-hero .btn-back:hover {
        background: rgba(255,255,255,.30);
        color: #fff;
        transform: translateX(-3px);
    }

    /* ============================================================
       PROGRESS STEPPER (sticky top)
       ============================================================ */
    .form-progress {
        background: #fff;
        border: 1px solid #eef0f4;
        border-radius: 1rem;
        padding: 1rem 1.5rem;
        margin-bottom: 1.5rem;
        position: sticky;
        top: 70px;
        z-index: 30;
        box-shadow: 0 4px 12px rgba(15,23,42,.05);
        animation: fadeSlideDown .65s cubic-bezier(.22,1,.36,1) .1s both;
    }
    .progress-bar-wrap {
        height: 6px;
        background: #f1f5f9;
        border-radius: 999px;
        overflow: hidden;
        margin-bottom: .85rem;
    }
    .progress-bar-fill {
        height: 100%;
        background: linear-gradient(90deg, #6366f1, #8b5cf6, #ec4899);
        background-size: 200% 100%;
        border-radius: 999px;
        transition: width .5s cubic-bezier(.22,1,.36,1);
        animation: shimmer 3s linear infinite;
    }
    @keyframes shimmer {
        0% { background-position: 0% 0; }
        100% { background-position: 200% 0; }
    }
    .step-list {
        display: flex;
        gap: 1rem;
        align-items: center;
        flex-wrap: wrap;
    }
    .step-item {
        display: inline-flex;
        align-items: center;
        gap: .55rem;
        font-size: .82rem;
        color: #94a3b8;
        font-weight: 600;
        transition: color .2s ease;
    }
    .step-item.done { color: #10b981; }
    .step-item.active { color: #4f46e5; }
    .step-item .step-dot {
        width: 28px; height: 28px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #f1f5f9;
        color: #94a3b8;
        font-size: .85rem;
        transition: all .25s ease;
    }
    .step-item.done .step-dot {
        background: linear-gradient(135deg, #34d399, #10b981);
        color: #fff;
        box-shadow: 0 4px 10px rgba(16,185,129,.30);
    }
    .step-item.active .step-dot {
        background: linear-gradient(135deg, #818cf8, #6366f1);
        color: #fff;
        box-shadow: 0 4px 10px rgba(99,102,241,.40);
        animation: pulseDot 1.6s ease-in-out infinite;
    }
    @keyframes pulseDot {
        0%, 100% { box-shadow: 0 0 0 0 rgba(99,102,241,.45); }
        50%      { box-shadow: 0 0 0 8px rgba(99,102,241,0); }
    }

    /* ============================================================
       SECTION CARD
       ============================================================ */
    .section-card {
        background: #fff;
        border: 1px solid #eef0f4;
        border-radius: 1rem;
        margin-bottom: 1.25rem;
        overflow: hidden;
        transition: box-shadow .25s ease, transform .25s ease, border-color .25s ease;
        animation: fadeSlideUp .55s cubic-bezier(.22,1,.36,1) both;
    }
    .section-card:hover {
        box-shadow: 0 14px 32px rgba(15,23,42,.06);
        border-color: #e2e8f0;
    }
    .section-card.is-active {
        border-color: rgba(99,102,241,.35);
        box-shadow: 0 0 0 4px rgba(99,102,241,.08);
    }
    .section-card:nth-of-type(1) { animation-delay: .15s; }
    .section-card:nth-of-type(2) { animation-delay: .25s; }
    .section-card:nth-of-type(3) { animation-delay: .35s; }
    .section-card:nth-of-type(4) { animation-delay: .45s; }

    .section-head {
        padding: 1.1rem 1.5rem;
        border-bottom: 1px solid #f1f3f7;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        background: linear-gradient(180deg, #fafbff 0%, #ffffff 100%);
    }
    .section-head .head-left {
        display: flex;
        align-items: center;
        gap: .85rem;
    }
    .section-head .section-icon {
        width: 44px; height: 44px;
        border-radius: 12px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        color: #fff;
        flex-shrink: 0;
        box-shadow: 0 6px 14px var(--icon-shadow, rgba(99,102,241,.30));
        background: var(--icon-bg, linear-gradient(135deg, #818cf8, #6366f1));
        transition: transform .3s ease;
    }
    .section-card:hover .section-icon { transform: rotate(-6deg) scale(1.06); }
    .section-head h6 {
        margin: 0;
        font-size: 1rem;
        font-weight: 800;
        color: #0f172a;
        letter-spacing: -.01em;
    }
    .section-head small {
        font-size: .78rem;
        color: #64748b;
        display: block;
        margin-top: .15rem;
    }
    .section-letter {
        font-size: .68rem;
        font-weight: 800;
        letter-spacing: .12em;
        color: #94a3b8;
        text-transform: uppercase;
        background: #f1f5f9;
        padding: .2rem .55rem;
        border-radius: 999px;
    }
    .section-body { padding: 1.5rem; }

    /* Color variants */
    .icon-primary { --icon-bg: linear-gradient(135deg, #818cf8, #6366f1); --icon-shadow: rgba(99,102,241,.35); }
    .icon-info    { --icon-bg: linear-gradient(135deg, #38bdf8, #0ea5e9); --icon-shadow: rgba(14,165,233,.35); }
    .icon-success { --icon-bg: linear-gradient(135deg, #34d399, #10b981); --icon-shadow: rgba(16,185,129,.35); }
    .icon-warning { --icon-bg: linear-gradient(135deg, #fbbf24, #f59e0b); --icon-shadow: rgba(245,158,11,.35); }
    .icon-danger  { --icon-bg: linear-gradient(135deg, #fb7185, #f43f5e); --icon-shadow: rgba(244,63,94,.35); }

    /* ============================================================
       MODERN INPUT FIELDS
       ============================================================ */
    .form-label.modern {
        font-size: .78rem;
        font-weight: 700;
        color: #475569;
        letter-spacing: .02em;
        margin-bottom: .4rem;
        display: inline-flex;
        align-items: center;
        gap: .35rem;
    }
    .form-label.modern .text-danger { font-size: .9rem; }

    .form-control.modern,
    .form-select.modern {
        border: 1px solid #e2e8f0;
        background: #f8fafc;
        border-radius: .65rem;
        padding: .6rem .85rem;
        font-size: .9rem;
        transition: all .2s ease;
    }
    .form-control.modern:hover,
    .form-select.modern:hover {
        border-color: #cbd5e1;
        background: #fff;
    }
    .form-control.modern:focus,
    .form-select.modern:focus {
        outline: 0;
        border-color: #6366f1;
        background: #fff;
        box-shadow: 0 0 0 4px rgba(99,102,241,.12);
        transform: translateY(-1px);
    }
    .form-control.modern[readonly] {
        background: #f1f5f9;
        color: #64748b;
        cursor: not-allowed;
    }
    .form-control.modern[readonly]:hover {
        border-color: #e2e8f0;
        background: #f1f5f9;
    }

    /* Input group */
    .input-group-modern {
        display: flex;
        align-items: center;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: .65rem;
        overflow: hidden;
        transition: all .2s ease;
    }
    .input-group-modern:focus-within {
        border-color: #6366f1;
        background: #fff;
        box-shadow: 0 0 0 4px rgba(99,102,241,.12);
        transform: translateY(-1px);
    }
    .input-group-modern .ig-prefix,
    .input-group-modern .ig-suffix {
        padding: 0 .85rem;
        font-size: .8rem;
        color: #64748b;
        font-weight: 600;
        background: rgba(99,102,241,.07);
    }
    .input-group-modern input,
    .input-group-modern select {
        flex: 1;
        border: 0;
        background: transparent;
        padding: .6rem .85rem;
        font-size: .9rem;
        outline: 0;
        min-width: 0;
    }

    /* Step badge */
    .step-pill {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        font-size: .7rem;
        font-weight: 700;
        padding: .25rem .7rem;
        border-radius: 999px;
        text-transform: uppercase;
        letter-spacing: .04em;
    }
    .step-pill-1 {
        background: linear-gradient(135deg, #ddd6fe, #c4b5fd);
        color: #5b21b6;
    }
    .step-pill-2 {
        background: linear-gradient(135deg, #d1fae5, #a7f3d0);
        color: #065f46;
    }

    .verifikator-section {
        background: #fafbff;
        border: 1px dashed #e2e8f0;
        border-radius: .85rem;
        padding: 1.1rem 1.25rem;
        margin-bottom: 1rem;
        position: relative;
    }
    .verifikator-section .vs-hint {
        font-size: .76rem;
        color: #64748b;
        margin: .35rem 0 1rem;
        display: flex;
        align-items: flex-start;
        gap: .35rem;
    }
    .verifikator-section .vs-hint i { color: #6366f1; }

    /* ============================================================
       PENERIMA CARD (Recipient row)
       ============================================================ */
    #penerimaContainer { padding: 0; }
    #penerimaContainer .penerima-empty {
        text-align: center;
        padding: 2.5rem 1rem;
        color: #94a3b8;
        background: linear-gradient(180deg, #fafbff 0%, #f1f5f9 100%);
        border: 2px dashed #e2e8f0;
        border-radius: 1rem;
    }
    #penerimaContainer .penerima-empty i {
        font-size: 2.5rem;
        margin-bottom: .65rem;
        display: block;
        background: linear-gradient(135deg, #c7d2fe, #818cf8);
        -webkit-background-clip: text;
        background-clip: text;
        -webkit-text-fill-color: transparent;
    }

    .penerima-row {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: .85rem;
        margin-bottom: .85rem;
        overflow: hidden;
        animation: scaleIn .35s cubic-bezier(.22,1,.36,1) both;
        transition: border-color .2s ease, box-shadow .2s ease, transform .2s ease;
    }
    .penerima-row.removing {
        animation: scaleOut .3s cubic-bezier(.22,1,.36,1) forwards;
    }
    .penerima-row:hover {
        border-color: #c7d2fe;
        box-shadow: 0 8px 20px rgba(99,102,241,.10);
    }
    .penerima-row .pr-head {
        padding: .75rem 1.1rem;
        background: linear-gradient(135deg, rgba(99,102,241,.08), rgba(139,92,246,.05));
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid #f1f3f7;
    }
    .penerima-row .pr-num {
        display: inline-flex;
        align-items: center;
        gap: .55rem;
        font-weight: 700;
        font-size: .85rem;
        color: #4f46e5;
    }
    .penerima-row .pr-num .pr-num-circle {
        width: 28px; height: 28px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #818cf8, #6366f1);
        color: #fff;
        font-size: .78rem;
        font-weight: 700;
        box-shadow: 0 4px 10px rgba(99,102,241,.35);
    }
    .penerima-row .btn-remove-row {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        background: rgba(244,63,94,.10);
        color: #be123c;
        border: 1px solid rgba(244,63,94,.20);
        font-size: .76rem;
        font-weight: 600;
        padding: .35rem .75rem;
        border-radius: .55rem;
        transition: all .15s ease;
    }
    .penerima-row .btn-remove-row:hover {
        background: #f43f5e;
        color: #fff;
        border-color: #f43f5e;
        transform: translateY(-1px);
    }
    .penerima-row .pr-body { padding: 1rem 1.1rem 1.1rem; }
    .penerima-row .pr-section-title {
        font-size: .68rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .08em;
        color: #94a3b8;
        margin: .65rem 0 .65rem;
        display: flex;
        align-items: center;
        gap: .35rem;
    }
    .penerima-row .pr-section-title::before {
        content: '';
        width: 18px; height: 1px;
        background: #cbd5e1;
    }

    /* ============================================================
       BUTTON: Add penerima
       ============================================================ */
    .btn-add-row {
        background: linear-gradient(135deg, #10b981, #059669);
        border: 0;
        color: #fff;
        font-weight: 600;
        font-size: .85rem;
        padding: .55rem 1.1rem;
        border-radius: .65rem;
        box-shadow: 0 6px 14px rgba(16,185,129,.30);
        transition: all .2s ease;
        display: inline-flex;
        align-items: center;
        gap: .4rem;
    }
    .btn-add-row:hover {
        color: #fff;
        transform: translateY(-2px);
        box-shadow: 0 12px 24px rgba(16,185,129,.40);
    }
    .btn-add-row:active { transform: translateY(0); }

    /* ============================================================
       SUMMARY (sticky right)
       ============================================================ */
    .summary-stack {
        position: sticky;
        top: 165px;
        display: flex;
        flex-direction: column;
        gap: 1rem;
        animation: fadeSlideUp .55s cubic-bezier(.22,1,.36,1) .55s both;
    }
    .summary-card-modern {
        background: #fff;
        border: 1px solid #eef0f4;
        border-radius: 1rem;
        overflow: hidden;
        transition: box-shadow .25s ease;
    }
    .summary-card-modern:hover {
        box-shadow: 0 14px 32px rgba(15,23,42,.06);
    }
    .summary-card-modern .sc-head {
        padding: 1rem 1.25rem;
        border-bottom: 1px solid #f1f3f7;
        background: linear-gradient(180deg, #fafbff 0%, #ffffff 100%);
        font-weight: 700;
        font-size: .85rem;
        color: #0f172a;
        display: flex;
        align-items: center;
        gap: .55rem;
    }
    .summary-card-modern .sc-head i {
        width: 28px; height: 28px;
        border-radius: 8px;
        display: inline-flex; align-items: center; justify-content: center;
        background: rgba(99,102,241,.12);
        color: #4f46e5;
    }
    .summary-card-modern .sc-body { padding: .65rem 1.25rem 1rem; }

    .summary-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: .55rem 0;
        border-bottom: 1px dashed #f1f3f7;
        font-size: .82rem;
    }
    .summary-row:last-child { border-bottom: 0; }
    .summary-row .label { color: #64748b; }
    .summary-row .value {
        font-weight: 700;
        font-variant-numeric: tabular-nums;
        color: #0f172a;
        transition: transform .2s ease, color .2s ease;
    }
    .summary-row .value.flash {
        animation: flashValue .55s ease;
    }
    .summary-row.total {
        margin-top: .35rem;
        padding-top: .85rem;
        border-top: 2px solid #e2e8f0;
        border-bottom: 0;
    }
    .summary-row.total .label {
        font-weight: 700;
        color: #0f172a;
        font-size: .85rem;
    }
    .summary-row.total .value {
        font-size: 1.15rem;
        background: linear-gradient(135deg, #10b981, #059669);
        -webkit-background-clip: text;
        background-clip: text;
        -webkit-text-fill-color: transparent;
        font-weight: 800;
    }
    .summary-row.pph .value { color: #dc2626; }

    /* Checklist */
    .checklist-modern .cl-item {
        display: flex;
        align-items: center;
        gap: .65rem;
        padding: .55rem .25rem;
        font-size: .82rem;
        color: #475569;
        border-bottom: 1px dashed #f1f3f7;
        transition: all .25s ease;
    }
    .checklist-modern .cl-item:last-child { border-bottom: 0; }
    .checklist-modern .cl-item .cl-icon {
        width: 22px; height: 22px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        font-size: .72rem;
        background: #f1f5f9;
        color: #94a3b8;
        transition: all .35s cubic-bezier(.22,1,.36,1);
    }
    .checklist-modern .cl-item.is-ok {
        color: #047857;
    }
    .checklist-modern .cl-item.is-ok .cl-icon {
        background: linear-gradient(135deg, #34d399, #10b981);
        color: #fff;
        transform: scale(1.05);
        box-shadow: 0 4px 10px rgba(16,185,129,.30);
        animation: bounceCheck .45s cubic-bezier(.22,1,.36,1);
    }

    /* Catatan card */
    .note-card {
        background: linear-gradient(135deg, rgba(251,191,36,.08), rgba(245,158,11,.05));
        border: 1px solid rgba(245,158,11,.18);
        border-radius: 1rem;
        padding: .9rem 1.1rem;
        font-size: .8rem;
        color: #78350f;
    }
    .note-card .note-title {
        font-weight: 700;
        color: #92400e;
        margin-bottom: .35rem;
        display: flex;
        align-items: center;
        gap: .35rem;
    }

    /* ============================================================
       SUBMIT BAR (sticky bottom)
       ============================================================ */
    .submit-bar {
        background: rgba(255,255,255,.85);
        backdrop-filter: blur(10px);
        border: 1px solid #eef0f4;
        border-radius: 1rem;
        padding: 1rem 1.25rem;
        display: flex;
        gap: .75rem;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        box-shadow: 0 12px 28px rgba(15,23,42,.08);
        position: sticky;
        bottom: 1rem;
        z-index: 20;
        margin-bottom: 1rem;
        animation: fadeSlideUp .65s cubic-bezier(.22,1,.36,1) .65s both;
    }
    .btn-submit-primary {
        background: linear-gradient(135deg, #6366f1, #8b5cf6, #ec4899);
        background-size: 200% 100%;
        background-position: 0% 0%;
        border: 0;
        color: #fff;
        font-weight: 700;
        padding: .7rem 1.5rem;
        border-radius: .7rem;
        font-size: .9rem;
        box-shadow: 0 8px 22px rgba(99,102,241,.35);
        transition: all .35s ease;
        display: inline-flex;
        align-items: center;
        gap: .5rem;
    }
    .btn-submit-primary:not(:disabled):hover {
        background-position: 100% 0%;
        transform: translateY(-2px);
        box-shadow: 0 14px 28px rgba(99,102,241,.45);
        color: #fff;
    }
    .btn-submit-primary:disabled {
        background: linear-gradient(135deg, #cbd5e1, #94a3b8);
        cursor: not-allowed;
        box-shadow: none;
        opacity: .85;
    }
    .btn-cancel-modern {
        background: #f1f5f9;
        border: 1px solid #e2e8f0;
        color: #475569;
        font-weight: 600;
        padding: .7rem 1.4rem;
        border-radius: .7rem;
        font-size: .9rem;
        transition: all .2s ease;
    }
    .btn-cancel-modern:hover {
        background: #e2e8f0;
        color: #1e293b;
        transform: translateY(-1px);
    }

    /* ============================================================
       VALIDATION ALERT
       ============================================================ */
    .alert-modern-error {
        background: linear-gradient(135deg, rgba(244,63,94,.06), rgba(220,38,38,.04));
        border: 1px solid rgba(244,63,94,.20);
        border-left: 4px solid #f43f5e;
        border-radius: 1rem;
        padding: 1rem 1.25rem;
        color: #991b1b;
        margin-bottom: 1.25rem;
        animation: shake .5s cubic-bezier(.36,.07,.19,.97) both;
    }
    .alert-modern-error .alert-title {
        font-weight: 800;
        color: #b91c1c;
        display: flex;
        align-items: center;
        gap: .5rem;
        margin-bottom: .5rem;
    }
    .alert-modern-error ul { margin: 0; padding-left: 1.5rem; font-size: .85rem; }

    /* Select2 polish */
    .select2-container--bootstrap-5 .select2-selection {
        border: 1px solid #e2e8f0 !important;
        background: #f8fafc !important;
        border-radius: .65rem !important;
        min-height: 41px !important;
        padding: .35rem .35rem !important;
        transition: all .2s ease !important;
    }
    .select2-container--bootstrap-5 .select2-selection:hover {
        border-color: #cbd5e1 !important;
        background: #fff !important;
    }
    .select2-container--bootstrap-5.select2-container--focus .select2-selection,
    .select2-container--bootstrap-5.select2-container--open .select2-selection {
        border-color: #6366f1 !important;
        background: #fff !important;
        box-shadow: 0 0 0 4px rgba(99,102,241,.12) !important;
    }

    /* File input modern */
    .file-drop {
        border: 2px dashed #e2e8f0;
        border-radius: .85rem;
        padding: 1.5rem;
        text-align: center;
        background: #fafbff;
        cursor: pointer;
        transition: all .25s ease;
        position: relative;
    }
    .file-drop:hover {
        border-color: #6366f1;
        background: rgba(99,102,241,.04);
    }
    .file-drop input[type="file"] {
        position: absolute; inset: 0;
        opacity: 0;
        cursor: pointer;
    }
    .file-drop .fd-icon {
        width: 52px; height: 52px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, rgba(245,158,11,.15), rgba(217,119,6,.10));
        color: #d97706;
        font-size: 1.4rem;
        margin-bottom: .65rem;
    }
    .file-drop .fd-title {
        font-weight: 700;
        color: #1e293b;
        margin-bottom: .2rem;
    }
    .file-drop .fd-sub {
        font-size: .75rem;
        color: #64748b;
    }
    .file-drop.has-file {
        border-color: #10b981;
        background: rgba(16,185,129,.04);
    }
    .file-drop.has-file .fd-icon {
        background: linear-gradient(135deg, rgba(16,185,129,.20), rgba(5,150,105,.10));
        color: #047857;
    }

    /* ============================================================
       ANIMATIONS
       ============================================================ */
    @keyframes fadeSlideDown {
        from { opacity: 0; transform: translateY(-12px); }
        to   { opacity: 1; transform: translateY(0); }
    }
    @keyframes fadeSlideUp {
        from { opacity: 0; transform: translateY(14px); }
        to   { opacity: 1; transform: translateY(0); }
    }
    @keyframes scaleIn {
        from { opacity: 0; transform: scale(.96) translateY(-6px); }
        to   { opacity: 1; transform: scale(1) translateY(0); }
    }
    @keyframes scaleOut {
        from { opacity: 1; transform: scale(1); max-height: 600px; }
        to   { opacity: 0; transform: scale(.95); max-height: 0; padding: 0; margin: 0; }
    }
    @keyframes bounceCheck {
        0%   { transform: scale(.5) rotate(-90deg); }
        60%  { transform: scale(1.18) rotate(8deg); }
        100% { transform: scale(1.05) rotate(0deg); }
    }
    @keyframes flashValue {
        0%   { transform: scale(1); color: #4f46e5; }
        50%  { transform: scale(1.08); color: #4f46e5; }
        100% { transform: scale(1); color: #0f172a; }
    }
    @keyframes shake {
        10%, 90% { transform: translateX(-1px); }
        20%, 80% { transform: translateX(2px); }
        30%, 50%, 70% { transform: translateX(-3px); }
        40%, 60% { transform: translateX(3px); }
    }

    @media (max-width: 991px) {
        .form-progress { position: static; }
        .summary-stack { position: static; }
        .submit-bar { position: static; }
    }
</style>
@endpush

@section('content')
    <x-page-title title="Manajemen Honor" subtitle="Tambah Honorarium" />

    {{-- Hero Header --}}
    <div class="form-hero">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h4><i class="bi bi-stars me-2"></i>Tambah Tagihan Honorarium</h4>
                <p class="small">Lengkapi data kegiatan, verifikator, penerima, dan dokumen pendukung untuk membuat draft pengajuan.</p>
            </div>
            <a href="{{ route('honorarium.index') }}" class="btn-back">
                <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar
            </a>
        </div>
    </div>

    {{-- Progress Stepper --}}
    <div class="form-progress" id="formProgress">
        <div class="progress-bar-wrap">
            <div class="progress-bar-fill" id="progressFill" style="width: 0%"></div>
        </div>
        <div class="step-list" id="stepList">
            <div class="step-item" data-step="info">
                <span class="step-dot"><i class="bi bi-info-lg"></i></span>
                <span>Informasi</span>
            </div>
            <i class="bi bi-chevron-right text-muted small"></i>
            <div class="step-item" data-step="verifikator">
                <span class="step-dot"><i class="bi bi-person-check"></i></span>
                <span>Verifikator</span>
            </div>
            <i class="bi bi-chevron-right text-muted small"></i>
            <div class="step-item" data-step="penerima">
                <span class="step-dot"><i class="bi bi-people"></i></span>
                <span>Penerima</span>
            </div>
            <i class="bi bi-chevron-right text-muted small"></i>
            <div class="step-item" data-step="dokumen">
                <span class="step-dot"><i class="bi bi-paperclip"></i></span>
                <span>Dokumen</span>
            </div>
            <span class="ms-auto small fw-bold text-muted" id="progressPct">0% lengkap</span>
        </div>
    </div>

    {{-- Validation Errors --}}
    @if ($errors->any())
        <div class="alert-modern-error">
            <div class="alert-title">
                <i class="bi bi-exclamation-octagon-fill"></i>
                Terdapat kesalahan pada formulir
            </div>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('honorarium.store') }}" method="POST" enctype="multipart/form-data" id="honorariumForm">
        @csrf
        <div class="row g-4">
            {{-- LEFT COLUMN --}}
            <div class="col-lg-8">

                {{-- A. Informasi Pengajuan --}}
                <div class="section-card" data-section="info">
                    <div class="section-head">
                        <div class="head-left">
                            <span class="section-icon icon-primary"><i class="bi bi-info-circle-fill"></i></span>
                            <div>
                                <h6>Informasi Pengajuan</h6>
                                <small>Data dasar honorarium dan sumber anggaran</small>
                            </div>
                        </div>
                        <span class="section-letter">Step A</span>
                    </div>
                    <div class="section-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label modern">No Tagihan</label>
                                <input type="text" class="form-control modern" value="{{ $nextNumber }}" readonly>
                                <div class="form-text small">Nomor otomatis</div>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label modern">Uraian / Deskripsi Kegiatan <span class="text-danger">*</span></label>
                                <input type="text" name="deskripsi" id="inp_deskripsi" class="form-control modern" required
                                    value="{{ old('deskripsi') }}" placeholder="Contoh: Pembayaran Honor Narasumber Sosialisasi...">
                            </div>
                            <div class="col-12">
                                <label class="form-label modern">Nama Supplier SPP/SPM <span class="text-danger">*</span></label>
                                <input type="text" name="nama_supplier" id="inp_nama_supplier" class="form-control modern" required
                                    value="{{ old('nama_supplier') }}" placeholder="Contoh: PARA PEGAWAI KANTOR UPBU AJI PANGERAN TUMENGGUNG PRANOTO">
                                <div class="form-text small"><i class="bi bi-info-circle me-1"></i>Tampil di kolom Nama Supplier pada PDF SPP/SPM honorarium.</div>
                            </div>
                            <div class="col-12">
                                @include('partials.dipa-item-grouped-select', [
                                    'budgetGroups' => $budgetGroups,
                                    'fieldName' => 'dipa_revision_item_id',
                                    'fieldId' => 'dipa_revision_item_id',
                                    'fieldClass' => 'form-select select2',
                                    'fieldLabel' => 'Sumber Anggaran (Item DIPA / COA)',
                                    'placeholder' => '-- Pilih Item Anggaran DIPA Aktif --',
                                ])
                            </div>
                        </div>
                    </div>
                </div>

                {{-- B. Verifikator --}}
                <div class="section-card" data-section="verifikator">
                    <div class="section-head">
                        <div class="head-left">
                            <span class="section-icon icon-info"><i class="bi bi-person-check-fill"></i></span>
                            <div>
                                <h6>Verifikator</h6>
                                <small>Pejabat yang akan memverifikasi pengajuan</small>
                            </div>
                        </div>
                        <span class="section-letter">Step B</span>
                    </div>
                    <div class="section-body">
                        @php
                            $verifikatorStep1 = [
                                'ppk'                   => ['label' => 'Pejabat Pembuat Komitmen (PPK)', 'input' => 'ppk_id',                   'id' => 'inp_ppk',                   'icon' => 'bi-person-badge'],
                                'ppspm'                 => ['label' => 'PPSPM',                          'input' => 'ppspm_id',                 'id' => 'inp_ppspm',                 'icon' => 'bi-person-check'],
                                'koordinator_keuangan'  => ['label' => 'Koordinator Keuangan',           'input' => 'koordinator_keuangan_id',  'id' => 'inp_koordinator_keuangan',  'icon' => 'bi-clipboard-check'],
                                'bendahara_pengeluaran' => ['label' => 'Bendahara Pengeluaran',          'input' => 'bendahara_pengeluaran_id', 'id' => 'inp_bendahara',             'icon' => 'bi-cash-stack'],
                                'bendahara_penerimaan'  => ['label' => 'Bendahara Penerimaan',           'input' => 'bendahara_penerimaan_id',  'id' => 'inp_bendahara_penerimaan',  'icon' => 'bi-wallet2'],
                            ];
                            $verifikatorStep2 = [
                                'kasubbag' => ['label' => 'Kasubbag Keuangan & TU', 'input' => 'kasubbag_id', 'id' => 'inp_kasubbag', 'icon' => 'bi-person-gear'],
                            ];
                        @endphp

                        <div class="verifikator-section">
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <span class="step-pill step-pill-1"><i class="bi bi-1-circle-fill"></i> Step 1</span>
                                <h6 class="fw-bold mb-0 small text-uppercase text-dark">Verifikasi Paralel</h6>
                            </div>
                            <p class="vs-hint"><i class="bi bi-info-circle"></i><span>Kelima verifikator menerima dokumen secara bersamaan. Pengajuan baru lanjut ke Step 2 setelah semua menyetujui.</span></p>
                            <div class="row g-3">
                                @foreach($verifikatorStep1 as $key => $meta)
                                    <div class="col-md-6">
                                        <label class="form-label modern">
                                            <i class="bi {{ $meta['icon'] }}"></i>
                                            {{ $meta['label'] }} <span class="text-danger">*</span>
                                        </label>
                                        <select name="{{ $meta['input'] }}" id="{{ $meta['id'] }}" class="form-select select2" required>
                                            <option value="">-- Pilih {{ $meta['label'] }} --</option>
                                            @foreach($verifikatorOptions[$key] ?? [] as $u)
                                                <option value="{{ $u['id'] }}" {{ old($meta['input']) == $u['id'] ? 'selected' : '' }}>
                                                    {{ $u['name'] }} @if($u['nip'] !== '-')- {{ $u['nip'] }}@endif
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="verifikator-section">
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <span class="step-pill step-pill-2"><i class="bi bi-2-circle-fill"></i> Step 2</span>
                                <h6 class="fw-bold mb-0 small text-uppercase text-dark">Persetujuan Final</h6>
                            </div>
                            <p class="vs-hint"><i class="bi bi-info-circle"></i><span>Verifikator final yang menyetujui pengajuan setelah Step 1 selesai.</span></p>
                            <div class="row g-3">
                                @foreach($verifikatorStep2 as $key => $meta)
                                    <div class="col-md-6">
                                        <label class="form-label modern">
                                            <i class="bi {{ $meta['icon'] }}"></i>
                                            {{ $meta['label'] }} <span class="text-danger">*</span>
                                        </label>
                                        <select name="{{ $meta['input'] }}" id="{{ $meta['id'] }}" class="form-select select2" required>
                                            <option value="">-- Pilih {{ $meta['label'] }} --</option>
                                            @foreach($verifikatorOptions[$key] ?? [] as $u)
                                                <option value="{{ $u['id'] }}" {{ old($meta['input']) == $u['id'] ? 'selected' : '' }}>
                                                    {{ $u['name'] }} @if($u['nip'] !== '-')- {{ $u['nip'] }}@endif
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="row g-3 mt-1">
                            <div class="col-12">
                                <label class="form-label modern"><i class="bi bi-credit-card-2-front"></i> Mekanisme Pembayaran <span class="text-danger">*</span></label>
                                <select name="mekanisme_pembayaran" class="form-select modern" required>
                                    @foreach(\App\Enums\MekanismePembayaran::optionsFor('HONORARIUM') as $val => $lbl)
                                        <option value="{{ $val }}" {{ old('mekanisme_pembayaran', \App\Enums\MekanismePembayaran::defaultFor('HONORARIUM')->value) === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                                    @endforeach
                                </select>
                                <div class="form-text small">LS - Pihak Ketiga: dibayar langsung ke rekening masing-masing penerima. LS - Via Bendahara: diteruskan melalui Bendahara Pengeluaran.</div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- C. Rincian Penerima --}}
                <div class="section-card" data-section="penerima">
                    <div class="section-head">
                        <div class="head-left">
                            <span class="section-icon icon-success"><i class="bi bi-people-fill"></i></span>
                            <div>
                                <h6>Rincian Penerima Honorarium</h6>
                                <small>Tambahkan data penerima dan nominal honor</small>
                            </div>
                        </div>
                        <button type="button" class="btn-add-row" id="btnAddRow">
                            <i class="bi bi-plus-lg"></i> Tambah Penerima
                        </button>
                    </div>
                    <div class="section-body" style="padding: 1.1rem 1.25rem;">
                        <div id="penerimaContainer">
                            {{-- Penerima cards rendered by JS --}}
                        </div>
                    </div>
                </div>

                {{-- D. Dokumen Pendukung --}}
                <div class="section-card" data-section="dokumen">
                    <div class="section-head">
                        <div class="head-left">
                            <span class="section-icon icon-warning"><i class="bi bi-file-earmark-arrow-up-fill"></i></span>
                            <div>
                                <h6>Dokumen Pendukung</h6>
                                <small>Upload SK atau dokumen dasar pembayaran (opsional)</small>
                            </div>
                        </div>
                        <span class="section-letter">Step D</span>
                    </div>
                    <div class="section-body">
                        <label class="file-drop" id="fileDropZone">
                            <div class="fd-icon"><i class="bi bi-cloud-arrow-up"></i></div>
                            <div class="fd-title" id="fdTitle">Klik atau tarik file ke sini</div>
                            <div class="fd-sub">PDF, DOC, DOCX · Maksimal 10MB</div>
                            <input type="file" name="file_sk" id="file_sk" accept=".pdf,.doc,.docx">
                        </label>
                    </div>
                </div>
            </div>

            {{-- RIGHT COLUMN: Sticky Summary --}}
            <div class="col-lg-4">
                <div class="summary-stack">

                    <div class="summary-card-modern">
                        <div class="sc-head"><i class="bi bi-calculator"></i> Ringkasan Nilai</div>
                        <div class="sc-body">
                            <div class="summary-row">
                                <span class="label">Jumlah Penerima</span>
                                <span class="value" id="sumRowCount">0</span>
                            </div>
                            <div class="summary-row">
                                <span class="label">Total Bruto</span>
                                <span class="value" id="sumBruto">Rp 0</span>
                            </div>
                            <div class="summary-row pph">
                                <span class="label">Total PPh</span>
                                <span class="value" id="sumPph">Rp 0</span>
                            </div>
                            <div class="summary-row total">
                                <span class="label">Total Netto</span>
                                <span class="value" id="sumNetto">Rp 0</span>
                            </div>
                        </div>
                    </div>

                    <div class="summary-card-modern">
                        <div class="sc-head"><i class="bi bi-clipboard-check"></i> Checklist Kelengkapan</div>
                        <div class="sc-body checklist-modern">
                            <div class="cl-item" id="chk_deskripsi">
                                <span class="cl-icon"><i class="bi bi-check"></i></span>
                                <span>Deskripsi kegiatan diisi</span>
                            </div>
                            <div class="cl-item" id="chk_supplier">
                                <span class="cl-icon"><i class="bi bi-check"></i></span>
                                <span>Nama supplier diisi</span>
                            </div>
                            <div class="cl-item" id="chk_dipa">
                                <span class="cl-icon"><i class="bi bi-check"></i></span>
                                <span>Sumber anggaran dipilih</span>
                            </div>
                            <div class="cl-item" id="chk_ppk">
                                <span class="cl-icon"><i class="bi bi-check"></i></span>
                                <span>Verifikator PPK dipilih</span>
                            </div>
                            <div class="cl-item" id="chk_bendahara">
                                <span class="cl-icon"><i class="bi bi-check"></i></span>
                                <span>Bendahara Pengeluaran dipilih</span>
                            </div>
                            <div class="cl-item" id="chk_penerima">
                                <span class="cl-icon"><i class="bi bi-check"></i></span>
                                <span>Minimal 1 penerima</span>
                            </div>
                            <div class="cl-item" id="chk_nominal">
                                <span class="cl-icon"><i class="bi bi-check"></i></span>
                                <span>Nominal penerima valid (&gt; 0)</span>
                            </div>
                        </div>
                    </div>

                    <div class="note-card">
                        <div class="note-title"><i class="bi bi-lightbulb-fill"></i> Tips</div>
                        Simpan dulu sebagai <strong>Draft</strong>, lalu lengkapi unggahan dokumen wajib di halaman Detail untuk mengajukan verifikasi.
                    </div>
                </div>
            </div>
        </div>

        {{-- Submit Bar --}}
        <div class="submit-bar">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-shield-check fs-4 text-success"></i>
                <div>
                    <div class="fw-bold small text-dark">Siap menyimpan?</div>
                    <div class="small text-muted">Pastikan semua checklist hijau sebelum klik simpan</div>
                </div>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('honorarium.index') }}" class="btn-cancel-modern">Batal</a>
                <button type="submit" name="submit_type" value="draft" class="btn-submit-primary" id="btnSubmitPpk">
                    <i class="bi bi-cloud-check-fill"></i> Simpan sebagai Draft
                </button>
            </div>
        </div>
    </form>

    <script>
    let rowIndex = 0;
    const STEPS = ['info', 'verifikator', 'penerima', 'dokumen'];

    function toNumber(value) { return parseFloat(String(value).replace(/,/g, '')) || 0; }
    function formatRp(value) { return new Intl.NumberFormat('id-ID').format(Math.round(value)); }

    function flashSummary(el) {
        if (!el) return;
        el.classList.remove('flash');
        void el.offsetWidth;
        el.classList.add('flash');
    }

    function updateRowNumbers() {
        document.querySelectorAll('#penerimaContainer .penerima-row').forEach((row, i) => {
            const numEl = row.querySelector('.row-no');
            if (numEl) numEl.textContent = i + 1;
        });
    }

    function recalcRow(row) {
        const honor = toNumber(row.querySelector('.honor_amount').value);
        const pphSelect = row.querySelector('.pph_percentage');
        let pphPct = 0;
        if (pphSelect) pphPct = toNumber(pphSelect.options[pphSelect.selectedIndex].getAttribute('data-pct'));
        let pphAmount = (honor * pphPct) / 100;
        row.querySelector('.pph_amount').value = pphAmount;
        if (row.querySelector('.pph_display')) row.querySelector('.pph_display').value = formatRp(pphAmount);
        row.querySelector('.netto_display').value = formatRp(honor - Math.round(pphAmount));
    }

    function recalcGrandTotal() {
        let tHonor = 0, tPph = 0, tNetto = 0, rowCount = 0;
        document.querySelectorAll('#penerimaContainer .penerima-row').forEach(row => {
            const h = toNumber(row.querySelector('.honor_amount').value);
            const p = toNumber(row.querySelector('.pph_amount').value);
            tHonor += h; tPph += p; tNetto += (h - p);
            rowCount++;
        });

        const elRow   = document.getElementById('sumRowCount');
        const elBruto = document.getElementById('sumBruto');
        const elPph   = document.getElementById('sumPph');
        const elNetto = document.getElementById('sumNetto');

        if (elRow.textContent != rowCount) flashSummary(elRow);
        if (elBruto.textContent !== 'Rp ' + formatRp(tHonor)) flashSummary(elBruto);
        if (elPph.textContent   !== 'Rp ' + formatRp(tPph))   flashSummary(elPph);
        if (elNetto.textContent !== 'Rp ' + formatRp(tNetto)) flashSummary(elNetto);

        elRow.textContent = rowCount;
        elBruto.textContent = 'Rp ' + formatRp(tHonor);
        elPph.textContent = 'Rp ' + formatRp(tPph);
        elNetto.textContent = 'Rp ' + formatRp(tNetto);

        // Penerima empty placeholder
        const container = document.getElementById('penerimaContainer');
        let placeholder = container.querySelector('.penerima-empty');
        if (rowCount === 0) {
            if (!placeholder) {
                container.insertAdjacentHTML('beforeend', `
                    <div class="penerima-empty">
                        <i class="bi bi-people"></i>
                        <h6 class="fw-bold text-secondary mb-1">Belum ada penerima honorarium</h6>
                        <small>Klik tombol <strong>Tambah Penerima</strong> di kanan atas untuk menambahkan penerima.</small>
                    </div>
                `);
            }
        } else if (placeholder) {
            placeholder.remove();
        }
    }

    function recalcAll() {
        document.querySelectorAll('#penerimaContainer .penerima-row').forEach(row => recalcRow(row));
        recalcGrandTotal();
        updateRowNumbers();
        updateChecklist();
    }

    function addRow(data = {}) {
        const container = document.getElementById('penerimaContainer');
        const placeholder = container.querySelector('.penerima-empty');
        if (placeholder) placeholder.remove();

        const div = document.createElement('div');
        div.className = 'penerima-row';
        div.innerHTML = `
            <div class="pr-head">
                <div class="pr-num">
                    <span class="pr-num-circle row-no">1</span>
                    <span>Penerima</span>
                </div>
                <button type="button" class="btn-remove-row" title="Hapus penerima ini">
                    <i class="bi bi-trash3"></i> Hapus
                </button>
            </div>
            <div class="pr-body">
                <div class="pr-section-title">Identitas Penerima</div>
                <div class="row g-3">
                    <div class="col-md-6 col-lg-3">
                        <label class="form-label modern">Nama Lengkap <span class="text-danger">*</span></label>
                        <input type="text" name="items[${rowIndex}][nama_personel]" class="form-control modern" value="${data.nama_personel ?? ''}" required placeholder="Budi Santoso">
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <label class="form-label modern">NIP/NRP/NIK</label>
                        <input type="text" name="items[${rowIndex}][nrp_nip]" class="form-control modern" value="${data.nrp_nip ?? ''}" placeholder="Opsional">
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <label class="form-label modern">Pangkat / Korps</label>
                        <input type="text" name="items[${rowIndex}][pangkat_korp]" class="form-control modern" value="${data.pangkat_korp ?? ''}" placeholder="Opsional">
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <label class="form-label modern">Jabatan</label>
                        <input type="text" name="items[${rowIndex}][jabatan]" class="form-control modern" value="${data.jabatan ?? ''}" placeholder="Opsional">
                    </div>
                </div>

                <div class="pr-section-title">Nominal Honor & PPh</div>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label modern">Nilai Honor (Bruto) <span class="text-danger">*</span></label>
                        <div class="input-group-modern">
                            <span class="ig-prefix">Rp</span>
                            <input type="text" inputmode="numeric" autocomplete="off" class="honor_display fw-bold" value="${formatRp(data.nilai_honor ?? 0)}" placeholder="0" required>
                        </div>
                        <input type="hidden" name="items[${rowIndex}][nilai_honor]" class="honor_amount" value="${data.nilai_honor ?? 0}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label modern">Potongan PPh</label>
                        <div class="d-flex gap-2">
                            <select class="form-select modern pph_percentage" style="max-width: 145px;">
                                <option value="0" data-pct="0">0% (Tanpa)</option>
                                @foreach($tarifPajaks as $tp)
                                    <option value="{{ $tp->persentase }}" data-pct="{{ $tp->persentase }}">
                                        {{ $tp->kode_pajak }} ({{ (float)$tp->persentase }}%)
                                    </option>
                                @endforeach
                            </select>
                            <div class="input-group-modern flex-fill">
                                <span class="ig-prefix">Rp</span>
                                <input type="text" class="pph_display text-danger fw-bold" readonly value="${formatRp(data.pph ?? 0)}">
                            </div>
                        </div>
                        <input type="hidden" name="items[${rowIndex}][pph]" class="pph_amount" value="${data.pph ?? 0}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label modern">Nilai Netto (Diterima)</label>
                        <div class="input-group-modern">
                            <span class="ig-prefix">Rp</span>
                            <input type="text" class="netto_display fw-bold text-success" readonly>
                        </div>
                    </div>
                </div>

                <div class="pr-section-title">Rekening Pembayaran</div>
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label modern">Jenis Bank</label>
                        <input type="text" name="items[${rowIndex}][jenis_bank]" class="form-control modern" value="${data.jenis_bank ?? ''}" placeholder="BRI, Mandiri">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label modern">No. Rekening</label>
                        <input type="text" name="items[${rowIndex}][rekening]" class="form-control modern" value="${data.rekening ?? ''}" placeholder="Opsional">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label modern">Atas Nama Rekening</label>
                        <input type="text" name="items[${rowIndex}][nama_rekening]" class="form-control modern" value="${data.nama_rekening ?? ''}" placeholder="Opsional">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label modern">No. HP / WhatsApp</label>
                        <input type="text" name="items[${rowIndex}][no_hp]" class="form-control modern" value="${data.no_hp ?? ''}" placeholder="08123456789">
                    </div>
                </div>
            </div>
        `;
        container.appendChild(div);
        rowIndex++;
        recalcAll();
    }

    function updateChecklist() {
        const checks = {
            chk_deskripsi: document.getElementById('inp_deskripsi')?.value.trim().length > 0,
            chk_supplier: document.getElementById('inp_nama_supplier')?.value.trim().length > 0,
            chk_dipa: document.getElementById('dipa_revision_item_id')?.value.length > 0,
            chk_ppk: document.getElementById('inp_ppk')?.value.length > 0,
            chk_bendahara: document.getElementById('inp_bendahara')?.value.length > 0,
            chk_penerima: document.querySelectorAll('#penerimaContainer .penerima-row').length > 0,
            chk_nominal: false,
        };

        let hasValidNominal = false;
        document.querySelectorAll('#penerimaContainer .penerima-row .honor_amount').forEach(el => {
            if (toNumber(el.value) > 0) hasValidNominal = true;
        });
        checks.chk_nominal = hasValidNominal;

        let total = 0, ok = 0;
        for (const [id, isOk] of Object.entries(checks)) {
            const el = document.getElementById(id);
            if (!el) continue;
            total++;
            el.classList.toggle('is-ok', isOk);
            if (isOk) ok++;
        }

        // Progress
        const pct = total > 0 ? Math.round((ok / total) * 100) : 0;
        document.getElementById('progressFill').style.width = pct + '%';
        document.getElementById('progressPct').textContent = pct + '% lengkap';

        // Step states
        const sectionFlags = {
            info: checks.chk_deskripsi && checks.chk_supplier && checks.chk_dipa,
            verifikator: checks.chk_ppk && checks.chk_bendahara,
            penerima: checks.chk_penerima && checks.chk_nominal,
            dokumen: !!document.getElementById('file_sk')?.files?.length,
        };
        const stepEls = document.querySelectorAll('.step-item');
        let firstActiveSet = false;
        stepEls.forEach(stepEl => {
            const step = stepEl.dataset.step;
            stepEl.classList.remove('done', 'active');
            if (sectionFlags[step]) {
                stepEl.classList.add('done');
            } else if (!firstActiveSet) {
                stepEl.classList.add('active');
                firstActiveSet = true;
            }
        });
        document.querySelectorAll('.section-card').forEach(card => {
            card.classList.toggle('is-active', card.dataset.section === document.querySelector('.step-item.active')?.dataset.step);
        });

        const allRequiredOk = checks.chk_deskripsi && checks.chk_supplier && checks.chk_dipa
                            && checks.chk_ppk && checks.chk_bendahara
                            && checks.chk_penerima && checks.chk_nominal;
        const btnSubmit = document.getElementById('btnSubmitPpk');
        if (btnSubmit) {
            btnSubmit.disabled = !allRequiredOk;
            btnSubmit.title = allRequiredOk ? '' : 'Lengkapi semua checklist terlebih dahulu';
        }
    }

    // File drop visual
    const fileInput = document.getElementById('file_sk');
    const dropZone = document.getElementById('fileDropZone');
    const fdTitle = document.getElementById('fdTitle');
    fileInput?.addEventListener('change', () => {
        if (fileInput.files.length) {
            dropZone.classList.add('has-file');
            fdTitle.textContent = fileInput.files[0].name;
        } else {
            dropZone.classList.remove('has-file');
            fdTitle.textContent = 'Klik atau tarik file ke sini';
        }
        updateChecklist();
    });

    // Format thousand separator on Bruto input (sync to hidden honor_amount)
    function formatHonorInput(displayInput) {
        const row = displayInput.closest('.penerima-row');
        if (!row) return;
        const hidden = row.querySelector('.honor_amount');

        // Remember caret position for nicer UX
        const before = displayInput.value;
        const caretFromEnd = before.length - (displayInput.selectionStart || 0);

        // Strip non-digits
        const digits = before.replace(/[^\d]/g, '');
        const num = digits ? parseInt(digits, 10) : 0;

        const formatted = digits ? new Intl.NumberFormat('id-ID').format(num) : '';
        displayInput.value = formatted;
        if (hidden) hidden.value = num;

        // Restore caret based on distance-from-end
        const newPos = Math.max(0, formatted.length - caretFromEnd);
        try { displayInput.setSelectionRange(newPos, newPos); } catch (_) {}
    }

    // Event listeners
    document.getElementById('btnAddRow').addEventListener('click', () => addRow());

    document.addEventListener('input', function (e) {
        if (e.target.classList.contains('honor_display')) {
            formatHonorInput(e.target);
            recalcRow(e.target.closest('.penerima-row'));
            recalcGrandTotal();
            updateChecklist();
            return;
        }
        if (e.target.classList.contains('honor_amount') || e.target.classList.contains('pph_amount')) {
            recalcRow(e.target.closest('.penerima-row'));
            recalcGrandTotal();
            updateChecklist();
        }
        if (e.target.id === 'inp_deskripsi' || e.target.id === 'inp_nama_supplier') updateChecklist();
    });

    // Block invalid characters on bruto input
    document.addEventListener('keydown', function (e) {
        if (!e.target.classList?.contains('honor_display')) return;
        const allowed = ['Backspace', 'Delete', 'ArrowLeft', 'ArrowRight', 'Tab', 'Home', 'End'];
        if (e.ctrlKey || e.metaKey || allowed.includes(e.key)) return;
        if (!/^\d$/.test(e.key)) e.preventDefault();
    });

    // Sanitize paste
    document.addEventListener('paste', function (e) {
        if (!e.target.classList?.contains('honor_display')) return;
        e.preventDefault();
        const text = (e.clipboardData || window.clipboardData).getData('text');
        const digits = (text || '').replace(/[^\d]/g, '');
        document.execCommand('insertText', false, digits);
    });

    document.addEventListener('change', function (e) {
        if (e.target.classList.contains('pph_percentage')) {
            recalcRow(e.target.closest('.penerima-row'));
            recalcGrandTotal();
            updateChecklist();
        }
        if (e.target.id === 'dipa_revision_item_id' || e.target.id === 'inp_ppk' || e.target.id === 'inp_bendahara') {
            updateChecklist();
        }
    });

    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.btn-remove-row');
        if (btn) {
            const row = btn.closest('.penerima-row');
            row.classList.add('removing');
            setTimeout(() => {
                row.remove();
                recalcAll();
            }, 280);
        }
    });

    // Select2
    if (window.jQuery && typeof window.jQuery.fn.select2 === 'function') {
        window.jQuery('.select2').select2({
            theme: 'bootstrap-5',
            width: '100%',
            placeholder: function() { return $(this).data('placeholder') || '-- Pilih --'; }
        });
        window.jQuery('.select2').on('change', function() { updateChecklist(); });
    }

    // Init
    addRow();
    updateChecklist();
    </script>
@endsection
