@extends('layouts.app')
@section('title')
    Detail Pagu Anggaran
@endsection
@push('css')
    <link href="{{ URL::asset('build/plugins/datatable/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet" />
@endpush
@section('content')

    {{-- 1. Header --}}
    <div class="d-flex justify-content-between align-items-center mb-1">
        <div>
            <h5 class="mb-0 fw-bold">Detail Pagu Anggaran</h5>
            <small>Monitoring detail penggunaan dan realisasi pagu anggaran</small>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('budgets.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Kembali</a>
            <a href="{{ route('budgets.edit', $budget->id) }}" class="btn btn-outline-warning"><i class="bi bi-pencil"></i> Edit Pagu</a>
        </div>
    </div>
    <hr>

    {{-- Alert jika realisasi melebihi pagu --}}
    @if($realisasi > $pagu)
        <div class="alert alert-danger d-flex align-items-center gap-2">
            <i class="bi bi-exclamation-octagon-fill fs-5"></i>
            <div><strong>Perhatian!</strong> Realisasi melebihi pagu anggaran. Segera lakukan revisi atau penyesuaian.</div>
        </div>
    @endif

    {{-- 2. Ringkasan Stat Cards --}}
    <div class="row row-cols-1 row-cols-sm-2 row-cols-md-4 g-3 mb-3">
        <div class="col">
            <div class="card rounded-4 mb-0 h-100">
                <div class="card-body p-3">
                    <p class="mb-1 small">Pagu Anggaran</p>
                    <h5 class="mb-0 fw-bold">Rp {{ number_format($pagu, 0, ',', '.') }}</h5>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card rounded-4 mb-0 h-100">
                <div class="card-body p-3">
                    <p class="mb-1 small">Total Realisasi</p>
                    <h5 class="mb-0 fw-bold">Rp {{ number_format($realisasi, 0, ',', '.') }}</h5>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card rounded-4 mb-0 h-100">
                <div class="card-body p-3">
                    <p class="mb-1 small">Sisa Anggaran</p>
                    <h5 class="mb-0 fw-bold">Rp {{ number_format($sisa, 0, ',', '.') }}</h5>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card rounded-4 mb-0 h-100">
                <div class="card-body p-3">
                    <p class="mb-1 small">Persentase Realisasi</p>
                    <h5 class="mb-0 fw-bold">{{ $persen }}%</h5>
                </div>
            </div>
        </div>
    </div>

    {{-- Progress Bar --}}
    <div class="card rounded-4 mb-4">
        <div class="card-body p-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <small class="fw-semibold">Realisasi Anggaran</small>
                <span class="badge bg-{{ $badgePenggunaan }}">{{ $statusPenggunaan }}</span>
            </div>
            <div class="progress" style="height: 10px;">
                <div class="progress-bar {{ $progressColor }}" role="progressbar" style="width: {{ min($persen, 100) }}%;" aria-valuenow="{{ $persen }}" aria-valuemin="0" aria-valuemax="100"></div>
            </div>
            <div class="d-flex justify-content-between mt-1">
                <small>Rp {{ number_format($realisasi, 0, ',', '.') }} terealisasi</small>
                <small>Rp {{ number_format($pagu, 0, ',', '.') }} total pagu</small>
            </div>
        </div>
    </div>

    <div class="row">
        {{-- Main Content (8 col) --}}
        <div class="col-xl-8">

            {{-- 3. Informasi Utama Pagu --}}
            <div class="card rounded-4 mb-4">
                <div class="card-body p-4">
                    <h6 class="mb-4 fw-bold"><i class="bi bi-info-circle me-2"></i>Informasi Pagu Anggaran</h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <small class="d-block">Tahun Anggaran</small>
                            <span class="fw-semibold">{{ $budget->year }}</span>
                        </div>
                        <div class="col-md-6">
                            <small class="d-block">Status Pagu</small>
                            @if(($budget->status_pagu ?? 'Aktif') == 'Aktif')
                                <span class="badge bg-success">Aktif</span>
                            @else
                                <span class="badge bg-secondary">Nonaktif</span>
                            @endif
                        </div>
                        <div class="col-12">
                            <small class="d-block">Uraian</small>
                            <span class="fw-semibold">{{ $budget->description }}</span>
                        </div>
                        <div class="col-12">
                            <small class="d-block">COA Lengkap</small>
                            <code class="fw-bold fs-6">{{ $budget->coa }}</code>
                        </div>
                        <div class="col-md-6">
                            <small class="d-block">Kode Akun</small>
                            <span class="fw-semibold">{{ $budget->account_code }}</span>
                        </div>
                        <div class="col-md-6">
                            <small class="d-block">Catatan</small>
                            <span>{{ $budget->catatan ?? '-' }}</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 4. Struktur Kode Anggaran --}}
            <div class="card rounded-4 mb-4">
                <div class="card-body p-4">
                    <h6 class="mb-3 fw-bold"><i class="bi bi-diagram-3 me-2"></i>Struktur Kode Anggaran</h6>

                    {{-- Visual COA --}}
                    <div class="d-flex flex-wrap gap-1 align-items-center mb-4">
                        <span class="badge bg-primary rounded-pill px-3 py-2">{{ $budget->program_code }}</span>
                        <span class="fw-bold">/</span>
                        <span class="badge bg-primary rounded-pill px-3 py-2">{{ $budget->activity_code }}</span>
                        <span class="fw-bold">/</span>
                        <span class="badge bg-primary rounded-pill px-3 py-2">{{ $budget->output_code }}</span>
                        <span class="fw-bold">/</span>
                        <span class="badge bg-primary rounded-pill px-3 py-2">{{ $budget->suboutput_code }}</span>
                        <span class="fw-bold">/</span>
                        <span class="badge bg-primary rounded-pill px-3 py-2">{{ $budget->component_code }}</span>
                        <span class="fw-bold">/</span>
                        <span class="badge bg-primary rounded-pill px-3 py-2">{{ $budget->subcomponent_code }}</span>
                        <span class="fw-bold">/</span>
                        <span class="badge bg-primary rounded-pill px-3 py-2">{{ $budget->account_code }}</span>
                        <span class="fw-bold">/</span>
                        <span class="badge bg-primary rounded-pill px-3 py-2">{{ $budget->item_code }}</span>
                    </div>

                    <div class="row row-cols-2 row-cols-md-4 g-3">
                        <div class="col">
                            <small class="d-block">Kode Program</small>
                            <span class="fw-semibold">{{ $budget->program_code ?? '-' }}</span>
                        </div>
                        <div class="col">
                            <small class="d-block">Kode Kegiatan</small>
                            <span class="fw-semibold">{{ $budget->activity_code ?? '-' }}</span>
                        </div>
                        <div class="col">
                            <small class="d-block">Kode Output</small>
                            <span class="fw-semibold">{{ $budget->output_code ?? '-' }}</span>
                        </div>
                        <div class="col">
                            <small class="d-block">Kode Suboutput</small>
                            <span class="fw-semibold">{{ $budget->suboutput_code ?? '-' }}</span>
                        </div>
                        <div class="col">
                            <small class="d-block">Kode Komponen</small>
                            <span class="fw-semibold">{{ $budget->component_code ?? '-' }}</span>
                        </div>
                        <div class="col">
                            <small class="d-block">Kode Subkomponen</small>
                            <span class="fw-semibold">{{ $budget->subcomponent_code ?? '-' }}</span>
                        </div>
                        <div class="col">
                            <small class="d-block">Kode Akun</small>
                            <span class="fw-semibold">{{ $budget->account_code ?? '-' }}</span>
                        </div>
                        <div class="col">
                            <small class="d-block">Kode Item</small>
                            <span class="fw-semibold">{{ $budget->item_code ?? '-' }}</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 5. Ringkasan Penggunaan Anggaran --}}
            <div class="card rounded-4 mb-4">
                <div class="card-body p-4">
                    <h6 class="mb-4 fw-bold"><i class="bi bi-graph-up me-2"></i>Penggunaan Anggaran</h6>
                    <div class="table-responsive">
                        <table class="table table-bordered mb-0">
                            <tbody>
                                <tr>
                                    <td class="fw-semibold" style="width: 40%;">Pagu Awal</td>
                                    <td>Rp {{ number_format($pagu, 0, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <td class="fw-semibold">Total Realisasi</td>
                                    <td>Rp {{ number_format($realisasi, 0, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <td class="fw-semibold">Sisa Anggaran</td>
                                    <td>Rp {{ number_format($sisa, 0, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <td class="fw-semibold">Persentase Penggunaan</td>
                                    <td>{{ $persen }}%</td>
                                </tr>
                                <tr>
                                    <td class="fw-semibold">Status Penggunaan</td>
                                    <td><span class="badge bg-{{ $badgePenggunaan }}">{{ $statusPenggunaan }}</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- 6. Riwayat Transaksi Terkait --}}
            <div class="card rounded-4 mb-4">
                <div class="card-body p-4">
                    <h6 class="mb-3 fw-bold"><i class="bi bi-clock-history me-2"></i>Riwayat Realisasi / Transaksi Terkait</h6>

                    {{-- Summary mini --}}
                    <div class="row row-cols-1 row-cols-sm-2 g-3 mb-3">
                        <div class="col">
                            <div class="card rounded-4 mb-0 h-100">
                                <div class="card-body p-3">
                                    <p class="mb-1 small">Jumlah Transaksi</p>
                                    <h6 class="mb-0 fw-bold">0</h6>
                                </div>
                            </div>
                        </div>
                        <div class="col">
                            <div class="card rounded-4 mb-0 h-100">
                                <div class="card-body p-3">
                                    <p class="mb-1 small">Total Nominal Realisasi</p>
                                    <h6 class="mb-0 fw-bold">Rp {{ number_format($realisasi, 0, ',', '.') }}</h6>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table id="tblTransaksi" class="table table-striped table-bordered" style="width:100%">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>No. Dokumen</th>
                                    <th>Uraian Transaksi</th>
                                    <th>Supplier / Mitra</th>
                                    <th>Nominal</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                {{-- Placeholder: akan diisi dari relasi transaksi --}}
                                {{-- Jika belum ada data, tampilkan empty state --}}
                            </tbody>
                        </table>
                    </div>

                    {{-- Empty State --}}
                    <div class="text-center py-4" id="emptyTransaksi">
                        <i class="bi bi-inbox fs-1 d-block mb-2 opacity-50"></i>
                        <p class="mb-0">Belum ada transaksi yang menggunakan pagu ini</p>
                    </div>
                </div>
            </div>

            {{-- 7. Catatan Tambahan --}}
            <div class="card rounded-4 mb-4">
                <div class="card-body p-4">
                    <h6 class="mb-3 fw-bold"><i class="bi bi-journal-text me-2"></i>Catatan Tambahan</h6>
                    @if($budget->catatan)
                        <p class="mb-0">{{ $budget->catatan }}</p>
                    @else
                        <p class="mb-0 opacity-50"><em>Belum ada catatan tambahan</em></p>
                    @endif
                </div>
            </div>
        </div>

        {{-- 8. Sidebar Ringkas (4 col) --}}
        <div class="col-xl-4">
            <div class="card rounded-4 mb-4 sticky-xl-top" style="top: 80px;">
                <div class="card-body p-4">
                    <h6 class="mb-4 fw-bold"><i class="bi bi-speedometer2 me-2"></i>Ringkasan Cepat</h6>

                    <div class="mb-3">
                        <small class="d-block">Status Pagu</small>
                        @if(($budget->status_pagu ?? 'Aktif') == 'Aktif')
                            <span class="badge bg-success">Aktif</span>
                        @else
                            <span class="badge bg-secondary">Nonaktif</span>
                        @endif
                    </div>

                    <div class="mb-3">
                        <small class="d-block">Persentase Realisasi</small>
                        <h5 class="fw-bold mb-1">{{ $persen }}%</h5>
                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar {{ $progressColor }}" role="progressbar" style="width: {{ min($persen, 100) }}%;"></div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <small class="d-block">Sisa Anggaran</small>
                        <span class="fw-bold">Rp {{ number_format($sisa, 0, ',', '.') }}</span>
                    </div>

                    <div class="mb-3">
                        <small class="d-block">Tahun Anggaran</small>
                        <span class="fw-bold">{{ $budget->year }}</span>
                    </div>

                    <div class="mb-3">
                        <small class="d-block">COA</small>
                        <code class="small">{{ $budget->coa }}</code>
                    </div>

                    <div class="mb-3">
                        <small class="d-block">Status Penggunaan</small>
                        <span class="badge bg-{{ $badgePenggunaan }}">{{ $statusPenggunaan }}</span>
                    </div>

                    <hr>
                    <div class="d-grid gap-2">
                        <a href="{{ route('budgets.edit', $budget->id) }}" class="btn btn-outline-warning btn-sm"><i class="bi bi-pencil me-1"></i>Edit Pagu</a>
                        <a href="{{ route('budgets.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Kembali ke Daftar</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
@push('script')
    <script src="{{ URL::asset('build/plugins/datatable/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/datatable/js/dataTables.bootstrap5.min.js') }}"></script>
    <script>
        $(document).ready(function() {
            let table = $('#tblTransaksi').DataTable({
                language: {
                    emptyTable: "Belum ada transaksi"
                }
            });
            // Hide empty state if DataTable has rows
            if (table.data().count() > 0) {
                $('#emptyTransaksi').hide();
            }
        });
    </script>
@endpush
