{{-- partial: peserta-list.blade.php --}}
{{-- Variables: $tagihan --}}
@php
    $tipeMap = [
        'luar_kota' => 'Luar Kota',
        'dalam_kota_lebih_8_jam' => 'Dalam Kota > 8 Jam',
        'diklat' => 'Diklat',
    ];
@endphp

<div class="modern-card">
    <div class="mc-head mc-icon-success">
        <div class="mc-head-left">
            <div class="mc-icon"><i class="bi bi-people-fill"></i></div>
            <div>
                <h6 class="mc-title">Daftar Peserta Perjalanan Dinas</h6>
                <p class="mc-sub">Klik kartu untuk melihat rincian biaya dan informasi peserta</p>
            </div>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <span class="mc-pill mc-pill-primary">
                <i class="bi bi-people-fill"></i> {{ $tagihan->detailPerjaldin->count() }} Peserta
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
                                    @if($detail->spt_file_path)
                                        <div class="pi-cell pi-attachment">
                                            <span class="pi-label" style="color: #be123c;"><i class="bi bi-paperclip"></i> Lampiran SPT</span>
                                            <a href="{{ Storage::url($detail->spt_file_path) }}" target="_blank" class="pi-link">
                                                <i class="bi bi-eye-fill me-1"></i>{{ \Illuminate\Support\Str::limit($detail->spt_file_name ?? 'Lihat', 18) }}
                                            </a>
                                        </div>
                                    @endif
                                </div>

                                {{-- Rincian Biaya --}}
                                <div class="biaya-card">
                                    <div class="biaya-title"><i class="bi bi-cash-coin"></i> Rincian Biaya</div>
                                    <div class="biaya-grid">
                                        @php
                                            $biayaItems = [
                                                ['Tiket', $detail->biaya_tiket],
                                                ['Transport', $detail->biaya_transport],
                                                ['Penginapan', $detail->biaya_penginapan],
                                                ['Uang Harian', $detail->uang_harian],
                                                ['Representasi', $detail->uang_representasi],
                                            ];
                                            if (!is_null($detail->uang_rapat ?? null) && (float)$detail->uang_rapat > 0) {
                                                $biayaItems[] = ['Rapat', $detail->uang_rapat];
                                            }
                                        @endphp
                                        @foreach($biayaItems as [$bl, $bv])
                                            <div class="biaya-cell">
                                                <span class="b-label">{{ $bl }}</span>
                                                <span class="b-value">Rp {{ number_format((float)($bv ?? 0), 0, ',', '.') }}</span>
                                            </div>
                                        @endforeach
                                        <div class="biaya-cell subtotal">
                                            <span class="b-label">Subtotal</span>
                                            <span class="b-value">Rp {{ number_format($sub, 0, ',', '.') }}</span>
                                        </div>
                                    </div>
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
