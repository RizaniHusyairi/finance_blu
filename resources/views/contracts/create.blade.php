@extends('layouts.app')
@section('title')
    Tambah Kontrak
@endsection
@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0 fw-bold">Tambah Kontrak</h5>
        <a href="{{ route('contracts.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Kembali ke Daftar</a>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('contracts.store') }}" method="POST" id="formKontrak" enctype="multipart/form-data">
        @csrf

        {{-- Top Right Summary Badges --}}
        <div class="d-flex justify-content-end gap-2 mb-3 sticky-top" style="top: 80px; z-index: 10;">
            <span class="badge bg-primary fs-6 shadow-sm">Nilai Kontrak: <span id="summary-nilai-kontrak">Rp 0</span></span>
            <span class="badge bg-info fs-6 shadow-sm">Total Termin: <span id="summary-total-termin">Rp 0</span></span>
            <span class="badge bg-warning text-dark fs-6 shadow-sm d-none" id="summary-badge-um">Total UM: <span id="summary-total-um">Rp 0</span></span>
            <span class="badge bg-danger fs-6 shadow-sm" id="summary-selisih">Selisih: Rp 0</span>
        </div>

        <div class="row">
            {{-- 1. Informasi Utama Kontrak --}}
            <div class="col-12 mb-4">
                <div class="card rounded-4 border-top border-4 border-primary h-100 shadow-sm">
                    <div class="card-body p-4">
                        <h6 class="mb-4 fw-bold text-primary"><i class="bi bi-info-circle me-2"></i>Informasi Utama Kontrak</h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">ID Transaksi</label>
                                <input type="text" class="form-control" name="id_transaksi" value="{{ $idTransaksi }}" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nomor Kontrak <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="contract_number" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tanggal Kontrak <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="date" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nomor SPK / SP</label>
                                <input type="text" class="form-control" name="nomor_spk_sp">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Uraian / Pekerjaan <span class="text-danger">*</span></label>
                                <textarea class="form-control" rows="3" name="description" required></textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Ketentuan Sanksi</label>
                                <textarea class="form-control" rows="3" name="ketentuan_sanksi" placeholder="Contoh: Sanksi denda 1/1000 per hari keterlambatan..."></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Penyedia / Mitra <span class="text-danger">*</span></label>
                                <select class="form-select" name="supplier_id" id="supplier_id" required onchange="fillSupplierInfo()">
                                    <option value="">-- Pilih --</option>
                                    @foreach($suppliers as $supplier)
                                        <option value="{{ $supplier->id }}" data-npwp="{{ $supplier->npwp ?? '00.000.000.0-000.000' }}" data-rek="{{ $supplier->account_number ?? '1234567890 (Bank Dummy)' }}">{{ $supplier->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">NPWP Supplier</label>
                                <input type="text" class="form-control" id="npwp_supplier" readonly>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Rekening Supplier</label>
                                <input type="text" class="form-control" id="rekening_supplier" readonly>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Mata Uang <span class="text-danger">*</span></label>
                                <select class="form-select" name="mata_uang" required>
                                    <option value="IDR" selected>IDR - Rupiah</option>
                                    <option value="USD">USD - US Dollar</option>
                                    <option value="EUR">EUR - Euro</option>
                                </select>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label">Nilai Kontrak <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="text" class="form-control form-control-lg fw-bold text-end" id="total_amount_display" value="0" required onkeyup="formatRupiah(this); updateSummary()">
                                    <input type="hidden" name="total_amount" id="total_amount" value="0">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Cara Bayar <span class="text-danger">*</span></label>
                                <select class="form-select" name="cara_bayar" id="cara_bayar" required onchange="toggleTermin()">
                                    <option value="">-- Pilih --</option>
                                    <option value="Sekali Bayar">Sekali Bayar</option>
                                    <option value="Termin">Termin</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="single-select-field" class="form-label">Sistem Bayar (Beban Anggaran/COA) <span class="text-danger">*</span></label>
                                <select class="form-select" name="budget_id" id="single-select-field" required>
                                    <option value="">-- Pilih Pagu --</option>
                                    @foreach($budgets as $budget)
                                        <option value="{{ $budget->id }}">{{ $budget->coa }} (Sisa: Rp {{ number_format($budget->remaining_budget,0,',','.') }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Tahun Anggaran <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" name="tahun_anggaran" value="{{ date('Y') }}" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Pejabat Pembuat Komitmen (PPK) <span class="text-danger">*</span></label>
                                <select class="form-select" name="ppk_id" required>
                                    <option value="">-- Pilih --</option>
                                    @foreach($ppk_users as $user)
                                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Pejabat Pengadaan</label>
                                <select class="form-select" name="pejabat_pengadaan_id">
                                    <option value="">-- Pilih (Opsional) --</option>
                                    @foreach($pengadaan_users as $user)
                                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>


            {{-- 3. Waktu Pelaksanaan Pekerjaan --}}
            <div class="col-12 mb-4">
                <div class="card rounded-4 border-top border-4 border-success h-100 shadow-sm">
                    <div class="card-body p-4">
                        <h6 class="mb-4 fw-bold text-success"><i class="bi bi-calendar-check me-2"></i>Waktu Pelaksanaan Pekerjaan</h6>
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Jangka Waktu <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" name="jangka_waktu_pekerjaan" id="jangka_waktu_pekerjaan" value="0" required onchange="calculateTanggalSelesai()">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Satuan Waktu <span class="text-danger">*</span></label>
                                <select class="form-select" name="satuan_waktu_pekerjaan" id="satuan_waktu_pekerjaan" required onchange="calculateTanggalSelesai()">
                                    <option value="Hari">Hari</option>
                                    <option value="Minggu">Minggu</option>
                                    <option value="Bulan">Bulan</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Tanggal Mulai <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="start_date" id="start_date" required onchange="calculateTanggalSelesai()">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Tanggal Selesai <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="end_date" id="end_date" required>
                                <small >Bisa disesuaikan manual</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 4. Waktu Pemeliharaan --}}
            <div class="col-12 mb-4">
                <div class="card rounded-4 border-top border-4 border-secondary h-100 shadow-sm">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="mb-0 fw-bold text-secondary"><i class="bi bi-tools me-2"></i>Waktu Pemeliharaan</h6>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="ada_masa_pemeliharaan" name="ada_masa_pemeliharaan" value="1" onchange="togglePemeliharaan()">
                                <label class="form-check-label" for="ada_masa_pemeliharaan">Ada Pemeliharaan</label>
                            </div>
                        </div>
                        <div id="pemeliharaan_section" style="display: none;">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">Jangka Wkt Pemeliharaan (Hari)</label>
                                    <input type="number" class="form-control" name="jangka_waktu_pemeliharaan">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Tanggal Mulai Pemeliharaan</label>
                                    <input type="date" class="form-control" name="tanggal_mulai_pemeliharaan">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Tanggal Selesai Pemeliharaan</label>
                                    <input type="date" class="form-control" name="tanggal_selesai_pemeliharaan">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 5. Pembayaran & Termin --}}
            <div class="col-12 mb-4">
                <div class="card rounded-4 border-top border-4 border-warning h-100 shadow-sm">
                    <div class="card-body p-4">
                        <h6 class="mb-4 fw-bold text-warning"><i class="bi bi-cash-stack me-2"></i>Pembayaran & Termin</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label class="form-label">Jumlah Termin</label>
                                <input type="number" class="form-control" name="jumlah_termin" id="jumlah_termin" value="1" min="1" onchange="generateTerminRows()">
                                <small id="termin_help_text">Pilih metode Termin diatas untuk mengubah ini.</small>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered align-middle">
                                <thead>
                                    <tr>
                                        <th style="width: 80px;">Termin</th>
                                        <th>Keterangan</th>
                                        <th>Persentase (%)</th>
                                        <th>Nilai (Rp)</th>
                                        <!-- <th style="width: 50px;">Aksi</th> -->
                                    </tr>
                                </thead>
                                <tbody id="termin_table_body">
                                    <!-- Rows generated by JS -->
                                </tbody>
                            </table>
                        </div>
                        
                    </div>
                </div>
            </div>

            {{-- 6. Uang Muka --}}
            <div class="col-12 mb-4">
                <div class="card rounded-4 border-top border-4 border-success h-100 shadow-sm">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="mb-0 fw-bold text-success"><i class="bi bi-wallet2 me-2"></i>Uang Muka</h6>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="ada_uang_muka" name="ada_uang_muka" value="1" onchange="toggleUangMuka()">
                                <label class="form-check-label" for="ada_uang_muka">Ada Uang Muka</label>
                            </div>
                        </div>
                        
                        <div id="uang_muka_section" style="display: none;">
                            <div class="row g-3 mb-4">
                                <div class="col-md-4">
                                    <label class="form-label">Nilai Uang Muka (Rp) <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" class="form-control" name="nilai_uang_muka" id="nilai_uang_muka" value="0" onkeyup="calcUmPercent()">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Persentase UM (%)</label>
                                    <input type="number" step="0.01" class="form-control" name="persentase_uang_muka" id="persentase_uang_muka" readonly>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Jumlah Angsuran UM</label>
                                    <input type="number" class="form-control" name="jumlah_angsuran_um" id="jumlah_angsuran_um" value="1" min="1" onchange="generateUmRows()">
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-bordered align-middle">
                                    <thead>
                                        <tr>
                                            <th style="width: 100px;">Angsuran Ke</th>
                                            <th>Nilai Angsuran (Rp)</th>
                                            <th>Keterangan</th>
                                            <th style="width: 50px;">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody id="um_table_body">
                                        <!-- Rows generated by JS -->
                                    </tbody>
                                </table>
                            </div>
                            <small class="text-danger d-none" id="um_warning">Total angsuran UM tidak sama dengan Nilai Uang Muka!</small>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 7. Jaminan Uang Muka --}}
            <div class="col-12 mb-4" id="jaminan_um_card" style="display: none;">
                <div class="card rounded-4 border-top border-4 border-info h-100 shadow-sm">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="mb-0 fw-bold text-info"><i class="bi bi-shield-check me-2"></i>Jaminan Uang Muka</h6>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="ada_jaminan_um" onchange="toggleJaminan()">
                                <label class="form-check-label" for="ada_jaminan_um">Input Data Jaminan</label>
                            </div>
                        </div>
                        <div id="jaminan_section" style="display: none;">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Penjamin (Asuransi/Bank)</label>
                                    <input type="text" class="form-control" name="penjamin_um">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Nomor Jaminan</label>
                                    <input type="text" class="form-control" name="nomor_jaminan_um">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Tanggal Jaminan</label>
                                    <input type="date" class="form-control" name="tanggal_jaminan_um">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Masa Berlaku (Hari)</label>
                                    <input type="number" class="form-control" name="masa_berlaku_jaminan">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Tanggal Mulai Jaminan</label>
                                    <input type="date" class="form-control" name="tanggal_mulai_jaminan">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Tanggal Selesai Jaminan</label>
                                    <input type="date" class="form-control" name="tanggal_selesai_jaminan">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 8. Dokumen Pendukung --}}
            <div class="col-12 mb-4">
                <div class="card rounded-4 border-top border-4 border-dark h-100 shadow-sm">
                    <div class="card-body p-4">
                        <h6 class="mb-4 fw-bold"><i class="bi bi-file-earmark-arrow-up me-2"></i>Dokumen Pendukung Kontrak</h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">BAPP (Berita Acara Pemeriksaan Pekerjaan)</label>
                                <input type="file" class="form-control" name="doc_bapp" accept=".pdf,.jpg,.jpeg,.png">
                                <small>Format: PDF, JPG, PNG (maks 5MB)</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">BAP (Berita Acara Pembayaran)</label>
                                <input type="file" class="form-control" name="doc_bap" accept=".pdf,.jpg,.jpeg,.png">
                                <small>Format: PDF, JPG, PNG (maks 5MB)</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">BAST (Berita Acara Serah Terima)</label>
                                <input type="file" class="form-control" name="doc_bast" accept=".pdf,.jpg,.jpeg,.png">
                                <small>Format: PDF, JPG, PNG (maks 5MB)</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Ringkasan Kontrak</label>
                                <input type="file" class="form-control" name="doc_ringkasan_kontrak" accept=".pdf,.jpg,.jpeg,.png">
                                <small>Format: PDF, JPG, PNG (maks 5MB)</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">SPMK (Surat Perintah Mulai Kerja)</label>
                                <input type="file" class="form-control" name="doc_spmk" accept=".pdf,.jpg,.jpeg,.png">
                                <small>Format: PDF, JPG, PNG (maks 5MB)</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Lampiran Pendukung Lainnya</label>
                                <input type="file" class="form-control" name="doc_lainnya[]" accept=".pdf,.jpg,.jpeg,.png" multiple>
                                <small>Bisa upload banyak file sekaligus (maks 5MB per file)</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Submit Form Action --}}
            <div class="col-12 mb-5">
                <div class="card rounded-4 shadow-sm">
                    <div class="card-body p-4 d-flex justify-content-between align-items-center">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="Draft" id="simpan_draft" name="simpan_draft">
                            <label class="form-check-label" for="simpan_draft">
                                Simpan sebagai DRAFT
                            </label>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="reset" class="btn btn-outline-secondary px-4" onclick="return confirm('Apakah Anda yakin ingin mereset seluruh form yang telah diisi?')">Reset Form</button>
                            <button type="submit" class="btn btn-primary px-5 fw-bold" id="btn_submit"><i class="bi bi-send me-1"></i>Simpan & Ajukan ke PPK</button>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </form>
@endsection

@push('script')
<script>
    // Initialize things on load
    document.addEventListener("DOMContentLoaded", function () {
        document.getElementById('jumlah_termin').readOnly = true;
        generateTerminRows();
    });

    // Format currency to Rp string
    function formatCurrency(amount) {
        return "Rp " + parseFloat(amount).toLocaleString('id-ID');
    }

    // Auto format input with dots
    function formatRupiah(element) {
        let val = element.value.replace(/[^,\d]/g, '').toString();
        let split = val.split(',');
        let sisa = split[0].length % 3;
        let rupiah = split[0].substr(0, sisa);
        let ribuan = split[0].substr(sisa).match(/\d{3}/gi);

        if (ribuan) {
            let separator = sisa ? '.' : '';
            rupiah += separator + ribuan.join('.');
        }

        rupiah = split[1] != undefined ? rupiah + ',' + split[1] : rupiah;
        element.value = rupiah;
        
        let numericValue = val.replace(',', '.'); // Convert to JS float format
        document.getElementById('total_amount').value = numericValue ? numericValue : 0;    }

    // Update Summary Header badgers
    function updateSummary() {
        let nilaiKontrak = parseFloat(document.getElementById('total_amount').value) || 0;
        document.getElementById('summary-nilai-kontrak').innerText = formatCurrency(nilaiKontrak);
        
        calcTerminTotal();
        calcUmPercent();
    }

    // Toggle logic for sections
    function togglePemeliharaan() {
        let isChecked = document.getElementById('ada_masa_pemeliharaan').checked;
        document.getElementById('pemeliharaan_section').style.display = isChecked ? 'block' : 'none';
    }

    function toggleJaminan() {
        let isChecked = document.getElementById('ada_jaminan_um').checked;
        document.getElementById('jaminan_section').style.display = isChecked ? 'block' : 'none';
    }

    function toggleUangMuka() {
        let isChecked = document.getElementById('ada_uang_muka').checked;
        document.getElementById('uang_muka_section').style.display = isChecked ? 'block' : 'none';
        document.getElementById('jaminan_um_card').style.display = isChecked ? 'block' : 'none';
        
        let badgeUm = document.getElementById('summary-badge-um');
        if(isChecked) {
            badgeUm.classList.remove('d-none');
            generateUmRows();
        } else {
            badgeUm.classList.add('d-none');
        }
    }

    function toggleTermin() {
        let caraBayar = document.getElementById('cara_bayar').value;
        let fieldJumlahTermin = document.getElementById('jumlah_termin');
        
        if (caraBayar === 'Sekali Bayar') {
            fieldJumlahTermin.value = 1;
            fieldJumlahTermin.readOnly = true;
            generateTerminRows();
        } else if (caraBayar === 'Termin') {
            fieldJumlahTermin.readOnly = false;
        } else {
            fieldJumlahTermin.readOnly = true;
        }
    }

    // Fake API call to Supplier
    function fillSupplierInfo() {
        let s = document.getElementById('supplier_id');
        if(s.selectedIndex > 0) {
            let n = s.options[s.selectedIndex].getAttribute('data-npwp');
            let r = s.options[s.selectedIndex].getAttribute('data-rek');
            document.getElementById('npwp_supplier').value = n;
            document.getElementById('rekening_supplier').value = r;
        } else {
            document.getElementById('npwp_supplier').value = '';
            document.getElementById('rekening_supplier').value = '';
        }
    }

    // Compute Date
    function calculateTanggalSelesai() {
        let startDate = document.getElementById('start_date').value;
        let durasi = parseInt(document.getElementById('jangka_waktu_pekerjaan').value) || 0;
        let satuan = document.getElementById('satuan_waktu_pekerjaan').value;

        if (startDate && durasi > 0) {
            let d = new Date(startDate);
            let multiplier = 1;

            if (satuan === 'Minggu') multiplier = 7;
            else if (satuan === 'Bulan') multiplier = 30; // Approx

            d.setDate(d.getDate() + (durasi * multiplier));

            let y = d.getFullYear();
            let m = ("0" + (d.getMonth() + 1)).slice(-2);
            let day = ("0" + d.getDate()).slice(-2);
            document.getElementById('end_date').value = y + "-" + m + "-" + day;
        }
    }

    // Dynamic Termin Rows Generation
    let terminCounter = 0;
    function generateTerminRows() {
        let qty = parseInt(document.getElementById('jumlah_termin').value) || 1;
        let tbody = document.getElementById('termin_table_body');
        tbody.innerHTML = '';
        terminCounter = 0;

        let nilaiTotal = parseFloat(document.getElementById('total_amount').value) || 0;
        let perNilai = nilaiTotal > 0 ? (nilaiTotal / qty).toFixed(2) : 0;
        let perPersen = (100 / qty).toFixed(2);

        for (let i = 1; i <= qty; i++) {
            terminCounter++;
            appendTerminTr(terminCounter, perPersen, perNilai);
        }
        calcTerminTotal();
    }

    function addTerminRow() {
        terminCounter++;
        appendTerminTr(terminCounter, 0, 0);
        
        let qtyField = document.getElementById('jumlah_termin');
        qtyField.value = terminCounter;
    }

    function appendTerminTr(index, defaultPersen, defaultNilai) {
        let tr = document.createElement('tr');
        tr.id = 'row_termin_' + index;
        
        tr.innerHTML = `
            <td>
                <input type="text" class="form-control form-control-sm text-center" name="termins[${index}][termin_ke]" value="${index}" readonly>
            </td>
            <td>
                <input type="text" class="form-control form-control-sm" name="termins[${index}][keterangan]" placeholder="Keterangan termin" required>
            </td>
            <td>
                <input type="number" step="0.01" class="form-control form-control-sm persen_termin" name="termins[${index}][persentase]" value="${defaultPersen}" onkeyup="calcFromPersen(this)">
            </td>
            <td>
                <input type="number" step="0.01" class="form-control form-control-sm nilai_termin" name="termins[${index}][nilai]" value="${defaultNilai}" onkeyup="calcTerminTotal()">
            </td>
        
        `;
        document.getElementById('termin_table_body').appendChild(tr);
    }

    function removeTerminRow(index) {
        let tr = document.getElementById('row_termin_' + index);
        if(tr) tr.remove();
        calcTerminTotal();
    }

    function calcFromPersen(el) {
        let row = el.closest('tr');
        let persenInput = parseFloat(el.value) || 0;
        let nilaiKontrak = parseFloat(document.getElementById('total_amount').value) || 0;
        
        let targetNilaiInput = row.querySelector('.nilai_termin');
        targetNilaiInput.value = (nilaiKontrak * (persenInput / 100)).toFixed(2);
        
        calcTerminTotal();
    }

    function calcTerminTotal() {
        let inputs = document.querySelectorAll('.nilai_termin');
        let total = 0;
        inputs.forEach(input => {
            total += parseFloat(input.value) || 0;
        });

        document.getElementById('summary-total-termin').innerText = formatCurrency(total);
        
        let nilaiKontrak = parseFloat(document.getElementById('total_amount').value) || 0;
        let selisih = nilaiKontrak - total;

        let badgeSelisih = document.getElementById('summary-selisih');
        badgeSelisih.innerText = "Selisih: " + formatCurrency(selisih);

        let btnSubmit = document.getElementById('btn_submit');
        
        if (Math.abs(selisih) > 1) { // Floating point tolerance
            badgeSelisih.className = "badge bg-danger fs-6 shadow-sm";
            btnSubmit.disabled = true;
        } else {
            badgeSelisih.className = "badge bg-success fs-6 shadow-sm";
            btnSubmit.disabled = false;
        }
    }

    // Uang Muka calculation
    function calcUmPercent() {
        let nilaiKontrak = parseFloat(document.getElementById('total_amount').value) || 0;
        let nilaiUm = parseFloat(document.getElementById('nilai_uang_muka').value) || 0;
        let persenUmField = document.getElementById('persentase_uang_muka');

        if(nilaiKontrak > 0) {
            persenUmField.value = ((nilaiUm / nilaiKontrak) * 100).toFixed(2);
        }

        document.getElementById('summary-total-um').innerText = formatCurrency(nilaiUm);

        // also update table if rows exit
        if(document.getElementById('ada_uang_muka').checked) {
            generateUmRows();
        }
    }

    let umCounter = 0;
    function generateUmRows() {
        let qty = parseInt(document.getElementById('jumlah_angsuran_um').value) || 1;
        let tbody = document.getElementById('um_table_body');
        tbody.innerHTML = '';
        umCounter = 0;

        let nilaiUm = parseFloat(document.getElementById('nilai_uang_muka').value) || 0;
        let perNilai = nilaiUm > 0 ? (nilaiUm / qty).toFixed(2) : 0;

        for (let i = 1; i <= qty; i++) {
            umCounter++;
            let tr = document.createElement('tr');
            tr.innerHTML = `
                <td>
                    <input type="text" class="form-control form-control-sm text-center" name="angsuran_ums[${umCounter}][angsuran_ke]" value="${umCounter}" readonly>
                </td>
                <td>
                    <input type="number" step="0.01" class="form-control form-control-sm nilai_angsuran_um" name="angsuran_ums[${umCounter}][nilai]" value="${perNilai}" onkeyup="calcAngsuranTotal()">
                </td>
                <td>
                    <input type="text" class="form-control form-control-sm" name="angsuran_ums[${umCounter}][keterangan]" placeholder="Dipotong dari Termin...">
                </td>
                <td></td>
            `;
            tbody.appendChild(tr);
        }
    }

    function calcAngsuranTotal() {
        let inputs = document.querySelectorAll('.nilai_angsuran_um');
        let total = 0;
        inputs.forEach(input => {
            total += parseFloat(input.value) || 0;
        });

        let targetUm = parseFloat(document.getElementById('nilai_uang_muka').value) || 0;
        
        if (Math.abs(targetUm - total) > 1) {
            document.getElementById('um_warning').classList.remove('d-none');
            document.getElementById('btn_submit').disabled = true;
        } else {
            document.getElementById('um_warning').classList.add('d-none');
            // Re-check termin selisih to re-enable global submit button safely
            calcTerminTotal();
        }
    }

</script>
@endpush
