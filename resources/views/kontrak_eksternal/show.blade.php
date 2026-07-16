@extends('layouts.app')
@section('title', 'Detail Kontrak')

@push('css')
<style>
    :root { --kx-primary: #4f46e5; --kx-primary-2: #a855f7; }
    @keyframes kxIn { from { opacity: 0; transform: translateY(14px); } to { opacity: 1; transform: translateY(0); } }
    .kx-reveal { opacity: 0; animation: kxIn .5s cubic-bezier(.22, 1, .36, 1) forwards; animation-delay: var(--d, 0s); }

    .kx-hero {
        position: relative; overflow: hidden; border-radius: 1.25rem; padding: 1.6rem 1.8rem; color: #fff;
        background: linear-gradient(120deg, #312e81 0%, #4f46e5 45%, #7c3aed 100%);
        box-shadow: 0 16px 36px -16px rgba(79, 70, 229, .55);
    }
    .kx-hero::before { content: ''; position: absolute; width: 260px; height: 260px; top: -130px; right: -60px; border-radius: 50%; background: rgba(255,255,255,.08); pointer-events: none; }
    .kx-hero-title { font-weight: 800; letter-spacing: -.4px; color: #fff !important; overflow-wrap: anywhere; }
    .kx-chip { display: inline-flex; align-items: center; gap: .4rem; padding: .3rem .75rem; border-radius: 999px; background: rgba(255,255,255,.14); border: 1px solid rgba(255,255,255,.22); font-size: .72rem; font-weight: 700; }
    .kx-status { display: inline-flex; align-items: center; gap: .35rem; padding: .3rem .8rem; border-radius: 999px; font-size: .72rem; font-weight: 800; letter-spacing: .05em; background: rgba(255,255,255,.92); }
    .kx-status.DRAFT { color: #92400e; }
    .kx-status.AKTIF { color: #065f46; }
    .kx-status.SELESAI { color: #3730a3; }
    .kx-status.DIBATALKAN { color: #991b1b; }

    .kx-card { border: 1px solid #eef0f4; border-radius: 1.15rem; background: #fff; box-shadow: 0 2px 10px rgba(15,23,42,.04); }
    .kx-card-head { display: flex; align-items: center; gap: .7rem; padding: 1rem 1.3rem .6rem; }
    .kx-card-title { font-weight: 800; margin: 0; letter-spacing: -.2px; }
    .kx-card-body { padding: .5rem 1.3rem 1.25rem; }
    .kx-ic { width: 38px; height: 38px; display: inline-flex; align-items: center; justify-content: center; border-radius: .8rem; color: #fff; background: linear-gradient(135deg, var(--tone, #4f46e5), var(--tone2, #818cf8)); }

    .kx-row { display: flex; justify-content: space-between; gap: 1rem; padding: .45rem 0; border-bottom: 1px dashed #eef0f4; font-size: .85rem; }
    .kx-row:last-child { border-bottom: 0; }
    .kx-row .k { color: #94a3b8; font-weight: 600; }
    .kx-row .v { font-weight: 700; color: #0f172a; text-align: right; font-variant-numeric: tabular-nums; }

    .kx-termin-table { width: 100%; font-size: .84rem; }
    .kx-termin-table th { font-size: .66rem; font-weight: 800; text-transform: uppercase; letter-spacing: .05em; color: #94a3b8; padding: .5rem .6rem; border-bottom: 1px solid #eef0f4; }
    .kx-termin-table td { padding: .65rem .6rem; vertical-align: middle; border-bottom: 1px dashed #f1f5f9; }
    .kx-termin-table .num { text-align: right; font-variant-numeric: tabular-nums; font-weight: 700; }
    .kx-tstat { display: inline-flex; align-items: center; gap: .3rem; padding: .22rem .6rem; border-radius: 999px; font-size: .64rem; font-weight: 800; letter-spacing: .04em; }
    .kx-tstat.LOCKED { background: #f1f5f9; color: #64748b; }
    .kx-tstat.READY_TO_BILL { background: #d1fae5; color: #065f46; }
    .kx-tstat.DRAFT { background: #fef3c7; color: #92400e; }
    .kx-tstat.SUDAH_DITAGIH { background: #e0e7ff; color: #3730a3; }
    .kx-idx { width: 32px; height: 32px; display: grid; place-items: center; border-radius: 10px; font-weight: 800; color: #fff; background: linear-gradient(135deg, #4f46e5, #818cf8); font-size: .78rem; }

    .kx-serap-track { height: 10px; border-radius: 999px; background: rgba(255,255,255,.25); overflow: hidden; }
    .kx-serap-fill { height: 100%; border-radius: 999px; background: #fff; transition: width .6s ease; }
    .kx-meta { font-size: .74rem; color: #94a3b8; font-weight: 600; }

    /* ── Informasi Kontrak: grid tile modern ─────────────────────── */
    .kxi-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .7rem; }
    @media (max-width: 575.98px) { .kxi-grid { grid-template-columns: 1fr; } .kxi-span2 { grid-column: auto !important; } }
    .kxi-span2 { grid-column: span 2; }
    .kxi-tile {
        position: relative; display: flex; align-items: center; gap: .8rem;
        border: 1px solid #eef0f4; border-radius: .9rem; padding: .8rem .95rem;
        background: linear-gradient(180deg, #fff, #fafbff);
        transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease;
        overflow: hidden;
    }
    .kxi-tile:hover { transform: translateY(-2px); border-color: #dfe3f6; box-shadow: 0 10px 22px -14px rgba(79,70,229,.45); }
    .kxi-ic {
        width: 38px; height: 38px; flex-shrink: 0; border-radius: 11px;
        display: inline-flex; align-items: center; justify-content: center;
        font-size: 1rem; color: var(--t, #4f46e5); background: var(--ts, #eef2ff);
        transition: transform .25s cubic-bezier(.34,1.56,.64,1);
    }
    .kxi-tile:hover .kxi-ic { transform: scale(1.12) rotate(-5deg); }
    .kxi-body { min-width: 0; flex: 1; }
    .kxi-lbl { font-size: .64rem; font-weight: 800; text-transform: uppercase; letter-spacing: .07em; color: #94a3b8; }
    .kxi-val { font-size: .88rem; font-weight: 800; color: #0f172a; overflow-wrap: anywhere; }
    .kxi-val-lg { font-size: 1.35rem; letter-spacing: -.02em; color: #047857; font-variant-numeric: tabular-nums; }
    .kxi-nilai { border-color: rgba(16,185,129,.35); background: linear-gradient(135deg, rgba(16,185,129,.09), rgba(16,185,129,.015)); }
    .kxi-nilai::after {
        content: ''; position: absolute; top: 0; bottom: 0; width: 38%; left: -55%;
        background: linear-gradient(100deg, transparent, rgba(255,255,255,.6), transparent);
        transform: skewX(-18deg); animation: kxiShine 3.6s ease-in-out 1s infinite;
    }
    @keyframes kxiShine { 0%, 60% { left: -55%; } 90%, 100% { left: 130%; } }
    .kxi-copy { cursor: pointer; }
    .kxi-copy-ic { color: #cbd5e1; font-size: .85rem; transition: color .2s; }
    .kxi-copy:hover .kxi-copy-ic { color: #4f46e5; }
    .kxi-copy.kxi-copied { border-color: #a7f3d0; background: #f0fdf4; }
    .kxi-copy.kxi-copied .kxi-copy-ic { color: #059669; }

    /* Panel dokumen Surat Pesanan */
    .kxi-doc {
        display: flex; align-items: center; gap: .9rem;
        border: 1px solid #fecdd3; border-radius: 1rem; padding: .95rem 1.05rem;
        background: linear-gradient(135deg, #fff, #fff1f2aa);
        transition: transform .2s ease, box-shadow .2s ease;
    }
    .kxi-doc:hover { transform: translateY(-2px); box-shadow: 0 12px 26px -16px rgba(225,29,72,.5); }
    .kxi-doc-missing { border: 2px dashed #e2e8f0; background: repeating-linear-gradient(-45deg, #f8fafc 0 12px, #f1f5f9 12px 24px); }
    .kxi-doc-missing:hover { box-shadow: none; transform: none; }
    .kxi-doc-ic {
        width: 46px; height: 46px; flex-shrink: 0; border-radius: 13px;
        display: inline-flex; align-items: center; justify-content: center;
        font-size: 1.35rem; color: #e11d48; background: #ffe4e6;
    }
    .kxi-doc-missing .kxi-doc-ic { color: #94a3b8; background: #f1f5f9; }
    .kxi-doc-btn {
        display: inline-flex; align-items: center; gap: .45rem; flex-shrink: 0;
        padding: .5rem 1.05rem; border-radius: 999px; text-decoration: none;
        font-size: .78rem; font-weight: 800; color: #fff;
        background: linear-gradient(135deg, #e11d48, #f43f5e);
        box-shadow: 0 8px 18px -8px rgba(225,29,72,.7);
        transition: transform .2s ease, box-shadow .2s ease;
    }
    .kxi-doc-btn:hover { color: #fff; transform: translateY(-2px) scale(1.03); box-shadow: 0 12px 24px -8px rgba(225,29,72,.8); }
    @media (prefers-reduced-motion: reduce) {
        .kxi-tile, .kxi-tile:hover, .kxi-ic, .kxi-doc, .kxi-doc-btn { transition: none; transform: none; }
        .kxi-nilai::after { animation: none; }
    }
</style>
@endpush

@section('content')
<div class="page-content">
    @php
        $totalTermin = $kontrak->termin->count();
        $tertagih = $kontrak->termin->where('status_termin', 'SUDAH_DITAGIH')->count();
        $serapan = $kontrak->persentase_serapan;
    @endphp

    {{-- Hero --}}
    <div class="kx-hero mb-4 kx-reveal" style="--d:.02s;">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 position-relative" style="z-index:1;">
            <div style="max-width: 720px;">
                <div class="d-flex align-items-center gap-2 flex-wrap mb-2">
                    <span class="kx-status {{ $kontrak->status_kontrak }}"><span>●</span> {{ $kontrak->status_kontrak }}</span>
                    <span class="kx-chip"><i class="bi bi-hash"></i> {{ $kontrak->nomor_surat_pesanan }}</span>
                    <span class="kx-chip"><i class="bi bi-cart-check"></i> {{ $kontrak->sumber }}</span>
                    <span class="kx-chip"><i class="bi bi-cash-stack"></i> {{ $kontrak->metode_pembayaran === 'TERMIN' ? $totalTermin . ' Termin' : 'Lumpsum' }}</span>
                </div>
                <h5 class="kx-hero-title mb-1">{{ $kontrak->nama_pekerjaan }}</h5>
                <div style="color:rgba(255,255,255,.85); font-size:.85rem;">
                    <i class="bi bi-shop"></i> {{ $kontrak->vendor?->nama_pihak }} ·
                    <i class="bi bi-calendar3"></i> {{ optional($kontrak->tanggal_surat_pesanan)->format('d M Y') }} ·
                    Nilai <strong>Rp {{ number_format((float) $kontrak->nilai_total_kontrak, 0, ',', '.') }}</strong>
                </div>
                <div class="mt-3" style="max-width: 420px;">
                    <div class="d-flex justify-content-between" style="font-size:.72rem; font-weight:700; color:rgba(255,255,255,.85);">
                        <span>Serapan {{ $tertagih }}/{{ $totalTermin }} termin</span><span>{{ number_format($serapan, 0) }}%</span>
                    </div>
                    <div class="kx-serap-track mt-1"><div class="kx-serap-fill" style="width: {{ min($serapan, 100) }}%;"></div></div>
                </div>
            </div>
            <div class="d-flex flex-column gap-2">
                <a href="{{ route('kontrak-eksternal.index') }}" class="btn btn-light rounded-3 fw-bold btn-sm"><i class="bi bi-arrow-left"></i> Kembali</a>
                @if($kontrak->isEditable())
                    <a href="{{ route('kontrak-eksternal.edit', $kontrak->id) }}" class="btn btn-warning rounded-3 fw-bold btn-sm"><i class="bi bi-pencil"></i> Edit Kontrak</a>
                    <form method="POST" action="{{ route('kontrak-eksternal.activate', $kontrak->id) }}"
                          onsubmit="return confirm('Aktifkan kontrak? Skema termin terkunci dan termin pertama siap ditagih.');">
                        @csrf
                        <button type="submit" class="btn btn-success rounded-3 fw-bold btn-sm w-100"><i class="bi bi-rocket-takeoff"></i> Aktifkan Kontrak</button>
                    </form>
                @endif
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success rounded-4 kx-reveal" style="--d:.04s;"><i class="bi bi-check-circle-fill me-1"></i> {{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger rounded-4 kx-reveal" style="--d:.04s;">
            @foreach($errors->all() as $err)<div><i class="bi bi-exclamation-triangle-fill me-1"></i> {{ $err }}</div>@endforeach
        </div>
    @endif

    @if($kontrak->status_kontrak === 'DRAFT')
        <div class="alert alert-warning rounded-4 kx-reveal" style="--d:.05s;">
            <i class="bi bi-info-circle-fill me-1"></i>
            Kontrak masih <strong>DRAFT</strong> — periksa skema termin di bawah, lalu klik <strong>Aktifkan Kontrak</strong> untuk mulai menagih termin pertama.
        </div>
    @endif

    <div class="row g-4">
        {{-- Kolom kiri --}}
        <div class="col-lg-8">
            {{-- Skema Termin & Tagihan --}}
            <div class="kx-card mb-4 kx-reveal" style="--d:.08s;">
                <div class="kx-card-head">
                    <span class="kx-ic" style="--tone:#7c3aed; --tone2:#a855f7;"><i class="bi bi-list-ol"></i></span>
                    <div>
                        <h6 class="kx-card-title">Skema Termin &amp; Tagihan</h6>
                        <div class="kx-meta">Termin terbuka berurutan — SP2D termin sebelumnya membuka termin berikutnya.</div>
                    </div>
                </div>
                <div class="kx-card-body">
                    <div class="table-responsive">
                        <table class="kx-termin-table">
                            <thead>
                                <tr>
                                    <th style="width:44px;">#</th>
                                    <th>Keterangan</th>
                                    <th>Jenis</th>
                                    <th class="num">%</th>
                                    <th class="num">Bruto</th>
                                    <th class="num">Angsuran UM</th>
                                    <th>Status</th>
                                    <th style="width:150px;"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($kontrak->termin as $termin)
                                    @php $tagihanTermin = $termin->detailKontrakEksternal?->tagihan; @endphp
                                    <tr>
                                        <td><span class="kx-idx">{{ $termin->termin_ke }}</span></td>
                                        <td class="fw-bold">{{ $termin->keterangan_termin }}</td>
                                        <td><span class="kx-meta">{{ $termin->jenis_termin }}</span></td>
                                        <td class="num">{{ rtrim(rtrim(number_format((float) $termin->persentase, 4, '.', ''), '0'), '.') }}%</td>
                                        <td class="num text-success">Rp {{ number_format((float) $termin->nilai_bruto_termin, 0, ',', '.') }}</td>
                                        <td class="num" style="color:#b45309;">{{ (float) $termin->potongan_angsuran_uang_muka > 0 ? '− Rp ' . number_format((float) $termin->potongan_angsuran_uang_muka, 0, ',', '.') : '—' }}</td>
                                        <td><span class="kx-tstat {{ $termin->status_termin }}">{{ str_replace('_', ' ', $termin->status_termin) }}</span></td>
                                        <td class="text-end">
                                            @if($kontrak->status_kontrak === 'AKTIF' && $termin->status_termin === 'READY_TO_BILL')
                                                <a href="{{ route('kontrak-eksternal.termin.bill', [$kontrak->id, $termin->id]) }}"
                                                   class="btn btn-sm btn-primary rounded-3 fw-bold">
                                                    <i class="bi bi-receipt"></i> Buat Tagihan
                                                </a>
                                            @elseif($tagihanTermin)
                                                <a href="{{ route('tagihan-kontrak-eksternal.show', $tagihanTermin->id) }}"
                                                   class="btn btn-sm btn-light border rounded-3 fw-bold">
                                                    <i class="bi bi-eye"></i> Tagihan
                                                </a>
                                            @else
                                                <span class="kx-meta"><i class="bi bi-lock"></i> Menunggu</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Informasi Kontrak --}}
            @php
                // Arsip Surat Pesanan aktif — disajikan lewat route terproteksi
                // arsip.view (auth + role internal), bukan tautan publik /storage.
                $spArsipAll = $kontrak->arsipDokumen->where('jenis_dokumen', 'SURAT_PESANAN')->sortByDesc('id');
                $spArsip = $spArsipAll->firstWhere('is_active', true) ?? $spArsipAll->first();
                $spUkuran = $spArsip?->ukuran_file ? number_format($spArsip->ukuran_file / 1024 / 1024, 2, ',', '.') . ' MB' : null;
            @endphp
            <div class="kx-card mb-4 kx-reveal" style="--d:.12s;">
                <div class="kx-card-head">
                    <span class="kx-ic" style="--tone:#4f46e5; --tone2:#818cf8;"><i class="bi bi-file-earmark-text"></i></span>
                    <div>
                        <h6 class="kx-card-title">Informasi Kontrak</h6>
                        <div class="kx-meta">Identitas Surat Pesanan &amp; nilai kesepakatan.</div>
                    </div>
                </div>
                <div class="kx-card-body">
                    {{-- Grid tile info --}}
                    <div class="kxi-grid">
                        <div class="kxi-tile kxi-span2 kxi-copy" data-kxi-copy="{{ $kontrak->nomor_surat_pesanan }}" title="Klik untuk menyalin nomor">
                            <span class="kxi-ic" style="--t:#4f46e5; --ts:#eef2ff;"><i class="bi bi-hash"></i></span>
                            <div class="kxi-body">
                                <div class="kxi-lbl">Nomor Surat Pesanan</div>
                                <div class="kxi-val font-monospace kxi-copy-text">{{ $kontrak->nomor_surat_pesanan }}</div>
                            </div>
                            <i class="bi bi-copy kxi-copy-ic"></i>
                        </div>
                        <div class="kxi-tile">
                            <span class="kxi-ic" style="--t:#0891b2; --ts:#ecfeff;"><i class="bi bi-calendar3"></i></span>
                            <div class="kxi-body">
                                <div class="kxi-lbl">Tanggal Surat Pesanan</div>
                                <div class="kxi-val">{{ optional($kontrak->tanggal_surat_pesanan)->translatedFormat('d F Y') ?? '-' }}</div>
                            </div>
                        </div>
                        <div class="kxi-tile">
                            <span class="kxi-ic" style="--t:#7c3aed; --ts:#f5f3ff;"><i class="bi bi-cart-check"></i></span>
                            <div class="kxi-body">
                                <div class="kxi-lbl">Sumber</div>
                                <div class="kxi-val">{{ $kontrak->sumber }}</div>
                            </div>
                        </div>
                        <div class="kxi-tile">
                            <span class="kxi-ic" style="--t:#c2410c; --ts:#fff7ed;"><i class="bi bi-credit-card-2-front"></i></span>
                            <div class="kxi-body">
                                <div class="kxi-lbl">Metode Pembayaran</div>
                                <div class="kxi-val">{{ $kontrak->metode_pembayaran === 'TERMIN' ? 'Termin (' . $totalTermin . '×)' : 'Lumpsum' }}</div>
                            </div>
                        </div>
                        <div class="kxi-tile">
                            <span class="kxi-ic" style="--t:#b45309; --ts:#fffbeb;"><i class="bi bi-wallet2"></i></span>
                            <div class="kxi-body">
                                <div class="kxi-lbl">Uang Muka</div>
                                <div class="kxi-val">{{ (float) $kontrak->nilai_uang_muka > 0 ? 'Rp ' . number_format((float) $kontrak->nilai_uang_muka, 0, ',', '.') : 'Tidak ada' }}</div>
                            </div>
                        </div>
                        <div class="kxi-tile kxi-span2 kxi-nilai">
                            <span class="kxi-ic" style="--t:#047857; --ts:#ecfdf5;"><i class="bi bi-cash-stack"></i></span>
                            <div class="kxi-body">
                                <div class="kxi-lbl" style="color:#059669;">Nilai Total Kontrak</div>
                                <div class="kxi-val kxi-val-lg">Rp {{ number_format((float) $kontrak->nilai_total_kontrak, 0, ',', '.') }}</div>
                            </div>
                        </div>
                        @if($kontrak->diaktifkan_at)
                            <div class="kxi-tile kxi-span2">
                                <span class="kxi-ic" style="--t:#059669; --ts:#ecfdf5;"><i class="bi bi-rocket-takeoff"></i></span>
                                <div class="kxi-body">
                                    <div class="kxi-lbl">Diaktifkan</div>
                                    <div class="kxi-val">{{ $kontrak->diaktifkan_at->translatedFormat('d F Y H:i') }}</div>
                                </div>
                            </div>
                        @endif
                    </div>

                    {{-- Dokumen Surat Pesanan --}}
                    <div class="kxi-doc {{ $spArsip ? '' : 'kxi-doc-missing' }} mt-3">
                        <span class="kxi-doc-ic"><i class="bi bi-file-earmark-pdf-fill"></i></span>
                        <div class="kxi-body" style="min-width:0;">
                            <div class="kxi-lbl">Dokumen Surat Pesanan (PDF)</div>
                            @if($spArsip)
                                <div class="kxi-val text-truncate" title="{{ $spArsip->nama_file_asli }}">{{ $spArsip->nama_file_asli ?? 'surat-pesanan.pdf' }}</div>
                                <div class="kx-meta">
                                    <i class="bi bi-check-circle-fill text-success"></i> Terunggah
                                    @if($spUkuran) · {{ $spUkuran }} @endif
                                    @if($spArsip->uploaded_at) · {{ \Carbon\Carbon::parse($spArsip->uploaded_at)->translatedFormat('d M Y H:i') }} @endif
                                </div>
                            @else
                                <div class="kxi-val text-danger" style="font-size:.85rem;">Belum diunggah</div>
                                <div class="kx-meta">Unggah lewat menu Edit Kontrak.</div>
                            @endif
                        </div>
                        @if($spArsip)
                            <a href="{{ route('arsip.view', $spArsip) }}" target="_blank" rel="noopener" class="kxi-doc-btn">
                                <i class="bi bi-box-arrow-up-right"></i> Buka Dokumen
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Kolom kanan --}}
        <div class="col-lg-4">
            <div class="kx-card mb-4 kx-reveal" style="--d:.1s;">
                <div class="kx-card-head">
                    <span class="kx-ic" style="--tone:#059669; --tone2:#34d399;"><i class="bi bi-shop"></i></span>
                    <h6 class="kx-card-title">Penyedia</h6>
                </div>
                <div class="kx-card-body">
                    <div class="fw-bold mb-1">{{ $kontrak->vendor?->nama_pihak }}</div>
                    <div class="kx-meta mb-2">{{ $kontrak->vendor?->alamat }}</div>
                    <div class="kx-row"><span class="k">NPWP</span><span class="v">{{ $kontrak->vendor?->npwp ?? '-' }}</span></div>
                    @php $rek = $kontrak->vendor?->rekening?->firstWhere('is_default', true) ?? $kontrak->vendor?->rekening?->first(); @endphp
                    <div class="kx-row"><span class="k">Bank</span><span class="v">{{ $rek->nama_bank ?? '-' }}</span></div>
                    <div class="kx-row"><span class="k">No. Rekening</span><span class="v">{{ $rek->nomor_rekening ?? '-' }}</span></div>
                    <div class="kx-row"><span class="k">Atas Nama</span><span class="v">{{ $rek->nama_rekening ?? '-' }}</span></div>
                </div>
            </div>

            <div class="kx-card mb-4 kx-reveal" style="--d:.14s;">
                <div class="kx-card-head">
                    <span class="kx-ic" style="--tone:#d97706; --tone2:#fbbf24;"><i class="bi bi-pen"></i></span>
                    <div>
                        <h6 class="kx-card-title">Verifikator &amp; Penanda Tangan</h6>
                        <div class="kx-meta">Berlaku untuk seluruh tagihan termin kontrak ini.</div>
                    </div>
                </div>
                <div class="kx-card-body">
                    @foreach([
                        'PPK' => $kontrak->ppkUser,
                        'PPSPM' => $kontrak->ppspmUser,
                        'Koordinator Keuangan' => $kontrak->koordinatorKeuanganUser,
                        'Bendahara Pengeluaran' => $kontrak->bendaharaPengeluaranUser,
                        'Bendahara Penerimaan' => $kontrak->bendaharaPenerimaanUser,
                        'Kasubbag Keuangan & TU' => $kontrak->kasubbagUser,
                    ] as $label => $user)
                        <div class="kx-row">
                            <span class="k">{{ $label }}</span>
                            <span class="v">{{ $user?->name ?? '-' }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('script')
<script>
    // Salin nomor Surat Pesanan dari tile Informasi Kontrak.
    document.addEventListener('click', function (e) {
        var tile = e.target.closest('.kxi-copy[data-kxi-copy]');
        if (!tile) return;
        var text = tile.getAttribute('data-kxi-copy');
        var label = tile.querySelector('.kxi-copy-text');
        var asli = label ? label.textContent : '';

        function done() {
            tile.classList.add('kxi-copied');
            if (label) label.textContent = 'Tersalin!';
            setTimeout(function () {
                tile.classList.remove('kxi-copied');
                if (label) label.textContent = asli;
            }, 1400);
        }

        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(done);
        } else {
            var ta = document.createElement('textarea');
            ta.value = text; document.body.appendChild(ta);
            ta.select(); document.execCommand('copy'); ta.remove();
            done();
        }
    });
</script>
@endpush
