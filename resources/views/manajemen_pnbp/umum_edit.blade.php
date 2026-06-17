@extends('layouts.app')
@section('title', 'Input PNBP Umum')

@section('content')
@php($accent = '#b45309')

<style>
    .umum-grid { overflow-x: auto; border: 1px solid #e6ecf5; border-radius: 14px; }
    .umum-table { min-width: 1180px; margin: 0; border-collapse: separate; border-spacing: 0; font-size: .84rem; }
    .umum-table th, .umum-table td { padding: .5rem .6rem; border-bottom: 1px solid #eef2f8; white-space: nowrap; vertical-align: middle; }
    .umum-table thead th { position: sticky; top: 0; z-index: 3; background: #fdf2e4; color: #475569;
        font-size: .72rem; font-weight: 700; text-transform: uppercase; letter-spacing: .4px;
        box-shadow: inset 0 -2px 0 {{ $accent }}; }
    .umum-table .col-uraian { position: sticky; left: 0; z-index: 2; background: #fff; min-width: 340px; max-width: 400px;
        white-space: normal; box-shadow: 10px 0 14px -10px rgba(15,23,42,.2); }
    .umum-table thead .col-uraian { z-index: 5; background: #fdf2e4; }
    .umum-table input.cell-input { width: 96px; text-align: right; font-variant-numeric: tabular-nums; }
    .umum-table input.uraian-input { width: 100%; min-width: 220px; }
    .umum-table .row-total { font-variant-numeric: tabular-nums; text-align: right; font-weight: 700; color: {{ $accent }}; }
    .umum-table tfoot td { position: sticky; bottom: 0; z-index: 3; background: #fbeedd; font-weight: 700;
        border-top: 2px solid {{ $accent }}; }
    .btn-del { line-height: 1; }
</style>

<div class="d-flex align-items-center flex-wrap gap-2 mb-3">
    <a href="{{ route('manajemen-pnbp.index', ['tahun' => $tahun]) }}" class="btn btn-sm btn-light border">
        <i class="bi bi-arrow-left"></i>
    </a>
    <div>
        <h5 class="mb-0 fw-bold">Input Realisasi PNBP Umum</h5>
        <small class="text-secondary">Isi nilai realisasi tiap item per bulan (angka tanpa titik/koma). Uraian dapat ditambah, diubah, atau dihapus.</small>
    </div>
    <form method="GET" class="ms-auto d-flex align-items-center gap-2">
        <label class="small text-secondary mb-0">Tahun</label>
        <input type="number" name="tahun" value="{{ $tahun }}" min="2000" max="2100"
               class="form-control form-control-sm" style="width:110px" onchange="this.form.submit()">
    </form>
</div>

@if ($errors->any())
    <div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-1"></i>Periksa kembali isian: pastikan nilai berupa angka ≥ 0 dan uraian tidak melebihi 255 karakter.</div>
@endif

<form method="POST" action="{{ route('manajemen-pnbp.umum.store') }}" id="umumForm">
    @csrf
    <input type="hidden" name="tahun" value="{{ $tahun }}">

    <div class="d-flex mb-2">
        <button type="button" class="btn btn-sm btn-outline-dark" id="btnAddRow">
            <i class="bi bi-plus-lg me-1"></i>Tambah Uraian
        </button>
    </div>

    <div class="card radius-10 border-0 shadow-sm overflow-hidden">
        <div class="card-body p-0">
            <div class="umum-grid">
                <table class="umum-table">
                    <thead>
                        <tr class="text-center">
                            <th class="col-uraian text-start">Uraian</th>
                            @foreach ($bulanLabels as $bl)
                                <th>{{ $bl }}</th>
                            @endforeach
                            <th class="text-end">Total</th>
                        </tr>
                    </thead>
                    <tbody id="umumBody">
                        @foreach ($items as $item)
                            <tr data-row data-existing-id="{{ $item->id }}">
                                <td class="col-uraian">
                                    <div class="d-flex align-items-center gap-2">
                                        <button type="button" class="btn btn-sm btn-link text-danger p-0 btn-del" title="Hapus uraian"><i class="bi bi-trash"></i></button>
                                        <input type="text" name="uraian[{{ $item->id }}]"
                                               value="{{ old("uraian.$item->id", $item->uraian) }}"
                                               class="form-control form-control-sm uraian-input" placeholder="Nama uraian">
                                    </div>
                                </td>
                                @for ($m = 1; $m <= 12; $m++)
                                    @php($val = old("nilai.$item->id.$m", $nilai->get($item->id)?->get($m)))
                                    <td>
                                        <input type="number" min="0" step="any"
                                               name="nilai[{{ $item->id }}][{{ $m }}]"
                                               value="{{ $val ?: '' }}"
                                               class="form-control form-control-sm cell-input" placeholder="0">
                                    </td>
                                @endfor
                                <td class="row-total">0</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="text-end">
                            <td class="col-uraian text-end text-uppercase" style="letter-spacing:.5px">Jumlah</td>
                            @for ($m = 1; $m <= 12; $m++)
                                <td class="col-sum" data-bulan="{{ $m }}">0</td>
                            @endfor
                            <td class="grand-total">0</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-end gap-2 mt-3">
        <a href="{{ route('manajemen-pnbp.index', ['tahun' => $tahun]) }}" class="btn btn-light border">Batal</a>
        <button type="submit" class="btn text-white" style="background:{{ $accent }}">
            <i class="bi bi-save me-1"></i>Simpan
        </button>
    </div>
</form>

{{-- Template baris baru --}}
<template id="rowTpl">
    <tr data-row>
        <td class="col-uraian">
            <div class="d-flex align-items-center gap-2">
                <button type="button" class="btn btn-sm btn-link text-danger p-0 btn-del" title="Hapus uraian"><i class="bi bi-trash"></i></button>
                <input type="text" name="uraian[__KEY__]" value="" class="form-control form-control-sm uraian-input" placeholder="Nama uraian baru">
            </div>
        </td>
        @for ($m = 1; $m <= 12; $m++)
            <td><input type="number" min="0" step="any" name="nilai[__KEY__][{{ $m }}]" value="" class="form-control form-control-sm cell-input" placeholder="0"></td>
        @endfor
        <td class="row-total">0</td>
    </tr>
</template>
@endsection

@push('script')
<script>
(function () {
    const form = document.getElementById('umumForm');
    const tbody = document.getElementById('umumBody');
    const tpl = document.getElementById('rowTpl');
    const idr = (v) => v ? Number(v).toLocaleString('id-ID') : '–';
    let newCounter = 0;

    function recalc() {
        const colSum = {};
        let grand = 0;
        tbody.querySelectorAll('tr[data-row]').forEach((tr) => {
            let rowTotal = 0;
            tr.querySelectorAll('.cell-input').forEach((inp) => {
                const m = inp.name.match(/\[(\d+)\]$/)[1];
                const v = parseFloat(inp.value) || 0;
                rowTotal += v;
                colSum[m] = (colSum[m] || 0) + v;
            });
            tr.querySelector('.row-total').textContent = idr(rowTotal);
            grand += rowTotal;
        });
        document.querySelectorAll('.col-sum').forEach((td) => {
            td.textContent = idr(colSum[td.dataset.bulan] || 0);
        });
        document.querySelector('.grand-total').textContent = idr(grand);
    }

    // Tambah baris baru
    document.getElementById('btnAddRow').addEventListener('click', () => {
        const key = 'new_' + (newCounter++);
        const html = tpl.innerHTML.replace(/__KEY__/g, key);
        tbody.insertAdjacentHTML('beforeend', html);
        const row = tbody.lastElementChild;
        row.querySelector('.uraian-input')?.focus();
        recalc();
    });

    // Hapus baris (existing → tandai deleted[], baru → buang saja)
    tbody.addEventListener('click', (e) => {
        const btn = e.target.closest('.btn-del');
        if (!btn) return;
        const tr = btn.closest('tr');
        const id = tr.dataset.existingId;
        if (id) {
            if (!confirm('Hapus uraian ini beserta seluruh nilai realisasinya?')) return;
            const h = document.createElement('input');
            h.type = 'hidden'; h.name = 'deleted[]'; h.value = id;
            form.appendChild(h);
        }
        tr.remove();
        recalc();
    });

    form.addEventListener('input', (e) => {
        if (e.target.classList.contains('cell-input')) recalc();
    });

    recalc();
})();
</script>
@endpush
