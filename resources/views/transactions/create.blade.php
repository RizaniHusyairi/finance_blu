@extends('layouts.app')
@section('title')
    Input Tagihan (Transaksi)
@endsection
@section('content')
    <x-page-title title="Tagihan & Pembayaran" subtitle="Input Transaksi Baru" />

    <div class="card border-top border-4 border-primary">
        <div class="card-body p-5">
            <div class="card-title d-flex align-items-center mb-4">
                <div><i class="bi bi-receipt me-1 font-22 text-primary"></i></div>
                <h5 class="mb-0 text-primary">Form Input Tagihan (Draft SPP)</h5>
            </div>
            
            <form action="{{ route('transactions.store') }}" method="POST">
                @csrf
                
                <h6 class="mb-3">Informasi Dasar</h6>
                <div class="row mb-3">
                    <label for="transaction_number" class="col-sm-3 col-form-label">Nomor Transaksi <span class="text-danger">*</span></label>
                    <div class="col-sm-9">
                        <input type="text" class="form-control @error('transaction_number') is-invalid @enderror" id="transaction_number" name="transaction_number" value="{{ old('transaction_number', 'TRX-'.date('YmdHis')) }}" required>
                        <small class="text-muted">Nomor unik transaksi (Bisa disesuaikan dengan format nomor SPP/Invoice)</small>
                        @error('transaction_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="row mb-3">
                    <label for="date" class="col-sm-3 col-form-label">Tanggal Transaksi <span class="text-danger">*</span></label>
                    <div class="col-sm-9">
                        <input type="date" class="form-control @error('date') is-invalid @enderror" id="date" name="date" value="{{ old('date', date('Y-m-d')) }}" required>
                        @error('date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="row mb-3">
                    <label for="type" class="col-sm-3 col-form-label">Jenis Pembayaran <span class="text-danger">*</span></label>
                    <div class="col-sm-9">
                        <select class="form-select @error('type') is-invalid @enderror" id="type" name="type" required onchange="toggleContractField()">
                            <option value="LS" {{ old('type', 'LS') == 'LS' ? 'selected' : '' }}>LS (Langsung) - Biasanya dgn Kontrak</option>
                            <option value="UP" {{ old('type') == 'UP' ? 'selected' : '' }}>UP (Uang Persediaan)</option>
                            <option value="TUP" {{ old('type') == 'TUP' ? 'selected' : '' }}>TUP (Tambahan Uang Persediaan)</option>
                        </select>
                        @error('type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="row mb-4">
                    <label for="description" class="col-sm-3 col-form-label">Uraian Pembayaran <span class="text-danger">*</span></label>
                    <div class="col-sm-9">
                        <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3" required>{{ old('description') }}</textarea>
                        @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <hr>
                <h6 class="mb-3 mt-4">Keterkaitan Kontrak & Anggaran</h6>
                
                <div class="row mb-3" id="contractRow">
                    <label for="contract_id" class="col-sm-3 col-form-label">Pilih Kontrak</label>
                    <div class="col-sm-9">
                        <select class="form-select @error('contract_id') is-invalid @enderror" id="contract_id" name="contract_id" onchange="loadTermsAndBudget()">
                            <option value="">-- Non-Kontrak --</option>
                            @foreach($contracts as $contract)
                                <option value="{{ $contract->id }}" 
                                    data-terms="{{ json_encode($contract->terms) }}"
                                    data-budget-id="{{ $contract->budget_id }}"
                                    {{ old('contract_id') == $contract->id ? 'selected' : '' }}>
                                    {{ $contract->contract_number }} - {{ Str::limit($contract->description, 50) }}
                                </option>
                            @endforeach
                        </select>
                        <small class="text-muted">Untuk pembayaran LS kepada Mitra, silakan pilih kontrak terkait.</small>
                        @error('contract_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="row mb-3" id="termRow" style="display: none;">
                    <label for="term_id" class="col-sm-3 col-form-label">Pilih Termin Pembayaran</label>
                    <div class="col-sm-9">
                        <select class="form-select @error('term_id') is-invalid @enderror" id="term_id" name="term_id" onchange="setAmountFromTerm()">
                            <option value="">-- Pilih Termin --</option>
                        </select>
                        @error('term_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
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
                        <small id="budgetHelp" class="text-muted">Jika memilih kontrak, pagu anggaran akan otomatis disesuaikan.</small>
                        @error('budget_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <hr>
                <h6 class="mb-3 mt-4">Nilai Transaksi</h6>

                <div class="row mb-4">
                    <label for="amount" class="col-sm-3 col-form-label">Nilai Bruto (Rp) <span class="text-danger">*</span></label>
                    <div class="col-sm-9">
                        <input type="number" step="0.01" class="form-control @error('amount') is-invalid @enderror" id="amount" name="amount" value="{{ old('amount') }}" required>
                        <small class="text-muted">Nilai pembayaran kotor sebelum dipotong pajak.</small>
                        @error('amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="row">
                    <label class="col-sm-3 col-form-label"></label>
                    <div class="col-sm-9">
                        <button type="submit" class="btn btn-primary px-4">Simpan Sebagai Draft</button>
                        <a href="{{ route('transactions.index') }}" class="btn btn-secondary px-4">Batal</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
@push('script')
<script>
    function toggleContractField() {
        const type = document.getElementById('type').value;
        const contractRow = document.getElementById('contractRow');
        const contractSelect = document.getElementById('contract_id');
        
        if (type !== 'LS') {
            contractSelect.value = '';
            contractRow.style.display = 'none';
            document.getElementById('termRow').style.display = 'none';
            document.getElementById('budget_id').removeAttribute('readonly');
            // reset budget and term formatting
            loadTermsAndBudget();
        } else {
            contractRow.style.display = 'flex';
            document.getElementById('budget_id').removeAttribute('readonly');
        }
    }

    function loadTermsAndBudget() {
        const contractSelect = document.getElementById('contract_id');
        const termRow = document.getElementById('termRow');
        const termSelect = document.getElementById('term_id');
        const budgetSelect = document.getElementById('budget_id');
        const amountInput = document.getElementById('amount');
        
        // Reset Terms
        termSelect.innerHTML = '<option value="">-- Pilih Termin --</option>';
        amountInput.removeAttribute('readonly');
        
        if (contractSelect.value) {
            const selectedOption = contractSelect.options[contractSelect.selectedIndex];
            const termsJson = selectedOption.getAttribute('data-terms');
            const budgetId = selectedOption.getAttribute('data-budget-id');
            
            // Auto Select Budget
            if (budgetId) {
                budgetSelect.value = budgetId;
                // Avoid changing budget manually if bound to contract
                budgetSelect.style.pointerEvents = 'none';
                budgetSelect.style.backgroundColor = '#e9ecef';
                 document.getElementById('budgetHelp').innerText = "Pagu otomatis terisi dari Kontrak dan dikunci.";
            }

            if (termsJson) {
                const terms = JSON.parse(termsJson);
                if (terms.length > 0) {
                    termRow.style.display = 'flex';
                    terms.forEach(term => {
                        let statusText = term.status === 'Paid' ? '(Lunas)' : '';
                        let disabled = term.status === 'Paid' ? 'disabled' : '';
                        termSelect.innerHTML += `<option value="${term.id}" data-amount="${term.amount}" ${disabled}>${term.term_name} - Rp ${parseFloat(term.amount).toLocaleString('id-ID')} ${statusText}</option>`;
                    });
                } else {
                    termRow.style.display = 'none';
                }
            } else {
                 termRow.style.display = 'none';
            }
        } else {
            termRow.style.display = 'none';
            budgetSelect.style.pointerEvents = 'auto';
            budgetSelect.style.backgroundColor = '';
            document.getElementById('budgetHelp').innerText = "Silakan pilih beban anggaran.";
        }
    }

    function setAmountFromTerm() {
        const termSelect = document.getElementById('term_id');
        const amountInput = document.getElementById('amount');
        
        if (termSelect.value) {
            const selectedOption = termSelect.options[termSelect.selectedIndex];
            const amount = selectedOption.getAttribute('data-amount');
            if (amount) {
                amountInput.value = parseFloat(amount).toFixed(2);
                amountInput.setAttribute('readonly', 'readonly');
            }
        } else {
            amountInput.removeAttribute('readonly');
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        toggleContractField();
        
        // Retain values on validation error (old input)
        @if(old('contract_id'))
            loadTermsAndBudget();
            setTimeout(() => {
                document.getElementById('term_id').value = "{{ old('term_id') }}";
                setAmountFromTerm();
            }, 500);
        @endif
    });
</script>
@endpush
