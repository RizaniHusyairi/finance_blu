{{-- peserta-accordion.blade.php --}}
{{-- Variables: $tagihan --}}
@php
    $tipeMap = [
        'luar_kota' => 'Luar Kota',
        'dalam_kota_lebih_8_jam' => 'Dalam Kota > 8 Jam',
        'diklat' => 'Diklat',
    ];
@endphp

<div class="card info-doc-card shadow-sm mb-4">
    <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-people text-primary me-2"></i>Daftar Peserta</h6>
        <div class="d-flex gap-2">
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-2 d-flex align-items-center gap-1">
                <i class="bi bi-person-fill" style="font-size: 0.7rem;"></i>
                {{ $tagihan->detailPerjaldin->count() }} Orang
            </span>
            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-2 font-mono-premium">
                Rp {{ number_format($tagihan->total_bruto, 0, ',', '.') }}
            </span>
        </div>
    </div>
    <div class="card-body py-3 px-3">
        @if($tagihan->detailPerjaldin->isEmpty())
            <div class="text-center text-muted py-5">
                <i class="bi bi-person-x display-5 d-block mb-3 text-secondary"></i>
                <p class="small mb-0">Belum ada peserta terdaftar.</p>
            </div>
        @else
            <div class="accordion accordion-flush" id="pesertaVerifikasiAccordion">
                @foreach($tagihan->detailPerjaldin as $idx => $detail)
                    @php
                        $sub = (float)($detail->biaya_tiket ?? 0)
                             + (float)($detail->biaya_transport ?? 0)
                             + (float)($detail->biaya_penginapan ?? 0)
                             + (float)($detail->uang_harian ?? 0)
                             + (float)($detail->uang_representasi ?? 0);
                        $namaLabel = $detail->nama_pegawai ?? ($detail->pegawai?->nama_lengkap ?? '-');
                        $nipLabel  = $detail->nip ?? ($detail->pegawai?->nip ?? null);
                        $tipeLabel = $tipeMap[$detail->tipe_perjalanan ?? ''] ?? ($detail->tipe_perjalanan ?? '-');
                        $hasFile   = !empty($detail->spt_file_path);
                    @endphp
                    <div class="peserta-accordion-item accordion-item overflow-hidden">
                        <h2 class="accordion-header">
                            <button class="accordion-button {{ $idx > 0 ? 'collapsed' : '' }} bg-white py-3 px-3 fw-semibold"
                                    type="button" data-bs-toggle="collapse"
                                    data-bs-target="#verPeserta{{ $idx }}">
                                <div class="d-flex align-items-center gap-3 w-100 me-3">
                                    {{-- Number Badge --}}
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-circle d-flex align-items-center justify-content-center fw-bold"
                                          style="width:30px;height:30px;font-size:0.75rem;flex-shrink:0;">{{ $idx+1 }}</span>
                                    {{-- Name & NIP --}}
                                    <div class="flex-fill" style="min-width: 0;">
                                        <span class="text-dark fw-semibold">{{ $namaLabel }}</span>
                                        @if($nipLabel)<span class="text-muted small ms-2 font-mono-premium" style="font-size: 0.72rem;">— {{ $nipLabel }}</span>@endif
                                    </div>
                                    {{-- Right Side Badges --}}
                                    <div class="d-none d-md-flex gap-2 align-items-center me-2 flex-shrink-0">
                                        @if($hasFile)
                                            <span class="badge bg-success-subtle text-success border border-success-subtle small rounded-pill px-2 py-1">
                                                <i class="bi bi-paperclip me-1"></i>Ada SPT
                                            </span>
                                        @else
                                            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle small rounded-pill px-2 py-1">
                                                <i class="bi bi-x-circle me-1"></i>Tanpa SPT
                                            </span>
                                        @endif
                                        <span class="fw-bold text-success font-mono-premium" style="font-size: 0.82rem;">Rp {{ number_format($sub, 0, ',', '.') }}</span>
                                    </div>
                                </div>
                            </button>
                        </h2>
                        <div id="verPeserta{{ $idx }}" class="accordion-collapse collapse {{ $idx === 0 ? 'show' : '' }}">
                            <div class="accordion-body pt-0 pb-4 px-3">
                                <hr class="mt-0 mb-3 opacity-10">
                                {{-- Detail Parameter Grid --}}
                                <div class="row g-3 mb-3">
                                    <div class="col-6 col-md-3">
                                        <div class="info-item-box">
                                            <div class="info-label"><i class="bi bi-file-earmark-text me-1"></i>No. SPT</div>
                                            <div class="info-value font-mono-premium small">{{ $detail->no_spt ?? '-' }}</div>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <div class="info-item-box">
                                            <div class="info-label"><i class="bi bi-file-earmark-ruled me-1"></i>No. SPPD</div>
                                            <div class="info-value font-mono-premium small">{{ $detail->no_sppd ?? '-' }}</div>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <div class="info-item-box">
                                            <div class="info-label"><i class="bi bi-geo-alt me-1"></i>Tujuan</div>
                                            <div class="info-value small">{{ $detail->tujuan ?? '-' }}</div>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <div class="info-item-box">
                                            <div class="info-label"><i class="bi bi-map me-1"></i>Provinsi / Tipe</div>
                                            <div class="info-value small">
                                                {{ $detail->provinsi?->provinsi ?? '-' }}
                                                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle rounded-pill ms-1" style="font-size: 0.62rem;">{{ $tipeLabel }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <div class="info-item-box">
                                            <div class="info-label"><i class="bi bi-calendar-event me-1"></i>Tgl Berangkat</div>
                                            <div class="info-value font-mono-premium small">{{ isset($detail->tgl_berangkat) ? \Carbon\Carbon::parse($detail->tgl_berangkat)->format('d M Y') : '-' }}</div>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <div class="info-item-box">
                                            <div class="info-label"><i class="bi bi-clock-history me-1"></i>Lama Hari</div>
                                            <div class="info-value font-mono-premium small">{{ $detail->lama_hari ?? 0 }} Hari</div>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <div class="info-item-box">
                                            <div class="info-label"><i class="bi bi-credit-card me-1"></i>Rekening</div>
                                            <div class="info-value font-mono-premium small">{{ $detail->rekening ?? '-' }}</div>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <div class="info-item-box">
                                            <div class="info-label"><i class="bi bi-paperclip me-1"></i>Lampiran SPT</div>
                                            @if($hasFile)
                                                <a href="{{ Storage::url($detail->spt_file_path) }}" target="_blank"
                                                   class="btn btn-sm btn-outline-success rounded-pill py-0 px-3 d-inline-flex align-items-center gap-1" style="font-size: 0.75rem;">
                                                    <i class="bi bi-eye"></i> Lihat
                                                </a>
                                            @else
                                                <span class="text-muted small">Tidak ada</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                {{-- Rincian Biaya Board --}}
                                <div class="biaya-board">
                                    <div class="small fw-bold text-muted mb-3 d-flex align-items-center gap-2">
                                        <i class="bi bi-cash-coin text-primary"></i>
                                        <span>Rincian Biaya</span>
                                        <a href="{{ route('perjaldins.pdf-perincian', [$tagihan->id, $detail->id]) }}" target="_blank"
                                           class="btn btn-sm btn-outline-primary rounded-pill py-0 px-3 ms-auto d-inline-flex align-items-center gap-1" style="font-size: 0.72rem;">
                                            <i class="bi bi-file-earmark-pdf"></i> PDF Perincian Biaya
                                        </a>
                                    </div>
                                    <div class="row g-2 align-items-end">
                                        @foreach([
                                            ['Tiket', $detail->biaya_tiket, 'bi-airplane', $detail->tiket_file_path, $detail->tiket_file_name],
                                            ['Transport', $detail->biaya_transport, 'bi-bus-front', $detail->transport_file_path, $detail->transport_file_name],
                                            ['Penginapan', $detail->biaya_penginapan, 'bi-building', $detail->penginapan_file_path, $detail->penginapan_file_name],
                                            ['Uang Harian', $detail->uang_harian, 'bi-wallet2', $detail->uang_harian_file_path, $detail->uang_harian_file_name],
                                            ['Representasi', $detail->uang_representasi, 'bi-briefcase', null, null],
                                        ] as [$bl, $bv, $bIcon, $bFile, $bFileName])
                                            <div class="col-6 col-md">
                                                <div class="text-muted d-flex align-items-center gap-1" style="font-size:.68rem;">
                                                    <i class="bi {{ $bIcon }}"></i> {{ $bl }}
                                                </div>
                                                <div class="fw-semibold font-mono-premium" style="font-size: 0.82rem;">Rp {{ number_format((float)($bv ?? 0), 0, ',', '.') }}</div>
                                                @if($bFile)
                                                    <a href="{{ Storage::url($bFile) }}" target="_blank"
                                                       class="d-inline-flex align-items-center gap-1 text-success text-decoration-none mt-1"
                                                       style="font-size: 0.68rem;" title="{{ $bFileName ?? 'Lampiran ' . $bl }}">
                                                        <i class="bi bi-paperclip"></i> Lihat Bukti
                                                    </a>
                                                @elseif((float)($bv ?? 0) > 0 && $bl !== 'Representasi')
                                                    <span class="d-inline-flex align-items-center gap-1 text-muted mt-1" style="font-size: 0.68rem;">
                                                        <i class="bi bi-dash-circle"></i> Tanpa bukti
                                                    </span>
                                                @endif
                                            </div>
                                        @endforeach
                                        <div class="col-6 col-md border-start border-3 border-success ps-3">
                                            <div class="text-muted d-flex align-items-center gap-1" style="font-size:.68rem;">
                                                <i class="bi bi-calculator"></i> Subtotal
                                            </div>
                                            <div class="fw-bold text-success font-mono-premium" style="font-size: 0.92rem;">Rp {{ number_format($sub, 0, ',', '.') }}</div>
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
