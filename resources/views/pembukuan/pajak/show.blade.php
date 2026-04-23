@extends('layouts.app')
@section('title', 'Detail Buku Pembantu Pajak')

@include('pembukuan.partials.styles')

@section('content')
    <x-page-title title="Pembukuan" subtitle="Detail Buku Pembantu Pajak" />

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-1 fw-bold text-dark">{{ $potongan->nama_pajak_snapshot ?? $potongan->jenis_potongan }}</h4>
            <div class="text-muted">{{ $tagihan?->nomor_tagihan ?? '-' }} · Rp {{ number_format($potongan->nominal_potongan, 0, ',', '.') }}</div>
        </div>
        <a href="{{ route('pembukuan.pajak.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Kembali</a>
    </div>

    <div class="book-meta mb-4">
        <div class="book-meta-item">
            <div class="meta-label">Status Billing / Setor</div>
            <div class="meta-value">
                @include('pembukuan.partials.status-badge', ['value' => $potongan->ntpn ? 'SUDAH_SETOR' : ($potongan->kode_billing ? 'SUDAH_BILLING' : 'BELUM_SETOR')])
            </div>
        </div>
        <div class="book-meta-item">
            <div class="meta-label">Status Masuk BKU</div>
            <div class="meta-value">@include('pembukuan.partials.status-badge', ['value' => $statusBku])</div>
        </div>
        <div class="book-meta-item">
            <div class="meta-label">Status Rekonsiliasi</div>
            <div class="meta-value">@include('pembukuan.partials.status-badge', ['value' => $statusRekonsiliasi])</div>
        </div>
        <div class="book-meta-item">
            <div class="meta-label">Nominal Potongan</div>
            <div class="meta-value">Rp {{ number_format($potongan->nominal_potongan, 0, ',', '.') }}</div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-xl-7">
            <div class="card book-card mb-4">
                <div class="card-header"><h6 class="mb-0 fw-bold">Informasi Tagihan Sumber</h6></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6"><strong>Nomor Tagihan</strong><div class="text-muted">{{ $tagihan?->nomor_tagihan ?? '-' }}</div></div>
                        <div class="col-md-6"><strong>Tipe Tagihan</strong><div class="text-muted">{{ $tagihan?->tipe_tagihan ?? '-' }}</div></div>
                        <div class="col-md-12"><strong>Deskripsi</strong><div class="text-muted">{{ $tagihan?->deskripsi ?? '-' }}</div></div>
                        <div class="col-md-4"><strong>SPP</strong><div class="text-muted">{{ $docChain['spp']?->nomor_spp ?? '-' }}</div></div>
                        <div class="col-md-4"><strong>SPM</strong><div class="text-muted">{{ $docChain['spm']?->nomor_spm ?? '-' }}</div></div>
                        <div class="col-md-4"><strong>NPI / SP2D</strong><div class="text-muted">{{ $docChain['npi']?->nomor_npi ?? '-' }} / {{ $docChain['sp2d']?->nomor_sp2d ?? '-' }}</div></div>
                    </div>
                </div>
            </div>

            <div class="card book-card mb-4">
                <div class="card-header"><h6 class="mb-0 fw-bold">Informasi Potongan Pajak</h6></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4"><strong>Jenis Potongan</strong><div class="text-muted">{{ $potongan->jenis_potongan }}</div></div>
                        <div class="col-md-4"><strong>Nama Pajak Snapshot</strong><div class="text-muted">{{ $potongan->nama_pajak_snapshot ?? '-' }}</div></div>
                        <div class="col-md-4"><strong>Akun Potongan</strong><div class="text-muted">{{ $potongan->akunPotongan?->nama_akun ?? '-' }}</div></div>
                        <div class="col-md-4"><strong>DPP</strong><div class="text-muted">Rp {{ number_format($potongan->dpp, 0, ',', '.') }}</div></div>
                        <div class="col-md-4"><strong>Tarif</strong><div class="text-muted">{{ $potongan->persentase_tarif_snapshot ? number_format($potongan->persentase_tarif_snapshot, 2) . '%' : '-' }}</div></div>
                        <div class="col-md-4"><strong>NTPN</strong><div class="text-muted">{{ $potongan->ntpn ?? '-' }}</div></div>
                    </div>
                </div>
            </div>

            <div class="card book-card">
                <div class="card-header"><h6 class="mb-0 fw-bold">Lampiran Billing / BPN / BPPU</h6></div>
                <div class="card-body">
                    @if($potongan->arsipDokumen->isEmpty())
                        @include('pembukuan.partials.empty-state', ['title' => 'Belum ada lampiran', 'message' => 'Dokumen billing atau bukti setor belum diunggah pada potongan ini.'])
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm align-middle">
                                <thead>
                                    <tr>
                                        <th>Jenis Dokumen</th>
                                        <th>Nama File</th>
                                        <th>Uploader</th>
                                        <th>Waktu Upload</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($potongan->arsipDokumen as $arsip)
                                        <tr>
                                            <td>{{ $arsip->jenis_dokumen }}</td>
                                            <td>{{ $arsip->nama_file_asli }}</td>
                                            <td>{{ $arsip->uploader?->name ?? '-' }}</td>
                                            <td>{{ optional($arsip->uploaded_at)->format('d M Y H:i') ?? '-' }}</td>
                                            <td>
                                                <a href="{{ \Illuminate\Support\Facades\Storage::url($arsip->path_file) }}" target="_blank" class="btn btn-sm btn-outline-primary">Lihat</a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-xl-5">
            <div class="card book-card mb-4">
                <div class="card-header"><h6 class="mb-0 fw-bold">Status BKU dan Rekonsiliasi</h6></div>
                <div class="card-body">
                    <div class="mb-3"><strong>Jumlah BKU terkait</strong><div class="text-muted">{{ $bkuEntries->count() }} transaksi</div></div>
                    <div class="mb-3"><strong>Jumlah Rekonsiliasi</strong><div class="text-muted">{{ $rekonsiliasi->count() }} pasangan</div></div>
                    <div class="small text-muted">Ledger ini fokus pada posisi pajak sebagai pembantu pembukuan; status di atas diturunkan dari keterkaitan tagihan ke BKU dan rekonsiliasi bank yang sudah ada.</div>
                </div>
            </div>

            <div class="card book-card">
                <div class="card-header"><h6 class="mb-0 fw-bold">Riwayat Rekonsiliasi</h6></div>
                <div class="card-body">
                    @if($rekonsiliasi->isEmpty())
                        @include('pembukuan.partials.empty-state', ['title' => 'Belum ada rekonsiliasi', 'message' => 'Belum ada pasangan rekonsiliasi yang mengaitkan potongan ini dengan mutasi bank.'])
                    @else
                        <div class="book-timeline">
                            @foreach($rekonsiliasi as $item)
                                <div class="book-timeline-item">
                                    <div class="book-timeline-dot"></div>
                                    <div class="fw-semibold">{{ $item->status }}</div>
                                    <div class="small text-muted">
                                        {{ $item->direkonsiliasiOleh?->name ?? '-' }} · {{ optional($item->direkonsiliasi_pada)->format('d M Y H:i') ?? '-' }}
                                    </div>
                                    <div class="small mt-1">
                                        Mutasi: {{ $item->detailMutasiBank?->nomor_referensi_bank ?? '-' }} · Selisih: Rp {{ number_format($item->selisih, 0, ',', '.') }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
