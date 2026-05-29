@extends('layouts.app')
@section('title', 'Detail SPP Honorarium')

@php
    $selectedBudgetItem = $selectedBudgetItem ?? ($sppModel?->dipaRevisionItem ?? null);
    $dipa = $selectedBudgetItem?->revision?->dipa;
    $statusTagihanClass = match ($tagihan->status) {
        'DISETUJUI_PPK', 'DISETUJUI' => 'bg-primary',
        'PROSES_SPP' => 'bg-info',
        'SPP_TERBIT', 'SEBAGIAN_SPP_TERBIT', 'SPP_LENGKAP' => 'bg-success',
        default => 'bg-secondary',
    };
    $statusSppLabel = $sppModel?->status ?? 'Belum Dibuat';
    $statusSppClass = match ($statusSppLabel) {
        'Belum Dibuat' => 'bg-secondary',
        'DRAFT' => 'bg-warning text-dark',
        'Menunggu Verifikasi' => 'bg-info',
        'Disetujui PPK' => 'bg-success',
        'Revisi' => 'bg-danger',
        'APPROVED', 'DISETUJUI_SPP' => 'bg-success',
        default => 'bg-secondary',
    };
    $canEditSpp = !$sppModel || in_array($sppModel->status ?? '', ['DRAFT', 'Revisi', '']);
    $canSubmitToPpk = $sppModel && in_array($sppModel->status ?? '', ['DRAFT', 'Revisi']);
    $ppkVerifikatorNama = $sppModel?->ppkVerifikator?->name ?? null;

    $workflowLockLabel = ($workflowSummary['edit_state'] ?? 'editable') === 'locked' ? 'Terkunci / readonly' : 'Dapat diedit';
    $nominalSpp = (float) ($sppModel->nominal_spp ?? $tagihan->total_netto);
    $documentStatusMeta = [
        'ready' => ['label' => 'Tersedia', 'class' => 'bg-success'],
        'tte' => ['label' => 'TTE Aktif', 'class' => 'bg-success'],
        'missing' => ['label' => 'Belum Ada', 'class' => 'bg-danger'],
        'not_required' => ['label' => 'Tidak Wajib', 'class' => 'bg-secondary'],
    ];
    $oldPpkVerifikator = old('ppk_verifikator_id', $sppModel?->ppk_verifikator_id);

    // Parallel workflow status interpretation
    $ppkStatusLabel = $ppkApproval->status ?? 'Belum diajukan';
    $koordinatorStatusLabel = $koordinatorApproval->status ?? 'Belum diajukan';
    $kasubbagStatusLabel = $kasubbagApproval->status ?? 'Belum diajukan';

    $ppkStatusClass = match($ppkStatusLabel) {
        'APPROVED' => 'text-success',
        'PENDING' => 'text-warning',
        'REVISION', 'REJECTED' => 'text-danger',
        default => 'text-muted'
    };

    $koordinatorStatusClass = match($koordinatorStatusLabel) {
        'APPROVED' => 'text-success',
        'PENDING' => 'text-warning',
        'REVISION', 'REJECTED' => 'text-danger',
        default => 'text-muted'
    };

    $kasubbagStatusClass = match($kasubbagStatusLabel) {
        'APPROVED' => 'text-success',
        'PENDING' => 'text-warning',
        'REVISION', 'REJECTED' => 'text-danger',
        default => 'text-muted'
    };

    // Overall Progress Step
    $progressStep = 1; // 1: Draft, 2: Verifikasi, 3: Final
    if ($sppModel && in_array($sppModel->status, ['Menunggu Verifikasi', 'Revisi'])) {
        $progressStep = 2;
    } elseif ($sppModel && in_array($sppModel->status, ['APPROVED', 'DISETUJUI_SPP', 'Disetujui PPK'])) {
        $progressStep = 3;
        // If workflow is APPROVED
        if (optional($sppModel->workflowInstances->first())->status === 'APPROVED') {
            $progressStep = 4;
        }
    }

    // Hero theme based on progress / revision state
    $isRevisi = $sppModel && $sppModel->status === 'Revisi';
    if ($isRevisi) {
        $heroClass = 'hero-rejected';
    } elseif ($progressStep >= 3) {
        $heroClass = 'hero-approved';
    } elseif ($progressStep == 2) {
        $heroClass = 'hero-pending';
    } else {
        $heroClass = 'hero-draft';
    }

    $sppFullyApproved = $sppModel && in_array($sppModel->status, ['APPROVED', 'DISETUJUI_SPP', 'SPP_TERBIT']);

    // Readiness percentage for progress ring
    $readyCount = collect($readinessChecklist)->where('status', 'ready')->count();
    $readyTotal = max(count($readinessChecklist), 1);
    $readyPct = (int) round(($readyCount / $readyTotal) * 100);

    $verifSteps = [
        ['nama' => 'Pejabat Pembuat Komitmen', 'user' => $ppkUser, 'status' => $ppkStatusLabel, 'class' => $ppkStatusClass, 'icon' => 'bi-person-check', 'tone' => 'primary'],
        ['nama' => 'Koordinator Keuangan', 'user' => $koordinatorUser, 'status' => $koordinatorStatusLabel, 'class' => $koordinatorStatusClass, 'icon' => 'bi-person-gear', 'tone' => 'info'],
        ['nama' => 'Kepala Subbagian Keuangan & TU', 'user' => $kasubbagUser, 'status' => $kasubbagStatusLabel, 'class' => $kasubbagStatusClass, 'icon' => 'bi-person-badge', 'tone' => 'warning'],
    ];

    $timelineSteps = [
        ['label' => 'Draft SPP', 'sub' => 'Penyusunan dokumen', 'icon' => 'bi-pencil-square'],
        ['label' => 'Verifikasi', 'sub' => 'PPK, Koordinator & Kasubbag', 'icon' => 'bi-search'],
        ['label' => 'Disetujui', 'sub' => 'Persetujuan verifikator', 'icon' => 'bi-check2-circle'],
        ['label' => 'Final / TTE', 'sub' => 'Siap lanjut SPM', 'icon' => 'bi-patch-check'],
    ];
@endphp

@push('css')
<style>
    body[data-bs-theme="blue-theme"] .main-content { background: #f6f7fb; }

    /* ============ ANIMATIONS ============ */
    @keyframes shHeroIn { from { opacity: 0; transform: translateY(-14px); } to { opacity: 1; transform: none; } }
    @keyframes shIn     { from { opacity: 0; transform: translateY(16px); } to { opacity: 1; transform: none; } }
    @keyframes shPop    { from { opacity: 0; transform: scale(.92); } to { opacity: 1; transform: scale(1); } }
    @keyframes shBar    { from { width: 0; } }
    @keyframes shPulse  { 0%,100% { box-shadow: 0 0 0 0 rgba(255,255,255,.45); } 50% { box-shadow: 0 0 0 8px rgba(255,255,255,0); } }
    @keyframes shFloat  { 0%,100% { transform: translateY(0) rotate(-12deg); } 50% { transform: translateY(-10px) rotate(-12deg); } }

    /* ============ HERO ============ */
    .sh-hero { position: relative; overflow: hidden; border-radius: 1.25rem; padding: 1.75rem 2rem; color: #fff; margin-bottom: 1.25rem; box-shadow: 0 16px 34px rgba(15,23,42,.18); animation: shHeroIn .55s cubic-bezier(.22,1,.36,1) both; }
    .sh-hero::before, .sh-hero::after { content: ''; position: absolute; border-radius: 50%; }
    .sh-hero::before { right: -90px; top: -90px; width: 280px; height: 280px; background: rgba(255,255,255,.10); }
    .sh-hero::after  { right: 80px; bottom: -80px; width: 180px; height: 180px; background: rgba(255,255,255,.07); }
    .sh-hero > * { position: relative; z-index: 1; }
    .hero-draft     { background: linear-gradient(135deg, #475569 0%, #334155 100%); }
    .hero-pending   { background: linear-gradient(135deg, #6366f1 0%, #4f46e5 50%, #2563eb 100%); }
    .hero-approved  { background: linear-gradient(135deg, #10b981 0%, #059669 50%, #047857 100%); }
    .hero-rejected  { background: linear-gradient(135deg, #f43f5e 0%, #e11d48 50%, #be123c 100%); }
    .hero-illust { position: absolute; right: 1.75rem; top: 50%; transform: translateY(-50%) rotate(-12deg); font-size: 8rem; opacity: .12; z-index: 0; animation: shFloat 6s ease-in-out infinite; }
    .hero-badge { display: inline-flex; align-items: center; gap: .35rem; background: rgba(255,255,255,.18); backdrop-filter: blur(8px); border: 1px solid rgba(255,255,255,.28); font-weight: 700; font-size: .74rem; padding: .35rem .85rem; border-radius: 999px; color: #fff; }
    .hero-badge.solid { background: rgba(255,255,255,.92); color: #0f172a; border-color: transparent; }
    .hero-title { font-weight: 800; font-size: 1.45rem; color: #fff; margin: .55rem 0 .35rem; letter-spacing: -.01em; }
    .hero-eyebrow { font-size: .72rem; font-weight: 700; letter-spacing: .12em; text-transform: uppercase; color: rgba(255,255,255,.8); }
    .hero-meta { display: flex; gap: .65rem 1.6rem; flex-wrap: wrap; margin-top: .85rem; font-size: .8rem; }
    .hero-meta .m-item { display: inline-flex; align-items: center; gap: .45rem; opacity: .95; color: #fff; }
    .hero-actions .btn { border-radius: 999px; font-weight: 700; }
    .hero-actions .btn-light { color: #1e293b; }

    /* ============ STAT CARDS ============ */
    .sh-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1.25rem; }
    .sh-stat { position: relative; overflow: hidden; background: #fff; border: 1px solid #eef0f4; border-radius: 1.1rem; padding: 1.1rem 1.25rem; box-shadow: 0 6px 16px rgba(15,23,42,.04); animation: shIn .5s cubic-bezier(.22,1,.36,1) both; animation-delay: calc(.06s * var(--d, 0)); transition: transform .25s ease, box-shadow .25s ease; }
    .sh-stat:hover { transform: translateY(-4px); box-shadow: 0 14px 28px rgba(15,23,42,.09); }
    .sh-stat .s-ic { width: 42px; height: 42px; border-radius: .8rem; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; margin-bottom: .65rem; }
    .sh-stat .s-label { font-size: .68rem; font-weight: 800; text-transform: uppercase; letter-spacing: .06em; color: #94a3b8; }
    .sh-stat .s-value { font-size: 1.35rem; font-weight: 800; color: #0f172a; line-height: 1.2; letter-spacing: -.01em; font-variant-numeric: tabular-nums; }
    .sh-stat .s-foot { font-size: .72rem; color: #94a3b8; margin-top: .15rem; }
    .s-ic.t-amber { background: rgba(245,158,11,.13); color: #d97706; }
    .s-ic.t-red   { background: rgba(244,63,94,.12); color: #e11d48; }
    .s-ic.t-green { background: rgba(16,185,129,.13); color: #059669; }
    .s-ic.t-indigo{ background: rgba(99,102,241,.13); color: #4f46e5; }
    .sh-stat .s-deco { position: absolute; right: -14px; bottom: -14px; font-size: 4.5rem; opacity: .05; }

    /* ============ CARD SHELL ============ */
    .sh-card { background: #fff; border: 1px solid #eef0f4; border-radius: 1.25rem; box-shadow: 0 6px 18px rgba(15,23,42,.05); overflow: hidden; margin-bottom: 1.25rem; animation: shIn .55s cubic-bezier(.22,1,.36,1) both; animation-delay: calc(.05s * var(--d, 0)); }
    .sh-card-head { display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: 1.1rem 1.4rem; border-bottom: 1px solid #f1f3f7; }
    .sh-head-left { display: flex; align-items: center; gap: .85rem; }
    .sh-ic { width: 42px; height: 42px; border-radius: .85rem; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; color: #fff; flex-shrink: 0; }
    .sh-ic-primary { background: linear-gradient(135deg, #818cf8, #6366f1); box-shadow: 0 8px 18px rgba(99,102,241,.3); }
    .sh-ic-info    { background: linear-gradient(135deg, #38bdf8, #0ea5e9); box-shadow: 0 8px 18px rgba(14,165,233,.3); }
    .sh-ic-success { background: linear-gradient(135deg, #34d399, #10b981); box-shadow: 0 8px 18px rgba(16,185,129,.3); }
    .sh-ic-amber   { background: linear-gradient(135deg, #fbbf24, #f59e0b); box-shadow: 0 8px 18px rgba(245,158,11,.3); }
    .sh-ic-slate   { background: linear-gradient(135deg, #94a3b8, #64748b); box-shadow: 0 8px 18px rgba(100,116,139,.3); }
    .sh-title { font-size: 1rem; font-weight: 800; color: #0f172a; margin: 0; letter-spacing: -.01em; }
    .sh-sub { font-size: .76rem; color: #64748b; margin: .12rem 0 0; }
    .sh-pill { display: inline-flex; align-items: center; gap: .35rem; font-size: .72rem; font-weight: 700; padding: .35rem .8rem; border-radius: 999px; white-space: nowrap; }
    .sh-pill-info { background: rgba(14,165,233,.12); color: #0369a1; }
    .sh-pill-success { background: rgba(16,185,129,.14); color: #047857; }
    .sh-pill-danger { background: rgba(244,63,94,.13); color: #be123c; }
    .sh-pill-warn { background: rgba(245,158,11,.14); color: #b45309; }
    .sh-pill-secondary { background: #eef1f6; color: #475569; }
    .sh-card-body { padding: 1.4rem; }

    /* ============ TIMELINE ============ */
    .sh-timeline { display: flex; align-items: flex-start; justify-content: space-between; position: relative; padding: .5rem 0 .25rem; }
    .sh-timeline .tl-track { position: absolute; top: 22px; left: 6%; right: 6%; height: 4px; background: #e2e8f0; border-radius: 999px; z-index: 0; }
    .sh-timeline .tl-fill { position: absolute; top: 22px; left: 6%; height: 4px; background: linear-gradient(90deg, #10b981, #34d399); border-radius: 999px; z-index: 1; animation: shBar 1s ease both; }
    .tl-step { position: relative; z-index: 2; flex: 1; display: flex; flex-direction: column; align-items: center; text-align: center; }
    .tl-dot { width: 46px; height: 46px; border-radius: 999px; background: #fff; border: 3px solid #e2e8f0; display: flex; align-items: center; justify-content: center; font-size: 1.15rem; color: #94a3b8; margin-bottom: .6rem; transition: all .3s ease; }
    .tl-step.passed .tl-dot { border-color: #10b981; background: #10b981; color: #fff; }
    .tl-step.active .tl-dot { border-color: #3b82f6; color: #3b82f6; background: #fff; animation: shPulse 2s infinite; box-shadow: 0 0 0 4px rgba(59,130,246,.18); }
    .tl-step.revision .tl-dot { border-color: #ef4444; color: #ef4444; background: #fee2e2; }
    .tl-label { font-weight: 700; color: #1e293b; font-size: .82rem; }
    .tl-sub { font-size: .7rem; color: #94a3b8; margin-top: .15rem; max-width: 140px; line-height: 1.3; }

    /* ============ READINESS RING ============ */
    .sh-ring { --pct: 0; width: 116px; height: 116px; border-radius: 50%; background: conic-gradient(#10b981 calc(var(--pct) * 1%), #e9edf3 0); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .sh-ring.warn { background: conic-gradient(#f59e0b calc(var(--pct) * 1%), #e9edf3 0); }
    .sh-ring-inner { width: 86px; height: 86px; border-radius: 50%; background: #fff; display: flex; flex-direction: column; align-items: center; justify-content: center; box-shadow: inset 0 0 0 1px #eef0f4; }
    .sh-ring-inner .rp { font-size: 1.5rem; font-weight: 800; color: #0f172a; line-height: 1; }
    .sh-ring-inner .rl { font-size: .62rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: #94a3b8; margin-top: .15rem; }

    .sh-check { display: flex; align-items: flex-start; gap: .7rem; padding: .55rem .7rem; border-radius: .7rem; transition: background .2s ease; }
    .sh-check:hover { background: #f8fafc; }
    .sh-check .ck-ic { width: 24px; height: 24px; border-radius: 999px; display: flex; align-items: center; justify-content: center; font-size: .72rem; flex-shrink: 0; }
    .ck-ready { background: rgba(16,185,129,.13); color: #059669; }
    .ck-miss  { background: rgba(244,63,94,.12); color: #e11d48; }
    .sh-check .ck-text { font-size: .85rem; color: #334155; font-weight: 500; }

    /* ============ VERIFIER ============ */
    .sh-verif { display: flex; align-items: center; gap: .85rem; padding: .85rem; border: 1px solid #eef0f4; border-radius: .9rem; background: #f8fafc; transition: all .2s ease; }
    .sh-verif:hover { transform: translateX(3px); border-color: #dbe3ef; }
    .sh-verif + .sh-verif { margin-top: .65rem; }
    .sh-verif .v-ava { width: 42px; height: 42px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.15rem; flex-shrink: 0; }
    .v-ava.primary { background: rgba(99,102,241,.12); color: #4f46e5; }
    .v-ava.info { background: rgba(14,165,233,.12); color: #0284c7; }
    .v-ava.warning { background: rgba(245,158,11,.13); color: #d97706; }
    .sh-verif .v-name { font-weight: 700; color: #1e293b; font-size: .85rem; }
    .sh-verif .v-role { font-size: .72rem; color: #64748b; }
    .sh-verif .v-nip { font-size: .68rem; color: #94a3b8; font-family: ui-monospace, monospace; }
    .sh-verif .v-status { font-size: .72rem; font-weight: 800; padding: .25rem .65rem; border-radius: 999px; white-space: nowrap; }
    .v-status.text-success { background: rgba(16,185,129,.14); }
    .v-status.text-warning { background: rgba(245,158,11,.16); color: #b45309 !important; }
    .v-status.text-danger { background: rgba(244,63,94,.13); }
    .v-status.text-muted { background: #eef1f6; }

    /* ============ INFO GRID ============ */
    .sh-info-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: .7rem; }
    .sh-info-cell { background: #f8fafc; border: 1px solid #eef0f4; border-radius: .8rem; padding: .75rem .9rem; border-left: 3px solid #cbd5e1; }
    .sh-info-cell.ic-primary { border-left-color: #6366f1; }
    .sh-info-cell.ic-info { border-left-color: #0ea5e9; }
    .sh-info-cell.ic-green { border-left-color: #10b981; }
    .sh-info-cell.ic-red { border-left-color: #f43f5e; }
    .sh-info-cell.ic-amber { border-left-color: #f59e0b; }
    .sh-info-cell .i-label { display: inline-flex; align-items: center; gap: .3rem; font-size: .65rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: #94a3b8; margin-bottom: .2rem; }
    .sh-info-cell .i-value { display: block; font-size: .92rem; font-weight: 700; color: #1e293b; word-break: break-word; line-height: 1.35; }
    .sh-info-cell .i-sub { display: block; font-size: .72rem; color: #94a3b8; margin-top: .1rem; }

    /* ============ TABLE ============ */
    .sh-table { width: 100%; border-collapse: separate; border-spacing: 0; }
    .sh-table thead th { font-size: .68rem; font-weight: 800; text-transform: uppercase; letter-spacing: .05em; color: #94a3b8; padding: .65rem .8rem; border-bottom: 2px solid #eef0f4; background: #f8fafc; }
    .sh-table tbody td { padding: .7rem .8rem; border-bottom: 1px solid #f1f3f7; font-size: .85rem; vertical-align: middle; }
    .sh-table tbody tr { transition: background .15s ease; }
    .sh-table tbody tr:hover { background: #f8fafc; }
    .sh-table tbody tr:last-child td { border-bottom: 0; }
    .sh-table .num { font-variant-numeric: tabular-nums; }

    /* ============ DOC ROW ============ */
    .sh-doc { display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: .8rem 0; border-bottom: 1px dashed #eef0f4; }
    .sh-doc:last-child { border-bottom: 0; }
    .sh-doc .d-left { display: flex; align-items: center; gap: .7rem; min-width: 0; }
    .sh-doc .d-ic { width: 36px; height: 36px; border-radius: .65rem; display: flex; align-items: center; justify-content: center; font-size: 1rem; flex-shrink: 0; background: #eef1f6; color: #64748b; }
    .sh-doc.is-ready .d-ic { background: rgba(16,185,129,.13); color: #059669; }
    .sh-doc .d-name { font-weight: 700; color: #1e293b; font-size: .85rem; }
    .sh-doc .d-badge { font-size: .64rem; font-weight: 800; padding: .15rem .55rem; border-radius: 999px; text-transform: uppercase; letter-spacing: .03em; }

    /* ============ DRAFT HIGHLIGHT ============ */
    .sh-draft-card { border: 1px solid rgba(99,102,241,.22); }
    .sh-draft-head { background: linear-gradient(135deg, #6366f1, #4f46e5); color: #fff; padding: 1rem 1.4rem; display: flex; align-items: center; justify-content: space-between; }
    .sh-draft-head h6 { margin: 0; font-weight: 800; }
    .sh-kv { display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: .6rem 0; border-bottom: 1px dashed #eef0f4; }
    .sh-kv:last-child { border-bottom: 0; }
    .sh-kv .k { font-size: .8rem; color: #64748b; font-weight: 600; }
    .sh-kv .v { font-size: .9rem; color: #1e293b; font-weight: 700; text-align: right; }

    /* ============ ACTIVITY ============ */
    .sh-act { position: relative; padding-left: 1.4rem; padding-bottom: 1.1rem; }
    .sh-act:last-child { padding-bottom: 0; }
    .sh-act::before { content: ''; position: absolute; left: 3px; top: 6px; width: 9px; height: 9px; border-radius: 999px; background: #cbd5e1; z-index: 1; }
    .sh-act::after { content: ''; position: absolute; left: 7px; top: 16px; bottom: -4px; width: 2px; background: #eef0f4; }
    .sh-act:last-child::after { display: none; }
    .sh-act.is-active::before { background: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,.2); }
    .sh-act .a-title { font-weight: 700; color: #1e293b; font-size: .85rem; }
    .sh-act .a-meta { font-size: .72rem; color: #94a3b8; margin-top: .1rem; }
    .sh-act .a-note { font-size: .76rem; color: #64748b; font-style: italic; margin-top: .25rem; background: #f8fafc; border-radius: .5rem; padding: .35rem .55rem; }

    /* ============ MODAL ============ */
    .sh-modal .modal-content { border: 0; border-radius: 1.25rem; overflow: hidden; box-shadow: 0 30px 70px rgba(15,23,42,.28); }
    .sh-modal-header { position: relative; overflow: hidden; padding: 1.5rem 1.6rem; color: #fff; }
    .sh-modal-header.h-primary { background: linear-gradient(135deg, #6366f1 0%, #4f46e5 60%, #2563eb 100%); }
    .sh-modal-header.h-success { background: linear-gradient(135deg, #10b981 0%, #059669 100%); }
    .sh-modal-header::after { content: ''; position: absolute; right: -40px; top: -40px; width: 160px; height: 160px; border-radius: 50%; background: rgba(255,255,255,.1); }
    .sh-modal-header .mh-ic { width: 46px; height: 46px; border-radius: .9rem; background: rgba(255,255,255,.2); display: flex; align-items: center; justify-content: center; font-size: 1.35rem; }
    .sh-modal-header h5 { font-weight: 800; margin: 0; }
    .sh-modal-header p { margin: 0; font-size: .8rem; opacity: .9; }
    .sh-modal .modal-body { padding: 1.5rem 1.6rem; background: #f8fafc; }
    .sh-modal-block { background: #fff; border: 1px solid #eef0f4; border-radius: 1rem; padding: 1.2rem 1.3rem; margin-bottom: 1.1rem; }
    .sh-modal-block:last-child { margin-bottom: 0; }
    .sh-modal-block .mb-head { display: flex; align-items: center; gap: .6rem; margin-bottom: 1rem; }
    .sh-modal-block .mb-num { width: 26px; height: 26px; border-radius: 999px; background: linear-gradient(135deg, #818cf8, #6366f1); color: #fff; font-weight: 800; font-size: .82rem; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .sh-modal-block .mb-title { font-weight: 800; color: #0f172a; font-size: .92rem; }
    .sh-modal label.form-label { font-weight: 700; font-size: .8rem; color: #334155; }
    .sh-modal .form-control, .sh-modal .form-select { border-radius: .7rem; border-color: #e2e8f0; }
    .sh-modal .form-control:focus, .sh-modal .form-select:focus { border-color: #818cf8; box-shadow: 0 0 0 .2rem rgba(99,102,241,.15); }
    .sh-modal .modal-footer { background: #fff; border-top: 1px solid #f1f3f7; padding: 1rem 1.6rem; }
    .sh-modal .modal-footer .btn { border-radius: 999px; font-weight: 700; }
    .sh-verifchip { display: flex; align-items: center; gap: .6rem; padding: .65rem .8rem; border: 1px dashed #e2e8f0; border-radius: .8rem; background: #f8fafc; }
    .sh-verifchip .vc-ic { width: 34px; height: 34px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1rem; flex-shrink: 0; }
    .sh-verifchip .vc-name { font-weight: 700; font-size: .82rem; color: #1e293b; line-height: 1.2; }
    .sh-verifchip .vc-role { font-size: .68rem; color: #94a3b8; }

    .sh-upload-zone { border: 2px dashed #cbd5e1; border-radius: 1rem; padding: 1.4rem; text-align: center; background: #fff; transition: all .2s ease; }
    .sh-upload-zone:hover { border-color: #6366f1; background: #f8f9ff; }

    @media (prefers-reduced-motion: reduce) {
        .sh-hero, .sh-stat, .sh-card, .hero-illust, .tl-fill, .tl-step.active .tl-dot { animation: none !important; }
    }
</style>
@endpush

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <x-page-title title="Workspace Operator BLU" subtitle="Detail & Persiapan Draft SPP Honorarium" />
        <a href="{{ route('spps.honor.index') }}" class="btn btn-outline-secondary btn-sm rounded-pill px-3"><i class="bi bi-arrow-left me-1"></i> Kembali</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm alert-dismissible fade show rounded-3">
            <div class="d-flex align-items-start gap-2"><i class="bi bi-check-circle-fill fs-5"></i><div>{{ session('success') }}</div></div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger border-0 shadow-sm alert-dismissible fade show rounded-3">
            <div class="d-flex align-items-start gap-2"><i class="bi bi-exclamation-triangle-fill fs-5"></i><div>{{ session('error') }}</div></div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger border-0 shadow-sm alert-dismissible fade show rounded-3">
            <div class="d-flex align-items-start gap-2">
                <i class="bi bi-exclamation-octagon-fill fs-5"></i>
                <div>
                    <div class="fw-semibold mb-1">Masih ada data yang perlu diperbaiki.</div>
                    <ul class="mb-0 ps-3">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- ============ HERO ============ --}}
    <div class="sh-hero {{ $heroClass }}">
        <i class="bi bi-cash-coin hero-illust"></i>
        <div class="d-flex flex-column flex-xl-row justify-content-between gap-4">
            <div class="flex-grow-1">
                <span class="hero-eyebrow"><i class="bi bi-receipt me-1"></i> SPP Honorarium</span>
                <h3 class="hero-title">{{ $tagihan->deskripsi ?? 'Pembuatan SPP Honorarium' }}</h3>
                <div class="d-flex flex-wrap gap-2 align-items-center">
                    <span class="hero-badge solid"><i class="bi bi-hash"></i> {{ $tagihan->nomor_tagihan ?? '-' }}</span>
                    <span class="hero-badge"><i class="bi bi-file-earmark-text"></i> Tagihan: {{ str_replace('_', ' ', $tagihan->status) }}</span>
                    <span class="hero-badge"><i class="bi bi-file-earmark-check"></i> SPP: {{ $statusSppLabel }}</span>
                    @if($isRevisi)
                        <span class="hero-badge"><i class="bi bi-exclamation-circle"></i> Butuh Perbaikan</span>
                    @endif
                </div>
                <div class="hero-meta">
                    <span class="m-item"><i class="bi bi-people"></i> {{ $tagihan->detailHonorarium->count() }} Penerima</span>
                    <span class="m-item"><i class="bi bi-calendar3"></i> Diajukan {{ $tagihan->created_at->translatedFormat('d M Y') }}</span>
                    <span class="m-item"><i class="bi bi-person-badge"></i> PPK: {{ $ppkUser?->name ?? 'Belum ditentukan' }}</span>
                </div>
            </div>

            <div class="hero-actions d-flex flex-column gap-2" style="min-width: 220px;">
                @if($sppModel)
                    <a href="{{ route('spps.cetak-pdf', $sppModel->id) }}" target="_blank" class="btn btn-light shadow-sm"><i class="bi bi-file-earmark-pdf me-1 text-danger"></i> Cetak PDF SPP</a>
                @endif

                @if(!$sppFullyApproved)
                    <button type="button" class="btn btn-light shadow-sm" data-bs-toggle="modal" data-bs-target="#modalSppHonor" {{ $canEditSpp ? '' : 'disabled' }}>
                        <i class="bi bi-pencil-square me-1 text-primary"></i> {{ $sppModel ? 'Edit Draft SPP' : 'Buat Draft Baru' }}
                    </button>
                @endif

                @if($sppModel)
                    @if($sppFullyApproved)
                        <div class="hero-badge justify-content-center"><i class="bi bi-patch-check-fill"></i> SPP final ber-TTE otomatis</div>
                        @hasanyrole('Super Admin|Operator BLU')
                            <a href="{{ route('spms.honor.detail', $sppModel->id) }}" class="btn btn-success shadow-sm">
                                <i class="bi bi-arrow-right-circle me-1"></i> {{ $sppModel->spm ? 'Lanjutkan SPM' : 'Lanjut Buat SPM' }}
                            </a>
                        @endhasanyrole
                    @elseif($canSubmitToPpk && $isReadyToSubmit)
                        <form action="{{ route('spps.honor.submit', $tagihan->id) }}" method="POST" onsubmit="return confirm('Ajukan SPP ini untuk verifikasi PPK, Koordinator dan Kasubbag secara paralel?')">
                            @csrf
                            <button type="submit" class="btn btn-success shadow-sm w-100"><i class="bi bi-send me-1"></i> Ajukan Verifikasi</button>
                        </form>
                    @else
                        <button type="button" class="btn btn-success shadow-sm w-100" disabled><i class="bi bi-send me-1"></i> Ajukan Verifikasi</button>
                        <div class="hero-badge justify-content-center"><i class="bi bi-hourglass-split"></i> Lengkapi checklist dahulu</div>
                    @endif
                @endif
            </div>
        </div>
    </div>

    {{-- ============ STAT CARDS ============ --}}
    <div class="sh-stats">
        <div class="sh-stat" style="--d:1;">
            <div class="s-ic t-amber"><i class="bi bi-cash-stack"></i></div>
            <div class="s-label">Nilai Bruto (Honor)</div>
            <div class="s-value">Rp {{ number_format($tagihan->total_bruto, 0, ',', '.') }}</div>
            <i class="bi bi-cash-stack s-deco"></i>
        </div>
        <div class="sh-stat" style="--d:2;">
            <div class="s-ic t-red"><i class="bi bi-dash-circle"></i></div>
            <div class="s-label">Total Potongan (PPh 21)</div>
            <div class="s-value text-danger">Rp {{ number_format($tagihan->total_potongan, 0, ',', '.') }}</div>
            <i class="bi bi-percent s-deco"></i>
        </div>
        <div class="sh-stat" style="--d:3;">
            <div class="s-ic t-green"><i class="bi bi-wallet2"></i></div>
            <div class="s-label">Nilai Netto (Dibayar)</div>
            <div class="s-value text-success">Rp {{ number_format($tagihan->total_netto, 0, ',', '.') }}</div>
            <i class="bi bi-wallet2 s-deco"></i>
        </div>
        <div class="sh-stat" style="--d:4;">
            <div class="s-ic t-indigo"><i class="bi bi-people-fill"></i></div>
            <div class="s-label">Total Penerima</div>
            <div class="s-value">{{ $tagihan->detailHonorarium->count() }} <span class="fs-6 fw-normal text-muted">Orang</span></div>
            <div class="s-foot">Nilai SPP: Rp {{ number_format($nominalSpp, 0, ',', '.') }}</div>
            <i class="bi bi-people s-deco"></i>
        </div>
    </div>

    {{-- ============ PROGRESS TIMELINE ============ --}}
    <div class="sh-card" style="--d:2;">
        <div class="sh-card-head">
            <div class="sh-head-left">
                <span class="sh-ic sh-ic-info"><i class="bi bi-signpost-2-fill"></i></span>
                <div><h5 class="sh-title">Progress Penerbitan SPP</h5><p class="sh-sub">Lacak perjalanan dokumen dari draft hingga final.</p></div>
            </div>
            <span class="sh-pill {{ $isRevisi ? 'sh-pill-danger' : ($progressStep >= 3 ? 'sh-pill-success' : 'sh-pill-info') }}">
                Tahap {{ min($progressStep, 4) }} dari 4
            </span>
        </div>
        <div class="sh-card-body">
            <div class="sh-timeline">
                <div class="tl-track"></div>
                <div class="tl-fill" style="width: {{ $progressStep <= 1 ? 0 : (min($progressStep,4)-1)/3*88 }}%;"></div>
                @foreach($timelineSteps as $si => $step)
                    @php
                        $stepNo = $si + 1;
                        $cls = '';
                        if ($isRevisi && $stepNo === 2) { $cls = 'revision'; }
                        elseif ($stepNo < $progressStep) { $cls = 'passed'; }
                        elseif ($stepNo == $progressStep) { $cls = 'active'; }
                    @endphp
                    <div class="tl-step {{ $cls }}">
                        <div class="tl-dot">
                            @if($cls === 'passed')<i class="bi bi-check-lg"></i>@elseif($cls === 'revision')<i class="bi bi-exclamation-lg"></i>@else<i class="bi {{ $step['icon'] }}"></i>@endif
                        </div>
                        <div class="tl-label">{{ $step['label'] }}</div>
                        <div class="tl-sub">{{ $step['sub'] }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- ============ READINESS + VERIFIKATOR ============ --}}
    <div class="row g-4 mb-1">
        <div class="col-xl-5">
            <div class="sh-card h-100" style="--d:3;">
                <div class="sh-card-head">
                    <div class="sh-head-left">
                        <span class="sh-ic sh-ic-success"><i class="bi bi-clipboard2-check-fill"></i></span>
                        <div><h5 class="sh-title">Checklist Kesiapan Draft</h5><p class="sh-sub">Syarat sebelum SPP diajukan.</p></div>
                    </div>
                    <span class="badge {{ $readinessStatus['class'] ?? 'bg-secondary' }} rounded-pill px-3 py-2">{{ $readinessStatus['label'] ?? 'N/A' }}</span>
                </div>
                <div class="sh-card-body">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="sh-ring {{ $readyPct < 100 ? 'warn' : '' }}" style="--pct: {{ $readyPct }};">
                            <div class="sh-ring-inner">
                                <span class="rp">{{ $readyPct }}%</span>
                                <span class="rl">Siap</span>
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <div class="fw-bold text-dark">{{ $readyCount }} dari {{ $readyTotal }} syarat terpenuhi</div>
                            <div class="small text-muted">{{ $readinessStatus['message'] ?? 'Lengkapi seluruh checklist untuk dapat mengajukan SPP.' }}</div>
                        </div>
                    </div>
                    <div>
                        @foreach($readinessChecklist as $item)
                            <div class="sh-check">
                                <span class="ck-ic {{ $item['status'] === 'ready' ? 'ck-ready' : 'ck-miss' }}"><i class="bi {{ $item['status'] === 'ready' ? 'bi-check-lg' : 'bi-x-lg' }}"></i></span>
                                <span class="ck-text">{{ $item['label'] }}</span>
                            </div>
                        @endforeach
                    </div>
                    @if(($readinessStatus['label'] ?? null) === 'Belum Lengkap' && $readinessIssues->isNotEmpty())
                        <div class="alert alert-warning mt-3 mb-0 p-2 py-2 small border-0 rounded-3">
                            <ul class="mb-0 ps-3">@foreach($readinessIssues as $issue)<li>{{ $issue }}</li>@endforeach</ul>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-xl-7">
            <div class="sh-card h-100" style="--d:4;">
                <div class="sh-card-head">
                    <div class="sh-head-left">
                        <span class="sh-ic sh-ic-primary"><i class="bi bi-people-fill"></i></span>
                        <div><h5 class="sh-title">Status Verifikator SPP</h5><p class="sh-sub">Verifikasi paralel oleh tiga pejabat.</p></div>
                    </div>
                </div>
                <div class="sh-card-body">
                    @foreach($verifSteps as $v)
                        <div class="sh-verif">
                            <span class="v-ava {{ $v['tone'] }}"><i class="bi {{ $v['icon'] }}"></i></span>
                            <div class="flex-grow-1 min-w-0">
                                <div class="v-role">{{ $v['nama'] }}</div>
                                <div class="v-name">{{ $v['user']?->name ?? 'Belum Ditentukan' }}</div>
                                @if($v['user']?->nip)<div class="v-nip">NIP: {{ $v['user']->nip }}</div>@endif
                            </div>
                            <span class="v-status {{ $v['class'] }}">{{ $v['status'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        {{-- ============ LEFT: SOURCE DATA ============ --}}
        <div class="col-xl-7">
            {{-- Rincian Tagihan --}}
            <div class="sh-card" style="--d:5;">
                <div class="sh-card-head">
                    <div class="sh-head-left">
                        <span class="sh-ic sh-ic-primary"><i class="bi bi-receipt"></i></span>
                        <div><h5 class="sh-title">Rincian Tagihan Honorarium</h5><p class="sh-sub">Sumber data nilai SPP.</p></div>
                    </div>
                    <span class="sh-pill sh-pill-secondary">#1</span>
                </div>
                <div class="sh-card-body">
                    <div class="sh-info-grid">
                        <div class="sh-info-cell ic-primary"><span class="i-label"><i class="bi bi-hash"></i>Nomor Tagihan</span><span class="i-value">{{ $tagihan->nomor_tagihan ?? '-' }}</span></div>
                        <div class="sh-info-cell ic-info" style="grid-column: span 2;"><span class="i-label"><i class="bi bi-card-text"></i>Uraian / Deskripsi</span><span class="i-value">{{ $tagihan->deskripsi ?? '-' }}</span></div>
                        <div class="sh-info-cell ic-amber"><span class="i-label"><i class="bi bi-cash-stack"></i>Nilai Bruto</span><span class="i-value">Rp {{ number_format($tagihan->total_bruto, 0, ',', '.') }}</span></div>
                        <div class="sh-info-cell ic-red"><span class="i-label"><i class="bi bi-percent"></i>Total Potongan PPh 21</span><span class="i-value text-danger">Rp {{ number_format($tagihan->total_potongan, 0, ',', '.') }}</span></div>
                        <div class="sh-info-cell ic-green"><span class="i-label"><i class="bi bi-wallet2"></i>Nilai Netto</span><span class="i-value text-success">Rp {{ number_format($tagihan->total_netto, 0, ',', '.') }}</span></div>
                    </div>
                </div>
            </div>

            {{-- Daftar Penerima --}}
            <div class="sh-card" style="--d:6;">
                <div class="sh-card-head">
                    <div class="sh-head-left">
                        <span class="sh-ic sh-ic-info"><i class="bi bi-people"></i></span>
                        <div><h5 class="sh-title">Daftar Penerima Honorarium</h5><p class="sh-sub">Rincian per penerima.</p></div>
                    </div>
                    <span class="sh-pill sh-pill-info">{{ $tagihan->detailHonorarium->count() }} Orang</span>
                </div>
                <div class="sh-card-body p-0">
                    <div class="table-responsive">
                        <table class="sh-table">
                            <thead>
                                <tr>
                                    <th class="text-center ps-4" style="width:5%">No</th>
                                    <th>Penerima</th>
                                    <th class="text-end">Bruto</th>
                                    <th class="text-end">PPh</th>
                                    <th class="text-end pe-4">Netto</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($tagihan->detailHonorarium as $idx => $detail)
                                    <tr>
                                        <td class="text-center ps-4 text-muted">{{ $idx + 1 }}</td>
                                        <td>
                                            <div class="fw-bold text-dark">{{ $detail->nama_personel }}</div>
                                            <div class="text-muted small font-monospace">{{ $detail->nrp_nip ?? '-' }}</div>
                                        </td>
                                        <td class="text-end num fw-semibold">Rp {{ number_format($detail->nilai_honor, 0, ',', '.') }}</td>
                                        <td class="text-end num text-danger small">Rp {{ number_format($detail->pph, 0, ',', '.') }}</td>
                                        <td class="text-end num fw-bold text-success pe-4">Rp {{ number_format($detail->nilai_honor - $detail->pph, 0, ',', '.') }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-center text-muted py-4"><i class="bi bi-inboxes me-1"></i> Belum ada rincian penerima</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Dokumen Lampiran --}}
            <div class="sh-card" style="--d:7;">
                <div class="sh-card-head">
                    <div class="sh-head-left">
                        <span class="sh-ic sh-ic-amber"><i class="bi bi-paperclip"></i></span>
                        <div><h5 class="sh-title">Dokumen Lampiran Penunjang</h5><p class="sh-sub">Berkas pendukung SPP.</p></div>
                    </div>
                </div>
                <div class="sh-card-body">
                    @foreach($documentStatuses as $document)
                        @php $docMeta = $documentStatusMeta[$document['status']] ?? $documentStatusMeta['missing']; @endphp
                        <div class="sh-doc {{ in_array($document['status'], ['ready','tte']) ? 'is-ready' : '' }}">
                            <div class="d-left">
                                <span class="d-ic"><i class="bi {{ in_array($document['status'], ['ready','tte']) ? 'bi-file-earmark-check' : 'bi-file-earmark' }}"></i></span>
                                <div class="min-w-0">
                                    <div class="d-name">
                                        {{ $document['label'] }}
                                        @if($document['status'] === 'tte')<i class="bi bi-patch-check-fill text-success ms-1" title="Dokumen ber-TTE QR digital"></i>@endif
                                    </div>
                                    <span class="d-badge {{ $docMeta['class'] }} text-white">{{ $docMeta['label'] }}</span>
                                </div>
                            </div>
                            <div>
                                @if($document['is_available'])
                                    <a href="{{ $document['url'] ?? \Illuminate\Support\Facades\Storage::url($document['path']) }}" target="_blank" class="btn btn-sm btn-outline-primary rounded-pill px-3"><i class="bi bi-eye me-1"></i> Lihat</a>
                                @else
                                    <span class="text-muted small">—</span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- ============ RIGHT: RESULT & VALIDATION ============ --}}
        <div class="col-xl-5">
            <div class="sticky-top" style="top: 1.5rem; z-index: 1;">
                {{-- Validasi Anggaran --}}
                <div class="sh-card" style="--d:5;">
                    <div class="sh-card-head">
                        <div class="sh-head-left">
                            <span class="sh-ic sh-ic-success"><i class="bi bi-bank2"></i></span>
                            <div><h5 class="sh-title">Validasi Anggaran</h5><p class="sh-sub">Mata anggaran (COA) terkait.</p></div>
                        </div>
                    </div>
                    <div class="sh-card-body">
                        <div class="sh-info-cell ic-info mb-3">
                            <span class="i-label"><i class="bi bi-journal-text"></i>DIPA / Tahun / Revisi</span>
                            <span class="i-value">{{ $masterDipa?->nomor_dipa ?? '-' }}</span>
                            <span class="i-sub">Thn {{ $masterDipa?->tahun_anggaran ?? '-' }} &bull; Revisi {{ $riwayatRevisiDipa?->nomor_revisi ?? $masterDipa?->revisi_aktif_ke ?? '-' }}</span>
                        </div>
                        <div class="p-3 rounded-3" style="background: linear-gradient(135deg, rgba(16,185,129,.06), rgba(16,185,129,.02)); border: 1px solid rgba(16,185,129,.22);">
                            <div class="i-label" style="color:#059669;"><i class="bi bi-upc-scan"></i> Item DIPA / Akun Terpakai</div>
                            <div class="fw-bold fs-6 text-dark mt-1">@if($selectedBudgetItem?->coa){{ $selectedBudgetItem->coa->kode_mak_lengkap }}@else<span class="text-danger">Belum Tersedia</span>@endif</div>
                            <div class="text-muted small lh-sm mt-1">{{ $selectedBudgetItem?->coa?->nama_akun ?? 'Tagihan Honorarium belum terkait dengan Item DIPA.' }}</div>
                        </div>
                    </div>
                </div>

                {{-- Ringkasan Draft SPP --}}
                <div class="sh-card sh-draft-card" style="--d:6;">
                    <div class="sh-draft-head">
                        <h6><i class="bi bi-file-earmark-check me-2"></i> Ringkasan Draft SPP</h6>
                        <span class="badge {{ $statusSppClass }} px-3 py-2 rounded-pill">{{ $statusSppLabel }}</span>
                    </div>
                    <div class="sh-card-body">
                        <div class="sh-kv"><span class="k"><i class="bi bi-hash me-1 text-muted"></i>Nomor SPP</span><span class="v">{{ $sppModel->nomor_spp ?? '-' }}</span></div>
                        <div class="sh-kv"><span class="k"><i class="bi bi-calendar3 me-1 text-muted"></i>Tanggal SPP</span><span class="v">{{ optional($sppModel?->tanggal_spp)->translatedFormat('d F Y') ?? '-' }}</span></div>
                        <div class="sh-kv"><span class="k"><i class="bi bi-tag me-1 text-muted"></i>Jenis Tagihan</span><span class="v">{{ $sppModel->jenis_tagihan ?? '-' }}</span></div>
                        <div class="sh-kv"><span class="k"><i class="bi bi-cash-coin me-1 text-muted"></i>Nilai SPP (BLU-TRF)</span><span class="v text-primary fs-6">Rp {{ number_format($nominalSpp, 0, ',', '.') }}</span></div>
                        <div class="mt-3 p-2 rounded-3 text-center small" style="background:#f1f5f9; color:#64748b;">
                            <i class="bi bi-{{ $workflowLockLabel === 'Terkunci / readonly' ? 'lock-fill' : 'unlock-fill' }} me-1"></i> Mode Dokumen: <strong>{{ $workflowLockLabel }}</strong>
                        </div>
                    </div>
                </div>

                {{-- Aktivitas Workflow --}}
                <div class="sh-card" style="--d:7;">
                    <div class="sh-card-head">
                        <div class="sh-head-left">
                            <span class="sh-ic sh-ic-slate"><i class="bi bi-clock-history"></i></span>
                            <div><h5 class="sh-title">Aktivitas Workflow</h5><p class="sh-sub">Riwayat terbaru dokumen.</p></div>
                        </div>
                    </div>
                    <div class="sh-card-body">
                        @forelse($recentActivities as $idx => $activity)
                            <div class="sh-act {{ $idx === 0 ? 'is-active' : '' }}">
                                <div class="a-title">{{ $activity['title'] }}</div>
                                <div class="a-meta"><i class="bi bi-clock me-1"></i>{{ $activity['time'] ?? '-' }} &bull; {{ $activity['actor'] }}</div>
                                @if(!empty($activity['note']))<div class="a-note"><i class="bi bi-chat-quote me-1"></i>{{ $activity['note'] }}</div>@endif
                            </div>
                        @empty
                            <div class="text-center text-muted small py-3"><i class="bi bi-clock-history d-block fs-3 mb-2 opacity-50"></i>Belum ada aktivitas.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ============ MODAL DRAFT SPP ============ --}}
    <div class="modal fade sh-modal" id="modalSppHonor" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered">
            <form action="{{ route('spps.honor.store', $tagihan->id) }}" method="POST" class="modal-content">
                @csrf
                <div class="sh-modal-header h-primary">
                    <div class="d-flex align-items-center gap-3 position-relative">
                        <span class="mh-ic"><i class="bi bi-pencil-square"></i></span>
                        <div>
                            <h5>{{ $sppModel ? 'Edit Draft SPP Honorarium' : 'Buat Draft SPP Honorarium' }}</h5>
                            <p>Lengkapi data berikut untuk menyiapkan dokumen SPP.</p>
                        </div>
                        <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                </div>

                <div class="modal-body">
                    @if($sppModel && $sppModel->status === 'Revisi')
                        <div class="alert alert-danger border-0 shadow-sm rounded-3 d-flex align-items-start gap-2">
                            <i class="bi bi-exclamation-triangle-fill fs-5"></i>
                            <div>Dokumen ini dikembalikan karena memerlukan revisi. Silakan sesuaikan data sebelum mengajukan ulang.</div>
                        </div>
                    @endif

                    <fieldset class="border-0 p-0 m-0" {{ $canEditSpp ? '' : 'disabled' }}>
                        {{-- SEC 1 --}}
                        <div class="sh-modal-block">
                            <div class="mb-head"><span class="mb-num">1</span><span class="mb-title">Informasi Dasar SPP</span></div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Nomor SPP <span class="text-danger">*</span></label>
                                    <input type="text" name="nomor_spp" class="form-control fw-bold text-primary" required value="{{ old('nomor_spp', $sppModel->nomor_spp ?? $autoNomorSpp) }}" placeholder="Ketik nomor SPP">
                                    <small class="text-muted"><i class="bi bi-info-circle me-1"></i>Digenerate otomatis, ubah jika perlu.</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Tanggal SPP <span class="text-danger">*</span></label>
                                    <input type="date" name="tanggal_spp" class="form-control" required value="{{ old('tanggal_spp', optional($sppModel?->tanggal_spp)->format('Y-m-d') ?? now()->format('Y-m-d')) }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Nominal SPP Akhir (Otomatis)</label>
                                    <input type="text" class="form-control fw-bold text-success" value="Rp {{ number_format($nominalSpp, 0, ',', '.') }}" readonly>
                                    <small class="text-muted"><i class="bi bi-info-circle me-1"></i>Nilai netto hasil pengurangan potongan pajak.</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Jenis Tagihan</label>
                                    <select name="jenis_tagihan" class="form-select">
                                        <option value="NON REMUNERASI" {{ old('jenis_tagihan', $sppModel?->jenis_tagihan) === 'NON REMUNERASI' ? 'selected' : '' }}>NON REMUNERASI</option>
                                        <option value="REMUNERASI" {{ old('jenis_tagihan', $sppModel?->jenis_tagihan) === 'REMUNERASI' ? 'selected' : '' }}>REMUNERASI</option>
                                    </select>
                                    <small class="text-muted"><i class="bi bi-info-circle me-1"></i>Kategori yang ditampilkan pada PDF SPP & SPM.</small>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Uraian SPP</label>
                                    <textarea name="uraian" class="form-control" rows="2">{{ old('uraian', $sppModel->uraian ?? $tagihan->deskripsi) }}</textarea>
                                </div>
                            </div>
                        </div>

                        {{-- SEC 2 --}}
                        <div class="sh-modal-block">
                            <div class="mb-head"><span class="mb-num">2</span><span class="mb-title">Penugasan Verifikator (Paralel)</span></div>
                            <div class="alert alert-info border-0 py-2 small mb-3 rounded-3">
                                <i class="bi bi-diagram-3 me-1"></i> Mode verifikasi paralel aktif. Dokumen diperiksa serentak oleh ketiga pejabat setelah diajukan.
                            </div>
                            <div class="row g-2">
                                <div class="col-md-4">
                                    <div class="sh-verifchip">
                                        <span class="vc-ic" style="background:rgba(99,102,241,.12);color:#4f46e5;"><i class="bi bi-person-check"></i></span>
                                        <div class="min-w-0"><div class="vc-role">Verifikator PPK</div><div class="vc-name text-truncate">{{ $ppkUser->name ?? 'Otomatis' }}</div></div>
                                    </div>
                                    <input type="hidden" name="ppk_verifikator_id" value="{{ $ppkUser->id ?? '' }}">
                                </div>
                                <div class="col-md-4">
                                    <div class="sh-verifchip">
                                        <span class="vc-ic" style="background:rgba(14,165,233,.12);color:#0284c7;"><i class="bi bi-person-gear"></i></span>
                                        <div class="min-w-0"><div class="vc-role">Koordinator Keuangan</div><div class="vc-name text-truncate">{{ $koordinatorUser->name ?? 'Otomatis' }}</div></div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="sh-verifchip">
                                        <span class="vc-ic" style="background:rgba(245,158,11,.13);color:#d97706;"><i class="bi bi-person-badge"></i></span>
                                        <div class="min-w-0"><div class="vc-role">Verifikator Kasubbag</div><div class="vc-name text-truncate">{{ $kasubbagUser->name ?? 'Otomatis' }}</div></div>
                                    </div>
                                </div>
                            </div>
                            <small class="text-muted d-block mt-2"><i class="bi bi-info-circle me-1"></i>Verifikator ditentukan otomatis oleh sistem berdasarkan verifikator tagihan.</small>
                        </div>
                    </fieldset>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    @if($canEditSpp)
                        <button type="submit" class="btn btn-primary px-4"><i class="bi bi-save me-1"></i> Simpan Draft SPP</button>
                    @endif
                </div>
            </form>
        </div>
    </div>

@endsection

@push('script')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        @if($errors->any() && old('nomor_spp'))
            const modalElement = document.getElementById('modalSppHonor');
            if (modalElement) { new bootstrap.Modal(modalElement).show(); }
        @endif
    });
</script>
@endpush
