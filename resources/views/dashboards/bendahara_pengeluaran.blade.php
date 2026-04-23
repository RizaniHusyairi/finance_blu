@extends('layouts.app')
@section('title', 'Dashboard Bendahara Pengeluaran')
@section('content')

<!-- ROW 1: Header Dashboard -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
    <div>
        <h3 class="mb-1 text-uppercase fw-bold text-dark">Dashboard Bendahara Pengeluaran</h3>
        <p class="text-muted mb-0">Kelola verifikasi tagihan, penerbitan NPI, SP2D, perpajakan, dan pembukuan.</p>
        <div class="small text-muted mt-1">
            <i class="bi bi-calendar3 me-1"></i> {{ $now->isoFormat('dddd, D MMMM YYYY') }} | 
            <i class="bi bi-person me-1"></i> {{ $user->name }} (Bendahara Pengeluaran)
        </div>
    </div>
    <div class="mt-3 mt-md-0 d-flex gap-2 flex-wrap">
        <a href="{{ route('verifikasi-bendahara.perjaldin.index') }}" class="btn btn-sm btn-outline-primary fw-semibold"><i class="bi bi-airplane me-1"></i> Tagihan Perjaldin</a>
        <a href="{{ route('verifikasi-bendahara.honorarium.index') }}" class="btn btn-sm btn-outline-primary fw-semibold"><i class="bi bi-people me-1"></i> Tagihan Honor</a>
        <a href="{{ route('npis.perjaldin.index') }}" class="btn btn-sm btn-outline-primary fw-semibold"><i class="bi bi-file-earmark-text me-1"></i> NPI Perjaldin</a>
        <a href="{{ route('sp2ds.perjaldin.index') }}" class="btn btn-sm btn-outline-primary fw-semibold"><i class="bi bi-journal-text me-1"></i> SP2D</a>
        <a href="{{ route('pembukuan.bku.index') }}" class="btn btn-sm btn-primary fw-semibold"><i class="bi bi-book me-1"></i> BKU</a>
    </div>
</div>

<!-- ROW 2: Summary Cards Utama (8 Cards) -->
<div class="row g-3 mb-4">
    <!-- Verifikasi Perjaldin -->
    <div class="col-xl-3 col-md-4 col-sm-6">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body">
                <h6 class="text-muted mb-2 small fw-bold text-uppercase">Tagihan Perjaldin</h6>
                <div class="d-flex justify-content-between align-items-center">
                    <h3 class="mb-0 fw-bold {{ $tagihanPerjaldin->count() > 0 ? 'text-warning' : 'text-success' }}">{{ $tagihanPerjaldin->count() }}</h3>
                    <div class="bg-primary bg-opacity-10 rounded-circle p-2">
                        <i class="bi bi-airplane text-primary fs-5"></i>
                    </div>
                </div>
                <div class="mt-2 small text-muted">
                    <span class="badge bg-danger rounded-pill">{{ $tagihanPerjaldin->where('status', 'REVISI_BENDAHARA')->count() }} Revisi</span>
                </div>
                <a href="{{ route('verifikasi-bendahara.perjaldin.index') }}" class="stretched-link"></a>
            </div>
        </div>
    </div>
    
    <!-- Verifikasi Honorarium -->
    <div class="col-xl-3 col-md-4 col-sm-6">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body">
                <h6 class="text-muted mb-2 small fw-bold text-uppercase">Tagihan Honorarium</h6>
                <div class="d-flex justify-content-between align-items-center">
                    <h3 class="mb-0 fw-bold {{ $tagihanHonorarium->count() > 0 ? 'text-warning' : 'text-success' }}">{{ $tagihanHonorarium->count() }}</h3>
                    <div class="bg-primary bg-opacity-10 rounded-circle p-2">
                        <i class="bi bi-people text-primary fs-5"></i>
                    </div>
                </div>
                <div class="mt-2 small text-muted">
                    <span class="badge bg-danger rounded-pill">{{ $tagihanHonorarium->where('status', 'REVISI_BENDAHARA')->count() }} Revisi</span>
                </div>
                <a href="{{ route('verifikasi-bendahara.honorarium.index') }}" class="stretched-link"></a>
            </div>
        </div>
    </div>

    <!-- NPI Siap Dibuat -->
    <div class="col-xl-3 col-md-4 col-sm-6">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body">
                <h6 class="text-muted mb-2 small fw-bold text-uppercase">NPI Siap Dibuat</h6>
                <div class="d-flex justify-content-between align-items-center">
                    <h3 class="mb-0 fw-bold {{ $totalNpiSiap > 0 ? 'text-info' : 'text-success' }}">{{ $totalNpiSiap }}</h3>
                    <div class="bg-info bg-opacity-10 rounded-circle p-2">
                        <i class="bi bi-file-earmark-plus text-info fs-5"></i>
                    </div>
                </div>
                <div class="mt-2 small text-muted">
                    SPM Final belum diproses
                </div>
            </div>
        </div>
    </div>

    <!-- SP2D Siap Dicatat -->
    <div class="col-xl-3 col-md-4 col-sm-6">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body">
                <h6 class="text-muted mb-2 small fw-bold text-uppercase">SP2D Siap Dicatat</h6>
                <div class="d-flex justify-content-between align-items-center">
                    <h3 class="mb-0 fw-bold {{ $totalSp2dSiap > 0 ? 'text-primary' : 'text-success' }}">{{ $totalSp2dSiap }}</h3>
                    <div class="bg-primary bg-opacity-10 rounded-circle p-2">
                        <i class="bi bi-journal-check text-primary fs-5"></i>
                    </div>
                </div>
                <div class="mt-2 small text-muted">
                    NPI Final belum diproses
                </div>
            </div>
        </div>
    </div>

    <!-- Pajak Belum Billing -->
    <div class="col-xl-3 col-md-4 col-sm-6">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body">
                <h6 class="text-muted mb-2 small fw-bold text-uppercase">Pajak Siap Billing</h6>
                <div class="d-flex justify-content-between align-items-center">
                    <h3 class="mb-0 fw-bold {{ $pajakBelumBilling->count() > 0 ? 'text-danger' : 'text-success' }}">{{ $pajakBelumBilling->count() }}</h3>
                    <div class="bg-danger bg-opacity-10 rounded-circle p-2">
                        <i class="bi bi-receipt text-danger fs-5"></i>
                    </div>
                </div>
                <div class="mt-2 small text-muted">
                    Belum buat ID Billing
                </div>
            </div>
        </div>
    </div>

    <!-- Pajak Siap NTPN -->
    <div class="col-xl-3 col-md-4 col-sm-6">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body">
                <h6 class="text-muted mb-2 small fw-bold text-uppercase">Pajak Siap Input NTPN</h6>
                <div class="d-flex justify-content-between align-items-center">
                    <h3 class="mb-0 fw-bold {{ $pajakSudahBilling->count() > 0 ? 'text-warning' : 'text-success' }}">{{ $pajakSudahBilling->count() }}</h3>
                    <div class="bg-warning bg-opacity-10 rounded-circle p-2">
                        <i class="bi bi-bank2 text-warning fs-5"></i>
                    </div>
                </div>
                <div class="mt-2 small text-muted">
                    Menunggu NTPN
                </div>
            </div>
        </div>
    </div>

    <!-- BKU Total Pengeluaran -->
    <div class="col-xl-3 col-md-4 col-sm-6">
        <div class="card border-0 shadow-sm rounded-4 h-100 bg-light">
            <div class="card-body">
                <h6 class="text-muted mb-2 small fw-bold text-uppercase">Pengeluaran Bulan Ini</h6>
                <div class="d-flex justify-content-between align-items-center">
                    <h4 class="mb-0 fw-bold text-dark text-truncate" title="Rp {{ number_format($totalPengeluaranBulanIni, 0, ',', '.') }}">Rp {{ number_format($totalPengeluaranBulanIni, 0, ',', '.') }}</h4>
                </div>
                <div class="mt-2 small text-muted">
                    Dari Buku Kas Umum
                </div>
                <a href="{{ route('pembukuan.bku.index') }}" class="stretched-link"></a>
            </div>
        </div>
    </div>

    <!-- Aktivitas Hari Ini -->
    <div class="col-xl-3 col-md-4 col-sm-6">
        <div class="card border-0 shadow-sm rounded-4 h-100 bg-primary text-white">
            <div class="card-body">
                <h6 class="text-white-50 mb-2 small fw-bold text-uppercase">Aktivitas Anda</h6>
                <div class="d-flex justify-content-between align-items-center">
                    <h3 class="mb-0 fw-bold text-white">{{ $aktivitasTerbaru->where('created_at', '>=', $now->startOfDay())->count() }}</h3>
                    <div class="bg-white bg-opacity-25 rounded-circle p-2">
                        <i class="bi bi-activity text-white fs-5"></i>
                    </div>
                </div>
                <div class="mt-2 small text-white-50">
                    Aksi hari ini
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ROW 3: Antrean Verifikasi & Prioritas -->
<div class="row g-4 mb-4">
    <!-- Antrean Verifikasi -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
                <h5 class="fw-bold mb-3"><i class="bi bi-list-check me-2 text-primary"></i>Antrean Verifikasi Saya</h5>
                <ul class="nav nav-tabs border-bottom" id="antreanTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active fw-semibold" data-bs-toggle="tab" data-bs-target="#tab-perjaldin" type="button">
                            Tagihan Perjaldin <span class="badge bg-danger rounded-pill ms-1">{{ $tagihanPerjaldin->count() }}</span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-semibold" data-bs-toggle="tab" data-bs-target="#tab-honorarium" type="button">
                            Tagihan Honorarium <span class="badge bg-danger rounded-pill ms-1">{{ $tagihanHonorarium->count() }}</span>
                        </button>
                    </li>
                </ul>
            </div>
            <div class="card-body p-0">
                <div class="tab-content" id="antreanTabsContent">
                    <!-- Tab Perjaldin -->
                    <div class="tab-pane fade show active" id="tab-perjaldin">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light text-muted small text-uppercase">
                                    <tr>
                                        <th class="ps-4">No Tagihan</th>
                                        <th>Uraian</th>
                                        <th>Nominal</th>
                                        <th>Status / Usia</th>
                                        <th class="pe-4 text-end">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($antreanList->where('tab', 'perjaldin')->take(5) as $item)
                                    <tr>
                                        <td class="ps-4 fw-bold text-primary">{{ $item->nomor }}</td>
                                        <td>
                                            <div class="text-truncate" style="max-width:200px;" title="{{ $item->uraian }}">{{ $item->uraian }}</div>
                                        </td>
                                        <td class="fw-semibold">Rp {{ number_format($item->nominal, 0, ',', '.') }}</td>
                                        <td>
                                            @if($item->is_revisi)
                                                <span class="badge bg-danger">Revisi</span>
                                            @else
                                                <span class="badge bg-warning text-dark">Pending</span>
                                            @endif
                                            <div class="small text-muted mt-1"><i class="bi bi-clock"></i> {{ $item->usia }} hari</div>
                                        </td>
                                        <td class="pe-4 text-end">
                                            <a href="{{ $item->url }}" class="btn btn-sm btn-primary rounded-pill px-3">Proses</a>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr><td colspan="5" class="text-center py-4 text-muted">Tidak ada antrean Perjaldin.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Tab Honorarium -->
                    <div class="tab-pane fade" id="tab-honorarium">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light text-muted small text-uppercase">
                                    <tr>
                                        <th class="ps-4">No Tagihan</th>
                                        <th>Uraian</th>
                                        <th>Nominal</th>
                                        <th>Status / Usia</th>
                                        <th class="pe-4 text-end">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($antreanList->where('tab', 'honorarium')->take(5) as $item)
                                    <tr>
                                        <td class="ps-4 fw-bold text-primary">{{ $item->nomor }}</td>
                                        <td><div class="text-truncate" style="max-width:200px;">{{ $item->uraian }}</div></td>
                                        <td class="fw-semibold">Rp {{ number_format($item->nominal, 0, ',', '.') }}</td>
                                        <td>
                                            @if($item->is_revisi)
                                                <span class="badge bg-danger">Revisi</span>
                                            @else
                                                <span class="badge bg-warning text-dark">Pending</span>
                                            @endif
                                            <div class="small text-muted mt-1"><i class="bi bi-clock"></i> {{ $item->usia }} hari</div>
                                        </td>
                                        <td class="pe-4 text-end">
                                            <a href="{{ $item->url }}" class="btn btn-sm btn-primary rounded-pill px-3">Proses</a>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr><td colspan="5" class="text-center py-4 text-muted">Tidak ada antrean Honorarium.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-white text-center py-3 border-top-0">
                <small class="text-muted"><i class="bi bi-info-circle me-1"></i>Menampilkan 5 data teratas per kategori.</small>
            </div>
        </div>
    </div>

    <!-- Prioritas Hari Ini -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-4 h-100 border-top border-warning border-3">
            <div class="card-header bg-white border-bottom-0 pt-4">
                <h5 class="fw-bold mb-0"><i class="bi bi-exclamation-triangle me-2 text-warning"></i>Prioritas Hari Ini</h5>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    @forelse($prioritasHariIni as $p)
                    <li class="list-group-item px-0 border-light py-3">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <span class="badge {{ $p->is_revisi ? 'bg-danger' : 'bg-warning text-dark' }} rounded-pill mb-1">
                                    {{ $p->is_revisi ? 'Revisi Belum Ditindaklanjuti' : 'Pending Lama' }}
                                </span>
                                <h6 class="mb-1 fw-bold">{{ $p->jenis }}</h6>
                                <div class="small text-primary fw-semibold">{{ $p->nomor }}</div>
                                <div class="small text-muted mt-1">Nominal: <span class="fw-bold text-dark">Rp {{ number_format($p->nominal, 0, ',', '.') }}</span></div>
                            </div>
                            <div class="text-end">
                                <div class="small text-danger fw-bold mb-2">{{ $p->usia }} Hari</div>
                                <a href="{{ $p->url }}" class="btn btn-sm btn-outline-primary rounded-pill">Buka</a>
                            </div>
                        </div>
                    </li>
                    @empty
                    <li class="list-group-item px-0 border-0 text-center py-4 text-muted">
                        <i class="bi bi-check-circle text-success fs-1 mb-2 d-block"></i>
                        Tidak ada verifikasi prioritas mendesak saat ini.
                    </li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- ROW 4: NPI & SP2D -->
<div class="row g-4 mb-4">
    <!-- Pembuatan NPI -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-header bg-white border-bottom-0 pt-4 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0"><i class="bi bi-file-earmark-plus me-2 text-info"></i>Pembuatan NPI</h5>
                <span class="badge bg-info text-dark rounded-pill">{{ $totalNpiSiap }} Siap Dibuat</span>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    @if($spmKontrakSiapNpi->count() > 0)
                    <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                        <div>
                            <div class="fw-bold">NPI Kontrak</div>
                            <div class="small text-muted">{{ $spmKontrakSiapNpi->count() }} SPM Kontrak Final menunggu NPI</div>
                        </div>
                        <a href="#" class="btn btn-sm btn-outline-info rounded-pill px-3">Buat NPI</a>
                    </li>
                    @endif
                    
                    @if($spmPerjaldinSiapNpi->count() > 0)
                    <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                        <div>
                            <div class="fw-bold">NPI Perjaldin</div>
                            <div class="small text-muted">{{ $spmPerjaldinSiapNpi->count() }} SPM Perjaldin Final menunggu NPI</div>
                        </div>
                        <a href="{{ route('npis.perjaldin.index') }}" class="btn btn-sm btn-outline-info rounded-pill px-3">Buat NPI</a>
                    </li>
                    @endif
                    
                    @if($spmHonorSiapNpi->count() > 0)
                    <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                        <div>
                            <div class="fw-bold">NPI Honor</div>
                            <div class="small text-muted">{{ $spmHonorSiapNpi->count() }} SPM Honor Final menunggu NPI</div>
                        </div>
                        <a href="#" class="btn btn-sm btn-outline-info rounded-pill px-3">Buat NPI</a>
                    </li>
                    @endif
                    
                    @if($totalNpiSiap == 0)
                    <li class="list-group-item border-0 text-center py-4 text-muted">
                        Tidak ada NPI yang perlu dibuat saat ini.
                    </li>
                    @endif
                </ul>
            </div>
        </div>
    </div>
    
    <!-- Pencatatan SP2D -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-header bg-white border-bottom-0 pt-4 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0"><i class="bi bi-journal-check me-2 text-primary"></i>Pencatatan SP2D</h5>
                <span class="badge bg-primary rounded-pill">{{ $totalSp2dSiap }} Siap Dicatat</span>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    @if($npiKontrakSiapSp2d->count() > 0)
                    <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                        <div>
                            <div class="fw-bold">SP2D Kontrak</div>
                            <div class="small text-muted">{{ $npiKontrakSiapSp2d->count() }} NPI Kontrak Final menunggu SP2D</div>
                        </div>
                        <a href="#" class="btn btn-sm btn-outline-primary rounded-pill px-3">Catat SP2D</a>
                    </li>
                    @endif
                    
                    @if($npiPerjaldinSiapSp2d->count() > 0)
                    <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                        <div>
                            <div class="fw-bold">SP2D Perjaldin</div>
                            <div class="small text-muted">{{ $npiPerjaldinSiapSp2d->count() }} NPI Perjaldin Final menunggu SP2D</div>
                        </div>
                        <a href="{{ route('sp2ds.perjaldin.index') }}" class="btn btn-sm btn-outline-primary rounded-pill px-3">Catat SP2D</a>
                    </li>
                    @endif
                    
                    @if($npiHonorSiapSp2d->count() > 0)
                    <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                        <div>
                            <div class="fw-bold">SP2D Honor</div>
                            <div class="small text-muted">{{ $npiHonorSiapSp2d->count() }} NPI Honor Final menunggu SP2D</div>
                        </div>
                        <a href="#" class="btn btn-sm btn-outline-primary rounded-pill px-3">Catat SP2D</a>
                    </li>
                    @endif
                    
                    @if($totalSp2dSiap == 0)
                    <li class="list-group-item border-0 text-center py-4 text-muted">
                        Tidak ada SP2D yang perlu dicatat saat ini.
                    </li>
                    @endif
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- ROW 5: Perpajakan -->
<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white border-bottom-0 pt-4 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0"><i class="bi bi-receipt me-2 text-danger"></i>Penyetoran Pajak & Buku Pembantu Pajak</h5>
                <a href="#" class="btn btn-sm btn-link text-danger">Kelola Pajak Kontrak</a>
            </div>
            <div class="card-body p-0">
                <div class="row g-0">
                    <div class="col-md-4 border-end">
                        <div class="p-4 bg-light h-100">
                            <h6 class="fw-bold mb-3 text-uppercase text-muted small">Ringkasan Pajak</h6>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Belum Billing</span>
                                <span class="badge bg-danger rounded-pill">{{ $pajakBelumBilling->count() }}</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Menunggu NTPN</span>
                                <span class="badge bg-warning text-dark rounded-pill">{{ $pajakSudahBilling->count() }}</span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>Sudah Setor (NTPN)</span>
                                <span class="badge bg-success rounded-pill">{{ $pajakSudahSetor->count() }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="table-responsive p-3">
                            <h6 class="fw-bold mb-3">Pajak Perlu Tindakan:</h6>
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light text-muted small">
                                    <tr>
                                        <th>Jenis Pajak</th>
                                        <th>No Tagihan</th>
                                        <th>Nominal</th>
                                        <th>Status</th>
                                        <th class="text-end">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($potonganPajak->whereNull('ntpn')->take(4) as $pajak)
                                    <tr>
                                        <td class="fw-semibold">{{ $pajak->pajak?->nama_pajak ?? $pajak->nama_pajak_snapshot }}</td>
                                        <td class="small">{{ $pajak->tagihan?->nomor_tagihan ?? '-' }}</td>
                                        <td class="fw-bold">Rp {{ number_format($pajak->nominal_potongan, 0, ',', '.') }}</td>
                                        <td>
                                            @if(!$pajak->kode_billing)
                                                <span class="badge bg-danger">Buat Billing</span>
                                            @else
                                                <span class="badge bg-warning text-dark">Input NTPN</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            <a href="#" class="btn btn-sm btn-outline-danger rounded-pill px-3">Proses</a>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr><td colspan="5" class="text-center py-3 text-muted">Semua tagihan pajak telah disetor.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ROW 6: Ringkasan Pembukuan & BKU & Rekon -->
<div class="row g-4 mb-4">
    <!-- Ringkasan Pembukuan -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-header bg-white border-bottom-0 pt-4">
                <h5 class="fw-bold mb-0"><i class="bi bi-wallet2 me-2 text-primary"></i>Ringkasan Pembukuan</h5>
            </div>
            <div class="card-body">
                <div class="p-3 bg-light rounded-4 border border-light mb-3">
                    <div class="text-muted small fw-bold text-uppercase mb-1">Saldo BKU Terakhir</div>
                    <h4 class="fw-bold mb-0 text-dark">Rp {{ number_format($saldoTerakhirBku, 0, ',', '.') }}</h4>
                </div>
                <div class="p-3 bg-light rounded-4 border border-light mb-3">
                    <div class="text-muted small fw-bold text-uppercase mb-1">Total Pengeluaran (Bulan Ini)</div>
                    <h4 class="fw-bold mb-0 text-danger">Rp {{ number_format($totalPengeluaranBulanIni, 0, ',', '.') }}</h4>
                </div>
                <div class="row g-3">
                    <div class="col-6">
                        <div class="p-3 bg-light rounded-4 border border-light text-center h-100">
                            <h4 class="fw-bold mb-1 {{ $mutasiBelumRekon > 0 ? 'text-danger' : 'text-success' }}">{{ $mutasiBelumRekon }}</h4>
                            <div class="small text-muted lh-sm">Mutasi Belum Rekon</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-3 bg-light rounded-4 border border-light text-center h-100">
                            <h4 class="fw-bold mb-1 text-success">{{ $pajakSudahSetor->count() }}</h4>
                            <div class="small text-muted lh-sm">Pajak Disetor</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Buku Kas Umum Terbaru -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-header bg-white border-bottom-0 pt-4 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0"><i class="bi bi-journal-text me-2 text-primary"></i>Buku Kas Umum Terbaru</h5>
                <a href="{{ route('pembukuan.bku.index') }}" class="btn btn-sm btn-link">Buka BKU Lengkap</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light text-muted small text-uppercase">
                            <tr>
                                <th class="ps-4">Tanggal</th>
                                <th>No Bukti</th>
                                <th>Uraian</th>
                                <th>Nominal</th>
                                <th class="pe-4 text-end">Saldo Akhir</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($bkuTerbaru as $bku)
                            <tr>
                                <td class="ps-4">{{ $bku->tanggal_transaksi->format('d/m/Y') }}</td>
                                <td class="fw-semibold text-primary">{{ $bku->nomor_bukti ?? '-' }}</td>
                                <td><div class="text-truncate" style="max-width:150px;" title="{{ $bku->uraian }}">{{ $bku->uraian }}</div></td>
                                <td>
                                    @if($bku->debit > 0)
                                        <span class="text-success fw-bold">+Rp {{ number_format($bku->debit, 0, ',', '.') }}</span>
                                    @elseif($bku->kredit > 0)
                                        <span class="text-danger fw-bold">-Rp {{ number_format($bku->kredit, 0, ',', '.') }}</span>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="pe-4 text-end fw-bold">Rp {{ number_format($bku->saldo_akhir, 0, ',', '.') }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="5" class="text-center py-4 text-muted">Belum ada transaksi BKU.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ROW 7: Bunga & Laporan & Aktivitas -->
<div class="row g-4 mb-4">
    <!-- Buku Pembantu Bunga Rekening & Pengesahan Belanja -->
    <div class="col-lg-6 d-flex flex-column gap-4">
        <div class="card border-0 shadow-sm rounded-4 flex-fill">
            <div class="card-header bg-white border-bottom-0 pt-4 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0"><i class="bi bi-graph-up-arrow me-2 text-primary"></i>Buku Pembantu Bunga Rekening</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light text-muted small text-uppercase">
                            <tr>
                                <th class="ps-4">Tanggal</th>
                                <th>Deskripsi</th>
                                <th class="pe-4 text-end">Bunga (Kredit)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($transaksiBunga as $bunga)
                            <tr>
                                <td class="ps-4">{{ $bunga->tanggal_transaksi->format('d/m/Y') }}</td>
                                <td><div class="text-truncate" style="max-width:200px;">{{ $bunga->deskripsi }}</div></td>
                                <td class="pe-4 text-end fw-bold text-success">+Rp {{ number_format($bunga->kredit, 0, ',', '.') }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="3" class="text-center py-3 text-muted">Belum ada bunga bulan ini.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="card border-0 shadow-sm rounded-4 flex-fill">
            <div class="card-header bg-white border-bottom-0 pt-4 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0"><i class="bi bi-file-earmark-check me-2 text-primary"></i>Buku Pengesahan Belanja</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light text-muted small text-uppercase">
                            <tr>
                                <th class="ps-4">No Laporan</th>
                                <th>Periode</th>
                                <th>Pengeluaran</th>
                                <th class="pe-4 text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($laporanPengesahan as $lp)
                            <tr>
                                <td class="ps-4 fw-semibold text-primary">{{ $lp->nomor_laporan ?? 'LP-'.$lp->id }}</td>
                                <td>Bulan {{ $lp->periode_bulan }} / {{ $lp->tahun }}</td>
                                <td class="fw-bold">Rp {{ number_format($lp->total_pengeluaran, 0, ',', '.') }}</td>
                                <td class="pe-4 text-end">
                                    <a href="#" class="btn btn-sm btn-outline-secondary rounded-pill px-3">Lihat</a>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="4" class="text-center py-3 text-muted">Belum ada Laporan Pengesahan.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Aktivitas Terbaru -->
    <div class="col-lg-6 d-flex flex-column gap-4">
        <div class="card border-0 shadow-sm rounded-4 flex-fill">
            <div class="card-header bg-white border-bottom-0 pt-4">
                <h5 class="fw-bold mb-0"><i class="bi bi-clock-history me-2 text-primary"></i>Aktivitas Terbaru Anda</h5>
            </div>
            <div class="card-body">
                <div class="timeline px-2">
                    @forelse($aktivitasTerbaru as $log)
                    <div class="d-flex mb-4">
                        <div class="me-3">
                            <div class="bg-primary bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                <i class="bi bi-check2-circle text-primary fs-5"></i>
                            </div>
                        </div>
                        <div>
                            <div class="fw-bold text-dark">{{ $log->aksi ?? 'Melakukan aksi' }}</div>
                            <p class="text-muted small mb-1">{{ $log->catatan ?? '-' }}</p>
                            <span class="badge bg-light text-dark border"><i class="bi bi-clock me-1"></i>{{ $log->created_at->diffForHumans() }}</span>
                        </div>
                    </div>
                    @empty
                    <div class="text-center py-4 text-muted">Belum ada aktivitas tercatat untuk peran ini.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('styles')
<style>
    /* Styling adjustments for timeline */
    .timeline {
        position: relative;
    }
    .timeline::before {
        content: '';
        position: absolute;
        top: 20px;
        bottom: 0;
        left: 28px;
        width: 2px;
        background-color: #e9ecef;
        z-index: 0;
    }
    .timeline > div {
        position: relative;
        z-index: 1;
    }
    .timeline > div:last-child {
        margin-bottom: 0 !important;
    }
</style>
@endpush
