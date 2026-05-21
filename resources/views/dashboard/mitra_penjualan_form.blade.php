@extends('layouts.app')
@section('title', 'Input Laporan Penjualan')

@push('css')
@include('dashboard.partials.mitra-ui')
<style>
    .form-wizard-header { background: linear-gradient(135deg, #0f2f57 0%, #1d6fb8 100%); border-radius: 1rem 1rem 0 0; }
    .section-card { border: 2px solid #e9ecef; border-radius: 1rem; transition: border-color .2s; }
    .section-card:focus-within { border-color: #1d6fb8; box-shadow: 0 0 0 3px rgba(29,111,184,.1); }
    .summary-box { background: linear-gradient(135deg, #f0fdf4, #dcfce7); border: 2px solid #86efac; border-radius: 1rem; }
    .omzet-input { font-size: 1.25rem; font-weight: 700; padding: .75rem 1rem; border: 2px solid #e9ecef; border-radius: .75rem; }
    .omzet-input:focus { border-color: #1d6fb8; box-shadow: 0 0 0 3px rgba(29,111,184,.1); }
    .file-drop { border: 2px dashed #cbd5e1; border-radius: 1rem; padding: 2rem; text-align: center; transition: all .2s; cursor: pointer; background: #fafbfc; }
    .file-drop:hover, .file-drop.dragover { border-color: #1d6fb8; background: #f0f7ff; }
    .file-drop .file-icon { font-size: 2.5rem; color: #94a3b8; }
    .file-drop.has-file { border-color: #22c55e; background: #f0fdf4; }
    .file-drop.has-file .file-icon { color: #22c55e; }
    .tipe-card { cursor: pointer; border: 2px solid #e9ecef; border-radius: .75rem; padding: 1rem 1.25rem; transition: all .2s; user-select: none; }
    .tipe-card:hover { border-color: #1d6fb8; background: #f0f7ff; }
    .tipe-card.selected { border-color: #1d6fb8; background: linear-gradient(135deg, #e8f4fd, #d0eaff); box-shadow: 0 2px 8px rgba(29,111,184,.15); }
    .tipe-card .tipe-icon { font-size: 1.5rem; }
    .kontrak-info { background: linear-gradient(135deg, #fffbeb, #fef3c7); border: 1px solid #fcd34d; border-radius: .75rem; }
    .periode-badge { display: inline-flex; align-items: center; gap: .5rem; padding: .5rem 1rem; border-radius: .5rem; background: #e8f4fd; font-weight: 600; color: #0f2f57; }
</style>
@endpush

@section('content')
@php
    $konsesiLayanan = $konsesiContext['layanan'] ?? null;
    $konsesiLayanans = $konsesiContext['layanans'] ?? collect();
    $selectedLayananId = old('layanan_jasa_id', $konsesiLayanan?->id);
    $persentaseKonsesi = $konsesiContext['persentase'] ?? 5;
    $kontrak = $konsesiContext['kontrak'] ?? null;
@endphp

<div class="mp-hero mb-4">
    <div class="d-flex flex-column flex-lg-row align-items-start align-items-lg-center justify-content-between gap-3">
        <div class="d-flex align-items-start gap-3">
            <span class="mp-hero-icon"><i class="bi bi-graph-up-arrow fs-4"></i></span>
            <div>
                <div class="small text-white-50 fw-bold text-uppercase mb-1">Input Laporan</div>
                <h4 class="mb-1 fw-bold text-white">Laporan Pendapatan Konsesi</h4>
                <p class="mb-0 small fw-semibold text-white-50">{{ $mitra->nama_mitra }}</p>
            </div>
        </div>
        <a href="{{ route('mitra.konsesi-penjualan') }}" class="btn btn-light fw-bold text-primary shadow-sm">
            <i class="bi bi-arrow-left me-1"></i>Kembali
        </a>
    </div>
</div>

@if($errors->any())
    <div class="alert alert-danger rounded-3">
        <ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
@endif
@if(session('error'))
    <div class="alert alert-danger rounded-3"><i class="bi bi-exclamation-triangle me-1"></i>{{ session('error') }}</div>
@endif
@if(! $konsesiLayanan)
    <div class="alert alert-warning rounded-3"><i class="bi bi-info-circle me-1"></i>Belum ada layanan Konsesi yang ditugaskan kepada mitra ini. Hubungi Admin Jasa untuk pengaturan layanan aktif.</div>
@endif

{{-- Kontrak Info --}}
@if($kontrak)
    <div class="kontrak-info p-3 mb-4 d-flex align-items-center gap-3">
        <i class="bi bi-file-earmark-ruled fs-4" style="color:#d97706;"></i>
        <div>
            <div class="small text-muted fw-bold">Kontrak Aktif</div>
            <div class="fw-bold">{{ $kontrak->nomor_kontrak ?? $kontrak->nama_kontrak ?? '-' }}</div>
            <div class="small text-muted">
                Berlaku: {{ optional($kontrak->tanggal_mulai)->format('d/m/Y') ?? '-' }}
                s.d. {{ optional($kontrak->tanggal_selesai)->format('d/m/Y') ?? 'selesai' }}
            </div>
        </div>
    </div>
@endif

<form method="POST" action="{{ route('mitra.penjualan.store') }}" enctype="multipart/form-data" id="formPenjualan" class="mp-form">
    @csrf

    {{-- Section 1: Layanan --}}
    <div class="section-card p-4 mb-4">
        <div class="d-flex align-items-center gap-2 mb-3">
            <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:32px;height:32px;background:#e8f4fd;">
                <i class="bi bi-shop text-primary fw-bold"></i>
            </div>
            <h6 class="mb-0 fw-bold">Layanan Konsesi</h6>
        </div>
        <select name="layanan_jasa_id" id="selLayanan" class="form-select form-select-lg @error('layanan_jasa_id') is-invalid @enderror" required {{ $konsesiLayanans->isEmpty() ? 'disabled' : '' }}>
            <option value="">— Pilih layanan konsesi —</option>
            @foreach($konsesiLayanans as $layanan)
                <option value="{{ $layanan->id }}" data-persentase="{{ (float) $layanan->persentase_konsesi }}" {{ (int) $selectedLayananId === (int) $layanan->id ? 'selected' : '' }}>
                    {{ $layanan->nama_layanan }}
                </option>
            @endforeach
        </select>
        @error('layanan_jasa_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        <div class="small text-muted mt-1"><i class="bi bi-info-circle me-1"></i>Pilihan dari layanan Konsesi yang sudah ditugaskan kepada Anda.</div>
    </div>

    {{-- Section 2: Tipe & Periode --}}
    <div class="section-card p-4 mb-4">
        <div class="d-flex align-items-center gap-2 mb-3">
            <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:32px;height:32px;background:#fce7f3;">
                <i class="bi bi-calendar-range" style="color:#db2777;"></i>
            </div>
            <h6 class="mb-0 fw-bold">Periode Laporan</h6>
        </div>

        {{-- Tipe Periode Cards --}}
        <label class="form-label fw-bold text-muted small text-uppercase">Tipe Periode</label>
        <input type="hidden" name="periode_tipe" id="inputTipePeriode" value="{{ old('periode_tipe', 'harian') }}">
        <div class="row g-3 mb-4">
            <div class="col-6">
                <div class="tipe-card {{ old('periode_tipe', 'harian') === 'harian' ? 'selected' : '' }}" data-tipe="harian">
                    <div class="d-flex align-items-center gap-3">
                        <div class="tipe-icon"><i class="bi bi-calendar-day text-primary"></i></div>
                        <div>
                            <div class="fw-bold">Harian</div>
                            <div class="small text-muted">Laporan untuk 1 hari</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6">
                <div class="tipe-card {{ old('periode_tipe', 'harian') === 'mingguan' ? 'selected' : '' }}" data-tipe="mingguan">
                    <div class="d-flex align-items-center gap-3">
                        <div class="tipe-icon"><i class="bi bi-calendar-week text-primary"></i></div>
                        <div>
                            <div class="fw-bold">Mingguan</div>
                            <div class="small text-muted">Laporan rentang hari (maks 7 hari)</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Date Inputs --}}
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label fw-bold text-muted small text-uppercase" id="labelMulai">Tanggal</label>
                <input type="date" name="periode_mulai" id="inputPeriodeMulai" value="{{ old('periode_mulai', now()->toDateString()) }}" class="form-control form-control-lg" required>
            </div>
            <div class="col-md-6" id="wrapSelesai" style="display:none;">
                <label class="form-label fw-bold text-muted small text-uppercase">Sampai Tanggal</label>
                <input type="date" name="periode_selesai" id="inputPeriodeSelesai" value="{{ old('periode_selesai', now()->toDateString()) }}" class="form-control form-control-lg" required>
            </div>
        </div>

        {{-- Periode Summary --}}
        <div class="mt-3" id="wrapPeriodeSummary">
            <div class="periode-badge" id="periodeBadge">
                <i class="bi bi-calendar-check"></i>
                <span id="periodeSummaryText"></span>
            </div>
        </div>
    </div>

    {{-- Section 3: Omzet --}}
    <div class="section-card p-4 mb-4">
        <div class="d-flex align-items-center gap-2 mb-3">
            <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:32px;height:32px;background:#fef3c7;">
                <i class="bi bi-cash-coin" style="color:#d97706;"></i>
            </div>
            <h6 class="mb-0 fw-bold">Data Omzet</h6>
        </div>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label fw-bold text-muted small text-uppercase">Total Omzet (Rp)</label>
                <input type="number" step="0.01" min="0" name="total_omzet" id="inputOmzet" value="{{ old('total_omzet') }}" class="form-control omzet-input" placeholder="0" required>
                <div class="small text-muted mt-1">Total pendapatan/omzet periode ini.</div>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-bold text-muted small text-uppercase">Total Transaksi</label>
                <input type="number" min="0" name="total_transaksi" value="{{ old('total_transaksi') }}" class="form-control omzet-input" placeholder="Opsional">
                <div class="small text-muted mt-1">Jumlah transaksi (opsional).</div>
            </div>
        </div>

    </div>

    {{-- Section 4: Dokumen --}}
    <div class="section-card p-4 mb-4">
        <div class="d-flex align-items-center gap-2 mb-3">
            <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:32px;height:32px;background:#ede9fe;">
                <i class="bi bi-file-earmark-arrow-up" style="color:#7c3aed;"></i>
            </div>
            <h6 class="mb-0 fw-bold">Dokumen Pendukung</h6>
        </div>
        <label class="file-drop d-block mb-3" id="fileDrop" for="fileInput">
            <div class="file-icon"><i class="bi bi-cloud-arrow-up"></i></div>
            <div class="fw-bold mt-2" id="fileLabel">Klik atau seret file ke sini</div>
            <div class="small text-muted">PDF, Excel, CSV, atau Gambar — Maks. 5 MB</div>
            <input type="file" name="file_laporan" id="fileInput" class="d-none" accept=".pdf,.xlsx,.xls,.csv,.jpg,.jpeg,.png" required>
        </label>
        <label class="form-label fw-bold text-muted small text-uppercase">Catatan (Opsional)</label>
        <textarea name="catatan_mitra" rows="3" class="form-control" placeholder="Catatan tambahan untuk Admin Jasa...">{{ old('catatan_mitra') }}</textarea>
    </div>

    {{-- Submit --}}
    <div class="d-flex justify-content-between align-items-center">
        <a href="{{ route('mitra.konsesi-penjualan') }}" class="btn btn-light border fw-bold px-4">Batal</a>
        <button type="submit" class="btn btn-primary fw-bold px-5 py-2" {{ ! $konsesiLayanan ? 'disabled' : '' }}>
            <i class="bi bi-send me-1"></i>Kirim Laporan
        </button>
    </div>
</form>
@endsection

@push('script')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const tipeCards = document.querySelectorAll('.tipe-card');
    const inputTipe = document.getElementById('inputTipePeriode');
    const inputMulai = document.getElementById('inputPeriodeMulai');
    const inputSelesai = document.getElementById('inputPeriodeSelesai');
    const wrapSelesai = document.getElementById('wrapSelesai');
    const labelMulai = document.getElementById('labelMulai');
    const summaryText = document.getElementById('periodeSummaryText');
    const selLayanan = document.getElementById('selLayanan');
    const inputOmzet = document.getElementById('inputOmzet');

    const fileDrop = document.getElementById('fileDrop');
    const fileInput = document.getElementById('fileInput');
    const fileLabel = document.getElementById('fileLabel');

    const fmtDate = str => {
        if (!str) return '-';
        const d = new Date(str);
        const days = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
        return days[d.getDay()] + ', ' + String(d.getDate()).padStart(2,'0') + '/' + String(d.getMonth()+1).padStart(2,'0') + '/' + d.getFullYear();
    };
    const fmtRp = v => new Intl.NumberFormat('id-ID',{style:'currency',currency:'IDR',minimumFractionDigits:0,maximumFractionDigits:0}).format(v||0);

    // Tipe Periode toggle
    function setTipe(tipe) {
        inputTipe.value = tipe;
        tipeCards.forEach(c => c.classList.toggle('selected', c.dataset.tipe === tipe));
        if (tipe === 'harian') {
            wrapSelesai.style.display = 'none';
            labelMulai.textContent = 'Tanggal';
            inputSelesai.value = inputMulai.value;
        } else {
            wrapSelesai.style.display = '';
            labelMulai.textContent = 'Dari Tanggal';
            // Auto-fill end date +6 days if empty or same as start
            if (!inputSelesai.value || inputSelesai.value === inputMulai.value) {
                autoFillEndDate();
            }
        }
        updateSummary();
    }

    function autoFillEndDate() {
        if (!inputMulai.value) return;
        const start = new Date(inputMulai.value);
        start.setDate(start.getDate() + 6);
        inputSelesai.value = start.toISOString().split('T')[0];
    }

    function updateSummary() {
        const tipe = inputTipe.value;
        if (tipe === 'harian') {
            summaryText.textContent = fmtDate(inputMulai.value);
        } else {
            const s = new Date(inputMulai.value), e = new Date(inputSelesai.value);
            const diffDays = Math.round((e - s) / (1000 * 60 * 60 * 24)) + 1;
            summaryText.textContent = fmtDate(inputMulai.value) + ' s.d. ' + fmtDate(inputSelesai.value) + ' (' + diffDays + ' hari)';
        }
    }



    tipeCards.forEach(card => card.addEventListener('click', () => setTipe(card.dataset.tipe)));

    inputMulai.addEventListener('change', function() {
        if (inputTipe.value === 'harian') {
            inputSelesai.value = inputMulai.value;
        } else {
            autoFillEndDate();
        }
        updateSummary();
    });
    inputSelesai.addEventListener('change', updateSummary);



    // File drag-and-drop
    ['dragenter','dragover'].forEach(e => fileDrop.addEventListener(e, ev => { ev.preventDefault(); fileDrop.classList.add('dragover'); }));
    ['dragleave','drop'].forEach(e => fileDrop.addEventListener(e, ev => { ev.preventDefault(); fileDrop.classList.remove('dragover'); }));
    fileDrop.addEventListener('drop', ev => { if(ev.dataTransfer.files.length){ fileInput.files = ev.dataTransfer.files; showFileName(); }});
    fileInput.addEventListener('change', showFileName);
    function showFileName() {
        if (fileInput.files.length) { fileLabel.textContent = fileInput.files[0].name; fileDrop.classList.add('has-file'); }
        else { fileLabel.textContent = 'Klik atau seret file ke sini'; fileDrop.classList.remove('has-file'); }
    }

    // Init
    setTipe(inputTipe.value);
});
</script>
@endpush
