<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-0">
        <div class="accordion accordion-flush" id="accordionPeserta">
            <div class="accordion-item border-0">
                <h2 class="accordion-header" id="headingPeserta">
                    <button class="accordion-button collapsed px-4 py-3 bg-light fw-bold text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#collapsePeserta" aria-expanded="false" aria-controls="collapsePeserta">
                        <i class="bi bi-people-fill text-primary me-2"></i> Ringkasan Peserta Perjalanan Dinas ({{ $tagihan->detailPerjaldin->count() }} Orang)
                    </button>
                </h2>
                <div id="collapsePeserta" class="accordion-collapse collapse" aria-labelledby="headingPeserta" data-bs-parent="#accordionPeserta">
                    <div class="accordion-body p-4 bg-white">
                        <div class="table-responsive">
                            <table class="table table-sm table-borderless table-striped align-middle mb-0">
                                <thead class="text-muted small">
                                    <tr>
                                        <th>Nama Pegawai / NIP</th>
                                        <th>Tujuan</th>
                                        <th>Tgl Berangkat</th>
                                        <th>Lama</th>
                                        <th class="text-end">Subtotal Bruto</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($tagihan->detailPerjaldin as $peserta)
                                        @php
                                            $subtotal = $peserta->biaya_tiket + $peserta->biaya_transport + $peserta->biaya_penginapan + $peserta->uang_harian + $peserta->uang_representasi;
                                        @endphp
                                        <tr>
                                            <td>
                                                <div class="fw-bold">{{ $peserta->nama_pegawai }}</div>
                                                <div class="small text-muted">{{ $peserta->nip ?: '-' }}</div>
                                            </td>
                                            <td>{{ $peserta->tujuan }}</td>
                                            <td>{{ \Carbon\Carbon::parse($peserta->tgl_berangkat)->format('d M Y') }}</td>
                                            <td>{{ $peserta->lama_hari }} Hari</td>
                                            <td class="text-end fw-bold">Rp {{ number_format($subtotal, 0, ',', '.') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
