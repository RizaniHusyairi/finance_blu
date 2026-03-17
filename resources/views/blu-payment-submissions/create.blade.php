@extends('layouts.app')

@section('title')
    Tambah Pengajuan Pembayaran BLU
@endsection

@section('content')
    @php
        $oldPotongan = old('potongan', []);
        if (empty($oldPotongan)) {
            $oldPotongan = [
                ['jenis_potongan' => '', 'akun_potongan' => '', 'jumlah_potongan' => '']
            ];
        }
    @endphp

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h5 class="mb-0 fw-bold">Tambah Pengajuan Pembayaran BLU</h5>
            <small class="text-muted">Input data awal pengajuan pembayaran BLU oleh Operator BLU</small>
        </div>
        <a href="{{ route('blu-payment-submissions.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
    </div>

    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('blu-payment-submissions.index') }}">Pengajuan Pembayaran BLU</a></li>
            <li class="breadcrumb-item active" aria-current="page">Tambah</li>
        </ol>
    </nav>

    @if ($errors->any())
        <div class="alert alert-danger rounded-4">
            <div class="fw-semibold mb-1">Terdapat kesalahan pada form:</div>
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('blu-payment-submissions.store') }}" method="POST" id="formPengajuanBlu">
        @csrf

        <input type="hidden" name="submit_mode" id="submit_mode" value="{{ old('submit_mode', 'draft') }}">
        <input type="hidden" name="document_type" id="document_type" value="{{ old('document_type', 'pengajuan') }}">
        <input type="hidden" name="workflow_status" id="workflow_status" value="{{ old('workflow_status', 'draft') }}">

        <div class="row">
            {{-- LEFT COLUMN --}}
            <div class="col-lg-8">

                {{-- A. Data SPP --}}
                <div class="card rounded-4 border-top border-4 border-primary shadow-sm mb-4">
                    <div class="card-body p-4">
                        <h6 class="fw-bold text-primary mb-4">
                            <i class="bi bi-file-earmark-text me-2"></i>A. Data SPP
                        </h6>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Nomor SPP <span class="text-danger">*</span></label>
                                <input type="text" name="nomor_spp" id="nomor_spp"
                                       class="form-control bg-light @error('nomor_spp') is-invalid @enderror"
                                       value="{{ old('nomor_spp', $nextSppNumber) }}"
                                       readonly>
                                <small class="text-muted">Nomor SPP otomatis. Format: SPP-BLU/APTP-{{ date('Y') }}/NNNN</small>
                                @error('nomor_spp')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Tanggal SPP <span class="text-danger">*</span></label>
                                <input type="date" name="tanggal_spp" id="tanggal_spp"
                                       class="form-control @error('tanggal_spp') is-invalid @enderror"
                                       value="{{ old('tanggal_spp', now()->format('Y-m-d')) }}">
                                @error('tanggal_spp')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                {{-- B. Data Pembayaran --}}
                <div class="card rounded-4 border-top border-4 border-success shadow-sm mb-4">
                    <div class="card-body p-4">
                        <h6 class="fw-bold text-success mb-4">
                            <i class="bi bi-cash-stack me-2"></i>B. Data Pembayaran
                        </h6>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Jenis Tagihan <span class="text-danger">*</span></label>
                                <select name="jenis_tagihan" id="jenis_tagihan"
                                        class="form-select @error('jenis_tagihan') is-invalid @enderror">
                                    <option value="">-- Pilih Jenis Tagihan --</option>
                                    @foreach (['NON REMUNERASI', 'REMUNERASI', 'PERJALDIN', 'PENGADAAN', 'LAINNYA'] as $item)
                                        <option value="{{ $item }}" {{ old('jenis_tagihan', 'NON REMUNERASI') === $item ? 'selected' : '' }}>
                                            {{ $item }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('jenis_tagihan')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Cara Bayar <span class="text-danger">*</span></label>
                                <select name="cara_bayar" id="cara_bayar"
                                        class="form-select @error('cara_bayar') is-invalid @enderror">
                                    <option value="">-- Pilih Cara Bayar --</option>
                                    @foreach (['SP2D BLU - TRF', 'TUNAI', 'LAINNYA'] as $item)
                                        <option value="{{ $item }}" {{ old('cara_bayar', 'SP2D BLU - TRF') === $item ? 'selected' : '' }}>
                                            {{ $item }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('cara_bayar')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Supplier / Penerima Pembayaran <span class="text-danger">*</span></label>
                                <select name="supplier_id" id="supplier_id"
                                        class="form-select searchable @error('supplier_id') is-invalid @enderror">
                                    <option value="">-- Pilih Supplier --</option>
                                    @foreach ($suppliers as $supplier)
                                        <option value="{{ $supplier->id }}"
                                            data-name="{{ $supplier->name }}"
                                            data-npwp="{{ $supplier->npwp ?? '' }}"
                                            data-bank="{{ $supplier->bank_name ?? '' }}"
                                            data-account-number="{{ $supplier->account_number ?? '' }}"
                                            data-account-name="{{ $supplier->account_name ?? '' }}"
                                            {{ old('supplier_id') == $supplier->id ? 'selected' : '' }}>
                                            {{ $supplier->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('supplier_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">NPWP Supplier</label>
                                <input type="text" class="form-control" id="supplier_npwp" readonly>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">Rekening Supplier</label>
                                <input type="text" class="form-control" id="supplier_rekening" readonly>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Uraian <span class="text-danger">*</span></label>
                                <textarea name="uraian" rows="4"
                                          class="form-control @error('uraian') is-invalid @enderror"
                                          placeholder="Masukkan uraian pengajuan pembayaran">{{ old('uraian') }}</textarea>
                                @error('uraian')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                {{-- C. Dasar Pembayaran --}}
                <div class="card rounded-4 border-top border-4 border-info shadow-sm mb-4">
                    <div class="card-body p-4">
                        <h6 class="fw-bold text-info mb-4">
                            <i class="bi bi-journal-text me-2"></i>C. Dasar Pembayaran
                        </h6>

                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label d-block">Jenis Pengajuan <span class="text-danger">*</span></label>
                                <div class="btn-group" role="group" aria-label="Jenis Pengajuan">
                                    <input type="radio" class="btn-check" name="jenis_pengajuan" id="jenis_pengajuan_non_kontrak"
                                           value="Non Kontrak" {{ old('jenis_pengajuan', 'Non Kontrak') === 'Non Kontrak' ? 'checked' : '' }}>
                                    <label class="btn btn-outline-secondary" for="jenis_pengajuan_non_kontrak">Non Kontrak</label>

                                    <input type="radio" class="btn-check" name="jenis_pengajuan" id="jenis_pengajuan_kontrak"
                                           value="Kontrak" {{ old('jenis_pengajuan') === 'Kontrak' ? 'checked' : '' }}>
                                    <label class="btn btn-outline-primary" for="jenis_pengajuan_kontrak">Kontrak</label>
                                </div>
                            </div>

                            <div class="col-12" id="sectionKontrak" style="display: none;">
                                <div class="border rounded-4 p-3 bg-light-subtle">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Nomor Kontrak <span class="text-danger contract-required d-none">*</span></label>
                                            <select name="contract_id" id="contract_id"
                                                    class="form-select searchable @error('contract_id') is-invalid @enderror">
                                                <option value="">-- Pilih Kontrak --</option>
                                                @foreach ($contracts as $contract)
                                                    <option value="{{ $contract->id }}"
                                                        data-contract-number="{{ $contract->contract_number }}"
                                                        data-contract-date="{{ \Carbon\Carbon::parse($contract->date)->format('Y-m-d') }}"
                                                        data-description="{{ $contract->description }}"
                                                        data-supplier-id="{{ $contract->supplier_id }}"
                                                        data-budget-id="{{ $contract->budget_id }}"
                                                        data-ppk-id="{{ $contract->ppk_id }}"
                                                        data-cara-bayar="{{ $contract->cara_bayar ?? '' }}"
                                                        data-total-amount="{{ $contract->total_amount ?? 0 }}"
                                                        data-jumlah-termin="{{ $contract->jumlah_termin ?? '' }}"
                                                        {{ old('contract_id') == $contract->id ? 'selected' : '' }}>
                                                        {{ $contract->contract_number }} - {{ \Illuminate\Support\Str::limit($contract->description, 60) }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('contract_id')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-md-3">
                                            <label class="form-label">Tanggal Kontrak</label>
                                            <input type="date" name="tanggal_kontrak" id="tanggal_kontrak"
                                                   class="form-control @error('tanggal_kontrak') is-invalid @enderror"
                                                   value="{{ old('tanggal_kontrak') }}" readonly>
                                            @error('tanggal_kontrak')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-md-3">
                                            <label class="form-label">Termin</label>
                                            <input type="text" name="termin" id="termin"
                                                   class="form-control @error('termin') is-invalid @enderror"
                                                   placeholder="Contoh: Termin 1"
                                                   value="{{ old('termin') }}">
                                            @error('termin')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Jenis Dokumen Dasar <span class="text-danger">*</span></label>
                                <select name="jenis_dokumen_dasar" id="jenis_dokumen_dasar"
                                        class="form-select @error('jenis_dokumen_dasar') is-invalid @enderror">
                                    <option value="">-- Pilih --</option>
                                    @foreach (['BAST', 'BA', 'Invoice', 'Memo', 'Dokumen Lain'] as $item)
                                        <option value="{{ $item }}" {{ old('jenis_dokumen_dasar') === $item ? 'selected' : '' }}>
                                            {{ $item }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('jenis_dokumen_dasar')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Nomor Dokumen Dasar <span class="text-danger">*</span></label>
                                <input type="text" name="nomor_dokumen_dasar"
                                       class="form-control @error('nomor_dokumen_dasar') is-invalid @enderror"
                                       value="{{ old('nomor_dokumen_dasar') }}"
                                       placeholder="Masukkan nomor dokumen dasar">
                                @error('nomor_dokumen_dasar')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Tanggal Dokumen Dasar <span class="text-danger">*</span></label>
                                <input type="date" name="tanggal_dokumen_dasar"
                                       class="form-control @error('tanggal_dokumen_dasar') is-invalid @enderror"
                                       value="{{ old('tanggal_dokumen_dasar') }}">
                                @error('tanggal_dokumen_dasar')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                {{-- D. Data Anggaran --}}
                <div class="card rounded-4 border-top border-4 border-warning shadow-sm mb-4">
                    <div class="card-body p-4">
                        <h6 class="fw-bold text-warning mb-4">
                            <i class="bi bi-wallet2 me-2"></i>D. Data Anggaran
                        </h6>

                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label">COA / Pagu Anggaran <span class="text-danger">*</span></label>
                                <select name="budget_id" id="budget_id"
                                        class="form-select searchable @error('budget_id') is-invalid @enderror">
                                    <option value="">-- Pilih COA / Pagu Anggaran --</option>
                                    @foreach ($budgets as $budget)
                                        <option value="{{ $budget->id }}"
                                            data-coa="{{ $budget->coa ?? '' }}"
                                            data-name="{{ $budget->name ?? $budget->description ?? $budget->coa ?? '-' }}"
                                            data-pagu="{{ $budget->initial_budget ?? $budget->total_budget ?? 0 }}"
                                            data-realisasi="{{ $budget->realized_budget ?? 0 }}"
                                            data-sisa="{{ $budget->remaining_budget ?? 0 }}"
                                            data-jenis-belanja="{{ $budget->jenis_belanja ?? '-' }}"
                                            {{ old('budget_id') == $budget->id ? 'selected' : '' }}>
                                            {{ $budget->coa ?? '-' }} - {{ $budget->name ?? $budget->description ?? 'Tanpa Nama Akun' }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('budget_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Jumlah Pembayaran (Bruto) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" step="0.01" min="0" name="jumlah_pembayaran" id="jumlah_pembayaran"
                                           class="form-control text-end @error('jumlah_pembayaran') is-invalid @enderror"
                                           value="{{ old('jumlah_pembayaran', 0) }}">
                                </div>
                                @error('jumlah_pembayaran')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12">
                                <div class="border rounded-4 p-3 bg-light" id="budgetInfoBox">
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <small class="text-muted d-block">Kode COA</small>
                                            <div class="fw-semibold" id="info_budget_coa">-</div>
                                        </div>
                                        <div class="col-md-4">
                                            <small class="text-muted d-block">Jenis Belanja</small>
                                            <div class="fw-semibold" id="info_budget_jenis_belanja">-</div>
                                        </div>
                                        <div class="col-md-4">
                                            <small class="text-muted d-block">Uraian Akun</small>
                                            <div class="fw-semibold" id="info_budget_name">-</div>
                                        </div>
                                        <div class="col-md-4">
                                            <small class="text-muted d-block">Pagu</small>
                                            <div class="fw-semibold" id="info_budget_pagu">Rp 0</div>
                                        </div>
                                        <div class="col-md-4">
                                            <small class="text-muted d-block">Realisasi</small>
                                            <div class="fw-semibold" id="info_budget_realisasi">Rp 0</div>
                                        </div>
                                        <div class="col-md-4">
                                            <small class="text-muted d-block">Sisa Pagu</small>
                                            <div class="fw-semibold text-success" id="info_budget_sisa">Rp 0</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- E. Potongan / Pajak --}}
                <div class="card rounded-4 border-top border-4 border-danger shadow-sm mb-4">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h6 class="fw-bold text-danger mb-0">
                                <i class="bi bi-receipt-cutoff me-2"></i>E. Potongan / Pajak
                            </h6>
                            <button type="button" class="btn btn-sm btn-outline-danger" id="btnTambahPotongan">
                                <i class="bi bi-plus-circle"></i> Tambah Potongan
                            </button>
                        </div>

                        <div id="potonganContainer">
                            @foreach ($oldPotongan as $index => $potongan)
                                <div class="border rounded-4 p-3 mb-3 potongan-item">
                                    <div class="row g-3 align-items-end">
                                        <div class="col-md-4">
                                            <label class="form-label">Jenis Potongan</label>
                                            <select name="potongan[{{ $index }}][jenis_potongan]" class="form-select">
                                                <option value="">-- Pilih --</option>
                                                @foreach (['PPN', 'PPh 21', 'PPh 22', 'PPh 23', 'Potongan Lainnya'] as $jenisPotongan)
                                                    <option value="{{ $jenisPotongan }}"
                                                        {{ ($potongan['jenis_potongan'] ?? '') === $jenisPotongan ? 'selected' : '' }}>
                                                        {{ $jenisPotongan }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="col-md-4">
                                            <label class="form-label">Akun Potongan</label>
                                            <input type="text"
                                                   name="potongan[{{ $index }}][akun_potongan]"
                                                   class="form-control"
                                                   placeholder="Masukkan akun potongan"
                                                   value="{{ $potongan['akun_potongan'] ?? '' }}">
                                        </div>

                                        <div class="col-md-3">
                                            <label class="form-label">Jumlah Potongan</label>
                                            <div class="input-group">
                                                <span class="input-group-text">Rp</span>
                                                <input type="number" step="0.01" min="0"
                                                       name="potongan[{{ $index }}][jumlah_potongan]"
                                                       class="form-control text-end jumlah-potongan"
                                                       value="{{ $potongan['jumlah_potongan'] ?? '' }}">
                                            </div>
                                        </div>

                                        <div class="col-md-1">
                                            <button type="button" class="btn btn-outline-danger w-100 btnHapusPotongan">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        @error('potongan')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror

                        <div class="alert alert-light border mt-3 mb-0">
                            <div class="d-flex justify-content-between">
                                <span class="fw-semibold">Total Potongan</span>
                                <span class="fw-bold text-danger" id="totalPotonganText">Rp 0</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- F. Data Pejabat --}}
                <div class="card rounded-4 border-top border-4 border-secondary shadow-sm mb-4">
                    <div class="card-body p-4">
                        <h6 class="fw-bold text-secondary mb-4">
                            <i class="bi bi-person-badge me-2"></i>F. Data Pejabat
                        </h6>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">PPK <span class="text-danger">*</span></label>
                                <select name="ppk_id" id="ppk_id"
                                        class="form-select searchable @error('ppk_id') is-invalid @enderror">
                                    <option value="">-- Pilih PPK --</option>
                                    @foreach ($ppks as $ppk)
                                        <option value="{{ $ppk->id }}"
                                            data-name="{{ $ppk->name }}"
                                            {{ old('ppk_id') == $ppk->id ? 'selected' : '' }}>
                                            {{ $ppk->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('ppk_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ACTION --}}
                <div class="card rounded-4 shadow-sm mb-5">
                    <div class="card-body p-4 d-flex flex-wrap justify-content-between align-items-center gap-3">
                        <div class="text-muted">
                            <small>
                                Saat <strong>Simpan dan Kirim Verifikasi</strong>, data akan masuk tahap
                                <strong>SPP</strong> dan diverifikasi oleh <strong>PPK</strong> dan <strong>Kasubag TU</strong>.
                            </small>
                        </div>

                        <div class="d-flex gap-2">
                            <a href="{{ route('blu-payment-submissions.index') }}" class="btn btn-outline-secondary px-4">
                                Batal
                            </a>

                            <button type="submit" class="btn btn-outline-primary px-4"
                                    onclick="setSubmitMode('draft')">
                                <i class="bi bi-save me-1"></i> Simpan Draft
                            </button>

                            <button type="submit" class="btn btn-primary px-4 fw-semibold"
                                    onclick="setSubmitMode('submit')">
                                <i class="bi bi-send me-1"></i> Simpan dan Kirim Verifikasi
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- RIGHT COLUMN --}}
            <div class="col-lg-4">
                <div class="position-sticky" style="top: 90px; z-index: 10;">

                    {{-- Ringkasan --}}
                    <div class="card rounded-4 border-top border-4 border-primary shadow-sm mb-4">
                        <div class="card-body p-4">
                            <h6 class="fw-bold text-primary mb-3">
                                <i class="bi bi-card-checklist me-2"></i>Ringkasan Pengajuan
                            </h6>

                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Nomor SPP</span>
                                <span class="fw-semibold text-end" id="summary_nomor_spp">-</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Tanggal SPP</span>
                                <span class="fw-semibold text-end" id="summary_tanggal_spp">-</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Supplier</span>
                                <span class="fw-semibold text-end" id="summary_supplier">-</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Jenis Tagihan</span>
                                <span class="fw-semibold text-end" id="summary_jenis_tagihan">-</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Cara Bayar</span>
                                <span class="fw-semibold text-end" id="summary_cara_bayar">-</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">COA</span>
                                <span class="fw-semibold text-end" id="summary_coa">-</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Jenis Belanja</span>
                                <span class="fw-semibold text-end" id="summary_jenis_belanja">-</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Bruto</span>
                                <span class="fw-semibold text-end" id="summary_bruto">Rp 0</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Total Potongan</span>
                                <span class="fw-semibold text-danger text-end" id="summary_total_potongan">Rp 0</span>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between">
                                <span class="fw-bold">Neto Dibayarkan</span>
                                <span class="fw-bold text-success text-end" id="summary_neto">Rp 0</span>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">PPK</span>
                                <span class="fw-semibold text-end" id="summary_ppk">-</span>
                            </div>
                        </div>
                    </div>

                    {{-- Workflow --}}
                    <div class="card rounded-4 border-top border-4 border-info shadow-sm mb-4">
                        <div class="card-body p-4">
                            <h6 class="fw-bold text-info mb-3">
                                <i class="bi bi-diagram-3 me-2"></i>Alur Verifikasi Dokumen
                            </h6>

                            <div class="small">
                                <div class="d-flex mb-3">
                                    <div class="me-3">
                                        <span class="badge rounded-pill bg-primary">1</span>
                                    </div>
                                    <div>
                                        <div class="fw-semibold">Input Awal oleh Operator BLU</div>
                                        <div class="text-muted">Membuat data pengajuan pembayaran BLU.</div>
                                    </div>
                                </div>

                                <div class="d-flex mb-3">
                                    <div class="me-3">
                                        <span class="badge rounded-pill bg-secondary">2</span>
                                    </div>
                                    <div>
                                        <div class="fw-semibold">SPP</div>
                                        <div class="text-muted">Diverifikasi oleh PPK &amp; Kasubag TU.</div>
                                    </div>
                                </div>

                                <div class="d-flex mb-3">
                                    <div class="me-3">
                                        <span class="badge rounded-pill bg-secondary">3</span>
                                    </div>
                                    <div>
                                        <div class="fw-semibold">SPM</div>
                                        <div class="text-muted">Diverifikasi oleh PPSPM &amp; Kasubag TU.</div>
                                    </div>
                                </div>

                                <div class="d-flex mb-3">
                                    <div class="me-3">
                                        <span class="badge rounded-pill bg-secondary">4</span>
                                    </div>
                                    <div>
                                        <div class="fw-semibold">NPI</div>
                                        <div class="text-muted">Diverifikasi oleh Bendahara Penerimaan, Bendahara Pengeluaran, PPK, dan Kasubag TU.</div>
                                    </div>
                                </div>

                                <div class="d-flex mb-3">
                                    <div class="me-3">
                                        <span class="badge rounded-pill bg-secondary">5</span>
                                    </div>
                                    <div>
                                        <div class="fw-semibold">Daftar Nominatif</div>
                                        <div class="text-muted">Data orang diinput oleh PPABP, lalu diverifikasi oleh PPK, Bendahara Pengeluaran, dan Kasubag TU.</div>
                                    </div>
                                </div>

                                <div class="d-flex">
                                    <div class="me-3">
                                        <span class="badge rounded-pill bg-secondary">6</span>
                                    </div>
                                    <div>
                                        <div class="fw-semibold">SP2D</div>
                                        <div class="text-muted">Diverifikasi oleh Bendahara Pengeluaran, PPK, dan Kasubag TU.</div>
                                    </div>
                                </div>
                            </div>

                            <div class="alert alert-light border mt-4 mb-0 small">
                                Halaman ini hanya untuk input awal oleh <strong>Operator BLU</strong>.
                                Data nominatif akan diinput pada tahap lanjutan oleh <strong>PPABP</strong>.
                            </div>
                        </div>
                    </div>

                    {{-- Validasi anggaran --}}
                    <div class="card rounded-4 border-top border-4 border-danger shadow-sm">
                        <div class="card-body p-4">
                            <h6 class="fw-bold text-danger mb-3">
                                <i class="bi bi-exclamation-triangle me-2"></i>Kontrol Validasi
                            </h6>
                            <ul class="small mb-0 ps-3">
                                <li>Total potongan tidak boleh melebihi jumlah pembayaran.</li>
                                <li>Jika pengajuan berbasis kontrak, nomor kontrak wajib dipilih.</li>
                                <li>Budget / pagu harus dipilih sebelum pengajuan disimpan.</li>
                                <li>Nomor SPP otomatis dan unik.</li>
                            </ul>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </form>
@endsection

@push('script')
<script>
    let potonganIndex = {{ count($oldPotongan) }};

    document.addEventListener('DOMContentLoaded', function () {
        initSearchable();
        bindEvents();
        toggleJenisPengajuan();
        fillSupplierInfo();
        fillBudgetInfo();
        updateSummary();

        const oldContract = document.getElementById('contract_id').value;
        if (oldContract) {
            fillContractInfo(false);
        }
    });

    function initSearchable() {
        if (window.jQuery && $.fn.select2) {
            $('.searchable').select2({
                width: '100%',
                theme: 'bootstrap-5'
            });
        }
    }

    function bindEvents() {
        document.getElementById('supplier_id').addEventListener('change', function () {
            fillSupplierInfo();
            updateSummary();
        });

        document.getElementById('budget_id').addEventListener('change', function () {
            fillBudgetInfo();
            updateSummary();
        });

        document.getElementById('contract_id').addEventListener('change', function () {
            fillContractInfo(true);
        });

        document.getElementById('ppk_id').addEventListener('change', updateSummary);
        document.getElementById('nomor_spp').addEventListener('input', updateSummary);
        document.getElementById('tanggal_spp').addEventListener('change', updateSummary);
        document.getElementById('jenis_tagihan').addEventListener('change', updateSummary);
        document.getElementById('cara_bayar').addEventListener('change', updateSummary);
        document.getElementById('jumlah_pembayaran').addEventListener('input', updateSummary);
        document.getElementById('btnTambahPotongan').addEventListener('click', addPotonganRow);

        document.querySelectorAll('input[name="jenis_pengajuan"]').forEach(el => {
            el.addEventListener('change', function () {
                toggleJenisPengajuan();
                updateSummary();
            });
        });

        document.addEventListener('input', function (e) {
            if (e.target.classList.contains('jumlah-potongan')) {
                updateSummary();
            }
        });

        document.addEventListener('click', function (e) {
            if (e.target.closest('.btnHapusPotongan')) {
                const item = e.target.closest('.potongan-item');
                const container = document.getElementById('potonganContainer');

                if (container.querySelectorAll('.potongan-item').length > 1) {
                    item.remove();
                } else {
                    item.querySelectorAll('input, select').forEach(el => el.value = '');
                }

                updateSummary();
            }
        });
    }

    function setSubmitMode(mode) {
        const submitMode = document.getElementById('submit_mode');
        const documentType = document.getElementById('document_type');
        const workflowStatus = document.getElementById('workflow_status');

        if (mode === 'submit') {
            submitMode.value = 'submit';
            documentType.value = 'spp';
            workflowStatus.value = 'waiting_verification';
        } else {
            submitMode.value = 'draft';
            documentType.value = 'pengajuan';
            workflowStatus.value = 'draft';
        }
    }

    function toggleJenisPengajuan() {
        const isKontrak = document.getElementById('jenis_pengajuan_kontrak').checked;
        const sectionKontrak = document.getElementById('sectionKontrak');

        sectionKontrak.style.display = isKontrak ? 'block' : 'none';

        document.querySelectorAll('.contract-required').forEach(el => {
            el.classList.toggle('d-none', !isKontrak);
        });

        const contractSelect = document.getElementById('contract_id');

        if (!isKontrak) {
            contractSelect.value = '';
            document.getElementById('tanggal_kontrak').value = '';
            document.getElementById('termin').value = '';
        }

        if (window.jQuery && $.fn.select2) {
            $('#contract_id').trigger('change.select2');
        }
    }

    function fillSupplierInfo() {
        const supplierSelect = document.getElementById('supplier_id');
        const selected = supplierSelect.options[supplierSelect.selectedIndex];

        const npwp = selected ? (selected.dataset.npwp || '') : '';
        const bank = selected ? (selected.dataset.bank || '') : '';
        const accountNumber = selected ? (selected.dataset.accountNumber || '') : '';
        const accountName = selected ? (selected.dataset.accountName || '') : '';

        document.getElementById('supplier_npwp').value = npwp;
        document.getElementById('supplier_rekening').value = [accountNumber, bank, accountName].filter(Boolean).join(' | ');
    }

    function fillContractInfo(autofill = true) {
        const contractSelect = document.getElementById('contract_id');
        const selected = contractSelect.options[contractSelect.selectedIndex];
        if (!selected || !selected.value) return;

        document.getElementById('tanggal_kontrak').value = selected.dataset.contractDate || '';

        if (!document.getElementById('termin').value) {
            const jumlahTermin = selected.dataset.jumlahTermin || '';
            document.getElementById('termin').value = jumlahTermin ? 'Termin ' + jumlahTermin : '';
        }

        if (autofill) {
            if (selected.dataset.supplierId) {
                document.getElementById('supplier_id').value = selected.dataset.supplierId;
                if (window.jQuery && $.fn.select2) {
                    $('#supplier_id').trigger('change.select2');
                }
                fillSupplierInfo();
            }

            if (selected.dataset.budgetId) {
                document.getElementById('budget_id').value = selected.dataset.budgetId;
                if (window.jQuery && $.fn.select2) {
                    $('#budget_id').trigger('change.select2');
                }
                fillBudgetInfo();
            }

            if (selected.dataset.ppkId) {
                document.getElementById('ppk_id').value = selected.dataset.ppkId;
                if (window.jQuery && $.fn.select2) {
                    $('#ppk_id').trigger('change.select2');
                }
            }

            if (selected.dataset.caraBayar) {
                document.getElementById('cara_bayar').value = selected.dataset.caraBayar;
            }

            if (!document.getElementById('jumlah_pembayaran').value || Number(document.getElementById('jumlah_pembayaran').value) === 0) {
                document.getElementById('jumlah_pembayaran').value = selected.dataset.totalAmount || 0;
            }
        }

        updateSummary();
    }

    function fillBudgetInfo() {
        const budgetSelect = document.getElementById('budget_id');
        const selected = budgetSelect.options[budgetSelect.selectedIndex];

        const coa = selected ? (selected.dataset.coa || '-') : '-';
        const name = selected ? (selected.dataset.name || '-') : '-';
        const pagu = selected ? Number(selected.dataset.pagu || 0) : 0;
        const realisasi = selected ? Number(selected.dataset.realisasi || 0) : 0;
        const sisa = selected ? Number(selected.dataset.sisa || 0) : 0;
        const jenisBelanja = selected ? (selected.dataset.jenisBelanja || '-') : '-';

        document.getElementById('info_budget_coa').textContent = coa;
        document.getElementById('info_budget_name').textContent = name;
        document.getElementById('info_budget_pagu').textContent = formatRupiah(pagu);
        document.getElementById('info_budget_realisasi').textContent = formatRupiah(realisasi);
        document.getElementById('info_budget_sisa').textContent = formatRupiah(sisa);
        document.getElementById('info_budget_jenis_belanja').textContent = jenisBelanja;
    }

    function addPotonganRow() {
        const container = document.getElementById('potonganContainer');

        const html = `
            <div class="border rounded-4 p-3 mb-3 potongan-item">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label">Jenis Potongan</label>
                        <select name="potongan[${potonganIndex}][jenis_potongan]" class="form-select">
                            <option value="">-- Pilih --</option>
                            <option value="PPN">PPN</option>
                            <option value="PPh 21">PPh 21</option>
                            <option value="PPh 22">PPh 22</option>
                            <option value="PPh 23">PPh 23</option>
                            <option value="Potongan Lainnya">Potongan Lainnya</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Akun Potongan</label>
                        <input type="text" name="potongan[${potonganIndex}][akun_potongan]" class="form-control" placeholder="Masukkan akun potongan">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Jumlah Potongan</label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="number" step="0.01" min="0" name="potongan[${potonganIndex}][jumlah_potongan]" class="form-control text-end jumlah-potongan">
                        </div>
                    </div>

                    <div class="col-md-1">
                        <button type="button" class="btn btn-outline-danger w-100 btnHapusPotongan">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        `;

        container.insertAdjacentHTML('beforeend', html);
        potonganIndex++;
    }

    function getTotalPotongan() {
        let total = 0;
        document.querySelectorAll('.jumlah-potongan').forEach(el => {
            total += Number(el.value || 0);
        });
        return total;
    }

    function updateSummary() {
        const nomorSpp = document.getElementById('nomor_spp').value || '-';
        const tanggalSpp = document.getElementById('tanggal_spp').value || '-';

        const supplierSelect = document.getElementById('supplier_id');
        const supplierText = supplierSelect.selectedIndex > 0 ? supplierSelect.options[supplierSelect.selectedIndex].text : '-';

        const jenisTagihan = document.getElementById('jenis_tagihan').value || '-';
        const caraBayar = document.getElementById('cara_bayar').value || '-';

        const budgetSelect = document.getElementById('budget_id');
        const budgetOption = budgetSelect.options[budgetSelect.selectedIndex];
        const coa = budgetOption && budgetOption.value ? (budgetOption.dataset.coa || '-') : '-';
        const jenisBelanja = budgetOption && budgetOption.value ? (budgetOption.dataset.jenisBelanja || '-') : '-';

        const bruto = Number(document.getElementById('jumlah_pembayaran').value || 0);
        const totalPotongan = getTotalPotongan();
        const neto = bruto - totalPotongan;

        const ppkSelect = document.getElementById('ppk_id');
        const ppkText = ppkSelect.selectedIndex > 0 ? ppkSelect.options[ppkSelect.selectedIndex].text : '-';

        document.getElementById('summary_nomor_spp').textContent = nomorSpp;
        document.getElementById('summary_tanggal_spp').textContent = tanggalSpp;
        document.getElementById('summary_supplier').textContent = supplierText;
        document.getElementById('summary_jenis_tagihan').textContent = jenisTagihan;
        document.getElementById('summary_cara_bayar').textContent = caraBayar;
        document.getElementById('summary_coa').textContent = coa;
        document.getElementById('summary_jenis_belanja').textContent = jenisBelanja;
        document.getElementById('summary_bruto').textContent = formatRupiah(bruto);
        document.getElementById('summary_total_potongan').textContent = formatRupiah(totalPotongan);
        document.getElementById('summary_neto').textContent = formatRupiah(neto);
        document.getElementById('summary_ppk').textContent = ppkText;

        document.getElementById('totalPotonganText').textContent = formatRupiah(totalPotongan);
    }

    function formatRupiah(amount) {
        return 'Rp ' + Number(amount || 0).toLocaleString('id-ID', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 2
        });
    }
</script>
@endpush