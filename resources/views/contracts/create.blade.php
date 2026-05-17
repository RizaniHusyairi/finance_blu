@extends('layouts.app')
@section('title')
    Tambah Kontrak Pengadaan
@endsection
@section('content')
    @php
        $oldProgressKeterangan = old('progress_keterangan', ['']);
        $oldProgressPersentase = old('progress_persentase', ['']);
        $progressRowCount = max(count($oldProgressKeterangan), count($oldProgressPersentase), 1);
    @endphp
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0 fw-bold">Tambah Kontrak Pengadaan</h5>
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

    <form action="{{ route('contracts.store') }}" method="POST" id="formKontrak" enctype="multipart/form-data">
        @csrf

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
                                        <option value="{{ $vendor->id }}" {{ old('vendor_id') == $vendor->id ? 'selected' : '' }}>
                                            {{ $vendor->nama_pihak }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                @include('partials.dipa-item-grouped-select', [
                                    'budgetGroups' => $budgetGroups,
                                    'fieldName' => 'dipa_revision_item_id',
                                    'fieldId' => 'dipa_revision_item_id',
                                    'fieldClass' => 'form-select select2',
                                    'fieldLabel' => 'Pilih Item Anggaran (COA)',
                                    'placeholder' => '-- Cari Item Anggaran DIPA Aktif --',
                                ])
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-bold">Nama Pekerjaan <span class="text-danger">*</span></label>
                                <textarea class="form-control" rows="3" name="nama_pekerjaan" placeholder="Contoh: Pengadaan Jasa Kebersihan (Cleaning Service) Area Terminal Bandara" required>{{ old('nama_pekerjaan') }}</textarea>
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
                                <label class="form-label fw-bold">Nomor SPK (Surat Perintah Kerja)</label>
                                <input type="text" class="form-control bg-light fw-semibold" value="{{ $nomorSpkPreview }}" readonly>
                                <small class="text-muted d-block mt-1">Preview nomor SPK otomatis. Nomor final akan digenerate ulang saat kontrak disimpan.</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Tanggal SPK <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="tanggal_spk" value="{{ old('tanggal_spk', now()->format('Y-m-d')) }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Nomor SPMK</label>
                                <input type="text" class="form-control bg-light fw-semibold" value="{{ $nomorSpmkPreview }}" readonly>
                                <small class="text-muted d-block mt-1">Preview nomor SPMK otomatis. Nomor final akan digenerate ulang saat kontrak disimpan.</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Tanggal SPMK <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="tanggal_spmk" value="{{ old('tanggal_spmk', now()->format('Y-m-d')) }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Nomor Surat Undangan Pengadaan Langsung <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="nomor_surat_undangan_pengadaan" value="{{ old('nomor_surat_undangan_pengadaan') }}" placeholder="Contoh: B/123/PL.04.01/2026" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Nomor Berita Acara Hasil Pengadaan Langsung <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="nomor_ba_hasil_pengadaan" value="{{ old('nomor_ba_hasil_pengadaan') }}" placeholder="Contoh: BA/045/PL/2026" required>
                            </div>
                            <div class="col-md-12 row mt-2">
                                <div class="col-md-3">
                                    <label class="form-label fw-bold">Tanggal Mulai Pekerjaan <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="tanggal_mulai" name="tanggal_mulai" value="{{ old('tanggal_mulai') }}" required onchange="hitungTanggalSelesai()">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-bold">Jangka Waktu <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" id="jangka_waktu" name="jangka_waktu" value="{{ old('jangka_waktu') }}" min="1" required oninput="hitungTanggalSelesai()">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-bold">Satuan Waktu <span class="text-danger">*</span></label>
                                    <select class="form-select" id="satuan_waktu" name="satuan_waktu" required onchange="hitungTanggalSelesai()">
                                        <option value="HARI" {{ old('satuan_waktu') == 'HARI' ? 'selected' : '' }}>Hari</option>
                                        <option value="MINGGU" {{ old('satuan_waktu') == 'MINGGU' ? 'selected' : '' }}>Minggu</option>
                                        <option value="BULAN" {{ old('satuan_waktu') == 'BULAN' ? 'selected' : '' }}>Bulan</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-bold">Tanggal Selesai Pekerjaan <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control bg-light" id="tanggal_selesai" name="tanggal_selesai" value="{{ old('tanggal_selesai') }}" readonly required>
                                </div>
                            
                            </div>
                            <div class="col-md-12">
                                <label class="form-label fw-bold">Ketentuan Denda</label>
                                <textarea class="form-control" rows="2" name="ketentuan_denda" placeholder="Contoh: Denda keterlambatan dikenakan 1/1000 dari nilai kontrak per hari kalender.">{{ old('ketentuan_denda') }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            

            {{-- Bagian 4: Waktu Pemeliharaan --}}
            <div class="col-12 mb-4">
                <div class="card shadow-sm border-0 rounded-4 h-100">
                    <div class="card-header bg-warning text-dark py-3 rounded-top-4">
                        <h6 class="mb-0 fw-bold"><i class="bi bi-tools me-2"></i>3. Waktu Pemeliharaan</h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-4 align-items-end">
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Tanggal Mulai Pemeliharaan</label>
                                <input type="date" class="form-control" id="tanggal_mulai_pemeliharaan" name="tanggal_mulai_pemeliharaan" value="{{ old('tanggal_mulai_pemeliharaan') }}" onchange="hitungTanggalSelesaiPemeliharaan()">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Masa Pemeliharaan (hari kalender)</label>
                                <input type="number" class="form-control" id="masa_pemeliharaan_hari" name="masa_pemeliharaan_hari" value="{{ old('masa_pemeliharaan_hari') }}" min="0" placeholder="Contoh: 180" oninput="hitungTanggalSelesaiPemeliharaan()">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Tanggal Selesai Pemeliharaan</label>
                                <input type="date" class="form-control bg-light" id="tanggal_selesai_pemeliharaan" name="tanggal_selesai_pemeliharaan" value="{{ old('tanggal_selesai_pemeliharaan') }}" readonly>
                            </div>
                            <div class="col-12">
                                <div class="alert alert-warning border-0 mb-0">
                                    <small class="mb-0 d-block">
                                        Waktu pemeliharaan bersifat opsional. Jika diisi, tanggal selesai pemeliharaan akan dihitung otomatis berdasarkan tanggal mulai dan masa pemeliharaan.
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Bagian 5: Nilai & Skema Pembayaran --}}
            <div class="col-12 mb-4">
                <div class="card shadow-sm border-0 rounded-4 h-100">
                    <div class="card-header bg-success text-white py-3 rounded-top-4">
                        <h6 class="mb-0 fw-bold"><i class="bi bi-cash-stack me-2"></i>4. Nilai & Skema Pembayaran</h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-4 align-items-center">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Nilai Total Kontrak (Rp) <span class="text-danger">*</span></label>
                                <input type="text" class="form-control rupiah-input" id="nilai_total_kontrak_display" placeholder="Misal: 100.000.000" value="{{ old('nilai_total_kontrak') }}" required>
                                <input type="hidden" name="nilai_total_kontrak" id="nilai_total_kontrak_value" value="{{ old('nilai_total_kontrak') }}">
                                <small class="text-danger mt-1 fw-bold d-none" id="pagu_error"></small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold d-block">Metode Pembayaran <span class="text-danger">*</span></label>
                                <div class="form-check form-check-inline mt-2">
                                    <input class="form-check-input" type="radio" name="metode_pembayaran" id="metodeLumpsum" value="LUMPSUM" {{ old('metode_pembayaran', 'LUMPSUM') == 'LUMPSUM' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="metodeLumpsum">LUMPSUM (Dibayar Sekaligus)</label>
                                </div>
                                <div class="form-check form-check-inline mt-2">
                                    <input class="form-check-input" type="radio" name="metode_pembayaran" id="metodeTermin" value="TERMIN" {{ old('metode_pembayaran') == 'TERMIN' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="metodeTermin">TERMIN (Dicicil/Bertahap)</label>
                                </div>
                            </div>
                            
                            <div class="col-12 mt-4 pt-3 border-top" id="wrapper_toggle_uang_muka">
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" id="ada_uang_muka" name="ada_uang_muka" value="1" {{ old('ada_uang_muka') ? 'checked' : '' }} onchange="toggleUangMuka()">
                                    <label class="form-check-label fw-bold" for="ada_uang_muka">Kontrak ini menerapkan Uang Muka (DP)?</label>
                                </div>
                            </div>

                                <div class="col-md-6" id="wrapper_uang_muka" style="display: none;">
                                    <label class="form-label fw-bold">Nilai Uang Muka (Rp) <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control rupiah-input" id="nilai_uang_muka_display" placeholder="Misal: 20.000.000" value="{{ old('nilai_uang_muka') }}" oninput="validasiUangMuka()">
                                    <input type="hidden" name="nilai_uang_muka" id="nilai_uang_muka_value" value="{{ old('nilai_uang_muka', 0) }}">
                                    <small class="text-danger mt-1 d-none" id="uang_muka_error">Peringatan: Nilai Uang Muka terindikasi melebihi batas batas wajar (30%) dari Total Kontrak!</small>
                                </div>
                                <div class="col-md-6" id="wrapper_file_jaminan_um" style="display: none;">
                                    <label class="form-label fw-bold">Jaminan Uang Muka <small class="">(PDF)</small></label>
                                    <input type="file" class="form-control" name="file_jaminan_um" id="file_jaminan_um" accept=".pdf">
                                    <small class="text-muted d-block mt-1">Unggah dokumen jaminan uang muka bersamaan dengan pengisian nilai uang muka.</small>
                                </div>

                                {{-- SKEMA TERMIN DINAMIS --}}
                                <div class="col-12 mt-4" id="wrapper_termin" style="display: none;">
                                    <div class="border rounded-4 p-4 bg-light">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <div>
                                                <h6 class="fw-bold mb-1 text-primary"><i class="bi bi-list-columns-reverse me-2"></i>Rincian Termin Progress</h6>
                                                <small class="text-muted">Jenis termin dibentuk otomatis oleh sistem: semua baris di bawah adalah progress, lalu sistem akan menambahkan 1 pelunasan dan retensi hanya jika diaktifkan.</small>
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
                                                        <th class="text-center" width="15%">Persentase (%)</th>
                                                        <th width="20%">Nilai Bruto (Rp)</th>
                                                        <th width="20%">Preview Angsuran UM (Rp)</th>
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
                                                            <td>
                                                                <input type="text" class="form-control bg-light termin-potongan-um-display fw-bold text-warning" placeholder="Rp 0" readonly>
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
                                            <div class="col-12" id="wrapper_toggle_retensi">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="gunakan_retensi" name="gunakan_retensi" value="1" {{ old('gunakan_retensi') ? 'checked' : '' }} onchange="toggleRetensiFields()">
                                                    <label class="form-check-label fw-bold" for="gunakan_retensi">Kontrak ini menggunakan retensi?</label>
                                                    <div class="form-text small">Standar Perpres 12/2021: 5% dari nilai kontrak, ditahan sampai masa pemeliharaan selesai. Retensi berkaitan dengan masa pemeliharaan di Bagian 3.</div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row g-3 mt-1" id="wrapper_retensi_fields" style="display: none;">
                                            <div class="col-lg-4">
                                                <label class="form-label fw-bold">Keterangan Retensi <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" id="retensi_keterangan" name="retensi_keterangan" value="{{ old('retensi_keterangan', 'Retensi Masa Pemeliharaan') }}" placeholder="Contoh: Retensi masa pemeliharaan">
                                            </div>
                                            <div class="col-lg-4">
                                                <label class="form-label fw-bold">Retensi (%) <span class="text-danger">*</span></label>
                                                <div class="input-group">
                                                    <input type="number" class="form-control text-center" id="retensi_persentase" name="retensi_persentase" placeholder="5" min="0.01" max="100" step="0.0001" value="{{ old('retensi_persentase') }}" oninput="kalkulasiTotalTermin(); validasiRangeRetensi();">
                                                    <span class="input-group-text">%</span>
                                                </div>
                                                <small class="text-warning d-none mt-1" id="retensi_warning"><i class="bi bi-exclamation-triangle-fill me-1"></i>Retensi umumnya 5–10% dari nilai kontrak. Pastikan angka ini sesuai kebijakan unit Anda.</small>
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
                                                        <tr id="retensi_preview_row">
                                                            <td class="fw-semibold text-danger">Retensi</td>
                                                            <td class="text-center"><span id="retensi_persen_display">0%</span></td>
                                                            <td class="text-end fw-bold text-danger" id="retensi_preview_display">Rp 0</td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                        <div class="border rounded-4 bg-warning bg-opacity-10 p-3 mt-4 d-none" id="wrapper_preview_angsuran_um">
                                            <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
                                                <div>
                                                    <h6 class="fw-bold text-dark mb-1">Preview Estimasi Potongan Angsuran Uang Muka</h6>
                                                    <small class="text-muted">Informasi ini hanya preview estimasi. Nilai final akan dihitung saat penagihan termin dibuat.</small>
                                                </div>
                                                <span class="badge bg-warning-subtle text-dark border">Preview Estimasi</span>
                                            </div>
                                            <div class="row g-3 mt-1">
                                                <div class="col-md-4">
                                                    <div class="small text-muted">Rasio Uang Muka</div>
                                                    <div class="fw-bold fs-5" id="rasio_uang_muka_display">0%</div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="small text-muted">Total Estimasi Potongan pada Progress</div>
                                                    <div class="fw-bold fs-5 text-warning" id="total_estimasi_um_progress_display">Rp 0</div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="small text-muted">Sisa Estimasi yang Tertutup di Pelunasan</div>
                                                    <div class="fw-bold fs-5 text-success" id="sisa_estimasi_um_pelunasan_display">Rp 0</div>
                                                </div>
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
            </div>

            {{-- Bagian 3: Penandatangan Kontrak --}}
            <div class="col-12 mb-4">
                <div class="card shadow-sm border-0 rounded-4 h-100">
                    <div class="card-header bg-secondary text-white py-3 rounded-top-4">
                        <h6 class="mb-0 fw-bold"><i class="bi bi-person-badge me-2"></i>5. Penandatangan Kontrak</h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-4">
                            <div class="col-md-8">
                                <label class="form-label fw-bold">Pilih PPK <span class="text-danger">*</span></label>
                                <select class="form-select select2" id="ppk_user_id" name="ppk_user_id" required>
                                    <option value="">-- Cari PPK --</option>
                                    @foreach($ppkUsers as $ppkUser)
                                        <option
                                            value="{{ $ppkUser->id }}"
                                            data-nama="{{ $ppkUser->pegawai->nama_lengkap ?? $ppkUser->name }}"
                                            data-nip="{{ $ppkUser->pegawai->nip ?? '' }}"
                                            {{ old('ppk_user_id') == $ppkUser->id ? 'selected' : '' }}
                                        >
                                            {{ $ppkUser->pegawai->nama_lengkap ?? $ppkUser->name }} - {{ $ppkUser->pegawai->nip ?? 'NIP belum diisi' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12">
                                <div class="alert alert-secondary border-0 mb-0">
                                    <small class="mb-0 d-block">
                                        PPK yang dipilih akan menjadi penandatangan dokumen kontrak sekaligus verifikator kontrak saat dokumen diajukan.
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            

            {{-- Tombol Aksi --}}
            <div class="col-12 mb-5">
                <div class="d-flex justify-content-end gap-3 p-3 bg-light rounded-4 shadow-sm">
                    <button type="reset" class="btn btn-outline-secondary px-4">Reset Form</button>
                    <button type="submit" class="btn btn-primary px-5 fw-bold"><i class="bi bi-save me-2"></i>Simpan Kontrak</button>
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
        // Initialize Select2
        $('.select2').select2({
            theme: 'classic'
        });

        // Toggle Uang Muka On Load
        toggleUangMuka();

        document.querySelectorAll('.rupiah-input').forEach(input => {
            // Inisialisasi awal jika ada error server / old value
            let hiddenInput = document.getElementById(input.id.replace('_display', '_value'));
            if(hiddenInput.value && hiddenInput.value > 0) {
                input.value = formatRupiah(hiddenInput.value);
            }

            input.addEventListener('input', function(e) {
                let cleanValue = this.value.replace(/[^,\d]/g, '');
                hiddenInput.value = cleanValue;
                this.value = formatRupiah(cleanValue);
                if (this.id === 'nilai_total_kontrak_display') {
                    validasiUangMuka();
                    validasiSisaPagu();
                    kalkulasiTotalTermin();
                } else if (this.id === 'nilai_uang_muka_display') {
                    validasiUangMuka();
                    kalkulasiTotalTermin();
                }
            });
        });

        $('#dipa_revision_item_id').on('change', function() {
            validasiSisaPagu();
        });
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
        let metodeDipilih = document.querySelector('input[name="metode_pembayaran"]:checked')?.value;
        let wrapperToggle = document.getElementById('wrapper_toggle_uang_muka');
        let checkbox = document.getElementById('ada_uang_muka');
        let isChecked = document.getElementById('ada_uang_muka').checked;
        let wrapper = document.getElementById('wrapper_uang_muka');
        let wrapperFileJaminan = document.getElementById('wrapper_file_jaminan_um');
        let wrapperPreview = document.getElementById('wrapper_preview_angsuran_um');
        let inputDisplay = document.getElementById('nilai_uang_muka_display');
        let inputValue = document.getElementById('nilai_uang_muka_value');
        let inputFileJaminan = document.getElementById('file_jaminan_um');

        if (metodeDipilih !== 'TERMIN') {
            wrapperToggle.style.display = 'none';
            checkbox.checked = false;
            isChecked = false;
        } else {
            wrapperToggle.style.display = 'block';
        }

        if (isChecked) {
            wrapper.style.display = 'block';
            wrapperFileJaminan.style.display = 'block';
            inputDisplay.required = true;
        } else {
            wrapper.style.display = 'none';
            wrapperFileJaminan.style.display = 'none';
            inputDisplay.required = false;
            inputDisplay.value = '';
            inputValue.value = 0;
            inputFileJaminan.value = '';
            wrapperPreview.classList.add('d-none');
            document.getElementById('uang_muka_error').classList.add('d-none');
        }
        kalkulasiTotalTermin();
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

    function validasiSisaPagu() {
        let selectCoa = document.getElementById('dipa_revision_item_id');
        let selectedOption = selectCoa.options[selectCoa.selectedIndex];
        let total = parseFloat(document.getElementById('nilai_total_kontrak_value').value) || 0;
        let sisaPagu = selectedOption ? parseFloat(selectedOption.getAttribute('data-sisa-pagu')) || 0 : 0;
        let btnSubmit = document.querySelector('button[type="submit"]');
        let errPaguEl = document.getElementById('pagu_error');

        if (total > 0 && selectedOption && selectedOption.value !== "") {
            if (total > sisaPagu) {
                if (errPaguEl) {
                    errPaguEl.classList.remove('d-none');
                    errPaguEl.innerText = "Peringatan: Nilai Kontrak (Rp " + formatRupiah(total.toString(), '') + ") melebihi sisa pagu COA yang tersedia (Rp " + formatRupiah(sisaPagu.toString(), '') + ").";
                }
                btnSubmit.disabled = true;
            } else {
                if (errPaguEl) errPaguEl.classList.add('d-none');
                btnSubmit.disabled = false;
            }
        } else {
            if (errPaguEl) errPaguEl.classList.add('d-none');
            btnSubmit.disabled = false;
        }
    }

    function hitungTanggalSelesai() {
        let tglMulai = document.getElementById('tanggal_mulai').value;
        let satuan = document.getElementById('satuan_waktu').value;
        let jangka = parseInt(document.getElementById('jangka_waktu').value);

        if (tglMulai && jangka > 0) {
            let nDate = new Date(tglMulai);
            
            if (satuan === 'HARI') {
                nDate.setDate(nDate.getDate() + jangka);
            } else if (satuan === 'MINGGU') {
                nDate.setDate(nDate.getDate() + (jangka * 7));
            } else if (satuan === 'BULAN') {
                nDate.setMonth(nDate.getMonth() + jangka);
            }
            
            let dd = String(nDate.getDate()).padStart(2, '0');
            let mm = String(nDate.getMonth() + 1).padStart(2, '0');
            let yyyy = nDate.getFullYear();

            document.getElementById('tanggal_selesai').value = yyyy + '-' + mm + '-' + dd;
        } else {
            document.getElementById('tanggal_selesai').value = '';
        }
    }

    function hitungTanggalSelesaiPemeliharaan() {
        let tglMulai = document.getElementById('tanggal_mulai_pemeliharaan').value;
        let lamaHari = parseInt(document.getElementById('masa_pemeliharaan_hari').value);

        if (tglMulai && !isNaN(lamaHari) && lamaHari >= 0) {
            let nDate = new Date(tglMulai);
            nDate.setDate(nDate.getDate() + lamaHari);

            let dd = String(nDate.getDate()).padStart(2, '0');
            let mm = String(nDate.getMonth() + 1).padStart(2, '0');
            let yyyy = nDate.getFullYear();
            document.getElementById('tanggal_selesai_pemeliharaan').value = yyyy + '-' + mm + '-' + dd;
        } else {
            document.getElementById('tanggal_selesai_pemeliharaan').value = '';
        }
    }

    // --- LOGIKA SKEMA TERMIN DINAMIS ---
    document.querySelectorAll('input[name="metode_pembayaran"]').forEach(radio => {
        radio.addEventListener('change', toggleTerminWrapper);
    });

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

    function toggleTerminWrapper() {
        let wp = document.getElementById('wrapper_termin');
        let isTermin = document.querySelector('input[name="metode_pembayaran"]:checked').value === 'TERMIN';
        let retensiInput = document.getElementById('retensi_persentase');
        let retensiKeterangan = document.getElementById('retensi_keterangan');
        let progressInputs = document.querySelectorAll('input[name="progress_keterangan[]"], input[name="progress_persentase[]"]');

        wp.style.display = isTermin ? 'block' : 'none';
        
        if (!isTermin) {
            document.getElementById('gunakan_retensi').checked = false;
        }

        retensiInput.required = isTermin;
        retensiKeterangan.required = isTermin;
        progressInputs.forEach(input => {
            input.required = isTermin;
        });

        toggleUangMuka();
        toggleRetensiFields();
        kalkulasiTotalTermin();
    }

    function toggleRetensiFields() {
        let isTermin = document.querySelector('input[name="metode_pembayaran"]:checked').value === 'TERMIN';
        let gunakanRetensi = document.getElementById('gunakan_retensi').checked;
        let wrapperToggleRetensi = document.getElementById('wrapper_toggle_retensi');
        let wrapperRetensiFields = document.getElementById('wrapper_retensi_fields');
        let retensiInput = document.getElementById('retensi_persentase');
        let retensiKeterangan = document.getElementById('retensi_keterangan');
        let retensiPreviewRow = document.getElementById('retensi_preview_row');

        wrapperToggleRetensi.style.display = isTermin ? 'block' : 'none';

        if (isTermin && gunakanRetensi) {
            wrapperRetensiFields.style.display = 'flex';
            retensiInput.required = true;
            retensiKeterangan.required = true;
            retensiPreviewRow.classList.remove('d-none');
            // Auto-fill 5% (default Perpres 12/2021) jika field kosong agar user tinggal konfirmasi.
            if (! retensiInput.value || parseFloat(retensiInput.value) <= 0) {
                retensiInput.value = '5';
                kalkulasiTotalTermin();
            }
        } else {
            wrapperRetensiFields.style.display = 'none';
            retensiInput.required = false;
            retensiKeterangan.required = false;
            retensiInput.value = '';
            retensiPreviewRow.classList.add('d-none');
        }
        validasiRangeRetensi();
    }

    function validasiRangeRetensi() {
        let warning = document.getElementById('retensi_warning');
        let gunakanRetensi = document.getElementById('gunakan_retensi').checked;
        let nilai = parseFloat(document.getElementById('retensi_persentase').value) || 0;

        if (warning) {
            warning.classList.toggle('d-none', !(gunakanRetensi && nilai > 10));
        }
    }

    function tambahRowProgress() {
        let tbody = document.getElementById('bodyTermin');
        let rowNumber = tbody.querySelectorAll('.termin-row').length + 1;
        let newRow = document.createElement('tr');
        newRow.className = 'termin-row';
        newRow.setAttribute('data-index', rowNumber);
        newRow.innerHTML = `
            <td class="text-center termin-nomor fw-bold align-middle">${rowNumber}</td>
            <td>
                <input type="text" class="form-control" name="progress_keterangan[]" placeholder="Contoh: Progress Tahap ${rowNumber}" required>
            </td>
            <td>
                <div class="input-group">
                    <input type="number" class="form-control termin-persen text-center" name="progress_persentase[]" placeholder="0" min="0.01" max="100" step="0.0001" required oninput="kalkulasiTotalTermin()">
                    <span class="input-group-text">%</span>
                </div>
            </td>
            <td>
                <input type="text" class="form-control bg-light termin-nilai-display fw-bold text-success" placeholder="Rp 0" readonly>
            </td>
            <td>
                <input type="text" class="form-control bg-light termin-potongan-um-display fw-bold text-warning" placeholder="Rp 0" readonly>
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

    function kalkulasiTotalTermin() {
        let methodIsTermin = document.querySelector('input[name="metode_pembayaran"]:checked').value === 'TERMIN';
        let gunakanRetensi = document.getElementById('gunakan_retensi').checked;
        if (!methodIsTermin) {
            document.getElementById('total_persen_display').innerText = '0%';
            document.getElementById('total_nilai_termin_display').innerText = 'Rp 0';
            document.getElementById('pelunasan_persen_display').innerText = '0%';
            document.getElementById('pelunasan_nilai_display').innerText = 'Rp 0';
            document.getElementById('retensi_persen_display').innerText = '0%';
            document.getElementById('retensi_preview_display').innerText = 'Rp 0';
            document.getElementById('retensi_nilai_display').value = '';
            document.querySelectorAll('.termin-potongan-um-display').forEach(input => input.value = '');
            document.getElementById('wrapper_preview_angsuran_um').classList.add('d-none');
            document.getElementById('rasio_uang_muka_display').innerText = '0%';
            document.getElementById('total_estimasi_um_progress_display').innerText = 'Rp 0';
            document.getElementById('sisa_estimasi_um_pelunasan_display').innerText = 'Rp 0';
            return;
        }

        let totalKontrakStr = document.getElementById('nilai_total_kontrak_value').value || 0;
        let totalKontrak = parseFloat(totalKontrakStr);
        let nilaiUangMuka = parseFloat(document.getElementById('nilai_uang_muka_value').value || 0);
        let rasioUangMuka = totalKontrak > 0 ? (nilaiUangMuka / totalKontrak) : 0;
        let shouldShowPreviewUm = methodIsTermin && document.getElementById('ada_uang_muka').checked && nilaiUangMuka > 0;
        let totalProgressPersen = 0;
        let totalProgressNilai = 0;
        let totalEstimasiPotonganUmProgress = 0;
        let rows = document.querySelectorAll('.termin-row');

        rows.forEach(row => {
            let persenInput = row.querySelector('.termin-persen');
            let dispVal = row.querySelector('.termin-nilai-display');
            let dispPotonganUm = row.querySelector('.termin-potongan-um-display');
            let p = parseFloat(persenInput.value) || 0;
            let n = totalKontrak > 0 && p > 0 ? ((p / 100) * totalKontrak) : 0;
            let estimasiPotongan = shouldShowPreviewUm ? (n * rasioUangMuka) : 0;

            dispVal.value = formatRupiah(Math.round(n).toString(), 'Rp ');
            dispPotonganUm.value = shouldShowPreviewUm ? formatRupiah(Math.round(estimasiPotongan).toString(), 'Rp ') : '';
            totalProgressPersen += p;
            totalProgressNilai += n;
            totalEstimasiPotonganUmProgress += estimasiPotongan;
        });

        let retensiPersen = gunakanRetensi ? (parseFloat(document.getElementById('retensi_persentase').value) || 0) : 0;
        let retensiNilai = totalKontrak > 0 && retensiPersen > 0 ? ((retensiPersen / 100) * totalKontrak) : 0;
        let pelunasanPersen = 100 - totalProgressPersen - retensiPersen;
        let pelunasanNilai = totalKontrak > 0 ? ((pelunasanPersen / 100) * totalKontrak) : 0;
        let totalPersen = totalProgressPersen + retensiPersen;
        document.getElementById('termin_peringatan').innerHTML = gunakanRetensi
            ? 'Total Progress + Retensi: <strong id="total_persen_display">' + totalPersen + '%</strong>'
            : 'Total Progress: <strong id="total_persen_display">' + totalPersen + '%</strong>';

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
        document.getElementById('retensi_nilai_display').value = gunakanRetensi ? formatRupiah(Math.round(retensiNilai).toString(), 'Rp ') : '';
        document.getElementById('retensi_persen_display').innerText = gunakanRetensi ? (retensiPersen + '%') : '0%';
        document.getElementById('retensi_preview_display').innerText = gunakanRetensi ? formatRupiah(Math.round(retensiNilai).toString(), 'Rp ') : 'Rp 0';
        document.getElementById('pelunasan_persen_display').innerText = pelunasanPersen.toFixed(4).replace(/\.?0+$/, '') + '%';
        document.getElementById('pelunasan_nilai_display').innerText = formatRupiah(Math.round(Math.max(pelunasanNilai, 0)).toString(), 'Rp ');

        let wrapperPreview = document.getElementById('wrapper_preview_angsuran_um');
        let sisaEstimasiPelunasan = Math.max(nilaiUangMuka - totalEstimasiPotonganUmProgress, 0);

        if (shouldShowPreviewUm) {
            wrapperPreview.classList.remove('d-none');
            document.getElementById('rasio_uang_muka_display').innerText = (rasioUangMuka * 100).toFixed(2).replace(/\.?0+$/, '') + '%';
            document.getElementById('total_estimasi_um_progress_display').innerText = formatRupiah(Math.round(totalEstimasiPotonganUmProgress).toString(), 'Rp ');
            document.getElementById('sisa_estimasi_um_pelunasan_display').innerText = formatRupiah(Math.round(sisaEstimasiPelunasan).toString(), 'Rp ');
        } else {
            wrapperPreview.classList.add('d-none');
            document.getElementById('rasio_uang_muka_display').innerText = '0%';
            document.getElementById('total_estimasi_um_progress_display').innerText = 'Rp 0';
            document.getElementById('sisa_estimasi_um_pelunasan_display').innerText = 'Rp 0';
        }
    }

    document.getElementById('gunakan_retensi').addEventListener('change', function() {
        toggleRetensiFields();
        kalkulasiTotalTermin();
    });

    toggleTerminWrapper();
    toggleRetensiFields();
    updateNomorTermin();
    hitungTanggalSelesaiPemeliharaan();
    kalkulasiTotalTermin();
    validasiSisaPagu();
</script>
@endpush
