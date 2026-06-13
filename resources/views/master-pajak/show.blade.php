@extends('layouts.app')

@section('title', 'Detail Pajak — ' . ($pajak->kode_pajak ?? $pajak->jenis_pajak))

@php
    $today = \Carbon\Carbon::today();
    $mulai = $pajak->berlaku_mulai;
    $sampai = $pajak->berlaku_sampai;

    // Format persentase rapi: 11.0000 -> 11%, 1.5000 -> 1,5%
    $persen = rtrim(rtrim(number_format($pajak->persentase, 4, ',', '.'), '0'), ',') . '%';

    // Hitung kondisi berlaku
    if (!$mulai && !$sampai) {
        $kondisiBerlaku = 'Tidak dibatasi periode';
        $kondisiBadge = 'bg-secondary';
    } elseif ($mulai && $mulai->gt($today)) {
        $kondisiBerlaku = 'Belum Berlaku';
        $kondisiBadge = 'bg-warning text-dark';
    } elseif ($sampai && $sampai->lt($today)) {
        $kondisiBerlaku = 'Sudah Berakhir';
        $kondisiBadge = 'bg-danger';
    } elseif ($mulai && !$sampai) {
        $kondisiBerlaku = 'Sedang Berlaku (Tanpa Batas Akhir)';
        $kondisiBadge = 'bg-info text-dark';
    } else {
        $kondisiBerlaku = 'Sedang Berlaku';
        $kondisiBadge = 'bg-success';
    }
@endphp

@section('content')
    <x-page-title title="Detail Pajak" subtitle="Informasi lengkap tarif pajak" />

    @if(session('success'))
        <div class="alert alert-success border-0 bg-success alert-dismissible fade show shadow-sm">
            <div class="text-white">{{ session('success') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Header dengan Tombol Aksi --}}
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <div>
            <h5 class="mb-1 fw-bold">Detail Pajak</h5>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 small">
                    <li class="breadcrumb-item"><a href="{{ route('master-pajak.index') }}" class="text-decoration-none">Master Data</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('master-pajak.index') }}" class="text-decoration-none">Pajak</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $pajak->kode_pajak ?? 'Detail' }}</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('master-pajak.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar
            </a>
            <a href="{{ route('master-pajak.edit', $pajak) }}" class="btn btn-primary">
                <i class="bi bi-pencil me-1"></i> Edit Pajak
            </a>
            <form action="{{ route('master-pajak.toggle', $pajak) }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn {{ $pajak->status_aktif ? 'btn-outline-warning' : 'btn-outline-success' }}" onclick="return confirm('{{ $pajak->status_aktif ? 'Nonaktifkan' : 'Aktifkan' }} tarif pajak ini?')">
                    <i class="bi {{ $pajak->status_aktif ? 'bi-pause-circle' : 'bi-play-circle' }} me-1"></i>
                    {{ $pajak->status_aktif ? 'Nonaktifkan' : 'Aktifkan' }}
                </button>
            </form>
        </div>
    </div>

    {{-- Card Ringkasan Utama --}}
    <div class="card shadow-sm border-0 rounded-4 mb-4" style="background: linear-gradient(135deg, #f8f9fc, #eef2ff); border-left: 4px solid #4361ee !important;">
        <div class="card-body p-4">
            <div class="row align-items-center g-3">
                <div class="col-md-3">
                    <div class="small text-muted mb-1">Kode Pajak</div>
                    <div class="fw-bold fs-4 text-primary">{{ $pajak->kode_pajak ?? '-' }}</div>
                </div>
                <div class="col-md-3">
                    <div class="small text-muted mb-1">Jenis Pajak</div>
                    <div class="fw-bold fs-5">{{ $pajak->jenis_pajak }}</div>
                </div>
                <div class="col-md-2">
                    <div class="small text-muted mb-1">Persentase</div>
                    <div class="fw-bold fs-4 text-primary">{{ $persen }}</div>
                </div>
                <div class="col-md-2">
                    <div class="small text-muted mb-1">Status</div>
                    <div>
                        <span class="badge {{ $pajak->status_aktif ? 'bg-success' : 'bg-secondary' }} fs-6 px-3 py-2">
                            {{ $pajak->status_aktif ? 'Aktif' : 'Nonaktif' }}
                        </span>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="small text-muted mb-1">Kondisi Berlaku</div>
                    <div>
                        <span class="badge {{ $kondisiBadge }} px-3 py-2" style="font-size: 11px;">{{ $kondisiBerlaku }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Layout Dua Kolom --}}
    <div class="row g-4 mb-4">

        {{-- KOLOM KIRI — Informasi Pajak --}}
        <div class="col-xl-6">
            <div class="card shadow-sm border-0 rounded-4 h-100">
                <div class="card-header bg-white border-bottom-0 pt-4 px-4">
                    <h5 class="mb-1 fw-bold"><i class="material-icons-outlined align-middle me-1 text-primary" style="font-size: 20px;">receipt</i> Informasi Utama</h5>
                </div>
                <div class="card-body px-4 pb-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="border rounded-4 p-3 h-100 bg-light">
                                <div class="small text-muted mb-1">Kode Pajak</div>
                                <div class="fw-bold fs-5">{{ $pajak->kode_pajak ?? '-' }}</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded-4 p-3 h-100 bg-light">
                                <div class="small text-muted mb-1">Jenis Pajak</div>
                                <div class="fw-bold fs-5">{{ $pajak->jenis_pajak }}</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded-4 p-3 h-100 bg-light">
                                <div class="small text-muted mb-1">Persentase</div>
                                <div class="fw-bold fs-4 text-primary">{{ $persen }}</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded-4 p-3 h-100 bg-light">
                                <div class="small text-muted mb-1">KAP / KJS (Kode Billing)</div>
                                @if($pajak->kode_akun_pajak || $pajak->kode_jenis_setoran)
                                    <div class="fw-bold fs-5 font-monospace">{{ $pajak->kode_akun_pajak ?? '—' }} / {{ $pajak->kode_jenis_setoran ?? '—' }}</div>
                                    <div class="small text-muted mt-1">Kode Akun Pajak / Kode Jenis Setoran</div>
                                @else
                                    <div class="text-muted fst-italic">Belum diisi</div>
                                @endif
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="border rounded-4 p-3 bg-light">
                                <div class="small text-muted mb-1">Rumus Perhitungan</div>
                                @if($pajak->rumus)
                                    <div class="fw-semibold" style="white-space: pre-wrap;">{{ $pajak->rumus }}</div>
                                @else
                                    <div class="text-muted fst-italic">Belum diisi</div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- KOLOM KANAN — Masa Berlaku & Status --}}
        <div class="col-xl-6">

            {{-- Card Masa Berlaku --}}
            <div class="card shadow-sm border-0 rounded-4 mb-4">
                <div class="card-header bg-white border-bottom-0 pt-4 px-4">
                    <h5 class="mb-1 fw-bold"><i class="material-icons-outlined align-middle me-1 text-info" style="font-size: 20px;">date_range</i> Masa Berlaku</h5>
                </div>
                <div class="card-body px-4 pb-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="border rounded-4 p-3 h-100 bg-light">
                                <div class="small text-muted mb-1">Berlaku Mulai</div>
                                <div class="fw-bold">{{ $mulai ? $mulai->format('d-m-Y') : 'Tidak ditentukan' }}</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded-4 p-3 h-100 bg-light">
                                <div class="small text-muted mb-1">Berlaku Sampai</div>
                                <div class="fw-bold">{{ $sampai ? $sampai->format('d-m-Y') : 'Tanpa batas akhir' }}</div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="border rounded-4 p-3 bg-light d-flex align-items-center gap-2">
                                <div class="small text-muted">Kondisi Saat Ini:</div>
                                <span class="badge {{ $kondisiBadge }} px-3 py-2">{{ $kondisiBerlaku }}</span>
                                @if($mulai && $sampai)
                                    <span class="text-muted small ms-2">{{ $mulai->format('d-m-Y') }} s/d {{ $sampai->format('d-m-Y') }}</span>
                                @elseif($mulai && !$sampai)
                                    <span class="text-muted small ms-2">Berlaku sejak {{ $mulai->format('d-m-Y') }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Card Status Data --}}
            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-header bg-white border-bottom-0 pt-4 px-4">
                    <h5 class="mb-1 fw-bold"><i class="material-icons-outlined align-middle me-1 text-success" style="font-size: 20px;">info</i> Status Data</h5>
                </div>
                <div class="card-body px-4 pb-4">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="border rounded-4 p-3 h-100 bg-light text-center">
                                <div class="small text-muted mb-1">Status Aktif</div>
                                <span class="badge {{ $pajak->status_aktif ? 'bg-success' : 'bg-secondary' }} fs-6 px-3 py-2">
                                    {{ $pajak->status_aktif ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border rounded-4 p-3 h-100 bg-light">
                                <div class="small text-muted mb-1">Dibuat Pada</div>
                                <div class="fw-bold">{{ $pajak->created_at ? $pajak->created_at->format('d-m-Y H:i') : '-' }}</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border rounded-4 p-3 h-100 bg-light">
                                <div class="small text-muted mb-1">Terakhir Diperbarui</div>
                                <div class="fw-bold">{{ $pajak->updated_at ? $pajak->updated_at->format('d-m-Y H:i') : '-' }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    {{-- Panel Catatan Penggunaan --}}
    <div class="card shadow-sm border-0 rounded-4 mb-4">
        <div class="card-body p-4">
            <h6 class="fw-bold mb-3"><i class="material-icons-outlined align-middle me-1 text-warning" style="font-size: 18px;">lightbulb</i> Catatan Penggunaan</h6>
            <ul class="mb-0 text-muted" style="font-size: 13px; line-height: 1.8;">
                <li>Tarif pajak ini digunakan sebagai <strong>referensi perhitungan</strong> pada dokumen SPP/SPM dan potongan tagihan.</li>
                <li>Jika terjadi perubahan tarif (misalnya kenaikan PPN), <strong>disarankan membuat data tarif baru</strong> daripada menimpa data lama agar histori tetap terjaga.</li>
                <li>Data pajak yang sudah tidak digunakan sebaiknya <strong>dinonaktifkan</strong>, bukan dihapus, agar referensi dokumen lama tetap valid.</li>
                <li>Kolom <em>Rumus</em> bersifat catatan referensi untuk operator dan auditor, tidak dieksekusi langsung oleh sistem.</li>
            </ul>
        </div>
    </div>
@endsection
