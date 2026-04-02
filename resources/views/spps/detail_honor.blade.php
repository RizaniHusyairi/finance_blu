@extends('layouts.app')
@section('title') Manajemen SPP Honor @endsection
@section('content')
    <x-page-title title="Pembuatan SPP" subtitle="Detail Honorarium" />

    @if(session('success'))
        <div class="alert alert-success border-0 bg-success alert-dismissible fade show">
            <div class="text-white">{{ session('success') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card mb-4">
        <div class="card-body bg-light">
            <h5 class="card-title">{{ $honorarium->description }}</h5>
            <div class="d-flex justify-content-between align-items-end flex-wrap gap-2">
                <div>
                    <p class="mb-1"><strong>No Transaksi:</strong> {{ $honorarium->transaction_number }}</p>
                    <p class="mb-1"><strong>No BAST:</strong> {{ $honorarium->bast_number ?? '-' }} | <strong>Tgl BAST:</strong> {{ $honorarium->bast_date ? $honorarium->bast_date->format('d/m/Y') : '-' }}</p>
                    <p class="mb-0"><strong>Jumlah Penerima:</strong> {{ $honorarium->honorariumItems->count() }} Orang</p>
                </div>
                <div class="text-end">
                    <div class="small fw-bold mb-1">Total Bruto:</div>
                    <h4 class="text-primary mb-0">Rp {{ number_format($honorarium->gross_amount, 0, ',', '.') }}</h4>
                </div>
            </div>
        </div>
    </div>

    {{-- Daftar Penerima Ringkas --}}
    <div class="card mb-4">
        <div class="card-header bg-transparent"><h6 class="mb-0">Daftar Penerima Honorarium</h6></div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm table-bordered table-striped align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>Nama</th>
                            <th>NRP/NIP</th>
                            <th>Pangkat/Korps</th>
                            <th class="text-end">Bruto (Rp)</th>
                            <th class="text-end">PPh 21 (Rp)</th>
                            <th class="text-end">Netto (Rp)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($honorarium->honorariumItems as $item)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $item->nama_personel ?? $item->name }}</td>
                            <td>{{ $item->nrp_nip ?? $item->nrp ?? '-' }}</td>
                            <td>{{ $item->pangkat_korp ?? $item->rank_corps ?? '-' }}</td>
                            <td class="text-end">{{ number_format((float) ($item->nilai_honor ?? $item->honor_amount ?? 0), 0, ',', '.') }}</td>
                            <td class="text-end">{{ number_format((float) ($item->pph ?? $item->pph_amount ?? 0), 0, ',', '.') }}</td>
                            <td class="text-end fw-bold">{{ number_format((float) (($item->nilai_honor ?? $item->honor_amount ?? 0) - ($item->pph ?? $item->pph_amount ?? 0)), 0, ',', '.') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="table-light">
                            <th colspan="4" class="text-end">TOTAL</th>
                            <th class="text-end">{{ number_format((float) $honorarium->honorariumItems->sum(fn ($item) => $item->nilai_honor ?? $item->honor_amount ?? 0), 0, ',', '.') }}</th>
                            <th class="text-end">{{ number_format((float) $honorarium->honorariumItems->sum(fn ($item) => $item->pph ?? $item->pph_amount ?? 0), 0, ',', '.') }}</th>
                            <th class="text-end">{{ number_format((float) $honorarium->honorariumItems->sum(fn ($item) => ($item->nilai_honor ?? $item->honor_amount ?? 0) - ($item->pph ?? $item->pph_amount ?? 0)), 0, ',', '.') }}</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    {{-- SPP Section --}}
    @php $sppModel = $honorarium->spps->first(); @endphp

    <div class="card">
        <div class="card-header bg-primary text-white">
            <h6 class="mb-0 text-white">Dokumen SPP Honorarium</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped align-middle">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 5%;">No</th>
                            <th style="width: 25%;">Kategori</th>
                            <th style="width: 25%;" class="text-end">Total Nominal</th>
                            <th style="width: 20%;" class="text-center">Status</th>
                            <th style="width: 25%;" class="text-center">Aksi / Dokumen</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>1</td>
                            <td class="fw-bold text-primary">Honorarium</td>
                            <td class="text-end fw-bold">Rp {{ number_format($honorarium->gross_amount, 0, ',', '.') }}</td>
                            <td class="text-center">
                                @if($sppModel)
                                    @if($sppModel->status_spp == 'Menunggu Verifikasi')
                                        <span class="badge bg-warning text-dark mb-1"><i class="bi bi-clock"></i> Menunggu PPK</span><br>
                                    @elseif($sppModel->status_spp == 'Revisi')
                                        <span class="badge bg-danger mb-1"><i class="bi bi-exclamation-triangle"></i> Revisi</span><br>
                                        <div class="text-danger small fw-bold mb-1" style="max-width: 200px; margin: 0 auto; line-height: 1.2;">
                                            &quot;{{ $sppModel->catatan_revisi }}&quot;
                                        </div>
                                    @elseif($sppModel->status_spp == 'Disetujui PPK')
                                        <span class="badge bg-success mb-1"><i class="bi bi-check-circle-fill"></i> Disetujui PPK</span><br>
                                    @else
                                        <span class="badge bg-success mb-1"><i class="bi bi-check-circle"></i> Terbit</span><br>
                                    @endif
                                    <small>{{ $sppModel->nomor_spp }}</small>
                                @else
                                    <span class="badge bg-secondary"><i class="bi bi-dash"></i> Belum Dibuat</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($sppModel)
                                    <a href="{{ route('spps.cetak-pdf', $sppModel->spp_id) }}" target="_blank" class="btn btn-sm btn-danger mb-1">
                                        <i class="bi bi-file-pdf"></i> PDF
                                    </a>
                                    @if($sppModel->status_spp != 'Disetujui PPK')
                                    <button class="btn btn-sm btn-warning mb-1" data-bs-toggle="modal" data-bs-target="#modalSpp">
                                        <i class="bi bi-pencil-square"></i> Edit
                                    </button>
                                    @endif
                                @else
                                    <button class="btn btn-sm btn-primary px-3" data-bs-toggle="modal" data-bs-target="#modalSpp">
                                        <i class="bi bi-plus-circle"></i> Buat SPP
                                    </button>
                                @endif

                                <!-- Modal Form SPP -->
                                <div class="modal fade" id="modalSpp" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-lg text-start">
                                        <div class="modal-content">
                                            <form action="{{ route('spps.honor.store', $honorarium->id) }}" method="POST">
                                                @csrf
                                                <input type="hidden" name="jumlah_uang" value="{{ $honorarium->gross_amount }}">

                                                <div class="modal-header">
                                                    <h5 class="modal-title">{{ $sppModel ? 'Edit SPP' : 'Terbitkan SPP' }}: Honorarium</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="alert alert-info py-2">
                                                        Total tagihan: <strong>Rp {{ number_format($honorarium->gross_amount, 0, ',', '.') }}</strong><br>
                                                        <small>{{ $honorarium->description }}</small>
                                                    </div>

                                                    <div class="row mb-3">
                                                        <div class="col-md-6">
                                                            <label class="form-label fw-bold">Nomor SPP</label>
                                                            <input type="text" name="nomor_spp" class="form-control" placeholder="SPM-BLU/APTP-2026/..." required value="{{ $sppModel->nomor_spp ?? '' }}">
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label class="form-label fw-bold">Tanggal SPP</label>
                                                            <input type="date" name="tanggal_spp" class="form-control" required value="{{ $sppModel->tanggal_spp ?? date('Y-m-d') }}">
                                                        </div>
                                                    </div>

                                                    <div class="row mb-3">
                                                        <div class="col-md-6">
                                                            <label class="form-label fw-bold">Nomor DIPA</label>
                                                            <input type="text" name="nomor_dipa" class="form-control" placeholder="DIPA-022.05.2..." required value="{{ $sppModel->nomor_dipa ?? '' }}">
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label class="form-label fw-bold">Tanggal DIPA</label>
                                                            <input type="date" name="tanggal_dipa" class="form-control" required value="{{ $sppModel->tanggal_dipa ?? '' }}">
                                                        </div>
                                                    </div>

                                                    <div class="row mb-3">
                                                        <div class="col-md-6">
                                                            <label class="form-label fw-bold">Tahun Anggaran</label>
                                                            <input type="text" name="tahun_anggaran" class="form-control" value="{{ $sppModel->tahun_anggaran ?? date('Y') }}" required>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label class="form-label fw-bold">Kode Akun (MAK) Pagu</label>
                                                            <select name="akun_mak" class="form-select" required>
                                                                <option value="">-- Pilih Kode Anggaran (COA) --</option>
                                                                @foreach($budgets as $budget)
                                                                    <option value="{{ $budget->coa }}" {{ ($sppModel->akun_mak ?? '') == $budget->coa ? 'selected' : '' }}>
                                                                        {{ $budget->coa }} - {{ Str::limit($budget->description, 50) }}
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                    </div>

                                                    <hr>
                                                    <h6 class="mb-3">Penandatangan</h6>
                                                    <div class="row mb-3">
                                                        <div class="col-md-6">
                                                            <label class="form-label fw-bold">Nama KPA / PPK</label>
                                                            <input type="text" name="penandatangan_nama" class="form-control" required value="{{ $sppModel->penandatangan_nama ?? '' }}">
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label class="form-label fw-bold">NIP Penandatangan</label>
                                                            <input type="text" name="penandatangan_nip" class="form-control" required value="{{ $sppModel->penandatangan_nip ?? '' }}">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                                                    <button type="submit" class="btn btn-primary">{{ $sppModel ? 'Simpan Perubahan' : 'Simpan & Terbitkan SPP' }}</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                <!-- End Modal -->
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <div class="mt-4">
                <a href="{{ route('spps.honor.index') }}" class="btn btn-secondary px-4"><i class="bi bi-arrow-left"></i> Kembali ke Daftar Honor</a>
            </div>
        </div>
    </div>
@endsection
