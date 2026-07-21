{{--
    Tooltip kustom tombol Hapus pada master data (DIPA & COA) — dipakai
    halaman daftar & detail keduanya. Elemen ber-atribut
    data-deltip="ok|locked|coa-ok|coa-locked" memunculkan kartu tooltip
    animatif berisi aturan hapus. Tooltip dirender ke <body> (fixed) agar
    tidak terpotong overflow tabel, dan memakai delegated listener sehingga
    tetap berfungsi setelah tabel di-swap AJAX.
--}}
<style>
    @keyframes dtPop {
        0%   { opacity: 0; transform: translateY(8px) scale(.85); }
        65%  { opacity: 1; transform: translateY(-3px) scale(1.03); }
        100% { opacity: 1; transform: translateY(0) scale(1); }
    }
    @keyframes dtPopBawah {
        0%   { opacity: 0; transform: translateY(-8px) scale(.85); }
        65%  { opacity: 1; transform: translateY(3px) scale(1.03); }
        100% { opacity: 1; transform: translateY(0) scale(1); }
    }
    @keyframes dtSheen { 0%, 55% { left: -70%; } 100% { left: 140%; } }
    @keyframes dtIconWiggle { 20%, 60% { transform: rotate(-10deg); } 40%, 80% { transform: rotate(8deg); } }

    .dipa-del-tip {
        position: fixed; z-index: 3000; width: 264px; max-width: calc(100vw - 16px);
        pointer-events: none; opacity: 0;
        border-radius: 14px; padding: 12px 14px; overflow: hidden;
        color: #fff; font-size: .76rem; line-height: 1.5;
        box-shadow: 0 18px 40px -14px rgba(15, 23, 42, .55);
        transform-origin: bottom center;
    }
    .dipa-del-tip.bawah { transform-origin: top center; }
    .dipa-del-tip.show { animation: dtPop .32s cubic-bezier(.34,1.56,.64,1) both; }
    .dipa-del-tip.show.bawah { animation: dtPopBawah .32s cubic-bezier(.34,1.56,.64,1) both; }

    .dipa-del-tip::before { /* sapuan kilau */
        content: ''; position: absolute; top: 0; bottom: 0; width: 40%; left: -70%;
        background: linear-gradient(100deg, transparent, rgba(255,255,255,.16), transparent);
        transform: skewX(-18deg); animation: dtSheen 2.6s ease-in-out .25s infinite;
    }
    .dipa-del-tip .dt-arrow {
        position: absolute; bottom: -5px; width: 12px; height: 12px;
        transform: translateX(-50%) rotate(45deg); border-radius: 2px;
    }
    .dipa-del-tip.bawah .dt-arrow { bottom: auto; top: -5px; }

    .dipa-del-tip .dt-head { display: table; margin-bottom: 5px; }
    .dipa-del-tip .dt-ic {
        display: table-cell; vertical-align: middle;
        width: 30px; height: 30px; border-radius: 9px; text-align: center;
        background: rgba(255,255,255,.18);
    }
    .dipa-del-tip .dt-ic i { font-size: 15px; line-height: 30px; display: inline-block;
        animation: dtIconWiggle .6s ease .15s 1; }
    .dipa-del-tip .dt-title { display: table-cell; vertical-align: middle;
        padding-left: 9px; font-weight: 800; font-size: .8rem; letter-spacing: .02em; }
    .dipa-del-tip .dt-body { color: rgba(255,255,255,.88); }
    .dipa-del-tip .dt-body b { color: #fff; }

    .dipa-del-tip.tip-ok { background: linear-gradient(135deg, #9f1239, #e11d48); }
    .dipa-del-tip.tip-ok .dt-arrow { background: #e11d48; }
    .dipa-del-tip.tip-ok.bawah .dt-arrow { background: #9f1239; }
    .dipa-del-tip.tip-locked { background: linear-gradient(135deg, #1e293b, #475569); }
    .dipa-del-tip.tip-locked .dt-arrow { background: #475569; }
    .dipa-del-tip.tip-locked.bawah .dt-arrow { background: #1e293b; }

    @media (prefers-reduced-motion: reduce) {
        .dipa-del-tip.show, .dipa-del-tip .dt-ic i, .dipa-del-tip::before { animation: none !important; }
        .dipa-del-tip.show { opacity: 1; }
    }
</style>

@push('script')
<script>
(function () {
    'use strict';
    if (window.__dipaDelTipInit) return;
    window.__dipaDelTipInit = true;

    var KONTEN = {
        ok: {
            icon: 'bi-trash3-fill',
            title: 'Hapus DIPA Permanen',
            body: 'DIPA ini masih <b>kosong</b> (tanpa COA &amp; transaksi) sehingga boleh dihapus. '
                + 'Seluruh revisinya ikut terhapus dan <b>nomornya dapat dipakai ulang</b>.',
        },
        locked: {
            icon: 'bi-shield-lock-fill',
            title: 'Tidak Dapat Dihapus',
            body: 'DIPA sudah memiliki <b>COA</b> atau dipakai tagihan/kontrak. '
                + 'Gunakan tombol <b>Nonaktifkan</b> agar jejak audit anggaran tetap utuh.',
        },
        'coa-ok': {
            icon: 'bi-trash3-fill',
            title: 'Hapus COA Permanen',
            body: 'COA ini <b>belum dipakai</b> pada item anggaran DIPA mana pun, '
                + 'sehingga boleh dihapus permanen dari master data.',
        },
        'coa-locked': {
            icon: 'bi-shield-lock-fill',
            title: 'Tidak Dapat Dihapus',
            body: 'COA sudah dipakai pada <b>item anggaran DIPA</b>. '
                + 'Gunakan tombol <b>Nonaktifkan</b> — riwayat transaksi &amp; jejak audit tetap utuh.',
        },
        'item-ok': {
            icon: 'bi-trash3-fill',
            title: 'Hapus COA dari Revisi',
            body: 'COA ini belum dipakai tagihan pada revisi ini. Menghapus hanya '
                + 'melepasnya dari revisi aktif — <b>master COA tetap ada</b>.',
        },
        'item-locked': {
            icon: 'bi-shield-lock-fill',
            title: 'Tidak Dapat Dihapus',
            body: 'COA ini sudah <b>dipakai tagihan</b> atau memiliki <b>realisasi</b> '
                + 'pada revisi ini. Gunakan <b>Nonaktifkan</b> agar tidak dipilih tagihan baru.',
        },
    };

    var tip = document.createElement('div');
    tip.className = 'dipa-del-tip';
    document.body.appendChild(tip);
    var aktif = null;

    function tampilkan(target) {
        var mode = target.getAttribute('data-deltip');
        var k = KONTEN[mode] || KONTEN.ok;
        var varian = mode && mode.indexOf('locked') !== -1 ? 'locked' : 'ok';

        tip.className = 'dipa-del-tip tip-' + varian;
        tip.innerHTML =
            '<span class="dt-arrow"></span>' +
            '<div class="dt-head"><span class="dt-ic"><i class="bi ' + k.icon + '"></i></span>' +
            '<span class="dt-title">' + k.title + '</span></div>' +
            '<div class="dt-body">' + k.body + '</div>';

        var rect = target.getBoundingClientRect();
        var x = rect.left + rect.width / 2;

        // Ukur dulu dalam keadaan tersembunyi agar lebar/tinggi diketahui.
        tip.style.visibility = 'hidden';
        tip.style.left = '0px';
        tip.style.top = '0px';
        var w = tip.offsetWidth;
        var h = tip.offsetHeight;

        // Jepit posisi horizontal ke dalam viewport (margin 8px) —
        // tombol di tepi kanan tidak lagi membuat kartu terpotong.
        var left = Math.max(8, Math.min(x - w / 2, window.innerWidth - w - 8));
        tip.style.left = left + 'px';

        // Panah tetap menunjuk titik tengah tombol meski kartu digeser.
        var arrow = tip.querySelector('.dt-arrow');
        arrow.style.left = Math.max(14, Math.min(x - left, w - 14)) + 'px';

        // Default di atas tombol; balik ke bawah bila mepet tepi atas layar.
        var perluBawah = rect.top - h - 10 < 8;
        if (perluBawah) {
            tip.classList.add('bawah');
            tip.style.top = (rect.bottom + 10) + 'px';
        } else {
            tip.style.top = (rect.top - h - 10) + 'px';
        }

        tip.style.visibility = '';

        // Restart animasi masuk.
        void tip.offsetWidth;
        tip.classList.add('show');
        aktif = target;
    }

    function sembunyikan() {
        tip.classList.remove('show');
        aktif = null;
    }

    document.addEventListener('mouseover', function (e) {
        var target = e.target.closest('[data-deltip]');
        if (target && target !== aktif) tampilkan(target);
    });
    document.addEventListener('mouseout', function (e) {
        var target = e.target.closest('[data-deltip]');
        if (target && (!e.relatedTarget || !target.contains(e.relatedTarget))) sembunyikan();
    });
    // Posisi jadi basi saat halaman digulir/di-resize — sembunyikan saja.
    document.addEventListener('scroll', sembunyikan, true);
    window.addEventListener('resize', sembunyikan);
})();
</script>
@endpush
