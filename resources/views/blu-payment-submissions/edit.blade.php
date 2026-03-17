@extends('layouts.app')
@section('title')
    Edit Tagihan (Pengajuan Pembayaran BLU)
@endsection
@section('content')
    <x-page-title title="Tagihan & Pembayaran" subtitle="Edit Pengajuan" />

    <div class="card border-top border-4 border-warning">
        <div class="card-body p-5">
            <div class="card-title d-flex align-items-center mb-4">
                <div><i class="bi bi-pencil me-1 font-22 text-warning"></i></div>
                <h5 class="mb-0 text-warning">Edit Tagihan (Draft SPP)</h5>
            </div>
            
            <form action="{{ route('blu-payment-submissions.update', $transaction->id) }}" method="POST">
                @csrf
                @method('PUT')
                
                <h6 class="mb-3">Informasi Dasar</h6>
                <div class="row mb-3">
                    <label for="transaction_number" class="col-sm-3 col-form-label">Nomor Transaksi <span class="text-danger">*</span></label>
                    <div class="col-sm-9">
                        <input type="text" class="form-control @error('transaction_number') is-invalid @enderror" id="transaction_number" name="transaction_number" value="{{ old('transaction_number', $transaction->transaction_number) }}" required>
                        @error('transaction_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="row mb-3">
                    <label for="date" class="col-sm-3 col-form-label">Tanggal Transaksi <span class="text-danger">*</span></label>
                    <div class="col-sm-9">
                        <input type="date" class="form-control @error('date') is-invalid @enderror" id="date" name="date" value="{{ old('date', \Carbon\Carbon::parse($transaction->date)->format('Y-m-d')) }}" required>
                        @error('date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="row mb-3">
                    <label for="type" class="col-sm-3 col-form-label">Jenis Pembayaran <span class="text-danger">*</span></label>
                    <div class="col-sm-9">
                        <select class="form-select @error('type') is-invalid @enderror" id="type" name="type" required onchange="toggleContractField()">
                            <option value="LS" {{ old('type', $transaction->type) == 'LS' ? 'selected' : '' }}>LS (Langsung) - Biasanya dgn Kontrak</option>
                            <option value="UP" {{ old('type', $transaction->type) == 'UP' ? 'selected' : '' }}>UP (Uang Persediaan)</option>
                            <option value="TUP" {{ old('type', $transaction->type) == 'TUP' ? 'selected' : '' }}>TUP (Tambahan Uang Persediaan)</option>
                        </select>
                        @error('type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="row mb-4">
                    <label for="description" class="col-sm-3 col-form-label">Uraian Pembayaran <span class="text-danger">*</span></label>
                    <div class="col-sm-9">
                        <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3" required>{{ old('description', $transaction->description) }}</textarea>
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
                                    {{ old('contract_id', $transaction->contract_id) == $contract->id ? 'selected' : '' }}>
                                    {{ $contract->contract_number }} - {{ Str::limit($contract->description, 50) }}
                                </option>
                            @endforeach
                        </select>
                        @error('contract_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="row mb-3" id="termRow" style="display: none;">
                    <label for="term_id" class="col-sm-3 col-form-label">Pilih Termin Pembayaran</label>
                    <div class="col-sm-9">
                        <select class="form-select @error('term_id') is-invalid @enderror" id="term_id" name="term_id" onchange="setAmountFromTerm()">
                            <option value="">-- Pilih Termin --</option>
                        </select>
                        <small class="text-muted" id="preselectedTermHelper" style="display:none;" data-val="{{ old('term_id', $transaction->term_id) }}"></small>
                        @error('term_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="row mb-4">
                    <label for="budget_id" class="col-sm-3 col-form-label">Beban Anggaran (COA) <span class="text-danger">*</span></label>
                    <div class="col-sm-9">
                        <select class="form-select @error('budget_id') is-invalid @enderror" id="budget_id" name="budget_id" required>
                            <option value="">-- Pilih Pagu Anggaran --</option>
                            @foreach($budgets as $budget)
                                <option value="{{ $budget->id }}" {{ old('budget_id', $transaction->budget_id) == $budget->id ? 'selected' : '' }}>
                                    {{ $budget->year }} - {{ $budget->coa }} : Sisa Rp {{ number_format($budget->remaining_budget, 0, ',', '.') }}
                                </option>
                            @endforeach
                        </select>
                        <small id="budgetHelp" class="text-muted"></small>
                        @error('budget_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <hr>
                <h6 class="mb-3 mt-4">Nilai Transaksi</h6>

                <div class="row mb-4">
                    <label for="amount" class="col-sm-3 col-form-label">Nilai Bruto (Rp) <span class="text-danger">*</span></label>
                    <div class="col-sm-9">
                        <input type="number" step="0.01" class="form-control @error('amount') is-invalid @enderror" id="amount" name="amount" value="{{ old('amount', $transaction->amount) }}" required>
                        @error('amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="row">
                    <label class="col-sm-3 col-form-label"></label>
                    <div class="col-sm-9">
                        <button type="submit" class="btn btn-primary px-4">Update Draft Pengajuan</button>
                        <a href="{{ route('blu-payment-submissions.index') }}" class="btn btn-secondary px-4">Batal</a>
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
            contractRow.style.display = 'none';
            document.getElementById('termRow').style.display = 'none';
            document.getElementById('budget_id').removeAttribute('style');
        } else {
            contractRow.style.display = 'flex';
        }
    }

    function loadTermsAndBudget() {
        const contractSelect = document.getElementById('contract_id');
        const termRow = document.getElementById('termRow');
        const termSelect = document.getElementById('term_id');
        const budgetSelect = document.getElementById('budget_id');
        const amountInput = document.getElementById('amount');
        const helper = document.getElementById('preselectedTermHelper');
        let preValue = helper.getAttribute('data-val');
        
        termSelect.innerHTML = '<option value="">-- Pilih Termin --</option>';
        amountInput.removeAttribute('readonly');
        
        if (contractSelect.value) {
            const selectedOption = contractSelect.options[contractSelect.selectedIndex];
            const termsJson = selectedOption.getAttribute('data-terms');
            const budgetId = selectedOption.getAttribute('data-budget-id');
            
            if (budgetId) {
                budgetSelect.value = budgetId;
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
                        let disabled = term.status === 'Paid' && term.id != preValue ? 'disabled' : '';
                        let sel = term.id == preValue ? 'selected' : '';
                        termSelect.innerHTML += `<option value="${term.id}" data-amount="${term.amount}" ${disabled} ${sel}>${term.term_name} - Rp ${parseFloat(term.amount).toLocaleString('id-ID')} ${statusText}</option>`;
                    });
                     setAmountFromTerm();
                } else {
                    termRow.style.display = 'none';
                }
            }
        } else {
            termRow.style.display = 'none';
            budgetSelect.style.pointerEvents = 'auto';
            budgetSelect.style.backgroundColor = '';
            document.getElementById('budgetHelp').innerText = "";
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
        loadTermsAndBudget();
    });
</script>
@endpush
