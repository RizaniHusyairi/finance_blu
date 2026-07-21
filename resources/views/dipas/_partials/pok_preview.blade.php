{{-- Pratinjau lengkap hasil baca POK: dipakai form Tambah DIPA & Tambah Revisi.
     Panggil window.renderPokPreview(data) pada sukses parse, window.clearPokPreview()
     saat file diganti/gagal. Letakkan include ini di dalam kolom lebar penuh (col-12). --}}

@once
@push('css')
<style>
@keyframes pokRise { from { opacity:0; transform: translateY(14px); } to { opacity:1; transform:none; } }
@keyframes pokRowIn { from { opacity:0; transform: translateY(6px); } to { opacity:1; transform:none; } }
@keyframes pokSheen { 0%,55% { left:-60%; } 90%,100% { left:130%; } }
@media (prefers-reduced-motion: reduce) {
    .pok-pv, .pok-pv * { animation-duration:.001s !important; animation-delay:0s !important; transition-duration:.001s !important; }
}

.pok-pv { border:1px solid #e0e7ff; border-radius:1.1rem; overflow:hidden; background:#fff;
    box-shadow:0 20px 44px -26px rgba(49,46,129,.4); animation:pokRise .5s cubic-bezier(.22,.61,.36,1) both; }

.pok-pv-head { position:relative; overflow:hidden; padding:1rem 1.25rem; color:#fff;
    background:linear-gradient(120deg,#1e1b4b,#4338ca 55%,#6d28d9); }
.pok-pv-head::after { content:''; position:absolute; top:0; bottom:0; width:45%; left:-60%;
    background:linear-gradient(100deg,transparent,rgba(255,255,255,.14),transparent);
    transform:skewX(-18deg); animation:pokSheen 5.5s ease-in-out 1s infinite; pointer-events:none; }
.pok-pv-title { font-weight:800; letter-spacing:.3px; font-size:.95rem; }
.pok-pv-title i { color:#a5b4fc; }
.pok-pv-sub { color:#c7d2fe; font-size:.74rem; }

.pok-chip { display:inline-flex; align-items:center; gap:.35rem; padding:.3rem .7rem; border-radius:999px;
    background:rgba(255,255,255,.13); border:1px solid rgba(255,255,255,.2); font-size:.72rem; font-weight:700;
    color:#fff; backdrop-filter:blur(2px); }
.pok-chip .lbl { color:#c7d2fe; font-weight:600; }
.pok-chip.blu { background:rgba(16,185,129,.22); border-color:rgba(110,231,183,.4); }
.pok-chip.rm { background:rgba(245,158,11,.22); border-color:rgba(253,230,138,.4); }

.pok-pv-warn { margin:1rem 1.25rem 0; border:1px solid #fde68a; background:#fffbeb; border-radius:.8rem;
    padding:.7rem 1rem; font-size:.8rem; color:#92400e; }

.pok-pv-scroll { max-height:460px; overflow:auto; }
.pok-tbl { width:100%; border-collapse:separate; border-spacing:0; font-size:.8rem; }
.pok-tbl thead th { position:sticky; top:0; z-index:2; background:#f8faff; color:#475569;
    font-size:.64rem; letter-spacing:.8px; text-transform:uppercase; font-weight:800;
    padding:.6rem .8rem; border-bottom:2px solid #e0e7ff; white-space:nowrap; }
.pok-tbl tbody td { padding:.55rem .8rem; border-bottom:1px solid #eef2ff; vertical-align:middle; }
.pok-tbl tbody tr { animation:pokRowIn .35s ease both; animation-delay:calc(var(--i, 0) * 12ms); }
.pok-tbl tbody tr:nth-child(even) { background:#fafbff; }
.pok-tbl tbody tr:hover { background:#eef2ff; box-shadow:inset 3px 0 0 #4f46e5; }
.pok-kode { font-family:var(--bs-font-monospace); font-size:.72rem; color:#4338ca; white-space:nowrap; }
.pok-num { font-variant-numeric:tabular-nums; white-space:nowrap; }
.pok-sat { display:inline-block; padding:.1rem .5rem; border-radius:.45rem; background:#eef2ff;
    color:#4338ca; font-size:.68rem; font-weight:700; }
.pok-sd { display:inline-block; padding:.12rem .55rem; border-radius:999px; font-size:.66rem; font-weight:800; letter-spacing:.4px; }
.pok-sd.blu { background:#ecfdf5; color:#047857; border:1px solid #a7f3d0; }
.pok-sd.rm { background:#fffbeb; color:#b45309; border:1px solid #fde68a; }
.pok-pv-foot { padding:.55rem 1.25rem; background:#f8faff; border-top:1px solid #e0e7ff;
    font-size:.72rem; color:#64748b; }
</style>
@endpush
@endonce

<div id="dfPokPreview" class="pok-pv mt-3 d-none">
    <div class="pok-pv-head">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <div class="pok-pv-title"><i class="bi bi-table me-2"></i>Pratinjau Rincian POK</div>
                <div class="pok-pv-sub">Seluruh baris detil yang akan menjadi COA + item revisi saat disimpan.</div>
            </div>
            <div class="d-flex flex-wrap gap-2" id="dfPokChips"></div>
        </div>
    </div>
    <div id="dfPokWarnings" class="pok-pv-warn d-none"></div>
    <div class="pok-pv-scroll mt-2">
        <table class="pok-tbl">
            <thead><tr>
                <th style="width:44px;">#</th>
                <th>Kode MAK Lengkap</th>
                <th style="min-width:220px;">Nama Detil</th>
                <th class="text-end">Volume</th>
                <th>Satuan</th>
                <th class="text-end">Harga Satuan</th>
                <th class="text-end">Jumlah</th>
                <th>SD</th>
            </tr></thead>
            <tbody id="dfPokRows"></tbody>
        </table>
    </div>
    <div class="pok-pv-foot" id="dfPokFoot"></div>
</div>

@push('script')
<script>
(function () {
    var wrap = document.getElementById('dfPokPreview');
    var warnBox = document.getElementById('dfPokWarnings');
    var chips = document.getElementById('dfPokChips');
    var foot = document.getElementById('dfPokFoot');
    var tbody = document.getElementById('dfPokRows');
    var fmtId = new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 });

    function esc(s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    window.clearPokPreview = function () {
        wrap.classList.add('d-none');
        warnBox.classList.add('d-none');
        chips.innerHTML = '';
        foot.innerHTML = '';
        tbody.innerHTML = '';
    };

    window.renderPokPreview = function (data) {
        var rows = data.rows || [];

        var sd = { RM: 0, BLU: 0 };
        rows.forEach(function (r) { sd[r.sumber_dana] = (sd[r.sumber_dana] || 0) + Number(r.jumlah || 0); });

        chips.innerHTML =
            '<span class="pok-chip"><i class="bi bi-list-ol"></i><span class="lbl">Baris</span> ' + fmtId.format(rows.length) + '</span>'
            + '<span class="pok-chip"><i class="bi bi-cash-stack"></i><span class="lbl">Total</span> Rp ' + fmtId.format(data.total || 0) + '</span>'
            + (sd.BLU ? '<span class="pok-chip blu"><span class="lbl">BLU</span> Rp ' + fmtId.format(sd.BLU) + '</span>' : '')
            + (sd.RM ? '<span class="pok-chip rm"><span class="lbl">RM</span> Rp ' + fmtId.format(sd.RM) + '</span>' : '');

        if (data.warnings && data.warnings.length) {
            warnBox.classList.remove('d-none');
            warnBox.innerHTML = '<div class="fw-bold mb-1"><i class="bi bi-exclamation-triangle me-1"></i>'
                + data.warnings.length + ' catatan pembacaan (tidak menghalangi impor):</div><ul class="mb-0 ps-3">'
                + data.warnings.map(function (w) { return '<li>' + esc(w) + '</li>'; }).join('') + '</ul>';
        } else {
            warnBox.classList.add('d-none');
        }

        tbody.innerHTML = rows.map(function (r, i) {
            return '<tr style="--i:' + Math.min(i, 30) + ';">'
                + '<td class="text-muted">' + (i + 1) + '</td>'
                + '<td><span class="pok-kode">' + esc(r.kode_mak_lengkap) + '</span></td>'
                + '<td>' + esc(r.nama) + '</td>'
                + '<td class="text-end pok-num">' + esc(String(parseFloat(r.volume)).replace('.', ',')) + '</td>'
                + '<td>' + (r.satuan ? '<span class="pok-sat">' + esc(r.satuan) + '</span>' : '<span class="text-muted">—</span>') + '</td>'
                + '<td class="text-end pok-num">Rp ' + fmtId.format(r.harga_satuan) + '</td>'
                + '<td class="text-end pok-num fw-bold" style="color:#1e1b4b;">Rp ' + fmtId.format(r.jumlah) + '</td>'
                + '<td><span class="pok-sd ' + (r.sumber_dana === 'BLU' ? 'blu' : 'rm') + '">' + esc(r.sumber_dana) + '</span></td>'
                + '</tr>';
        }).join('');

        foot.innerHTML = '<i class="bi bi-mouse me-1"></i>Gulir untuk melihat seluruh ' + fmtId.format(rows.length)
            + ' baris · nilai pagu tiap item = volume × harga satuan sesuai POK.';

        wrap.classList.remove('d-none');
    };
})();
</script>
@endpush
