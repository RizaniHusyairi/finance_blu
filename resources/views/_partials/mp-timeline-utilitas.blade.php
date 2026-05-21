{{--
    Timeline Aktivitas untuk Laporan Utilitas (listrik / air).
    Required: $laporan (LaporanUtilitas)
    Optional: $tanggalWaktu closure, $tagihanRoute (default 'tagihan-jasa.show')
--}}
@php
    $tanggalWaktu = $tanggalWaktu ?? function ($v) {
        return $v ? \Carbon\Carbon::parse($v)->translatedFormat('d M Y H:i') : '-';
    };
    $tagihanRoute = $tagihanRoute ?? 'tagihan-jasa.show';
    $statusUtilitas = $laporan->status ?? null;
@endphp

<div class="card border-0 rounded-4 mb-4 mp-timeline-card">
    <div class="mp-timeline-header">
        <span class="mp-timeline-header-icon"><i class="bi bi-activity"></i></span>
        <div>
            <h6 class="mp-timeline-header-title">Timeline Aktivitas</h6>
            <div class="mp-timeline-header-subtitle">Riwayat proses laporan utilitas sampai tagihan.</div>
        </div>
    </div>
    <div class="mp-timeline">
        {{-- Dibuat oleh pencatat (Admin Listrik / Admin Air) --}}
        <div class="mp-timeline-item">
            <div class="mp-timeline-dot secondary">
                <i class="bi bi-file-earmark-plus"></i>
            </div>
            <div class="mp-timeline-content">
                <div class="mp-timeline-title">Laporan Pemakaian Dibuat</div>
                <div class="mp-timeline-time"><i class="bi bi-calendar2-check text-primary"></i>{{ $tanggalWaktu($laporan->created_at) }}</div>
                <div class="mp-timeline-note">Pencatatan stan meter awal & akhir tersimpan sebagai draft.</div>
                @if($laporan->createdByUser)
                    <div class="mp-timeline-note">oleh {{ $laporan->createdByUser->name ?? $laporan->createdByUser->email }}</div>
                @endif
            </div>
        </div>

        {{-- Dikirim ke Admin Jasa --}}
        @if(in_array($statusUtilitas, ['dikirim_ke_admin_jasa', 'ditagihkan', 'ditolak']))
            <div class="mp-timeline-item">
                <div class="mp-timeline-dot warning">
                    <i class="bi bi-send"></i>
                </div>
                <div class="mp-timeline-content">
                    <div class="mp-timeline-title">Dikirim ke Admin Jasa</div>
                    <div class="mp-timeline-time"><i class="bi bi-clock text-warning"></i>{{ $tanggalWaktu($laporan->updated_at) }}</div>
                    <div class="mp-timeline-note">Laporan diteruskan untuk diverifikasi dan ditarifkan.</div>
                </div>
            </div>
        @endif

        {{-- Ditolak --}}
        @if($statusUtilitas === 'ditolak')
            <div class="mp-timeline-item">
                <div class="mp-timeline-dot danger">
                    <i class="bi bi-x-lg"></i>
                </div>
                <div class="mp-timeline-content">
                    <div class="mp-timeline-title">Laporan Ditolak</div>
                    <div class="mp-timeline-time">
                        <i class="bi bi-exclamation-circle text-danger"></i>
                        {{ $tanggalWaktu($laporan->updated_at) }}
                    </div>
                    @if($laporan->catatan_admin_jasa)
                        <div class="mp-timeline-note text-danger">Catatan: {{ $laporan->catatan_admin_jasa }}</div>
                    @endif
                </div>
            </div>
        @endif

        {{-- Ditagihkan --}}
        @if($laporan->tagihan_jasa_id && $laporan->tagihanJasa)
            <div class="mp-timeline-item">
                <div class="mp-timeline-dot primary">
                    <i class="bi bi-receipt"></i>
                </div>
                <div class="mp-timeline-content">
                    <div class="mp-timeline-title">Tagihan Diterbitkan</div>
                    <div class="mp-timeline-time"><i class="bi bi-calendar2-check text-primary"></i>{{ $tanggalWaktu($laporan->tagihanJasa->created_at) }}</div>
                    <div class="mp-timeline-note">Tagihan resmi terbit berdasarkan laporan ini.</div>
                    @if(\Illuminate\Support\Facades\Route::has($tagihanRoute))
                        <a href="{{ route($tagihanRoute, $laporan->tagihanJasa) }}" class="btn btn-sm btn-outline-primary mp-timeline-link">
                            <i class="bi bi-box-arrow-up-right"></i>{{ $laporan->tagihanJasa->nomor_tagihan ?? 'Lihat Tagihan' }}
                        </a>
                    @endif
                </div>
            </div>
        @endif
    </div>
</div>
