{{--
    Form async halaman Proses Tagihan.
    Semua form ber-kelas .js-async-form disubmit via fetch (tanpa navigasi):
    - Loading  : tombol morph (spinner + shimmer) + overlay kartu bertema
                 "cockpit" (pesawat melayang + titik memantul).
    - Hasil    : toast SkyAlert (sukses/peringatan/gagal), tombol jadi ✓,
                 lalu konten #ptShowContent di-refresh via ?partial=1 — tanpa
                 reload halaman. Gagal: kartu bergetar, isian form tetap utuh.
    Server side: middleware AjaxFlashToJson mengubah redirect+flash menjadi
    JSON bila request membawa header X-Async-Form.
--}}
<style>
    /* ---------- Keyframes form async ---------- */
    @keyframes ptaFadeIn    { from { opacity: 0; } to { opacity: 1; } }
    @keyframes ptaPop       { 0% { transform: scale(.5); opacity: 0; } 65% { transform: scale(1.15); } 100% { transform: scale(1); opacity: 1; } }
    @keyframes ptaShake     { 10%, 90% { transform: translateX(-2px); } 20%, 80% { transform: translateX(3px); }
                              30%, 50%, 70% { transform: translateX(-5px); } 40%, 60% { transform: translateX(5px); } }
    @keyframes ptaPlane     { 0%, 100% { transform: translate(-6px, 2px) rotate(-4deg); } 50% { transform: translate(6px, -4px) rotate(4deg); } }
    @keyframes ptaDot       { 0%, 80%, 100% { transform: translateY(0); opacity: .45; } 40% { transform: translateY(-7px); opacity: 1; } }
    @keyframes ptaBtnSheen  { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }

    /* ---------- Tombol saat memproses ---------- */
    .pta-busy {
        pointer-events: none; opacity: .92;
        background-image: linear-gradient(110deg, transparent 30%, rgba(255,255,255,.35) 50%, transparent 70%) !important;
        background-size: 200% 100% !important;
        animation: ptaBtnSheen 1.6s linear infinite;
    }
    .pta-done-ok { background: linear-gradient(135deg, #059669, #10b981) !important; border-color: transparent !important; color: #fff !important; }
    .pta-done-ok .pta-check { display: inline-flex; animation: ptaPop .45s cubic-bezier(.34,1.56,.64,1) both; }

    /* ---------- Overlay kartu ---------- */
    .pt-async-host { position: relative; }
    .pt-async-overlay {
        position: absolute; inset: 0; z-index: 40;
        display: flex; flex-direction: column; align-items: center; justify-content: center; gap: .55rem;
        background: rgba(255, 255, 255, .68);
        backdrop-filter: blur(3px); -webkit-backdrop-filter: blur(3px);
        border-radius: inherit;
        animation: ptaFadeIn .25s ease both;
    }
    .pt-async-overlay .pta-plane {
        width: 54px; height: 54px; border-radius: 16px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.5rem; color: #fff;
        background: linear-gradient(135deg, var(--pt-primary, #4f46e5), var(--pt-primary-2, #7c3aed));
        box-shadow: 0 14px 30px -12px rgba(79, 70, 229, .8);
    }
    .pt-async-overlay .pta-plane i { animation: ptaPlane 1.6s ease-in-out infinite; }
    .pt-async-overlay .pta-text { font-size: .8rem; font-weight: 800; letter-spacing: .06em; color: #4338ca; text-transform: uppercase; }
    .pt-async-overlay .pta-dots { display: flex; gap: .35rem; }
    .pt-async-overlay .pta-dots i {
        width: 8px; height: 8px; border-radius: 50%;
        background: var(--pt-primary, #4f46e5);
        animation: ptaDot 1.2s ease-in-out infinite;
    }
    .pt-async-overlay .pta-dots i:nth-child(2) { animation-delay: .15s; }
    .pt-async-overlay .pta-dots i:nth-child(3) { animation-delay: .3s; }

    /* ---------- Hasil ---------- */
    .pta-shake { animation: ptaShake .5s cubic-bezier(.36,.07,.19,.97) both; }
    .pta-swapped { animation: ptaFadeIn .4s ease both; }

    @media (prefers-reduced-motion: reduce) {
        .pta-busy, .pt-async-overlay, .pt-async-overlay .pta-plane i,
        .pt-async-overlay .pta-dots i, .pta-shake, .pta-swapped, .pta-done-ok .pta-check {
            animation: none !important;
        }
        .pt-async-overlay { backdrop-filter: none; -webkit-backdrop-filter: none; }
    }
</style>

@push('script')
<script>
(function () {
    'use strict';

    var PARTIAL_URL = @json(route('proses-tagihan.show', $tagihan->id) . '?partial=1');
    var CONTAINER_ID = 'ptShowContent';
    var reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    function toast(level, text, opts) {
        if (!window.SkyAlert) { window.alert(text); return; }
        var map = { success: 'success', warning: 'warning', info: 'info', danger: 'danger' };
        SkyAlert.show(Object.assign({
            type: map[level] || 'info',
            message: text,
            duration: level === 'danger' ? 9000 : 6000,
        }, opts || {}));
    }

    /* ---------- Loading state ---------- */
    function overlayHost(form) {
        return form.closest('.modal-content')
            || form.closest('.process-card')
            || form.closest('.vm-inner')
            || form;
    }

    function showLoading(form) {
        var btn = form.dataset.ptaSubmitter
            ? form.querySelector('[data-pta-active]')
            : form.querySelector('button[type=submit]');
        var host = overlayHost(form);
        host.classList.add('pt-async-host');

        var ov = document.createElement('div');
        ov.className = 'pt-async-overlay';
        ov.innerHTML =
            '<div class="pta-plane"><i class="bi bi-airplane-fill"></i></div>' +
            '<div class="pta-text">Memproses…</div>' +
            '<div class="pta-dots"><i></i><i></i><i></i></div>';
        host.appendChild(ov);

        var restore = null;
        if (btn) {
            var w = btn.getBoundingClientRect().width;
            var orig = btn.innerHTML;
            btn.style.minWidth = Math.ceil(w) + 'px';
            btn.disabled = true;
            btn.classList.add('pta-busy');
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Memproses…';
            restore = function () {
                btn.disabled = false;
                btn.classList.remove('pta-busy');
                btn.innerHTML = orig;
                btn.style.removeProperty('min-width');
            };
        }

        return {
            btn: btn,
            done: function () { ov.remove(); },
            restore: function () { ov.remove(); if (restore) restore(); },
            success: function () {
                ov.remove();
                if (btn) {
                    btn.classList.remove('pta-busy');
                    btn.classList.add('pta-done-ok');
                    btn.innerHTML = '<span class="pta-check"><i class="bi bi-check-lg"></i></span> Berhasil';
                }
            },
        };
    }

    function shakeCard(form) {
        if (reduced) return;
        var host = overlayHost(form);
        host.classList.remove('pta-shake');
        void host.offsetWidth; // restart animasi
        host.classList.add('pta-shake');
        setTimeout(function () { host.classList.remove('pta-shake'); }, 600);
    }

    /* ---------- Tutup modal pemilik form (bila ada) ---------- */
    function closeOwnerModal(form) {
        var modal = form.closest('.modal');
        if (!modal || !window.bootstrap) return Promise.resolve();
        var inst = bootstrap.Modal.getInstance(modal);
        if (!inst) return Promise.resolve();
        return new Promise(function (resolve) {
            modal.addEventListener('hidden.bs.modal', function () { resolve(); }, { once: true });
            inst.hide();
            setTimeout(resolve, 600); // jaring pengaman bila event tidak terpanggil
        });
    }

    /* ---------- Refresh konten tanpa reload ---------- */
    function refreshContent() {
        return fetch(PARTIAL_URL, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' },
            credentials: 'same-origin',
        }).then(function (r) {
            if (!r.ok) throw new Error('partial ' + r.status);
            return r.text();
        }).then(function (html) {
            var container = document.getElementById(CONTAINER_ID);
            if (!container) { window.location.reload(); return; }
            if (window.PT && PT.cleanupDynamicModals) PT.cleanupDynamicModals();
            container.innerHTML = html;
            container.classList.remove('pta-swapped');
            void container.offsetWidth;
            container.classList.add('pta-swapped');
            if (window.PT && PT.init) PT.init(container);
            if (window.enhanceCoaSelects) window.enhanceCoaSelects(container);
        }).catch(function () {
            // Fallback terakhir: konten gagal diambil — muat ulang halaman.
            window.location.reload();
        });
    }

    /* ---------- Interceptor submit ---------- */
    document.addEventListener('submit', function (e) {
        var form = e.target;
        if (!(form instanceof HTMLFormElement)) return;
        if (!form.classList.contains('js-async-form')) return;
        // Sudah dicegah pihak lain (mis. interceptor SkyConfirm) → biarkan.
        if (e.defaultPrevented) return;

        e.preventDefault();

        if (form.dataset.ptaBusy) return;
        form.dataset.ptaBusy = '1';

        // Tandai tombol submit yang dipakai (untuk form dengan >1 tombol submit,
        // mis. tombol "Setujui" bernilai aksi=approve).
        form.querySelectorAll('[data-pta-active]').forEach(function (b) { b.removeAttribute('data-pta-active'); });
        var submitter = e.submitter || form.querySelector('button[type=submit]');
        if (submitter) submitter.setAttribute('data-pta-active', '1');
        form.dataset.ptaSubmitter = submitter ? '1' : '';

        var body = new FormData(form);
        // Sertakan name/value tombol submit (mis. name="aksi" value="approve").
        if (submitter && submitter.name) body.append(submitter.name, submitter.value);

        var ui = showLoading(form);

        fetch(form.action, {
            method: 'POST',
            body: body,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-Async-Form': '1',
                'Accept': 'application/json',
            },
            credentials: 'same-origin',
        }).then(function (r) {
            var ct = r.headers.get('content-type') || '';
            var isJson = ct.indexOf('json') !== -1;
            return (isJson ? r.json() : Promise.resolve(null)).then(function (data) {
                return { status: r.status, data: data };
            });
        }).then(function (res) {
            var data = res.data || {};

            if (res.status === 419) {
                ui.restore();
                toast('danger', 'Sesi Anda berakhir atau berkas terlalu besar. Muat ulang halaman lalu coba lagi.', {
                    title: 'Sesi Berakhir', persistent: true,
                    actions: [{ label: 'Muat Ulang', onClick: function () { window.location.reload(); } }],
                });
                return;
            }

            if (res.status === 422) {
                ui.restore();
                var msg = data.message
                    || (data.errors && Object.values(data.errors)[0] && Object.values(data.errors)[0][0])
                    || 'Periksa kembali isian formulir.';
                toast('danger', msg, { title: 'Validasi Gagal' });
                shakeCard(form);
                return;
            }

            if (res.status === 413) {
                ui.restore();
                toast('danger', 'Berkas yang diunggah terlalu besar. Maksimal 5 MB per berkas.', { title: 'Unggahan Ditolak' });
                shakeCard(form);
                return;
            }

            if (res.status >= 500 || !res.data) {
                ui.restore();
                toast('danger', 'Terjadi kesalahan pada server. Silakan coba lagi atau hubungi administrator.', { title: 'Gagal Memproses' });
                shakeCard(form);
                return;
            }

            // 200 JSON dari middleware AjaxFlashToJson.
            var messages = data.messages || [];
            if (!messages.length) {
                messages = [{ level: data.ok ? 'success' : 'danger', text: data.ok ? 'Berhasil diproses.' : 'Permintaan tidak dapat diproses.' }];
            }

            if (!data.ok) {
                ui.restore();
                messages.forEach(function (m) { toast(m.level, m.text); });
                shakeCard(form);
                return;
            }

            // ── SUKSES ──
            ui.success();
            messages.forEach(function (m, i) {
                setTimeout(function () { toast(m.level, m.text); }, i * 220);
            });
            if (data.bulk_approved && window.PT && PT.celebrate) PT.celebrate();

            closeOwnerModal(form).then(refreshContent);
        }).catch(function () {
            ui.restore();
            toast('danger', 'Koneksi terputus — periksa jaringan Anda lalu coba lagi.', { title: 'Gagal Terhubung' });
            shakeCard(form);
        }).finally(function () {
            delete form.dataset.ptaBusy;
        });
    });
})();
</script>
@endpush
