@extends('layouts.app')
@section('title', 'Verifikasi Tagihan Honorarium — ' . $tagihan->nomor_tagihan)

@push('css')
<style>
    .approval-row {
        display: flex; align-items: center; gap: 12px;
        padding: 10px 12px; border-radius: 8px; margin-bottom: 6px;
        background: #f8f9fa; border-left: 4px solid #adb5bd;
    }
    .approval-row.is-approved { background: #d1f4d8; border-left-color: var(--bs-success); }
    .approval-row.is-pending  { background: #fff7e0; border-left-color: var(--bs-warning); }
    .approval-row.is-revision { background: #fde2e4; border-left-color: var(--bs-danger); }
    .approval-row.is-rejected { background: #f8d7da; border-left-color: var(--bs-danger); }
    .approval-row.is-waiting  { background: #e9ecef; border-left-color: var(--bs-secondary); opacity: .7; }
    .approval-icon {
        width: 32px; height: 32px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-weight: 700; color: #fff; flex-shrink: 0;
    }
</style>
@endpush

@section('content')
@php
    $statusMeta = [
        'PENDING'   => ['icon' => 'hourglass-split', 'color' => 'warning',   'label' => 'Menunggu',  'cls' => 'is-pending'],
        'APPROVED'  => ['icon' => 'check-lg',        'color' => 'success',   'label' => 'Disetujui', 'cls' => 'is-approved'],
        'REVISION'  => ['icon' => 'arrow-counterclockwise', 'color' => 'danger', 'label' => 'Revisi', 'cls' => 'is-revision'],
        'REJECTED'  => ['icon' => 'x-lg',            'color' => 'danger',    'label' => 'Ditolak',   'cls' => 'is-rejected'],
        'WAITING'   => ['icon' => 'clock-history',   'color' => 'secondary', 'label' => 'Menunggu Step Sebelumnya', 'cls' => 'is-waiting'],
    ];

    $roleColors = [
        'PPK' => '#0d6efd', 'PPSPM' => '#6610f2',
        'KOORDINATOR_KEUANGAN' => '#198754',
        'BENDAHARA_PENGELUARAN' => '#d63384',
        'BENDAHARA_PENERIMAAN' => '#fd7e14',
        'KASUBBAG' => '#0dcaf0',
    ];

    $roleLabels = [
        'PPK' => 'PPK', 'PPSPM' => 'PPSPM',
        'KOORDINATOR_KEUANGAN' => 'Koordinator Keuangan',
        'BENDAHARA_PENGELUARAN' => 'Bendahara Pengeluaran',
        'BENDAHARA_PENERIMAAN' => 'Bendahara Penerimaan',
        'KASUBBAG' => 'Kepala Subbagian Keuangan & Tata Usaha',
    ];

    $normalizeRoleKey = fn($code) => strtoupper(str_replace([' ', '-'], '_', $code));

    // === Hitung progres step 1 (paralel) ===
    $step1Approvals = $approvalsByStep->get(1) ?? collect();
    $step1Total     = $step1Approvals->count();
    $step1Approved  = $step1Approvals->where('status', 'APPROVED');
    $step1Pending   = $step1Approvals->where('status', 'PENDING');
    $step1Done      = $step1Approved->count() === $step1Total && $step1Total > 0;
    $step1Progress  = $step1Total > 0 ? round(($step1Approved->count() / $step1Total) * 100) : 0;

    // Step 2 (Kasubbag)
    $step2Approval  = ($approvalsByStep->get(2) ?? collect())->first();

    // Apakah user yang login adalah Kasubbag dan workflow masih di step 1
    $userRoles      = auth()->user()?->getRoleNames()->toArray() ?? [];
    $isKasubbag     = in_array('Kepala Subbagian Keuangan dan Tata Usaha', $userRoles, true);
    $kasubbagWaiting = $isKasubbag && $instance && $instance->step_saat_ini === 1 && $instance->status === 'IN_PROGRESS';

    // Apakah user ini salah satu verifikator step 1 yang SUDAH approve tapi step 1 belum tuntas
    $alreadyApprovedButWaiting = false;
    if ($instance && $instance->step_saat_ini === 1 && ! $isKasubbag) {
        $myStep1 = $step1Approvals->first(function ($a) use ($userRoles, $roleLabels, $normalizeRoleKey) {
            $key = $normalizeRoleKey($a->role_code);
            return $a->assigned_user_id === auth()->id()
                || in_array($roleLabels[$key] ?? $a->role_code, $userRoles, true);
        });
        if ($myStep1 && $myStep1->status === 'APPROVED' && ! $step1Done) {
            $alreadyApprovedButWaiting = true;
        }
    }

    // Dokumen wajib & opsional honorarium
    $dokumenList = [
        ['jenis' => 'Daftar Nominatif Bertandatangan',  'label' => 'Daftar Nominatif',  'icon' => 'file-earmark-text',  'color' => 'primary', 'required' => true],
        ['jenis' => 'Dokumen Honorarium Bertandatangan','label' => 'Dokumen Honorarium','icon' => 'file-earmark-check', 'color' => 'success', 'required' => true],
        ['jenis' => 'SK Honorarium',                     'label' => 'SK Honorarium',    'icon' => 'file-earmark-pdf',   'color' => 'info',    'required' => false],
    ];
    foreach ($dokumenList as &$doc) {
        $doc['arsip'] = $tagihan->arsipDokumen->firstWhere('jenis_dokumen', $doc['jenis']);
    }
    unset($doc);
@endphp

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
        <div>
            <h4 class="fw-bold mb-1">Verifikasi Tagihan Honorarium</h4>
            <div class="small text-muted">
                <span class="font-monospace">{{ $tagihan->nomor_tagihan }}</span> ·
                {{ \Illuminate\Support\Str::limit($tagihan->deskripsi ?? '-', 80) }} ·
                {{ $tagihan->detailHonorarium->count() }} penerima
            </div>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('verifikasi-tagihan-honorarium.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Kembali ke Antrean
            </a>
        </div>
    </div>

    @if(session('success'))<div class="alert alert-success border-0 shadow-sm">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger border-0 shadow-sm">{{ $errors->first() }}</div>@endif

    {{-- Banner kontekstual untuk Kasubbag yang menunggu step 1 selesai --}}
    @if($kasubbagWaiting)
        <div class="alert border-0 shadow-sm rounded-4 mb-4" style="background: linear-gradient(135deg, #e7f5ff 0%, #fff8e1 100%);">
            <div class="d-flex flex-wrap align-items-center gap-3">
                <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 56px; height: 56px; background: #0dcaf0; color: #fff;">
                    <i class="bi bi-clock-history fs-3"></i>
                </div>
                <div class="flex-grow-1 min-width-0">
                    <h6 class="fw-bold mb-1 text-dark">Menunggu Verifikasi 5 Pejabat (Paralel)</h6>
                    <div class="small text-muted">
                        Persetujuan Anda sebagai <strong>Kepala Subbagian Keuangan dan Tata Usaha</strong> akan tersedia
                        setelah seluruh 5 verifikator (PPK, PPSPM, Koordinator Keuangan, Bendahara Pengeluaran, Bendahara Penerimaan)
                        menyelesaikan verifikasi mereka.
                    </div>
                </div>
                <div class="text-end">
                    <div class="fs-3 fw-bold text-{{ $step1Approved->count() === $step1Total ? 'success' : 'warning' }}">
                        {{ $step1Approved->count() }}<small class="text-muted">/{{ $step1Total }}</small>
                    </div>
                    <div class="small text-muted">sudah verifikasi</div>
                </div>
            </div>
            <div class="progress mt-3" style="height: 8px;">
                <div class="progress-bar bg-success" role="progressbar" style="width: {{ $step1Progress }}%"></div>
            </div>
        </div>
    @endif

    {{-- Banner untuk verifikator step 1 yang sudah approve tapi rekan-rekannya belum --}}
    @if($alreadyApprovedButWaiting)
        <div class="alert alert-success border-0 shadow-sm rounded-4 mb-4">
            <div class="d-flex flex-wrap align-items-center gap-3">
                <i class="bi bi-check-circle-fill fs-3 text-success"></i>
                <div class="flex-grow-1">
                    <h6 class="fw-bold mb-1">Verifikasi Anda sudah tercatat</h6>
                    <div class="small mb-0">
                        Menunggu <strong>{{ $step1Pending->count() }} verifikator lain</strong> menyelesaikan persetujuan paralel ini.
                    </div>
                </div>
                <div class="badge bg-success-subtle text-success">{{ $step1Approved->count() }}/{{ $step1Total }} terselesaikan</div>
            </div>
        </div>
    @endif

    <div class="row g-4">
        {{-- Kolom Kiri --}}
        <div class="col-lg-8">
            {{-- Ringkasan --}}
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white border-bottom pt-4 px-4 pb-3">
                    <h5 class="fw-bold mb-0 text-primary"><i class="bi bi-receipt me-2"></i>Ringkasan Tagihan</h5>
                </div>
                <div class="card-body p-4">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="text-muted small">Uraian Kegiatan</div>
                            <div class="fw-bold">{{ $tagihan->deskripsi ?? '-' }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small">Nama Supplier</div>
                            <div class="fw-bold">{{ $tagihan->nama_supplier ?? '-' }}</div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-muted small">Total Bruto</div>
                            <div class="fw-bold fs-5">Rp {{ number_format($tagihan->total_bruto, 0, ',', '.') }}</div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-muted small">Total PPh / Potongan</div>
                            <div class="fw-bold fs-5 text-danger">Rp {{ number_format($tagihan->total_potongan, 0, ',', '.') }}</div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-muted small">Total Netto</div>
                            <div class="fw-bold fs-5 text-success">Rp {{ number_format($tagihan->total_netto, 0, ',', '.') }}</div>
                        </div>
                        <div class="col-md-12">
                            <div class="text-muted small">Mekanisme Pembayaran</div>
                            <span class="badge bg-primary-subtle text-primary fs-6 px-3 py-2">
                                <i class="bi bi-bank me-1"></i>{{ optional($tagihan->mekanisme_pembayaran)->label() ?? '-' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Status Approval per Step --}}
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white border-bottom pt-4 px-4 pb-3">
                    <h5 class="fw-bold mb-0 text-success"><i class="bi bi-list-check me-2"></i>Status Verifikasi</h5>
                    <p class="text-muted small mb-0 mt-1">Step 1 dilakukan paralel oleh 5 verifikator. Step 2 (Kasubbag) berjalan setelah step 1 selesai.</p>
                </div>
                <div class="card-body p-4">
                    @forelse($approvalsByStep as $stepNo => $approvals)
                        <div class="mb-3">
                            <div class="text-uppercase fw-bold small text-muted mb-2" style="letter-spacing: .5px;">
                                Step {{ $stepNo }}
                                @if($stepNo == 1)<span class="badge bg-info-subtle text-info ms-1">Paralel · {{ $approvals->count() }} verifikator</span>
                                @else<span class="badge bg-primary-subtle text-primary ms-1">Final</span>
                                @endif
                            </div>
                            @foreach($approvals as $a)
                                @php
                                    $meta = $statusMeta[$a->status] ?? $statusMeta['WAITING'];
                                    $rcKey = $normalizeRoleKey($a->role_code);
                                    $color = $roleColors[$rcKey] ?? '#6c757d';
                                @endphp
                                <div class="approval-row {{ $meta['cls'] }}">
                                    <div class="approval-icon" style="background: {{ $color }};">
                                        <i class="bi bi-{{ $meta['icon'] }}"></i>
                                    </div>
                                    <div class="flex-grow-1 min-width-0">
                                        <div class="fw-bold">{{ $a->nama_step ?? ($roleLabels[$rcKey] ?? $a->role_code) }}</div>
                                        <div class="small text-muted">
                                            <i class="bi bi-person me-1"></i>
                                            {{ $a->assignedUser?->name ?? '— belum ditentukan —' }}
                                            @if($a->acted_at)
                                                · <span class="text-{{ $meta['color'] }} fw-semibold">{{ $meta['label'] }}</span>
                                                · {{ $a->acted_at->format('d M Y H:i') }}
                                                @if($a->actedByUser)oleh {{ $a->actedByUser->name }}@endif
                                            @else
                                                · <span class="text-{{ $meta['color'] }}">{{ $meta['label'] }}</span>
                                            @endif
                                        </div>
                                        @if($a->catatan)
                                            <div class="small fst-italic mt-1"><i class="bi bi-chat-left-text me-1"></i>{{ $a->catatan }}</div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @empty
                        <p class="text-muted">Belum ada step approval.</p>
                    @endforelse
                </div>
            </div>

            {{-- Rincian Penerima --}}
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white border-bottom pt-4 px-4 pb-3">
                    <h5 class="fw-bold mb-0 text-secondary"><i class="bi bi-people me-2"></i>Rincian Penerima Honor</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height: 420px; overflow-y: auto;">
                        <table class="table table-hover table-striped align-middle mb-0">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th class="text-center" style="width: 50px;">No</th>
                                    <th>Nama Penerima</th>
                                    <th>Rekening</th>
                                    <th class="text-end">Bruto</th>
                                    <th class="text-end">PPh</th>
                                    <th class="text-end">Netto</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($tagihan->detailHonorarium as $idx => $detail)
                                    <tr>
                                        <td class="text-center">{{ $idx + 1 }}</td>
                                        <td>
                                            <div class="fw-bold small">{{ $detail->nama_personel }}</div>
                                            <div class="text-muted" style="font-size: 11px;">{{ $detail->nrp_nip ?? '-' }} · {{ $detail->jabatan ?? '-' }}</div>
                                        </td>
                                        <td>
                                            <div class="small">{{ $detail->nama_rekening ?? '-' }}</div>
                                            <div class="text-muted font-monospace" style="font-size: 11px;">{{ $detail->jenis_bank ?? '-' }} {{ $detail->rekening ?? '' }}</div>
                                        </td>
                                        <td class="text-end small">Rp {{ number_format($detail->nilai_honor, 0, ',', '.') }}</td>
                                        <td class="text-end small text-danger">Rp {{ number_format($detail->pph, 0, ',', '.') }}</td>
                                        <td class="text-end fw-bold text-success small">Rp {{ number_format($detail->nilai_honor - $detail->pph, 0, ',', '.') }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-center text-muted py-4">Belum ada penerima.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Dokumen Pendukung --}}
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white border-bottom pt-4 px-4 pb-3">
                    <h5 class="fw-bold mb-0 text-primary"><i class="bi bi-file-earmark-pdf me-2"></i>Dokumen Pendukung</h5>
                    <p class="text-muted small mb-0 mt-1">Daftar Nominatif & Dokumen Honorarium bertandatangan adalah dokumen wajib. SK Honorarium opsional.</p>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        @foreach($dokumenList as $doc)
                            <div class="col-md-4">
                                <div class="border rounded-3 p-3 h-100 d-flex flex-column">
                                    <div class="d-flex align-items-center gap-2 mb-2">
                                        <i class="bi bi-{{ $doc['icon'] }} fs-4 text-{{ $doc['color'] }}"></i>
                                        <div>
                                            <div class="fw-bold">{{ $doc['label'] }}</div>
                                            @if($doc['arsip'])
                                                <div class="small text-success"><i class="bi bi-check-circle-fill me-1"></i>Tersedia</div>
                                            @else
                                                <div class="small text-{{ $doc['required'] ? 'danger' : 'muted' }}">
                                                    <i class="bi bi-{{ $doc['required'] ? 'exclamation-triangle-fill' : 'dash-circle' }} me-1"></i>
                                                    {{ $doc['required'] ? 'Belum diunggah' : 'Tidak ada' }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                    @if($doc['arsip'])
                                        <div class="small text-muted text-truncate mb-2" title="{{ $doc['arsip']->nama_file_asli }}">
                                            {{ $doc['arsip']->nama_file_asli }}
                                        </div>
                                        <a href="{{ route('verifikasi-tagihan-honorarium.arsip', [$tagihan->id, $doc['arsip']->id]) }}"
                                           target="_blank"
                                           class="btn btn-sm btn-outline-{{ $doc['color'] }} mt-auto">
                                            <i class="bi bi-eye me-1"></i>Lihat Dokumen
                                        </a>
                                    @else
                                        <button type="button" class="btn btn-sm btn-outline-secondary mt-auto" disabled>
                                            <i class="bi bi-eye-slash me-1"></i>Tidak Tersedia
                                        </button>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        {{-- Kolom Kanan: Aksi + Ringkasan --}}
        <div class="col-lg-4">
            <div class="sticky-top topbar-safe-sticky z-1">

                {{-- Card Aksi / Status Anda --}}
                <div class="card border-0 shadow-sm rounded-4 mb-3">
                    <div class="card-body p-4">
                        @if($canAct && ($myApprovals ?? collect())->count() > 1)
                            {{-- === DUAL-ROLE: Tampilkan tombol verifikasi per role === --}}
                            <h5 class="fw-bold mb-3"><i class="bi bi-gear me-2 text-primary"></i>Aksi Verifikasi</h5>
                            <div class="alert alert-info border-0 small py-2 mb-3">
                                <i class="bi bi-people-fill me-1"></i>
                                Anda memiliki <strong>{{ ($myApprovals ?? collect())->count() }} peran verifikasi</strong> pada tagihan ini.
                                Silakan verifikasi masing-masing peran secara terpisah.
                            </div>

                            @foreach(($myApprovals ?? collect()) as $approval)
                                @php
                                    $rcKey = $normalizeRoleKey($approval->role_code);
                                    $roleName = $roleLabels[$rcKey] ?? $approval->role_code;
                                    $roleColor = $roleColors[$rcKey] ?? '#6c757d';
                                    $approvalIdx = $loop->index;
                                @endphp
                                <div class="border rounded-4 p-3 mb-3" style="border-color: {{ $roleColor }}30 !important; background: {{ $roleColor }}08;">
                                    <div class="d-flex align-items-center gap-2 mb-3">
                                        <span class="badge rounded-pill px-3 py-2" style="background: {{ $roleColor }};">
                                            {{ $roleName }}
                                        </span>
                                        <span class="badge bg-warning text-dark small">Perlu Tindakan</span>
                                    </div>

                                    {{-- Approve --}}
                                    <form action="{{ route('verifikasi-tagihan-honorarium.approve', $tagihan->id) }}" method="POST" class="mb-2"
                                          onsubmit="return confirm('Setujui tagihan sebagai {{ $roleName }}?');">
                                        @csrf
                                        <input type="hidden" name="approval_id" value="{{ $approval->id }}">
                                        <textarea name="catatan" rows="1" class="form-control form-control-sm mb-2"
                                                  placeholder="Catatan persetujuan {{ $roleName }} (opsional)..."></textarea>
                                        <button type="submit" class="btn btn-success w-100 fw-bold btn-sm">
                                            <i class="bi bi-check-lg me-1"></i>Verifikasi ({{ $roleName }})
                                        </button>
                                    </form>

                                    <div class="d-flex gap-2">
                                        <button type="button" class="btn btn-outline-warning btn-sm flex-fill fw-bold"
                                                data-bs-toggle="modal" data-bs-target="#modalRevisi{{ $approvalIdx }}">
                                            <i class="bi bi-arrow-counterclockwise me-1"></i>Revisi
                                        </button>
                                        <button type="button" class="btn btn-outline-danger btn-sm flex-fill fw-bold"
                                                data-bs-toggle="modal" data-bs-target="#modalReject{{ $approvalIdx }}">
                                            <i class="bi bi-x-lg me-1"></i>Tolak
                                        </button>
                                    </div>
                                </div>
                            @endforeach

                        @elseif($canAct && $myApproval)
                            <h5 class="fw-bold mb-3"><i class="bi bi-gear me-2 text-primary"></i>Aksi Verifikasi</h5>
                            <div class="alert alert-info border-0 small py-2 mb-3">
                                Sebagai <strong>{{ $roleLabels[$normalizeRoleKey($myApproval->role_code)] ?? $myApproval->role_code }}</strong>,
                                Anda dapat melakukan aksi pada tagihan ini.
                            </div>

                            {{-- Approve --}}
                            <form action="{{ route('verifikasi-tagihan-honorarium.approve', $tagihan->id) }}" method="POST" class="mb-3" onsubmit="return confirm('Setujui tagihan ini?');">
                                @csrf
                                <input type="hidden" name="approval_id" value="{{ $myApproval->id }}">
                                <label class="form-label fw-bold small">Catatan (opsional)</label>
                                <textarea name="catatan" rows="2" class="form-control form-control-sm mb-2" placeholder="Catatan persetujuan..."></textarea>
                                <button type="submit" class="btn btn-success w-100 fw-bold">
                                    <i class="bi bi-check-lg me-1"></i>Setujui
                                </button>
                            </form>

                            <button type="button" class="btn btn-outline-warning w-100 fw-bold mb-2" data-bs-toggle="modal" data-bs-target="#modalRevisi">
                                <i class="bi bi-arrow-counterclockwise me-1"></i>Minta Revisi
                            </button>
                            <button type="button" class="btn btn-outline-danger w-100 fw-bold" data-bs-toggle="modal" data-bs-target="#modalReject">
                                <i class="bi bi-x-lg me-1"></i>Tolak
                            </button>
                        @elseif($kasubbagWaiting)
                            <h5 class="fw-bold mb-2"><i class="bi bi-clock-history me-2 text-info"></i>Antrean Kasubbag</h5>
                            <p class="text-muted small mb-3">
                                Tombol persetujuan akan aktif setelah seluruh 5 verifikator paralel selesai.
                            </p>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="small fw-semibold text-muted">Progres Step 1</span>
                                <span class="small fw-bold">{{ $step1Approved->count() }}/{{ $step1Total }}</span>
                            </div>
                            <div class="progress mb-3" style="height: 8px;">
                                <div class="progress-bar bg-success" style="width: {{ $step1Progress }}%"></div>
                            </div>
                            <button type="button" class="btn btn-secondary w-100 fw-bold" disabled>
                                <i class="bi bi-lock me-1"></i>Setujui (Belum Tersedia)
                            </button>
                        @elseif($alreadyApprovedButWaiting)
                            <h5 class="fw-bold mb-2"><i class="bi bi-check-circle-fill me-2 text-success"></i>Verifikasi Anda Selesai</h5>
                            <p class="text-muted small mb-3">
                                Menunggu verifikator lain menyelesaikan persetujuan paralel.
                            </p>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="small fw-semibold text-muted">Progres Step 1</span>
                                <span class="small fw-bold">{{ $step1Approved->count() }}/{{ $step1Total }}</span>
                            </div>
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar bg-success" style="width: {{ $step1Progress }}%"></div>
                            </div>
                        @else
                            <h5 class="fw-bold mb-3"><i class="bi bi-info-circle me-2 text-muted"></i>Status Anda</h5>
                            <div class="alert alert-secondary border-0 small py-2 mb-0">
                                Anda tidak memiliki tugas verifikasi yang pending pada tagihan ini saat ini.
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Card Ringkasan Verifikator (sudah/belum) --}}
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-header bg-white border-bottom pt-3 px-4 pb-2">
                        <h6 class="fw-bold mb-0"><i class="bi bi-people me-2 text-success"></i>Ringkasan Verifikator</h6>
                    </div>
                    <div class="card-body p-4">
                        {{-- Step 1: 5 paralel --}}
                        <div class="text-uppercase fw-bold text-muted small mb-2" style="letter-spacing: .5px;">
                            Step 1 — Paralel
                            <span class="badge bg-{{ $step1Done ? 'success' : 'warning text-dark' }} ms-1">{{ $step1Approved->count() }}/{{ $step1Total }}</span>
                        </div>
                        <ul class="list-group list-group-flush mb-3">
                            @foreach($step1Approvals as $a)
                                @php
                                    $rcKey = $normalizeRoleKey($a->role_code);
                                    $rColor = $roleColors[$rcKey] ?? '#6c757d';
                                    $rLabel = $roleLabels[$rcKey] ?? $a->role_code;
                                    $sMeta  = $statusMeta[$a->status] ?? $statusMeta['WAITING'];
                                @endphp
                                <li class="list-group-item border-0 px-0 py-2">
                                    <div class="d-flex align-items-start gap-2">
                                        <span class="role-chip mt-1" style="background: {{ $rColor }}1a; color: {{ $rColor }}; flex-shrink:0; font-size: .68rem; padding: 2px 8px; border-radius: 999px; font-weight: 600;">
                                            {{ \Illuminate\Support\Str::limit(strtoupper($rLabel), 12, '') }}
                                        </span>
                                        <div class="flex-grow-1 min-width-0">
                                            <div class="small fw-semibold text-truncate">{{ $a->assignedUser?->name ?? '— belum ditentukan —' }}</div>
                                            <div class="small text-{{ $sMeta['color'] }}">
                                                <i class="bi bi-{{ $sMeta['icon'] }} me-1"></i>{{ $sMeta['label'] }}
                                                @if($a->acted_at)<span class="text-muted">· {{ $a->acted_at->diffForHumans() }}</span>@endif
                                            </div>
                                        </div>
                                    </div>
                                </li>
                            @endforeach
                        </ul>

                        {{-- Step 2: Kasubbag --}}
                        <div class="text-uppercase fw-bold text-muted small mb-2" style="letter-spacing: .5px;">
                            Step 2 — Final
                            <span class="badge bg-{{ $step2Approval && $step2Approval->status === 'APPROVED' ? 'success' : 'secondary' }} ms-1">
                                {{ $step2Approval ? ($statusMeta[$step2Approval->status]['label'] ?? $step2Approval->status) : '—' }}
                            </span>
                        </div>
                        @if($step2Approval)
                            @php $sMeta2 = $statusMeta[$step2Approval->status] ?? $statusMeta['WAITING']; @endphp
                            <div class="d-flex align-items-start gap-2">
                                <span class="role-chip mt-1" style="background: #0dcaf01a; color: #0dcaf0; flex-shrink:0; font-size: .68rem; padding: 2px 8px; border-radius: 999px; font-weight: 600;">KASUBBAG</span>
                                <div class="flex-grow-1 min-width-0">
                                    <div class="small fw-semibold text-truncate">{{ $step2Approval->assignedUser?->name ?? '— belum ditentukan —' }}</div>
                                    <div class="small text-{{ $sMeta2['color'] }}">
                                        <i class="bi bi-{{ $sMeta2['icon'] }} me-1"></i>{{ $sMeta2['label'] }}
                                        @if($step2Approval->acted_at)<span class="text-muted">· {{ $step2Approval->acted_at->diffForHumans() }}</span>@endif
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

@if($canAct && ($myApprovals ?? collect())->count() > 1)
    {{-- Dual-role modals: satu set modal per role --}}
    @foreach(($myApprovals ?? collect()) as $approval)
        @php
            $rcKey = $normalizeRoleKey($approval->role_code);
            $roleName = $roleLabels[$rcKey] ?? $approval->role_code;
            $approvalIdx = $loop->index;
        @endphp
        {{-- Modal Revisi per role --}}
        <div class="modal fade" id="modalRevisi{{ $approvalIdx }}" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <form action="{{ route('verifikasi-tagihan-honorarium.revisi', $tagihan->id) }}" method="POST">
                    @csrf
                    <input type="hidden" name="approval_id" value="{{ $approval->id }}">
                    <div class="modal-content">
                        <div class="modal-header"><h5 class="modal-title">Minta Revisi ({{ $roleName }})</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                        <div class="modal-body">
                            <label class="form-label fw-bold">Catatan Revisi <span class="text-danger">*</span></label>
                            <textarea name="catatan" rows="4" class="form-control" placeholder="Tuliskan apa yang perlu diperbaiki..." required></textarea>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-warning fw-bold"><i class="bi bi-arrow-counterclockwise me-1"></i>Kirim Revisi ({{ $roleName }})</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- Modal Reject per role --}}
        <div class="modal fade" id="modalReject{{ $approvalIdx }}" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <form action="{{ route('verifikasi-tagihan-honorarium.reject', $tagihan->id) }}" method="POST" onsubmit="return confirm('Tolak tagihan sebagai {{ $roleName }}?');">
                    @csrf
                    <input type="hidden" name="approval_id" value="{{ $approval->id }}">
                    <div class="modal-content">
                        <div class="modal-header"><h5 class="modal-title">Tolak Tagihan ({{ $roleName }})</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                        <div class="modal-body">
                            <div class="alert alert-danger border-0 small">Penolakan akan menghentikan workflow tagihan secara permanen.</div>
                            <label class="form-label fw-bold">Alasan Penolakan <span class="text-danger">*</span></label>
                            <textarea name="catatan" rows="4" class="form-control" placeholder="Tuliskan alasan penolakan..." required></textarea>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-danger fw-bold"><i class="bi bi-x-lg me-1"></i>Tolak ({{ $roleName }})</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    @endforeach
@elseif($canAct && $myApproval)
    {{-- Single-role modals --}}
    <div class="modal fade" id="modalRevisi" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <form action="{{ route('verifikasi-tagihan-honorarium.revisi', $tagihan->id) }}" method="POST">
                @csrf
                <input type="hidden" name="approval_id" value="{{ $myApproval->id }}">
                <div class="modal-content">
                    <div class="modal-header"><h5 class="modal-title">Minta Revisi</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                    <div class="modal-body">
                        <label class="form-label fw-bold">Catatan Revisi <span class="text-danger">*</span></label>
                        <textarea name="catatan" rows="4" class="form-control" placeholder="Tuliskan apa yang perlu diperbaiki..." required></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-warning fw-bold"><i class="bi bi-arrow-counterclockwise me-1"></i>Kirim Revisi</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="modalReject" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <form action="{{ route('verifikasi-tagihan-honorarium.reject', $tagihan->id) }}" method="POST" onsubmit="return confirm('Tolak tagihan ini?');">
                @csrf
                <input type="hidden" name="approval_id" value="{{ $myApproval->id }}">
                <div class="modal-content">
                    <div class="modal-header"><h5 class="modal-title">Tolak Tagihan</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                    <div class="modal-body">
                        <div class="alert alert-danger border-0 small">Penolakan akan menghentikan workflow tagihan secara permanen.</div>
                        <label class="form-label fw-bold">Alasan Penolakan <span class="text-danger">*</span></label>
                        <textarea name="catatan" rows="4" class="form-control" placeholder="Tuliskan alasan penolakan..." required></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-danger fw-bold"><i class="bi bi-x-lg me-1"></i>Tolak</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endif

@endsection
