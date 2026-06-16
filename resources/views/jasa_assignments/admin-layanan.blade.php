@extends('layouts.app')
@section('title', 'Pengaturan Layanan Admin Jasa')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
    <div>
        <h4 class="mb-0 fw-bold">Pengaturan Layanan Admin Jasa</h4>
        <p class="mb-0 small">{{ $user->name }} | {{ $user->email }}</p>
    </div>
    <a href="{{ route('jasa.admin.show', $user) }}" class="btn btn-secondary fw-bold">Kembali</a>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<form action="{{ route('jasa.admin.layanan.update', $user) }}" method="POST">
    @csrf
    @method('PUT')
    @foreach(($hiddenSelectedIds ?? []) as $hiddenSelectedId)
        <input type="hidden" name="layanan_ids[]" value="{{ $hiddenSelectedId }}">
    @endforeach
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-white p-3 border-bottom rounded-top-4 d-flex justify-content-between align-items-center">
            <div>
                <h6 class="mb-0 fw-bold">Pilih Layanan yang Dapat Dikelola Admin</h6>
                <small class="text-muted">Centang kategori untuk memilih seluruh item tarif di bawahnya. Filter hanya mengatur tampilan, pilihan di tab lain tetap dipertahankan.</small>
            </div>
            <button type="submit" class="btn btn-success fw-bold">Simpan Pengaturan</button>
        </div>
        <div class="card-body p-4">
            <ul class="nav nav-pills gap-2 mb-3">
                <li class="nav-item">
                    <a class="nav-link fw-bold {{ ($tipe ?? 'SEMUA') === 'SEMUA' ? 'active' : '' }}" href="{{ route('jasa.admin.layanan.edit', $user) }}">
                        Semua <span class="badge {{ ($tipe ?? 'SEMUA') === 'SEMUA' ? 'bg-light text-primary' : 'bg-secondary' }} ms-1">{{ $counts['SEMUA'] ?? 0 }}</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link fw-bold {{ ($tipe ?? 'SEMUA') === 'PNBP' ? 'active' : '' }}" href="{{ route('jasa.admin.layanan.edit', ['user' => $user, 'tipe' => 'PNBP']) }}">
                        PNBP <span class="badge {{ ($tipe ?? 'SEMUA') === 'PNBP' ? 'bg-light text-primary' : 'bg-secondary' }} ms-1">{{ $counts['PNBP'] ?? 0 }}</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link fw-bold {{ ($tipe ?? 'SEMUA') === 'KONSESI' ? 'active' : '' }}" href="{{ route('jasa.admin.layanan.edit', ['user' => $user, 'tipe' => 'KONSESI']) }}">
                        Mendukung Konsesi <span class="badge {{ ($tipe ?? 'SEMUA') === 'KONSESI' ? 'bg-light text-primary' : 'bg-secondary' }} ms-1">{{ $counts['KONSESI'] ?? 0 }}</span>
                    </a>
                </li>
            </ul>

            <div class="alert alert-info bg-info-subtle border-0 text-dark small">
                Layanan PNBP yang diberi label <strong>Ada Konsesi</strong> tetap bisa dikelola sebagai tagihan PNBP biasa, sekaligus bisa dipakai untuk laporan penjualan konsesi.
            </div>

            <div class="input-group mb-3" style="max-width:560px;">
                <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                <input type="text" id="layananSearch" class="form-control" placeholder="Cari nama layanan, kategori, atau kode..." autocomplete="off">
                <button type="button" id="layananSearchClear" class="btn btn-outline-secondary" title="Bersihkan pencarian"><i class="bi bi-x-lg"></i></button>
            </div>
            <div id="layananSearchEmpty" class="alert alert-warning border-0 small d-none" data-sky-ignore>
                <i class="bi bi-search me-1"></i>Tidak ada layanan yang cocok dengan pencarian.
            </div>

            @include('jasa_assignments.partials.layanan-tree', [
                'layanans' => $layanans,
                'selectedIds' => $selectedIds,
                'parentId' => 'root',
                'depth' => 0,
            ])
        </div>
    </div>
</form>
@endsection

@push('script')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const root = document.querySelector('form[action*="layanan"]');
        if (!root) return;

        function directChildNodes(node) {
            const childrenWrap = node.querySelector(':scope > .layanan-children');
            if (!childrenWrap) return [];
            return Array.from(childrenWrap.querySelectorAll(':scope > .layanan-node'));
        }

        function descendantLeafChecks(node) {
            return Array.from(node.querySelectorAll(':scope .layanan-leaf-check'));
        }

        function updateParentState(node) {
            const parentCheck = node.querySelector(':scope > .d-flex .layanan-parent-check');
            if (!parentCheck) return;

            const leaves = descendantLeafChecks(node);
            const checkedCount = leaves.filter(input => input.checked).length;

            parentCheck.checked = leaves.length > 0 && checkedCount === leaves.length;
            parentCheck.indeterminate = checkedCount > 0 && checkedCount < leaves.length;
        }

        function updateAllParentStates() {
            const nodes = Array.from(root.querySelectorAll('.layanan-node')).reverse();
            nodes.forEach(updateParentState);
        }

        function openNode(node) {
            const childrenWrap = node.querySelector(':scope > .layanan-children');
            const toggle = node.querySelector(':scope > .d-flex .layanan-toggle');
            if (!childrenWrap || !toggle) return;

            childrenWrap.classList.remove('d-none');
            toggle.setAttribute('aria-expanded', 'true');
            toggle.querySelector('i')?.classList.remove('bi-caret-right-fill');
            toggle.querySelector('i')?.classList.add('bi-caret-down-fill');
        }

        function closeNode(node) {
            const childrenWrap = node.querySelector(':scope > .layanan-children');
            const toggle = node.querySelector(':scope > .d-flex .layanan-toggle');
            if (!childrenWrap || !toggle) return;

            childrenWrap.classList.add('d-none');
            toggle.setAttribute('aria-expanded', 'false');
            toggle.querySelector('i')?.classList.remove('bi-caret-down-fill');
            toggle.querySelector('i')?.classList.add('bi-caret-right-fill');
        }

        root.addEventListener('click', function (event) {
            const toggle = event.target.closest('.layanan-toggle');
            if (!toggle) return;

            const node = toggle.closest('.layanan-node');
            const childrenWrap = node?.querySelector(':scope > .layanan-children');
            if (!node || !childrenWrap) return;

            if (childrenWrap.classList.contains('d-none')) {
                openNode(node);
            } else {
                closeNode(node);
            }
        });

        root.addEventListener('change', function (event) {
            const target = event.target;
            if (!target.classList.contains('layanan-check')) return;

            const node = target.closest('.layanan-node');
            if (!node) return;

            if (target.classList.contains('layanan-parent-check')) {
                descendantLeafChecks(node).forEach(input => {
                    input.checked = target.checked;
                });
            }

            updateAllParentStates();
        });

        updateAllParentStates();

        function expandToChecked() {
            root.querySelectorAll('.layanan-leaf-check:checked').forEach(function (leaf) {
                let node = leaf.closest('.layanan-node')?.parentElement?.closest('.layanan-node');
                while (node) {
                    openNode(node);
                    node = node.parentElement?.closest('.layanan-node');
                }
            });
        }

        function collapseAll() {
            root.querySelectorAll('.layanan-node').forEach(closeNode);
        }

        expandToChecked();

        // ---- Pencarian / filter tampilan tree ----
        const searchInput = document.getElementById('layananSearch');
        const searchClear = document.getElementById('layananSearchClear');
        const searchEmpty = document.getElementById('layananSearchEmpty');

        function nodeText(node) {
            const head = node.querySelector(':scope > .d-flex');
            if (!head) return '';
            const name = head.querySelector('.fw-semibold')?.textContent || '';
            const code = head.querySelector('.small')?.textContent || '';
            return (name + ' ' + code).toLowerCase();
        }

        function applyFilter(q) {
            q = (q || '').trim().toLowerCase();
            const nodes = Array.from(root.querySelectorAll('.layanan-node'));

            if (!q) {
                nodes.forEach(n => n.classList.remove('d-none'));
                collapseAll();
                expandToChecked();
                if (searchEmpty) searchEmpty.classList.add('d-none');
                return;
            }

            nodes.forEach(n => { n.dataset.match = nodeText(n).indexOf(q) !== -1 ? '1' : '0'; });

            let shownCount = 0;
            nodes.forEach(n => {
                const self = n.dataset.match === '1';
                const descMatch = !!n.querySelector('.layanan-node[data-match="1"]');
                let ancMatch = false, anc = n.parentElement?.closest('.layanan-node');
                while (anc) { if (anc.dataset.match === '1') { ancMatch = true; break; } anc = anc.parentElement?.closest('.layanan-node'); }
                const show = self || descMatch || ancMatch;
                n.classList.toggle('d-none', !show);
                if (show) shownCount++;
            });

            // Buka semua node yang tampil agar jalur & turunannya terlihat.
            nodes.forEach(n => { if (!n.classList.contains('d-none')) openNode(n); });

            if (searchEmpty) searchEmpty.classList.toggle('d-none', shownCount !== 0);
        }

        if (searchInput) {
            searchInput.addEventListener('input', function () { applyFilter(this.value); });
            // Cegah Enter men-submit form saat mengetik di kotak cari.
            searchInput.addEventListener('keydown', function (e) { if (e.key === 'Enter') e.preventDefault(); });
        }
        if (searchClear) {
            searchClear.addEventListener('click', function () {
                if (!searchInput) return;
                searchInput.value = '';
                applyFilter('');
                searchInput.focus();
            });
        }
    });
</script>
@endpush
