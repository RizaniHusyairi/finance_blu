@extends('layouts.app')
@section('title')
    Tambah Data Perjaldin
@endsection
@section('content')
    <x-page-title title="Manajemen Perjaldin" subtitle="Tambah Data" />

    <div class="card">
        <div class="card-body">
            <h6 class="mb-4">Form Pengajuan Perjaldin Baru</h6>
            @if ($errors->any())
                <div class="alert alert-danger border-0 bg-danger alert-dismissible fade show">
                    <ul class="text-white mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <form action="{{ route('perjaldins.store') }}" method="POST">
                @csrf

                <h5 class="mb-3 border-bottom pb-2">Informasi Rencana Acara & Anggaran</h5>
                <div class="row mb-4">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Uraian / Judul Perjalanan Dinas <span class="text-danger">*</span></label>
                        <input type="text" name="deskripsi" class="form-control" placeholder="Contoh: Rapat Koordinasi..."
                            required value="{{ old('deskripsi') }}">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">No BAST (Opsional)</label>
                        <input type="text" name="no_bast" class="form-control" placeholder="" value="{{ old('no_bast') }}">
                    </div>
                    <div class="col-md-4 mb-3">
                        @include('partials.dipa-item-grouped-select', [
                            'budgetGroups' => $budgetGroups,
                            'fieldName' => 'dipa_revision_item_id',
                            'fieldId' => 'dipa_revision_item_id',
                            'fieldClass' => 'form-select select2',
                            'fieldLabel' => 'Sumber Anggaran (Item DIPA / COA)',
                            'placeholder' => '-- Pilih Item Anggaran DIPA Aktif --',
                        ])
                    </div>
                </div>

                <h5 class="mb-3 border-bottom pb-2">Daftar Pegawai yang Berangkat</h5>
                <div class="table-responsive">
                    <table class="table table-bordered align-middle" id="repeaterTable" style="min-width: 1500px;">
                        <thead class="table-light text-center">
                            <tr>
                                <th>#</th>
                                <th>Pegawai</th>
                                <th>No SPT</th>
                                <th>Provinsi & Tipe</th>
                                <th>Tanggal & Lama</th>
                                <th>Rincian Biaya (Tiket, Transport, Penginapan, UH, Representasi)</th>
                                <th>Jumlah</th>
                                <th>Rekening</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Baris pertama (Template) -->
                            <tr class="item-row">
                                <td class="text-center row-number">1</td>
                                <td>
                                    <input type="text" class="form-control mb-1" name="peserta[0][nama_pegawai]" placeholder="Nama Pegawai" required>
                                    <input type="text" class="form-control form-control-sm" name="peserta[0][nip]" placeholder="NIP (Opsional)">
                                </td>
                                <td>
                                    <input type="text" class="form-control" name="peserta[0][no_spt]"
                                        placeholder="No SPT" required>
                                </td>
                                <td>
                                    <select class="form-select mb-1 provinsi-select" name="peserta[0][provinsi_id]" style="min-width: 150px;">
                                        <option value="">-- Provinsi --</option>
                                        @foreach($masterProvinsi as $prov)
                                            <option value="{{ $prov->id }}">{{ $prov->provinsi }}</option>
                                        @endforeach
                                    </select>
                                    <select class="form-select mb-1 tipe-perjalanan-select" name="peserta[0][tipe_perjalanan]" style="min-width: 150px;">
                                        <option value="Luar Kota">Luar Kota</option>
                                        <option value="Dalam Kota Lebih Dari 8 Jam">Dalam Kota &gt; 8 Jam</option>
                                        <option value="Diklat">Diklat</option>
                                    </select>
                                </td>
                                <td>
                                    <input type="date" class="form-control mb-1" name="peserta[0][tgl_berangkat]"
                                        required>
                                    <div class="input-group">
                                        <input type="number" class="form-control lama-hari-input" name="peserta[0][lama_hari]"
                                            placeholder="Lama" required min="1">
                                        <span class="input-group-text">Hari</span>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex flex-wrap gap-1">
                                        <input type="text" class="form-control form-control-sm biaya-input"
                                            name="peserta[0][biaya_tiket]" placeholder="Tiket" style="width: 120px;"
                                            onkeyup="calculateJumlah(this)">
                                        <input type="text" class="form-control form-control-sm biaya-input"
                                            name="peserta[0][biaya_transport]" placeholder="Transport" style="width: 120px;"
                                            onkeyup="calculateJumlah(this)">
                                        <input type="text" class="form-control form-control-sm biaya-input"
                                            name="peserta[0][biaya_penginapan]" placeholder="Penginapan" style="width: 120px;"
                                            onkeyup="calculateJumlah(this)">
                                        <input type="text" class="form-control form-control-sm biaya-input uang-harian-input"
                                            name="peserta[0][uang_harian]" placeholder="Uang Harian" style="width: 120px;"
                                            onkeyup="calculateJumlah(this)">
                                        <input type="text" class="form-control form-control-sm biaya-input"
                                            name="peserta[0][uang_representasi]" placeholder="Representasi"
                                            style="width: 120px;" onkeyup="calculateJumlah(this)">
                                    </div>
                                </td>
                                <td>
                                    <input type="text" class="form-control text-end row-jumlah" readonly value="0">
                                </td>
                                <td>
                                    <input type="text" class="form-control" name="peserta[0][rekening]"
                                        placeholder="Ex: 0001 (BRI)">
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-outline-danger btn-sm btn-delete-row" disabled><i
                                            class="bi bi-trash"></i></button>
                                </td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="6" class="text-end fw-bold">TOTAL KESELURUHAN</td>
                                <td>
                                    <input type="text" class="form-control text-end fw-bold bg-light" id="grandTotal"
                                        readonly value="0">
                                </td>
                                <td colspan="2"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="mt-3">
                    <button type="button" class="btn btn-secondary" id="btnAddRow"><i class="bi bi-plus-circle"></i> Tambah
                        Pegawai</button>
                </div>

                <div class="mt-5 text-end">
                    <a href="{{ route('perjaldins.index') }}" class="btn btn-light"><i class="bi bi-x-circle"></i> Batal</a>
                    <button type="submit" class="btn btn-primary px-4"><i class="bi bi-save"></i> Simpan Data</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('script')
    <script>
        let rowIdx = 1;
        const masterTarif = @json($masterProvinsi);

        // Formatting numbers to string with commas
        function formatNumber(n) {
            return n.toString().replace(/\D/g, "").replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        }

        // Auto calculate per row and grand total
        window.calculateJumlah = function (element) {
            let val = $(element).val();
            $(element).val(formatNumber(val));

            let tr = $(element).closest('tr');
            let total = 0;
            tr.find('.biaya-input').each(function () {
                let num = parseFloat($(this).val().replace(/,/g, ''));
                if (!isNaN(num)) total += num;
            });
            tr.find('.row-jumlah').val(formatNumber(total));
            calculateGrandTotal();
        }

        function calculateGrandTotal() {
            let grandTotal = 0;
            $('.row-jumlah').each(function () {
                let num = parseFloat($(this).val().replace(/,/g, ''));
                if (!isNaN(num)) grandTotal += num;
            });
            $('#grandTotal').val(formatNumber(grandTotal));
        }

        $(document).ready(function () {
            $('.select2').select2({
                theme: 'bootstrap-5',
                width: '100%',
                placeholder: '-- Pilih Item Anggaran DIPA Aktif --'
            });

            // Add Row
            $('#btnAddRow').click(function () {
                let newRow = $('#repeaterTable tbody tr:first').clone();

                newRow.find('input, textarea').val('');
                newRow.find('.row-jumlah').val('0');

                newRow.find('input, select, textarea').each(function () {
                    var name = $(this).attr('name');
                    if (name) {
                        name = name.replace(/\[0\]/g, '[' + rowIdx + ']');
                        $(this).attr('name', name);
                    }
                });

                newRow.find('.btn-delete-row').prop('disabled', false);
                $('#repeaterTable tbody').append(newRow);
                updateRowNumbers();
                // Enable all delete buttons since we have > 1 row
                $('.btn-delete-row').prop('disabled', false);
                rowIdx++;
            });

            // Delete Row
            $(document).on('click', '.btn-delete-row', function () {
                if ($('#repeaterTable tbody tr').length > 1) {
                    $(this).closest('tr').remove();
                    updateRowNumbers();
                    calculateGrandTotal();
                    if ($('#repeaterTable tbody tr').length === 1) {
                        $('.btn-delete-row').prop('disabled', true);
                    }
                }
            });

            function updateRowNumbers() {
                $('#repeaterTable tbody tr').each(function (index) {
                    $(this).find('.row-number').text(index + 1);
                });
            }

            // Auto-fill Uang Harian based on master data
            $(document).on('change keyup', '.provinsi-select, .tipe-perjalanan-select, .lama-hari-input', function() {
                let tr = $(this).closest('tr');
                let provId = tr.find('.provinsi-select').val();
                let tipe = tr.find('.tipe-perjalanan-select').val();
                let lamaHari = parseInt(tr.find('.lama-hari-input').val()) || 1;
                
                if (provId) {
                    // find tariff
                    let tarifInfo = masterTarif.find(t => t.id == provId);
                    if (tarifInfo) {
                        let amount = 0;
                        if (tipe === 'Luar Kota') amount = tarifInfo.luar_kota;
                        else if (tipe === 'Dalam Kota Lebih Dari 8 Jam') amount = tarifInfo.dalam_kota_lebih_8_jam;
                        else if (tipe === 'Diklat') amount = tarifInfo.diklat;
                        
                        let totalAmount = amount * lamaHari;
                        let inputUangHarian = tr.find('.uang-harian-input');
                        inputUangHarian.val(formatNumber(totalAmount));
                        calculateJumlah(inputUangHarian[0]);
                    }
                }
            });
        });
    </script>
@endpush
