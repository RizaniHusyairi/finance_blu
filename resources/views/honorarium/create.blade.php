@extends('layouts.app')

@section('title')
    Tambah Honorarium
@endsection

@push('css')
<style>
    .summary-card { position: sticky; top: 80px; }
    .checklist-item { display: flex; align-items: center; gap: 8px; padding: 6px 0; font-size: 13px; }
    .checklist-item .bi { font-size: 16px; }
    .checklist-ok { color: #20c997; }
    .checklist-fail { color: #dc3545; }
    .section-icon { width: 32px; height: 32px; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center; font-size: 14px; }
</style>
@endpush

@section('content')
    <x-page-title title="Manajemen Honor" subtitle="Tambah Honorarium" />

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h6 class="mb-1 text-uppercase fw-bold"><i class="bi bi-plus-circle me-1"></i> Tambah Honorarium</h6>
            <p class="text-muted small mb-0">Lengkapi data kegiatan, penerima, dan komponen pembayaran.</p>
        </div>
        <a href="{{ route('honorarium.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Kembali ke Daftar
        </a>
    </div>
    <hr>

    {{-- Validation Errors --}}
    @if ($errors->any())
        <div class="alert alert-danger border-0 bg-danger alert-dismissible fade show py-2 mb-3">
            <div class="text-white">
                <i class="bi bi-exclamation-triangle me-1"></i>
                <strong>Terdapat kesalahan pada formulir:</strong>
                <ul class="mb-0 mt-1 ps-3">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <form action="{{ route('honorarium.store') }}" method="POST" enctype="multipart/form-data" id="honorariumForm">
        @csrf
        <div class="row">
            {{-- LEFT COLUMN --}}
            <div class="col-lg-8">

                {{-- Section A: Informasi Pengajuan --}}
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom py-3">
                        <div class="d-flex align-items-center gap-2">
                            <span class="section-icon bg-primary bg-opacity-10 text-primary"><i class="bi bi-info-circle-fill"></i></span>
                            <div>
                                <h6 class="fw-bold mb-0 text-primary">A. Informasi Pengajuan</h6>
                                <small class="text-muted">Data dasar honorarium dan sumber anggaran</small>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">No Tagihan</label>
                                <input type="text" class="form-control bg-light" value="{{ $nextNumber }}" readonly>
                                <div class="form-text">Nomor otomatis</div>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label fw-semibold">Uraian / Deskripsi Kegiatan <span class="text-danger">*</span></label>
                                <input type="text" name="deskripsi" id="inp_deskripsi" class="form-control" required
                                    value="{{ old('deskripsi') }}" placeholder="Contoh: Pembayaran Honor Narasumber Sosialisasi...">
                            </div>
                        </div>
                        <div class="row g-3 mt-1">
                            <div class="col-md-12">
                                @include('partials.dipa-item-grouped-select', [
                                    'budgetGroups' => $budgetGroups,
                                    'fieldName' => 'dipa_revision_item_id',
                                    'fieldId' => 'dipa_revision_item_id',
                                    'fieldClass' => 'form-select select2',
                                    'fieldLabel' => 'Sumber Anggaran (Item DIPA / COA)',
                                    'placeholder' => '-- Pilih Item Anggaran DIPA Aktif --',
                                ])
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Section B: Verifikator --}}
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom py-3">
                        <div class="d-flex align-items-center gap-2">
                            <span class="section-icon bg-info bg-opacity-10 text-info"><i class="bi bi-person-check-fill"></i></span>
                            <div>
                                <h6 class="fw-bold mb-0 text-info">B. Verifikator</h6>
                                <small class="text-muted">Pilih pejabat yang akan memverifikasi pengajuan</small>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Pejabat Pembuat Komitmen (PPK) <span class="text-danger">*</span></label>
                                <select name="ppk_id" id="inp_ppk" class="form-select select2" required>
                                    <option value="">-- Pilih PPK --</option>
                                    @foreach($ppkUsers as $ppk)
                                        <option value="{{ $ppk->id }}" {{ old('ppk_id') == $ppk->id ? 'selected' : '' }}>{{ $ppk->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Bendahara Pengeluaran <span class="text-danger">*</span></label>
                                <select name="bendahara_pengeluaran_id" id="inp_bendahara" class="form-select select2" required>
                                    <option value="">-- Pilih Bendahara Pengeluaran --</option>
                                    @foreach($bendaharaUsers as $bendahara)
                                        <option value="{{ $bendahara->id }}" {{ old('bendahara_pengeluaran_id') == $bendahara->id ? 'selected' : '' }}>{{ $bendahara->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Section C: Rincian Penerima --}}
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom py-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center gap-2">
                                <span class="section-icon bg-success bg-opacity-10 text-success"><i class="bi bi-people-fill"></i></span>
                                <div>
                                    <h6 class="fw-bold mb-0 text-success">C. Rincian Penerima Honorarium</h6>
                                    <small class="text-muted">Tambahkan data penerima dan nominal honor</small>
                                </div>
                            </div>
                            <button type="button" class="btn btn-sm btn-success" id="btnAddRow">
                                <i class="bi bi-plus-circle"></i> Tambah Penerima
                            </button>
                        </div>
                    </div>
                    <div class="card-body p-3 bg-light">
                        <div id="penerimaContainer">
                            {{-- Penerima cards will be appended here by JS --}}
                        </div>
                    </div>
                </div>

                {{-- Section D: Dokumen Pendukung --}}
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom py-3">
                        <div class="d-flex align-items-center gap-2">
                            <span class="section-icon bg-warning bg-opacity-10 text-warning"><i class="bi bi-file-earmark-arrow-up-fill"></i></span>
                            <div>
                                <h6 class="fw-bold mb-0 text-warning">D. Dokumen Pendukung</h6>
                                <small class="text-muted">Upload file SK atau dokumen dasar pembayaran</small>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <div class="row">
                            <div class="col-md-8">
                                <label class="form-label fw-semibold">File SK Honorarium <span class="badge bg-light text-muted border">Opsional</span></label>
                                <input type="file" name="file_sk" id="file_sk" accept=".pdf,.doc,.docx" class="form-control">
                                <div class="form-text"><i class="bi bi-info-circle"></i> Format: PDF, DOC, DOCX (Maks 10MB). File akan tersimpan sebagai lampiran dokumen.</div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Action Buttons --}}
                <div class="d-flex gap-2 mb-4">
                    <button type="submit" name="submit_type" value="draft" class="btn btn-secondary px-4">
                        <i class="bi bi-save me-1"></i> Simpan Draft
                    </button>
                    <button type="submit" name="submit_type" value="submit_ppk" class="btn btn-primary px-4" id="btnSubmitPpk">
                        <i class="bi bi-send me-1"></i> Simpan & Ajukan ke PPK
                    </button>
                    <a href="{{ route('honorarium.index') }}" class="btn btn-outline-secondary px-4">Batal</a>
                </div>
            </div>

            {{-- RIGHT COLUMN: Sticky Summary --}}
            <div class="col-lg-4">
                <div class="summary-card">
                    {{-- Ringkasan Nilai --}}
                    <div class="card border-0 shadow-sm mb-3">
                        <div class="card-header bg-white border-bottom py-3">
                            <h6 class="fw-bold mb-0"><i class="bi bi-calculator me-1 text-primary"></i> Ringkasan Nilai</h6>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-borderless mb-0" style="font-size:13px;">
                                <tbody>
                                    <tr>
                                        <td class="text-muted ps-3 py-2">Jumlah Penerima</td>
                                        <td class="text-end fw-bold pe-3 py-2" id="sumRowCount">0</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted ps-3 py-2">Total Bruto</td>
                                        <td class="text-end fw-bold pe-3 py-2" id="sumBruto">Rp 0</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted ps-3 py-2">Total PPh</td>
                                        <td class="text-end fw-bold text-danger pe-3 py-2" id="sumPph">Rp 0</td>
                                    </tr>
                                    <tr class="border-top">
                                        <td class="ps-3 py-2 fw-bold">Total Netto</td>
                                        <td class="text-end fw-bold text-success pe-3 py-2 fs-6" id="sumNetto">Rp 0</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- Checklist Kelengkapan --}}
                    <div class="card border-0 shadow-sm mb-3">
                        <div class="card-header bg-white border-bottom py-3">
                            <h6 class="fw-bold mb-0"><i class="bi bi-clipboard-check me-1 text-success"></i> Checklist Kelengkapan</h6>
                        </div>
                        <div class="card-body px-3 py-2">
                            <div class="checklist-item" id="chk_deskripsi">
                                <i class="bi bi-x-circle-fill checklist-fail"></i>
                                <span>Deskripsi kegiatan diisi</span>
                            </div>
                            <div class="checklist-item" id="chk_dipa">
                                <i class="bi bi-x-circle-fill checklist-fail"></i>
                                <span>Sumber anggaran dipilih</span>
                            </div>
                            <div class="checklist-item" id="chk_ppk">
                                <i class="bi bi-x-circle-fill checklist-fail"></i>
                                <span>Verifikator PPK dipilih</span>
                            </div>
                            <div class="checklist-item" id="chk_bendahara">
                                <i class="bi bi-x-circle-fill checklist-fail"></i>
                                <span>Bendahara Pengeluaran dipilih</span>
                            </div>
                            <div class="checklist-item" id="chk_penerima">
                                <i class="bi bi-x-circle-fill checklist-fail"></i>
                                <span>Minimal 1 penerima</span>
                            </div>
                            <div class="checklist-item" id="chk_nominal">
                                <i class="bi bi-x-circle-fill checklist-fail"></i>
                                <span>Nominal penerima valid (> 0)</span>
                            </div>
                        </div>
                    </div>

                    {{-- Catatan --}}
                    <div class="card border-0 shadow-sm bg-light">
                        <div class="card-body p-3">
                            <p class="small text-muted mb-2"><i class="bi bi-lightbulb text-warning me-1"></i> <strong>Catatan:</strong></p>
                            <ul class="small text-muted mb-0 ps-3">
                                <li><strong>Simpan Draft</strong> — data tersimpan dan dapat diedit kembali.</li>
                                <li><strong>Ajukan ke PPK</strong> — data dikirim untuk verifikasi dan tidak bisa diedit sampai disetujui/ditolak.</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <script>
    let rowIndex = 0;

    function toNumber(value) { return parseFloat(String(value).replace(/,/g, '')) || 0; }
    function formatRp(value) { return new Intl.NumberFormat('id-ID').format(Math.round(value)); }

    function updateRowNumbers() {
        document.querySelectorAll('#penerimaContainer .penerima-row').forEach((row, i) => {
            row.querySelector('.row-no').textContent = i + 1;
        });
    }

    function recalcRow(row) {
        const honor = toNumber(row.querySelector('.honor_amount').value);
        const pphSelect = row.querySelector('.pph_percentage');
        let pphPct = 0;
        if (pphSelect) pphPct = toNumber(pphSelect.options[pphSelect.selectedIndex].getAttribute('data-pct'));
        let pphAmount = (honor * pphPct) / 100;
        row.querySelector('.pph_amount').value = pphAmount;
        if(row.querySelector('.pph_display')) {
            row.querySelector('.pph_display').value = formatRp(pphAmount);
        }
        row.querySelector('.netto_display').value = formatRp(honor - Math.round(pphAmount));
    }

    function recalcGrandTotal() {
        let tHonor = 0, tPph = 0, tNetto = 0, rowCount = 0;
        document.querySelectorAll('#penerimaContainer .penerima-row').forEach(row => {
            const h = toNumber(row.querySelector('.honor_amount').value);
            const p = toNumber(row.querySelector('.pph_amount').value);
            tHonor += h; tPph += p; tNetto += (h - p);
            rowCount++;
        });

        // Update sidebar summary
        document.getElementById('sumRowCount').textContent = rowCount;
        document.getElementById('sumBruto').textContent = 'Rp ' + formatRp(tHonor);
        document.getElementById('sumPph').textContent = 'Rp ' + formatRp(tPph);
        document.getElementById('sumNetto').textContent = 'Rp ' + formatRp(tNetto);
    }

    function recalcAll() {
        document.querySelectorAll('#penerimaContainer .penerima-row').forEach(row => recalcRow(row));
        recalcGrandTotal();
        updateRowNumbers();
        updateChecklist();
    }

    function addRow(data = {}) {
        const container = document.getElementById('penerimaContainer');
        const div = document.createElement('div');
        div.className = 'card border border-success border-opacity-25 shadow-sm penerima-row mb-3';
        div.innerHTML = `
            <div class="card-header bg-success bg-opacity-10 py-2 d-flex justify-content-between align-items-center border-bottom-0">
                <h6 class="fw-bold text-success mb-0 m-0"><i class="bi bi-person-fill me-1"></i> Penerima #<span class="row-no"></span></h6>
                <button type="button" class="btn btn-sm btn-outline-danger btn-remove-row" title="Hapus penerima ini">
                    <i class="bi bi-trash"></i> Hapus
                </button>
            </div>
            <div class="card-body p-3 bg-white">
                <div class="row g-3">
                    <div class="col-md-6 col-lg-3">
                        <label class="form-label small fw-semibold text-muted mb-1">Nama Lengkap <span class="text-danger">*</span></label>
                        <input type="text" name="items[${rowIndex}][nama_personel]" class="form-control form-control-sm border-start-0 border-top-0 border-end-0 border-primary rounded-0 px-0 focus-ring-none" value="${data.nama_personel ?? ''}" required placeholder="Contoh: Budi Santoso">
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <label class="form-label small fw-semibold text-muted mb-1">NIP/NRP/NIK</label>
                        <input type="text" name="items[${rowIndex}][nrp_nip]" class="form-control form-control-sm border-start-0 border-top-0 border-end-0 rounded-0 px-0 focus-ring-none" value="${data.nrp_nip ?? ''}" placeholder="Opsional">
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <label class="form-label small fw-semibold text-muted mb-1">Pangkat/Korp</label>
                        <input type="text" name="items[${rowIndex}][pangkat_korp]" class="form-control form-control-sm border-start-0 border-top-0 border-end-0 rounded-0 px-0 focus-ring-none" value="${data.pangkat_korp ?? ''}" placeholder="Opsional">
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <label class="form-label small fw-semibold text-muted mb-1">Jabatan</label>
                        <input type="text" name="items[${rowIndex}][jabatan]" class="form-control form-control-sm border-start-0 border-top-0 border-end-0 rounded-0 px-0 focus-ring-none" value="${data.jabatan ?? ''}" placeholder="Opsional">
                    </div>

                    <div class="col-md-4 col-lg-4 mt-4">
                        <label class="form-label small fw-semibold text-muted mb-1">Nilai Honor (Bruto) <span class="text-danger">*</span></label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light border-0">Rp</span>
                            <input type="number" step="0.01" min="0" name="items[${rowIndex}][nilai_honor]" class="form-control form-control-sm border-start-0 border-top-0 border-end-0 border-success rounded-0 px-1 fw-bold honor_amount focus-ring-none" value="${data.nilai_honor ?? 0}" required>
                        </div>
                    </div>
                    <div class="col-md-4 col-lg-4 mt-4">
                        <label class="form-label small fw-semibold text-muted mb-1">Potongan PPh</label>
                        <div class="d-flex gap-2">
                            <select class="form-select form-select-sm border-start-0 border-top-0 border-end-0 rounded-0 px-0 pph_percentage focus-ring-none" style="max-width: 130px;">
                                <option value="0" data-pct="0">0% (Tanpa PPh)</option>
                                @foreach($tarifPajaks as $tp)
                                    <option value="{{ $tp->persentase }}" data-pct="{{ $tp->persentase }}" ${data.pph_pct == {{ $tp->persentase }} ? 'selected' : ''}>
                                        {{ $tp->kode_pajak }} ({{ (float)$tp->persentase }}%)
                                    </option>
                                @endforeach
                            </select>
                            <div class="input-group input-group-sm flex-fill">
                                <span class="input-group-text bg-light border-0">Rp</span>
                                <input type="text" class="form-control form-control-sm pph_display border-start-0 border-top-0 border-end-0 border-danger rounded-0 px-1 text-danger focus-ring-none" readonly value="${formatRp(data.pph ?? 0)}">
                            </div>
                        </div>
                        <input type="hidden" name="items[${rowIndex}][pph]" class="pph_amount" value="${data.pph ?? 0}">
                    </div>
                    <div class="col-md-4 col-lg-4 mt-4">
                        <label class="form-label small fw-semibold text-muted mb-1">Nilai Netto (Diterima)</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light border-0">Rp</span>
                            <input type="text" class="form-control form-control-sm fw-bold text-success netto_display border-start-0 border-top-0 border-end-0 border-success rounded-0 px-1 focus-ring-none" readonly>
                        </div>
                    </div>

                    <div class="col-md-3 col-lg-3 mt-4">
                        <label class="form-label small fw-semibold text-muted mb-1">Jenis Bank</label>
                        <input type="text" name="items[${rowIndex}][jenis_bank]" class="form-control form-control-sm border-start-0 border-top-0 border-end-0 rounded-0 px-0 focus-ring-none" value="${data.jenis_bank ?? ''}" placeholder="Contoh: BRI, Mandiri">
                    </div>
                    <div class="col-md-3 col-lg-3 mt-4">
                        <label class="form-label small fw-semibold text-muted mb-1">No. Rekening</label>
                        <input type="text" name="items[${rowIndex}][rekening]" class="form-control form-control-sm border-start-0 border-top-0 border-end-0 rounded-0 px-0 focus-ring-none" value="${data.rekening ?? ''}" placeholder="Opsional">
                    </div>
                    <div class="col-md-3 col-lg-3 mt-4">
                        <label class="form-label small fw-semibold text-muted mb-1">Atas Nama Rekening</label>
                        <input type="text" name="items[${rowIndex}][nama_rekening]" class="form-control form-control-sm border-start-0 border-top-0 border-end-0 rounded-0 px-0 focus-ring-none" value="${data.nama_rekening ?? ''}" placeholder="Opsional">
                    </div>
                    <div class="col-md-3 col-lg-3 mt-4">
                        <label class="form-label small fw-semibold text-muted mb-1">No. HP (WhatsApp)</label>
                        <input type="text" name="items[${rowIndex}][no_hp]" class="form-control form-control-sm border-start-0 border-top-0 border-end-0 rounded-0 px-0 focus-ring-none" value="${data.no_hp ?? ''}" placeholder="Contoh: 08123456789">
                    </div>
                </div>
            </div>
        `;
        container.appendChild(div);
        rowIndex++;
        recalcAll();
    }

    // Checklist logic
    function updateChecklist() {
        const checks = {
            chk_deskripsi: document.getElementById('inp_deskripsi')?.value.trim().length > 0,
            chk_dipa: document.getElementById('dipa_revision_item_id')?.value.length > 0,
            chk_ppk: document.getElementById('inp_ppk')?.value.length > 0,
            chk_bendahara: document.getElementById('inp_bendahara')?.value.length > 0,
            chk_penerima: document.querySelectorAll('#penerimaContainer .penerima-row').length > 0,
            chk_nominal: false,
        };

        // Check if at least one row has honor > 0
        let hasValidNominal = false;
        document.querySelectorAll('#penerimaContainer .penerima-row .honor_amount').forEach(el => {
            if (toNumber(el.value) > 0) hasValidNominal = true;
        });
        checks.chk_nominal = hasValidNominal;

        let allGood = true;
        for (const [id, ok] of Object.entries(checks)) {
            const el = document.getElementById(id);
            if (!el) continue;
            const icon = el.querySelector('i');
            if (ok) {
                icon.className = 'bi bi-check-circle-fill checklist-ok';
            } else {
                icon.className = 'bi bi-x-circle-fill checklist-fail';
                allGood = false;
            }
        }

        const btnSubmit = document.getElementById('btnSubmitPpk');
        if (btnSubmit) {
            btnSubmit.disabled = !allGood;
            btnSubmit.title = allGood ? '' : 'Lengkapi semua checklist terlebih dahulu';
        }
    }

    // Event listeners
    document.getElementById('btnAddRow').addEventListener('click', () => addRow());

    document.addEventListener('input', function (e) {
        if (e.target.classList.contains('honor_amount') || e.target.classList.contains('pph_amount')) {
            recalcRow(e.target.closest('.penerima-row'));
            recalcGrandTotal();
            updateChecklist();
        }
        if (e.target.id === 'inp_deskripsi') updateChecklist();
    });

    document.addEventListener('change', function (e) {
        if (e.target.classList.contains('pph_percentage')) {
            recalcRow(e.target.closest('.penerima-row'));
            recalcGrandTotal();
            updateChecklist();
        }
        if (e.target.id === 'dipa_revision_item_id' || e.target.id === 'inp_ppk' || e.target.id === 'inp_bendahara') {
            updateChecklist();
        }
    });

    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.btn-remove-row');
        if (btn) {
            btn.closest('.penerima-row').remove();
            recalcAll();
        }
    });

    // Select2
    if (window.jQuery && typeof window.jQuery.fn.select2 === 'function') {
        window.jQuery('.select2').select2({
            theme: 'bootstrap-5',
            width: '100%',
            placeholder: function() { return $(this).data('placeholder') || '-- Pilih --'; }
        });
        window.jQuery('.select2').on('change', function() { updateChecklist(); });
    }

    // Init
    addRow();
    updateChecklist();
    </script>
@endsection
