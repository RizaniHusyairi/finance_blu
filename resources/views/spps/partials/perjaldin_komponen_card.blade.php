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

    // Detail COA terpilih (untuk tampilan yang mudah dibaca)
    $coaKode = null; $coaNama = null; $coaSisaPagu = null;
    if ($komponen->dipaRevisionItem && $komponen->dipaRevisionItem->coa) {
        $coaKode = $komponen->dipaRevisionItem->coa->kode_mak_lengkap;
        $coaNama = $komponen->dipaRevisionItem->coa->nama_akun;
        $coaSisaPagu = (float) $komponen->dipaRevisionItem->sisa_pagu;
    }

    // Status SPP UI
    if (!$hasSpp) {
        $sppStatusBadge = '<span class="komp-badge komp-badge-muted"><i class="bi bi-dash-circle"></i> Belum Dibuat</span>';
        $accent = 'sk-muted';
    } else {
        $sppStatusBadge = '<span class="badge ' . $komponen->status_badge_class . '">' . $komponen->status_label . '</span>';
        $accent = 'sk-' . str_replace(['bg-', ' text-dark'], '', $komponen->status_badge_class);
    }
@endphp

<div class="komp-card {{ $accent }} {{ $coaSelected ? 'is-coa-ok' : 'is-coa-missing' }} {{ $hasSpp ? 'has-spp' : '' }}" style="--ki: {{ $loop->index }};">
    <span class="komp-bar"></span>

    <!-- Baris atas: identitas + nominal -->
    <div class="komp-top">
        <div class="komp-identity">
            <span class="komp-ico"><i class="bi {{ $icon }}"></i></span>
            <div class="komp-id-text">
                <h6 class="komp-name">{{ $komponen->nama_komponen }}</h6>
                <div class="komp-tags">
                    <span class="komp-chip"><i class="bi bi-people-fill"></i> {{ $komponen->jumlah_peserta }} Peserta</span>
                    {!! $sppStatusBadge !!}
                </div>
            </div>
        </div>
        <div class="komp-nominal">
            <span class="komp-nom-label">Total Biaya</span>
            <span class="komp-nom-value">Rp {{ number_format($komponen->total_nominal, 0, ',', '.') }}</span>
        </div>
    </div>

    <!-- Baris bawah: COA + aksi -->
    <div class="komp-bottom">
        <div class="komp-coa-area">
            <div class="komp-coa-head">
                <span class="komp-coa-title"><i class="bi bi-bookmark-check-fill"></i> Mata Anggaran (COA)</span>
                @if($coaSelected)
                    <span class="komp-pill komp-pill-ok"><i class="bi bi-check-circle-fill"></i> COA Terpilih</span>
                @else
                    <span class="komp-pill komp-pill-warn"><i class="bi bi-exclamation-circle-fill"></i> Belum Dipilih</span>
                @endif
            </div>

            {{-- Kartu COA terpilih: mudah dibaca --}}
            @if($coaSelected && $coaKode)
                <div class="komp-coa-readable">
                    <span class="kcr-icon"><i class="bi bi-upc-scan"></i></span>
                    <div class="kcr-text">
                        <span class="kcr-kode">{{ $coaKode }}</span>
                        <span class="kcr-nama">{{ $coaNama ?: 'Nama akun tidak tersedia' }}</span>
                    </div>
                    <div class="kcr-pagu">
                        <span class="kcr-pagu-label">Sisa Pagu</span>
                        <span class="kcr-pagu-value">Rp {{ number_format($coaSisaPagu ?? 0, 0, ',', '.') }}</span>
                    </div>
                </div>
            @endif

            <form action="{{ route('perjaldins.komponen.update-coa', $komponen->id) }}" method="POST"
                  class="komponen-coa-form"
                  data-komponen-id="{{ $komponen->id }}"
                  data-total-nominal="{{ $komponen->total_nominal }}"
                  data-current-coa-id="{{ $komponen->dipa_revision_item_id ?? '' }}">
                @csrf
                @method('PUT')
                {{-- COA tersimpan otomatis saat operator memilih COA yang valid (lihat JS) --}}
                @unless($hasSpp)
                    <label class="komp-coa-sublabel">{{ $coaSelected ? 'Ganti COA' : 'Pilih COA untuk komponen ini' }}</label>
                @endunless
                <select name="dipa_revision_item_id" class="form-select form-select-sm komponen-coa-select js-coa-select" data-coa-placeholder="Cari & pilih COA..." {{ $hasSpp ? 'disabled' : 'required' }}>
                    <option value="">-- Pilih COA --</option>
                    @foreach($budgets as $budgetGroup)
                        <optgroup label="{{ $budgetGroup['label'] }}">
                            @foreach($budgetGroup['items'] as $item)
                                <option value="{{ $item['id'] }}"
                                        data-sisa-pagu="{{ $item['sisa_pagu'] }}"
                                        data-kode-mak="{{ $item['kode_mak'] ?? '' }}"
                                        {{ $komponen->dipa_revision_item_id == $item['id'] ? 'selected' : '' }}>
                                    {{ $item['option_label'] }}
                                </option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
                <div class="komponen-coa-feedback small mt-1" style="display:none; line-height: 1.25;"></div>
            </form>
        </div>

        <!-- Aksi -->
        <div class="komp-action">
            @if(!$hasSpp)
                <button class="btn btn-primary px-4 rounded-pill komponen-buat-spp-btn w-100"
                        data-bs-toggle="modal" data-bs-target="#modalSpp{{ $komponen->id }}"
                        data-komponen-id="{{ $komponen->id }}"
                        {{ $coaSelected ? '' : 'disabled' }}
                        title="{{ $coaSelected ? 'Buat SPP untuk komponen ini' : 'Pilih COA terlebih dahulu' }}">
                    <i class="bi bi-pencil-square me-1"></i> Buat SPP
                </button>
                @unless($coaSelected)
                    <span class="komp-action-hint"><i class="bi bi-info-circle"></i> Pilih COA dulu untuk membuat SPP</span>
                @endunless
            @else
                <span class="komp-spp-no"><i class="bi bi-file-earmark-text me-1"></i>{{ $spp->nomor_spp }}</span>
                <div class="komp-action-btns">
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
                        @if(in_array($spp->status, ['DISETUJUI_SPP', 'APPROVED', 'SPP_TERBIT'], true))
                            <span class="btn btn-sm btn-outline-success disabled" title="SPP final sudah ber-TTE otomatis">
                                <i class="bi bi-patch-check"></i> TTE
                            </span>
                            @hasanyrole('Super Admin|Operator BLU')
                                <a href="{{ route('spms.perjaldin.detail', $spp->id) }}" class="btn btn-sm btn-success" title="{{ $spp->spm ? 'Lanjutkan SPM' : 'Lanjut Buat SPM' }}">
                                    <i class="bi bi-arrow-right-circle"></i> SPM
                                </a>
                            @endhasanyrole
                        @endif
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>

@php
    $isReadOnly = $hasSpp && !in_array($spp->status, ['DRAFT', 'Revisi', 'REVISI_PPK', 'REVISI_KASUBBAG'], true);
    $coaKodeModal = $coaKode; // dari blok @php di atas
    $coaNamaModal = $coaNama;
@endphp
<!-- Modal Form SPP -->
<div class="modal fade spp-modal" id="modalSpp{{ $komponen->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable text-start">
        <div class="modal-content spp-modal-content border-0">
            <form action="{{ route('spps.store-from-perjaldin-komponen', $komponen->id) }}" method="POST">
                @csrf
                <input type="hidden" name="tahun_anggaran" value="{{ $spp->tahun_anggaran ?? date('Y') }}">

                <div class="spp-modal-header">
                    <span class="spp-header-glow"></span>
                    <div class="d-flex align-items-center gap-3 position-relative">
                        <span class="spp-header-icon"><i class="bi {{ $icon }}"></i></span>
                        <div>
                            <h5 class="modal-title fw-bold mb-0">
                                @if($isReadOnly)
                                    Lihat Draft SPP
                                @else
                                    {{ $hasSpp ? 'Edit' : 'Buat' }} Draft SPP
                                @endif
                            </h5>
                            <small class="spp-header-sub">{{ $komponen->nama_komponen }}</small>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white position-relative" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body spp-modal-body">

                    @if($hasSpp && $spp->status_spp === 'Revisi')
                        <div class="spp-revisi-alert spp-anim">
                            <i class="bi bi-exclamation-triangle-fill"></i>
                            <div>
                                <strong>Catatan Revisi</strong>
                                <p class="mb-0">{{ $spp->logs()->where('aksi', 'REVISI_SPP')->latest()->value('catatan') ?? 'Tidak ada catatan.' }}</p>
                            </div>
                        </div>
                    @endif

                    <!-- Banner total pencairan -->
                    <div class="spp-total-banner spp-anim">
                        <span class="spp-tb-glow"></span>
                        <div class="spp-tb-left">
                            <span class="spp-tb-label"><i class="bi bi-cash-coin me-1"></i> Total Pencairan Item Ini</span>
                            <span class="spp-tb-uraian">Belanja Barang Perjalanan Dinas Pegawai &mdash; {{ $komponen->nama_komponen }}</span>
                        </div>
                        <div class="spp-tb-value">Rp {{ number_format($komponen->total_nominal, 0, ',', '.') }}</div>
                    </div>

                    <!-- Detail SPP -->
                    <div class="spp-section spp-anim">
                        <div class="spp-section-title"><i class="bi bi-file-earmark-text"></i> Detail SPP</div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="spp-label">Nomor SPP</label>
                                @if($hasSpp)
                                    <div class="input-group spp-input-group">
                                        <span class="input-group-text"><i class="bi bi-hash"></i></span>
                                        <input type="text" class="form-control fw-bold text-primary" value="{{ $spp->nomor_spp }}" readonly>
                                    </div>
                                @else
                                    @php
                                        $previewNomor = 'SPP-BLU/APTP-' . date('Y') . '/' . str_pad($nextSppCounter + $loop->index, 4, '0', STR_PAD_LEFT);
                                    @endphp
                                    <div class="input-group spp-input-group">
                                        <span class="input-group-text"><i class="bi bi-hash"></i></span>
                                        <input type="text" class="form-control text-muted fst-italic" value="{{ $previewNomor }}" readonly>
                                    </div>
                                    <small class="spp-hint"><i class="bi bi-info-circle me-1"></i>Nomor final digenerate otomatis saat disimpan</small>
                                @endif
                            </div>
                            <div class="col-md-6">
                                <label class="spp-label">Tanggal SPP</label>
                                <div class="input-group spp-input-group">
                                    <span class="input-group-text"><i class="bi bi-calendar-event"></i></span>
                                    <input type="date" name="tanggal_spp" class="form-control" required value="{{ $spp->tanggal_spp ?? date('Y-m-d') }}" {{ $isReadOnly ? 'disabled' : '' }}>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="spp-label">Jenis Tagihan</label>
                                <select name="jenis_tagihan" class="form-select" {{ $isReadOnly ? 'disabled' : '' }}>
                                    <option value="NON REMUNERASI" {{ ($spp?->jenis_tagihan ?? 'NON REMUNERASI') === 'NON REMUNERASI' ? 'selected' : '' }}>NON REMUNERASI</option>
                                    <option value="REMUNERASI" {{ ($spp?->jenis_tagihan ?? '') === 'REMUNERASI' ? 'selected' : '' }}>REMUNERASI</option>
                                </select>
                                <small class="spp-hint">Kategori tagihan pada PDF SPP &amp; SPM.</small>
                            </div>
                            <div class="col-md-6">
                                <label class="spp-label">COA / MAK Terpilih</label>
                                <div class="spp-coa-box {{ $coaKodeModal ? 'is-set' : 'is-empty' }}" data-komponen-id="{{ $komponen->id }}">
                                    <span class="spp-coa-icon"><i class="bi bi-upc-scan"></i></span>
                                    <div class="spp-coa-text">
                                        <span class="spp-coa-kode">{{ $coaKodeModal ?: 'Belum ada COA' }}</span>
                                        <span class="spp-coa-nama">{{ $coaKodeModal ? ($coaNamaModal ?: 'Nama akun tidak tersedia') : 'Pilih COA pada kartu item terlebih dahulu' }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Verifikator -->
                    <div class="spp-section spp-anim">
                        <div class="spp-section-title"><i class="bi bi-people"></i> Verifikator &amp; Penandatangan</div>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="spp-signer">
                                    <span class="spp-signer-ico spp-sg-ppk"><i class="bi bi-person-badge"></i></span>
                                    <div>
                                        <span class="spp-signer-role">Verifikator PPK</span>
                                        <span class="spp-signer-name">{{ $ppkUser->name ?? 'PPK Tidak Tersedia (Otomatis)' }}</span>
                                    </div>
                                </div>
                                <input type="hidden" name="ppk_verifikator_id" value="{{ $ppkUser->id ?? '' }}">
                            </div>
                            <div class="col-md-4">
                                <div class="spp-signer">
                                    <span class="spp-signer-ico spp-sg-koor"><i class="bi bi-person-workspace"></i></span>
                                    <div>
                                        <span class="spp-signer-role">Koordinator Keuangan</span>
                                        <span class="spp-signer-name">{{ $koordinatorUser->name ?? '(User Koordinator belum ada)' }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="spp-signer">
                                    <span class="spp-signer-ico spp-sg-kasub"><i class="bi bi-person-gear"></i></span>
                                    <div>
                                        <span class="spp-signer-role">Kasubbag (Otomatis)</span>
                                        <span class="spp-signer-name">{{ $kasubbagUser->name ?? '(User Kasubbag belum ada)' }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="spp-modal-footer">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Tutup</button>
                    @if(!$isReadOnly)
                        <button type="submit" class="btn btn-primary rounded-pill px-4 spp-save-btn"><i class="bi bi-save me-1"></i> Simpan Draft SPP</button>
                    @endif
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Aktivitas SPP -->
@if($hasSpp)
<div class="modal fade wf-modal" id="modalAktivitas{{ $komponen->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered text-start">
        <div class="modal-content wf-modal-content border-0">
            <div class="wf-modal-header">
                <span class="wf-header-glow"></span>
                <div class="d-flex align-items-center gap-3 position-relative">
                    <span class="wf-header-icon"><i class="bi bi-activity"></i></span>
                    <div>
                        <h5 class="modal-title fw-bold mb-0">Aktivitas Workflow SPP</h5>
                        <small class="wf-header-sub">{{ $spp->nomor_spp }}</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white position-relative" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body wf-modal-body">
                @php
                    $workflow = $spp->workflowInstances?->sortByDesc('created_at')->first();
                    $apps = $workflow ? $workflow->approvals->sortBy('urutan_step') : collect([]);
                    $wfMeta = [
                        'APPROVED' => ['node' => 'is-done',     'icon' => 'bi-check-lg',         'badge' => 'wf-badge-done',     'label' => 'Selesai'],
                        'REJECTED' => ['node' => 'is-rejected', 'icon' => 'bi-x-lg',            'badge' => 'wf-badge-rejected', 'label' => 'Ditolak'],
                        'REVISION' => ['node' => 'is-revision', 'icon' => 'bi-arrow-repeat',    'badge' => 'wf-badge-revision', 'label' => 'Revisi'],
                        'PENDING'  => ['node' => 'is-pending',  'icon' => 'bi-hourglass-split', 'badge' => 'wf-badge-pending',  'label' => 'Menunggu'],
                    ];
                @endphp
                @if($apps->isNotEmpty())
                <div class="wf-timeline">
                    @foreach($apps as $app)
                        @php $m = $wfMeta[$app->status] ?? ['node' => 'is-idle', 'icon' => 'bi-three-dots', 'badge' => 'wf-badge-idle', 'label' => 'Belum']; @endphp
                        <div class="wf-timeline-item">
                            <span class="wf-node {{ $m['node'] }}"><i class="bi {{ $m['icon'] }}"></i></span>
                            <div class="wf-card">
                                <div class="d-flex justify-content-between align-items-start gap-2">
                                    <h6 class="wf-step-title mb-0">{{ $app->nama_step }}</h6>
                                    <span class="wf-badge {{ $m['badge'] }}">{{ $m['label'] }}</span>
                                </div>
                                <div class="wf-actor"><i class="bi bi-person-circle me-1"></i>{{ $app->actedByUser?->name ?? 'Sistem / Belum Ditentukan' }}</div>
                                @if($app->catatan)
                                <div class="wf-note"><i class="bi bi-chat-left-text me-1"></i>{{ $app->catatan }}</div>
                                @endif
                                @if($app->acted_at)
                                <div class="wf-time"><i class="bi bi-clock me-1"></i>{{ \Carbon\Carbon::parse($app->acted_at)->format('d M Y, H:i') }}</div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
                @else
                <div class="wf-empty">
                    <i class="bi bi-inbox"></i>
                    <p class="mb-0">Belum ada aktivitas workflow tercatat.<br><small>Pastikan SPP telah diajukan ke PPK.</small></p>
                </div>
                @endif
            </div>
            <div class="modal-footer border-0 pt-0 bg-transparent">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
@endif
@once
@push('css')
<style>
.wf-modal-content{border-radius:20px;overflow:hidden;box-shadow:0 24px 60px -12px rgba(2,8,23,.35);}
.wf-modal .modal-dialog{transition:transform .4s cubic-bezier(.22,1,.36,1),opacity .4s;}
.wf-modal.fade .modal-dialog{transform:translateY(28px) scale(.95);opacity:0;}
.wf-modal.show .modal-dialog{transform:none;opacity:1;}

.wf-modal-header{position:relative;display:flex;align-items:center;justify-content:space-between;padding:20px 22px;color:#fff;background:linear-gradient(120deg,#0ea5e9 0%,#3b82f6 55%,#6366f1 100%);overflow:hidden;}
.wf-header-glow{position:absolute;top:-60%;right:-8%;width:220px;height:220px;border-radius:50%;background:radial-gradient(circle,rgba(255,255,255,.35),transparent 70%);animation:wfGlow 6s ease-in-out infinite;pointer-events:none;}
@keyframes wfGlow{0%,100%{transform:translate(0,0) scale(1);opacity:.7}50%{transform:translate(-22px,16px) scale(1.18);opacity:1}}
.wf-header-icon{display:inline-flex;align-items:center;justify-content:center;width:44px;height:44px;border-radius:14px;background:rgba(255,255,255,.18);font-size:1.3rem;backdrop-filter:blur(4px);animation:wfBeat 1.9s ease-in-out infinite;}
@keyframes wfBeat{0%,100%{transform:scale(1)}15%{transform:scale(1.18)}30%{transform:scale(1)}45%{transform:scale(1.12)}60%{transform:scale(1)}}
.wf-header-sub{opacity:.9;font-weight:600;letter-spacing:.3px;}

.wf-modal-body{padding:24px 24px 8px;background:#f8fafc;}
.wf-timeline{position:relative;}
.wf-timeline-item{position:relative;padding:0 0 22px 58px;}
.wf-timeline-item:last-child{padding-bottom:4px;}
.wf-timeline-item:not(:last-child)::before{content:'';position:absolute;left:20px;top:44px;bottom:4px;width:2px;background:linear-gradient(#cbd5e1,#e2e8f0);}
.wf-node{position:absolute;left:1px;top:2px;width:40px;height:40px;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.05rem;box-shadow:0 6px 16px -4px rgba(2,8,23,.35);z-index:1;}
.wf-node.is-done{background:linear-gradient(135deg,#22c55e,#16a34a);}
.wf-node.is-rejected{background:linear-gradient(135deg,#ef4444,#dc2626);}
.wf-node.is-revision{background:linear-gradient(135deg,#f59e0b,#d97706);}
.wf-node.is-pending{background:linear-gradient(135deg,#3b82f6,#2563eb);}
.wf-node.is-idle{background:linear-gradient(135deg,#94a3b8,#64748b);}
.wf-node.is-revision i{animation:wfSpin 2.4s linear infinite;}
@keyframes wfSpin{to{transform:rotate(360deg)}}
.wf-node.is-pending::after{content:'';position:absolute;inset:-3px;border-radius:50%;border:2px solid #3b82f6;animation:wfPulse 1.7s ease-out infinite;}
@keyframes wfPulse{0%{transform:scale(.9);opacity:.7}100%{transform:scale(1.7);opacity:0}}

.wf-card{background:#fff;border-radius:14px;padding:12px 14px;box-shadow:0 4px 14px -6px rgba(2,8,23,.12);border:1px solid #eef2f7;transition:transform .2s,box-shadow .2s;}
.wf-card:hover{transform:translateY(-2px);box-shadow:0 10px 22px -8px rgba(2,8,23,.2);}
.wf-step-title{font-size:.92rem;font-weight:700;color:#1e293b;line-height:1.3;}
.wf-actor{font-size:.8rem;color:#64748b;margin-top:4px;}
.wf-note{font-size:.8rem;color:#b45309;background:#fffbeb;border-radius:8px;padding:5px 9px;margin-top:7px;}
.wf-time{font-size:.75rem;color:#94a3b8;margin-top:6px;}

.wf-badge{font-size:.7rem;font-weight:700;padding:5px 11px;border-radius:999px;white-space:nowrap;}
.wf-badge-done{background:#dcfce7;color:#15803d;}
.wf-badge-rejected{background:#fee2e2;color:#b91c1c;}
.wf-badge-revision{background:#fef3c7;color:#b45309;}
.wf-badge-pending{background:#dbeafe;color:#1d4ed8;}
.wf-badge-idle{background:#e2e8f0;color:#475569;}

.wf-empty{text-align:center;padding:34px 10px;color:#94a3b8;}
.wf-empty i{font-size:2.4rem;display:block;margin-bottom:10px;opacity:.6;}

.wf-animate .wf-timeline-item{opacity:0;animation:wfIn .5s cubic-bezier(.22,1,.36,1) forwards;}
.wf-animate .wf-timeline-item:nth-child(1){animation-delay:.06s}
.wf-animate .wf-timeline-item:nth-child(2){animation-delay:.16s}
.wf-animate .wf-timeline-item:nth-child(3){animation-delay:.26s}
.wf-animate .wf-timeline-item:nth-child(4){animation-delay:.36s}
.wf-animate .wf-timeline-item:nth-child(5){animation-delay:.46s}
.wf-animate .wf-timeline-item:nth-child(n+6){animation-delay:.56s}
@keyframes wfIn{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:none}}

@media (prefers-reduced-motion: reduce){
.wf-header-glow,.wf-header-icon,.wf-node.is-revision i,.wf-node.is-pending::after,.wf-animate .wf-timeline-item{animation:none!important;}
.wf-animate .wf-timeline-item{opacity:1;}
}

/* ============ MODAL DRAFT SPP ============ */
.spp-modal-content{border-radius:22px;overflow:hidden;box-shadow:0 28px 64px -14px rgba(2,8,23,.4);}
.spp-modal .modal-dialog{transition:transform .42s cubic-bezier(.22,1,.36,1),opacity .42s;}
.spp-modal.fade .modal-dialog{transform:translateY(30px) scale(.94);opacity:0;}
.spp-modal.show .modal-dialog{transform:none;opacity:1;}

.spp-modal-header{position:relative;display:flex;align-items:center;justify-content:space-between;gap:1rem;padding:20px 24px;color:#fff;background:linear-gradient(120deg,#6366f1 0%,#4f46e5 50%,#2563eb 100%);overflow:hidden;}
.spp-header-glow{position:absolute;top:-65%;right:-6%;width:230px;height:230px;border-radius:50%;background:radial-gradient(circle,rgba(255,255,255,.35),transparent 70%);animation:sppGlow 6.5s ease-in-out infinite;pointer-events:none;}
@keyframes sppGlow{0%,100%{transform:translate(0,0) scale(1);opacity:.7}50%{transform:translate(-24px,18px) scale(1.2);opacity:1}}
.spp-header-icon{display:inline-flex;align-items:center;justify-content:center;width:46px;height:46px;border-radius:14px;background:rgba(255,255,255,.18);font-size:1.35rem;backdrop-filter:blur(4px);animation:sppBeat 2s ease-in-out infinite;flex-shrink:0;}
@keyframes sppBeat{0%,100%{transform:scale(1)}15%{transform:scale(1.16)}30%{transform:scale(1)}45%{transform:scale(1.1)}60%{transform:scale(1)}}
.spp-header-sub{opacity:.9;font-weight:600;letter-spacing:.3px;}

.spp-modal-body{padding:22px 24px 6px;background:#f8fafc;}

/* Animasi konten saat modal dibuka */
.spp-anim{opacity:0;}
.spp-modal.is-animating .spp-anim{animation:sppFadeUp .5s cubic-bezier(.22,1,.36,1) forwards;}
.spp-modal.is-animating .spp-anim:nth-of-type(1){animation-delay:.05s}
.spp-modal.is-animating .spp-anim:nth-of-type(2){animation-delay:.13s}
.spp-modal.is-animating .spp-anim:nth-of-type(3){animation-delay:.21s}
.spp-modal.is-animating .spp-anim:nth-of-type(4){animation-delay:.29s}
@keyframes sppFadeUp{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:none}}

/* Revisi alert */
.spp-revisi-alert{display:flex;gap:.7rem;background:#fef2f2;border:1px solid #fecaca;border-left:4px solid #ef4444;border-radius:12px;padding:.85rem 1rem;margin-bottom:1rem;color:#b91c1c;}
.spp-revisi-alert i{font-size:1.2rem;margin-top:1px;}
.spp-revisi-alert strong{display:block;font-size:.82rem;}
.spp-revisi-alert p{font-size:.85rem;}

/* Banner total */
.spp-total-banner{position:relative;display:flex;align-items:center;justify-content:space-between;gap:1rem;overflow:hidden;background:linear-gradient(120deg,#0ea5e9,#2563eb);color:#fff;border-radius:16px;padding:1rem 1.25rem;margin-bottom:1.1rem;box-shadow:0 12px 26px -10px rgba(37,99,235,.55);}
.spp-tb-glow{position:absolute;top:-60%;right:-4%;width:180px;height:180px;border-radius:50%;background:radial-gradient(circle,rgba(255,255,255,.30),transparent 70%);pointer-events:none;}
.spp-tb-left{position:relative;min-width:0;}
.spp-tb-label{display:block;font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;opacity:.95;}
.spp-tb-uraian{display:block;font-size:.78rem;opacity:.88;margin-top:.15rem;}
.spp-tb-value{position:relative;font-size:1.5rem;font-weight:800;white-space:nowrap;font-variant-numeric:tabular-nums;text-shadow:0 1px 2px rgba(0,0,0,.15);}

/* Section */
.spp-section{background:#fff;border:1px solid #eef0f4;border-radius:16px;padding:1.1rem 1.25rem;margin-bottom:1.1rem;}
.spp-section-title{display:inline-flex;align-items:center;gap:.45rem;font-size:.76rem;font-weight:800;text-transform:uppercase;letter-spacing:.05em;color:#475569;margin-bottom:.9rem;}
.spp-section-title i{color:#6366f1;}
.spp-label{display:block;font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:#94a3b8;margin-bottom:.35rem;}
.spp-hint{display:block;font-size:.72rem;color:#94a3b8;margin-top:.3rem;}

.spp-input-group .input-group-text{background:#eef2ff;border:1.5px solid #e2e8f0;border-right:0;color:#6366f1;border-radius:10px 0 0 10px;}
.spp-input-group .form-control{border:1.5px solid #e2e8f0;border-radius:0 10px 10px 0;}
.spp-input-group .form-control:focus{border-color:#6366f1;box-shadow:none;}
.spp-section .form-select{border:1.5px solid #e2e8f0;border-radius:10px;}
.spp-section .form-select:focus{border-color:#6366f1;box-shadow:0 0 0 4px rgba(99,102,241,.12);}

/* COA box di modal */
.spp-coa-box{display:flex;align-items:center;gap:.7rem;border-radius:12px;padding:.65rem .8rem;transition:all .25s ease;}
.spp-coa-box.is-set{background:linear-gradient(135deg,rgba(16,185,129,.10),rgba(14,165,233,.06));border:1.5px solid rgba(16,185,129,.35);}
.spp-coa-box.is-empty{background:#fff7ed;border:1.5px dashed #fdba74;}
.spp-coa-icon{width:36px;height:36px;flex-shrink:0;border-radius:10px;display:inline-flex;align-items:center;justify-content:center;font-size:1rem;color:#fff;}
.spp-coa-box.is-set .spp-coa-icon{background:linear-gradient(135deg,#10b981,#059669);box-shadow:0 6px 14px rgba(16,185,129,.3);}
.spp-coa-box.is-empty .spp-coa-icon{background:linear-gradient(135deg,#fb923c,#f97316);}
.spp-coa-text{min-width:0;}
.spp-coa-kode{display:block;font-family:ui-monospace,'Cascadia Code',Consolas,monospace;font-size:.86rem;font-weight:700;color:#065f46;word-break:break-all;line-height:1.25;}
.spp-coa-box.is-empty .spp-coa-kode{color:#c2410c;}
.spp-coa-nama{display:block;font-size:.76rem;font-weight:600;color:#64748b;margin-top:.1rem;}

/* Signer card */
.spp-signer{display:flex;align-items:center;gap:.65rem;background:#f8fafc;border:1px solid #eef0f4;border-radius:12px;padding:.7rem .85rem;height:100%;transition:all .2s ease;}
.spp-signer:hover{background:#fff;box-shadow:0 8px 18px rgba(15,23,42,.06);transform:translateY(-2px);}
.spp-signer-ico{width:36px;height:36px;flex-shrink:0;border-radius:10px;display:inline-flex;align-items:center;justify-content:center;font-size:1rem;color:#fff;}
.spp-sg-ppk{background:linear-gradient(135deg,#818cf8,#6366f1);}
.spp-sg-koor{background:linear-gradient(135deg,#38bdf8,#0ea5e9);}
.spp-sg-kasub{background:linear-gradient(135deg,#a78bfa,#8b5cf6);}
.spp-signer-role{display:block;font-size:.66rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:#94a3b8;}
.spp-signer-name{display:block;font-size:.85rem;font-weight:700;color:#1e293b;line-height:1.25;}

.spp-modal-footer{display:flex;justify-content:flex-end;gap:.6rem;padding:16px 24px;background:#fff;border-top:1px solid #eef0f4;}
.spp-save-btn{box-shadow:0 8px 18px rgba(99,102,241,.32);}

@media (prefers-reduced-motion: reduce){
.spp-header-glow,.spp-header-icon{animation:none!important;}
.spp-anim{opacity:1!important;}
.spp-modal.is-animating .spp-anim{animation:none!important;}
}
</style>
@endpush
@push('script')
<script>
// Pindahkan semua modal ke <body> agar tidak terjebak stacking-context kartu
// (modal nested di .msp-card/.rekap-wrap bisa berada di bawah backdrop → tidak bisa diklik).
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.spp-modal, .wf-modal, [id^="modalUploadSignedSpp"]').forEach(function (m) {
        if (m.parentElement !== document.body) document.body.appendChild(m);
    });
});

// Mainkan animasi timeline tiap kali modal aktivitas dibuka.
document.addEventListener('show.bs.modal', function (e) {
    if (!e.target.id || e.target.id.indexOf('modalAktivitas') !== 0) return;
    const tl = e.target.querySelector('.wf-timeline');
    if (!tl) return;
    tl.classList.remove('wf-animate');
    void tl.offsetWidth; // paksa reflow agar animasi restart
    tl.classList.add('wf-animate');
});

// Modal Draft SPP: animasi konten + sinkronkan COA dari kartu item (tanpa reload).
document.addEventListener('show.bs.modal', function (e) {
    if (!e.target.id || e.target.id.indexOf('modalSpp') !== 0) return;
    const modal = e.target;

    // Restart animasi konten.
    modal.classList.remove('is-animating');
    void modal.offsetWidth;
    modal.classList.add('is-animating');

    // Sinkronkan tampilan COA dari select kartu item terkait.
    const coaBox = modal.querySelector('.spp-coa-box');
    if (!coaBox) return;
    const komponenId = coaBox.dataset.komponenId;
    const form = document.querySelector('.komponen-coa-form[data-komponen-id="' + komponenId + '"]');
    const select = form ? form.querySelector('.komponen-coa-select') : null;
    const opt = select && select.selectedIndex >= 0 ? select.options[select.selectedIndex] : null;

    const kodeEl = coaBox.querySelector('.spp-coa-kode');
    const namaEl = coaBox.querySelector('.spp-coa-nama');

    if (opt && opt.value) {
        const parts = (opt.textContent || '').split('|').map((s) => s.trim());
        const kode = (opt.dataset.kodeMak || parts[0] || '').trim();
        const nama = (parts[1] || 'Nama akun tidak tersedia').trim();
        coaBox.classList.add('is-set');
        coaBox.classList.remove('is-empty');
        coaBox.querySelector('.spp-coa-icon i').className = 'bi bi-upc-scan';
        if (kodeEl) kodeEl.textContent = kode;
        if (namaEl) namaEl.textContent = nama;
    } else {
        coaBox.classList.add('is-empty');
        coaBox.classList.remove('is-set');
        if (kodeEl) kodeEl.textContent = 'Belum ada COA';
        if (namaEl) namaEl.textContent = 'Pilih COA pada kartu item terlebih dahulu';
    }
});
</script>
@endpush
@endonce
@once
@push('css')
<style>
/* ============ KOMPONEN CARD (Rekap Item Biaya) ============ */
@keyframes kompIn { from { opacity: 0; transform: translateY(14px); } to { opacity: 1; transform: none; } }

.komp-card {
    position: relative;
    background: #fff;
    border: 1px solid #e9edf5;
    border-radius: 1.1rem;
    padding: 1.15rem 1.35rem 1.15rem 1.6rem;
    margin-bottom: 1rem;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(15,23,42,.04);
    transition: transform .25s ease, box-shadow .25s ease, border-color .25s ease;
    animation: kompIn .45s cubic-bezier(.22,1,.36,1) both;
    animation-delay: calc(.06s * var(--ki, 0));
}
.komp-card:hover { transform: translateY(-3px); box-shadow: 0 16px 34px rgba(15,23,42,.10); border-color: #dbe2ef; }

/* Accent bar kiri sesuai status SPP */
.komp-bar { position: absolute; left: 0; top: 0; bottom: 0; width: 5px; background: var(--kc, #94a3b8); }
.komp-card.sk-muted   { --kc: #cbd5e1; }
.komp-card.sk-secondary { --kc: #94a3b8; }
.komp-card.sk-primary { --kc: #6366f1; }
.komp-card.sk-info    { --kc: #0ea5e9; }
.komp-card.sk-success { --kc: #10b981; }
.komp-card.sk-warning { --kc: #f59e0b; }
.komp-card.sk-danger  { --kc: #f43f5e; }

/* ---- Baris atas ---- */
.komp-top { display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap; }
.komp-identity { display: flex; align-items: center; gap: .9rem; min-width: 0; }
.komp-ico {
    width: 50px; height: 50px; flex-shrink: 0;
    border-radius: 15px;
    display: inline-flex; align-items: center; justify-content: center;
    font-size: 1.4rem; color: #fff;
    background: linear-gradient(135deg, #818cf8, #6366f1);
    box-shadow: 0 8px 18px rgba(99,102,241,.32);
    transition: transform .3s ease;
}
.komp-card:hover .komp-ico { transform: rotate(-6deg) scale(1.06); }
.komp-id-text { min-width: 0; }
.komp-name { font-size: 1.05rem; font-weight: 800; color: #0f172a; margin: 0 0 .35rem; letter-spacing: -.01em; }
.komp-tags { display: flex; align-items: center; gap: .4rem; flex-wrap: wrap; }
.komp-chip {
    display: inline-flex; align-items: center; gap: .3rem;
    font-size: .72rem; font-weight: 600;
    padding: .25rem .65rem; border-radius: 999px;
    background: #eef2ff; color: #4338ca;
}
.komp-badge { display: inline-flex; align-items: center; gap: .3rem; font-size: .72rem; font-weight: 700; padding: .25rem .65rem; border-radius: 999px; }
.komp-badge-muted { background: #f1f5f9; color: #64748b; }

.komp-nominal { text-align: right; flex-shrink: 0; }
.komp-nom-label { display: block; font-size: .66rem; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: #94a3b8; margin-bottom: .1rem; }
.komp-nom-value { display: block; font-size: 1.4rem; font-weight: 800; color: #0f172a; line-height: 1.05; font-variant-numeric: tabular-nums; }

/* ---- Baris bawah (COA + aksi) ---- */
.komp-bottom {
    display: flex; align-items: flex-end; gap: 1.25rem;
    margin-top: 1.1rem; padding-top: 1.1rem;
    border-top: 1px dashed #e9edf5;
    flex-wrap: wrap;
}
.komp-coa-area { flex: 1 1 380px; min-width: 0; }
.komp-coa-head { display: flex; align-items: center; justify-content: space-between; gap: .5rem; margin-bottom: .55rem; }
.komp-coa-title { display: inline-flex; align-items: center; gap: .4rem; font-size: .74rem; font-weight: 800; text-transform: uppercase; letter-spacing: .05em; color: #475569; }
.komp-coa-title i { color: #6366f1; }
.komp-pill { display: inline-flex; align-items: center; gap: .3rem; font-size: .7rem; font-weight: 700; padding: .22rem .65rem; border-radius: 999px; white-space: nowrap; }
.komp-pill-ok   { background: #dcfce7; color: #15803d; }
.komp-pill-warn { background: #fff7ed; color: #c2410c; }

/* Kartu COA terpilih — mudah dibaca */
.komp-coa-readable {
    display: flex; align-items: center; gap: .75rem;
    background: linear-gradient(135deg, rgba(99,102,241,.07), rgba(14,165,233,.05));
    border: 1px solid rgba(99,102,241,.20);
    border-radius: .8rem;
    padding: .7rem .85rem;
    margin-bottom: .65rem;
}
.komp-coa-readable .kcr-icon {
    width: 38px; height: 38px; flex-shrink: 0;
    border-radius: 11px;
    display: inline-flex; align-items: center; justify-content: center;
    font-size: 1.05rem; color: #fff;
    background: linear-gradient(135deg, #6366f1, #4f46e5);
    box-shadow: 0 6px 14px rgba(99,102,241,.30);
}
.komp-coa-readable .kcr-text { min-width: 0; flex: 1 1 auto; }
.komp-coa-readable .kcr-kode {
    display: block;
    font-family: 'SFMono-Regular', ui-monospace, 'Cascadia Code', Consolas, monospace;
    font-size: .92rem; font-weight: 700; color: #312e81;
    letter-spacing: .01em; word-break: break-all; line-height: 1.25;
}
.komp-coa-readable .kcr-nama { display: block; font-size: .78rem; font-weight: 600; color: #64748b; margin-top: .1rem; }

/* Sisa pagu COA */
.komp-coa-readable .kcr-pagu {
    flex-shrink: 0; text-align: right; padding-left: .85rem; margin-left: auto;
    border-left: 1px dashed rgba(99,102,241,.25);
}
.kcr-pagu-label { display: block; font-size: .62rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: #94a3b8; }
.kcr-pagu-value { display: block; font-size: .92rem; font-weight: 800; color: #15803d; line-height: 1.2; font-variant-numeric: tabular-nums; white-space: nowrap; }

@media (max-width: 575.98px) {
    .komp-coa-readable .kcr-pagu { padding-left: .6rem; }
    .kcr-pagu-value { font-size: .82rem; }
}

.komp-coa-sublabel { display: block; font-size: .72rem; font-weight: 600; color: #94a3b8; margin-bottom: .25rem; }

/* ---- Aksi ---- */
.komp-action { flex: 0 0 auto; display: flex; flex-direction: column; align-items: stretch; gap: .5rem; min-width: 180px; }
.komp-action-hint { display: inline-flex; align-items: center; gap: .3rem; font-size: .72rem; color: #94a3b8; justify-content: center; }
.komp-spp-no { display: inline-flex; align-items: center; font-size: .8rem; font-weight: 700; color: #4338ca; }
.komp-action-btns { display: flex; flex-wrap: wrap; gap: .4rem; justify-content: flex-end; }

@media (max-width: 575.98px) {
    .komp-top { align-items: flex-start; }
    .komp-nominal { text-align: left; }
    .komp-action { width: 100%; min-width: 0; }
    .komp-action-btns { justify-content: flex-start; }
}

@media (prefers-reduced-motion: reduce) {
    .komp-card { animation: none !important; }
}
</style>
@endpush
@push('script')
<script>
(function () {
    // Real-time validasi sisa PAGU saat operator pilih COA untuk komponen perjaldin.
    // Total biaya komponen tidak boleh > sisa pagu COA yang dipilih. Saat COA valid
    // dipilih, form auto-submit menyimpan COA — operator tidak perlu klik tombol simpan.
    const formatRp = (n) => 'Rp ' + Number(n).toLocaleString('id-ID');

    function findBuatSppBtn(komponenId) {
        return document.querySelector('.komponen-buat-spp-btn[data-komponen-id="' + komponenId + '"]');
    }

    function setBuatSppBtnState(komponenId, enabled, tooltip) {
        const btn = findBuatSppBtn(komponenId);
        if (!btn) return;
        btn.disabled = !enabled;
        btn.title = tooltip || (enabled ? 'Buat SPP untuk komponen ini' : 'Pilih COA terlebih dahulu');
    }

    function evaluateForm(form) {
        const select = form.querySelector('.komponen-coa-select');
        const feedback = form.querySelector('.komponen-coa-feedback');
        if (!select || !feedback) return { valid: false, changed: false };

        const komponenId = form.dataset.komponenId;
        const totalNominal = parseFloat(form.dataset.totalNominal || '0');
        const currentCoaId = form.dataset.currentCoaId || '';
        const selectedOpt = select.options[select.selectedIndex] || null;
        const selectedId = selectedOpt ? selectedOpt.value : '';

        // Belum pilih COA
        if (!selectedId) {
            feedback.style.display = 'none';
            select.classList.remove('is-invalid', 'is-valid');
            setBuatSppBtnState(komponenId, false, 'Pilih COA terlebih dahulu');
            return { valid: false, changed: false };
        }

        const sisaPagu = parseFloat(selectedOpt.dataset.sisaPagu || '0');
        const isSameCoa = String(selectedId) === String(currentCoaId);

        if (!isSameCoa && totalNominal > sisaPagu) {
            // Over pagu — block save dan disable Buat SPP
            const kurang = totalNominal - sisaPagu;
            feedback.innerHTML = '<i class="bi bi-exclamation-triangle-fill text-danger me-1"></i>'
                + '<strong class="text-danger">Sisa pagu tidak cukup.</strong> '
                + 'Total biaya ' + formatRp(totalNominal) + ' melebihi sisa pagu COA ('
                + formatRp(sisaPagu) + '). Kurang ' + formatRp(kurang) + '.';
            feedback.style.display = 'block';
            select.classList.add('is-invalid');
            select.classList.remove('is-valid');
            setBuatSppBtnState(komponenId, false, 'Sisa pagu COA tidak mencukupi');
            return { valid: false, changed: true };
        }

        // Valid — COA mencukupi
        if (isSameCoa) {
            // Re-pilih COA yang sudah tersimpan → tidak perlu submit, cukup info
            feedback.style.display = 'none';
            select.classList.remove('is-invalid', 'is-valid');
        } else {
            feedback.innerHTML = '<i class="bi bi-arrow-clockwise text-primary me-1"></i>'
                + '<span class="text-primary">Menyimpan COA…</span> '
                + 'Sisa pagu setelah komitmen: ' + formatRp(sisaPagu - totalNominal) + '.';
            feedback.style.display = 'block';
            select.classList.add('is-valid');
            select.classList.remove('is-invalid');
        }
        setBuatSppBtnState(komponenId, true, 'Buat SPP untuk komponen ini');
        return { valid: true, changed: !isSameCoa };
    }

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    // Perbarui kartu COA "mudah dibaca" tanpa reload, dari teks option terpilih.
    // Format option_label: "KODE | NAMA AKUN | Pagu Rp .. | Sisa Rp ..".
    function updateCoaReadable(form, selectedOpt) {
        const card = form.closest('.komp-card');
        if (!card || !selectedOpt) return;

        const parts = (selectedOpt.textContent || '').split('|').map((s) => s.trim());
        const kode = (selectedOpt.dataset.kodeMak || parts[0] || '').trim();
        const nama = (parts[1] || 'Nama akun tidak tersedia').trim();
        const sisaPagu = parseFloat(selectedOpt.dataset.sisaPagu || '0');

        // Tandai status kartu & pill.
        card.classList.add('is-coa-ok');
        card.classList.remove('is-coa-missing');
        const pill = card.querySelector('.komp-pill');
        if (pill) {
            pill.className = 'komp-pill komp-pill-ok';
            pill.innerHTML = '<i class="bi bi-check-circle-fill"></i> COA Terpilih';
        }

        // Buat / perbarui blok readable.
        let readable = card.querySelector('.komp-coa-readable');
        if (!readable) {
            readable = document.createElement('div');
            readable.className = 'komp-coa-readable';
            readable.innerHTML = '<span class="kcr-icon"><i class="bi bi-upc-scan"></i></span>'
                + '<div class="kcr-text"><span class="kcr-kode"></span><span class="kcr-nama"></span></div>'
                + '<div class="kcr-pagu"><span class="kcr-pagu-label">Sisa Pagu</span><span class="kcr-pagu-value"></span></div>';
            const head = card.querySelector('.komp-coa-head');
            if (head) head.insertAdjacentElement('afterend', readable);
        }
        readable.querySelector('.kcr-kode').textContent = kode;
        readable.querySelector('.kcr-nama').textContent = nama;

        // Pastikan elemen sisa pagu ada (DOM lama mungkin belum punya), lalu perbarui.
        let paguEl = readable.querySelector('.kcr-pagu');
        if (!paguEl) {
            paguEl = document.createElement('div');
            paguEl.className = 'kcr-pagu';
            paguEl.innerHTML = '<span class="kcr-pagu-label">Sisa Pagu</span><span class="kcr-pagu-value"></span>';
            readable.appendChild(paguEl);
        }
        paguEl.querySelector('.kcr-pagu-value').textContent = formatRp(sisaPagu);

        // Perbarui label sub & jadikan "Ganti COA".
        const sublabel = form.querySelector('.komp-coa-sublabel');
        if (sublabel) sublabel.textContent = 'Ganti COA';
    }

    // Simpan COA via AJAX — tanpa reload halaman.
    async function saveCoaIfNeeded(form, result) {
        if (!result.valid || !result.changed) return;
        const select = form.querySelector('.komponen-coa-select');
        const feedback = form.querySelector('.komponen-coa-feedback');
        if (!select || select.disabled) return;
        if (form.dataset.submitting === '1') return;

        const selectedOpt = select.options[select.selectedIndex] || null;
        const selectedId = selectedOpt ? selectedOpt.value : '';
        if (!selectedId) return;

        form.dataset.submitting = '1';
        select.style.pointerEvents = 'none';

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    _method: 'PUT',
                    dipa_revision_item_id: selectedId,
                }),
            });
            const data = await response.json().catch(() => ({}));

            if (response.ok && data.success) {
                // Tersimpan: tandai COA sebagai current, aktifkan tombol Buat SPP.
                form.dataset.currentCoaId = selectedId;
                select.classList.remove('is-invalid');
                select.classList.add('is-valid');
                feedback.innerHTML = '<i class="bi bi-check-circle-fill text-success me-1"></i>'
                    + '<span class="text-success">' + (data.message || 'COA tersimpan.') + '</span>';
                feedback.style.display = 'block';
                updateCoaReadable(form, selectedOpt);
                setBuatSppBtnState(form.dataset.komponenId, true, 'Buat SPP untuk komponen ini');
            } else {
                select.classList.add('is-invalid');
                select.classList.remove('is-valid');
                feedback.innerHTML = '<i class="bi bi-exclamation-triangle-fill text-danger me-1"></i>'
                    + '<span class="text-danger">' + (data.message || 'Gagal menyimpan COA.') + '</span>';
                feedback.style.display = 'block';
                setBuatSppBtnState(form.dataset.komponenId, false, 'Pilih COA yang valid');
            }
        } catch (err) {
            feedback.innerHTML = '<i class="bi bi-exclamation-triangle-fill text-danger me-1"></i>'
                + '<span class="text-danger">Gagal menyimpan COA. Periksa koneksi lalu coba lagi.</span>';
            feedback.style.display = 'block';
        } finally {
            form.dataset.submitting = '0';
            select.style.pointerEvents = '';
        }
    }

    // Handler perubahan COA: evaluasi pagu + simpan (AJAX) + perbarui tampilan.
    function handleCoaChange(selectEl) {
        const form = selectEl.closest('.komponen-coa-form');
        if (!form) return;
        const result = evaluateForm(form);
        saveCoaIfNeeded(form, result);
    }

    // Initial evaluation (jika halaman dimuat dengan COA sudah terpilih)
    document.querySelectorAll('.komponen-coa-form').forEach((form) => {
        evaluateForm(form);
    });

    // Select COA di-enhance Select2 (jQuery). Select2 memicu event `change`
    // lewat jQuery.trigger() yang TIDAK menjangkau addEventListener native,
    // sehingga binding WAJIB via jQuery agar pemilihan COA lewat dropdown
    // terdeteksi (menyimpan COA & memperbarui kartu "mudah dibaca").
    // jQuery .on('change') juga tetap menangkap event change native biasa.
    if (window.jQuery) {
        window.jQuery(document).on('change', '.komponen-coa-select', function () {
            handleCoaChange(this);
        });
    } else {
        document.addEventListener('change', (e) => {
            if (e.target.matches('.komponen-coa-select')) handleCoaChange(e.target);
        });
    }

    // Cegah submit form biasa (Enter) yang menyebabkan reload halaman.
    document.addEventListener('submit', (e) => {
        if (e.target.matches('.komponen-coa-form')) {
            e.preventDefault();
        }
    });
})();
</script>
@endpush
@endonce
