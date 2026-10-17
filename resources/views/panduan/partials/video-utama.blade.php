{{-- Video panduan unggulan suatu peran (PanduanRegistry 'video_utama'). Variabel: $video --}}
@php
    $vSrc = asset($video['src']);
    $vPoster = ! empty($video['poster']) ? asset($video['poster']) : null;
@endphp

@push('css')
<style>
    .pd-video { border: 1px solid #e2e8f0; border-radius: 18px; background: #fff; box-shadow: 0 10px 30px rgba(15, 23, 42, .06); padding: 1.1rem; margin-bottom: 1.5rem; }
    .pd-video-head { display: flex; flex-wrap: wrap; align-items: flex-start; justify-content: space-between; gap: .75rem; margin-bottom: .9rem; }
    .pd-video-tag { display: inline-flex; align-items: center; gap: .3rem; padding: .2rem .65rem; border-radius: 999px; background: var(--pd-accent-soft, #eef2ff); color: var(--pd-accent, #4f46e5); font-size: .72rem; font-weight: 800; letter-spacing: .05em; text-transform: uppercase; }
    .pd-video-tag i { font-size: 16px; }
    .pd-video h3 { font-size: 1.1rem; font-weight: 800; margin: .45rem 0 .2rem; color: #0f172a; }
    .pd-video-desc { margin: 0; color: #475569; font-size: .9rem; }
    .pd-video-dur { display: inline-flex; align-items: center; gap: .3rem; padding: .3rem .7rem; border-radius: 999px; background: #0f172a; color: #fff; font-size: .78rem; font-weight: 700; white-space: nowrap; }
    .pd-video-dur i { font-size: 16px; }
    .pd-video-grid { display: grid; grid-template-columns: minmax(0, 1fr) 300px; gap: 1rem; }
    .pd-video-player video { display: block; width: 100%; aspect-ratio: 16 / 10; border-radius: 14px; background: #0f172a; }
    .pd-video-status { margin-top: .5rem; font-size: .8rem; color: #0284c7; }
    .pd-video-chapters { list-style: none; margin: 0; padding: 0; max-height: 440px; overflow-y: auto; }
    .pd-video-chapters-title { font-size: .72rem; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; color: #64748b; margin-bottom: .45rem; }
    .pd-chapter { display: flex; align-items: center; gap: .6rem; width: 100%; margin-bottom: .35rem; padding: .5rem .6rem; border: 1px solid transparent; border-radius: 10px; background: #f8fafc; color: #0f172a; text-align: left; font-size: .86rem; font-weight: 600; transition: background .15s, border-color .15s; }
    .pd-chapter:hover { background: #f1f5f9; }
    .pd-chapter.is-active { border-color: var(--pd-accent, #4f46e5); background: var(--pd-accent-soft, #eef2ff); }
    .pd-chapter-time { flex: none; min-width: 42px; padding: .1rem .35rem; border-radius: 6px; background: #fff; border: 1px solid #e2e8f0; color: var(--pd-accent, #4f46e5); font: 700 .75rem/1.5 ui-monospace, SFMono-Regular, Menlo, monospace; text-align: center; }
    @media (max-width: 991.98px) {
        .pd-video-grid { grid-template-columns: 1fr; }
        .pd-video-chapters { max-height: none; display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: .35rem; }
        .pd-chapter { margin-bottom: 0; }
    }
</style>
@endpush

<section class="pd-video" id="video-panduan" aria-label="Video panduan">
    <div class="pd-video-head">
        <div>
            <span class="pd-video-tag"><i class="material-icons-outlined">smart_display</i> Video Panduan</span>
            <h3>{{ $video['judul'] }}</h3>
            @if(! empty($video['deskripsi']))
                <p class="pd-video-desc">{{ $video['deskripsi'] }}</p>
            @endif
        </div>
        @if(! empty($video['durasi']))
            <span class="pd-video-dur"><i class="material-icons-outlined">schedule</i>{{ $video['durasi'] }}</span>
        @endif
    </div>

    <div class="pd-video-grid">
        <div class="pd-video-player">
            <video id="pdVideo" controls preload="metadata" playsinline @if($vPoster) poster="{{ $vPoster }}" @endif>
                <source src="{{ $vSrc }}" type="video/webm">
                Browser Anda tidak mendukung pemutaran video ini.
            </video>
            <div id="pdVideoStatus" class="pd-video-status d-none">
                <span class="spinner-border spinner-border-sm me-1" role="status"></span>Menyiapkan video agar bisa melompat antarbab…
            </div>
        </div>
        @if(! empty($video['bab']))
            <div>
                <div class="pd-video-chapters-title">Daftar bab</div>
                <ul class="pd-video-chapters">
                    @foreach($video['bab'] as [$detik, $judul])
                        <li>
                            <button type="button" class="pd-chapter {{ $loop->first ? 'is-active' : '' }}" data-pd-seek="{{ $detik }}">
                                <span class="pd-chapter-time">{{ sprintf('%d:%02d', intdiv($detik, 60), $detik % 60) }}</span>
                                <span>{{ $judul }}</span>
                            </button>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
</section>

@push('script')
<script>
(function () {
    const video = document.getElementById('pdVideo');
    if (!video) return;
    const chapters = Array.from(document.querySelectorAll('[data-pd-seek]'));
    const status = document.getElementById('pdVideoStatus');
    let blobLoading = null;

    // Server tanpa HTTP Range (mis. `php artisan serve`) membuat video tidak bisa di-seek;
    // fallback: unduh utuh sekali sebagai Blob agar lompat bab tetap berfungsi.
    function isSeekable(t) {
        for (let i = 0; i < video.seekable.length; i++) {
            if (t >= video.seekable.start(i) && t <= video.seekable.end(i)) return true;
        }
        return false;
    }
    function loadAsBlob() {
        if (blobLoading) return blobLoading;
        if (status) status.classList.remove('d-none');
        const src = video.currentSrc || video.querySelector('source').src;
        blobLoading = fetch(src, { credentials: 'same-origin' })
            .then(function (res) { if (!res.ok) throw new Error('HTTP ' + res.status); return res.blob(); })
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
            .finally(function () { if (status) status.classList.add('d-none'); });
        return blobLoading;
    }
    function seekTo(t) {
        const go = function () { video.currentTime = t; video.play().catch(function () {}); };
        if (isSeekable(t)) return go();
        loadAsBlob().then(go).catch(go);
    }

    chapters.forEach(function (btn) {
        btn.addEventListener('click', function () { seekTo(Number(btn.dataset.pdSeek)); });
    });
    video.addEventListener('play', function () {
        if (!video.src.startsWith('blob:') && isFinite(video.duration) && !isSeekable(video.duration - 1)) {
            loadAsBlob().catch(function () {});
        }
    }, { once: true });
    video.addEventListener('timeupdate', function () {
        let active = chapters[0];
        chapters.forEach(function (btn) { if (video.currentTime >= Number(btn.dataset.pdSeek)) active = btn; });
        chapters.forEach(function (btn) { btn.classList.toggle('is-active', btn === active); });
    });
})();
</script>
@endpush
