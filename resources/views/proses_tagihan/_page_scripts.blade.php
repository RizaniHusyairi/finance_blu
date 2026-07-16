{{--
    Modul script halaman Proses Tagihan (window.PT).
    Semua behavior kartu dikumpulkan di sini dalam bentuk fungsi init yang
    idempoten & re-callable, karena konten #ptShowContent dapat di-swap ulang
    oleh form async (refresh tanpa reload). Listener global (delegated pada
    document) didaftarkan sekali; listener per-elemen dipasang lewat PT.init().
--}}
@push('script')
<script>
(function () {
    'use strict';

    var reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var fmt = new Intl.NumberFormat('id-ID');

    var PT = window.PT = window.PT || {};

    /* ═════════════ Reveal on scroll ═════════════ */
    var revealObserver = ('IntersectionObserver' in window && !reduced)
        ? new IntersectionObserver(function (entries) {
            entries.forEach(function (e) {
                if (e.isIntersecting) {
                    e.target.classList.add('in');
                    revealObserver.unobserve(e.target);
                }
            });
        }, { threshold: 0.08, rootMargin: '0px 0px -40px 0px' })
        : null;

    function initReveal(root) {
        root.querySelectorAll('.reveal:not(.in)').forEach(function (el) {
            revealObserver ? revealObserver.observe(el) : el.classList.add('in');
        });
    }

    /* ═════════════ Count-up rupiah ═════════════ */
    function countUp(el) {
        var target = parseFloat(el.getAttribute('data-countup')) || 0;
        if (reduced || target <= 0) { el.textContent = fmt.format(target); return; }
        var dur = 1300, start = null;
        function tick(ts) {
            if (!start) start = ts;
            var p = Math.min((ts - start) / dur, 1);
            var eased = 1 - Math.pow(1 - p, 3);
            el.textContent = fmt.format(Math.round(target * eased));
            if (p < 1) requestAnimationFrame(tick);
        }
        requestAnimationFrame(tick);
    }
    var cuObserver = ('IntersectionObserver' in window)
        ? new IntersectionObserver(function (entries) {
            entries.forEach(function (e) {
                if (e.isIntersecting) { countUp(e.target); cuObserver.unobserve(e.target); }
            });
        }, { threshold: 0.4 })
        : null;

    function initCountup(root) {
        root.querySelectorAll('[data-countup]:not([data-cu-done])').forEach(function (el) {
            el.setAttribute('data-cu-done', '1');
            cuObserver ? cuObserver.observe(el) : countUp(el);
        });
    }

    /* ═════════════ Confetti ═════════════ */
    var CONF_COLORS = ['#4f46e5', '#10b981', '#f59e0b', '#06b6d4', '#8b5cf6', '#f43f5e'];

    function spawnConfetti(host, count, ttl) {
        if (reduced) { host.remove(); return; }
        for (var i = 0; i < count; i++) {
            var s = document.createElement('span');
            s.style.left = Math.random() * 100 + 'vw';
            s.style.background = CONF_COLORS[i % CONF_COLORS.length];
            s.style.animationDuration = (2.2 + Math.random() * 2.2) + 's';
            s.style.animationDelay = (Math.random() * 1.2) + 's';
            s.style.transform = 'rotate(' + Math.random() * 360 + 'deg)';
            host.appendChild(s);
        }
        setTimeout(function () { host.remove(); }, ttl || 6500);
    }

    // Perayaan on-demand (dipanggil form async saat bulk_approved).
    PT.celebrate = function () {
        var host = document.createElement('div');
        host.className = 'pt-confetti';
        document.body.appendChild(host);
        spawnConfetti(host, 70, 6000);
    };

    function initConfetti(root) {
        // Tagihan SELESAI — sekali per tagihan per sesi.
        var conf = root.querySelector('#ptConfetti') || (root.id === 'ptConfetti' ? root : null);
        conf = conf || document.getElementById('ptConfetti');
        if (conf && !conf.dataset.ptDone) {
            conf.dataset.ptDone = '1';
            var key = 'pt-confetti-' + conf.getAttribute('data-tagihan');
            if (!sessionStorage.getItem(key)) {
                sessionStorage.setItem(key, '1');
                spawnConfetti(conf, 90, 6500);
            } else {
                conf.remove();
            }
        }
        // Verifikasi massal sukses via jalur non-async (flash bulk_approved).
        var vm = document.getElementById('vmConfetti');
        if (vm && !vm.dataset.ptDone) {
            vm.dataset.ptDone = '1';
            spawnConfetti(vm, 70, 6000);
        }
    }

    /* ═════════════ Modal yang harus keluar dari kartu ber-transform ═════════════
       Modal dipindah ke <body> agar backdrop tidak terjebak stacking context.
       Setiap pemindahan ditandai data-pt-dynamic supaya bisa dibersihkan
       sebelum konten di-swap (hindari duplikat ID). */
    function initModals(root) {
        root.querySelectorAll('.js-pt-revisi-modal, #modalVerifikasiMassal').forEach(function (m) {
            if (m.parentElement !== document.body) {
                m.setAttribute('data-pt-dynamic', '1');
                document.body.appendChild(m);
            }
        });
        document.querySelectorAll('.js-pt-revisi-modal').forEach(syncRevisiModal);
    }

    // Dipanggil form async SEBELUM swap konten.
    PT.cleanupDynamicModals = function () {
        document.querySelectorAll('body > [data-pt-dynamic]').forEach(function (m) {
            var inst = window.bootstrap && bootstrap.Modal.getInstance(m);
            if (inst) inst.dispose();
            m.remove();
        });
        document.querySelectorAll('.modal-backdrop').forEach(function (b) { b.remove(); });
        document.body.classList.remove('modal-open');
        document.body.style.removeProperty('overflow');
        document.body.style.removeProperty('padding-right');
    };

    /* ═════════════ Modal revisi dokumen: sinkron field wajib ═════════════ */
    function syncRevisiModal(modal) {
        var checked = modal.querySelector('.js-revisi-target:checked');
        var target = checked ? checked.value : 'tagihan';

        modal.querySelectorAll('.js-target-section').forEach(function (section) {
            // Seksi 'tagihan' = checklist per bagian; seksi 'catatan'
            // dipakai bersama untuk target pajak/coa/bukti.
            var active = section.dataset.section === 'tagihan'
                ? target === 'tagihan'
                : target !== 'tagihan';
            section.classList.toggle('d-none', !active);

            section.querySelectorAll('textarea[name="catatan"]').forEach(function (el) {
                el.disabled = !active;
                el.required = active;
            });

            section.querySelectorAll('.js-revisi-item').forEach(function (item) {
                var cb = item.querySelector('.js-revisi-doc');
                var note = item.querySelector('.js-revisi-catatan');
                cb.disabled = !active;
                var on = active && cb.checked;
                note.classList.toggle('d-none', !cb.checked);
                note.disabled = !on;
                note.required = on;
            });
        });
    }

    document.addEventListener('change', function (e) {
        if (!e.target.classList) return;
        if (!e.target.classList.contains('js-revisi-target') && !e.target.classList.contains('js-revisi-doc')) return;
        var modal = e.target.closest('.js-pt-revisi-modal');
        if (modal) syncRevisiModal(modal);
    });

    /* ═════════════ Kalkulator pajak kontrak ═════════════ */
    function initPajakKontrak() {
        var form = document.getElementById('formPajakKontrak');
        if (!form || form.dataset.ptInit) return;
        form.dataset.ptInit = '1';

        var rowsBox = document.getElementById('pjkRows');
        var tpl = document.getElementById('pjkRowTemplate');
        var bruto = parseFloat(form.dataset.bruto) || 0;
        var nonPajak = parseFloat(form.dataset.nonPajak) || 0;

        function selectedOpt(sel) {
            return sel.value ? sel.options[sel.selectedIndex] : null;
        }

        /* Input DPP/nominal tampil berformat rupiah (1.234.567,89).
           parseRp membaca format itu; formatRp menuliskannya kembali;
           saat submit nilai dikembalikan ke angka mentah untuk server. */
        function parseRp(v) {
            if (v === null || v === undefined) return 0;
            v = String(v).trim();
            if (!v) return 0;
            return parseFloat(v.replace(/\./g, '').replace(',', '.')) || 0;
        }
        function formatRp(n) {
            if (!isFinite(n)) return '';
            return new Intl.NumberFormat('id-ID', { maximumFractionDigits: 2 }).format(n);
        }
        function liveFormat(input) {
            var raw = input.value;
            var caretDigits = raw.slice(0, input.selectionStart || 0).replace(/[^\d,]/g, '').length;

            // Hanya digit + satu koma desimal (maks 2 digit desimal).
            var clean = raw.replace(/[^\d,]/g, '');
            var firstComma = clean.indexOf(',');
            if (firstComma !== -1) {
                clean = clean.slice(0, firstComma + 1) + clean.slice(firstComma + 1).replace(/,/g, '');
            }
            var parts = clean.split(',');
            var intPart = parts[0].replace(/^0+(?=\d)/, '');
            var grouped = intPart.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            var out = grouped + (parts.length > 1 ? ',' + parts[1].slice(0, 2) : '');

            input.value = out;
            // Kembalikan caret ke posisi setelah jumlah digit yang sama.
            var pos = 0, seen = 0;
            while (pos < out.length && seen < caretDigits) {
                if (/[\d,]/.test(out[pos])) seen++;
                pos++;
            }
            try { input.setSelectionRange(pos, pos); } catch (err) { /* input tidak fokus */ }
        }

        // Sesuai kalkulator pajak: DPP = bruto × 100/(100+PPN); nominal = ROUNDUP(DPP × tarif, -2).
        function ppnRate() {
            var rate = 11;
            rowsBox.querySelectorAll('.pjk-select').forEach(function (sel) {
                var o = selectedOpt(sel);
                if (o && (o.dataset.kode || '').toUpperCase().indexOf('PPN') === 0) {
                    rate = parseFloat(o.dataset.persen) || rate;
                }
            });
            return rate;
        }
        function dppDefault() { return bruto * 100 / (100 + ppnRate()); }
        function roundUp100(x) { return Math.ceil(x / 100) * 100; }

        // Aktifkan/namai input sesuai tarif terpilih + tampilkan info KAP/rumus.
        function syncRow(row, recompute) {
            var sel = row.querySelector('.pjk-select');
            var dpp = row.querySelector('.pjk-dpp');
            var nom = row.querySelector('.pjk-nominal');
            var info = row.querySelector('.pjk-info');
            var o = selectedOpt(sel);

            if (!o) {
                dpp.disabled = nom.disabled = true;
                dpp.removeAttribute('name');
                nom.removeAttribute('name');
                info.classList.add('d-none');
                info.textContent = '';
                return;
            }

            dpp.disabled = nom.disabled = false;
            dpp.name = 'dpp[' + sel.value + ']';
            nom.name = 'nominal[' + sel.value + ']';

            var infoParts = [];
            if (o.dataset.kap) infoParts.push(o.dataset.kap);
            if (o.dataset.rumus) infoParts.push(o.dataset.rumus);
            info.textContent = infoParts.join(' — ');
            info.classList.toggle('d-none', infoParts.length === 0);

            if (recompute) {
                if (dpp.dataset.manual !== '1') {
                    dpp.value = formatRp(Math.round(dppDefault() * 100) / 100);
                }
                if (nom.dataset.manual !== '1') {
                    nom.value = formatRp(roundUp100(parseRp(dpp.value) * (parseFloat(o.dataset.persen) || 0) / 100));
                }
            }
        }

        // Tarif yang sudah dipakai baris lain dinonaktifkan agar tidak dipilih ganda.
        function syncOptionDisabling() {
            var chosen = [];
            rowsBox.querySelectorAll('.pjk-select').forEach(function (s) { if (s.value) chosen.push(s.value); });
            rowsBox.querySelectorAll('.pjk-select').forEach(function (s) {
                Array.prototype.forEach.call(s.options, function (o) {
                    if (!o.value) return;
                    o.disabled = chosen.indexOf(o.value) !== -1 && o.value !== s.value;
                });
            });
        }

        function refreshAll(recompute) {
            rowsBox.querySelectorAll('.pjk-row').forEach(function (row) { syncRow(row, recompute); });
            syncOptionDisabling();

            var total = 0;
            rowsBox.querySelectorAll('.pjk-row').forEach(function (row) {
                if (!row.querySelector('.pjk-select').value) return;
                total += parseRp(row.querySelector('.pjk-nominal').value);
            });
            document.getElementById('pjkTotal').textContent = fmt.format(Math.round(total + nonPajak));
            document.getElementById('pjkNetto').textContent = fmt.format(Math.round(Math.max(0, bruto - total - nonPajak)));
        }

        function addRow() {
            var node = tpl.content.firstElementChild.cloneNode(true);
            rowsBox.appendChild(node);
            refreshAll(false);
            return node;
        }

        form.querySelector('#pjkAddRow').addEventListener('click', function () { addRow(); });

        rowsBox.addEventListener('change', function (e) {
            if (!e.target.classList.contains('pjk-select')) return;
            var row = e.target.closest('.pjk-row');
            // Ganti tipe pajak = hitung ulang default baris ini.
            row.querySelector('.pjk-dpp').dataset.manual = '';
            row.querySelector('.pjk-nominal').dataset.manual = '';
            // Pilihan PPN mengubah faktor ekstraksi DPP semua baris non-manual.
            refreshAll(true);
        });

        rowsBox.addEventListener('input', function (e) {
            var row = e.target.closest('.pjk-row');
            if (!row) return;
            if (e.target.classList.contains('pjk-dpp')) {
                liveFormat(e.target);
                e.target.dataset.manual = '1';
                var nom = row.querySelector('.pjk-nominal');
                var o = selectedOpt(row.querySelector('.pjk-select'));
                if (o && nom.dataset.manual !== '1') {
                    nom.value = formatRp(roundUp100(parseRp(e.target.value) * (parseFloat(o.dataset.persen) || 0) / 100));
                }
                refreshAll(false);
            } else if (e.target.classList.contains('pjk-nominal')) {
                liveFormat(e.target);
                e.target.dataset.manual = '1';
                refreshAll(false);
            }
        });

        rowsBox.addEventListener('click', function (e) {
            var btn = e.target.closest('.pjk-remove');
            if (!btn) return;
            btn.closest('.pjk-row').remove();
            if (!rowsBox.querySelector('.pjk-row')) addRow(); // minimal satu baris
            refreshAll(true); // menghapus baris PPN mengubah default DPP baris lain
        });

        // Sebelum nilai terkirim, kembalikan input berformat rupiah ke angka
        // mentah (listener pada form berjalan lebih dulu daripada interceptor
        // async di document yang membangun FormData). Tampilan diformat ulang
        // setelahnya agar tetap rapi selama request berjalan.
        form.addEventListener('submit', function () {
            var inputs = rowsBox.querySelectorAll('.pjk-dpp, .pjk-nominal');
            inputs.forEach(function (inp) {
                if (!inp.name || inp.disabled) return;
                inp.value = String(parseRp(inp.value));
            });
            setTimeout(function () {
                inputs.forEach(function (inp) {
                    if (!inp.name || inp.disabled || inp.value === '') return;
                    inp.value = formatRp(parseFloat(inp.value) || 0);
                });
            }, 0);
        });

        if (!rowsBox.querySelector('.pjk-row')) addRow();
        refreshAll(true);
    }

    /* ═════════════ Listener delegated global (sekali daftar) ═════════════ */

    // Ripple pada tombol aksi.
    document.addEventListener('click', function (ev) {
        var btn = ev.target.closest('.btn-pt-action');
        if (!btn || reduced) return;
        var rect = btn.getBoundingClientRect();
        var r = document.createElement('span');
        var size = Math.max(rect.width, rect.height);
        r.className = 'pt-ripple';
        r.style.width = r.style.height = size + 'px';
        r.style.left = (ev.clientX - rect.left - size / 2) + 'px';
        r.style.top = (ev.clientY - rect.top - size / 2) + 'px';
        btn.appendChild(r);
        requestAnimationFrame(function () { r.classList.add('go'); });
        setTimeout(function () { r.remove(); }, 650);
    });

    // Stepper: klik untuk scroll ke seksi.
    document.addEventListener('click', function (ev) {
        var btn = ev.target.closest('.pt-stage[data-scroll]');
        if (!btn) return;
        var target = document.getElementById(btn.getAttribute('data-scroll'));
        if (target) target.scrollIntoView({ behavior: reduced ? 'auto' : 'smooth', block: 'start' });
    });

    // Salin nomor: chip ringkasan (.rk-copy) & tombol generik [data-copy].
    document.addEventListener('click', function (ev) {
        var el = ev.target.closest('[data-copy]');
        if (!el) return;
        var text = el.getAttribute('data-copy');

        function copy(t) {
            if (navigator.clipboard && navigator.clipboard.writeText) {
                return navigator.clipboard.writeText(t);
            }
            var ta = document.createElement('textarea');
            ta.value = t; document.body.appendChild(ta);
            ta.select(); document.execCommand('copy'); ta.remove();
            return Promise.resolve();
        }

        copy(text).then(function () {
            if (el.classList.contains('rk-copy')) {
                var label = el.querySelector('.rk-copy-text');
                var asli = label ? label.textContent : '';
                el.classList.add('rk-copied');
                if (label) label.textContent = 'Tersalin!';
                setTimeout(function () {
                    el.classList.remove('rk-copied');
                    if (label) label.textContent = asli;
                }, 1400);
            } else {
                var orig = el.innerHTML;
                el.innerHTML = '<i class="bi bi-check-lg"></i> Tersalin!';
                el.classList.add('btn-success', 'text-white');
                setTimeout(function () {
                    el.innerHTML = orig;
                    el.classList.remove('btn-success', 'text-white');
                }, 1600);
            }
        });
    });

    /* ═════════════ Entry point ═════════════ */
    PT.init = function (root) {
        root = root || document.getElementById('ptShowContent') || document;
        initReveal(root);
        initCountup(root);
        initModals(root);
        initConfetti(root);
        initPajakKontrak();
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { PT.init(); });
    } else {
        PT.init();
    }
})();
</script>
@endpush
