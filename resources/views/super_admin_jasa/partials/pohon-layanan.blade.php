{{--
    Pemilih layanan berbentuk pohon per kategori (dipakai Scope Layanan Kontrak & Layanan Admin Jasa).
    Parameter:
      $pohonLayanan       koleksi layanan daun (relasi parent dimuat) yang bisa dipilih
      $pohonTerpilih      array id layanan yang tercentang
      $labelHitung        teks penghitung, mis. "layanan dalam scope"
      $pesanKosong        HTML peringatan bila belum ada yang dipilih
      $pesanMasterKosong  teks bila master layanan kosong
      $konfirmasiKosongkan teks konfirmasi tombol "Kosongkan pilihan"
    Input yang dikirim: layanan_ids[]. Penghitung opsional di bilah simpan: #ksFooterCount.
--}}
@php
    $pohonTerpilih = collect($pohonTerpilih ?? [])->map(fn ($id) => (int) $id)->all();
    $layananPath = function ($layanan) {
        $names = [$layanan->nama_layanan];
        for ($parent = $layanan->parent, $guard = 0; $parent && $guard < 10; $parent = $parent->parent, $guard++) {
            array_unshift($names, $parent->nama_layanan);
        }

        return implode(' > ', $names);
    };

// Scope layanan dikelompokkan per kategori puncak (root); jalur di antara root
// dan item tampil sebagai subjudul agar tidak diulang di setiap item.
$grupScope = collect($pohonLayanan ?? [])
    ->map(function ($layanan) {
        $leluhur = [];
        $parent = $layanan->parent;
        $guard = 0;
        while ($parent && $guard < 10) {
            array_unshift($leluhur, $parent);
            $parent = $parent->parent;
            $guard++;
        }
        $root = $leluhur[0] ?? $layanan;

        return [
            'layanan' => $layanan,
            'root_id' => $root->id,
            'root_nama' => $root->nama_layanan,
            'sub' => collect(array_slice($leluhur, 1))->pluck('nama_layanan')->implode(' › '),
        ];
    })
    ->groupBy('root_id')
    ->map(function ($items) use ($pohonTerpilih) {
        return [
            'nama' => $items->first()['root_nama'],
            'sub' => $items->sortBy(fn ($i) => $i['sub'] . '|' . $i['layanan']->nama_layanan)->groupBy('sub'),
            'jumlah' => $items->count(),
            'terpilih' => $items->filter(fn ($i) => in_array((int) $i['layanan']->id, $pohonTerpilih, true))->count(),
        ];
    })
    ->sortBy('nama', SORT_NATURAL | SORT_FLAG_CASE);
@endphp

@once
@push('css')
<style>
    /* ===== Pohon layanan per kategori (scope kontrak, layanan Admin Jasa) ===== */
    .ks-toolbar { display: flex; flex-wrap: wrap; align-items: center; gap: 10px; margin-bottom: 12px; }
    .ks-search { position: relative; flex: 1 1 320px; }
    .ks-search i { position: absolute; left: 13px; top: 50%; transform: translateY(-50%); color: #94a3b8; }
    .ks-search .form-control { padding-left: 38px; min-height: 42px; border-radius: 12px; }
    .ks-tools { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; }
    .ks-only { display: inline-flex; align-items: center; gap: 6px; margin: 0 6px 0 0; font-size: 13px; font-weight: 600; color: #334155; cursor: pointer; }
    .ks-scope .form-check-input { flex: none; width: 1.15em; height: 1.15em; margin: 0; cursor: pointer; }

    .ks-summary { margin-bottom: 12px; padding: 12px 14px; border: 1px solid #bfdbfe; border-radius: 14px; background: #eff6ff; }
    .ks-summary-head { display: flex; align-items: center; justify-content: space-between; gap: 8px; }
    .ks-count { font-size: 14px; font-weight: 700; color: #1e3a8a; }
    .ks-count b { font-size: 16px; }
    .ks-chips { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 8px; }
    .ks-chips:empty { display: none; }
    .ks-chip {
        display: inline-flex; align-items: center; gap: 6px; max-width: 100%; padding: 4px 6px 4px 10px;
        border-radius: 999px; background: #fff; border: 1px solid #93c5fd; color: #1d4ed8; font-size: 12.5px; font-weight: 600;
    }
    .ks-chip span { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .ks-chip button { border: 0; background: #dbeafe; color: #1d4ed8; width: 20px; height: 20px; border-radius: 50%; line-height: 1; padding: 0; flex: none; }
    .ks-chip button:hover { background: #bfdbfe; }
    .ks-empty-warn { display: none; gap: 8px; margin-top: 8px; padding: 8px 10px; border-radius: 10px; background: #fffbeb; border: 1px solid #fde68a; color: #92400e; font-size: 13px; }
    .ks-scope.is-empty .ks-empty-warn { display: flex; }
    .ks-scope.is-empty .ks-summary { background: #fffdf5; border-color: #fde68a; }
    .ks-scope.is-empty #ksClear { display: none; }

    .ks-groups { display: grid; gap: 8px; }
    .ks-group { border: 1px solid #e2e8f0; border-radius: 14px; background: #fff; overflow: hidden; }
    .ks-group[open] { border-color: #c7d2fe; box-shadow: 0 6px 18px rgba(37, 99, 235, .06); }
    .ks-group > summary {
        display: flex; align-items: center; gap: 8px; padding: 11px 14px; cursor: pointer; list-style: none;
        background: #f8fafc; font-weight: 700; color: #1e293b; user-select: none;
    }
    .ks-group > summary::-webkit-details-marker { display: none; }
    .ks-group[open] > summary { background: #f5f8ff; border-bottom: 1px solid #e0e7ff; }
    .ks-chev { transition: transform .15s; color: #64748b; }
    .ks-group[open] .ks-chev { transform: rotate(90deg); }
    .ks-group-name { flex: 1; min-width: 0; font-size: 14px; }
    .ks-badge { flex: none; padding: 2px 9px; border-radius: 999px; background: #e2e8f0; color: #475569; font-size: 12px; font-weight: 800; }
    .ks-badge.has { background: #2563eb; color: #fff; }
    .ks-all { flex: none; display: inline-flex; align-items: center; gap: 6px; margin: 0; padding: 3px 8px; border: 0; background: transparent; border-radius: 8px; font-size: 12px; font-weight: 700; color: #1d4ed8; cursor: pointer; }
    .ks-all i { font-size: 15px; }
    .ks-all:hover { background: #e0e7ff; }
    .ks-group-body { padding: 10px 14px 14px; }
    .ks-sub { margin: 8px 0 6px; font-size: 12px; font-weight: 700; color: #64748b; }
    .ks-sub:first-child { margin-top: 0; }
    .ks-items { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 6px; }
    .ks-item {
        display: flex; align-items: flex-start; gap: 10px; margin: 0; padding: 9px 11px; border: 1px solid #e2e8f0;
        border-radius: 11px; background: #fff; cursor: pointer; transition: border-color .12s, background .12s, box-shadow .12s;
    }
    .ks-item:hover { border-color: #93c5fd; background: #f8fbff; }
    .ks-item .form-check-input { margin-top: 2px; }
    .ks-item:has(input:checked) { border-color: #2563eb; background: #eff6ff; box-shadow: inset 3px 0 0 #2563eb; }
    .ks-item-text { min-width: 0; }
    .ks-item-nama { display: block; font-size: 13.5px; font-weight: 600; color: #0f172a; line-height: 1.35; }
    .ks-item-meta { display: block; margin-top: 2px; font-size: 11.5px; color: #64748b; }
    .ks-kode { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; color: #3730a3; }
    .ks-noresult { padding: 22px; text-align: center; color: #64748b; border: 1px dashed #cbd5e1; border-radius: 14px; }
    [data-ks-item][hidden], [data-ks-group][hidden], [data-ks-sub][hidden], [data-ks-items][hidden] { display: none !important; }

    /* Bilah simpan menempel di bawah layar (kartu induk perlu overflow visible agar sticky bekerja). */
    .jasa-form-card:has(.ks-sticky-footer) { overflow: visible; }
    .ks-sticky-footer {
        position: sticky; bottom: 0; z-index: 30; border-top: 1px solid #e2e8f0;
        border-radius: 0 0 20px 20px; box-shadow: 0 -10px 26px rgba(15, 23, 42, .08); background: rgba(248, 250, 252, .97);
        backdrop-filter: blur(6px);
    }
    .ks-footer-count { font-size: 13.5px; font-weight: 600; color: #334155; }
    .ks-footer-count b { color: #1d4ed8; }
    .ks-footer-count.is-empty, .ks-footer-count.is-empty b { color: #b45309; }
    @media (max-width: 575.98px) {
        .ks-sticky-footer { padding: 10px 14px !important; }
        .ks-sticky-footer > div { flex-direction: row !important; flex-wrap: wrap; }
        .ks-sticky-footer .ks-footer-count { flex-basis: 100%; }
        .ks-sticky-footer .btn { flex: 1 1 0; padding: 8px 10px !important; white-space: nowrap; font-size: 14px; }
        .ks-sticky-footer .btn-primary { flex-grow: 2; }
        .ks-items { grid-template-columns: 1fr; }
        .ks-group > summary { flex-wrap: wrap; }
        .ks-group-name { flex-basis: calc(100% - 60px); }
        .ks-all { margin-left: auto; }
    }
</style>
@endpush
@endonce

@if($grupScope->isEmpty())
    <div class="alert alert-info mb-0">{{ $pesanMasterKosong ?? 'Belum ada item tarif aktif di master layanan.' }}</div>
@else
    <div class="ks-scope" id="ksScope">
        <div class="ks-toolbar">
            <div class="ks-search">
                <i class="bi bi-search"></i>
                <input type="search" id="ksSearch" class="form-control" placeholder="Cari kode, nama layanan, atau kategori…" autocomplete="off">
            </div>
            <div class="ks-tools">
                <label class="ks-only">
                    <input type="checkbox" id="ksOnlySelected" class="form-check-input"> Tampilkan yang terpilih saja
                </label>
                <button type="button" class="btn btn-sm btn-light border fw-semibold" data-ks-expand="1"><i class="bi bi-arrows-expand"></i> Buka semua</button>
                <button type="button" class="btn btn-sm btn-light border fw-semibold" data-ks-expand="0"><i class="bi bi-arrows-collapse"></i> Tutup semua</button>
            </div>
        </div>

        <div class="ks-summary" id="ksSummary">
            <div class="ks-summary-head">
                <span class="ks-count"><b id="ksCount">{{ count($pohonTerpilih) }}</b> {{ $labelHitung ?? 'layanan dalam scope' }}</span>
                <button type="button" class="btn btn-link btn-sm p-0 text-danger fw-semibold" id="ksClear">Kosongkan pilihan</button>
            </div>
            <div class="ks-chips" id="ksChips"></div>
            <div class="ks-empty-warn" id="ksEmptyWarn">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <span>{!! $pesanKosong ?? 'Belum ada layanan dipilih.' !!}</span>
            </div>
        </div>

        <div class="ks-groups">
            @foreach($grupScope as $rootId => $grup)
                <details class="ks-group" data-ks-group @if($grup['terpilih'] > 0) open @endif>
                    <summary>
                        <i class="bi bi-chevron-right ks-chev"></i>
                        <i class="bi bi-folder2-open text-primary"></i>
                        <span class="ks-group-name">{{ $grup['nama'] }}</span>
                        <span class="ks-badge" data-ks-badge>{{ $grup['terpilih'] }}/{{ $grup['jumlah'] }}</span>
                        <button type="button" class="ks-all" data-ks-all aria-pressed="false" title="Pilih / lepas semua layanan di kategori ini (yang sedang tampil)">
                            <i class="bi bi-square"></i> Pilih semua
                        </button>
                    </summary>
                    <div class="ks-group-body">
                        @foreach($grup['sub'] as $sub => $items)
                            @if($sub !== '')
                                <div class="ks-sub" data-ks-sub><i class="bi bi-arrow-return-right"></i> {{ $sub }}</div>
                            @endif
                            <div class="ks-items" data-ks-items>
                                @foreach($items as $item)
                                    @php($l = $item['layanan'])
                                    @php($kode = $l->kode_layanan ?: str_pad($l->id, 6, '0', STR_PAD_LEFT))
                                    <label class="ks-item" data-ks-item data-search="{{ \Illuminate\Support\Str::lower($kode . ' ' . $l->nama_layanan . ' ' . $sub . ' ' . $grup['nama']) }}" title="{{ $layananPath($l) }}">
                                        <input type="checkbox" name="layanan_ids[]" value="{{ $l->id }}" class="form-check-input" data-nama="{{ $l->nama_layanan }}" @checked(in_array((int) $l->id, $pohonTerpilih, true))>
                                        <span class="ks-item-text">
                                            <span class="ks-item-nama">{{ $l->nama_layanan }}</span>
                                            <span class="ks-item-meta"><span class="ks-kode">{{ $kode }}</span>@if($l->satuan) · {{ $l->satuan }}@endif</span>
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        @endforeach
                    </div>
                </details>
            @endforeach
        </div>
        <div class="ks-noresult" id="ksNoResult" hidden><i class="bi bi-search"></i> Tidak ada layanan yang cocok dengan pencarian.</div>
    </div>
@endif

@push('script')
<script>
/**
 * Pohon layanan per kategori dengan pencarian, filter "terpilih saja", pilih-semua per
 * kategori, ringkasan chip, dan penghitung di bilah simpan (#ksFooterCount, opsional).
 * Checkbox asli (name="layanan_ids[]") tetap menjadi sumber data yang dikirim form.
 */
(function () {
    const scope = document.getElementById('ksScope');
    if (!scope) return;

    const boxes = Array.from(scope.querySelectorAll('input[name="layanan_ids[]"]'));
    const groups = Array.from(scope.querySelectorAll('[data-ks-group]'));
    const search = document.getElementById('ksSearch');
    const onlySelected = document.getElementById('ksOnlySelected');
    const chips = document.getElementById('ksChips');
    const count = document.getElementById('ksCount');
    const footer = document.getElementById('ksFooterCount');
    const noResult = document.getElementById('ksNoResult');

    function itemsOf(group) { return Array.from(group.querySelectorAll('[data-ks-item]')); }
    function boxOf(item) { return item.querySelector('input[name="layanan_ids[]"]'); }

    function renderSummary() {
        const selected = boxes.filter((b) => b.checked);
        count.textContent = selected.length;
        if (footer) {
            footer.querySelector('b').textContent = selected.length;
            footer.classList.toggle('is-empty', selected.length === 0);
        }
        scope.classList.toggle('is-empty', selected.length === 0);

        chips.innerHTML = '';
        selected.forEach((b) => {
            const chip = document.createElement('span');
            chip.className = 'ks-chip';
            const text = document.createElement('span');
            text.textContent = b.dataset.nama;
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.title = 'Hapus dari scope';
            btn.setAttribute('aria-label', 'Hapus ' + b.dataset.nama + ' dari scope');
            btn.innerHTML = '&times;';
            btn.addEventListener('click', () => { b.checked = false; update(); });
            chip.append(text, btn);
            chips.appendChild(chip);
        });

        groups.forEach((g) => {
            const items = itemsOf(g);
            const picked = items.filter((i) => boxOf(i).checked).length;
            const badge = g.querySelector('[data-ks-badge]');
            badge.textContent = picked + '/' + items.length;
            badge.classList.toggle('has', picked > 0);

            const visible = items.filter((i) => !i.hidden);
            const visPicked = visible.filter((i) => boxOf(i).checked).length;
            const all = g.querySelector('[data-ks-all]');
            const state = visible.length && visPicked === visible.length ? 'all' : (visPicked > 0 ? 'some' : 'none');
            all.dataset.state = state;
            all.setAttribute('aria-pressed', state === 'all' ? 'true' : 'false');
            all.querySelector('i').className = state === 'all' ? 'bi bi-check-square-fill' : (state === 'some' ? 'bi bi-dash-square-fill' : 'bi bi-square');
        });
    }

    function applyFilter() {
        const terms = (search.value || '').toLowerCase().split(/\s+/).filter(Boolean);
        const only = onlySelected.checked;
        let anyVisible = false;

        groups.forEach((g) => {
            let groupVisible = 0;
            g.querySelectorAll('[data-ks-items]').forEach((list) => {
                let listVisible = 0;
                list.querySelectorAll('[data-ks-item]').forEach((item) => {
                    const hay = item.dataset.search;
                    const show = terms.every((t) => hay.includes(t)) && (!only || boxOf(item).checked);
                    item.hidden = !show;
                    if (show) listVisible++;
                });
                list.hidden = listVisible === 0;
                const sub = list.previousElementSibling;
                if (sub && sub.hasAttribute('data-ks-sub')) sub.hidden = listVisible === 0;
                groupVisible += listVisible;
            });
            g.hidden = groupVisible === 0;
            if (terms.length || only) g.open = groupVisible > 0;
            anyVisible = anyVisible || groupVisible > 0;
        });
        noResult.hidden = anyVisible;
        renderSummary();
    }

    function update() {
        if (onlySelected.checked) applyFilter(); else renderSummary();
    }

    boxes.forEach((b) => b.addEventListener('change', update));

    groups.forEach((g) => {
        g.querySelector('[data-ks-all]').addEventListener('click', (e) => {
            e.preventDefault(); // jangan ikut membuka/menutup kategori
            e.stopPropagation();
            const visible = itemsOf(g).filter((i) => !i.hidden);
            const target = e.currentTarget.dataset.state !== 'all';
            visible.forEach((i) => { boxOf(i).checked = target; });
            update();
        });
    });

    let timer;
    search.addEventListener('input', () => { clearTimeout(timer); timer = setTimeout(applyFilter, 120); });
    search.addEventListener('keydown', (e) => { if (e.key === 'Enter') e.preventDefault(); });
    onlySelected.addEventListener('change', applyFilter);

    scope.querySelectorAll('[data-ks-expand]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const open = btn.dataset.ksExpand === '1';
            groups.forEach((g) => { if (!g.hidden) g.open = open; });
        });
    });

    document.getElementById('ksClear').addEventListener('click', () => {
        if (!confirm(@json($konfirmasiKosongkan ?? 'Kosongkan semua layanan yang dipilih?'))) return;
        boxes.forEach((b) => { b.checked = false; });
        update();
    });

    renderSummary();
})();
</script>
@endpush
