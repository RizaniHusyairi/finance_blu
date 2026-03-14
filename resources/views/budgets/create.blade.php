@extends('layouts.app')
@section('title')
    Tambah Pagu Anggaran
@endsection
@section('content')
    <x-page-title title="Master Data" subtitle="Tambah Pagu Anggaran" />

    <div class="card">
        <div class="card-body">
            <h5 class="mb-4">Form Tambah Pagu Anggaran (DIPA)</h5>
            <form action="{{ route('budgets.store') }}" method="POST">
                @csrf
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="year" class="form-label">Tahun Anggaran <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('year') is-invalid @enderror" id="year" name="year" value="{{ old('year', date('Y')) }}" required>
                        @error('year') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="coa" class="form-label">Kode COA / MAK <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('coa') is-invalid @enderror" id="coa" name="coa" value="{{ old('coa') }}" required>
                        @error('coa') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label">Uraian Pagu <span class="text-danger">*</span></label>
                    <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="2" required>{{ old('description') }}</textarea>
                    @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="mb-4">
                    <label for="initial_budget" class="form-label">Nilai Pagu Awal (Rp) <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" class="form-control @error('initial_budget') is-invalid @enderror" id="initial_budget" name="initial_budget" value="{{ old('initial_budget') }}" required>
                    @error('initial_budget') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <h6 class="border-bottom pb-2">Detail Kode (Opsional)</h6>
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label for="program_code" class="form-label">Program</label>
                        <input type="text" class="form-control" id="program_code" name="program_code" value="{{ old('program_code') }}">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="activity_code" class="form-label">Kegiatan</label>
                        <input type="text" class="form-control" id="activity_code" name="activity_code" value="{{ old('activity_code') }}">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="output_code" class="form-label">Output/KRO</label>
                        <input type="text" class="form-control" id="output_code" name="output_code" value="{{ old('output_code') }}">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="suboutput_code" class="form-label">Suboutput/RO</label>
                        <input type="text" class="form-control" id="suboutput_code" name="suboutput_code" value="{{ old('suboutput_code') }}">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label for="component_code" class="form-label">Komponen</label>
                        <input type="text" class="form-control" id="component_code" name="component_code" value="{{ old('component_code') }}">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="subcomponent_code" class="form-label">Subkomponen</label>
                        <input type="text" class="form-control" id="subcomponent_code" name="subcomponent_code" value="{{ old('subcomponent_code') }}">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="account_code" class="form-label">Akun</label>
                        <input type="text" class="form-control" id="account_code" name="account_code" value="{{ old('account_code') }}">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="item_code" class="form-label">Item / Detail</label>
                        <input type="text" class="form-control" id="item_code" name="item_code" value="{{ old('item_code') }}">
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary px-4">Simpan</button>
                    <a href="{{ route('budgets.index') }}" class="btn btn-secondary px-4">Batal</a>
                </div>
            </form>
        </div>
    </div>
@endsection
