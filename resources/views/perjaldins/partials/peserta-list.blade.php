{{-- partial: peserta-list.blade.php --}}
{{-- Variables: $tagihan --}}
@php
    $tipeMap = [
        'luar_kota' => 'Luar Kota',
        'dalam_kota_lebih_8_jam' => 'Dalam Kota > 8 Jam',
        'diklat' => 'Diklat',
    ];
@endphp

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-bold text-dark">
            <i class="bi bi-people text-primary me-2"></i>Daftar Peserta Perjalanan Dinas
        </h6>
        <div class="d-flex gap-2 align-items-center">
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3">
                {{ $tagihan->detailPerjaldin->count() }} Peserta
            </span>
            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3">
                Rp {{ number_format($tagihan->total_bruto, 0, ',', '.') }}
            </span>
        </div>
    </div>
    <div class="card-body py-3">
        @if($tagihan->detailPerjaldin->isEmpty())
            <div class="text-center text-muted py-5">
                <i class="bi bi-person-x display-6 d-block mb-2"></i>
                <small>Belum ada peserta terdaftar.</small>
            </div>
        @else
            <div class="accordion accordion-flush" id="pesertaAccordion">
                @foreach($tagihan->detailPerjaldin as $idx => $detail)
                    @php
                        $sub = (float)($detail->biaya_tiket ?? 0)
                             + (float)($detail->biaya_transport ?? 0)
                             + (float)($detail->biaya_penginapan ?? 0)
                             + (float)($detail->uang_harian ?? 0)
                             + (float)($detail->uang_representasi ?? 0);
                        $namaLabel = $detail->nama_pegawai ?? ($detail->pegawai->nama_lengkap ?? '-');
                        $nipLabel = $detail->nip ?? ($detail->pegawai->nip ?? null);
                        $tipeLabel = $tipeMap[$detail->tipe_perjalanan ?? ''] ?? ($detail->tipe_perjalanan ?? '-');
                    @endphp
                    <div class="accordion-item border rounded-3 mb-2 overflow-hidden shadow-sm">
                        <h2 class="accordion-header">
                            <button class="accordion-button {{ $idx > 0 ? 'collapsed' : '' }} bg-white fw-semibold py-3 px-4"
                                    type="button"
                                    data-bs-toggle="collapse"
                                    data-bs-target="#peserta{{ $idx }}"
                                    aria-expanded="{{ $idx === 0 ? 'true' : 'false' }}">
                                <div class="d-flex align-items-center gap-3 w-100 me-3">
                                    <span class="badge bg-primary rounded-circle d-flex align-items-center justify-content-center" style="width:28px;height:28px;font-size:0.75rem;flex-shrink:0;">{{ $idx + 1 }}</span>
                                    <div class="flex-fill">
                                        <span class="text-dark">{{ $namaLabel }}</span>
                                        @if($nipLabel)
                                            <span class="text-muted small ms-2">— {{ $nipLabel }}</span>
                                        @endif
                                    </div>
                                    <div class="d-none d-md-flex gap-3 align-items-center ms-auto me-2">
                                        <span class="badge bg-light text-secondary border small">
                                            <i class="bi bi-geo-alt me-1"></i>{{ $detail->provinsi?->provinsi ?? '-' }}
                                        </span>
                                        <span class="badge bg-light text-secondary border small">
                                            <i class="bi bi-calendar3 me-1"></i>{{ isset($detail->tgl_berangkat) ? \Carbon\Carbon::parse($detail->tgl_berangkat)->format('d M Y') : '-' }} · {{ $detail->lama_hari ?? 0 }} hr
                                        </span>
                                        <span class="fw-semibold text-success small">Rp {{ number_format($sub, 0, ',', '.') }}</span>
                                    </div>
                                </div>
                            </button>
                        </h2>
                        <div id="peserta{{ $idx }}" class="accordion-collapse collapse {{ $idx === 0 ? 'show' : '' }}">
                            <div class="accordion-body pt-0 pb-4 px-4">
                                <hr class="mt-0 mb-3">
                                <div class="row g-3 mb-3">
                                    <div class="col-sm-6 col-md-3">
                                        <label class="text-muted small d-block mb-1"><i class="bi bi-receipt me-1"></i>No. SPT</label>
                                        <span class="fw-semibold">{{ $detail->no_spt ?? '-' }}</span>
                                    </div>
                                    <div class="col-sm-6 col-md-3">
                                        <label class="text-muted small d-block mb-1"><i class="bi bi-receipt me-1"></i>No. SPPD</label>
                                        <span class="fw-semibold">{{ $detail->no_sppd ?? '-' }}</span>
                                    </div>
                                    <div class="col-sm-6 col-md-3">
                                        <label class="text-muted small d-block mb-1"><i class="bi bi-geo-alt me-1"></i>Tujuan</label>
                                        <span class="fw-semibold">{{ $detail->tujuan ?? '-' }}</span>
                                    </div>
                                    <div class="col-sm-6 col-md-3">
                                        <label class="text-muted small d-block mb-1"><i class="bi bi-credit-card me-1"></i>Rekening</label>
                                        <span class="fw-semibold">{{ $detail->rekening ?? '-' }}</span>
                                    </div>
                                    <div class="col-sm-6 col-md-3">
                                        <label class="text-muted small d-block mb-1"><i class="bi bi-map me-1"></i>Provinsi / Tipe</label>
                                        <span class="fw-semibold">{{ $detail->provinsi?->provinsi ?? '-' }}</span>
                                        <span class="badge bg-light text-secondary border ms-1 small">{{ $tipeLabel }}</span>
                                    </div>
                                    <div class="col-sm-6 col-md-3">
                                        <label class="text-muted small d-block mb-1"><i class="bi bi-calendar-event me-1"></i>Tgl Berangkat</label>
                                        <span class="fw-semibold">{{ isset($detail->tgl_berangkat) ? \Carbon\Carbon::parse($detail->tgl_berangkat)->format('d M Y') : '-' }}</span>
                                    </div>
                                    <div class="col-sm-6 col-md-3">
                                        <label class="text-muted small d-block mb-1"><i class="bi bi-hourglass-split me-1"></i>Lama Hari</label>
                                        <span class="fw-semibold">{{ $detail->lama_hari ?? 0 }} Hari</span>
                                    </div>
                                    @if($detail->spt_file_path)
                                        <div class="col-sm-6 col-md-3">
                                            <label class="text-muted small d-block mb-1"><i class="bi bi-paperclip me-1"></i>Lampiran SPT</label>
                                            <a href="{{ Storage::url($detail->spt_file_path) }}" target="_blank" class="btn btn-sm btn-outline-primary py-0 px-2">
                                                <i class="bi bi-eye"></i> {{ $detail->spt_file_name ?? 'Lihat' }}
                                            </a>
                                        </div>
                                    @endif
                                </div>

                                {{-- Rincian Biaya --}}
                                <div class="bg-light rounded-3 p-3">
                                    <div class="small fw-semibold text-muted mb-2"><i class="bi bi-cash-coin me-1"></i>Rincian Biaya</div>
                                    <div class="row g-2">
                                        @foreach([
                                            ['Tiket', $detail->biaya_tiket],
                                            ['Transport', $detail->biaya_transport],
                                            ['Penginapan', $detail->biaya_penginapan],
                                            ['Uang Harian', $detail->uang_harian],
                                            ['Representasi', $detail->uang_representasi],
                                        ] as [$bl, $bv])
                                            <div class="col-6 col-md-2">
                                                <div class="text-muted" style="font-size:0.72rem;">{{ $bl }}</div>
                                                <div class="fw-semibold small">Rp {{ number_format((float)($bv ?? 0), 0, ',', '.') }}</div>
                                            </div>
                                        @endforeach
                                        <div class="col-6 col-md-2 border-start border-2 border-success">
                                            <div class="text-muted" style="font-size:0.72rem;">Subtotal</div>
                                            <div class="fw-bold text-success small">Rp {{ number_format($sub, 0, ',', '.') }}</div>
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
