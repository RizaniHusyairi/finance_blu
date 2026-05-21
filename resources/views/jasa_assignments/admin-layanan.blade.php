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

        root.querySelectorAll('.layanan-leaf-check:checked').forEach(function (leaf) {
            let node = leaf.closest('.layanan-node')?.parentElement?.closest('.layanan-node');
            while (node) {
                openNode(node);
                node = node.parentElement?.closest('.layanan-node');
            }
        });
    });
</script>
@endpush
