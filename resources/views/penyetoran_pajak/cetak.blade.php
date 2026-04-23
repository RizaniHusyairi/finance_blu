@extends('layouts.app')
@section('title', 'Cetak Penyetoran Pajak')

@section('content')
    <div class="text-center py-5">
        <i class="bi bi-printer fs-1 text-muted d-block mb-3"></i>
        <h5 class="fw-bold text-dark">Halaman Cetak Penyetoran Pajak</h5>
        <p class="text-muted">Fitur cetak sedang dalam pengembangan.</p>
        <a href="{{ route('pajak-potongan.detail', $potongan->id) }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-arrow-left me-1"></i> Kembali ke Detail</a>
    </div>
@endsection
