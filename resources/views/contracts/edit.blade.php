@extends('layouts.app')
@section('title')
    Edit Kontrak Pengadaan
@endsection
@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h5 class="mb-0 fw-bold">Edit Kontrak Pengadaan</h5>
            <div class="small text-muted">Nomor SPK: {{ $kontrak->nomor_spk }}</div>
        </div>
        <a href="{{ route('contracts.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Kembali ke Daftar</a>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger border-0 alert-dismissible fade show shadow-sm">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <form action="{{ route('contracts.update', $kontrak->id) }}" method="POST" id="formKontrak" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="row">
            {{-- Bagian 1: Data Utama & Pemilihan Anggaran --}}
            <div class="col-12 mb-4">
                <div class="card shadow-sm border-0 rounded-4 h-100">
                    <div class="card-header bg-primary text-white py-3 rounded-top-4">
                        <h6 class="mb-0 fw-bold"><i class="bi bi-file-earmark-text me-2"></i>1. Data Utama & Pemilihan Anggaran</h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Pilih Vendor / Mitra <span class="text-danger">*</span></label>
                                <select class="form-select select2" name="vendor_id" required>
                                    <option value="">-- Cari Vendor --</option>
                                    @foreach($vendors as $vendor)
                                        <option value="{{ $vendor->id }}" {{ old('vendor_id', $kontrak->vendor_id) == $vendor->id ? 'selected' : '' }}>
                                            {{ $vendor->nama_perusahaan }} ({{ str_replace('_', ' ', $vendor->kategori) }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Pilih Tahun & Nomor DIPA <span class="text-danger">*</span></label>
                                <select class="form-select select2" name="master_dipa_id" required>
                                    <option value="">-- Pilih DIPA --</option>
                                    @foreach($dipas as $dipa)
                                        <option value="{{ $dipa->id }}" {{ old('master_dipa_id', $kontrak->master_dipa_id) == $dipa->id ? 'selected' : '' }}>
                                            Tahun {{ $dipa->tahun_anggaran }} - {{ $dipa->nomor_dipa }} (Pagu: Rp {{ number_format($dipa->total_pagu, 0, ',', '.') }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-bold">Nama Pekerjaan <span class="text-danger">*</span></label>
                                <textarea class="form-control" rows="3" name="nama_pekerjaan" placeholder="Contoh: Pengadaan Jasa Kebersihan (Cleaning Service) Area Terminal Bandara" required>{{ old('nama_pekerjaan', $kontrak->nama_pekerjaan) }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Bagian 2: Detail Kontrak & Waktu Pelaksanaan --}}
            <div class="col-12 mb-4">
                <div class="card shadow-sm border-0 rounded-4 h-100">
                    <div class="card-header bg-info text-white py-3 rounded-top-4">
                        <h6 class="mb-0 fw-bold"><i class="bi bi-calendar-range me-2"></i>2. Detail Kontrak & Waktu Pelaksanaan</h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Nomor SPK (Surat Perintah Kerja) <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="nomor_spk" value="{{ old('nomor_spk', $kontrak->nomor_spk) }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Tanggal SPK <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="tanggal_spk" value="{{ old('tanggal_spk', $kontrak->tanggal_spk) }}" required>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label fw-bold">Tanggal Mulai Pekerjaan <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="tanggal_mulai" name="tanggal_mulai" value="{{ old('tanggal_mulai', $kontrak->tanggal_mulai) }}" required onchange="hitungTanggalSelesai()">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold">Satuan Waktu <span class="text-danger">*</span></label>
                                <select class="form-select" id="satuan_waktu" name="satuan_waktu" required onchange="hitungTanggalSelesai()">
                                    <option value="HARI" {{ old('satuan_waktu', $kontrak->satuan_waktu) == 'HARI' ? 'selected' : '' }}>Hari Kalender</option>
                                    <option value="MINGGU" {{ old('satuan_waktu', $kontrak->satuan_waktu) == 'MINGGU' ? 'selected' : '' }}>Minggu</option>
                                    <option value="BULAN" {{ old('satuan_waktu', $kontrak->satuan_waktu) == 'BULAN' ? 'selected' : '' }}>Bulan</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold">Jangka Waktu <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="jangka_waktu" name="jangka_waktu" value="{{ old('jangka_waktu', $kontrak->jangka_waktu) }}" min="1" required oninput="hitungTanggalSelesai()">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold">Tanggal Selesai Pekerjaan <span class="text-danger">*</span></label>
                                <input type="date" class="form-control bg-light" id="tanggal_selesai" name="tanggal_selesai" value="{{ old('tanggal_selesai', $kontrak->tanggal_selesai) }}" readonly required>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Bagian 3: Nilai & Skema Pembayaran --}}
            <div class="col-12 mb-4">
                <div class="card shadow-sm border-0 rounded-4 h-100">
                    <div class="card-header bg-success text-white py-3 rounded-top-4">
                        <h6 class="mb-0 fw-bold"><i class="bi bi-cash-stack me-2"></i>3. Nilai & Skema Pembayaran</h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-4 align-items-center">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Nilai Total Kontrak (Rp) <span class="text-danger">*</span></label>
                                <input type="text" class="form-control rupiah-input" id="nilai_total_kontrak_display" placeholder="Misal: 100.000.000" value="{{ old('nilai_total_kontrak', $kontrak->nilai_total_kontrak) }}" required>
                                <input type="hidden" name="nilai_total_kontrak" id="nilai_total_kontrak_value" value="{{ old('nilai_total_kontrak', $kontrak->nilai_total_kontrak) }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold d-block">Metode Pembayaran <span class="text-danger">*</span></label>
                                <div class="form-check form-check-inline mt-2">
                                    <input class="form-check-input" type="radio" name="metode_pembayaran" id="metodeLumpsum" value="LUMPSUM" {{ old('metode_pembayaran', $kontrak->metode_pembayaran) == 'LUMPSUM' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="metodeLumpsum">LUMPSUM (Dibayar Sekaligus)</label>
                                </div>
                                <div class="form-check form-check-inline mt-2">
                                    <input class="form-check-input" type="radio" name="metode_pembayaran" id="metodeTermin" value="TERMIN" {{ old('metode_pembayaran', $kontrak->metode_pembayaran) == 'TERMIN' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="metodeTermin">TERMIN (Dicicil/Bertahap)</label>
                                </div>
                            </div>
                            
                            <div class="col-12 mt-4 pt-3 border-top">
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" id="ada_uang_muka" name="ada_uang_muka" value="1" {{ old('ada_uang_muka', $kontrak->ada_uang_muka) ? 'checked' : '' }} onchange="toggleUangMuka()">
                                    <label class="form-check-label fw-bold" for="ada_uang_muka">Kontrak ini menerapkan Uang Muka (DP)?</label>
                                </div>
                            </div>

                            <div class="col-md-6" id="wrapper_uang_muka" style="display: none;">
                                <label class="form-label fw-bold">Nilai Uang Muka (Rp) <span class="text-danger">*</span></label>
                                <input type="text" class="form-control rupiah-input" id="nilai_uang_muka_display" placeholder="Misal: 20.000.000" value="{{ old('nilai_uang_muka', $kontrak->nilai_uang_muka) }}" oninput="validasiUangMuka()">
                                <input type="hidden" name="nilai_uang_muka" id="nilai_uang_muka_value" value="{{ old('nilai_uang_muka', $kontrak->nilai_uang_muka) }}">
                                <small class="text-danger mt-1 d-none" id="uang_muka_error">Peringatan: Nilai Uang Muka terindikasi melebihi batas batas wajar (30%) dari Total Kontrak!</small>
                            </div>

                            {{-- SKEMA TERMIN DINAMIS --}}
                            <div class="col-12 mt-4" id="wrapper_termin" style="display: none;">
                                <div class="border rounded-4 p-4 bg-light">
                                    <div class="alert alert-warning py-2 mb-3 shadow-sm border-0 d-flex align-items-center">
                                        <i class="bi bi-exclamation-triangle-fill text-warning me-2 fs-5"></i> 
                                        <span>Menyimpan halaman ini akan **memformat ulang** skema termin jika Anda merubah struktur tabel di bawah.</span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h6 class="fw-bold mb-0 text-primary"><i class="bi bi-list-columns-reverse me-2"></i>Rincian Skema Termin</h6>
                                        <button type="button" class="btn btn-sm btn-outline-primary" id="btnTambahTermin" onclick="tambahRowTermin()">
                                            <i class="bi bi-plus-circle me-1"></i> Tambah Termin
                                        </button>
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table table-bordered bg-white mb-2" id="tabelTermin">
                                            <thead class="table-light">
                                                <tr>
                                                    <th class="text-center" width="5%">Ke</th>
                                                    <th width="20%">Jenis</th>
                                                    <th width="35%">Keterangan</th>
                                                    <th class="text-center" width="15%">Persen (%)</th>
                                                    <th width="20%">Nilai Bruto (Rp)</th>
                                                    <th class="text-center" width="5%">Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody id="bodyTermin">
                                                {{-- JS will render rows here on load if edit mode --}}
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="d-flex justify-content-between px-2 pt-2 border-top">
                                        <small class="text-muted" id="termin_peringatan">Total Persentase: <strong id="total_persen_display">0%</strong></small>
                                        <small class="text-muted fw-bold">Total Nilai: <strong class="text-success" id="total_nilai_termin_display">Rp 0</strong></small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Bagian 4: Unggah Dokumen Perikatan --}}
            <div class="col-12 mb-4">
                <div class="card shadow-sm border-0 rounded-4 h-100">
                    <div class="card-header bg-secondary text-white py-3 rounded-top-4">
                        <h6 class="mb-0 fw-bold"><i class="bi bi-paperclip me-2"></i>4. Unggah Dokumen Perikatan (.PDF)</h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="alert alert-light border shadow-sm mb-4">
                            <i class="bi bi-info-circle-fill text-primary me-2"></i>Format didukung hanya <strong>.PDF</strong> dengan ukuran maksimal <strong>5 MB</strong>. Mengunggah file baru akan menggantikan file yang sebelumnya tersimpan (Replace).
                        </div>
                        <div class="row g-4">
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Dokumen SPK</label>
                                @if($kontrak->file_spk)
                                    <div class="mb-2"><a href="{{ Storage::url($kontrak->file_spk) }}" target="_blank" class="badge bg-primary text-decoration-none"><i class="bi bi-file-earmark-pdf"></i> SPK Saat Ini</a></div>
                                @endif
                                <input type="file" class="form-control" name="file_spk" accept=".pdf">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Dokumen SPMK</label>
                                @if($kontrak->file_spmk)
                                    <div class="mb-2"><a href="{{ Storage::url($kontrak->file_spmk) }}" target="_blank" class="badge bg-info text-decoration-none text-dark"><i class="bi bi-file-earmark-pdf"></i> SPMK Saat Ini</a></div>
                                @endif
                                <input type="file" class="form-control" name="file_spmk" accept=".pdf">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Ringkasan Kontrak</label>
                                @if($kontrak->file_ringkasan_kontrak)
                                    <div class="mb-2"><a href="{{ Storage::url($kontrak->file_ringkasan_kontrak) }}" target="_blank" class="badge bg-secondary text-decoration-none"><i class="bi bi-file-earmark-pdf"></i> Ringkasan Saat Ini</a></div>
                                @endif
                                <input type="file" class="form-control" name="file_ringkasan_kontrak" accept=".pdf">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Tombol Aksi --}}
            <div class="col-12 mb-5">
                <div class="d-flex justify-content-end gap-3 p-3 bg-light rounded-4 shadow-sm align-items-center">
                    <span class="text-muted small me-auto"><i class="bi bi-info-circle me-1"></i> Status Saat Ini: <strong class="text-dark">{{ $kontrak->status_kontrak }}</strong></span>
                    <button type="reset" class="btn btn-outline-secondary px-4">Reset Form</button>
                    <button type="submit" class="btn btn-warning px-5 fw-bold text-dark"><i class="bi bi-pencil-square me-2"></i>Simpan Perubahan</button>
                </div>
            </div>

        </div>
    </form>
@endsection

@push('css')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
.select2-container .select2-selection--single {
    height: 38px;
    border: 1px solid #dee2e6;
    border-radius: 6px;
}
.select2-container--default .select2-selection--single .select2-selection__rendered {
    line-height: 36px;
}
.select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 36px;
}
</style>
@endpush

@push('script')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    let terminCounter = 0;
    
    // Data Termin Exising dari Controller to JSON
    let existingTermins = @json($kontrak->termin ?? []);

    document.addEventListener("DOMContentLoaded", function () {
        $('.select2').select2({ theme: 'classic' });
        toggleUangMuka();

        document.querySelectorAll('.rupiah-input').forEach(input => {
            let hiddenInput = document.getElementById(input.id.replace('_display', '_value'));
            if(hiddenInput.value && hiddenInput.value > 0) {
                input.value = formatRupiah(hiddenInput.value);
            }
            input.addEventListener('keyup', function(e) {
                let cleanValue = this.value.replace(/[^,\d]/g, '');
                hiddenInput.value = cleanValue;
                this.value = formatRupiah(cleanValue);
                if (this.id === 'nilai_total_kontrak_display') {
                    validasiUangMuka();
                }
            });
        });

        // Load existing Termin rows if TERMIN
        if (existingTermins.length > 0 && document.querySelector('input[name="metode_pembayaran"]:checked').value === 'TERMIN') {
            existingTermins.forEach((t) => {
                tambahRowTerminData(t.jenis_termin, t.keterangan_termin, t.persentase, t.nilai_bruto_termin);
            });
        } else if (document.querySelector('input[name="metode_pembayaran"]:checked').value === 'TERMIN' && existingTermins.length === 0) {
            // Jika statusnya termin tapi tak ada datanya, create setidaknya baris pertama
            tambahRowTermin();
        }

        kalkulasiTotalTermin();
    });

    function formatRupiah(angka, prefix = 'Rp '){
        let number_string = angka.replace(/[^,\d]/g, '').toString(),
        split   		= number_string.split(','),
        sisa     		= split[0].length % 3,
        rupiah     		= split[0].substr(0, sisa),
        ribuan     		= split[0].substr(sisa).match(/\d{3}/gi);

        if(ribuan){
            let separator = sisa ? '.' : '';
            rupiah += separator + ribuan.join('.');
        }

        rupiah = split[1] != undefined ? rupiah + ',' + split[1] : rupiah;
        return prefix == undefined ? rupiah : (rupiah ? 'Rp ' + rupiah : '');
    }

    function toggleUangMuka() {
        let isChecked = document.getElementById('ada_uang_muka').checked;
        let wrapper = document.getElementById('wrapper_uang_muka');
        let inputDisplay = document.getElementById('nilai_uang_muka_display');
        let inputValue = document.getElementById('nilai_uang_muka_value');

        if (isChecked) {
            wrapper.style.display = 'block';
            inputDisplay.required = true;
        } else {
            wrapper.style.display = 'none';
            inputDisplay.required = false;
            // DONT reset value when toggling in edit mode immediately on load. 
            // We only reset if the user interacts. But we let `hidden` handle it when submitting
            // So if `ada_uang_muka` is unchecked upon saving, controller will ignore DP.
            document.getElementById('uang_muka_error').classList.add('d-none');
        }
    }

    function validasiUangMuka() {
        let total = parseFloat(document.getElementById('nilai_total_kontrak_value').value) || 0;
        let dp = parseFloat(document.getElementById('nilai_uang_muka_value').value) || 0;
        let errEl = document.getElementById('uang_muka_error');

        if (total > 0 && dp > (total * 0.3)) {
            errEl.classList.remove('d-none');
        } else {
            errEl.classList.add('d-none');
        }
    }

    function hitungTanggalSelesai() {
        let tglMulai = document.getElementById('tanggal_mulai').value;
        let satuan = document.getElementById('satuan_waktu').value;
        let jangka = parseInt(document.getElementById('jangka_waktu').value);

        if (tglMulai && jangka > 0) {
            let nDate = new Date(tglMulai);
            if (satuan === 'HARI') { nDate.setDate(nDate.getDate() + jangka); } 
            else if (satuan === 'MINGGU') { nDate.setDate(nDate.getDate() + (jangka * 7)); } 
            else if (satuan === 'BULAN') { nDate.setMonth(nDate.getMonth() + jangka); }
            
            let dd = String(nDate.getDate()).padStart(2, '0');
            let mm = String(nDate.getMonth() + 1).padStart(2, '0');
            let yyyy = nDate.getFullYear();
            document.getElementById('tanggal_selesai').value = yyyy + '-' + mm + '-' + dd;
        } else {
            document.getElementById('tanggal_selesai').value = '';
        }
    }

    document.querySelectorAll('input[name="metode_pembayaran"]').forEach(radio => {
        radio.addEventListener('change', function() {
            let wp = document.getElementById('wrapper_termin');
            if (this.value === 'TERMIN') {
                wp.style.display = 'block';
                if(terminCounter === 0) tambahRowTermin();
                kalkulasiTotalTermin();
            } else {
                wp.style.display = 'none';
            }
        });
    });

    if (document.querySelector('input[name="metode_pembayaran"]:checked').value === 'TERMIN') {
        document.getElementById('wrapper_termin').style.display = 'block';
    }

    // Function to add a completely empty row
    function tambahRowTermin() {
        tambahRowTerminData('PROGRESS', '', '', '0');
    }

    // Function to add a prefilled row (for editing)
    function tambahRowTerminData(jenis, ket, persen, nilai) {
        terminCounter++;
        let tbody = document.getElementById('bodyTermin');
        let newRow = document.createElement('tr');
        newRow.className = 'termin-row';
        newRow.id = `row_termin_${terminCounter}`;
        
        // Jenis selection string builders
        let jUM = (jenis == 'UANG_MUKA') ? 'selected' : '';
        let jP = (jenis == 'PROGRESS') ? 'selected' : '';
        let jL = (jenis == 'PELUNASAN') ? 'selected' : '';
        let jR = (jenis == 'RETENSI') ? 'selected' : '';

        newRow.innerHTML = `
            <td class="text-center termin-nomor fw-bold align-middle"></td>
            <td>
                <select class="form-select" name="termin_jenis[]" required>
                    <option value="UANG_MUKA" ${jUM}>Uang Muka</option>
                    <option value="PROGRESS" ${jP}>Progress</option>
                    <option value="PELUNASAN" ${jL}>Pelunasan</option>
                    <option value="RETENSI" ${jR}>Retensi</option>
                </select>
            </td>
            <td><input type="text" class="form-control" name="termin_keterangan[]" placeholder="Contoh: Tahap Lanjutan" value="${ket}" required></td>
            <td>
                <div class="input-group">
                    <input type="number" class="form-control termin-persen text-center" name="termin_persentase[]" placeholder="0" min="1" max="100" value="${persen}" required oninput="hitungTermin(${terminCounter})">
                    <span class="input-group-text">%</span>
                </div>
            </td>
            <td>
                <input type="text" class="form-control bg-light termin-nilai-display fw-bold text-success" id="termin_nilai_display_${terminCounter}" placeholder="Rp 0" readonly>
                <input type="hidden" class="termin-nilai-val" name="termin_nilai[]" id="termin_nilai_val_${terminCounter}" value="${nilai}">
            </td>
            <td class="text-center align-middle">
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="hapusRowTermin(${terminCounter})" ${terminCounter==1 && jenis=='PELUNASAN' && existingTermins.length <= 1 ? 'disabled' : ''}><i class="bi bi-trash"></i></button>
            </td>
        `;

        tbody.appendChild(newRow);
        updateNomorTermin();
        kalkulasiTotalTermin();
    }

    function hapusRowTermin(id) {
        let el = document.getElementById(`row_termin_${id}`);
        if(el) el.remove();
        updateNomorTermin();
        kalkulasiTotalTermin();
    }

    function updateNomorTermin() {
        let rows = document.querySelectorAll('.termin-row');
        rows.forEach((row, index) => {
            row.querySelector('.termin-nomor').innerText = index + 1;
            // Disable delete if it's the only remaining row
            let btnHapus = row.querySelector('.btn-outline-danger');
            if(rows.length === 1 && btnHapus) {
                btnHapus.disabled = true;
            } else if (btnHapus) {
                btnHapus.disabled = false;
            }
        });
    }

    function hitungTermin(id) {
        kalkulasiTotalTermin();
    }

    function kalkulasiTotalTermin() {
        let methodIsTermin = document.querySelector('input[name="metode_pembayaran"]:checked') ? document.querySelector('input[name="metode_pembayaran"]:checked').value === 'TERMIN' : false;
        if (!methodIsTermin) return;

        let totalKontrakStr = document.getElementById('nilai_total_kontrak_value').value || 0;
        let totalKontrak = parseFloat(totalKontrakStr);
        let totalPersen = 0;
        let totalNilai = 0;

        let rows = document.querySelectorAll('.termin-row');
        
        rows.forEach(row => {
            let persenInput = row.querySelector('.termin-persen');
            let dispVal = row.querySelector('.termin-nilai-display');
            let hidVal = row.querySelector('.termin-nilai-val');
            
            let p = parseFloat(persenInput.value) || 0;
            totalPersen += p;
            
            let n = 0;
            if (totalKontrak > 0 && p > 0) {
                n = Math.round((p / 100) * totalKontrak);
            }
            
            hidVal.value = n;
            dispVal.value = formatRupiah(n.toString(), 'Rp ');
            totalNilai += n;
        });

        let dispPersen = document.getElementById('total_persen_display');
        dispPersen.innerText = totalPersen + '%';
        if (totalPersen > 100) {
            dispPersen.className = 'text-danger fw-bold';
        } else if (totalPersen === 100) {
            dispPersen.className = 'text-success fw-bold';
        } else {
            dispPersen.className = 'text-warning text-dark fw-bold';
        }

        document.getElementById('total_nilai_termin_display').innerText = formatRupiah(totalNilai.toString(), 'Rp ');
    }

    document.getElementById('nilai_total_kontrak_display').addEventListener('keyup', function() {
        kalkulasiTotalTermin();
    });
</script>
@endpush
