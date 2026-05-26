@extends('layouts.app')
@section('title', 'Catat Meter ' . ucfirst($jenis))

@push('css')
@include('dashboard.partials.mitra-ui')
<style>
    .util-stat {
        position: relative;
        overflow: hidden;
        min-height: 104px;
        border: 1px solid rgba(37, 99, 235, .10);
        border-radius: 18px;
        background: #fff;
        box-shadow: 0 14px 34px rgba(37, 99, 235, .07);
    }

    .util-stat::after {
        content: "";
        position: absolute;
        right: -44px;
        top: -56px;
        width: 132px;
        height: 132px;
        border-radius: 999px;
        background: var(--util-glow, rgba(59, 130, 246, .13));
    }

    .util-stat-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 38px;
        height: 38px;
        border-radius: 12px;
        color: var(--util-color, #2563eb);
        background: var(--util-soft, #dbeafe);
    }

    .util-stat-label {
        color: #64748b;
        font-size: 11px;
        font-weight: 900;
        letter-spacing: .03em;
        text-transform: uppercase;
    }

    .util-stat-value {
        color: #12355c;
        font-size: 24px;
        font-weight: 900;
        line-height: 1;
    }

    .util-fieldset {
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        background: linear-gradient(180deg, #f8fbff 0%, #fff 72%);
        padding: 16px;
    }

    .util-fieldset-title {
        display: flex;
        align-items: center;
        gap: 8px;
        color: #12355c;
        font-size: 12px;
        font-weight: 900;
        letter-spacing: .02em;
        text-transform: uppercase;
    }

    .util-type-card {
        cursor: pointer;
        display: block;
        height: 100%;
        border: 1px solid #dbeafe;
        border-radius: 14px;
        background: #fff;
        padding: 13px 14px;
        transition: border-color .2s ease, background .2s ease, box-shadow .2s ease, transform .2s ease;
        user-select: none;
    }

    .util-type-card:hover,
    .util-type-card.selected {
        border-color: #2563eb;
        background: #eff6ff;
        box-shadow: 0 12px 24px rgba(37, 99, 235, .10);
        transform: translateY(-1px);
    }

    .util-type-card .form-check-input {
        margin-top: .15rem;
    }

    .util-file {
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 12px;
        min-height: 72px;
        border: 1px dashed #bfdbfe;
        border-radius: 14px;
        background: #f8fbff;
        padding: 12px 14px;
        transition: border-color .2s ease, background .2s ease;
    }

    .util-file:hover {
        border-color: #2563eb;
        background: #eff6ff;
    }

    .util-file-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 38px;
        height: 38px;
        flex: 0 0 38px;
        border-radius: 12px;
        color: #2563eb;
        background: #dbeafe;
    }

    .util-file-name {
        color: #64748b;
        font-size: 12px;
        font-weight: 700;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        max-width: 100%;
    }

    .util-file.has-file {
        border-color: #22c55e;
        background: #f0fdf4;
    }

    .util-file.has-file .util-file-icon {
        color: #15803d;
        background: #dcfce7;
    }

    .util-file.has-file .util-file-name {
        color: #15803d;
    }

    .util-table .fw-meter {
        color: #12355c;
        font-weight: 800;
    }

    .util-table .btn {
        white-space: nowrap;
    }

    .mp-form .select2-container--bootstrap-5 .select2-selection {
        border-color: #dbeafe;
        border-radius: .85rem;
        min-height: 42px;
        box-shadow: 0 1px 2px rgba(15, 23, 42, .03);
    }

    .mp-form .select2-container--bootstrap-5.select2-container--focus .select2-selection,
    .mp-form .select2-container--bootstrap-5.select2-container--open .select2-selection {
        border-color: #3b82f6;
        box-shadow: 0 0 0 4px rgba(59, 130, 246, .12);
    }

    @media (max-width: 575.98px) {
        .util-file {
            align-items: flex-start;
        }
    }
</style>
@endpush

@section('content')
@php
    $unitLabel = $jenis === 'listrik' ? 'kWh' : 'm&sup3;';
    $laporanCollection = method_exists($laporans, 'items') ? collect($laporans->items()) : collect($laporans);
    $statusMeta = fn ($status) => match ($status) {
        'draft' => ['Draft', 'muted', 'bi-pencil-square'],
        'dikirim_ke_admin_jasa' => ['Menunggu Tagihan', 'warning', 'bi-hourglass-split'],
        'ditolak' => ['Ditolak', 'danger', 'bi-x-circle'],
        'ditagihkan' => ['Ditagihkan', 'success', 'bi-check-circle'],
        default => [str($status)->headline(), 'muted', 'bi-circle'],
    };
@endphp

<div class="mp-hero mb-4">
    <div class="d-flex flex-column flex-lg-row align-items-start align-items-lg-center justify-content-between gap-3">
        <div class="d-flex align-items-start gap-3">
            <span class="mp-hero-icon"><i class="bi {{ $jenis === 'listrik' ? 'bi-lightning-charge' : 'bi-droplet' }} fs-4"></i></span>
            <div>
                <div class="small text-white-50 fw-bold text-uppercase mb-1">Dashboard Utilitas</div>
                <h4 class="mb-1 fw-bold text-white">Catat Meter {{ ucfirst($jenis) }}</h4>
                <p class="mb-0 small fw-semibold text-white-50">Input pemakaian bulanan untuk diteruskan ke Admin Jasa.</p>
            </div>
        </div>
        <span class="mp-soft-badge info bg-white bg-opacity-10 text-white border border-light border-opacity-25">
            <i class="bi bi-calendar3"></i>{{ now()->translatedFormat('F Y') }}
        </span>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success rounded-3"><i class="bi bi-check-circle me-1"></i>{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger rounded-3"><i class="bi bi-exclamation-triangle me-1"></i>{{ session('error') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-danger rounded-3">
        <ul class="mb-0">
            @foreach($errors->all() as $err)
                <li>{{ $err }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="util-stat p-3" style="--util-color:#2563eb;--util-soft:#dbeafe;--util-glow:rgba(37,99,235,.14);">
            <div class="d-flex align-items-start justify-content-between gap-3 position-relative">
                <div>
                    <div class="util-stat-label mb-2">Laporan Ditampilkan</div>
                    <div class="util-stat-value">{{ $laporanCollection->count() }}</div>
                    <div class="small fw-semibold text-muted mt-2">Riwayat pada halaman ini</div>
                </div>
                <span class="util-stat-icon"><i class="bi bi-table"></i></span>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="util-stat p-3" style="--util-color:#d97706;--util-soft:#fef3c7;--util-glow:rgba(251,191,36,.20);">
            <div class="d-flex align-items-start justify-content-between gap-3 position-relative">
                <div>
                    <div class="util-stat-label mb-2">Menunggu Proses</div>
                    <div class="util-stat-value">{{ $laporanCollection->where('status', 'dikirim_ke_admin_jasa')->count() }}</div>
                    <div class="small fw-semibold text-muted mt-2">Sudah dikirim ke Admin Jasa</div>
                </div>
                <span class="util-stat-icon"><i class="bi bi-hourglass-split"></i></span>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="util-stat p-3" style="--util-color:#15803d;--util-soft:#dcfce7;--util-glow:rgba(34,197,94,.16);">
            <div class="d-flex align-items-start justify-content-between gap-3 position-relative">
                <div>
                    <div class="util-stat-label mb-2">Sudah Ditagihkan</div>
                    <div class="util-stat-value">{{ $laporanCollection->where('status', 'ditagihkan')->count() }}</div>
                    <div class="small fw-semibold text-muted mt-2">Laporan sudah menjadi tagihan</div>
                </div>
                <span class="util-stat-icon"><i class="bi bi-receipt-cutoff"></i></span>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-xl-4 col-lg-5">
        <div class="mp-card">
            <div class="mp-card-header">
                <div class="mp-card-title">
                    <span class="mp-card-icon"><i class="bi bi-pencil-square"></i></span>
                    <div>
                        <h6>Input Laporan Baru</h6>
                        <small>Catat periode dan bukti meter</small>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <form action="{{ route('utilitas.store') }}" method="POST" enctype="multipart/form-data" class="mp-form" id="utilitasForm">
                    @csrf
                    <input type="hidden" name="jenis" value="{{ $jenis }}">
                    <input type="hidden" name="layanan_jasa_id" value="{{ $layanan->id }}">

                    <div class="util-fieldset mb-3">
                        <div class="util-fieldset-title mb-3"><i class="bi bi-building"></i> Pelanggan</div>
                        <label class="form-label small fw-bold">Mitra / Pelanggan</label>
                        <select name="mitra_jasa_id" class="form-select select2" required>
                            <option value="">Pilih Mitra...</option>
                            @foreach($mitras as $mitra)
                                <option value="{{ $mitra->id }}" {{ old('mitra_jasa_id') == $mitra->id ? 'selected' : '' }}>{{ $mitra->nama_mitra }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="util-fieldset mb-3">
                        <div class="util-fieldset-title mb-3"><i class="bi bi-calendar-range"></i> Periode</div>
                        <div class="row g-3">
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
                    </div>

                    <div class="util-fieldset mb-3">
                        <div class="util-fieldset-title mb-3"><i class="bi bi-speedometer2"></i> Jenis Pencatatan</div>
                        <div class="row g-2">
                            <div class="col-sm-6">
                                <label class="util-type-card {{ old('tipe_perhitungan', 'kwh') === 'kwh' ? 'selected' : '' }}" for="tipe_kwh">
                                    <div class="form-check mb-0">
                                        <input class="form-check-input" type="radio" name="tipe_perhitungan" id="tipe_kwh" value="kwh" {{ old('tipe_perhitungan', 'kwh') === 'kwh' ? 'checked' : '' }}>
                                        <div class="ms-1">
                                            <div class="fw-bold">{!! $unitLabel !!}</div>
                                            <div class="small text-muted">Meter</div>
                                        </div>
                                    </div>
                                </label>
                            </div>
                            <div class="col-sm-6">
                                <label class="util-type-card {{ old('tipe_perhitungan') === 'flat' ? 'selected' : '' }}" for="tipe_flat">
                                    <div class="form-check mb-0">
                                        <input class="form-check-input" type="radio" name="tipe_perhitungan" id="tipe_flat" value="flat" {{ old('tipe_perhitungan') === 'flat' ? 'checked' : '' }}>
                                        <div class="ms-1">
                                            <div class="fw-bold">Flat</div>
                                            <div class="small text-muted">Manual</div>
                                        </div>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div id="section-flat" class="util-fieldset mb-3" style="display:none;">
                        <div class="util-fieldset-title mb-3"><i class="bi bi-keyboard"></i> Pemakaian Manual</div>
                        <label class="form-label small fw-bold">Jumlah Pemakaian</label>
                        <input type="number" name="pemakaian_manual" class="form-control" min="0" step="0.01" value="{{ old('pemakaian_manual') }}" placeholder="Masukkan jumlah pemakaian">
                    </div>

                    <div id="section-kwh" class="util-fieldset mb-3">
                        <div class="util-fieldset-title mb-3"><i class="bi bi-calculator"></i> Data Meter</div>
                        <div class="row g-3 mb-3">
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
                            <label class="form-label small fw-bold text-muted">Pemakaian Otomatis</label>
                            <input type="text" id="pemakaian_display" class="form-control bg-light" readonly placeholder="Isi stan awal dan akhir">
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Bukti Awal {!! $unitLabel !!} <span class="text-danger">*</span></label>
                                <label class="util-file" for="file_bukti_awal">
                                    <span class="util-file-icon"><i class="bi bi-cloud-arrow-up"></i></span>
                                    <span class="min-w-0">
                                        <span class="d-block fw-bold">Pilih Foto</span>
                                        <span class="d-block util-file-name">Foto meteran awal. Max 5MB.</span>
                                    </span>
                                </label>
                                <input type="file" id="file_bukti_awal" name="file_bukti_awal" class="d-none util-file-input" accept="image/*">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Bukti Akhir {!! $unitLabel !!} <span class="text-danger">*</span></label>
                                <label class="util-file" for="file_bukti">
                                    <span class="util-file-icon"><i class="bi bi-cloud-arrow-up"></i></span>
                                    <span class="min-w-0">
                                        <span class="d-block fw-bold">Pilih Foto</span>
                                        <span class="d-block util-file-name">Foto meteran akhir. Max 5MB.</span>
                                    </span>
                                </label>
                                <input type="file" id="file_bukti" name="file_bukti" class="d-none util-file-input" accept="image/*">
                            </div>
                        </div>
                    </div>

                    <button class="btn btn-primary w-100 fw-bold py-2">
                        <i class="bi bi-save me-1"></i>Simpan Laporan
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-xl-8 col-lg-7">
        <div class="mp-card">
            <div class="mp-card-header">
                <div class="mp-card-title">
                    <span class="mp-card-icon"><i class="bi bi-clock-history"></i></span>
                    <div>
                        <h6>Riwayat Laporan {{ ucfirst($jenis) }}</h6>
                        <small>Laporan meter dan status proses</small>
                    </div>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 mp-table util-table">
                    <thead>
                        <tr>
                            <th>Periode</th>
                            <th>Mitra</th>
                            <th>Tipe</th>
                            <th>Stan / Pemakaian</th>
                            <th>Bukti</th>
                            <th>Status</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($laporans as $lap)
                            @php($badge = $statusMeta($lap->status))
                            <tr>
                                <td class="fw-meter">{{ str_pad($lap->bulan, 2, '0', STR_PAD_LEFT) }}/{{ $lap->tahun }}</td>
                                <td>
                                    <div class="fw-semibold text-dark">{{ $lap->mitraJasa->nama_mitra ?? '-' }}</div>
                                    <div class="small text-muted">{{ $lap->layananJasa->nama_layanan ?? 'Utilitas' }}</div>
                                </td>
                                <td>
                                    <span class="mp-soft-badge {{ $lap->tipe_perhitungan == 'kwh' ? 'info' : 'muted' }}">
                                        {{ $lap->tipe_perhitungan == 'kwh' ? ($lap->jenis == 'listrik' ? 'KWH' : 'M3') : 'FLAT' }}
                                    </span>
                                </td>
                                <td>
                                    @if($lap->tipe_perhitungan == 'kwh')
                                        <span class="fw-meter">{{ $lap->stan_awal }} &rarr; {{ $lap->stan_akhir }}</span><br>
                                        <small class="text-muted">= {{ $lap->pemakaian }} unit</small>
                                    @else
                                        <span class="fw-meter">{{ $lap->pemakaian }} unit</span><br>
                                        <small class="text-muted">Flat manual</small>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex gap-1">
                                        @if($lap->file_bukti_awal)
                                            <a href="{{ asset('storage/' . $lap->file_bukti_awal) }}" target="_blank" class="btn btn-sm btn-light border text-primary jasa-icon-btn" title="Bukti awal" aria-label="Bukti awal"><i class="bi bi-image"></i></a>
                                        @endif
                                        @if($lap->file_bukti)
                                            <a href="{{ asset('storage/' . $lap->file_bukti) }}" target="_blank" class="btn btn-sm btn-light border text-primary jasa-icon-btn" title="Bukti akhir" aria-label="Bukti akhir"><i class="bi bi-images"></i></a>
                                        @endif
                                        @if(!$lap->file_bukti && !$lap->file_bukti_awal)
                                            <span class="text-muted">-</span>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <span class="mp-soft-badge {{ $badge[1] }}" title="{{ $lap->status == 'ditolak' ? $lap->catatan_admin_jasa : '' }}">
                                        <i class="bi {{ $badge[2] }}"></i>{{ $badge[0] }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    @if($lap->status == 'draft' || $lap->status == 'ditolak')
                                        <div class="d-flex gap-1 justify-content-end">
                                            <form action="{{ route('utilitas.submit', $lap->id) }}" method="POST">
                                                @csrf
                                                <button class="btn btn-sm btn-primary jasa-icon-btn" title="Kirim ke Admin Jasa" aria-label="Kirim ke Admin Jasa"><i class="bi bi-send"></i></button>
                                            </form>
                                            <form action="{{ route('utilitas.destroy', $lap->id) }}" method="POST" onsubmit="return confirm('Hapus laporan?');">
                                                @csrf @method('DELETE')
                                                <button class="btn btn-sm btn-outline-danger jasa-icon-btn" title="Hapus laporan" aria-label="Hapus laporan"><i class="bi bi-trash"></i></button>
                                            </form>
                                        </div>
                                    @elseif($lap->status == 'ditagihkan' && $lap->tagihan_jasa_id)
                                        <span class="mp-soft-badge success"><i class="bi bi-check-circle"></i>Selesai</span>
                                    @else
                                        <span class="text-muted small">-</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7">
                                    <div class="mp-empty d-flex flex-column align-items-center justify-content-center text-center py-4">
                                        <span class="mp-empty-icon"><i class="bi bi-inbox"></i></span>
                                        <div class="fw-bold">Belum ada riwayat laporan.</div>
                                        <div class="small">Laporan yang disimpan akan tampil di sini.</div>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($laporans->hasPages())
                <div class="card-footer bg-white border-0 pt-3">
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
        function refreshTypeCards() {
            $('.util-type-card').removeClass('selected');
            $('input[name="tipe_perhitungan"]:checked').closest('.util-type-card').addClass('selected');
        }

        function toggleSections() {
            var tipe = $('input[name="tipe_perhitungan"]:checked').val();
            refreshTypeCards();

            if (tipe === 'kwh') {
                $('#section-kwh').show();
                $('#section-flat').hide();
                $('[name="pemakaian_manual"]').removeAttr('required');
                $('#stan_awal, #stan_akhir').attr('required', true);
            } else {
                $('#section-kwh').hide();
                $('#section-flat').show();
                $('[name="pemakaian_manual"]').attr('required', true);
                $('#stan_awal, #stan_akhir').removeAttr('required');
            }
        }

        $('input[name="tipe_perhitungan"]').on('change', toggleSections);
        toggleSections();

        $('#stan_awal, #stan_akhir').on('input', function() {
            var awal = parseInt($('#stan_awal').val()) || 0;
            var akhir = parseInt($('#stan_akhir').val()) || 0;
            var pemakaian = Math.max(0, akhir - awal);
            $('#pemakaian_display').val(pemakaian + ' unit');
        }).trigger('input');

        $('.util-file-input').on('change', function() {
            var fileName = this.files && this.files.length ? this.files[0].name : '';
            var fileLabel = $('label[for="' + this.id + '"]');
            fileLabel.toggleClass('has-file', !!fileName);
            fileLabel.find('.util-file-name').text(fileName || (this.id === 'file_bukti_awal' ? 'Foto meteran awal. Max 5MB.' : 'Foto meteran akhir. Max 5MB.'));
        });

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
