@extends('layouts.app')

@section('title', 'Dashboard - Pejabat Pembuat Komitmen')

@push('css')
    <style>
        .card-stats { transition: all 0.3s ease; }
        .card-stats:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important; }
        .icon-circle { width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 24px; }
        .nav-tabs .nav-link { font-weight: 600; color: #6c757d; border: none; padding: 1rem 1.5rem; border-bottom: 3px solid transparent; }
        .nav-tabs .nav-link.active { color: #0d6efd; background: transparent; border-color: #0d6efd; }
    </style>
@endpush

@section('content')

{{-- 1. HEADER & WELCOME BANNER --}}
<div class="d-flex flex-column mb-4 pb-3 border-bottom">
    <div class="mb-3">
        <h4 class="mb-1 fw-bold text-dark">
            @php
                $hour = date('H');
                $greeting = ($hour < 12) ? 'Selamat Pagi' : (($hour < 15) ? 'Selamat Siang' : (($hour < 18) ? 'Selamat Sore' : 'Selamat Malam'));
            @endphp
            {{ $greeting }}, {{ Auth::user()->name }}!
        </h4>
        <p class=" mb-0 fs-5">
            Ada <strong class="text-danger">
                {{ $kpi['kontrak_baru'] + $kpi['tagihan_bast'] + $kpi['pencairan'] }} Dokumen
            </strong> di meja Anda yang menunggu otorisasi hari ini.
        </p>
    </div>

    @if($alertModal)
    <div class="alert alert-danger border-0 bg-danger text-white alert-dismissible fade show shadow-sm mt-3 d-flex align-items-center mb-0">
        <i class="bi bi-exclamation-triangle-fill fs-4 me-3"></i> 
        <div>
            <strong>Peringatan Kritis:</strong> {{ $alertModal }}
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif
</div>

{{-- 2. KPI CARDS (METRIKS TUGAS) --}}
<div class="row g-4 mb-4">
    <!-- Verifikasi SPK -->
    <div class="col-sm-6 col-xl-3">
        <div class="card card-stats border-0 shadow-sm rounded-4 h-100 border-bottom border-warning border-4">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="icon-circle bg-warning bg-opacity-10 text-warning">
                        <i class="bi bi-file-earmark-plus-fill"></i>
                    </div>
                </div>
                <h3 class="fw-bold mb-1">{{ $kpi['kontrak_baru'] }} <span class="fs-6  fw-normal">Draft</span></h3>
                <h6 class="fw-bold text-dark">Verifikasi SPK Baru</h6>
                <p class=" small mb-0">Menunggu diaktifkan (PENDING_PPK).</p>
            </div>
        </div>
    </div>
    
    <!-- Verifikasi BAST -->
    <div class="col-sm-6 col-xl-3">
        <div class="card card-stats border-0 shadow-sm rounded-4 h-100 border-bottom border-primary border-4" style="border-bottom-color: #fd7e14 !important;">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="icon-circle bg-opacity-10" style="background-color: rgba(253, 126, 20, 0.1); color: #fd7e14;">
                        <i class="bi bi-receipt-cutoff"></i>
                    </div>
                </div>
                <h3 class="fw-bold mb-1">{{ $kpi['tagihan_bast'] }} <span class="fs-6  fw-normal">Tagihan</span></h3>
                <h6 class="fw-bold text-dark">Verifikasi BAST / Tagihan</h6>
                <p class=" small mb-0">Cek kesesuaian fisik lapangan.</p>
            </div>
        </div>
    </div>
    
    <!-- Otorisasi Pencairan -->
    <div class="col-sm-6 col-xl-3">
        <div class="card card-stats border-0 shadow-sm rounded-4 h-100 border-bottom border-danger border-4">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="icon-circle bg-danger bg-opacity-10 text-danger">
                        <i class="bi bi-pen-fill"></i>
                    </div>
                </div>
                <h3 class="fw-bold mb-1">{{ $kpi['pencairan'] }} <span class="fs-6  fw-normal">Dokumen</span></h3>
                <h6 class="fw-bold text-dark">Otorisasi Pencairan</h6>
                <p class=" small mb-0">SPP, NPI, & SP2D siap TTE.</p>
            </div>
        </div>
    </div>

    <!-- Sisa Pagu DIPA -->
    <div class="col-sm-6 col-xl-3">
        <div class="card card-stats border-0 shadow-sm rounded-4 h-100 border-bottom border-success border-4">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="icon-circle bg-success bg-opacity-10 text-success">
                        <i class="bi bi-safe2-fill"></i>
                    </div>
                </div>
                <h3 class="fw-bold mb-1 fs-4 text-truncate" title="Rp {{ number_format($kpi['sisa_pagu'], 0, ',', '.') }}">
                    Rp {{ number_format($kpi['sisa_pagu'], 0, ',', '.') }}
                </h3>
                <h6 class="fw-bold text-dark">Sisa Pagu DIPA {{ date('Y') }}</h6>
                <p class=" small mb-0">Dari total Rp {{ number_format($kpi['total_pagu'], 0, ',', '.') }}.</p>
            </div>
        </div>
    </div>
</div>

{{-- 3. VISUALISASI DATA (GRAFIK) --}}
<div class="row g-4 mb-4">
    <!-- Kolom Kiri: Doughnut Chart 40% -->
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-header bg-white p-4 border-bottom rounded-top-4">
                <h6 class="mb-0 fw-bold"><i class="bi bi-pie-chart text-success me-2"></i> Persentase Serapan DIPA</h6>
            </div>
            <div class="card-body p-4 d-flex justify-content-center align-items-center">
                <div style="height: 300px; width: 100%;">
                    <canvas id="serapanChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Kolom Kanan: Bar Chart 60% -->
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-header bg-white p-4 border-bottom rounded-top-4">
                <h6 class="mb-0 fw-bold"><i class="bi bi-bar-chart-steps text-primary me-2"></i> Serapan per Jenis Belanja</h6>
            </div>
            <div class="card-body p-4 mt-2">
                <div style="height: 280px; width: 100%;">
                    <canvas id="belanjaChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- 4. ACTIONABLE TABLES (MEJA KERJA) --}}
<div class="card border-0 shadow-sm rounded-4 mb-5">
    <div class="card-header bg-white p-0 border-bottom rounded-top-4">
        <ul class="nav nav-tabs px-3" id="workTableTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active fw-bold py-3 text-dark" id="kontrak-tab" data-bs-toggle="tab" data-bs-target="#kontrak" type="button" role="tab">
                    📄 Kontrak Baru ({{ $kpi['kontrak_baru'] }})
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-bold py-3 text-dark" id="tagihan-tab" data-bs-toggle="tab" data-bs-target="#tagihan" type="button" role="tab">
                    🧾 Tagihan BAST ({{ $kpi['tagihan_bast'] }})
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-bold py-3 text-dark" id="pencairan-tab" data-bs-toggle="tab" data-bs-target="#pencairan" type="button" role="tab">
                    🔏 Tanda Tangan Pencairan ({{ $kpi['pencairan'] }})
                </button>
            </li>
        </ul>
    </div>
    
    <div class="card-body p-4">
        <div class="tab-content">
            
            {{-- TAB 1: KONTRAK BARU --}}
            <div class="tab-pane fade show active" id="kontrak" role="tabpanel">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light  small">
                            <tr>
                                <th>Prioritas</th>
                                <th>Jenis</th>
                                <th>Nomor SPK & Pekerjaan</th>
                                <th>Nilai (Rp)</th>
                                <th>Vendor</th>
                                <th class="text-center">Aksi Cepat</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($tab_kontrak as $k)
                                <tr>
                                    <td><span class="badge bg-warning text-dark"><i class="bi bi-record-circle me-1"></i> Sedang</span></td>
                                    <td><span class="fw-bold text-secondary">Kontrak</span></td>
                                    <td>
                                        <div class="fw-bold text-dark">{{ $k->nomor_spk }}</div>
                                        <small class="">{{ Str::limit($k->nama_pekerjaan, 40) }}</small>
                                    </td>
                                    <td><span class="fw-bold text-success">Rp {{ number_format($k->nilai_total_kontrak, 0, ',', '.') }}</span></td>
                                    <td>{{ $k->vendor->nama_perusahaan ?? '-' }}</td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-outline-primary fw-bold btn-review" 
                                            data-title="Review Kontrak: {{ $k->nomor_spk }}"
                                            data-url-approve="{{ route('contracts.approve', $k->id) }}"
                                            data-url-reject="{{ route('contracts.reject', $k->id) }}"
                                            data-file="{{ asset('storage/' . $k->file_spk) }}"
                                            data-nominal="Rp {{ number_format($k->nilai_total_kontrak, 0, ',', '.') }}">
                                            <i class="bi bi-eye"></i> Review & TTD
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center py-4 ">Tidak ada Kontrak Baru yang menunggu verifikasi.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- TAB 2: TAGIHAN BAST --}}
            <div class="tab-pane fade" id="tagihan" role="tabpanel">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light  small">
                            <tr>
                                <th>Prioritas</th>
                                <th>Tipe Tagihan</th>
                                <th>Nomor Tagihan & Deskripsi</th>
                                <th>Nilai Bruto (Rp)</th>
                                <th>Pembuat</th>
                                <th class="text-center">Aksi Cepat</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($tab_tagihan as $t)
                                <tr>
                                    <td><span class="badge bg-warning text-dark"><i class="bi bi-record-circle me-1"></i> Sedang</span></td>
                                    <td><span class="fw-bold text-secondary">{{ $t->tipe_tagihan }}</span></td>
                                    <td>
                                        <div class="fw-bold text-dark">{{ $t->nomor_tagihan }}</div>
                                        <small class="">{{ Str::limit($t->deskripsi, 40) }}</small>
                                    </td>
                                    <td><span class="fw-bold text-success">Rp {{ number_format($t->total_bruto, 0, ',', '.') }}</span></td>
                                    <td>{{ $t->creator->name ?? '-' }}</td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-outline-primary fw-bold btn-review"
                                            data-title="Review Tagihan: {{ $t->nomor_tagihan }}"
                                            data-url-approve="#"
                                            data-url-reject="#"
                                            data-nominal="Rp {{ number_format($t->total_bruto, 0, ',', '.') }}">
                                            <i class="bi bi-eye"></i> Review & TTD
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center py-4 ">Tidak ada Tagihan / BAST yang menunggu verifikasi.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- TAB 3: PENCAIRAN --}}
            <div class="tab-pane fade" id="pencairan" role="tabpanel">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light  small">
                            <tr>
                                <th>Prioritas</th>
                                <th>Jenis Dokumen</th>
                                <th>Nomor Surat</th>
                                <th>Nilai (Rp)</th>
                                <th>Pembuat</th>
                                <th class="text-center">Aksi Cepat</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($tab_pencairan as $p)
                                <tr>
                                    <td>
                                        @if($p->prioritas == 'Tinggi')
                                            <span class="badge bg-danger"><i class="bi bi-record-circle me-1"></i> Tinggi</span>
                                        @else
                                            <span class="badge bg-warning text-dark"><i class="bi bi-record-circle me-1"></i> Sedang</span>
                                        @endif
                                    </td>
                                    <td><span class="fw-bold text-secondary">{{ $p->jenis }}</span></td>
                                    <td>
                                        <div class="fw-bold text-dark">{{ $p->nomor }}</div>
                                    </td>
                                    <td>
                                        @if($p->nilai > 0)
                                            <span class="fw-bold text-success">Rp {{ number_format($p->nilai, 0, ',', '.') }}</span>
                                        @else
                                            <span class=" fst-italic">Mengikuti SPP</span>
                                        @endif
                                    </td>
                                    <td>{{ $p->pembuat }}</td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-outline-primary fw-bold btn-review"
                                            data-title="Review Pencairan: {{ $p->nomor }}"
                                            data-url-approve="{{ $p->url_approve }}"
                                            data-url-reject="{{ $p->url_reject }}"
                                            data-nominal="{{ $p->nilai > 0 ? 'Rp '.number_format($p->nilai,0,',','.') : '-' }}">
                                            <i class="bi bi-eye"></i> Review & TTD
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center py-4 ">Tidak ada Dokumen Pencairan yang menunggu TTD Anda.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>

{{-- MODAL REVIEW DOKUMEN --}}
<div class="modal fade" id="reviewModal" tabindex="-1" aria-labelledby="reviewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header bg-light border-bottom">
                <h5 class="modal-title fw-bold" id="reviewModalLabel">Review Dokumen</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div class="row g-0 h-100">
                    <!-- Sisi Kiri: PDF Preview -->
                    <div class="col-lg-8 border-end bg-light p-3 d-flex flex-column" style="min-height: 500px;">
                        <span class="fw-bold  mb-2"><i class="bi bi-file-pdf text-danger"></i> Pratinjau Dokumen PDF</span>
                        <iframe id="pdfPreview" src="" class="w-100 flex-grow-1 border rounded bg-white shadow-sm" style="display: none;"></iframe>
                        <div id="pdfNoFile" class="w-100 flex-grow-1 border rounded bg-white shadow-sm d-flex justify-content-center align-items-center flex-column " style="display: flex;">
                            <i class="bi bi-file-earmark-x fs-1 mb-2"></i>
                            <p class="mb-0">File dokumen belum diunggah atau tidak tersedia untuk pratinjau.</p>
                        </div>
                    </div>
                    
                    <!-- Sisi Kanan: Detail & Form Aksi -->
                    <div class="col-lg-4 p-4 d-flex flex-column">
                        <div class="mb-4">
                            <h6 class="fw-bold  text-uppercase small mb-3">Informasi Utama</h6>
                            <div class="d-flex justify-content-between mb-2 pb-2 border-bottom">
                                <span class="text-secondary">Nomor Seri</span>
                                <span class="fw-bold text-dark text-end" id="reviewNoSeri">-</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2 pb-2 border-bottom">
                                <span class="text-secondary">Total Nilai</span>
                                <span class="fw-bold text-success fs-5 text-end" id="reviewNilai">Rp 0</span>
                            </div>
                            
                            <div class="mt-4 p-3 bg-warning bg-opacity-10 rounded border border-warning">
                                <span class="fw-bold text-warning-emphasis"><i class="bi bi-info-circle me-1"></i> Catatan Pemeriksa Sebelumnya:</span>
                                <p class="mb-0 mt-2 small text-dark fst-italic">"Dokumen sudah sesuai dengan fisik dan pagu masih tersedia."</p>
                            </div>
                        </div>

                        <div class="mt-auto pt-3 border-top">
                            <!-- Form Tolak -->
                            <form id="formReject" method="POST" action="" class="mb-3">
                                @csrf
                                <div class="mb-2">
                                    <label class="form-label fw-bold small text-danger">Alasan Penolakan (Wajib isi jika ditolak):</label>
                                    <textarea name="notes" class="form-control form-control-sm" rows="2" placeholder="Sebutkan bagian mana yang perlu diperbaiki..."></textarea>
                                </div>
                                <button type="button" class="btn btn-outline-danger w-100 fw-bold" onclick="submitReject()"><i class="bi bi-arrow-return-left me-1"></i> Kembalikan / Tolak</button>
                            </form>
                            
                            <!-- Form Setujui -->
                            <form id="formApprove" method="POST" action="">
                                @csrf
                                <button type="button" class="btn btn-primary w-100 fw-bold py-2 btn-lg shadow-sm" onclick="submitApprove()">
                                    <i class="bi bi-check-circle me-1"></i> Setujui
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('script')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function () {
        
        // Modal & Tombol Aksi Script
        const reviewModal = new bootstrap.Modal(document.getElementById('reviewModal'));
        
        $('.btn-review').on('click', function() {
            // Ambil data dari attribute tombol
            let title = $(this).data('title');
            let nominal = $(this).data('nominal');
            let urlApprove = $(this).data('url-approve');
            let urlReject = $(this).data('url-reject');
            let fileUrl = $(this).data('file');

            // Apply ke modal target
            $('#reviewModalLabel').text(title);
            $('#reviewNoSeri').text(title.split(': ')[1] || '-');
            $('#reviewNilai').text(nominal);
            
            // Set form actions
            $('#formApprove').attr('action', urlApprove);
            $('#formReject').attr('action', urlReject);

            // Tampilkan iFrame jika ada file
            if (fileUrl && fileUrl !== 'http://localhost/storage/') {
                $('#pdfPreview').attr('src', fileUrl).show();
                $('#pdfNoFile').hide();
            } else {
                $('#pdfPreview').hide().attr('src', '');
                $('#pdfNoFile').show();
            }

            reviewModal.show();
        });

        // Submit helper (supaya event button biasa bisa submit form)
        window.submitApprove = function() {
            if(confirm('Apakah Anda yakin menyetujui dokumen ini?')) {
                $('#formApprove').submit();
            }
        }
        
        window.submitReject = function() {
            let notes = $('#formReject textarea[name="notes"]').val();
            if(notes.trim() === '') {
                alert('Alasan penolakan / perbaikan wajib diisi!');
                return;
            }
            if(confirm('Kembalikan dokumen ini untuk direvisi?')) {
                $('#formReject').submit();
            }
        }

        // ===========================
        // CHART.JS RENDERER
        // ===========================

        // Doughnut Chart (Persentase Serapan)
        const ctxSerapan = document.getElementById('serapanChart').getContext('2d');
        new Chart(ctxSerapan, {
            type: 'doughnut',
            data: {
                labels: {!! json_encode($chart_serapan_labels) !!},
                datasets: [{
                    data: {!! json_encode($chart_serapan_data) !!},
                    backgroundColor: ['#198754', '#dee2e6'], // Hijau untuk serapan, abu untuk sisa
                    borderWidth: 0,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let val = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(context.raw);
                                return context.label + ': ' + val;
                            }
                        }
                    }
                },
                cutout: '70%' // Bikin chart melingkar cakep
            }
        });

        // Bar Chart Horizontal (Serapan per Belanja)
        const ctxBelanja = document.getElementById('belanjaChart').getContext('2d');
        new Chart(ctxBelanja, {
            type: 'bar',
            data: {
                labels: {!! json_encode($chart_bar_labels) !!},
                datasets: [
                    {
                        label: 'Terserap',
                        data: {!! json_encode($chart_bar_realisasi) !!},
                        backgroundColor: '#fd7e14' // Oranye segar
                    },
                    {
                        label: 'Pagu DIPA',
                        data: {!! json_encode($chart_bar_pagu) !!},
                        backgroundColor: '#e9ecef',
                        skipNull: true,
                    }
                ]
            },
            options: {
                indexAxis: 'y', // Mengubah menjadi horizontal chart
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'top' },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ' + new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(context.raw);
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        stacked: false, 
                        ticks: {
                            callback: function(value) {
                                if (value >= 1e9) {
                                    return 'Rp ' + (value / 1e9) + ' M';
                                } else if (value >= 1e6) {
                                    return 'Rp ' + (value / 1e6) + ' Jt';
                                }
                                return value;
                            }
                        }
                    },
                    y: {
                        stacked: false
                    }
                }
            }
        });
    });
</script>
@endpush
