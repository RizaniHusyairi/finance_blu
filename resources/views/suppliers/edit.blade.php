@extends('layouts.app')
@section('title')
    Edit Mitra & Vendor
@endsection
@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0 fw-bold">Edit Mitra & Vendor</h5>
        <a href="{{ route('suppliers.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Kembali ke Daftar</a>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger border-0 alert-dismissible fade show">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <form action="{{ route('suppliers.update', $supplier->id) }}" method="POST" id="formSupplier">
        @csrf
        @method('PUT')

        @php
            // Memanggil data rekening
            $rek = $supplier->rekening()->first();
        @endphp

        <div class="row">
            {{-- Bagian 1: Klasifikasi & Identitas Utama --}}
            <div class="col-12 mb-4">
                <div class="card shadow-sm border-0 rounded-4 h-100">
                    <div class="card-header bg-primary text-white py-3 rounded-top-4">
                        <h6 class="mb-0 fw-bold"><i class="bi bi-buildings me-2"></i>1. Klasifikasi & Identitas Utama</h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Kategori Mitra <span class="text-danger">*</span></label>
                                <select class="form-select" name="kategori" required>
                                    <option value="">-- Pilih Kategori --</option>
                                    <option value="VENDOR_PENGELUARAN" {{ old('kategori', $supplier->kategori) == 'VENDOR_PENGELUARAN' ? 'selected' : '' }}>Vendor Pengeluaran (Penyedia Barang/Jasa)</option>
                                    <option value="MITRA_PENERIMAAN" {{ old('kategori', $supplier->kategori) == 'MITRA_PENERIMAAN' ? 'selected' : '' }}>Mitra Penerimaan (Penyewa Tenant)</option>
                                    <option value="KEDUANYA" {{ old('kategori', $supplier->kategori) == 'KEDUANYA' ? 'selected' : '' }}>Keduanya</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Tipe Supplier <span class="text-danger">*</span></label>
                                <select class="form-select" name="tipe_supplier" required>
                                    <option value="">-- Pilih Tipe --</option>
                                    <option value="01 - Satker" {{ old('tipe_supplier', $supplier->tipe_supplier) == '01 - Satker' ? 'selected' : '' }}>01 - Satker</option>
                                    <option value="02 - Penyedia/Badan Usaha" {{ old('tipe_supplier', $supplier->tipe_supplier) == '02 - Penyedia/Badan Usaha' ? 'selected' : '' }}>02 - Penyedia/Badan Usaha</option>
                                    <option value="03 - Pegawai" {{ old('tipe_supplier', $supplier->tipe_supplier) == '03 - Pegawai' ? 'selected' : '' }}>03 - Pegawai</option>
                                    <option value="06 - Penerusan Pinjaman" {{ old('tipe_supplier', $supplier->tipe_supplier) == '06 - Penerusan Pinjaman' ? 'selected' : '' }}>06 - Penerusan Pinjaman</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Nama Perusahaan / Instansi <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="nama_perusahaan" value="{{ old('nama_perusahaan', $supplier->nama_perusahaan) }}" placeholder="Contoh: CV. ANUGRAH AGUNG JAYA" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Nama Direktur / Penanggung Jawab</label>
                                <input type="text" class="form-control" name="nama_direktur" value="{{ old('nama_direktur', $supplier->nama_direktur) }}" placeholder="Contoh: Arifin">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Jabatan Penandatangan</label>
                                <input type="text" class="form-control" name="jabatan_penandatangan" value="{{ old('jabatan_penandatangan', $supplier->jabatan_penandatangan) }}" placeholder="Contoh: Direktur Utama">
                                <div class="form-text">Digunakan untuk dokumen kontrak dan SPMK pada sisi penandatangan vendor.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">NPWP <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="npwp" id="npwp" value="{{ old('npwp', $supplier->npwp) }}" placeholder="00.000.000.0-000.000" required>
                                <div class="form-text">Format otomatis, ketik angka saja.</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Bagian 2: Informasi Kontak & Alamat --}}
            <div class="col-12 mb-4">
                <div class="card shadow-sm border-0 rounded-4 h-100">
                    <div class="card-header bg-info text-white py-3 rounded-top-4">
                        <h6 class="mb-0 fw-bold"><i class="bi bi-geo-alt me-2"></i>2. Informasi Kontak & Alamat</h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-4">
                            <div class="col-md-12">
                                <label class="form-label fw-bold">Nomor Telepon / HP</label>
                                <input type="text" class="form-control" name="no_telepon" value="{{ old('no_telepon', $supplier->no_telepon) }}" placeholder="Contoh: 081234567890" pattern="^0[0-9]{8,15}$" title="Nomor telepon harus diawali nol dan berisi angka saja.">
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-bold">Alamat Lengkap Perusahaan</label>
                                <textarea class="form-control" rows="3" name="alamat" placeholder="Contoh: Jl. PM. Noor Perum Bumi Sempaja Blok HA 133...">{{ old('alamat', $supplier->alamat) }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Bagian 3: Informasi Rekening Bank --}}
            <div class="col-12 mb-4">
                <div class="card shadow-sm border-0 rounded-4 h-100">
                    <div class="card-header bg-success text-white py-3 rounded-top-4">
                        <h6 class="mb-0 fw-bold"><i class="bi bi-bank me-2"></i>3. Informasi Rekening Bank</h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="alert alert-light border shadow-sm mb-4">
                            <i class="bi bi-info-circle-fill text-primary me-2"></i>Data rekening bank wajib diisi untuk kelancaran pencairan pembayaran (SP2D).
                        </div>
                        <div class="row g-4">
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Nama Bank <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="nama_bank" value="{{ old('nama_bank', $rek->nama_bank ?? '') }}" placeholder="Contoh: Bank Kaltimtara Cab. Pembantu Sempaja" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Nomor Rekening <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="nomor_rekening" value="{{ old('nomor_rekening', $rek->nomor_rekening ?? '') }}" placeholder="Contoh: 1331502631" pattern="^[0-9]+$" title="Hanya masukkan angka." required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Nama Pemilik Rekening <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="nama_rekening" value="{{ old('nama_rekening', $rek->nama_rekening ?? '') }}" placeholder="Sesuai buku tabungan, cth: CV. ANUGRAH AGUNG JAYA" required>
                                <div class="form-text">Pastikan nama pemilik rekening sama dengan nama instansi/vendor.</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Tombol Aksi --}}
            <div class="col-12 mb-5">
                <div class="d-flex justify-content-end gap-3 p-3 bg-light rounded-4 shadow-sm">
                    <button type="reset" class="btn btn-outline-secondary px-4">Kembalikan Semula</button>
                    <button type="submit" class="btn btn-warning px-5 fw-bold text-dark"><i class="bi bi-pencil-square me-2"></i>Simpan Perubahan</button>
                </div>
            </div>

        </div>
    </form>
@endsection

@push('script')
<script>
    document.addEventListener("DOMContentLoaded", function () {
        // NPWP Input Masking: 00.000.000.0-000.000
        const npwpInput = document.getElementById('npwp');
        
        npwpInput.addEventListener('input', function (e) {
            let value = e.target.value.replace(/\D/g, ''); // Remove non-digits
            let formatted = '';
            
            if (value.length > 0) formatted += value.substring(0, 2);
            if (value.length > 2) formatted += '.' + value.substring(2, 5);
            if (value.length > 5) formatted += '.' + value.substring(5, 8);
            if (value.length > 8) formatted += '.' + value.substring(8, 9);
            if (value.length > 9) formatted += '-' + value.substring(9, 12);
            if (value.length > 12) formatted += '.' + value.substring(12, 15);
            
            e.target.value = formatted;
        });
    });
</script>
@endpush
