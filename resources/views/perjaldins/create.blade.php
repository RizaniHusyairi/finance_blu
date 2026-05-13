@extends('layouts.app')
@section('title')
    Tambah Data Perjaldin
@endsection
@section('content')
    <x-page-title title="Manajemen Perjaldin" subtitle="Tambah Data" />

    <div class="card p-3">
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

            <form action="{{ route('perjaldins.store') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <!-- SECTION A: HEADER DOKUMEN -->
                <h5 class="mb-3 border-bottom pb-2 text-primary"><i class="bi bi-file-earmark-text"></i> Bagian A: Informasi Dokumen & Anggaran</h5>
                <div class="row mb-4">
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Uraian / Judul Perjalanan <span class="text-danger">*</span></label>
                        <input type="text" name="deskripsi" class="form-control" placeholder="Acara..." required value="{{ old('deskripsi') }}">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Nomor Perjalanan Dinas <span class="text-danger">*</span></label>
                        <input type="text" name="nomor_perjaldin" class="form-control" placeholder="Contoh: KU.201/1245/APTP/2026" required value="{{ old('nomor_perjaldin') }}">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Periode Bulan <span class="text-danger">*</span></label>
                        <select name="periode_bulan" class="form-select" required>
                            <option value="">-- Pilih Bulan --</option>
                            @for($i=1; $i<=12; $i++)
                                <option value="{{ $i }}" {{ old('periode_bulan', date('n')) == $i ? 'selected' : '' }}>{{ date('F', mktime(0, 0, 0, $i, 10)) }}</option>
                            @endfor
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Periode Tahun <span class="text-danger">*</span></label>
                        <input type="number" name="periode_tahun" class="form-control" required value="{{ old('periode_tahun', date('Y')) }}" min="2000" max="2100">
                    </div>

                    <div class="col-md-3 mb-3">
                        <label class="form-label">Kota TTD <span class="text-danger">*</span></label>
                        <input type="text" name="kota_ttd" class="form-control" placeholder="Samarinda" required value="{{ old('kota_ttd', 'Samarinda') }}">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Tanggal TTD <span class="text-danger">*</span></label>
                        <input type="date" name="tanggal_ttd" class="form-control" required value="{{ old('tanggal_ttd', date('Y-m-d')) }}">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Mekanisme Pembayaran <span class="text-danger">*</span></label>
                        <select name="mekanisme_pembayaran" class="form-select" required>
                            @foreach(\App\Enums\MekanismePembayaran::optionsFor('PERJALDIN') as $val => $lbl)
                                <option value="{{ $val }}" {{ old('mekanisme_pembayaran', \App\Enums\MekanismePembayaran::defaultFor('PERJALDIN')->value) === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                            @endforeach
                        </select>
                        <div class="form-text">LS - Pihak Ketiga: ditransfer langsung ke rekening masing-masing peserta. LS - Via Bendahara: diteruskan melalui Bendahara Pengeluaran.</div>
                    </div>
                </div>

                <!-- SECTION B: VERIFIKATOR DOKUMEN -->
                <h5 class="mb-3 border-bottom pb-2 text-primary mt-4"><i class="bi bi-pen"></i> Bagian B: Verifikator Dokumen</h5>
                @php
                    $kasubbagId = old('kasubbag_user_id', optional($kasubbagUser)->id);
                    $kasubbagNama = old('kasubbag_nama_snapshot', optional($kasubbagUser)->name);
                    $kasubbagNip = old('kasubbag_nip_snapshot', optional(optional($kasubbagUser)->pegawai)->nip);
                @endphp

                {{-- Baris 1: Pejabat Otorisasi --}}
                <div class="row g-3 mb-3">
                    <!-- PPK -->
                    <div class="col-md-6 col-lg-4">
                        <div class="card border shadow-sm h-100">
                            <div class="card-header bg-light py-2 px-3">
                                <h6 class="mb-0 fw-bold font-13"><i class="bi bi-person-badge me-1 text-primary"></i> Pejabat Pembuat Komitmen (PPK)</h6>
                            </div>
                            <div class="card-body p-3">
                                <div class="mb-2">
                                    <label class="form-label font-12 mb-1">Pilih User PPK</label>
                                    <select name="ppk_user_id" class="form-select form-select-sm select2" id="ppkUserId">
                                        <option value="">-- Pilih --</option>
                                        @foreach($ppkUsers as $user)
                                            <option value="{{ $user->id }}" data-nip="{{ optional($user->pegawai)->nip }}" data-nama="{{ $user->name }}" {{ old('ppk_user_id') == $user->id ? 'selected' : '' }}>
                                                {{ $user->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label font-12 mb-1">Nama <span class="text-danger">*</span></label>
                                    <input type="text" name="ppk_nama_snapshot" id="ppkNamaSnapshot" class="form-control form-control-sm" required value="{{ old('ppk_nama_snapshot') }}">
                                </div>
                                <div class="mb-0">
                                    <label class="form-label font-12 mb-1">NIP <span class="text-danger">*</span></label>
                                    <input type="text" name="ppk_nip_snapshot" id="ppkNipSnapshot" class="form-control form-control-sm" required value="{{ old('ppk_nip_snapshot') }}">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- PPSPM -->
                    <div class="col-md-6 col-lg-4">
                        <div class="card border shadow-sm h-100">
                            <div class="card-header bg-light py-2 px-3">
                                <h6 class="mb-0 fw-bold font-13"><i class="bi bi-person-check me-1 text-success"></i> PPSPM</h6>
                            </div>
                            <div class="card-body p-3">
                                <div class="mb-2">
                                    <label class="form-label font-12 mb-1">Pilih User PPSPM <span class="text-danger">*</span></label>
                                    <select name="ppspm_user_id" class="form-select form-select-sm select2" id="ppspmUserId" required>
                                        <option value="">-- Pilih --</option>
                                        @foreach($ppspmUsers as $user)
                                            <option value="{{ $user->id }}" data-nip="{{ optional($user->pegawai)->nip }}" data-nama="{{ $user->name }}" {{ old('ppspm_user_id') == $user->id ? 'selected' : '' }}>
                                                {{ $user->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label font-12 mb-1">Nama <span class="text-danger">*</span></label>
                                    <input type="text" name="ppspm_nama_snapshot" id="ppspmNamaSnapshot" class="form-control form-control-sm" required value="{{ old('ppspm_nama_snapshot') }}">
                                </div>
                                <div class="mb-0">
                                    <label class="form-label font-12 mb-1">NIP</label>
                                    <input type="text" name="ppspm_nip_snapshot" id="ppspmNipSnapshot" class="form-control form-control-sm" value="{{ old('ppspm_nip_snapshot') }}">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Koordinator Keuangan -->
                    <div class="col-md-6 col-lg-4">
                        <div class="card border shadow-sm h-100">
                            <div class="card-header bg-light py-2 px-3">
                                <h6 class="mb-0 fw-bold font-13"><i class="bi bi-clipboard-check me-1 text-info"></i> Koordinator Keuangan</h6>
                            </div>
                            <div class="card-body p-3">
                                <div class="mb-2">
                                    <label class="form-label font-12 mb-1">Pilih User <span class="text-danger">*</span></label>
                                    <select name="koordinator_keuangan_user_id" class="form-select form-select-sm select2" id="koorKeuanganUserId" required>
                                        <option value="">-- Pilih --</option>
                                        @foreach($koorKeuanganUsers as $user)
                                            <option value="{{ $user->id }}" data-nip="{{ optional($user->pegawai)->nip }}" data-nama="{{ $user->name }}" {{ old('koordinator_keuangan_user_id') == $user->id ? 'selected' : '' }}>
                                                {{ $user->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label font-12 mb-1">Nama <span class="text-danger">*</span></label>
                                    <input type="text" name="koordinator_keuangan_nama_snapshot" id="koorKeuanganNamaSnapshot" class="form-control form-control-sm" required value="{{ old('koordinator_keuangan_nama_snapshot') }}">
                                </div>
                                <div class="mb-0">
                                    <label class="form-label font-12 mb-1">NIP</label>
                                    <input type="text" name="koordinator_keuangan_nip_snapshot" id="koorKeuanganNipSnapshot" class="form-control form-control-sm" value="{{ old('koordinator_keuangan_nip_snapshot') }}">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Baris 2: Bendahara & Kasubbag --}}
                <div class="row g-3 mb-4">
                    <!-- Bendahara Penerimaan -->
                    <div class="col-md-6 col-lg-4">
                        <div class="card border shadow-sm h-100">
                            <div class="card-header bg-light py-2 px-3">
                                <h6 class="mb-0 fw-bold font-13"><i class="bi bi-wallet2 me-1 text-warning"></i> Bendahara Penerimaan</h6>
                            </div>
                            <div class="card-body p-3">
                                <div class="mb-2">
                                    <label class="form-label font-12 mb-1">Pilih User <span class="text-danger">*</span></label>
                                    <select name="bendahara_penerimaan_user_id" class="form-select form-select-sm select2" id="bendaharaPenerimaanUserId" required>
                                        <option value="">-- Pilih --</option>
                                        @foreach($bendaharaPenerimaanUsers as $user)
                                            <option value="{{ $user->id }}" data-nip="{{ optional($user->pegawai)->nip }}" data-nama="{{ $user->name }}" {{ old('bendahara_penerimaan_user_id') == $user->id ? 'selected' : '' }}>
                                                {{ $user->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label font-12 mb-1">Nama <span class="text-danger">*</span></label>
                                    <input type="text" name="bendahara_penerimaan_nama_snapshot" id="bendaharaPenerimaanNamaSnapshot" class="form-control form-control-sm" required value="{{ old('bendahara_penerimaan_nama_snapshot') }}">
                                </div>
                                <div class="mb-0">
                                    <label class="form-label font-12 mb-1">NIP</label>
                                    <input type="text" name="bendahara_penerimaan_nip_snapshot" id="bendaharaPenerimaanNipSnapshot" class="form-control form-control-sm" value="{{ old('bendahara_penerimaan_nip_snapshot') }}">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Bendahara Pengeluaran -->
                    <div class="col-md-6 col-lg-4">
                        <div class="card border shadow-sm h-100">
                            <div class="card-header bg-light py-2 px-3">
                                <h6 class="mb-0 fw-bold font-13"><i class="bi bi-cash-stack me-1 text-danger"></i> Bendahara Pengeluaran</h6>
                            </div>
                            <div class="card-body p-3">
                                <div class="mb-2">
                                    <label class="form-label font-12 mb-1">Pilih User <span class="text-danger">*</span></label>
                                    <select name="bendahara_pengeluaran_user_id" class="form-select form-select-sm select2" id="bendaharaUserId" required>
                                        <option value="">-- Pilih --</option>
                                        @foreach($bendaharaUsers as $user)
                                            <option value="{{ $user->id }}" data-nip="{{ optional($user->pegawai)->nip }}" data-nama="{{ $user->name }}" {{ old('bendahara_pengeluaran_user_id') == $user->id ? 'selected' : '' }}>
                                                {{ $user->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label font-12 mb-1">Nama <span class="text-danger">*</span></label>
                                    <input type="text" name="bendahara_pengeluaran_nama_snapshot" id="bendaharaNamaSnapshot" class="form-control form-control-sm" required value="{{ old('bendahara_pengeluaran_nama_snapshot') }}">
                                </div>
                                <div class="mb-0">
                                    <label class="form-label font-12 mb-1">NIP <span class="text-danger">*</span></label>
                                    <input type="text" name="bendahara_pengeluaran_nip_snapshot" id="bendaharaNipSnapshot" class="form-control form-control-sm" required value="{{ old('bendahara_pengeluaran_nip_snapshot') }}">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Kasubbag (Otomatis) -->
                    <div class="col-md-6 col-lg-4">
                        <div class="card border shadow-sm h-100 bg-light bg-opacity-50">
                            <div class="card-header bg-light py-2 px-3">
                                <h6 class="mb-0 fw-bold font-13"><i class="bi bi-person-gear me-1 text-secondary"></i> Kasubbag <span class="badge bg-secondary font-10 ms-1">Otomatis</span></h6>
                            </div>
                            <div class="card-body p-3">
                                <input type="hidden" name="kasubbag_user_id" value="{{ $kasubbagId }}">
                                <div class="mb-2">
                                    <label class="form-label font-12 mb-1">User Kasubbag</label>
                                    <input type="text" class="form-control form-control-sm bg-white" readonly value="{{ $kasubbagNama ?: 'Belum ada user Kasubbag' }}">
                                    <input type="hidden" name="kasubbag_nama_snapshot" value="{{ $kasubbagNama }}">
                                </div>
                                <div class="mb-0">
                                    <label class="form-label font-12 mb-1">NIP Kasubbag</label>
                                    <input type="text" class="form-control form-control-sm bg-white" readonly value="{{ $kasubbagNip ?: '-' }}">
                                    <input type="hidden" name="kasubbag_nip_snapshot" value="{{ $kasubbagNip }}">
                                    <small class="text-muted font-10 d-block mt-1">Ditentukan otomatis dari role Kasubbag.</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                                <!-- SECTION C: DAFTAR NOMINATIF PEGAWAI -->
                <div class="d-flex justify-content-between align-items-end mb-3 mt-4 border-bottom pb-2">
                    <h5 class="text-primary mb-0"><i class="bi bi-people"></i> Bagian C: Daftar Nominatif Pegawai</h5>
                    <button type="button" class="btn btn-dark btn-sm btn-add-row-trigger"><i class="bi bi-plus-circle"></i> Tambah Pegawai</button>
                </div>

                <!-- Bagian Summary (Global Tab) -->
                <div class="alert alert-info d-flex align-items-center mb-3 shadow-sm border-0">
                    <div class="me-auto">
                        <strong>Ringkasan:</strong> <span id="summaryCount">1</span> Peserta Terdaftar
                    </div>
                    <div class="fs-5 fw-bold text-dark">
                        Grand Total: Rp <span id="summaryGrandTotal" class="text-primary">0</span>
                    </div>
                    <input type="hidden" id="grandTotal" name="total_bruto" value="0">
                </div>

                <!-- Wrapper Card List -->
                <div id="pesertaRepeater">
                    @php
                        $oldPeserta = old('peserta', [0 => []]);
                        $isCreate = true;
                    @endphp

                    @foreach($oldPeserta as $index => $row)
                        @include('perjaldins.partials.peserta-card', ['index' => $index, 'row' => $row, 'masterProvinsi' => $masterProvinsi, 'masterPegawai' => $masterPegawai, 'isCreate' => true])
                    @endforeach
                </div>

                <div class="mt-3">
                    <button type="button" class="btn btn-dark btn-sm btn-add-row-trigger"><i class="bi bi-plus-circle"></i> Tambah Baris Peserta</button>
                </div>

                <div class="mt-5 border-top pt-3 text-end">
                    <a href="{{ route('perjaldins.index') }}" class="btn btn-light"><i class="bi bi-x-circle"></i> Batal</a>
                    <button type="submit" class="btn btn-primary px-4"><i class="bi bi-save"></i> Simpan Dokumen Rekap Perjalanan Dinas</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('script')
    <script>
        let rowIdx = {{ count($oldPeserta) }};

        // Formatting numbers to string with commas
        function formatNumber(n) {
            return n.toString().replace(/\D/g, "").replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        }

        // Auto calculate per row and grand total
        window.calculateJumlah = function (element) {
            let val = $(element).val();
            if(val !== '') {
                $(element).val(formatNumber(val));
            }

            let card = $(element).closest('.item-row');
            let total = 0;
            card.find('.biaya-input').each(function () {
                let num = parseFloat($(this).val().replace(/,/g, ''));
                if (!isNaN(num)) total += num;
            });
            card.find('.row-jumlah').val(formatNumber(total));
            card.find('.summary-total').text(formatNumber(total)); // update header card summary
            calculateGrandTotal();
        }

        function calculateGrandTotal() {
            let grandTotal = 0;
            $('.row-jumlah').each(function () {
                let num = parseFloat($(this).val().replace(/,/g, ''));
                if (!isNaN(num)) grandTotal += num;
            });
            $('#grandTotal').val(formatNumber(grandTotal));
            $('#summaryGrandTotal').text(formatNumber(grandTotal));
            $('#summaryCount').text($('.item-row').length);
        }

        // Auto calculate Uang Harian
        function calculateUangHarian(card) {
            let provSelect = card.find('.provinsi-select option:selected');
            let tipe = card.find('.tipe-select').val();
            let lamaHari = parseInt(card.find('.lama-hari-input').val()) || 0;

            if (provSelect.val() !== '' && typeof provSelect.val() !== 'undefined' && tipe !== '') {
                let rate = 0;
                if (tipe === 'luar_kota') rate = parseFloat(provSelect.data('luar')) || 0;
                else if (tipe === 'dalam_kota_lebih_8_jam') rate = parseFloat(provSelect.data('dalam')) || 0;
                else if (tipe === 'diklat') rate = parseFloat(provSelect.data('diklat')) || 0;

                let totalUangHarian = rate * lamaHari;
                card.find('.uang-harian-input').val(formatNumber(totalUangHarian));
                calculateJumlah(card.find('.uang-harian-input')[0]);
            }
        }

        $(document).ready(function () {
            $('.select2').select2({
                theme: 'bootstrap-5',
                width: '100%'
            });

            // Initialize formatting for old inputs
            $('.biaya-input').each(function() {
               calculateJumlah(this); 
            });

            // Auto-fill PPK
            $('#ppkUserId').change(function() {
                let selected = $(this).find(':selected');
                if(selected.val() !== '') {
                    $('#ppkNamaSnapshot').val(selected.data('nama'));
                    $('#ppkNipSnapshot').val(selected.data('nip'));
                }
            });

            // Auto-fill PPSPM
            $('#ppspmUserId').change(function() {
                let selected = $(this).find(':selected');
                if(selected.val() !== '') {
                    $('#ppspmNamaSnapshot').val(selected.data('nama'));
                    $('#ppspmNipSnapshot').val(selected.data('nip'));
                }
            });

            // Auto-fill Bendahara Penerimaan
            $('#bendaharaPenerimaanUserId').change(function() {
                let selected = $(this).find(':selected');
                if(selected.val() !== '') {
                    $('#bendaharaPenerimaanNamaSnapshot').val(selected.data('nama'));
                    $('#bendaharaPenerimaanNipSnapshot').val(selected.data('nip'));
                }
            });

            // Auto-fill Bendahara
            $('#bendaharaUserId').change(function() {
                let selected = $(this).find(':selected');
                if(selected.val() !== '') {
                    $('#bendaharaNamaSnapshot').val(selected.data('nama'));
                    $('#bendaharaNipSnapshot').val(selected.data('nip'));
                }
            });

            // Auto-fill Koordinator Keuangan
            $('#koorKeuanganUserId').change(function() {
                let selected = $(this).find(':selected');
                if(selected.val() !== '') {
                    $('#koorKeuanganNamaSnapshot').val(selected.data('nama'));
                    $('#koorKeuanganNipSnapshot').val(selected.data('nip'));
                }
            });

            // Add Row Check
            $(document).on('click', '.btn-add-row-trigger', function (e) {
                e.preventDefault();
                
                // Destroy Select2 on the first row before cloning
                let firstRow = $('.item-row:first');
                if (firstRow.find('.select2').hasClass('select2-hidden-accessible')) {
                    firstRow.find('.select2').select2('destroy');
                }

                let newRow = firstRow.clone();

                // Reinitialize Select2 on the first row
                firstRow.find('.select2').select2({
                    theme: 'bootstrap-5',
                    width: '100%'
                });

                // Clear values and validation classes
                newRow.find('input[type="text"], input[type="number"], input[type="date"], input[type="hidden"], input[type="file"], textarea').val('').removeClass('is-invalid');
                newRow.find('select').prop('selectedIndex', 0).removeClass('is-invalid');
                newRow.find('.row-jumlah').val('0');
                newRow.find('.summary-nama, .summary-tujuan').text('-');
                newRow.find('.summary-total').text('0');
                newRow.find('.file-existing-notice').remove();
                newRow.find('.file-status-badge').removeClass('bg-success').addClass('bg-secondary').html('<i class="bi bi-paperclip"></i> SPT Kosong');
                newRow.find('.nip-input').val('').prop('readonly', true);
                newRow.find('.rekening-input').val('').prop('readonly', false);
                newRow.find('.rek-hint').addClass('d-none');
                
                // Remove any leftover select2 elements if they were cloned
                newRow.find('.select2-container').remove();
                newRow.find('.select2-hidden-accessible').removeClass('select2-hidden-accessible').removeAttr('data-select2-id aria-hidden tabindex');

                // Adjust Collapse id
                let collapseId = 'collapsePeserta' + rowIdx;
                newRow.find('.collapse-trigger').attr('data-bs-target', '#' + collapseId);
                newRow.find('.peserta-collapse').attr('id', collapseId).addClass('show');

                newRow.find('input, select, textarea').each(function () {
                    var name = $(this).attr('name');
                    if (name) {
                        name = name.replace(/\[\d+\]/g, '[' + rowIdx + ']');
                        $(this).attr('name', name);
                    }
                    $(this).removeAttr('id'); // Remove id to avoid duplicates
                });

                newRow.find('.btn-delete-row').prop('disabled', false);
                $('#pesertaRepeater').append(newRow);
                
                // Initialize Select2 on the new row
                newRow.find('.select2').select2({
                    theme: 'bootstrap-5',
                    width: '100%'
                });

                updateRowNumbers();
                $('.btn-delete-row').prop('disabled', false);
                rowIdx++;
            });

            // Delete Row
            $(document).on('click', '.btn-delete-row', function (e) {
                e.preventDefault();
                e.stopPropagation();
                if ($('.item-row').length > 1) {
                    $(this).closest('.item-row').remove();
                    updateRowNumbers();
                    calculateGrandTotal();
                    if ($('.item-row').length === 1) {
                        $('.btn-delete-row').prop('disabled', true);
                    }
                }
            });

            // Listeners for Uang Harian auto-calc
            $(document).on('change', '.provinsi-select, .tipe-select', function() {
                calculateUangHarian($(this).closest('.item-row'));
            });
            
            $(document).on('input', '.lama-hari-input', function() {
                calculateUangHarian($(this).closest('.item-row'));
            });

            // Auto-fill pegawai (NIP, Rekening, Summary)
            $(document).on('change', '.pegawai-select', function() {
                let card = $(this).closest('.item-row');
                let selected = $(this).find(':selected');
                let nama = selected.data('nama') || '';
                let nip = selected.data('nip') || '';
                let rek = selected.data('rek') || '';

                // Fill hidden nama & NIP
                card.find('.input-nama-hidden').val(nama);
                card.find('.nip-input').val(nip);

                // Rekening: auto-fill if available, otherwise leave editable with hint
                if (rek) {
                    card.find('.rekening-input').val(rek).prop('readonly', false);
                    card.find('.rek-hint').addClass('d-none');
                } else {
                    card.find('.rekening-input').val('').prop('readonly', false);
                    if (selected.val() !== '') {
                        card.find('.rek-hint').removeClass('d-none');
                    } else {
                        card.find('.rek-hint').addClass('d-none');
                    }
                }

                // Update summary
                card.find('.summary-nama').text(nama || '-');
            });

            // Update Summary Tujuan Live
            $(document).on('input', '.input-tujuan', function() {
                $(this).closest('.item-row').find('.summary-tujuan').text($(this).val() || '-');
            });

            // File select listener to update badge
            $(document).on('change', '.spt-file-input', function() {
                let card = $(this).closest('.item-row');
                let badge = card.find('.file-status-badge');
                if (this.files && this.files.length > 0) {
                    badge.removeClass('bg-secondary text-secondary border-secondary').addClass('bg-success text-white').html('<i class="bi bi-paperclip"></i> SPT: ' + this.files[0].name.substring(0, 15) + '...');
                } else {
                    badge.removeClass('bg-success text-white').addClass('bg-secondary text-white').html('<i class="bi bi-paperclip"></i> SPT Kosong');
                }
            });

            // Collapse icon toggle
            $(document).on('click', '.collapse-trigger', function() {
                let icon = $(this).find('.toggle-icon');
                if ($(this).attr('aria-expanded') === 'true') {
                    // It will collapse
                    icon.removeClass('bi-chevron-down').addClass('bi-chevron-right');
                } else {
                    // It will expand
                    icon.removeClass('bi-chevron-right').addClass('bi-chevron-down');
                }
            });

            function updateRowNumbers() {
                $('.item-row').each(function (index) {
                    $(this).find('.row-number').text(index + 1);
                });
                calculateGrandTotal();
            }

            // Custom UI error toggling validation helper
            @if($errors->any())
                // Ensure all items with validation errors are visible (expanded)
                $('.is-invalid').closest('.peserta-collapse').addClass('show');
                $('.is-invalid').closest('.item-row').addClass('border border-danger');
            @endif
            
            updateRowNumbers();
        });
    </script>
@endpush
