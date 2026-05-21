@extends('layouts.app')
@section('title', 'Pengaturan Layanan Mitra')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
    <div>
        <h4 class="mb-0 fw-bold">Pengaturan Layanan Mitra</h4>
        <p class="mb-0 small">{{ $mitra->nama_mitra }} | {{ $mitra->email ?: 'Email belum diisi' }}</p>
    </div>
    <a href="{{ route('jasa.mitra.show', $mitra) }}" class="btn btn-secondary fw-bold">Kembali</a>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<form action="{{ route('jasa.mitra.layanan.update', $mitra) }}" method="POST">
    @csrf
    @method('PUT')
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-white p-3 border-bottom rounded-top-4 d-flex justify-content-between align-items-center">
            <div>
                <h6 class="mb-0 fw-bold">Pilih Layanan yang Berlaku untuk Mitra</h6>
                <small class="text-muted">Centang kategori untuk memilih seluruh item tarif di bawahnya. Klik panah untuk membuka turunan layanan.</small>
            </div>
            <button type="submit" class="btn btn-success fw-bold">Simpan Pengaturan</button>
        </div>
        <div class="card-body p-4">
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
            Array.from(root.querySelectorAll('.layanan-node')).reverse().forEach(updateParentState);
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
