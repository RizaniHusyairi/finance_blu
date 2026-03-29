@extends('layouts.app')
@section('title', 'Buat Addendum Kontrak')
@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-0 text-uppercase fw-bold">Buat Addendum Kontrak</h4>
            <div class="small text-muted mt-1">Ref Kontrak Asal: <span class="fw-bold text-primary">{{ $contract->contract_number ?? 'Draft' }}</span></div>
        </div>
        <div>
            <a href="{{ route('contracts.show', $contract->id) }}" class="btn btn-outline-secondary px-4 fw-bold shadow-sm">
                <i class="bi bi-arrow-left me-1"></i> Kembali ke Kontrak
            </a>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger border-0 bg-danger alert-dismissible fade show text-white shadow-sm">
            <div class="fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill me-2"></i>Terdapat Kesalahan:</div>
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <form action="{{ route('addendums.store', $contract->id) }}" method="POST">
        @csrf
        
        <div class="card rounded-4 shadow-sm border-0 mb-4">
            <div class="card-header bg-transparent border-bottom py-3">
                <h6 class="mb-0 fw-bold"><i class="bi bi-file-earmark-plus me-2 text-primary"></i>Detail Addendum</h6>
            </div>
            <div class="card-body p-4">
                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Nomor Addendum <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="addendum_number" value="{{ old('addendum_number') }}" required placeholder="Contoh: ADD-01/KONTRAK/2026">
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Tanggal Addendum <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" name="date" value="{{ old('date') }}" required>
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold">Keterangan / Alasan Addendum <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="reason" rows="3" required placeholder="Jelaskan perubahan apa yang dilakukan dan mengapa...">{{ old('reason') }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="card rounded-4 shadow-sm border-0 mb-4">
            <div class="card-header bg-transparent border-bottom py-3">
                <h6 class="mb-0 fw-bold"><i class="bi bi-percent me-2 text-warning"></i>Perubahan Nilai & Waktu (Opsional)</h6>
            </div>
            <div class="card-body p-4">
                <div class="alert alert-warning border-0 bg-warning-subtle text-warning-emphasis mb-4">
                    <i class="bi bi-info-circle-fill me-2"></i><strong>Informasi:</strong> Kosongkan field di bawah jika addendum tidak mengubah nilai kontrak atau waktu pelaksanaan kontrak utama.
                </div>
                
                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Nilai Total Keseluruhan Baru (Rp)</label>
                        <input type="number" step="0.01" class="form-control" name="new_total_amount" value="{{ old('new_total_amount') }}" placeholder="Contoh: 150000000">
                        <div class="form-text">Nilai asal kontrak ini: <strong>Rp {{ number_format($contract->total_amount, 0, ',', '.') }}</strong></div>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Tanggal Selesai Pelaksanaan Baru</label>
                        <input type="date" class="form-control" name="new_end_date" value="{{ old('new_end_date') }}">
                        <div class="form-text">Tanggal selesai asal kontrak ini: <strong>{{ $contract->end_date ? \Carbon\Carbon::parse($contract->end_date)->format('d/m/Y') : '-' }}</strong></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card rounded-4 shadow-sm border-0">
            <div class="card-body p-4 d-flex justify-content-end align-items-center gap-2">
                <a href="{{ route('contracts.show', $contract->id) }}" class="btn btn-outline-secondary px-4 fw-bold">Batal</a>
                <button type="submit" class="btn btn-primary px-5 fw-bold"><i class="bi bi-save me-2"></i>Simpan Addendum</button>
            </div>
        </div>

    </form>
@endsection
