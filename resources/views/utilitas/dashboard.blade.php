@extends('layouts.app')
@section('title', 'Catat Meter ' . ucfirst($jenis))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
    <div>
        <h4 class="mb-0 fw-bold">Catat Meter {{ ucfirst($jenis) }}</h4>
        <p class="mb-0 small text-muted">Input pemakaian bulanan untuk diteruskan ke Admin Jasa.</p>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach($errors->all() as $err)
                <li>{{ $err }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="row g-4">
    {{-- Form Input --}}
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white pt-3 pb-2 border-0">
                <h6 class="fw-bold mb-0">Input Laporan Baru</h6>
            </div>
            <div class="card-body">
                <form action="{{ route('utilitas.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="jenis" value="{{ $jenis }}">
                    <input type="hidden" name="layanan_jasa_id" value="{{ $layanan->id }}">

                    {{-- Mitra --}}
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Mitra / Pelanggan</label>
                        <select name="mitra_jasa_id" class="form-select select2" required>
                            <option value="">Pilih Mitra...</option>
                            @foreach($mitras as $mitra)
                                <option value="{{ $mitra->id }}" {{ old('mitra_jasa_id') == $mitra->id ? 'selected' : '' }}>{{ $mitra->nama_mitra }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Periode --}}
                    <div class="row mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold">Bulan</label>
                            <select name="bulan" class="form-select" required>
                                @for($i=1; $i<=12; $i++)
                                    <option value="{{ $i }}" {{ (old('bulan', now()->month) == $i) ? 'selected' : '' }}>
                                        {{ \Carbon\Carbon::create()->month($i)->translatedFormat('F') }}
                                    </option>
                                @endfor
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">Tahun</label>
                            <input type="number" name="tahun" class="form-control" value="{{ old('tahun', now()->year) }}" required>
                        </div>
                    </div>

                    {{-- Tipe Pencatatan --}}
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Jenis Pencatatan</label>
                        <div class="d-flex gap-3">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="tipe_perhitungan" id="tipe_kwh" value="kwh" {{ old('tipe_perhitungan', 'kwh') === 'kwh' ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold" for="tipe_kwh">{{ $jenis == 'listrik' ? 'kWh' : 'm³' }} (Meter)</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="tipe_perhitungan" id="tipe_flat" value="flat" {{ old('tipe_perhitungan') === 'flat' ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold" for="tipe_flat">Flat (Manual)</label>
                            </div>
                        </div>
                    </div>

                    {{-- Flat Fields: Input pemakaian langsung --}}
                    <div id="section-flat" style="display:none;">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Jumlah Pemakaian</label>
                            <input type="number" name="pemakaian_manual" class="form-control" min="0" step="0.01" value="{{ old('pemakaian_manual') }}" placeholder="Masukkan jumlah pemakaian">
                        </div>
                    </div>

                    {{-- kWh Fields: Stan Awal & Akhir + 2 Bukti Foto --}}
                    <div id="section-kwh">
                        <div class="row mb-3">
                            <div class="col-6">
                                <label class="form-label small fw-bold">Stan Awal</label>
                                <input type="number" id="stan_awal" name="stan_awal" class="form-control" min="0" value="{{ old('stan_awal') }}">
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-bold">Stan Akhir</label>
                                <input type="number" id="stan_akhir" name="stan_akhir" class="form-control" min="0" value="{{ old('stan_akhir') }}">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted">Pemakaian (Otomatis)</label>
                            <input type="text" id="pemakaian_display" class="form-control bg-light" readonly placeholder="Isi stan awal & akhir">
                        </div>
                        <div class="row mb-3">
                            <div class="col-6">
                                <label class="form-label small fw-bold">Bukti Awal {{ $jenis == 'listrik' ? 'kWh' : 'm³' }} <span class="text-danger">*</span></label>
                                <input type="file" name="file_bukti_awal" class="form-control" accept="image/*">
                                <div class="form-text">Foto meteran awal. Max 5MB.</div>
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-bold">Bukti Akhir {{ $jenis == 'listrik' ? 'kWh' : 'm³' }} <span class="text-danger">*</span></label>
                                <input type="file" name="file_bukti" class="form-control" accept="image/*">
                                <div class="form-text">Foto meteran akhir. Max 5MB.</div>
                            </div>
                        </div>
                    </div>

                    <button class="btn btn-primary w-100 fw-bold">Simpan Laporan</button>
                </form>
            </div>
        </div>
    </div>

    {{-- Tabel Riwayat --}}
    <div class="col-md-8">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white pt-3 pb-2 border-0">
                <h6 class="fw-bold mb-0">Riwayat Laporan {{ ucfirst($jenis) }}</h6>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Periode</th>
                            <th>Mitra</th>
                            <th>Tipe</th>
                            <th>Stan / Pemakaian</th>
                            <th>Bukti</th>
                            <th>Status</th>
                            <th class="pe-3 text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($laporans as $lap)
                            <tr>
                                <td class="ps-3 fw-semibold">{{ str_pad($lap->bulan, 2, '0', STR_PAD_LEFT) }}/{{ $lap->tahun }}</td>
                                <td>{{ $lap->mitraJasa->nama_mitra ?? '-' }}</td>
                                <td><span class="badge {{ $lap->tipe_perhitungan == 'kwh' ? 'bg-info' : 'bg-secondary' }}">{{ $lap->tipe_perhitungan == 'kwh' ? ($lap->jenis == 'listrik' ? 'KWH' : 'M³') : 'FLAT' }}</span></td>
                                <td>
                                    @if($lap->tipe_perhitungan == 'kwh')
                                        {{ $lap->stan_awal }} → {{ $lap->stan_akhir }}<br>
                                        <small class="text-muted">= {{ $lap->pemakaian }} unit</small>
                                    @else
                                        {{ $lap->pemakaian }} unit <small class="text-muted">(flat)</small>
                                    @endif
                                </td>
                                <td>
                                    @if($lap->file_bukti_awal)
                                        <a href="{{ asset('storage/' . $lap->file_bukti_awal) }}" target="_blank" class="btn btn-sm btn-outline-primary" title="Bukti Awal"><i class="bi bi-image"></i></a>
                                    @endif
                                    @if($lap->file_bukti)
                                        <a href="{{ asset('storage/' . $lap->file_bukti) }}" target="_blank" class="btn btn-sm btn-outline-primary" title="Bukti Akhir"><i class="bi bi-image"></i></a>
                                    @endif
                                    @if(!$lap->file_bukti && !$lap->file_bukti_awal)
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if($lap->status == 'draft')
                                        <span class="badge bg-secondary">Draft</span>
                                    @elseif($lap->status == 'dikirim_ke_admin_jasa')
                                        <span class="badge bg-warning text-dark">Menunggu Tagihan</span>
                                    @elseif($lap->status == 'ditolak')
                                        <span class="badge bg-danger" title="{{ $lap->catatan_admin_jasa }}">Ditolak</span>
                                    @elseif($lap->status == 'ditagihkan')
                                        <span class="badge bg-success">Ditagihkan</span>
                                    @endif
                                </td>
                                <td class="pe-3 text-end">
                                    @if($lap->status == 'draft' || $lap->status == 'ditolak')
                                        <div class="d-flex gap-1 justify-content-end">
                                            <form action="{{ route('utilitas.submit', $lap->id) }}" method="POST">
                                                @csrf
                                                <button class="btn btn-sm btn-success" title="Kirim ke Admin Jasa"><i class="bi bi-send"></i> Kirim</button>
                                            </form>
                                            <form action="{{ route('utilitas.destroy', $lap->id) }}" method="POST" onsubmit="return confirm('Hapus laporan?');">
                                                @csrf @method('DELETE')
                                                <button class="btn btn-sm btn-danger"><i class="bi bi-trash"></i></button>
                                            </form>
                                        </div>
                                    @elseif($lap->status == 'ditagihkan' && $lap->tagihan_jasa_id)
                                        <span class="text-success small fw-bold">✓ Sudah Ditagihkan</span>
                                    @else
                                        <span class="text-muted small">-</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">Belum ada riwayat laporan.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($laporans->hasPages())
                <div class="card-footer bg-white">
                    {{ $laporans->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('script')
<script>
    $(document).ready(function() {
        // Toggle Flat / kWh sections
        function toggleSections() {
            var tipe = $('input[name="tipe_perhitungan"]:checked').val();
            if (tipe === 'kwh') {
                // kWh: stan awal & akhir + 2 bukti
                $('#section-kwh').show();
                $('#section-flat').hide();
                $('[name="pemakaian_manual"]').removeAttr('required');
                $('#stan_awal, #stan_akhir').attr('required', true);
            } else {
                // Flat: pemakaian manual
                $('#section-kwh').hide();
                $('#section-flat').show();
                $('[name="pemakaian_manual"]').attr('required', true);
                $('#stan_awal, #stan_akhir').removeAttr('required');
            }
        }

        $('input[name="tipe_perhitungan"]').on('change', toggleSections);
        toggleSections(); // init on load

        // Auto-calculate pemakaian for kWh mode (stan awal/akhir)
        $('#stan_awal, #stan_akhir').on('input', function() {
            var awal = parseInt($('#stan_awal').val()) || 0;
            var akhir = parseInt($('#stan_akhir').val()) || 0;
            var pemakaian = Math.max(0, akhir - awal);
            $('#pemakaian_display').val(pemakaian + ' unit');
        });

        // AJAX fetch last stan_akhir
        function fetchLastStanAkhir() {
            var mitra_id = $('select[name="mitra_jasa_id"]').val();
            var bulan = $('select[name="bulan"]').val();
            var tahun = $('input[name="tahun"]').val();
            var tipe = $('input[name="tipe_perhitungan"]:checked').val();

            if (mitra_id && bulan && tahun && tipe === 'kwh') {
                $.ajax({
                    url: '{{ route("utilitas.last-stan-akhir") }}',
                    type: 'GET',
                    data: {
                        mitra_jasa_id: mitra_id,
                        layanan_jasa_id: '{{ $layanan->id }}',
                        bulan: bulan,
                        tahun: tahun
                    },
                    success: function(res) {
                        $('#stan_awal').val(res.stan_akhir).trigger('input');
                    }
                });
            }
        }

        $('select[name="mitra_jasa_id"], select[name="bulan"], input[name="tahun"]').on('change', fetchLastStanAkhir);
        $('input[name="tipe_perhitungan"]').on('change', function() {
            if ($(this).val() === 'kwh') {
                fetchLastStanAkhir();
            }
        });
    });
</script>
@endpush
