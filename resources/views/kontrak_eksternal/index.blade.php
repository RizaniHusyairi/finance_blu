@extends('layouts.app')
@section('title', 'Kontrak')

@push('css')
<style>
    :root { --ke-primary: #4f46e5; --ke-primary-2: #a855f7; }
    @keyframes keIn { from { opacity: 0; transform: translateY(14px); } to { opacity: 1; transform: translateY(0); } }
    .ke-reveal { opacity: 0; animation: keIn .5s cubic-bezier(.22, 1, .36, 1) forwards; animation-delay: var(--d, 0s); }

    .ke-hero {
        position: relative; overflow: hidden; border-radius: 1.25rem; padding: 1.6rem 1.8rem; color: #fff;
        background: linear-gradient(120deg, #312e81 0%, #4f46e5 45%, #a855f7 100%);
        box-shadow: 0 16px 36px -16px rgba(79, 70, 229, .55);
    }
    .ke-hero::before { content: ''; position: absolute; width: 260px; height: 260px; top: -130px; right: -60px; border-radius: 50%; background: rgba(255,255,255,.08); pointer-events: none; }
    .ke-hero-title { font-weight: 800; letter-spacing: -.5px; margin-bottom: .2rem; color: #fff !important; }
    .ke-hero-sub { color: rgba(255, 255, 255, .82); font-size: .87rem; max-width: 640px; }
    .ke-hero-chip { display: inline-flex; align-items: center; gap: .4rem; padding: .3rem .75rem; border-radius: 999px; background: rgba(255,255,255,.14); border: 1px solid rgba(255,255,255,.22); font-size: .72rem; font-weight: 700; }

    .ke-card {
        border: 1px solid #eef0f4; border-radius: 1.15rem; background: #fff; padding: 1.15rem 1.3rem;
        box-shadow: 0 2px 10px rgba(15, 23, 42, .04); transition: transform .2s ease, box-shadow .2s ease;
    }
    .ke-card:hover { transform: translateY(-2px); box-shadow: 0 12px 28px -14px rgba(79, 70, 229, .35); }
    .ke-status { display: inline-flex; align-items: center; gap: .35rem; padding: .28rem .7rem; border-radius: 999px; font-size: .68rem; font-weight: 800; letter-spacing: .04em; }
    .ke-status.DRAFT { background: #fef3c7; color: #92400e; }
    .ke-status.AKTIF { background: #d1fae5; color: #065f46; }
    .ke-status.SELESAI { background: #e0e7ff; color: #3730a3; }
    .ke-status.DIBATALKAN { background: #fee2e2; color: #991b1b; }
    .ke-serap-track { height: 8px; border-radius: 999px; background: #eef0f4; overflow: hidden; }
    .ke-serap-fill { height: 100%; border-radius: 999px; background: linear-gradient(90deg, #4f46e5, #a855f7); transition: width .6s cubic-bezier(.22, 1, .36, 1); }
    .ke-meta { font-size: .74rem; color: #94a3b8; font-weight: 600; }
    .ke-num { font-variant-numeric: tabular-nums; }
</style>
@endpush

@section('content')
<div class="page-content">
    <div class="ke-hero mb-4 ke-reveal" style="--d:.02s;">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 position-relative" style="z-index:1;">
            <div>
                <h4 class="ke-hero-title">Kontrak</h4>
                <div class="ke-hero-sub">
                    Master kontrak yang dibuat &amp; ditandatangani di luar sistem — daftarkan sekali beserta skema
                    terminnya, lalu tagih tiap termin dari halaman kontrak (pola Manajemen SPK).
                </div>
                <div class="d-flex flex-wrap gap-2 mt-2">
                    <span class="ke-hero-chip"><i class="bi bi-cart-check"></i> e-Purchasing / INAPROC</span>
                    <span class="ke-hero-chip"><i class="bi bi-list-ol"></i> Termin bertahap</span>
                    <span class="ke-hero-chip"><i class="bi bi-unlock"></i> Terbuka otomatis pasca-SP2D</span>
                </div>
            </div>
            <a href="{{ route('kontrak-eksternal.create') }}" class="btn btn-light rounded-3 fw-bold px-3 py-2">
                <i class="bi bi-plus-lg"></i> Daftarkan Kontrak
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success rounded-4 ke-reveal" style="--d:.04s;"><i class="bi bi-check-circle-fill me-1"></i> {{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger rounded-4 ke-reveal" style="--d:.04s;">
            @foreach($errors->all() as $err)<div><i class="bi bi-exclamation-triangle-fill me-1"></i> {{ $err }}</div>@endforeach
        </div>
    @endif

    @forelse($kontraks as $i => $kontrak)
        @php
            $totalTermin = $kontrak->termin->count();
            $tertagih = $kontrak->termin->where('status_termin', 'SUDAH_DITAGIH')->count();
            $serapan = $kontrak->persentase_serapan;
        @endphp
        <div class="ke-card mb-3 ke-reveal" style="--d:{{ .06 + $i * .04 }}s;">
            <div class="row g-3 align-items-center">
                <div class="col-lg-5">
                    <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                        <span class="ke-status {{ $kontrak->status_kontrak }}">{{ $kontrak->status_kontrak }}</span>
                        <span class="ke-meta"><i class="bi bi-hash"></i>{{ $kontrak->nomor_surat_pesanan }}</span>
                    </div>
                    <a href="{{ route('kontrak-eksternal.show', $kontrak->id) }}" class="fw-bold text-dark text-decoration-none d-block" style="font-size:.95rem;">
                        {{ $kontrak->nama_pekerjaan }}
                    </a>
                    <div class="ke-meta mt-1">
                        <i class="bi bi-shop"></i> {{ $kontrak->vendor?->nama_pihak ?? '-' }}
                        · <i class="bi bi-calendar3"></i> {{ optional($kontrak->tanggal_surat_pesanan)->format('d M Y') ?? '-' }}
                        · {{ $kontrak->metode_pembayaran === 'TERMIN' ? $totalTermin . ' termin' : 'Lumpsum' }}
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="ke-meta mb-1">Nilai Kontrak</div>
                    <div class="fw-bold ke-num" style="font-size:1.02rem;">Rp {{ number_format((float) $kontrak->nilai_total_kontrak, 0, ',', '.') }}</div>
                    @if((float) $kontrak->nilai_uang_muka > 0)
                        <div class="ke-meta">UM Rp {{ number_format((float) $kontrak->nilai_uang_muka, 0, ',', '.') }}</div>
                    @endif
                </div>
                <div class="col-lg-2 col-md-6">
                    <div class="d-flex justify-content-between ke-meta mb-1">
                        <span>Serapan {{ $tertagih }}/{{ $totalTermin }}</span><span>{{ number_format($serapan, 0) }}%</span>
                    </div>
                    <div class="ke-serap-track"><div class="ke-serap-fill" style="width: {{ min($serapan, 100) }}%;"></div></div>
                </div>
                <div class="col-lg-2 text-lg-end">
                    <a href="{{ route('kontrak-eksternal.show', $kontrak->id) }}" class="btn btn-sm btn-primary rounded-3 fw-bold">
                        <i class="bi bi-eye"></i> Detail
                    </a>
                    @if($kontrak->isEditable())
                        <a href="{{ route('kontrak-eksternal.edit', $kontrak->id) }}" class="btn btn-sm btn-light border rounded-3 fw-bold">
                            <i class="bi bi-pencil"></i>
                        </a>
                    @endif
                </div>
            </div>
        </div>
    @empty
        <div class="ke-card text-center py-5 ke-reveal" style="--d:.06s;">
            <div style="font-size:2.4rem;">📄</div>
            <div class="fw-bold mt-2">Belum ada kontrak</div>
            <div class="ke-meta mb-3">Daftarkan kontrak/Surat Pesanan pertama Anda — unggah PDF dan biarkan sistem mengisi formnya.</div>
            <a href="{{ route('kontrak-eksternal.create') }}" class="btn btn-primary rounded-3 fw-bold"><i class="bi bi-plus-lg"></i> Daftarkan Kontrak</a>
        </div>
    @endforelse
</div>
@endsection
