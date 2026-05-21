@extends('layouts.app')
@section('title', 'Dashboard Koordinator Jasa')

@section('content')
<style>
    @keyframes kjReveal { from { opacity: 0; transform: translateY(16px); } to { opacity: 1; transform: translateY(0); } }
    @keyframes kjSweep { 0% { transform: translateX(-120%) skewX(-18deg); opacity: 0; } 30% { opacity: .3; } 70%, 100% { transform: translateX(220%) skewX(-18deg); opacity: 0; } }
    @keyframes kjFloat { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-5px); } }
    .kj-hero {
        position: relative;
        overflow: hidden;
        border-radius: 18px;
        padding: 28px 30px;
        color: #fff;
        background: linear-gradient(120deg, #0f2f54 0%, #155c99 58%, #0f766e 100%);
        box-shadow: 0 18px 44px rgba(15, 47, 84, .20);
        animation: kjReveal .45s ease both;
    }
    .kj-hero::before {
        content: "";
        position: absolute;
        inset: 0;
        width: 42%;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,.24), transparent);
        animation: kjSweep 4.6s ease-in-out infinite;
    }
    .kj-hero::after {
        content: "";
        position: absolute;
        right: -80px;
        top: -90px;
        width: 360px;
        height: 230px;
        border-radius: 0 0 0 999px;
        border-left: 2px solid rgba(251, 191, 36, .35);
        border-bottom: 2px solid rgba(191, 219, 254, .22);
    }
    .kj-hero > * { position: relative; z-index: 1; }
    .kj-stat, .kj-panel {
        border: 1px solid rgba(148, 163, 184, .22);
        border-radius: 16px;
        background: #fff;
        box-shadow: 0 14px 34px rgba(15, 23, 42, .07);
        animation: kjReveal .5s ease both;
    }
    .kj-stat {
        overflow: hidden;
        position: relative;
    }
    .kj-stat::before {
        content: "";
        position: absolute;
        inset: 0 auto 0 0;
        width: 4px;
        background: var(--accent, #2563eb);
    }
    .kj-icon {
        width: 46px;
        height: 46px;
        border-radius: 14px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: var(--accent, #2563eb);
        background: var(--accent-bg, #eff6ff);
        animation: kjFloat 3.2s ease-in-out infinite;
    }
    .kj-panel .card-header {
        border-bottom: 1px solid #bfdbfe;
        background: linear-gradient(90deg, #eff6ff 0%, #f8fbff 58%, #fff 100%);
    }
    .kj-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        padding: 14px 0;
        border-bottom: 1px solid #e2e8f0;
    }
    .kj-row:last-child { border-bottom: 0; }
    .kj-empty {
        border: 1px dashed #cbd5e1;
        border-radius: 14px;
        padding: 26px;
        text-align: center;
        color: #64748b;
        background: #f8fafc;
    }
    .kj-total {
        border-radius: 999px;
        padding: 6px 12px;
        font-size: .78rem;
        font-weight: 800;
        color: #0f766e;
        background: #ccfbf1;
    }
</style>

@php
    $cards = [
        ['label' => 'Menunggu Verifikasi', 'value' => $koordinatorStats['menunggu_verifikasi'] ?? 0, 'icon' => 'bi-shield-exclamation', 'accent' => '#2563eb', 'bg' => '#eff6ff', 'note' => 'Tagihan yang perlu keputusan Anda'],
        ['label' => 'Total Tagihan', 'value' => $koordinatorStats['total_tagihan'] ?? 0, 'icon' => 'bi-receipt-cutoff', 'accent' => '#0891b2', 'bg' => '#e9faff', 'note' => 'Seluruh tagihan jasa tercatat'],
        ['label' => 'Sudah Diverifikasi', 'value' => $koordinatorStats['sudah_diverifikasi'] ?? 0, 'icon' => 'bi-check2-circle', 'accent' => '#16a34a', 'bg' => '#ecfdf3', 'note' => 'Keputusan terakhir oleh Koordinator'],
        ['label' => 'Jatuh Tempo', 'value' => $koordinatorStats['jatuh_tempo'] ?? 0, 'icon' => 'bi-calendar2-x', 'accent' => '#dc2626', 'bg' => '#fef2f2', 'note' => 'Perlu perhatian pembayaran'],
    ];
@endphp

<div class="kj-hero mb-4 d-flex flex-wrap align-items-center justify-content-between gap-3">
    <div>
        <div class="small text-white-50 fw-bold text-uppercase mb-1">Portal Koordinator Jasa</div>
        <h3 class="mb-1 fw-bold text-white">Dashboard Koordinator Jasa</h3>
        <p class="mb-0 text-white-50">Pantau antrean verifikasi, riwayat keputusan, dan status tagihan jasa.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('verifikasi-tagihan-jasa.index') }}" class="btn btn-light fw-bold text-primary shadow-sm">
            <i class="bi bi-shield-check me-1"></i> Verifikasi Tagihan Jasa
        </a>
        <a href="{{ route('admin-jasa.tagihan.log-bulanan') }}" class="btn btn-outline-light fw-bold">
            <i class="bi bi-journal-text me-1"></i> Log Tagihan
        </a>
    </div>
</div>

<div class="row g-3 mb-4">
    @foreach($cards as $card)
        <div class="col-sm-6 col-xl-3">
            <div class="kj-stat h-100 p-4" style="--accent: {{ $card['accent'] }}; --accent-bg: {{ $card['bg'] }};">
                <div class="d-flex justify-content-between align-items-start gap-3">
                    <div>
                        <div class="small text-muted fw-bold text-uppercase">{{ $card['label'] }}</div>
                        <div class="fs-3 fw-black fw-bold" style="color: {{ $card['accent'] }}">{{ number_format($card['value'], 0, ',', '.') }}</div>
                    </div>
                    <span class="kj-icon"><i class="bi {{ $card['icon'] }} fs-4"></i></span>
                </div>
                <div class="small text-muted mt-2">{{ $card['note'] }}</div>
            </div>
        </div>
    @endforeach
</div>

<div class="row g-4">
    <div class="col-xl-6">
        <div class="card kj-panel h-100 border-0">
            <div class="card-header p-3 fw-bold d-flex align-items-center justify-content-between">
                <span><i class="bi bi-list-task text-primary me-2"></i>To Do Verifikasi</span>
                <a href="{{ route('verifikasi-tagihan-jasa.index') }}" class="btn btn-sm btn-outline-primary fw-bold">Semua</a>
            </div>
            <div class="card-body">
                @forelse($pendingVerifikasiTagihans as $tagihan)
                    @php($mitra = $tagihan->mitra ?? $tagihan->mitraLegacy)
                    <div class="kj-row">
                        <div>
                            <div class="fw-bold text-primary">{{ $tagihan->nomor_tagihan }}</div>
                            <small class="text-muted">{{ $mitra->nama_pihak ?? '-' }} · {{ optional($tagihan->tanggal_tagihan)->format('d/m/Y') ?: '-' }}</small>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="kj-total">Rp {{ number_format((float) $tagihan->total_tagihan, 0, ',', '.') }}</span>
                            <a href="{{ route('verifikasi-tagihan-jasa.show', $tagihan->id) }}" class="btn btn-sm btn-primary" title="Verifikasi">
                                <i class="bi bi-check2-square"></i>
                            </a>
                        </div>
                    </div>
                @empty
                    <div class="kj-empty">
                        <i class="bi bi-check2-circle fs-2 d-block mb-2 text-success"></i>
                        Tidak ada tagihan jasa yang menunggu verifikasi.
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="col-xl-6">
        <div class="card kj-panel h-100 border-0">
            <div class="card-header p-3 fw-bold d-flex align-items-center justify-content-between">
                <span><i class="bi bi-check2-circle text-success me-2"></i>Sudah Diverifikasi</span>
                <span class="badge bg-success">{{ number_format($koordinatorStats['sudah_diverifikasi'] ?? 0, 0, ',', '.') }}</span>
            </div>
            <div class="card-body">
                @forelse($verifiedByKoordinatorRows as $verificationRow)
                    <div class="kj-row">
                        <div>
                            <div class="fw-bold {{ $verificationRow['text_class'] ?? 'text-success' }}">{{ $verificationRow['nomor_tagihan'] ?? '-' }}</div>
                            <small class="text-muted">{{ $verificationRow['mitra_nama'] ?? '-' }} · {{ $verificationRow['acted_at_label'] ?? '-' }}</small>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge {{ $verificationRow['status_class'] ?? 'bg-success' }}">{{ $verificationRow['status_label'] ?? 'Disetujui' }}</span>
                            <a href="{{ route('tagihan-jasa.show', $verificationRow['tagihan_id']) }}" class="btn btn-sm btn-outline-primary" title="Detail">
                                <i class="bi bi-eye"></i>
                            </a>
                        </div>
                    </div>
                @empty
                    <div class="kj-empty">
                        <i class="bi bi-clipboard-check fs-2 d-block mb-2 text-primary"></i>
                        Belum ada riwayat verifikasi oleh Koordinator Jasa.
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="card kj-panel border-0">
            <div class="card-header p-3 fw-bold d-flex align-items-center justify-content-between">
                <span><i class="bi bi-journal-text text-info me-2"></i>Tagihan Terbaru</span>
                <a href="{{ route('admin-jasa.tagihan.log-bulanan') }}" class="btn btn-sm btn-outline-primary fw-bold">Lihat Log</a>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    @forelse($recentTagihans as $tagihan)
                        @php($mitra = $tagihan->mitra ?? $tagihan->mitraLegacy)
                        <div class="col-md-6">
                            <div class="kj-row h-100">
                                <div>
                                    <div class="fw-bold">{{ $tagihan->nomor_tagihan }}</div>
                                    <small class="text-muted">{{ $mitra->nama_pihak ?? '-' }} · {{ str_replace('_', ' ', $tagihan->status) }}</small>
                                </div>
                                <a href="{{ route('tagihan-jasa.show', $tagihan->id) }}" class="btn btn-sm btn-outline-primary" title="Detail">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </div>
                        </div>
                    @empty
                        <div class="col-12">
                            <div class="kj-empty">Belum ada tagihan jasa.</div>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
