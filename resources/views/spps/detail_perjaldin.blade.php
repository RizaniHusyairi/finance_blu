@extends('layouts.app')
@section('title', 'Manajemen Multi-SPP Perjaldin')

@section('content')
    <x-page-title title="Pembuatan SPP" subtitle="Detail Multi-SPP Perjaldin" />

    @if(session('success'))
        <div class="alert alert-success border-0 bg-success alert-dismissible fade show text-white shadow-sm">
            <i class="bi bi-check-circle me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error') || $errors->any())
        <div class="alert alert-danger border-0 bg-danger alert-dismissible fade show shadow-sm text-white">
            <i class="bi bi-exclamation-triangle me-2"></i>
            {{ session('error') ?? $errors->first() }}
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Partials Hero Header -->
    @include('spps.partials.perjaldin_detail_hero')

    <!-- Partials Peserta Summary -->
    @include('spps.partials.perjaldin_peserta_summary')

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom py-3">
            <h5 class="mb-0 fw-bold"><i class="bi bi-ui-radios-grid text-primary me-2"></i>Rekap Item Biaya Perjaldin</h5>
            <p class="text-muted small mb-0 mt-1">Pilih COA dan kelola SPP untuk setiap item biaya secara terpisah.</p>
        </div>
        <div class="card-body bg-light">
            @php
                $komponens = $tagihan->komponenPerjaldin->where('total_nominal', '>', 0);
            @endphp
            
            @forelse($komponens as $komponen)
                @include('spps.partials.perjaldin_komponen_card')
            @empty
                <div class="text-center py-5">
                    <img src="{{ URL::asset('build/images/no-data.svg') }}" alt="No Data" class="mb-3" style="width: 120px; opacity: 0.5;">
                    <h6 class="text-muted fw-normal">Tidak ada item biaya yang tercatat untuk Perjaldin ini.</h6>
                </div>
            @endforelse
            
            <div class="mt-4">
                <a href="{{ route('spps.perjaldin.index') }}" class="btn btn-outline-secondary px-4 rounded-pill">
                    <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar
                </a>
            </div>
        </div>
    </div>
@endsection
