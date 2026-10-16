{{-- Video panduan "Tambah Mitra Jasa" — tampil di form tambah mitra (Super Admin Jasa). --}}
@php
    $panduanVideo = asset('videos/panduan/tambah-mitra-jasa.webm');
    $panduanPoster = asset('videos/panduan/tambah-mitra-jasa-poster.jpg');
    $panduanBab = [
        ['detik' => 0,   'judul' => 'Pengantar',                  'ket' => 'Gambaran 4 langkah utama'],
        ['detik' => 9,   'judul' => 'Buka menu Mitra Jasa',       'ket' => 'Kelola Jasa → Mitra Jasa'],
        ['detik' => 20,  'judul' => 'Tombol Tambah mitra',        'ket' => 'Dari halaman daftar mitra'],
        ['detik' => 31,  'judul' => 'Isi formulir mitra',         'ket' => 'Identitas, kontak, penanggung jawab'],
        ['detik' => 89,  'judul' => 'Simpan & halaman detail',    'ket' => 'Cek ringkasan data mitra'],
        ['detik' => 103, 'judul' => 'Buat akun portal mitra',     'ket' => 'Email login & password awal'],
        ['detik' => 127, 'judul' => 'Input kontrak dasar',        'ket' => 'Membuka layanan, konsesi, PJP2U'],
        ['detik' => 153, 'judul' => 'Ringkasan',                  'ket' => 'Checklist akhir'],
    ];
@endphp

@push('css')
<style>
    .mj-guide {
        position: relative;
        display: grid;
        grid-template-columns: minmax(0, 300px) minmax(0, 1fr);
        gap: 22px;
        align-items: center;
        padding: 18px;
        border: 1px solid rgba(14, 165, 233, .22);
        border-radius: 20px;
        background:
            radial-gradient(circle at 100% 0, rgba(125, 211, 252, .28), transparent 42%),
            linear-gradient(135deg, #f0f9ff 0%, #ffffff 60%);
        box-shadow: 0 14px 36px rgba(14, 116, 144, .10);
    }

    .mj-guide-thumb {
        position: relative;
        display: block;
        width: 100%;
        aspect-ratio: 16 / 10;
        border: 0;
        border-radius: 14px;
        overflow: hidden;
        padding: 0;
        background: #0f172a center / cover no-repeat;
        box-shadow: 0 10px 26px rgba(15, 23, 42, .25);
        cursor: pointer;
    }

    .mj-guide-thumb::after {
        content: "";
        position: absolute;
        inset: 0;
        background: linear-gradient(180deg, rgba(15, 23, 42, .05), rgba(15, 23, 42, .45));
        transition: background .2s;
    }

    .mj-guide-thumb:hover::after { background: linear-gradient(180deg, rgba(15, 23, 42, 0), rgba(15, 23, 42, .3)); }

    .mj-guide-play {
        position: absolute;
        z-index: 1;
        left: 50%;
        top: 50%;
        width: 62px;
        height: 62px;
        margin: -31px 0 0 -31px;
        display: grid;
        place-items: center;
        border-radius: 50%;
        background: #fff;
        color: #0284c7;
        font-size: 30px;
        box-shadow: 0 0 0 0 rgba(255, 255, 255, .7);
        animation: mjGuidePulse 2.2s infinite;
        transition: transform .2s;
    }

    .mj-guide-thumb:hover .mj-guide-play { transform: scale(1.08); }

    @keyframes mjGuidePulse {
        0% { box-shadow: 0 0 0 0 rgba(255, 255, 255, .65); }
        70% { box-shadow: 0 0 0 18px rgba(255, 255, 255, 0); }
        100% { box-shadow: 0 0 0 0 rgba(255, 255, 255, 0); }
    }

    .mj-guide-duration {
        position: absolute;
        z-index: 1;
        right: 10px;
        bottom: 10px;
        padding: 3px 9px;
        border-radius: 999px;
        background: rgba(15, 23, 42, .78);
        color: #fff;
        font-size: 12px;
        font-weight: 700;
    }

    .mj-guide-tag {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 11px;
        border-radius: 999px;
        background: #e0f2fe;
        color: #0369a1;
        font-size: 11px;
        font-weight: 800;
        letter-spacing: .05em;
        text-transform: uppercase;
    }

    .mj-guide h5 { margin: 10px 0 4px; font-weight: 800; color: #0f172a; }
    .mj-guide p { margin: 0 0 12px; color: #475569; }

    .mj-guide-steps {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: 14px;
        padding: 0;
        list-style: none;
        counter-reset: mjstep;
    }

    .mj-guide-steps li {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 6px 12px 6px 6px;
        border: 1px solid #e2e8f0;
        border-radius: 999px;
        background: #fff;
        color: #334155;
        font-size: 13px;
        font-weight: 600;
        counter-increment: mjstep;
    }

    .mj-guide-steps li::before {
        content: counter(mjstep);
        display: grid;
        place-items: center;
        width: 22px;
        height: 22px;
        border-radius: 50%;
        background: #0ea5e9;
        color: #fff;
        font-size: 12px;
        font-weight: 800;
    }

    .mj-guide-close {
        position: absolute;
        top: 10px;
        right: 12px;
    }

    .mj-guide-mini { display: none; }
    .mj-guide-collapsed .mj-guide { display: none; }
    .mj-guide-collapsed .mj-guide-mini { display: inline-flex; }

    /* Modal pemutar */
    .mj-player-modal .modal-content {
        border: 0;
        border-radius: 20px;
        overflow: hidden;
        background: #0f172a;
        color: #e2e8f0;
    }

    .mj-player-modal .modal-header {
        border-bottom: 1px solid rgba(148, 163, 184, .18);
        background: linear-gradient(120deg, #12355c 0%, #174f86 60%, #1d65a6 100%);
    }

    .mj-player-modal .modal-title { color: #fff !important; }

    .mj-player-modal video {
        display: block;
        width: 100%;
        height: auto;
        max-height: 72vh;
        background: #000;
        border-radius: 12px;
    }

    .mj-chapters {
        max-height: 72vh;
        overflow-y: auto;
        margin: 0;
        padding: 0;
        list-style: none;
    }

    .mj-chapter {
        display: flex;
        gap: 12px;
        width: 100%;
        padding: 10px 12px;
        margin-bottom: 6px;
        border: 1px solid transparent;
        border-radius: 12px;
        background: rgba(148, 163, 184, .08);
        color: inherit;
        text-align: left;
        transition: background .15s, border-color .15s;
    }

    .mj-chapter:hover { background: rgba(148, 163, 184, .16); }

    .mj-chapter.is-active {
        border-color: rgba(56, 189, 248, .6);
        background: rgba(14, 165, 233, .18);
    }

    .mj-chapter-time {
        flex: none;
        min-width: 44px;
        padding: 2px 6px;
        border-radius: 6px;
        background: rgba(15, 23, 42, .6);
        color: #7dd3fc;
        font: 700 12px/1.6 ui-monospace, SFMono-Regular, Menlo, monospace;
        text-align: center;
        height: fit-content;
    }

    .mj-chapter strong { display: block; color: #f8fafc; font-size: 14px; }
    .mj-chapter small { color: #94a3b8; }

    @media (max-width: 767.98px) {
        .mj-guide { grid-template-columns: 1fr; padding: 42px 14px 14px; }
        .mj-guide-thumb { max-width: 420px; }
        .mj-chapters { max-height: none; }
    }
</style>
@endpush

<div id="mjGuideWrap" class="mb-4">
    <section class="mj-guide" aria-label="Video panduan menambahkan mitra jasa">
        <button type="button" class="mj-guide-thumb" style="background-image:url('{{ $panduanPoster }}')"
                data-bs-toggle="modal" data-bs-target="#mjGuideModal" aria-label="Putar video panduan">
            <span class="mj-guide-play"><i class="bi bi-play-fill"></i></span>
            <span class="mj-guide-duration"><i class="bi bi-clock me-1"></i>2:42</span>
        </button>

        <div>
            <span class="mj-guide-tag"><i class="bi bi-camera-reels"></i> Video Panduan</span>
            <h5>Baru pertama kali menambahkan mitra? Tonton panduan singkat ini.</h5>
            <p class="small">Lihat langsung cara mengisi formulir di bawah, membuat akun portal mitra, sampai menginput kontrak agar layanan mitra terbuka.</p>
            <ol class="mj-guide-steps">
                <li>Isi identitas &amp; kontak</li>
                <li>Simpan mitra</li>
                <li>Buat akun portal</li>
                <li>Input kontrak</li>
            </ol>
            <div class="d-flex flex-wrap gap-2">
                <button type="button" class="btn btn-primary fw-bold px-3" data-bs-toggle="modal" data-bs-target="#mjGuideModal">
                    <i class="bi bi-play-circle me-1"></i>Putar Panduan
                </button>
                <button type="button" class="btn btn-light border fw-semibold text-secondary" data-mj-guide-toggle>
                    Sembunyikan
                </button>
            </div>
        </div>

        <button type="button" class="btn-close mj-guide-close" data-mj-guide-toggle aria-label="Sembunyikan panduan"></button>
    </section>

    <button type="button" class="mj-guide-mini btn btn-outline-primary btn-sm fw-bold rounded-pill align-items-center gap-1"
            data-bs-toggle="modal" data-bs-target="#mjGuideModal">
        <i class="bi bi-play-circle-fill"></i> Tonton video panduan tambah mitra (2:42)
    </button>
</div>

<div class="modal fade mj-player-modal" id="mjGuideModal" tabindex="-1" aria-labelledby="mjGuideModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header text-white">
                <div>
                    <div class="small fw-bold text-white-50 text-uppercase">Panduan Super Admin Jasa</div>
                    <h5 class="modal-title fw-bold" id="mjGuideModalLabel">Cara Menambahkan Mitra Jasa</h5>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body p-3 p-lg-4">
                <div class="row g-3">
                    <div class="col-lg-8">
                        <video id="mjGuideVideo" controls preload="metadata" playsinline poster="{{ $panduanPoster }}">
                            <source src="{{ $panduanVideo }}" type="video/webm">
                            Browser Anda tidak mendukung pemutaran video ini.
                        </video>
                        <div id="mjGuideStatus" class="small text-info mt-2 d-none">
                            <span class="spinner-border spinner-border-sm me-1" role="status"></span>Menyiapkan video agar bisa melompat antarbab…
                        </div>
                        <div class="small text-secondary mt-2">
                            <i class="bi bi-info-circle me-1"></i>Data pada video adalah contoh. Password awal disamarkan demi keamanan.
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="small fw-bold text-uppercase text-white-50 mb-2">Daftar Bab</div>
                        <ul class="mj-chapters">
                            @foreach($panduanBab as $bab)
                                <li>
                                    <button type="button" class="mj-chapter {{ $loop->first ? 'is-active' : '' }}" data-mj-seek="{{ $bab['detik'] }}">
                                        <span class="mj-chapter-time">{{ sprintf('%d:%02d', intdiv($bab['detik'], 60), $bab['detik'] % 60) }}</span>
                                        <span>
                                            <strong>{{ $bab['judul'] }}</strong>
                                            <small>{{ $bab['ket'] }}</small>
                                        </span>
                                    </button>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('script')
<script>
(function () {
    const wrap = document.getElementById('mjGuideWrap');
    const modal = document.getElementById('mjGuideModal');
    const video = document.getElementById('mjGuideVideo');
    if (!wrap || !modal || !video) return;

    const storageKey = 'sikeren.panduan.tambahMitra.hidden';
    try {
        if (localStorage.getItem(storageKey) === '1') wrap.classList.add('mj-guide-collapsed');
    } catch (e) {}

    wrap.querySelectorAll('[data-mj-guide-toggle]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            wrap.classList.add('mj-guide-collapsed');
            try { localStorage.setItem(storageKey, '1'); } catch (e) {}
        });
    });

    const chapters = Array.from(modal.querySelectorAll('[data-mj-seek]'));
    const status = document.getElementById('mjGuideStatus');
    let blobLoading = null;

    // Server tanpa dukungan HTTP Range (mis. `php artisan serve`) membuat video
    // tidak bisa di-seek: browser kembali ke 0. Fallback: unduh utuh sekali
    // sebagai Blob agar lompat bab & scrubbing tetap berfungsi.
    function isSeekable(target) {
        for (let i = 0; i < video.seekable.length; i++) {
            if (target >= video.seekable.start(i) && target <= video.seekable.end(i)) return true;
        }
        return false;
    }

    function loadAsBlob() {
        if (blobLoading) return blobLoading;
        if (status) status.classList.remove('d-none');
        const src = video.currentSrc || video.querySelector('source').src;
        blobLoading = fetch(src, { credentials: 'same-origin' })
            .then(function (res) {
                if (!res.ok) throw new Error('HTTP ' + res.status);
                return res.blob();
            })
            .then(function (blob) {
                const resumeAt = video.currentTime;
                const wasPlaying = !video.paused;
                return new Promise(function (resolve) {
                    video.addEventListener('loadedmetadata', function () {
                        video.currentTime = resumeAt;
                        if (wasPlaying) video.play().catch(function () {});
                        resolve();
                    }, { once: true });
                    video.src = URL.createObjectURL(blob);
                    video.load();
                });
            })
            .finally(function () {
                if (status) status.classList.add('d-none');
            });
        return blobLoading;
    }

    function seekTo(target) {
        const go = function () {
            video.currentTime = target;
            video.play().catch(function () {});
        };
        if (isSeekable(target)) return go();
        loadAsBlob().then(go).catch(go);
    }

    chapters.forEach(function (btn) {
        btn.addEventListener('click', function () { seekTo(Number(btn.dataset.mjSeek)); });
    });

    // Saat modal dibuka, siapkan fallback juga untuk scrubbing manual lewat kontrol bawaan.
    function prepareSeeking() {
        if (video.src.startsWith('blob:')) return;
        if (video.readyState < 1) {
            video.addEventListener('loadedmetadata', prepareSeeking, { once: true });
            return;
        }
        if (isFinite(video.duration) && !isSeekable(video.duration - 1)) {
            loadAsBlob().catch(function () {});
        }
    }

    video.addEventListener('timeupdate', function () {
        let active = chapters[0];
        chapters.forEach(function (btn) {
            if (video.currentTime >= Number(btn.dataset.mjSeek)) active = btn;
        });
        chapters.forEach(function (btn) { btn.classList.toggle('is-active', btn === active); });
    });

    modal.addEventListener('shown.bs.modal', function () {
        video.play().catch(function () {});
        prepareSeeking();
    });
    modal.addEventListener('hidden.bs.modal', function () { video.pause(); });
})();
</script>
@endpush
