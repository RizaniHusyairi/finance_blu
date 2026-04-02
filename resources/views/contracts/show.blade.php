@extends('layouts.app')
@section('title', 'Detail Kontrak: ' . Str::limit($kontrak->nama_pekerjaan, 30))

@section('content')
@php
    $readyTerms = $kontrak->termin->where('status_termin', 'READY_TO_BILL')->values();
@endphp
<div class="row g-4">
    <!-- KOLOM KIRI: MAIN KONTEN -->
    <div class="col-lg-8 col-xl-9">
        
        {{-- BAGIAN 1: HEADER & STATUS VISUAL --}}
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <a href="{{ route('contracts.index') }}" class="btn btn-sm btn-outline-secondary mb-2 rounded-pill shadow-sm">
                    <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar
                </a>
                <h4 class="mb-1 fw-bold">{{ $kontrak->nama_pekerjaan }}</h4>
                <div class="text-muted small">
                    <i class="bi bi-hash me-1"></i> Nomor SPK: <strong>{{ $kontrak->nomor_spk }}</strong>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2">
                @if($kontrak->status_kontrak == 'AKTIF')
                    <span class="badge bg-primary px-3 py-2 rounded-pill shadow-sm fs-6"><i class="bi bi-play-circle me-1"></i> AKTIF</span>
                @elseif($kontrak->status_kontrak == 'SELESAI')
                    <span class="badge bg-success px-3 py-2 rounded-pill shadow-sm fs-6"><i class="bi bi-check-circle me-1"></i> SELESAI</span>
                @elseif($kontrak->status_kontrak == 'DRAFT' || $kontrak->status_kontrak == 'REVISI')
                    <span class="badge bg-secondary px-3 py-2 rounded-pill shadow-sm fs-6"><i class="bi bi-pencil me-1"></i> {{ $kontrak->status_kontrak }}</span>
                @else
                    <span class="badge bg-warning text-dark px-3 py-2 rounded-pill shadow-sm fs-6">{{ str_replace('_', ' ', $kontrak->status_kontrak) }}</span>
                @endif

                {{-- Aksi Pejabat Pengadaan --}}
                @if(Auth::user()->hasRole('Pejabat Pengadaan') && in_array($kontrak->status_kontrak, ['DRAFT', 'REVISI']))
                    <form action="{{ route('contracts.submit', $kontrak->id) }}" method="POST" class="m-0" onsubmit="return confirm('Ajukan kontrak ini ke PPK?')">
                        @csrf
                        <button type="submit" class="btn btn-warning btn-sm shadow-sm fw-bold">
                            <i class="bi bi-send-check me-1"></i> Ajukan ke PPK
                        </button>
                    </form>
                @endif
                
                {{-- Aksi PPK --}}
                @if(Auth::user()->hasRole('PPK') && $kontrak->status_kontrak === 'PENDING_PPK')
                    <button type="button" class="btn btn-danger btn-sm shadow-sm fw-bold" data-bs-toggle="modal" data-bs-target="#modalTolak">
                        <i class="bi bi-arrow-return-left me-1"></i> Kembalikan (Revisi)
                    </button>
                    <form action="{{ route('contracts.approve', $kontrak->id) }}" method="POST" class="m-0" onsubmit="return confirm('Apakah Anda yakin menyetujui Kontrak ini?')">
                        @csrf
                        <button type="submit" class="btn btn-primary btn-sm shadow-sm fw-bold">
                            <i class="bi bi-check-circle me-1"></i> Setujui Kontrak
                        </button>
                    </form>
                @endif

                @if($kontrak->status_kontrak === 'AKTIF')
                    <button type="button" class="btn btn-success btn-sm shadow-sm fw-bold" data-bs-toggle="modal" data-bs-target="#modalTagihKontrakDetail" {{ $readyTerms->isEmpty() ? 'disabled' : '' }}>
                        <i class="bi bi-cash-stack me-1"></i> Buat Tagihan
                    </button>
                @endif
            </div>
        </div>

        {{-- Progress Bar Keuangan --}}
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-end mb-2">
                    <div>
                        <h6 class="text-muted fw-bold mb-1">Serapan Dana (Realisasi)</h6>
                        
                        <h4 class="fw-bold text-success mb-0 d-inline-block">Rp {{ number_format($kontrak->total_terserap, 0, ',', '.') }}</h4>
                        <span class="text-muted ms-2">/ Rp {{ number_format($kontrak->nilai_total_kontrak, 0, ',', '.') }}</span>
                    </div>
                    <div class="text-end">
                        <span class="badge bg-light text-primary border border-primary fs-6">{{ number_format($kontrak->persentase_serapan, 1) }}% Tercapai</span>
                    </div>
                </div>
                <div class="progress" style="height: 12px; border-radius: 10px;">
                    <div class="progress-bar progress-bar-striped progress-bar-animated bg-success" role="progressbar" style="width: {{ $kontrak->persentase_serapan }}%" aria-valuenow="{{ $kontrak->persentase_serapan }}" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
            </div>
        </div>

        {{-- BAGIAN 2: KARTU SUMMARY --}}
        <div class="row g-3 mb-4">
            <div class="col-md-6 col-xl-3">
                <div class="card border-0 shadow-sm rounded-4 h-100 border-start border-primary border-4">
                    <div class="card-body p-3">
                        <small class="text-muted text-uppercase fw-bold"><i class="bi bi-file-earmark-text me-1"></i> Identitas Perikatan</small>
                        <div class="mt-2 text-dark font-monospace small mb-1">{{ $kontrak->nomor_spk }}</div>
                        <div class="small mb-1">Tgl: <strong>{{ \Carbon\Carbon::parse($kontrak->tanggal_spk)->format('d M Y') }}</strong></div>
                        <div class="small">DIPA: <span class="badge bg-light text-dark border">{{ $kontrak->dipa->nomor_dipa ?? 'N/A' }}</span></div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="card border-0 shadow-sm rounded-4 h-100 border-start border-info border-4">
                    <div class="card-body p-3">
                        <small class="text-muted text-uppercase fw-bold"><i class="bi bi-building me-1"></i> Vendor & Rekening</small>
                        <div class="mt-2 fw-bold text-dark text-truncate" title="{{ $kontrak->vendor->nama_perusahaan ?? '-' }}">{{ $kontrak->vendor->nama_perusahaan ?? '-' }}</div>
                        <div class="small text-muted mb-1">NPWP: {{ $kontrak->vendor->npwp ?? '-' }}</div>
                        @php
                            $rek = $kontrak->vendor->rekening->first();
                        @endphp
                        <div class="small bg-light p-1 rounded font-monospace text-primary text-truncate">
                            {{ $rek ? $rek->nama_bank . ' - ' . $rek->nomor_rekening : 'Belum Ada Rekening' }}
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="card border-0 shadow-sm rounded-4 h-100 border-start border-success border-4">
                    <div class="card-body p-3">
                        <small class="text-muted text-uppercase fw-bold"><i class="bi bi-cash-stack me-1"></i> Nilai & Skema</small>
                        <div class="mt-2 fw-bold text-success fs-5">Rp {{ number_format($kontrak->nilai_total_kontrak, 0, ',', '.') }}</div>
                        <div class="small text-muted mb-1">Metode: <strong>{{ $kontrak->metode_pembayaran }}</strong></div>
                        <div class="small text-muted">Uang Muka: 
                            @if($kontrak->ada_uang_muka)
                                <strong class="text-dark">Rp {{ number_format($kontrak->nilai_uang_muka, 0, ',', '.') }}</strong>
                            @else
                                Tidak Ada
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="card border-0 shadow-sm rounded-4 h-100 border-start border-warning border-4">
                    <div class="card-body p-3">
                        <small class="text-muted text-uppercase fw-bold"><i class="bi bi-calendar-range me-1"></i> Garis Waktu</small>
                        <div class="mt-2 fw-bold text-dark">{{ $kontrak->jangka_waktu }} {{ ucfirst(strtolower($kontrak->satuan_waktu)) }}</div>
                        <div class="small text-muted mb-1">Mulai: <strong>{{ \Carbon\Carbon::parse($kontrak->tanggal_mulai)->format('d M Y') }}</strong></div>
                        <div class="small text-danger">Selesai: <strong>{{ \Carbon\Carbon::parse($kontrak->tanggal_selesai)->format('d M Y') }}</strong></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- BAGIAN 3: TAB DETAIL --}}
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white p-0 border-bottom rounded-top-4">
                <ul class="nav nav-tabs px-3" id="detailTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-bold py-3 border-0 border-bottom border-3 text-secondary" id="dokumen-tab" data-bs-toggle="tab" data-bs-target="#dokumen" type="button" role="tab" aria-controls="dokumen" aria-selected="false">
                            <i class="bi bi-folder2-open me-2"></i> Dokumen Awal & Jaminan
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active fw-bold py-3 border-0 border-bottom border-3 border-primary text-primary" id="termin-tab" data-bs-toggle="tab" data-bs-target="#termin" type="button" role="tab" aria-controls="termin" aria-selected="true">
                            <i class="bi bi-list-check me-2"></i> Skema Termin & Tagihan
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-bold py-3 border-0 border-bottom border-3 text-secondary" id="addendum-tab" data-bs-toggle="tab" data-bs-target="#addendum" type="button" role="tab" aria-controls="addendum" aria-selected="false">
                            <i class="bi bi-journal-text me-2"></i> Riwayat Addendum
                        </button>
                    </li>
                </ul>
            </div>
            <div class="card-body p-4">
                <div class="tab-content">
                    
                    {{-- TAB DOKUMEN AWAL --}}
                    <div class="tab-pane fade" id="dokumen" role="tabpanel" aria-labelledby="dokumen-tab">
                        <h6 class="fw-bold mb-3">Arsip Berkas Perikatan (SPK)</h6>
                        <div class="list-group mb-4">
                            @if($kontrak->file_spk)
                            <a href="{{ Storage::url($kontrak->file_spk) }}" target="_blank" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                <div><i class="bi bi-file-pdf text-danger me-2"></i> Surat Perintah Kerja (SPK)</div>
                                <span class="badge bg-light text-primary border"><i class="bi bi-download"></i> Unduh</span>
                            </a>
                            @endif
                            
                            @if($kontrak->file_spmk)
                            <a href="{{ Storage::url($kontrak->file_spmk) }}" target="_blank" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                <div><i class="bi bi-file-pdf text-danger me-2"></i> Surat Perintah Mulai Kerja (SPMK)</div>
                                <span class="badge bg-light text-primary border"><i class="bi bi-download"></i> Unduh</span>
                            </a>
                            @endif

                            @if($kontrak->file_ringkasan_kontrak)
                            <a href="{{ Storage::url($kontrak->file_ringkasan_kontrak) }}" target="_blank" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                <div><i class="bi bi-file-pdf text-danger me-2"></i> Ringkasan Kontrak</div>
                                <span class="badge bg-light text-primary border"><i class="bi bi-download"></i> Unduh</span>
                            </a>
                            @endif

                            @if(!$kontrak->file_spk && !$kontrak->file_spmk && !$kontrak->file_ringkasan_kontrak)
                            <div class="text-center p-3 text-muted bg-light rounded">
                                Belum ada dokumen awal yang diunggah.
                            </div>
                            @endif
                        </div>

                        {{-- Disini bisa ditambahkan tabel jaminan_kontrak jika relasinya ada --}}
                    </div>

                    {{-- TAB TERMIN & TAGIHAN --}}
                    <div class="tab-pane fade show active" id="termin" role="tabpanel" aria-labelledby="termin-tab">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th class="text-center" width="10%">Termin</th>
                                        <th width="30%">Keterangan</th>
                                        <th width="15%" class="text-center">Persentase</th>
                                        <th width="20%">Nilai Bruto</th>
                                        <th width="15%" class="text-center">Status</th>
                                        <th width="10%" class="text-center">Aksi Tagihan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($kontrak->termin as $termin)
                                    <tr>
                                        <td class="text-center fw-bold">{{ $termin->termin_ke }}</td>
                                        <td>{{ $termin->keterangan_termin }}<br><small class="text-muted">{{ str_replace('_', ' ', $termin->jenis_termin) }}</small></td>
                                        <td class="text-center"><span class="badge bg-secondary">{{ $termin->persentase }}%</span></td>
                                        <td class="fw-bold">Rp {{ number_format($termin->nilai_bruto_termin, 0, ',', '.') }}</td>
                                        <td class="text-center">
                                            @if($termin->status_termin == 'LOCKED')
                                                <span class="badge bg-light text-dark border"><i class="bi bi-lock-fill"></i> LOCKED</span>
                                            @elseif($termin->status_termin == 'READY_TO_BILL')
                                                <span class="badge bg-warning text-dark"><i class="bi bi-bell-fill"></i> READY</span>
                                            @elseif($termin->status_termin == 'SUDAH_DITAGIH')
                                                <span class="badge bg-success"><i class="bi bi-check-circle-fill"></i> DITAGIH</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if($kontrak->status_kontrak === 'AKTIF' && $termin->status_termin === 'READY_TO_BILL')
                                                <button type="button" class="btn btn-sm btn-primary shadow-sm" title="Buat Tagihan" data-bs-toggle="modal" data-bs-target="#modalTagihTermin{{ $termin->id }}">
                                                    <i class="bi bi-cash"></i> Buat Tagihan
                                                </button>
                                            @elseif($kontrak->status_kontrak === 'AKTIF' && $termin->status_termin === 'LOCKED')
                                                <button disabled class="btn btn-sm btn-outline-secondary" title="Termin Masih Terkunci">
                                                    <i class="bi bi-lock-fill"></i> Terkunci
                                                </button>
                                            @elseif($termin->status_termin === 'SUDAH_DITAGIH')
                                                <button class="btn btn-sm btn-light text-success border border-success" title="Lihat SP2D / Tagihan" disabled>
                                                    <i class="bi bi-search"></i> Lihat
                                                </button>
                                            @endif
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted">Belum ada skema termin dibuat.</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- TAB ADDENDUM --}}
                    <div class="tab-pane fade" id="addendum" role="tabpanel" aria-labelledby="addendum-tab">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>No. Addendum</th>
                                        <th>Tanggal</th>
                                        <th>Jenis Perubahan</th>
                                        <th>Keterangan</th>
                                        <th class="text-center">Dokumen</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($kontrak->addendums as $addm)
                                    <tr>
                                        <td class="fw-bold">{{ $addm->nomor_addendum }}</td>
                                        <td>{{ \Carbon\Carbon::parse($addm->tanggal_addendum)->format('d M Y') }}</td>
                                        <td><span class="badge bg-secondary">{{ str_replace('_', ' ', $addm->jenis_addendum) }}</span></td>
                                        <td><small>{{ Str::limit($addm->keterangan_alasan, 50) }}</small></td>
                                        <td class="text-center">
                                            @if($addm->file_addendum)
                                                <a href="{{ Storage::url($addm->file_addendum) }}" target="_blank" class="btn btn-sm btn-light text-primary border"><i class="bi bi-download"></i></a>
                                            @else
                                                -
                                            @endif
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted">Belum ada riwayat addendum pada kontrak ini.</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
            </div>
        </div>

    </div>

    <!-- KOLOM KANAN: AUDIT TRAIL / RIWAYAT AKTIVITAS -->
    <div class="col-lg-4 col-xl-3">
        <div class="card border-0 shadow-sm rounded-4 h-100 position-sticky" style="top: 20px;">
            <div class="card-header bg-white p-4 border-bottom rounded-top-4">
                <h6 class="mb-0 fw-bold"><i class="bi bi-clock-history me-2 text-primary"></i> Riwayat Aktivitas Proyek</h6>
            </div>
            <div class="card-body p-4" style="max-height: 80vh; overflow-y: auto;">
                
                <div class="activity-timeline border-start ms-3 ps-4 position-relative border-primary border-2 opacity-75">
                    @foreach($semuaAktivitas as $idx => $akt)
                        <div class="timeline-item mb-4 position-relative">
                            {{-- Titik Ikon --}}
                            <div class="timeline-icon position-absolute rounded-circle d-flex align-items-center justify-content-center bg-white border border-2 border-primary" 
                                 style="width: 32px; height: 32px; left: -42px; top: 0; z-index: 2;">
                                <i class="bi {{ $akt['ikon'] ?? 'bi-record-circle' }} text-primary fs-6"></i>
                            </div>
                            
                            {{-- Konten Riwayat --}}
                            <div>
                                <small class="text-muted fw-bold d-block mb-1">
                                    {{ \Carbon\Carbon::parse($akt['tanggal'])->diffForHumans() }} 
                                    <span class="fw-normal">({{ \Carbon\Carbon::parse($akt['tanggal'])->format('d M H:i') }})</span>
                                </small>
                                <div class="fw-bold text-dark mb-1">{{ $akt['judul'] }}</div>
                                <div class="small text-muted mb-2"><i class="bi bi-person me-1"></i> Oleh: {{ $akt['aktor'] }}</div>
                                @if(isset($akt['catatan']) && $akt['catatan'] !== '-')
                                    <div class="p-2 bg-light rounded text-muted small fst-italic border border-light-subtle">
                                        "{{ $akt['catatan'] }}"
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach

                    {{-- Titik Akhir (Start) --}}
                    <div class="timeline-item position-relative mt-2">
                        <div class="timeline-icon position-absolute rounded-circle bg-secondary border border-2 border-white" 
                                style="width: 14px; height: 14px; left: -33px; top: 5px;">
                        </div>
                        <small class="text-muted">Awal Inisiasi</small>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
</div>

{{-- MODAL TOLAK KONTRAK UNTUK PPK --}}
@if(Auth::user()->hasRole('PPK') && $kontrak->status_kontrak === 'PENDING_PPK')
<div class="modal fade" id="modalTolak" tabindex="-1" aria-labelledby="modalTolakLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="{{ route('contracts.reject', $kontrak->id) }}" class="modal-content border-0 rounded-4 shadow">
            @csrf
            <div class="modal-header bg-danger text-white border-bottom-0">
                <h5 class="modal-title fw-bold" id="modalTolakLabel"><i class="bi bi-exclamation-triangle me-2"></i> Kembalikan Kontrak (Revisi)</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <p class="mb-3 text-muted">Silakan sebutkan alasan mengapa draf kontrak ini dikembalikan ke Pejabat Pengadaan untuk direvisi.</p>
                <div class="mb-3">
                    <label class="form-label fw-bold small text-danger">Catatan Revisi / Penolakan <span class="text-danger">*</span></label>
                    <textarea name="notes" class="form-control" rows="4" placeholder="Contoh: Lampiran jaminan pelaksanaan nilai tidak sesuai..." required></textarea>
                </div>
            </div>
            <div class="modal-footer bg-light border-top-0">
                <button type="button" class="btn btn-outline-secondary fw-bold" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-danger fw-bold"><i class="bi bi-arrow-return-left me-1"></i> Simpan & Kirim Kembali</button>
            </div>
        </form>
    </div>
</div>
@endif

@if($kontrak->status_kontrak === 'AKTIF')
<div class="modal fade" id="modalTagihKontrakDetail" tabindex="-1" aria-labelledby="modalTagihKontrakDetailLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow">
            <div class="modal-header bg-success text-white border-0">
                <div>
                    <h5 class="modal-title fw-bold" id="modalTagihKontrakDetailLabel">Pilih Termin / Lumpsum untuk Ditagih</h5>
                    <div class="small opacity-75">{{ $kontrak->nomor_spk }} - {{ Str::limit($kontrak->nama_pekerjaan, 80) }}</div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                @if($readyTerms->isEmpty())
                    <div class="alert alert-light border mb-0">Belum ada termin atau lumpsum yang siap ditagih untuk kontrak ini.</div>
                @else
                    <div class="list-group">
                        @foreach($readyTerms as $termin)
                            <div class="list-group-item list-group-item-action d-flex justify-content-between align-items-center gap-3">
                                <div>
                                    <div class="fw-bold">Termin {{ $termin->termin_ke }} - {{ str_replace('_', ' ', $termin->jenis_termin) }}</div>
                                    <div class="small text-muted">{{ $termin->keterangan_termin }}</div>
                                    <div class="small mt-1">
                                        <span class="badge bg-light text-dark border">{{ $termin->persentase }}%</span>
                                        <span class="ms-2 fw-semibold text-success">Rp {{ number_format($termin->nilai_bruto_termin, 0, ',', '.') }}</span>
                                    </div>
                                </div>
                                <a href="{{ route('tagihan.kontrak.create', ['kontrak_id' => $kontrak->id, 'termin_id' => $termin->id]) }}" class="btn btn-primary btn-sm fw-bold">
                                    <i class="bi bi-send-plus me-1"></i> Tagih
                                </a>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@foreach($readyTerms as $termin)
<div class="modal fade" id="modalTagihTermin{{ $termin->id }}" tabindex="-1" aria-labelledby="modalTagihTerminLabel{{ $termin->id }}" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow">
            <div class="modal-header bg-success text-white border-0">
                <h5 class="modal-title fw-bold" id="modalTagihTerminLabel{{ $termin->id }}">Konfirmasi Buat Tagihan</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="fw-bold mb-1">Termin {{ $termin->termin_ke }} - {{ str_replace('_', ' ', $termin->jenis_termin) }}</div>
                <div class="text-muted small mb-2">{{ $termin->keterangan_termin }}</div>
                <div class="small">Nilai bruto: <span class="fw-bold text-success">Rp {{ number_format($termin->nilai_bruto_termin, 0, ',', '.') }}</span></div>
            </div>
            <div class="modal-footer bg-light border-0">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <a href="{{ route('tagihan.kontrak.create', ['kontrak_id' => $kontrak->id, 'termin_id' => $termin->id]) }}" class="btn btn-primary fw-bold">
                    <i class="bi bi-send-plus me-1"></i> Lanjut Buat Tagihan
                </a>
            </div>
        </div>
    </div>
</div>
@endforeach
@endif

@endsection

@push('css')
<style>
    .activity-timeline { border-left-color: #0d6efd !important; }
    .timeline-item { padding-bottom: 1.5rem; }
    .timeline-item:last-child { padding-bottom: 0; }
    .timeline-icon { box-shadow: 0 0 0 4px #fff; }
</style>
@endpush
