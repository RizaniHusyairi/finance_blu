@extends('layouts.app')
@section('title', 'Detail Rekap Tagihan Garbarata')

@section('content')
@include('super_admin_jasa.laporan._styles')

<div class="sa-report-page">
    <div class="sa-report-hero d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <div class="small text-uppercase fw-bold" style="letter-spacing:.08em;color:#fbbf24;">Rekap #{{ $item->id }}</div>
            <h4 class="fw-bold mb-1"><i class="bi bi-receipt-cutoff me-2"></i>{{ $item->mitra?->nama_mitra ?? '-' }} &middot; {{ $item->periode_label }}</h4>
            <p class="mb-0 small">
                <span class="badge {{ $item->status_badge }}">{{ $item->status_label }}</span>
                &middot; Dibuat {{ optional($item->created_at)->format('d M Y H:i') }}
                &middot; oleh {{ $item->creator?->name ?? '-' }}
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('pengajuan-penagihan-garbarata.index') }}" class="btn btn-light border">
                <i class="bi bi-arrow-left me-1"></i>Kembali
            </a>
            @if($item->status === \App\Models\PengajuanPenagihanGarbarata::STATUS_DISETUJUI && ! $item->tagihan_jasa_id)
                <a href="{{ route('tagihan-jasa.create', ['amc_garbarata_pengajuan_id' => $item->id]) }}" class="btn btn-success fw-bold">
                    <i class="bi bi-receipt me-1"></i>Buat Tagihan
                </a>
            @endif
            @if($canReview)
                <button type="button" class="btn btn-success fw-bold" data-bs-toggle="modal" data-bs-target="#reviewModal" data-keputusan="DISETUJUI">
                    <i class="bi bi-check2-circle me-1"></i>Setujui
                </button>
                <button type="button" class="btn btn-outline-danger fw-bold" data-bs-toggle="modal" data-bs-target="#reviewModal" data-keputusan="DITOLAK">
                    <i class="bi bi-x-circle me-1"></i>Tolak
                </button>
            @endif
            @if($canCancel)
                <form method="POST" action="{{ route('pengajuan-penagihan-garbarata.destroy', $item) }}"
                      onsubmit="return confirm('Batalkan rekap ini? Pemakaian akan dikembalikan ke Draft.');">
                    @csrf @method('DELETE')
                    <button class="btn btn-outline-warning fw-bold"><i class="bi bi-x-octagon me-1"></i>Batalkan</button>
                </form>
            @endif
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0">{{ session('success') }}</div>
    @endif

    <div class="row g-3 mb-3">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100"><div class="card-body">
                <div class="small text-uppercase text-muted fw-bold">Jumlah Pemakaian</div>
                <div class="display-6 fw-bold">{{ number_format($item->jumlah_pemakaian) }}</div>
            </div></div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100"><div class="card-body">
                <div class="small text-uppercase text-muted fw-bold">Total Rentang</div>
                <div class="display-6 fw-bold">{{ number_format($item->total_rentang) }}</div>
            </div></div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100"><div class="card-body">
                <div class="small text-uppercase text-muted fw-bold">Estimasi Nominal</div>
                <div class="display-6 fw-bold">Rp {{ number_format($totalNominal, 0, ',', '.') }}</div>
            </div></div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100"><div class="card-body">
                <div class="small text-uppercase text-muted fw-bold">Dikunci Oleh</div>
                <div class="fw-bold mt-2">{{ $item->reviewer?->name ?? '—' }}</div>
                <div class="small text-muted">{{ optional($item->reviewed_at)->format('d M Y H:i') ?? 'Menunggu validasi' }}</div>
            </div></div>
        </div>
    </div>

    @if($item->catatan_amc)
        <div class="alert alert-warning border-0"><strong>Catatan Rekap:</strong> {{ $item->catatan_amc }}</div>
    @endif
    @if($item->catatan_admin)
        <div class="alert {{ $item->status === \App\Models\PengajuanPenagihanGarbarata::STATUS_DITOLAK ? 'alert-danger' : 'alert-info' }} border-0">
            <strong>Catatan Sistem:</strong> {{ $item->catatan_admin }}
        </div>
    @endif

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white border-0">
            <strong>Rincian Pemakaian Garbarata</strong>
            <span class="text-muted small ms-2">({{ $rows->count() }} baris)</span>
        </div>
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="small text-uppercase">Tanggal</th>
                        <th class="small text-uppercase">Flight / Reg</th>
                        <th class="small text-uppercase">Layanan</th>
                        <th class="small text-uppercase">Docking</th>
                        <th class="small text-uppercase">Undocking</th>
                        <th class="small text-uppercase text-end">Durasi</th>
                        <th class="small text-uppercase text-end">Rentang</th>
                        <th class="small text-uppercase text-end">Tarif</th>
                        <th class="small text-uppercase text-end">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        <tr>
                            <td>{{ optional($row->tanggal)->format('d M Y') }}</td>
                            <td>
                                <div class="fw-semibold small">{{ $row->nomor_penerbangan ?? '-' }}</div>
                                <div class="text-muted small">{{ $row->registrasi_pesawat ?? '-' }}</div>
                            </td>
                            <td class="small">{{ $row->layanan?->nama_layanan ?? '-' }}</td>
                            <td class="small">{{ optional($row->docking_at)->format('H:i') }}</td>
                            <td class="small">{{ optional($row->undocking_at)->format('H:i') }}</td>
                            <td class="text-end small">{{ number_format($row->durasi_menit) }} mnt</td>
                            <td class="text-end fw-bold">{{ number_format($row->jumlah_rentang) }}</td>
                            <td class="text-end small">Rp {{ number_format((float) ($row->tarif_garbarata ?? 0), 0, ',', '.') }}</td>
                            <td class="text-end fw-semibold">Rp {{ number_format((float) ($row->tarif_garbarata ?? 0) * (int) $row->jumlah_rentang, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="text-center text-muted py-4">Tidak ada baris pemakaian.</td></tr>
                    @endforelse
                </tbody>
                <tfoot class="table-light">
                    <tr>
                        <td colspan="6" class="text-end fw-bold small text-uppercase">TOTAL</td>
                        <td class="text-end fw-bold">{{ number_format($item->total_rentang) }}</td>
                        <td></td>
                        <td class="text-end fw-bold">Rp {{ number_format($totalNominal, 0, ',', '.') }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

@if($canReview)
<div class="modal fade" id="reviewModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('pengajuan-penagihan-garbarata.review', $item) }}" class="modal-content border-0">
            @csrf
            <input type="hidden" name="keputusan" id="reviewKeputusan" value="DISETUJUI">
            <div class="modal-header bg-warning-subtle">
                <h5 class="modal-title fw-bold" id="reviewTitle">Validasi Rekap</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="small mb-3">Rekap: <strong>{{ $item->mitra?->nama_mitra }} &middot; {{ $item->periode_label }}</strong> &middot; {{ number_format($item->total_rentang) }} rentang &middot; Rp {{ number_format($totalNominal, 0, ',', '.') }}</p>
                <label class="form-label fw-bold">Catatan <span id="catatanRequired" class="text-danger d-none">*</span></label>
                <textarea name="catatan_admin" class="form-control" rows="3" maxlength="2000" placeholder="Wajib diisi jika menolak."></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-warning fw-bold" id="reviewSubmit">Setujui</button>
            </div>
        </form>
    </div>
</div>
<script>
    document.querySelectorAll('[data-bs-target="#reviewModal"]').forEach(btn => {
        btn.addEventListener('click', () => {
            const k = btn.dataset.keputusan;
            document.getElementById('reviewKeputusan').value = k;
            document.getElementById('reviewTitle').textContent = k === 'DISETUJUI' ? 'Setujui Rekap' : 'Tolak Rekap';
            document.getElementById('reviewSubmit').textContent = k === 'DISETUJUI' ? 'Setujui' : 'Tolak';
            document.getElementById('reviewSubmit').className = k === 'DISETUJUI' ? 'btn btn-success fw-bold' : 'btn btn-danger fw-bold';
            document.getElementById('catatanRequired').classList.toggle('d-none', k === 'DISETUJUI');
        });
    });
</script>
@endif
@endsection
