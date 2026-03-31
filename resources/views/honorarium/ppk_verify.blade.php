@extends('layouts.app')

@section('title', 'Verifikasi Dokumen Honorarium')

@php
    $totalBruto = $tagihan->detailHonorarium->sum('nilai_honor');
    $totalPph = $tagihan->detailHonorarium->sum('pph');
    $totalNetto = $tagihan->detailHonorarium->sum(function ($detail) {
        return (float) $detail->nilai_honor - (float) $detail->pph;
    });

    $dokumenPendukung = collect($dokumenPendukung ?? [])->filter(function ($dokumen) {
        return !empty($dokumen['label']) && (!empty($dokumen['url']) || !empty($dokumen['path']));
    })->values();
@endphp

@push('css')
    <style>
        .honor-summary-card {
            background: linear-gradient(135deg, rgba(13, 110, 253, 0.08), rgba(13, 202, 240, 0.12));
        }

        .honor-stat-label {
            letter-spacing: 0.08em;
            font-size: 0.72rem;
        }

        .honor-stat-value {
            font-size: 1.8rem;
            line-height: 1.1;
        }

        .honor-doc-link {
            transition: all 0.2s ease;
        }

        .honor-doc-link:hover {
            transform: translateY(-1px);
        }
    </style>
@endpush

@section('content')
    <div class="row">
        <div class="col-12 col-lg-10 mx-auto">
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
                <div>
                    <a href="{{ route('honorarium.ppk.pending') }}" class="btn btn-sm btn-outline-secondary mb-3">
                        <i class="bi bi-arrow-left me-1"></i> Kembali
                    </a>
                    <h4 class="mb-1 fw-bold">Verifikasi Tagihan Honorarium</h4>
                    <p class="text-muted mb-0">Telaah rincian penerima honorarium dan putuskan tindak lanjut dokumen ini.</p>
                </div>
                <div class="text-lg-end">
                    <span class="badge bg-warning text-dark px-3 py-2 fs-6">
                        <i class="bi bi-hourglass-split me-1"></i> MENUNGGU VERIFIKASI PPK
                    </span>
                </div>
            </div>

            @if(session('success'))
                <div class="alert alert-success border-0 bg-success alert-dismissible fade show">
                    <div class="text-white">{{ session('success') }}</div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger border-0 bg-danger alert-dismissible fade show">
                    <div class="text-white">{{ session('error') }}</div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger border-0 bg-danger alert-dismissible fade show">
                    <ul class="text-white mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <div class="card rounded-4">
                <div class="card-header bg-transparent border-bottom-0 pt-4 px-4">
                    <h5 class="mb-0 fw-bold">Informasi Kegiatan & Dokumen Pendukung</h5>
                </div>
                <div class="card-body px-4 pb-4">
                    <div class="row g-4">
                        <div class="col-12 col-lg-6">
                            <div class="border rounded-4 p-4 h-100">
                                <div class="mb-4">
                                    <div class="text-muted small mb-1">Nomor Tagihan</div>
                                    <div class="fw-bold fs-5 text-primary">{{ $tagihan->nomor_tagihan }}</div>
                                </div>
                                <div class="mb-4">
                                    <div class="text-muted small mb-1">Tanggal Diajukan</div>
                                    <div class="fw-semibold">{{ optional($tagihan->created_at)->format('d F Y, H:i') ?? '-' }}</div>
                                </div>
                                <div>
                                    <div class="text-muted small mb-1">Uraian / Deskripsi Kegiatan</div>
                                    <div class="fw-semibold lh-lg">{{ $tagihan->deskripsi }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-lg-6">
                            <div class="border rounded-4 p-4 h-100">
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <h6 class="mb-0 fw-bold">Dokumen Lampiran</h6>
                                    <span class="badge bg-light text-dark border">{{ $dokumenPendukung->count() }} file</span>
                                </div>

                                @if($dokumenPendukung->isNotEmpty())
                                    <div class="list-group list-group-flush">
                                        @foreach($dokumenPendukung as $dokumen)
                                            @php
                                                $dokumenUrl = $dokumen['url'] ?? \Illuminate\Support\Facades\Storage::url($dokumen['path']);
                                            @endphp
                                            <a href="{{ $dokumenUrl }}"
                                               target="_blank"
                                               rel="noopener noreferrer"
                                               class="list-group-item list-group-item-action border rounded-3 mb-2 honor-doc-link">
                                                <div class="d-flex align-items-center justify-content-between gap-3">
                                                    <div class="d-flex align-items-center gap-3">
                                                        <div class="text-danger fs-4">
                                                            <i class="bi bi-file-earmark-pdf-fill"></i>
                                                        </div>
                                                        <div>
                                                            <div class="fw-semibold">{{ $dokumen['label'] }}</div>
                                                            <small class="text-muted">{{ $dokumen['keterangan'] ?? 'Buka dokumen di tab baru' }}</small>
                                                        </div>
                                                    </div>
                                                    <span class="btn btn-sm btn-outline-primary">Buka</span>
                                                </div>
                                            </a>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="alert alert-light border mb-0">
                                        <div class="d-flex align-items-start gap-3">
                                            <i class="bi bi-folder2-open text-secondary fs-4"></i>
                                            <div>
                                                <div class="fw-semibold mb-1">Belum ada lampiran yang ditampilkan</div>
                                                <div class="text-muted small">Siapkan array <code>$dokumenPendukung</code> dari controller agar tombol SK, daftar hadir, atau lampiran lainnya muncul di sini.</div>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card rounded-4 mt-4">
                <div class="card-header bg-transparent border-bottom-0 pt-4 px-4">
                    <h5 class="mb-0 fw-bold">Rincian Penerima Honor</h5>
                </div>
                <div class="card-body px-4 pb-4">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-center" style="width: 70px;">No</th>
                                    <th>Nama Personel & Jabatan</th>
                                    <th class="text-end">Nilai Honor (Bruto)</th>
                                    <th class="text-end">Potongan PPh</th>
                                    <th class="text-end">Nilai Bersih (Netto)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($tagihan->detailHonorarium as $detail)
                                    @php
                                        $netto = (float) $detail->nilai_honor - (float) $detail->pph;
                                    @endphp
                                    <tr>
                                        <td class="text-center fw-semibold">{{ $loop->iteration }}</td>
                                        <td>
                                            <div class="fw-semibold">{{ $detail->nama_personel ?? '-' }}</div>
                                            <div class="text-muted small">{{ $detail->jabatan ?: 'Jabatan belum diisi' }}</div>
                                        </td>
                                        <td class="text-end fw-semibold">
                                            Rp {{ number_format($detail->nilai_honor, 0, ',', '.') }}
                                        </td>
                                        <td class="text-end">
                                            <span class="fw-semibold text-danger">
                                                Rp {{ number_format($detail->pph, 0, ',', '.') }}
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <span class="fw-bold text-success">
                                                Rp {{ number_format($netto, 0, ',', '.') }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">Belum ada rincian penerima honor.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card rounded-4 mt-4 honor-summary-card border-0">
                <div class="card-body p-4 p-lg-5">
                    <div class="row g-4 align-items-end">
                        <div class="col-12 col-xl-7">
                            <div class="row g-3">
                                <div class="col-12 col-md-4">
                                    <div class="bg-white rounded-4 p-4 shadow-sm h-100">
                                        <div class="text-uppercase text-muted fw-semibold honor-stat-label mb-2">Total Bruto</div>
                                        <div class="fw-bolder text-dark honor-stat-value">Rp {{ number_format($totalBruto, 0, ',', '.') }}</div>
                                    </div>
                                </div>
                                <div class="col-12 col-md-4">
                                    <div class="bg-white rounded-4 p-4 shadow-sm h-100">
                                        <div class="text-uppercase text-muted fw-semibold honor-stat-label mb-2">Total PPh</div>
                                        <div class="fw-bolder text-danger honor-stat-value">Rp {{ number_format($totalPph, 0, ',', '.') }}</div>
                                    </div>
                                </div>
                                <div class="col-12 col-md-4">
                                    <div class="bg-white rounded-4 p-4 shadow-sm h-100">
                                        <div class="text-uppercase text-muted fw-semibold honor-stat-label mb-2">Total Netto</div>
                                        <div class="fw-bolder text-success honor-stat-value">Rp {{ number_format($totalNetto, 0, ',', '.') }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-xl-5">
                            <div class="bg-white rounded-4 p-4 shadow-sm">
                                <div class="text-muted small mb-3">Keputusan Verifikasi</div>
                                <div class="d-flex flex-column flex-md-row gap-3">
                                    <button type="button"
                                            class="btn btn-outline-danger flex-fill"
                                            data-bs-toggle="modal"
                                            data-bs-target="#rejectHonorModal">
                                        <i class="bi bi-arrow-counterclockwise me-1"></i> Kembalikan (Revisi)
                                    </button>

                                    <form action="{{ route('honorarium.approve-ppk', $tagihan->id) }}" method="POST" id="approveHonorForm" class="flex-fill">
                                        @csrf
                                        <button type="button"
                                                class="btn btn-primary w-100 fw-bold"
                                                onclick="confirmHonorApproval()">
                                            <i class="bi bi-check-circle me-1"></i> Setujui & Teruskan ke SPP
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="rejectHonorModal" tabindex="-1" aria-labelledby="rejectHonorModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form action="{{ route('honorarium.reject-ppk', $tagihan->id) }}" method="POST" class="modal-content border-0 rounded-4">
                @csrf
                <div class="modal-header bg-danger text-white border-0">
                    <h5 class="modal-title fw-bold" id="rejectHonorModalLabel">Kembalikan untuk Revisi</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="text-muted mb-3">Tuliskan catatan revisi yang perlu ditindaklanjuti sebelum tagihan honorarium ini dapat diproses lebih lanjut.</p>
                    <div class="mb-0">
                        <label for="catatan_revisi" class="form-label fw-semibold">Catatan Revisi <span class="text-danger">*</span></label>
                        <textarea name="catatan_revisi"
                                  id="catatan_revisi"
                                  rows="5"
                                  class="form-control"
                                  placeholder="Contoh: lampiran SK belum lengkap, nominal PPh perlu disesuaikan, atau data penerima perlu diperbaiki."
                                  required>{{ old('catatan_revisi') }}</textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 bg-light">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger">Kirim Revisi</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('script')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function confirmHonorApproval() {
            const form = document.getElementById('approveHonorForm');

            if (typeof Swal === 'undefined') {
                if (confirm('Setujui tagihan honorarium ini dan teruskan ke proses SPP?')) {
                    form.submit();
                }
                return;
            }

            Swal.fire({
                title: 'Setujui tagihan honorarium ini?',
                text: 'Setelah disetujui, dokumen akan diteruskan ke proses SPP.',
                icon: 'question',
                showCancelButton: true,
                reverseButtons: true,
                confirmButtonText: '<i class="bi bi-check-circle me-1"></i> Ya, setujui',
                cancelButtonText: 'Batal',
                customClass: {
                    confirmButton: 'btn btn-primary px-4',
                    cancelButton: 'btn btn-light px-4',
                    actions: 'gap-3'
                },
                buttonsStyling: false
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        }
    </script>
@endpush
