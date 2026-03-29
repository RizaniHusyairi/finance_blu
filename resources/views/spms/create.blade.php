@extends('layouts.app')
@section('title') Buat SPM Perjaldin @endsection
@section('content')
<x-page-title title="Formulir SPM" subtitle="Nomor SPP: {{ $spp->nomor_spp }}" />

<div class="row">
    <div class="col-12 col-lg-8 mx-auto">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0 text-white"><i class="bi bi-pencil-square"></i> Terbitkan SPM Baru</h5>
            </div>
            <div class="card-body p-4 text-start">
                
                @if($spp->catatan_revisi && $spp->status_spp == 'Revisi SPM')
                    <div class="alert alert-danger px-3 py-2 border-start border-4 border-danger">
                        <strong class="text-danger"><i class="bi bi-exclamation-triangle-fill"></i> Catatan Penolakan PPSPM Sebelumnya:</strong>
                        <p class="mb-0 mt-1 fst-italic">{{ $spp->catatan_revisi }}</p>
                    </div>
                @endif

                <div class="row mb-4 bg-light p-3 rounded">
                    <div class="col-md-6">
                        <small class="text-muted d-block">Dasar Pembayaran SPP</small>
                        <strong class="text-dark">{{ $spp->nomor_spp }}</strong>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <small class="text-muted d-block">Nominal SPP (Rp)</small>
                        <strong class="text-success fs-5">{{ number_format($spp->jumlah_uang, 0, ',', '.') }}</strong>
                    </div>
                </div>

                <form method="POST" action="{{ route('spms.store', $spp->spp_id) }}">
                    @csrf
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Nomor SPM <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="nomor_spm" required 
                                   value="{{ old('nomor_spm', $spp->nomor_spm ?? str_replace('SPP', 'SPM', $spp->nomor_spp)) }}" 
                                   placeholder="Contoh: SPM-BLU/APTP-2026/0032">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Tanggal SPM <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="tanggal_spm" required 
                                   value="{{ old('tanggal_spm', $spp->tanggal_spm ?? date('Y-m-d')) }}">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold">Pilih Pejabat Penandatangan SPM (PPSPM / KPA) <span class="text-danger">*</span></label>
                        <select class="form-select" name="ppspm_id" required>
                            <option value="">-- Silakan Pilih Pejabat Berwenang --</option>
                            @foreach($ppspms as $pejabat)
                                <option value="{{ $pejabat->id }}">{{ $pejabat->name }} - NIP. {{ $pejabat->nip ?? 'Tidak ada NIP' }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted mt-1"><i class="bi bi-info-circle"></i> SPM ini akan dikirim agar disetujui secara digital oleh pejabat yang Anda pilih di atas.</small>
                    </div>

                    <hr>
                    <div class="d-flex justify-content-between">
                        <a href="{{ route('spms.index') }}" class="btn btn-secondary px-4"><i class="bi bi-arrow-left"></i> Batal</a>
                        <button type="submit" class="btn btn-success px-4" onclick="return confirm('Apakah nomor dan tanggal SPM sudah benar? Data akan diajukan ke meja PPSPM.')"><i class="bi bi-send-check"></i> Ajukan SPM ke PPSPM</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
