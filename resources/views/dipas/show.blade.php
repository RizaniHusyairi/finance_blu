@extends('layouts.app')

@section('title', 'Detail DIPA')

@php
    $selisih = $summary['selisih_pagu_vs_item'];
    $hasSelisih = round((float) $selisih, 2) !== 0.0;
@endphp

@section('content')
    <x-page-title title="Detail DIPA" subtitle="Pusat pengelolaan header DIPA, revisi, dan item anggaran" />

    @if(session('success'))
        <div class="alert alert-success border-0 bg-success alert-dismissible fade show shadow-sm">
            <div class="text-white">{{ session('success') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('info'))
        <div class="alert alert-info border-0 bg-info alert-dismissible fade show shadow-sm">
            <div class="text-white">{{ session('info') }}</div>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger border-0 bg-danger alert-dismissible fade show shadow-sm">
            <div class="text-white">{{ session('error') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger border-0 bg-danger alert-dismissible fade show shadow-sm">
            <ul class="text-white mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <div>
            <h4 class="mb-1 fw-bold">{{ $dipa->nomor_dipa }}</h4>
            <div class="d-flex flex-wrap gap-2 mt-2">
                <span class="badge bg-light text-dark border">Tahun {{ $dipa->tahun_anggaran }}</span>
                <span class="badge bg-info text-dark">Revisi Aktif {{ $dipa->revisi_aktif_ke ?? 0 }}</span>
                <span class="badge {{ $dipa->status_aktif ? 'bg-success' : 'bg-secondary' }}">{{ $dipa->status_aktif ? 'Aktif' : 'Nonaktif' }}</span>
            </div>
            <p class="text-muted mb-0 mt-2">Tanggal disahkan: {{ optional($dipa->tanggal_disahkan)->format('d M Y') ?? '-' }}</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('dipas.revisions.create', $dipa) }}" class="btn btn-outline-primary">
                <i class="bi bi-files me-1"></i> Tambah Revisi
            </a>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambahItem">
                <i class="bi bi-plus-lg me-1"></i> Tambah Item Anggaran
            </button>
            <a href="{{ route('dipas.edit', $dipa) }}" class="btn btn-outline-warning">
                <i class="bi bi-pencil-square me-1"></i> Edit Header
            </a>
            <a href="{{ route('dipas.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar
            </a>
        </div>
    </div>

    <div class="row row-cols-1 row-cols-md-2 row-cols-xl-4 g-3 mb-4">
        <div class="col">
            <div class="card rounded-4 h-100 shadow-sm border-0">
                <div class="card-body p-3 border-start border-4 border-primary rounded-4">
                    <p class="mb-1 small text-muted">Total Pagu Revisi Aktif</p>
                    <h5 class="mb-0 fw-bold">Rp {{ number_format($summary['total_pagu_revisi_aktif'], 0, ',', '.') }}</h5>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card rounded-4 h-100 shadow-sm border-0">
                <div class="card-body p-3 border-start border-4 border-info rounded-4">
                    <p class="mb-1 small text-muted">Total Item Anggaran</p>
                    <h5 class="mb-0 fw-bold">Rp {{ number_format($summary['total_item_anggaran'], 0, ',', '.') }}</h5>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card rounded-4 h-100 shadow-sm border-0">
                <div class="card-body p-3 border-start border-4 border-success rounded-4">
                    <p class="mb-1 small text-muted">Jumlah Item Aktif</p>
                    <h5 class="mb-0 fw-bold">{{ number_format($summary['jumlah_item_aktif']) }}</h5>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card rounded-4 h-100 shadow-sm border-0">
                <div class="card-body p-3 border-start border-4 {{ $hasSelisih ? 'border-danger' : 'border-warning' }} rounded-4">
                    <p class="mb-1 small text-muted">Selisih Pagu vs Item</p>
                    <h5 class="mb-0 fw-bold {{ $hasSelisih ? 'text-danger' : 'text-success' }}">Rp {{ number_format($selisih, 0, ',', '.') }}</h5>
                </div>
            </div>
        </div>
    </div>

    @if($hasSelisih)
        <div class="alert alert-warning border-0 shadow-sm mb-4">
            <i class="bi bi-exclamation-triangle me-2"></i>
            Total pagu revisi aktif belum sinkron dengan total item anggaran. Selisih saat ini adalah
            <strong>Rp {{ number_format($selisih, 0, ',', '.') }}</strong>.
        </div>
    @endif

    <div class="card shadow-sm border-0 rounded-4 mb-4">
        <div class="card-header bg-white border-bottom-0 pt-4 px-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h5 class="mb-1 fw-bold">Informasi Revisi Aktif</h5>
                <p class="text-muted small mb-0">Detail revisi yang saat ini dipakai sebagai dasar item anggaran.</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('dipas.revisions.create', $dipa) }}" class="btn btn-primary btn-sm">Tambah Revisi Baru</a>
            </div>
        </div>
        <div class="card-body px-4 pb-4">
            <div class="row g-3">
                <div class="col-md-3">
                    <div class="border rounded-4 p-3 h-100">
                        <div class="small text-muted mb-1">Nomor Revisi</div>
                        <div class="fw-bold">{{ $activeRevision->nomor_revisi ?? '-' }}</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="border rounded-4 p-3 h-100">
                        <div class="small text-muted mb-1">Tanggal Revisi</div>
                        <div class="fw-bold">{{ optional($activeRevision?->tanggal_revisi)->format('d M Y') ?? '-' }}</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="border rounded-4 p-3 h-100">
                        <div class="small text-muted mb-1">Total Pagu</div>
                        <div class="fw-bold">Rp {{ number_format($activeRevision->total_pagu ?? 0, 0, ',', '.') }}</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="border rounded-4 p-3 h-100">
                        <div class="small text-muted mb-1">Dokumen DIPA</div>
                        @if($activeRevision?->file_dokumen_dipa)
                            <a href="{{ Storage::url($activeRevision->file_dokumen_dipa) }}" target="_blank" rel="noopener noreferrer" class="btn btn-outline-secondary btn-sm w-100">Buka Dokumen</a>
                        @else
                            <div class="fw-semibold text-muted">Belum ada file</div>
                        @endif
                    </div>
                </div>
                <div class="col-12">
                    <div class="border rounded-4 p-3">
                        <div class="small text-muted mb-1">Keterangan</div>
                        <div class="fw-semibold">{{ $activeRevision->keterangan ?: '-' }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0 rounded-4 mb-4">
        <div class="card-header bg-white border-bottom-0 pt-4 px-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h5 class="mb-1 fw-bold">Item Anggaran Revisi Aktif</h5>
                <p class="text-muted small mb-0">Kelola item COA yang masuk dalam revisi aktif DIPA ini.</p>
            </div>
            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalTambahItem">
                <i class="bi bi-plus-lg me-1"></i> Tambah Item Anggaran
            </button>
        </div>
        <div class="card-body px-4 pb-4">
            <form method="GET" action="{{ route('dipas.show', $dipa) }}" class="mb-4">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Cari COA</label>
                        <input type="text" name="search_coa" class="form-control" value="{{ request('search_coa') }}" placeholder="Kode MAK lengkap">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Cari Nama Akun</label>
                        <input type="text" name="search_nama_akun" class="form-control" value="{{ request('search_nama_akun') }}" placeholder="Nama akun">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold">Kode Akun</label>
                        <select name="kd_akun" class="form-select">
                            <option value="">Semua</option>
                            @foreach($kdAkunOptions as $kdAkun)
                                <option value="{{ $kdAkun }}" {{ (string) request('kd_akun') === (string) $kdAkun ? 'selected' : '' }}>{{ $kdAkun }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold">Status Aktif</label>
                        <select name="status_item" class="form-select">
                            <option value="">Semua</option>
                            <option value="aktif" {{ request('status_item') === 'aktif' ? 'selected' : '' }}>Aktif</option>
                            <option value="nonaktif" {{ request('status_item') === 'nonaktif' ? 'selected' : '' }}>Nonaktif</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                            <a href="{{ route('dipas.show', $dipa) }}" class="btn btn-outline-secondary btn-sm">Reset</a>
                        </div>
                    </div>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center" width="5%">No</th>
                            <th width="18%">COA Lengkap</th>
                            <th width="15%">Nama Akun</th>
                            <th width="14%" class="text-end">Nilai Pagu</th>
                            <th width="14%" class="text-end">Realisasi</th>
                            <th width="14%" class="text-end">Sisa Pagu</th>
                            <th width="8%" class="text-center">Status</th>
                            <th width="12%" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($items as $item)
                            <tr>
                                <td class="text-center">{{ $loop->iteration }}</td>
                                <td class="fw-bold text-primary">
                                    {{ $item->coa->kode_mak_lengkap ?? '-' }}<br>
                                    <small class="text-muted">{{ $item->coa->kd_akun ?? '-' }}</small>
                                </td>
                                <td>
                                    {{ $item->coa->nama_akun ?? '-' }}<br>
                                    <small class="text-muted">{{ $item->coa->jenis_akun ?? '-' }}</small>
                                </td>
                                <td class="text-end fw-semibold text-dark">Rp {{ number_format($item->nilai_pagu, 0, ',', '.') }}</td>
                                <td class="text-end text-success">Rp {{ number_format($item->total_realisasi, 0, ',', '.') }}</td>
                                <td class="text-end fw-bold {{ $item->sisa_pagu < 0 ? 'text-danger' : 'text-primary' }}">Rp {{ number_format($item->sisa_pagu, 0, ',', '.') }}</td>
                                <td class="text-center">
                                    <span class="badge {{ $item->status_aktif ? 'bg-success' : 'bg-secondary' }}">{{ $item->status_aktif ? 'Aktif' : 'Nonaktif' }}</span>
                                </td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-2 flex-wrap">
                                        <form action="{{ route('dipas.items.toggle', [$dipa, $item]) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-warning">{{ $item->status_aktif ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                                        </form>
                                        <form action="{{ route('dipas.items.destroy', [$dipa, $item]) }}" method="POST" onsubmit="return confirm('Hapus item ini dari revisi aktif?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Hapus</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">Belum ada item anggaran pada revisi aktif ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0 rounded-4">
        <div class="card-header bg-white border-bottom-0 pt-4 px-4">
            <h5 class="mb-1 fw-bold">Histori Revisi</h5>
            <p class="text-muted small mb-0">Daftar seluruh revisi DIPA dan kontrol untuk mengaktifkan revisi tertentu.</p>
        </div>
        <div class="card-body px-4 pb-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Nomor Revisi</th>
                            <th>Tanggal Revisi</th>
                            <th class="text-end">Total Pagu</th>
                            <th class="text-center">Status Aktif</th>
                            <th>Keterangan</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($dipa->revisions->sortByDesc('nomor_revisi') as $revision)
                            <tr>
                                <td class="fw-bold">Revisi {{ $revision->nomor_revisi }}</td>
                                <td>{{ optional($revision->tanggal_revisi)->format('d M Y') ?? '-' }}</td>
                                <td class="text-end">Rp {{ number_format($revision->total_pagu ?? 0, 0, ',', '.') }}</td>
                                <td class="text-center">
                                    <span class="badge {{ $revision->is_active ? 'bg-success' : 'bg-secondary' }}">{{ $revision->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                                </td>
                                <td>{{ $revision->keterangan ?: '-' }}</td>
                                <td class="text-center">
                                    @if(!$revision->is_active)
                                        <form action="{{ route('dipas.revisions.activate', [$dipa, $revision]) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-primary">Aktifkan Revisi Ini</button>
                                        </form>
                                    @else
                                        <span class="text-muted small">Revisi aktif saat ini</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalTambahItem" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <form action="{{ route('dipas.items.store', $dipa) }}" method="POST">
                    @csrf
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title fw-bold"><i class="bi bi-plus-circle me-2"></i>Tambah Item Anggaran</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Pilih COA</label>
                                <select name="coa_id" id="coa_id" class="form-select select2-coa" required>
                                    <option value="">-- Pilih COA --</option>
                                    @foreach($coaOptions as $coa)
                                        <option value="{{ $coa->id }}"
                                            data-kode="{{ $coa->kode_mak_lengkap }}"
                                            data-kd-akun="{{ $coa->kd_akun }}"
                                            data-nama="{{ $coa->nama_akun }}"
                                            data-jenis="{{ $coa->jenis_akun }}">
                                            {{ $coa->kode_mak_lengkap }} - {{ $coa->nama_akun }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Nilai Pagu</label>
                                <input type="hidden" name="nilai_pagu" id="nilai_pagu" value="{{ old('nilai_pagu') }}">
                                <input type="text" id="nilai_pagu_display" class="form-control" inputmode="numeric" placeholder="Rp 0" value="{{ old('nilai_pagu') ? 'Rp ' . number_format((float) old('nilai_pagu'), 0, ',', '.') : '' }}" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Status Aktif</label>
                                <select name="status_aktif" class="form-select" required>
                                    <option value="1">Aktif</option>
                                    <option value="0">Nonaktif</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <div class="border rounded-4 p-3 bg-light">
                                    <div class="small text-muted mb-2">Preview COA</div>
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <div class="small text-muted">COA Lengkap</div>
                                            <div class="fw-bold" id="preview_kode_mak">-</div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="small text-muted">Kode Akun</div>
                                            <div class="fw-bold" id="preview_kd_akun">-</div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="small text-muted">Jenis Akun</div>
                                            <div class="fw-bold" id="preview_jenis_akun">-</div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="small text-muted">Nama Akun</div>
                                            <div class="fw-bold" id="preview_nama_akun">-</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
                        <button type="submit" class="btn btn-primary">Simpan Item</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('script')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const coaSelect = document.getElementById('coa_id');
        const nilaiPaguHidden = document.getElementById('nilai_pagu');
        const nilaiPaguDisplay = document.getElementById('nilai_pagu_display');

        if (!coaSelect || !nilaiPaguHidden || !nilaiPaguDisplay) {
            return;
        }

        if (window.jQuery && typeof window.jQuery.fn.select2 === 'function') {
            window.jQuery(coaSelect).select2({
                theme: 'bootstrap-5',
                width: '100%',
                dropdownParent: window.jQuery('#modalTambahItem'),
                placeholder: '-- Pilih COA --'
            });
        }

        const updatePreviewCoa = function () {
            const selected = coaSelect.options[coaSelect.selectedIndex];
            document.getElementById('preview_kode_mak').textContent = selected?.dataset?.kode || '-';
            document.getElementById('preview_kd_akun').textContent = selected?.dataset?.kdAkun || '-';
            document.getElementById('preview_nama_akun').textContent = selected?.dataset?.nama || '-';
            document.getElementById('preview_jenis_akun').textContent = selected?.dataset?.jenis || '-';
        };

        coaSelect.addEventListener('change', updatePreviewCoa);

        const formatRupiah = (value) => {
            return 'Rp ' + new Intl.NumberFormat('id-ID', {
                maximumFractionDigits: 0,
            }).format(value || 0);
        };

        const syncNilaiPagu = () => {
            const numeric = nilaiPaguDisplay.value.replace(/[^\d]/g, '');
            nilaiPaguHidden.value = numeric;
            nilaiPaguDisplay.value = numeric ? formatRupiah(parseInt(numeric, 10)) : '';
        };

        nilaiPaguDisplay.addEventListener('input', syncNilaiPagu);
        nilaiPaguDisplay.addEventListener('blur', syncNilaiPagu);

        updatePreviewCoa();
        syncNilaiPagu();
    });
</script>
@endpush

