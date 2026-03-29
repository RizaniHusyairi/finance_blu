@extends('layouts.app')

@section('title')
    Input Honorarium
@endsection

@section('content')
    <x-page-title title="Manajemen Honor" subtitle="Input Data Honorarium" />

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="mb-0 text-uppercase">Form Input Honorarium</h6>
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

    <form action="{{ route('honorarium.store') }}" method="POST">
        @csrf

        <div class="card rounded-4 border-top border-4 border-primary shadow-sm mb-4">
            <div class="card-body p-4">
                <h6 class="fw-bold text-primary mb-4">A. Data Honorarium</h6>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">No Tagihan</label>
                        <input type="text" class="form-control bg-light" value="{{ $nextNumber }}" readonly>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Sumber Anggaran (DIPA) <span class="text-danger">*</span></label>
                        <select name="master_dipa_id" class="form-select" required>
                            <option value="">-- Pilih DIPA --</option>
                            @foreach ($dipas as $dipa)
                                <option value="{{ $dipa->id }}" {{ old('master_dipa_id') == $dipa->id ? 'selected' : '' }}>
                                    {{ $dipa->tahun_anggaran }} — {{ $dipa->nomor_dipa ?? 'DIPA #'.$dipa->id }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Uraian / Deskripsi Kegiatan <span class="text-danger">*</span></label>
                        <input type="text" name="deskripsi" class="form-control" required
                            value="{{ old('deskripsi') }}" placeholder="Contoh: Pembayaran Honor Narasumber Sosialisasi...">
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
                                <th>Personel</th>
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
                                <td colspan="2" class="text-end">TOTAL</td>
                                <td>
                                    <input type="text" class="form-control form-control-sm bg-light" id="grandHonor" readonly>
                                </td>
                                <td>
                                    <input type="text" class="form-control form-control-sm bg-light" id="grandPph" readonly>
                                </td>
                                <td>
                                    <input type="text" class="form-control form-control-sm bg-light" id="grandNetto" readonly>
                                </td>
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
    const personelsData = @json($personels);

    function toNumber(value) { return parseFloat(String(value).replace(/,/g, '')) || 0; }
    function formatRp(value) { return new Intl.NumberFormat('id-ID').format(value); }

    function buildPersonelOptions(selectedId) {
        let html = '<option value="">-- Pilih Personel --</option>';
        personelsData.forEach(p => {
            const sel = p.id == selectedId ? 'selected' : '';
            html += `<option value="${p.id}" data-nrp="${p.nrp_nik}" data-pangkat="${p.pangkat}" data-jabatan="${p.jabatan}" ${sel}>${p.nama_lengkap} (${p.nrp_nik})</option>`;
        });
        return html;
    }

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
        let tHonor = 0, tPph = 0, tNetto = 0;
        document.querySelectorAll('#honorariumTable tbody tr').forEach(row => {
            const h = toNumber(row.querySelector('.honor_amount').value);
            const p = toNumber(row.querySelector('.pph_amount').value);
            tHonor += h; tPph += p; tNetto += (h - p);
        });
        document.getElementById('grandHonor').value = formatRp(tHonor);
        document.getElementById('grandPph').value = formatRp(tPph);
        document.getElementById('grandNetto').value = formatRp(tNetto);
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
                <select name="items[${rowIndex}][personel_id]" class="form-select form-select-sm personel-select" required>
                    ${buildPersonelOptions(data.personel_id ?? '')}
                </select>
            </td>
            <td>
                <input type="number" step="0.01" min="0" name="items[${rowIndex}][nilai_honor]"
                       class="form-control form-control-sm honor_amount"
                       value="${data.nilai_honor ?? 0}" required>
            </td>
            <td>
                <input type="number" step="0.01" min="0" name="items[${rowIndex}][pph]"
                       class="form-control form-control-sm pph_amount"
                       value="${data.pph ?? 0}">
            </td>
            <td>
                <input type="text" class="form-control form-control-sm netto_display bg-light" readonly>
            </td>
            <td>
                <input type="text" name="items[${rowIndex}][rekening]" class="form-control form-control-sm"
                       value="${data.rekening ?? ''}">
            </td>
            <td>
                <input type="text" name="items[${rowIndex}][jenis_bank]" class="form-control form-control-sm"
                       value="${data.jenis_bank ?? ''}">
            </td>
            <td>
                <input type="text" name="items[${rowIndex}][nama_rekening]" class="form-control form-control-sm"
                       value="${data.nama_rekening ?? ''}">
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-danger btn-remove-row">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
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
        if (btn) {
            btn.closest('tr').remove();
            recalcAll();
        }
    });

    // Start with 1 row
    addRow();
    </script>
@endsection