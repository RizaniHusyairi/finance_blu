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

    <form action="{{ route('honorarium.store') }}" method="POST" enctype="multipart/form-data">
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
                        @include('partials.dipa-item-grouped-select', [
                            'budgetGroups' => $budgetGroups,
                            'fieldName' => 'dipa_revision_item_id',
                            'fieldId' => 'dipa_revision_item_id',
                            'fieldClass' => 'form-select select2',
                            'fieldLabel' => 'Sumber Anggaran (Item DIPA / COA)',
                            'placeholder' => '-- Pilih Item Anggaran DIPA Aktif --',
                        ])
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Uraian / Deskripsi Kegiatan <span class="text-danger">*</span></label>
                        <input type="text" name="deskripsi" class="form-control" required
                            value="{{ old('deskripsi') }}" placeholder="Contoh: Pembayaran Honor Narasumber Sosialisasi...">
                    </div>
                </div>
                <div class="row g-3 mt-1">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Verifikator PPK <span class="text-danger">*</span></label>
                        <select name="ppk_id" class="form-select select2" required>
                            <option value="">-- Pilih Pejabat Pembuat Komitmen --</option>
                            @foreach($ppkUsers as $ppk)
                                <option value="{{ $ppk->id }}" {{ old('ppk_id') == $ppk->id ? 'selected' : '' }}>{{ $ppk->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Verifikator Bendahara Pengeluaran <span class="text-danger">*</span></label>
                        <select name="bendahara_pengeluaran_id" class="form-select select2" required>
                            <option value="">-- Pilih Bendahara Pengeluaran --</option>
                            @foreach($bendaharaUsers as $bendahara)
                                <option value="{{ $bendahara->id }}" {{ old('bendahara_pengeluaran_id') == $bendahara->id ? 'selected' : '' }}>{{ $bendahara->name }}</option>
                            @endforeach
                        </select>
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
                                <th>NRP/NIP/NIK</th>
                                <th>Pangkat/Korp</th>
                                <th>Jabatan</th>
                                <th>Honor (Rp)</th>
                                <th>PPh (Rp)</th>
                                <th>Netto</th>
                                <th>No Rekening</th>
                                <th>Jenis Bank</th>
                                <th>Nama Rekening</th>
                                <th>No HP</th>
                                <th style="width: 70px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                        <tfoot>
                            <tr class="table-light fw-bold">
                                <td colspan="5" class="text-end">TOTAL</td>
                                <td>
                                    <input type="text" class="form-control form-control-sm bg-light" id="grandHonor" readonly>
                                </td>
                                <td>
                                    <input type="text" class="form-control form-control-sm bg-light" id="grandPph" readonly>
                                </td>
                                <td>
                                    <input type="text" class="form-control form-control-sm bg-light" id="grandNetto" readonly>
                                </td>
                                <td colspan="5"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <div class="card rounded-4 border-top border-4 border-warning shadow-sm mb-4">
            <div class="card-body p-4">
                <h6 class="fw-bold text-warning mb-4"><i class="bi bi-file-earmark-pdf me-2"></i>C. Upload File SK Dasar Pembayaran Honorarium</h6>
                <div class="row">
                    <div class="col-md-6">
                        <label class="form-label">Silakan unggah dokumen SK Honorarium (Opsional) <span class="text-muted"><small>Format: PDF, DOCX (Maks 10MB)</small></span></label>
                        <input type="file" name="file_sk" id="file_sk" accept=".pdf,.doc,.docx" class="form-control">
                        <div class="form-text">File yang diunggah akan otomatis terarsip dalam lampiran dokumen Tagihan NPI Honorarium.</div>
                    </div>
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
        
        const pphSelect = row.querySelector('.pph_percentage');
        let pphPct = 0;
        if (pphSelect) pphPct = toNumber(pphSelect.options[pphSelect.selectedIndex].getAttribute('data-pct'));
        
        let pphAmount = (honor * pphPct) / 100;
        row.querySelector('.pph_amount').value = pphAmount;
        if(row.querySelector('.pph_display')) {
            row.querySelector('.pph_display').value = formatRp(pphAmount);
        }
        
        row.querySelector('.netto_display').value = formatRp(honor - Math.round(pphAmount));
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
            <td>
                <input type="number" step="0.01" min="0" name="items[${rowIndex}][nilai_honor]"
                       class="form-control form-control-sm honor_amount"
                       value="${data.nilai_honor ?? 0}" required>
            </td>
            <td>
                <select class="form-select form-select-sm pph_percentage mb-1" style="font-size: 11px;">
                    <option value="0" data-pct="0">0% (Tanpa PPh)</option>
                    @foreach($tarifPajaks as $tp)
                        <option value="{{ $tp->persentase }}" data-pct="{{ $tp->persentase }}" ${data.pph_pct == {{ $tp->persentase }} ? 'selected' : ''}>
                            {{ $tp->kode_pajak }} ({{ (float)$tp->persentase }}%)
                        </option>
                    @endforeach
                </select>
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light border-end-0 text-muted" style="padding: 0.1rem 0.3rem; font-size:10px;">Rp</span>
                    <input type="text" class="form-control form-control-sm pph_display bg-light border-start-0 px-1" readonly style="font-size:11px;" value="${formatRp(data.pph ?? 0)}">
                </div>
                <input type="hidden" name="items[${rowIndex}][pph]" class="pph_amount" value="${data.pph ?? 0}">
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
            <td>
                <input type="text" name="items[${rowIndex}][no_hp]" class="form-control form-control-sm"
                       value="${data.no_hp ?? ''}">
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

    document.addEventListener('change', function (e) {
        if (e.target.classList.contains('pph_percentage')) {
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

    if (window.jQuery && typeof window.jQuery.fn.select2 === 'function') {
        window.jQuery('.select2').select2({
            theme: 'bootstrap-5',
            width: '100%',
            placeholder: '-- Pilih Item Anggaran DIPA Aktif --'
        });
    }

    // Start with 1 row
    addRow();
    </script>
@endsection
