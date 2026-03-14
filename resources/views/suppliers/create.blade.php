@extends('layouts.app')
@section('title')
    Tambah Supplier
@endsection
@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0 fw-bold">Tambah Supplier</h5>
        <a href="{{ route('suppliers.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Kembali ke Daftar</a>
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

    <form action="{{ route('suppliers.store') }}" method="POST" id="formSupplier">
        @csrf

        {{-- Summary Panel --}}
        <div class="row row-cols-1 row-cols-sm-2 row-cols-md-4 g-3 mb-4">
            
            <div class="col">
                <div class="card rounded-4 mb-0 h-100">
                    <div class="card-body p-3">
                        <p class="mb-1 small">Kelengkapan Data</p>
                        <h6 class="mb-0 fw-bold" id="summary-kelengkapan">
                            <span class="badge bg-secondary">Belum Diisi</span>
                        </h6>
                    </div>
                </div>
            </div>
            
        </div>

        <div class="row">
            {{-- Section 1: Informasi Utama --}}
            <div class="col-12 mb-4">
                <div class="card rounded-4 h-100">
                    <div class="card-body p-4">
                        <h6 class="mb-4 fw-bold"><i class="bi bi-info-circle me-2"></i>1. Informasi Utama Supplier</h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">ID Supplier</label>
                                <input type="text" class="form-control" name="id_supplier" value="{{ $idSupplier }}" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nama Supplier / Mitra <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="name" id="name" value="{{ old('name') }}" placeholder="Masukkan nama supplier / mitra" required oninput="checkKelengkapan()">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tipe Supplier <span class="text-danger">*</span></label>
                                <select class="form-select" name="type" id="type" required onchange="updateSummaryTipe()">
                                    <option value="">-- Pilih Tipe --</option>
                                    <option value="01 Satker" {{ old('type') == '01 Satker' ? 'selected' : '' }}>01 Satker</option>
                                    <option value="02 Penyedia Barang Jasa" {{ old('type') == '02 Penyedia Barang Jasa' ? 'selected' : '' }}>02 Penyedia Barang Jasa</option>
                                    <option value="03 Pegawai" {{ old('type') == '03 Pegawai' ? 'selected' : '' }}>03 Pegawai</option>
                                    <option value="06 Penerusan Pinjaman" {{ old('type') == '06 Penerusan Pinjaman' ? 'selected' : '' }}>06 Penerusan Pinjaman</option>
                                </select>
                            </div>
                            <div class="col-md-6"></div>
                            <div class="col-12">
                                <label class="form-label">Alamat Lengkap <span class="text-danger">*</span></label>
                                <textarea class="form-control" rows="3" name="address" placeholder="Masukkan alamat lengkap supplier" required oninput="checkKelengkapan()">{{ old('address') }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Section 2: Informasi Pajak --}}
            <div class="col-12 mb-4">
                <div class="card rounded-4 h-100">
                    <div class="card-body p-4">
                        <h6 class="mb-4 fw-bold"><i class="bi bi-receipt me-2"></i>2. Informasi Pajak</h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Status NPWP <span class="text-danger">*</span></label>
                                <select class="form-select" name="npwp_status" id="npwp_status" required onchange="toggleNpwp()">
                                    <option value="Belum Ada" {{ old('npwp_status', 'Belum Ada') == 'Belum Ada' ? 'selected' : '' }}>Belum Ada</option>
                                    <option value="Tersedia" {{ old('npwp_status') == 'Tersedia' ? 'selected' : '' }}>Tersedia</option>
                                </select>
                                <small>Pilih "Tersedia" untuk mengisi NPWP secara langsung</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">NPWP</label>
                                <input type="text" class="form-control" name="npwp" id="npwp" value="{{ old('npwp') }}" placeholder="Masukkan NPWP supplier" disabled oninput="checkKelengkapan()">
                                <small id="npwp_help">Field ini akan aktif jika Status NPWP adalah "Tersedia"</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Section 3: Informasi Rekening --}}
            <div class="col-12 mb-4">
                <div class="card rounded-4 h-100">
                    <div class="card-body p-4">
                        <h6 class="mb-4 fw-bold"><i class="bi bi-bank me-2"></i>3. Informasi Rekening</h6>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Status Rekening <span class="text-danger">*</span></label>
                                <select class="form-select" name="rekening_status" id="rekening_status" required onchange="toggleRekening()">
                                    <option value="Belum Ada" {{ old('rekening_status', 'Belum Ada') == 'Belum Ada' ? 'selected' : '' }}>Belum Ada</option>
                                    <option value="Tersedia" {{ old('rekening_status') == 'Tersedia' ? 'selected' : '' }}>Tersedia</option>
                                </select>
                                <small>Pilih "Tersedia" untuk mengisi data rekening</small>
                            </div>
                            <div class="col-md-8"></div>
                            <div class="col-md-4">
                                <label class="form-label">Bank / Pos</label>
                                <input type="text" class="form-control rek-field" name="bank_name" id="bank_name" value="{{ old('bank_name') }}" placeholder="Contoh: BRI, Mandiri, BNI, Pos" disabled oninput="checkKelengkapan()">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">No. Rekening</label>
                                <input type="text" class="form-control rek-field" name="bank_account" id="bank_account" value="{{ old('bank_account') }}" placeholder="Nomor rekening" disabled oninput="checkKelengkapan()">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Nama Pemilik Rekening</label>
                                <input type="text" class="form-control rek-field" name="account_name" id="account_name" value="{{ old('account_name') }}" placeholder="Nama pemilik rekening" disabled oninput="checkKelengkapan()">
                            </div>
                        </div>
                        <small id="rek_help">Field rekening akan aktif jika Status Rekening adalah "Tersedia"</small>
                    </div>
                </div>
            </div>

            {{-- Section 4: Kontak & Status --}}
            <div class="col-12 mb-4">
                <div class="card rounded-4 h-100">
                    <div class="card-body p-4">
                        <h6 class="mb-4 fw-bold"><i class="bi bi-telephone me-2"></i>4. Kontak & Status</h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">No. Telepon</label>
                                <input type="text" class="form-control" name="phone" value="{{ old('phone') }}" placeholder="Masukkan nomor telepon supplier" oninput="checkKelengkapan()">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Status Supplier <span class="text-danger">*</span></label>
                                <select class="form-select" name="status" id="status" required onchange="updateSummaryStatus()">
                                    <option value="Aktif" {{ old('status', 'Aktif') == 'Aktif' ? 'selected' : '' }}>Aktif</option>
                                    <option value="Nonaktif" {{ old('status') == 'Nonaktif' ? 'selected' : '' }}>Nonaktif</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Catatan</label>
                                <textarea class="form-control" rows="2" name="catatan" placeholder="Tambahkan catatan bila diperlukan">{{ old('catatan') }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Konfirmasi Duplikat (hidden checkbox) --}}
            <div class="col-12 mb-2" id="duplicate_warning" style="display: none;">
                <div class="alert alert-warning d-flex align-items-center gap-2">
                    <input class="form-check-input" type="checkbox" name="confirm_duplicate" id="confirm_duplicate" value="1">
                    <label class="form-check-label" for="confirm_duplicate">Saya yakin ingin menyimpan meskipun nama supplier sudah ada</label>
                </div>
            </div>

            {{-- Tombol Aksi --}}
            <div class="col-12 mb-5">
                <div class="card rounded-4">
                    <div class="card-body p-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="1" id="simpan_draft" name="simpan_draft">
                            <label class="form-check-label" for="simpan_draft">Simpan sebagai Draft (Nonaktif)</label>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="{{ route('suppliers.index') }}" class="btn btn-outline-secondary px-4" onclick="return confirmBatal()">Batal</a>
                            <button type="reset" class="btn btn-outline-secondary px-4" onclick="return confirm('Apakah Anda yakin ingin mereset seluruh form?')">Reset Form</button>
                            <button type="submit" class="btn btn-primary px-5 fw-bold">Simpan Supplier</button>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </form>
@endsection

@push('script')
<script>
    document.addEventListener("DOMContentLoaded", function () {
        toggleNpwp();
        toggleRekening();
        checkKelengkapan();
    });

    function toggleNpwp() {
        let status = document.getElementById('npwp_status').value;
        let npwpField = document.getElementById('npwp');
        if (status === 'Tersedia') {
            npwpField.disabled = false;
            npwpField.required = true;
        } else {
            npwpField.disabled = true;
            npwpField.required = false;
            npwpField.value = '';
        }
        checkKelengkapan();
    }

    function toggleRekening() {
        let status = document.getElementById('rekening_status').value;
        let fields = document.querySelectorAll('.rek-field');
        fields.forEach(f => {
            if (status === 'Tersedia') {
                f.disabled = false;
                f.required = true;
            } else {
                f.disabled = true;
                f.required = false;
                f.value = '';
            }
        });
        checkKelengkapan();
    }

    function updateSummaryStatus() {
        document.getElementById('summary-status').innerText = document.getElementById('status').value;
    }

    function updateSummaryTipe() {
        let tipe = document.getElementById('type').value;
        document.getElementById('summary-tipe').innerText = tipe || '-';
    }

    function checkKelengkapan() {
        let name = document.getElementById('name').value.trim();
        let type = document.getElementById('type').value;
        let alamat = document.querySelector('[name="address"]').value.trim();
        let npwpStatus = document.getElementById('npwp_status').value;
        let rekStatus = document.getElementById('rekening_status').value;

        let filled = 0;
        let total = 5; // name, type, alamat, npwp section, rekening section

        if (name) filled++;
        if (type) filled++;
        if (alamat) filled++;

        // NPWP section
        if (npwpStatus === 'Tersedia') {
            let npwp = document.getElementById('npwp').value.trim();
            if (npwp) filled++;
        } else if (npwpStatus === 'Terlampir') {
            filled++;
        }

        // Rekening section
        if (rekStatus === 'Tersedia') {
            let bank = document.getElementById('bank_name').value.trim();
            let norek = document.getElementById('bank_account').value.trim();
            let namarek = document.getElementById('account_name').value.trim();
            if (bank && norek && namarek) filled++;
        } else if (rekStatus === 'Terlampir') {
            filled++;
        }

        let el = document.getElementById('summary-kelengkapan');

        if (filled >= total) {
            el.innerHTML = '<span class="badge bg-success">Lengkap</span>';
        } else if (filled >= 3) {
            el.innerHTML = '<span class="badge bg-warning">Sebagian (' + filled + '/' + total + ')</span>';
        } else if (filled >= 1) {
            el.innerHTML = '<span class="badge bg-danger">Belum Lengkap (' + filled + '/' + total + ')</span>';
        } else {
            el.innerHTML = '<span class="badge bg-secondary">Belum Diisi</span>';
        }
    }

    function confirmBatal() {
        let name = document.getElementById('name').value.trim();
        if (name) {
            return confirm('Data sudah terisi. Apakah Anda yakin ingin membatalkan?');
        }
        return true;
    }
</script>
@endpush
