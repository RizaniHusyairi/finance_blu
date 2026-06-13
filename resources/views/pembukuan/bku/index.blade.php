@extends('layouts.app')
@section('title', 'Buku Kas Umum')

@include('pembukuan.partials.styles')

@push('css')
<style>
    .bku-modern {
        --bku-indigo: #6366f1;
        --bku-violet: #8b5cf6;
        --bku-emerald: #10b981;
        --bku-rose: #f43f5e;
        --bku-amber: #f59e0b;
        --bku-ink: #0f172a;
        --bku-muted: #64748b;
    }

    /* ---- Hero ---- */
    .bku-hero {
        position: relative;
        overflow: hidden;
        border-radius: 1.25rem;
        padding: 1.75rem;
        margin-bottom: 1.25rem;
        color: #fff;
        background: linear-gradient(125deg, #4f46e5 0%, #6366f1 45%, #8b5cf6 100%);
        box-shadow: 0 18px 40px -22px rgba(79, 70, 229, 0.75);
    }
    .bku-hero::after {
        content: "";
        position: absolute;
        top: -70px;
        right: -40px;
        width: 280px;
        height: 280px;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(255,255,255,.20) 0%, rgba(255,255,255,0) 70%);
        pointer-events: none;
    }
    .bku-hero .hero-icon {
        width: 54px;
        height: 54px;
        display: grid;
        place-items: center;
        border-radius: 1rem;
        font-size: 1.55rem;
        background: rgba(255, 255, 255, 0.18);
        backdrop-filter: blur(4px);
    }
    .bku-hero h4 { color: #fff; }
    .bku-hero .hero-sub { color: rgba(255, 255, 255, 0.82); max-width: 46ch; }
    .bku-hero .btn {
        font-weight: 600;
        border-radius: 0.8rem;
        padding: 0.55rem 1.1rem;
        border: none;
        transition: transform .15s ease, box-shadow .15s ease;
    }
    .bku-hero .btn:hover { transform: translateY(-2px); box-shadow: 0 12px 22px -10px rgba(0,0,0,.45); }
    .bku-hero .btn-saldo { background: #fff; color: #047857; }
    .bku-hero .btn-pdf   { background: rgba(255,255,255,0.16); color: #fff; }
    .bku-hero .btn-excel { background: #16a34a; color: #fff; }

    /* ---- Stat cards ---- */
    .bku-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
        gap: 1rem;
        margin-bottom: 1.25rem;
    }
    .bku-stat {
        position: relative;
        overflow: hidden;
        background: #fff;
        border: 1px solid rgba(15, 23, 42, 0.06);
        border-radius: 1.1rem;
        padding: 1.15rem 1.25rem;
        box-shadow: 0 10px 30px -24px rgba(15, 23, 42, 0.5);
        transition: transform .18s ease, box-shadow .18s ease;
    }
    .bku-stat:hover { transform: translateY(-4px); box-shadow: 0 18px 34px -22px rgba(15, 23, 42, 0.55); }
    .bku-stat .bar { position: absolute; left: 0; top: 0; bottom: 0; width: 5px; }
    .bku-stat .ic {
        width: 46px;
        height: 46px;
        display: grid;
        place-items: center;
        border-radius: .85rem;
        font-size: 1.3rem;
    }
    .bku-stat .lbl {
        font-size: .72rem;
        text-transform: uppercase;
        letter-spacing: .05em;
        font-weight: 700;
        color: var(--bku-muted);
    }
    .bku-stat .val { font-size: 1.4rem; font-weight: 800; color: var(--bku-ink); line-height: 1.15; }

    .t-emerald { color: var(--bku-emerald); }
    .t-rose    { color: var(--bku-rose); }
    .t-indigo  { color: var(--bku-indigo); }
    .t-amber   { color: var(--bku-amber); }
    .bg-emerald { background: rgba(16,185,129,.12);  color: var(--bku-emerald); }
    .bg-rose    { background: rgba(244,63,94,.12);   color: var(--bku-rose); }
    .bg-indigo  { background: rgba(99,102,241,.12);  color: var(--bku-indigo); }
    .bg-amber   { background: rgba(245,158,11,.12);  color: var(--bku-amber); }
    .bar-emerald { background: var(--bku-emerald); }
    .bar-rose    { background: var(--bku-rose); }
    .bar-indigo  { background: var(--bku-indigo); }
    .bar-amber   { background: var(--bku-amber); }

    /* ---- Filter ---- */
    .bku-modern .book-filter {
        border-radius: 1.1rem;
        box-shadow: 0 10px 30px -26px rgba(15, 23, 42, 0.5);
    }
    .bku-modern .book-filter .form-control:focus,
    .bku-modern .book-filter .form-select:focus {
        border-color: var(--bku-indigo);
        box-shadow: 0 0 0 .2rem rgba(99,102,241,.15);
    }
    .bku-filter-head { display: flex; align-items: center; gap: .5rem; font-weight: 700; color: var(--bku-muted); margin-bottom: .85rem; }
    .bku-filter-head i { color: var(--bku-indigo); }

    /* ---- Table card ---- */
    .bku-modern .book-card { border-radius: 1.1rem; }
    .bku-modern .book-table thead th { background: #f8fafc; }
    .bku-modern .book-table tbody tr { transition: background .12s ease; }
    .bku-modern .book-table tbody tr:hover { background: #f8faff; }
    .pill-bukti {
        font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
        font-size: .78rem;
        background: #f1f5f9;
        color: #475569;
        padding: .22rem .55rem;
        border-radius: .5rem;
        white-space: nowrap;
    }
    .amt-in  { color: var(--bku-emerald); font-weight: 700; }
    .amt-out { color: var(--bku-rose);    font-weight: 700; }
    .rek-chip i { color: var(--bku-indigo); }

    /* ---- Empty ---- */
    .bku-empty { text-align: center; padding: 3.5rem 1rem; }
    .bku-empty .ic {
        width: 84px; height: 84px;
        display: grid; place-items: center;
        margin: 0 auto 1rem;
        border-radius: 1.3rem;
        background: rgba(99,102,241,.08);
        color: var(--bku-indigo);
        font-size: 2.3rem;
    }
</style>
@endpush

@section('content')
<div class="bku-modern">
    <x-page-title title="Pembukuan" subtitle="Buku Kas Umum" />

    {{-- ===== Hero ===== --}}
    <div class="bku-hero">
        <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 align-items-lg-center position-relative">
            <div class="d-flex align-items-center gap-3">
                <div class="hero-icon"><i class="bi bi-journal-bookmark-fill"></i></div>
                <div>
                    <h4 class="mb-1 fw-bold">Buku Kas Umum</h4>
                    <div class="hero-sub">Ledger arus kas masuk dan keluar dengan saldo berjalan per rekening sumber.</div>
                </div>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <button type="button" class="btn btn-saldo" data-bs-toggle="modal" data-bs-target="#modalSaldoAwal">
                    <i class="bi bi-cash-coin me-1"></i> Input Saldo Awal
                </button>
                <a href="{{ route('pembukuan.bku.pdf', request()->query()) }}" target="_blank" class="btn btn-pdf">
                    <i class="bi bi-file-earmark-pdf me-1"></i> Export PDF
                </a>
                <a href="{{ route('pembukuan.bku.excel', request()->query()) }}" class="btn btn-excel">
                    <i class="bi bi-file-earmark-excel me-1"></i> Export Excel
                </a>
            </div>
        </div>
    </div>

    {{-- ===== Summary cards ===== --}}
    @php
        $stats = [
            ['label' => 'Total Debit',      'value' => 'Rp ' . number_format($summary['total_debit'] ?? 0, 0, ',', '.'),  'tone' => 'emerald', 'icon' => 'bi-arrow-down-left-circle-fill'],
            ['label' => 'Total Kredit',     'value' => 'Rp ' . number_format($summary['total_kredit'] ?? 0, 0, ',', '.'), 'tone' => 'rose',    'icon' => 'bi-arrow-up-right-circle-fill'],
            ['label' => 'Saldo Akhir',      'value' => 'Rp ' . number_format($summary['saldo_akhir'] ?? 0, 0, ',', '.'),  'tone' => 'indigo',  'icon' => 'bi-wallet2'],
            ['label' => 'Jumlah Transaksi', 'value' => number_format($summary['jumlah_transaksi'] ?? 0, 0, ',', '.'),     'tone' => 'amber',   'icon' => 'bi-collection-fill'],
        ];
    @endphp
    <div class="bku-stats">
        @foreach($stats as $s)
            <div class="bku-stat">
                <span class="bar bar-{{ $s['tone'] }}"></span>
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="lbl mb-2">{{ $s['label'] }}</div>
                        <div class="val t-{{ $s['tone'] }}">{{ $s['value'] }}</div>
                    </div>
                    <div class="ic bg-{{ $s['tone'] }}"><i class="bi {{ $s['icon'] }}"></i></div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- ===== Filter ===== --}}
    <div class="book-filter">
        <div class="bku-filter-head"><i class="bi bi-funnel-fill"></i> Filter Transaksi</div>
        <form method="GET" action="{{ route('pembukuan.bku.index') }}" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label fw-semibold small">Tanggal Awal</label>
                <input type="date" name="start_date" class="form-control" value="{{ $filters['start_date'] }}">
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold small">Tanggal Akhir</label>
                <input type="date" name="end_date" class="form-control" value="{{ $filters['end_date'] }}">
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold small">Rekening Bank</label>
                <select name="rekening_bank_id" class="form-select">
                    <option value="">Semua Rekening</option>
                    @foreach($rekeningOptions as $rekening)
                        <option value="{{ $rekening->id }}" @selected((string) $filters['rekening_bank_id'] === (string) $rekening->id)>
                            {{ $rekening->nama_bank }} - {{ $rekening->nomor_rekening }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold small">Arus Kas</label>
                <select name="arus_kas" class="form-select">
                    <option value="">Semua</option>
                    <option value="DEBIT_MASUK" @selected($filters['arus_kas'] === 'DEBIT_MASUK')>Debit Masuk</option>
                    <option value="KREDIT_KELUAR" @selected($filters['arus_kas'] === 'KREDIT_KELUAR')>Kredit Keluar</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold small">Sumber Transaksi</label>
                <select name="sumber_transaksi" class="form-select">
                    <option value="">Semua</option>
                    <option value="pengeluaran" @selected($filters['sumber_transaksi'] === 'pengeluaran')>Pengeluaran</option>
                    <option value="penerimaan" @selected($filters['sumber_transaksi'] === 'penerimaan')>Penerimaan</option>
                </select>
            </div>
            <div class="col-12 d-flex gap-2">
                <button class="btn btn-primary"><i class="bi bi-search me-1"></i>Filter</button>
                <a href="{{ route('pembukuan.bku.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-counterclockwise me-1"></i>Reset</a>
            </div>
        </form>
    </div>

    {{-- ===== Table ===== --}}
    <div class="card book-card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <h6 class="mb-0 fw-bold"><i class="bi bi-list-ul me-1 text-primary"></i>Daftar Transaksi BKU</h6>
                <small class="text-muted">Data langsung dari tabel `buku_kas_umum` beserta referensi pengeluaran / penerimaan.</small>
            </div>
            <span class="badge bg-primary rounded-pill">{{ $entries->count() }} baris</span>
        </div>
        <div class="card-body p-0">
            @if($entries->isEmpty())
                <div class="bku-empty">
                    <div class="ic"><i class="bi bi-inbox"></i></div>
                    <h6 class="fw-bold mb-1">Belum ada transaksi BKU</h6>
                    <p class="text-muted small mb-0">Ubah filter atau pastikan data BKU sudah tercatat.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 book-table">
                        <thead class="table-light">
                            <tr>
                                <th>Tanggal</th>
                                <th>Nomor Bukti</th>
                                <th>Uraian</th>
                                <th>Rekening Sumber</th>
                                <th>Arus Kas</th>
                                <th class="text-end">Nominal</th>
                                <th class="text-end">Saldo Akhir</th>
                                <th>Referensi</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($entries as $entry)
                                @php $isDebit = $entry->arus_kas === 'DEBIT_MASUK'; @endphp
                                <tr>
                                    <td class="text-nowrap"><i class="bi bi-calendar3 text-muted me-1"></i>{{ optional($entry->tanggal_transaksi)->format('d M Y') }}</td>
                                    <td><span class="pill-bukti">{{ $entry->nomor_bukti }}</span></td>
                                    <td>
                                        <div class="fw-semibold text-dark">{{ $entry->uraian }}</div>
                                        <div class="small text-muted">
                                            {{ $entry->referensiPengeluaran ? 'Sumber: Pengeluaran' : ($entry->referensiPenerimaan ? 'Sumber: Penerimaan' : 'Sumber: Manual/Sistem') }}
                                        </div>
                                    </td>
                                    <td>
                                        <div class="rek-chip"><i class="bi bi-bank me-1"></i>{{ $entry->sumberRekening?->nama_bank ?? '-' }}</div>
                                        <div class="small text-muted">{{ $entry->sumberRekening?->nomor_rekening ?? '-' }}</div>
                                    </td>
                                    <td>@include('pembukuan.partials.status-badge', ['value' => $entry->arus_kas])</td>
                                    <td class="text-end fw-bold {{ $isDebit ? 'amt-in' : 'amt-out' }}">
                                        {{ $isDebit ? '+' : '-' }} Rp {{ number_format($entry->nominal, 0, ',', '.') }}
                                    </td>
                                    <td class="text-end fw-semibold">Rp {{ number_format($entry->saldo_akhir, 0, ',', '.') }}</td>
                                    <td>
                                        @if($entry->referensiPengeluaran)
                                            <div class="fw-semibold">{{ $entry->referensiPengeluaran->nomor_tagihan ?? '-' }}</div>
                                            <div class="small text-muted">{{ $entry->referensiPengeluaran->pihak?->nama_pihak ?? 'Tagihan pengeluaran' }}</div>
                                        @elseif($entry->referensiPenerimaan)
                                            <div class="fw-semibold">{{ $entry->referensiPenerimaan->nomor_invoice ?? '-' }}</div>
                                            <div class="small text-muted">{{ $entry->referensiPenerimaan->mitra?->nama_pihak ?? 'Transaksi penerimaan' }}</div>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <a href="{{ route('pembukuan.bku.show', $entry->id) }}" class="btn btn-sm btn-outline-primary jasa-icon-btn" title="Detail" data-bs-toggle="tooltip">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    {{-- ===== Modal Input Saldo Awal ===== --}}
    <div class="modal fade" id="modalSaldoAwal" tabindex="-1" aria-labelledby="modalSaldoAwalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" action="{{ route('pembukuan.bku.saldo-awal.store') }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalSaldoAwalLabel">
                            <i class="bi bi-cash-coin me-2"></i>Input Saldo Awal
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info d-flex gap-2 align-items-start" data-sky-ignore>
                            <i class="bi bi-info-circle-fill mt-1"></i>
                            <div class="small mb-0">
                                Saldo awal dicatat sebagai 1 baris penerimaan (DEBIT) di BKU. Hanya bisa diinput bila
                                <strong>tidak ada transaksi BKU pada bulan tepat sebelum tanggal saldo awal</strong>
                                untuk rekening tersebut.
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Rekening Bank <span class="text-danger">*</span></label>
                            <select name="rekening_bank_id" class="form-select @error('rekening_bank_id') is-invalid @enderror" required>
                                <option value="">— Pilih Rekening —</option>
                                @foreach($rekeningOptions as $rekening)
                                    <option value="{{ $rekening->id }}"
                                        @selected((string) old('rekening_bank_id', $filters['rekening_bank_id']) === (string) $rekening->id)>
                                        {{ $rekening->nama_bank }} - {{ $rekening->nomor_rekening }}
                                    </option>
                                @endforeach
                            </select>
                            @error('rekening_bank_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Tanggal Saldo Awal <span class="text-danger">*</span></label>
                            <input type="date" name="tanggal"
                                   class="form-control @error('tanggal') is-invalid @enderror"
                                   value="{{ old('tanggal', now()->startOfMonth()->toDateString()) }}" required>
                            @error('tanggal')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Nominal Saldo Awal (Rp) <span class="text-danger">*</span></label>
                            {{-- Tampilan berformat rupiah; nilai mentah dikirim lewat input hidden "nominal". --}}
                            <div class="input-group @error('nominal') has-validation @enderror">
                                <span class="input-group-text">Rp</span>
                                <input type="text" id="saldoAwalNominalDisplay" inputmode="numeric" autocomplete="off"
                                       class="form-control js-rupiah @error('nominal') is-invalid @enderror"
                                       data-target="saldoAwalNominal"
                                       value="{{ old('nominal') !== null && old('nominal') !== '' ? number_format((float) old('nominal'), 0, ',', '.') : '' }}"
                                       placeholder="0" required>
                                @error('nominal')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <input type="hidden" name="nominal" id="saldoAwalNominal" value="{{ old('nominal') }}">
                        </div>

                        <div class="mb-1">
                            <label class="form-label fw-semibold small">Uraian (opsional)</label>
                            <input type="text" name="uraian" maxlength="255"
                                   class="form-control @error('uraian') is-invalid @enderror"
                                   value="{{ old('uraian') }}" placeholder="Saldo Awal">
                            @error('uraian')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-check2-circle me-1"></i>Simpan Saldo Awal
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('script')
<script>
    // Buka kembali modal saldo awal otomatis bila ada error validasi dari submit sebelumnya.
    @if($errors->any())
    document.addEventListener('DOMContentLoaded', function () {
        var el = document.getElementById('modalSaldoAwal');
        if (el && window.bootstrap) {
            bootstrap.Modal.getOrCreateInstance(el).show();
        }
    });
    @endif

    // Input nominal berformat rupiah (pemisah ribuan) — nilai mentah disimpan
    // ke input hidden agar validasi numeric di server tetap bekerja.
    (function () {
        'use strict';
        var fmt = new Intl.NumberFormat('id-ID');

        document.querySelectorAll('.js-rupiah').forEach(function (display) {
            var hidden = document.getElementById(display.dataset.target);
            if (!hidden) return;

            function sync() {
                var digits = display.value.replace(/\D+/g, '').replace(/^0+(?=\d)/, '');

                // Pertahankan posisi kursor berdasarkan jumlah digit di kirinya.
                var digitsBeforeCaret = display.value
                    .slice(0, display.selectionStart || 0)
                    .replace(/\D+/g, '').length;

                display.value = digits === '' ? '' : fmt.format(parseInt(digits, 10));
                hidden.value = digits;

                var pos = 0, seen = 0;
                while (pos < display.value.length && seen < digitsBeforeCaret) {
                    if (/\d/.test(display.value[pos])) seen++;
                    pos++;
                }
                display.setSelectionRange(pos, pos);
            }

            display.addEventListener('input', sync);

            // Format ulang nilai old() saat halaman dimuat (tanpa menggeser kursor).
            if (display.value !== '') {
                var digits = display.value.replace(/\D+/g, '');
                display.value = digits === '' ? '' : fmt.format(parseInt(digits, 10));
                hidden.value = digits;
            }
        });
    })();
</script>
@endpush
