@extends('layouts.app')
@section('title', 'Detail Mitra Jasa')

@push('css')
    <script>
        window.tailwind = window.tailwind || {};
        window.tailwind.config = { corePlugins: { preflight: false } };
    </script>
    <script src="https://cdn.tailwindcss.com"></script>
@endpush

@section('content')
@php
    $tagihanPnbp = $tagihanPnbp ?? collect();
    $layananTreeItems = $layananTreeItems ?? collect();
    $selectedLayananIds = $selectedLayananIds ?? [];
    $visibleLayananIds = $visibleLayananIds ?? [];
    $canManageMitraMaster = auth()->user()?->hasAnyRole(['Super Admin', 'Super Admin Jasa', 'Koordinator Jasa']) === true;
    $rupiah = fn ($value) => 'Rp ' . number_format((float) $value, 0, ',', '.');
    $tanggal = fn ($value) => $value ? \Carbon\Carbon::parse($value)->format('d/m/Y') : '-';
    $layananPath = function ($layanan) {
        if (! $layanan) {
            return collect();
        }

        $items = collect([$layanan]);
        $parent = $layanan->parent;
        $guard = 0;

        while ($parent && $guard < 10) {
            $items->prepend($parent);
            $parent = $parent->parent;
            $guard++;
        }

        return $items;
    };
    $layananTreeById = ($layananTreeItems ?? collect())->keyBy('id');
    $isPjp2uReport = function ($row) {
        $details = $row->penerbangan_details;

        return is_array($details) ? count($details) > 0 : filled($details);
    };
    $laporanPjp2uRows = $mitra->penjualan
        ->filter($isPjp2uReport)
        ->values();
    $laporanPendapatanRows = $mitra->penjualan
        ->reject($isPjp2uReport)
        ->values();
    $layananAktifCount = $mitra->layananJasa->count();
    $konsesiAktifCount = $mitra->konsesi->where('status_aktif', true)->count();
    $laporanCount = $mitra->penjualan->count();
    $totalNilaiTagihan = $mitra->penjualan->sum(fn ($row) => (float) ($row->nilai_tagihan ?? 0));
    $tagihanPnbpRows = $tagihanPnbp
        ->map(function ($tagihan) use ($tanggal) {
            $detailNames = collect($tagihan->details ?? [])
                ->map(fn ($detail) => $detail->layananJasa?->nama_layanan)
                ->filter()
                ->unique()
                ->values();

            return [
                'tagihan' => $tagihan,
                'nomor' => $tagihan->nomor_tagihan ?? '-',
                'layanan_names' => $detailNames,
                'tanggal' => $tanggal($tagihan->tanggal_tagihan),
                'total' => (float) ($tagihan->total_tagihan ?? 0),
                'status' => str_replace('_', ' ', $tagihan->status_pembayaran ?: ($tagihan->status ?? '-')),
                'jatuh_tempo' => $tanggal($tagihan->tanggal_jatuh_tempo),
            ];
        })
        ->values();
    $tagihanPnbpCount = $tagihanPnbpRows->count();
    $tagihanKonsesiRows = $laporanPendapatanRows->whereNotNull('tagihan_jasa_id');
    $tagihanPjp2uRows = $laporanPjp2uRows->whereNotNull('tagihan_jasa_id');
    $tagihanUtilitasRows = ($mitra->laporanUtilitas ?? collect())
        ->whereNotNull('tagihan_jasa_id')
        ->values();
    $linkedKonsesiTagihanIds = $tagihanKonsesiRows
        ->pluck('tagihan_jasa_id')
        ->filter()
        ->unique();
    $tagihanKonsesiInvoiceRows = $tagihanKonsesiRows
        ->map(function ($penjualan) use ($tanggal) {
            $tagihan = $penjualan->tagihanJasa;

            return [
                'tagihan' => $tagihan,
                'layanan' => $penjualan->layananJasa->nama_layanan ?? '-',
                'subtext' => $penjualan->persentase_konsesi !== null ? rtrim(rtrim(number_format((float) $penjualan->persentase_konsesi, 4, ',', '.'), '0'), ',') . '%' : '-',
                'periode' => $tanggal($penjualan->periode_mulai) . ' s.d. ' . $tanggal($penjualan->periode_selesai),
                'jatuh_tempo' => $tanggal($tagihan?->tanggal_jatuh_tempo),
                'total' => (float) ($tagihan->total_tagihan ?? $penjualan->nilai_tagihan ?? 0),
                'status' => $tagihan->status ?? $penjualan->status,
            ];
        })
        ->merge(
            ($mitra->tagihanJasas ?? collect())
                ->whereNotIn('id', $linkedKonsesiTagihanIds->all())
                ->filter(function ($tagihan) {
                    return $tagihan->details->contains(function ($detail) {
                        return stripos((string) $detail->keterangan, 'konsesi') !== false
                            || stripos((string) $detail->layananJasa?->nama_layanan, 'konsesi') !== false;
                    });
                })
                ->map(function ($tagihan) use ($tanggal) {
                    $detail = $tagihan->details->first(function ($item) {
                        return stripos((string) $item->keterangan, 'konsesi') !== false
                            || stripos((string) $item->layananJasa?->nama_layanan, 'konsesi') !== false;
                    }) ?? $tagihan->details->first();

                    return [
                        'tagihan' => $tagihan,
                        'layanan' => $detail?->layananJasa?->nama_layanan ?? 'Tagihan Konsesi',
                        'subtext' => $detail?->keterangan ?: '-',
                        'periode' => $tanggal($tagihan->tanggal_tagihan),
                        'jatuh_tempo' => $tanggal($tagihan->tanggal_jatuh_tempo),
                        'total' => (float) ($tagihan->total_tagihan ?? 0),
                        'status' => $tagihan->status ?? $tagihan->status_pembayaran ?? '-',
                    ];
                })
        )
        ->sortByDesc(fn ($row) => $row['tagihan']?->tanggal_tagihan ?? $row['tagihan']?->created_at)
        ->values();
    $tagihanKonsesiCount = $tagihanKonsesiInvoiceRows->count();
    $totalTagihanKonsesi = $tagihanKonsesiInvoiceRows->sum(fn ($row) => (float) $row['total']);
    $totalTagihanPjp2u = $tagihanPjp2uRows->sum(function ($penjualan) {
        return (float) ($penjualan->tagihanJasa->total_tagihan ?? $penjualan->nilai_tagihan ?? 0);
    });
    $totalTagihanUtilitas = $tagihanUtilitasRows->sum(function ($laporan) {
        return (float) ($laporan->tagihanJasa->total_tagihan ?? $laporan->total_biaya ?? 0);
    });
    $totalTagihanPnbp = $tagihanPnbpRows->sum(fn ($row) => (float) $row['total']);
    $konsesiTreeVisibleIds = function ($layanan) use (&$layananTreeById, &$selectedLayananIds) {
        if (! $layanan) {
            return [];
        }

        $visibleIds = [];
        $current = $layanan;
        $guard = 0;

        while ($current && $guard < 10) {
            $visibleIds[] = $current->id;
            $current = $current->parent_id ? $layananTreeById->get($current->parent_id) : null;
            $guard++;
        }

        foreach ($selectedLayananIds as $selectedId) {
            $current = $layananTreeById->get($selectedId);
            $branchIds = [];
            $foundScope = false;
            $guard = 0;

            while ($current && $guard < 10) {
                $branchIds[] = $current->id;

                if ((int) $current->id === (int) $layanan->id) {
                    $foundScope = true;
                    break;
                }

                $current = $current->parent_id ? $layananTreeById->get($current->parent_id) : null;
                $guard++;
            }

            if ($foundScope) {
                $visibleIds = array_merge($visibleIds, $branchIds);
            }
        }

        return array_values(array_unique($visibleIds));
    };
    $konsesiTreeCheckedIds = function ($layanan) use (&$layananTreeById, &$selectedLayananIds) {
        if (! $layanan) {
            return [];
        }

        if ($layanan->is_leaf) {
            return [$layanan->id];
        }

        $checkedIds = [];

        foreach ($selectedLayananIds as $selectedId) {
            $current = $layananTreeById->get($selectedId);
            $guard = 0;

            while ($current && $guard < 10) {
                if ((int) $current->id === (int) $layanan->id) {
                    $checkedIds[] = $selectedId;
                    break;
                }

                $current = $current->parent_id ? $layananTreeById->get($current->parent_id) : null;
                $guard++;
            }
        }

        return array_values(array_unique($checkedIds));
    };
@endphp

<style>
    .mitra-detail-page {
        color: #0f172a;
    }
    .mitra-hero {
        position: relative;
        overflow: hidden;
        border: 1px solid rgba(15, 23, 42, .08);
        border-radius: 16px;
        background:
            radial-gradient(circle at 92% 18%, rgba(20, 184, 166, .22), transparent 28%),
            linear-gradient(135deg, #102a43 0%, #14558f 55%, #0f766e 100%);
        box-shadow: 0 18px 42px rgba(15, 23, 42, .14);
    }
    .mitra-hero-title {
        font-size: clamp(1.35rem, 2vw, 2rem);
        letter-spacing: 0;
    }
    .mitra-chip {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        border: 1px solid rgba(255,255,255,.22);
        border-radius: 999px;
        padding: .38rem .72rem;
        background: rgba(255,255,255,.12);
        color: rgba(255,255,255,.92);
        font-size: .82rem;
        font-weight: 700;
    }
    .mitra-action {
        border-radius: 10px;
        font-weight: 700;
        box-shadow: 0 8px 18px rgba(15, 23, 42, .10);
    }
    .mitra-section-card {
        border: 1px solid rgba(15,23,42,.08);
        border-radius: 16px;
        box-shadow: 0 14px 34px rgba(15,23,42,.07);
        background: #fff;
    }
    .mitra-section-card .card-header {
        border-bottom: 1px solid #bfdbfe;
        background: linear-gradient(90deg, #eff6ff 0%, #f8fbff 58%, #ffffff 100%);
        padding: .65rem .9rem;
    }
    .mitra-section-title {
        display: flex;
        align-items: center;
        gap: .65rem;
        margin: 0;
        font-size: .98rem;
        font-weight: 800;
        color: #1e3a8a;
    }
    .mitra-section-icon,
    .mitra-info-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 34px;
        height: 34px;
        flex: 0 0 34px;
        border-radius: 9px;
        background: #1d4ed8;
        color: #fff;
        box-shadow: 0 10px 20px rgba(37,99,235,.18);
    }
    .mitra-info-item {
        display: flex;
        gap: .8rem;
        height: 100%;
        padding: .9rem;
        border: 1px solid rgba(15,23,42,.08);
        border-radius: 12px;
        background: #f8fafc;
    }
    .mitra-info-label {
        color: #64748b;
        font-size: .75rem;
        font-weight: 800;
        text-transform: uppercase;
    }
    .mitra-info-value {
        overflow-wrap: anywhere;
        font-weight: 700;
    }
    .mitra-stat {
        height: 100%;
        border: 1px solid rgba(15,23,42,.08);
        border-radius: 14px;
        padding: 1rem;
        background: #fff;
        box-shadow: 0 10px 24px rgba(15,23,42,.06);
    }
    .mitra-stat-label {
        color: #64748b;
        font-size: .76rem;
        font-weight: 800;
        text-transform: uppercase;
    }
    .mitra-stat-value {
        margin-top: .35rem;
        color: #0f172a;
        font-size: 1.25rem;
        font-weight: 850;
    }
    .mitra-table {
        --bs-table-striped-bg: #f8fafc;
        margin-bottom: 0;
    }
    .mitra-table thead th {
        border-bottom: 1px solid #e2e8f0;
        color: #64748b;
        font-size: .76rem;
        font-weight: 800;
        text-transform: uppercase;
        white-space: nowrap;
    }
    .mitra-table tbody td {
        border-color: #edf2f7;
        padding-top: .85rem;
        padding-bottom: .85rem;
        vertical-align: middle;
    }
    .mitra-empty {
        padding: 2.25rem 1rem;
        text-align: center;
        color: #64748b;
        background: #f8fafc;
        border: 1px dashed #cbd5e1;
        border-radius: 14px;
    }
    .mitra-empty i {
        display: block;
        margin-bottom: .55rem;
        color: #94a3b8;
        font-size: 1.6rem;
    }
    .mitra-list-row {
        display: flex;
        justify-content: space-between;
        gap: 1rem;
        padding: 1rem 0;
        border-bottom: 1px solid #edf2f7;
    }
    .mitra-list-row:last-child {
        border-bottom: 0;
        padding-bottom: 0;
    }
    .konsesi-tree-node summary {
        cursor: pointer;
        list-style: none;
    }
    .konsesi-tree-node summary::-webkit-details-marker {
        display: none;
    }
    .konsesi-tree-node:not([open]) > summary .konsesi-tree-caret i {
        transform: rotate(-90deg);
    }
    .konsesi-tree-caret i {
        display: inline-block;
        transition: transform .15s ease;
    }
    .konsesi-tree-row {
        line-height: 1.55;
        padding: 1px 0;
    }
    .konsesi-tree-row:hover {
        background: #f8fafc;
        border-radius: 4px;
    }
    .layanan-tree-panel {
        max-height: 520px;
        overflow: auto;
        background: #fbfdff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 1rem 1.15rem;
    }
    .layanan-tree-node summary {
        cursor: pointer;
        list-style: none;
    }
    .layanan-tree-node summary::-webkit-details-marker {
        display: none;
    }
    .layanan-tree-row {
        display: flex;
        align-items: flex-start;
        gap: 6px;
        color: #155e9f;
        line-height: 1.8;
        font-size: 15px;
    }
    .layanan-tree-row:hover .layanan-tree-title {
        text-decoration: underline;
    }
    .layanan-tree-branch {
        width: 12px;
        height: 14px;
        border-left: 1px solid #64748b;
        border-bottom: 1px solid #64748b;
        flex: 0 0 12px;
        margin-top: 2px;
    }
    .layanan-tree-icon {
        color: #0f766e;
        width: 16px;
        flex: 0 0 16px;
    }
    .layanan-tree-title {
        flex: 1;
    }
    .layanan-tree-children {
        margin-left: 16px;
    }
    .layanan-tree-leaf {
        margin-bottom: 4px;
    }
    .layanan-tree-meta {
        margin-left: 34px;
    }
    @media (max-width: 767.98px) {
        .mitra-hero {
            border-radius: 14px;
        }
        .mitra-list-row {
            display: block;
        }
    }

    /* ============ Workflow Pipeline (Mitra → Kontrak → Layanan) ============ */
    .workflow-pipeline {
        background: linear-gradient(135deg, #fafbff 0%, #ffffff 100%);
        border: 1px solid #eef0f4;
        border-radius: 16px;
        padding: 1rem 1.25rem;
        margin-bottom: 1.15rem;
        display: flex;
        align-items: center;
        gap: .85rem;
        flex-wrap: wrap;
        box-shadow: 0 4px 12px rgba(15,23,42,.04);
    }
    .wp-step {
        display: flex;
        align-items: center;
        gap: .65rem;
        padding: .35rem .25rem;
        flex-shrink: 0;
    }
    .wp-step .wp-num {
        width: 32px; height: 32px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        font-size: .85rem;
        background: #e2e8f0;
        color: #64748b;
        flex-shrink: 0;
        border: 2px solid #e2e8f0;
        transition: all .25s ease;
    }
    .wp-step.is-done .wp-num {
        background: linear-gradient(135deg, #34d399, #10b981);
        color: #fff;
        border-color: #10b981;
        box-shadow: 0 4px 10px rgba(16,185,129,.30);
    }
    .wp-step.is-active .wp-num {
        background: linear-gradient(135deg, #fbbf24, #f59e0b);
        color: #fff;
        border-color: #f59e0b;
        box-shadow: 0 4px 12px rgba(245,158,11,.40);
        animation: wpPulse 1.6s ease-in-out infinite;
    }
    @keyframes wpPulse {
        0%, 100% { box-shadow: 0 0 0 0 rgba(245,158,11,.45); }
        50%      { box-shadow: 0 0 0 8px rgba(245,158,11,0); }
    }
    .wp-step .wp-info { line-height: 1.2; }
    .wp-step .wp-label {
        font-size: .65rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .06em;
        color: #94a3b8;
    }
    .wp-step.is-done .wp-label { color: #047857; }
    .wp-step.is-active .wp-label { color: #b45309; }
    .wp-step .wp-text {
        font-size: .85rem;
        font-weight: 700;
        color: #0f172a;
    }
    .wp-arrow {
        flex: 1 1 30px;
        min-width: 24px;
        height: 2px;
        background: repeating-linear-gradient(90deg, #cbd5e1 0 6px, transparent 6px 12px);
        position: relative;
    }
    .wp-arrow.is-done {
        background: linear-gradient(90deg, #10b981, #34d399);
    }
    .wp-arrow.is-active {
        background: linear-gradient(90deg, #f59e0b, #fbbf24, #f59e0b);
        background-size: 200% 100%;
        animation: wpShimmer 1.8s linear infinite;
    }
    @keyframes wpShimmer {
        0%   { background-position: 0% 0; }
        100% { background-position: 200% 0; }
    }

    /* ============ Priority Kontrak Card (when empty) ============ */
    .kontrak-priority-card {
        position: relative;
        overflow: hidden;
        border: 0;
        border-radius: 18px;
        padding: 1.5rem 1.75rem;
        margin-bottom: 1.15rem;
        background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 50%, #db2777 100%);
        color: #fff;
        box-shadow: 0 18px 40px rgba(79,70,229,.30);
        animation: secInUp .55s cubic-bezier(.22,1,.36,1) both;
    }
    .kontrak-priority-card::before,
    .kontrak-priority-card::after {
        content: '';
        position: absolute;
        border-radius: 50%;
        pointer-events: none;
    }
    .kontrak-priority-card::before {
        right: -110px; top: -110px;
        width: 280px; height: 280px;
        background: rgba(255,255,255,.10);
    }
    .kontrak-priority-card::after {
        left: -60px; bottom: -80px;
        width: 200px; height: 200px;
        background: rgba(255,255,255,.06);
    }
    .kontrak-priority-card > * { position: relative; z-index: 1; }
    .kontrak-priority-card .kpc-illust {
        position: absolute;
        right: 1.5rem; top: 50%;
        transform: translateY(-50%) rotate(-10deg);
        font-size: 7.5rem;
        opacity: .14;
        z-index: 0;
        line-height: 1;
    }
    .kontrak-priority-card .kpc-tag {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        background: rgba(255,255,255,.18);
        backdrop-filter: blur(8px);
        border: 1px solid rgba(255,255,255,.30);
        padding: .35rem .85rem;
        border-radius: 999px;
        font-size: .72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .06em;
        color: #fff;
        margin-bottom: .65rem;
        animation: kpcTagBounce 1.8s ease-in-out infinite;
    }
    @keyframes kpcTagBounce {
        0%, 100% { transform: translateY(0); }
        50%      { transform: translateY(-3px); }
    }
    .kontrak-priority-card .kpc-tag i { color: #fde68a; }
    .kontrak-priority-card .kpc-title {
        font-size: 1.45rem;
        font-weight: 800;
        margin: 0 0 .35rem;
        color: #fff !important;
        text-shadow: 0 1px 2px rgba(0,0,0,.15);
        letter-spacing: -.01em;
    }
    .kontrak-priority-card .kpc-sub {
        font-size: .9rem;
        color: rgba(255,255,255,.92);
        margin: 0 0 1rem;
        max-width: 640px;
    }
    .kontrak-priority-card .kpc-checklist {
        display: flex;
        flex-wrap: wrap;
        gap: .5rem;
        margin-bottom: 1rem;
    }
    .kontrak-priority-card .kpc-check {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        background: rgba(255,255,255,.14);
        border: 1px solid rgba(255,255,255,.25);
        padding: .35rem .75rem;
        border-radius: 999px;
        font-size: .76rem;
        font-weight: 600;
    }
    .kontrak-priority-card .kpc-check i { color: #fde68a; }

    .btn-kpc-cta {
        display: inline-flex;
        align-items: center;
        gap: .5rem;
        background: #fff;
        color: #4338ca;
        font-weight: 800;
        padding: .75rem 1.4rem;
        border-radius: .75rem;
        font-size: .9rem;
        text-decoration: none;
        box-shadow: 0 12px 28px rgba(0,0,0,.18);
        transition: all .25s ease;
        border: 0;
    }
    .btn-kpc-cta:hover {
        color: #312e81;
        transform: translateY(-2px);
        box-shadow: 0 18px 36px rgba(0,0,0,.25);
    }
    .btn-kpc-cta i { font-size: 1.05rem; }

    /* ============ Kontrak Active card ============ */
    .kontrak-active-card {
        position: relative;
        background: #fff;
        border: 1px solid #eef0f4;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 4px 12px rgba(15,23,42,.04);
        margin-bottom: 1.15rem;
        transition: box-shadow .25s ease;
        animation: secInUp .55s cubic-bezier(.22,1,.36,1) both;
    }
    .kontrak-active-card:hover { box-shadow: 0 14px 32px rgba(15,23,42,.07); }
    .kontrak-active-card .kac-head {
        padding: 1rem 1.5rem;
        background: linear-gradient(135deg, rgba(16,185,129,.08), rgba(16,185,129,.02));
        border-bottom: 1px solid #eef0f4;
        display: flex;
        align-items: center;
        gap: .85rem;
        flex-wrap: wrap;
    }
    .kontrak-active-card .kac-icon {
        width: 44px; height: 44px;
        border-radius: 12px;
        background: linear-gradient(135deg, #34d399, #10b981);
        color: #fff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        flex-shrink: 0;
        box-shadow: 0 6px 14px rgba(16,185,129,.30);
    }
    .kontrak-active-card .kac-title {
        font-weight: 800;
        font-size: 1rem;
        color: #0f172a;
        margin: 0;
        letter-spacing: -.01em;
    }
    .kontrak-active-card .kac-sub {
        font-size: .76rem;
        color: #047857;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .06em;
        margin-top: .15rem;
    }
    .kontrak-active-card .kac-actions {
        margin-left: auto;
    }
    .btn-kac-add {
        background: linear-gradient(135deg, #6366f1, #4f46e5);
        color: #fff;
        font-weight: 700;
        font-size: .8rem;
        padding: .5rem 1rem;
        border-radius: .55rem;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        box-shadow: 0 4px 10px rgba(99,102,241,.30);
        transition: all .2s ease;
    }
    .btn-kac-add:hover {
        color: #fff;
        transform: translateY(-1px);
        box-shadow: 0 8px 18px rgba(99,102,241,.45);
    }
    .kontrak-active-card .kac-body {
        padding: 1rem 1.5rem 1.25rem;
    }
    .kontrak-row-modern {
        display: flex;
        align-items: flex-start;
        gap: 1rem;
        padding: .9rem 0;
        border-bottom: 1px dashed #eef2f7;
    }
    .kontrak-row-modern:last-child { border-bottom: 0; padding-bottom: 0; }
    .kontrak-row-modern:first-child { padding-top: 0; }
    .kontrak-row-modern .krm-num {
        width: 32px; height: 32px;
        border-radius: 9px;
        background: linear-gradient(135deg, rgba(99,102,241,.12), rgba(139,92,246,.06));
        color: #4338ca;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        font-size: .82rem;
        flex-shrink: 0;
    }
    .kontrak-row-modern .krm-info { flex: 1 1 auto; min-width: 0; }
    .kontrak-row-modern .krm-title {
        font-weight: 700;
        color: #0f172a;
        font-size: .9rem;
        margin: 0 0 .25rem;
    }
    .kontrak-row-modern .krm-meta {
        font-size: .76rem;
        color: #64748b;
        display: flex;
        gap: .85rem;
        flex-wrap: wrap;
        align-items: center;
    }
    .kontrak-row-modern .krm-meta i { color: #94a3b8; }
    .kontrak-row-modern .krm-scope {
        margin-top: .35rem;
        font-size: .76rem;
        color: #4338ca;
        background: rgba(99,102,241,.08);
        padding: .25rem .6rem;
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
        gap: .35rem;
    }
    .kontrak-row-modern .krm-actions {
        display: flex;
        gap: .35rem;
        flex-shrink: 0;
    }
    .kontrak-status-pill {
        display: inline-flex;
        align-items: center;
        gap: .3rem;
        font-size: .65rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .05em;
        padding: .2rem .55rem;
        border-radius: 999px;
    }
    .kontrak-status-pill.is-aktif   { background: rgba(16,185,129,.12); color: #047857; }
    .kontrak-status-pill.is-draft   { background: rgba(100,116,139,.12); color: #475569; }
    .kontrak-status-pill.is-revisi  { background: rgba(245,158,11,.14); color: #b45309; }
    .kontrak-status-pill.is-selesai { background: rgba(99,102,241,.12); color: #4338ca; }

    /* ============ Locked Section ============ */
    .section-locked { position: relative; opacity: .82; }
    .section-locked .card-header { filter: grayscale(.45); }
    .section-locked .mitra-section-title { color: #475569; }
    .section-locked .mitra-section-icon {
        background: linear-gradient(135deg, #94a3b8, #64748b) !important;
        box-shadow: 0 6px 14px rgba(100,116,139,.25) !important;
    }
    .btn-add-disabled {
        background: #e2e8f0 !important;
        color: #94a3b8 !important;
        cursor: not-allowed !important;
        pointer-events: none;
        border: 1px dashed #cbd5e1 !important;
        box-shadow: none !important;
    }
    .locked-banner {
        display: flex;
        align-items: flex-start;
        gap: .75rem;
        background: linear-gradient(135deg, rgba(245,158,11,.08), rgba(245,158,11,.02));
        border: 1px solid rgba(245,158,11,.25);
        border-left: 4px solid #f59e0b;
        border-radius: 12px;
        padding: .85rem 1rem;
        margin-bottom: 1rem;
    }
    .locked-banner .lb-icon {
        width: 38px; height: 38px;
        border-radius: 10px;
        background: linear-gradient(135deg, #fbbf24, #f59e0b);
        color: #fff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.05rem;
        flex-shrink: 0;
        box-shadow: 0 4px 10px rgba(245,158,11,.30);
    }
    .locked-banner .lb-text {
        font-size: .82rem;
        color: #92400e;
        line-height: 1.5;
    }
    .locked-banner .lb-text strong { color: #78350f; }
    .locked-banner .lb-cta { margin-top: .55rem; }
    .locked-banner .lb-cta a {
        background: linear-gradient(135deg, #f59e0b, #d97706);
        color: #fff;
        font-weight: 700;
        font-size: .78rem;
        padding: .45rem .9rem;
        border-radius: .55rem;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        box-shadow: 0 4px 10px rgba(245,158,11,.35);
        transition: all .2s ease;
    }
    .locked-banner .lb-cta a:hover {
        color: #fff;
        transform: translateY(-1px);
        box-shadow: 0 8px 18px rgba(245,158,11,.45);
    }
    .locked-empty {
        position: relative;
        padding: 2.5rem 1rem 2.25rem;
        text-align: center;
        background: repeating-linear-gradient(135deg, #f8fafc 0 12px, #fafbff 12px 24px);
        border: 1px dashed #cbd5e1;
        border-radius: 14px;
        color: #64748b;
    }
    .locked-empty .le-icon {
        width: 64px; height: 64px;
        border-radius: 18px;
        background: linear-gradient(135deg, #94a3b8, #64748b);
        color: #fff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.65rem;
        margin: 0 auto .65rem;
        box-shadow: 0 8px 18px rgba(100,116,139,.25);
        position: relative;
    }
    .locked-empty .le-icon::after {
        content: '\F47A';
        font-family: 'bootstrap-icons';
        position: absolute;
        right: -6px; bottom: -6px;
        width: 26px; height: 26px;
        border-radius: 50%;
        background: linear-gradient(135deg, #fbbf24, #f59e0b);
        color: #fff;
        font-size: .85rem;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 3px solid #fff;
        box-shadow: 0 4px 10px rgba(245,158,11,.35);
    }
    .locked-empty .le-title {
        font-weight: 800;
        color: #1e293b;
        font-size: .95rem;
        margin: 0 0 .25rem;
    }
    .locked-empty .le-sub {
        font-size: .82rem;
        color: #64748b;
        max-width: 380px;
        margin: 0 auto;
    }

    @keyframes secInUp {
        from { opacity: 0; transform: translateY(10px); }
        to   { opacity: 1; transform: translateY(0); }
    }
</style>

<div class="mitra-detail-page">
<div class="mitra-hero p-4 p-lg-5 mb-4 text-white">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
        <div>
            <div class="mitra-chip mb-3">
                <i class="bi bi-building"></i>
                Detail Mitra Jasa
            </div>
            <h4 class="mitra-hero-title mb-2 fw-bold text-white">{{ $mitra->nama_mitra }}</h4>
            <div class="d-flex flex-wrap gap-2">
                <span class="mitra-chip"><i class="bi bi-upc-scan"></i>{{ $mitra->kode_mitra ?: 'Kode belum diisi' }}</span>
                <span class="mitra-chip"><i class="bi bi-briefcase"></i>{{ str_replace('_', ' ', $mitra->jenis_mitra ?: 'Jenis belum diisi') }}</span>
                <span class="mitra-chip"><i class="bi {{ $mitra->status_aktif ? 'bi-check-circle' : 'bi-slash-circle' }}"></i>{{ $mitra->status_aktif ? 'Aktif' : 'Nonaktif' }}</span>
                @unless($canManageMitraMaster)
                    <span class="mitra-chip"><i class="bi bi-eye"></i>Mode baca Admin Jasa</span>
                @endunless
            </div>
        </div>
        <div class="d-flex gap-2">
            @if($canManageMitraMaster)
                <a href="{{ route('jasa.mitra.edit', $mitra) }}" class="btn btn-light mitra-action jasa-icon-btn" title="Edit" aria-label="Edit"><i class="bi bi-pencil-square"></i></a>
            @endif
            <a href="{{ route('jasa.mitra.index') }}" class="btn btn-outline-light mitra-action jasa-icon-btn" title="Kembali" aria-label="Kembali"><i class="bi bi-arrow-left"></i></a>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-xl-3">
        <div class="mitra-stat">
            <div class="mitra-stat-label">Layanan Aktif</div>
            <div class="mitra-stat-value">{{ number_format($layananAktifCount, 0, ',', '.') }}</div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="mitra-stat">
            <div class="mitra-stat-label">Konsesi Aktif</div>
            <div class="mitra-stat-value">{{ number_format($konsesiAktifCount, 0, ',', '.') }}</div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="mitra-stat">
            <div class="mitra-stat-label">Laporan</div>
            <div class="mitra-stat-value">{{ number_format($laporanCount, 0, ',', '.') }}</div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="mitra-stat">
            <div class="mitra-stat-label">Nilai Tagihan</div>
            <div class="mitra-stat-value">{{ $rupiah($totalNilaiTagihan) }}</div>
        </div>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif
@php
    $hasKontrak = $mitra->kontrak->isNotEmpty();
    $kontrakCount = $mitra->kontrak->count();
    $kontrakActiveCount = $mitra->kontrak->where('status_kontrak', 'AKTIF')->count();
    $stepMitraDone = (bool) $mitra->id;
    $stepKontrakDone = $hasKontrak;
    $stepLayananDone = $hasKontrak && $layananAktifCount > 0;
@endphp
<div class="row g-4">
    <div class="col-lg-5">
        <div class="card mitra-section-card overflow-hidden">
            <div class="card-header">
                <h5 class="mitra-section-title"><span class="mitra-section-icon"><i class="bi bi-person-vcard"></i></span>Informasi Mitra</h5>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    @foreach([
                        ['NPWP', $mitra->npwp ?: '-', 'bi-card-text'],
                        ['Email', $mitra->email ?: '-', 'bi-envelope'],
                        ['No HP/WA', $mitra->no_telepon ?: '-', 'bi-telephone'],
                        ['Penanggung Jawab', $mitra->nama_penanggung_jawab ?: '-', 'bi-person'],
                        ['Jabatan PJ', $mitra->jabatan_penanggung_jawab ?: '-', 'bi-briefcase'],
                    ] as $item)
                        <div class="col-12">
                            <div class="mitra-info-item">
                                <div class="mitra-info-icon"><i class="bi {{ $item[2] }}"></i></div>
                                <div class="flex-grow-1">
                                    <div class="mitra-info-label">{{ $item[0] }}</div>
                                    <div class="mitra-info-value">{{ $item[1] }}</div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                    <div class="col-12">
                        <div class="mitra-info-item">
                            <div class="mitra-info-icon"><i class="bi bi-geo-alt"></i></div>
                            <div>
                                <div class="mitra-info-label">Alamat</div>
                                <div class="mitra-info-value">{{ $mitra->alamat ?: '-' }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card mitra-section-card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center gap-3 flex-wrap">
                <h5 class="mitra-section-title"><span class="mitra-section-icon"><i class="bi bi-shield-lock"></i></span>Akun Mitra</h5>
                @if($canManageMitraMaster)
                    @if(!$mitra->user)
                        <form action="{{ route('jasa.mitra.account.store', $mitra) }}" method="POST">
                            @csrf
                            <button class="btn btn-sm btn-primary fw-bold jasa-icon-btn" type="submit" title="Buat akun" aria-label="Buat akun"><i class="bi bi-person-plus"></i></button>
                        </form>
                    @else
                        <form action="{{ route('jasa.mitra.account.reset', $mitra) }}" method="POST" onsubmit="return confirm('Reset password akun mitra ini? Password baru akan ditampilkan setelah proses berhasil.');">
                            @csrf
                            <button class="btn btn-sm btn-warning fw-bold jasa-icon-btn" type="submit" title="Reset password" aria-label="Reset password"><i class="bi bi-arrow-clockwise"></i></button>
                        </form>
                    @endif
                @endif
            </div>
            <div class="card-body">
                @if($mitra->user)
                    <span class="badge bg-success mb-2">Akun Aktif</span>
                    <div>Email login: <strong>{{ $mitra->user->email }}</strong></div>
                    @if(session('mitra_password'))
                        <div class="alert alert-warning mt-3 mb-0">
                            <div class="fw-bold mb-1">Password awal akun mitra</div>
                            <div>Email: <strong>{{ session('mitra_email', $mitra->user->email) }}</strong></div>
                            <div>Password: <strong>{{ session('mitra_password') }}</strong></div>
                            <small>Password ini hanya ditampilkan setelah akun dibuat atau di-reset. Simpan sebagai informasi awal untuk mitra.</small>
                        </div>
                    @else
                        <div class="small text-muted mt-2">
                            Password tidak dapat ditampilkan ulang karena tersimpan dalam bentuk hash.
                            @if($canManageMitraMaster)
                                Gunakan tombol reset untuk membuat password awal baru.
                            @endif
                        </div>
                    @endif
                @else
                    <div class="mitra-empty"><i class="bi bi-person-lock"></i>Mitra belum memiliki akun login. Pastikan email mitra sudah diisi sebelum membuat akun.</div>
                @endif
            </div>
        </div>

        {{-- Workflow Pipeline --}}
        <div class="workflow-pipeline">
            <div class="wp-step is-done">
                <span class="wp-num"><i class="bi bi-check-lg"></i></span>
                <div class="wp-info">
                    <div class="wp-label">Langkah 1</div>
                    <div class="wp-text">Mitra Ditambahkan</div>
                </div>
            </div>
            <div class="wp-arrow {{ $stepKontrakDone ? 'is-done' : 'is-active' }}"></div>
            <div class="wp-step {{ $stepKontrakDone ? 'is-done' : 'is-active' }}">
                <span class="wp-num">{{ $stepKontrakDone ? '✓' : '2' }}</span>
                <div class="wp-info">
                    <div class="wp-label">{{ $stepKontrakDone ? 'Langkah 2 — Selesai' : 'Langkah 2 — Wajib' }}</div>
                    <div class="wp-text">Input Kontrak Dasar</div>
                </div>
            </div>
            <div class="wp-arrow {{ $stepLayananDone ? 'is-done' : '' }}"></div>
            <div class="wp-step {{ $stepLayananDone ? 'is-done' : '' }}">
                <span class="wp-num">{{ $stepLayananDone ? '✓' : '3' }}</span>
                <div class="wp-info">
                    <div class="wp-label">Langkah 3</div>
                    <div class="wp-text">Layanan, Konsesi &amp; PJP2U</div>
                </div>
            </div>
        </div>

        {{-- Kontrak: Priority Card (empty) atau Active Card --}}
        @if(! $hasKontrak)
            <div class="kontrak-priority-card">
                <i class="bi bi-file-earmark-text-fill kpc-illust d-none d-md-block"></i>
                <span class="kpc-tag"><i class="bi bi-exclamation-diamond-fill"></i> Tindakan Diperlukan</span>
                <h5 class="kpc-title"><i class="bi bi-file-earmark-text me-2"></i>Input Kontrak / Dokumen Dasar Terlebih Dahulu</h5>
                <p class="kpc-sub">
                    Kontrak adalah <strong>dasar utama</strong> sebelum Anda dapat menambahkan
                    Layanan Aktif, Hak Kelola Konsesi, maupun Hak PJP2U untuk mitra ini.
                    Tanpa kontrak, ketiga modul tersebut akan tetap terkunci.
                </p>
                <div class="kpc-checklist">
                    <span class="kpc-check"><i class="bi bi-check2-circle"></i> Mengikat scope layanan</span>
                    <span class="kpc-check"><i class="bi bi-check2-circle"></i> Menentukan masa berlaku</span>
                    <span class="kpc-check"><i class="bi bi-check2-circle"></i> Wajib untuk konsesi &amp; PJP2U</span>
                </div>
                @if($canManageMitraMaster)
                    <a href="{{ route('jasa.mitra.kontrak.create', $mitra) }}" class="btn-kpc-cta">
                        <i class="bi bi-plus-lg"></i> Tambah Kontrak Sekarang
                    </a>
                @else
                    <span class="kpc-check"><i class="bi bi-info-circle"></i> Hubungi Admin Jasa untuk menambahkan kontrak.</span>
                @endif
            </div>
        @else
            <div class="kontrak-active-card">
                <div class="kac-head">
                    <span class="kac-icon"><i class="bi bi-file-earmark-check-fill"></i></span>
                    <div>
                        <div class="kac-sub">Kontrak / Dokumen Dasar</div>
                        <h6 class="kac-title">{{ $kontrakCount }} dokumen tercatat &middot; {{ $kontrakActiveCount }} aktif</h6>
                    </div>
                    @if($canManageMitraMaster)
                        <div class="kac-actions">
                            <a href="{{ route('jasa.mitra.kontrak.create', $mitra) }}" class="btn-kac-add" title="Tambah kontrak">
                                <i class="bi bi-plus-lg"></i> Tambah Kontrak
                            </a>
                        </div>
                    @endif
                </div>
                <div class="kac-body">
                    @foreach($mitra->kontrak as $kontrak)
                        @php
                            $statusKey = strtolower((string) $kontrak->status_kontrak);
                            $statusCls = match($statusKey) {
                                'aktif'   => 'is-aktif',
                                'draft'   => 'is-draft',
                                'revisi'  => 'is-revisi',
                                'selesai' => 'is-selesai',
                                default   => 'is-draft',
                            };
                        @endphp
                        <div class="kontrak-row-modern">
                            <span class="krm-num">{{ $loop->iteration }}</span>
                            <div class="krm-info">
                                <div class="krm-title">{{ $kontrak->nomor_kontrak ?: '-' }} &middot; {{ $kontrak->nama_kontrak ?: 'Dokumen Mitra Jasa' }}</div>
                                <div class="krm-meta">
                                    <span><i class="bi bi-tag-fill me-1"></i>{{ str_replace('_', ' ', $kontrak->jenis_dokumen ?: '-') }}</span>
                                    <span><i class="bi bi-calendar3 me-1"></i>{{ optional($kontrak->tanggal_mulai)->format('d/m/Y') ?: '-' }} &mdash; {{ optional($kontrak->tanggal_selesai)->format('d/m/Y') ?: '-' }}</span>
                                    <span class="kontrak-status-pill {{ $statusCls }}">{{ $kontrak->status_kontrak }}</span>
                                </div>
                                <span class="krm-scope">
                                    <i class="bi bi-diagram-3"></i>
                                    {{ $kontrak->layananJasa->isEmpty() ? 'Berlaku untuk semua layanan aktif mitra' : $kontrak->layananJasa->pluck('kode_layanan')->filter()->join(', ') }}
                                </span>
                            </div>
                            <div class="krm-actions">
                                <a href="{{ route('jasa.mitra.kontrak.show', [$mitra, $kontrak]) }}" class="btn btn-sm btn-light border jasa-icon-btn" title="Detail" aria-label="Detail"><i class="bi bi-eye"></i></a>
                                @if($canManageMitraMaster)
                                    <form method="POST" action="{{ route('jasa.mitra.kontrak.destroy', [$mitra, $kontrak]) }}" onsubmit="return confirm('Hapus kontrak/dokumen ini? Data yang sudah dipakai tagihan tidak bisa dihapus.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-light border text-danger jasa-icon-btn" title="Hapus" aria-label="Hapus"><i class="bi bi-trash"></i></button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        @unless($hasKontrak)
            <div class="locked-banner">
                <span class="lb-icon"><i class="bi bi-lock-fill"></i></span>
                <div>
                    <div class="lb-text">
                        <strong>Modul terkunci.</strong>
                        Layanan Aktif, Hak Kelola Konsesi, dan Hak PJP2U baru bisa diatur setelah minimal satu kontrak dimasukkan.
                    </div>
                    @if($canManageMitraMaster)
                        <div class="lb-cta">
                            <a href="{{ route('jasa.mitra.kontrak.create', $mitra) }}">
                                <i class="bi bi-plus-lg"></i> Tambah Kontrak untuk Membuka Modul
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        @endunless

        <div class="card mitra-section-card {{ ! $hasKontrak ? 'section-locked' : '' }}">
            <div class="card-header d-flex justify-content-between align-items-center gap-3 flex-wrap">
                <h5 class="mitra-section-title">
                    <span class="mitra-section-icon"><i class="bi {{ $hasKontrak ? 'bi-diagram-3' : 'bi-lock-fill' }}"></i></span>
                    Layanan Aktif
                </h5>
                @if($canManageMitraMaster)
                    @if($hasKontrak)
                        <a href="{{ route('jasa.mitra.layanan.edit', $mitra) }}" class="btn btn-sm btn-primary fw-bold jasa-icon-btn" title="Atur layanan" aria-label="Atur layanan"><i class="bi bi-sliders"></i></a>
                    @else
                        <button type="button" class="btn btn-sm fw-bold jasa-icon-btn btn-add-disabled" title="Tambah kontrak terlebih dahulu" aria-label="Terkunci"><i class="bi bi-lock-fill"></i></button>
                    @endif
                @endif
            </div>
            <div class="card-body">
                @if(! $hasKontrak)
                    <div class="locked-empty">
                        <div class="le-icon"><i class="bi bi-diagram-3"></i></div>
                        <div class="le-title">Modul Layanan Aktif Terkunci</div>
                        <div class="le-sub">Setelah kontrak dasar dimasukkan, Anda dapat mengatur layanan apa saja yang aktif untuk mitra ini.</div>
                    </div>
                @elseif($mitra->layananJasa->isEmpty())
                    <div class="mitra-empty"><i class="bi bi-diagram-3"></i>Belum ada layanan aktif untuk mitra ini.</div>
                @else
                    <div class="mb-3">
                        <div class="fw-bold">Jenis Penerimaan</div>
                        <div class="small text-muted">{{ number_format($layananAktifCount, 0, ',', '.') }} layanan terhubung ke mitra ini.</div>
                    </div>
                    <div class="layanan-tree-panel">
                        @php
                            $childrenByParent = $layananTreeItems->groupBy(fn ($item) => $item->parent_id ?: 'root');
                        @endphp
                        @include('super_admin_jasa.mitra.partials.layanan-tree-readonly', [
                            'childrenByParent' => $childrenByParent,
                            'parentId' => 'root',
                            'depth' => 0,
                            'selectedLayananIds' => $selectedLayananIds,
                            'visibleLayananIds' => $visibleLayananIds,
                        ])
                    </div>
                @endif
            </div>
        </div>

        <div class="card mitra-section-card mt-4 {{ ! $hasKontrak ? 'section-locked' : '' }}">
            <div class="card-header d-flex justify-content-between align-items-center gap-3 flex-wrap">
                <h5 class="mitra-section-title">
                    <span class="mitra-section-icon"><i class="bi {{ $hasKontrak ? 'bi-percent' : 'bi-lock-fill' }}"></i></span>
                    Hak Kelola Konsesi
                </h5>
                @if($canManageMitraMaster)
                    @if($hasKontrak)
                        <a href="{{ route('jasa.mitra.konsesi.create', $mitra) }}" class="btn btn-sm btn-primary fw-bold jasa-icon-btn" title="Tambah hak konsesi" aria-label="Tambah hak konsesi"><i class="bi bi-plus-lg"></i></a>
                    @else
                        <button type="button" class="btn btn-sm fw-bold jasa-icon-btn btn-add-disabled" title="Tambah kontrak terlebih dahulu" aria-label="Terkunci"><i class="bi bi-lock-fill"></i></button>
                    @endif
                @endif
            </div>
            <div class="card-body">
                @if(! $hasKontrak)
                    <div class="locked-empty">
                        <div class="le-icon"><i class="bi bi-percent"></i></div>
                        <div class="le-title">Modul Hak Kelola Konsesi Terkunci</div>
                        <div class="le-sub">Konsesi mengikat persentase pendapatan dengan kontrak. Tambahkan kontrak dasar lebih dulu.</div>
                    </div>
                @elseif($mitra->konsesi->isEmpty())
                    <div class="mitra-empty"><i class="bi bi-percent"></i>Belum ada hak kelola konsesi untuk mitra ini.</div>
                @else
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mitra-table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Layanan</th>
                                    <th>Persentase</th>
                                    <th>Status</th>
                                    @if($canManageMitraMaster)
                                        <th>Aksi</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($mitra->konsesi as $konsesi)
                                    <tr>
                                        <td>
                                            <div class="fw-semibold">{{ $konsesi->layananJasa->nama_layanan ?? '-' }}</div>
                                            <small class="text-muted">{{ $konsesi->kontrakMitraJasa->nomor_kontrak ?? 'Tanpa Kontrak' }}</small>
                                        </td>
                                        <td>
                                            @if($konsesi->persentase_konsesi)
                                                <div class="fw-bold text-success">{{ rtrim(rtrim(number_format((float) $konsesi->persentase_konsesi, 4, ',', '.'), '0'), ',') }}%</div>
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge {{ $konsesi->status_aktif ? 'bg-success' : 'bg-secondary' }}">
                                                {{ $konsesi->status_aktif ? 'Aktif' : 'Nonaktif' }}
                                            </span>
                                        </td>
                                        @if($canManageMitraMaster)
                                            <td>
                                                <a href="{{ route('jasa.mitra.konsesi.edit', [$mitra, $konsesi]) }}" class="btn btn-sm btn-light border jasa-icon-btn" title="Edit" aria-label="Edit"><i class="bi bi-pencil"></i></a>
                                                <form method="POST" action="{{ route('jasa.mitra.konsesi.deactivate', [$mitra, $konsesi]) }}" class="d-inline" onsubmit="return confirm('Nonaktifkan konsesi ini?');">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button class="btn btn-sm btn-light border text-warning jasa-icon-btn" {{ !$konsesi->status_aktif ? 'disabled' : '' }} title="Nonaktifkan" aria-label="Nonaktifkan"><i class="bi bi-pause-circle"></i></button>
                                                </form>
                                            </td>
                                        @endif
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        <div class="card mitra-section-card mt-4 {{ ! $hasKontrak ? 'section-locked' : '' }}">
            <div class="card-header d-flex justify-content-between align-items-center gap-3 flex-wrap">
                <h5 class="mitra-section-title">
                    <span class="mitra-section-icon"><i class="bi {{ $hasKontrak ? 'bi-airplane' : 'bi-lock-fill' }}"></i></span>
                    Hak PJP2U
                </h5>
                @if($canManageMitraMaster)
                    @if($hasKontrak)
                        <a href="{{ route('jasa.mitra.pjp2u.create', $mitra) }}" class="btn btn-sm btn-primary fw-bold jasa-icon-btn" title="Tambah hak PJP2U" aria-label="Tambah hak PJP2U"><i class="bi bi-plus-lg"></i></a>
                    @else
                        <button type="button" class="btn btn-sm fw-bold jasa-icon-btn btn-add-disabled" title="Tambah kontrak terlebih dahulu" aria-label="Terkunci"><i class="bi bi-lock-fill"></i></button>
                    @endif
                @endif
            </div>
            <div class="card-body">
                @if(! $hasKontrak)
                    <div class="locked-empty">
                        <div class="le-icon"><i class="bi bi-airplane"></i></div>
                        <div class="le-title">Modul Hak PJP2U Terkunci</div>
                        <div class="le-sub">Hak input laporan PAX terikat pada kontrak. Tambahkan kontrak dasar lebih dulu.</div>
                    </div>
                @elseif($mitra->pjp2u->isEmpty())
                    <div class="mitra-empty"><i class="bi bi-airplane"></i>Belum ada hak PJP2U untuk mitra ini.</div>
                @else
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mitra-table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Layanan</th>
                                    <th>Masa Berlaku</th>
                                    <th>Status</th>
                                    @if($canManageMitraMaster)
                                        <th>Aksi</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($mitra->pjp2u as $hakPjp2u)
                                    <tr>
                                        <td>
                                            <div class="fw-semibold">{{ $hakPjp2u->layananJasa->nama_layanan ?? '-' }}</div>
                                            <small class="text-muted">{{ $hakPjp2u->kontrakMitraJasa->nomor_kontrak ?? 'Tanpa Kontrak' }}</small>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                {{ optional($hakPjp2u->tanggal_mulai)->format('d/m/Y') ?: '-' }}
                                                s.d.
                                                {{ optional($hakPjp2u->tanggal_selesai)->format('d/m/Y') ?: 'seterusnya' }}
                                            </small>
                                        </td>
                                        <td>
                                            <span class="badge {{ $hakPjp2u->status_aktif ? 'bg-success' : 'bg-secondary' }}">
                                                {{ $hakPjp2u->status_aktif ? 'Aktif' : 'Nonaktif' }}
                                            </span>
                                        </td>
                                        @if($canManageMitraMaster)
                                            <td>
                                                <a href="{{ route('jasa.mitra.pjp2u.edit', [$mitra, $hakPjp2u]) }}" class="btn btn-sm btn-light border jasa-icon-btn" title="Edit" aria-label="Edit"><i class="bi bi-pencil"></i></a>
                                                <form method="POST" action="{{ route('jasa.mitra.pjp2u.deactivate', [$mitra, $hakPjp2u]) }}" class="d-inline" onsubmit="return confirm('Nonaktifkan hak PJP2U ini?');">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button class="btn btn-sm btn-light border text-warning jasa-icon-btn" {{ !$hakPjp2u->status_aktif ? 'disabled' : '' }} title="Nonaktifkan" aria-label="Nonaktifkan"><i class="bi bi-pause-circle"></i></button>
                                                </form>
                                            </td>
                                        @endif
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

    </div>
</div>

<div class="row g-4 mt-1">
    <div class="col-12">
        <div class="card mitra-section-card mt-4">
            <div class="card-header d-flex justify-content-between align-items-center gap-3 flex-wrap">
                <h5 class="mitra-section-title"><span class="mitra-section-icon"><i class="bi bi-bar-chart-line"></i></span>Riwayat Laporan Pendapatan/Penjualan</h5>
                @if($canManageMitraMaster)
                    <a href="{{ route('jasa.mitra.penjualan.create', $mitra) }}" class="btn btn-sm btn-primary fw-bold jasa-icon-btn" title="Tambah laporan" aria-label="Tambah laporan"><i class="bi bi-plus-lg"></i></a>
                @endif
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-hover mitra-table align-middle">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Layanan</th>
                                <th>Periode</th>
                                <th>Omzet</th>
                                <th>Persen</th>
                                <th>Nilai Tagihan</th>
                                <th>Status</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($laporanPendapatanRows as $penjualan)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                        <a href="{{ route('jasa.mitra.penjualan.show', [$mitra, $penjualan]) }}" class="text-decoration-none fw-semibold">
                                            {{ $penjualan->layananJasa->nama_layanan ?? '-' }}
                                        </a>
                                    </td>
                                    <td>{{ $tanggal($penjualan->periode_mulai) }} s.d. {{ $tanggal($penjualan->periode_selesai) }}</td>
                                    <td>{{ $rupiah($penjualan->total_omzet) }}</td>
                                    <td>{{ $penjualan->persentase_konsesi !== null ? rtrim(rtrim(number_format((float) $penjualan->persentase_konsesi, 4, ',', '.'), '0'), ',') . '%' : '-' }}</td>
                                    <td>{{ $rupiah($penjualan->nilai_tagihan) }}</td>
                                    <td><span class="badge bg-{{ $penjualan->status_color }}{{ in_array($penjualan->status, ['draft'], true) ? ' text-dark' : '' }}">{{ $penjualan->label_status }}</span></td>
                                    <td class="text-end">
                                        <div class="d-flex justify-content-end gap-1 flex-wrap align-items-center">
                                            @if($penjualan->file_laporan)
                                                <a href="{{ asset('storage/' . $penjualan->file_laporan) }}" target="_blank" class="btn btn-sm btn-light border jasa-icon-btn" title="File laporan" aria-label="File laporan"><i class="bi bi-paperclip"></i></a>
                                            @endif
                                            <a href="{{ route('jasa.mitra.penjualan.show', [$mitra, $penjualan]) }}" class="btn btn-sm btn-light border jasa-icon-btn" title="Detail" aria-label="Detail"><i class="bi bi-eye"></i></a>
                                            @if($canManageMitraMaster && in_array($penjualan->status, ['draft', 'ditolak'], true))
                                                <a href="{{ route('jasa.mitra.penjualan.edit', [$mitra, $penjualan]) }}" class="btn btn-sm btn-light border jasa-icon-btn" title="Edit" aria-label="Edit"><i class="bi bi-pencil"></i></a>
                                                <form method="POST" action="{{ route('jasa.mitra.penjualan.submit', [$mitra, $penjualan]) }}">
                                                    @csrf
                                                    <button class="btn btn-sm btn-primary jasa-icon-btn" title="Ajukan" aria-label="Ajukan"><i class="bi bi-send"></i></button>
                                                </form>
                                            @endif
                                            @if($penjualan->status === 'diajukan' && $penjualan->can_be_verified)
                                                <form method="POST" action="{{ route('jasa.mitra.penjualan.verify', [$mitra, $penjualan]) }}">
                                                    @csrf
                                                    <button class="btn btn-sm btn-success jasa-icon-btn" title="Verifikasi" aria-label="Verifikasi"><i class="bi bi-check2-circle"></i></button>
                                                </form>
                                                <form method="POST" action="{{ route('jasa.mitra.penjualan.reject', [$mitra, $penjualan]) }}" onsubmit="return confirm('Tolak laporan ini? Pastikan catatan sudah diisi.');" class="d-flex gap-1">
                                                    @csrf
                                                    <input type="text" name="catatan_verifikator" class="form-control form-control-sm" placeholder="Catatan penolakan" required style="width: 170px;">
                                                    <button class="btn btn-sm btn-danger jasa-icon-btn" title="Tolak" aria-label="Tolak"><i class="bi bi-x-circle"></i></button>
                                                </form>
                                            @endif
                                            @if($penjualan->status === 'diajukan' && ! $penjualan->can_be_verified)
                                                <span class="badge bg-warning text-dark" title="Verifikasi hanya dapat dilakukan setelah bulan pelaporan berakhir">
                                                    <i class="bi bi-hourglass-split"></i> Tunggu Ganti Bulan
                                                </span>
                                            @endif
                                            @if($penjualan->status === 'diverifikasi' && ! $penjualan->tagihan_jasa_id && $penjualan->layanan_jasa_id)
                                                @if($penjualan->can_create_tagihan)
                                                    <a href="{{ route('tagihan-jasa.create', ['penjualan_id' => $penjualan->id]) }}" class="btn btn-sm btn-primary jasa-icon-btn" title="Buat tagihan" aria-label="Buat tagihan"><i class="bi bi-receipt"></i></a>
                                                @else
                                                    <span class="badge bg-info text-dark" title="Tagihan dapat dibuat mulai {{ $penjualan->tagihan_available_date }}">
                                                        <i class="bi bi-calendar-check"></i> {{ $penjualan->tagihan_available_date }}
                                                    </span>
                                                @endif
                                            @endif
                                            @if($penjualan->status === 'ditagihkan' && $penjualan->tagihanJasa)
                                                <a href="{{ route('tagihan-jasa.show', $penjualan->tagihanJasa) }}" class="btn btn-sm btn-outline-primary jasa-icon-btn" title="Lihat tagihan" aria-label="Lihat tagihan"><i class="bi bi-receipt"></i></a>
                                            @endif
                                        </div>
                                        @if($penjualan->catatan_verifikator)
                                            <div class="small text-danger mt-1">{{ $penjualan->catatan_verifikator }}</div>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8"><div class="mitra-empty"><i class="bi bi-bar-chart-line"></i>Belum ada laporan pendapatan/penjualan.</div></td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card mitra-section-card mt-4">
            <div class="card-header d-flex justify-content-between align-items-center gap-3 flex-wrap">
                <h5 class="mitra-section-title"><span class="mitra-section-icon"><i class="bi bi-airplane"></i></span>Riwayat Laporan PAX PJP2U</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-hover mitra-table align-middle">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Layanan</th>
                                <th>Periode</th>
                                <th>Total PAX</th>
                                <th>Tarif</th>
                                <th>Nilai Tagihan</th>
                                <th>Status</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($laporanPjp2uRows as $penjualan)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                        <a href="{{ route('jasa.mitra.penjualan.show', [$mitra, $penjualan]) }}" class="text-decoration-none fw-semibold">
                                            {{ $penjualan->layananJasa->nama_layanan ?? '-' }}
                                        </a>
                                        @if($penjualan->nomor_penerbangan)
                                            <div class="small text-muted">{{ $penjualan->nomor_penerbangan }}</div>
                                        @endif
                                    </td>
                                    <td>{{ $tanggal($penjualan->periode_mulai) }} s.d. {{ $tanggal($penjualan->periode_selesai) }}</td>
                                    <td>{{ number_format((float) $penjualan->total_omzet, 0, ',', '.') }} pax</td>
                                    <td>{{ $rupiah($penjualan->layananJasa->tarif_dasar ?? 0) }}</td>
                                    <td>{{ $rupiah($penjualan->nilai_tagihan) }}</td>
                                    <td><span class="badge bg-{{ $penjualan->status_color }}{{ in_array($penjualan->status, ['draft'], true) ? ' text-dark' : '' }}">{{ $penjualan->label_status }}</span></td>
                                    <td class="text-end">
                                        <div class="d-flex justify-content-end gap-1 flex-wrap align-items-center">
                                            @if($penjualan->file_laporan)
                                                <a href="{{ asset('storage/' . $penjualan->file_laporan) }}" target="_blank" class="btn btn-sm btn-light border jasa-icon-btn" title="File laporan" aria-label="File laporan"><i class="bi bi-paperclip"></i></a>
                                            @endif
                                            <a href="{{ route('jasa.mitra.penjualan.show', [$mitra, $penjualan]) }}" class="btn btn-sm btn-light border jasa-icon-btn" title="Detail" aria-label="Detail"><i class="bi bi-eye"></i></a>
                                            @if($penjualan->status === 'diajukan' && $penjualan->can_be_verified)
                                                <form method="POST" action="{{ route('jasa.mitra.penjualan.verify', [$mitra, $penjualan]) }}">
                                                    @csrf
                                                    <button class="btn btn-sm btn-success jasa-icon-btn" title="Verifikasi" aria-label="Verifikasi"><i class="bi bi-check2-circle"></i></button>
                                                </form>
                                                <form method="POST" action="{{ route('jasa.mitra.penjualan.reject', [$mitra, $penjualan]) }}" onsubmit="return confirm('Tolak laporan ini? Pastikan catatan sudah diisi.');" class="d-flex gap-1">
                                                    @csrf
                                                    <input type="text" name="catatan_verifikator" class="form-control form-control-sm" placeholder="Catatan penolakan" required style="width: 170px;">
                                                    <button class="btn btn-sm btn-danger jasa-icon-btn" title="Tolak" aria-label="Tolak"><i class="bi bi-x-circle"></i></button>
                                                </form>
                                            @endif
                                            @if($penjualan->status === 'diajukan' && ! $penjualan->can_be_verified)
                                                <span class="badge bg-warning text-dark" title="Verifikasi hanya dapat dilakukan setelah bulan pelaporan berakhir">
                                                    <i class="bi bi-hourglass-split"></i> Tunggu Ganti Bulan
                                                </span>
                                            @endif
                                            @if($penjualan->status === 'diverifikasi' && ! $penjualan->tagihan_jasa_id && $penjualan->layanan_jasa_id)
                                                @if($penjualan->can_create_tagihan)
                                                    <a href="{{ route('tagihan-jasa.create', ['penjualan_id' => $penjualan->id]) }}" class="btn btn-sm btn-primary jasa-icon-btn" title="Buat tagihan" aria-label="Buat tagihan"><i class="bi bi-receipt"></i></a>
                                                @else
                                                    <span class="badge bg-info text-dark" title="Tagihan dapat dibuat mulai {{ $penjualan->tagihan_available_date }}">
                                                        <i class="bi bi-calendar-check"></i> {{ $penjualan->tagihan_available_date }}
                                                    </span>
                                                @endif
                                            @endif
                                            @if($penjualan->status === 'ditagihkan' && $penjualan->tagihanJasa)
                                                <a href="{{ route('tagihan-jasa.show', $penjualan->tagihanJasa) }}" class="btn btn-sm btn-outline-primary jasa-icon-btn" title="Lihat tagihan" aria-label="Lihat tagihan"><i class="bi bi-receipt"></i></a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8"><div class="mitra-empty"><i class="bi bi-airplane"></i>Belum ada laporan PAX PJP2U.</div></td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="tw-scope mt-4">
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-[0_18px_45px_rgba(15,23,42,.08)]">
                <div class="bg-gradient-to-r from-sky-600 via-cyan-600 to-teal-500 px-5 py-5 text-white sm:px-6">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                        <div class="flex items-start gap-3">
                            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-white/15 ring-1 ring-white/25">
                                <i class="bi bi-receipt text-xl"></i>
                            </div>
                            <div>
                                <p class="mb-1 text-xs font-bold uppercase tracking-wide text-cyan-100">Tagihan Konsesi</p>
                                <h5 class="mb-0 text-xl font-bold text-white">Riwayat Tagihan Konsesi</h5>
                                <p class="mb-0 mt-1 text-sm text-cyan-50">Tagihan yang terbentuk dari laporan pendapatan/penjualan mitra.</p>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-2 sm:min-w-[320px]">
                            <div class="rounded-xl bg-white/14 p-3 ring-1 ring-white/20">
                                <div class="text-xs font-semibold text-cyan-50">Jumlah</div>
                                <div class="mt-1 text-2xl font-black text-white">{{ number_format($tagihanKonsesiCount, 0, ',', '.') }}</div>
                            </div>
                            <div class="rounded-xl bg-white/14 p-3 ring-1 ring-white/20">
                                <div class="text-xs font-semibold text-cyan-50">Total</div>
                                <div class="mt-1 text-lg font-black text-white">{{ $rupiah($totalTagihanKonsesi) }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="p-4 sm:p-5">
                    @if($tagihanKonsesiInvoiceRows->isEmpty())
                        <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-6 py-10 text-center">
                            <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-2xl bg-white text-slate-400 shadow-sm">
                                <i class="bi bi-receipt text-2xl"></i>
                            </div>
                            <div class="font-semibold text-slate-700">Belum ada tagihan konsesi.</div>
                            <div class="mt-1 text-sm text-slate-500">Tagihan akan tampil setelah laporan diverifikasi dan dibuatkan tagihan.</div>
                        </div>
                    @else
                        <div class="overflow-hidden rounded-2xl border border-slate-200">
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-slate-200 text-sm">
                                    <thead class="bg-slate-50">
                                        <tr>
                                            <th class="px-4 py-3 text-left text-xs font-bold uppercase text-slate-500">No</th>
                                            <th class="px-4 py-3 text-left text-xs font-bold uppercase text-slate-500">No Tagihan</th>
                                            <th class="px-4 py-3 text-left text-xs font-bold uppercase text-slate-500">Layanan</th>
                                            <th class="px-4 py-3 text-left text-xs font-bold uppercase text-slate-500">Periode</th>
                                            <th class="px-4 py-3 text-left text-xs font-bold uppercase text-slate-500">Jatuh Tempo</th>
                                            <th class="px-4 py-3 text-left text-xs font-bold uppercase text-slate-500">Total</th>
                                            <th class="px-4 py-3 text-left text-xs font-bold uppercase text-slate-500">Status</th>
                                            <th class="px-4 py-3 text-right text-xs font-bold uppercase text-slate-500">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 bg-white">
                                        @foreach($tagihanKonsesiInvoiceRows as $row)
                                            @php($tagihan = $row['tagihan'])
                                            <tr class="transition hover:bg-cyan-50/50">
                                                <td class="whitespace-nowrap px-4 py-4 font-semibold text-slate-500">{{ $loop->iteration }}</td>
                                                <td class="whitespace-nowrap px-4 py-4 font-bold text-slate-900">{{ $tagihan->nomor_tagihan ?? '-' }}</td>
                                                <td class="px-4 py-4">
                                                    <div class="max-w-[280px] font-semibold text-slate-800">{{ $row['layanan'] }}</div>
                                                    <div class="max-w-[420px] truncate text-xs text-slate-500" title="{{ $row['subtext'] }}">{{ $row['subtext'] }}</div>
                                                </td>
                                                <td class="whitespace-nowrap px-4 py-4 text-slate-600">{{ $row['periode'] }}</td>
                                                <td class="whitespace-nowrap px-4 py-4 text-slate-600">{{ $row['jatuh_tempo'] }}</td>
                                                <td class="whitespace-nowrap px-4 py-4 font-black text-emerald-600">{{ $rupiah($row['total']) }}</td>
                                                <td class="whitespace-nowrap px-4 py-4">
                                                    <span class="inline-flex items-center rounded-full bg-sky-50 px-2.5 py-1 text-xs font-bold text-sky-700 ring-1 ring-inset ring-sky-200">
                                                        <span class="mr-1.5 h-1.5 w-1.5 rounded-full bg-sky-500"></span>{{ $row['status'] }}
                                                    </span>
                                                </td>
                                                <td class="whitespace-nowrap px-4 py-4 text-right">
                                                    @if($tagihan)
                                                        <a href="{{ route('tagihan-jasa.show', $tagihan) }}" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-xs font-bold text-slate-700 shadow-sm transition hover:border-cyan-300 hover:bg-cyan-50 hover:text-cyan-700" title="Detail" aria-label="Detail">
                                                            <i class="bi bi-eye"></i>
                                                        </a>
                                                    @else
                                                        <span class="text-slate-400">-</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="tw-scope mt-4">
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-[0_18px_45px_rgba(15,23,42,.08)]">
                <div class="bg-gradient-to-r from-teal-700 via-cyan-600 to-blue-500 px-5 py-5 text-white sm:px-6">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                        <div class="flex items-start gap-3">
                            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-white/15 ring-1 ring-white/25">
                                <i class="bi bi-droplet-half text-xl"></i>
                            </div>
                            <div>
                                <p class="mb-1 text-xs font-bold uppercase tracking-wide text-cyan-100">Tagihan Utilitas</p>
                                <h5 class="mb-0 text-xl font-bold text-white">Riwayat Tagihan Air & Listrik</h5>
                                <p class="mb-0 mt-1 text-sm text-cyan-50">Tagihan yang terbentuk dari laporan pemakaian utilitas mitra.</p>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-2 sm:min-w-[320px]">
                            <div class="rounded-xl bg-white/14 p-3 ring-1 ring-white/20">
                                <div class="text-xs font-semibold text-cyan-50">Jumlah</div>
                                <div class="mt-1 text-2xl font-black text-white">{{ number_format($tagihanUtilitasRows->count(), 0, ',', '.') }}</div>
                            </div>
                            <div class="rounded-xl bg-white/14 p-3 ring-1 ring-white/20">
                                <div class="text-xs font-semibold text-cyan-50">Total</div>
                                <div class="mt-1 text-lg font-black text-white">{{ $rupiah($totalTagihanUtilitas) }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="p-4 sm:p-5">
                    @if($tagihanUtilitasRows->isEmpty())
                        <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-6 py-10 text-center">
                            <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-2xl bg-white text-slate-400 shadow-sm">
                                <i class="bi bi-droplet-half text-2xl"></i>
                            </div>
                            <div class="font-semibold text-slate-700">Belum ada tagihan air/listrik.</div>
                            <div class="mt-1 text-sm text-slate-500">Tagihan utilitas akan tampil setelah laporan air/listrik dibuatkan tagihan.</div>
                        </div>
                    @else
                        <div class="overflow-hidden rounded-2xl border border-slate-200">
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-slate-200 text-sm">
                                    <thead class="bg-slate-50">
                                        <tr>
                                            <th class="px-4 py-3 text-left text-xs font-bold uppercase text-slate-500">No</th>
                                            <th class="px-4 py-3 text-left text-xs font-bold uppercase text-slate-500">No Tagihan</th>
                                            <th class="px-4 py-3 text-left text-xs font-bold uppercase text-slate-500">Jenis</th>
                                            <th class="px-4 py-3 text-left text-xs font-bold uppercase text-slate-500">Periode</th>
                                            <th class="px-4 py-3 text-left text-xs font-bold uppercase text-slate-500">Pemakaian</th>
                                            <th class="px-4 py-3 text-left text-xs font-bold uppercase text-slate-500">Total</th>
                                            <th class="px-4 py-3 text-left text-xs font-bold uppercase text-slate-500">Status</th>
                                            <th class="px-4 py-3 text-right text-xs font-bold uppercase text-slate-500">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 bg-white">
                                        @foreach($tagihanUtilitasRows as $laporan)
                                            @php($tagihan = $laporan->tagihanJasa)
                                            <tr class="transition hover:bg-cyan-50/50">
                                                <td class="whitespace-nowrap px-4 py-4 font-semibold text-slate-500">{{ $loop->iteration }}</td>
                                                <td class="whitespace-nowrap px-4 py-4 font-bold text-slate-900">{{ $tagihan->nomor_tagihan ?? '-' }}</td>
                                                <td class="px-4 py-4">
                                                    <div class="font-semibold capitalize text-slate-800">{{ $laporan->jenis }}</div>
                                                    <div class="text-xs text-slate-500">{{ $laporan->layananJasa->nama_layanan ?? '-' }}</div>
                                                </td>
                                                <td class="whitespace-nowrap px-4 py-4 text-slate-600">{{ str_pad($laporan->bulan, 2, '0', STR_PAD_LEFT) }}/{{ $laporan->tahun }}</td>
                                                <td class="whitespace-nowrap px-4 py-4 font-semibold text-slate-700">{{ number_format((float) $laporan->pemakaian, 2, ',', '.') }}</td>
                                                <td class="whitespace-nowrap px-4 py-4 font-black text-emerald-600">{{ $rupiah($tagihan->total_tagihan ?? $laporan->total_biaya) }}</td>
                                                <td class="whitespace-nowrap px-4 py-4">
                                                    <span class="inline-flex items-center rounded-full bg-cyan-50 px-2.5 py-1 text-xs font-bold text-cyan-700 ring-1 ring-inset ring-cyan-200">
                                                        <span class="mr-1.5 h-1.5 w-1.5 rounded-full bg-cyan-500"></span>{{ $tagihan->status ?? $laporan->status }}
                                                    </span>
                                                </td>
                                                <td class="whitespace-nowrap px-4 py-4 text-right">
                                                    @if($tagihan)
                                                        <a href="{{ route('tagihan-jasa.show', $tagihan) }}" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-xs font-bold text-slate-700 shadow-sm transition hover:border-cyan-300 hover:bg-cyan-50 hover:text-cyan-700" title="Detail" aria-label="Detail">
                                                            <i class="bi bi-eye"></i>
                                                        </a>
                                                    @else
                                                        <span class="text-slate-400">-</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="tw-scope mt-4">
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-[0_18px_45px_rgba(15,23,42,.08)]">
                <div class="bg-gradient-to-r from-blue-700 via-sky-600 to-cyan-500 px-5 py-5 text-white sm:px-6">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                        <div class="flex items-start gap-3">
                            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-white/15 ring-1 ring-white/25">
                                <i class="bi bi-airplane text-xl"></i>
                            </div>
                            <div>
                                <p class="mb-1 text-xs font-bold uppercase tracking-wide text-sky-100">Tagihan PJP2U</p>
                                <h5 class="mb-0 text-xl font-bold text-white">Riwayat Tagihan PAX PJP2U</h5>
                                <p class="mb-0 mt-1 text-sm text-sky-50">Tagihan yang terbentuk dari laporan PAX PJP2U mitra.</p>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-2 sm:min-w-[320px]">
                            <div class="rounded-xl bg-white/14 p-3 ring-1 ring-white/20">
                                <div class="text-xs font-semibold text-sky-50">Jumlah</div>
                                <div class="mt-1 text-2xl font-black text-white">{{ number_format($tagihanPjp2uRows->count(), 0, ',', '.') }}</div>
                            </div>
                            <div class="rounded-xl bg-white/14 p-3 ring-1 ring-white/20">
                                <div class="text-xs font-semibold text-sky-50">Total</div>
                                <div class="mt-1 text-lg font-black text-white">{{ $rupiah($totalTagihanPjp2u) }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="p-4 sm:p-5">
                    @if($tagihanPjp2uRows->isEmpty())
                        <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-6 py-10 text-center">
                            <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-2xl bg-white text-slate-400 shadow-sm">
                                <i class="bi bi-airplane text-2xl"></i>
                            </div>
                            <div class="font-semibold text-slate-700">Belum ada tagihan PJP2U.</div>
                            <div class="mt-1 text-sm text-slate-500">Tagihan PJP2U akan tampil setelah laporan PAX diverifikasi dan dibuatkan tagihan.</div>
                        </div>
                    @else
                        <div class="overflow-hidden rounded-2xl border border-slate-200">
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-slate-200 text-sm">
                                    <thead class="bg-slate-50">
                                        <tr>
                                            <th class="px-4 py-3 text-left text-xs font-bold uppercase text-slate-500">No</th>
                                            <th class="px-4 py-3 text-left text-xs font-bold uppercase text-slate-500">No Tagihan</th>
                                            <th class="px-4 py-3 text-left text-xs font-bold uppercase text-slate-500">Layanan</th>
                                            <th class="px-4 py-3 text-left text-xs font-bold uppercase text-slate-500">Periode</th>
                                            <th class="px-4 py-3 text-left text-xs font-bold uppercase text-slate-500">PAX</th>
                                            <th class="px-4 py-3 text-left text-xs font-bold uppercase text-slate-500">Total</th>
                                            <th class="px-4 py-3 text-left text-xs font-bold uppercase text-slate-500">Status</th>
                                            <th class="px-4 py-3 text-right text-xs font-bold uppercase text-slate-500">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 bg-white">
                                        @foreach($tagihanPjp2uRows as $penjualan)
                                            @php($tagihan = $penjualan->tagihanJasa)
                                            <tr class="transition hover:bg-sky-50/50">
                                                <td class="whitespace-nowrap px-4 py-4 font-semibold text-slate-500">{{ $loop->iteration }}</td>
                                                <td class="whitespace-nowrap px-4 py-4">
                                                    <div class="font-bold text-slate-900">{{ $tagihan->nomor_tagihan ?? '-' }}</div>
                                                </td>
                                                <td class="px-4 py-4">
                                                    <div class="max-w-[280px] font-semibold text-slate-800">{{ $penjualan->layananJasa->nama_layanan ?? '-' }}</div>
                                                    <div class="text-xs text-slate-500">{{ $penjualan->nomor_penerbangan ?: '-' }}</div>
                                                </td>
                                                <td class="whitespace-nowrap px-4 py-4 text-slate-600">{{ $tanggal($penjualan->periode_mulai) }} s.d. {{ $tanggal($penjualan->periode_selesai) }}</td>
                                                <td class="whitespace-nowrap px-4 py-4 font-semibold text-slate-700">{{ number_format((float) $penjualan->total_omzet, 0, ',', '.') }}</td>
                                                <td class="whitespace-nowrap px-4 py-4 font-black text-emerald-600">{{ $rupiah($tagihan->total_tagihan ?? $penjualan->nilai_tagihan) }}</td>
                                                <td class="whitespace-nowrap px-4 py-4">
                                                    <span class="inline-flex items-center rounded-full bg-sky-50 px-2.5 py-1 text-xs font-bold text-sky-700 ring-1 ring-inset ring-sky-200">
                                                        <span class="mr-1.5 h-1.5 w-1.5 rounded-full bg-sky-500"></span>{{ $tagihan->status ?? $penjualan->status }}
                                                    </span>
                                                </td>
                                                <td class="whitespace-nowrap px-4 py-4 text-right">
                                                    @if($tagihan)
                                                        <a href="{{ route('tagihan-jasa.show', $tagihan) }}" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-xs font-bold text-slate-700 shadow-sm transition hover:border-sky-300 hover:bg-sky-50 hover:text-sky-700" title="Detail" aria-label="Detail">
                                                            <i class="bi bi-eye"></i>
                                                        </a>
                                                    @else
                                                        <span class="text-slate-400">-</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="tw-scope mt-4">
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-[0_18px_45px_rgba(15,23,42,.08)]">
                <div class="bg-gradient-to-r from-indigo-600 via-violet-600 to-fuchsia-500 px-5 py-5 text-white sm:px-6">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                        <div class="flex items-start gap-3">
                            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-white/15 ring-1 ring-white/25">
                                <i class="bi bi-journal-check text-xl"></i>
                            </div>
                            <div>
                                <p class="mb-1 text-xs font-bold uppercase tracking-wide text-violet-100">Tagihan PNBP</p>
                                <h5 class="mb-0 text-xl font-bold text-white">Riwayat Tagihan PNBP</h5>
                                <p class="mb-0 mt-1 text-sm text-violet-50">Daftar tagihan PNBP yang terkait dengan mitra jasa ini.</p>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-2 sm:min-w-[320px]">
                            <div class="rounded-xl bg-white/14 p-3 ring-1 ring-white/20">
                                <div class="text-xs font-semibold text-violet-50">Jumlah</div>
                                <div class="mt-1 text-2xl font-black text-white">{{ number_format($tagihanPnbpCount, 0, ',', '.') }}</div>
                            </div>
                            <div class="rounded-xl bg-white/14 p-3 ring-1 ring-white/20">
                                <div class="text-xs font-semibold text-violet-50">Total</div>
                                <div class="mt-1 text-lg font-black text-white">{{ $rupiah($totalTagihanPnbp) }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="p-4 sm:p-5">
                    @if($tagihanPnbpRows->isEmpty())
                        <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-6 py-10 text-center">
                            <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-2xl bg-white text-slate-400 shadow-sm">
                                <i class="bi bi-journal-check text-2xl"></i>
                            </div>
                            <div class="font-semibold text-slate-700">Belum ada tagihan PNBP.</div>
                            <div class="mt-1 text-sm text-slate-500">Data tagihan PNBP akan muncul setelah diterbitkan untuk mitra ini.</div>
                        </div>
                    @else
                        <div class="overflow-hidden rounded-2xl border border-slate-200">
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-slate-200 text-sm">
                                    <thead class="bg-slate-50">
                                        <tr>
                                            <th class="px-4 py-3 text-left text-xs font-bold uppercase text-slate-500">No</th>
                                            <th class="px-4 py-3 text-left text-xs font-bold uppercase text-slate-500">No Tagihan</th>
                                            <th class="px-4 py-3 text-left text-xs font-bold uppercase text-slate-500">Layanan</th>
                                            <th class="px-4 py-3 text-left text-xs font-bold uppercase text-slate-500">Tanggal</th>
                                            <th class="px-4 py-3 text-left text-xs font-bold uppercase text-slate-500">Total</th>
                                            <th class="px-4 py-3 text-left text-xs font-bold uppercase text-slate-500">Status</th>
                                            <th class="px-4 py-3 text-left text-xs font-bold uppercase text-slate-500">Jatuh Tempo</th>
                                            <th class="px-4 py-3 text-right text-xs font-bold uppercase text-slate-500">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 bg-white">
                                        @foreach($tagihanPnbpRows as $row)
                                            <tr class="transition hover:bg-violet-50/50">
                                                <td class="whitespace-nowrap px-4 py-4 font-semibold text-slate-500">{{ $loop->iteration }}</td>
                                                <td class="whitespace-nowrap px-4 py-4">
                                                    <div class="font-bold text-slate-900">{{ $row['nomor'] }}</div>
                                                </td>
                                                <td class="px-4 py-4">
                                                    @if($row['layanan_names']->isNotEmpty())
                                                        <div class="max-w-[320px] font-semibold text-slate-800">
                                                            {{ $row['layanan_names']->take(2)->join(', ') }}
                                                            @if($row['layanan_names']->count() > 2)
                                                                <span class="font-bold text-violet-600">+{{ $row['layanan_names']->count() - 2 }} layanan</span>
                                                            @endif
                                                        </div>
                                                    @else
                                                        <span class="text-slate-400">-</span>
                                                    @endif
                                                </td>
                                                <td class="whitespace-nowrap px-4 py-4 text-slate-600">{{ $row['tanggal'] }}</td>
                                                <td class="whitespace-nowrap px-4 py-4 font-black text-emerald-600">{{ $rupiah($row['total']) }}</td>
                                                <td class="whitespace-nowrap px-4 py-4">
                                                    <span class="inline-flex items-center rounded-full bg-amber-50 px-2.5 py-1 text-xs font-bold capitalize text-amber-700 ring-1 ring-inset ring-amber-200">
                                                        <span class="mr-1.5 h-1.5 w-1.5 rounded-full bg-amber-500"></span>{{ $row['status'] }}
                                                    </span>
                                                </td>
                                                <td class="whitespace-nowrap px-4 py-4 text-slate-600">{{ $row['jatuh_tempo'] }}</td>
                                                <td class="whitespace-nowrap px-4 py-4 text-right">
                                                    <a href="{{ route('tagihan-jasa.show', $row['tagihan']) }}" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-xs font-bold text-slate-700 shadow-sm transition hover:border-violet-300 hover:bg-violet-50 hover:text-violet-700" title="Detail" aria-label="Detail">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
</div>
@endsection
