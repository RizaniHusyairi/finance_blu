{{--
    Konten dinamis halaman Proses Tagihan. Dirender dalam dua mode:
    1. Full page — di-include show.blade.php dalam <div id="ptShowContent">.
    2. Fragment  — dikembalikan controller saat ?partial=1 (refresh tanpa
       reload oleh form async), lalu di-swap ke #ptShowContent oleh JS.
    Jangan menaruh CSS/script halaman di sini — tempatnya di show.blade.php,
    _page_scripts.blade.php, dan _async_forms.blade.php.
--}}
@php
    $pihak = $tagihan->detailKontrak?->kontrakTermin?->kontrak?->vendor?->nama_pihak
        ?? $tagihan->pihak?->nama_pihak
        ?? $tagihan->nama_supplier
        ?? '-';

    $selesai = $tagihan->status === 'SELESAI';

    // tone chip status hero
    $statusTone = match (true) {
        $selesai => 'tone-success',
        str_starts_with($tagihan->status, 'DITOLAK') => 'tone-danger',
        str_starts_with($tagihan->status, 'REVISI') => 'tone-warning',
        default => '',
    };

    // ------- Pipeline stages -------
    $pajakBeres = $state['potonganPajak']->isEmpty() || $state['pajakSettled'];
    $stages = [
        ['label' => 'Verifikasi', 'sub' => '6 Verifikator',      'icon' => 'bi-patch-check-fill',   'done' => $state['tagihanApproved'], 'anchor' => 'sec-ringkasan'],
        ['label' => 'COA',        'sub' => 'Pembebanan',         'icon' => 'bi-calculator-fill',    'done' => $state['coaDone'],         'anchor' => 'sec-coa'],
        ['label' => 'KPA',        'sub' => 'Persetujuan',        'icon' => 'bi-shield-fill-check',  'done' => $state['kpaDone'],         'anchor' => 'sec-kpa'],
        // Rantai lama (dibuat sebelum aturan pajak) dianggap melewati tahap ini.
        ...($state['pajakKontrak'] ?? false ? [
            ['label' => 'Pajak',  'sub' => 'Tipe & Faktur',      'icon' => 'bi-receipt-cutoff',     'done' => $state['pajakKontrakDone'] || (bool) $state['spp'], 'anchor' => 'sec-pajak-kontrak'],
        ] : []),
        ['label' => 'Dokumen',    'sub' => 'SPP · SPM · NPI',    'icon' => 'bi-layers-fill',        'done' => $state['dokumenSiapBayar'],'anchor' => 'sec-dokumen'],
        ['label' => 'Transfer',   'sub' => 'Bukti Bayar',        'icon' => 'bi-bank2',              'done' => (bool) $state['buktiTransfer'], 'anchor' => 'sec-penyelesaian'],
        ['label' => 'SP2D',       'sub' => 'Penerbitan',         'icon' => 'bi-award-fill',         'done' => $state['sp2dTerbit'],      'anchor' => 'sec-penyelesaian'],
        ['label' => 'Pembukuan',  'sub' => 'Pajak & BKU',        'icon' => 'bi-journal-check',      'done' => $state['bkuPosted'] && $pajakBeres, 'anchor' => 'sec-penyelesaian'],
    ];
    $currentIdx = count($stages);
    foreach ($stages as $i => $s) {
        if (! $s['done']) { $currentIdx = $i; break; }
    }
@endphp

{{-- Confetti saat tagihan selesai --}}
@if($selesai)
    <div class="pt-confetti" id="ptConfetti" data-tagihan="{{ $tagihan->id }}"></div>
@endif

<!-- Back -->
<div class="mb-3 reveal">
    <a href="{{ route('proses-tagihan.index') }}" class="btn btn-sm btn-light rounded-pill border shadow-sm fw-semibold px-3">
        <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar
    </a>
</div>

<!-- ============ HERO ============ -->
<div class="pt-hero">
    <div class="grid-lines"></div>
    <div class="pt-hero-content">
        <div>
            <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                <span class="pt-chip"><i class="bi bi-tag-fill"></i> {{ $tagihan->tipe_tagihan }}</span>
                <span class="pt-chip {{ $statusTone }}">
                    @unless($selesai)<span class="dot"></span>@else<i class="bi bi-check-circle-fill"></i>@endunless
                    {{ str_replace('_', ' ', $tagihan->status) }}
                </span>
            </div>
            <h2 class="fw-bolder mb-1 text-white" style="font-size: clamp(1.4rem, 3vw, 1.9rem); letter-spacing: -.5px;">
                {{ $tagihan->nomor_tagihan }}
                @if($tagihan->is_historis)
                    <span class="badge align-middle" style="background:rgba(14,116,144,.35); border:1px solid rgba(165,243,252,.5); color:#a5f3fc; font-size:.6em; letter-spacing:1px;" title="Tagihan arsip yang direkam dari berkas — sudah selesai diproses di luar sistem, tanpa alur verifikasi ulang."><i class="bi bi-archive me-1"></i>HISTORIS</span>
                @endif
            </h2>
            <div class="text-white opacity-75 fw-semibold"><i class="bi bi-building me-1"></i> {{ $pihak }}</div>
        </div>
        <div class="text-md-end">
            <div class="text-white fs-8 text-uppercase fw-bold opacity-75 mb-1" style="letter-spacing: 1.6px;">Total Netto (Dibayarkan)</div>
            <div class="pt-amount">
                <span class="opacity-75" style="font-size:.55em; vertical-align: .45em;">Rp</span><span data-countup="{{ (float) $tagihan->total_netto }}">{{ number_format((float) $tagihan->total_netto, 0, ',', '.') }}</span>
            </div>
        </div>
    </div>
</div>

<!-- ============ PIPELINE STEPPER ============ -->
<div class="pt-pipeline reveal" id="ptPipeline">
    <div class="pt-pipeline-track">
        @foreach($stages as $i => $stage)
            @php $cls = $stage['done'] ? 'done' : ($i === $currentIdx ? 'current' : 'todo'); @endphp
            <button type="button" class="pt-stage {{ $cls }} {{ $stage['done'] ? 'bar-full' : '' }}" data-scroll="{{ $stage['anchor'] }}"
                    title="{{ $stage['label'] }} — {{ $stage['done'] ? 'Selesai' : ($i === $currentIdx ? 'Tahap saat ini' : 'Belum dimulai') }}">
                <span class="bar"><i></i></span>
                <span class="node">
                    @if($stage['done'])<i class="bi bi-check-lg"></i>@else<i class="bi {{ $stage['icon'] }}"></i>@endif
                </span>
                <div class="lbl">{{ $stage['label'] }}</div>
                <div class="sub">{{ $stage['sub'] }}</div>
            </button>
        @endforeach
    </div>
</div>

{{-- Flash messages --}}
@if(session('success'))
    <div class="alert alert-success pt-alert d-flex align-items-center gap-3 reveal">
        <i class="bi bi-check-circle-fill fs-4"></i><div>{{ session('success') }}</div>
    </div>
@endif
@if(session('warning'))
    <div class="alert alert-warning pt-alert d-flex align-items-center gap-3 reveal">
        <i class="bi bi-exclamation-circle-fill fs-4"></i><div>{{ session('warning') }}</div>
    </div>
@endif
@if(session('error'))
    <div class="alert alert-danger pt-alert d-flex align-items-center gap-3 reveal">
        <i class="bi bi-exclamation-triangle-fill fs-4"></i><div>{{ session('error') }}</div>
    </div>
@endif
@if($errors->any())
    <div class="alert alert-danger pt-alert d-flex align-items-center gap-3 reveal">
        <i class="bi bi-exclamation-triangle-fill fs-4"></i><div>{{ $errors->first() }}</div>
    </div>
@endif

<div class="row g-4">
    <!-- ============ MAIN ============ -->
    <div class="col-lg-8">

        <!-- Ringkasan -->
        <div id="sec-ringkasan" class="process-card mb-4 reveal">
            <div class="process-card-body">
                <div class="d-flex align-items-center justify-content-between gap-2 mb-3 pb-3 border-bottom border-light-subtle">
                    <div class="d-flex align-items-center gap-3">
                        <div class="doc-icon-tile" style="--tone: var(--pt-primary); --tone-soft: var(--tone-indigo-soft); width:44px;height:44px;font-size:1.2rem;">
                            <i class="bi bi-journal-text"></i>
                        </div>
                        <h6 class="mb-0 fw-bold text-dark">Ringkasan Tagihan</h6>
                    </div>
                    @if($state['tagihanApproved'])
                        <span class="pt-status success"><i class="bi bi-patch-check-fill"></i> Terverifikasi 6 Verifikator</span>
                    @else
                        <span class="pt-status warning shimmer"><i class="bi bi-hourglass-split"></i> Proses Verifikasi</span>
                    @endif
                </div>

                @php
                    $kontrakSpk = $tagihan->detailKontrak?->kontrakTermin?->kontrak;
                    $terminRingkas = $tagihan->detailKontrak?->kontrakTermin;
                    $bruto = (float) $tagihan->total_bruto;
                    $potongan = (float) $tagihan->total_potongan;
                    $netto = (float) $tagihan->total_netto;
                    $persenPotongan = $bruto > 0 ? round($potongan / $bruto * 100, 1) : 0;
                    $persenNetto = $bruto > 0 ? round(100 - $persenPotongan, 1) : 0;
                    $tipeLabelRingkas = ['KONTRAK' => 'Tagihan SPK', 'KONTRAK_EKSTERNAL' => 'Tagihan Kontrak', 'PERJALDIN' => 'Perjaldin', 'HONORARIUM' => 'Honorarium'][$tagihan->tipe_tagihan] ?? $tagihan->tipe_tagihan;
                    $detailEksternal = $tagihan->detailKontrakEksternal;
                @endphp

                {{-- Identitas pekerjaan --}}
                <div class="rk-identity mb-4">
                    <div class="rk-job">
                        <i class="bi bi-briefcase-fill me-2" style="color: var(--pt-primary);"></i>{{ $kontrakSpk->nama_pekerjaan ?? $detailEksternal?->nama_pekerjaan ?? $tagihan->deskripsi }}
                    </div>
                    @if($kontrakSpk || $detailEksternal)
                        <div class="rk-desc">{{ $tagihan->deskripsi }}</div>
                    @endif
                    <div class="d-flex flex-wrap gap-2 mt-3">
                        <span class="rk-chip"><i class="bi bi-tag-fill"></i> {{ $tipeLabelRingkas }}</span>
                        @if($kontrakSpk)
                            <span class="rk-chip rk-copy" data-copy="{{ $kontrakSpk->nomor_spk }}" title="Klik untuk menyalin nomor SPK">
                                <i class="bi bi-hash"></i> <span class="rk-copy-text">{{ $kontrakSpk->nomor_spk }}</span> <i class="bi bi-copy" style="color:#94a3b8;font-size:.7rem;"></i>
                            </span>
                        @endif
                        @if($detailEksternal)
                            <span class="rk-chip rk-copy" data-copy="{{ $detailEksternal->nomor_surat_pesanan }}" title="Klik untuk menyalin nomor Surat Pesanan">
                                <i class="bi bi-hash"></i> <span class="rk-copy-text">{{ $detailEksternal->nomor_surat_pesanan }}</span> <i class="bi bi-copy" style="color:#94a3b8;font-size:.7rem;"></i>
                            </span>
                            @if($detailEksternal->termin_ke)
                                <span class="rk-chip"><i class="bi bi-collection-fill"></i> Termin {{ $detailEksternal->termin_ke }}{{ $detailEksternal->total_termin ? ' / ' . $detailEksternal->total_termin : '' }}</span>
                            @endif
                        @endif
                        @if($terminRingkas)
                            <span class="rk-chip"><i class="bi bi-collection-fill"></i> Termin {{ $terminRingkas->termin_ke }} · {{ str_replace('_', ' ', $terminRingkas->jenis_termin) }}</span>
                        @endif
                        <span class="rk-chip rk-copy" data-copy="{{ $tagihan->nomor_tagihan }}" title="Klik untuk menyalin nomor tagihan">
                            <i class="bi bi-receipt"></i> <span class="rk-copy-text">{{ $tagihan->nomor_tagihan }}</span> <i class="bi bi-copy" style="color:#94a3b8;font-size:.7rem;"></i>
                        </span>
                    </div>
                </div>

                {{-- Rincian nilai --}}
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="rk-stat">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="rk-ic"><i class="bi bi-cash-stack"></i></span>
                                <span class="rk-lbl">Nilai Bruto</span>
                            </div>
                            <div class="rk-val">Rp <span data-countup="{{ $bruto }}">{{ number_format($bruto, 0, ',', '.') }}</span></div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="rk-stat rk-potongan">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="rk-ic"><i class="bi bi-dash-circle"></i></span>
                                <span class="rk-lbl">Total Potongan</span>
                            </div>
                            <div class="rk-val">- Rp <span data-countup="{{ $potongan }}">{{ number_format($potongan, 0, ',', '.') }}</span></div>
                            <div class="small text-muted mt-1">{{ $persenPotongan }}% dari bruto</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="rk-stat rk-netto">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="rk-ic"><i class="bi bi-wallet2"></i></span>
                                <span class="rk-lbl" style="color:#059669;">Netto Dibayarkan ke Vendor</span>
                            </div>
                            <div class="rk-val">Rp <span data-countup="{{ $netto }}">{{ number_format($netto, 0, ',', '.') }}</span></div>
                        </div>
                    </div>
                </div>

                {{-- Komposisi bruto → netto + potongan --}}
                <div class="rk-bar-wrap mt-3">
                    <div class="rk-bar" role="img" aria-label="Komposisi nilai: netto {{ $persenNetto }}%, potongan {{ $persenPotongan }}%">
                        <span class="rk-seg-netto" style="--w: {{ $persenNetto }}%;"></span>
                        <span class="rk-seg-potongan" style="--w: {{ $persenPotongan }}%;"></span>
                    </div>
                    <div class="rk-legend">
                        <span><span class="dot" style="background:#10b981;"></span>Netto dibayarkan · {{ $persenNetto }}%</span>
                        @if($potongan > 0)
                            <span><span class="dot" style="background:#e11d48;"></span>Potongan (pajak/angsuran) · {{ $persenPotongan }}%</span>
                        @else
                            <span><span class="dot" style="background:#cbd5e1;"></span>Tanpa potongan</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Informasi Vendor -->
        @php
            $vendor = $tagihan->detailKontrak?->kontrakTermin?->kontrak?->vendor ?? $tagihan->pihak;
            $rekening = $vendor ? $vendor->rekening()->where('status_aktif', true)->first() ?? $vendor->rekening()->first() : null;
        @endphp
        @if($vendor)
        <div class="process-card mb-4 reveal">
            <div class="process-card-body">
                <div class="d-flex align-items-center justify-content-between gap-2 mb-3 pb-3 border-bottom border-light-subtle">
                    <div class="d-flex align-items-center gap-3">
                        <div class="doc-icon-tile" style="--tone: var(--pt-info); --tone-soft: var(--tone-info-soft); width:44px;height:44px;font-size:1.2rem;">
                            <i class="bi bi-building"></i>
                        </div>
                        <h6 class="mb-0 fw-bold text-dark">Informasi Vendor & Rekening</h6>
                    </div>
                </div>

                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="process-muted mb-1">Nama Vendor</div>
                        <div class="process-value fs-6">{{ $vendor->nama_pihak ?? '-' }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="process-muted mb-1">NPWP</div>
                        <div class="process-value fs-6">{{ $vendor->npwp ?? '-' }}</div>
                    </div>
                    @if($rekening)
                    <div class="col-md-4">
                        <div class="process-muted mb-1">Bank</div>
                        <div class="process-value fs-6">{{ $rekening->nama_bank ?? '-' }}</div>
                    </div>
                    <div class="col-md-4">
                        <div class="process-muted mb-1">Nomor Rekening</div>
                        <div class="process-value fs-6 font-monospace text-primary fw-bolder">{{ $rekening->nomor_rekening ?? '-' }}</div>
                    </div>
                    <div class="col-md-4">
                        <div class="process-muted mb-1">Atas Nama</div>
                        <div class="process-value fs-6">{{ $rekening->nama_rekening ?? '-' }}</div>
                    </div>
                    @else
                    <div class="col-12">
                        <div class="alert alert-warning small mb-0 py-2 border-0 d-flex align-items-center gap-2">
                            <i class="bi bi-exclamation-triangle"></i> Data rekening bank vendor belum dilengkapi di Master Data.
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
        @endif

        <!-- Daftar penerima honorarium (khusus tipe HONORARIUM) -->
        @if($tagihan->tipe_tagihan === 'HONORARIUM')
            <div id="sec-penerima-honor" class="reveal">
                @include('proses_tagihan._penerima_honorarium_card', ['tagihan' => $tagihan])
            </div>
        @endif

        <!-- Bundel arsip historis (scan SILABI) — sumber perekaman tagihan ini -->
        @if($tagihan->is_historis && $state['buktiTransfer'])
            <div id="sec-arsip-historis" class="reveal">
                <div class="process-card doc-card mb-4" style="--tone:#0e7490; --tone-soft:#ecfeff;">
                    <div class="process-card-body p-4">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                            <div class="d-flex align-items-center gap-3">
                                <div class="doc-icon-tile" style="background:linear-gradient(135deg,#0e7490,#22d3ee); color:#fff;">
                                    <i class="bi bi-archive-fill"></i>
                                </div>
                                <div>
                                    <div class="fw-bold text-dark">Bundel Arsip Historis (Scan SILABI)</div>
                                    <div class="text-muted small">
                                        Berkas sumber perekaman tagihan ini — SPP, SPM, NPI &amp; dokumen pendukung dalam satu PDF.
                                        <span class="fw-semibold">{{ $state['buktiTransfer']->nama_file_asli }}</span>
                                        · {{ number_format(($state['buktiTransfer']->ukuran_file ?? 0) / 1048576, 2, ',', '.') }} MB
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex gap-2 flex-wrap">
                                <button type="button"
                                        class="btn fw-bold shadow-sm btn-pt-action text-white"
                                        style="background:linear-gradient(120deg,#0e7490,#0891b2);"
                                        data-bs-toggle="modal" data-bs-target="#modalArsipHistoris">
                                    <i class="bi bi-eye"></i> Lihat Arsip
                                </button>
                                <a href="{{ route('arsip-sensitif.download', $state['buktiTransfer']->id) }}"
                                   class="btn btn-outline-secondary fw-bold btn-pt-action">
                                    <i class="bi bi-download"></i> Unduh
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- Modal penampil bundel arsip historis --}}
        @if($tagihan->is_historis && $state['buktiTransfer'])
            @push('css')
            <style>
                @keyframes avSheen { 0%,55%{left:-70%} 90%,100%{left:140%} }
                @keyframes avPulse { 0%,100%{transform:scale(1); opacity:.75} 50%{transform:scale(1.12); opacity:1} }
                @media (prefers-reduced-motion: reduce) {
                    #modalArsipHistoris * { animation-duration:.001s !important; transition-duration:.001s !important; }
                }
                #modalArsipHistoris .modal-dialog { max-width: min(1200px, 94vw); transition: transform .35s cubic-bezier(.22,.61,.36,1), opacity .35s ease; transform: translateY(26px) scale(.97); }
                #modalArsipHistoris.show .modal-dialog { transform: none; }
                #modalArsipHistoris .modal-content { border:0; border-radius:1.1rem; overflow:hidden; box-shadow:0 40px 90px -30px rgba(11,16,32,.75); }
                #modalArsipHistoris .av-head { position:relative; overflow:hidden; display:flex; align-items:center; gap:.85rem;
                    padding:1rem 1.35rem; color:#fff; background:linear-gradient(120deg,#0b1020,#155e75 55%,#0e7490); }
                #modalArsipHistoris .av-head::after { content:''; position:absolute; top:0; bottom:0; width:40%; left:-70%;
                    background:linear-gradient(100deg,transparent,rgba(255,255,255,.16),transparent); transform:skewX(-18deg);
                    animation:avSheen 5.5s ease-in-out 1s infinite; pointer-events:none; }
                #modalArsipHistoris .av-ic { display:inline-grid; place-items:center; width:40px; height:40px; border-radius:.85rem; flex-shrink:0;
                    background:rgba(255,255,255,.14); border:1px solid rgba(255,255,255,.25); font-size:1.05rem; color:#a5f3fc; }
                #modalArsipHistoris .av-ttl { font-weight:800; letter-spacing:.2px; }
                #modalArsipHistoris .av-sub { font-size:.74rem; color:#a5f3fc; }
                #modalArsipHistoris .av-act { position:relative; z-index:2; display:inline-flex; align-items:center; gap:.4rem;
                    padding:.42rem .9rem; border-radius:999px; font-size:.78rem; font-weight:700; color:#fff; text-decoration:none;
                    background:rgba(255,255,255,.12); border:1px solid rgba(255,255,255,.25); transition:background .2s, transform .2s; }
                #modalArsipHistoris .av-act:hover { background:rgba(255,255,255,.22); transform:translateY(-1px); color:#fff; }
                #modalArsipHistoris .av-body { position:relative; background:#0f172a; height:min(78vh, 900px); }
                #modalArsipHistoris .av-body iframe { width:100%; height:100%; border:0; display:block; }
                #modalArsipHistoris .av-loading { position:absolute; inset:0; display:grid; place-items:center; color:#a5f3fc;
                    background:radial-gradient(ellipse at center, #12203a, #0f172a); }
                #modalArsipHistoris .av-loading i { font-size:2.2rem; animation:avPulse 1.3s ease-in-out infinite; }
            </style>
            @endpush

            <div class="modal fade" id="modalArsipHistoris" tabindex="-1" aria-labelledby="modalArsipHistorisLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="av-head">
                            <span class="av-ic"><i class="bi bi-archive-fill"></i></span>
                            <div class="me-auto min-w-0">
                                <div class="av-ttl" id="modalArsipHistorisLabel">Bundel Arsip Historis</div>
                                <div class="av-sub text-truncate">{{ $state['buktiTransfer']->nama_file_asli }}</div>
                            </div>
                            <a class="av-act" href="{{ route('arsip-sensitif.download', ['arsip' => $state['buktiTransfer']->id, 'inline' => 1]) }}" target="_blank" rel="noopener" title="Buka di tab baru">
                                <i class="bi bi-box-arrow-up-right"></i> Tab Baru
                            </a>
                            <a class="av-act" href="{{ route('arsip-sensitif.download', $state['buktiTransfer']->id) }}" title="Unduh PDF">
                                <i class="bi bi-download"></i> Unduh
                            </a>
                            <button type="button" class="av-act" data-bs-dismiss="modal" aria-label="Tutup" style="cursor:pointer;">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </div>
                        <div class="av-body">
                            <div class="av-loading" id="avArsipLoading">
                                <div class="text-center">
                                    <i class="bi bi-file-earmark-pdf"></i>
                                    <div class="small mt-2 fw-semibold">Memuat bundel arsip…</div>
                                </div>
                            </div>
                            <iframe id="avArsipFrame" title="Pratinjau bundel arsip historis"
                                    data-src="{{ route('arsip-sensitif.download', ['arsip' => $state['buktiTransfer']->id, 'inline' => 1]) }}"></iframe>
                        </div>
                    </div>
                </div>
            </div>

            @push('script')
            <script>
                // Delegasi di document agar tetap bekerja setelah konten halaman
                // di-refresh async (partial re-render mengganti elemen modal).
                if (!window.__avArsipInit) {
                    window.__avArsipInit = true;
                    document.addEventListener('shown.bs.modal', function (e) {
                        if (!e.target || e.target.id !== 'modalArsipHistoris') return;
                        var frame = e.target.querySelector('#avArsipFrame');
                        var loading = e.target.querySelector('#avArsipLoading');
                        if (frame && !frame.src) {
                            frame.addEventListener('load', function () {
                                if (loading) loading.style.display = 'none';
                            }, { once: true });
                            frame.src = frame.dataset.src;
                        }
                    });
                }
            </script>
            @endpush
        @endif

        <!-- Dokumen yang diunggah / di-generate saat pembuatan tagihan -->
        <div id="sec-dokumen-tagihan" class="reveal">
            @include('proses_tagihan._dokumen_pendukung_card', ['dokumenPendukung' => $dokumenPendukung])
        </div>

        @if($state['missingPrereqs'] && ! $state['spp'])
            @php
                // Peta prasyarat → ikon & penanggung jawab (dibaca dari kalimatnya)
                // agar tiap orang langsung tahu bagian siapa yang belum selesai.
                $pwMeta = function (string $item): array {
                    return match (true) {
                        str_contains($item, 'COA') => ['bi-tag-fill', 'PPK'],
                        str_contains($item, 'KPA') => ['bi-person-fill-check', 'PPK → KPA'],
                        str_contains($item, 'pajak') => ['bi-percent', 'Operator BLU'],
                        str_contains($item, 'BAP') || str_contains($item, 'Vendor') => ['bi-vector-pen', 'Vendor / Staf (manual)'],
                        str_contains($item, 'Verifikator') => ['bi-person-plus-fill', 'Pejabat Pengadaan'],
                        default => ['bi-exclamation-circle', null],
                    };
                };
            @endphp
            {{-- Panel inline (bukan .alert) sehingga tidak diubah menjadi popup toast oleh sky-alerts. --}}
            <div class="pw-panel mb-4 reveal" role="alert">
                <div class="pw-head">
                    <div class="pw-ic"><i class="bi bi-exclamation-triangle-fill"></i></div>
                    <div>
                        <h6 class="fw-bolder text-dark mb-1">Draft Dokumen Belum Dapat Dibuat</h6>
                        <div class="small text-secondary">
                            Lengkapi prasyarat di bawah — draft SPP/SPM/NPI akan dibuat dan
                            <strong>diajukan otomatis ke verifikator</strong> begitu semuanya terpenuhi.
                        </div>
                    </div>
                    <span class="pw-count"><i class="bi bi-list-check me-1"></i>{{ count($state['missingPrereqs']) }} prasyarat tersisa</span>
                </div>
                <div>
                    @foreach($state['missingPrereqs'] as $item)
                        @php [$pwIkon, $pwRole] = $pwMeta($item); @endphp
                        <div class="pw-item">
                            <span class="pw-item-ic"><i class="bi {{ $pwIkon }}"></i></span>
                            <div>
                                <div class="pw-item-text">{{ $item }}</div>
                                @if($pwRole)
                                    <span class="pw-role"><i class="bi bi-person-badge"></i> {{ $pwRole }}</span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Prasyarat: COA + KPA -->
        <div id="sec-coa" class="reveal">
            @include('proses_tagihan._coa_card', ['tagihan' => $tagihan, 'state' => $state, 'coaOptions' => $coaOptions])
        </div>

        <div id="sec-kpa" class="reveal">
            @include('proses_tagihan._kpa_card', ['tagihan' => $tagihan, 'state' => $state])
        </div>

        @if($state['pajakKontrak'] ?? false)
            <div id="sec-pajak-kontrak" class="reveal">
                @include('proses_tagihan._pajak_kontrak_card', ['tagihan' => $tagihan, 'state' => $state, 'pajakOptions' => $pajakOptions])
            </div>
        @endif

        @php
            // Bagian yang dapat ditandai verifikator pada modal revisi
            // "kembalikan ke pembuat tagihan" (lihat _dokumen_card).
            $chainDocsForRevisi = array_filter([
                'tagihan' => ['label' => 'Data Tagihan & Dokumen Pendukung', 'nomor' => $tagihan->nomor_tagihan],
                'spp' => $state['spp'] ? ['label' => 'SPP', 'nomor' => $state['spp']->nomor_spp] : null,
                'spm' => $state['spm'] ? ['label' => 'SPM', 'nomor' => $state['spm']->nomor_spm] : null,
                'npi' => $state['npi'] ? ['label' => 'NPI', 'nomor' => $state['npi']->nomor_npi] : null,
            ]);
        @endphp

        <!-- Dokumen Pencairan -->
        <div id="sec-dokumen" class="d-flex align-items-center gap-3 mt-5 mb-3 reveal">
            <h5 class="fw-bolder text-dark mb-0"><i class="bi bi-layers-half me-2 text-primary"></i>Alur Dokumen Pencairan</h5>
            <div class="flex-grow-1 border-top border-2 border-light-subtle"></div>
            @if($state['dokumenSiapBayar'])
                <span class="pt-status success"><i class="bi bi-check-circle-fill"></i> Semua Disetujui</span>
            @endif
        </div>

        {{-- Verifikasi massal: satu tombol untuk semua dokumen milik user --}}
        @include('proses_tagihan._verifikasi_massal_card')

        <div class="reveal">
            @include('proses_tagihan._dokumen_card', [
                'tagihan' => $tagihan,
                'jenis' => 'spp',
                'label' => 'SPP — Surat Permintaan Pembayaran',
                'icon' => 'bi-file-earmark-arrow-up-fill',
                'color' => 'indigo',
                'document' => $state['spp'],
                'instance' => $state['sppInstance'],
                'myApprovals' => $state['myApprovals']['spp'],
                'chainDocs' => $chainDocsForRevisi,
                'pdfRoute' => $state['spp'] ? route('spps.cetak-pdf', $state['spp']->id) : null,
            ])
        </div>

        <div class="reveal">
            @include('proses_tagihan._dokumen_card', [
                'tagihan' => $tagihan,
                'jenis' => 'spm',
                'label' => 'SPM — Surat Perintah Membayar',
                'icon' => 'bi-file-earmark-check-fill',
                'color' => 'violet',
                'document' => $state['spm'],
                'instance' => $state['spmInstance'],
                'myApprovals' => $state['myApprovals']['spm'],
                'chainDocs' => $chainDocsForRevisi,
                'pdfRoute' => $state['spm'] ? route('spms.cetak-pdf', $state['spm']->id) : null,
            ])
        </div>

        <div class="reveal">
            @include('proses_tagihan._dokumen_card', [
                'tagihan' => $tagihan,
                'jenis' => 'npi',
                'label' => 'NPI — Nota Pemindahbukuan Internal',
                'icon' => 'bi-file-earmark-ruled-fill',
                'color' => 'emerald',
                'document' => $state['npi'],
                'instance' => $state['npiInstance'],
                'myApprovals' => $state['myApprovals']['npi'],
                'chainDocs' => $chainDocsForRevisi,
                'pdfRoute' => $state['npi'] ? route('npis.cetak-pdf', $state['npi']->id) : null,
            ])
        </div>

        <!-- Penyelesaian -->
        <div id="sec-penyelesaian" class="d-flex align-items-center gap-3 mt-5 mb-3 reveal">
            <h5 class="fw-bolder text-dark mb-0"><i class="bi bi-wallet2 me-2 text-success"></i>Penyelesaian Pembayaran</h5>
            <div class="flex-grow-1 border-top border-2 border-light-subtle"></div>
            @if($selesai)
                <span class="pt-status success"><i class="bi bi-stars"></i> Tagihan Selesai</span>
            @endif
        </div>

        <div class="reveal">
            @include('proses_tagihan._bukti_transfer_card', ['tagihan' => $tagihan, 'state' => $state])
        </div>
        <div class="reveal">
            @include('proses_tagihan._sp2d_card', ['tagihan' => $tagihan, 'state' => $state])
        </div>
        <div class="reveal">
            @include('proses_tagihan._pajak_card', ['tagihan' => $tagihan, 'state' => $state])
        </div>
    </div>

    <!-- ============ SIDEBAR ============ -->
    <div class="col-lg-4">
        <div class="process-sticky reveal">
            @include('proses_tagihan._timeline', ['tagihan' => $tagihan, 'state' => $state])
        </div>
    </div>
</div>
