@extends('layouts.app')

@section('title')
    Pengajuan Pembayaran BLU
@endsection

@push('css')
    <style>
        .payment-hero {
            background: linear-gradient(135deg, rgba(13, 110, 253, 0.12), rgba(13, 202, 240, 0.08));
            border: 1px solid rgba(13, 110, 253, 0.08);
        }

        .stat-card {
            min-height: 148px;
        }

        .stat-icon {
            width: 48px;
            height: 48px;
        }

        .filter-shell {
            background: #f8fbff;
            border: 1px solid #e5eefc;
        }

        .submission-table thead th {
            white-space: nowrap;
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #6c757d;
        }

        .submission-table tbody tr {
            transition: background-color 0.2s ease;
        }

        .submission-table tbody tr:hover {
            background-color: rgba(13, 110, 253, 0.03);
        }

        .submission-number {
            font-weight: 700;
            color: #1f2d3d;
        }

        .muted-meta {
            font-size: 0.78rem;
            color: #6c757d;
        }

        .summary-alert {
            border-radius: 1rem;
        }

        .empty-state {
            display: none;
            border: 1px dashed #c8d7eb;
            border-radius: 1.25rem;
            background: linear-gradient(180deg, #fbfdff 0%, #f3f8ff 100%);
        }

        .table-slim td {
            padding-top: 1rem;
            padding-bottom: 1rem;
        }
    </style>
@endpush

@section('content')
    @php
        $statusClasses = [
            'Draft' => 'warning text-dark',
            'Menunggu Verifikasi' => 'info text-dark',
            'Menunggu Persetujuan' => 'primary',
            'Direvisi' => 'warning text-dark',
            'Ditolak' => 'danger',
            'Disetujui' => 'success',
            'Proses SP2D' => 'secondary',
            'Sudah Cair' => 'success',
        ];

        $alertClasses = ['warning' => 'warning', 'danger' => 'danger', 'success' => 'success'];
    @endphp

    <div class="card border-0 shadow-sm payment-hero rounded-4 mb-4">
        <div class="card-body p-4 p-xl-5">
            <div class="d-flex flex-column flex-xl-row justify-content-between align-items-xl-start gap-3">
                <div>
                    <div class="d-inline-flex align-items-center gap-2 px-3 py-2 rounded-pill bg-white text-primary small fw-semibold mb-3">
                        <span class="material-icons-outlined fs-6">payments</span>
                        Pusat Kerja Operator BLU
                    </div>
                    <h2 class="mb-2 fw-bold">Pengajuan Pembayaran BLU</h2>
                    <p class="mb-0 text-secondary">
                        Kelola data pengajuan pembayaran BLU, dokumen pendukung, status verifikasi, dan progres pencairan.
                    </p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('blu-payment-submissions.create') }}" class="btn btn-primary px-4">
                        <i class="bi bi-plus-circle me-1"></i> Tambah Pengajuan
                    </a>
                    <button type="button" class="btn btn-outline-success px-4 js-demo-action" data-message="Fitur export Excel akan dihubungkan ke backend pada tahap berikutnya.">
                        <i class="bi bi-file-earmark-excel me-1"></i> Export Excel
                    </button>
                    <button type="button" class="btn btn-outline-secondary px-4 js-demo-action" data-message="Fitur export PDF masih bersifat rancangan UI.">
                        <i class="bi bi-file-earmark-pdf me-1"></i> Export PDF
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        @foreach ($stats as $stat)
            <div class="col-12 col-md-6 col-xl-4 col-xxl-2">
                <div class="card stat-card rounded-4 border-0 shadow-sm h-100">
                    <div class="card-body d-flex flex-column justify-content-between">
                        <div class="d-flex align-items-start justify-content-between gap-3">
                            <div class="d-flex align-items-center justify-content-center rounded-circle bg-{{ $stat['color'] }} bg-opacity-10 text-{{ $stat['color'] }} stat-icon">
                                <span class="material-icons-outlined">{{ $stat['icon'] }}</span>
                            </div>
                            <span class="badge bg-{{ $stat['color'] }} bg-opacity-10 text-{{ $stat['color'] }} border border-{{ $stat['color'] }} border-opacity-10">Status</span>
                        </div>
                        <div>
                            <p class="mb-1 text-secondary">{{ $stat['label'] }}</p>
                            <h3 class="mb-1 fw-bold">{{ $stat['count'] }}</h3>
                            <small class="text-muted">{{ $stat['note'] }}</small>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    @if ($warningSummary->isNotEmpty())
        <div class="row g-3 mb-4">
            @foreach ($warningSummary as $item)
                <div class="col-12 col-xl-4">
                    <div class="alert alert-{{ $alertClasses[$item['type']] ?? 'warning' }} border-0 shadow-sm summary-alert mb-0">
                        <div class="fw-semibold mb-1">{{ $item['title'] }}</div>
                        <div class="small mb-1">{{ $item['message'] }}</div>
                        <div class="small fw-semibold">{{ $item['submission_number'] }}</div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <div class="card rounded-4 border-0 shadow-sm mb-4">
        <div class="card-body filter-shell rounded-4 p-3 p-xl-4">
            <div class="row g-3 align-items-end">
                <div class="col-12 col-lg-4">
                    <label for="searchSubmission" class="form-label fw-semibold">Cari Pengajuan</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                        <input type="text" id="searchSubmission" class="form-control" placeholder="Cari nomor pengajuan, uraian, supplier, atau kontrak">
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-3">
                    <label for="statusFilter" class="form-label fw-semibold">Filter Status</label>
                    <select id="statusFilter" class="form-select">
                        <option value="">Semua Status</option>
                        @foreach (array_keys($statusClasses) as $status)
                            <option value="{{ $status }}">{{ $status }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-6 col-lg-3">
                    <label for="contractFilter" class="form-label fw-semibold">Jenis Pengajuan</label>
                    <select id="contractFilter" class="form-select">
                        <option value="">Semua Jenis</option>
                        <option value="Kontrak">Kontrak</option>
                        <option value="Non-Kontrak">Non-Kontrak</option>
                    </select>
                </div>
                <div class="col-12 col-lg-2">
                    <button type="button" id="resetFilter" class="btn btn-outline-secondary w-100">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="card rounded-4 border-0 shadow-sm">
        <div class="card-header bg-transparent border-0 p-4 pb-0 d-flex flex-column flex-xl-row justify-content-between align-items-xl-center gap-2">
            <div>
                <h5 class="mb-1">Daftar Pengajuan Pembayaran</h5>
                <p class="text-muted mb-0">Pantau status, nilai tagihan, dan progres pencairan dalam satu tampilan ringkas.</p>
            </div>
            <div class="d-flex align-items-center gap-2 text-muted small">
                <span class="badge bg-light text-dark border">Data Dummy</span>
                <span>{{ $submissions->count() }} pengajuan</span>
            </div>
        </div>
        <div class="card-body p-4">
            <div class="table-responsive">
                <table class="table align-middle submission-table table-slim" id="submissionTable">
                    <thead>
                        <tr>
                            <th>No. Pengajuan</th>
                            <th>Tanggal</th>
                            <th>Uraian / Paket</th>
                            <th>Supplier</th>
                            <th>Kontrak</th>
                            <th class="text-end">Nilai Bruto</th>
                            <th class="text-end">Pajak</th>
                            <th class="text-end">Nilai Netto</th>
                            <th>Status</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($submissions as $submission)
                            <tr
                                data-search="{{ strtolower($submission['submission_number'].' '.$submission['title'].' '.$submission['supplier'].' '.($submission['contract_number'] ?? '')) }}"
                                data-status="{{ $submission['status'] }}"
                                data-contract="{{ $submission['contract_type'] }}"
                            >
                                <td class="align-top">
                                    <div class="submission-number">{{ $submission['submission_number'] }}</div>
                                    <div class="muted-meta">NPI: {{ $submission['npi_number'] ?? '-' }}</div>
                                </td>
                                <td class="align-top">
                                    <div class="fw-semibold">{{ $submission['date_label'] }}</div>
                                    <div class="muted-meta">{{ $submission['payment_type'] }}</div>
                                </td>
                                <td class="align-top">
                                    <div class="fw-semibold text-truncate" style="max-width: 250px;" title="{{ $submission['title'] }}">{{ $submission['title'] }}</div>
                                    <div class="muted-meta">{{ $submission['payment_note'] }}</div>
                                </td>
                                <td class="align-top">
                                    <div class="fw-semibold text-truncate" style="max-width: 180px;" title="{{ $submission['supplier'] }}">{{ $submission['supplier_short'] }}</div>
                                    <div class="muted-meta">{{ $submission['operator'] }}</div>
                                </td>
                                <td class="align-top">
                                    @if ($submission['contract_number'])
                                        <div class="fw-semibold">{{ $submission['contract_number'] }}</div>
                                        <div class="d-flex flex-wrap gap-1 mt-1">
                                            <span class="badge bg-primary-subtle text-primary border border-primary border-opacity-10">Kontrak</span>
                                            @if ($submission['contract_term_label'])
                                                <span class="muted-meta">{{ $submission['contract_term_label'] }}</span>
                                            @endif
                                        </div>
                                    @else
                                        <span class="badge bg-light text-dark border">Non-Kontrak</span>
                                    @endif
                                </td>
                                <td class="text-end align-top fw-semibold">Rp {{ number_format($submission['gross_amount'], 0, ',', '.') }}</td>
                                <td class="text-end align-top">
                                    <div class="fw-semibold">Rp {{ number_format($submission['tax_total'], 0, ',', '.') }}</div>
                                    <div class="muted-meta">PPN + PPh</div>
                                </td>
                                <td class="text-end align-top fw-semibold text-success">Rp {{ number_format($submission['net_amount'], 0, ',', '.') }}</td>
                                <td class="align-top">
                                    <span class="badge bg-{{ $statusClasses[$submission['status']] ?? 'secondary' }}">{{ $submission['status'] }}</span>
                                </td>
                                <td class="text-center align-top">
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">Aksi</button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li>
                                                <a class="dropdown-item" href="{{ route('blu-payment-submissions.show', $submission['submission_number']) }}">
                                                    <i class="bi bi-eye me-2"></i>Lihat Detail
                                                </a>
                                            </li>
                                            <li>
                                                <button class="dropdown-item js-demo-action" type="button" data-message="Mode edit pengajuan akan dihubungkan ke form transaksi atau dokumen pada tahap implementasi berikutnya." @disabled($submission['is_final'])>
                                                    <i class="bi bi-pencil-square me-2"></i>Edit Pengajuan
                                                </button>
                                            </li>
                                            <li>
                                                <button class="dropdown-item js-demo-action" type="button" data-message="Pengajuan draft siap dikirim ke tahap verifikasi." @disabled(! $submission['can_submit'])>
                                                    <i class="bi bi-send-check me-2"></i>Kirim Verifikasi
                                                </button>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <button class="dropdown-item text-danger js-cancel-action" type="button" @disabled(! $submission['can_cancel'])>
                                                    <i class="bi bi-x-circle me-2"></i>Batalkan
                                                </button>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="empty-state mt-3 p-5 text-center" id="emptyState">
                <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-primary bg-opacity-10 text-primary mb-3" style="width:72px;height:72px;">
                    <span class="material-icons-outlined fs-1">receipt_long</span>
                </div>
                <h4 class="fw-bold mb-2">Belum ada pengajuan pembayaran BLU</h4>
                <p class="text-muted mb-4">Mulai buat pengajuan pembayaran baru untuk memproses tagihan BLU</p>
                <a href="{{ route('blu-payment-submissions.create') }}" class="btn btn-primary px-4">
                    <i class="bi bi-plus-circle me-1"></i> Tambah Pengajuan
                </a>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const searchInput = document.getElementById('searchSubmission');
            const statusFilter = document.getElementById('statusFilter');
            const contractFilter = document.getElementById('contractFilter');
            const resetButton = document.getElementById('resetFilter');
            const rows = Array.from(document.querySelectorAll('#submissionTable tbody tr'));
            const emptyState = document.getElementById('emptyState');
            const tableWrapper = document.querySelector('#submissionTable').closest('.table-responsive');

            function applyFilter() {
                const keyword = (searchInput.value || '').toLowerCase().trim();
                const status = statusFilter.value;
                const contractType = contractFilter.value;
                let visibleCount = 0;

                rows.forEach((row) => {
                    const matchesKeyword = !keyword || row.dataset.search.includes(keyword);
                    const matchesStatus = !status || row.dataset.status === status;
                    const matchesContract = !contractType || row.dataset.contract === contractType;
                    const visible = matchesKeyword && matchesStatus && matchesContract;

                    row.style.display = visible ? '' : 'none';
                    if (visible) {
                        visibleCount += 1;
                    }
                });

                const showEmpty = visibleCount === 0;
                emptyState.style.display = showEmpty ? 'block' : 'none';
                tableWrapper.style.display = showEmpty ? 'none' : 'block';
            }

            [searchInput, statusFilter, contractFilter].forEach((element) => {
                element.addEventListener('input', applyFilter);
                element.addEventListener('change', applyFilter);
            });

            resetButton.addEventListener('click', function () {
                searchInput.value = '';
                statusFilter.value = '';
                contractFilter.value = '';
                applyFilter();
            });

            document.querySelectorAll('.js-demo-action').forEach((button) => {
                button.addEventListener('click', function () {
                    if (button.hasAttribute('disabled')) {
                        return;
                    }

                    window.alert(button.dataset.message || 'Aksi ini masih berupa rancangan antarmuka.');
                });
            });

            document.querySelectorAll('.js-cancel-action').forEach((button) => {
                button.addEventListener('click', function () {
                    if (button.hasAttribute('disabled')) {
                        return;
                    }

                    if (window.confirm('Batalkan pengajuan ini? Tindakan ini hanya simulasi pada tampilan demo.')) {
                        window.alert('Pengajuan dibatalkan pada mode demo tampilan.');
                    }
                });
            });

            applyFilter();
        });
    </script>
@endpush
