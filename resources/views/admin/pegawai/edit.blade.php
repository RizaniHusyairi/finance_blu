@extends('layouts.app')

@section('title', 'Edit Pegawai')

@push('css')
    @include('admin._partials.styles')
@endpush

@section('content')
    <x-page-title title="Data Pegawai" subtitle="Edit Pegawai" />

    <div class="admin-hero d-flex align-items-center gap-3 mb-4"
         style="background: linear-gradient(135deg, #0ea5e9, #6366f1, #8b5cf6);">
        <div class="hero-icon"><i class="material-icons-outlined">edit</i></div>
        <div>
            <h1>Edit Pegawai</h1>
            <p>{{ $pegawai->nama_lengkap }}</p>
        </div>
    </div>

    @include('admin._partials.flash')

    <form method="POST" action="{{ route('admin.pegawai.update', $pegawai) }}">
        @csrf
        @method('PUT')
        @include('admin.pegawai._form', ['pegawai' => $pegawai])
    </form>
@endsection
