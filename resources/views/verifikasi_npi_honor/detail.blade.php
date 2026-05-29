@extends('layouts.app')
@section('title', 'Verifikasi NPI Honorarium')

@push('css')
    @include('verifikasi_npi._styles')
@endpush

@section('content')
@php
    use App\Models\DokumenNpi;

    $finalStatuses = [DokumenNpi::STATUS_DISETUJUI_FINAL, DokumenNpi::STATUS_APPROVED_KASUBAG, DokumenNpi::STATUS_NPI_TERBIT, DokumenNpi::STATUS_MENUNGGU_UPLOAD];
    $isNpiFinal = in_array($npi->status, [DokumenNpi::STATUS_DISETUJUI_FINAL, DokumenNpi::STATUS_APPROVED_KASUBAG, DokumenNpi::STATUS_NPI_TERBIT], true);
    $isRevisi   = $npi->status === DokumenNpi::STATUS_REVISI;

    if ($isNpiFinal)      { $heroClass = 'is-final';  $statusKey = 's-final';  }
    elseif ($isRevisi)    { $heroClass = 'is-revisi'; $statusKey = 's-revisi'; }
    else                  { $heroClass = '';          $statusKey = 's-wait';   }

    $allApprovals = collect($wf?->approvals ?? [])->sortBy('urutan_step');
    $personelList = collect($tagihan?->detailHonorarium ?? []);
    $jumlahPenerima = $personelList->count();

    $readyCount = collect($checklist)->where('status', 'ready')->count();
    $totalReady = is_countable($checklist) ? count($checklist) : 0;
    $readyPct   = $totalReady > 0 ? round($readyCount / $totalReady * 100) : 0;

    $coa = $spm?->dipaRevisionItem?->coa;

    $arsipList = collect($tagihan?->arsipDokumen ?? [])->filter(fn($a) => ($a->is_active ?? true));

    $statusMeta = [
        'APPROVED' => ['badge' => 'bg-success', 'cls' => 'is-approved', 'ico' => 'bx-check', 'label' => 'Disetujui'],
        'PENDING'  => ['badge' => 'bg-warning text-dark', 'cls' => 'is-pending', 'ico' => 'bx-time-five', 'label' => 'Menunggu'],
        'REVISION' => ['badge' => 'bg-danger', 'cls' => 'is-revision', 'ico' => 'bx-undo', 'label' => 'Revisi'],
        'REJECTED' => ['badge' => 'bg-danger', 'cls' => 'is-revision', 'ico' => 'bx-x', 'label' => 'Ditolak'],
        'WAITING'  => ['badge' => 'bg-light text-dark border', 'cls' => 'is-waiting', 'ico' => 'bx-minus', 'label' => 'Antri'],
    ];
@endphp

@if(session('success'))
    <div class="alert alert-success border-0 shadow-sm alert-dismissible fade show">
        <i class="bx bx-check-circle me-1"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
@if(session('error'))
    <div class="alert alert-danger border-0 shadow-sm alert-dismissible fade show">
        <i class="bx bx-error-circle me-1"></i> {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
@if($errors->any())
    <div class="alert alert-danger border-0 shadow-sm alert-dismissible fade show">
        <ul class="mb-0 ps-3">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

{{-- ====== HERO ====== --}}
<div class="card vnpi-hero {{ $heroClass }} mb-4">
    <div class="card-body p-4">
        <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-3">
            <div class="npi-min-w-0">
                <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                    <span class="hero-tag"><i class='bx bx-money me-1'></i> Verifikasi NPI Honorarium</span>
                    <span class="vnpi-pill {{ $statusKey }}">{{ str_replace('_', ' ', $npi->status) }}</span>
                    @if($canVerify)<span class="vnpi-pill s-alert"><i class="bx bx-bell"></i> Menunggu Aksi Anda</span>@endif
                </div>
                <h3 class="fw-bold mb-1 text-break">{{ $npi->nomor_npi ?? 'NPI Belum Bernomor' }}</h3>
                <div class="hero-sub small text-break"><i class='bx bx-receipt me-1'></i>{{ $tagihan?->deskripsi ?? 'NPI Honorarium' }}</div>
            </div>
            <div class="d-flex flex-wrap gap-2 justify-content-lg-end">
                <a href="{{ route('verifikasi-npi.honor.index') }}" class="btn btn-hero-light"><i class='bx bx-arrow-back'></i> Kembali</a>
                @if($isNpiFinal)
                    <a href="{{ route('npis.cetak-pdf', $npi->id) }}" target="_blank" class="btn btn-light fw-semibold"><i class='bx bxs-file-pdf text-danger'></i> Cetak PDF</a>
                @endif
            </div>
        </div>

        <div class="hero-meta p-3">
            <div class="row g-3">
                <div class="col-6 col-md-3"><div class="field-label">Nomor SPM</div><div class="field-value text-truncate">{{ $spm?->nomor_spm ?? '-' }}</div></div>
                <div class="col-6 col-md-3"><div class="field-label">Nomor SPP</div><div class="field-value text-truncate">{{ $spp?->nomor_spp ?? '-' }}</div></div>
                <div class="col-6 col-md-2"><div class="field-label">Jumlah Penerima</div><div class="field-value">{{ $jumlahPenerima }} Orang</div></div>
                <div class="col-6 col-md-4">
                    <div class="field-label">Nilai NPI (Netto)</div>
                    <div class="nominal-hero">Rp {{ number_format($spm->nominal_spm ?? $tagihan?->total_netto ?? 0, 0, ',', '.') }}</div>
                </div>
            </div>
            <hr class="my-3" style="border-color: rgba(255,255,255,.18);">
            <div class="row g-3">
                <div class="col-md-6"><div class="field-label">Tujuan Penerimaan (Bendahara Penerimaan)</div><div class="field-value"><i class="bx bx-user-check"></i> {{ $npi->bendaharaPenerimaan?->name ?? 'Belum ditunjuk' }}</div></div>
                <div class="col-md-6"><div class="field-label">Tanggal NPI</div><div class="field-value">{{ optional($npi->tanggal_npi)->format('d F Y') ?? '-' }}</div></div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    {{-- ====== KOLOM KIRI ====== --}}
    <div class="col-xl-8">

        {{-- Progress Verifikasi Paralel --}}
        <div class="vnpi-card c-rose mb-4">
            <div class="vnpi-card-head">
                <span class="ico-wrap"><i class="bx bx-git-merge"></i></span>
                <div><h6>Progress Verifikasi Paralel</h6><span class="head-sub">Persetujuan mufakat seluruh verifikator</span></div>
            </div>
            <div class="vnpi-card-body">
                @forelse($allApprovals as $ap)
                    @php
                        $meta = $statusMeta[$ap->status] ?? $statusMeta['WAITING'];
                        $isMine = in_array($ap->role_code, $roleCodes ?? [], true);
                    @endphp
                    <div class="approval-row {{ $meta['cls'] }} {{ $isMine ? 'is-mine' : '' }}">
                        <div class="d-flex align-items-center gap-3">
                            <span class="role-avatar"><i class="bx {{ $meta['ico'] }}"></i></span>
                            <div class="flex-grow-1 npi-min-w-0">
                                <div class="d-flex justify-content-between align-items-start gap-2">
                                    <div class="fw-bold text-dark">
                                        {{ $ap->role_code }}
                                        @if($isMine)<span class="badge bg-primary bg-opacity-10 text-primary ms-1" style="font-size:9px;">PERAN ANDA</span>@endif
                                    </div>
                                    <span class="approval-badge {{ $meta['badge'] }}">{{ $meta['label'] }}</span>
                                </div>
                                <div class="text-muted small">{{ $ap->actedByUser?->name ?? $ap->assignedUser?->name ?? 'Semua pemegang peran' }}</div>
                                @if($ap->acted_at)
                                    <div class="text-muted" style="font-size:.7rem;"><i class="bx bx-time"></i> {{ \Carbon\Carbon::parse($ap->acted_at)->format('d M Y H:i') }}</div>
                                @endif
                                @if($ap->catatan)
                                    <div class="small fst-italic text-muted mt-1 p-2 bg-light rounded">"{{ $ap->catatan }}"</div>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center text-muted small py-3">Belum ada antrean verifikasi.</div>
                @endforelse
            </div>
        </div>

        {{-- Dokumen Sumber --}}
        <div class="vnpi-card c-blue mb-4">
            <div class="vnpi-card-head">
                <span class="ico-wrap"><i class="bx bx-file"></i></span>
                <div><h6>Dokumen Sumber & Anggaran</h6><span class="head-sub">Penelusuran SPM / SPP / Tagihan</span></div>
            </div>
            <div class="vnpi-card-body">
                <div class="row g-3">
                    <div class="col-md-4"><span class="field-label">Nomor SPM</span><span class="field-value">{{ $spm?->nomor_spm ?? '-' }}</span><div class="text-muted small">{{ optional($spm?->tanggal_spm)->format('d M Y') ?? '-' }}</div></div>
                    <div class="col-md-4"><span class="field-label">Nomor SPP</span><span class="field-value">{{ $spp?->nomor_spp ?? '-' }}</span><div class="text-muted small">{{ optional($spp?->tanggal_spp)->format('d M Y') ?? '-' }}</div></div>
                    <div class="col-md-4"><span class="field-label">Nomor Tagihan</span><span class="field-value">{{ $tagihan?->nomor_tagihan ?? '-' }}</span><div class="text-muted small">PPK: {{ $tagihan?->spp?->ppkVerifikator?->name ?? '-' }}</div></div>
                    <div class="col-12">
                        <span class="field-label">Kode COA (Mata Anggaran)</span>
                        @if($coa)
                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 font-monospace">{{ $coa->kode_mak_lengkap ?? $coa->kd_akun }}</span>
                            <span class="small fw-normal d-block mt-1 text-muted">{{ $coa->nama_akun }}</span>
                        @else
                            <span class="text-muted fst-italic">Belum ada akun DIPA tertaut.</span>
                        @endif
                    </div>
                </div>
                <div class="row g-2 mt-2">
                    <div class="col-md-4"><div class="mini-doc" style="--card-accent:#2563eb;"><div class="doc-label">Bruto</div><div class="fw-bold">Rp {{ number_format($tagihan?->total_bruto ?? 0, 0, ',', '.') }}</div></div></div>
                    <div class="col-md-4"><div class="mini-doc" style="--card-accent:#e11d48;"><div class="doc-label text-danger">Potongan PPh</div><div class="fw-bold text-danger">Rp {{ number_format($tagihan?->total_potongan ?? 0, 0, ',', '.') }}</div></div></div>
                    <div class="col-md-4"><div class="mini-doc" style="--card-accent:#16a34a;"><div class="doc-label text-success">Netto NPI</div><div class="fw-bold text-success">Rp {{ number_format($tagihan?->total_netto ?? 0, 0, ',', '.') }}</div></div></div>
                </div>
            </div>
        </div>

        {{-- Rincian Penerima --}}
        <div class="vnpi-card c-teal mb-4">
            <div class="vnpi-card-head">
                <span class="ico-wrap"><i class="bx bx-group"></i></span>
                <div class="flex-grow-1 d-flex justify-content-between align-items-center">
                    <div><h6>Daftar Penerima & Rekening</h6><span class="head-sub">Rincian pembayaran honorarium</span></div>
                    <span class="badge bg-light text-dark border">{{ $jumlahPenerima }} Orang</span>
                </div>
            </div>
            <div class="vnpi-card-body px-0 pb-0">
                <div class="table-responsive">
                    <table class="table vnpi-table align-middle">
                        <thead>
                            <tr>
                                <th class="ps-3">Nama Personel</th>
                                <th class="text-end">Bruto</th>
                                <th class="text-end">PPh</th>
                                <th class="text-end">Netto</th>
                                <th class="pe-3">Rekening Pembayaran</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($personelList as $p)
                                <tr>
                                    <td class="ps-3">
                                        <div class="fw-semibold text-dark">{{ $p->nama_personel }}</div>
                                        <div class="text-muted" style="font-size:.7rem;">{{ $p->jabatan ?? 'Pegawai' }}</div>
                                    </td>
                                    <td class="text-end">Rp {{ number_format($p->nilai_honor ?? 0, 0, ',', '.') }}</td>
                                    <td class="text-end text-danger">Rp {{ number_format($p->pph ?? 0, 0, ',', '.') }}</td>
                                    <td class="text-end fw-bold text-success">Rp {{ number_format($p->netto ?? 0, 0, ',', '.') }}</td>
                                    <td class="pe-3">
                                        @if($p->rekening)
                                            <div class="fw-semibold">{{ $p->jenis_bank ?? 'BANK' }} · <span class="font-monospace fw-normal">{{ $p->rekening }}</span></div>
                                            <div class="text-muted" style="font-size:.7rem;">a.n. {{ $p->nama_rekening }}</div>
                                        @else
                                            <span class="badge bg-danger">Rekening Hilang</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted py-4">Belum ada data penerima.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Kelengkapan Dokumen --}}
        <div class="vnpi-card c-purple mb-4">
            <div class="vnpi-card-head">
                <span class="ico-wrap"><i class="bx bx-folder-open"></i></span>
                <div><h6>Kelengkapan Dokumen Pendukung</h6><span class="head-sub">Arsip yang tersemat pada tagihan</span></div>
            </div>
            <div class="vnpi-card-body">
                <div class="doc-row is-ready d-flex justify-content-between align-items-center">
                    <div><i class="bx bx-check-circle text-success me-1"></i> <span class="fw-semibold">Dokumen SPM Honorarium</span></div>
                    <span class="badge bg-success bg-opacity-10 text-success">Tersedia</span>
                </div>
                <div class="doc-row is-ready d-flex justify-content-between align-items-center">
                    <div><i class="bx bx-check-circle text-success me-1"></i> <span class="fw-semibold">Dokumen SPP</span></div>
                    <span class="badge bg-success bg-opacity-10 text-success">Tersedia</span>
                </div>
                @forelse($arsipList as $arsip)
                    @php
                        $label = \Illuminate\Support\Str::title(str_replace('_', ' ', strtolower($arsip->jenis_dokumen ?? 'Dokumen Pendukung')));
                        $fileName = $arsip->nama_file_asli ?? $arsip->nama_dokumen ?? $label;
                        $path = $arsip->path_file ?? $arsip->file_path ?? null;
                    @endphp
                    <div class="doc-row is-optional d-flex justify-content-between align-items-center">
                        <div class="npi-min-w-0">
                            <div class="fw-semibold text-truncate">{{ $label }}</div>
                            <div class="text-muted small text-truncate">{{ $fileName }}</div>
                        </div>
                        @if($path)
                            <a href="{{ filter_var($path, FILTER_VALIDATE_URL) ? $path : \Illuminate\Support\Facades\Storage::url($path) }}" target="_blank" class="btn btn-sm btn-outline-primary ms-2"><i class="bx bx-link-external"></i> Lihat</a>
                        @endif
                    </div>
                @empty
                    <div class="text-center text-muted small py-2">Tidak ada arsip dokumen pendukung tambahan.</div>
                @endforelse
            </div>
        </div>

    </div>

    {{-- ====== KOLOM KANAN (STICKY) ====== --}}
    <div class="col-xl-4">
        <div class="vnpi-sticky">

            {{-- Action Hero --}}
            @if($canVerify)
                <div class="action-hero">
                    <div class="ah-label"><i class="bx bx-shield-quarter"></i> Aksi Verifikasi Anda</div>
                    <div class="small mb-3" style="color:rgba(255,255,255,.85);">Anda memegang <strong>{{ $activeRoleApprovals->count() }}</strong> peran pada NPI ini. Putuskan untuk tiap peran.</div>
                    @foreach($activeRoleApprovals as $act)
                        <div class="role-action-box">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="fw-semibold text-white"><i class="bx bx-id-card me-1"></i>{{ $act->role_code }}</span>
                                <span class="badge bg-warning text-dark">Menunggu</span>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-light btn-sm flex-fill fw-bold text-success" data-bs-toggle="modal" data-bs-target="#modalApproveHonor{{ $act->id }}"><i class="bx bx-check-circle"></i> Setujui</button>
                                <button type="button" class="btn btn-sm flex-fill fw-bold text-white" style="background:rgba(225,29,72,.85);" data-bs-toggle="modal" data-bs-target="#modalRevisiHonor{{ $act->id }}"><i class="bx bx-x-circle"></i> Revisi</button>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="vnpi-card {{ $isNpiFinal ? 'c-green' : ($isRevisi ? 'c-rose' : 'c-slate') }} mb-3">
                    <div class="vnpi-card-body pt-3 text-center">
                        <i class="bx {{ $isNpiFinal ? 'bx-check-shield text-success' : ($isRevisi ? 'bx-error-circle text-danger' : 'bx-hourglass text-secondary') }}" style="font-size:2.5rem;"></i>
                        <h6 class="fw-bold mt-2 mb-1">
                            {{ $isNpiFinal ? 'Verifikasi NPI Selesai' : ($isRevisi ? 'NPI Dikembalikan untuk Revisi' : 'Menunggu Verifikator Lain') }}
                        </h6>
                        <p class="text-muted small mb-0">
                            @if($isNpiFinal) Seluruh verifikator telah menyetujui NPI ini.
                            @elseif($isRevisi) NPI dikembalikan ke meja Bendahara Pengeluaran.
                            @elseif($myApproval?->status === 'APPROVED') Anda telah menyetujui. Menunggu verifikator lain.
                            @else Tidak ada aksi yang menunggu peran Anda saat ini.
                            @endif
                        </p>
                    </div>
                </div>
            @endif

            {{-- Readiness --}}
            <div class="vnpi-card c-amber mb-3">
                <div class="vnpi-card-head">
                    <span class="ico-wrap"><i class="bx bx-task"></i></span>
                    <div><h6>Validasi Kesiapan</h6><span class="head-sub">{{ $readyCount }} dari {{ $totalReady }} terpenuhi</span></div>
                </div>
                <div class="vnpi-card-body">
                    <div class="readiness-progress"><div class="bar" style="width: {{ $readyPct }}%;"></div></div>
                    <ul class="ready-list">
                        @foreach($checklist as $c)
                            <li>
                                <span class="ico {{ $c['status'] === 'ready' ? 'ok' : 'no' }}"><i class="bx {{ $c['status'] === 'ready' ? 'bx-check' : 'bx-x' }}"></i></span>
                                <div><div class="fw-semibold text-dark">{{ $c['label'] }}</div><div class="text-muted small lh-sm">{{ $c['message'] }}</div></div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>

            {{-- Log --}}
            <div class="vnpi-card c-slate mb-3">
                <div class="vnpi-card-head">
                    <span class="ico-wrap"><i class="bx bx-history"></i></span>
                    <div><h6>Riwayat Persetujuan</h6><span class="head-sub">Jejak audit NPI</span></div>
                </div>
                <div class="vnpi-card-body" style="max-height: 360px; overflow-y: auto;">
                    @forelse($recentLogs as $log)
                        <div class="vnpi-timeline-item is-active">
                            <div class="fw-bold text-dark small">{{ str_replace('_', ' ', $log->aksi) }}</div>
                            <div class="small my-1"><span class="badge bg-light text-dark border">{{ $log->role_saat_itu }}</span> {{ $log->user?->name ?? 'Sistem' }}</div>
                            <div class="text-muted" style="font-size:.7rem;">{{ optional($log->created_at)->format('d M Y H:i') }}</div>
                            @if(!empty($log->catatan))
                                <div class="small text-muted fst-italic mt-1 p-2 bg-light rounded">"{{ $log->catatan }}"</div>
                            @endif
                        </div>
                    @empty
                        <div class="text-center text-muted small py-3">Belum ada riwayat tercatat.</div>
                    @endforelse
                </div>
            </div>

        </div>
    </div>
</div>

{{-- ====== MODALS PER ROLE ====== --}}
@if($canVerify)
    @foreach($activeRoleApprovals as $act)
        <div class="modal fade" id="modalApproveHonor{{ $act->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header bg-success text-white border-0">
                        <h5 class="modal-title fw-bold"><i class="bx bx-check-circle me-1"></i> Setujui sebagai {{ $act->role_code }}?</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <form action="{{ route('verifikasi-npi.honor.approve', $npi->id) }}" method="POST">
                        @csrf
                        <input type="hidden" name="approval_id" value="{{ $act->id }}">
                        <div class="modal-body">
                            <p>Anda akan menyetujui NPI Honorarium <strong>{{ $npi->nomor_npi ?? 'DRAFT' }}</strong> sebagai <strong>{{ $act->role_code }}</strong>.</p>
                            <div class="mb-2">
                                <label class="field-label">Catatan Persetujuan (Opsional)</label>
                                <textarea name="catatan" class="form-control" rows="2" placeholder="Sampaikan pesan mufakat NPI..."></textarea>
                            </div>
                        </div>
                        <div class="modal-footer border-0">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-success px-4 fw-bold">Ya, Setujui</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="modal fade" id="modalRevisiHonor{{ $act->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header bg-danger text-white border-0">
                        <h5 class="modal-title fw-bold"><i class="bx bx-x-circle me-1"></i> Minta Revisi ({{ $act->role_code }})?</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <form action="{{ route('verifikasi-npi.honor.reject', $npi->id) }}" method="POST">
                        @csrf
                        <input type="hidden" name="approval_id" value="{{ $act->id }}">
                        <div class="modal-body">
                            <p class="text-muted small">NPI <strong>{{ $npi->nomor_npi ?? 'DRAFT' }}</strong> akan dikembalikan untuk diperbaiki oleh Bendahara Pengeluaran.</p>
                            <div class="mb-2">
                                <label class="field-label">Catatan Revisi <span class="text-danger">*</span></label>
                                <textarea name="catatan" class="form-control border-danger border-opacity-50" rows="3" required placeholder="Jelaskan detail yang harus diperbaiki..."></textarea>
                            </div>
                        </div>
                        <div class="modal-footer border-0">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-danger px-4 fw-bold">Kembalikan NPI</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endforeach
@endif
@endsection
