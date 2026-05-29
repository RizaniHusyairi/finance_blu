@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
                <div class="card-header bg-primary bg-gradient text-white p-4 border-0">
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-white bg-opacity-25 rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                            <i class="bi bi-shield-check fs-2 text-white"></i>
                        </div>
                        <div>
                            <h4 class="mb-1 fw-bold text-white">Persetujuan Tagihan KPA</h4>
                            <p class="mb-0 text-white text-opacity-75 fs-6">Detail dokumen SPP dan tagihan yang memerlukan persetujuan Anda.</p>
                        </div>
                    </div>
                </div>

                <div class="card-body p-4 p-md-5">
                    @if(session('success'))
                        <div class="alert alert-success d-flex align-items-center gap-3 border-0 shadow-sm rounded-3 mb-4">
                            <i class="bi bi-check-circle-fill fs-4 text-success"></i>
                            <div class="fw-medium text-success">{{ session('success') }}</div>
                        </div>
                    @endif
                    @if(session('error'))
                        <div class="alert alert-danger d-flex align-items-center gap-3 border-0 shadow-sm rounded-3 mb-4">
                            <i class="bi bi-exclamation-triangle-fill fs-4 text-danger"></i>
                            <div class="fw-medium text-danger">{{ session('error') }}</div>
                        </div>
                    @endif

                    <!-- Detail SPP Section -->
                    <h5 class="fw-bold mb-3 text-primary"><i class="bi bi-file-earmark-text me-2"></i>Informasi Tagihan</h5>
                    <div class="row g-4 mb-4">
                        <div class="col-md-6">
                            <div class="p-3 bg-light rounded-3 h-100 border">
                                <span class="text-secondary d-block fs-7 mb-1">Nomor SPP</span>
                                <span class="fw-bold fs-6 text-dark">{{ $spp->nomor_spp }}</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 bg-light rounded-3 h-100 border">
                                <span class="text-secondary d-block fs-7 mb-1">Tanggal SPP</span>
                                <span class="fw-bold fs-6 text-dark">{{ \Carbon\Carbon::parse($spp->tanggal_spp)->translatedFormat('d F Y') }}</span>
                            </div>
                        </div>
                        @php
                            $tipe = $spp->tagihan?->tipe_tagihan;
                            $isPerjaldin = !empty($spp->tagihan_perjaldin_komponen_id);
                        @endphp
                        
                        @if($isPerjaldin)
                            @php
                                $detailPerjaldin = $spp->tagihan?->detailPerjaldin?->first();
                                $totalPegawai = $spp->tagihan?->detailPerjaldin?->count() ?? 1;
                            @endphp
                            <div class="col-12">
                                <div class="p-3 bg-light rounded-3 border">
                                    <span class="text-secondary d-block fs-7 mb-1">Pegawai yang Ditugaskan</span>
                                    <span class="fw-bold fs-6 text-dark">
                                        {{ $detailPerjaldin?->nama_pegawai ?? '-' }} 
                                        @if($totalPegawai > 1)
                                            <span class="badge bg-secondary ms-2">+{{ $totalPegawai - 1 }} Pegawai Lainnya</span>
                                        @endif
                                    </span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="p-3 bg-light rounded-3 h-100 border">
                                    <span class="text-secondary d-block fs-7 mb-1">Tujuan / Lokasi</span>
                                    <span class="fw-medium text-dark">{{ $detailPerjaldin?->tujuan ?? '-' }}</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="p-3 bg-light rounded-3 h-100 border">
                                    <span class="text-secondary d-block fs-7 mb-1">Tanggal & Lama Perjalanan</span>
                                    <span class="fw-medium text-dark">
                                        {{ $detailPerjaldin && $detailPerjaldin->tgl_berangkat ? \Carbon\Carbon::parse($detailPerjaldin->tgl_berangkat)->translatedFormat('d M Y') : '-' }} 
                                        ({{ $detailPerjaldin?->lama_hari ?? 0 }} Hari)
                                    </span>
                                </div>
                            </div>
                            
                        @elseif($tipe === 'HONORARIUM')
                            <div class="col-md-6">
                                <div class="p-3 bg-light rounded-3 h-100 border">
                                    <span class="text-secondary d-block fs-7 mb-1">Jumlah Personel</span>
                                    <span class="fw-bold fs-6 text-dark">
                                        <i class="bi bi-people-fill me-1 text-primary"></i> 
                                        {{ $spp->tagihan?->detailHonorarium?->count() ?? 0 }} Orang
                                    </span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="p-3 bg-light rounded-3 h-100 border">
                                    <span class="text-secondary d-block fs-7 mb-1">Bulan/Periode Honor</span>
                                    <span class="fw-bold fs-6 text-dark">{{ \Carbon\Carbon::parse($spp->tanggal_spp)->translatedFormat('F Y') }}</span>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="p-3 bg-light rounded-3 border">
                                    <span class="text-secondary d-block fs-7 mb-1">Uraian Kegiatan</span>
                                    <span class="fw-medium text-dark">{{ $spp->tagihan?->deskripsi ?? '-' }}</span>
                                </div>
                            </div>
                            
                        @else
                            <div class="col-12">
                                <div class="p-3 bg-light rounded-3 border">
                                    <span class="text-secondary d-block fs-7 mb-1">Vendor / Rekanan</span>
                                    <span class="fw-bold fs-6 text-dark">
                                        {{ $spp->tagihan?->detailKontrak?->kontrakTermin?->kontrak?->vendor?->nama_pihak 
                                            ?? $spp->tagihan?->pihak?->nama_pihak 
                                            ?? '-' }}
                                    </span>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="p-3 bg-light rounded-3 border">
                                    <span class="text-secondary d-block fs-7 mb-1">Uraian Penggunaan</span>
                                    <span class="fw-medium text-dark">{{ $spp->tagihan?->deskripsi ?? '-' }}</span>
                                </div>
                            </div>
                        @endif
                    </div>

                    <!-- Nominal Section -->
                    <h5 class="fw-bold mb-3 text-primary"><i class="bi bi-cash-stack me-2"></i>Rincian Nominal</h5>
                    <div class="p-4 rounded-4 border mb-5 shadow-sm bg-white">
                        <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom border-light-subtle">
                            <span class="text-secondary fs-6 fw-medium">Nilai Tagihan Kotor</span>
                            <span class="fw-bold fs-5 text-dark">Rp {{ number_format($spp->tagihan?->total_bruto ?? 0, 0, ',', '.') }}</span>
                        </div>
                        
                        <div class="mb-3 pb-3 border-bottom border-light-subtle">
                            <span class="text-secondary fs-6 fw-medium d-block mb-2">Potongan / Pajak</span>
                            @if($spp->tagihan?->potonganTagihan && $spp->tagihan->potonganTagihan->count() > 0)
                                @foreach($spp->tagihan->potonganTagihan as $potongan)
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="text-muted fs-7"><i class="bi bi-dash text-danger me-1"></i>{{ $potongan->nama_pajak_snapshot ?? $potongan->jenis_potongan }}</span>
                                        <span class="text-danger fw-medium fs-7">- Rp {{ number_format($potongan->nominal_potongan, 0, ',', '.') }}</span>
                                    </div>
                                @endforeach
                            @else
                                <span class="text-muted fs-7 fst-italic">Tidak ada potongan</span>
                            @endif
                        </div>

                        <div class="d-flex justify-content-between align-items-center pt-2">
                            <span class="fw-bold fs-5 text-primary">Total Bersih (Netto)</span>
                            <span class="fw-bold fs-3 text-success">Rp {{ number_format($spp->nominal_spp, 0, ',', '.') }}</span>
                        </div>
                    </div>

                    <!-- Dokumen Pendukung Section -->
                    <h5 class="fw-bold mb-3 text-primary"><i class="bi bi-folder2-open me-2"></i>Dokumen Pendukung Terlampir</h5>
                    <div class="p-4 rounded-4 border mb-5 shadow-sm bg-white">
                        <div class="row g-3">
                            @forelse($dokumenItems as $item)
                                <div class="col-md-6">
                                    <a href="{{ $item['url'] }}" target="_blank" class="d-flex align-items-center p-3 rounded-3 border bg-light text-decoration-none h-100" style="transition: all 0.2s;">
                                        <div class="{{ !empty($item['is_generated']) ? 'bg-primary text-primary' : 'bg-danger text-danger' }} bg-opacity-10 p-2 rounded me-3 d-flex align-items-center justify-content-center">
                                            <i class="bi {{ !empty($item['is_generated']) ? 'bi-file-earmark-check-fill' : 'bi-file-earmark-pdf-fill' }} fs-4"></i>
                                        </div>
                                        <div class="min-w-0">
                                            <div class="fw-bold text-dark mb-1 text-truncate">{{ $item['title'] }}</div>
                                            @if(!empty($item['source']))
                                                <span class="badge {{ !empty($item['is_generated']) ? 'bg-primary' : 'bg-secondary' }} rounded-pill mb-1">{{ $item['source'] }}</span>
                                            @endif
                                            <div class="text-primary fw-medium small">Buka Dokumen <i class="bi bi-box-arrow-up-right ms-1"></i></div>
                                        </div>
                                    </a>
                                </div>
                            @empty
                                <div class="col-12 text-center text-muted py-4">
                                    <div class="bg-secondary bg-opacity-10 rounded-circle d-inline-flex p-3 mb-2"><i class="bi bi-folder-x fs-3"></i></div>
                                    <div class="fw-semibold">Tidak ada lampiran dokumen</div>
                                </div>
                            @endforelse
                        </div>
                    </div>

                    <!-- Approval Form -->
                    @if(!$spp->kpa_approval_status || $spp->kpa_approval_status === 'PENDING_KPA')
                        <h5 class="fw-bold mb-3 text-primary"><i class="bi bi-pencil-square me-2"></i>Tindakan Persetujuan</h5>
                        <div class="bg-light p-4 rounded-4 border">
                            <form action="{{ route('kpa.approval.process', $spp->id) }}" method="POST">
                                @csrf
                                <div class="mb-4">
                                    <label for="notes" class="form-label fw-bold text-dark">Catatan (Opsional)</label>
                                    <textarea name="notes" id="notes" rows="3" class="form-control rounded-3 border-secondary-subtle" placeholder="Berikan catatan tambahan jika diperlukan..."></textarea>
                                </div>
                                <div class="d-flex flex-column flex-sm-row gap-3">
                                    <button type="submit" name="action" value="approve" class="btn btn-success rounded-pill px-5 py-2 fw-bold flex-grow-1 shadow-sm d-flex align-items-center justify-content-center gap-2">
                                        <i class="bi bi-check-circle-fill fs-5"></i> Setujui Tagihan
                                    </button>
                                    <button type="submit" name="action" value="reject" class="btn btn-outline-danger rounded-pill px-5 py-2 fw-bold flex-grow-1 d-flex align-items-center justify-content-center gap-2">
                                        <i class="bi bi-x-circle fs-5"></i> Tolak Tagihan
                                    </button>
                                </div>
                            </form>
                        </div>
                    @else
                        <div class="alert alert-info d-flex align-items-center gap-3 border-0 shadow-sm rounded-3">
                            <i class="bi bi-info-circle-fill fs-3 text-info"></i>
                            <div>
                                <h6 class="fw-bold mb-1">Tagihan Sudah Diproses</h6>
                                <p class="mb-0 text-secondary">
                                    Tagihan ini telah <strong>{{ $spp->kpa_approval_status === 'APPROVED' ? 'Disetujui' : 'Ditolak' }}</strong> 
                                    pada {{ \Carbon\Carbon::parse($spp->kpa_approved_at)->translatedFormat('d F Y H:i') }}.
                                </p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
            
            <div class="text-center text-muted fs-7 mb-5">
                Masuk sebagai <strong>{{ $user->name }}</strong> (KPA) &bull; Sistem Informasi Keuangan BLU
            </div>
        </div>
    </div>
</div>

<style>
    body { background-color: #f8f9fa; }
</style>
@endsection
