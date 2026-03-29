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

                <h5 class="mb-3 border-bottom pb-2">Informasi Rencana Acara</h5>
                <div class="row mb-4">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Uraian / Judul Perjalanan Dinas <span class="text-danger">*</span></label>
                        <input type="text" name="uraian" class="form-control" placeholder="Contoh: Rapat Koordinasi..."
                            required value="{{ old('uraian') }}">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">No BAST (Opsional)</label>
                        <input type="text" name="no_bast" class="form-control" placeholder="" value="{{ old('no_bast') }}">
                    </div>
                </div>

                <h5 class="mb-3 border-bottom pb-2">Daftar Pejabat / Pegawai Berangkat</h5>
                <div class="table-responsive">
                    <table class="table table-bordered align-middle" id="repeaterTable" style="min-width: 1500px;">
                        <thead class="table-light text-center">
                            <tr>
                                <th>#</th>
                                <th>Pegawai</th>
                                <th>No SPT / SPPD</th>
                                <th>Tujuan</th>
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
                                    <div class="mb-2">
                                        <select class="form-select employee-select" name="pejabats[0][employee_id]">
                                            <option value="">-- Pilih Pegawai dari Sistem --</option>
                                            @foreach($employees as $emp)
                                                <option value="{{ $emp->id }}" data-nip="{{ $emp->nip }}"
                                                    data-name="{{ $emp->name }}">{{ $emp->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <input type="text" class="form-control mb-1 employee-name"
                                        name="pejabats[0][nama_pejabat]" placeholder="Nama Pegawai" required>
                                    <input type="text" class="form-control employee-nip" name="pejabats[0][nip]"
                                        placeholder="NIP">
                                </td>
                                <td>
                                    <input type="text" class="form-control mb-1" name="pejabats[0][no_spt]"
                                        placeholder="No SPT" required>
                                    <input type="text" class="form-control" name="pejabats[0][no_sppd]"
                                        placeholder="No SPPD" required>
                                </td>
                                <td>
                                    <textarea class="form-control" name="pejabats[0][tujuan]" rows="2"
                                        placeholder="Tujuan..." required></textarea>
                                </td>
                                <td>
                                    <input type="date" class="form-control mb-1" name="pejabats[0][tanggal_berangkat]"
                                        required>
                                    <div class="input-group">
                                        <input type="number" class="form-control" name="pejabats[0][lama_perjalanan_dinas]"
                                            placeholder="Lama" required min="1">
                                        <span class="input-group-text">Hari</span>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex flex-wrap gap-1">
                                        <input type="text" class="form-control form-control-sm biaya-input"
                                            name="pejabats[0][tiket]" placeholder="Tiket" style="width: 120px;"
                                            onkeyup="calculateJumlah(this)">
                                        <input type="text" class="form-control form-control-sm biaya-input"
                                            name="pejabats[0][transport]" placeholder="Transport" style="width: 120px;"
                                            onkeyup="calculateJumlah(this)">
                                        <input type="text" class="form-control form-control-sm biaya-input"
                                            name="pejabats[0][penginapan]" placeholder="Penginapan" style="width: 120px;"
                                            onkeyup="calculateJumlah(this)">
                                        <input type="text" class="form-control form-control-sm biaya-input"
                                            name="pejabats[0][uang_harian]" placeholder="Uang Harian" style="width: 120px;"
                                            onkeyup="calculateJumlah(this)">
                                        <input type="text" class="form-control form-control-sm biaya-input"
                                            name="pejabats[0][uang_representasi]" placeholder="Representasi"
                                            style="width: 120px;" onkeyup="calculateJumlah(this)">
                                    </div>
                                </td>
                                <td>
                                    <input type="text" class="form-control text-end row-jumlah" readonly value="0">
                                </td>
                                <td>
                                    <input type="text" class="form-control" name="pejabats[0][rekening]"
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
                        Pejabat</button>
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

        // Formatting numbers to string with commas
        function formatNumber(n) {
            return n.toString().replace(/\D/g, "").replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        }

        // Auto calculate per row and grand total
        window.calculateJumlah = function (element) {
            // Format the input value while typing
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

            // Auto fill Name and NIP when Employee selected
            $(document).on('change', '.employee-select', function () {
                let tr = $(this).closest('tr');
                let selected = $(this).find('option:selected');
                if (selected.val() != '') {
                    tr.find('.employee-name').val(selected.data('name'));
                    tr.find('.employee-nip').val(selected.data('nip'));
                } else {
                    tr.find('.employee-name').val('');
                    tr.find('.employee-nip').val('');
                }
            });

            // Add Row
            $('#btnAddRow').click(function () {
                // Clone first row
                let newRow = $('#repeaterTable tbody tr:first').clone();

                // Clear values in clone
                newRow.find('input, textarea').val('');
                newRow.find('.row-jumlah').val('0');
                newRow.find('.employee-select').prop('selectedIndex', 0);

                // Re-index names
                newRow.find('input, select, textarea').each(function () {
                    var name = $(this).attr('name');
                    if (name) {
                        name = name.replace(/\[0\]/g, '[' + rowIdx + ']');
                        $(this).attr('name', name);
                    }
                });

                // Enable delete button on new row
                newRow.find('.btn-delete-row').prop('disabled', false);

                // Append to table
                $('#repeaterTable tbody').append(newRow);

                // Update row numbers
                updateRowNumbers();

                rowIdx++;
            });

            // Delete Row
            $(document).on('click', '.btn-delete-row', function () {
                if ($('#repeaterTable tbody tr').length > 1) {
                    $(this).closest('tr').remove();
                    updateRowNumbers();
                    calculateGrandTotal();
                }
            });

            function updateRowNumbers() {
                $('#repeaterTable tbody tr').each(function (index) {
                    $(this).find('.row-number').text(index + 1);
                });
            }
        });
    </script>
@endpush