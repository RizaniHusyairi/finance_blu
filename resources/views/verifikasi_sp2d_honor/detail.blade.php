@extends('layouts.app')
@section('title', 'Detail Verifikasi SP2D Honorarium')

@php
    $canEdit = $canVerify ?? false;

    $statusClassMap = [
        'PENDING'  => 'text-warning font-monospace fw-bold',
        'APPROVED' => 'text-success fw-bold',
        'REVISION' => 'text-danger fw-bold'
    ];
    $ppkStatus = $statusClassMap[$ppkApproval?->status ?? ''] ?? 'text-secondary fw-bold';
    $kasubbagStatus = $statusClassMap[$kasubbagApproval?->status ?? ''] ?? 'text-secondary fw-bold';

    function labelStatusIndo($status) {
        return match($status) {
            'PENDING' => 'Menunggu',
            'APPROVED' => 'Disetujui',
            'REVISION' => 'Revisi',
            default => 'Belum Valid',
        };
    }
@endphp

@push('css')
    <style>
        .split-card { border: 0; box-shadow: 0 0.125rem 0.25rem rgba(15,23,42,.03); border: 1px solid rgba(15,23,42,.08); border-radius: 12px; overflow: hidden; background: #fff; margin-bottom: 2rem;}
        .card-header-gray { background: #f8fafc; border-bottom: 1px solid rgba(15,23,42,.06); padding: 1rem 1.5rem; font-weight: 700; color: #1e293b; display: flex; align-items: center; justify-content: space-between; }
        .info-group { margin-bottom: 1.5rem; }
        .info-label { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 700; color: #64748b; margin-bottom: 0.25rem; }
        .info-value { font-size: 0.95rem; font-weight: 600; color: #1e293b; border-bottom: 1px dashed rgba(15,23,42,.15); padding-bottom: .25rem; display: inline-block; width: 100%; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        
        .timeline-vertical { position: relative; padding-left: 2rem; margin-top: 1.5rem; }
        .timeline-vertical::before { content: ''; position: absolute; left: 0.5rem; top: 0; bottom: 0; width: 2px; background: #e2e8f0; }
        .tl-item { position: relative; margin-bottom: 1.5rem; }
        .tl-item:last-child { margin-bottom: 0; }
        .tl-dot { position: absolute; left: -2.35rem; width: 1.5rem; height: 1.5rem; border-radius: 50%; background: #fff; border: 3px solid #e2e8f0; display: flex; align-items: center; justify-content: center; z-index: 2; transition: all .2s; }
        .tl-item.approved .tl-dot { border-color: #10b981; background: #10b981; }
        .tl-item.rejected .tl-dot { border-color: #ef4444; background: #ef4444; }
        .tl-item.pending .tl-dot { border-color: #f59e0b; background: #fcf1ce; }
        .tl-title { font-weight: 700; color: #334155; font-size: 0.9rem; margin-bottom: 0.25rem; }
        .tl-meta { font-size: 0.8rem; color: #64748b; }
        .tl-comment { background: #f1f5f9; padding: 0.75rem; border-radius: 0.5rem; font-size: 0.8rem; margin-top: 0.5rem; border-left: 3px solid #cbd5e1; }
        .tl-item.rejected .tl-comment { border-left-color: #ef4444; background: #fef2f2; }

        .checker-item { display: flex; align-items: flex-start; gap: .75rem; padding: .65rem; border-bottom: 1px solid rgba(15,23,42,.05); }
        .checker-item:last-child { border-bottom: none; }
        .checker-icon.ready { color: #10b981; }
        .checker-icon.missing { color: #ef4444; }
    </style>
@endpush

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <x-page-title title="Persetujuan Pencairan SP2D" subtitle="{{ $roleCode }}" />
        <a href="{{ route('verifikasi-sp2d.honor.index') }}" class="btn btn-outline-secondary btn-sm fw-bold"><i class="bi bi-arrow-left me-1"></i> Kembali ke Rak Antrean</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm alert-dismissible fade show">
            <i class="bi bi-check-circle-fill me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger border-0 shadow-sm alert-dismissible fade show">
            <ul class="mb-0 ps-3">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row g-4">
        {{-- KIRI: Transparansi Sumber Historis & Draf SP2D --}}
        <div class="col-xl-8">

            {{-- Info Pokok Dokumen SP2D --}}
            <div class="split-card">
                <div class="card-header-gray">
                    <div><i class="bi bi-wallet2 text-primary me-2"></i> Identifikasi SP2D & Akar Asal</div>
                    <span class="badge bg-dark fw-normal rounded-pill px-3">{{ $sp2d->status }}</span>
                </div>
                <div class="p-4 row">
                    <div class="col-md-3 info-group"><div class="info-label">Draf No. SP2D</div><div class="info-value font-monospace text-success">{{ $sp2d->nomor_sp2d ?? '-' }}</div></div>
                    <div class="col-md-3 info-group"><div class="info-label">Tanggal SP2D</div><div class="info-value">{{ optional($sp2d->tanggal_sp2d)->format('d M Y') ?? '-' }}</div></div>
                    <div class="col-md-3 info-group"><div class="info-label">No. NPI Sumber</div><div class="info-value text-primary">{{ $npi?->nomor_npi ?? '-' }}</div></div>
                    <div class="col-md-3 info-group"><div class="info-label">Asal Pengajuan SPM</div><div class="info-value">{{ $spm?->nomor_spm ?? '-' }}</div></div>

                    <div class="col-12 mt-2">
                        <div class="info-label">Deskripsi Beban Giro & Honorarium</div>
                        <div class="fw-bold text-dark fs-5">{{ $tagihan?->deskripsi ?? 'Pencairan Beban Tetap' }}</div>
                    </div>
                </div>
            </div>

            {{-- Ekstraksi Netto & Tabel Rekening Terlampir --}}
            <div class="split-card">
                <div class="card-header-gray">
                    <div><i class="bi bi-people-fill text-primary me-2"></i> Distribusi Pencairan Ril Personil (Tervalidasi)</div>
                    @php $rekeningBermasalah = collect($checklist)->firstWhere('status', 'missing') !== null; @endphp
                    @if($rekeningBermasalah)
                     <span class="badge bg-danger rounded-pill"><i class="bi bi-exclamation-circle"></i> Anomali Terdeteksi</span>
                    @else
                     <span class="badge bg-success bg-opacity-10 text-success rounded-pill border border-success"><i class="bi bi-check2-all"></i> Clean Bill</span>
                    @endif
                </div>
                <div class="p-0 table-responsive" style="max-height: 400px;">
                    <table class="table table-hover align-middle mb-0" style="font-size: 0.85rem;">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th class="ps-4">Subjek Penerima</th>
                                <th>Verifikasi Alur Transfer</th>
                                <th class="text-end pe-4">Bersih Transfer (Netto)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($tagihan?->detailHonorarium ?? [] as $det)
                            <tr>
                                <td class="ps-4 py-3">
                                    <div class="fw-bold text-dark">{{ $det->nama_personel }}</div>
                                    <div class="text-muted" style="font-size: 0.75rem;">NIP. {{ $det->nip ?? '-' }}</div>
                                </td>
                                <td>
                                    @if(empty($det->rekening))
                                        <span class="badge bg-danger">Rekening Nihil / Anomali!</span>
                                    @else
                                        <div class="fw-bold text-primary">{{ $det->jenis_bank ?? 'BANK' }} - {{ $det->rekening }}</div>
                                        <div class="text-muted" style="font-size: 0.75rem;">Pemilik a/n: {{ $det->nama_rekening }}</div>
                                    @endif
                                </td>
                                <td class="text-end pe-4 fw-bold text-success">
                                    Rp {{ number_format($det->netto ?? 0, 0, ',', '.') }}
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="3" class="text-center text-muted p-4">Rincian personel tak diketemukan!</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="bg-light p-3 border-top d-flex justify-content-end align-items-center gap-4">
                    <div class="text-end">
                        <div class="small fw-bold text-muted text-uppercase">Total Pemotongan PPh</div>
                        <div class="fw-bold text-danger">Rp {{ number_format($tagihan?->total_potongan ?? 0, 0, ',', '.') }}</div>
                    </div>
                    <div class="text-end">
                        <div class="small fw-bold text-dark text-uppercase">TOTAL PENCAIRAN SP2D RIL</div>
                        <div class="fw-bold fs-4 text-success mb-0" style="line-height:1;">Rp {{ number_format($tagihan?->total_netto ?? 0, 0, ',', '.') }}</div>
                    </div>
                </div>
            </div>

            {{-- Lampiran Bukti Fisik --}}
            <div class="split-card">
                <div class="card-header-gray py-2"><div><i class="bi bi-paperclip text-primary me-2"></i> Bundle Bukti (SPP/NPI)</div></div>
                <div class="p-3 d-flex flex-wrap gap-2">
                    @forelse($sp2d->npi?->spm?->spp?->tagihan?->arsipDokumen ?? [] as $arsip)
                        <a href="{{ Storage::url($arsip->file_path) }}" target="_blank" class="btn btn-sm btn-outline-secondary rounded-pill px-3 shadow-sm bg-white">
                            <i class="bi bi-file-earmark-pdf text-danger me-1"></i> {{ $arsip->nama_dokumen }}
                        </a>
                    @empty
                        <div class="text-muted small px-3">Tidak terkalibrasi ke bundle bukti PDF.</div>
                    @endforelse
                </div>
            </div>

            {{-- Log Riwayat Sistem --}}
            <div class="split-card">
                <div class="card-header-gray py-2"><div><i class="bi bi-hourglass-bottom text-primary me-2"></i> Logis Trail Audit (5 Perekut terakhir)</div></div>
                <div class="p-0">
                    <table class="table table-sm mb-0 align-middle" style="font-size:0.8rem;">
                        <tbody>
                            @forelse($recentLogs as $log)
                            <tr>
                                <td class="ps-3 py-2 text-muted" width="15%">{{ $log->created_at->format('d/m/Y H:i') }}</td>
                                <td width="20%"><span class="badge bg-light text-dark border">{{ $log->role_saat_itu }}</span><br><small>{{ $log->user?->name }}</small></td>
                                <td class="fw-semibold">{{ $log->aksi }}</td>
                                <td>{{ Str::limit($log->catatan, 35) }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="4" class="text-center text-muted p-2">Sistem belum mencatat peluit audit apapun.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

        {{-- KANAN: Terminal Keputusan (Setuju / Tolak) --}}
        <div class="col-xl-4">
            <div class="sticky-top" style="top: 1.5rem; z-index: 10;">
                
                {{-- Panel Ceklis & Persetujuan SP2D paralel --}}
                <div class="split-card mb-4 shadow">
                    <div class="card-header-gray bg-white border-bottom-0 pb-0">
                        <div class="w-100">
                            <h6 class="fw-bold mb-1 text-primary"><i class="bi bi-arrow-down-square-fill me-1"></i> Otoritas SP2D Paralel</h6>
                            <div class="small text-muted mb-3">Persilangan persetujuan SP2D Honorarium</div>
                        </div>
                    </div>
                    
                    <div class="px-4 pb-3">
                        <div class="timeline-vertical mt-0">
                            {{-- Step 1. Pengajuan NPI Bendahara --}}
                            <div class="tl-item approved">
                                <div class="tl-dot"><i class="bi bi-check text-white"></i></div>
                                <div class="tl-title">Alur Pelepasan SP2D</div>
                                <div class="tl-meta">Oleh: {{ $sp2d->bendaharaPengeluaran?->name ?? 'Bendahara Pengeluaran' }}</div>
                            </div>

                            {{-- Step 2. Verifikasi PPK (Paralel A) --}}
                            @php
                                $ppkClass = match($ppkApproval?->status) {
                                    'APPROVED' => 'approved',
                                    'REVISION' => 'rejected',
                                    default => 'pending'
                                };
                            @endphp
                            <div class="tl-item {{ $ppkClass }}">
                                <div class="tl-dot">
                                    @if($ppkClass == 'approved') <i class="bi bi-check text-white"></i>
                                    @elseif($ppkClass == 'rejected') <i class="bi bi-x text-white"></i>
                                    @else <i class="bi bi-person text-warning"></i> @endif
                                </div>
                                <div class="tl-title">PPK</div>
                                <div class="tl-meta {{ $ppkStatus }}">STATUS: {{ labelStatusIndo($ppkApproval?->status) }}</div>
                                @if($ppkApproval?->catatan)
                                    <div class="tl-comment">{{ $ppkApproval->catatan }}</div>
                                @endif
                            </div>

                            {{-- Step 3. Verifikasi Kasubbag (Paralel B) --}}
                            @php
                                $kasClass = match($kasubbagApproval?->status) {
                                    'APPROVED' => 'approved',
                                    'REVISION' => 'rejected',
                                    default => 'pending'
                                };
                            @endphp
                            <div class="tl-item {{ $kasClass }}">
                                <div class="tl-dot">
                                    @if($kasClass == 'approved') <i class="bi bi-check text-white"></i>
                                    @elseif($kasClass == 'rejected') <i class="bi bi-x text-white"></i>
                                    @else <i class="bi bi-person text-warning"></i> @endif
                                </div>
                            <div class="tl-title">Kepala Subbagian Keuangan dan Tata Usaha</div>
                                <div class="tl-meta {{ $kasubbagStatus }}">STATUS: {{ labelStatusIndo($kasubbagApproval?->status) }}</div>
                                @if($kasubbagApproval?->catatan)
                                    <div class="tl-comment">{{ $kasubbagApproval->catatan }}</div>
                                @endif
                            </div>

                            {{-- Step Akhir --}}
                            <div class="tl-item {{ in_array($sp2d->status, ['DISETUJUI_FINAL', 'EXECUTED']) ? 'approved' : 'pending' }}">
                                <div class="tl-dot"><i class="bi bi-flag-fill {{ in_array($sp2d->status, ['DISETUJUI_FINAL', 'EXECUTED']) ? 'text-white' : 'opacity-50' }}"></i></div>
                                <div class="tl-title">Pencairan Dana (Final)</div>
                            </div>
                        </div>
                    </div>

                    {{-- Bila ada Hak Approve --}}
                    @if($canVerify)
                    <div class="p-4 bg-light border-top">
                        <div class="d-flex align-items-center mb-3">
                            <span class="badge bg-warning text-dark shadow-sm py-2 px-3 fw-bold w-100 blink-soft" style="font-size: 0.8rem;">
                                <i class="bi bi-exclamation-circle-fill me-1"></i> MEMINTA RESPON ANDA
                            </span>
                        </div>
                        
                        <div class="d-flex flex-column gap-2">
                            <button type="button" class="btn btn-success fw-bold shadow py-2" data-bs-toggle="modal" data-bs-target="#modalApprove">
                                <i class="bi bi-check-circle-fill me-1"></i> SAHKAN PENCAIRAN SP2D 
                            </button>
                            <button type="button" class="btn btn-danger btn-sm shadow-sm opacity-75 mt-1" data-bs-toggle="modal" data-bs-target="#modalReject">
                                <i class="bi bi-x-circle me-1"></i> Gagalkan & Turunkan Revisi
                            </button>
                        </div>
                    </div>
                    @else
                        <div class="p-4 bg-white border-top text-center text-muted small">
                            <i class="bi bi-shield-lock text-secondary fs-3 d-block mb-1 opacity-50"></i>
                            Sidang verifikasi Anda tidak aktif atau berkas telah kedaluwarsa diloloskan/diblokir pada langkah Anda.
                        </div>
                    @endif
                </div>

            </div>
        </div>
    </div>

    {{-- Modal Afirmasi --}}
    @if($canVerify)
    <div class="modal fade" id="modalApprove" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered border-0">
            <div class="modal-content shadow-lg border-0 rounded-4">
                <form action="{{ route('verifikasi-sp2d.honor.approve', $sp2d->id) }}" method="POST">
                    @csrf
                    <div class="modal-header bg-success text-white border-0 py-3 rounded-top-4">
                        <h6 class="modal-title fw-bold"><i class="bi bi-check2-square me-2"></i> Persetujuan SP2D {{ $roleCode }}</h6>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4 bg-light">
                        <p class="small text-muted mb-3">Dengan melegitimasi berkas ini, Anda menyepakati transfer netto riil sebesar <strong class="text-dark">Rp {{ number_format($tagihan?->total_netto ?? 0, 0, ',', '.') }}</strong> dengan SP2D <strong class="text-dark">{{ $sp2d->nomor_sp2d }}</strong>. Apakah rekening penerima dirasa aman?</p>
                        <div class="mb-0">
                            <label class="form-label small fw-bold">Catatan Restu (Opsional)</label>
                            <textarea name="catatan" class="form-control" rows="2" placeholder="Sematkan catatan persetujuan jika diperlukan..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pb-4 pe-4 bg-light">
                        <button type="button" class="btn btn-secondary px-4 fw-bold" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-success px-4 fw-bold shadow"><i class="bi bi-check-circle-fill me-1"></i> SAHKAN</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalReject" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered border-0">
            <div class="modal-content shadow-lg border-0 rounded-4">
                <form action="{{ route('verifikasi-sp2d.honor.reject', $sp2d->id) }}" method="POST">
                    @csrf
                    <div class="modal-header bg-danger text-white border-0 py-3 rounded-top-4">
                        <h6 class="modal-title fw-bold"><i class="bi bi-x-square me-2"></i> Pemblokiran SP2D {{ $roleCode }}</h6>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4 bg-light">
                        <div class="alert alert-danger bg-danger text-white border-0 p-3 mb-3 d-flex align-items-center gap-2">
                            <i class="bi bi-exclamation-octagon fs-4"></i>
                            <div class="small fw-semibold">Peringatan Keras: Penolakan ini memenggal alur paralel dan menyentak dokumen paksa ke meja 'Draf Revisi' Bendahara Pengeluaran.</div>
                        </div>
                        <div class="mb-0">
                            <label class="form-label small fw-bold">Penjelasan Cacat/Revisi Form *</label>
                            <textarea name="catatan" class="form-control" rows="3" required placeholder="Jelaskan secara eksplisit di mana letak kerusakan Nomor SP2D / Tanggal / Referensi NPI sumber sehingga harus dikembalikan..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pb-4 pe-4 bg-light">
                        <button type="button" class="btn btn-secondary px-4 fw-bold" data-bs-dismiss="modal">Tutup</button>
                        <button type="submit" class="btn btn-danger px-4 fw-bold shadow"><i class="bi bi-x-circle-fill me-1"></i> TOLAK REVISI</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
@endsection
