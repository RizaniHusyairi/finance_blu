@extends('layouts.app')

@section('title', 'Rincian Tagihan (Siap SPP)')

@php
    $detailKontrak = $tagihan->detailKontrak;
    $termin = $detailKontrak->kontrakTermin ?? $detailKontrak->termin ?? null;
    $kontrak = $termin->kontrak ?? null;
    $vendor = $kontrak->vendor ?? null;
    $rekening = $vendor->rekening->first() ?? null;
    $potonganTagihans = collect($tagihan->potonganTagihan ?? $tagihan->potongans ?? []);

    $lampiranDokumen = collect([
        ['label' => 'File BAST', 'path' => $detailKontrak->file_bast ?? null],
        ['label' => 'File BAPP', 'path' => $detailKontrak->file_bapp ?? null],
        ['label' => 'File BAP', 'path' => $detailKontrak->file_bap ?? null],
        ['label' => 'File Invoice', 'path' => $detailKontrak->file_invoice ?? null],
        ['label' => 'File Kwitansi', 'path' => $detailKontrak->file_kwitansi ?? null],
        ['label' => 'Lampiran Lainnya', 'path' => $detailKontrak->file_lampiran_lainnya ?? null],
    ])->filter(fn ($item) => !empty($item['path']))->values();
@endphp

@section('content')
    <x-page-title title="Rincian Tagihan (Siap SPP)" subtitle="Pemeriksaan Operator BLU" />

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

    <div class="row">
        <div class="col-12 col-lg-10 mx-auto">
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
                <a href="{{ route('operator.spp.kontrak.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Kembali ke Antrean
                </a>
                <span class="badge bg-success px-3 py-2 fs-6">
                    <i class="bi bi-patch-check-fill me-1"></i> VERIFIKASI PPK SELESAI
                </span>
            </div>

            <div class="card rounded-4 mb-4">
                <div class="card-header bg-transparent border-bottom-0 pt-4 px-4">
                    <h5 class="mb-0 fw-bold">Informasi Kontrak & Vendor</h5>
                </div>
                <div class="card-body px-4 pb-4">
                    <div class="row g-4">
                        <div class="col-12 col-lg-6">
                            <div class="border rounded-4 p-4 h-100">
                                <div class="mb-3">
                                    <div class="text-muted small mb-1">Nomor BAST</div>
                                    <div class="fw-semibold">{{ $detailKontrak->nomor_bast ?? '-' }}</div>
                                </div>
                                <div class="mb-3">
                                    <div class="text-muted small mb-1">Nomor SPK</div>
                                    <div class="fw-semibold">{{ $kontrak->nomor_spk ?? '-' }}</div>
                                </div>
                                <div class="mb-3">
                                    <div class="text-muted small mb-1">Nama Pekerjaan</div>
                                    <div class="fw-semibold">{{ $kontrak->nama_pekerjaan ?? '-' }}</div>
                                </div>
                                <div>
                                    <div class="text-muted small mb-1">Keterangan Termin</div>
                                    <div class="fw-semibold">
                                        Pembayaran Termin Ke-{{ $termin->termin_ke ?? '-' }}
                                        @if(!empty($termin->persentase))
                                            <span class="text-muted">({{ $termin->persentase }}%)</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-lg-6">
                            <div class="border rounded-4 p-4 h-100">
                                <div class="mb-3">
                                    <div class="text-muted small mb-1">Nama Perusahaan</div>
                                    <div class="fw-semibold">{{ $vendor->nama_perusahaan ?? '-' }}</div>
                                </div>
                                <div class="mb-3">
                                    <div class="text-muted small mb-1">NPWP</div>
                                    <div class="fw-semibold">{{ $vendor->npwp ?? '-' }}</div>
                                </div>
                                <div>
                                    <div class="text-muted small mb-1">Alamat</div>
                                    <div class="fw-semibold">{{ $vendor->alamat ?? '-' }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card rounded-4 mb-4">
                <div class="card-header bg-light border-bottom-0 py-3 px-4">
                    <h5 class="mb-0 fw-bold">Rincian Keuangan & Potongan</h5>
                </div>
                <div class="card-body px-4 pb-4">
                    <div class="list-group list-group-flush">
                        <div class="list-group-item px-0 py-3 d-flex justify-content-between align-items-center">
                            <div>
                                <div class="fw-semibold">Nilai Bruto (Kotor)</div>
                                <small class="text-muted">Nilai tagihan sebelum potongan dan pajak</small>
                            </div>
                            <div class="fw-bold fs-5">
                                Rp {{ number_format($tagihan->total_bruto, 0, ',', '.') }}
                            </div>
                        </div>

                        <div class="list-group-item px-0 py-3">
                            <div class="fw-semibold mb-3">Daftar Potongan / Pajak</div>

                            @if($potonganTagihans->isNotEmpty())
                                <div class="table-responsive">
                                    <table class="table table-sm table-borderless align-middle mb-0">
                                        <tbody>
                                            @foreach($potonganTagihans as $potongan)
                                                <tr>
                                                    <td class="ps-0">
                                                        <span class="fw-semibold">{{ $potongan->jenis_potongan ?? 'Potongan' }}</span>
                                                        @if(!empty($potongan->deskripsi))
                                                            <div class="text-muted small">{{ $potongan->deskripsi }}</div>
                                                        @endif
                                                    </td>
                                                    <td class="pe-0 text-end">
                                                        <span class="fw-bold text-danger">
                                                            Rp {{ number_format($potongan->nominal_potongan ?? 0, 0, ',', '.') }}
                                                        </span>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="text-muted">Tidak ada data potongan pada tagihan ini.</div>
                            @endif
                        </div>

                        <div class="list-group-item px-0 py-3 d-flex justify-content-between align-items-center">
                            <div>
                                <div class="fw-semibold">Total Potongan</div>
                                <small class="text-muted">Akumulasi seluruh potongan tagihan</small>
                            </div>
                            <div class="fw-bold fs-5 text-danger">
                                Rp {{ number_format($tagihan->total_potongan, 0, ',', '.') }}
                            </div>
                        </div>

                        <div class="list-group-item px-0 pt-4 pb-2">
                            <div class="text-muted text-uppercase small fw-semibold mb-2">Nilai Netto (Dibayarkan)</div>
                            <div class="fw-bolder text-success" style="font-size: 2.25rem; line-height: 1.1;">
                                Rp {{ number_format($tagihan->total_netto, 0, ',', '.') }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card rounded-4 mb-4">
                <div class="card-header bg-transparent border-bottom-0 pt-4 px-4">
                    <h5 class="mb-0 fw-bold">Informasi Rekening Tujuan & Dokumen Pendukung</h5>
                </div>
                <div class="card-body px-4 pb-4">
                    <div class="row g-4">
                        <div class="col-12 col-lg-6">
                            <div class="border rounded-4 p-4 h-100">
                                <h6 class="fw-bold mb-3">Rekening Vendor</h6>
                                <div class="mb-3">
                                    <div class="text-muted small mb-1">Nama Bank</div>
                                    <div class="fw-semibold">{{ $rekening->nama_bank ?? '-' }}</div>
                                </div>
                                <div class="mb-3">
                                    <div class="text-muted small mb-1">Nomor Rekening</div>
                                    <div class="fw-semibold">{{ $rekening->nomor_rekening ?? '-' }}</div>
                                </div>
                                <div>
                                    <div class="text-muted small mb-1">Atas Nama Rekening</div>
                                    <div class="fw-semibold">{{ $rekening->nama_rekening ?? '-' }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-lg-6">
                            <div class="border rounded-4 p-4 h-100">
                                <h6 class="fw-bold mb-3">Lampiran Dokumen</h6>

                                @if($lampiranDokumen->isNotEmpty())
                                    <div class="list-group">
                                        @foreach($lampiranDokumen as $lampiran)
                                            <a href="{{ \Illuminate\Support\Facades\Storage::url($lampiran['path']) }}"
                                               target="_blank"
                                               rel="noopener noreferrer"
                                               class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                                <span class="d-flex align-items-center gap-2">
                                                    <i class="bi bi-file-earmark-pdf text-danger"></i>
                                                    <span>{{ $lampiran['label'] }}</span>
                                                </span>
                                                <span class="btn btn-sm btn-outline-secondary">Buka</span>
                                            </a>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="alert alert-light border mb-0">
                                        Belum ada dokumen pendukung yang terlampir pada tagihan ini.
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end pb-2">
                <a href="{{ route('operator.spp.kontrak.create', $tagihan->id) }}" class="btn btn-primary btn-lg">
                    [📝 Lanjut Buat Dokumen SPP]
                </a>
            </div>
        </div>
    </div>
@endsection
