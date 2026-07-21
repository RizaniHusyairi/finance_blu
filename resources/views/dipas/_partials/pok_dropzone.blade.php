{{-- Kartu dropzone POK/dokumen DIPA — dipakai form Tambah DIPA & Tambah Revisi.
     Variabel: $dzJudul, $dzSub, $dzFlow (array chip alur), $dzWarna (aksen).
     ID elemen dipertahankan (dfDrop/dfFile/dfDropIcon/dfDropText/dfDropHint/
     dfPokToken/dfPokInfo) agar JS bacaPok existing tetap bekerja. --}}
@php
    $dzJudul = $dzJudul ?? 'Unggah Dokumen DIPA / POK — Form Terisi Otomatis';
    $dzSub = $dzSub ?? 'PDF POK hasil cetak aplikasi anggaran akan dibaca otomatis.';
    $dzWarna = $dzWarna ?? '#4f46e5';
    $dzFlow = $dzFlow ?? [
        ['bi-file-earmark-arrow-up', 'Unggah PDF POK'],
        ['bi-eye', 'Dibaca otomatis'],
        ['bi-magic', 'Form terisi'],
        ['bi-table', 'Pratinjau rincian tampil'],
    ];
@endphp

@once
@push('css')
<style>
@keyframes pdFloat { 0%,100%{transform:translateY(0)} 50%{transform:translateY(-9px)} }
@keyframes pdScan { 0%{top:0} 50%{top:calc(100% - 3px)} 100%{top:0} }
@keyframes pdPulse { 0%,100%{box-shadow:0 0 0 0 rgba(8,145,178,.45)} 50%{box-shadow:0 0 0 9px rgba(8,145,178,0)} }
@keyframes pdRise { from{opacity:0; transform:translateY(14px)} to{opacity:1; transform:none} }
@media (prefers-reduced-motion: reduce) {
    .pd-card, .pd-card * { animation-duration:.001s !important; animation-delay:0s !important; transition-duration:.001s !important; }
}

.pd-card { position:relative; border-radius:1.15rem; margin-bottom:1.5rem; overflow:hidden;
    background:linear-gradient(#fff,#fff) padding-box, linear-gradient(120deg,var(--dz,#4f46e5),#0e7490,#7c3aed) border-box;
    border:2px solid transparent; box-shadow:0 22px 46px -30px rgba(30,27,75,.5);
    opacity:0; animation:pdRise .5s cubic-bezier(.22,.61,.36,1) .02s both; }
.pd-head { display:flex; align-items:center; gap:.7rem; padding:.95rem 1.35rem; border-bottom:1px solid #eef2ff;
    background:linear-gradient(180deg,#fafbff,#fff); }
.pd-head .ic { display:inline-grid; place-items:center; width:30px; height:30px; border-radius:.65rem;
    background:linear-gradient(135deg,var(--dz,#4f46e5),#0e7490); color:#fff; font-size:.85rem; }
.pd-head .ttl { font-weight:800; color:#1e1b4b; font-size:.92rem; }
.pd-head .hint { font-size:.72rem; color:#94a3b8; margin-left:auto; }

.pd-drop { position:relative; overflow:hidden; display:block; margin:1.1rem 1.35rem; border:2px dashed #c7d2fe;
    border-radius:1rem; padding:1.5rem 1.3rem; background:linear-gradient(180deg,#f8faff,#eef2ff55);
    text-align:center; cursor:pointer; transition:all .3s ease; }
.pd-drop:hover, .pd-drop.dragover { border-color:#818cf8; background:#eef2ff; transform:scale(1.004); }
.pd-drop input[type=file] { position:absolute; inset:0; opacity:0; cursor:pointer; }
.pd-drop .big-ic { display:inline-grid; place-items:center; width:60px; height:60px; border-radius:1.05rem; margin-bottom:.55rem;
    background:linear-gradient(135deg,var(--dz,#4f46e5),#0e7490); color:#fff; font-size:1.6rem;
    box-shadow:0 14px 28px -14px rgba(67,56,202,.7); animation:pdFloat 4.5s ease-in-out infinite; }
.pd-drop .d-ttl { font-weight:800; color:#1e1b4b; }
.pd-drop .d-sub { font-size:.78rem; color:#64748b; }
.pd-drop .scanline { display:none; position:absolute; left:10px; right:10px; height:3px; border-radius:99px; z-index:2;
    background:linear-gradient(90deg, transparent, #22d3ee, transparent); animation:pdScan 1.5s ease-in-out infinite; pointer-events:none; }
.pd-drop.reading { border-style:solid; border-color:#67e8f9; background:#ecfeff; }
.pd-drop.reading .scanline { display:block; }
.pd-drop.reading .big-ic { animation:pdPulse 1.4s ease-in-out infinite; background:linear-gradient(135deg,#0891b2,#22d3ee); }
.pd-drop.done { border-style:solid; border-color:#6ee7b7; background:#f0fdfa; }
.pd-drop.done .big-ic { background:linear-gradient(135deg,#059669,#14b8a6); animation:none; }

.pd-flow { display:flex; flex-wrap:wrap; justify-content:center; gap:.45rem; margin-top:.85rem; }
.pd-flow .f { display:inline-flex; align-items:center; gap:.4rem; padding:.28rem .7rem; border-radius:999px;
    background:#fff; border:1px solid #e0e7ff; font-size:.71rem; font-weight:700; color:#4338ca; }
.pd-flow .f i { color:#0e7490; }
.pd-flow .sep { color:#c7d2fe; align-self:center; }
.pd-info { margin:0 1.35rem 1.1rem; }
</style>
@endpush
@endonce

<div class="pd-card" style="--dz: {{ $dzWarna }};">
    <div class="pd-head">
        <span class="ic"><i class="bi bi-stars"></i></span>
        <span class="ttl">{{ $dzJudul }}</span>
        <span class="hint">PDF · maks 20 MB</span>
    </div>
    <label class="pd-drop" id="dfDrop">
        <input type="file" name="file_dokumen_dipa" id="dfFile" accept=".pdf,application/pdf">
        <div class="scanline"></div>
        <div class="big-ic"><i class="bi bi-cloud-arrow-up-fill" id="dfDropIcon"></i></div>
        <div class="d-ttl" id="dfDropText">Seret file ke sini, atau klik untuk memilih</div>
        <div class="d-sub" id="dfDropHint">{{ $dzSub }}</div>
        <div class="pd-flow">
            @foreach($dzFlow as $i => [$icon, $label])
                @if($i > 0)<span class="sep"><i class="bi bi-arrow-right"></i></span>@endif
                <span class="f"><i class="bi {{ $icon }}"></i> {{ $label }}</span>
            @endforeach
        </div>
    </label>
    <input type="hidden" name="pok_token" id="dfPokToken" value="">
    <div class="pd-info d-none" id="dfPokInfo"></div>
</div>
