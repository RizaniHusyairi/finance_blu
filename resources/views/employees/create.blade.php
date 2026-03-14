@extends('layouts.app')
@section('title')
    Tambah Pegawai
@endsection
@section('content')
    <x-page-title title="Master Data" subtitle="Tambah Pegawai" />

    <div class="card">
        <div class="card-body">
            <h5 class="mb-4">Form Tambah Pegawai</h5>
            <form action="{{ route('employees.store') }}" method="POST">
                @csrf
                <div class="row mb-3">
                    <label for="name" class="col-sm-3 col-form-label">Nama Lengkap <span class="text-danger">*</span></label>
                    <div class="col-sm-9">
                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required>
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="row mb-3">
                    <label for="nip" class="col-sm-3 col-form-label">NIP</label>
                    <div class="col-sm-9">
                        <input type="text" class="form-control @error('nip') is-invalid @enderror" id="nip" name="nip" value="{{ old('nip') }}">
                        @error('nip') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="row mb-3">
                    <label for="position" class="col-sm-3 col-form-label">Jabatan</label>
                    <div class="col-sm-9">
                        <input type="text" class="form-control @error('position') is-invalid @enderror" id="position" name="position" value="{{ old('position') }}">
                        @error('position') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="row">
                    <label class="col-sm-3 col-form-label"></label>
                    <div class="col-sm-9">
                        <button type="submit" class="btn btn-primary px-4">Simpan</button>
                        <a href="{{ route('employees.index') }}" class="btn btn-secondary px-4">Batal</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
