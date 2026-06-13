@extends('layouts.app')
@section('title', 'Dashboard Bendahara Penerimaan')
@section('content')

<!-- ROW 1: Header Dashboard -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
    <div>
        <h3 class="mb-1 text-uppercase fw-bold text-dark">Dashboard Bendahara Penerimaan</h3>
        <p class="text-muted mb-0">Pantau antrean verifikasi, pembukuan, dan status pembayaran dalam satu layar.</p>
        <div class="small text-muted mt-1">
            <i class="bi bi-calendar3 me-1"></i> {{ $now->isoFormat('dddd, D MMMM YYYY') }} | 
            <i class="bi bi-person me-1"></i> {{ $user->name }} (Bendahara Penerimaan)
        </div>
    </div>
    <div class="mt-3 mt-md-0 d-flex gap-2 flex-wrap">
        <a href="{{ route('verifikasi-bendahara-penerimaan.perjaldin.index') }}" class="btn btn-sm btn-outline-primary fw-semibold"><i class="bi bi-airplane me-1"></i> Tagihan Perjaldin</a>
        <a href="{{ route('proses-tagihan.index') }}" class="btn btn-sm btn-outline-primary fw-semibold"><i class="bi bi-file-earmark-text me-1"></i> Proses Tagihan</a>
        <a href="{{ route('pembukuan.bku.index') }}" class="btn btn-sm btn-primary fw-semibold"><i class="bi bi-book me-1"></i> BKU</a>
    </div>
</div>

<!-- ROW 2: Summary Cards Utama -->
<div class="row g-3 mb-4">
    <!-- A. Tagihan Perjaldin -->
    <div class="col-xl-2 col-md-4 col-sm-6">
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
                <a href="{{ route('verifikasi-bendahara-penerimaan.perjaldin.index') }}" class="stretched-link"></a>
            </div>
        </div>
    </div>
    
    <!-- B. NPI Kontrak -->
    <div class="col-xl-2 col-md-4 col-sm-6">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body">
                <h6 class="text-muted mb-2 small fw-bold text-uppercase">NPI Kontrak</h6>
                <div class="d-flex justify-content-between align-items-center">
                    <h3 class="mb-0 fw-bold {{ $npiKontrak->count() > 0 ? 'text-warning' : 'text-success' }}">{{ $npiKontrak->count() }}</h3>
                    <div class="bg-primary bg-opacity-10 rounded-circle p-2">
                        <i class="bi bi-file-earmark-text text-primary fs-5"></i>
                    </div>
                </div>
                <div class="mt-2 small text-muted">
                    <span class="badge bg-danger rounded-pill">{{ $npiKontrak->where('status', \App\Models\DokumenNpi::STATUS_REVISI)->count() }} Revisi</span>
                </div>
                <a href="{{ route('proses-tagihan.index') }}" class="stretched-link"></a>
            </div>
        </div>
    </div>

    <!-- C. NPI Perjaldin -->
    <div class="col-xl-2 col-md-4 col-sm-6">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body">
                <h6 class="text-muted mb-2 small fw-bold text-uppercase">NPI Perjaldin</h6>
                <div class="d-flex justify-content-between align-items-center">
                    <h3 class="mb-0 fw-bold {{ $npiPerjaldin->count() > 0 ? 'text-warning' : 'text-success' }}">{{ $npiPerjaldin->count() }}</h3>
                    <div class="bg-primary bg-opacity-10 rounded-circle p-2">
                        <i class="bi bi-airplane-engines text-primary fs-5"></i>
                    </div>
                </div>
                <div class="mt-2 small text-muted">
                    <span class="badge bg-danger rounded-pill">{{ $npiPerjaldin->where('status', \App\Models\DokumenNpi::STATUS_REVISI)->count() }} Revisi</span>
                </div>
            </div>
        </div>
    </div>

    <!-- D. NPI Honor -->
    <div class="col-xl-2 col-md-4 col-sm-6">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body">
                <h6 class="text-muted mb-2 small fw-bold text-uppercase">NPI Honor</h6>
                <div class="d-flex justify-content-between align-items-center">
                    <h3 class="mb-0 fw-bold {{ $npiHonor->count() > 0 ? 'text-warning' : 'text-success' }}">{{ $npiHonor->count() }}</h3>
                    <div class="bg-primary bg-opacity-10 rounded-circle p-2">
                        <i class="bi bi-people text-primary fs-5"></i>
                    </div>
                </div>
                <div class="mt-2 small text-muted">
                    <span class="badge bg-danger rounded-pill">{{ $npiHonor->where('status', \App\Models\DokumenNpi::STATUS_REVISI)->count() }} Revisi</span>
                </div>
                <a href="{{ route('proses-tagihan.index') }}" class="stretched-link"></a>
            </div>
        </div>
    </div>

    <!-- E. Piutang Perlu Dicek -->
    <div class="col-xl-2 col-md-4 col-sm-6">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body">
                <h6 class="text-muted mb-2 small fw-bold text-uppercase">Cek Piutang</h6>
                <div class="d-flex justify-content-between align-items-center">
                    <h3 class="mb-0 fw-bold {{ $totalPiutangPending > 0 ? 'text-danger' : 'text-success' }}">{{ $totalPiutangPending }}</h3>
                    <div class="bg-danger bg-opacity-10 rounded-circle p-2">
                        <i class="bi bi-cash-stack text-danger fs-5"></i>
                    </div>
                </div>
                <div class="mt-2 small text-muted text-truncate" title="Rp {{ number_format($nominalPiutangOutstanding, 0, ',', '.') }}">
                    <span class="fw-bold">Rp {{ number_format($nominalPiutangOutstanding, 0, ',', '.') }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- F. Aktivitas Hari Ini -->
    <div class="col-xl-2 col-md-4 col-sm-6">
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
                        <button class="nav-link fw-semibold" data-bs-toggle="tab" data-bs-target="#tab-kontrak" type="button">
                            NPI Kontrak <span class="badge bg-danger rounded-pill ms-1">{{ $npiKontrak->count() }}</span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-semibold" data-bs-toggle="tab" data-bs-target="#tab-npiperjaldin" type="button">
                            NPI Perjaldin <span class="badge bg-danger rounded-pill ms-1">{{ $npiPerjaldin->count() }}</span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-semibold" data-bs-toggle="tab" data-bs-target="#tab-npihonor" type="button">
                            NPI Honor <span class="badge bg-danger rounded-pill ms-1">{{ $npiHonor->count() }}</span>
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
                                        <th class="ps-4">No Dokumen</th>
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
                    
                    <!-- Tab Kontrak -->
                    <div class="tab-pane fade" id="tab-kontrak">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light text-muted small text-uppercase">
                                    <tr>
                                        <th class="ps-4">No NPI</th>
                                        <th>Uraian</th>
                                        <th>Nominal</th>
                                        <th>Status / Usia</th>
                                        <th class="pe-4 text-end">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($antreanList->where('tab', 'npi_kontrak')->take(5) as $item)
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
                                    <tr><td colspan="5" class="text-center py-4 text-muted">Tidak ada antrean NPI Kontrak.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Tab NPI Perjaldin -->
                    <div class="tab-pane fade" id="tab-npiperjaldin">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light text-muted small text-uppercase">
                                    <tr>
                                        <th class="ps-4">No NPI</th>
                                        <th>Uraian</th>
                                        <th>Nominal</th>
                                        <th>Status / Usia</th>
                                        <th class="pe-4 text-end">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($antreanList->where('tab', 'npi_perjaldin')->take(5) as $item)
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
                                    <tr><td colspan="5" class="text-center py-4 text-muted">Tidak ada antrean NPI Perjaldin.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Tab NPI Honor -->
                    <div class="tab-pane fade" id="tab-npihonor">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light text-muted small text-uppercase">
                                    <tr>
                                        <th class="ps-4">No NPI</th>
                                        <th>Uraian</th>
                                        <th>Nominal</th>
                                        <th>Status / Usia</th>
                                        <th class="pe-4 text-end">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($antreanList->where('tab', 'npi_honor')->take(5) as $item)
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
                                    <tr><td colspan="5" class="text-center py-4 text-muted">Tidak ada antrean NPI Honor.</td></tr>
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
                        Tidak ada antrean prioritas mendesak saat ini.
                    </li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- ROW 4: Ringkasan Pembukuan & Pengecekan Piutang -->
<div class="row g-4 mb-4">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-header bg-white border-bottom-0 pt-4">
                <h5 class="fw-bold mb-0"><i class="bi bi-wallet2 me-2 text-primary"></i>Ringkasan Pembukuan (Bulan Ini)</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-sm-6">
                        <div class="p-3 bg-light rounded-4 border border-light">
                            <div class="text-muted small fw-bold text-uppercase mb-1">Saldo BKU</div>
                            <h4 class="fw-bold mb-0 text-dark">Rp {{ number_format($saldoTerakhirBku, 0, ',', '.') }}</h4>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="p-3 bg-light rounded-4 border border-light">
                            <div class="text-muted small fw-bold text-uppercase mb-1">Mutasi Belum Rekon</div>
                            <h4 class="fw-bold mb-0 {{ $mutasiBelumRekon > 0 ? 'text-danger' : 'text-success' }}">{{ $mutasiBelumRekon }} <span class="fs-6 text-muted fw-normal">Item</span></h4>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="p-3 bg-light rounded-4 border border-light">
                            <div class="text-muted small fw-bold text-uppercase mb-1">Bunga Rekening</div>
                            <h4 class="fw-bold mb-0 text-success">Rp {{ number_format($bungaRekeningBulanIni, 0, ',', '.') }}</h4>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="p-3 bg-light rounded-4 border border-light">
                            <div class="text-muted small fw-bold text-uppercase mb-1">Rekon Matched</div>
                            <h4 class="fw-bold mb-0 text-success">{{ $rekonMatched }} <span class="fs-6 text-muted fw-normal">Item</span></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-header bg-white border-bottom-0 pt-4 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0"><i class="bi bi-cash-coin me-2 text-primary"></i>Pengecekan Pembayaran / Piutang</h5>
                <a href="#" class="btn btn-sm btn-link">Lihat Semua</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light text-muted small text-uppercase">
                            <tr>
                                <th class="ps-4">No Invoice</th>
                                <th>Mitra</th>
                                <th>Outstanding</th>
                                <th>Status</th>
                                <th class="pe-4 text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($piutangTable as $p)
                            <tr>
                                <td class="ps-4 fw-semibold">{{ $p->nomor_invoice ?? 'INV-'.$p->id }}</td>
                                <td>{{ $p->mitra?->nama_pihak ?? '-' }}</td>
                                <td class="fw-bold text-danger">Rp {{ number_format($p->nominal_tagihan - $p->total_dibayar, 0, ',', '.') }}</td>
                                <td>
                                    <span class="badge bg-{{ $p->status_pembayaran == 'UNPAID' ? 'danger' : 'warning text-dark' }}">{{ $p->status_pembayaran }}</span>
                                </td>
                                <td class="pe-4 text-end">
                                    <a href="#" class="btn btn-sm btn-outline-secondary rounded-pill">Detail</a>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="5" class="text-center py-4 text-muted">Semua piutang telah lunas.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ROW 5: BKU & Rekonsiliasi -->
<div class="row g-4 mb-4">
    <!-- BKU Terbaru -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-header bg-white border-bottom-0 pt-4 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0"><i class="bi bi-journal-text me-2 text-primary"></i>Buku Kas Umum Terbaru</h5>
                <a href="#" class="btn btn-sm btn-link">Buka BKU</a>
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
                                <th class="pe-4 text-end">Saldo</th>
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

    <!-- Buku Pembantu Bank / Rekonsiliasi -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-header bg-white border-bottom-0 pt-4 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0"><i class="bi bi-bank me-2 text-primary"></i>Buku Pembantu Bank & Rekonsiliasi</h5>
                <a href="#" class="btn btn-sm btn-link">Lihat Buku Bank</a>
            </div>
            <div class="card-body p-0">
                <div class="p-3 bg-light border-bottom">
                    <h6 class="fw-bold mb-2">Mutasi Bank Belum Rekonsiliasi:</h6>
                    <ul class="list-group list-group-flush bg-transparent">
                        @forelse($mutasiPending as $mutasi)
                        <li class="list-group-item bg-transparent px-0 border-light">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="fw-semibold">{{ $mutasi->tanggal_transaksi->format('d/m/Y') }}</div>
                                    <div class="small text-muted text-truncate" style="max-width:200px;">{{ $mutasi->deskripsi }}</div>
                                </div>
                                <div class="text-end">
                                    <div class="fw-bold {{ $mutasi->kredit > 0 ? 'text-success' : 'text-danger' }}">
                                        {{ $mutasi->kredit > 0 ? '+' : '-' }}Rp {{ number_format($mutasi->kredit > 0 ? $mutasi->kredit : $mutasi->debit, 0, ',', '.') }}
                                    </div>
                                    <a href="#" class="btn btn-sm btn-primary rounded-pill py-0 mt-1" style="font-size: 0.75rem;">Rekon</a>
                                </div>
                            </div>
                        </li>
                        @empty
                        <li class="list-group-item bg-transparent px-0 border-0 text-muted">Semua mutasi telah direkonsiliasi.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ROW 6: Buku Pembantu Bunga & Pengesahan -->
<div class="row g-4 mb-4">
    <!-- Buku Pembantu Bunga Rekening -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-header bg-white border-bottom-0 pt-4 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0"><i class="bi bi-graph-up-arrow me-2 text-primary"></i>Buku Pembantu Bunga Rekening</h5>
                <a href="#" class="btn btn-sm btn-link">Detail Bunga</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light text-muted small text-uppercase">
                            <tr>
                                <th class="ps-4">Tanggal</th>
                                <th>Deskripsi</th>
                                <th>Nominal Bunga</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($transaksiBunga as $bunga)
                            <tr>
                                <td class="ps-4">{{ $bunga->tanggal_transaksi->format('d/m/Y') }}</td>
                                <td><div class="text-truncate" style="max-width:200px;" title="{{ $bunga->deskripsi }}">{{ $bunga->deskripsi }}</div></td>
                                <td class="fw-bold text-success">+Rp {{ number_format($bunga->kredit, 0, ',', '.') }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="3" class="text-center py-4 text-muted">Tidak ada transaksi bunga rekening tercatat.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="p-3 bg-light text-center border-top">
                    <small class="text-muted fst-italic">Catatan: Bunga diidentifikasi dari deskripsi mutasi bank.</small>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Buku Pengesahan Belanja -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-header bg-white border-bottom-0 pt-4 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0"><i class="bi bi-file-earmark-check me-2 text-primary"></i>Buku Pengesahan Belanja</h5>
                <a href="#" class="btn btn-sm btn-link">Lihat Semua</a>
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
                            <tr><td colspan="4" class="text-center py-4 text-muted">Belum ada Laporan Pengesahan BLU.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ROW 7: Aktivitas & Notifikasi -->
<div class="row g-4 mb-4">
    <!-- Aktivitas Terbaru -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4 h-100">
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
    
    <!-- Notifikasi -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-header bg-white border-bottom-0 pt-4">
                <h5 class="fw-bold mb-0"><i class="bi bi-bell me-2 text-primary"></i>Notifikasi</h5>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    @forelse($notifikasi as $notif)
                    <li class="list-group-item px-0 border-light py-3">
                        <div class="d-flex align-items-start">
                            <div class="bg-light rounded p-2 me-3">
                                <i class="bi bi-bell-fill text-warning"></i>
                            </div>
                            <div>
                                <h6 class="mb-1 fw-bold">{{ $notif->data['title'] ?? 'Sistem' }}</h6>
                                <p class="small text-muted mb-1">{{ $notif->data['message'] ?? '' }}</p>
                                <div class="small text-primary fw-semibold">{{ $notif->created_at->diffForHumans() }}</div>
                            </div>
                        </div>
                    </li>
                    @empty
                    <li class="list-group-item px-0 border-0 text-center py-4 text-muted">
                        <i class="bi bi-bell-slash fs-1 text-light mb-2 d-block"></i>
                        Tidak ada notifikasi baru.
                    </li>
                    @endforelse
                </ul>
            </div>
            <div class="card-footer bg-white text-center border-top-0 pb-4">
                <a href="#" class="btn btn-outline-primary btn-sm rounded-pill px-4">Lihat Semua Notifikasi</a>
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
