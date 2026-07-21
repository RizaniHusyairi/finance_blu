@extends('layouts.app')

@section('title', 'Input Tagihan Historis')

@push('css')
<style>
:root { --th-ink:#0b1020; --th-indigo:#4338ca; --th-teal:#0e7490; --th-border:#e2e8f0; }

/* ── animasi ── */
@keyframes thAurora { 0%{background-position:0% 50%} 50%{background-position:100% 50%} 100%{background-position:0% 50%} }
@keyframes thFloat { 0%,100%{transform:translateY(0)} 50%{transform:translateY(-10px)} }
@keyframes thRise { from{opacity:0; transform:translateY(16px)} to{opacity:1; transform:none} }
@keyframes thPulse { 0%,100%{box-shadow:0 0 0 0 rgba(14,116,144,.45)} 50%{box-shadow:0 0 0 9px rgba(14,116,144,0)} }
@keyframes thSheen { 0%,55%{left:-70%} 90%,100%{left:140%} }
@keyframes thFill { from{background:#ccfbf1} to{background:#f0fdfa} }
@keyframes thScan { 0%{top:0} 50%{top:calc(100% - 3px)} 100%{top:0} }
@keyframes thPop { 0%{transform:scale(.6); opacity:0} 70%{transform:scale(1.15)} 100%{transform:scale(1); opacity:1} }
@media (prefers-reduced-motion: reduce) {
    .th-page *, .th-page *::before, .th-page *::after { animation-duration:.001s !important; animation-delay:0s !important; animation-iteration-count:1 !important; transition-duration:.001s !important; }
}

/* ── hero ── */
.th-hero { position:relative; overflow:hidden; border-radius:1.5rem; padding:1.9rem 2.1rem; margin-bottom:1.4rem; color:#fff;
    background:linear-gradient(125deg,#0b1020,#1e1b4b 34%,#4338ca 62%,#0e7490 92%);
    background-size:320% 320%; animation:thAurora 18s ease infinite;
    box-shadow:0 28px 56px -26px rgba(30,27,75,.65); }
.th-hero::before, .th-hero::after { content:''; position:absolute; border-radius:50%; pointer-events:none;
    background:radial-gradient(circle, rgba(255,255,255,.15) 0%, transparent 70%); }
.th-hero::before { width:340px; height:340px; top:-55%; left:-4%; animation:thFloat 9s ease-in-out infinite; }
.th-hero::after { width:260px; height:260px; bottom:-58%; right:-3%; animation:thFloat 12s ease-in-out infinite reverse; }
.th-hero .mesh { position:absolute; inset:0; opacity:.14; pointer-events:none;
    background-image:linear-gradient(rgba(255,255,255,.4) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,.4) 1px, transparent 1px);
    background-size:42px 42px; mask-image:radial-gradient(ellipse at 20% 0%, #000 5%, transparent 60%); }
.th-hero h4 { color:#fff !important; font-weight:800; letter-spacing:-.4px; margin:0; font-size:clamp(1.25rem,2.3vw,1.65rem); }
.th-hero .kicker { font-size:.72rem; letter-spacing:2.5px; color:#a5f3fc; font-weight:800; }
.th-hero .sub { color:#c7d2fe; font-size:.86rem; max-width:640px; }
.th-hero .btn-light { border-radius:999px; font-weight:700; }

/* stepper */
.th-steps { position:relative; z-index:2; display:flex; gap:.6rem; flex-wrap:wrap; margin-top:1.1rem; }
.th-step { display:inline-flex; align-items:center; gap:.5rem; padding:.42rem .95rem; border-radius:999px;
    background:rgba(255,255,255,.1); border:1px solid rgba(255,255,255,.22); font-size:.76rem; font-weight:700;
    color:#c7d2fe; backdrop-filter:blur(3px); transition:all .35s ease; }
.th-step .n { display:inline-grid; place-items:center; width:20px; height:20px; border-radius:50%;
    background:rgba(255,255,255,.16); font-size:.68rem; }
.th-step.on { background:rgba(45,212,191,.25); border-color:rgba(153,246,228,.6); color:#ccfbf1; animation:thPulse 2.4s ease-in-out infinite; }
.th-step.done { background:rgba(16,185,129,.28); border-color:rgba(110,231,183,.55); color:#d1fae5; animation:none; }
.th-step.done .n { background:#10b981; }

/* ── kartu ── */
.th-card { background:#fff; border:1px solid #e0e7ff; border-radius:1.15rem; margin-bottom:1.15rem; overflow:hidden;
    box-shadow:0 20px 44px -30px rgba(30,27,75,.45); opacity:0; animation:thRise .55s cubic-bezier(.22,.61,.36,1) both;
    animation-delay:var(--d, 0s); transition:box-shadow .25s ease, transform .25s ease; }
.th-card:hover { box-shadow:0 26px 54px -28px rgba(67,56,202,.4); }
.th-card .head { display:flex; align-items:center; gap:.7rem; padding:.95rem 1.35rem; border-bottom:1px solid #eef2ff;
    background:linear-gradient(180deg,#fafbff,#fff); }
.th-num { display:inline-grid; place-items:center; width:30px; height:30px; border-radius:.65rem; flex-shrink:0;
    background:linear-gradient(135deg,#4338ca,#7c3aed); color:#fff; font-weight:800; font-size:.8rem; }
.th-num.teal { background:linear-gradient(135deg,#0e7490,#14b8a6); }
.th-num.amber { background:linear-gradient(135deg,#b45309,#f59e0b); }
.th-card .head .ttl { font-weight:800; color:#1e1b4b; font-size:.92rem; }
.th-card .head .hint { font-size:.72rem; color:#94a3b8; margin-left:auto; }
.th-card .body { padding:1.15rem 1.35rem; }

.th-page .form-label { font-size:.78rem; font-weight:700; color:#334155; letter-spacing:.2px; }
.th-page .form-control, .th-page .form-select { border-radius:.65rem; transition:border-color .2s, box-shadow .2s, background .3s; }
.th-page .form-control:focus, .th-page .form-select:focus { border-color:#818cf8; box-shadow:0 0 0 .2rem rgba(99,102,241,.12); }
.th-page .form-control.th-auto { animation:thFill 1.6s ease both; border-color:#99f6e4; }

/* pipeline dokumen */
.th-pipe { position:relative; display:grid; grid-template-columns:repeat(4, 1fr); gap:.9rem; }
@media (max-width: 992px) { .th-pipe { grid-template-columns:repeat(2, 1fr); } }
.th-doc { position:relative; border:1.5px dashed #c7d2fe; border-radius:.95rem; padding:.9rem; background:#f8faff; transition:all .3s ease; }
.th-doc.filled { border-style:solid; border-color:#99f6e4; background:#f0fdfa; }
.th-doc .ttl { display:flex; align-items:center; gap:.45rem; font-size:.72rem; font-weight:800; letter-spacing:1px;
    color:#4338ca; text-transform:uppercase; margin-bottom:.55rem; }
.th-doc.filled .ttl { color:#0f766e; }
.th-doc .ok { display:none; margin-left:auto; color:#10b981; animation:thPop .4s ease both; }
.th-doc.filled .ok { display:inline; }
.th-doc .arrow { position:absolute; right:-13px; top:50%; transform:translateY(-50%); color:#c7d2fe; font-size:1rem; z-index:2; }
.th-doc:last-child .arrow { display:none; }
@media (max-width: 992px) { .th-doc .arrow { display:none; } }

/* netto bar */
.th-netto { position:relative; overflow:hidden; border-radius:.95rem; padding:.95rem 1.2rem; color:#fff;
    background:linear-gradient(120deg,#0f766e,#0e7490 60%,#155e75); display:flex; flex-wrap:wrap; align-items:center;
    justify-content:space-between; gap:.6rem; box-shadow:0 16px 32px -18px rgba(15,118,110,.6); }
.th-netto::after { content:''; position:absolute; top:0; bottom:0; width:40%; left:-70%;
    background:linear-gradient(100deg,transparent,rgba(255,255,255,.18),transparent); transform:skewX(-18deg);
    animation:thSheen 5s ease-in-out 1.2s infinite; pointer-events:none; }
.th-netto .rumus { font-size:.74rem; color:#a5f3fc; font-weight:600; }
.th-netto .val { font-size:1.35rem; font-weight:800; font-variant-numeric:tabular-nums; letter-spacing:-.4px; }

/* potongan */
.th-pot-row { display:flex; gap:.4rem; margin-bottom:.4rem; animation:thRise .35s ease both; }

/* ── kartu dropzone OCR ── */
.th-dropcard { position:relative; border:0 !important;
    background:linear-gradient(#fff,#fff) padding-box, linear-gradient(120deg,#4338ca,#0e7490,#7c3aed) border-box;
    border:2px solid transparent !important; }
.th-drop { position:relative; overflow:hidden; border:2px dashed #c7d2fe; border-radius:1rem; padding:1.6rem 1.4rem;
    background:linear-gradient(180deg,#f8faff,#eef2ff55); text-align:center; cursor:pointer; transition:all .3s ease; }
.th-drop:hover, .th-drop.dragover { border-color:#818cf8; background:#eef2ff; transform:scale(1.005); }
.th-drop input[type=file] { position:absolute; inset:0; opacity:0; cursor:pointer; }
.th-drop .ic { display:inline-grid; place-items:center; width:64px; height:64px; border-radius:1.1rem; margin-bottom:.6rem;
    background:linear-gradient(135deg,#4338ca,#0e7490); color:#fff; font-size:1.7rem;
    box-shadow:0 14px 28px -14px rgba(67,56,202,.7); animation:thFloat 4.5s ease-in-out infinite; }
.th-drop .ttl { font-weight:800; color:#1e1b4b; }
.th-drop .sub { font-size:.78rem; color:#64748b; }
.th-drop .scanline { display:none; position:absolute; left:10px; right:10px; height:3px; border-radius:99px; z-index:2;
    background:linear-gradient(90deg, transparent, #22d3ee, transparent); animation:thScan 1.5s ease-in-out infinite; pointer-events:none; }
.th-drop.reading { border-style:solid; border-color:#67e8f9; background:#ecfeff; }
.th-drop.reading .scanline { display:block; }
.th-drop.reading .ic { animation:thPulse 1.4s ease-in-out infinite; background:linear-gradient(135deg,#0891b2,#22d3ee); }
.th-drop.done { border-style:solid; border-color:#6ee7b7; background:#f0fdfa; }
.th-drop.done .ic { background:linear-gradient(135deg,#059669,#14b8a6); animation:none; }
.th-flow { display:flex; flex-wrap:wrap; justify-content:center; gap:.45rem; margin-top:.9rem; }
.th-flow .f { display:inline-flex; align-items:center; gap:.4rem; padding:.3rem .75rem; border-radius:999px;
    background:#fff; border:1px solid #e0e7ff; font-size:.72rem; font-weight:700; color:#4338ca; }
.th-flow .f i { color:#0e7490; }
.th-flow .sep { color:#c7d2fe; align-self:center; }
.th-info { border-radius:.8rem; padding:.7rem 1rem; font-size:.82rem; border:1px solid #c7d2fe; background:#eef2ff; animation:thRise .4s ease both; text-align:left; }
.th-info.ok { border-color:#a7f3d0; background:#ecfdf5; }
.th-info.err { border-color:#fecdd3; background:#fff1f2; }

/* preview kanan */
.th-preview-card { position:sticky; top:1rem; }
.th-preview-card .frame { border-radius:.7rem; border:1px solid #e2e8f0; max-height:70vh; overflow:auto;
    box-shadow:inset 0 0 0 1px rgba(67,56,202,.06); }
.th-warn { border:1px solid #fde68a; background:#fffbeb; border-radius:.7rem; padding:.6rem .9rem; font-size:.78rem; color:#92400e; }

/* tips list */
.th-tips li { line-height:1.85; }
.th-tips li::marker { color:#0e7490; }

/* tombol utama */
.th-submit { position:relative; overflow:hidden; border:0; border-radius:.8rem; padding:.7rem 1.6rem; font-weight:800; color:#fff;
    background:linear-gradient(120deg,#4338ca,#7c3aed); box-shadow:0 14px 28px -14px rgba(79,70,229,.7); transition:transform .2s, box-shadow .2s; }
.th-submit:hover { transform:translateY(-2px); color:#fff; box-shadow:0 20px 36px -14px rgba(79,70,229,.8); }
.th-submit:active { transform:scale(.97); }
</style>
@endpush

@section('content')
<div class="page-content th-page">

    {{-- ════════ HERO ════════ --}}
    <div class="th-hero">
        <div class="mesh"></div>
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3" style="position:relative; z-index:2;">
            <div>
                <div class="kicker"><i class="bi bi-archive me-1"></i>ARSIP SILABI · TANPA VERIFIKASI ULANG</div>
                <h4>Input Tagihan Historis <span style="filter:drop-shadow(0 2px 6px rgba(0,0,0,.4));">🗂️</span></h4>
                <div class="sub mt-1">Unggah bundel scan → OCR membaca SPP-nya → form terisi otomatis → tersimpan langsung sampai realisasi anggaran &amp; BKU bertanggal arsip.</div>
            </div>
            <a href="{{ route('proses-tagihan.index') }}" class="btn btn-light px-3"><i class="bi bi-arrow-left me-1"></i>Kembali</a>
        </div>
        <div class="th-steps">
            <span class="th-step on" id="thStep1"><span class="n">1</span> Unggah &amp; OCR</span>
            <span class="th-step" id="thStep2"><span class="n">2</span> Verifikasi isian</span>
            <span class="th-step" id="thStep3"><span class="n">3</span> Simpan → BKU</span>
        </div>
    </div>

    @if(session('error'))
        <div class="alert alert-danger d-flex align-items-start gap-2" style="border-radius:.9rem;">
            <i class="bi bi-exclamation-octagon-fill mt-1"></i>
            <div>{{ session('error') }}</div>
        </div>
    @endif
    @if($errors->any())
        <div class="alert alert-warning" style="border-radius:.9rem;">
            <div class="fw-bold mb-1"><i class="bi bi-exclamation-triangle me-1"></i>Periksa isian berikut:</div>
            <ul class="mb-0 ps-3">
                @foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('proses-tagihan.historis.store') }}" method="POST" enctype="multipart/form-data" id="thForm">
        @csrf
        <div class="row g-3">
            <div class="col-lg-7">

                {{-- 00 · Dropzone bundel arsip + OCR --}}
                <div class="th-card th-dropcard" style="--d:.02s;">
                    <div class="head">
                        <span class="th-num" style="background:linear-gradient(135deg,#0e7490,#22d3ee);"><i class="bi bi-stars"></i></span>
                        <span class="ttl">Unggah Bundel Arsip — Form Terisi Otomatis</span>
                        <span class="hint">PDF scan SILABI · maks 25 MB</span>
                    </div>
                    <div class="body">
                        <label class="th-drop d-block mb-0" id="thDrop">
                            <input type="file" name="file_arsip" id="thFileArsip" accept="application/pdf">
                            <div class="scanline"></div>
                            <div class="ic" id="thDropIcon"><i class="bi bi-cloud-arrow-up-fill"></i></div>
                            <div class="ttl" id="thDropTitle">Seret bundel arsip ke sini, atau klik untuk memilih</div>
                            <div class="sub" id="thDropSub">Halaman SPP akan dibaca OCR — nomor, tanggal, nilai, potongan, COA, dan pihak terisi sendiri.</div>
                            <div class="th-flow">
                                <span class="f"><i class="bi bi-file-earmark-arrow-up"></i> Unggah PDF</span>
                                <span class="sep"><i class="bi bi-arrow-right"></i></span>
                                <span class="f"><i class="bi bi-eye"></i> OCR membaca SPP</span>
                                <span class="sep"><i class="bi bi-arrow-right"></i></span>
                                <span class="f"><i class="bi bi-magic"></i> Form terisi otomatis</span>
                                <span class="sep"><i class="bi bi-arrow-right"></i></span>
                                <span class="f"><i class="bi bi-file-earmark-image"></i> Scan tampil untuk verifikasi</span>
                            </div>
                        </label>
                        <input type="hidden" name="arsip_token" id="thArsipToken" value="">
                        <div class="d-none mt-2" id="thOcrInfo"></div>
                    </div>
                </div>

                {{-- 01 · Identitas --}}
                <div class="th-card" style="--d:.08s;">
                    <div class="head">
                        <span class="th-num">01</span>
                        <span class="ttl">Identitas Tagihan</span>
                        <span class="hint">periksa hasil isian otomatis</span>
                    </div>
                    <div class="body row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Jenis Tagihan</label>
                            <select name="tipe_tagihan" class="form-select" required>
                                <option value="KONTRAK_EKSTERNAL" {{ old('tipe_tagihan') === 'KONTRAK_EKSTERNAL' ? 'selected' : '' }}>Kontrak / Vendor</option>
                                <option value="HONORARIUM" {{ old('tipe_tagihan') === 'HONORARIUM' ? 'selected' : '' }}>Honorarium</option>
                                <option value="PERJALDIN" {{ old('tipe_tagihan') === 'PERJALDIN' ? 'selected' : '' }}>Perjalanan Dinas</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nomor Tagihan (arsip)</label>
                            <input type="text" name="nomor_tagihan" class="form-control" placeholder="HIS/2026/0001" value="{{ old('nomor_tagihan') }}" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Uraian</label>
                            <input type="text" name="deskripsi" class="form-control" placeholder="mis. Pembayaran Belanja Barang Pekerjaan Pemotongan Rumput Sisi Udara Tahap I" value="{{ old('deskripsi') }}" required maxlength="500">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Pihak / Supplier</label>
                            <select name="pihak_id" class="form-select">
                                <option value="">— Pihak baru (isi di kanan) / tanpa pihak —</option>
                                @foreach($pihakOptions as $pihak)
                                    <option value="{{ $pihak->id }}" {{ (string) old('pihak_id') === (string) $pihak->id ? 'selected' : '' }}>{{ $pihak->nama_pihak }}{{ $pihak->npwp ? ' · ' . $pihak->npwp : '' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Pihak Baru <span class="text-muted fw-normal">(bila tidak ada di daftar)</span></label>
                            <input type="text" name="pihak_nama_baru" id="thPihakNamaBaru" class="form-control" placeholder="Nama supplier, mis. CV. Garuda Karya Bersama" value="{{ old('pihak_nama_baru') }}">
                            <div class="small text-muted mt-1"><i class="bi bi-person-plus me-1"></i>Diisi → panel kelengkapan data vendor terbuka di bawah, dan vendor tersimpan ke Master Data.</div>
                        </div>
                        <div class="col-12 {{ old('pihak_nama_baru') ? '' : 'd-none' }}" id="thPihakDetail">
                            <div style="border:1.5px dashed #c7d2fe; border-radius:.95rem; padding:1rem 1.1rem; background:#f8faff;">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <span class="th-num" style="width:24px; height:24px; font-size:.7rem; background:linear-gradient(135deg,#0e7490,#14b8a6);"><i class="bi bi-building-add"></i></span>
                                    <span class="fw-bold" style="font-size:.82rem; color:#1e1b4b;">Kelengkapan Data Vendor Baru</span>
                                    <span class="small text-muted ms-auto">tersimpan ke Master Data Vendor</span>
                                </div>
                                <div class="row g-2">
                                    <div class="col-md-4">
                                        <label class="form-label mb-1">NPWP</label>
                                        <input type="text" name="pihak_npwp" class="form-control form-control-sm" placeholder="15–16 digit" value="{{ old('pihak_npwp') }}">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label mb-1">Nama Direktur / Penanggung Jawab</label>
                                        <input type="text" name="pihak_direktur" class="form-control form-control-sm" placeholder="mis. Abdul Fatah" value="{{ old('pihak_direktur') }}">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label mb-1">Jabatan Penandatangan</label>
                                        <input type="text" name="pihak_jabatan" class="form-control form-control-sm" placeholder="mis. Direktur" value="{{ old('pihak_jabatan', 'Direktur') }}">
                                    </div>
                                    <div class="col-md-8">
                                        <label class="form-label mb-1">Alamat</label>
                                        <input type="text" name="pihak_alamat" class="form-control form-control-sm" placeholder="Alamat lengkap sesuai berkas" value="{{ old('pihak_alamat') }}">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label mb-1">Email / Telepon <span class="text-muted fw-normal">(ops.)</span></label>
                                        <div class="d-flex gap-1">
                                            <input type="email" name="pihak_email" class="form-control form-control-sm" placeholder="Email" value="{{ old('pihak_email') }}">
                                            <input type="text" name="pihak_telepon" class="form-control form-control-sm" placeholder="Telepon" value="{{ old('pihak_telepon') }}">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label mb-1">Bank</label>
                                        <input type="text" name="pihak_bank" class="form-control form-control-sm" placeholder="mis. Bank Tabungan Negara" value="{{ old('pihak_bank') }}">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label mb-1">Nomor Rekening</label>
                                        <input type="text" name="pihak_norek" class="form-control form-control-sm" inputmode="numeric" placeholder="sesuai kolom Rekening pada SPP" value="{{ old('pihak_norek') }}">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label mb-1">Nama Pemilik Rekening</label>
                                        <input type="text" name="pihak_nama_rekening" class="form-control form-control-sm" placeholder="biasanya sama dengan nama supplier" value="{{ old('pihak_nama_rekening') }}">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            @include('partials.dipa-item-grouped-select', [
                                'fieldLabel' => 'COA / Item Anggaran (sesuai kolom Pengeluaran pada SPP)',
                                'helpText' => 'Pilih item DIPA yang kodenya sama dengan kode MAK pada berkas SPP.',
                            ])
                        </div>
                    </div>
                </div>

                {{-- 02 · Nilai & potongan --}}
                <div class="th-card" style="--d:.12s;">
                    <div class="head">
                        <span class="th-num teal">02</span>
                        <span class="ttl">Nilai &amp; Potongan</span>
                        <span class="hint">netto dihitung live</span>
                    </div>
                    <div class="body row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Bruto (Jumlah Pengeluaran)</label>
                            <div class="input-group">
                                <span class="input-group-text fw-bold">Rp</span>
                                <input type="text" inputmode="numeric" id="thBrutoDisplay" class="form-control fw-bold" placeholder="0" required>
                                <input type="hidden" name="total_bruto" id="thBruto" value="{{ old('total_bruto') }}">
                            </div>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label d-flex justify-content-between">Potongan (akun pajak dari berkas)
                                <button type="button" class="btn btn-sm btn-outline-primary py-0" id="thTambahPotongan"><i class="bi bi-plus-lg"></i> Baris</button>
                            </label>
                            <div id="thPotonganRows"></div>
                        </div>
                        <div class="col-12">
                            <div class="th-netto">
                                <div>
                                    <div class="fw-bold"><i class="bi bi-calculator me-1"></i>Total Pembayaran (netto)</div>
                                    <div class="rumus" id="thRumus">Rp 0 − Rp 0 potongan</div>
                                </div>
                                <div class="val" id="thNetto">Rp 0</div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 03 · Rantai dokumen --}}
                <div class="th-card" style="--d:.19s;">
                    <div class="head">
                        <span class="th-num amber">03</span>
                        <span class="ttl">Rantai Dokumen — nomor &amp; tanggal sesuai berkas</span>
                        <span class="hint">SPP → SPM → NPI → SP2D</span>
                    </div>
                    <div class="body">
                        <div class="th-pipe mb-3">
                            @foreach([
                                ['spp', 'SPP BLU', 'bi-file-earmark-text', 'SPM-BLU/APTP-2026/0001'],
                                ['spm', 'SPM BLU', 'bi-file-earmark-check', 'SPM-BLU/APTP-2026/0001'],
                                ['npi', 'NPI', 'bi-arrow-left-right', 'NPI-BLU/APTP-2026/0001'],
                                ['sp2d', 'SP2D', 'bi-bank', 'SP2D-BLU/APTP-2026/0001'],
                            ] as [$key, $judul, $icon, $contoh])
                                <div class="th-doc" data-doc="{{ $key }}">
                                    <div class="ttl"><i class="bi {{ $icon }}"></i>{{ $judul }}<i class="bi bi-check-circle-fill ok"></i></div>
                                    <input type="text" name="nomor_{{ $key }}" class="form-control form-control-sm mb-1 js-doc-input" placeholder="{{ $contoh }}" value="{{ old('nomor_' . $key) }}" required>
                                    <input type="date" name="tanggal_{{ $key }}" class="form-control form-control-sm js-doc-input" value="{{ old('tanggal_' . $key) }}" required>
                                    @if($key === 'sp2d')
                                        <div class="small text-muted mt-1"><i class="bi bi-journal-check me-1"></i>= tanggal transaksi BKU</div>
                                    @endif
                                    <i class="bi bi-chevron-right arrow"></i>
                                </div>
                            @endforeach
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Nomor Urut Register SPP <span class="text-muted fw-normal">(ops.)</span></label>
                                <input type="number" name="register_nomor_urut" class="form-control" min="1" max="9999" placeholder="mis. 1 untuk .../0001" value="{{ old('register_nomor_urut') }}">
                                <div class="small text-muted mt-1">Bila diisi, nomor urut ditandai terpakai pada register SPP_BLU sehingga penomoran otomatis melompatinya.</div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- aksi --}}
                <div class="th-card" style="--d:.26s;">
                    <div class="body d-flex justify-content-end align-items-center gap-2">
                        <span class="small text-muted me-auto"><i class="bi bi-shield-check me-1"></i>Sekali simpan: dokumen final + realisasi + BKU — aman diulang bila gagal (transaksi utuh).</span>
                        <a href="{{ route('proses-tagihan.index') }}" class="btn btn-outline-secondary">Batal</a>
                        <button type="submit" class="th-submit" onclick="return confirm('Rekam tagihan historis ini langsung berstatus SELESAI sampai BKU?');">
                            <i class="bi bi-archive me-1"></i>Rekam Tagihan Historis
                        </button>
                    </div>
                </div>
            </div>

            {{-- ════════ PANEL KANAN ════════ --}}
            <div class="col-lg-5">
                <div class="th-card th-preview-card d-none" style="--d:.1s;" id="thPreviewCard">
                    <div class="head">
                        <span class="th-num teal"><i class="bi bi-file-earmark-image"></i></span>
                        <span class="ttl">Scan Halaman 1 (SPP)</span>
                        <span class="hint">klik gambar untuk zoom</span>
                    </div>
                    <div class="body p-2">
                        <div id="thOcrWarnings" class="th-warn d-none mb-2"></div>
                        <div class="frame">
                            <img id="thPreviewImg" src="" alt="Pratinjau scan halaman 1" style="width:100%; display:block; cursor:zoom-in;" onclick="this.style.width = this.style.width === '160%' ? '100%' : '160%'; this.style.cursor = this.style.width === '160%' ? 'zoom-out' : 'zoom-in';">
                        </div>
                    </div>
                </div>

                <div class="th-card" style="--d:.17s;">
                    <div class="head">
                        <span class="th-num"><i class="bi bi-lightbulb"></i></span>
                        <span class="ttl">Cara Membaca Berkas</span>
                    </div>
                    <div class="body small text-muted">
                        <ul class="ps-3 mb-0 th-tips">
                            <li><b>Bruto</b> = "Jumlah Pengeluaran" pada SPP/SPM.</li>
                            <li><b>Potongan</b> = tabel POTONGAN (mis. akun 411211 = PPN, 411124 = PPh) — netto harus sama dengan "Total Pembayaran" pada berkas.</li>
                            <li><b>COA</b> = kode kolom PENGELUARAN (mis. <code>GA.4647.CDE…525114</code>) — pastikan DIPA-nya sudah diimpor dari POK.</li>
                            <li><b>Nomor &amp; tanggal dokumen</b> diketik persis seperti berkas; tidak digenerate ulang.</li>
                            <li>Setelah simpan: serapan DIPA bertambah, transaksi masuk <b>BKU Pengeluaran</b> bertanggal SP2D, tagihan ber-badge <b>HISTORIS</b> tanpa antrean verifikasi.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('script')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var fmt = new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 });
    var brutoDisplay = document.getElementById('thBrutoDisplay');
    var brutoHidden = document.getElementById('thBruto');
    var nettoEl = document.getElementById('thNetto');
    var rumusEl = document.getElementById('thRumus');
    var rowsWrap = document.getElementById('thPotonganRows');

    function angka(v) { return parseInt(String(v || '').replace(/[^\d]/g, ''), 10) || 0; }

    /* count-up halus pada angka netto */
    var nettoTampil = 0;
    function animasiNetto(target) {
        var reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        if (reduced) { nettoTampil = target; nettoEl.textContent = 'Rp ' + fmt.format(target); return; }
        var awal = nettoTampil, mulai = performance.now(), durasi = 450;
        function step(now) {
            var p = Math.min((now - mulai) / durasi, 1);
            var eased = 1 - Math.pow(1 - p, 3);
            nettoEl.textContent = 'Rp ' + fmt.format(Math.round(awal + (target - awal) * eased));
            if (p < 1) requestAnimationFrame(step); else nettoTampil = target;
        }
        requestAnimationFrame(step);
    }

    function hitungNetto() {
        var potongan = 0;
        rowsWrap.querySelectorAll('.js-pot-nominal-display').forEach(function (el) { potongan += angka(el.value); });
        var bruto = angka(brutoHidden.value);
        animasiNetto(Math.max(0, bruto - potongan));
        rumusEl.textContent = 'Rp ' + fmt.format(bruto) + ' − Rp ' + fmt.format(potongan) + ' potongan';
    }

    function pasangRupiah(display, hidden) {
        display.addEventListener('input', function () {
            var n = angka(display.value);
            hidden.value = n || '';
            display.value = n ? fmt.format(n) : '';
            hitungNetto();
        });
    }

    pasangRupiah(brutoDisplay, brutoHidden);
    if (brutoHidden.value) { brutoDisplay.value = fmt.format(angka(brutoHidden.value)); }

    function tambahBaris(nama, nominal, ntpn) {
        var row = document.createElement('div');
        row.className = 'th-pot-row';
        row.innerHTML =
            '<input type="text" name="potongan_nama[]" class="form-control form-control-sm" style="max-width:110px;" placeholder="Akun, mis. 411211" value="' + (nama || '') + '">'
            + '<input type="text" class="form-control form-control-sm js-pot-nominal-display" inputmode="numeric" placeholder="Nominal" value="' + (nominal ? fmt.format(nominal) : '') + '">'
            + '<input type="hidden" name="potongan_nominal[]" value="' + (nominal || '') + '">'
            + '<input type="text" name="potongan_ntpn[]" class="form-control form-control-sm" style="max-width:130px;" placeholder="NTPN (ops.)" value="' + (ntpn || '') + '">'
            + '<button type="button" class="btn btn-sm btn-outline-danger js-pot-hapus"><i class="bi bi-x-lg"></i></button>';
        rowsWrap.appendChild(row);
        pasangRupiah(row.querySelector('.js-pot-nominal-display'), row.querySelector('input[name="potongan_nominal[]"]'));
        row.querySelector('.js-pot-hapus').addEventListener('click', function () { row.remove(); hitungNetto(); });
    }

    document.getElementById('thTambahPotongan').addEventListener('click', function () { tambahBaris(); });
    tambahBaris();
    hitungNetto();

    /* ── panel kelengkapan vendor baru: tampil saat nama pihak baru diisi ── */
    var pihakNamaBaru = document.getElementById('thPihakNamaBaru');
    var pihakDetail = document.getElementById('thPihakDetail');
    var pihakSelect = document.querySelector('[name="pihak_id"]');

    function togglePihakDetail() {
        var pakaiBaru = pihakNamaBaru.value.trim() !== '' && (!pihakSelect || pihakSelect.value === '');
        pihakDetail.classList.toggle('d-none', !pakaiBaru);
    }
    pihakNamaBaru.addEventListener('input', togglePihakDetail);
    if (pihakSelect) pihakSelect.addEventListener('change', togglePihakDetail);
    togglePihakDetail();

    /* ── pipeline dokumen: badge ✓ + stepper saat terisi ── */
    function refreshDocState() {
        var semuaLengkap = true;
        document.querySelectorAll('.th-doc').forEach(function (doc) {
            var inputs = doc.querySelectorAll('.js-doc-input');
            var lengkap = Array.from(inputs).every(function (i) { return i.value.trim() !== ''; });
            doc.classList.toggle('filled', lengkap);
            if (!lengkap) semuaLengkap = false;
        });
        var step2 = document.getElementById('thStep2');
        var step3 = document.getElementById('thStep3');
        if (semuaLengkap && angka(brutoHidden.value) > 0) {
            step2.classList.remove('on'); step2.classList.add('done');
            step3.classList.add('on');
        }
    }
    document.querySelectorAll('.js-doc-input').forEach(function (el) {
        el.addEventListener('input', refreshDocState);
        el.addEventListener('change', refreshDocState);
    });
    refreshDocState();

    /* ── OCR arsip: unggah → baca → isi form otomatis ── */
    var fileArsip = document.getElementById('thFileArsip');
    var arsipToken = document.getElementById('thArsipToken');
    var ocrInfo = document.getElementById('thOcrInfo');
    var drop = document.getElementById('thDrop');
    var dropIcon = document.getElementById('thDropIcon');
    var dropTitle = document.getElementById('thDropTitle');
    var dropSub = document.getElementById('thDropSub');
    var previewCard = document.getElementById('thPreviewCard');
    var previewImg = document.getElementById('thPreviewImg');
    var ocrWarnings = document.getElementById('thOcrWarnings');
    var terisiOtomatis = 0;

    ['dragenter', 'dragover'].forEach(function (ev) {
        drop.addEventListener(ev, function (e) { e.preventDefault(); drop.classList.add('dragover'); });
    });
    ['dragleave', 'drop'].forEach(function (ev) {
        drop.addEventListener(ev, function (e) { e.preventDefault(); drop.classList.remove('dragover'); });
    });

    function dropState(state, judul, sub, ikon) {
        drop.classList.remove('reading', 'done');
        if (state) drop.classList.add(state);
        dropIcon.innerHTML = '<i class="bi ' + ikon + '"></i>';
        dropTitle.textContent = judul;
        dropSub.innerHTML = sub;
    }

    function infoOcr(html, warna) {
        ocrInfo.classList.remove('d-none');
        ocrInfo.innerHTML = '<div class="th-info ' + (warna || '') + '">' + html + '</div>';
    }

    function isi(nama, nilai) {
        if (nilai === null || nilai === undefined || nilai === '') return;
        var el = document.querySelector('[name="' + nama + '"]');
        if (!el) return;
        el.value = nilai;
        el.classList.remove('th-auto');
        void el.offsetWidth; // restart animasi thFill
        el.classList.add('th-auto');
        el.dispatchEvent(new Event('change'));
        terisiOtomatis++;
    }

    fileArsip.addEventListener('change', function () {
        var f = fileArsip.files[0];
        if (!f) return;
        arsipToken.value = '';
        terisiOtomatis = 0;
        dropState('reading', 'Memindai "' + f.name.slice(0, 60) + (f.name.length > 60 ? '…' : '') + '"',
            'OCR sedang membaca halaman SPP — biasanya 5–15 detik…', 'bi-eye-fill');
        infoOcr('<i class="bi bi-hourglass-split me-1"></i>Membaca scan dengan OCR — beberapa detik…', '');

        var fd = new FormData();
        fd.append('file_arsip', f);

        fetch('{{ route('proses-tagihan.historis.baca-arsip') }}', {
            method: 'POST',
            body: fd,
            credentials: 'same-origin',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || '{{ csrf_token() }}',
                'Accept': 'application/json'
            }
        })
        .then(function (res) { return res.json().then(function (data) { return { status: res.status, data: data }; }); })
        .then(function (r) {
            if (r.status !== 200 || !r.data.ok) {
                dropState('', 'Berkas tidak terbaca — klik untuk coba lagi',
                    'Anda tetap bisa mengisi form manual sambil membuka PDF-nya sendiri.', 'bi-cloud-arrow-up-fill');
                infoOcr('<i class="bi bi-exclamation-triangle me-1"></i>' + (r.data.pesan || 'Berkas tidak terbaca — isi form manual.'), 'err');
                return;
            }
            var d = r.data;
            arsipToken.value = d.token;
            var fl = d.fields || {};

            isi('nomor_tagihan', fl.register_nomor_urut ? 'HIS/' + (fl.tanggal_spp || '').slice(0, 4) + '/' + String(fl.register_nomor_urut).padStart(4, '0') : null);
            isi('deskripsi', fl.deskripsi);
            isi('tipe_tagihan', fl.tipe_tagihan);
            isi('register_nomor_urut', fl.register_nomor_urut);
            ['spp', 'spm', 'npi', 'sp2d'].forEach(function (k) {
                isi('nomor_' + k, fl['nomor_' + k]);
                isi('tanggal_' + k, fl['tanggal_' + k]);
            });

            if (fl.total_bruto) {
                brutoHidden.value = Math.round(fl.total_bruto);
                brutoDisplay.value = fmt.format(Math.round(fl.total_bruto));
                brutoDisplay.classList.add('th-auto');
            }

            rowsWrap.innerHTML = '';
            (d.potongan && d.potongan.length ? d.potongan : [null]).forEach(function (p) {
                p ? tambahBaris(p.nama, Math.round(p.nominal)) : tambahBaris();
            });
            hitungNetto();

            if (d.pihak_id_cocok) {
                isi('pihak_id', d.pihak_id_cocok);
            } else if (fl.pihak_nama) {
                isi('pihak_nama_baru', fl.pihak_nama);
                isi('pihak_npwp', fl.pihak_npwp);
                isi('pihak_alamat', fl.pihak_alamat);
                isi('pihak_bank', fl.pihak_bank);
                isi('pihak_norek', fl.pihak_rekening);
                isi('pihak_nama_rekening', fl.pihak_nama_rekening || fl.pihak_nama);
                togglePihakDetail();
            }

            if (fl.dipa_revision_item_id) {
                var coaSelect = document.querySelector('[name="dipa_revision_item_id"]');
                if (coaSelect) {
                    coaSelect.value = String(fl.dipa_revision_item_id);
                    if (window.jQuery && window.jQuery.fn.select2 && window.jQuery(coaSelect).data('select2')) {
                        window.jQuery(coaSelect).trigger('change');
                    } else {
                        coaSelect.dispatchEvent(new Event('change'));
                    }
                }
            }

            if (d.preview) {
                previewImg.src = d.preview;
                previewCard.classList.remove('d-none');
            }
            if (d.warnings && d.warnings.length) {
                ocrWarnings.classList.remove('d-none');
                ocrWarnings.innerHTML = '<div class="fw-bold mb-1"><i class="bi bi-exclamation-triangle me-1"></i>' + d.warnings.length + ' catatan pembacaan:</div><ul class="mb-0 ps-3">'
                    + d.warnings.map(function (w) { return '<li>' + String(w).replace(/[<>]/g, '') + '</li>'; }).join('') + '</ul>';
            } else {
                ocrWarnings.classList.add('d-none');
            }

            var step1 = document.getElementById('thStep1');
            step1.classList.remove('on'); step1.classList.add('done');
            document.getElementById('thStep2').classList.add('on');
            refreshDocState();

            var jumlahPotongan = (d.potongan || []).length;
            dropState('done', f.name.slice(0, 60) + (f.name.length > 60 ? '…' : ''),
                '<b>' + terisiOtomatis + ' field</b> + <b>' + jumlahPotongan + ' potongan</b> terisi otomatis · klik untuk mengganti berkas', 'bi-file-earmark-check-fill');

            infoOcr('<i class="bi bi-magic me-1"></i><b>Form terisi otomatis dari hasil OCR.</b> Cocokkan dengan scan di panel kanan, lengkapi yang kosong, lalu simpan. '
                + (d.ocr_aktif ? '' : '<span class="text-danger">OCR tidak aktif — hanya nama file yang dibaca.</span>'), 'ok');
        })
        .catch(function () {
            dropState('', 'Gagal membaca berkas — klik untuk coba lagi', 'Anda tetap bisa mengisi form manual.', 'bi-cloud-arrow-up-fill');
            infoOcr('<i class="bi bi-exclamation-triangle me-1"></i>Gagal membaca berkas — isi form manual.', 'err');
        });
    });
});
</script>
@endpush
