@extends('layouts.app')
@section('title', 'Detail SP2D Perjalanan Dinas')

@section('content')
@include('sp2ds.partials.detail-styles')

@php
    use App\Models\DokumenSp2d;

    $st = $sp2d?->status;
    $statusVariant = match($st) {
        DokumenSp2d::STATUS_DRAFT               => 'slate',
        DokumenSp2d::STATUS_REVISI              => 'rose',
        DokumenSp2d::STATUS_MENUNGGU_VERIFIKASI => 'cyan',
        DokumenSp2d::STATUS_DISETUJUI_FINAL     => 'green',
        DokumenSp2d::STATUS_EXECUTED            => 'primary',
        default                                 => 'amber',
    };
    $statusLabel = $sp2d ? str_replace('_', ' ', $sp2d->status) : 'Belum Dibuat';
    $isEditable = !$sp2d || in_array($sp2d->status, [DokumenSp2d::STATUS_DRAFT, DokumenSp2d::STATUS_REVISI]);

    $progressStep = match($st) {
        null, DokumenSp2d::STATUS_DRAFT, DokumenSp2d::STATUS_REVISI => 1,
        DokumenSp2d::STATUS_MENUNGGU_VERIFIKASI                     => 2,
        default                                                    => 3,
    };
    $stepFail = in_array('REVISION', [$ppkApproval?->status, $kasubbagApproval?->status, $ppspmApproval?->status, $koordinatorApproval?->status], true);
@endphp

@foreach (['success' => ['check_circle','success'], 'error' => ['error','danger']] as $key => $cfg)
    @if(session($key))
        <div class="alert alert-{{ $cfg[1] }} border-0 shadow-sm alert-dismissible fade show mb-3">
            <i class="material-icons-outlined align-middle me-1">{{ $cfg[0] }}</i> {{ session($key) }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
@endforeach
@if($errors->any())
    <div class="alert alert-danger border-0 shadow-sm alert-dismissible fade show mb-3">
        <ul class="mb-0 ps-3">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="sp2dd">

    {{-- ===== HERO ===== --}}
    <div class="sp2dd-hero">
        <span class="sp2dd-hero__bar"></span>
        <div class="sp2dd-hero__in d-flex flex-column flex-xl-row justify-content-between gap-3">
            <div>
                <span class="sp2dd-eyebrow"><i class="material-icons-outlined" style="font-size:15px;">flight_takeoff</i> Detail Pencatatan SP2D · Perjalanan Dinas</span>
                <h1>{{ Str::limit($tagihan?->deskripsi ?? 'Pencairan SP2D Perjalanan Dinas', 70) }}</h1>
                <p class="sub">Bendahara Penerimaan: {{ $npi->bendaharaPenerimaan?->name ?? '-' }}</p>

                <div class="sp2dd-hero__meta">
                    <div><div class="k">Nomor SP2D</div><div class="v">{{ $sp2d?->nomor_sp2d ?? 'Draft baru' }}</div></div>
                    <div><div class="k">Nomor NPI</div><div class="v">{{ $npi->nomor_npi ?? '-' }}</div></div>
                    <div><div class="k">Peserta</div><div class="v">{{ collect($tagihan?->detailPerjaldin)->count() }} Orang</div></div>
                </div>
                <div class="sp2dd-hero__amount"><span class="k">Dasar NPI Final</span><span class="v">Rp {{ number_format($spm?->nominal_spm ?? $defaultNilai, 0, ',', '.') }}</span></div>
            </div>

            <div class="d-flex flex-column gap-2 align-items-stretch align-items-xl-end" style="min-width: 210px;">
                <span class="sp2dd-status sp2dd-status--{{ $statusVariant }}"><span class="dot"></span> {{ $statusLabel }}</span>
                <a href="{{ route('sp2ds.perjaldin.index') }}" class="sp2dd-hbtn"><i class="material-icons-outlined">arrow_back</i> Kembali</a>
                @if($sp2d && in_array($sp2d->status, [DokumenSp2d::STATUS_DISETUJUI_FINAL, DokumenSp2d::STATUS_EXECUTED]))
                    <a href="{{ route('sp2ds.perjaldin.cetak', $sp2d->id) }}" target="_blank" class="sp2dd-hbtn sp2dd-hbtn--solid"><i class="material-icons-outlined">print</i> Cetak PDF</a>
                @endif
            </div>
        </div>
    </div>

    {{-- ===== STEPPER ===== --}}
    @include('sp2ds.partials.detail-stepper', ['progressStep' => $progressStep, 'stepFail' => $stepFail])

    {{-- ===== VERIFIKATOR ===== --}}
    @if($sp2d && $wf)
        @include('sp2ds.partials.detail-verifikator', ['verifikators' => [
            'PPK' => $ppkApproval,
            'Kasubbag' => $kasubbagApproval,
            'PPSPM' => $ppspmApproval,
            'Koordinator' => $koordinatorApproval,
        ]])
    @endif

    <div class="row g-4">
        {{-- ===== LEFT ===== --}}
        <div class="col-xl-7">
            {{-- Ringkasan nilai --}}
            <div class="sp2dd-card">
                <div class="sp2dd-card__head"><div class="sp2dd-card__title"><i class="material-icons-outlined">summarize</i> Ringkasan Dokumen (Netto)</div></div>
                <div class="sp2dd-card__body">
                    <div class="sp2dd-mini">
                        <div class="sp2dd-minicard"><div class="k">Tagihan Bruto</div><div class="v">Rp {{ number_format($tagihan?->total_tagihan ?? 0, 0, ',', '.') }}</div></div>
                        <div class="sp2dd-minicard"><div class="k">Nilai SPP / SPM</div><div class="v">Rp {{ number_format($defaultNilai, 0, ',', '.') }}</div></div>
                        <div class="sp2dd-minicard green"><div class="k">Dasar NPI Final</div><div class="v">Rp {{ number_format($spm?->nominal_spm ?? 0, 0, ',', '.') }}</div></div>
                        <div class="sp2dd-minicard accent"><div class="k">Nilai SP2D</div><div class="v">Rp {{ number_format($sp2d?->nilai_sp2d ?? $defaultNilai, 0, ',', '.') }}</div></div>
                    </div>
                </div>
            </div>

            {{-- Penelusuran dokumen --}}
            <div class="sp2dd-card">
                <div class="sp2dd-card__head"><div class="sp2dd-card__title"><i class="material-icons-outlined">account_tree</i> Penelusuran Dokumen Anggaran</div></div>
                <div class="sp2dd-card__body">
                    <div class="sp2dd-grid mb-3">
                        <div class="sp2dd-field"><span class="k">Beban COA (DIPA)</span><span class="v">@if($komponen?->dipaRevisionItem?->coa){{ $komponen->dipaRevisionItem->coa->kode_akun }} — {{ Str::limit($komponen->dipaRevisionItem->coa->nama_akun, 28) }}@else - @endif</span></div>
                        <div class="sp2dd-field"><span class="k">Bendahara Penerimaan</span><span class="v">{{ $npi->bendaharaPenerimaan?->name ?? '-' }}</span></div>
                    </div>
                    <div class="d-flex flex-column gap-2">
                        @foreach([
                            ['NPI Pemindahbukuan Internal', $npi->nomor_npi, $npi->tanggal_npi, 'swap_horiz'],
                            ['Dokumen SPM', $spm?->nomor_spm, $spm?->tanggal_spm, 'request_quote'],
                            ['Surat Permintaan Pembayaran (SPP)', $spp?->nomor_spp, $spp?->tanggal_spp, 'receipt'],
                        ] as $doc)
                            <div class="d-flex align-items-center gap-3 p-2 rounded-3" style="background:#f8fafc; border:1px solid var(--d-line);">
                                <div style="width:42px;height:42px;border-radius:11px;display:grid;place-items:center;background:#eef2ff;color:var(--d-primary);flex-shrink:0;"><i class="material-icons-outlined">{{ $doc[3] }}</i></div>
                                <div class="flex-grow-1"><div class="fw-bold" style="font-size:13px;color:var(--d-ink);">{{ $doc[0] }}</div><div class="sp2dd-tl__m">{{ $doc[1] ?? '-' }}</div></div>
                                <div class="text-end"><div class="sp2dd-tl__m">Tanggal</div><div class="fw-bold small">{{ optional($doc[2])->format('d M Y') ?? '-' }}</div></div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Rincian peserta --}}
            <div class="sp2dd-card">
                <div class="sp2dd-card__head"><div class="sp2dd-card__title"><i class="material-icons-outlined">groups</i> Rekapitulasi Pembayaran per Peserta</div></div>
                <div class="table-responsive">
                    <table class="sp2dd-table">
                        <thead><tr><th>Peserta</th><th>Tujuan</th><th>Lama</th><th class="text-end">Tr &amp; Tiket</th><th class="text-end">UH</th><th class="text-end">Subtotal</th></tr></thead>
                        <tbody>
                            @forelse($tagihan?->detailPerjaldin ?? [] as $det)
                                @php $subtotal = ($det->biaya_tiket ?? 0)+($det->biaya_transport ?? 0)+($det->biaya_penginapan ?? 0)+($det->uang_harian ?? 0)+($det->uang_representasi ?? 0); @endphp
                                <tr>
                                    <td><div class="fw-bold" style="color:var(--d-ink);">{{ $det->nama_pegawai ?: ($det->pegawai?->nama_lengkap ?? '-') }}</div><div class="sp2dd-tl__m">{{ $det->nip ?: ($det->pegawai?->nip ?? '-') }}</div></td>
                                    <td><div>{{ $det->tujuan }}</div><div class="sp2dd-tl__m">{{ $det->provinsi?->provinsi ?? '-' }}</div></td>
                                    <td>{{ $det->lama_hari }} hr</td>
                                    <td class="text-end">{{ number_format(($det->biaya_tiket ?? 0)+($det->biaya_transport ?? 0), 0, ',', '.') }}</td>
                                    <td class="text-end">{{ number_format($det->uang_harian ?? 0, 0, ',', '.') }}</td>
                                    <td class="text-end fw-bold" style="color:var(--d-primary);">Rp {{ number_format($subtotal, 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-muted py-4">Belum ada rincian peserta Perjaldin.</td></tr>
                            @endforelse
                        </tbody>
                        <tfoot><tr><td colspan="5" class="text-end">TOTAL KESELURUHAN</td><td class="text-end" style="color:var(--d-primary);">Rp {{ number_format($defaultNilai, 0, ',', '.') }}</td></tr></tfoot>
                    </table>
                </div>
            </div>
        </div>

        {{-- ===== RIGHT ===== --}}
        <div class="col-xl-5">
            <div class="sticky-side d-flex flex-column gap-4">

                {{-- Form (editable) or status panel --}}
                @if($isEditable)
                    <div class="sp2dd-form-card">
                        <div class="sp2dd-form-card__head"><i class="material-icons-outlined">edit_document</i> Form Pengisian SP2D</div>
                        <div class="sp2dd-form-card__body">
                            <form action="{{ route('sp2ds.perjaldin.store', $npi->id) }}" method="POST" id="form-draft-sp2d">
                                @csrf
                                <div class="mb-3">
                                    <label class="sp2dd-label">Nomor SP2D <span class="text-danger">*</span></label>
                                    <input type="text" name="nomor_sp2d" class="sp2dd-input mono" value="{{ old('nomor_sp2d', $sp2d?->nomor_sp2d ?? $autoNomorSp2d) }}" required placeholder="Contoh: 12345/SP2D/2026">
                                    <small class="text-muted"><i class="bi bi-info-circle me-1"></i>Diturunkan dari SPP, ubah jika perlu.</small>
                                </div>
                                <div class="mb-3">
                                    <label class="sp2dd-label">Tanggal SP2D <span class="text-danger">*</span></label>
                                    <input type="date" name="tanggal_sp2d" class="sp2dd-input" value="{{ old('tanggal_sp2d', $sp2d?->tanggal_sp2d?->format('Y-m-d') ?? date('Y-m-d')) }}" required>
                                </div>
                                <div class="mb-3">
                                    <label class="sp2dd-label">Nilai SP2D (Rp) <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" min="0" name="nilai_sp2d" class="sp2dd-input" value="{{ old('nilai_sp2d', floatval($sp2d?->nilai_sp2d ?? $defaultNilai)) }}" required>
                                    <div class="text-muted small mt-1">Mengikuti Netto NPI/SPM. Sesuaikan bila ada selisih biaya admin bank.</div>
                                </div>
                                <div class="mb-3">
                                    <label class="sp2dd-label">Tahun Anggaran <span class="text-danger">*</span></label>
                                    <input type="text" name="tahun_anggaran" class="sp2dd-input" value="{{ old('tahun_anggaran', $sp2d?->tahun_anggaran ?? $defaultTahun) }}" required maxlength="4">
                                </div>
                                <div class="mb-3">
                                    <label class="sp2dd-label">Catatan Pembukuan <span class="text-muted">(Opsional)</span></label>
                                    <textarea name="catatan" rows="2" class="sp2dd-input" placeholder="Tulis catatan jika ada...">{{ old('catatan', $sp2d?->catatan ?? '') }}</textarea>
                                </div>
                                <button type="submit" class="sp2dd-btn sp2dd-btn--primary"><i class="material-icons-outlined">save</i> Simpan Referensi SP2D (Draft)</button>
                            </form>
                        </div>
                    </div>
                @else
                    <div class="sp2dd-form-card">
                        <div class="sp2dd-form-card__head"><i class="material-icons-outlined">paid</i> Pencairan &amp; Bukti Transfer</div>
                        <div class="sp2dd-form-card__body">
                            @if(in_array($sp2d->status, [DokumenSp2d::STATUS_DISETUJUI_FINAL, DokumenSp2d::STATUS_SP2D_TERBIT, DokumenSp2d::STATUS_MENUNGGU_UPLOAD]))
                                <div class="sp2dd-pay">
                                    <div class="sp2dd-pay__banner">
                                        <i class="material-icons-outlined">verified</i>
                                        <div>
                                            <div class="t">SP2D disetujui seluruh verifikator</div>
                                            <div class="s">Unggah bukti transfer untuk menyelesaikan tagihan perjalanan dinas &amp; mencatat ke BKU.</div>
                                        </div>
                                    </div>
                                    <form action="{{ route('sp2ds.catat-bku', $sp2d->id) }}" method="POST" enctype="multipart/form-data" id="formBuktiTransfer">
                                        @csrf
                                        <label class="sp2dd-label">Keterangan Transfer <span class="text-muted">(opsional)</span></label>
                                        <textarea name="catatan_bku" class="sp2dd-input mb-3" rows="2" placeholder="Contoh: Transfer perjalanan dinas {{ $tagihan?->nomor_tagihan }}"></textarea>

                                        <label class="sp2dd-label">Bukti Transfer SP2D <span class="text-danger">*</span></label>
                                        <div class="sp2dd-drop" id="dropBukti">
                                            <div class="sp2dd-drop__icon"><i class="material-icons-outlined">cloud_upload</i></div>
                                            <div class="sp2dd-drop__title">Seret &amp; lepas file di sini</div>
                                            <div class="sp2dd-drop__hint">atau <span class="sp2dd-drop__browse">pilih dari perangkat</span> &middot; PDF / JPG / PNG &middot; maks. 5MB</div>
                                            <input type="file" name="bukti_transfer" id="inputBukti" accept=".pdf,.jpg,.jpeg,.png" required>
                                        </div>
                                        <div class="sp2dd-file-pill" id="filePill">
                                            <span class="sp2dd-file-pill__ic"><i class="material-icons-outlined">description</i></span>
                                            <div class="sp2dd-min-w-0">
                                                <div class="sp2dd-file-pill__name" id="fileName">-</div>
                                                <div class="sp2dd-file-pill__meta" id="fileMeta"></div>
                                            </div>
                                            <button type="button" class="sp2dd-file-pill__x" id="fileClear" title="Hapus"><i class="material-icons-outlined">close</i></button>
                                        </div>

                                        <button type="submit" class="sp2dd-btn sp2dd-btn--success mt-3" onclick="return confirm('Upload bukti transfer dan selesaikan tagihan ini?')"><i class="material-icons-outlined">paid</i> Upload Bukti &amp; Selesaikan</button>
                                    </form>
                                </div>
                            @elseif($sp2d->status === DokumenSp2d::STATUS_EXECUTED)
                                @php $buktiTransferSp2d = $sp2d?->bukti_transfer; @endphp
                                <div class="sp2dd-pay__banner" style="background:linear-gradient(120deg,#eef2ff,#e0f2fe);border-color:rgba(79,70,229,.25);">
                                    <i class="material-icons-outlined" style="color:var(--d-primary);">task_alt</i>
                                    <div>
                                        <div class="t">Tagihan SELESAI &amp; bukti transfer terunggah</div>
                                        <div class="s">SP2D telah dicairkan dan tercatat ke BKU.</div>
                                        @if($buktiTransferSp2d)
                                            <a href="{{ route('arsip-sensitif.download', $buktiTransferSp2d->id) }}" class="small fw-semibold text-primary d-inline-flex align-items-center gap-1 mt-1"><i class="material-icons-outlined" style="font-size:15px;">visibility</i> Lihat bukti transfer</a>
                                        @endif
                                    </div>
                                </div>
                                <a href="{{ route('sp2ds.perjaldin.cetak', $sp2d->id) }}" target="_blank" class="sp2dd-btn sp2dd-btn--success mt-3"><i class="material-icons-outlined">print</i> Cetak PDF SP2D</a>
                            @else
                                <div style="text-align:center; padding: 6px 0;">
                                    <div style="width:64px;height:64px;border-radius:20px;margin:0 auto 12px;display:grid;place-items:center;background:#eef2ff;color:var(--d-primary);"><i class="material-icons-outlined" style="font-size:34px;">pending_actions</i></div>
                                    <h6 class="fw-bold" style="color:var(--d-primary);">Sedang Diverifikasi Paralel</h6>
                                    <p class="sp2dd-tl__m mb-0">Menunggu PPK, Kasubbag, PPSPM &amp; Koordinator memverifikasi draft ini.</p>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif

                {{-- Checklist + submit --}}
                <div class="sp2dd-card mb-0">
                    <div class="sp2dd-card__head"><div class="sp2dd-card__title"><i class="material-icons-outlined">fact_check</i> Validasi Pengajuan</div></div>
                    <div class="sp2dd-card__body">
                        @php
                            $checklist = [
                                ['ok' => $checks['npi_final'], 't' => 'NPI Disetujui Final / Selesai'],
                                ['ok' => $checks['spp_tersedia'] && $checks['spm_tersedia'], 't' => 'Lampiran SPP & SPM Valid'],
                                ['ok' => $checks['tagihan_ada'] && $checks['peserta_ada'], 't' => 'Rincian Perjalanan & Peserta Terdata'],
                                ['ok' => $checks['sp2d_valid'] && $checks['sp2d_tersimpan'], 't' => 'Draft SP2D Tersimpan'],
                            ];
                        @endphp
                        @foreach($checklist as $c)
                            <div class="sp2dd-check {{ $c['ok'] ? 'ok' : 'no' }}">
                                <div class="sp2dd-check__ic"><i class="material-icons-outlined">{{ $c['ok'] ? 'check' : 'close' }}</i></div>
                                <div><div class="sp2dd-check__t">{{ $c['t'] }}</div></div>
                            </div>
                        @endforeach

                        <div class="d-flex align-items-center justify-content-between my-3">
                            <span class="fw-bold small">Status Persiapan</span>
                            @if($isLengkap)
                                <span class="sp2dd-vpill ok"><i class="material-icons-outlined">check_circle</i> LENGKAP</span>
                            @else
                                <span class="sp2dd-vpill wait"><i class="material-icons-outlined">hourglass_top</i> BELUM LENGKAP</span>
                            @endif
                        </div>

                        <form action="{{ route('sp2ds.perjaldin.submit', $npi->id) }}" method="POST" id="form-submit-sp2d" onsubmit="return confirm('Ajukan pencairan SP2D ini ke tahap verifikasi PPK, Kasubbag, PPSPM, dan Koordinator Keuangan?')">
                            @csrf
                            <button type="submit" class="sp2dd-btn sp2dd-btn--success" {{ (!$isLengkap || ($sp2d && !in_array($sp2d->status, [DokumenSp2d::STATUS_DRAFT, DokumenSp2d::STATUS_REVISI]))) ? 'disabled' : '' }}><i class="material-icons-outlined">send</i> Ajukan SP2D Sekarang</button>
                            @if(!$isLengkap && (!$sp2d || in_array($sp2d->status, [DokumenSp2d::STATUS_DRAFT, DokumenSp2d::STATUS_REVISI])))
                                <div class="text-danger small mt-2 text-center">Lengkapi draft parameter SP2D di atas terlebih dahulu.</div>
                            @endif
                        </form>
                    </div>
                </div>

                {{-- Antrean verifikasi (detail per approval, with notes) --}}
                @if($sp2d && $wf)
                <div class="sp2dd-card mb-0">
                    <div class="sp2dd-card__head"><div class="sp2dd-card__title"><i class="material-icons-outlined">how_to_reg</i> Antrean Verifikasi SP2D</div></div>
                    <div class="sp2dd-card__body">
                        <div class="sp2dd-tl">
                            @foreach($wf->approvals as $approval)
                                @php
                                    $tone = match($approval->status){ 'APPROVED' => 'var(--d-green)', 'REVISION','REJECTED' => 'var(--d-rose)', default => 'var(--d-amber)' };
                                @endphp
                                <div class="sp2dd-tl__item" style="animation-delay: {{ $loop->index * 0.05 }}s;">
                                    <span class="sp2dd-tl__dot" style="background: {{ $tone }}; box-shadow: 0 0 0 4px rgba(0,0,0,.05);"></span>
                                    <div class="d-flex justify-content-between align-items-start gap-2">
                                        <div>
                                            <div class="sp2dd-tl__t">{{ $approval->role_code }}</div>
                                            <div class="sp2dd-tl__m">{{ $approval->assignedUser?->name ?? 'Semua ' . $approval->role_code }}</div>
                                        </div>
                                        <span class="sp2dd-vpill {{ $approval->status === 'APPROVED' ? 'ok' : (in_array($approval->status, ['REVISION','REJECTED']) ? 'bad' : 'wait') }}">{{ $approval->status }}</span>
                                    </div>
                                    @if($approval->catatan)<div class="sp2dd-tl__note">"{{ $approval->catatan }}"</div>@endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                @endif

                {{-- Riwayat log --}}
                @if($sp2d && $sp2d->logs && $sp2d->logs->count() > 0)
                <div class="sp2dd-card mb-0">
                    <div class="sp2dd-card__head"><div class="sp2dd-card__title"><i class="material-icons-outlined">history</i> Riwayat Log SP2D</div></div>
                    <div class="sp2dd-card__body" style="max-height: 280px; overflow-y:auto;">
                        <div class="sp2dd-tl">
                            @foreach($sp2d->logs as $log)
                                <div class="sp2dd-tl__item">
                                    <span class="sp2dd-tl__dot"></span>
                                    <div class="sp2dd-tl__t">{{ $log->catatan }}</div>
                                    <div class="sp2dd-tl__m">{{ $log->aksi }} &bull; {{ $log->user?->name ?? 'Sistem' }} &bull; {{ $log->created_at->format('d/m/y H:i') }}</div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

@push('script')
<script>
(function () {
    var drop = document.getElementById('dropBukti');
    if (!drop) return;
    var input = document.getElementById('inputBukti');
    var pill = document.getElementById('filePill');
    var nameEl = document.getElementById('fileName');
    var metaEl = document.getElementById('fileMeta');
    var clearBtn = document.getElementById('fileClear');

    function humanSize(bytes) {
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
        return (bytes / 1048576).toFixed(2) + ' MB';
    }

    function render() {
        if (input.files && input.files.length) {
            var f = input.files[0];
            nameEl.textContent = f.name;
            metaEl.textContent = humanSize(f.size) + ' \u00b7 siap diunggah';
            pill.classList.add('show');
            drop.classList.add('has-file');
            drop.querySelector('.sp2dd-drop__title').textContent = 'File terpilih';
        } else {
            pill.classList.remove('show');
            drop.classList.remove('has-file');
            drop.querySelector('.sp2dd-drop__title').textContent = 'Seret & lepas file di sini';
        }
    }

    input.addEventListener('change', render);
    clearBtn.addEventListener('click', function () { input.value = ''; render(); });

    ['dragenter', 'dragover'].forEach(function (ev) {
        drop.addEventListener(ev, function (e) { e.preventDefault(); drop.classList.add('is-drag'); });
    });
    ['dragleave', 'drop'].forEach(function (ev) {
        drop.addEventListener(ev, function (e) { e.preventDefault(); drop.classList.remove('is-drag'); });
    });
    drop.addEventListener('drop', function (e) {
        if (e.dataTransfer && e.dataTransfer.files.length) {
            input.files = e.dataTransfer.files;
            render();
        }
    });
})();
</script>
@endpush

@endsection
