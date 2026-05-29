@extends('layouts.app')
@section('title', 'Detail SP2D Kontrak')

@section('content')
@include('sp2ds.partials.detail-styles')

@php
    use App\Models\DokumenSp2d;

    $statusVariant = match($statusSp2d) {
        'BELUM DIBUAT'        => 'amber',
        'DRAFT'               => 'slate',
        'REVISI'              => 'rose',
        'MENUNGGU_VERIFIKASI' => 'cyan',
        'DISETUJUI_FINAL', 'MENUNGGU_UPLOAD', 'SP2D_TERBIT' => 'green',
        'EXECUTED'            => 'primary',
        default               => 'slate',
    };
    $statusLabel = match($statusSp2d) {
        'BELUM DIBUAT'        => 'Belum Dibuat',
        'DRAFT'               => 'Draft',
        'REVISI'              => 'Draft Revisi',
        'MENUNGGU_VERIFIKASI' => 'Menunggu Verifikasi',
        'DISETUJUI_FINAL'     => 'Disetujui Final',
        'MENUNGGU_UPLOAD'     => 'Menunggu Upload SP2D',
        'SP2D_TERBIT'         => 'SP2D Terbit',
        'EXECUTED'            => 'Lunas / BKU',
        default               => $statusSp2d,
    };

    $progressStep = match($statusSp2d) {
        'BELUM DIBUAT', 'DRAFT', 'REVISI' => 1,
        'MENUNGGU_VERIFIKASI'             => 2,
        default                           => 3,
    };
    $stepFail = in_array('REVISION', [$ppkApproval?->status, $kasubbagApproval?->status, $ppspmApproval?->status, $koordinatorApproval?->status], true);
@endphp

{{-- flash --}}
@foreach (['success' => ['check_circle','success'], 'error' => ['error','danger']] as $key => $cfg)
    @if(session($key))
        <div class="alert alert-{{ $cfg[1] }} border-0 shadow-sm alert-dismissible fade show mb-3">
            <i class="material-icons-outlined align-middle me-1">{{ $cfg[0] }}</i> {{ session($key) }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
@endforeach

<div class="sp2dd">

    {{-- ===== HERO ===== --}}
    <div class="sp2dd-hero">
        <span class="sp2dd-hero__bar"></span>
        <div class="sp2dd-hero__in d-flex flex-column flex-xl-row justify-content-between gap-3">
            <div>
                <span class="sp2dd-eyebrow"><i class="material-icons-outlined" style="font-size:15px;">description</i> Detail Pencatatan SP2D · Kontrak</span>
                <h1>{{ $kontrak?->nama_pekerjaan ?? 'Pencairan SP2D Kontrak' }}</h1>
                <p class="sub">{{ $vendor?->nama_pihak ?? 'Vendor' }} &bull; Ref Tagihan {{ $tagihan?->nomor_tagihan ?? '-' }}</p>

                <div class="sp2dd-hero__meta">
                    <div><div class="k">Nomor SP2D</div><div class="v">{{ $sp2d?->nomor_sp2d ?? 'Belum ada' }}</div></div>
                    <div><div class="k">Nomor NPI</div><div class="v">{{ $npi->nomor_npi ?? '-' }}</div></div>
                    <div><div class="k">Nomor SPM</div><div class="v">{{ $spm?->nomor_spm ?? '-' }}</div></div>
                    <div><div class="k">Nomor SPK</div><div class="v">{{ $kontrak?->nomor_spk ?? '-' }}</div></div>
                </div>
                <div class="sp2dd-hero__amount"><span class="k">Nilai SP2D</span><span class="v">Rp {{ number_format($nominalSp2d, 0, ',', '.') }}</span></div>
            </div>

            <div class="d-flex flex-column gap-2 align-items-stretch align-items-xl-end" style="min-width: 210px;">
                <span class="sp2dd-status sp2dd-status--{{ $statusVariant }}"><span class="dot"></span> {{ $statusLabel }}</span>
                <a href="{{ route('sp2ds.kontrak.index') }}" class="sp2dd-hbtn"><i class="material-icons-outlined">arrow_back</i> Kembali</a>
                @if(in_array($statusSp2d, ['DISETUJUI_FINAL', 'MENUNGGU_UPLOAD', 'SP2D_TERBIT', 'EXECUTED']))
                    <a href="{{ route('sp2ds.cetak-pdf', $sp2d->id) }}" target="_blank" class="sp2dd-hbtn sp2dd-hbtn--solid"><i class="material-icons-outlined">print</i> Cetak PDF</a>
                @endif
                @if($canSubmit)
                    <button type="button" class="sp2dd-hbtn sp2dd-hbtn--solid" data-bs-toggle="modal" data-bs-target="#modalSubmit"><i class="material-icons-outlined">publish</i> Ajukan Verifikasi</button>
                @endif
            </div>
        </div>
    </div>

    {{-- ===== STEPPER ===== --}}
    @include('sp2ds.partials.detail-stepper', ['progressStep' => $progressStep, 'stepFail' => $stepFail])

    {{-- ===== VERIFIKATOR ===== --}}
    @if($sp2d && !in_array($statusSp2d, ['BELUM DIBUAT', 'DRAFT']))
        @include('sp2ds.partials.detail-verifikator', ['verifikators' => [
            'PPK' => $ppkApproval,
            'Kasubbag' => $kasubbagApproval,
            'PPSPM' => $ppspmApproval,
            'Koordinator' => $koordinatorApproval,
        ]])
    @endif

    <div class="row g-4">
        {{-- ===== LEFT: SOURCE DATA ===== --}}
        <div class="col-xl-7">
            {{-- Ringkasan SP2D & NPI --}}
            <div class="sp2dd-card">
                <div class="sp2dd-card__head"><div class="sp2dd-card__title"><i class="material-icons-outlined">receipt_long</i> Ringkasan SP2D &amp; NPI</div></div>
                <div class="sp2dd-card__body">
                    <div class="sp2dd-grid">
                        <div class="sp2dd-field"><span class="k">Nomor SP2D</span><span class="v mono">{{ $sp2d?->nomor_sp2d ?? '-' }}</span></div>
                        <div class="sp2dd-field"><span class="k">Tanggal SP2D</span><span class="v">{{ $sp2d?->tanggal_sp2d ? \Carbon\Carbon::parse($sp2d->tanggal_sp2d)->format('d M Y') : '-' }}</span></div>
                        <div class="sp2dd-field"><span class="k">Nomor NPI</span><span class="v">{{ $npi->nomor_npi ?? '-' }}</span></div>
                        <div class="sp2dd-field"><span class="k">Tanggal NPI</span><span class="v">{{ $npi->tanggal_npi ? \Carbon\Carbon::parse($npi->tanggal_npi)->format('d M Y') : '-' }}</span></div>
                        <div class="sp2dd-field"><span class="k">Status NPI</span><span class="v">{{ str_replace('_',' ',$npi->status) }}</span></div>
                        <div class="sp2dd-field"><span class="k">Uraian</span><span class="v">{{ $kontrak?->nama_pekerjaan ?? '-' }}</span></div>
                    </div>
                </div>
            </div>

            {{-- SPM & SPP --}}
            <div class="sp2dd-card">
                <div class="sp2dd-card__head"><div class="sp2dd-card__title"><i class="material-icons-outlined">request_quote</i> Ringkasan SPM &amp; SPP</div></div>
                <div class="sp2dd-card__body">
                    <div class="sp2dd-grid">
                        <div class="sp2dd-field"><span class="k">Nomor SPM</span><span class="v">{{ $spm?->nomor_spm ?? '-' }}</span></div>
                        <div class="sp2dd-field"><span class="k">Nomor SPP</span><span class="v">{{ $spp?->nomor_spp ?? '-' }}</span></div>
                        <div class="sp2dd-field"><span class="k">Nomor Tagihan</span><span class="v">{{ $tagihan?->nomor_tagihan ?? '-' }}</span></div>
                        <div class="sp2dd-field"><span class="k">Nilai Tagihan Akhir</span><span class="v" style="color:var(--d-green);">Rp {{ number_format($nominalSp2d, 0, ',', '.') }}</span></div>
                    </div>
                </div>
            </div>

            {{-- Dasar Kontrak --}}
            <div class="sp2dd-card">
                <div class="sp2dd-card__head"><div class="sp2dd-card__title"><i class="material-icons-outlined">assignment</i> Dasar Kontrak</div></div>
                <div class="sp2dd-card__body">
                    <div class="sp2dd-grid">
                        <div class="sp2dd-field" style="grid-column: 1 / -1;"><span class="k">Nama Pekerjaan</span><span class="v">{{ $kontrak?->nama_pekerjaan ?? '-' }}</span></div>
                        <div class="sp2dd-field"><span class="k">Nomor SPK</span><span class="v">{{ $kontrak?->nomor_spk ?? '-' }}</span></div>
                        <div class="sp2dd-field"><span class="k">Termin</span><span class="v">{{ $termin?->termin_ke ?? '-' }} ({{ $termin?->jenis_termin ?? '-' }})</span></div>
                        <div class="sp2dd-field"><span class="k">BAST</span><span class="v">{{ $detailKontrak?->nomor_bast ?? '-' }}</span></div>
                        <div class="sp2dd-field"><span class="k">BAPP</span><span class="v">{{ $detailKontrak?->nomor_bapp ?? '-' }}</span></div>
                        <div class="sp2dd-field"><span class="k">BAP</span><span class="v">{{ $detailKontrak?->nomor_bap ?? '-' }}</span></div>
                    </div>
                </div>
            </div>

            {{-- Vendor & Rekening --}}
            <div class="sp2dd-card">
                <div class="sp2dd-card__head"><div class="sp2dd-card__title"><i class="material-icons-outlined">account_balance</i> Vendor &amp; Rekening Tujuan</div></div>
                <div class="sp2dd-card__body">
                    <div class="sp2dd-grid">
                        <div class="sp2dd-field" style="grid-column: 1 / -1;"><span class="k">Nama Vendor</span><span class="v">{{ $vendor?->nama_pihak ?? '-' }}</span></div>
                        <div class="sp2dd-field"><span class="k">Bank</span><span class="v">{{ $rekening?->nama_bank ?? '-' }}</span></div>
                        <div class="sp2dd-field"><span class="k">No. Rekening</span><span class="v mono">{{ $rekening?->nomor_rekening ?? '-' }}</span></div>
                        <div class="sp2dd-field" style="grid-column: 1 / -1;"><span class="k">Atas Nama</span><span class="v">{{ $rekening?->nama_rekening ?? '-' }}</span></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ===== RIGHT: ACTION ===== --}}
        <div class="col-xl-5">
            <div class="sticky-side d-flex flex-column gap-4">

                {{-- Form / state card --}}
                <div class="sp2dd-form-card">
                    <div class="sp2dd-form-card__head"><i class="material-icons-outlined">draw</i> Informasi SP2D</div>
                    <div class="sp2dd-form-card__body">
                        <div class="sp2dd-readline"><span class="k">Nomor SP2D</span><span class="v mono" style="color:var(--d-primary);">{{ $sp2d?->nomor_sp2d ?? '[ Belum diisi ]' }}</span></div>
                        <div class="sp2dd-readline"><span class="k">Tanggal SP2D</span><span class="v">{{ $sp2d?->tanggal_sp2d ? \Carbon\Carbon::parse($sp2d->tanggal_sp2d)->format('d M Y') : '[ Belum diisi ]' }}</span></div>
                        <div class="sp2dd-amount-box"><span class="k">Nilai Netto SP2D</span><span class="v">Rp {{ number_format($nominalSp2d, 0, ',', '.') }}</span></div>

                        @if($isEditable)
                            <button type="button" class="sp2dd-btn sp2dd-btn--warn" data-bs-toggle="modal" data-bs-target="#modalDraftSave"><i class="material-icons-outlined">edit</i> Edit Draft SP2D</button>
                            @if($canSubmit)
                                <button type="button" class="sp2dd-btn sp2dd-btn--primary mt-2" data-bs-toggle="modal" data-bs-target="#modalSubmit"><i class="material-icons-outlined">publish</i> Ajukan Verifikasi</button>
                            @endif
                        @else
                            @php $buktiTransferSp2d = $sp2d?->bukti_transfer; @endphp

                            @if(in_array($statusSp2d, ['DISETUJUI_FINAL', 'SP2D_TERBIT', 'MENUNGGU_UPLOAD']))
                                <div class="sp2dd-pay">
                                    <div class="sp2dd-pay__banner">
                                        <i class="material-icons-outlined">verified</i>
                                        <div>
                                            <div class="t">SP2D disetujui seluruh verifikator</div>
                                            <div class="s">Unggah bukti transfer untuk menyelesaikan tagihan &amp; mencatat ke BKU.</div>
                                        </div>
                                    </div>
                                    <form action="{{ route('sp2ds.catat-bku', $sp2d->id) }}" method="POST" enctype="multipart/form-data" id="formBuktiTransfer">
                                        @csrf
                                        <label class="sp2dd-label">Keterangan Transfer <span class="text-muted">(opsional)</span></label>
                                        <textarea name="catatan_bku" class="sp2dd-input mb-3" rows="2" placeholder="Contoh: Transfer pembayaran kontrak {{ $tagihan?->nomor_tagihan }}"></textarea>

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
                            @elseif($statusSp2d === 'EXECUTED')
                                <div class="sp2dd-pay__banner" style="background:linear-gradient(120deg,#eef2ff,#e0f2fe);border-color:rgba(79,70,229,.25);">
                                    <i class="material-icons-outlined" style="color:var(--d-primary);">task_alt</i>
                                    <div>
                                        <div class="t">Tagihan SELESAI &amp; bukti transfer terunggah</div>
                                        <div class="s">Lanjutkan penyetoran pajak kontrak. Setelah NTPN lengkap, masuk BKU.</div>
                                        @if($buktiTransferSp2d)
                                            <a href="{{ route('arsip-sensitif.download', $buktiTransferSp2d->id) }}" class="small fw-semibold text-primary d-inline-flex align-items-center gap-1 mt-1"><i class="material-icons-outlined" style="font-size:15px;">visibility</i> Lihat bukti transfer</a>
                                        @endif
                                    </div>
                                </div>
                            @endif
                        @endif
                    </div>
                </div>

                {{-- Revisi notes --}}
                @if($revisionNotes->count() > 0)
                <div class="sp2dd-card" style="border-color: rgba(225,29,72,.3);">
                    <div class="sp2dd-card__head"><div class="sp2dd-card__title" style="color:var(--d-rose);"><i class="material-icons-outlined" style="color:var(--d-rose);">replay</i> Catatan Revisi Verifikator</div></div>
                    <div class="sp2dd-card__body">
                        <div class="sp2dd-tl">
                            @foreach($revisionNotes as $note)
                                <div class="sp2dd-tl__item" style="animation-delay: {{ $loop->index * 0.06 }}s;">
                                    <span class="sp2dd-tl__dot" style="background:var(--d-rose);box-shadow:0 0 0 4px rgba(225,29,72,.15);"></span>
                                    <div class="sp2dd-tl__t">{{ $note['role'] }}</div>
                                    <div class="sp2dd-tl__m">{{ $note['user'] }} &bull; {{ $note['time'] }}</div>
                                    <div class="sp2dd-tl__note">"{{ $note['catatan'] }}"</div>
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

{{-- ===== MODALS ===== --}}
@if($isEditable)
<div class="modal fade" id="modalDraftSave" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 1rem; overflow: hidden;">
            <div class="modal-header border-0 text-white p-4" style="background: linear-gradient(135deg, #4f46e5, #7c3aed);">
                <div>
                    <h5 class="modal-title fw-bold mb-1"><i class="material-icons-outlined me-1" style="font-size:20px; vertical-align:middle;">edit</i> Edit Draft SP2D</h5>
                    <div class="small opacity-75">Kontrak &mdash; {{ $kontrak?->nama_pekerjaan ?? 'Pencairan SP2D' }}</div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="formDraftSp2d" action="{{ route('sp2ds.kontrak.store', $npi->id) }}" method="POST">
                @csrf
                <input type="hidden" id="submitFormFlag" name="is_submit" value="0">
                <div class="modal-body p-4">
                    <div class="alert alert-info border-0 py-2 d-flex align-items-center gap-2 mb-4" style="font-size:.85rem;">
                        <i class="material-icons-outlined" style="font-size:18px;">info</i>
                        <span>Isi data pencatatan SP2D. Setelah disimpan, Anda dapat mengajukan verifikasi.</span>
                    </div>
                    <div class="mb-3">
                        <label class="sp2dd-label">Nomor SP2D <span class="text-danger">*</span></label>
                        <input type="text" name="nomor_sp2d" class="sp2dd-input" required value="{{ old('nomor_sp2d', $sp2d?->nomor_sp2d ?? $autoNomorSp2d) }}" placeholder="Contoh: 1234/SP2D/2026">
                        <small class="text-muted"><i class="bi bi-info-circle me-1"></i>Diturunkan dari SPP, ubah jika perlu.</small>
                    </div>
                    <div class="mb-3">
                        <label class="sp2dd-label">Tanggal SP2D <span class="text-danger">*</span></label>
                        <input type="date" name="tanggal_sp2d" class="sp2dd-input" required value="{{ old('tanggal_sp2d', $sp2d?->tanggal_sp2d ? \Carbon\Carbon::parse($sp2d->tanggal_sp2d)->format('Y-m-d') : date('Y-m-d')) }}">
                    </div>
                    <div class="sp2dd-amount-box"><span class="k">Nilai Netto SP2D</span><span class="v">Rp {{ number_format($nominalSp2d, 0, ',', '.') }}</span></div>
                </div>
                <div class="modal-footer border-0 bg-light px-4 py-3">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning fw-bold px-4 text-white"><i class="material-icons-outlined me-1" style="font-size:16px; vertical-align:middle;">save</i> Simpan Draft</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

@if($canSubmit)
<div class="modal fade" id="modalSubmit" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 1rem; overflow: hidden;">
            <form action="{{ route('sp2ds.kontrak.submit', $npi->id) }}" method="POST">
                @csrf
                <div class="modal-header border-0 text-white p-4" style="background: linear-gradient(135deg, #1d4ed8, #0891b2);">
                    <div>
                        <h5 class="modal-title fw-bold mb-1"><i class="material-icons-outlined me-1" style="font-size:20px; vertical-align:middle;">publish</i> Ajukan Verifikasi SP2D</h5>
                        <div class="small opacity-75">{{ $sp2d?->nomor_sp2d ?? '-' }} &bull; {{ $kontrak?->nama_pekerjaan ?? 'Kontrak' }}</div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="alert alert-warning border-0 py-2 d-flex align-items-start gap-2 mb-4" style="font-size:.85rem;">
                        <i class="material-icons-outlined mt-1" style="font-size:18px;">warning</i>
                        <span>Setelah pengajuan, draft SP2D akan <strong>dikunci</strong> dan masuk proses verifikasi paralel.</span>
                    </div>
                    <div class="sp2dd-amount-box"><span class="k">Nominal SP2D</span><span class="v">Rp {{ number_format($nominalSp2d, 0, ',', '.') }}</span></div>
                    <div class="fw-semibold small mb-2">Notifikasi dikirim ke:</div>
                    <ul class="list-unstyled mb-0" style="font-size:13px;">
                        <li class="d-flex align-items-center gap-2 mb-2"><i class="material-icons-outlined text-primary" style="font-size:16px;">person</i> <strong>PPK</strong></li>
                        <li class="d-flex align-items-center gap-2 mb-2"><i class="material-icons-outlined text-primary" style="font-size:16px;">person</i> <strong>Kepala Subbagian Keuangan dan Tata Usaha</strong></li>
                        <li class="d-flex align-items-center gap-2 mb-2"><i class="material-icons-outlined text-primary" style="font-size:16px;">person</i> <strong>PPSPM</strong></li>
                        <li class="d-flex align-items-center gap-2"><i class="material-icons-outlined text-primary" style="font-size:16px;">person</i> <strong>Koordinator Keuangan</strong></li>
                    </ul>
                </div>
                <div class="modal-footer border-0 bg-light px-4 py-3">
                    <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary fw-bold px-4"><i class="material-icons-outlined me-1" style="font-size:16px; vertical-align:middle;">send</i> Ajukan Sekarang</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

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

    clearBtn.addEventListener('click', function () {
        input.value = '';
        render();
    });

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
