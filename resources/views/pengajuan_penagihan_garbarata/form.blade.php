@extends('layouts.app')
@section('title', 'Ambil Data Tagihan Garbarata')

@section('content')
@include('super_admin_jasa.laporan._styles')

<div class="sa-report-page">
    <div class="sa-report-hero d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <div class="small text-uppercase fw-bold" style="letter-spacing:.08em;color:#fbbf24;">Jasa &middot; Penagihan Bulanan Garbarata</div>
            <h4 class="fw-bold mb-1"><i class="bi bi-plus-square me-2"></i>Ambil Data Penagihan Garbarata</h4>
            <p class="mb-0 small">Pilih mitra dan bulan, tinjau pemakaian AMC, lalu kunci rekap sebagai bahan Tagihan Jasa.</p>
        </div>
        <a href="{{ route('pengajuan-penagihan-garbarata.index') }}" class="btn btn-light border">
            <i class="bi bi-arrow-left me-1"></i>Kembali
        </a>
    </div>

    @if($errors->any())
        <div class="alert alert-danger border-0">
            <strong>Periksa kembali:</strong>
            <ul class="mb-0 small">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    @if(!empty($periodeError))
        <div class="alert alert-warning border-0">
            <i class="bi bi-exclamation-triangle me-1"></i> {{ $periodeError }}
        </div>
    @endif

    {{-- Step 1: Filter periode --}}
    <form method="GET" class="card border-0 shadow-sm mb-3">
        <div class="card-body row g-3 align-items-end">
            <div class="col-md-5">
                <label class="form-label small fw-bold text-uppercase">Mitra Jasa <span class="text-danger">*</span></label>
                <select name="mitra_jasa_id" class="form-select" required>
                    <option value="">— Pilih mitra —</option>
                    @foreach($mitraOptions as $m)
                        <option value="{{ $m->id }}" @selected($selectedMitraId == $m->id)>{{ $m->nama_mitra }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-bold text-uppercase">Periode Bulan <span class="text-danger">*</span></label>
                <input type="month" name="periode_bulan" class="form-control" value="{{ $selectedPeriode }}" placeholder="{{ now()->format('Y-m') }}" pattern="\d{4}-\d{2}" required>
                <div class="form-text small">Pakai picker bulan, atau ketik format <code>YYYY-MM</code> (mis. <code>{{ now()->format('Y-m') }}</code>).</div>
            </div>
            <div class="col-md-3 d-grid">
                <button class="btn btn-primary fw-bold"><i class="bi bi-search me-1"></i>Tinjau Data</button>
            </div>
        </div>
    </form>

    {{-- Step 2: Pratinjau & submit --}}
    @if($selectedMitraId && $selectedTahun)
        <form method="POST" action="{{ route('pengajuan-penagihan-garbarata.store') }}">
            @csrf
            <input type="hidden" name="mitra_jasa_id" value="{{ $selectedMitraId }}">
            <input type="hidden" name="periode_tahun" value="{{ $selectedTahun }}">
            <input type="hidden" name="periode_bulan" value="{{ $selectedBulan }}">

            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-warning-subtle border-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <strong>Pemakaian Siap Ditarik</strong>
                            <span class="text-muted small ms-2">({{ $eligibleRows->count() }} baris ditemukan — status Draft / Siap Ditagih, belum terikat rekap lain)</span>
                        </div>
                        @if($eligibleRows->count() > 0)
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="checkAll" checked>
                                <label class="form-check-label small fw-bold" for="checkAll">Pilih semua</label>
                            </div>
                        @endif
                    </div>
                </div>

                @if($eligibleRows->isEmpty())
                    <div class="card-body text-center text-muted py-4">
                        <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                        Tidak ada pemakaian Garbarata milik mitra ini di bulan tersebut yang siap ditarik.
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:36px;"></th>
                                    <th class="small text-uppercase">Tanggal</th>
                                    <th class="small text-uppercase">Flight / Reg</th>
                                    <th class="small text-uppercase">Route</th>
                                    <th class="small text-uppercase">Type</th>
                                    <th class="small text-uppercase text-center">Docking</th>
                                    <th class="small text-uppercase text-center">Undocking</th>
                                    <th class="small text-uppercase text-end">Durasi</th>
                                    <th class="small text-uppercase text-end">Rentang</th>
                                    <th class="small text-uppercase text-end">Tarif</th>
                                    <th class="small text-uppercase text-end">Total Rp</th>
                                    <th class="small text-uppercase text-center">File</th>
                                    <th class="small text-uppercase">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $grandTotal = 0; @endphp
                                @foreach($eligibleRows as $row)
                                    @php
                                        $rowTotal = (float) ($row->tarif_garbarata ?? 0) * (int) $row->jumlah_rentang;
                                        $grandTotal += $rowTotal;
                                    @endphp
                                    <tr data-total="{{ $rowTotal }}">
                                        <td>
                                            <input class="form-check-input row-check" type="checkbox" name="pemakaian_ids[]" value="{{ $row->id }}" checked>
                                        </td>
                                        <td class="small">{{ optional($row->tanggal)->format('d M Y') }}</td>
                                        <td>
                                            <div class="fw-semibold small">{{ $row->nomor_penerbangan ?? '-' }}</div>
                                            <div class="text-muted small">{{ $row->registrasi_pesawat ?? '-' }}</div>
                                        </td>
                                        <td class="small text-muted">{{ $row->route ?? '-' }}</td>
                                        <td class="small">{{ $row->type_pesawat ?? '-' }}</td>
                                        <td class="text-center small">{{ optional($row->docking_at)->format('H:i') }}</td>
                                        <td class="text-center small">{{ optional($row->undocking_at)->format('H:i') }}</td>
                                        <td class="text-end small">{{ number_format($row->durasi_menit) }} mnt</td>
                                        <td class="text-end fw-bold text-primary">{{ number_format($row->jumlah_rentang) }}</td>
                                        <td class="text-end small">Rp {{ number_format((float) ($row->tarif_garbarata ?? 0), 0, ',', '.') }}</td>
                                        <td class="text-end fw-bold text-success">Rp {{ number_format($rowTotal, 0, ',', '.') }}</td>
                                        <td class="text-center">
                                            @if($row->file_pendukung)
                                                <a href="{{ route('pemakaian-garbarata.file', $row->id) }}" target="_blank" class="btn btn-sm btn-outline-secondary" title="Buka file pendukung">
                                                    <i class="bi bi-paperclip"></i>
                                                </a>
                                            @else
                                                <span class="text-danger small" title="Tidak ada file pendukung"><i class="bi bi-exclamation-triangle"></i></span>
                                            @endif
                                        </td>
                                        <td><span class="badge {{ $row->status_badge }}">{{ $row->status_label }}</span></td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <td colspan="8" class="text-end fw-bold small text-uppercase">Total Terpilih</td>
                                    <td class="text-end fw-bold" id="totalRentang">{{ number_format($eligibleRows->sum('jumlah_rentang')) }}</td>
                                    <td></td>
                                    <td class="text-end fw-bold text-success" id="totalNominal">Rp {{ number_format($grandTotal, 0, ',', '.') }}</td>
                                    <td colspan="2"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @endif
            </div>

            @if($eligibleRows->count() > 0)
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-body">
                        <label class="form-label fw-bold">Catatan Rekap <span class="text-muted small">(opsional)</span></label>
                        <textarea name="catatan_amc" class="form-control" rows="3" maxlength="2000" placeholder="Contoh: Termasuk penerbangan extra non-schedule dari permohonan #15.">{{ old('catatan_amc') }}</textarea>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('pengajuan-penagihan-garbarata.index') }}" class="btn btn-light border">Batal</a>
                    <button type="submit" class="btn btn-warning fw-bold">
                        <i class="bi bi-lock me-1"></i>Kunci Rekap & Buat Tagihan
                    </button>
                </div>
            @endif
        </form>
    @endif
</div>

<script>
    (function () {
        const checkAll = document.getElementById('checkAll');
        const rows = document.querySelectorAll('.row-check');
        const totalRentangCell = document.getElementById('totalRentang');
        const totalNominalCell = document.getElementById('totalNominal');
        const perRow = Array.from(rows).map(r => {
            const tr = r.closest('tr');
            const rentang = parseInt((tr?.querySelector('td:nth-child(9)')?.innerText || '0').replace(/[^0-9]/g, ''), 10) || 0;
            const total = parseFloat(tr?.dataset.total || '0') || 0;
            return { rentang, total };
        });
        const formatRp = v => 'Rp ' + (Math.round(v) || 0).toLocaleString('id-ID');
        const recompute = () => {
            let sumRentang = 0;
            let sumTotal = 0;
            rows.forEach((r, i) => {
                if (r.checked) {
                    sumRentang += perRow[i].rentang;
                    sumTotal += perRow[i].total;
                }
            });
            if (totalRentangCell) totalRentangCell.textContent = sumRentang.toLocaleString('id-ID');
            if (totalNominalCell) totalNominalCell.textContent = formatRp(sumTotal);
        };
        if (checkAll) {
            checkAll.addEventListener('change', () => {
                rows.forEach(r => r.checked = checkAll.checked);
                recompute();
            });
        }
        rows.forEach(r => r.addEventListener('change', recompute));
    })();
</script>
@endsection
