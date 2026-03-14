@extends('layouts.app')
@section('title')
    Tambah Mitra
@endsection
@section('content')
    <x-page-title title="Master Data" subtitle="Tambah Mitra" />

    <div class="card">
        <div class="card-body">
            <h5 class="mb-4">Form Tambah Mitra</h5>
            <form action="{{ route('suppliers.store') }}" method="POST">
                @csrf
                <div class="row mb-3">
                    <label for="name" class="col-sm-3 col-form-label">Nama Mitra / PT <span class="text-danger">*</span></label>
                    <div class="col-sm-9">
                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required>
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="row mb-3">
                    <label for="type" class="col-sm-3 col-form-label">Tipe Vendor</label>
                    <div class="col-sm-9">
                        <input type="text" class="form-control" id="type" name="type" value="{{ old('type') }}">
                    </div>
                </div>
                <div class="row mb-3">
                    <label for="address" class="col-sm-3 col-form-label">Alamat Lengkap</label>
                    <div class="col-sm-9">
                        <textarea class="form-control" id="address" name="address" rows="3">{{ old('address') }}</textarea>
                    </div>
                </div>
                <div class="row mb-3">
                    <label for="npwp" class="col-sm-3 col-form-label">NPWP</label>
                    <div class="col-sm-9">
                        <input type="text" class="form-control" id="npwp" name="npwp" value="{{ old('npwp') }}">
                    </div>
                </div>
                 <h6 class="mt-4 border-bottom pb-2">Informasi Bank</h6>
                <div class="row mb-3">
                    <label for="bank_name" class="col-sm-3 col-form-label">Nama Bank</label>
                    <div class="col-sm-9">
                        <input type="text" class="form-control" id="bank_name" name="bank_name" value="{{ old('bank_name') }}">
                    </div>
                </div>
                <div class="row mb-3">
                    <label for="bank_account" class="col-sm-3 col-form-label">Nomor Rekening</label>
                    <div class="col-sm-9">
                        <input type="text" class="form-control" id="bank_account" name="bank_account" value="{{ old('bank_account') }}">
                    </div>
                </div>
                <div class="row mb-3">
                    <label for="account_name" class="col-sm-3 col-form-label">Nama Pemilik Rekening</label>
                    <div class="col-sm-9">
                        <input type="text" class="form-control" id="account_name" name="account_name" value="{{ old('account_name') }}">
                    </div>
                </div>
                <div class="row mb-3">
                    <label for="phone" class="col-sm-3 col-form-label">Nomor Telepon</label>
                    <div class="col-sm-9">
                        <input type="text" class="form-control" id="phone" name="phone" value="{{ old('phone') }}">
                    </div>
                </div>
                <div class="row mt-4">
                    <label class="col-sm-3 col-form-label"></label>
                    <div class="col-sm-9">
                        <button type="submit" class="btn btn-primary px-4">Simpan</button>
                        <a href="{{ route('suppliers.index') }}" class="btn btn-secondary px-4">Batal</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
