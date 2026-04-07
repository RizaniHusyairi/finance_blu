@extends('layouts.app')
@section('title', 'Manajemen SPP Kontrak')

@php
    $vendor = $kontrak?->vendor;
    $rekening = $vendor?->rekening?->first();
    $dipa = $kontrak?->dipa;
    $activeRevision = $dipa?->activeRevision;
    $selectedBudgetItem = $sppModel?->dipaRevisionItem ?? $budgetItems->first();
    $potonganTagihans = collect($tagihan->potonganTagihan ?? $tagihan->potongans ?? []);
    $potonganAngsuranUm = $potonganTagihans->firstWhere('jenis_potongan', 'ANGSURAN_UANG_MUKA');
    $potonganPajak = $potonganTagihans->filter(fn ($item) => $item->jenis_potongan !== 'ANGSURAN_UANG_MUKA');
    $potonganPajakForm = $potonganPajak->map(fn ($item) => [
        'id' => $item->pajak_id,
        'dpp' => (float) ($item->dpp ?? 0),
        'nominal' => (float) ($item->nominal_potongan ?? 0),
    ])->values();
    $pajakOptionsSppData = collect($pajaks ?? [])->map(fn ($pj) => [
        'id' => $pj->id,
        'text' => ($pj->jenis_pajak ?? $pj->nama_pajak) . ' (' . $pj->persentase . '%)',
        'tarif' => (float) $pj->persentase,
    ])->values();
    $isPelunasan = ($termin->jenis_termin ?? null) === 'PELUNASAN';
    $ebillingPath = optional(optional($sppModel)->arsipDokumen?->firstWhere('jenis_dokumen', 'E_BILLING'))->path_file;
    $lampiranDokumen = collect([
        ['label' => 'BAST', 'path' => $tagihan->detailKontrak->file_bast ?? null, 'show' => $isPelunasan],
        ['label' => 'BAPP', 'path' => $tagihan->detailKontrak->file_bapp ?? null, 'show' => true],
        ['label' => 'BAP', 'path' => $tagihan->detailKontrak->file_bap ?? null, 'show' => true],
        ['label' => 'Invoice', 'path' => $tagihan->detailKontrak->file_invoice ?? null, 'show' => true],
        ['label' => 'Kwitansi', 'path' => $tagihan->detailKontrak->file_kwitansi ?? null, 'show' => true],
        ['label' => 'Faktur Pajak', 'path' => $tagihan->detailKontrak->file_faktur_pajak ?? null, 'show' => true],
        ['label' => 'E-Billing SPP', 'path' => $ebillingPath, 'show' => true],
        ['label' => 'Lampiran Lainnya', 'path' => $tagihan->detailKontrak->file_lampiran_lainnya ?? null, 'show' => true],
    ])->filter(fn ($item) => $item['show'] && !empty($item['path']))->values();
    $statusTagihanClass = match ($tagihan->status) {
        'READY_FOR_SPP' => 'bg-info',
        'PROSES_SPP' => 'bg-primary',
        'SPP_TERBIT' => 'bg-success',
        default => 'bg-secondary',
    };
    $statusSppLabel = $sppModel->status_spp ?? 'Belum Dibuat';
    $statusSppClass = match ($statusSppLabel) {
        'Belum Dibuat' => 'bg-warning text-dark',
        'Menunggu Verifikasi' => 'bg-warning text-dark',
        'Disetujui PPK' => 'bg-success',
        'Revisi' => 'bg-danger',
        default => 'bg-info',
    };
@endphp

@section('content')
    <x-page-title title="Pembuatan SPP" subtitle="Detail Tagihan Kontrak" />

    @if(session('success'))
        <div class="alert alert-success border-0 bg-success alert-dismissible fade show">
            <div class="text-white">{{ session('success') }}</div>
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
        <div class="col-12">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-start gap-4">
                        <div>
                            <div class="text-muted small text-uppercase fw-semibold mb-2">Ringkasan Kerja Operator BLU</div>
                            <h4 class="mb-2 fw-bold">{{ $kontrak->nama_pekerjaan ?? $tagihan->deskripsi }}</h4>
                            <div class="d-flex flex-wrap gap-2">
                                <span class="badge {{ $statusTagihanClass }} px-3 py-2">{{ $tagihan->status }}</span>
                                <span class="badge {{ $statusSppClass }} px-3 py-2">{{ $statusSppLabel }}</span>
                                @if($termin)
                                    <span class="badge bg-light text-dark border px-3 py-2">
                                        Termin {{ $termin->termin_ke ?? '-' }} @if(!empty($termin->persentase)) / {{ $termin->persentase }}%@endif
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="d-flex flex-wrap gap-2 justify-content-lg-end">
                            <a href="{{ route('spps.kontrak.index') }}" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left me-1"></i> Kembali
                            </a>
                            @if($sppModel)
                                <a href="{{ route('spps.cetak-pdf', $sppModel->id) }}" target="_blank" class="btn btn-outline-danger">
                                    <i class="bi bi-file-earmark-pdf me-1"></i> Cetak PDF
                                </a>
                            @endif
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalSppKontrak">
                                <i class="bi bi-pencil-square me-1"></i> {{ $sppModel ? 'Edit SPP' : 'Buat SPP' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-md-6 col-xl-3">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body p-4">
                            <div class="text-muted small text-uppercase fw-semibold mb-2">Nomor Tagihan</div>
                            <div class="fw-bold fs-5 text-primary">{{ $tagihan->nomor_tagihan }}</div>
                            <div class="small text-muted mt-2">Identitas utama tagihan yang akan diproses menjadi SPP.</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-xl-3">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body p-4">
                            <div class="text-muted small text-uppercase fw-semibold mb-2">Nomor SPK / Kontrak</div>
                            <div class="fw-bold fs-5">{{ $kontrak->nomor_spk ?? '-' }}</div>
                            <div class="small text-muted mt-2">Dokumen kontrak induk untuk tagihan ini.</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-xl-3">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body p-4">
                            <div class="text-muted small text-uppercase fw-semibold mb-2">Nilai Bruto</div>
                            <div class="fw-bold fs-4 text-dark">Rp {{ number_format($tagihan->total_bruto, 0, ',', '.') }}</div>
                            <div class="small text-muted mt-2">Nilai sebelum potongan dan pajak.</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-xl-3">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body p-4">
                            <div class="text-muted small text-uppercase fw-semibold mb-2">Nilai Netto</div>
                            <div class="fw-bold fs-4 text-success">Rp {{ number_format($tagihan->total_netto, 0, ',', '.') }}</div>
                            <div class="small text-muted mt-2">Nilai pembayaran bersih yang akan dicairkan.</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-white border-0 pt-4 px-4">
                            <h5 class="mb-1 fw-bold">Ringkasan Tagihan</h5>
                            <p class="text-muted small mb-0">Ikhtisar data utama sebelum Operator BLU menerbitkan dokumen SPP.</p>
                        </div>
                        <div class="card-body px-4 pb-4">
                            <div class="table-responsive">
                                <table class="table align-middle mb-0">
                                    <tbody>
                                        <tr>
                                            <td class="ps-0 text-muted fw-semibold" style="width: 32%;">Nomor Tagihan</td>
                                            <td class="pe-0 fw-bold text-primary">{{ $tagihan->nomor_tagihan }}</td>
                                        </tr>
                                        <tr>
                                            <td class="ps-0 text-muted fw-semibold">Uraian</td>
                                            <td class="pe-0">{{ $tagihan->deskripsi }}</td>
                                        </tr>
                                        <tr>
                                            <td class="ps-0 text-muted fw-semibold">Status Tagihan</td>
                                            <td class="pe-0"><span class="badge {{ $statusTagihanClass }}">{{ $tagihan->status }}</span></td>
                                        </tr>
                                        <tr>
                                            <td class="ps-0 text-muted fw-semibold">Status SPP</td>
                                            <td class="pe-0"><span class="badge {{ $statusSppClass }}">{{ $statusSppLabel }}</span></td>
                                        </tr>
                                        <tr>
                                            <td class="ps-0 text-muted fw-semibold">Nilai Bruto</td>
                                            <td class="pe-0 fw-semibold">Rp {{ number_format($tagihan->total_bruto, 0, ',', '.') }}</td>
                                        </tr>
                                        <tr>
                                            <td class="ps-0 text-muted fw-semibold">Total Potongan</td>
                                            <td class="pe-0 fw-semibold text-danger">Rp {{ number_format($tagihan->total_potongan, 0, ',', '.') }}</td>
                                        </tr>
                                        <tr>
                                            <td class="ps-0 text-muted fw-semibold">Nilai Netto</td>
                                            <td class="pe-0 fw-bold text-success fs-5">Rp {{ number_format($tagihan->total_netto, 0, ',', '.') }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-white border-0 pt-4 px-4">
                            <h5 class="mb-1 fw-bold">Informasi DIPA</h5>
                            <p class="text-muted small mb-0">Sumber anggaran aktif yang menjadi dasar penerbitan dokumen SPP.</p>
                        </div>
                        <div class="card-body px-4 pb-4">
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <div class="border rounded-4 p-3 h-100">
                                        <div class="text-muted small mb-1">Nomor DIPA</div>
                                        <div class="fw-bold">{{ $dipa->nomor_dipa ?? '-' }}</div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="border rounded-4 p-3 h-100">
                                        <div class="text-muted small mb-1">Tahun Anggaran</div>
                                        <div class="fw-bold">{{ $dipa->tahun_anggaran ?? '-' }}</div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="border rounded-4 p-3 h-100">
                                        <div class="text-muted small mb-1">Revisi Aktif</div>
                                        <div class="fw-bold">{{ $dipa->revisi_aktif_ke ?? '-' }}</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="border rounded-4 p-3 h-100">
                                        <div class="text-muted small mb-1">Tanggal Pengesahan</div>
                                        <div class="fw-bold">{{ optional($dipa?->tanggal_disahkan)->format('d M Y') ?? '-' }}</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="border rounded-4 p-3 h-100">
                                        <div class="text-muted small mb-1">Total Pagu Revisi Aktif</div>
                                        <div class="fw-bold text-primary">Rp {{ number_format($activeRevision->total_pagu ?? 0, 0, ',', '.') }}</div>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="border rounded-4 p-3">
                                        <div class="text-muted small mb-1">Item DIPA / MAK yang Dipakai</div>
                                        <div class="fw-bold">
                                            @if($selectedBudgetItem?->coa)
                                                {{ $selectedBudgetItem->coa->kode_mak_lengkap }} - {{ $selectedBudgetItem->coa->nama_akun }}
                                            @else
                                                -
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-white border-0 pt-4 px-4">
                            <h5 class="mb-1 fw-bold">Informasi Kontrak</h5>
                            <p class="text-muted small mb-0">Data kontrak induk yang menjadi dasar penerbitan SPP.</p>
                        </div>
                        <div class="card-body px-4 pb-4">
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <div class="border rounded-4 p-3 h-100">
                                        <div class="text-muted small mb-1">Nomor SPK</div>
                                        <div class="fw-bold">{{ $kontrak->nomor_spk ?? '-' }}</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="border rounded-4 p-3 h-100">
                                        <div class="text-muted small mb-1">Tanggal SPK</div>
                                        <div class="fw-bold">{{ optional($kontrak?->tanggal_spk)->format('d M Y') ?? '-' }}</div>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="border rounded-4 p-3">
                                        <div class="text-muted small mb-1">Nama Pekerjaan</div>
                                        <div class="fw-bold">{{ $kontrak->nama_pekerjaan ?? '-' }}</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="border rounded-4 p-3 h-100">
                                        <div class="text-muted small mb-1">Nilai Total Kontrak</div>
                                        <div class="fw-bold text-primary">Rp {{ number_format($kontrak->nilai_total_kontrak ?? 0, 0, ',', '.') }}</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="border rounded-4 p-3 h-100">
                                        <div class="text-muted small mb-1">Metode Pembayaran</div>
                                        <div class="fw-bold">{{ $kontrak->metode_pembayaran ?? '-' }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-white border-0 pt-4 px-4">
                            <h5 class="mb-1 fw-bold">Informasi Termin / BAST / BAP</h5>
                            <p class="text-muted small mb-0">Detail termin penagihan dan dokumen dasar pencairan.</p>
                        </div>
                        <div class="card-body px-4 pb-4">
                            <div class="row g-4">
                                <div class="col-md-4">
                                    <div class="border rounded-4 p-3 h-100">
                                        <div class="text-muted small mb-1">Termin</div>
                                        <div class="fw-bold fs-5">Termin {{ $termin->termin_ke ?? '-' }}</div>
                                        <div class="small text-muted mt-1">{{ $termin->jenis_termin ?? '-' }}</div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="border rounded-4 p-3 h-100">
                                        <div class="text-muted small mb-1">Persentase</div>
                                        <div class="fw-bold fs-5">{{ $termin->persentase ?? 0 }}%</div>
                                        <div class="small text-muted mt-1">Porsi termin terhadap kontrak.</div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="border rounded-4 p-3 h-100">
                                        <div class="text-muted small mb-1">Nilai Bruto Termin</div>
                                        <div class="fw-bold fs-5">Rp {{ number_format($termin->nilai_bruto_termin ?? 0, 0, ',', '.') }}</div>
                                        <div class="small text-muted mt-1">Nilai bruto termin yang ditagihkan.</div>
                                    </div>
                                </div>
                                @if($isPelunasan)
                                    <div class="col-md-6">
                                        <div class="border rounded-4 p-3 h-100">
                                            <div class="text-muted small mb-1">Nomor BAST</div>
                                            <div class="fw-bold">{{ $tagihan->detailKontrak->nomor_bast ?? '-' }}</div>
                                            <div class="small text-muted mt-1">Tanggal: {{ optional($tagihan->detailKontrak->tanggal_bast)->format('d M Y') ?? '-' }}</div>
                                        </div>
                                    </div>
                                @endif
                                <div class="col-md-6">
                                    <div class="border rounded-4 p-3 h-100">
                                        <div class="text-muted small mb-1">Nomor BAP</div>
                                        <div class="fw-bold">{{ $tagihan->detailKontrak->nomor_bap ?? '-' }}</div>
                                        <div class="small text-muted mt-1">Tanggal: {{ optional($tagihan->detailKontrak->tanggal_bap)->format('d M Y') ?? '-' }}</div>
                                    </div>
                                </div>
                                @if(!empty($tagihan->detailKontrak->nomor_bapp))
                                    <div class="col-md-6">
                                        <div class="border rounded-4 p-3 h-100">
                                            <div class="text-muted small mb-1">Nomor BAPP</div>
                                            <div class="fw-bold">{{ $tagihan->detailKontrak->nomor_bapp }}</div>
                                            <div class="small text-muted mt-1">Tanggal: {{ optional($tagihan->detailKontrak->tanggal_bapp)->format('d M Y') ?? '-' }}</div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="border rounded-4 p-3 h-100">
                                            <div class="text-muted small mb-1">Pemeriksa Hasil Pekerjaan</div>
                                            <div class="fw-bold">{{ $tagihan->detailKontrak->nama_pemeriksa ?? '-' }}</div>
                                            <div class="small text-muted mt-1">{{ $tagihan->detailKontrak->jabatan_pemeriksa ?? '-' }} (NIP: {{ $tagihan->detailKontrak->nip_pemeriksa ?? '-' }})</div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    @if($potonganTagihans->isNotEmpty())
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header bg-white border-0 pt-4 px-4">
                                <h5 class="mb-1 fw-bold">Ringkasan Potongan</h5>
                                <p class="text-muted small mb-0">Potongan pada tagihan yang mempengaruhi nilai netto SPP.</p>
                            </div>
                            <div class="card-body px-4 pb-4">
                                <div class="table-responsive">
                                    <table class="table align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Jenis Potongan</th>
                                                <th>Keterangan</th>
                                                <th class="text-end">Nominal</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($potonganTagihans as $potongan)
                                                <tr>
                                                    <td class="fw-semibold">{{ $potongan->jenis_potongan }}</td>
                                                    <td class="text-muted">{{ $potongan->deskripsi ?: '-' }}</td>
                                                    <td class="text-end fw-bold {{ $potongan->jenis_potongan === 'ANGSURAN_UANG_MUKA' ? 'text-warning' : 'text-danger' }}">
                                                        Rp {{ number_format($potongan->nominal_potongan ?? 0, 0, ',', '.') }}
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <th colspan="2" class="text-end">Total Potongan</th>
                                                <th class="text-end text-danger">Rp {{ number_format($tagihan->total_potongan, 0, ',', '.') }}</th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                                @if($potonganAngsuranUm)
                                    <div class="alert alert-warning mt-3 mb-0">
                                        <i class="bi bi-wallet2 me-2"></i>
                                        Angsuran uang muka pada tagihan ini sebesar
                                        <strong>Rp {{ number_format($potonganAngsuranUm->nominal_potongan ?? 0, 0, ',', '.') }}</strong>.
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif

                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-white border-0 pt-4 px-4">
                            <h5 class="mb-1 fw-bold">Lampiran Dokumen Tagihan</h5>
                            <p class="text-muted small mb-0">Dokumen pendukung yang perlu dipastikan Operator BLU sebelum menerbitkan SPP.</p>
                        </div>
                        <div class="card-body px-4 pb-4">
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
                                    Tidak ada lampiran dokumen yang tersedia untuk tagihan kontrak ini.
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-white border-0 pt-4 px-4">
                            <h5 class="mb-1 fw-bold">Informasi Vendor & Rekening</h5>
                            <p class="text-muted small mb-0">Pastikan rekening vendor benar sebelum menerbitkan SPP.</p>
                        </div>
                        <div class="card-body px-4 pb-4">
                            <div class="mb-3">
                                <div class="text-muted small mb-1">Vendor</div>
                                <div class="fw-bold">{{ $vendor->nama_pihak ?? '-' }}</div>
                            </div>
                            <div class="mb-3">
                                <div class="text-muted small mb-1">NPWP</div>
                                <div class="fw-semibold">{{ $vendor->npwp ?? '-' }}</div>
                            </div>
                            <div class="mb-3">
                                <div class="text-muted small mb-1">Alamat</div>
                                <div class="fw-semibold">{{ $vendor->alamat ?? '-' }}</div>
                            </div>
                            <hr>
                            <div class="mb-3">
                                <div class="text-muted small mb-1">Nama Bank</div>
                                <div class="fw-semibold">{{ $rekening->nama_bank ?? '-' }}</div>
                            </div>
                            <div class="mb-3">
                                <div class="text-muted small mb-1">Nomor Rekening</div>
                                <div class="fw-bold">{{ $rekening->nomor_rekening ?? '-' }}</div>
                            </div>
                            <div>
                                <div class="text-muted small mb-1">Atas Nama</div>
                                <div class="fw-semibold">{{ $rekening->nama_rekening ?? '-' }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-white border-0 pt-4 px-4">
                            <h5 class="mb-1 fw-bold">Ringkasan SPP</h5>
                            <p class="text-muted small mb-0">Status terakhir dokumen SPP untuk tagihan kontrak ini.</p>
                        </div>
                        <div class="card-body px-4 pb-4">
                            <div class="border rounded-4 p-3 mb-3">
                                <div class="text-muted small mb-1">Status SPP</div>
                                <span class="badge {{ $statusSppClass }} px-3 py-2">{{ $statusSppLabel }}</span>
                            </div>
                            <div class="border rounded-4 p-3 mb-3">
                                <div class="text-muted small mb-1">Nomor SPP</div>
                                <div class="fw-bold">{{ $sppModel->nomor_spp ?? '-' }}</div>
                            </div>
                            <div class="border rounded-4 p-3 mb-3">
                                <div class="text-muted small mb-1">Tanggal SPP</div>
                                <div class="fw-semibold">{{ optional($sppModel?->tanggal_spp)->format('d M Y') ?? '-' }}</div>
                            </div>
                            <div class="border rounded-4 p-3 mb-3">
                                <div class="text-muted small mb-1">Nilai SPP</div>
                                <div class="fw-bold text-primary">Rp {{ number_format($sppModel->nominal_spp ?? $tagihan->total_netto, 0, ',', '.') }}</div>
                            </div>
                            <div class="border rounded-4 p-3">
                                <div class="text-muted small mb-1">Item DIPA / MAK</div>
                                <div class="fw-semibold">
                                    @if($selectedBudgetItem?->coa)
                                        {{ $selectedBudgetItem->coa->kode_mak_lengkap }}<br>
                                        <span class="text-muted">{{ $selectedBudgetItem->coa->nama_akun }}</span>
                                    @else
                                        -
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-4">
                            <div class="text-muted small text-uppercase fw-semibold mb-3">Aksi Utama</div>
                            <div class="d-grid gap-2">
                                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalSppKontrak">
                                    <i class="bi bi-pencil-square me-1"></i> {{ $sppModel ? 'Edit Dokumen SPP' : 'Buat Dokumen SPP' }}
                                </button>
                                @if($sppModel)
                                    <a href="{{ route('spps.cetak-pdf', $sppModel->id) }}" target="_blank" class="btn btn-outline-danger">
                                        <i class="bi bi-file-earmark-pdf me-1"></i> Cetak PDF
                                    </a>
                                @endif
                                <a href="{{ route('spps.kontrak.index') }}" class="btn btn-outline-secondary">
                                    <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalSppKontrak" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <form action="{{ route('spps.kontrak.store', $tagihan->id) }}" method="POST" enctype="multipart/form-data" id="formSppKontrak">
                    @csrf
                    <input type="hidden" name="jumlah_uang" id="jumlah_uang_spp" value="{{ $tagihan->total_netto }}">

                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title fw-bold">
                            <i class="bi bi-file-earmark-text me-2"></i>{{ $sppModel ? 'Edit Dokumen SPP' : 'Terbitkan Dokumen SPP' }}
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body p-4">
                        <div class="card bg-light border-0 mb-4">
                            <div class="card-body">
                                <div class="text-muted small text-uppercase fw-semibold mb-2">Ringkasan Tagihan</div>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="small text-muted">Nomor Tagihan</div>
                                        <div class="fw-bold">{{ $tagihan->nomor_tagihan }}</div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="small text-muted">Vendor</div>
                                        <div class="fw-bold">{{ $vendor->nama_pihak ?? '-' }}</div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="small text-muted">Nomor SPK</div>
                                        <div class="fw-bold">{{ $kontrak->nomor_spk ?? '-' }}</div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="small text-muted">Termin</div>
                                        <div class="fw-bold">Termin {{ $termin->termin_ke ?? '-' }} @if(!empty($termin->persentase))/ {{ $termin->persentase }}%@endif</div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="small text-muted">Nilai Bruto Tagihan</div>
                                        <div class="fw-bold">Rp {{ number_format($tagihan->total_bruto, 0, ',', '.') }}</div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="small text-muted">Potongan Angsuran Uang Muka</div>
                                        <div class="fw-bold text-warning">Rp {{ number_format($potonganAngsuranUm->nominal_potongan ?? 0, 0, ',', '.') }}</div>
                                    </div>
                                    <div class="col-12">
                                        <div class="small text-muted">Nilai Netto Tagihan</div>
                                        <div class="fw-bold text-success fs-5" id="modal_total_netto_display">Rp {{ number_format($tagihan->total_netto, 0, ',', '.') }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <h6 class="fw-bold mb-3">Data SPP</h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Nomor SPP</label>
                                    <input type="text" name="nomor_spp" class="form-control" required value="{{ old('nomor_spp', $sppModel->nomor_spp ?? '') }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Tanggal SPP</label>
                                    <input type="date" name="tanggal_spp" class="form-control" required value="{{ old('tanggal_spp', optional($sppModel?->tanggal_spp)->format('Y-m-d') ?? now()->format('Y-m-d')) }}">
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <h6 class="fw-bold mb-3">Rincian Potongan Pajak</h6>
                            <div class="border rounded-4 p-3 bg-light mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div>
                                        <div class="small text-muted">Input potongan pajak dilakukan pada tahap SPP</div>
                                        <div class="fw-semibold">Nilai SPP akan dihitung dari bruto dikurangi seluruh potongan.</div>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-primary" id="btnTambahPajakSpp">
                                        <i class="bi bi-plus"></i> Tambah Potongan
                                    </button>
                                </div>
                                <div id="containerPajakSpp">
                                    <div class="text-center text-muted p-3 bg-white rounded border" id="pajakSppKosong">
                                        Tidak ada potongan pajak. Nilai SPP akan mengikuti nilai netto tagihan saat ini.
                                    </div>
                                </div>
                                <div class="row g-3 mt-3">
                                    <div class="col-md-4">
                                        <div class="small text-muted mb-1">Nilai Bruto</div>
                                        <div class="fw-bold">Rp {{ number_format($tagihan->total_bruto, 0, ',', '.') }}</div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="small text-muted mb-1">Total Potongan Pajak</div>
                                        <div class="fw-bold text-danger" id="total_potongan_pajak_spp_display">Rp {{ number_format($potonganPajak->sum('nominal_potongan'), 0, ',', '.') }}</div>
                                    </div>
                                    <div class="col-md-4 text-md-end">
                                        <div class="small text-muted mb-1">Nominal SPP</div>
                                        <div class="fw-bold text-success fs-5" id="jumlah_uang_spp_display">Rp {{ number_format($tagihan->total_netto, 0, ',', '.') }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <h6 class="fw-bold mb-3">Dokumen Pajak</h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">File Faktur Pajak</label>
                                    <input type="file" name="file_faktur_pajak" class="form-control" accept=".pdf">
                                    <div class="form-text">Unggah atau ganti faktur pajak pada tahap pembuatan SPP.</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">File E-Billing</label>
                                    <input type="file" name="file_ebilling" class="form-control" accept=".pdf">
                                    <div class="form-text">Unggah bukti E-billing pajak jika sudah tersedia.</div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-semibold">Faktur Pajak Tersedia</label>
                                    @if($tagihan->detailKontrak?->file_faktur_pajak)
                                        <a href="{{ \Illuminate\Support\Facades\Storage::url($tagihan->detailKontrak->file_faktur_pajak) }}"
                                           target="_blank"
                                           rel="noopener noreferrer"
                                           class="btn btn-outline-secondary w-100">
                                            <i class="bi bi-file-earmark-pdf me-1"></i> Buka Faktur Pajak
                                        </a>
                                    @else
                                        <input type="text" class="form-control" value="Belum ada faktur pajak pada tagihan" readonly>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <h6 class="fw-bold mb-3">Verifikasi Dokumen SPP</h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Verifikator PPK</label>
                                    <select name="ppk_verifikator_id" class="form-select" required>
                                        <option value="">-- Pilih Verifikator PPK --</option>
                                        @foreach($ppkUsers as $ppkUser)
                                            <option value="{{ $ppkUser->id }}" {{ (string) old('ppk_verifikator_id') === (string) $ppkUser->id ? 'selected' : '' }}>
                                                {{ $ppkUser->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Verifikator Kasubbag</label>
                                    <input type="text" class="form-control" value="{{ $kasubbagUser->name ?? '-' }}" readonly>
                                </div>
                            </div>
                        </div>

                        <div>
                            <h6 class="fw-bold mb-3">Informasi DIPA</h6>
                            <div class="border rounded-4 p-3 bg-light">
                                <div class="small text-muted mb-1">DIPA aktif yang dipakai dokumen SPP ini</div>
                                <div class="fw-bold">{{ $dipa->nomor_dipa ?? '-' }}</div>
                                <div class="text-muted small mt-1">
                                    @if($selectedBudgetItem?->coa)
                                        {{ $selectedBudgetItem->coa->kode_mak_lengkap }} - {{ $selectedBudgetItem->coa->nama_akun }}
                                    @else
                                        Item DIPA aktif akan dipilih otomatis dari kontrak.
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-1"></i>{{ $sppModel ? 'Simpan Perubahan' : 'Simpan & Terbitkan SPP' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('script')
<script>
    const pajakOptionsSpp = @json($pajakOptionsSppData);
    const existingPotonganPajakSpp = @json($potonganPajakForm);
    const brutoSpp = @json((float) $tagihan->total_bruto);
    const potonganUmSpp = @json((float) ($potonganAngsuranUm->nominal_potongan ?? 0));
    let pajakSppCounter = 0;

    document.addEventListener('DOMContentLoaded', function () {
        const tambahBtn = document.getElementById('btnTambahPajakSpp');
        if (tambahBtn) {
            tambahBtn.addEventListener('click', function () {
                tambahRowPajakSpp();
            });
        }

        if (existingPotonganPajakSpp.length > 0) {
            existingPotonganPajakSpp.forEach(function (item) {
                tambahRowPajakSpp(item);
            });
        }

        hitungTotalNettoSpp();
    });

    function tambahRowPajakSpp(initialData = null) {
        const container = document.getElementById('containerPajakSpp');
        const emptyState = document.getElementById('pajakSppKosong');
        if (emptyState) {
            emptyState.style.display = 'none';
        }

        pajakSppCounter += 1;
        const rowId = pajakSppCounter;

        let optionsHtml = '<option value="">-- Pilih Jenis Pajak --</option>';
        pajakOptionsSpp.forEach(function (item) {
            const selected = String(initialData?.id ?? '') === String(item.id) ? 'selected' : '';
            optionsHtml += `<option value="${item.id}" data-tarif="${item.tarif}" ${selected}>${item.text}</option>`;
        });

        const dppValue = initialData?.dpp ?? 0;
        const nominalValue = initialData?.nominal ?? 0;

        const wrapper = document.createElement('div');
        wrapper.className = 'row g-3 mb-3 border p-3 rounded bg-white shadow-sm align-items-end pajak-row-spp';
        wrapper.id = `pajak_spp_row_${rowId}`;
        wrapper.innerHTML = `
            <div class="col-md-3">
                <label class="form-label small fw-bold">Jenis Pajak</label>
                <select class="form-select" name="pajak[${rowId}][id]" id="pajak_spp_sel_${rowId}" onchange="hitungPajakRowSpp(${rowId})" required>
                    ${optionsHtml}
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-bold">Nilai DPP (Rp)</label>
                <input type="text" class="form-control" id="dpp_spp_display_${rowId}" value="${formatRupiahCustom(Math.round(dppValue))}" onkeyup="formatInputDppSpp(this, ${rowId})">
                <input type="hidden" name="pajak[${rowId}][dpp]" id="dpp_spp_val_${rowId}" value="${dppValue}">
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-bold text-danger">Potongan Terhitung (Rp)</label>
                <input type="text" class="form-control bg-light fw-bold text-danger" id="potongan_spp_display_${rowId}" value="Rp ${formatRupiahCustom(Math.round(nominalValue))}" readonly>
                <input type="hidden" name="pajak[${rowId}][nominal]" id="potongan_spp_val_${rowId}" value="${nominalValue}" class="potongan-val-spp">
            </div>
            <div class="col-md-1 text-end">
                <button type="button" class="btn btn-outline-danger w-100" onclick="hapusPajakRowSpp(${rowId})"><i class="bi bi-trash"></i></button>
            </div>
        `;

        container.appendChild(wrapper);
        hitungPajakRowSpp(rowId, initialData?.nominal ?? null);
    }

    function hapusPajakRowSpp(id) {
        const row = document.getElementById(`pajak_spp_row_${id}`);
        if (row) {
            row.remove();
        }

        if (document.querySelectorAll('.pajak-row-spp').length === 0) {
            document.getElementById('pajakSppKosong').style.display = 'block';
        }

        hitungTotalNettoSpp();
    }

    function formatInputDppSpp(input, id) {
        const clean = input.value.replace(/[^,\d]/g, '');
        document.getElementById(`dpp_spp_val_${id}`).value = clean || 0;
        input.value = formatRupiahCustom(clean || 0);
        hitungPajakRowSpp(id);
    }

    function hitungPajakRowSpp(id, presetNominal = null) {
        const select = document.getElementById(`pajak_spp_sel_${id}`);
        const selected = select?.options[select.selectedIndex];
        const tarif = parseFloat(selected?.getAttribute('data-tarif') || 0);
        const dpp = parseFloat(document.getElementById(`dpp_spp_val_${id}`)?.value || 0);
        const hasil = presetNominal !== null ? parseFloat(presetNominal) || 0 : (dpp * tarif / 100);

        document.getElementById(`potongan_spp_val_${id}`).value = hasil;
        document.getElementById(`potongan_spp_display_${id}`).value = 'Rp ' + formatRupiahCustom(Math.round(hasil));
        hitungTotalNettoSpp();
    }

    function hitungTotalNettoSpp() {
        let totalPotonganPajak = 0;
        document.querySelectorAll('.potongan-val-spp').forEach(function (input) {
            totalPotonganPajak += parseFloat(input.value || 0);
        });

        const totalNetto = Math.max(0, brutoSpp - potonganUmSpp - totalPotonganPajak);
        document.getElementById('total_potongan_pajak_spp_display').textContent = 'Rp ' + formatRupiahCustom(Math.round(totalPotonganPajak));
        document.getElementById('jumlah_uang_spp_display').textContent = 'Rp ' + formatRupiahCustom(Math.round(totalNetto));
        document.getElementById('modal_total_netto_display').textContent = 'Rp ' + formatRupiahCustom(Math.round(totalNetto));
        document.getElementById('jumlah_uang_spp').value = totalNetto;
    }

    function formatRupiahCustom(angka) {
        let numberString = angka.toString().replace(/[^,\d]/g, '');
        let split = numberString.split(',');
        let sisa = split[0].length % 3;
        let rupiah = split[0].substr(0, sisa);
        let ribuan = split[0].substr(sisa).match(/\d{3}/gi);

        if (ribuan) {
            let separator = sisa ? '.' : '';
            rupiah += separator + ribuan.join('.');
        }

        return split[1] !== undefined ? rupiah + ',' + split[1] : rupiah;
    }
</script>
@endpush
