@extends('layouts.app')

@section('title', 'Rekening Bank')

@section('content')
    <x-page-title title="Rekening Bank" subtitle="Kelola rekening bank, jenis (penerimaan/pengeluaran), dan saldo awal pembukuan" />

    @if(session('success'))
        <div class="alert alert-success border-0 bg-success alert-dismissible fade show shadow-sm">
            <div class="text-white">{{ session('success') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger border-0 bg-danger alert-dismissible fade show shadow-sm">
            <div class="text-white">{{ session('error') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row g-3 mb-4">
        <div class="col-md-3 col-6">
            <div class="card shadow-sm border-0 rounded-4 h-100"><div class="card-body">
                <div class="small text-muted">Total Rekening</div>
                <div class="fs-4 fw-bold">{{ number_format($summary['total']) }}</div>
            </div></div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card shadow-sm border-0 rounded-4 h-100"><div class="card-body">
                <div class="small text-muted">Aktif</div>
                <div class="fs-4 fw-bold text-success">{{ number_format($summary['aktif']) }}</div>
            </div></div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card shadow-sm border-0 rounded-4 h-100"><div class="card-body">
                <div class="small text-muted">Rek. Penerimaan</div>
                <div class="fs-4 fw-bold">{{ number_format($summary['penerimaan']) }}</div>
            </div></div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card shadow-sm border-0 rounded-4 h-100"><div class="card-body">
                <div class="small text-muted">Rek. Pengeluaran</div>
                <div class="fs-4 fw-bold">{{ number_format($summary['pengeluaran']) }}</div>
            </div></div>
        </div>
    </div>

    <div class="card shadow-sm border-0 rounded-4">
        <div class="card-header bg-white border-bottom-0 pt-4 px-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="mb-0 fw-bold">Daftar Rekening</h5>
            <a href="{{ route('rekening-bank.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle me-1"></i> Tambah Rekening
            </a>
        </div>
        <div class="card-body px-4">
            <form method="GET" action="{{ route('rekening-bank.index') }}" class="row g-2 mb-4">
                <div class="col-md-5">
                    <input type="text" name="search" class="form-control" placeholder="Cari bank / nomor / atas nama" value="{{ request('search') }}">
                </div>
                <div class="col-md-3">
                    <select name="jenis_rekening" class="form-select">
                        <option value="">Semua Jenis</option>
                        @foreach($jenisOptions as $val => $label)
                            <option value="{{ $val }}" @selected(request('jenis_rekening') === $val)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="status_aktif" class="form-select">
                        <option value="">Semua Status</option>
                        <option value="aktif" @selected(request('status_aktif') === 'aktif')>Aktif</option>
                        <option value="nonaktif" @selected(request('status_aktif') === 'nonaktif')>Nonaktif</option>
                    </select>
                </div>
                <div class="col-md-2 d-grid">
                    <button type="submit" class="btn btn-outline-primary"><i class="bi bi-search me-1"></i> Filter</button>
                </div>
            </form>

            @include('rekening_bank._table')
        </div>
    </div>
@endsection
