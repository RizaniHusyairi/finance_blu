@extends('layouts.app')
@section('title')
    Edit Data Perjaldin
@endsection
@section('content')
    <x-page-title title="Manajemen Perjaldin" subtitle="Edit Data" />

    <div class="card">
        <div class="card-body">
            <h6 class="mb-4">Edit Rencana Perjalanan Pegawai</h6>
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

            <form action="{{ route('perjaldins.update', $pejabat->pejabat_id) }}" method="POST">
                @csrf
                @method('PUT')
                
                <h5 class="mb-3 border-bottom pb-2">Informasi Rencana Acara</h5>
                <div class="row mb-4">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Uraian / Judul Perjalanan Dinas <span class="text-danger">*</span></label>
                        <input type="text" name="uraian" class="form-control" required value="{{ old('uraian', $pejabat->perjaldin->uraian) }}">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">No BAST</label>
                        <input type="text" name="no_bast" class="form-control" value="{{ old('no_bast', $pejabat->perjaldin->no_bast) }}">
                    </div>
                </div>

                <h5 class="mb-3 border-bottom pb-2">Data Pejabat / Pegawai Berangkat</h5>
                
                <div class="row mb-3">
                    <!-- <div class="col-md-4">
                        <label class="form-label">Pilih Pegawai dari Sistem</label>
                        <select class="form-select" id="employeeSelect" name="employee_id">
                            <option value="">-- Manual / Bukan Karyawan Tetap --</option>
                            @foreach($employees as $emp)
                                <option value="{{ $emp->id }}" data-nip="{{ $emp->nip }}" data-name="{{ $emp->name }}" {{ $pejabat->employee_id == $emp->id ? 'selected' : '' }}>
                                    {{ $emp->name }}
                                </option>
                            @endforeach
                        </select>
                    </div> -->
                    <div class="col-md-4">
                        <label class="form-label">Nama Pegawai <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="employeeName" name="nama_pejabat" required value="{{ old('nama_pejabat', $pejabat->nama_pejabat) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">NIP</label>
                        <input type="text" class="form-control" id="employeeNip" name="nip" value="{{ old('nip', $pejabat->nip) }}">
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-3">
                        <label class="form-label">No SPT <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="no_spt" required value="{{ old('no_spt', $pejabat->no_spt) }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">No SPPD <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="no_sppd" required value="{{ old('no_sppd', $pejabat->no_sppd) }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Tanggal Berangkat <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" name="tanggal_berangkat" required value="{{ old('tanggal_berangkat', $pejabat->tanggal_berangkat) }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Lama Perjalanan (Hari) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="number" class="form-control" name="lama_perjalanan_dinas" required min="1" value="{{ old('lama_perjalanan_dinas', $pejabat->lama_perjalanan_dinas) }}">
                            <span class="input-group-text">Hari</span>
                        </div>
                    </div>
                </div>
                
                <div class="row mb-4">
                    <div class="col-md-12">
                        <label class="form-label">Tujuan <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="tujuan" rows="2" required>{{ old('tujuan', $pejabat->tujuan) }}</textarea>
                    </div>
                </div>

                <h5 class="mb-3 border-bottom pb-2">Rincian Biaya</h5>
                <div class="row mb-3">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Tiket</label>
                        <input type="text" class="form-control biaya-input" name="tiket" value="{{ number_format(old('tiket', $pejabat->tiket), 0, '', ',') }}" onkeyup="calculateJumlah()">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Transport</label>
                        <input type="text" class="form-control biaya-input" name="transport" value="{{ number_format(old('transport', $pejabat->transport), 0, '', ',') }}" onkeyup="calculateJumlah()">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Uang Harian</label>
                        <input type="text" class="form-control biaya-input" name="uang_harian" value="{{ number_format(old('uang_harian', $pejabat->uang_harian), 0, '', ',') }}" onkeyup="calculateJumlah()">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Penginapan</label>
                        <input type="text" class="form-control biaya-input" name="penginapan" value="{{ number_format(old('penginapan', $pejabat->penginapan), 0, '', ',') }}" onkeyup="calculateJumlah()">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Uang Representasi</label>
                        <input type="text" class="form-control biaya-input" name="uang_representasi" value="{{ number_format(old('uang_representasi', $pejabat->uang_representasi), 0, '', ',') }}" onkeyup="calculateJumlah()">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold">JUMLAH TOTAL</label>
                        <input type="text" class="form-control bg-light fw-bold" id="grandTotal" disabled value="0">
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-6">
                        <label class="form-label">Rekening Bank (Diterima)</label>
                        <input type="text" class="form-control" name="rekening" placeholder="Ex: 0001 (BRI)" value="{{ old('rekening', $pejabat->rekening) }}">
                    </div>
                </div>

                <div class="mt-4 text-end">
                    <a href="{{ route('perjaldins.index') }}" class="btn btn-light"><i class="bi bi-x-circle"></i> Batal</a>
                    <button type="submit" class="btn btn-primary px-4"><i class="bi bi-save"></i> Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('script')
<script>
    function formatNumber(n) {
        return n.toString().replace(/\D/g, "").replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    }

    window.calculateJumlah = function() {
        let total = 0;
        $('.biaya-input').each(function() {
            // format self
            let val = $(this).val();
            let rawNum = val.replace(/\D/g, "");
            $(this).val(rawNum ? formatNumber(rawNum) : '');

            let num = parseFloat(rawNum);
            if (!isNaN(num)) total += num;
        });
        $('#grandTotal').val(formatNumber(total));
    }

    $(document).ready(function() {
        calculateJumlah(); // initial calc
        
        // Auto fill Name and NIP when Employee selected
        $('#employeeSelect').on('change', function() {
            let selected = $(this).find('option:selected');
            if(selected.val() != '') {
                $('#employeeName').val(selected.data('name'));
                $('#employeeNip').val(selected.data('nip'));
            } else {
                $('#employeeName').val('');
                $('#employeeNip').val('');
            }
        });
    });
</script>
@endpush
