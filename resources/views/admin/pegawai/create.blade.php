@extends('layouts.app')

@section('title', 'Tambah Pegawai')

@push('css')
    @include('admin._partials.styles')
@endpush

@section('content')
    <x-page-title title="Data Pegawai" subtitle="Tambah Pegawai" />

    <div class="admin-hero d-flex align-items-center gap-3 mb-4"
         style="background: linear-gradient(135deg, #0ea5e9, #6366f1, #8b5cf6);">
        <div class="hero-icon"><i class="material-icons-outlined">person_add_alt</i></div>
        <div>
            <h1>Tambah Pegawai</h1>
            <p>Isi data dasar pegawai. Akun login bisa dibuat setelahnya melalui menu Manajemen User.</p>
        </div>
    </div>

    @include('admin._partials.flash')

    <form method="POST" action="{{ route('admin.pegawai.store') }}">
        @csrf
        @include('admin.pegawai._form', ['pegawai' => $pegawai])
    </form>
@endsection
