@extends('layouts.app')

@section('title', 'Detail Rekening Bank')

@php
    $jenis = $rekening->jenis_rekening?->value ?? (string) $rekening->jenis_rekening;
    $jenisLabel = \App\Enums\JenisRekening::tryFrom($jenis)?->label() ?? $jenis;
@endphp

@section('content')
    <x-page-title title="Detail Rekening Bank" subtitle="{{ $rekening->nama_bank }} — {{ $rekening->nomor_rekening }}" />

    @if(session('error'))
        <div class="alert alert-danger border-0 bg-danger alert-dismissible fade show shadow-sm">
            <div class="text-white">{{ session('error') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <a href="{{ route('rekening-bank.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar
        </a>
        <a href="{{ route('rekening-bank.edit', $rekening) }}" class="btn btn-warning">
            <i class="bi bi-pencil-square me-1"></i> Edit
        </a>
    </div>

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 rounded-4 h-100">
                <div class="card-header bg-white border-bottom-0 pt-4 px-4"><h5 class="mb-0 fw-bold">Informasi</h5></div>
                <div class="card-body px-4">
                    <table class="table table-sm">
                        <tr><th width="40%">Nama Bank</th><td>{{ $rekening->nama_bank }}</td></tr>
                        <tr><th>Nomor Rekening</th><td>{{ $rekening->nomor_rekening }}</td></tr>
                        <tr><th>Atas Nama</th><td>{{ $rekening->nama_rekening }}</td></tr>
                        <tr><th>Kode Bank</th><td>{{ $rekening->kode_bank ?: '-' }}</td></tr>
                        <tr><th>Pemilik</th><td>{{ $rekening->pemilik?->name ?? '-' }}</td></tr>
                        <tr><th>Jenis</th><td><span class="badge bg-info text-dark">{{ $jenisLabel }}</span></td></tr>
                        <tr><th>Default</th><td>{{ $rekening->is_default ? 'Ya' : 'Tidak' }}</td></tr>
                        <tr><th>Status</th><td><span class="badge {{ $rekening->status_aktif ? 'bg-success' : 'bg-secondary' }}">{{ $rekening->status_aktif ? 'Aktif' : 'Nonaktif' }}</span></td></tr>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 rounded-4 h-100">
                <div class="card-header bg-white border-bottom-0 pt-4 px-4"><h5 class="mb-0 fw-bold">Saldo & Aktivitas BKU</h5></div>
                <div class="card-body px-4">
                    <table class="table table-sm">
                        <tr><th width="50%">Saldo Awal</th><td class="text-end fw-semibold">Rp {{ number_format((float) $rekening->saldo_awal, 2, ',', '.') }}</td></tr>
                        <tr><th>Saldo Awal Per Tanggal</th><td class="text-end">{{ optional($rekening->saldo_awal_per_tanggal)->format('d-m-Y') ?? '-' }}</td></tr>
                        <tr><th>Total Penerimaan (BKU)</th><td class="text-end text-success">Rp {{ number_format((float) ($bkuStats->total_masuk ?? 0), 2, ',', '.') }}</td></tr>
                        <tr><th>Total Pengeluaran (BKU)</th><td class="text-end text-danger">Rp {{ number_format((float) ($bkuStats->total_keluar ?? 0), 2, ',', '.') }}</td></tr>
                        <tr><th>Saldo Akhir Terakhir</th><td class="text-end fw-bold">Rp {{ number_format((float) $saldoTerakhir, 2, ',', '.') }}</td></tr>
                        <tr><th>Jumlah Transaksi BKU</th><td class="text-end">{{ number_format((int) ($bkuStats->jumlah ?? 0)) }}</td></tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
