@php
    $documentId = $document?->id;
    $nomorField = ['spp' => 'nomor_spp', 'spm' => 'nomor_spm', 'npi' => 'nomor_npi', 'sp2d' => 'nomor_sp2d'][$jenis] ?? null;
    $tanggalField = ['spp' => 'tanggal_spp', 'spm' => 'tanggal_spm', 'npi' => 'tanggal_npi', 'sp2d' => 'tanggal_sp2d'][$jenis] ?? null;
    $nomor = $document && $nomorField ? $document->{$nomorField} : null;
    $tanggalRaw = $document && $tanggalField ? $document->{$tanggalField} : null;
    $tanggal = $tanggalRaw ? \Carbon\Carbon::parse($tanggalRaw)->translatedFormat('d M Y') : '—';
    $nominal = $document?->nominal_spp ?? $document?->nominal_spm ?? $document?->jumlah_uang ?? null;

    $status = $document?->status ?? null;
    $instanceStatus = $instance?->status;
    $submittableStatuses = ['DRAFT', 'Revisi', 'REVISI', 'REVISI_PPK', 'REVISI_KASUBBAG', 'DITOLAK_PPK', 'DITOLAK_KASUBBAG'];
    $awaitingSubmit = $document && in_array($document->status, $submittableStatuses, true);
    $submittable = $awaitingSubmit && $canSubmit;

    // Catatan penghalang pengajuan (mis. NPI menunggu SPP & SPM disetujui).
    $lockedNote = $lockedNote ?? null;

    // Peta tone warna per kartu
    $tone = match ($color ?? 'indigo') {
        'violet' => ['var(--tone-violet)', 'var(--tone-violet-soft)'],
        'emerald' => ['var(--tone-emerald)', 'var(--tone-emerald-soft)'],
        'info' => ['var(--tone-info)', 'var(--tone-info-soft)'],
        default => ['var(--tone-indigo)', 'var(--tone-indigo-soft)'],
    };

    // Peta status → gaya chip
    $statusUpper = strtoupper((string) $status);
    [$chipClass, $chipIcon, $chipText] = match (true) {
        $status === null => ['neutral', 'bi-slash-circle', 'Belum Dibuat'],
        in_array($statusUpper, ['DISETUJUI_FINAL', 'EXECUTED', 'SELESAI', 'SPP_TERBIT', 'SPM_TERBIT', 'NPI_TERBIT', 'SP2D_TERBIT', 'APPROVED'], true)
            => ['success', 'bi-check-circle-fill', str_replace('_', ' ', $statusUpper === 'EXECUTED' ? 'TERBIT' : $status)],
        str_contains($statusUpper, 'MENUNGGU') => ['warning shimmer', 'bi-hourglass-split', $status],
        str_contains($statusUpper, 'DITOLAK') => ['danger', 'bi-x-circle-fill', str_replace('_', ' ', $status)],
        str_contains($statusUpper, 'REVISI') => ['warning', 'bi-arrow-counterclockwise', str_replace('_', ' ', $status)],
        $statusUpper === 'DRAFT' => ['info', 'bi-pencil-square', 'DRAFT'],
        default => ['neutral', 'bi-circle', str_replace('_', ' ', $status)],
    };

    $isWaiting = $myApprovals->isNotEmpty();
    $approvals = $instance ? $instance->approvals->sortBy([['urutan_step', 'asc'], ['id', 'asc']]) : collect();

    // Catatan revisi terakhir (ditampilkan ke pengaju selama dokumen berstatus revisi).
    $revisionApproval = str_contains($statusUpper, 'REVISI')
        ? $approvals->where('status', 'REVISION')->sortByDesc('acted_at')->first()
        : null;

    // Bagian yang dapat dipilih pada modal "kembalikan ke pembuat tagihan".
    $chainDocs = $chainDocs ?? [];
@endphp

<div class="process-card doc-card mb-4" style="--tone: {{ $tone[0] }}; --tone-soft: {{ $tone[1] }};">
    <div class="process-card-body p-4">

        {{-- ===== Header ===== --}}
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
            <div class="d-flex align-items-center gap-3">
                <div class="doc-icon-tile {{ $isWaiting ? 'waiting' : '' }}">
                    <i class="bi {{ $icon ?? 'bi-file-earmark-text' }}"></i>
                </div>
                <div>
                    <div class="text-secondary fw-bold fs-8 text-uppercase letter-spacing-1">{{ $label }}</div>
                    @if($nomor)
                        <div class="fw-bolder text-dark font-monospace" style="font-size: 1.05rem;">{{ $nomor }}</div>
                    @else
                        <div class="fw-bold text-muted fst-italic">Draft belum tersedia</div>
                    @endif
                    <div class="text-secondary fs-8 mt-1"><i class="bi bi-calendar3 me-1"></i>{{ $tanggal }}</div>
                </div>
            </div>
            <div class="d-flex flex-column align-items-end gap-2">
                <span class="pt-status {{ $chipClass }}"><i class="bi {{ $chipIcon }}"></i> {{ $chipText }}</span>
                @if($nominal !== null)
                    <div class="fw-bolder fs-6" style="color: {{ $tone[0] }};">
                        Rp <span data-countup="{{ (float) $nominal }}">{{ number_format((float) $nominal, 0, ',', '.') }}</span>
                    </div>
                @endif
            </div>
        </div>

        @if(! $document)
            {{-- ===== Belum ada draft ===== --}}
            <div class="pt-locked">
                <i class="bi bi-lock-fill fs-4"></i>
                <div class="small fw-semibold">
                    Draft {{ strtoupper($jenis) }} dibuat otomatis oleh sistem setelah
                    <span class="text-dark">COA</span> dan <span class="text-dark">persetujuan KPA</span> terpenuhi.
                </div>
            </div>
        @else

            {{-- ===== Rantai verifikator workflow ===== --}}
            @if($approvals->isNotEmpty())
                <div class="d-flex flex-wrap align-items-center gap-2 mb-3 pt-2 border-top border-light-subtle">
                    <span class="text-secondary fs-8 fw-bold text-uppercase letter-spacing-1 me-1 mt-2">Verifikator:</span>
                    @foreach($approvals as $appr)
                        @php
                            $apprState = match ($appr->status) {
                                'APPROVED' => ['ok', 'bi-check-lg'],
                                'PENDING' => ['wait', 'bi-hourglass-split'],
                                'REJECTED' => ['bad', 'bi-x-lg'],
                                'REVISION' => ['bad', 'bi-arrow-counterclockwise'],
                                default => ['idle', 'bi-dash'],
                            };
                        @endphp
                        <span class="pt-approver {{ $apprState[0] }} mt-2" title="{{ $appr->nama_step }} — {{ $appr->status }}">
                            <span class="ava"><i class="bi {{ $apprState[1] }}"></i></span>
                            {{ $appr->role_code }}
                        </span>
                    @endforeach
                    @if($instanceStatus)
                        <span class="badge bg-light text-secondary border rounded-pill fs-8 mt-2 ms-auto">
                            <i class="bi bi-diagram-3 me-1"></i>{{ $instanceStatus }}
                        </span>
                    @endif
                </div>
            @endif

            {{-- ===== Catatan revisi dari verifikator ===== --}}
            @if($revisionApproval)
                {{-- data-sky-ignore: panel kontekstual, jangan diubah jadi toast oleh sky-alerts. --}}
                <div class="alert alert-warning border-warning-subtle d-flex gap-3 align-items-start mb-0 mt-3" data-sky-ignore>
                    <i class="bi bi-arrow-counterclockwise fs-4 flex-shrink-0"></i>
                    <div class="small">
                        <div class="fw-bold">
                            Revisi diminta oleh {{ $revisionApproval->actedByUser?->name ?? $revisionApproval->role_code }}
                            <span class="fw-normal text-secondary">
                                ({{ $revisionApproval->nama_step }}@if($revisionApproval->acted_at), {{ \Carbon\Carbon::parse($revisionApproval->acted_at)->translatedFormat('d M Y H:i') }}@endif)
                            </span>
                        </div>
                        <div class="mt-1" style="white-space: pre-line;">{{ $revisionApproval->catatan ?: 'Tanpa catatan.' }}</div>
                        <div class="text-secondary mt-2">
                            @if(strtolower($jenis) === 'sp2d')
                                Unggah ulang <strong>bukti transfer</strong> yang benar pada kartu Bukti Transfer — SP2D akan diajukan kembali ke PPK secara otomatis.
                            @else
                                Perbaiki sesuai catatan lalu klik <strong>Ajukan {{ strtoupper($jenis) }}</strong> — verifikasi akan diulang dari awal.
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            {{-- ===== Aksi dokumen ===== --}}
            <div class="d-flex flex-wrap align-items-center gap-2 mt-3">
                @if($pdfRoute)
                    <a href="{{ $pdfRoute }}" target="_blank" class="btn btn-light bg-white border shadow-sm btn-pt-action text-dark btn-sm">
                        <i class="bi bi-file-earmark-pdf-fill text-danger"></i> Lihat PDF
                    </a>
                @endif

                @if($submittable && $submitRoute)
                    <form method="POST" action="{{ $submitRoute }}" class="m-0 ms-auto">
                        @csrf
                        <button type="submit" class="btn btn-primary shadow btn-pt-action">
                            <i class="bi bi-send-fill"></i> Ajukan {{ strtoupper($jenis) }}
                        </button>
                    </form>
                @endif
            </div>

            @if($awaitingSubmit && $lockedNote)
                <div class="pt-locked mt-3">
                    <i class="bi bi-lock-fill fs-4"></i>
                    <div class="small fw-semibold">{{ $lockedNote }}</div>
                </div>
            @endif

            {{-- ===== Kotak persetujuan milik user ===== --}}
            @if($isWaiting)
                <div class="pt-attn mt-4">
                    <div class="pt-attn-inner">
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <div class="spinner-grow spinner-grow-sm text-warning" role="status"></div>
                            <h6 class="fw-bolder text-dark mb-0">Giliran Anda — Dokumen Menunggu Persetujuan</h6>
                        </div>

                        @foreach($myApprovals as $approval)
                            <form method="POST" action="{{ route('proses-tagihan.dokumen.aksi', [$tagihan->id, $jenis]) }}" class="bg-white p-3 rounded-4 shadow-sm border mb-2">
                                @csrf
                                <input type="hidden" name="approval_id" value="{{ $approval->id }}">
                                <input type="hidden" name="dokumen_id" value="{{ $documentId }}">

                                <div class="d-flex align-items-center gap-2 mb-3 pb-2 border-bottom border-light-subtle">
                                    <span class="badge bg-dark rounded-pill px-3">{{ $approval->role_code }}</span>
                                    <span class="fw-semibold text-secondary fs-7">{{ $approval->nama_step }}</span>
                                </div>

                                <div class="form-floating mb-3">
                                    <textarea name="catatan" class="form-control border-warning-subtle" id="catatan_{{ $approval->id }}" style="height: 76px" placeholder="Tinggalkan catatan..."></textarea>
                                    <label for="catatan_{{ $approval->id }}">Catatan persetujuan (opsional)</label>
                                </div>

                                <div class="d-flex flex-wrap gap-2">
                                    <button name="aksi" value="approve" class="btn btn-success fw-bold flex-grow-1 shadow-sm btn-pt-action justify-content-center" type="submit">
                                        <i class="bi bi-check-circle-fill"></i> Setujui
                                    </button>
                                    <button type="button" class="btn btn-warning fw-bold flex-grow-1 shadow-sm btn-pt-action justify-content-center text-dark"
                                            data-bs-toggle="modal" data-bs-target="#modalRevisi{{ $approval->id }}">
                                        <i class="bi bi-arrow-counterclockwise"></i> Revisi
                                    </button>
                                </div>
                            </form>

                            {{-- ===== Modal revisi berdasar akar masalah ===== --}}
                            @php
                                // Ringkasan dokumen yang akan dibatalkan (untuk keterangan dampak).
                                $dampakDokumen = collect($chainDocs)->except('tagihan')
                                    ->map(fn ($b) => $b['label'] . (empty($b['nomor']) ? '' : ' ' . $b['nomor']))
                                    ->implode(', ');
                                $isSp2dCard = strtolower($jenis) === 'sp2d';
                                $adaPajakKontrak = $tagihan->tipe_tagihan === 'KONTRAK';
                            @endphp
                            <div class="modal fade js-pt-revisi-modal" id="modalRevisi{{ $approval->id }}" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                                    <form method="POST" action="{{ route('proses-tagihan.dokumen.aksi', [$tagihan->id, $jenis]) }}" class="modal-content">
                                        @csrf
                                        <input type="hidden" name="aksi" value="revisi">
                                        <input type="hidden" name="approval_id" value="{{ $approval->id }}">
                                        <input type="hidden" name="dokumen_id" value="{{ $documentId }}">

                                        <div class="modal-header">
                                            <h5 class="modal-title fw-bold">
                                                <i class="bi bi-arrow-counterclockwise me-2 text-warning"></i>Permintaan Revisi {{ strtoupper($jenis) }}
                                            </h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                                        </div>

                                        <div class="modal-body">
                                            <div class="small text-secondary mb-3">
                                                Dokumen ini di-generate dari data tagihan — pilih <strong>akar masalahnya</strong>,
                                                sistem akan mengembalikannya ke pihak yang berwenang memperbaiki.
                                            </div>

                                            <div class="form-check mb-2 p-3 ps-5 border rounded-3">
                                                <input class="form-check-input js-revisi-target" type="radio" name="target" value="tagihan"
                                                       id="targetTagihan{{ $approval->id }}" checked>
                                                <label class="form-check-label" for="targetTagihan{{ $approval->id }}">
                                                    <span class="fw-bold d-block">Masalah pada data tagihan / dokumen pendukung
                                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle ms-1">ke Pembuat Tagihan</span>
                                                    </span>
                                                    <span class="text-muted small">
                                                        Rantai dokumen ({{ $dampakDokumen ?: 'SPP/SPM/NPI/SP2D' }}) dibatalkan,
                                                        persetujuan KPA di-reset, dan tagihan <strong>diverifikasi ulang oleh seluruh
                                                        verifikator</strong> setelah pembuatnya memperbaiki data
                                                        (nominal, uraian, dokumen pendukung).
                                                    </span>
                                                </label>
                                            </div>

                                            @if($adaPajakKontrak)
                                                <div class="form-check mb-2 p-3 ps-5 border rounded-3">
                                                    <input class="form-check-input js-revisi-target" type="radio" name="target" value="pajak"
                                                           id="targetPajak{{ $approval->id }}">
                                                    <label class="form-check-label" for="targetPajak{{ $approval->id }}">
                                                        <span class="fw-bold d-block">Masalah pada pajak / faktur pajak
                                                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle ms-1">ke Operator BLU</span>
                                                        </span>
                                                        <span class="text-muted small">
                                                            Rantai dokumen ({{ $dampakDokumen ?: 'SPP/SPM/NPI/SP2D' }}) dibatalkan dan
                                                            persetujuan KPA di-reset (nilai netto berubah), namun <strong>verifikasi tagihan
                                                            tidak diulang</strong>. Operator BLU memperbaiki tipe pajak / faktur pajak,
                                                            lalu draft dibuat ulang otomatis setelah KPA menyetujui kembali.
                                                        </span>
                                                    </label>
                                                </div>
                                            @endif

                                            <div class="form-check mb-2 p-3 ps-5 border rounded-3">
                                                <input class="form-check-input js-revisi-target" type="radio" name="target" value="coa"
                                                       id="targetCoa{{ $approval->id }}">
                                                <label class="form-check-label" for="targetCoa{{ $approval->id }}">
                                                    <span class="fw-bold d-block">Masalah pada pembebanan COA
                                                        <span class="badge bg-info-subtle text-info border border-info-subtle ms-1">ke PPK</span>
                                                    </span>
                                                    <span class="text-muted small">
                                                        Rantai dokumen ({{ $dampakDokumen ?: 'SPP/SPM/NPI/SP2D' }}) dibatalkan dan
                                                        persetujuan KPA di-reset, namun <strong>verifikasi tagihan tidak diulang</strong>.
                                                        PPK memperbaiki pembebanan COA, lalu draft dibuat ulang otomatis
                                                        setelah KPA menyetujui kembali.
                                                    </span>
                                                </label>
                                            </div>

                                            @if($isSp2dCard)
                                                <div class="form-check mb-2 p-3 ps-5 border rounded-3">
                                                    <input class="form-check-input js-revisi-target" type="radio" name="target" value="bukti"
                                                           id="targetBukti{{ $approval->id }}">
                                                    <label class="form-check-label" for="targetBukti{{ $approval->id }}">
                                                        <span class="fw-bold d-block">Bukti transfer salah / tidak sesuai
                                                            <span class="badge bg-success-subtle text-success border border-success-subtle ms-1">ke Bendahara Pengeluaran</span>
                                                        </span>
                                                        <span class="text-muted small">
                                                            Hanya SP2D yang dikembalikan — Bendahara Pengeluaran mengunggah ulang
                                                            bukti transfer, dokumen lain pada rantai tidak terpengaruh.
                                                        </span>
                                                    </label>
                                                </div>
                                            @endif

                                            <div class="js-target-section d-none mt-3" data-section="catatan">
                                                <div class="form-floating">
                                                    <textarea name="catatan" class="form-control" id="catatanRevisi{{ $approval->id }}"
                                                              style="height: 90px" placeholder="Catatan revisi" disabled></textarea>
                                                    <label for="catatanRevisi{{ $approval->id }}">Catatan revisi (wajib)</label>
                                                </div>
                                            </div>

                                            <div class="js-target-section mt-3" data-section="tagihan">
                                                <div class="small fw-semibold text-secondary mb-2">
                                                    Centang bagian yang perlu diperbaiki dan beri catatan untuk masing-masing:
                                                </div>
                                                @foreach($chainDocs as $bagianKey => $bagian)
                                                    <div class="border rounded-3 p-2 px-3 mb-2 js-revisi-item">
                                                        <div class="form-check">
                                                            <input class="form-check-input js-revisi-doc" type="checkbox" name="revisi_doc[]"
                                                                   value="{{ $bagianKey }}" id="revDoc{{ $approval->id }}_{{ $bagianKey }}">
                                                            <label class="form-check-label fw-semibold" for="revDoc{{ $approval->id }}_{{ $bagianKey }}">
                                                                {{ $bagian['label'] }}
                                                                @if(!empty($bagian['nomor']))
                                                                    <span class="text-muted fw-normal font-monospace small ms-1">{{ $bagian['nomor'] }}</span>
                                                                @endif
                                                            </label>
                                                        </div>
                                                        <textarea name="revisi_catatan[{{ $bagianKey }}]" rows="2" maxlength="1000"
                                                                  class="form-control form-control-sm mt-2 d-none js-revisi-catatan"
                                                                  placeholder="Catatan revisi untuk {{ $bagian['label'] }} (wajib)" disabled></textarea>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>

                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Batal</button>
                                            <button type="submit" class="btn btn-warning fw-bold text-dark">
                                                <i class="bi bi-arrow-counterclockwise"></i> Kirim Permintaan Revisi
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @elseif($instance && $instanceStatus === 'IN_PROGRESS')
                <div class="d-flex align-items-center gap-2 text-muted small bg-light p-2 px-3 rounded-3 border border-light-subtle mt-3">
                    <i class="bi bi-people"></i> Sedang menunggu persetujuan verifikator lain — tidak ada tindakan untuk Anda.
                </div>
            @endif
        @endif
    </div>
</div>

@once
    @push('script')
    <script>
    (function () {
        'use strict';

        // Pindahkan modal revisi ke <body> agar backdrop tidak terjebak
        // di dalam kartu yang memakai transform (animasi .reveal).
        document.querySelectorAll('.js-pt-revisi-modal').forEach(function (m) {
            document.body.appendChild(m);
        });

        function syncRevisiModal(modal) {
            var checked = modal.querySelector('.js-revisi-target:checked');
            var target = checked ? checked.value : 'tagihan';

            modal.querySelectorAll('.js-target-section').forEach(function (section) {
                // Seksi 'tagihan' = checklist per bagian; seksi 'catatan'
                // dipakai bersama untuk target pajak/coa/bukti.
                var active = section.dataset.section === 'tagihan'
                    ? target === 'tagihan'
                    : target !== 'tagihan';
                section.classList.toggle('d-none', !active);

                section.querySelectorAll('textarea[name="catatan"]').forEach(function (el) {
                    el.disabled = !active;
                    el.required = active;
                });

                section.querySelectorAll('.js-revisi-item').forEach(function (item) {
                    var cb = item.querySelector('.js-revisi-doc');
                    var note = item.querySelector('.js-revisi-catatan');
                    cb.disabled = !active;
                    var on = active && cb.checked;
                    note.classList.toggle('d-none', !cb.checked);
                    note.disabled = !on;
                    note.required = on;
                });
            });
        }

        document.addEventListener('change', function (e) {
            if (!e.target.classList.contains('js-revisi-target') && !e.target.classList.contains('js-revisi-doc')) return;
            var modal = e.target.closest('.js-pt-revisi-modal');
            if (modal) syncRevisiModal(modal);
        });

        document.querySelectorAll('.js-pt-revisi-modal').forEach(syncRevisiModal);
    })();
    </script>
    @endpush
@endonce
