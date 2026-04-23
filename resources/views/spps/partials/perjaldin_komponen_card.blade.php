@php
    $hasSpp = $komponen->hasDokumenTurunan();
    $spp = $hasSpp ? $komponen->dokumenSpp : null;
    $coaSelected = !empty($komponen->dipa_revision_item_id);
    
    // Icon mapping
    $iconMap = [
        'TIKET' => 'bi-ticket-detailed',
        'TRANSPORT' => 'bi-car-front',
        'PENGINAPAN' => 'bi-building',
        'UANG_HARIAN' => 'bi-wallet2',
        'UANG_REPRESENTASI' => 'bi-briefcase',
    ];
    $icon = $iconMap[$komponen->kode_komponen] ?? 'bi-cash-coin';

    // Status COA
    $coaStatus = $coaSelected 
        ? '<span class="badge bg-success-subtle text-success"><i class="bi bi-check-circle me-1"></i>COA Terpilih</span>'
        : '<span class="badge bg-danger-subtle text-danger"><i class="bi bi-x-circle me-1"></i>COA Belum Ada</span>';

    // Status SPP UI
    if (!$hasSpp) {
        $sppStatusBadge = '<span class="badge bg-secondary">Belum Dibuat</span>';
        $sppStatusClass = 'border-start border-4 border-secondary';
    } else {
        $sppStatusBadge = '<span class="badge ' . $komponen->status_badge_class . '">' . $komponen->status_label . '</span>';
        $sppStatusClass = 'border-start border-4 border-' . str_replace(['bg-', ' text-dark'], '', $komponen->status_badge_class);
    }
@endphp

<div class="card mb-3 shadow-sm border-0 {{ $sppStatusClass }}">
    <div class="card-body">
        <div class="row align-items-center">
            <!-- Basic Info Kolom Kiri -->
            <div class="col-md-3">
                <div class="d-flex align-items-center">
                    <div class="bg-primary bg-opacity-10 text-primary p-3 rounded-circle me-3">
                        <i class="bi {{ $icon }} fs-4"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-1">{{ $komponen->nama_komponen }}</h6>
                        <div class="small text-muted">{{ $komponen->jumlah_peserta }} Peserta berhak</div>
                        <div class="mt-1">{!! $sppStatusBadge !!}</div>
                    </div>
                </div>
            </div>

            <!-- Nominal Kolom Tengah -->
            <div class="col-md-3 text-md-start mt-3 mt-md-0 border-end">
                <div class="text-muted small mb-1">Total Biaya</div>
                <h4 class="fw-bold mb-0 text-dark">Rp {{ number_format($komponen->total_nominal, 0, ',', '.') }}</h4>
            </div>

            <!-- Status COA Kolom -->
            <div class="col-md-3 mt-3 mt-md-0 px-md-4">
                <div class="text-muted small mb-1">Status COA</div>
                <div class="mb-2">{!! $coaStatus !!}</div>
                
                <form action="{{ route('perjaldins.komponen.update-coa', $komponen->id) }}" method="POST" class="d-flex gap-2">
                    @csrf
                    @method('PUT')
                    <!-- Hanya tampilkan form beda view kalau blm ada SPP, kl sdh SPP disabled -->
                    <select name="dipa_revision_item_id" class="form-select form-select-sm" {{ $hasSpp ? 'disabled' : 'required' }}>
                        <option value="">-- Pilih COA --</option>
                        @foreach($budgets as $budgetGroup)
                            <optgroup label="{{ $budgetGroup['label'] }}">
                                @foreach($budgetGroup['items'] as $item)
                                    <option value="{{ $item['id'] }}" {{ $komponen->dipa_revision_item_id == $item['id'] ? 'selected' : '' }}>
                                        {{ $item['option_label'] }}
                                    </option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                    @if(!$hasSpp)
                        <button type="submit" class="btn btn-sm btn-outline-primary" title="Simpan COA"><i class="bi bi-save"></i></button>
                    @endif
                </form>
            </div>

            <!-- Aksi Kolom Kanan -->
            <div class="col-md-3 text-md-end mt-3 mt-md-0">
                @if(!$hasSpp)
                    @if($coaSelected)
                        <button class="btn btn-primary px-4 rounded-pill" data-bs-toggle="modal" data-bs-target="#modalSpp{{ $komponen->id }}">
                            <i class="bi bi-pencil-square me-1"></i> Buat SPP
                        </button>
                    @else
                        <button class="btn btn-secondary px-4 rounded-pill" disabled title="Pilih COA terlebih dahulu">
                            <i class="bi bi-pencil-square me-1"></i> Buat SPP
                        </button>
                    @endif
                @else
                    <div class="d-flex flex-column align-items-end gap-2">
                        <small class="text-muted fw-bold">{{ $spp->nomor_spp }}</small>
                        <div class="d-flex gap-2">
                            <a href="{{ route('spps.cetak-pdf', $spp->id) }}" target="_blank" class="btn btn-sm btn-outline-danger" title="Cetak PDF">
                                <i class="bi bi-file-pdf"></i>
                            </a>
                            @if(in_array($spp->status, ['DRAFT', 'Revisi', 'REVISI_PPK', 'REVISI_KASUBBAG'], true))
                                <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#modalSpp{{ $komponen->id }}">
                                    <i class="bi bi-pencil"></i> Edit SPP
                                </button>
                                <form action="{{ route('spps.workflow.submit', $spp->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Ajukan SPP ini ke PPK?');">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-success" title="Ajukan ke PPK">
                                        <i class="bi bi-send"></i> Submit
                                    </button>
                                </form>
                            @else
                                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalSpp{{ $komponen->id }}">
                                    <i class="bi bi-eye"></i> Lihat detail
                                </button>
                                <button class="btn btn-sm btn-info text-white" data-bs-toggle="modal" data-bs-target="#modalAktivitas{{ $komponen->id }}">
                                    <i class="bi bi-activity"></i> Aktivitas SPP
                                </button>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Modal Form SPP -->
<div class="modal fade" id="modalSpp{{ $komponen->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg text-start">
        <div class="modal-content">
            <form action="{{ route('spps.store-from-perjaldin-komponen', $komponen->id) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">
                        @if($hasSpp && !in_array($spp->status, ['DRAFT', 'Revisi', 'REVISI_PPK', 'REVISI_KASUBBAG'], true))
                            Lihat Draft SPP: {{ $komponen->nama_komponen }}
                        @else
                            {{ $hasSpp ? 'Edit' : 'Buat' }} Draft SPP: {{ $komponen->nama_komponen }}
                        @endif
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @php $isReadOnly = $hasSpp && !in_array($spp->status, ['DRAFT', 'Revisi', 'REVISI_PPK', 'REVISI_KASUBBAG'], true); @endphp

                    @if($hasSpp && $spp->status_spp === 'Revisi')
                        <div class="alert alert-danger mb-3">
                            <strong><i class="bi bi-exclamation-triangle-fill me-1"></i> Catatan Revisi:</strong><br>
                            {{ $spp->logs()->where('aksi', 'REVISI_SPP')->latest()->value('catatan') ?? 'Tidak ada catatan.' }}
                        </div>
                    @endif

                    <div class="alert alert-info py-2 mb-4 shadow-sm border-0">
                        Total pencairan untuk item ini: <strong class="fs-5">Rp {{ number_format($komponen->total_nominal, 0, ',', '.') }}</strong><br>
                        <small>Uraian: Belanja Barang Perjalanan Dinas Pegawai - {{ $komponen->nama_komponen }}</small>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Nomor SPP</label>
                            @if($hasSpp)
                                <div class="input-group">
                                    <span class="input-group-text bg-primary text-white"><i class="bi bi-hash"></i></span>
                                    <input type="text" class="form-control bg-light fw-bold text-primary" value="{{ $spp->nomor_spp }}" readonly>
                                </div>
                            @else
                                @php
                                    $previewNomor = 'SPP-BLU/APTP-' . date('Y') . '/' . str_pad($nextSppCounter + $loop->index, 4, '0', STR_PAD_LEFT);
                                @endphp
                                <div class="input-group">
                                    <span class="input-group-text bg-secondary text-white"><i class="bi bi-hash"></i></span>
                                    <input type="text" class="form-control bg-light text-muted fst-italic" value="{{ $previewNomor }}" readonly>
                                </div>
                                <small class="text-muted"><i class="bi bi-info-circle me-1"></i>Nomor final digenerate otomatis saat disimpan</small>
                            @endif
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Tanggal SPP</label>
                            <input type="date" name="tanggal_spp" class="form-control" required value="{{ $spp->tanggal_spp ?? date('Y-m-d') }}" {{ $isReadOnly ? 'disabled' : '' }}>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Tahun Anggaran</label>
                            <input type="text" name="tahun_anggaran" class="form-control" value="{{ $spp->tahun_anggaran ?? date('Y') }}" required {{ $isReadOnly ? 'disabled' : '' }}>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-success">COA / MAK Terpilih</label>
                            @php
                                $coaText = '-';
                                if($komponen->dipaRevisionItem && $komponen->dipaRevisionItem->coa) {
                                    $coa = $komponen->dipaRevisionItem->coa;
                                    $coaText = $coa->kode_mak_lengkap . ' - ' . $coa->nama_akun;
                                }
                            @endphp
                            <input type="text" class="form-control bg-success-subtle border-success" value="{{ $coaText }}" readonly>
                        </div>
                    </div>

                    <hr>
                    <h6 class="mb-3">Verifikator & Penandatangan</h6>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">PPK Verifikator (Wajib)</label>
                            <select name="ppk_verifikator_id" class="form-select border-primary" required {{ $isReadOnly ? 'disabled' : '' }}>
                                <option value="">-- Pilih PPK --</option>
                                @foreach($ppkUsers as $ppk)
                                    <option value="{{ $ppk->id }}" {{ ($spp->ppk_verifikator_id ?? '') == $ppk->id ? 'selected' : '' }}>
                                        {{ $ppk->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Kasubbag Verifikator (Otomatis)</label>
                            <input type="text" class="form-control bg-light text-muted border-0 shadow-none" value="{{ $kasubbagUser->name ?? '(User Kasubbag belum ada)' }}" readonly>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                    @if(!$isReadOnly)
                        <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Simpan Draft SPP</button>
                    @endif
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Aktivitas SPP -->
@if($hasSpp)
<div class="modal fade" id="modalAktivitas{{ $komponen->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered text-start">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title"><i class="bi bi-activity me-2"></i>Aktivitas Workflow SPP</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <ul class="list-group list-group-flush">
                    @php
                        $workflow = $spp->workflowInstances?->sortByDesc('created_at')->first();
                        $apps = $workflow ? $workflow->approvals->sortBy('urutan_step') : collect([]);
                    @endphp
                    @forelse($apps as $app)
                        <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                            <div>
                                <h6 class="mb-1 fw-bold">{{ $app->nama_step }}</h6>
                                <small class="text-muted"><i class="bi bi-person me-1"></i>{{ $app->actedByUser?->name ?? 'Sistem / Belum Ditentukan' }}</small>
                                @if($app->catatan)
                                <div class="small text-warning mt-1"><i class="bi bi-chat-left-text me-1"></i>Catatan: {{ $app->catatan }}</div>
                                @endif
                                @if($app->acted_at)
                                <div class="small text-muted mt-1"><i class="bi bi-clock me-1"></i>{{ \Carbon\Carbon::parse($app->acted_at)->format('d M Y, H:i') }}</div>
                                @endif
                            </div>
                            <div>
                                @if($app->status === 'APPROVED')
                                    <span class="badge bg-success rounded-pill px-3 py-2">Selesai</span>
                                @elseif($app->status === 'REJECTED')
                                    <span class="badge bg-danger rounded-pill px-3 py-2">Ditolak</span>
                                @elseif($app->status === 'REVISION')
                                    <span class="badge bg-warning rounded-pill px-3 py-2 text-dark">Revisi</span>
                                @elseif($app->status === 'PENDING')
                                    <span class="badge bg-primary rounded-pill px-3 py-2">Menunggu</span>
                                @else
                                    <span class="badge bg-secondary rounded-pill px-3 py-2">Belum</span>
                                @endif
                            </div>
                        </li>
                    @empty
                        <li class="list-group-item text-center py-4 text-muted">Belum ada aktivitas workflow tercatat. Pastikan SPP telah diajukan ke PPK.</li>
                    @endforelse
                </ul>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
@endif
