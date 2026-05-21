{{--
    Timeline Aktivitas untuk Laporan Penjualan/Konsesi/PJP2U.
    Required: $penjualan (MitraJasaPenjualan)
    Optional helper: $tanggalWaktu (closure formatting datetime). If not given, fallback Carbon format.
    Optional: $tagihanRoute (route name) e.g. 'mitra.tagihan-jasa.show' or 'tagihan-jasa.show'. Default 'tagihan-jasa.show'.
--}}
@php
    $tanggalWaktu = $tanggalWaktu ?? function ($v) {
        return $v ? \Carbon\Carbon::parse($v)->translatedFormat('d M Y H:i') : '-';
    };
    $tagihanRoute = $tagihanRoute ?? 'tagihan-jasa.show';
@endphp

<div class="card border-0 rounded-4 mb-4 mp-timeline-card">
    <div class="mp-timeline-header">
        <span class="mp-timeline-header-icon"><i class="bi bi-activity"></i></span>
        <div>
            <h6 class="mp-timeline-header-title">Timeline Aktivitas</h6>
            <div class="mp-timeline-header-subtitle">Riwayat proses laporan sampai tagihan.</div>
        </div>
    </div>
    <div class="mp-timeline">
        {{-- Dibuat --}}
        <div class="mp-timeline-item">
            <div class="mp-timeline-dot secondary">
                <i class="bi bi-file-earmark-plus"></i>
            </div>
            <div class="mp-timeline-content">
                <div class="mp-timeline-title">Laporan Dibuat</div>
                <div class="mp-timeline-time"><i class="bi bi-calendar2-check text-primary"></i>{{ $tanggalWaktu($penjualan->created_at) }}</div>
                <div class="mp-timeline-note">Draft awal laporan berhasil tercatat di sistem.</div>
                @if($penjualan->createdByUser)
                    <div class="mp-timeline-note">oleh {{ $penjualan->createdByUser->name ?? $penjualan->createdByUser->email }}</div>
                @endif
            </div>
        </div>

        {{-- Diajukan --}}
        @if($penjualan->submitted_at)
            <div class="mp-timeline-item">
                <div class="mp-timeline-dot warning">
                    <i class="bi bi-send"></i>
                </div>
                <div class="mp-timeline-content">
                    <div class="mp-timeline-title">Diajukan untuk Verifikasi</div>
                    <div class="mp-timeline-time"><i class="bi bi-clock text-warning"></i>{{ $tanggalWaktu($penjualan->submitted_at) }}</div>
                    <div class="mp-timeline-note">Laporan sudah dikirim dan menunggu pemeriksaan admin.</div>
                </div>
            </div>
        @endif

        {{-- Diverifikasi / Ditolak --}}
        @if($penjualan->verified_at)
            <div class="mp-timeline-item">
                <div class="mp-timeline-dot {{ $penjualan->status === 'ditolak' ? 'danger' : 'success' }}">
                    <i class="bi {{ $penjualan->status === 'ditolak' ? 'bi-x-lg' : 'bi-check2' }}"></i>
                </div>
                <div class="mp-timeline-content">
                    <div class="mp-timeline-title">{{ $penjualan->status === 'ditolak' ? 'Laporan Ditolak' : 'Laporan Diverifikasi' }}</div>
                    <div class="mp-timeline-time">
                        <i class="bi {{ $penjualan->status === 'ditolak' ? 'bi-exclamation-circle text-danger' : 'bi-patch-check text-success' }}"></i>
                        {{ $tanggalWaktu($penjualan->verified_at) }}
                    </div>
                    @if($penjualan->verifiedByUser)
                        <div class="mp-timeline-note">oleh {{ $penjualan->verifiedByUser->name ?? $penjualan->verifiedByUser->email }}</div>
                    @endif
                    @if($penjualan->status === 'ditolak' && $penjualan->catatan_verifikator)
                        <div class="mp-timeline-note text-danger">Catatan: {{ $penjualan->catatan_verifikator }}</div>
                    @endif
                </div>
            </div>
        @endif

        {{-- Ditagihkan --}}
        @if($penjualan->tagihan_jasa_id && $penjualan->tagihanJasa)
            <div class="mp-timeline-item">
                <div class="mp-timeline-dot primary">
                    <i class="bi bi-receipt"></i>
                </div>
                <div class="mp-timeline-content">
                    <div class="mp-timeline-title">Tagihan Dibuat</div>
                    <div class="mp-timeline-time"><i class="bi bi-calendar2-check text-primary"></i>{{ $tanggalWaktu($penjualan->tagihanJasa->created_at) }}</div>
                    <div class="mp-timeline-note">Tagihan telah diterbitkan berdasarkan laporan ini.</div>
                    @if(\Illuminate\Support\Facades\Route::has($tagihanRoute))
                        <a href="{{ route($tagihanRoute, $penjualan->tagihanJasa) }}" class="btn btn-sm btn-outline-primary mp-timeline-link">
                            <i class="bi bi-box-arrow-up-right"></i>{{ $penjualan->tagihanJasa->nomor_tagihan ?? 'Lihat Tagihan' }}
                        </a>
                    @endif
                </div>
            </div>
        @endif
    </div>
</div>
