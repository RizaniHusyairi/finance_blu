@extends('layouts.app')
@section('title')
    Edit Pagu Anggaran
@endsection
@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0 fw-bold">Edit Pagu Anggaran</h5>
        <a href="{{ route('budgets.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Kembali ke Daftar</a>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('budgets.update', $budget->id) }}" method="POST" id="formPagu">
        @csrf
        @method('PUT')

        {{-- Section 1 — Struktur Kode Anggaran --}}
        <div class="card rounded-4 mb-4">
            <div class="card-body p-4">
                <h6 class="mb-4 fw-bold"><i class="bi bi-diagram-3 me-2"></i>1. Struktur Kode Anggaran</h6>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Tahun Anggaran <span class="text-danger">*</span></label>
                        <select class="form-select" name="year" id="year" required onchange="updateSummary()">
                            @for ($y = date('Y') + 1; $y >= date('Y') - 2; $y--)
                                <option value="{{ $y }}" {{ old('year', $budget->year) == $y ? 'selected' : '' }}>{{ $y }}</option>
                            @endfor
                        </select>
                    </div>
                    <div class="col-md-8"></div>

                    <div class="col-md-3">
                        <label class="form-label">Kode Program <span class="text-danger">*</span></label>
                        <input type="text" class="form-control coa-part" name="program_code" id="program_code" value="{{ old('program_code', $budget->program_code) }}" placeholder="Contoh: GA" required oninput="buildCOA()">
                        <small>kd_program</small>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Kode Kegiatan <span class="text-danger">*</span></label>
                        <input type="text" class="form-control coa-part" name="activity_code" id="activity_code" value="{{ old('activity_code', $budget->activity_code) }}" placeholder="Contoh: 4645" required oninput="buildCOA()">
                        <small>kd_giat</small>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Kode Output <span class="text-danger">*</span></label>
                        <input type="text" class="form-control coa-part" name="output_code" id="output_code" value="{{ old('output_code', $budget->output_code) }}" placeholder="Contoh: CBE" required oninput="buildCOA()">
                        <small>kd_output</small>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Kode Suboutput <span class="text-danger">*</span></label>
                        <input type="text" class="form-control coa-part" name="suboutput_code" id="suboutput_code" value="{{ old('suboutput_code', $budget->suboutput_code) }}" placeholder="Contoh: 001" required oninput="buildCOA()">
                        <small>kd_suboutput</small>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Kode Komponen <span class="text-danger">*</span></label>
                        <input type="text" class="form-control coa-part" name="component_code" id="component_code" value="{{ old('component_code', $budget->component_code) }}" placeholder="Contoh: 054" required oninput="buildCOA()">
                        <small>kd_komponen</small>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Kode Subkomponen <span class="text-danger">*</span></label>
                        <input type="text" class="form-control coa-part" name="subcomponent_code" id="subcomponent_code" value="{{ old('subcomponent_code', $budget->subcomponent_code) }}" placeholder="Contoh: A" required oninput="buildCOA()">
                        <small>kd_subkomponen</small>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Kode Akun <span class="text-danger">*</span></label>
                        <input type="text" class="form-control coa-part" name="account_code" id="account_code" value="{{ old('account_code', $budget->account_code) }}" placeholder="Contoh: 537113" required oninput="buildCOA()">
                        <small>kd_akun</small>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Kode Item <span class="text-danger">*</span></label>
                        <input type="text" class="form-control coa-part" name="item_code" id="item_code" value="{{ old('item_code', $budget->item_code) }}" placeholder="Contoh: 00001" required oninput="buildCOA()">
                        <small>kd_item</small>
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-bold">COA (Auto-Generate)</label>
                        <input type="text" class="form-control fw-bold" id="coa_display" readonly placeholder="COA akan terbentuk otomatis dari kode di atas">
                        <div id="coa_warning" class="mt-1" style="display:none;">
                            <span class="badge bg-danger"><i class="bi bi-exclamation-triangle me-1"></i>COA ini sudah digunakan!</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Section 2 — Informasi Anggaran --}}
        <div class="card rounded-4 mb-4">
            <div class="card-body p-4">
                <h6 class="mb-4 fw-bold"><i class="bi bi-cash-stack me-2"></i>2. Informasi Anggaran</h6>
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">Uraian Pagu Anggaran <span class="text-danger">*</span></label>
                        <textarea class="form-control" rows="3" name="description" id="description" placeholder="Masukkan uraian pagu anggaran" required>{{ old('description', $budget->description) }}</textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Nilai Pagu (Rp) <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="pagu_display" placeholder="Masukkan nominal pagu anggaran" oninput="formatCurrency(this); updateSummary()">
                        <input type="hidden" name="initial_budget" id="initial_budget" value="{{ old('initial_budget', intval($budget->initial_budget)) }}">
                        <small>Mengubah pagu akan menghitung ulang sisa berdasarkan realisasi (Rp {{ number_format($budget->realized_budget, 0, ',', '.') }})</small>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Status Pagu <span class="text-danger">*</span></label>
                        <select class="form-select" name="status_pagu" id="status_pagu" required onchange="updateSummary()">
                            <option value="Aktif" {{ old('status_pagu', $budget->status_pagu ?? 'Aktif') == 'Aktif' ? 'selected' : '' }}>Aktif</option>
                            <option value="Nonaktif" {{ old('status_pagu', $budget->status_pagu ?? '') == 'Nonaktif' ? 'selected' : '' }}>Nonaktif</option>
                        </select>
                    </div>
                    <div class="col-md-3"></div>
                    <div class="col-12">
                        <label class="form-label">Catatan</label>
                        <textarea class="form-control" rows="2" name="catatan" placeholder="Tambahkan catatan bila diperlukan">{{ old('catatan', $budget->catatan) }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        {{-- Section 3 — Ringkasan Otomatis --}}
        <div class="card rounded-4 mb-4">
            <div class="card-body p-4">
                <h6 class="mb-4 fw-bold"><i class="bi bi-clipboard-data me-2"></i>3. Ringkasan</h6>
                <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-xl-6 g-3">
                    <div class="col">
                        <div class="card rounded-4 mb-0 h-100">
                            <div class="card-body p-3">
                                <p class="mb-1 small">Tahun Anggaran</p>
                                <h6 class="mb-0 fw-bold" id="sum-tahun">{{ $budget->year }}</h6>
                            </div>
                        </div>
                    </div>
                    <div class="col">
                        <div class="card rounded-4 mb-0 h-100">
                            <div class="card-body p-3">
                                <p class="mb-1 small">COA Lengkap</p>
                                <h6 class="mb-0 fw-bold text-break" id="sum-coa" style="font-size: 0.75rem;">{{ $budget->coa }}</h6>
                            </div>
                        </div>
                    </div>
                    <div class="col">
                        <div class="card rounded-4 mb-0 h-100">
                            <div class="card-body p-3">
                                <p class="mb-1 small">Pagu</p>
                                <h6 class="mb-0 fw-bold" id="sum-pagu">Rp {{ number_format($budget->initial_budget, 0, ',', '.') }}</h6>
                            </div>
                        </div>
                    </div>
                    <div class="col">
                        <div class="card rounded-4 mb-0 h-100">
                            <div class="card-body p-3">
                                <p class="mb-1 small">Realisasi</p>
                                <h6 class="mb-0 fw-bold">Rp {{ number_format($budget->realized_budget, 0, ',', '.') }}</h6>
                            </div>
                        </div>
                    </div>
                    <div class="col">
                        <div class="card rounded-4 mb-0 h-100">
                            <div class="card-body p-3">
                                <p class="mb-1 small">Sisa</p>
                                <h6 class="mb-0 fw-bold" id="sum-sisa">Rp {{ number_format($budget->remaining_budget, 0, ',', '.') }}</h6>
                            </div>
                        </div>
                    </div>
                    <div class="col">
                        <div class="card rounded-4 mb-0 h-100">
                            <div class="card-body p-3">
                                <p class="mb-1 small">Status Pagu</p>
                                <h6 class="mb-0 fw-bold" id="sum-status">{{ $budget->status_pagu ?? 'Aktif' }}</h6>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tombol Aksi --}}
        <div class="card rounded-4 mb-5">
            <div class="card-body p-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div></div>
                <div class="d-flex gap-2">
                    <a href="{{ route('budgets.index') }}" class="btn btn-outline-secondary px-4" onclick="return confirmBatal()">Batal</a>
                    <button type="reset" class="btn btn-outline-secondary px-4" onclick="return confirm('Apakah Anda yakin ingin mereset form ke data awal?')">Reset Form</button>
                    <button type="submit" class="btn btn-primary px-5 fw-bold">Simpan Perubahan</button>
                </div>
            </div>
        </div>

    </form>
@endsection

@push('script')
<script>
    const existingCoas = @json($existingCoas);
    const realizedBudget = {{ intval($budget->realized_budget) }};

    document.addEventListener("DOMContentLoaded", function () {
        buildCOA();
        // Restore pagu display
        let oldPagu = document.getElementById('initial_budget').value;
        if (oldPagu) {
            document.getElementById('pagu_display').value = formatNumber(parseInt(oldPagu));
        }
        updateSummary();
    });

    function buildCOA() {
        let parts = [
            document.getElementById('program_code').value.trim(),
            document.getElementById('activity_code').value.trim(),
            document.getElementById('output_code').value.trim(),
            document.getElementById('suboutput_code').value.trim(),
            document.getElementById('component_code').value.trim(),
            document.getElementById('subcomponent_code').value.trim(),
            document.getElementById('account_code').value.trim(),
            document.getElementById('item_code').value.trim(),
        ];

        let allFilled = parts.every(p => p.length > 0);
        let coa = parts.join('.');
        document.getElementById('coa_display').value = allFilled ? coa : '';
        document.getElementById('sum-coa').innerText = allFilled ? coa : '-';

        // Check duplicate (excluding current)
        let warning = document.getElementById('coa_warning');
        if (allFilled && existingCoas.includes(coa)) {
            warning.style.display = 'block';
        } else {
            warning.style.display = 'none';
        }

        document.getElementById('sum-tahun').innerText = document.getElementById('year').value;
    }

    function formatCurrency(el) {
        let raw = el.value.replace(/\D/g, '');
        let num = parseInt(raw) || 0;
        document.getElementById('initial_budget').value = num;
        el.value = formatNumber(num);
    }

    function formatNumber(n) {
        return n.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }

    function formatRupiah(n) {
        return 'Rp ' + formatNumber(n);
    }

    function updateSummary() {
        let pagu = parseInt(document.getElementById('initial_budget').value) || 0;
        let sisa = pagu - realizedBudget;
        document.getElementById('sum-pagu').innerText = formatRupiah(pagu);
        document.getElementById('sum-sisa').innerText = formatRupiah(sisa);
        document.getElementById('sum-status').innerText = document.getElementById('status_pagu').value;
        document.getElementById('sum-tahun').innerText = document.getElementById('year').value;
    }

    function confirmBatal() {
        return confirm('Apakah Anda yakin ingin membatalkan perubahan?');
    }
</script>
@endpush
