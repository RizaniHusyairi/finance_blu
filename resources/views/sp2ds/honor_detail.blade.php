@extends('layouts.app')
@section('title', 'Detail SP2D Honorarium')

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
    $statusLabel = $sp2d ? str_replace('_', ' ', $sp2d->status) : 'Siap Dibuat';
    $canEdit = is_null($sp2d) || in_array($sp2d->status, [DokumenSp2d::STATUS_DRAFT, DokumenSp2d::STATUS_REVISI]);
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
                <span class="sp2dd-eyebrow"><i class="material-icons-outlined" style="font-size:15px;">payments</i> Detail Pencatatan SP2D · Honorarium</span>
                <h1>{{ $tagihan?->deskripsi ?? 'Pencairan SP2D Honorarium' }}</h1>
                <p class="sub">Ref Tagihan {{ $tagihan?->nomor_tagihan ?? '-' }}</p>

                <div class="sp2dd-hero__meta">
                    <div><div class="k">Nomor SP2D</div><div class="v">{{ $sp2d?->nomor_sp2d ?? '[ Draft baru ]' }}</div></div>
                    <div><div class="k">Nomor NPI</div><div class="v">{{ $npi->nomor_npi ?? '-' }}</div></div>
                    <div><div class="k">Penerima</div><div class="v">{{ $rekeningPenerima->count() }} Orang</div></div>
                </div>
                <div class="sp2dd-hero__amount"><span class="k">Nilai Netto NPI</span><span class="v">Rp {{ number_format($defaultNilai, 0, ',', '.') }}</span></div>
            </div>

            <div class="d-flex flex-column gap-2 align-items-stretch align-items-xl-end" style="min-width: 210px;">
                <span class="sp2dd-status sp2dd-status--{{ $statusVariant }}"><span class="dot"></span> {{ $statusLabel }}</span>
                <a href="{{ route('sp2ds.honor.index') }}" class="sp2dd-hbtn"><i class="material-icons-outlined">arrow_back</i> Kembali</a>
                @if($isSP2DFinal)
                    <a href="{{ route('sp2ds.cetak-pdf', $sp2d->id) }}" target="_blank" class="sp2dd-hbtn sp2dd-hbtn--solid"><i class="material-icons-outlined">print</i> Cetak PDF</a>
                @endif
            </div>
        </div>
    </div>

    {{-- ===== STEPPER ===== --}}
    @include('sp2ds.partials.detail-stepper', ['progressStep' => $progressStep, 'stepFail' => $stepFail])

    {{-- ===== VERIFIKATOR ===== --}}
    @if($sp2d && $workflow)
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
            {{-- Dokumen Sumber Anggaran --}}
            <div class="sp2dd-card">
                <div class="sp2dd-card__head"><div class="sp2dd-card__title"><i class="material-icons-outlined">folder_open</i> Dokumen Sumber Anggaran</div></div>
                <div class="sp2dd-card__body">
                    <div class="sp2dd-grid mb-3">
                        <div class="sp2dd-field"><span class="k">No. SPP</span><span class="v">{{ $spp?->nomor_spp ?? '-' }}</span></div>
                        <div class="sp2dd-field"><span class="k">No. SPM</span><span class="v">{{ $spm?->nomor_spm ?? '-' }}</span></div>
                        <div class="sp2dd-field" style="grid-column: 1 / -1;"><span class="k">Beban / COA Honorarium</span><span class="v">{{ $spp?->tagihanHonorarium?->dipaRevisionItem?->coa?->kode ?? '-' }} — {{ $spp?->tagihanHonorarium?->dipaRevisionItem?->coa?->name ?? '-' }}</span></div>
                    </div>
                    <div class="sp2dd-mini">
                        <div class="sp2dd-minicard"><div class="k">Nilai Bruto</div><div class="v">Rp {{ number_format($tagihan?->total_bruto ?? 0, 0, ',', '.') }}</div></div>
                        <div class="sp2dd-minicard"><div class="k" style="color:var(--d-rose);">Potongan PPh</div><div class="v" style="color:var(--d-rose);">Rp {{ number_format($tagihan?->total_potongan ?? 0, 0, ',', '.') }}</div></div>
                        <div class="sp2dd-minicard green" style="grid-column: span 2;"><div class="k">Netto Ril NPI (SP2D)</div><div class="v">Rp {{ number_format($tagihan?->total_netto ?? 0, 0, ',', '.') }}</div></div>
                    </div>
                </div>
            </div>

            {{-- Rincian Personel --}}
            <div class="sp2dd-card">
                <div class="sp2dd-card__head"><div class="sp2dd-card__title"><i class="material-icons-outlined">groups</i> Rincian Personel ({{ $rekeningPenerima->count() }} Orang)</div></div>
                <div class="table-responsive" style="max-height: 400px;">
                    <table class="sp2dd-table">
                        <thead><tr><th>Nama &amp; Jabatan</th><th>Rekening Transfer</th><th class="text-end">Netto</th></tr></thead>
                        <tbody>
                            @foreach($rekeningPenerima as $p)
                                <tr>
                                    <td><div class="fw-bold" style="color:var(--d-ink);">{{ $p['nama'] }}</div><div class="sp2dd-tl__m">{{ $p['jabatan'] }}</div></td>
                                    <td>
                                        @if($p['rekening'] === 'KOSONG')
                                            <span class="sp2dd-vpill bad"><i class="material-icons-outlined">error</i> Rekening Kosong</span>
                                        @else
                                            <div class="fw-bold" style="color:var(--d-primary);">{{ $p['bank'] }} - {{ $p['rekening'] }}</div>
                                            <div class="sp2dd-tl__m">A/n {{ $p['nama_rekening'] }}</div>
                                        @endif
                                    </td>
                                    <td class="text-end fw-bold" style="color:var(--d-green);">Rp {{ number_format($p['netto'], 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Lampiran --}}
            <div class="sp2dd-card">
                <div class="sp2dd-card__head"><div class="sp2dd-card__title"><i class="material-icons-outlined">attach_file</i> Dokumen Lampiran NPI / SPP</div></div>
                <div class="sp2dd-card__body">
                    <div class="d-flex flex-wrap gap-2">
                        @forelse($tagihan?->arsipDokumen ?? [] as $arsip)
                            <a href="{{ Storage::url($arsip->file_path) }}" target="_blank" class="sp2dd-hbtn" style="color:var(--d-ink); background:#f1f5f9; border-color:var(--d-line);"><i class="material-icons-outlined" style="color:var(--d-rose);">picture_as_pdf</i> {{ Str::limit($arsip->nama_dokumen, 22) }}</a>
                        @empty
                            <div class="text-muted small">Tidak ada dokumen sisipan.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        {{-- ===== RIGHT ===== --}}
        <div class="col-xl-5">
            <div class="sticky-side d-flex flex-column gap-4">

                {{-- Checklist --}}
                <div class="sp2dd-card mb-0">
                    <div class="sp2dd-card__head"><div class="sp2dd-card__title"><i class="material-icons-outlined">verified_user</i> Indikator Kesiapan</div></div>
                    <div class="sp2dd-card__body">
                        @php
                            $checklist = [
                                ['ok' => $checks['npi_final'], 't' => 'NPI Disetujui Final', 's' => 'Sumber dana ber-TTE'],
                                ['ok' => $checks['tagihan_ada'] && $checks['spp_tersedia'], 't' => 'Pohon Relasi Lengkap', 's' => 'SPP · SPM · NPI'],
                                ['ok' => $checks['rekening_valid'], 't' => 'Validitas Rekening', 's' => 'Tidak ada rekening rumpang'],
                                ['ok' => $checks['sp2d_valid'], 't' => 'Draft SP2D Tersimpan', 's' => 'Nomor & tanggal terisi'],
                            ];
                        @endphp
                        @foreach($checklist as $c)
                            <div class="sp2dd-check {{ $c['ok'] ? 'ok' : 'no' }}">
                                <div class="sp2dd-check__ic"><i class="material-icons-outlined">{{ $c['ok'] ? 'check' : 'close' }}</i></div>
                                <div><div class="sp2dd-check__t">{{ $c['t'] }}</div><div class="sp2dd-check__s">{{ $c['s'] }}</div></div>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Form / state --}}
                <div class="sp2dd-form-card">
                    <div class="sp2dd-form-card__head"><i class="material-icons-outlined">card_membership</i> Pencatatan SP2D Honorarium</div>
                    <div class="sp2dd-form-card__body">
                        <div class="sp2dd-readline"><span class="k">Nomor SP2D</span><span class="v mono" style="color:var(--d-primary);">{{ $sp2d?->nomor_sp2d ?? '[ Belum diisi ]' }}</span></div>
                        <div class="sp2dd-readline"><span class="k">Tanggal SP2D</span><span class="v">{{ $sp2d?->tanggal_sp2d ? optional($sp2d->tanggal_sp2d)->format('d M Y') : '[ Belum diisi ]' }}</span></div>
                        <div class="sp2dd-amount-box"><span class="k">Nilai Netto</span><span class="v">Rp {{ number_format($defaultNilai, 0, ',', '.') }}</span></div>

                        @if($canEdit)
                            <button type="button" class="sp2dd-btn sp2dd-btn--warn" data-bs-toggle="modal" data-bs-target="#modalEditDraftSp2d"><i class="material-icons-outlined">edit</i> Edit Draft SP2D</button>
                            <form action="{{ route('sp2ds.honor.submit', $npi->id) }}" method="POST" class="mt-2" onsubmit="return confirm('Pengajuan akan mengunci Draf dan mengoper SP2D ke verifikator (PPK, Kasubbag, PPSPM, Koordinator). Lanjutkan?');">
                                @csrf
                                <button type="submit" class="sp2dd-btn sp2dd-btn--success" {{ !$checks['sp2d_valid'] ? 'disabled' : '' }}><i class="material-icons-outlined">send</i> Ajukan Verifikasi SP2D</button>
                                @if(!$checks['sp2d_valid'])
                                    <div class="text-danger small mt-2 text-center">Simpan draft melalui tombol kuning terlebih dahulu.</div>
                                @endif
                            </form>
                        @elseif(in_array($sp2d?->status, [\App\Models\DokumenSp2d::STATUS_DISETUJUI_FINAL, \App\Models\DokumenSp2d::STATUS_SP2D_TERBIT, \App\Models\DokumenSp2d::STATUS_MENUNGGU_UPLOAD]))
                            <div class="sp2dd-pay">
                                <div class="sp2dd-pay__banner">
                                    <i class="material-icons-outlined">verified</i>
                                    <div>
                                        <div class="t">SP2D disetujui seluruh verifikator</div>
                                        <div class="s">Unggah bukti transfer untuk menyelesaikan tagihan honorarium.</div>
                                    </div>
                                </div>
                                <form action="{{ route('sp2ds.catat-bku', $sp2d->id) }}" method="POST" enctype="multipart/form-data" id="formBuktiTransfer">
                                    @csrf
                                    <label class="sp2dd-label">Keterangan Transfer <span class="text-muted">(opsional)</span></label>
                                    <textarea name="catatan_bku" class="sp2dd-input mb-3" rows="2" placeholder="Contoh: Transfer honorarium {{ $tagihan?->nomor_tagihan }}"></textarea>

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
                        @elseif($sp2d?->status === \App\Models\DokumenSp2d::STATUS_EXECUTED)
                            @php $buktiTransferSp2d = $sp2d?->bukti_transfer; @endphp
                            <div class="sp2dd-pay__banner" style="background:linear-gradient(120deg,#eef2ff,#e0f2fe);border-color:rgba(79,70,229,.25);">
                                <i class="material-icons-outlined" style="color:var(--d-primary);">task_alt</i>
                                <div>
                                    <div class="t">Tagihan SELESAI &amp; bukti transfer terunggah</div>
                                    <div class="s">Lanjutkan penyetoran pajak honorarium. Setelah NTPN lengkap, masuk BKU.</div>
                                    @if($buktiTransferSp2d)
                                        <a href="{{ route('arsip-sensitif.download', $buktiTransferSp2d->id) }}" class="small fw-semibold text-primary d-inline-flex align-items-center gap-1 mt-1"><i class="material-icons-outlined" style="font-size:15px;">visibility</i> Lihat bukti transfer</a>
                                    @endif
                                </div>
                            </div>
                        @else
                            <div class="sp2dd-locked">
                                <i class="material-icons-outlined">lock</i>
                                <div><div class="fw-bold small">Draft terkunci (read-only)</div><div class="sp2dd-tl__m">SP2D sedang dalam lajur persetujuan dan tak dapat diubah.</div></div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@if($canEdit)
<div class="modal fade" id="modalEditDraftSp2d" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 1rem; overflow: hidden;">
            <div class="modal-header border-0 text-white p-4" style="background: linear-gradient(135deg, #4f46e5, #7c3aed);">
                <div>
                    <h5 class="modal-title fw-bold mb-1"><i class="material-icons-outlined me-1" style="font-size:20px; vertical-align:middle;">edit</i> Edit Draft SP2D</h5>
                    <div class="small opacity-75">Honorarium &mdash; {{ $tagihan?->deskripsi ?? 'Pencairan SP2D' }}</div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('sp2ds.honor.store', $npi->id) }}" method="POST" id="formDraftSp2d">
                @csrf
                <div class="modal-body p-4">
                    <div class="alert alert-info border-0 py-2 d-flex align-items-center gap-2 mb-4" style="font-size:.85rem;">
                        <i class="material-icons-outlined" style="font-size:18px;">info</i>
                        <span>Isi data pencatatan SP2D. Setelah disimpan, Anda dapat mengajukan verifikasi.</span>
                    </div>
                    <div class="mb-3">
                        <label class="sp2dd-label">Nomor SP2D <span class="text-danger">*</span></label>
                        <input type="text" name="nomor_sp2d" class="sp2dd-input mono" value="{{ old('nomor_sp2d', $sp2d?->nomor_sp2d ?? $autoNomorSp2d) }}" required placeholder="Contoh: 001/SP2D/2026">
                        <small class="text-muted"><i class="bi bi-info-circle me-1"></i>Diturunkan dari SPP, ubah jika perlu.</small>
                    </div>
                    <div class="mb-3">
                        <label class="sp2dd-label">Tanggal SP2D <span class="text-danger">*</span></label>
                        <input type="date" name="tanggal_sp2d" class="sp2dd-input" value="{{ old('tanggal_sp2d', $sp2d?->tanggal_sp2d?->format('Y-m-d') ?? date('Y-m-d')) }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="sp2dd-label">Catatan Bendahara (Opsional)</label>
                        <textarea name="catatan" class="sp2dd-input" rows="3" placeholder="Informasi tambahan...">{{ old('catatan', '') }}</textarea>
                    </div>
                    <div class="sp2dd-amount-box"><span class="k">Nilai Netto SP2D</span><span class="v">Rp {{ number_format($defaultNilai, 0, ',', '.') }}</span></div>
                </div>
                <div class="modal-footer border-0 bg-light px-4 py-3">
                    <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning fw-bold px-4 text-white"><i class="material-icons-outlined me-1" style="font-size:16px; vertical-align:middle;">save</i> Simpan Draft</button>
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
