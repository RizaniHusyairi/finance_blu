@extends('layouts.app')
@section('title', $penjualan->exists ? 'Edit Laporan Penjualan' : 'Tambah Laporan Penjualan')

@push('css')
<style>
    .form-wizard-header { background: linear-gradient(135deg, #0f2f57 0%, #1d6fb8 100%); border-radius: 1rem 1rem 0 0; }
    .section-card { border: 2px solid #e9ecef; border-radius: 1rem; transition: border-color .2s; }
    .section-card:focus-within { border-color: #1d6fb8; box-shadow: 0 0 0 3px rgba(29,111,184,.1); }
    .summary-box { background: linear-gradient(135deg, #f0fdf4, #dcfce7); border: 2px solid #86efac; border-radius: 1rem; }
    .omzet-input { font-size: 1.1rem; font-weight: 700; padding: .6rem 1rem; border: 2px solid #e9ecef; border-radius: .75rem; }
    .omzet-input:focus { border-color: #1d6fb8; box-shadow: 0 0 0 3px rgba(29,111,184,.1); }
    .file-drop { border: 2px dashed #cbd5e1; border-radius: 1rem; padding: 1.5rem; text-align: center; transition: all .2s; cursor: pointer; background: #fafbfc; }
    .file-drop:hover, .file-drop.dragover { border-color: #1d6fb8; background: #f0f7ff; }
    .file-drop .file-icon { font-size: 2rem; color: #94a3b8; }
    .file-drop.has-file { border-color: #22c55e; background: #f0fdf4; }
    .file-drop.has-file .file-icon { color: #22c55e; }
    .tipe-card { cursor: pointer; border: 2px solid #e9ecef; border-radius: .75rem; padding: 1rem 1.25rem; transition: all .2s; user-select: none; }
    .tipe-card:hover { border-color: #1d6fb8; background: #f0f7ff; }
    .tipe-card.selected { border-color: #1d6fb8; background: linear-gradient(135deg, #e8f4fd, #d0eaff); }
    .kontrak-info { background: linear-gradient(135deg, #fffbeb, #fef3c7); border: 1px solid #fcd34d; border-radius: .75rem; }
    .periode-badge { display: inline-flex; align-items: center; gap: .5rem; padding: .5rem 1rem; border-radius: .5rem; background: #e8f4fd; font-weight: 600; color: #0f2f57; }
</style>
@endpush

@section('content')
@php
    $konsesiLayanan = $konsesiContext['layanan'] ?? null;
    $konsesiLayanans = $konsesiContext['layanans'] ?? collect();
    $selectedLayananId = old('layanan_jasa_id', $penjualan->layanan_jasa_id ?: $konsesiLayanan?->id);
    $persentaseKonsesi = $konsesiContext['persentase'] ?? 5;
    $editMode = $penjualan->exists;
    $kontrak = $konsesiContext['kontrak'] ?? null;
    $periodeMulai = old('periode_mulai', optional($penjualan->periode_mulai)->format('Y-m-d') ?: now()->toDateString());
    $periodeSelesai = old('periode_selesai', optional($penjualan->periode_selesai)->format('Y-m-d') ?: now()->toDateString());
@endphp

<div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
    <div class="form-wizard-header p-4 text-white">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h4 class="fw-bold mb-0 text-white">{{ $editMode ? 'Edit Laporan Penjualan' : 'Tambah Laporan Penjualan' }}</h4>
                <div class="small text-white-50 mt-1">{{ $mitra->nama_mitra }}</div>
            </div>
            <a href="{{ route('jasa.mitra.show', $mitra) }}" class="btn btn-light btn-sm fw-bold jasa-icon-btn" title="Kembali" aria-label="Kembali"><i class="bi bi-arrow-left"></i></a>
        </div>
    </div>
</div>

@if($errors->any())
    <div class="alert alert-danger rounded-3"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
@endif
@if(! $konsesiLayanan)
    <div class="alert alert-warning rounded-3"><i class="bi bi-info-circle me-1"></i>Mitra belum memiliki layanan Konsesi yang pernah ditagihkan.</div>
@endif

@if($kontrak)
    <div class="kontrak-info p-3 mb-4 d-flex align-items-center gap-3">
        <i class="bi bi-file-earmark-ruled fs-4" style="color:#d97706;"></i>
        <div>
            <div class="small text-muted fw-bold">Kontrak Aktif</div>
            <div class="fw-bold">{{ $kontrak->nomor_kontrak ?? $kontrak->nama_kontrak ?? '-' }}</div>
            <div class="small text-muted">Berlaku: {{ optional($kontrak->tanggal_mulai)->format('d/m/Y') ?? '-' }} s.d. {{ optional($kontrak->tanggal_selesai)->format('d/m/Y') ?? 'selesai' }}</div>
        </div>
    </div>
@endif

<form method="POST" enctype="multipart/form-data" action="{{ $editMode ? route('jasa.mitra.penjualan.update', [$mitra, $penjualan]) : route('jasa.mitra.penjualan.store', $mitra) }}">
    @csrf
    @if($editMode) @method('PUT') @endif

    {{-- Section 1: Layanan --}}
    <div class="section-card p-4 mb-4">
        <div class="d-flex align-items-center gap-2 mb-3">
            <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:32px;height:32px;background:#e8f4fd;"><i class="bi bi-shop text-primary fw-bold"></i></div>
            <h6 class="mb-0 fw-bold">Layanan Konsesi</h6>
        </div>
        <select name="layanan_jasa_id" id="selLayanan" class="form-select form-select-lg @error('layanan_jasa_id') is-invalid @enderror" required {{ $konsesiLayanans->isEmpty() ? 'disabled' : '' }}>
            <option value="">— Pilih layanan konsesi —</option>
            @foreach($konsesiLayanans as $layanan)
                <option value="{{ $layanan->id }}" data-persentase="{{ (float) $layanan->persentase_konsesi }}" {{ (int) $selectedLayananId === (int) $layanan->id ? 'selected' : '' }}>
                    {{ $layanan->nama_layanan }} — {{ rtrim(rtrim(number_format((float) $layanan->persentase_konsesi, 4, ',', '.'), '0'), ',') }}%
                </option>
            @endforeach
        </select>
        @error('layanan_jasa_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    {{-- Section 2: Periode --}}
    <div class="section-card p-4 mb-4">
        <div class="d-flex align-items-center gap-2 mb-3">
            <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:32px;height:32px;background:#fce7f3;"><i class="bi bi-calendar-range" style="color:#db2777;"></i></div>
            <h6 class="mb-0 fw-bold">Periode Laporan</h6>
        </div>
        <input type="hidden" name="periode_tipe" id="inputTipePeriode" value="{{ old('periode_tipe', $penjualan->periode_tipe ?: 'harian') }}">
        <div class="row g-3 mb-4">
            <div class="col-6">
                <div class="tipe-card {{ old('periode_tipe', $penjualan->periode_tipe ?: 'harian') === 'harian' ? 'selected' : '' }}" data-tipe="harian">
                    <div class="d-flex align-items-center gap-3"><div class="tipe-icon">📅</div><div><div class="fw-bold">Harian</div><div class="small text-muted">1 hari</div></div></div>
                </div>
            </div>
            <div class="col-6">
                <div class="tipe-card {{ old('periode_tipe', $penjualan->periode_tipe ?: 'harian') === 'mingguan' ? 'selected' : '' }}" data-tipe="mingguan">
                    <div class="d-flex align-items-center gap-3"><div class="tipe-icon">📆</div><div><div class="fw-bold">Mingguan</div><div class="small text-muted">Rentang hari</div></div></div>
                </div>
            </div>
        </div>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label fw-bold text-muted small text-uppercase" id="labelMulai">Tanggal</label>
                <input type="date" name="periode_mulai" id="inputPeriodeMulai" value="{{ $periodeMulai }}" class="form-control form-control-lg" required>
            </div>
            <div class="col-md-6" id="wrapSelesai" style="display:none;">
                <label class="form-label fw-bold text-muted small text-uppercase">Sampai Tanggal</label>
                <input type="date" name="periode_selesai" id="inputPeriodeSelesai" value="{{ $periodeSelesai }}" class="form-control form-control-lg" required>
            </div>
        </div>
        <div class="mt-3"><div class="periode-badge"><i class="bi bi-calendar-check"></i><span id="periodeSummaryText"></span></div></div>
    </div>

    {{-- Section 3: Omzet --}}
    <div class="section-card p-4 mb-4">
        <div class="d-flex align-items-center gap-2 mb-3">
            <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:32px;height:32px;background:#fef3c7;"><i class="bi bi-cash-coin" style="color:#d97706;"></i></div>
            <h6 class="mb-0 fw-bold">Data Omzet</h6>
        </div>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label fw-bold text-muted small text-uppercase">Total Omzet (Rp)</label>
                <input type="number" step="0.01" min="0" name="total_omzet" id="inputOmzet" value="{{ old('total_omzet', $penjualan->total_omzet) }}" class="form-control omzet-input" placeholder="0" required>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-bold text-muted small text-uppercase">Total Transaksi</label>
                <input type="number" min="0" name="total_transaksi" value="{{ old('total_transaksi', $penjualan->total_transaksi) }}" class="form-control omzet-input" placeholder="Opsional">
            </div>
        </div>
        <div class="summary-box p-4 mt-4">
            <div class="row g-3 align-items-center">
                <div class="col-md-4 text-center"><div class="small text-muted fw-bold">PERSENTASE</div><div class="fs-3 fw-bold" style="color:#0f2f57;" id="previewPersen">0%</div></div>
                <div class="col-md-4 text-center"><div class="small text-muted fw-bold">TOTAL OMZET</div><div class="fs-5 fw-bold text-dark" id="previewOmzet">Rp 0</div></div>
                <div class="col-md-4 text-center"><div class="small text-muted fw-bold">NILAI TAGIHAN</div><div class="fs-3 fw-bold text-success" id="previewTagihan">Rp 0</div></div>
            </div>
        </div>
    </div>

    {{-- Section 4: Dokumen --}}
    <div class="section-card p-4 mb-4">
        <div class="d-flex align-items-center gap-2 mb-3">
            <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:32px;height:32px;background:#ede9fe;"><i class="bi bi-file-earmark-arrow-up" style="color:#7c3aed;"></i></div>
            <h6 class="mb-0 fw-bold">Dokumen & Catatan</h6>
        </div>
        <label class="file-drop d-block mb-3" id="fileDrop" for="fileInput">
            <div class="file-icon"><i class="bi bi-cloud-arrow-up"></i></div>
            <div class="fw-bold mt-2" id="fileLabel">{{ $penjualan->file_laporan ? 'File lama tersimpan — klik untuk mengganti' : 'Klik atau seret file ke sini' }}</div>
            <div class="small text-muted">PDF, Excel, CSV, atau Gambar — Maks. 5 MB</div>
            <input type="file" name="file_laporan" id="fileInput" class="d-none" accept=".pdf,.xlsx,.xls,.csv,.jpg,.jpeg,.png" {{ $editMode ? '' : 'required' }}>
        </label>
        @if($penjualan->file_laporan)
            <div class="small text-muted mb-3"><i class="bi bi-paperclip me-1"></i><a href="{{ asset('storage/' . $penjualan->file_laporan) }}" target="_blank">Lihat file lama</a></div>
        @endif
        <label class="form-label fw-bold text-muted small text-uppercase">Catatan (Opsional)</label>
        <textarea name="catatan_mitra" rows="3" class="form-control" placeholder="Catatan tambahan...">{{ old('catatan_mitra', $penjualan->catatan_mitra) }}</textarea>
    </div>

    <div class="d-flex justify-content-between align-items-center">
        <a href="{{ route('jasa.mitra.show', $mitra) }}" class="btn btn-light border fw-bold px-4">Batal</a>
        <button type="submit" class="btn btn-primary fw-bold px-5 py-2" {{ ! $konsesiLayanan ? 'disabled' : '' }}><i class="bi bi-save me-1"></i>{{ $editMode ? 'Simpan Perubahan' : 'Simpan Laporan' }}</button>
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
    const previewPersen = document.getElementById('previewPersen');
    const previewOmzet = document.getElementById('previewOmzet');
    const previewTagihan = document.getElementById('previewTagihan');
    const fileDrop = document.getElementById('fileDrop');
    const fileInput = document.getElementById('fileInput');
    const fileLabel = document.getElementById('fileLabel');

    const fmtDate = str => { if(!str)return'-'; const d=new Date(str); const days=['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu']; return days[d.getDay()]+', '+String(d.getDate()).padStart(2,'0')+'/'+String(d.getMonth()+1).padStart(2,'0')+'/'+d.getFullYear(); };
    const fmtRp = v => new Intl.NumberFormat('id-ID',{style:'currency',currency:'IDR',minimumFractionDigits:0,maximumFractionDigits:0}).format(v||0);

    function setTipe(tipe) {
        inputTipe.value = tipe;
        tipeCards.forEach(c => c.classList.toggle('selected', c.dataset.tipe === tipe));
        if (tipe === 'harian') { wrapSelesai.style.display='none'; labelMulai.textContent='Tanggal'; inputSelesai.value=inputMulai.value; }
        else { wrapSelesai.style.display=''; labelMulai.textContent='Dari Tanggal'; if(!inputSelesai.value||inputSelesai.value===inputMulai.value) autoFillEndDate(); }
        updateSummary();
    }
    function autoFillEndDate() { if(!inputMulai.value)return; const s=new Date(inputMulai.value); s.setDate(s.getDate()+6); inputSelesai.value=s.toISOString().split('T')[0]; }
    function updateSummary() {
        if(inputTipe.value==='harian') summaryText.textContent=fmtDate(inputMulai.value);
        else { const s=new Date(inputMulai.value),e=new Date(inputSelesai.value),d=Math.round((e-s)/(864e5))+1; summaryText.textContent=fmtDate(inputMulai.value)+' s.d. '+fmtDate(inputSelesai.value)+' ('+d+' hari)'; }
    }
    function updateCalc() {
        const opt=selLayanan.options[selLayanan.selectedIndex], p=Number(opt?.dataset?.persentase||0), o=Number(inputOmzet.value||0);
        previewPersen.textContent=Number(p).toLocaleString('id-ID',{maximumFractionDigits:4})+'%';
        previewOmzet.textContent=fmtRp(o); previewTagihan.textContent=fmtRp(o*p/100);
    }
    tipeCards.forEach(c=>c.addEventListener('click',()=>setTipe(c.dataset.tipe)));
    inputMulai.addEventListener('change',()=>{ if(inputTipe.value==='harian')inputSelesai.value=inputMulai.value; else autoFillEndDate(); updateSummary(); });
    inputSelesai.addEventListener('change',updateSummary);
    selLayanan.addEventListener('change',updateCalc); inputOmzet.addEventListener('input',updateCalc);
    ['dragenter','dragover'].forEach(e=>fileDrop.addEventListener(e,ev=>{ev.preventDefault();fileDrop.classList.add('dragover');}));
    ['dragleave','drop'].forEach(e=>fileDrop.addEventListener(e,ev=>{ev.preventDefault();fileDrop.classList.remove('dragover');}));
    fileDrop.addEventListener('drop',ev=>{if(ev.dataTransfer.files.length){fileInput.files=ev.dataTransfer.files;showFile();}});
    fileInput.addEventListener('change',showFile);
    function showFile(){if(fileInput.files.length){fileLabel.textContent=fileInput.files[0].name;fileDrop.classList.add('has-file');}}
    setTipe(inputTipe.value); updateCalc();
});
</script>
@endpush
