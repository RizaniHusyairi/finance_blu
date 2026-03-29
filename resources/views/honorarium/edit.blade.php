@extends('layouts.app')

@section('title')
    Edit Honorarium
@endsection

@section('content')
    <x-page-title title="Manajemen Honor" subtitle="Edit Data Honorarium" />

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="mb-0 text-uppercase">Form Edit Honorarium</h6>
        <a href="{{ route('honorarium.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
    </div>
    <hr>

    @if ($errors->any())
        <div class="alert alert-danger rounded-4">
            <div class="fw-semibold mb-1">Form masih berantakan. Benerin ini dulu:</div>
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('honorarium.update', $honorarium->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="card rounded-4 border-top border-4 border-primary shadow-sm mb-4">
            <div class="card-body p-4">
                <h6 class="fw-bold text-primary mb-4">A. Data Honorarium</h6>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Nomor Kegiatan</label>
                        <input type="text" name="activity_number" class="form-control"
                               value="{{ old('activity_number', $honorarium->activity_number) }}"
                               placeholder="Contoh: 123/KGT/III/2026">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">No Honorarium</label>
                        <input type="text" name="transaction_number" class="form-control bg-light"
                               value="{{ old('transaction_number', $honorarium->transaction_number) }}" readonly>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Tanggal</label>
                        <input type="date" name="date" class="form-control"
                               value="{{ old('date', optional($honorarium->date)->format('Y-m-d')) }}">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">No SPP</label>
                        <input type="text" name="spp_number" class="form-control"
                               value="{{ old('spp_number', $honorarium->spp_number) }}">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Tanggal SPP</label>
                        <input type="date" name="spp_date" class="form-control"
                               value="{{ old('spp_date', optional($honorarium->spp_date)->format('Y-m-d')) }}">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">No BAST</label>
                        <input type="text" name="bast_number" class="form-control"
                               value="{{ old('bast_number', $honorarium->bast_number) }}">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Tanggal BAST</label>
                        <input type="date" name="bast_date" class="form-control"
                               value="{{ old('bast_date', optional($honorarium->bast_date)->format('Y-m-d')) }}">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Pagu Anggaran</label>
                        <select name="budget_id" class="form-select">
                            <option value="">-- Pilih Pagu --</option>
                            @foreach ($budgets as $budget)
                                <option value="{{ $budget->id }}"
                                    {{ old('budget_id', $honorarium->budget_id) == $budget->id ? 'selected' : '' }}>
                                    {{ $budget->coa }} - {{ $budget->description }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">PPK</label>
                        <select name="ppk_id" class="form-select">
                            <option value="">-- Pilih PPK --</option>
                            @foreach ($ppks as $ppk)
                                <option value="{{ $ppk->id }}"
                                    {{ old('ppk_id', $honorarium->ppk_id) == $ppk->id ? 'selected' : '' }}>
                                    {{ $ppk->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-12">
                        <label class="form-label">Uraian</label>
                        <textarea name="description" rows="4" class="form-control">{{ old('description', $honorarium->description) }}</textarea>
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
                                <th>Nama</th>
                                <th>NRP</th>
                                <th>Pangkat/Korp</th>
                                <th>Jabatan</th>
                                <th>Honor</th>
                                <th>PPh</th>
                                <th>Jumlah</th>
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
                                    <input type="text" class="form-control form-control-sm bg-light" id="grandJumlah" readonly>
                                </td>
                                <td colspan="5"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <small class="text-muted">
                    Jumlah dihitung otomatis dari Honor - PPh. PPh kosong dianggap 0.
                </small>
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

    @php
        $oldItems = old(
            'items',
            $honorarium->honorariumItems->map(function ($item) {
                return [
                    'name' => $item->name,
                    'nrp' => $item->nrp,
                    'rank_corps' => $item->rank_corps,
                    'position' => $item->position,
                    'honor_amount' => $item->honor_amount,
                    'pph_amount' => $item->pph_amount,
                    'bank_account_number' => $item->bank_account_number,
                    'bank_name' => $item->bank_name,
                    'bank_account_name' => $item->bank_account_name,
                    'phone_number' => $item->phone_number,
                ];
            })->values()->toArray()
        );
    @endphp

    <script>
        let rowIndex = 0;
        let oldItems = @json($oldItems);

        function toNumber(value) {
            return parseFloat(value) || 0;
        }

        function formatNumber(value) {
            return new Intl.NumberFormat('id-ID').format(value);
        }

        function updateRowNumbers() {
            document.querySelectorAll('#honorariumTable tbody tr').forEach((row, index) => {
                row.querySelector('.row-no').value = index + 1;
            });
        }

        function recalcRow(row) {
            const honor = toNumber(row.querySelector('.honor_amount').value);
            const pph = toNumber(row.querySelector('.pph_amount').value);
            const jumlah = honor - pph;

            row.querySelector('.jumlah_display').value = formatNumber(jumlah);
        }

        function recalcGrandTotal() {
            let totalHonor = 0;
            let totalPph = 0;
            let totalJumlah = 0;

            document.querySelectorAll('#honorariumTable tbody tr').forEach(row => {
                const honor = toNumber(row.querySelector('.honor_amount').value);
                const pph = toNumber(row.querySelector('.pph_amount').value);
                const jumlah = honor - pph;

                totalHonor += honor;
                totalPph += pph;
                totalJumlah += jumlah;
            });

            document.getElementById('grandHonor').value = formatNumber(totalHonor);
            document.getElementById('grandPph').value = formatNumber(totalPph);
            document.getElementById('grandJumlah').value = formatNumber(totalJumlah);
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
                <td>
                    <input type="text" class="form-control form-control-sm row-no text-center bg-light" readonly>
                </td>
                <td>
                    <input type="text" name="items[${rowIndex}][name]" class="form-control form-control-sm"
                           value="${data.name ?? ''}" required>
                </td>
                <td>
                    <input type="text" name="items[${rowIndex}][nrp]" class="form-control form-control-sm"
                           value="${data.nrp ?? ''}">
                </td>
                <td>
                    <input type="text" name="items[${rowIndex}][rank_corps]" class="form-control form-control-sm"
                           value="${data.rank_corps ?? ''}">
                </td>
                <td>
                    <input type="text" name="items[${rowIndex}][position]" class="form-control form-control-sm"
                           value="${data.position ?? ''}">
                </td>
                <td>
                    <input type="number" step="0.01" min="0" name="items[${rowIndex}][honor_amount]"
                           class="form-control form-control-sm honor_amount"
                           value="${data.honor_amount ?? 0}" required>
                </td>
                <td>
                    <input type="number" step="0.01" min="0" name="items[${rowIndex}][pph_amount]"
                           class="form-control form-control-sm pph_amount"
                           value="${data.pph_amount ?? 0}">
                </td>
                <td>
                    <input type="text" class="form-control form-control-sm jumlah_display bg-light" readonly>
                </td>
                <td>
                    <input type="text" name="items[${rowIndex}][bank_account_number]" class="form-control form-control-sm"
                           value="${data.bank_account_number ?? ''}">
                </td>
                <td>
                    <input type="text" name="items[${rowIndex}][bank_name]" class="form-control form-control-sm"
                           value="${data.bank_name ?? ''}">
                </td>
                <td>
                    <input type="text" name="items[${rowIndex}][bank_account_name]" class="form-control form-control-sm"
                           value="${data.bank_account_name ?? ''}">
                </td>
                <td>
                    <input type="text" name="items[${rowIndex}][phone_number]" class="form-control form-control-sm"
                           value="${data.phone_number ?? ''}">
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

        document.getElementById('btnAddRow').addEventListener('click', function () {
            addRow();
        });

        document.addEventListener('input', function (e) {
            if (e.target.classList.contains('honor_amount') || e.target.classList.contains('pph_amount')) {
                const row = e.target.closest('tr');
                recalcRow(row);
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

        if (oldItems.length > 0) {
            oldItems.forEach(item => addRow(item));
        } else {
            addRow();
        }
    </script>
@endsection