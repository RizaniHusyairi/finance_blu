@extends('layouts.app')
@section('title', 'Master Data Layanan Jasa')

@push('css')
    <script>
        window.tailwind = window.tailwind || {};
        window.tailwind.config = { corePlugins: { preflight: false } };
    </script>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @keyframes blueHeaderGlow {
            0%, 100% { opacity: .55; transform: translate3d(-28px, 0, 0) scale(1); }
            50% { opacity: .95; transform: translate3d(72px, -18px, 0) scale(1.12); }
        }
        @keyframes blueHeaderSweep {
            0% { transform: translateX(-120%) skewX(-18deg); opacity: 0; }
            18% { opacity: .35; }
            45%, 100% { transform: translateX(220%) skewX(-18deg); opacity: 0; }
        }
        .blue-animated-header { position: relative; isolation: isolate; }
        .blue-animated-header::before,
        .blue-animated-header::after,
        .blue-animated-header .blue-header-wave {
            content: "";
            position: absolute;
            pointer-events: none;
            z-index: -1;
        }
        .blue-animated-header::before {
            width: 360px;
            height: 360px;
            right: 8%;
            top: -170px;
            border-radius: 999px;
            background: radial-gradient(circle, rgba(125, 211, 252, .32), rgba(59, 130, 246, .22) 42%, transparent 68%);
            animation: blueHeaderGlow 4.5s ease-in-out infinite;
        }
        .blue-animated-header::after {
            inset: 0;
            width: 48%;
            background: linear-gradient(90deg, transparent, rgba(125,211,252,.16), rgba(255,255,255,.24), rgba(96,165,250,.14), transparent);
            animation: blueHeaderSweep 3.8s ease-in-out infinite;
        }
        .blue-animated-header .blue-header-wave {
            left: -90px;
            bottom: -120px;
            width: 420px;
            height: 230px;
            border-radius: 999px;
            background: radial-gradient(circle, rgba(125, 211, 252, .22), transparent 65%);
            animation: blueHeaderGlow 5.2s ease-in-out infinite reverse;
        }
        .service-tabs .nav-link {
            border: 1px solid #dbe3ef;
            color: #475569;
            font-weight: 700;
            padding: 8px 14px;
        }

        .service-tabs .nav-link.active {
            background: #0d6efd;
            border-color: #0d6efd;
            color: #fff;
        }

        .service-tree-card {
            border: 1px solid rgba(37, 99, 235, .12);
            border-radius: 18px;
            background: #fff;
            overflow: hidden;
            box-shadow: 0 16px 42px rgba(37, 99, 235, .08);
        }

        .service-tree-header {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 12px;
            align-items: center;
            padding: 10px 14px;
            border-bottom: 1px solid #bfdbfe;
            background: linear-gradient(90deg, #eff6ff 0%, #f8fbff 58%, #ffffff 100%);
        }

        .soft-table-title {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .soft-table-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 34px;
            height: 34px;
            flex: 0 0 34px;
            border-radius: 9px;
            background: #1d4ed8;
            color: #fff;
            box-shadow: 0 10px 20px rgba(37, 99, 235, .18);
        }

        .soft-table-title h6 {
            margin: 0;
            color: #1e3a8a;
            font-weight: 800;
        }

        .soft-table-title .text-muted {
            color: #64748b !important;
            font-size: 12px;
            font-weight: 700;
        }

        .service-tree-body {
            padding: 22px 20px;
        }

        .service-tree-tools {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 12px;
            align-items: center;
            padding: 14px 20px 0;
        }

        .service-search-box {
            position: relative;
        }

        .service-search-box .bi-search {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #2563eb;
            pointer-events: none;
        }

        .service-search-box input {
            border: 1px solid #bfdbfe;
            border-radius: 14px;
            min-height: 44px;
            padding-left: 40px;
            box-shadow: 0 8px 24px rgba(37, 99, 235, .07);
        }

        .service-search-box input:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, .12);
        }

        .service-search-count {
            border: 1px solid #bfdbfe;
            border-radius: 999px;
            background: #eff6ff;
            color: #1d4ed8;
            font-size: 12px;
            font-weight: 900;
            padding: 8px 12px;
            white-space: nowrap;
        }

        .service-tree-panel {
            border: 1px solid #dbe3ef;
            border-radius: 14px;
            background: #fff;
            padding: 12px 16px;
            max-height: 560px;
            overflow: auto;
        }

        .tree-node {
            --level: 0;
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 14px;
            align-items: center;
            padding: 6px 0 6px calc(var(--level) * 20px);
            border-bottom: 0;
            transition: background-color .18s ease;
        }

        .tree-node:hover {
            background: rgba(239, 246, 255, .55);
        }

        .tree-node.is-search-match {
            border-radius: 10px;
            background: #eff6ff;
        }

        .tree-node.is-search-match .tree-title {
            color: #1d4ed8;
        }

        .tree-main {
            min-width: 0;
            display: flex;
            align-items: flex-start;
            gap: 7px;
        }

        .tree-branch {
            color: #111827;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
            line-height: 1.35;
            flex: 0 0 22px;
            font-size: 14px;
            white-space: pre;
        }

        .tree-title {
            color: #2563a8;
            font-weight: 800;
            line-height: 1.35;
            word-break: break-word;
            letter-spacing: .01em;
        }

        .tree-meta {
            color: #64748b;
            font-size: 12px;
            margin-top: 2px;
        }

        .tree-actions {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 8px;
            flex-wrap: wrap;
        }

        .tree-actions .badge {
            border-radius: 7px;
            font-size: 10px;
            font-weight: 900;
            padding: 5px 8px;
        }

        .tree-action-buttons {
            display: flex;
            gap: 4px;
        }

        .tree-toggle {
            border: 0;
            background: transparent;
            color: #334155;
            padding: 0;
            margin-top: 1px;
            line-height: 1;
        }

        .tree-toggle-spacer {
            width: 15px;
            flex: 0 0 15px;
        }

        .tree-toggle .bi {
            font-size: 14px;
        }

        .tree-node.is-collapsed .bi-dash-square {
            display: none;
        }

        .tree-node:not(.is-collapsed) .bi-plus-square {
            display: none;
        }

        .tree-folder-closed {
            display: none;
        }

        .tree-item-count {
            min-width: 44px;
            text-align: right;
            color: #475569;
            font-size: 12px;
            font-weight: 700;
        }

        .tree-node.is-collapsed .tree-folder-open {
            display: none;
        }

        .tree-node.is-collapsed .tree-folder-closed {
            display: inline-block;
        }

        .tree-empty {
            min-height: 170px;
            display: grid;
            place-items: center;
            color: #64748b;
            text-align: center;
        }

        @media (max-width: 768px) {
            .service-tree-header,
            .tree-node {
                grid-template-columns: 1fr;
            }

            .tree-actions {
                justify-content: flex-start;
                padding-left: calc(var(--level) * 20px + 28px);
            }

            .service-tree-tools {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endpush

@section('content')
@php
    $childrenByParent = $layanans->groupBy(fn ($layanan) => $layanan->parent_id ?: 0);
    $visibleIds = $layanans->pluck('id')->map(fn ($id) => (int) $id)->all();
    $visibleIdSet = array_flip($visibleIds);
    $roots = $childrenByParent->get(0, collect());

    $countVisibleChildren = function ($layanan) use (&$countVisibleChildren, $childrenByParent) {
        return $childrenByParent->get($layanan->id, collect())->reduce(function ($count, $child) use (&$countVisibleChildren) {
            return $count + 1 + $countVisibleChildren($child);
        }, 0);
    };

    $renderNode = function ($layanan, $level = 0) use (&$renderNode, $childrenByParent, $countVisibleChildren, $canManageMaster, $visibleIdSet) {
        $children = $childrenByParent->get($layanan->id, collect())
            ->filter(fn ($child) => isset($visibleIdSet[$child->id]))
            ->sortBy('nama_layanan', SORT_NATURAL | SORT_FLAG_CASE);
        $hasChildren = $children->isNotEmpty();
        $type = $layanan->tipe_layanan ?? 'PNBP';
        $hasKonsesi = (bool) ($layanan->mendukung_konsesi ?? false);
        $childCount = $countVisibleChildren($layanan);
        $branch = '|_';
        $nodeClasses = 'tree-node' . ($hasChildren ? '' : ' tree-leaf');
        $searchText = collect([
            $layanan->nama_layanan,
            $layanan->kode_layanan,
            $layanan->kode_mak,
            $layanan->kode_jenis_pembayaran,
            $layanan->kode_pembayaran_lengkap,
            $layanan->kode_akun,
            $layanan->satuan,
            $layanan->tarif_dasar,
            $layanan->tipe_layanan,
            $hasKonsesi ? 'konsesi' : null,
            $layanan->is_leaf ? 'tarif item tarif' : 'jenis kategori',
            $layanan->is_active ? 'aktif' : 'nonaktif',
        ])->filter()->implode(' ');
@endphp
        <div class="{{ $nodeClasses }} {{ $hasChildren ? 'is-collapsed' : '' }}" data-node-id="{{ $layanan->id }}" data-parent-id="{{ $layanan->parent_id ?: 0 }}" data-search="{{ e(mb_strtolower($searchText)) }}" style="--level: {{ $level }};">
            <div class="tree-main">
                <span class="tree-branch">{{ $branch }}</span>
                @if($hasChildren)
                    <button type="button" class="tree-toggle" data-tree-toggle="{{ $layanan->id }}" aria-label="Buka tutup layanan">
                        <i class="bi bi-dash-square"></i>
                        <i class="bi bi-plus-square"></i>
                    </button>
                    <i class="bi bi-folder2-open text-primary mt-1 tree-folder-open"></i>
                    <i class="bi bi-folder text-primary mt-1 tree-folder-closed"></i>
                @else
                    <span class="tree-toggle-spacer"></span>
                    <i class="bi bi-folder2-open text-primary mt-1"></i>
                @endif
                <div class="min-w-0">
                    <div class="tree-title">{{ $layanan->nama_layanan }}</div>
                    <div class="tree-meta">
                        @if($layanan->kode_pembayaran_lengkap)
                            Kode bayar {{ $layanan->kode_pembayaran_lengkap }}
                        @elseif($layanan->kode_mak)
                            MAK {{ $layanan->kode_mak }}
                        @else
                            -
                        @endif
                        <span class="mx-1">|</span>
                        @if($layanan->is_leaf)
                            Tarif Rp {{ number_format($layanan->tarif_dasar ?? 0, 0, ',', '.') }}{{ $layanan->satuan ? ' / ' . $layanan->satuan : '' }}
                            @if($hasKonsesi && $layanan->persentase_konsesi !== null)
                                <span class="mx-1">|</span>
                                Konsesi {{ rtrim(rtrim(number_format((float) $layanan->persentase_konsesi, 4, ',', '.'), '0'), ',') }}%
                            @endif
                        @else
                            {{ $childCount }} item
                        @endif
                        <span class="mx-1">|</span>
                        {{ $layanan->is_active ? 'Aktif' : 'Nonaktif' }}
                    </div>
                </div>
            </div>
            <div class="tree-actions">
                <span class="badge {{ $type === 'KONSESI' ? 'bg-info text-dark' : 'bg-primary' }}">{{ $type === 'KONSESI' ? 'Konsesi Saja' : 'PNBP' }}</span>
                @if($hasKonsesi)
                    <span class="badge bg-success">Ada Konsesi</span>
                @endif
                <span class="badge {{ $layanan->is_leaf ? 'bg-warning text-dark' : 'bg-primary' }}">{{ $layanan->is_leaf ? 'Tarif' : ($level === 0 ? 'Jenis Layanan' : 'Kategori') }}</span>
                @if($canManageMaster)
                    <div class="tree-action-buttons">
                        <a href="{{ route('master-layanan-jasa.edit', $layanan->id) }}" class="btn btn-sm btn-light text-primary border" title="Edit">
                            <i class="bi bi-pencil-square"></i>
                        </a>
                        <form action="{{ route('master-layanan-jasa.destroy', $layanan->id) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus data ini?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-light text-danger border" title="Hapus">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    </div>
                @endif
                <span class="tree-item-count">{{ $hasChildren ? $childCount . ' item' : 'leaf' }}</span>
            </div>
        </div>
@php
        foreach ($children as $child) {
            echo $renderNode($child, $level + 1);
        }
    };
@endphp

<div class="tw-scope">
<div class="blue-animated-header mb-4 overflow-hidden rounded-3xl border border-blue-900/20 bg-gradient-to-r from-[#12355c] via-[#174f86] to-[#1d65a6] px-5 py-5 shadow-[0_18px_50px_rgba(18,53,92,.22)] sm:px-6">
    <span class="blue-header-wave"></span>
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div class="flex items-start gap-3">
            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-blue-600 text-white shadow-lg shadow-blue-600/20">
                <i class="bi bi-diagram-3 text-xl"></i>
            </div>
            <div>
                <h4 class="mb-1 text-xl font-black text-white lg:text-2xl">{{ $canManageMaster ? 'Master Data Layanan Jasa' : 'Layanan Jasa Dikelola' }}</h4>
                <p class="mb-0 text-sm font-semibold text-blue-100/80">{{ $canManageMaster ? 'Satu master layanan dengan pemisahan tipe PNBP dan Konsesi' : 'Daftar layanan jasa yang ditugaskan kepada Admin Jasa login' }}</p>
            </div>
        </div>
        @if($canManageMaster)
            <a href="{{ route('master-layanan-jasa.create') }}" class="inline-flex items-center justify-center rounded-xl bg-white px-4 py-2 text-sm font-bold text-blue-700 shadow-lg shadow-slate-950/15 transition hover:-translate-y-0.5 hover:bg-blue-50">
                <i class="bi bi-plus-lg me-2"></i>Tambah Layanan
            </a>
        @endif
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        <i class="bi bi-check-circle me-2"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="bi bi-exclamation-triangle me-2"></i> {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<ul class="nav nav-pills service-tabs gap-2 mb-3">
    <li class="nav-item">
        <a class="nav-link {{ $tipe === 'SEMUA' ? 'active' : '' }}" href="{{ route('master-layanan-jasa.index') }}">
            Semua <span class="badge {{ $tipe === 'SEMUA' ? 'bg-light text-primary' : 'bg-secondary' }} ms-1">{{ $counts['SEMUA'] ?? 0 }}</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ $tipe === 'PNBP' ? 'active' : '' }}" href="{{ route('master-layanan-jasa.index', ['tipe' => 'PNBP']) }}">
            PNBP <span class="badge {{ $tipe === 'PNBP' ? 'bg-light text-primary' : 'bg-secondary' }} ms-1">{{ $counts['PNBP'] ?? 0 }}</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ $tipe === 'KONSESI' ? 'active' : '' }}" href="{{ route('master-layanan-jasa.index', ['tipe' => 'KONSESI']) }}">
            Konsesi <span class="badge {{ $tipe === 'KONSESI' ? 'bg-light text-primary' : 'bg-secondary' }} ms-1">{{ $counts['KONSESI'] ?? 0 }}</span>
        </a>
    </li>
</ul>

<div class="service-tree-card shadow-sm">
    <div class="service-tree-header">
        <div class="soft-table-title">
            <span class="soft-table-icon"><i class="bi bi-list-task"></i></span>
            <div>
                <h6>Pilih Jenis Penerimaan</h6>
                <div class="text-muted">
                    {{ $tipe === 'SEMUA' ? 'Menampilkan semua layanan PNBP dan layanan yang mendukung Konsesi' : ($tipe === 'KONSESI' ? 'Menampilkan layanan yang mendukung Konsesi' : 'Menampilkan layanan bertipe PNBP') }}
                </div>
            </div>
        </div>
        <div class="d-flex gap-2 flex-wrap justify-content-end">
            <span class="badge bg-primary">PNBP</span>
            <span class="badge bg-success">Ada Konsesi</span>
            <span class="badge bg-warning text-dark">Tarif</span>
        </div>
    </div>
    <div class="service-tree-tools">
        <div class="service-search-box">
            <i class="bi bi-search"></i>
            <input type="search" id="masterServiceSearch" class="form-control" placeholder="Cari nama layanan, kode MAK, akun, satuan, tarif, PNBP, atau Konsesi">
        </div>
        <div class="service-search-count" id="masterServiceSearchCount">{{ $layanans->count() }} layanan</div>
    </div>
    <div class="service-tree-body">
        <div class="service-tree-panel">
            <div class="tree-empty d-none" id="masterServiceNoResult">
                <div>
                    <i class="bi bi-search fs-1 d-block mb-2"></i>
                    Layanan tidak ditemukan.
                </div>
            </div>
            @if($roots->isEmpty())
                <div class="tree-empty">
                    <div>
                        <i class="bi bi-folder2-open fs-1 d-block mb-2"></i>
                        Belum ada layanan jasa pada filter ini.
                    </div>
                </div>
            @else
                @foreach($roots->sortBy('nama_layanan', SORT_NATURAL | SORT_FLAG_CASE) as $root)
                    {!! $renderNode($root, 0) !!}
                @endforeach
            @endif
        </div>
    </div>
</div>
</div>
@endsection

@push('script')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const childMap = {};
            const nodeMap = {};
            const rootNodes = [];

            document.querySelectorAll('[data-node-id]').forEach(function (node) {
                nodeMap[node.getAttribute('data-node-id')] = node;
                const parentId = node.getAttribute('data-parent-id');
                if (! childMap[parentId]) {
                    childMap[parentId] = [];
                }
                childMap[parentId].push(node);

                if (parentId === '0') {
                    rootNodes.push(node);
                }
            });

            function setChildrenVisible(parentId, visible) {
                (childMap[parentId] || []).forEach(function (child) {
                    child.classList.toggle('d-none', !visible);

                    const childId = child.getAttribute('data-node-id');
                    if (! visible) {
                        setChildrenVisible(childId, false);
                    } else if (! child.classList.contains('is-collapsed')) {
                        setChildrenVisible(childId, true);
                    }
                });
            }

            document.querySelectorAll('[data-tree-toggle]').forEach(function (button) {
                button.addEventListener('click', function () {
                    const id = button.getAttribute('data-tree-toggle');
                    const node = document.querySelector('[data-node-id="' + id + '"]');
                    const collapsed = ! node.classList.contains('is-collapsed');

                    node.classList.toggle('is-collapsed', collapsed);
                    setChildrenVisible(id, !collapsed);
                });
            });

            function collapseInitialTree() {
                document.querySelectorAll('[data-node-id]').forEach(function (node) {
                    node.classList.remove('is-search-match');
                    if (node.getAttribute('data-parent-id') !== '0') {
                        node.classList.add('d-none');
                    } else {
                        node.classList.remove('d-none');
                    }

                    if (!node.classList.contains('tree-leaf')) {
                        node.classList.add('is-collapsed');
                    }
                });
            }

            function collectDescendants(node, visibleIds) {
                const id = node.getAttribute('data-node-id');
                (childMap[id] || []).forEach(function (child) {
                    visibleIds.add(child.getAttribute('data-node-id'));
                    collectDescendants(child, visibleIds);
                });
            }

            function collectAncestors(node, visibleIds) {
                let parentId = node.getAttribute('data-parent-id');
                while (parentId && parentId !== '0') {
                    const parentNode = nodeMap[parentId];
                    if (!parentNode) {
                        break;
                    }

                    visibleIds.add(parentId);
                    parentId = parentNode.getAttribute('data-parent-id');
                }
            }

            function updateSearchCount(count, isSearching) {
                const counter = document.getElementById('masterServiceSearchCount');
                if (!counter) {
                    return;
                }

                counter.textContent = isSearching ? count + ' hasil' : document.querySelectorAll('[data-node-id]').length + ' layanan';
            }

            function applySearch(term) {
                const normalizedTerm = term.trim().toLowerCase();
                const noResult = document.getElementById('masterServiceNoResult');
                const nodes = Array.from(document.querySelectorAll('[data-node-id]'));

                if (!normalizedTerm) {
                    if (noResult) {
                        noResult.classList.add('d-none');
                    }
                    collapseInitialTree();
                    updateSearchCount(nodes.length, false);
                    return;
                }

                const visibleIds = new Set();
                let matchCount = 0;

                nodes.forEach(function (node) {
                    const isMatch = (node.getAttribute('data-search') || '').includes(normalizedTerm);
                    node.classList.toggle('is-search-match', isMatch);

                    if (isMatch) {
                        matchCount++;
                        visibleIds.add(node.getAttribute('data-node-id'));
                        collectAncestors(node, visibleIds);
                        collectDescendants(node, visibleIds);
                    }
                });

                nodes.forEach(function (node) {
                    const id = node.getAttribute('data-node-id');
                    const visible = visibleIds.has(id);
                    node.classList.toggle('d-none', !visible);

                    if (visible && !node.classList.contains('tree-leaf')) {
                        node.classList.remove('is-collapsed');
                    }
                });

                if (noResult) {
                    noResult.classList.toggle('d-none', matchCount > 0);
                }
                updateSearchCount(matchCount, true);
            }

            collapseInitialTree();

            const searchInput = document.getElementById('masterServiceSearch');
            if (searchInput) {
                searchInput.addEventListener('input', function () {
                    applySearch(searchInput.value);
                });
            }

            document.querySelectorAll('[data-node-id]').forEach(function (node) {
                if (node.getAttribute('data-parent-id') !== '0') {
                    node.classList.add('d-none');
                }
            });
        });
    </script>
@endpush
