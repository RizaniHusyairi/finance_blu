@php
    $canEditCoa = auth()->user()?->hasAnyRole(['PPK', 'Super Admin'])
        && $state['tagihanApproved']
        && ! $state['spp'];
@endphp

<div class="process-card mb-4 position-relative overflow-hidden">
    @if($state['coaDone'])
        <div class="position-absolute top-0 end-0 p-3 opacity-25" style="transform: scale(2) translate(10%, -10%); pointer-events: none;">
            <i class="bi bi-check-circle-fill text-success" style="font-size: 5rem;"></i>
        </div>
    @endif
    
    <div class="process-card-body position-relative z-index-1">
        @if($tagihan->chain_correction_target === 'COA')
            {{-- data-sky-ignore: panel kontekstual, jangan diubah jadi toast oleh sky-alerts. --}}
            <div class="alert alert-warning border-warning-subtle d-flex gap-3 align-items-start mb-4" data-sky-ignore>
                <i class="bi bi-arrow-counterclockwise fs-4 flex-shrink-0"></i>
                <div class="small">
                    <div class="fw-bold">
                        Perbaikan COA diminta oleh {{ $tagihan->chainCorrectionRequester?->name ?? 'verifikator' }}
                        @if($tagihan->chain_correction_requested_at)
                            <span class="fw-normal text-secondary">({{ \Carbon\Carbon::parse($tagihan->chain_correction_requested_at)->translatedFormat('d M Y H:i') }})</span>
                        @endif
                    </div>
                    <div class="mt-1" style="white-space: pre-line;">{{ $tagihan->chain_correction_note ?: 'Tanpa catatan.' }}</div>
                    <div class="text-secondary mt-2">
                        Rantai dokumen telah dibatalkan dan persetujuan KPA di-reset. Perbaiki pembebanan COA lalu klik
                        <strong>Simpan</strong> — setelah itu ajukan ulang persetujuan KPA agar draft dokumen dibuat ulang otomatis.
                    </div>
                </div>
            </div>
        @endif

        <div class="d-flex justify-content-between align-items-start gap-3 mb-4 pb-3 border-bottom border-light-subtle">
            <div class="d-flex align-items-center gap-3">
                <div class="{{ $state['coaDone'] ? 'bg-success' : 'bg-warning' }} bg-opacity-10 rounded p-2 d-flex align-items-center justify-content-center">
                    <i class="bi bi-calculator fs-5 {{ $state['coaDone'] ? 'text-success' : 'text-warning' }}"></i>
                </div>
                <div>
                    <h6 class="mb-1 fw-bold text-dark">Pembebanan Anggaran (COA)</h6>
                    <div class="text-secondary small">PPK memilih item DIPA sebagai sumber dana sebelum draft dicetak.</div>
                </div>
            </div>
            <span class="badge {{ $state['coaDone'] ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-warning-subtle text-warning border border-warning-subtle' }} rounded-pill px-3 py-2 fw-bold">
                @if($state['coaDone'])
                    <i class="bi bi-check-circle-fill me-1"></i> Lengkap
                @else
                    <i class="bi bi-hourglass-split me-1"></i> Menunggu PPK
                @endif
            </span>
        </div>

        @if($tagihan->tipe_tagihan === 'PERJALDIN')
            <form method="POST" action="{{ route('proses-tagihan.coa', $tagihan->id) }}">
                @csrf
                <div class="table-responsive mb-4 rounded-3 border border-light-subtle">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="text-secondary fw-semibold fs-7 text-uppercase py-3 ps-4">Komponen Biaya</th>
                                <th class="text-secondary fw-semibold fs-7 text-uppercase py-3">Nominal</th>
                                <th class="text-secondary fw-semibold fs-7 text-uppercase py-3 pe-4">Pembebanan COA</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($tagihan->komponenPerjaldin->where('total_nominal', '>', 0) as $komponen)
                                <tr>
                                    <td class="ps-4 py-3 fw-medium text-dark">{{ $komponen->nama_komponen }}</td>
                                    <td class="py-3 fs-6 fw-bold text-primary">Rp {{ number_format((float) $komponen->total_nominal, 0, ',', '.') }}</td>
                                    <td class="pe-4 py-3" style="min-width: 320px;">
                                        @if($canEditCoa)
                                            <select name="coa[{{ $komponen->id }}]" class="form-select js-coa-select" required id="coa_{{ $komponen->id }}"
                                                    data-coa-placeholder="-- Cari Sumber Dana (COA) --">
                                                <option value=""></option>
                                                @foreach($coaOptions as $item)
                                                    <option value="{{ $item->id }}" @selected((int) $komponen->dipa_revision_item_id === (int) $item->id)
                                                            data-kode="{{ $item->coa?->kode_mak_lengkap }}"
                                                            data-uraian="{{ \Illuminate\Support\Str::limit($item->coa?->uraian, 90) }}"
                                                            data-sisa="{{ number_format((float) $item->sisa_pagu, 0, ',', '.') }}"
                                                            data-dipa="{{ $item->dipaRevision?->masterDipa?->nomor_dipa }}"
                                                            data-dipa-tahun="{{ $item->dipaRevision?->masterDipa?->tahun_anggaran }}"
                                                            data-dipa-revisi="{{ $item->dipaRevision?->nomor_revisi }}">
                                                        {{ $item->coa?->kode_mak_lengkap }} — {{ \Illuminate\Support\Str::limit($item->coa?->uraian, 70) }} (Sisa: Rp {{ number_format((float) $item->sisa_pagu, 0, ',', '.') }}){{ $item->dipaRevision?->masterDipa?->nomor_dipa ? ' · DIPA ' . $item->dipaRevision->masterDipa->nomor_dipa : '' }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        @else
                                            @if($komponen->dipaRevisionItem)
                                                <div class="d-flex align-items-center gap-2">
                                                    <div class="bg-primary bg-opacity-10 text-primary px-2 py-1 rounded fw-bold font-monospace fs-7">
                                                        {{ $komponen->dipaRevisionItem->coa?->kode_mak_lengkap }}
                                                    </div>
                                                </div>
                                                <div class="text-muted small mt-1 text-truncate" style="max-width: 300px;" title="{{ $komponen->dipaRevisionItem->coa?->uraian }}">
                                                    {{ $komponen->dipaRevisionItem->coa?->uraian }}
                                                </div>
                                            @else
                                                <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary-subtle">Belum Dipilih</span>
                                            @endif
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if($canEditCoa)
                    <div class="text-end">
                        <button class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm btn-pt-action" type="submit">
                            <i class="bi bi-save-fill"></i> Simpan Pembebanan COA
                        </button>
                    </div>
                @endif
            </form>
        @else
            <form method="POST" action="{{ route('proses-tagihan.coa', $tagihan->id) }}">
                @csrf
                @if($canEditCoa)
                    <div class="p-4 bg-light rounded-4 border border-light-subtle mb-4">
                        <label class="form-label fw-bold text-dark mb-2" for="main_coa">Pilih Mata Anggaran (COA) untuk Pembayaran Ini <span class="text-danger">*</span></label>
                        <select name="dipa_revision_item_id" class="form-select js-coa-select" required id="main_coa"
                                data-coa-placeholder="-- Cari Item Anggaran DIPA Aktif --">
                            <option value=""></option>
                            @foreach($coaOptions as $item)
                                <option value="{{ $item->id }}" @selected((int) $tagihan->dipa_revision_item_id === (int) $item->id)
                                        data-kode="{{ $item->coa?->kode_mak_lengkap }}"
                                        data-uraian="{{ \Illuminate\Support\Str::limit($item->coa?->uraian, 90) }}"
                                        data-sisa="{{ number_format((float) $item->sisa_pagu, 0, ',', '.') }}"
                                        data-dipa="{{ $item->dipaRevision?->masterDipa?->nomor_dipa }}"
                                        data-dipa-tahun="{{ $item->dipaRevision?->masterDipa?->tahun_anggaran }}"
                                        data-dipa-revisi="{{ $item->dipaRevision?->nomor_revisi }}">
                                    {{ $item->coa?->kode_mak_lengkap }} - {{ \Illuminate\Support\Str::limit($item->coa?->uraian, 70) }} (Sisa: Rp {{ number_format((float) $item->sisa_pagu, 0, ',', '.') }}){{ $item->dipaRevision?->masterDipa?->nomor_dipa ? ' · DIPA ' . $item->dipaRevision->masterDipa->nomor_dipa : '' }}
                                </option>
                            @endforeach
                        </select>
                        <small class="text-muted d-block mt-2">Ketik untuk mencari kode MAK / uraian. Sisa pagu tampil di tiap pilihan.</small>
                        <div class="text-end mt-3">
                            <button class="btn btn-primary rounded-pill px-5 py-2 fw-bold shadow btn-pt-action" type="submit">
                                <i class="bi bi-save-fill"></i> Simpan COA
                            </button>
                        </div>
                    </div>
                @else
                    <div class="p-4 bg-light rounded-4 border border-light-subtle d-flex align-items-center gap-3">
                        <div class="bg-white rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width: 50px; height: 50px;">
                            <i class="bi bi-journal-album fs-4 text-primary"></i>
                        </div>
                        <div class="flex-grow-1">
                            @if($tagihan->dipaRevisionItem)
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <span class="fs-7 text-secondary text-uppercase fw-semibold">Kode MA:</span>
                                    <span class="fs-5 fw-bold text-primary font-monospace">{{ $tagihan->dipaRevisionItem->coa?->kode_mak_lengkap }}</span>
                                </div>
                                <div class="text-dark fw-medium">{{ $tagihan->dipaRevisionItem->coa?->uraian }}</div>
                            @else
                                <div class="text-muted fst-italic py-2"><i class="bi bi-info-circle me-1"></i> PPK belum memilih pembebanan anggaran.</div>
                            @endif
                        </div>
                    </div>
                @endif
            </form>
        @endif
    </div>
</div>
