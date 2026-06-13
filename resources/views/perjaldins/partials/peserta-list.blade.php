{{-- partial: peserta-list.blade.php --}}
{{-- Variables: $tagihan --}}
@php
    $tipeMap = [
        'luar_kota' => 'Luar Kota',
        'dalam_kota_lebih_8_jam' => 'Dalam Kota > 8 Jam',
        'diklat' => 'Diklat',
    ];

    // Jenis bukti dukung yang bisa diunggah tiap peserta (dipakai di body kartu).
    $buktiTypes = [
        ['key' => 'spt',         'label' => 'SPT',         'icon' => 'bi-file-earmark-text-fill', 'accent' => '#6366f1', 'shadow' => 'rgba(99,102,241,.30)'],
        ['key' => 'tiket',       'label' => 'Tiket',       'icon' => 'bi-ticket-detailed-fill',   'accent' => '#0ea5e9', 'shadow' => 'rgba(14,165,233,.30)'],
        ['key' => 'transport',   'label' => 'Transport',   'icon' => 'bi-car-front-fill',         'accent' => '#f59e0b', 'shadow' => 'rgba(245,158,11,.30)'],
        ['key' => 'penginapan',  'label' => 'Penginapan',  'icon' => 'bi-building-fill',           'accent' => '#8b5cf6', 'shadow' => 'rgba(139,92,246,.30)'],
        ['key' => 'uang_harian', 'label' => 'Uang Harian', 'icon' => 'bi-wallet-fill',            'accent' => '#10b981', 'shadow' => 'rgba(16,185,129,.30)'],
    ];

    $totalBuktiSemua = 0;
    foreach ($tagihan->detailPerjaldin as $d) {
        foreach ($buktiTypes as $bt) {
            if ($d->{$bt['key'] . '_file_path'}) $totalBuktiSemua++;
        }
    }
@endphp

<div class="modern-card">
    <div class="mc-head mc-icon-success">
        <div class="mc-head-left">
            <div class="mc-icon"><i class="bi bi-people-fill"></i></div>
            <div>
                <h6 class="mc-title">Daftar Peserta &amp; Bukti Dukung</h6>
                <p class="mc-sub">Klik kartu untuk melihat rincian biaya, informasi peserta, dan berkas bukti</p>
            </div>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <span class="mc-pill mc-pill-primary">
                <i class="bi bi-people-fill"></i> {{ $tagihan->detailPerjaldin->count() }} Peserta
            </span>
            <span class="mc-pill mc-pill-info">
                <i class="bi bi-paperclip"></i> {{ $totalBuktiSemua }} Berkas Bukti
            </span>
            <span class="mc-pill mc-pill-success">
                <i class="bi bi-cash-stack"></i> Rp {{ number_format($tagihan->total_bruto, 0, ',', '.') }}
            </span>
        </div>
    </div>
    <div class="mc-body">
        @if($tagihan->detailPerjaldin->isEmpty())
            <div class="empty-state-modern">
                <i class="bi bi-person-x"></i>
                <h6 class="text-secondary fw-bold mb-1">Belum ada peserta terdaftar</h6>
                <small>Tambahkan peserta perjalanan dinas melalui menu Edit Dokumen.</small>
            </div>
        @else
            <div class="peserta-acc">
                @foreach($tagihan->detailPerjaldin as $idx => $detail)
                    @php
                        $sub = (float)($detail->biaya_tiket ?? 0)
                             + (float)($detail->biaya_transport ?? 0)
                             + (float)($detail->biaya_penginapan ?? 0)
                             + (float)($detail->uang_harian ?? 0)
                             + (float)($detail->uang_representasi ?? 0)
                             + (float)($detail->uang_rapat ?? 0);
                        $namaLabel = $detail->nama_pegawai ?? ($detail->pegawai->nama_lengkap ?? '-');
                        $nipLabel = $detail->nip ?? ($detail->pegawai->nip ?? null);
                        $tipeLabel = $tipeMap[$detail->tipe_perjalanan ?? ''] ?? ($detail->tipe_perjalanan ?? '-');
                    @endphp
                    <div class="peserta-item {{ $idx === 0 ? 'is-open' : '' }}">
                        <button type="button" class="peserta-toggle">
                            <span class="peserta-num">{{ $idx + 1 }}</span>
                            <div class="peserta-info">
                                <p class="peserta-name">{{ $namaLabel }}</p>
                                <span class="peserta-meta">
                                    @if($nipLabel)<i class="bi bi-card-text"></i> {{ $nipLabel }}@endif
                                </span>
                            </div>
                            <div class="peserta-quick-stats">
                                <span class="pq-pill">
                                    <i class="bi bi-geo-alt-fill"></i> {{ \Illuminate\Support\Str::limit($detail->provinsi?->provinsi ?? '-', 18) }}
                                </span>
                                <span class="pq-pill">
                                    <i class="bi bi-calendar3"></i> {{ isset($detail->tgl_berangkat) ? \Carbon\Carbon::parse($detail->tgl_berangkat)->format('d M Y') : '-' }}
                                </span>
                                <span class="pq-pill">
                                    <i class="bi bi-hourglass-split"></i> {{ $detail->lama_hari ?? 0 }} hr
                                </span>
                                <span class="pq-pill pq-money">
                                    Rp {{ number_format($sub, 0, ',', '.') }}
                                </span>
                            </div>
                            <i class="bi bi-chevron-down peserta-chevron"></i>
                        </button>
                        <div class="peserta-body">
                            <div class="peserta-body-inner">
                                <div class="peserta-info-grid">
                                    <div class="pi-cell">
                                        <span class="pi-label"><i class="bi bi-receipt"></i> No. SPT</span>
                                        <span class="pi-value">{{ $detail->no_spt ?? '-' }}</span>
                                    </div>
                                    <div class="pi-cell">
                                        <span class="pi-label"><i class="bi bi-receipt-cutoff"></i> No. SPPD</span>
                                        <span class="pi-value">{{ $detail->no_sppd ?? '-' }}</span>
                                    </div>
                                    <div class="pi-cell">
                                        <span class="pi-label"><i class="bi bi-geo-fill"></i> Tujuan</span>
                                        <span class="pi-value">{{ $detail->tujuan ?? '-' }}</span>
                                    </div>
                                    <div class="pi-cell">
                                        <span class="pi-label"><i class="bi bi-credit-card-fill"></i> Rekening</span>
                                        <span class="pi-value">{{ $detail->rekening ?? '-' }}</span>
                                    </div>
                                    <div class="pi-cell">
                                        <span class="pi-label"><i class="bi bi-map-fill"></i> Provinsi</span>
                                        <span class="pi-value">
                                            {{ $detail->provinsi?->provinsi ?? '-' }}
                                            @if($tipeLabel !== '-')<small class="text-muted d-block" style="font-size: .7rem;">{{ $tipeLabel }}</small>@endif
                                        </span>
                                    </div>
                                    <div class="pi-cell">
                                        <span class="pi-label"><i class="bi bi-calendar-event-fill"></i> Tgl Berangkat</span>
                                        <span class="pi-value">{{ isset($detail->tgl_berangkat) ? \Carbon\Carbon::parse($detail->tgl_berangkat)->translatedFormat('d M Y') : '-' }}</span>
                                    </div>
                                    <div class="pi-cell">
                                        <span class="pi-label"><i class="bi bi-hourglass-split"></i> Lama Hari</span>
                                        <span class="pi-value">{{ $detail->lama_hari ?? 0 }} Hari</span>
                                    </div>
                                </div>

                                {{-- Rincian Biaya — struktur selaras dengan form tambah perjaldin --}}
                                @php
                                    $uhTotal = (float)($detail->uang_harian ?? 0)
                                             + (float)($detail->uang_representasi ?? 0)
                                             + (float)($detail->uang_rapat ?? 0);
                                @endphp
                                <div class="biaya-card">
                                    <div class="biaya-title d-flex align-items-center flex-wrap gap-2">
                                        <span><i class="bi bi-cash-coin"></i> Rincian Biaya</span>
                                        <a href="{{ route('perjaldins.pdf-perincian', [$tagihan->id, $detail->id]) }}" target="_blank"
                                           class="btn btn-sm btn-outline-primary rounded-pill py-0 px-3 ms-auto d-inline-flex align-items-center gap-1"
                                           style="font-size: 0.72rem;">
                                            <i class="bi bi-file-earmark-pdf"></i> PDF Perincian Biaya
                                        </a>
                                    </div>
                                    <div class="biaya-layout">
                                        <div class="biaya-cell">
                                            <span class="b-label">Tiket</span>
                                            <span class="b-value">Rp {{ number_format((float)($detail->biaya_tiket ?? 0), 0, ',', '.') }}</span>
                                        </div>
                                        <div class="biaya-cell">
                                            <span class="b-label">Transport</span>
                                            <span class="b-value">Rp {{ number_format((float)($detail->biaya_transport ?? 0), 0, ',', '.') }}</span>
                                        </div>
                                        <div class="biaya-cell">
                                            <span class="b-label">Penginapan</span>
                                            <span class="b-value">Rp {{ number_format((float)($detail->biaya_penginapan ?? 0), 0, ',', '.') }}</span>
                                        </div>

                                        {{-- Grup Uang Harian (Harian + Representasi + Rapat) --}}
                                        <div class="uh-group">
                                            <div class="uh-group-head">
                                                <span class="uh-group-title"><i class="bi bi-wallet2"></i> Uang Harian</span>
                                                <span class="uh-group-total">Rp {{ number_format($uhTotal, 0, ',', '.') }}</span>
                                            </div>
                                            <div class="uh-group-grid">
                                                <div class="uh-sub">
                                                    <span class="b-label">Harian</span>
                                                    <span class="b-value">Rp {{ number_format((float)($detail->uang_harian ?? 0), 0, ',', '.') }}</span>
                                                </div>
                                                <div class="uh-sub">
                                                    <span class="b-label">+ Representasi</span>
                                                    <span class="b-value">Rp {{ number_format((float)($detail->uang_representasi ?? 0), 0, ',', '.') }}</span>
                                                </div>
                                                <div class="uh-sub">
                                                    <span class="b-label">+ Rapat</span>
                                                    <span class="b-value">Rp {{ number_format((float)($detail->uang_rapat ?? 0), 0, ',', '.') }}</span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="biaya-cell subtotal">
                                            <span class="b-label">Subtotal</span>
                                            <span class="b-value">Rp {{ number_format($sub, 0, ',', '.') }}</span>
                                        </div>
                                    </div>
                                </div>

                                {{-- Bukti Dukung peserta --}}
                                @php
                                    $pesertaFiles = [];
                                    foreach ($buktiTypes as $bt) {
                                        $path = $detail->{$bt['key'] . '_file_path'} ?? null;
                                        if ($path) {
                                            $pesertaFiles[] = $bt + [
                                                'path' => $path,
                                                'name' => $detail->{$bt['key'] . '_file_name'} ?? basename($path),
                                            ];
                                        }
                                    }
                                @endphp
                                <div class="peserta-bukti">
                                    <div class="biaya-title"><i class="bi bi-paperclip"></i> Bukti Dukung</div>
                                    @if(!empty($pesertaFiles))
                                        <div class="bukti-files">
                                            @foreach($pesertaFiles as $f)
                                                @php $ext = strtoupper(pathinfo($f['name'], PATHINFO_EXTENSION) ?: pathinfo($f['path'], PATHINFO_EXTENSION)); @endphp
                                                <a href="{{ Storage::url($f['path']) }}" target="_blank" rel="noopener"
                                                   class="bukti-file"
                                                   style="--bf-accent: {{ $f['accent'] }}; --bf-shadow: {{ $f['shadow'] }};"
                                                   title="{{ $f['name'] }}">
                                                    <span class="bf-icon"><i class="bi {{ $f['icon'] }}"></i></span>
                                                    <span class="bf-meta">
                                                        <span class="bf-type">{{ $f['label'] }}</span>
                                                        <span class="bf-name">{{ $f['name'] }}</span>
                                                        @if($ext)<span class="bf-ext">{{ $ext }}</span>@endif
                                                    </span>
                                                    <i class="bi bi-box-arrow-up-right bf-view"></i>
                                                </a>
                                            @endforeach
                                        </div>
                                    @else
                                        <div class="peserta-bukti-empty">
                                            <i class="bi bi-folder2-open me-1"></i> Peserta ini belum mengunggah berkas bukti.
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>

<script>
(function () {
    document.querySelectorAll('.peserta-acc .peserta-toggle').forEach(btn => {
        btn.addEventListener('click', function () {
            const item = this.closest('.peserta-item');
            item.classList.toggle('is-open');
        });
    });
})();
</script>
