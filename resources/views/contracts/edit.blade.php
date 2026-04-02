@extends('layouts.app')
@section('title')
    Edit Kontrak Pengadaan
@endsection
@section('content')
    @php
        $existingProgressTermins = $kontrak->termin->where('jenis_termin', 'PROGRESS')->values();
        $existingRetensiTermin = $kontrak->termin->firstWhere('jenis_termin', 'RETENSI');
        $oldProgressKeterangan = old('progress_keterangan', $existingProgressTermins->pluck('keterangan_termin')->all() ?: ['']);
        $oldProgressPersentase = old('progress_persentase', $existingProgressTermins->pluck('persentase')->all() ?: ['']);
        $progressRowCount = max(count($oldProgressKeterangan), count($oldProgressPersentase), 1);
        $oldRetensiPersentase = old('retensi_persentase', $existingRetensiTermin->persentase ?? null);
        $oldRetensiKeterangan = old('retensi_keterangan', $existingRetensiTermin->keterangan_termin ?? 'Retensi');
    @endphp
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
                                            {{ $vendor->nama_pihak }} ({{ str_replace('_', ' ', $vendor->kategori) }})
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
                                    <option value="HARI" {{ old('satuan_waktu', $kontrak->satuan_waktu) == 'HARI' ? 'selected' : '' }}>Hari</option>
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
                                        <span>Menyimpan perubahan akan membentuk ulang termin otomatis dengan urutan PROGRESS, lalu PELUNASAN, lalu RETENSI.</span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <div>
                                            <h6 class="fw-bold mb-1 text-primary"><i class="bi bi-list-columns-reverse me-2"></i>Rincian Termin Progress</h6>
                                            <small class="text-muted">Anda cukup mengatur progress dan retensi. Pelunasan akan dihitung otomatis dari sisa persentase kontrak.</small>
                                        </div>
                                        <button type="button" class="btn btn-sm btn-outline-primary" id="btnTambahTermin" onclick="tambahRowProgress()">
                                            <i class="bi bi-plus-circle me-1"></i> Tambah Progress
                                        </button>
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table table-bordered bg-white mb-2" id="tabelTermin">
                                            <thead class="table-light">
                                                <tr>
                                                    <th class="text-center" width="5%">Ke</th>
                                                    <th width="40%">Keterangan Progress</th>
                                                    <th class="text-center" width="15%">Persen (%)</th>
                                                    <th width="25%">Nilai Bruto (Rp)</th>
                                                    <th class="text-center" width="5%">Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody id="bodyTermin">
                                                @for ($i = 0; $i < $progressRowCount; $i++)
                                                    <tr class="termin-row" data-index="{{ $i + 1 }}">
                                                        <td class="text-center termin-nomor fw-bold align-middle">{{ $i + 1 }}</td>
                                                        <td>
                                                            <input type="text" class="form-control" name="progress_keterangan[]" placeholder="Contoh: Progress Tahap {{ $i + 1 }}" value="{{ $oldProgressKeterangan[$i] ?? '' }}" required>
                                                        </td>
                                                        <td>
                                                            <div class="input-group">
                                                                <input type="number" class="form-control termin-persen text-center" name="progress_persentase[]" placeholder="0" min="0.01" max="100" step="0.0001" value="{{ $oldProgressPersentase[$i] ?? '' }}" required oninput="kalkulasiTotalTermin()">
                                                                <span class="input-group-text">%</span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <input type="text" class="form-control bg-light termin-nilai-display fw-bold text-success" placeholder="Rp 0" readonly>
                                                        </td>
                                                        <td class="text-center align-middle">
                                                            <button type="button" class="btn btn-sm btn-outline-danger btn-hapus-termin" onclick="hapusRowProgress(this)" {{ $progressRowCount === 1 ? 'disabled' : '' }}><i class="bi bi-trash"></i></button>
                                                        </td>
                                                    </tr>
                                                @endfor
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="row g-3 mt-1">
                                        <div class="col-lg-4">
                                            <label class="form-label fw-bold">Retensi (%) <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <input type="number" class="form-control text-center" id="retensi_persentase" name="retensi_persentase" placeholder="Contoh: 5" min="0.01" max="100" step="0.0001" value="{{ $oldRetensiPersentase }}" oninput="kalkulasiTotalTermin()">
                                                <span class="input-group-text">%</span>
                                            </div>
                                        </div>
                                        <div class="col-lg-4">
                                            <label class="form-label fw-bold">Keterangan Retensi <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="retensi_keterangan" name="retensi_keterangan" value="{{ $oldRetensiKeterangan }}" placeholder="Contoh: Retensi masa pemeliharaan">
                                        </div>
                                        <div class="col-lg-4">
                                            <label class="form-label fw-bold">Nilai Retensi (Rp)</label>
                                            <input type="text" class="form-control bg-light fw-bold text-danger" id="retensi_nilai_display" placeholder="Rp 0" readonly>
                                        </div>
                                    </div>
                                    <div class="border rounded-4 bg-white p-3 mt-4">
                                        <h6 class="fw-bold text-dark mb-3">Preview Termin Otomatis</h6>
                                        <div class="table-responsive">
                                            <table class="table table-sm align-middle mb-0">
                                                <thead>
                                                    <tr>
                                                        <th>Jenis</th>
                                                        <th class="text-center">Persentase</th>
                                                        <th class="text-end">Nilai</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td class="fw-semibold text-primary">Pelunasan (otomatis)</td>
                                                        <td class="text-center"><span id="pelunasan_persen_display">0%</span></td>
                                                        <td class="text-end fw-bold text-success" id="pelunasan_nilai_display">Rp 0</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="fw-semibold text-danger">Retensi</td>
                                                        <td class="text-center"><span id="retensi_persen_display">0%</span></td>
                                                        <td class="text-end fw-bold text-danger" id="retensi_preview_display">Rp 0</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                    <div class="d-flex justify-content-between px-2 pt-3 border-top mt-3">
                                        <small id="termin_peringatan">Total Progress + Retensi: <strong id="total_persen_display">0%</strong></small>
                                        <small class="fw-bold">Total Nilai Progress: <strong class="text-success" id="total_nilai_termin_display">Rp 0</strong></small>
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
                            <div class="col-md-4" id="wrapper_file_jaminan_um" style="display: none;">
                                <label class="form-label fw-bold">Jaminan Uang Muka</label>
                                @if($kontrak->file_jaminan_uang_muka)
                                    <div class="mb-2"><a href="{{ Storage::url($kontrak->file_jaminan_uang_muka) }}" target="_blank" class="badge bg-warning text-dark text-decoration-none"><i class="bi bi-file-earmark-pdf"></i> Jaminan UM Saat Ini</a></div>
                                @endif
                                <input type="file" class="form-control" name="file_jaminan_um" id="file_jaminan_um" accept=".pdf">
                                <small class="text-muted d-block mt-1">Field ini hanya tampil jika kontrak menggunakan uang muka.</small>
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
        toggleTerminWrapper();
        updateNomorTermin();
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
        let wrapperFileJaminan = document.getElementById('wrapper_file_jaminan_um');
        let inputDisplay = document.getElementById('nilai_uang_muka_display');
        let inputValue = document.getElementById('nilai_uang_muka_value');
        let inputFileJaminan = document.getElementById('file_jaminan_um');

        if (isChecked) {
            wrapper.style.display = 'block';
            wrapperFileJaminan.style.display = 'block';
            inputDisplay.required = true;
        } else {
            wrapper.style.display = 'none';
            wrapperFileJaminan.style.display = 'none';
            inputDisplay.required = false;
            inputFileJaminan.value = '';
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
        radio.addEventListener('change', toggleTerminWrapper);
    });

    function toggleTerminWrapper() {
        let wp = document.getElementById('wrapper_termin');
        let isTermin = document.querySelector('input[name="metode_pembayaran"]:checked').value === 'TERMIN';
        let retensiInput = document.getElementById('retensi_persentase');
        let retensiKeterangan = document.getElementById('retensi_keterangan');
        let progressInputs = document.querySelectorAll('input[name="progress_keterangan[]"], input[name="progress_persentase[]"]');

        wp.style.display = isTermin ? 'block' : 'none';
        retensiInput.required = isTermin;
        retensiKeterangan.required = isTermin;
        progressInputs.forEach(input => {
            input.required = isTermin;
        });

        kalkulasiTotalTermin();
    }

    function tambahRowProgress() {
        let tbody = document.getElementById('bodyTermin');
        let rowNumber = tbody.querySelectorAll('.termin-row').length + 1;
        let newRow = document.createElement('tr');
        newRow.className = 'termin-row';
        newRow.setAttribute('data-index', rowNumber);
        newRow.innerHTML = `
            <td class="text-center termin-nomor fw-bold align-middle">${rowNumber}</td>
            <td><input type="text" class="form-control" name="progress_keterangan[]" placeholder="Contoh: Progress Tahap ${rowNumber}" required></td>
            <td>
                <div class="input-group">
                    <input type="number" class="form-control termin-persen text-center" name="progress_persentase[]" placeholder="0" min="0.01" max="100" step="0.0001" required oninput="kalkulasiTotalTermin()">
                    <span class="input-group-text">%</span>
                </div>
            </td>
            <td>
                <input type="text" class="form-control bg-light termin-nilai-display fw-bold text-success" placeholder="Rp 0" readonly>
            </td>
            <td class="text-center align-middle">
                <button type="button" class="btn btn-sm btn-outline-danger btn-hapus-termin" onclick="hapusRowProgress(this)"><i class="bi bi-trash"></i></button>
            </td>
        `;

        tbody.appendChild(newRow);
        updateNomorTermin();
        toggleTerminWrapper();
    }

    function hapusRowProgress(button) {
        let rows = document.querySelectorAll('.termin-row');
        if (rows.length === 1) {
            return;
        }

        button.closest('.termin-row').remove();
        updateNomorTermin();
        kalkulasiTotalTermin();
    }

    function updateNomorTermin() {
        let rows = document.querySelectorAll('.termin-row');
        rows.forEach((row, index) => {
            row.querySelector('.termin-nomor').innerText = index + 1;
            row.setAttribute('data-index', index + 1);
        });

        document.querySelectorAll('.btn-hapus-termin').forEach(button => {
            button.disabled = rows.length === 1;
        });
    }

    function kalkulasiTotalTermin() {
        let methodIsTermin = document.querySelector('input[name="metode_pembayaran"]:checked') ? document.querySelector('input[name="metode_pembayaran"]:checked').value === 'TERMIN' : false;
        if (!methodIsTermin) {
            document.getElementById('total_persen_display').innerText = '0%';
            document.getElementById('total_nilai_termin_display').innerText = 'Rp 0';
            document.getElementById('pelunasan_persen_display').innerText = '0%';
            document.getElementById('pelunasan_nilai_display').innerText = 'Rp 0';
            document.getElementById('retensi_persen_display').innerText = '0%';
            document.getElementById('retensi_preview_display').innerText = 'Rp 0';
            document.getElementById('retensi_nilai_display').value = '';
            return;
        }

        let totalKontrakStr = document.getElementById('nilai_total_kontrak_value').value || 0;
        let totalKontrak = parseFloat(totalKontrakStr);
        let totalProgressPersen = 0;
        let totalProgressNilai = 0;

        let rows = document.querySelectorAll('.termin-row');
        
        rows.forEach(row => {
            let persenInput = row.querySelector('.termin-persen');
            let dispVal = row.querySelector('.termin-nilai-display');
            
            let p = parseFloat(persenInput.value) || 0;
            let n = totalKontrak > 0 && p > 0 ? ((p / 100) * totalKontrak) : 0;

            dispVal.value = formatRupiah(Math.round(n).toString(), 'Rp ');
            totalProgressPersen += p;
            totalProgressNilai += n;
        });

        let retensiPersen = parseFloat(document.getElementById('retensi_persentase').value) || 0;
        let retensiNilai = totalKontrak > 0 && retensiPersen > 0 ? ((retensiPersen / 100) * totalKontrak) : 0;
        let pelunasanPersen = 100 - totalProgressPersen - retensiPersen;
        let pelunasanNilai = totalKontrak > 0 ? ((pelunasanPersen / 100) * totalKontrak) : 0;
        let totalPersen = totalProgressPersen + retensiPersen;

        let dispPersen = document.getElementById('total_persen_display');
        dispPersen.innerText = totalPersen + '%';
        if (totalPersen > 100) {
            dispPersen.className = 'text-danger fw-bold';
        } else if (Math.abs(totalPersen - 100) < 0.0001) {
            dispPersen.className = 'text-success fw-bold';
        } else {
            dispPersen.className = 'text-warning text-dark fw-bold';
        }

        document.getElementById('total_nilai_termin_display').innerText = formatRupiah(Math.round(totalProgressNilai).toString(), 'Rp ');
        document.getElementById('retensi_nilai_display').value = formatRupiah(Math.round(retensiNilai).toString(), 'Rp ');
        document.getElementById('retensi_persen_display').innerText = retensiPersen + '%';
        document.getElementById('retensi_preview_display').innerText = formatRupiah(Math.round(retensiNilai).toString(), 'Rp ');
        document.getElementById('pelunasan_persen_display').innerText = pelunasanPersen.toFixed(4).replace(/\.?0+$/, '') + '%';
        document.getElementById('pelunasan_nilai_display').innerText = formatRupiah(Math.round(Math.max(pelunasanNilai, 0)).toString(), 'Rp ');
    }

    document.getElementById('nilai_total_kontrak_display').addEventListener('keyup', function() {
        kalkulasiTotalTermin();
    });
</script>
@endpush
