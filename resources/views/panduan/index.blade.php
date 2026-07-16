@extends('layouts.app')

@section('title', 'Pusat Panduan')

@push('css')
<style>
    .panduan-page { --pd-accent: #4f46e5; --pd-accent-soft: rgba(79,70,229,.12); }

    .pd-hero {
        color: #fff; border-radius: 1rem; padding: 1.5rem 1.75rem;
        display: flex; align-items: center; gap: 1.1rem; margin-bottom: 1.5rem;
        background: var(--pd-accent);
        background: linear-gradient(135deg, var(--pd-accent), color-mix(in srgb, var(--pd-accent), #000 35%));
    }
    .pd-hero .pd-hero-icon {
        width: 56px; height: 56px; flex-shrink: 0; border-radius: .9rem;
        background: rgba(255,255,255,.18); display: flex; align-items: center; justify-content: center;
    }
    .pd-hero .pd-hero-icon i { font-size: 32px; }
    /* Warna eksplisit + !important: tema (semi-dark/dark) menimpa warna heading
       global sehingga judul bisa gelap di atas banner gelap. */
    .pd-hero h1 { font-size: 1.5rem; font-weight: 700; margin: 0; color: #fff !important; }
    .pd-hero p { margin: .15rem 0 0; opacity: .9; font-size: .95rem; color: #fff !important; }
    .pd-hero .pd-hero-icon i { color: #fff; }

    .pd-switch { display: flex; flex-wrap: wrap; align-items: center; gap: .5rem; margin-bottom: 1.5rem; }
    .pd-switch .pd-switch-label { font-size: .85rem; color: #64748b; margin-right: .25rem; }
    .pd-pill {
        display: inline-flex; align-items: center; gap: .4rem; padding: .4rem .8rem;
        border: 1px solid #e2e8f0; border-radius: 2rem; background: #fff; color: #334155;
        font-size: .85rem; font-weight: 500; text-decoration: none; transition: all .15s;
    }
    .pd-pill i { font-size: 18px; }
    .pd-pill:hover { border-color: var(--pd-accent); color: var(--pd-accent); }
    .pd-pill.active { background: var(--pd-accent); border-color: var(--pd-accent); color: #fff; }
    .pd-pill .pd-you {
        font-size: .65rem; font-weight: 600; text-transform: uppercase; letter-spacing: .03em;
        background: var(--pd-accent-soft); color: var(--pd-accent); padding: .1rem .4rem; border-radius: 1rem;
    }
    .pd-pill.active .pd-you { background: rgba(255,255,255,.25); color: #fff; }

    .pd-card { background: #fff; border: 1px solid #e9edf3; border-radius: 1rem; padding: 1.25rem 1.4rem; margin-bottom: 1.5rem; }
    .pd-card-head { display: flex; align-items: flex-start; gap: 1rem; }
    .pd-card-head .pd-role-icon {
        width: 48px; height: 48px; flex-shrink: 0; border-radius: .8rem;
        background: var(--pd-accent-soft); color: var(--pd-accent);
        display: flex; align-items: center; justify-content: center;
    }
    .pd-card-head .pd-role-icon i { font-size: 26px; }
    .pd-card-head h2 { font-size: 1.2rem; font-weight: 700; margin: 0; color: #0f172a; }
    .pd-card-head p { margin: .3rem 0 0; color: #475569; font-size: .92rem; line-height: 1.6; }
    .pd-draft {
        display: inline-flex; align-items: center; gap: .3rem; margin-top: .6rem;
        font-size: .75rem; font-weight: 600; color: #92400e; background: #fef3c7;
        padding: .25rem .6rem; border-radius: 1rem;
    }
    .pd-draft i { font-size: 15px; }

    .pd-section-head { display: flex; align-items: center; justify-content: space-between; gap: 1rem; margin: 0 .15rem 1rem; }
    .pd-section-head h3 { font-size: 1.05rem; font-weight: 700; margin: 0; color: #0f172a; }
    .pd-tour-btn {
        display: inline-flex; align-items: center; gap: .4rem; border: 1px solid var(--pd-accent);
        background: var(--pd-accent-soft); color: var(--pd-accent); font-weight: 600; font-size: .85rem;
        padding: .45rem .9rem; border-radius: .6rem; transition: all .15s;
    }
    .pd-tour-btn:hover { background: var(--pd-accent); color: #fff; }
    .pd-tour-btn i { font-size: 18px; }
    .pd-head-actions { display: flex; align-items: center; gap: .5rem; flex-wrap: wrap; }
    a.pd-tour-btn { text-decoration: none; }

    .pd-steps { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: .85rem; margin-bottom: 1.75rem; }
    .pd-step { position: relative; background: #fff; border: 1px solid #e9edf3; border-radius: .9rem; padding: 1rem 1.1rem; }
    .pd-step-top { display: flex; align-items: center; gap: .6rem; margin-bottom: .6rem; }
    .pd-step-num {
        width: 26px; height: 26px; flex-shrink: 0; border-radius: 50%; background: var(--pd-accent); color: #fff;
        font-size: .8rem; font-weight: 700; display: flex; align-items: center; justify-content: center;
    }
    .pd-step-top .pd-step-glyph { color: var(--pd-accent); font-size: 22px; }
    .pd-step h4 { font-size: .95rem; font-weight: 700; margin: 0 0 .35rem; color: #0f172a; }
    .pd-step p { font-size: .82rem; color: #475569; line-height: 1.55; margin: 0 0 .6rem; }
    .pd-step-rincian { margin: 0 0 .6rem; padding-left: 1.05rem; }
    .pd-step-rincian li { font-size: .79rem; color: #475569; line-height: 1.5; margin-bottom: .2rem; }
    .pd-menu-badge {
        display: inline-flex; align-items: center; gap: .3rem; font-size: .72rem; font-weight: 600;
        color: #475569; background: #f1f5f9; padding: .2rem .55rem; border-radius: .5rem;
    }
    .pd-menu-badge i { font-size: 14px; }

    .pd-empty {
        text-align: center; color: #64748b; background: #f8fafc; border: 1px dashed #cbd5e1;
        border-radius: .9rem; padding: 2rem 1rem; margin-bottom: 1.75rem; font-size: .9rem;
    }
    .pd-empty-state { text-align: center; padding: 2.5rem 1.5rem; }
    .pd-empty-state i { font-size: 48px; color: var(--pd-accent); opacity: .7; }
    .pd-empty-state h5 { font-weight: 700; margin: .75rem 0 .35rem; color: #0f172a; }
    .pd-empty-state p { color: #64748b; max-width: 460px; margin: 0 auto; font-size: .9rem; }

    .pd-faq .accordion-button { font-weight: 600; font-size: .92rem; }
    .pd-faq .accordion-button:not(.collapsed) { background: var(--pd-accent-soft); color: var(--pd-accent); box-shadow: none; }
    .pd-faq .accordion-button:focus { box-shadow: none; border-color: var(--pd-accent); }
    .pd-faq .accordion-body { font-size: .88rem; color: #475569; line-height: 1.65; }

    .pd-foot { text-align: center; color: #94a3b8; font-size: .82rem; margin-top: 1.5rem; }

    .pd-menus { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 1rem; margin-bottom: 1.75rem; }
    .pd-menu-group { background: #fff; border: 1px solid #e9edf3; border-radius: .9rem; padding: 1rem 1.1rem; }
    .pd-menu-group-title { font-size: .72rem; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; color: var(--pd-accent); margin-bottom: .5rem; }
    .pd-menu-row { display: flex; align-items: flex-start; gap: .6rem; padding: .55rem 0; border-top: 1px solid #f1f5f9; }
    .pd-menu-row:first-of-type { border-top: none; padding-top: .15rem; }
    .pd-menu-row > i { font-size: 20px; color: #64748b; margin-top: 1px; flex-shrink: 0; }
    .pd-menu-name { font-size: .88rem; font-weight: 600; color: #0f172a; }
    .pd-menu-guna { font-size: .8rem; color: #64748b; line-height: 1.45; }

    .pd-step-tips { display: flex; align-items: flex-start; gap: .4rem; margin-top: .7rem; background: #fffbeb; border: 1px solid #fde68a; border-radius: .6rem; padding: .5rem .6rem; font-size: .78rem; color: #92400e; line-height: 1.45; }
    .pd-step-tips > i { font-size: 16px; flex-shrink: 0; margin-top: 1px; }
    .pd-step-video { display: inline-flex; align-items: center; gap: .35rem; margin-top: .7rem; font-size: .8rem; font-weight: 600; color: var(--pd-accent, #4f46e5); text-decoration: none; }
    .pd-step-video > i { font-size: 18px; }
    .pd-step-video:hover { text-decoration: underline; }

    /* ── Tur interaktif (vanilla, tanpa dependensi) ── */
    .pd-tour-active {
        position: relative; z-index: 1056; border-radius: .9rem;
        box-shadow: 0 0 0 4px var(--pd-accent), 0 0 0 9999px rgba(15,23,42,.55) !important;
        transition: box-shadow .2s ease;
    }
    .pd-tour-tip {
        position: absolute; z-index: 1060; width: 320px; max-width: calc(100vw - 24px);
        background: #fff; border-radius: .85rem; box-shadow: 0 18px 50px rgba(2,6,23,.3); padding: 1rem 1.1rem;
    }
    .pd-tip-count { font-size: .7rem; letter-spacing: .04em; text-transform: uppercase; color: var(--pd-accent, #4f46e5); font-weight: 700; }
    .pd-tip-title { font-weight: 700; font-size: 1rem; margin: .15rem 0 .35rem; color: #0f172a; }
    .pd-tip-body { font-size: .85rem; color: #475569; line-height: 1.55; }
    .pd-tip-actions { display: flex; align-items: center; justify-content: space-between; margin-top: .9rem; }
    .btn-pd-next { background: var(--pd-accent, #4f46e5); color: #fff; border: none; }
    .btn-pd-next:hover { filter: brightness(.93); color: #fff; }
</style>
@endpush

@section('content')
    <x-page-title title="Bantuan" subtitle="Pusat Panduan" />

    <div class="panduan-page"
         @if($guide) style="--pd-accent: rgb({{ $guide['warna'] }}); --pd-accent-soft: rgba({{ $guide['warna'] }}, .12);" @endif>

        {{-- Hero --}}
        <div class="pd-hero">
            <div class="pd-hero-icon"><i class="material-icons-outlined">menu_book</i></div>
            <div class="flex-grow-1">
                <h1>Pusat Panduan</h1>
                <p>Langkah-langkah memakai aplikasi, menyesuaikan peran Anda. Tidak perlu membaca semuanya — cukup alur kerja peran Anda.</p>
            </div>
        </div>

        {{-- Pemilih peran --}}
        @if(count($browsable) > 1 || $isSuperAdmin)
            <div class="pd-switch">
                <span class="pd-switch-label">Panduan untuk peran:</span>
                @foreach($browsable as $r)
                    <a href="{{ route('panduan.index', ['role' => $r]) }}"
                       class="pd-pill {{ $r === $activeRole ? 'active' : '' }}">
                        <i class="material-icons-outlined">{{ $registry[$r]['ikon'] }}</i>
                        {{ $registry[$r]['label'] }}
                        @if(in_array($r, $userRoles, true))
                            <span class="pd-you">peran Anda</span>
                        @endif
                    </a>
                @endforeach
            </div>
        @endif

        @if($guide)
            {{-- Kartu ringkasan peran --}}
            <div class="pd-card">
                <div class="pd-card-head">
                    <div class="pd-role-icon"><i class="material-icons-outlined">{{ $guide['ikon'] }}</i></div>
                    <div>
                        <h2>{{ $guide['label'] }}</h2>
                        <p>{{ $guide['ringkasan'] }}</p>
                        @if(empty($guide['alur']))
                            <span class="pd-draft"><i class="material-icons-outlined">construction</i> Langkah terperinci sedang disiapkan</span>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Alur kerja --}}
            @if(!empty($guide['alur']))
                <div class="pd-section-head">
                    <h3>Alur kerja saya</h3>
                    <div class="pd-head-actions">
                        <a href="{{ route('panduan.sop.download', ['slug' => \App\Support\Panduan\PanduanRegistry::slugFor($activeRole)]) }}" class="pd-tour-btn">
                            <i class="material-icons-outlined">download</i> Unduh SOP (PDF)
                        </a>
                        <button type="button" id="pdTourBtn" class="pd-tour-btn">
                            <i class="material-icons-outlined">play_circle</i> Mulai tur
                        </button>
                    </div>
                </div>
                <div class="pd-steps">
                    @foreach($guide['alur'] as $i => $s)
                        <div class="pd-step"
                             data-tour-step="{{ $i }}"
                             data-tour-title="{{ $s['judul'] }}"
                             data-tour-body="{{ $s['detail'] }}">
                            <div class="pd-step-top">
                                <span class="pd-step-num">{{ $i + 1 }}</span>
                                <i class="material-icons-outlined pd-step-glyph">{{ $s['ikon'] }}</i>
                            </div>
                            <h4>{{ $s['judul'] }}</h4>
                            <p>{{ $s['detail'] }}</p>
                            @if(!empty($s['rincian']))
                                <ul class="pd-step-rincian">
                                    @foreach($s['rincian'] as $r)
                                        <li>{{ $r }}</li>
                                    @endforeach
                                </ul>
                            @endif
                            @if(!empty($s['menu']))
                                <span class="pd-menu-badge"><i class="material-icons-outlined">arrow_right</i>{{ $s['menu'] }}</span>
                            @endif
                            @if(!empty($s['tips']))
                                <div class="pd-step-tips"><i class="material-icons-outlined">lightbulb</i><span>{{ $s['tips'] }}</span></div>
                            @endif
                            @if(!empty($s['video']))
                                <a href="{{ $s['video'] }}" target="_blank" rel="noopener" class="pd-step-video"><i class="material-icons-outlined">play_circle</i> Tonton video</a>
                            @endif
                        </div>
                    @endforeach
                </div>
            @else
                <div class="pd-empty">
                    Langkah-langkah untuk peran ini sedang disiapkan. Panduan PPK sudah tersedia sebagai contoh.
                </div>
            @endif

            {{-- Fungsi tiap menu yang dimiliki peran --}}
            @if(!empty($guide['menus']))
                <div class="pd-section-head"><h3>Fungsi menu Anda</h3></div>
                <div class="pd-menus">
                    @foreach($guide['menus'] as $grp)
                        <div class="pd-menu-group">
                            <div class="pd-menu-group-title">{{ $grp['grup'] }}</div>
                            @foreach($grp['items'] as $m)
                                <div class="pd-menu-row">
                                    <i class="material-icons-outlined">{{ $m['ikon'] }}</i>
                                    <div>
                                        <div class="pd-menu-name">{{ $m['nama'] }}</div>
                                        <div class="pd-menu-guna">{{ $m['guna'] }}</div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- FAQ --}}
            @if(!empty($guide['faq']))
                <div class="pd-section-head"><h3>Pertanyaan umum</h3></div>
                <div class="accordion pd-faq" id="pdFaq">
                    @foreach($guide['faq'] as $i => $f)
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button"
                                        data-bs-toggle="collapse" data-bs-target="#pdFaq{{ $i }}">
                                    {{ $f['t'] }}
                                </button>
                            </h2>
                            <div id="pdFaq{{ $i }}" class="accordion-collapse collapse" data-bs-parent="#pdFaq">
                                <div class="accordion-body">{{ $f['j'] }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        @else
            {{-- Tidak ada panduan untuk peran user --}}
            <div class="pd-card pd-empty-state">
                <i class="material-icons-outlined">menu_book</i>
                <h5>Panduan untuk peran Anda sedang disiapkan</h5>
                <p>Tim sedang menyusun panduan langkah-demi-langkah untuk peran Anda. Sementara itu, silakan hubungi Super Admin atau koordinator keuangan bila membutuhkan bantuan.</p>
            </div>
        @endif

        <p class="pd-foot">Butuh bantuan lebih lanjut? Hubungi Super Admin atau administrator keuangan satker.</p>
    </div>
@endsection

@push('script')
<script>
    // ── Tur interaktif Pusat Panduan: menyorot kartu alur kerja satu per satu ──
    document.addEventListener('DOMContentLoaded', function () {
        const btn = document.getElementById('pdTourBtn');
        if (!btn) return;

        const steps = Array.prototype.slice.call(document.querySelectorAll('[data-tour-step]'));
        if (!steps.length) return;

        let idx = 0, tip = null;

        function clearActive() { steps.forEach(function (s) { s.classList.remove('pd-tour-active'); }); }
        function removeTip() { if (tip) { tip.remove(); tip = null; } }

        function end() {
            clearActive();
            removeTip();
            document.removeEventListener('keydown', onKey);
            window.removeEventListener('resize', reposition);
            window.removeEventListener('scroll', reposition, true);
        }

        function reposition() {
            if (!tip) return;
            const r = steps[idx].getBoundingClientRect();
            const tipH = tip.offsetHeight, tipW = tip.offsetWidth;
            let top = window.scrollY + r.bottom + 12;
            if (r.bottom + tipH + 20 > window.innerHeight && r.top - tipH - 12 > 0) {
                top = window.scrollY + r.top - tipH - 12;
            }
            let left = window.scrollX + r.left + (r.width / 2) - (tipW / 2);
            const min = window.scrollX + 12;
            const max = window.scrollX + window.innerWidth - tipW - 12;
            tip.style.top = top + 'px';
            tip.style.left = Math.max(min, Math.min(left, max)) + 'px';
        }

        function render() {
            const el = steps[idx];
            clearActive();
            el.classList.add('pd-tour-active');
            el.scrollIntoView({ behavior: 'smooth', block: 'center' });

            if (!tip) {
                tip = document.createElement('div');
                tip.className = 'pd-tour-tip';
                // Tooltip di-append ke <body> (di luar .panduan-page) supaya posisi absolut
                // mengikuti koordinat dokumen — tapi itu memutus pewarisan variabel --pd-accent.
                // Maka salin aksen peran ke elemen tooltip agar tombol/teksnya tetap berwarna.
                const page = document.querySelector('.panduan-page');
                if (page) {
                    const cs = getComputedStyle(page);
                    tip.style.setProperty('--pd-accent', (cs.getPropertyValue('--pd-accent') || '').trim() || '#4f46e5');
                    tip.style.setProperty('--pd-accent-soft', (cs.getPropertyValue('--pd-accent-soft') || '').trim() || 'rgba(79,70,229,.12)');
                }
                document.body.appendChild(tip);
            }

            const last = idx === steps.length - 1;
            const title = el.getAttribute('data-tour-title') || '';
            const body = el.getAttribute('data-tour-body') || '';

            tip.innerHTML =
                '<div class="pd-tip-count">Langkah ' + (idx + 1) + ' dari ' + steps.length + '</div>' +
                '<div class="pd-tip-title"></div>' +
                '<div class="pd-tip-body"></div>' +
                '<div class="pd-tip-actions">' +
                    '<button type="button" class="btn btn-sm btn-link text-muted px-0" data-pd="end">Tutup</button>' +
                    '<div class="d-flex gap-2">' +
                        (idx > 0 ? '<button type="button" class="btn btn-sm btn-outline-secondary" data-pd="prev">Sebelumnya</button>' : '') +
                        '<button type="button" class="btn btn-sm btn-pd-next" data-pd="next">' + (last ? 'Selesai' : 'Berikutnya') + '</button>' +
                    '</div>' +
                '</div>';
            // Isi teks via textContent agar aman dari karakter khusus.
            tip.querySelector('.pd-tip-title').textContent = title;
            tip.querySelector('.pd-tip-body').textContent = body;

            setTimeout(reposition, 220);
        }

        function next() { if (idx < steps.length - 1) { idx++; render(); } else end(); }
        function prev() { if (idx > 0) { idx--; render(); } }

        function onKey(e) {
            if (e.key === 'Escape') end();
            else if (e.key === 'ArrowRight') next();
            else if (e.key === 'ArrowLeft') prev();
        }

        btn.addEventListener('click', function () {
            idx = 0;
            document.addEventListener('keydown', onKey);
            window.addEventListener('resize', reposition);
            window.addEventListener('scroll', reposition, true);
            render();
        });

        document.addEventListener('click', function (e) {
            const b = e.target.closest('[data-pd]');
            if (!b || !tip) return;
            const a = b.getAttribute('data-pd');
            if (a === 'end') end();
            else if (a === 'next') next();
            else if (a === 'prev') prev();
        });
    });
</script>
@endpush
