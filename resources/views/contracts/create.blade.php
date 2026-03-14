@extends('layouts.app')
@section('title')
    Tambah Kontrak
@endsection
@section('content')
    <x-page-title title="Manajemen Kontrak" subtitle="Tambah Kontrak Baru" />

    <div class="card border-top border-4 border-primary">
        <div class="card-body p-5">
            <div class="card-title d-flex align-items-center mb-4">
                <div><i class="bi bi-file-earmark-text me-1 font-22 text-primary"></i></div>
                <h5 class="mb-0 text-primary">Form Input Kontrak</h5>
            </div>
            
            <form action="{{ route('contracts.store') }}" method="POST">
                @csrf
                
                <h6 class="mb-3">Informasi Umum</h6>
                <div class="row mb-3">
                    <label for="contract_number" class="col-sm-3 col-form-label">Nomor Kontrak <span class="text-danger">*</span></label>
                    <div class="col-sm-9">
                        <input type="text" class="form-control @error('contract_number') is-invalid @enderror" id="contract_number" name="contract_number" value="{{ old('contract_number') }}" required>
                        @error('contract_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="row mb-3">
                    <label for="date" class="col-sm-3 col-form-label">Tanggal Kontrak <span class="text-danger">*</span></label>
                    <div class="col-sm-9">
                        <input type="date" class="form-control @error('date') is-invalid @enderror" id="date" name="date" value="{{ old('date') }}" required>
                        @error('date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="row mb-3">
                    <label for="description" class="col-sm-3 col-form-label">Uraian / Pekerjaan <span class="text-danger">*</span></label>
                    <div class="col-sm-9">
                        <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3" required>{{ old('description') }}</textarea>
                        @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="row mb-4">
                    <label for="type" class="col-sm-3 col-form-label">Jenis Kontrak</label>
                    <div class="col-sm-9">
                        <select class="form-select @error('type') is-invalid @enderror" id="type" name="type">
                            <option value="">-- Pilih Jenis --</option>
                            <option value="Lumpsum" {{ old('type') == 'Lumpsum' ? 'selected' : '' }}>Lumpsum</option>
                            <option value="Harga Satuan" {{ old('type') == 'Harga Satuan' ? 'selected' : '' }}>Harga Satuan</option>
                            <option value="Gabungan" {{ old('type') == 'Gabungan' ? 'selected' : '' }}>Gabungan Lumpsum & Harga Satuan</option>
                        </select>
                        @error('type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <hr>
                <h6 class="mb-3 mt-4">Keterkaitan Master Data</h6>
                
                <div class="row mb-3">
                    <label for="supplier_id" class="col-sm-3 col-form-label">Penyedia / Mitra <span class="text-danger">*</span></label>
                    <div class="col-sm-9">
                        <select class="form-select @error('supplier_id') is-invalid @enderror" id="supplier_id" name="supplier_id" required>
                            <option value="">-- Pilih Mitra --</option>
                            @foreach($suppliers as $supplier)
                                <option value="{{ $supplier->id }}" {{ old('supplier_id') == $supplier->id ? 'selected' : '' }}>{{ $supplier->name }} {{ $supplier->type ? '('.$supplier->type.')' : '' }}</option>
                            @endforeach
                        </select>
                        @error('supplier_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="row mb-4">
                    <label for="budget_id" class="col-sm-3 col-form-label">Beban Anggaran (COA) <span class="text-danger">*</span></label>
                    <div class="col-sm-9">
                        <select class="form-select @error('budget_id') is-invalid @enderror" id="budget_id" name="budget_id" required>
                            <option value="">-- Pilih Pagu Anggaran --</option>
                            @foreach($budgets as $budget)
                                <option value="{{ $budget->id }}" {{ old('budget_id') == $budget->id ? 'selected' : '' }}>
                                    {{ $budget->year }} - {{ $budget->coa }} : Sisa Rp {{ number_format($budget->remaining_budget, 0, ',', '.') }}
                                </option>
                            @endforeach
                        </select>
                        @error('budget_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <hr>
                <h6 class="mb-3 mt-4">Nilai & Jangka Waktu</h6>

                <div class="row mb-3">
                    <label for="total_amount" class="col-sm-3 col-form-label">Nilai Kontrak (Rp) <span class="text-danger">*</span></label>
                    <div class="col-sm-9">
                        <input type="number" step="0.01" class="form-control @error('total_amount') is-invalid @enderror" id="total_amount" name="total_amount" value="{{ old('total_amount') }}" required>
                        @error('total_amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="row mb-3">
                    <label for="start_date" class="col-sm-3 col-form-label">Tanggal Mulai <span class="text-danger">*</span></label>
                    <div class="col-sm-9">
                        <input type="date" class="form-control @error('start_date') is-invalid @enderror" id="start_date" name="start_date" value="{{ old('start_date') }}" required>
                        @error('start_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="row mb-3">
                    <label for="end_date" class="col-sm-3 col-form-label">Tanggal Berakhir <span class="text-danger">*</span></label>
                    <div class="col-sm-9">
                        <input type="date" class="form-control @error('end_date') is-invalid @enderror" id="end_date" name="end_date" value="{{ old('end_date') }}" required>
                        @error('end_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="row mb-4">
                    <label for="status" class="col-sm-3 col-form-label">Status Kontrak <span class="text-danger">*</span></label>
                    <div class="col-sm-9">
                        <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
                            <option value="Draft" {{ old('status') == 'Draft' ? 'selected' : '' }}>Draft</option>
                            <option value="Active" {{ old('status') == 'Active' ? 'selected' : '' }}>Aktif</option>
                            <option value="Completed" {{ old('status') == 'Completed' ? 'selected' : '' }}>Selesai</option>
                            <option value="Terminated" {{ old('status') == 'Terminated' ? 'selected' : '' }}>Batal/Terminated</option>
                        </select>
                        @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="row">
                    <label class="col-sm-3 col-form-label"></label>
                    <div class="col-sm-9">
                        <button type="submit" class="btn btn-primary px-4">Simpan Kontrak</button>
                        <a href="{{ route('contracts.index') }}" class="btn btn-secondary px-4">Batal</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
