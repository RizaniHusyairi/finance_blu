@extends('layouts.app')

@section('title', 'Edit Rekening Bank')

@section('content')
    <x-page-title title="Edit Rekening Bank" subtitle="Perbarui data rekening, jenis, dan saldo awal" />

    <div class="d-flex justify-content-end mb-3">
        <a href="{{ route('rekening-bank.show', $rekening) }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Kembali ke Detail
        </a>
    </div>

    <form action="{{ route('rekening-bank.update', $rekening) }}" method="POST">
        @csrf
        @method('PUT')
        @include('rekening_bank._form', ['rekening' => $rekening])

        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-body p-4 d-flex justify-content-end flex-wrap gap-2">
                <a href="{{ route('rekening-bank.show', $rekening) }}" class="btn btn-outline-secondary px-4">Batal</a>
                <button type="submit" class="btn btn-primary px-4"><i class="bi bi-save me-1"></i> Simpan Perubahan</button>
            </div>
        </div>
    </form>
@endsection
