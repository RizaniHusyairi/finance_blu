@php
    $totalPeserta = $tagihan->detailPerjaldin->count();
@endphp

<div class="msp-card mb-4" style="--d: .28s;">
    <div class="msp-card-head">
        <div class="mc-head-left">
            <span class="mc-icon mc-icon-info"><i class="bi bi-people-fill"></i></span>
            <div>
                <h5 class="mc-title">Peserta Perjalanan Dinas</h5>
                <p class="mc-sub">Rincian peserta beserta tujuan & komponen biaya masing-masing.</p>
            </div>
        </div>
        <span class="mc-pill mc-pill-info">{{ $totalPeserta }} Orang</span>
    </div>
    <div class="msp-card-body">
        @if($totalPeserta === 0)
            <div class="text-center text-muted py-3"><i class="bi bi-inboxes me-1"></i> Belum ada data peserta.</div>
        @else
        <div class="peserta-list">
            @foreach($tagihan->detailPerjaldin as $i => $peserta)
                @php
                    $subtotal = $peserta->biaya_tiket + $peserta->biaya_transport + $peserta->biaya_penginapan + $peserta->uang_harian + $peserta->uang_representasi + ($peserta->uang_rapat ?? 0);
                @endphp
                <div class="peserta-item" style="--i: {{ $i }};">
                    <button type="button" class="peserta-toggle" onclick="this.closest('.peserta-item').classList.toggle('is-open')">
                        <span class="peserta-num">{{ $i + 1 }}</span>
                        <span class="peserta-info">
                            <span class="peserta-name">{{ $peserta->nama_pegawai }}</span>
                            <span class="peserta-meta">
                                <i class="bi bi-person-vcard me-1"></i>{{ $peserta->nip ?: 'NIP -' }}
                                <span class="mx-2">&middot;</span>
                                <i class="bi bi-geo-alt me-1"></i>{{ $peserta->tujuan }}
                            </span>
                        </span>
                        <span class="peserta-quick">
                            <span class="pq-pill"><i class="bi bi-clock-history"></i>{{ $peserta->lama_hari }} Hari</span>
                            <span class="pq-pill pq-money">Rp {{ number_format($subtotal, 0, ',', '.') }}</span>
                        </span>
                        <i class="bi bi-chevron-down peserta-chevron"></i>
                    </button>
                    <div class="peserta-body">
                        <div class="peserta-body-inner">
                            <div class="pi-grid">
                                <div class="pi-cell"><span class="pi-label"><i class="bi bi-signpost-split"></i>Tipe Perjalanan</span><span class="pi-value">{{ ucwords(str_replace('_', ' ', $peserta->tipe_perjalanan ?? '-')) }}</span></div>
                                <div class="pi-cell"><span class="pi-label"><i class="bi bi-file-earmark-text"></i>No. SPT</span><span class="pi-value">{{ $peserta->no_spt ?: '-' }}</span></div>
                                <div class="pi-cell"><span class="pi-label"><i class="bi bi-file-earmark-ruled"></i>No. SPPD</span><span class="pi-value">{{ $peserta->no_sppd ?: '-' }}</span></div>
                                <div class="pi-cell"><span class="pi-label"><i class="bi bi-calendar-event"></i>Tgl Berangkat</span><span class="pi-value">{{ \Carbon\Carbon::parse($peserta->tgl_berangkat)->translatedFormat('d M Y') }}</span></div>
                                <div class="pi-cell"><span class="pi-label"><i class="bi bi-hourglass"></i>Lama</span><span class="pi-value">{{ $peserta->lama_hari }} Hari</span></div>
                                <div class="pi-cell"><span class="pi-label"><i class="bi bi-bank"></i>Rekening</span><span class="pi-value">{{ $peserta->rekening ?: '-' }}</span></div>
                            </div>

                            <div class="biaya-card mt-3">
                                <div class="biaya-title"><i class="bi bi-cash-stack"></i> Rincian Biaya</div>
                                <div class="biaya-grid">
                                    <div class="biaya-cell"><span class="b-label">Tiket</span><span class="b-value">Rp {{ number_format($peserta->biaya_tiket, 0, ',', '.') }}</span></div>
                                    <div class="biaya-cell"><span class="b-label">Transport</span><span class="b-value">Rp {{ number_format($peserta->biaya_transport, 0, ',', '.') }}</span></div>
                                    <div class="biaya-cell"><span class="b-label">Penginapan</span><span class="b-value">Rp {{ number_format($peserta->biaya_penginapan, 0, ',', '.') }}</span></div>
                                    <div class="biaya-cell"><span class="b-label">Uang Harian</span><span class="b-value">Rp {{ number_format($peserta->uang_harian, 0, ',', '.') }}</span></div>
                                    <div class="biaya-cell"><span class="b-label">Representasi</span><span class="b-value">Rp {{ number_format($peserta->uang_representasi, 0, ',', '.') }}</span></div>
                                    @if(($peserta->uang_rapat ?? 0) > 0)
                                    <div class="biaya-cell"><span class="b-label">Uang Rapat</span><span class="b-value">Rp {{ number_format($peserta->uang_rapat, 0, ',', '.') }}</span></div>
                                    @endif
                                    <div class="biaya-cell subtotal"><span class="b-label">Subtotal</span><span class="b-value">Rp {{ number_format($subtotal, 0, ',', '.') }}</span></div>
                                </div>
                            </div>

                            @php
                                $buktiFiles = [
                                    ['label' => 'SPT',         'icon' => 'bi-file-earmark-text', 'path' => $peserta->spt_file_path,         'name' => $peserta->spt_file_name],
                                    ['label' => 'Tiket',       'icon' => 'bi-airplane',          'path' => $peserta->tiket_file_path,       'name' => $peserta->tiket_file_name],
                                    ['label' => 'Transport',   'icon' => 'bi-bus-front',         'path' => $peserta->transport_file_path,   'name' => $peserta->transport_file_name],
                                    ['label' => 'Penginapan',  'icon' => 'bi-building',          'path' => $peserta->penginapan_file_path,  'name' => $peserta->penginapan_file_name],
                                    ['label' => 'Uang Harian', 'icon' => 'bi-wallet2',           'path' => $peserta->uang_harian_file_path, 'name' => $peserta->uang_harian_file_name],
                                ];
                                $jmlBukti = collect($buktiFiles)->whereNotNull('path')->count();
                            @endphp
                            <div class="bukti-card mt-3">
                                <div class="biaya-title">
                                    <i class="bi bi-paperclip"></i> Bukti Dukung
                                    <span class="bukti-count">{{ $jmlBukti }}/{{ count($buktiFiles) }} berkas</span>
                                </div>
                                <div class="bukti-grid">
                                    @foreach($buktiFiles as $bf)
                                        <div class="bukti-cell {{ $bf['path'] ? 'has-file' : 'no-file' }}">
                                            <span class="bukti-ic"><i class="bi {{ $bf['icon'] }}"></i></span>
                                            <div class="bukti-text">
                                                <span class="bukti-label">{{ $bf['label'] }}</span>
                                                <span class="bukti-name" title="{{ $bf['name'] ?: 'Tidak ada berkas' }}">{{ $bf['name'] ?: 'Tidak ada berkas' }}</span>
                                            </div>
                                            @if($bf['path'])
                                                <a href="{{ \Illuminate\Support\Facades\Storage::url($bf['path']) }}" target="_blank" class="bukti-link" title="Lihat / unduh {{ $bf['label'] }}">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                            @else
                                                <span class="bukti-none"><i class="bi bi-dash-lg"></i></span>
                                            @endif
                                        </div>
                                    @endforeach
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
