@extends('layouts.app')

@section('title')
    Edit Honorarium
@endsection

@section('content')
    <x-page-title title="Manajemen Honor" subtitle="Edit Data Honorarium" />

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="mb-0 text-uppercase">Edit Honorarium — <span class="text-primary">{{ $tagihan->nomor_tagihan }}</span></h6>
        <a href="{{ route('honorarium.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
    </div>
    <hr>

    @if ($errors->any())
        <div class="alert alert-danger rounded-4">
            <div class="fw-semibold mb-1">Terdapat kesalahan:</div>
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('honorarium.update', $tagihan->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="card rounded-4 border-top border-4 border-primary shadow-sm mb-4">
            <div class="card-body p-4">
                <h6 class="fw-bold text-primary mb-4">A. Data Honorarium</h6>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">No Tagihan</label>
                        <input type="text" class="form-control bg-light" value="{{ $tagihan->nomor_tagihan }}" readonly>
                    </div>
                    <div class="col-md-4">
                        @include('partials.dipa-item-grouped-select', [
                            'budgetGroups' => $budgetGroups,
                            'fieldName' => 'dipa_revision_item_id',
                            'fieldId' => 'dipa_revision_item_id',
                            'fieldClass' => 'form-select select2',
                            'fieldLabel' => 'Sumber Anggaran (Item DIPA / COA)',
                            'placeholder' => '-- Pilih Item Anggaran DIPA Aktif --',
                            'selectedValue' => old('dipa_revision_item_id', $tagihan->dipa_revision_item_id),
                        ])
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Uraian / Deskripsi Kegiatan <span class="text-danger">*</span></label>
                        <input type="text" name="deskripsi" class="form-control" required
                            value="{{ old('deskripsi', $tagihan->deskripsi) }}">
                    </div>
                </div>
            </div>
        </div>

        <div class="card rounded-4 border-top border-4 border-success shadow-sm mb-4">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold text-success mb-0">B. Rincian Penerima Honorarium</h6>
                    <button type="button" class="btn btn-sm btn-success" id="btnAddRow">
                        <i class="bi bi-plus-circle"></i> Tambah Baris
                    </button>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered align-middle" id="honorariumTable">
                        <thead class="table-light text-center">
                            <tr>
                                <th style="width: 50px;">No</th>
                                <th>Nama Personel</th>
                                <th>NRP/NIP</th>
                                <th>Pangkat/Korp</th>
                                <th>Jabatan</th>
                                <th>Honor (Rp)</th>
                                <th>PPh (Rp)</th>
                                <th>Netto</th>
                                <th>No Rekening</th>
                                <th>Jenis Bank</th>
                                <th>Nama Rekening</th>
                                <th style="width: 70px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                        <tfoot>
                            <tr class="table-light fw-bold">
                                <td colspan="5" class="text-end">TOTAL</td>
                                <td><input type="text" class="form-control form-control-sm bg-light" id="grandHonor" readonly></td>
                                <td><input type="text" class="form-control form-control-sm bg-light" id="grandPph" readonly></td>
                                <td><input type="text" class="form-control form-control-sm bg-light" id="grandNetto" readonly></td>
                                <td colspan="4"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <div class="d-flex gap-2">
            <button type="submit" name="submit_type" value="draft" class="btn btn-secondary">
                <i class="bi bi-save"></i> Simpan Draft
            </button>
            <button type="submit" name="submit_type" value="submit_ppk" class="btn btn-primary">
                <i class="bi bi-send"></i> Ajukan ke PPK
            </button>
            <a href="{{ route('honorarium.index') }}" class="btn btn-light">Batal</a>
        </div>
    </form>

    <script>
    let rowIndex = 0;

    function toNumber(value) { return parseFloat(String(value).replace(/,/g, '')) || 0; }
    function formatRp(value) { return new Intl.NumberFormat('id-ID').format(value); }

    function updateRowNumbers() {
        document.querySelectorAll('#honorariumTable tbody tr').forEach((row, i) => {
            row.querySelector('.row-no').textContent = i + 1;
        });
    }

    function recalcRow(row) {
        const honor = toNumber(row.querySelector('.honor_amount').value);
        const pph = toNumber(row.querySelector('.pph_amount').value);
        row.querySelector('.netto_display').value = formatRp(honor - pph);
    }

    function recalcGrandTotal() {
        let tH = 0, tP = 0, tN = 0;
        document.querySelectorAll('#honorariumTable tbody tr').forEach(row => {
            const h = toNumber(row.querySelector('.honor_amount').value);
            const p = toNumber(row.querySelector('.pph_amount').value);
            tH += h; tP += p; tN += (h - p);
        });
        document.getElementById('grandHonor').value = formatRp(tH);
        document.getElementById('grandPph').value = formatRp(tP);
        document.getElementById('grandNetto').value = formatRp(tN);
    }

    function recalcAll() {
        document.querySelectorAll('#honorariumTable tbody tr').forEach(row => recalcRow(row));
        recalcGrandTotal();
        updateRowNumbers();
    }

    function addRow(data = {}) {
        const tbody = document.querySelector('#honorariumTable tbody');
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td class="text-center row-no"></td>
            <td>
                <input type="text" name="items[${rowIndex}][nama_personel]" class="form-control form-control-sm" value="${data.nama_personel ?? ''}" required>
            </td>
            <td>
                <input type="text" name="items[${rowIndex}][nrp_nip]" class="form-control form-control-sm" value="${data.nrp_nip ?? ''}">
            </td>
            <td>
                <input type="text" name="items[${rowIndex}][pangkat_korp]" class="form-control form-control-sm" value="${data.pangkat_korp ?? ''}">
            </td>
            <td>
                <input type="text" name="items[${rowIndex}][jabatan]" class="form-control form-control-sm" value="${data.jabatan ?? ''}">
            </td>
            <td><input type="number" step="0.01" min="0" name="items[${rowIndex}][nilai_honor]" class="form-control form-control-sm honor_amount" value="${data.nilai_honor ?? 0}" required></td>
            <td><input type="number" step="0.01" min="0" name="items[${rowIndex}][pph]" class="form-control form-control-sm pph_amount" value="${data.pph ?? 0}"></td>
            <td><input type="text" class="form-control form-control-sm netto_display bg-light" readonly></td>
            <td><input type="text" name="items[${rowIndex}][rekening]" class="form-control form-control-sm" value="${data.rekening ?? ''}"></td>
            <td><input type="text" name="items[${rowIndex}][jenis_bank]" class="form-control form-control-sm" value="${data.jenis_bank ?? ''}"></td>
            <td><input type="text" name="items[${rowIndex}][nama_rekening]" class="form-control form-control-sm" value="${data.nama_rekening ?? ''}"></td>
            <td class="text-center"><button type="button" class="btn btn-sm btn-danger btn-remove-row"><i class="bi bi-trash"></i></button></td>
        `;
        tbody.appendChild(tr);
        rowIndex++;
        recalcAll();
    }

    document.getElementById('btnAddRow').addEventListener('click', () => addRow());
    document.addEventListener('input', function (e) {
        if (e.target.classList.contains('honor_amount') || e.target.classList.contains('pph_amount')) {
            recalcRow(e.target.closest('tr'));
            recalcGrandTotal();
        }
    });
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.btn-remove-row');
        if (btn) { btn.closest('tr').remove(); recalcAll(); }
    });

    if (window.jQuery && typeof window.jQuery.fn.select2 === 'function') {
        window.jQuery('.select2').select2({
            theme: 'bootstrap-5',
            width: '100%',
            placeholder: '-- Pilih Item Anggaran DIPA Aktif --'
        });
    }

    // Pre-populate existing rows from DB
    const existingItems = @json($tagihan->detailHonorarium);
    if (existingItems.length > 0) {
        existingItems.forEach(item => addRow(item));
    } else {
        addRow();
    }
    </script>
@endsection
