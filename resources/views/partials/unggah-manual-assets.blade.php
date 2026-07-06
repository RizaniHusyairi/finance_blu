{{-- Gaya & interaksi bersama untuk modal "Unggah Manual TTD Basah Vendor"
     (dipakai halaman Detail SPK dan Tagihan SPK). Prefiks .um- agar tidak
     bentrok dengan gaya halaman. --}}
<style>
    /* ── Kerangka modal ─────────────────────────────────────────── */
    .um-modal .modal-content{
        border:0; border-radius:1.1rem; overflow:hidden;
        box-shadow:0 24px 70px -18px rgba(120,53,15,.35);
    }
    .um-modal .modal-header{
        border:0; padding:1.15rem 1.4rem 1rem;
        background:linear-gradient(120deg,#7C2D12 0%,#B45309 55%,#D97706 100%);
        color:#fff; position:relative; overflow:hidden;
    }
    .um-modal .modal-header::after{
        content:""; position:absolute; inset:0;
        background:radial-gradient(420px 120px at 85% -20%,rgba(255,255,255,.28),transparent 70%);
        pointer-events:none;
    }
    .um-head-ic{
        width:44px;height:44px;border-radius:14px;flex-shrink:0;
        display:flex;align-items:center;justify-content:center;
        background:rgba(255,255,255,.18);border:1px solid rgba(255,255,255,.3);
        font-size:1.25rem;backdrop-filter:blur(2px);
        animation:umFloat 3.2s ease-in-out infinite;
    }
    @keyframes umFloat{0%,100%{transform:translateY(0)}50%{transform:translateY(-4px)}}
    .um-modal .modal-title{font-weight:800;letter-spacing:-.01em}
    .um-modal .modal-header small{color:rgba(255,255,255,.85)}
    .um-modal .btn-close{filter:invert(1) grayscale(1) brightness(1.6)}
    .um-modal .modal-body{padding:1.35rem 1.4rem; background:#FDFBF8}
    .um-modal .modal-footer{
        border-top:1px solid #F1E7D8; background:#FBF7F1;
        padding:.9rem 1.4rem; align-items:center;
    }

    /* ── Kartu dokumen (drop area) ──────────────────────────────── */
    .um-doc{
        display:flex; align-items:center; gap:.9rem; width:100%;
        background:#fff; border:1.5px dashed #E2D6C3; border-radius:.9rem;
        padding:.9rem 1rem; cursor:pointer; margin:0;
        transition:border-color .2s, box-shadow .2s, transform .2s, background .2s;
        opacity:0;
    }
    .modal.show .um-doc{animation:umRise .45s cubic-bezier(.22,1,.36,1) forwards}
    .modal.show .um-doc:nth-child(2){animation-delay:.06s}
    .modal.show .um-doc:nth-child(3){animation-delay:.12s}
    .modal.show .um-doc:nth-child(4){animation-delay:.18s}
    @keyframes umRise{from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:translateY(0)}}
    .um-doc:hover:not(.um-locked):not(.um-done){
        border-color:#D97706; box-shadow:0 10px 24px -14px rgba(180,83,9,.45);
        transform:translateY(-2px); background:#FFFDF9;
    }
    .um-doc.um-drag{border-color:#B45309; background:#FDF3E3; border-style:solid}
    .um-doc:focus-within{outline:3px solid #F5D9AE; outline-offset:2px}

    .um-doc-ic{
        width:46px;height:46px;border-radius:12px;flex-shrink:0;
        display:flex;align-items:center;justify-content:center;font-size:1.35rem;
        background:#FDF3E3;color:#B45309;border:1px solid #F1DFBB;
        transition:background .25s,color .25s,transform .25s;
    }
    .um-doc-name{font-weight:700;font-size:.95rem;display:flex;align-items:center;gap:.45rem;flex-wrap:wrap}
    .um-doc-sub{font-size:.8rem;color:#8A7A63}
    .um-doc-file{
        display:none;font-size:.8rem;font-weight:600;color:#15803D;
        align-items:center;gap:.35rem;margin-top:.15rem;
    }
    .um-doc-action{
        margin-left:auto;flex-shrink:0;font-size:.8rem;font-weight:700;
        color:#B45309;background:#FDF3E3;border:1px solid #F1DFBB;border-radius:2rem;
        padding:.4rem .95rem;white-space:nowrap;transition:all .2s;
    }
    .um-doc:hover:not(.um-locked):not(.um-done) .um-doc-action{background:#B45309;color:#fff}

    /* Terpilih */
    .um-doc.um-picked{border-style:solid;border-color:#86C79A;background:#F6FCF8}
    .um-doc.um-picked .um-doc-ic{background:#E8F5EC;color:#15803D;border-color:#CBE7D3;animation:umPop .4s cubic-bezier(.34,1.56,.64,1)}
    .um-doc.um-picked .um-doc-file{display:inline-flex;animation:umFadeIn .35s ease}
    .um-doc.um-picked .um-doc-sub{display:none}
    .um-doc.um-picked .um-doc-action{background:#fff;color:#15803D;border-color:#CBE7D3}
    @keyframes umPop{0%{transform:scale(.6) rotate(-8deg)}60%{transform:scale(1.12)}100%{transform:scale(1)}}
    @keyframes umFadeIn{from{opacity:0;transform:translateX(-6px)}to{opacity:1;transform:translateX(0)}}

    /* Terkunci / selesai */
    .um-doc.um-locked{cursor:not-allowed;background:#FAFAF8;border-style:solid;border-color:#EAE6DE;opacity:.75}
    .um-doc.um-locked .um-doc-ic{background:#F1F0EC;color:#9C948A;border-color:#E7E3DC}
    .um-doc.um-done{cursor:default;border-style:solid;border-color:#CBE7D3;background:#F6FCF8}
    .um-doc.um-done .um-doc-ic{background:#E8F5EC;color:#15803D;border-color:#CBE7D3}

    /* ── Field & pernyataan ─────────────────────────────────────── */
    .um-modal .form-control{border-radius:.6rem;border-color:#E7DECF}
    .um-modal .form-control:focus{border-color:#D97706;box-shadow:0 0 0 .2rem rgba(217,119,6,.15)}
    .um-declare{
        display:flex;gap:.7rem;align-items:flex-start;cursor:pointer;margin:0;
        background:#FDF3E3;border:1.5px solid #F1DFBB;border-radius:.9rem;padding:.9rem 1rem;
        transition:border-color .25s, background .25s, box-shadow .25s;
    }
    .um-declare input{width:1.15rem;height:1.15rem;margin-top:.15rem;flex-shrink:0;accent-color:#B45309;cursor:pointer}
    .um-declare.um-agreed{border-color:#86C79A;background:#F6FCF8;box-shadow:0 6px 18px -12px rgba(21,128,61,.5)}
    .um-declare.um-agreed .um-declare-ic{color:#15803D;animation:umPop .4s cubic-bezier(.34,1.56,.64,1)}

    /* ── Penghitung & tombol simpan ─────────────────────────────── */
    .um-counter{
        font-size:.78rem;font-weight:700;color:#8A7A63;background:#F5EFE5;
        border-radius:2rem;padding:.35rem .8rem;margin-right:auto;
        display:inline-flex;align-items:center;gap:.4rem;transition:all .25s;
    }
    .um-counter.um-has{background:#E8F5EC;color:#15803D}
    .um-submit{
        border:0;border-radius:.7rem;padding:.6rem 1.35rem;font-weight:700;color:#fff;
        background:linear-gradient(120deg,#B45309,#D97706);
        box-shadow:0 10px 22px -10px rgba(180,83,9,.65);
        position:relative;overflow:hidden;transition:transform .2s, box-shadow .2s, filter .2s;
    }
    .um-submit:hover:not(:disabled){transform:translateY(-2px);box-shadow:0 16px 30px -12px rgba(180,83,9,.75);color:#fff}
    .um-submit:active:not(:disabled){transform:translateY(0)}
    .um-submit:disabled{background:#D8CFC2;box-shadow:none;cursor:not-allowed}
    .um-submit::after{
        content:"";position:absolute;top:0;bottom:0;width:36%;left:-45%;
        background:linear-gradient(100deg,transparent,rgba(255,255,255,.45),transparent);
        transform:skewX(-18deg);
    }
    .um-submit:not(:disabled)::after{animation:umShine 2.8s ease-in-out infinite}
    @keyframes umShine{0%,55%{left:-45%}85%,100%{left:115%}}
    .um-submit.um-armed{animation:umArm .5s cubic-bezier(.34,1.56,.64,1)}
    @keyframes umArm{0%{transform:scale(.96)}60%{transform:scale(1.04)}100%{transform:scale(1)}}

    @media (prefers-reduced-motion: reduce){
        .um-modal *,.modal.show .um-doc{animation:none !important;transition:none !important;opacity:1}
    }
</style>

<script>
    /* Interaksi kartu unggah: pilih file, drag & drop, status terpilih,
       penghitung dokumen, dan pengaktifan tombol simpan. */
    function umFormatSize(bytes) {
        if (!bytes && bytes !== 0) return '';
        if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(0) + ' KB';
        return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
    }

    function umRefresh(modalId) {
        var modal = document.getElementById(modalId);
        if (!modal) return;

        var total = 0, picked = 0, wajibOk = true;
        modal.querySelectorAll('.um-doc input[type=file]').forEach(function (input) {
            total++;
            var card = input.closest('.um-doc');
            var ada = input.files && input.files.length > 0;
            if (ada) {
                picked++;
                card.classList.add('um-picked');
                var f = input.files[0];
                card.querySelector('.um-file-name').textContent = f.name;
                card.querySelector('.um-file-size').textContent = '· ' + umFormatSize(f.size);
                card.querySelector('.um-doc-action').textContent = 'Ganti File';
            } else {
                card.classList.remove('um-picked');
                card.querySelector('.um-doc-action').textContent = 'Pilih PDF';
                if (input.classList.contains('um-wajib')) wajibOk = false;
            }
        });

        var counter = modal.querySelector('.um-counter');
        if (counter) {
            counter.querySelector('span').textContent = picked + ' dari ' + total + ' dokumen dipilih';
            counter.classList.toggle('um-has', picked > 0);
        }

        var declare = modal.querySelector('.um-declare input');
        var declareBox = modal.querySelector('.um-declare');
        var setuju = declare ? declare.checked : false;
        if (declareBox) declareBox.classList.toggle('um-agreed', setuju);

        var btn = modal.querySelector('.um-submit');
        if (btn) {
            var siap = setuju && wajibOk && picked > 0;
            if (siap && btn.disabled) {
                btn.disabled = false;
                btn.classList.add('um-armed');
                setTimeout(function () { btn.classList.remove('um-armed'); }, 550);
            } else if (!siap) {
                btn.disabled = true;
            }
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.um-modal').forEach(function (modal) {
            var modalId = modal.id;

            // Pindahkan modal ke <body>: bila tetap bersarang di dalam kartu
            // yang memiliki animation/transform, backdrop Bootstrap menutupi
            // modal (stacking context) sehingga modal tidak dapat diklik.
            if (modal.parentElement !== document.body) {
                document.body.appendChild(modal);
            }

            modal.querySelectorAll('.um-doc:not(.um-locked):not(.um-done)').forEach(function (card) {
                var input = card.querySelector('input[type=file]');
                if (!input) return;

                input.addEventListener('change', function () { umRefresh(modalId); });

                ['dragenter', 'dragover'].forEach(function (ev) {
                    card.addEventListener(ev, function (e) { e.preventDefault(); card.classList.add('um-drag'); });
                });
                ['dragleave', 'drop'].forEach(function (ev) {
                    card.addEventListener(ev, function (e) { e.preventDefault(); card.classList.remove('um-drag'); });
                });
                card.addEventListener('drop', function (e) {
                    if (e.dataTransfer.files.length > 0 && e.dataTransfer.files[0].type === 'application/pdf') {
                        input.files = e.dataTransfer.files;
                        umRefresh(modalId);
                    }
                });
            });

            var declare = modal.querySelector('.um-declare input');
            if (declare) declare.addEventListener('change', function () { umRefresh(modalId); });

            umRefresh(modalId);
        });
    });
</script>
