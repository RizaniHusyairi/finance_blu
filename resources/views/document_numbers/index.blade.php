@extends('layouts.app')

@section('title', 'Manajemen Nomor Dokumen')

@php
    $statusClass = [
        'AVAILABLE' => 'bg-success',
        'RESERVED' => 'bg-warning text-dark',
        'USED' => 'bg-primary',
        'CANCELLED' => 'bg-secondary',
    ];
@endphp

@push('css')
<style>
    /* ── Catat Nomor Eksternal Card ── */
    .ext-card {
        border: none;
        border-radius: 1rem;
        overflow: hidden;
        box-shadow: 0 2px 12px rgba(0,0,0,.06);
        transition: box-shadow .3s ease;
    }
    .ext-card:hover {
        box-shadow: 0 6px 24px rgba(67,97,238,.12);
    }

    .ext-card-header {
        background: linear-gradient(135deg, #4361ee 0%, #3a0ca3 100%);
        padding: 1.15rem 1.5rem;
        display: flex;
        align-items: center;
        gap: .75rem;
    }
    .ext-card-header .icon-circle {
        width: 40px; height: 40px;
        border-radius: 50%;
        background: rgba(255,255,255,.18);
        display: flex; align-items: center; justify-content: center;
        font-size: 1.2rem;
        color: #fff;
        flex-shrink: 0;
    }
    .ext-card-header .header-text h6 {
        margin: 0; color: #fff; font-weight: 700; font-size: .95rem;
    }
    .ext-card-header .header-text span {
        color: rgba(255,255,255,.72); font-size: .78rem;
    }

    .ext-card-body {
        padding: 1.5rem;
        background: #fff;
    }

    /* ── Form group styling ── */
    .ext-form-group { margin-bottom: 0; }
    .ext-form-group .form-label {
        font-size: .8rem;
        font-weight: 600;
        color: #495057;
        margin-bottom: .35rem;
        letter-spacing: .02em;
        text-transform: uppercase;
    }
    .ext-form-group .form-label .text-danger { font-size: .85rem; }

    .ext-form-group .input-group {
        border-radius: .5rem;
        overflow: hidden;
        transition: box-shadow .2s ease;
    }
    .ext-form-group .input-group:focus-within {
        box-shadow: 0 0 0 3px rgba(67,97,238,.18);
    }
    .ext-form-group .input-group .input-group-text {
        background: #f0f2ff;
        border: 1px solid #dee2e6;
        border-right: none;
        color: #4361ee;
        font-size: .95rem;
        padding: .45rem .75rem;
    }
    .ext-form-group .input-group .form-control,
    .ext-form-group .input-group .form-select {
        border: 1px solid #dee2e6;
        border-left: none;
        font-size: .88rem;
        padding: .5rem .75rem;
    }
    .ext-form-group .input-group .form-control:focus,
    .ext-form-group .input-group .form-select:focus {
        border-color: #4361ee;
        box-shadow: none;
    }
    /* standalone fields without input-group icon */
    .ext-form-group > .form-control,
    .ext-form-group > .form-select {
        border-radius: .5rem;
        font-size: .88rem;
        padding: .5rem .75rem;
        transition: border-color .2s, box-shadow .2s;
    }
    .ext-form-group > .form-control:focus,
    .ext-form-group > .form-select:focus {
        border-color: #4361ee;
        box-shadow: 0 0 0 3px rgba(67,97,238,.18);
    }

    /* ── Feedback (absolute so it doesn't push layout) ── */
    .field-with-feedback { position: relative; }
    .field-with-feedback .field-feedback {
        position: absolute;
        top: 100%;
        left: 0; right: 0;
        margin-top: 4px;
        font-size: .78rem;
        line-height: 1.2;
        min-height: 1rem;
        pointer-events: none;
        font-weight: 500;
    }
    .field-with-feedback .field-feedback.text-danger { color: #e63946 !important; }
    .field-with-feedback .field-feedback.text-success { color: #2d6a4f !important; }
    .field-with-feedback .field-feedback.text-muted  { color: #6c757d !important; }
    .field-with-feedback .invalid-feedback { display: none !important; }

    /* ── Status dot indicator ── */
    .status-dot {
        display: inline-block;
        width: 8px; height: 8px;
        border-radius: 50%;
        margin-right: 6px;
        vertical-align: middle;
        background: #adb5bd;
        transition: background .3s ease;
    }
    .status-dot.checking {
        background: #ffc107;
        animation: pulseDot .9s ease-in-out infinite;
    }
    .status-dot.valid   { background: #2d6a4f; }
    .status-dot.invalid { background: #e63946; }

    @keyframes pulseDot {
        0%, 100% { transform: scale(1); opacity: 1; }
        50%      { transform: scale(1.6); opacity: .5; }
    }

    /* ── Divider ── */
    .ext-divider {
        border: none;
        border-top: 1px dashed #dee2e6;
        margin: 1.25rem 0;
    }

    /* ── Submit area ── */
    .ext-submit-area {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        flex-wrap: wrap;
        padding-top: .25rem;
    }
    .ext-submit-area .info-banner {
        display: flex;
        align-items: flex-start;
        gap: .5rem;
        background: #f0f2ff;
        border-radius: .5rem;
        padding: .6rem 1rem;
        font-size: .8rem;
        color: #3a0ca3;
        line-height: 1.45;
        flex: 1;
        min-width: 200px;
    }
    .ext-submit-area .info-banner i {
        font-size: 1rem;
        margin-top: 1px;
        flex-shrink: 0;
    }
    .ext-submit-btn {
        background: linear-gradient(135deg, #4361ee, #3a0ca3);
        border: none;
        color: #fff;
        font-weight: 700;
        padding: .6rem 1.8rem;
        border-radius: .5rem;
        font-size: .88rem;
        display: inline-flex;
        align-items: center;
        gap: .5rem;
        transition: transform .2s ease, box-shadow .2s ease, opacity .2s ease;
        white-space: nowrap;
    }
    .ext-submit-btn:hover:not(:disabled) {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(67,97,238,.35);
        color: #fff;
    }
    .ext-submit-btn:active:not(:disabled) {
        transform: translateY(0);
    }
    .ext-submit-btn:disabled {
        opacity: .55;
        cursor: not-allowed;
    }
</style>
@endpush

@section('content')
    <x-page-title title="Manajemen Nomor Dokumen" subtitle="Register global nomor 4 digit untuk SPK, SPMK, BAPP, BAST, dan BAP" />

    @if(session('success'))
        <div class="alert alert-success border-0 bg-success alert-dismissible fade show shadow-sm">
            <div class="text-white">{{ session('success') }}</div>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger border-0 bg-danger alert-dismissible fade show shadow-sm">
            <div class="text-white">{{ session('error') }}</div>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger border-0 bg-danger alert-dismissible fade show shadow-sm">
            <div class="text-white">{{ $errors->first() }}</div>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row row-cols-1 row-cols-md-2 row-cols-xl-4 g-3 mb-4">
        <div class="col">
            <div class="card rounded-4 h-100 shadow-sm border-0">
                <div class="card-body p-3 border-start border-4 border-primary rounded-4">
                    <p class="mb-1 small text-muted">Total Register</p>
                    <h4 class="mb-0 fw-bold">{{ number_format($summary['total']) }}</h4>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card rounded-4 h-100 shadow-sm border-0">
                <div class="card-body p-3 border-start border-4 border-success rounded-4">
                    <p class="mb-1 small text-muted">Tersedia</p>
                    <h4 class="mb-0 fw-bold">{{ number_format($summary['available']) }}</h4>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card rounded-4 h-100 shadow-sm border-0">
                <div class="card-body p-3 border-start border-4 border-warning rounded-4">
                    <p class="mb-1 small text-muted">Reserved</p>
                    <h4 class="mb-0 fw-bold">{{ number_format($summary['reserved']) }}</h4>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card rounded-4 h-100 shadow-sm border-0">
                <div class="card-body p-3 border-start border-4 border-info rounded-4">
                    <p class="mb-1 small text-muted">Digunakan</p>
                    <h4 class="mb-0 fw-bold">{{ number_format($summary['used']) }}</h4>
                </div>
            </div>
        </div>
    </div>

    <div class="card ext-card mb-4">
        {{-- ── Card Header ── --}}
        <div class="ext-card-header">
            <div class="icon-circle">
                <i class="bx bx-bookmark-plus"></i>
            </div>
            <div class="header-text">
                <h6>Catat Nomor Eksternal</h6>
                <span>Catat nomor dokumen yang sudah dipakai di luar sistem agar dilewati pada penomoran otomatis</span>
            </div>
        </div>

        {{-- ── Card Body / Form ── --}}
        <div class="ext-card-body">
            <form method="POST" action="{{ route('document-numbers.store') }}">
                @csrf

                {{-- Row 1 — Identitas dokumen --}}
                <div class="row g-3 mb-3">
                    <div class="col-lg-4 col-md-6">
                        <div class="ext-form-group">
                            <label class="form-label">Jenis Dokumen</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bx bx-file"></i></span>
                                <select name="document_key" id="documentKeyInput" class="form-select" required>
                                    @foreach($documentKeys as $key)
                                        <option value="{{ $key }}" @selected(old('document_key') === $key)>{{ str_replace('_', ' ', $key) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-3 col-md-6 d-none" id="customPrefixWrapper">
                        <div class="ext-form-group">
                            <label class="form-label">Kode Awal Surat <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bx bx-code-alt"></i></span>
                                <input type="text" name="custom_prefix" id="customPrefixInput"
                                       class="form-control text-uppercase" maxlength="50"
                                       placeholder="Mis: ND.001" value="{{ old('custom_prefix') }}"
                                       pattern="[A-Za-z0-9.\-/_]+"
                                       title="Hanya huruf, angka, titik, garis, dan slash">
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-2 col-md-4">
                        <div class="ext-form-group">
                            <label class="form-label">Tahun</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bx bx-calendar"></i></span>
                                <input type="number" name="tahun" id="tahunInput"
                                       value="{{ request('tahun', now()->year) }}"
                                       class="form-control" min="2000" max="2100" required>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Row 2 — Nomor & detail --}}
                <div class="row g-3 mb-2">
                    <div class="col-lg-3 col-md-4 field-with-feedback">
                        <div class="ext-form-group">
                            <label class="form-label">
                                <span class="status-dot" id="startNumberDot"></span>
                                Mulai 4 Digit <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bx bx-hash"></i></span>
                                <input type="text" name="start_number" id="startNumberInput"
                                       class="form-control" inputmode="numeric"
                                       pattern="[0-9]{4}" minlength="4" maxlength="4"
                                       placeholder="0205" required
                                       title="Wajib 4 digit angka, contoh: 0205">
                            </div>
                            <div class="field-feedback text-muted" id="startNumberFeedback"></div>
                        </div>
                    </div>

                    <div class="col-lg-2 col-md-4">
                        <div class="ext-form-group">
                            <label class="form-label">Jumlah</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bx bx-layer"></i></span>
                                <input type="number" name="count" value="1"
                                       class="form-control" min="1" max="100" required>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-5 col-md-4">
                        <div class="ext-form-group">
                            <label class="form-label">Catatan</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bx bx-note"></i></span>
                                <input type="text" name="notes" class="form-control"
                                       placeholder="Dipakai surat manual / keterangan lain">
                            </div>
                        </div>
                    </div>
                </div>

                <hr class="ext-divider">

                {{-- Footer — Info banner + submit --}}
                <div class="ext-submit-area">
                    <div class="info-banner">
                        <i class="bx bx-info-circle"></i>
                        <span>Isi <strong>4 digit</strong> nomor awal (misal: <code>0205</code>). Jumlah 1 = satu nomor, lebih dari 1 = rentang nomor berurutan yang akan dilewati sistem.</span>
                    </div>
                    <button type="submit" class="ext-submit-btn">
                        <i class="bx bx-lock-alt"></i> Catat & Lewati Nomor
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm border-0 rounded-4 mb-4">
        <div class="card-body p-4">
            <form method="GET" action="{{ route('document-numbers.index') }}">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Cari Nomor</label>
                        <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="PL.107 atau 0200">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Jenis Dokumen</label>
                        <select name="document_key" class="form-select">
                            <option value="">Semua</option>
                            @foreach($documentKeys as $key)
                                <option value="{{ $key }}" @selected(request('document_key') === $key)>{{ str_replace('_', ' ', $key) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold">Status</label>
                        <select name="status" class="form-select">
                            <option value="">Semua</option>
                            @foreach($statusOptions as $status)
                                <option value="{{ $status }}" @selected(request('status') === $status)>{{ $status }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold">Tahun</label>
                        <input type="number" name="tahun" value="{{ request('tahun') }}" class="form-control" min="2000" max="2100">
                    </div>
                    <div class="col-md-2">
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary"><i class="bi bi-funnel me-1"></i> Filter</button>
                            <a href="{{ route('document-numbers.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-counterclockwise me-1"></i> Reset</a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm border-0 rounded-4">
        <div class="card-body p-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th width="5%" class="text-center">No</th>
                            <th width="14%">Jenis</th>
                            <th width="25%">Nomor Dokumen</th>
                            <th width="10%" class="text-center">Status</th>
                            <th width="16%">Pemakaian</th>
                            <th width="18%">Catatan</th>
                            <th width="12%" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($numbers as $number)
                            <tr>
                                <td class="text-center">{{ $numbers->firstItem() + $loop->index }}</td>
                                <td>
                                    <div class="fw-bold">{{ str_replace('_', ' ', $number->document_key) }}</div>
                                    <div class="small text-muted">{{ $number->series_prefix }}</div>
                                </td>
                                <td>
                                    <div class="fw-bold font-monospace text-primary">{{ $number->full_number }}</div>
                                    <div class="small text-muted">Nomor urut: {{ str_pad((string) $number->running_number, $number->number_padding, '0', STR_PAD_LEFT) }}</div>
                                </td>
                                <td class="text-center">
                                    <span class="badge {{ $statusClass[$number->status] ?? 'bg-secondary' }}">{{ $number->status }}</span>
                                </td>
                                <td>
                                    @if($number->status === 'RESERVED')
                                        <div class="fw-semibold">{{ $number->reservedBy->name ?? '-' }}</div>
                                        <div class="small text-muted">{{ optional($number->reserved_at)->translatedFormat('d M Y H:i') }}</div>
                                    @elseif($number->status === 'USED')
                                        <div class="fw-semibold">{{ $number->usedBy->name ?? '-' }}</div>
                                        <div class="small text-muted">{{ $number->usage_source === 'EXTERNAL' ? 'Eksternal' : 'Sistem' }}</div>
                                        <div class="small text-muted">{{ optional($number->used_at)->translatedFormat('d M Y H:i') }}</div>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="small text-muted">{{ $number->notes ?: '-' }}</td>
                                <td class="text-center">
                                    <div class="btn-group">
                                        @if($number->status === 'RESERVED')
                                            <form method="POST" action="{{ route('document-numbers.release', $number) }}">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-secondary">Lepas</button>
                                            </form>
                                        @endif
                                        @if(in_array($number->status, ['AVAILABLE', 'RESERVED'], true))
                                            <form method="POST" action="{{ route('document-numbers.mark-used', $number) }}">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-primary">Eksternal</button>
                                            </form>
                                            <form method="POST" action="{{ route('document-numbers.cancel', $number) }}" onsubmit="return confirm('Batalkan nomor ini?');">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-danger">Batal</button>
                                            </form>
                                        @elseif($number->status === 'USED' && $number->usage_source === 'EXTERNAL')
                                            <form method="POST" action="{{ route('document-numbers.cancel', $number) }}" onsubmit="return confirm('Batalkan nomor eksternal ini?');">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-danger">Batal</button>
                                            </form>
                                        @else
                                            <span class="text-muted small">Terkunci</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">Belum ada nomor dokumen pada filter ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($numbers->hasPages())
                <div class="mt-4 d-flex justify-content-end">
                    {{ $numbers->withQueryString()->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection

@push('script')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const storeForm = document.querySelector('form[action="{{ route('document-numbers.store') }}"]');
        if (!storeForm) return;

        const startNumberInput = document.getElementById('startNumberInput');
        const documentKeyInput = document.getElementById('documentKeyInput');
        const tahunInput = document.getElementById('tahunInput');
        const countInput = storeForm.querySelector('input[name="count"]');
        const feedback = document.getElementById('startNumberFeedback');
        const submitBtn = storeForm.querySelector('button[type="submit"]');
        const customPrefixWrapper = document.getElementById('customPrefixWrapper');
        const customPrefixInput = document.getElementById('customPrefixInput');

        const startNumberDot = document.getElementById('startNumberDot');

        if (!startNumberInput || !documentKeyInput || !tahunInput || !submitBtn) return;

        function toggleCustomPrefix() {
            const isLainnya = documentKeyInput.value === 'LAINNYA';
            if (!customPrefixWrapper || !customPrefixInput) return;
            customPrefixWrapper.classList.toggle('d-none', !isLainnya);
            customPrefixInput.required = isLainnya;
            if (!isLainnya) {
                customPrefixInput.value = '';
                customPrefixInput.classList.remove('is-invalid');
            }
        }
        toggleCustomPrefix();
        documentKeyInput.addEventListener('change', toggleCustomPrefix);

        // Auto-uppercase custom prefix saat user mengetik
        if (customPrefixInput) {
            customPrefixInput.addEventListener('input', function () {
                const cleaned = this.value.toUpperCase().replace(/[^A-Z0-9.\-\/_]/g, '');
                if (cleaned !== this.value) this.value = cleaned;
                checkAvailability();
            });
        }

        let timeoutId;
        let activeController = null;

        function setState(state, message) {
            startNumberInput.classList.remove('is-invalid', 'is-valid');
            feedback.classList.remove('text-danger', 'text-success', 'text-muted');
            feedback.textContent = '';
            submitBtn.disabled = false;

            // Update status dot
            if (startNumberDot) {
                startNumberDot.classList.remove('checking', 'valid', 'invalid');
                if (state) startNumberDot.classList.add(state);
            }

            if (state === 'invalid') {
                startNumberInput.classList.add('is-invalid');
                feedback.classList.add('text-danger');
                feedback.textContent = message || 'Nomor tidak valid.';
                submitBtn.disabled = true;
            } else if (state === 'valid') {
                startNumberInput.classList.add('is-valid');
                feedback.classList.add('text-success');
                feedback.textContent = message || 'Nomor tersedia.';
            } else if (state === 'checking') {
                feedback.classList.add('text-muted');
                feedback.textContent = message || 'Memeriksa ketersediaan...';
            } else {
                feedback.classList.add('text-muted');
            }
        }

        function checkAvailability() {
            clearTimeout(timeoutId);
            if (activeController) {
                activeController.abort();
                activeController = null;
            }

            const raw = startNumberInput.value.replace(/[^0-9]/g, '').slice(0, 4);
            startNumberInput.value = raw;

            if (raw.length === 0) {
                setState('checking', 'Wajib isi 4 digit angka.');
                submitBtn.disabled = true;
                return;
            }

            if (raw.length < 4) {
                setState('invalid', 'Nomor harus tepat 4 digit (kurang ' + (4 - raw.length) + ' digit lagi).');
                return;
            }

            const numeric = parseInt(raw, 10);
            if (!numeric || numeric < 1 || numeric > 9999) {
                setState('invalid', 'Nomor urut harus berada di antara 0001 sampai 9999.');
                return;
            }

            const tahun = parseInt(tahunInput.value, 10);
            if (!tahun || tahun < 2000 || tahun > 2100) {
                setState('invalid', 'Tahun tidak valid.');
                return;
            }

            const isLainnya = documentKeyInput.value === 'LAINNYA';
            const customPrefix = customPrefixInput ? customPrefixInput.value.trim() : '';
            if (isLainnya && customPrefix === '') {
                setState('invalid', 'Isi dulu kode awal surat untuk jenis Lainnya.');
                return;
            }

            timeoutId = setTimeout(() => {
                activeController = new AbortController();
                const params = new URLSearchParams({
                    document_key: documentKeyInput.value,
                    tahun: String(tahun),
                    start_number: raw,
                    count: countInput ? (countInput.value || '1') : '1'
                });
                if (isLainnya && customPrefix) {
                    params.append('custom_prefix', customPrefix);
                }

                setState('checking');

                fetch(`{{ route('document-numbers.check') }}?${params.toString()}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    signal: activeController.signal
                })
                    .then(async response => {
                        const payload = await response.json().catch(() => ({}));
                        if (!response.ok) {
                            const firstError = payload && payload.errors
                                ? Object.values(payload.errors).flat()[0]
                                : (payload.message || payload.error || 'Gagal memeriksa nomor.');
                            throw new Error(firstError);
                        }
                        return payload;
                    })
                    .then(data => {
                        if (data.exists === true) {
                            const conflictText = Array.isArray(data.conflicts) && data.conflicts.length
                                ? ' (' + data.conflicts.join(', ') + ')'
                                : '';
                            setState('invalid', 'Nomor urut sudah dicatat/digunakan' + conflictText + '.');
                        } else {
                            setState('valid');
                        }
                    })
                    .catch(error => {
                        if (error.name === 'AbortError') return;
                        console.error('Error checking document number:', error);
                        setState('invalid', error.message || 'Gagal memeriksa nomor. Coba lagi.');
                    });
            }, 400);
        }

        startNumberInput.addEventListener('input', checkAvailability);
        documentKeyInput.addEventListener('change', checkAvailability);
        tahunInput.addEventListener('input', checkAvailability);
        if (countInput) countInput.addEventListener('input', checkAvailability);

        // Inisialisasi: input kosong → tombol submit disabled & beri petunjuk
        checkAvailability();
    });
</script>
@endpush
