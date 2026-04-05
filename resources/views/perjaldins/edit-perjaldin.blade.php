@extends('layouts.app')
@section('title')
    Edit Data Perjaldin
@endsection
@section('content')
    <x-page-title title="Manajemen Perjaldin" subtitle="Edit Data" />

    <div class="card">
        <div class="card-body">
            <h6 class="mb-4">Edit Pengajuan Perjaldin — <span class="text-primary">{{ $tagihan->nomor_tagihan }}</span></h6>
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

            @php
                // Parse deskripsi to extract uraian and no_bast
                $parts = explode(' | BAST: ', $tagihan->deskripsi);
                $uraian = $parts[0] ?? $tagihan->deskripsi;
                $noBast = $parts[1] ?? '';
            @endphp

            <form action="{{ route('perjaldins.update-perjaldin', $tagihan->id) }}" method="POST">
                @csrf
                @method('PUT')

                <h5 class="mb-3 border-bottom pb-2">Informasi Rencana Acara & Anggaran</h5>
                <div class="row mb-4">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Uraian / Judul Perjalanan Dinas <span class="text-danger">*</span></label>
                        <input type="text" name="deskripsi" class="form-control" placeholder="Contoh: Rapat Koordinasi..."
                            required value="{{ old('deskripsi', $uraian) }}">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">No BAST (Opsional)</label>
                        <input type="text" name="no_bast" class="form-control" value="{{ old('no_bast', $noBast) }}">
                    </div>
                    <div class="col-md-4 mb-3">
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
                </div>

                <h5 class="mb-3 border-bottom pb-2">Daftar Pegawai yang Berangkat</h5>
                <div class="table-responsive">
                    <table class="table table-bordered align-middle" id="repeaterTable" style="min-width: 1500px;">
                        <thead class="table-light text-center">
                            <tr>
                                <th>#</th>
                                <th>Pegawai</th>
                                <th>No SPT</th>
                                <th>Tujuan</th>
                                <th>Tanggal & Lama</th>
                                <th>Rincian Biaya (Tiket, Transport, Penginapan, UH, Representasi)</th>
                                <th>Jumlah</th>
                                <th>Rekening</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($tagihan->detailPerjaldin as $index => $detail)
                            <tr class="item-row">
                                <td class="text-center row-number">{{ $index + 1 }}</td>
                                <td>
                                    <input type="hidden" name="peserta[{{ $index }}][detail_id]" value="{{ $detail->id }}">
                                    <select class="form-select pegawai-select" name="peserta[{{ $index }}][pegawai_id]" required>
                                        <option value="">-- Pilih Pegawai --</option>
                                        @foreach($pegawais as $peg)
                                            <option value="{{ $peg->id }}" data-nip="{{ $peg->nip }}" {{ $detail->pegawai_id == $peg->id ? 'selected' : '' }}>
                                                {{ $peg->nama_lengkap }} {{ $peg->nip ? '('.$peg->nip.')' : '' }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <input type="text" class="form-control" name="peserta[{{ $index }}][no_spt]"
                                        placeholder="No SPT" required value="{{ old("peserta.$index.no_spt", $detail->no_spt) }}">
                                </td>
                                <td>
                                    <textarea class="form-control" name="peserta[{{ $index }}][tujuan]" rows="2"
                                        placeholder="Tujuan..." required>{{ old("peserta.$index.tujuan", $detail->tujuan) }}</textarea>
                                </td>
                                <td>
                                    <input type="date" class="form-control mb-1" name="peserta[{{ $index }}][tgl_berangkat]"
                                        required value="{{ old("peserta.$index.tgl_berangkat", $detail->tgl_berangkat) }}">
                                    <div class="input-group">
                                        <input type="number" class="form-control" name="peserta[{{ $index }}][lama_hari]"
                                            placeholder="Lama" required min="1" value="{{ old("peserta.$index.lama_hari", $detail->lama_hari) }}">
                                        <span class="input-group-text">Hari</span>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex flex-wrap gap-1">
                                        <input type="text" class="form-control form-control-sm biaya-input"
                                            name="peserta[{{ $index }}][biaya_tiket]" placeholder="Tiket" style="width: 120px;"
                                            onkeyup="calculateJumlah(this)" value="{{ number_format((float)$detail->biaya_tiket, 0, '.', ',') }}">
                                        <input type="text" class="form-control form-control-sm biaya-input"
                                            name="peserta[{{ $index }}][biaya_transport]" placeholder="Transport" style="width: 120px;"
                                            onkeyup="calculateJumlah(this)" value="{{ number_format((float)$detail->biaya_transport, 0, '.', ',') }}">
                                        <input type="text" class="form-control form-control-sm biaya-input"
                                            name="peserta[{{ $index }}][biaya_penginapan]" placeholder="Penginapan" style="width: 120px;"
                                            onkeyup="calculateJumlah(this)" value="{{ number_format((float)$detail->biaya_penginapan, 0, '.', ',') }}">
                                        <input type="text" class="form-control form-control-sm biaya-input"
                                            name="peserta[{{ $index }}][uang_harian]" placeholder="Uang Harian" style="width: 120px;"
                                            onkeyup="calculateJumlah(this)" value="{{ number_format((float)$detail->uang_harian, 0, '.', ',') }}">
                                        <input type="text" class="form-control form-control-sm biaya-input"
                                            name="peserta[{{ $index }}][uang_representasi]" placeholder="Representasi"
                                            style="width: 120px;" onkeyup="calculateJumlah(this)" value="{{ number_format((float)$detail->uang_representasi, 0, '.', ',') }}">
                                    </div>
                                </td>
                                <td>
                                    @php
                                        $subtotal = $detail->biaya_tiket + $detail->biaya_transport + $detail->biaya_penginapan + $detail->uang_harian + $detail->uang_representasi;
                                    @endphp
                                    <input type="text" class="form-control text-end row-jumlah" readonly value="{{ number_format((float)$subtotal, 0, '.', ',') }}">
                                </td>
                                <td>
                                    <input type="text" class="form-control" name="peserta[{{ $index }}][rekening]"
                                        placeholder="Ex: 0001 (BRI)" value="{{ old("peserta.$index.rekening", $detail->rekening) }}">
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-outline-danger btn-sm btn-delete-row" {{ $tagihan->detailPerjaldin->count() == 1 ? 'disabled' : '' }}><i
                                            class="bi bi-trash"></i></button>
                                </td>
                            </tr>
                            @endforeach
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
                    <button type="submit" class="btn btn-primary px-4"><i class="bi bi-save"></i> Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Template Row (Hidden) -->
    <table style="display: none;">
        <tbody id="rowTemplate">
            <tr class="item-row">
                <td class="text-center row-number"></td>
                <td>
                    <input type="hidden" name="peserta[__INDEX__][detail_id]" value="">
                    <select class="form-select pegawai-select" name="peserta[__INDEX__][pegawai_id]" required>
                        <option value="">-- Pilih Pegawai --</option>
                        @foreach($pegawais as $peg)
                            <option value="{{ $peg->id }}" data-nip="{{ $peg->nip }}">{{ $peg->nama_lengkap }} {{ $peg->nip ? '('.$peg->nip.')' : '' }}</option>
                        @endforeach
                    </select>
                </td>
                <td>
                    <input type="text" class="form-control" name="peserta[__INDEX__][no_spt]"
                        placeholder="No SPT" required>
                </td>
                <td>
                    <textarea class="form-control" name="peserta[__INDEX__][tujuan]" rows="2"
                        placeholder="Tujuan..." required></textarea>
                </td>
                <td>
                    <input type="date" class="form-control mb-1" name="peserta[__INDEX__][tgl_berangkat]"
                        required>
                    <div class="input-group">
                        <input type="number" class="form-control" name="peserta[__INDEX__][lama_hari]"
                            placeholder="Lama" required min="1">
                        <span class="input-group-text">Hari</span>
                    </div>
                </td>
                <td>
                    <div class="d-flex flex-wrap gap-1">
                        <input type="text" class="form-control form-control-sm biaya-input"
                            name="peserta[__INDEX__][biaya_tiket]" placeholder="Tiket" style="width: 120px;"
                            onkeyup="calculateJumlah(this)">
                        <input type="text" class="form-control form-control-sm biaya-input"
                            name="peserta[__INDEX__][biaya_transport]" placeholder="Transport" style="width: 120px;"
                            onkeyup="calculateJumlah(this)">
                        <input type="text" class="form-control form-control-sm biaya-input"
                            name="peserta[__INDEX__][biaya_penginapan]" placeholder="Penginapan" style="width: 120px;"
                            onkeyup="calculateJumlah(this)">
                        <input type="text" class="form-control form-control-sm biaya-input"
                            name="peserta[__INDEX__][uang_harian]" placeholder="Uang Harian" style="width: 120px;"
                            onkeyup="calculateJumlah(this)">
                        <input type="text" class="form-control form-control-sm biaya-input"
                            name="peserta[__INDEX__][uang_representasi]" placeholder="Representasi"
                            style="width: 120px;" onkeyup="calculateJumlah(this)">
                    </div>
                </td>
                <td>
                    <input type="text" class="form-control text-end row-jumlah" readonly value="0">
                </td>
                <td>
                    <input type="text" class="form-control" name="peserta[__INDEX__][rekening]"
                        placeholder="Ex: 0001 (BRI)">
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-outline-danger btn-sm btn-delete-row"><i
                            class="bi bi-trash"></i></button>
                </td>
            </tr>
        </tbody>
    </table>
@endsection

@push('script')
    <script>
        let rowIdx = {{ $tagihan->detailPerjaldin->count() }};

        function formatNumber(n) {
            return n.toString().replace(/\D/g, "").replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        }

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
            calculateGrandTotal();
            $('.select2').select2({
                theme: 'bootstrap-5',
                width: '100%',
                placeholder: '-- Pilih Item Anggaran DIPA Aktif --'
            });

            // Add Row
            $('#btnAddRow').click(function () {
                let templateContent = $('#rowTemplate').html();
                let newRowHtml = templateContent.replace(/__INDEX__/g, rowIdx);
                let newRow = $(newRowHtml);
                $('#repeaterTable tbody').append(newRow);
                updateRowNumbers();
                $('.btn-delete-row').prop('disabled', false);
                rowIdx++;
            });

            // Delete Row
            $(document).on('click', '.btn-delete-row', function () {
                if ($('#repeaterTable tbody tr.item-row').length > 1) {
                    $(this).closest('tr').remove();
                    updateRowNumbers();
                    calculateGrandTotal();
                    if ($('#repeaterTable tbody tr.item-row').length === 1) {
                         $('.btn-delete-row').prop('disabled', true);
                    }
                }
            });

            function updateRowNumbers() {
                $('#repeaterTable tbody tr.item-row').each(function (index) {
                    $(this).find('.row-number').text(index + 1);
                });
            }
        });
    </script>
@endpush
