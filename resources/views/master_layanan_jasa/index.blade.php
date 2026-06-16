@extends('layouts.app')
@section('title', 'Master Data Layanan Jasa')

@push('css')
    <script>
        window.tailwind = window.tailwind || {};
        window.tailwind.config = { corePlugins: { preflight: false } };
    </script>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .tw-scope { color: #0f172a; }
        .lj-hero {
            background: linear-gradient(120deg, #12355c 0%, #174f86 55%, #1d65a6 100%);
            box-shadow: 0 18px 50px rgba(18, 53, 92, .22);
        }
        /* Tab pills */
        .lj-tab { transition: all .15s ease; }
        .lj-tab.is-active { background: #2563eb; color: #fff; box-shadow: 0 6px 16px rgba(37,99,235,.28); }
        .lj-tab.is-active .lj-tab-badge { background: rgba(255,255,255,.25); color: #fff; }
        /* Category panel */
        .lj-cat { transition: background .12s ease; }
        .lj-cat:hover { background: #eff6ff; }
        .lj-cat.is-active { background: #dbeafe; color: #1d4ed8; font-weight: 700; }
        .lj-cat-scroll { max-height: 620px; overflow: auto; }
        .lj-cat-scroll::-webkit-scrollbar { width: 7px; }
        .lj-cat-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 99px; }
        /* Card */
        .lj-card {
            background: #fff; border: 1px solid #e8eef5; border-radius: 16px; padding: 16px 18px;
            box-shadow: 0 6px 18px rgba(15, 47, 87, .06); transition: transform .15s ease, box-shadow .15s ease;
            display: flex; flex-direction: column; gap: 10px;
        }
        .lj-card { animation: ljFadeUp .35s ease both; }
        @keyframes ljFadeUp { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: none; } }
        .lj-card:hover, .lj-card:focus-within { transform: translateY(-3px); box-shadow: 0 14px 30px rgba(37, 99, 235, .16); border-color: #93c5fd; }
        .lj-sec-title { font-weight: 800; font-size: .95rem; color: #1e3a5f; }
        .lj-sec-count { background: #eff6ff; color: #1d4ed8; }
        .lj-card-title { font-weight: 800; font-size: .95rem; color: #1e3a5f; line-height: 1.3; }
        .lj-badge { font-size: .66rem; font-weight: 800; letter-spacing: .02em; padding: 3px 9px; border-radius: 999px; text-transform: uppercase; }
        .lj-badge-pnbp { background: #dbeafe; color: #1d4ed8; }
        .lj-badge-konsesi-type { background: #cffafe; color: #0e7490; }
        .lj-badge-tarif { background: #fef3c7; color: #b45309; }
        .lj-badge-konsesi { background: #dcfce7; color: #15803d; }
        .lj-badge-kategori { background: #ede9fe; color: #6d28d9; }
        .lj-meta-label { font-size: .64rem; text-transform: uppercase; letter-spacing: .05em; color: #94a3b8; font-weight: 700; }
        .lj-tarif { font-size: .98rem; font-weight: 800; color: #b91c1c; }
        .lj-star { color: #cbd5e1; cursor: pointer; }
        .lj-star.is-on { color: #f59e0b; }
        .lj-kebab { line-height: 1; } .lj-kebab:hover { color: #1d4ed8; }
        .lj-card .dropdown-item { font-size: .85rem; }
        .lj-status-dot { width: 8px; height: 8px; border-radius: 999px; display: inline-block; }
        /* List mode */
        #ljGrid.is-list { grid-template-columns: 1fr !important; }
        #ljGrid.is-list .lj-card { flex-direction: row; align-items: center; flex-wrap: wrap; }
        .lj-view-btn.is-active { background: #2563eb; color: #fff; }
        .lj-page-btn.is-active { background: #2563eb; color: #fff; border-color: #2563eb; }
    </style>
@endpush

@section('content')
@php
    $byId = $layanans->keyBy('id');

    $ancestorsOf = function ($l) use ($byId) {
        $ids = [];
        $cur = $l; $guard = 0;
        while ($cur && $guard++ < 25) {
            $ids[] = (int) $cur->id;
            $cur = $cur->parent_id ? $byId->get($cur->parent_id) : null;
        }
        return $ids; // self-first ... root
    };

    // Jumlah descendant (tanpa diri sendiri) per kategori + jumlah anak langsung.
    $catCount = [];
    $childCount = [];
    foreach ($layanans as $l) {
        if ($l->parent_id) {
            $childCount[(int) $l->parent_id] = ($childCount[(int) $l->parent_id] ?? 0) + 1;
        }
        foreach (array_slice($ancestorsOf($l), 1) as $aid) {
            $catCount[$aid] = ($catCount[$aid] ?? 0) + 1;
        }
    }

    $nonLeaf = $layanans->filter(fn ($l) => ! $l->is_leaf);
    $rootCats = $nonLeaf->filter(fn ($l) => empty($l->parent_id))->sortBy('nama_layanan', SORT_NATURAL | SORT_FLAG_CASE);
    $childrenByParent = $nonLeaf->groupBy('parent_id');

    // Peta untuk grouping client-side: nama kategori + daftar sub-kategori per parent (root).
    $catNameMap = $nonLeaf->mapWithKeys(fn ($c) => [(int) $c->id => $c->nama_layanan])->all();
    $catChildrenMap = [];
    foreach ($rootCats as $root) {
        $catChildrenMap[(int) $root->id] = $childrenByParent->get($root->id, collect())
            ->sortBy('nama_layanan', SORT_NATURAL | SORT_FLAG_CASE)
            ->pluck('id')->map(fn ($i) => (int) $i)->values()->all();
    }
@endphp

<div class="tw-scope">

    {{-- ===== HERO ===== --}}
    <div class="lj-hero mb-4 overflow-hidden rounded-3xl px-5 py-5 sm:px-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex items-start gap-3">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-blue-600 text-white shadow-lg">
                    <i class="bi bi-diagram-3 text-xl"></i>
                </div>
                <div>
                    <h4 class="mb-1 text-xl font-black text-white lg:text-2xl">{{ $canManageMaster ? 'Master Data Layanan Jasa' : 'Layanan Jasa Dikelola' }}</h4>
                    <p class="mb-0 text-sm font-semibold text-blue-100/80">{{ $canManageMaster ? 'Katalog layanan jasa dengan tarif PNBP & Konsesi' : 'Daftar layanan jasa yang ditugaskan kepada Admin Jasa login' }}</p>
                </div>
            </div>
            @if($canManageMaster)
                <a href="{{ route('master-layanan-jasa.create') }}" class="inline-flex items-center justify-center rounded-xl bg-white px-4 py-2 text-sm font-bold text-blue-700 shadow-lg transition hover:-translate-y-0.5 hover:bg-blue-50">
                    <i class="bi bi-plus-lg me-2"></i>Tambah Layanan
                </a>
            @endif
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle me-2"></i>{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show"><i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    {{-- ===== TOOLBAR ===== --}}
    <div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        {{-- Tabs --}}
        <div class="flex flex-wrap items-center gap-2">
            @php
                $tabs = [
                    'SEMUA' => ['Semua', 'bg-slate-100 text-slate-700'],
                    'PNBP' => ['PNBP', 'bg-blue-50 text-blue-700'],
                    'KONSESI' => ['Konsesi', 'bg-emerald-50 text-emerald-700'],
                    'TARIF' => ['Tarif', 'bg-amber-50 text-amber-700'],
                ];
            @endphp
            @foreach($tabs as $key => [$label, $cls])
                <button type="button" class="lj-tab {{ $tipe === $key ? 'is-active' : $cls }} inline-flex items-center gap-2 rounded-full px-4 py-1.5 text-sm font-bold" data-tab="{{ $key }}">
                    {{ $label }}
                    <span class="lj-tab-badge rounded-full bg-slate-200 px-2 py-0.5 text-xs font-black text-slate-600">{{ $counts[$key] ?? 0 }}</span>
                </button>
            @endforeach
        </div>
        {{-- Search + sort + view --}}
        <div class="flex flex-wrap items-center gap-2">
            <div class="relative">
                <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                <input type="search" id="ljSearch" class="form-control !w-72 !rounded-xl !pl-9" placeholder="Cari layanan, kode, satuan, tarif...">
            </div>
            <select id="ljSort" class="form-select !w-auto !rounded-xl">
                <option value="name-asc">Urutkan: Nama A-Z</option>
                <option value="name-desc">Nama Z-A</option>
                <option value="tarif-desc">Tarif tertinggi</option>
                <option value="tarif-asc">Tarif terendah</option>
                <option value="updated-desc">Terbaru diperbarui</option>
            </select>
            <div class="inline-flex overflow-hidden rounded-xl border border-slate-200">
                <button type="button" class="lj-view-btn is-active px-3 py-2" data-view="grid" title="Grid"><i class="bi bi-grid-3x3-gap-fill"></i></button>
                <button type="button" class="lj-view-btn px-3 py-2" data-view="list" title="List"><i class="bi bi-list-ul"></i></button>
            </div>
        </div>
    </div>

    {{-- ===== BODY: kategori + kartu ===== --}}
    <div class="grid grid-cols-1 gap-4 lg:grid-cols-[260px_minmax(0,1fr)]">

        {{-- Kategori --}}
        <aside class="rounded-2xl border border-slate-200 bg-white p-3 shadow-sm self-start lg:sticky lg:top-4">
            <div class="mb-2 px-2 text-xs font-black uppercase tracking-wide text-slate-500">Kategori Layanan</div>
            <div class="lj-cat-scroll">
                <button type="button" class="lj-cat is-active flex w-full items-center justify-between rounded-lg px-3 py-2 text-left text-sm" data-cat="all">
                    <span class="font-semibold">Semua Kategori</span>
                    <span class="rounded-full bg-slate-100 px-2 text-xs font-bold text-slate-600">{{ $counts['SEMUA'] ?? 0 }}</span>
                </button>
                @foreach($rootCats as $root)
                    <button type="button" class="lj-cat flex w-full items-center justify-between rounded-lg px-3 py-2 text-left text-sm" data-cat="{{ $root->id }}">
                        <span class="min-w-0 truncate"><i class="bi bi-folder2 me-1 text-blue-500"></i>{{ $root->nama_layanan }}</span>
                        <span class="ms-2 shrink-0 rounded-full bg-slate-100 px-2 text-xs font-bold text-slate-600">{{ $catCount[$root->id] ?? 0 }}</span>
                    </button>
                    @foreach(($childrenByParent->get($root->id, collect()))->sortBy('nama_layanan', SORT_NATURAL | SORT_FLAG_CASE) as $sub)
                        <button type="button" class="lj-cat flex w-full items-center justify-between rounded-lg px-3 py-1.5 pl-7 text-left text-[.8rem]" data-cat="{{ $sub->id }}">
                            <span class="min-w-0 truncate text-slate-600">{{ $sub->nama_layanan }}</span>
                            <span class="ms-2 shrink-0 rounded-full bg-slate-100 px-1.5 text-[.7rem] font-bold text-slate-500">{{ $catCount[$sub->id] ?? 0 }}</span>
                        </button>
                    @endforeach
                @endforeach
            </div>
        </aside>

        {{-- Kartu --}}
        <section>
            <div class="mb-3 flex flex-wrap items-baseline gap-2">
                <h5 id="ljHeader" class="m-0 text-base font-extrabold text-slate-800">{{ $counts['SEMUA'] ?? 0 }} layanan ditemukan</h5>
                <span id="ljHeaderSub" class="text-sm text-slate-400"></span>
            </div>

            <div id="ljGrid" class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3">
                @foreach($layanans as $l)
                    @php
                        $type = $l->tipe_layanan ?? 'PNBP';
                        $hasKonsesi = (bool) ($l->mendukung_konsesi ?? false);
                        $isLeaf = (bool) $l->is_leaf;
                        $kodeText = $l->kode_pembayaran_lengkap
                            ? 'Kode bayar ' . $l->kode_pembayaran_lengkap
                            : ($l->kode_mak ? 'MAK ' . $l->kode_mak : '-');
                        $ancIds = implode(' ', $ancestorsOf($l));
                        $searchText = mb_strtolower(collect([
                            $l->nama_layanan, $l->kode_mak, $l->kode_pembayaran_lengkap, $l->kode_akun,
                            $l->satuan, $l->tarif_dasar, $type, $hasKonsesi ? 'konsesi' : null,
                        ])->filter()->implode(' '));
                    @endphp
                    <article class="lj-card"
                        data-tipe="{{ $type }}"
                        data-konsesi="{{ $hasKonsesi ? 1 : 0 }}"
                        data-leaf="{{ $isLeaf ? 1 : 0 }}"
                        data-cats="{{ $ancIds }}"
                        data-name="{{ e(mb_strtolower($l->nama_layanan)) }}"
                        data-tarif="{{ (float) ($l->tarif_dasar ?? 0) }}"
                        data-updated="{{ optional($l->updated_at)->timestamp ?? 0 }}"
                        data-search="{{ e($searchText) }}">
                        <div class="flex items-start justify-between gap-2">
                            <h3 class="lj-card-title">{{ $l->nama_layanan }}</h3>
                            <div class="flex shrink-0 items-center gap-1">
                                <i class="bi bi-star lj-star" role="button" title="Tandai"></i>
                                @if($canManageMaster)
                                    <div class="dropdown">
                                        <button class="lj-kebab btn border-0 p-0 px-1 text-slate-400" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Aksi"><i class="bi bi-three-dots-vertical"></i></button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                            <li><a class="dropdown-item" href="{{ route('master-layanan-jasa.edit', $l->id) }}"><i class="bi bi-pencil-square me-2 text-primary"></i>Edit</a></li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <form action="{{ route('master-layanan-jasa.destroy', $l->id) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus layanan ini?');">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="dropdown-item text-danger"><i class="bi bi-trash me-2"></i>Hapus</button>
                                                </form>
                                            </li>
                                        </ul>
                                    </div>
                                @endif
                            </div>
                        </div>
                        <div class="flex flex-wrap gap-1">
                            <span class="lj-badge {{ $type === 'KONSESI' ? 'lj-badge-konsesi-type' : 'lj-badge-pnbp' }}">{{ $type === 'KONSESI' ? 'Konsesi Saja' : 'PNBP' }}</span>
                            @if($isLeaf)
                                <span class="lj-badge lj-badge-tarif">Tarif</span>
                            @else
                                <span class="lj-badge lj-badge-kategori">{{ empty($l->parent_id) ? 'Jenis Layanan' : 'Kategori' }}</span>
                            @endif
                            @if($hasKonsesi)
                                <span class="lj-badge lj-badge-konsesi">Konsesi</span>
                            @endif
                        </div>
                        <div>
                            <div class="lj-meta-label">Kode Layanan</div>
                            <div class="text-sm font-semibold text-slate-700">{{ $kodeText }}</div>
                        </div>
                        <div class="flex items-end justify-between gap-2">
                            <div>
                                <div class="lj-meta-label">Tarif</div>
                                @if($isLeaf)
                                    <div class="lj-tarif">Rp {{ number_format((float) ($l->tarif_dasar ?? 0), 0, ',', '.') }}</div>
                                    @if($hasKonsesi && $l->persentase_konsesi !== null)
                                        <div class="text-xs font-bold text-emerald-600">Konsesi {{ rtrim(rtrim(number_format((float) $l->persentase_konsesi, 4, ',', '.'), '0'), ',') }}%</div>
                                    @endif
                                @else
                                    <div class="text-sm font-bold text-slate-600">{{ $childCount[$l->id] ?? 0 }} item</div>
                                @endif
                            </div>
                            <div class="text-right">
                                <div class="lj-meta-label">Satuan</div>
                                <div class="text-sm font-semibold text-slate-700">{{ $l->satuan ?: '-' }}</div>
                            </div>
                        </div>
                        <hr class="my-1 border-slate-100">
                        <div class="flex items-center justify-between text-xs text-slate-500">
                            <span class="inline-flex items-center gap-1 font-semibold {{ $l->is_active ? 'text-emerald-600' : 'text-slate-400' }}">
                                <span class="lj-status-dot {{ $l->is_active ? 'bg-emerald-500' : 'bg-slate-300' }}"></span>{{ $l->is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                            <span><i class="bi bi-calendar3 me-1"></i>{{ optional($l->updated_at)->translatedFormat('d M Y') ?: '-' }}</span>
                        </div>
                    </article>
                @endforeach
            </div>

            {{-- Grouped sections (muncul saat parent kategori dipilih) --}}
            <div id="ljGrouped" class="hidden space-y-6"></div>

            {{-- Empty state --}}
            <div id="ljEmpty" class="hidden rounded-2xl border border-dashed border-slate-200 bg-white py-16 text-center text-slate-400">
                <i class="bi bi-inbox d-block fs-1 mb-2"></i>Tidak ada layanan yang cocok dengan filter.
            </div>

            {{-- Pagination --}}
            <div id="ljPagerWrap" class="mt-4 flex flex-col items-center justify-between gap-3 sm:flex-row">
                <div class="text-sm text-slate-500" id="ljPageInfo"></div>
                <div class="flex items-center gap-2">
                    <div id="ljPager" class="flex flex-wrap items-center gap-1"></div>
                    <select id="ljPageSize" class="form-select !w-auto !rounded-xl">
                        <option value="12">12 / halaman</option>
                        <option value="24">24 / halaman</option>
                        <option value="48">48 / halaman</option>
                    </select>
                </div>
            </div>
        </section>
    </div>
</div>
@endsection

@push('script')
<script>
(function () {
    const grid = document.getElementById('ljGrid');
    if (!grid) return;
    const cards = Array.from(grid.querySelectorAll('.lj-card'));
    const groupedBox = document.getElementById('ljGrouped');
    const pager = document.getElementById('ljPager');
    const pagerWrap = document.getElementById('ljPagerWrap');
    const pageInfo = document.getElementById('ljPageInfo');
    const emptyBox = document.getElementById('ljEmpty');
    const headerEl = document.getElementById('ljHeader');
    const headerSub = document.getElementById('ljHeaderSub');

    const LJ_CAT_NAME = @json((object) ($catNameMap ?? []));
    const LJ_CAT_CHILDREN = @json((object) ($catChildrenMap ?? []));

    const state = { tab: @json($tipe), cat: 'all', q: '', sort: 'name-asc', page: 1, size: 12 };

    const esc = s => String(s).replace(/[&<>"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));
    const hasCat = (c, id) => (' ' + c.dataset.cats + ' ').includes(' ' + id + ' ');
    const SEC_GRID = 'grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3';
    function appendSection(title, list) {
        const wrap = document.createElement('div');
        wrap.innerHTML = `<div class="mb-2 mt-1 flex items-center gap-2"><span class="lj-sec-title">${esc(title)}</span><span class="lj-sec-count rounded-full px-2 py-0.5 text-xs font-bold">${list.length} item</span></div>`;
        const g = document.createElement('div');
        g.className = SEC_GRID;
        list.forEach(c => g.appendChild(c));
        wrap.appendChild(g);
        groupedBox.appendChild(wrap);
    }

    function matches(card) {
        if (state.tab === 'PNBP' && card.dataset.tipe !== 'PNBP') return false;
        if (state.tab === 'KONSESI' && card.dataset.konsesi !== '1') return false;
        if (state.tab === 'TARIF' && card.dataset.leaf !== '1') return false;
        if (state.cat !== 'all' && !(' ' + card.dataset.cats + ' ').includes(' ' + state.cat + ' ')) return false;
        if (state.q && !card.dataset.search.includes(state.q)) return false;
        return true;
    }

    function sortCards(list) {
        const by = {
            'name-asc': (a, b) => a.dataset.name.localeCompare(b.dataset.name),
            'name-desc': (a, b) => b.dataset.name.localeCompare(a.dataset.name),
            'tarif-desc': (a, b) => (+b.dataset.tarif) - (+a.dataset.tarif),
            'tarif-asc': (a, b) => (+a.dataset.tarif) - (+b.dataset.tarif),
            'updated-desc': (a, b) => (+b.dataset.updated) - (+a.dataset.updated),
        }[state.sort];
        return by ? list.slice().sort(by) : list;
    }

    function render() {
        const visible = sortCards(cards.filter(matches));
        const total = visible.length;

        // Detach semua kartu lalu render ulang sesuai mode (memicu animasi fade-up).
        cards.forEach(c => { if (c.parentNode) c.parentNode.removeChild(c); });
        groupedBox.innerHTML = '';

        if (state.cat !== 'all') {
            headerEl.textContent = LJ_CAT_NAME[state.cat] || 'Kategori';
            headerSub.textContent = '· ' + total.toLocaleString('id-ID') + ' layanan';
        } else {
            headerEl.textContent = total.toLocaleString('id-ID') + ' layanan ditemukan';
            headerSub.textContent = '';
        }
        emptyBox.classList.toggle('hidden', total !== 0);

        const subCats = LJ_CAT_CHILDREN[state.cat] || [];
        const isGrouped = state.cat !== 'all' && subCats.length > 0;

        // ===== MODE GROUPED: parent kategori dipilih → section per sub-kategori =====
        if (isGrouped) {
            grid.classList.add('hidden');
            groupedBox.classList.remove('hidden');
            pagerWrap.classList.add('hidden');
            const used = new Set();
            subCats.forEach(childId => {
                const sect = visible.filter(c => hasCat(c, childId));
                if (!sect.length) return;
                sect.forEach(c => used.add(c));
                appendSection(LJ_CAT_NAME[childId] || 'Sub Kategori', sect);
            });
            const leftovers = visible.filter(c => !used.has(c));
            if (leftovers.length) appendSection('Lainnya', leftovers);
            return;
        }

        // ===== MODE FLAT: semua / child kategori / tab → grid + pagination =====
        groupedBox.classList.add('hidden');
        grid.classList.remove('hidden');
        pagerWrap.classList.remove('hidden');

        const pages = Math.max(1, Math.ceil(total / state.size));
        if (state.page > pages) state.page = pages;
        const start = (state.page - 1) * state.size;
        const end = start + state.size;
        visible.slice(start, end).forEach(c => grid.appendChild(c));
        pageInfo.textContent = total === 0 ? '' : `Menampilkan ${start + 1}–${Math.min(end, total)} dari ${total} layanan`;

        pager.innerHTML = '';
        const addBtn = (label, page, opts = {}) => {
            const b = document.createElement('button');
            b.type = 'button';
            b.className = 'lj-page-btn btn btn-sm border ' + (opts.active ? 'is-active' : 'btn-light');
            b.innerHTML = label;
            if (opts.disabled) { b.disabled = true; }
            else if (!opts.active) { b.addEventListener('click', () => { state.page = page; render(); }); }
            pager.appendChild(b);
        };
        addBtn('<i class="bi bi-chevron-left"></i>', state.page - 1, { disabled: state.page === 1 });
        const win = 2;
        for (let p = 1; p <= pages; p++) {
            if (p === 1 || p === pages || (p >= state.page - win && p <= state.page + win)) {
                addBtn(String(p), p, { active: p === state.page });
            } else if (p === state.page - win - 1 || p === state.page + win + 1) {
                const dots = document.createElement('span');
                dots.className = 'px-1 text-slate-400';
                dots.textContent = '…';
                pager.appendChild(dots);
            }
        }
        addBtn('<i class="bi bi-chevron-right"></i>', state.page + 1, { disabled: state.page === pages });
    }

    document.querySelectorAll('.lj-tab').forEach(btn => btn.addEventListener('click', () => {
        document.querySelectorAll('.lj-tab').forEach(b => b.classList.remove('is-active'));
        btn.classList.add('is-active');
        state.tab = btn.dataset.tab; state.page = 1; render();
    }));

    document.querySelectorAll('.lj-cat').forEach(btn => btn.addEventListener('click', () => {
        document.querySelectorAll('.lj-cat').forEach(b => b.classList.remove('is-active'));
        btn.classList.add('is-active');
        state.cat = btn.dataset.cat; state.page = 1; render();
    }));

    let t = null;
    document.getElementById('ljSearch').addEventListener('input', function () {
        clearTimeout(t);
        t = setTimeout(() => { state.q = this.value.trim().toLowerCase(); state.page = 1; render(); }, 180);
    });

    document.getElementById('ljSort').addEventListener('change', function () { state.sort = this.value; render(); });
    document.getElementById('ljPageSize').addEventListener('change', function () { state.size = parseInt(this.value, 10) || 12; state.page = 1; render(); });

    document.querySelectorAll('.lj-view-btn').forEach(btn => btn.addEventListener('click', () => {
        document.querySelectorAll('.lj-view-btn').forEach(b => b.classList.remove('is-active'));
        btn.classList.add('is-active');
        grid.classList.toggle('is-list', btn.dataset.view === 'list');
    }));

    document.addEventListener('click', e => {
        const star = e.target.closest('.lj-star');
        if (star) { star.classList.toggle('bi-star'); star.classList.toggle('bi-star-fill'); star.classList.toggle('is-on'); }
    });

    render();
})();
</script>
@endpush
