@extends('layouts.app')
@section('title', 'Form Standing Instruction')
@section('content')
@php
    $si = $spp->standingInstruction;
    $tanggalSuratValue = $si?->tanggal_surat
        ? $si->tanggal_surat->format('Y-m-d')
        : date('Y-m-d');
@endphp
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1"><a href="{{ $returnUrl }}" class="text-decoration-none text-muted"><i class="bi bi-arrow-left"></i> Kembali</a> | Standing Instruction</h4>
            <div class="text-muted small">SPP: {{ $spp->nomor_spp }} - {{ $spp->jenis_tagihan }}</div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($warningNominal)
        <div class="alert alert-warning mb-4">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> Peringatan: Nominal Standing Instruction (Rp {{ number_format($si?->nominal_transfer ?? 0, 0, ',', '.') }}) berbeda dengan nominal SPP saat ini (Rp {{ number_format($spp->nominal_spp, 0, ',', '.') }}). Jika Anda menyimpan ulang, nominal akan disesuaikan dan status kembali menjadi DRAFT.
        </div>
    @endif

    <div class="row">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0 fw-bold">Form Data Surat</h6>
                </div>
                <div class="card-body">
                    <form action="{{ route('standing-instructions.store', $spp->id) }}" method="POST">
                        @csrf
                        <input type="hidden" name="return_to" value="{{ $returnUrl }}">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Nomor Surat</label>
                            <input type="text" name="nomor_surat" class="form-control" value="{{ old('nomor_surat', $si?->nomor_surat ?? '') }}" placeholder="Contoh: SI/001/UPBU-APT/2026">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Tanggal Surat</label>
                            <input type="date" name="tanggal_surat" class="form-control" value="{{ old('tanggal_surat', $tanggalSuratValue) }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Kuasa Pengguna Anggaran (KPA)</label>
                            <input type="text" class="form-control bg-light" value="{{ $kpaUser ? $kpaUser->name : 'USER KPA BELUM DITENTUKAN' }}" readonly>
                            @if(!$kpaUser)
                                <div class="text-danger small mt-1"><i class="bi bi-x-circle"></i> Sistem tidak dapat menemukan user dengan Role KPA. Mohon hubungi Super Admin.</div>
                            @endif
                        </div>

                        <hr>
                        <h6 class="fw-bold mb-3">Sumber Dana</h6>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Nomor Rekening Sumber</label>
                            <input type="text" name="rekening_sumber_nomor" class="form-control" value="{{ old('rekening_sumber_nomor', $si?->rekening_sumber_nomor ?? '') }}" required placeholder="Contoh: 2001302887451">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Nama Rekening Sumber</label>
                            <input type="text" name="rekening_sumber_nama" class="form-control" value="{{ old('rekening_sumber_nama', $si?->rekening_sumber_nama ?? '') }}" required placeholder="Contoh: RPL 046 BLU APT PRANOTO UNTUK OPS">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Nama Bank Sumber</label>
                            <input type="text" name="rekening_sumber_bank" class="form-control" value="{{ old('rekening_sumber_bank', $si?->rekening_sumber_bank ?? '') }}" required placeholder="Contoh: Bank BTN KC Samarinda">
                        </div>

                        <hr>
                        <h6 class="fw-bold mb-3">Tujuan Transfer</h6>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Nomor Rekening Tujuan</label>
                            <input type="text" name="rekening_tujuan_nomor" class="form-control" value="{{ old('rekening_tujuan_nomor', $si?->rekening_tujuan_nomor ?? '') }}" required placeholder="Contoh: 1234567890">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Nama Pemilik Rekening</label>
                            <input type="text" name="rekening_tujuan_nama" class="form-control" value="{{ old('rekening_tujuan_nama', $si?->rekening_tujuan_nama ?? '') }}" required placeholder="Atas Nama (Sesuai Buku Tabungan)">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Nama Bank</label>
                            <input type="text" name="rekening_tujuan_bank" class="form-control" value="{{ old('rekening_tujuan_bank', $si?->rekening_tujuan_bank ?? '') }}" required placeholder="Contoh: Bank Mandiri KCP Samarinda">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Nominal Transfer</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number" name="nominal_transfer" class="form-control" value="{{ old('nominal_transfer', $si?->nominal_transfer ?? $spp->nominal_spp) }}" step="0.01">
                            </div>
                            <div class="form-text">Biarkan sesuai default untuk memakai nominal SPP saat ini.</div>
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-bold">Uraian Penggunaan</label>
                            <textarea name="uraian_penggunaan" class="form-control" rows="3" placeholder="Contoh: kegiatan operasional Bandar Udara">{{ old('uraian_penggunaan', $si?->uraian_penggunaan ?? 'kegiatan operasional Bandar Udara') }}</textarea>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary fw-bold"><i class="bi bi-save me-1"></i> Simpan Draft Standing Instruction</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card border-0 shadow-sm sticky-top" style="top: 20px;">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold">Preview / PDF</h6>
                    @if($si)
                        <a href="{{ route('standing-instructions.print', $spp->id) }}" target="_blank" class="btn btn-sm btn-outline-secondary"><i class="bi bi-box-arrow-up-right me-1"></i> Buka Tab Baru</a>
                    @endif
                </div>
                <div class="card-body p-0">
                    @if($si)
                        <iframe src="{{ route('standing-instructions.print', $spp->id) }}" width="100%" height="750px" style="border: none;"></iframe>
                    @else
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-file-earmark-pdf" style="font-size: 4rem;"></i>
                            <p class="mt-3">Simpan form terlebih dahulu untuk melihat preview PDF.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
