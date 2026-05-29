@extends('layouts.app')
@section('title', 'Manajemen SPP Perjaldin')

@push('css')
    <link href="{{ URL::asset('build/plugins/datatable/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet" />
    <style>
        .table-custom-hover tbody tr:hover { background-color: #f8f9fa; }
        .stat-card {
            transition: transform .2s ease, box-shadow .2s ease;
            min-height: 175px;
        }
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 .75rem 1.25rem rgba(0,0,0,.08) !important;
        }
        .stat-icon {
            width: 44px; height: 44px;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
            font-size: 1.25rem;
        }
        .stat-value {
            font-size: 1.65rem;
            line-height: 1.2;
            letter-spacing: -.5px;
        }
        .stat-deco {
            position: absolute;
            left: 0;
            right: 0;
            bottom: 0;
            height: 60px;
            overflow: hidden;
            pointer-events: none;
        }
        .stat-wave {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 200%;
            height: 100%;
        }
        .stat-wave-1 { opacity: .18; animation: stat-wave-scroll 9s linear infinite; }
        .stat-wave-2 { opacity: .28; animation: stat-wave-scroll 13s linear infinite reverse; }
        .stat-wave-3 { opacity: .40; animation: stat-wave-scroll 17s linear infinite; }
        @keyframes stat-wave-scroll {
            from { transform: translate3d(0, 0, 0); }
            to   { transform: translate3d(-50%, 0, 0); }
        }
        @media (prefers-reduced-motion: reduce) {
            .stat-wave-1, .stat-wave-2, .stat-wave-3 { animation: none; }
        }

        /* ===== Tab Switcher ===== */
        .spp-tabs { display: inline-flex; gap: .35rem; background: #eef1f7; border-radius: 999px; padding: .3rem; border: 1px solid #e4e8f0; }
        .spp-tabs .nav-link {
            display: inline-flex; align-items: center; gap: .45rem;
            border: 0; border-radius: 999px;
            padding: .5rem 1.1rem; font-size: .85rem; font-weight: 700; color: #64748b;
            background: transparent; transition: all .25s ease;
        }
        .spp-tabs .nav-link:hover { color: #4338ca; }
        .spp-tabs .nav-link.active { background: #fff; color: #4338ca; box-shadow: 0 4px 12px rgba(79,70,229,.16); }
        .spp-tabs .tab-count { font-size: .72rem; font-weight: 800; min-width: 20px; height: 20px; padding: 0 .35rem; border-radius: 999px; display: inline-flex; align-items: center; justify-content: center; background: #cbd5e1; color: #fff; }
        .spp-tabs .nav-link.active .tab-count { background: #6366f1; }
        .spp-tabs .nav-link.tab-revisi .tab-count { background: #fca5a5; }
        .spp-tabs .nav-link.tab-revisi.active { color: #b91c1c; }
        .spp-tabs .nav-link.tab-revisi.active .tab-count { background: #ef4444; }
        .tab-pane.fade { transition: opacity .25s ease; }

        /* ===== Revisi cards ===== */
        @keyframes revIn { from { opacity: 0; transform: translateY(14px); } to { opacity: 1; transform: none; } }
        .revisi-list { display: flex; flex-direction: column; gap: 1rem; }
        .revisi-card {
            position: relative; overflow: hidden;
            background: #fff; border: 1px solid #fecaca; border-left: 5px solid #ef4444;
            border-radius: 1rem; padding: 1.1rem 1.3rem;
            box-shadow: 0 4px 12px rgba(15,23,42,.04);
            animation: revIn .45s cubic-bezier(.22,1,.36,1) both; animation-delay: calc(.06s * var(--ri, 0));
            transition: transform .25s ease, box-shadow .25s ease;
        }
        .revisi-card:hover { transform: translateY(-3px); box-shadow: 0 14px 30px rgba(239,68,68,.12); }
        .rev-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; flex-wrap: wrap; }
        .rev-no { display: block; font-weight: 800; color: #0f172a; font-size: 1rem; }
        .rev-desc { display: block; font-size: .82rem; color: #64748b; margin-top: .1rem; }
        .rev-badge { display: inline-flex; align-items: center; gap: .35rem; font-size: .72rem; font-weight: 700; padding: .3rem .75rem; border-radius: 999px; background: #fee2e2; color: #b91c1c; white-space: nowrap; }
        .rev-note { display: flex; gap: .55rem; background: #fff7f7; border: 1px solid #fecaca; border-radius: .7rem; padding: .7rem .85rem; margin: .85rem 0; font-size: .86rem; color: #7f1d1d; line-height: 1.5; }
        .rev-note i { color: #ef4444; font-size: 1rem; margin-top: 1px; flex-shrink: 0; }
        .rev-foot { display: flex; align-items: center; justify-content: space-between; gap: .75rem; flex-wrap: wrap; }
        .rev-meta { display: flex; align-items: center; gap: 1.1rem; flex-wrap: wrap; font-size: .76rem; color: #64748b; }
        .rev-meta span { display: inline-flex; align-items: center; gap: .35rem; }
        .rev-empty { text-align: center; padding: 3rem 1rem; color: #94a3b8; }
        .rev-empty i { font-size: 3rem; display: block; margin-bottom: .75rem; opacity: .5; color: #10b981; }
    </style>
@endpush

@section('content')
    <x-page-title title="Pembuatan SPP Perjaldin" subtitle="Kelola item biaya Perjaldin yang siap diproses menjadi SPP" />

    @if(session('success'))
        <div class="alert alert-success border-0 bg-success alert-dismissible fade show shadow-sm text-white">
            <i class="bi bi-check-circle me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error') || $errors->any())
        <div class="alert alert-danger border-0 bg-danger alert-dismissible fade show shadow-sm text-white">
            <i class="bi bi-exclamation-triangle me-2"></i>
            {{ session('error') ?? $errors->first() }}
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @include('spps.partials.perjaldin_index_summary')

    @php $jmlRevisi = isset($revisis) ? $revisis->count() : 0; @endphp

    <ul class="nav spp-tabs mb-3" id="perjaldinTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="tab-siap-btn" data-bs-toggle="pill" data-bs-target="#tab-siap" type="button" role="tab">
                <i class="bi bi-list-check"></i> Siap SPP
                <span class="tab-count">{{ $perjaldins->count() }}</span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link tab-revisi" id="tab-revisi-btn" data-bs-toggle="pill" data-bs-target="#tab-revisi" type="button" role="tab">
                <i class="bi bi-arrow-counterclockwise"></i> Revisi
                <span class="tab-count">{{ $jmlRevisi }}</span>
            </button>
        </li>
    </ul>

    <div class="tab-content" id="perjaldinTabContent">
    <div class="tab-pane fade show active" id="tab-siap" role="tabpanel">
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold"><i class="bi bi-list-check me-2 text-primary"></i>Daftar Tagihan Perjaldin Siap SPP</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive p-2">
                <table id="example" class="table table-custom-hover align-middle mb-0" style="width:100%">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Dokumen Perjaldin</th>
                            <th>Total Netto</th>
                            <th>Info Item Aktif</th>
                            <th>Status Kesiapan SPP</th>
                            <th class="text-center pe-4">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($perjaldins as $idx => $perjaldin)
                            @php
                                $komponens = $perjaldin->komponenPerjaldin->where('total_nominal', '>', 0);
                                $jmlAktif = $komponens->count();
                                $jmlSpp = $komponens->filter(fn($x) => $x->hasDokumenTurunan())->count();

                                if($jmlAktif == 0) {
                                    $progressStatus = ['text' => 'Kosong', 'bg' => 'bg-secondary', 'icon' => 'bi-dash-circle'];
                                } elseif($jmlSpp < $jmlAktif) {
                                    $progressStatus = ['text' => 'Siap Buat SPP', 'bg' => 'bg-primary', 'icon' => 'bi-file-earmark-plus'];
                                } elseif($jmlSpp == $jmlAktif) {
                                    $isAllDone = $komponens->every(fn($x) => in_array($x->status_proses, ['DISETUJUI_SPP', 'LANJUT_SPM', 'SELESAI']));
                                    if ($isAllDone) {
                                        $progressStatus = ['text' => 'SPP Lengkap', 'bg' => 'bg-success', 'icon' => 'bi-check-circle'];
                                    } else {
                                        $progressStatus = ['text' => 'Dalam Verifikasi SPP', 'bg' => 'bg-info text-white', 'icon' => 'bi-hourglass-split'];
                                    }
                                }
                            @endphp
                            <tr>
                                <td class="ps-4 py-3">
                                    <div class="fw-bold text-dark">{{ $perjaldin->nomor_tagihan }}</div>
                                    <div class="small text-muted mb-1 text-truncate" style="max-width: 300px;">{{ $perjaldin->deskripsi }}</div>
                                    <div class="small text-secondary">
                                        <i class="bi bi-calendar-check me-1"></i> Disetujui: {{ $perjaldin->waktu_verifikasi_ppk ? $perjaldin->waktu_verifikasi_ppk->format('d M Y') : '-' }}
                                    </div>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark">Rp {{ number_format($perjaldin->total_netto, 0, ',', '.') }}</div>
                                    <div class="small text-muted">{{ $perjaldin->detailPerjaldin->count() }} Peserta</div>
                                </td>
                                <td>
                                    @if($jmlAktif > 0)
                                        <div class="d-flex flex-column gap-1">
                                            <div class="small"><strong>{{ $jmlAktif }}</strong> Item Biaya</div>
                                            <div class="small text-muted"><i class="bi bi-file-text-fill me-1 text-primary"></i>{{ $jmlSpp }} draft SPP</div>
                                        </div>
                                    @else
                                        <span class="text-muted small">Tidak ada item > Rp 0</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge {{ $progressStatus['bg'] }} px-3 py-2 rounded-pill">
                                        <i class="bi {{ $progressStatus['icon'] }} me-1"></i> {{ $progressStatus['text'] }}
                                    </span>
                                </td>
                                <td class="text-center pe-4">
                                    <a href="{{ route('spps.perjaldin.detail', $perjaldin->id) }}" class="btn btn-sm btn-outline-primary px-3 rounded-pill fw-bold">
                                        <i class="bi bi-gear me-1"></i> Kelola Item
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-5">
                                    <i class="bi bi-folder2-open text-muted" style="font-size: 3rem;"></i>
                                    <h6 class="mt-3 fw-bold">Belum Ada Dokumen Perjaldin Siap SPP</h6>
                                    <p class="text-muted small mb-0">Tagihan perjaldin yang telah diverifikasi akan muncul di sini.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    </div>{{-- /#tab-siap --}}

    <div class="tab-pane fade" id="tab-revisi" role="tabpanel">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold"><i class="bi bi-arrow-counterclockwise me-2 text-danger"></i>Perjaldin yang Anda Kembalikan untuk Revisi</h6>
                @if($jmlRevisi > 0)<span class="badge bg-danger-subtle text-danger rounded-pill px-3">{{ $jmlRevisi }} Dokumen</span>@endif
            </div>
            <div class="card-body">
                @forelse(($revisis ?? collect()) as $i => $rev)
                    @php
                        $revLog = $rev->logs->where('aksi', 'KEMBALIKAN_REVISI_COA')->sortByDesc('created_at')->first();
                        $catatan = $revLog?->catatan;
                        // Bersihkan prefix "Dikembalikan oleh Operator BLU. Alasan: " jika ada.
                        if ($catatan && \Illuminate\Support\Str::contains($catatan, 'Alasan:')) {
                            $catatan = trim(\Illuminate\Support\Str::after($catatan, 'Alasan:'));
                        }
                    @endphp
                    <div class="revisi-card" style="--ri: {{ $i }};">
                        <div class="rev-head">
                            <div>
                                <span class="rev-no"><i class="bi bi-receipt me-1 text-danger"></i>{{ $rev->nomor_tagihan }}</span>
                                <span class="rev-desc">{{ $rev->deskripsi }}</span>
                            </div>
                            <span class="rev-badge"><i class="bi bi-arrow-return-left"></i> Dikembalikan</span>
                        </div>

                        <div class="rev-note">
                            <i class="bi bi-chat-quote-fill"></i>
                            <div><strong>Catatan revisi:</strong> {{ $catatan ?: 'Tidak ada catatan tercatat.' }}</div>
                        </div>

                        <div class="rev-foot">
                            <div class="rev-meta">
                                <span><i class="bi bi-clock-history"></i> {{ $revLog?->created_at ? $revLog->created_at->translatedFormat('d M Y, H:i') : '-' }}</span>
                                <span><i class="bi bi-cash-stack"></i> Rp {{ number_format($rev->total_netto, 0, ',', '.') }}</span>
                                <span><i class="bi bi-people"></i> {{ $rev->detailPerjaldin->count() }} Peserta</span>
                            </div>
                            <a href="{{ route('perjaldins.show', $rev->id) }}" class="btn btn-sm btn-outline-danger px-3 rounded-pill fw-bold">
                                <i class="bi bi-box-arrow-up-right me-1"></i> Lihat Detail
                            </a>
                        </div>
                    </div>
                @empty
                    <div class="rev-empty">
                        <i class="bi bi-check2-circle"></i>
                        <h6 class="fw-bold mb-1">Tidak Ada Dokumen Menunggu Revisi</h6>
                        <p class="small mb-0">Semua dokumen Perjaldin yang Anda kembalikan telah ditindaklanjuti.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>{{-- /#tab-revisi --}}
    </div>{{-- /.tab-content --}}
@endsection

@push('script')
    <script src="{{ URL::asset('build/plugins/datatable/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/datatable/js/dataTables.bootstrap5.min.js') }}"></script>
    <script>
        $(document).ready(function() { 
            $('#example').DataTable({
                "pageLength": 10,
                "ordering": false, /* Custom ordering visually implies a priority, so disable default DataTable ordering which messes up with badges */
                "language": {
                    "search": "Cari Dokumen:",
                    "lengthMenu": "Tampilkan _MENU_ data",
                    "info": "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
                    "infoEmpty": "Tidak ada data yang tersedia",
                    "paginate": {
                        "next": "Selanjutnya",
                        "previous": "Sebelumnya"
                    }
                }
            });

            // Aktifkan tab dari hash URL (#tab-revisi) & simpan hash saat tab diganti.
            var hash = window.location.hash;
            if (hash === '#tab-revisi') {
                var trigger = document.querySelector('#tab-revisi-btn');
                if (trigger) new bootstrap.Tab(trigger).show();
            }
            document.querySelectorAll('#perjaldinTab button[data-bs-toggle="pill"]').forEach(function (btn) {
                btn.addEventListener('shown.bs.tab', function (e) {
                    history.replaceState(null, '', e.target.getAttribute('data-bs-target'));
                });
            });
        });
    </script>
@endpush
