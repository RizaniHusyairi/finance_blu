{{-- ============================================================
     Shared "Pencatatan SP2D" index layout.
     Used by Kontrak / Perjaldin / Honorarium with normalized data.

     Expected variables:
     - $pageTitle      string  e.g. "Kontrak"
     - $pageSubtitle   string
     - $heroIcon       string  material icon name
     - $accent         string  hex color for accent gradient seed
     - $indexRoute     string  route name for the GET filter form
     - $detailRoute    string  route name for detail (receives npi id)
     - $searchPlaceholder string
     - $statusOptions  array   [value => label]
     - $statusFilter   string
     - $search         string
     - $stats          array   list of ['label','value','icon','color','filter']
     - $secondColLabel string  header for the "subject" column (e.g. "Vendor / Pekerjaan")
     - $rows           Collection of normalized row objects/arrays with keys:
          npi_id, nomor_sp2d, tanggal_sp2d, nomor_npi, nomor_spm, nomor_spp,
          third_label (SPK/SPP label text), subject_title, subject_sub,
          nominal, status_label, status_variant (amber|slate|rose|cyan|green|primary),
          state (create|manage|view), show_verif (bool),
          verif => ['PPK'=>status,'KSB'=>status,'PPSPM'=>status]  (optional)
============================================================ --}}
@include('sp2ds.partials.pencatatan-styles')

<div class="sp2d-page">

    {{-- flash --}}
    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm alert-dismissible fade show mb-3">
            <i class="material-icons-outlined align-middle me-1">check_circle</i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger border-0 shadow-sm alert-dismissible fade show mb-3">
            <i class="material-icons-outlined align-middle me-1">error</i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger border-0 shadow-sm alert-dismissible fade show mb-3">
            <ul class="mb-0 ps-3">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- ===== HERO ===== --}}
    <div class="sp2d-hero">
        <span class="sp2d-hero__bar"></span>
        <div class="sp2d-hero__content d-flex align-items-center justify-content-between gap-3">
            <div>
                <span class="sp2d-eyebrow"><i class="material-icons-outlined" style="font-size:15px;">receipt_long</i> Pencatatan SP2D</span>
                <h1>{{ $pageTitle }}</h1>
                <p>{{ $pageSubtitle }}</p>
                <span class="sp2d-hero__badge"><i class="material-icons-outlined" style="font-size:17px;">account_balance_wallet</i> Role aktif: Bendahara Pengeluaran</span>
            </div>
            <div class="sp2d-hero__icon"><i class="material-icons-outlined">{{ $heroIcon }}</i></div>
        </div>
    </div>

    {{-- ===== STAT CARDS ===== --}}
    <div class="sp2d-stats">
        @foreach($stats as $stat)
            <a class="sp2d-stat {{ $statusFilter === $stat['filter'] ? 'is-active' : '' }}"
               style="--accent: {{ $stat['color'] }};"
               href="{{ route($indexRoute, ['status' => $stat['filter'], 'search' => $search]) }}">
                <div class="sp2d-stat__top">
                    <div>
                        <div class="sp2d-stat__num" data-count="{{ $stat['value'] }}">0</div>
                        <div class="sp2d-stat__label">{{ $stat['label'] }}</div>
                    </div>
                    <div class="sp2d-stat__icon"><i class="material-icons-outlined">{{ $stat['icon'] }}</i></div>
                </div>
                <span class="sp2d-stat__spark">Filter →</span>
            </a>
        @endforeach
    </div>

    {{-- ===== TOOLBAR ===== --}}
    <div class="sp2d-toolbar">
        <form method="GET" action="{{ route($indexRoute) }}" class="row g-2 align-items-center">
            <div class="col-12 col-md-3">
                <select name="status" class="sp2d-select w-100" onchange="this.form.submit()">
                    @foreach($statusOptions as $val => $label)
                        <option value="{{ $val }}" {{ $statusFilter === $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-7">
                <div class="sp2d-input-wrap">
                    <i class="material-icons-outlined">search</i>
                    <input type="text" name="search" class="sp2d-input" value="{{ $search }}" placeholder="{{ $searchPlaceholder }}">
                </div>
            </div>
            <div class="col-12 col-md-2 d-flex gap-2">
                <button type="submit" class="sp2d-btn flex-grow-1 justify-content-center"><i class="material-icons-outlined" style="font-size:18px;">filter_alt</i> Filter</button>
                <a href="{{ route($indexRoute) }}" class="sp2d-btn-ghost" title="Reset"><i class="material-icons-outlined" style="font-size:18px;">refresh</i></a>
            </div>
        </form>
    </div>

    {{-- ===== TABLE ===== --}}
    <div class="sp2d-panel">
        <div class="sp2d-panel__head">
            <div class="sp2d-panel__title"><i class="material-icons-outlined">list_alt</i> Daftar Antrean SP2D</div>
            <span class="sp2d-count-chip">{{ $rows->count() }} dokumen</span>
        </div>
        <div class="table-responsive">
            <table class="sp2d-table">
                <thead>
                    <tr>
                        <th style="width:42px;">#</th>
                        <th>Nomor SP2D</th>
                        <th>NPI / SPM / {{ $thirdLabel ?? 'SPP' }}</th>
                        <th>{{ $secondColLabel }}</th>
                        <th class="text-end">Nilai SP2D</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Verifikasi</th>
                        <th class="text-center" style="width:140px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $i => $r)
                        @php $r = (object) $r; @endphp
                        <tr class="sp2d-row {{ $r->state === 'create' || $r->state === 'manage' ? 'is-actionable' : '' }}" style="animation-delay: {{ min($i * 0.04, 0.4) }}s;">
                            <td class="text-muted">{{ $i + 1 }}</td>
                            <td>
                                @if(!empty($r->nomor_sp2d))
                                    <div class="sp2d-docnum">{{ $r->nomor_sp2d }}</div>
                                    <div class="sp2d-sub">{{ $r->tanggal_sp2d ? \Carbon\Carbon::parse($r->tanggal_sp2d)->format('d M Y') : '-' }}</div>
                                @else
                                    <span class="sp2d-docnum-empty">Belum dibuat</span>
                                @endif
                            </td>
                            <td>
                                <div class="sp2d-meta">
                                    <span class="k">NPI:</span> <span class="fw-semibold">{{ $r->nomor_npi ?? '-' }}</span><br>
                                    <span class="k">SPM:</span> {{ $r->nomor_spm ?? '-' }}<br>
                                    <span class="k">{{ $thirdLabel ?? 'SPP' }}:</span> {{ $r->third_value ?? '-' }}
                                </div>
                            </td>
                            <td>
                                <div class="fw-semibold text-truncate" style="max-width: 220px;" title="{{ $r->subject_title }}">{{ \Illuminate\Support\Str::limit($r->subject_title ?? '-', 52) }}</div>
                                @if(!empty($r->subject_sub))
                                    <div class="sp2d-sub text-truncate" style="max-width: 220px;">{{ $r->subject_sub }}</div>
                                @endif
                            </td>
                            <td class="text-end"><span class="sp2d-amount">Rp {{ number_format($r->nominal ?? 0, 0, ',', '.') }}</span></td>
                            <td class="text-center">
                                <span class="sp2d-badge sp2d-badge--{{ $r->status_variant }} {{ in_array($r->status_variant, ['cyan','primary']) ? 'is-pulse' : '' }}">
                                    <span class="dot"></span>{{ $r->status_label }}
                                </span>
                            </td>
                            <td class="text-center">
                                @if(!empty($r->show_verif) && !empty($r->verif))
                                    <div class="sp2d-verif justify-content-center">
                                        @foreach($r->verif as $role => $st)
                                            @php
                                                $cls = match($st) {
                                                    'APPROVED' => 'ok',
                                                    'PENDING'  => 'wait',
                                                    'REVISION','REJECTED' => 'bad',
                                                    default => '',
                                                };
                                                $ic = match($st) {
                                                    'APPROVED' => 'check',
                                                    'PENDING'  => 'hourglass_empty',
                                                    'REVISION','REJECTED' => 'close',
                                                    default => 'remove',
                                                };
                                            @endphp
                                            <span class="sp2d-vchip {{ $cls }}" title="{{ $role }}: {{ $st ?? '-' }}">{{ $role }} <i class="material-icons-outlined">{{ $ic }}</i></span>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($r->state === 'create')
                                    <a href="{{ route($detailRoute, $r->npi_id) }}" class="sp2d-action sp2d-action--create"><i class="material-icons-outlined">add_circle</i> Buat SP2D</a>
                                @elseif($r->state === 'manage')
                                    <a href="{{ route($detailRoute, $r->npi_id) }}" class="sp2d-action sp2d-action--manage"><i class="material-icons-outlined">edit_document</i> Kelola</a>
                                @else
                                    <a href="{{ route($detailRoute, $r->npi_id) }}" class="sp2d-action sp2d-action--view"><i class="material-icons-outlined">visibility</i> Detail</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">
                                <div class="sp2d-empty">
                                    <div class="sp2d-empty__icon"><i class="material-icons-outlined">inbox</i></div>
                                    <h6>Belum ada data</h6>
                                    <p>Tidak ada SP2D {{ $pageTitle }} yang cocok dengan filter saat ini.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
(function () {
    // Count-up animation for stat numbers
    const els = document.querySelectorAll('.sp2d-stat__num');
    const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    els.forEach(function (el) {
        const target = parseInt(el.getAttribute('data-count') || '0', 10);
        if (reduce || target === 0) { el.textContent = target; return; }
        const dur = 900, start = performance.now();
        function tick(now) {
            const p = Math.min((now - start) / dur, 1);
            const eased = 1 - Math.pow(1 - p, 3);
            el.textContent = Math.round(target * eased);
            if (p < 1) requestAnimationFrame(tick);
        }
        requestAnimationFrame(tick);
    });
})();
</script>
