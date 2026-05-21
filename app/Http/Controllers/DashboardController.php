<?php

namespace App\Http\Controllers;

use App\Models\MasterDipa;
use App\Models\RiwayatRevisiDipa;
use App\Models\MasterCoa;
use App\Models\DetailDipa;
use App\Models\KontrakPengadaan;
use App\Models\Tagihan;
use App\Models\TagihanJasa;
use App\Models\LayananJasa;
use App\Models\MasterMitraVendor;
use App\Models\MasterPihak;
use App\Models\MitraJasa;
use App\Models\DokumenSpp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * Internal Dashboard (KPA, Operator BLU, Bendahara, Kasubag, PPSPM, dll)
     */
    public function internal()
    {
        if (Auth::user()->hasRole('Koordinator Jasa') && ! Auth::user()->hasAnyRole(['Super Admin', 'Super Admin Jasa'])) {
            return redirect()->route('koordinator-jasa.dashboard');
        }
        if (Auth::user()->hasRole('Super Admin Jasa')) {
            return redirect()->route('super-admin-jasa.dashboard');
        }
        if (Auth::user()->hasRole('Pejabat Pengadaan')) {
            return $this->pejabatPengadaan();   
        }
        if (Auth::user()->hasRole('PPK')) {
            return $this->ppk();   
        }
        if (Auth::user()->hasRole('Bendahara Penerimaan')) {
            return redirect()->route('dashboard.bendahara-penerimaan');
        }
        if (Auth::user()->hasRole('Bendahara Pengeluaran')) {
            return redirect()->route('dashboard.bendahara-pengeluaran');
        }
        if (Auth::user()->hasRole('Admin Jasa')) {
            return redirect()->route('admin-jasa.dashboard');
        }
        if (Auth::user()->hasRole('Admin Konsesi')) {
            return redirect()->route('jasa.mitra.penjualan.index');
        }

        $now = now();

        // ============================================================
        // KPI CARDS
        // ============================================================
        $totalPagu = RiwayatRevisiDipa::where('is_active', true)->sum('total_pagu');
        $totalRealisasi = DB::table('realisasi_anggaran')->sum('nominal_cair');
        $sisaAnggaran = max(0, $totalPagu - $totalRealisasi);
        $persenRealisasi = $totalPagu > 0 ? round(($totalRealisasi / $totalPagu) * 100, 1) : 0;

        $totalKontrakAktif = KontrakPengadaan::where('status_kontrak', 'AKTIF')->count();
        $totalMitra = MasterMitraVendor::count();

        // Tagihan pipeline summary
        $tagihanPending = Tagihan::whereIn('status', ['DRAFT', 'PENDING_REVIEW', 'PENDING_BENDAHARA'])->count();
        $tagihanRevisi  = Tagihan::whereIn('status', ['DITOLAK_PPK', 'REVISI_BENDAHARA', 'REVISI'])->count();

        // SPP yang sudah diproses bulan ini
        $sppBulanIni = DokumenSpp::whereMonth('tanggal_spp', $now->month)
                        ->whereYear('tanggal_spp', $now->year)
                        ->count();

        // ============================================================
        // CHART: Serapan per Jenis Belanja (51, 52, 53, BLU)
        // ============================================================
        $jenisAkun = [
            ['label' => 'Belanja Pegawai (51)', 'pattern' => '51'],
            ['label' => 'Belanja Barang (52)',   'pattern' => '52'],
            ['label' => 'Belanja Modal (53)',    'pattern' => '53'],
            ['label' => 'Belanja BLU (525)',     'pattern' => '525'],
        ];

        $chartBarLabels = [];
        $chartBarPagu = [];
        $chartBarRealisasi = [];

        foreach ($jenisAkun as $ja) {
            $pagu = DB::table('dipa_revision_items')
                ->join('master_coas', 'dipa_revision_items.coa_id', '=', 'master_coas.id')
                ->where('master_coas.kd_akun', 'like', $ja['pattern'] . '%')
                ->sum('dipa_revision_items.nilai_pagu');
            
            $realisasi = DB::table('realisasi_anggaran')
                ->join('dipa_revision_items', 'realisasi_anggaran.dipa_revision_item_id', '=', 'dipa_revision_items.id')
                ->join('master_coas', 'dipa_revision_items.coa_id', '=', 'master_coas.id')
                ->where('master_coas.kd_akun', 'like', $ja['pattern'] . '%')
                ->sum('realisasi_anggaran.nominal_cair');
            
            $chartBarLabels[] = $ja['label'];
            $chartBarPagu[] = (float) $pagu;
            $chartBarRealisasi[] = (float) $realisasi;
        }

        $budgetItems = DetailDipa::query()
            ->join('master_coas', 'dipa_revision_items.coa_id', '=', 'master_coas.id')
            ->join('dipa_revisions', 'dipa_revision_items.dipa_revision_id', '=', 'dipa_revisions.id')
            ->join('master_dipas', 'dipa_revisions.master_dipa_id', '=', 'master_dipas.id')
            ->where('dipa_revisions.is_active', true)
            ->where('dipa_revision_items.status_aktif', true)
            ->select([
                'master_coas.kode_mak_lengkap as coa',
                'master_coas.nama_akun as description',
                'dipa_revision_items.nilai_pagu as initial_budget',
                'master_dipas.tahun_anggaran as year',
            ])
            ->latest('dipa_revision_items.updated_at')
            ->take(5)
            ->get();

        // ============================================================
        // CHART: Status Tagihan (Donut)
        // ============================================================
        $statusCounts = Tagihan::select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        // ============================================================
        // TABLE: Kontrak Aktif Terbaru
        // ============================================================
        $activeContracts = KontrakPengadaan::where('status_kontrak', 'AKTIF')
            ->with('vendor')
            ->latest()
            ->take(5)
            ->get();

        // ============================================================
        // TABLE: Tagihan Menunggu / Pending
        // ============================================================
        $pendingTagihan = Tagihan::whereIn('status', ['PENDING_REVIEW', 'PENDING_BENDAHARA'])
            ->latest()
            ->take(5)
            ->get();

        // ============================================================
        // TABLE: Kontrak Hampir Jatuh Tempo (H-14)
        // ============================================================
        $jatuhTempo = KontrakPengadaan::where('status_kontrak', 'AKTIF')
            ->where('tanggal_selesai', '<=', $now->copy()->addDays(14))
            ->with('vendor')
            ->orderBy('tanggal_selesai', 'asc')
            ->take(5)
            ->get();

        return view('dashboard.internal', compact(
            'totalPagu', 'totalRealisasi', 'sisaAnggaran', 'persenRealisasi',
            'totalKontrakAktif', 'totalMitra', 'tagihanPending', 'tagihanRevisi', 'sppBulanIni',
            'chartBarLabels', 'chartBarPagu', 'chartBarRealisasi',
            'statusCounts',
            'activeContracts', 'pendingTagihan', 'jatuhTempo', 'budgetItems'
        ));
    }

    /**
     * Mitra Portal Dashboard (External suppliers)
     */
    public function mitra()
    {
        $user = auth()->user();

        $profile = $user->profilable;
        if ($profile instanceof MitraJasa) {
            $vendor = $profile;
        } elseif ($profile instanceof MasterPihak) {
            $vendor = MasterPihak::find($profile->id);
        } else {
            $vendor = null;
        }

        $contracts = collect();
        $tagihan = collect();
        $isMitraJasaPortal = $vendor instanceof MitraJasa;
        $layananTreeItems = collect();
        $selectedLayananIds = [];
        $visibleLayananIds = [];

        if ($vendor instanceof MitraJasa) {
            $contracts = $vendor->kontrak()
                ->latest('tanggal_kontrak')
                ->latest('id')
                ->get();

            $tagihan = TagihanJasa::query()
                ->with(['kontrakMitraJasa', 'details.layananJasa'])
                ->where('mitra_jasa_id', $vendor->id)
                ->whereIn('status', ['PUBLISHED', 'LUNAS'])
                ->latest('tanggal_tagihan')
                ->latest('id')
                ->get();

            $selectedLayananIds = $vendor->layananJasaAktif()->pluck('layanan_jasas.id')->all();
            $visibleLayananIds = $this->buildVisibleLayananIds($selectedLayananIds);
            $layananTreeItems = LayananJasa::query()
                ->whereIn('id', $visibleLayananIds)
                ->orderBy('level')
                ->orderBy('id')
                ->get();
        } elseif ($vendor instanceof MasterPihak) {
            $contracts = KontrakPengadaan::where('vendor_id', $vendor->id)
                ->with(['termin'])
                ->latest()
                ->get();

            $contractIds = $contracts->pluck('id');

            // Tagihan terkait kontrak mitra ini (via detail_kontrak)
            $tagihan = Tagihan::where('tipe_tagihan', 'KONTRAK')
                ->with('potonganTagihan')
                ->whereHas('detailKontrak', function($q) use ($contractIds) {
                    $q->whereHas('kontrakTermin', function($q2) use ($contractIds) {
                        $q2->whereIn('kontrak_pengadaan_id', $contractIds);
                    });
                })
                ->latest()
                ->get();
        }

        // Summary stats for the mitra
        $totalKontrak = $contracts->count();
        $totalNilaiKontrak = $isMitraJasaPortal
            ? $contracts->sum('nilai_kontrak')
            : $contracts->sum('nilai_total_kontrak');
        $totalDibayar = $isMitraJasaPortal
            ? $tagihan->where('status', 'LUNAS')->sum('total_tagihan')
            : $tagihan->whereIn('status', ['CAIR', 'SP2D', 'SELESAI'])->sum('total_netto');
        $totalPending = $isMitraJasaPortal
            ? $tagihan->where('status', 'PUBLISHED')->sum('total_tagihan')
            : $tagihan->whereNotIn('status', ['CAIR', 'SP2D', 'SELESAI', 'DITOLAK_PPK'])->sum('total_netto');

        // ====== Chart datasets & calendar (mitra) ======
        $chartTagihanBulanan = ['labels' => [], 'jumlah' => [], 'nominal' => []];
        $chartStatus = ['labels' => [], 'data' => []];
        $persentaseLunas = 0;
        $calendar = [
            'month' => now()->month,
            'year' => now()->year,
            'monthLabel' => now()->translatedFormat('F Y'),
            'firstWeekday' => (int) now()->copy()->startOfMonth()->dayOfWeek,
            'daysInMonth' => now()->daysInMonth,
            'today' => (int) now()->day,
            'events' => [],
        ];

        if ($tagihan->isNotEmpty()) {
            $now = now();
            $start = $now->copy()->startOfMonth()->subMonths(11);
            $bulanLabels = [];
            $bulanJumlah = array_fill(0, 12, 0);
            $bulanNominal = array_fill(0, 12, 0.0);
            for ($i = 0; $i < 12; $i++) {
                $bulanLabels[] = $start->copy()->addMonths($i)->translatedFormat('M Y');
            }

            $statusCount = [];
            foreach ($tagihan as $t) {
                $tgl = $isMitraJasaPortal
                    ? ($t->tanggal_tagihan ?? $t->created_at)
                    : ($t->tanggal_tagihan ?? $t->created_at);
                if ($tgl) {
                    $tglC = \Illuminate\Support\Carbon::parse($tgl);
                    $diffM = ($tglC->year - $start->year) * 12 + ($tglC->month - $start->month);
                    if ($diffM >= 0 && $diffM < 12) {
                        $bulanJumlah[$diffM] += 1;
                        $nominal = $isMitraJasaPortal ? (float) ($t->total_tagihan ?? 0) : (float) ($t->total_netto ?? 0);
                        $bulanNominal[$diffM] += $nominal;
                    }
                }
                $st = $t->status ?? '-';
                $statusCount[$st] = ($statusCount[$st] ?? 0) + 1;
            }

            $chartTagihanBulanan = [
                'labels' => $bulanLabels,
                'jumlah' => array_values($bulanJumlah),
                'nominal' => array_values($bulanNominal),
            ];
            $chartStatus = [
                'labels' => array_map(fn ($s) => str_replace('_', ' ', $s), array_keys($statusCount)),
                'data' => array_values($statusCount),
            ];

            $totalT = max(1, $tagihan->count());
            $lunasCount = $isMitraJasaPortal
                ? $tagihan->where('status', 'LUNAS')->count()
                : $tagihan->whereIn('status', ['CAIR', 'SP2D', 'SELESAI', 'LUNAS'])->count();
            $persentaseLunas = round(($lunasCount / $totalT) * 100, 1);

            // Calendar events: tanggal_tagihan & tanggal_jatuh_tempo bulan ini
            $calMonth = (int) $now->month;
            $calYear = (int) $now->year;
            foreach ($tagihan as $t) {
                $tgl = $t->tanggal_tagihan ?? null;
                $tempo = $t->tanggal_jatuh_tempo ?? null;
                $st = $t->status ?? '-';
                $nomor = $t->nomor_tagihan ?? '-';

                if ($tgl) {
                    $c = \Illuminate\Support\Carbon::parse($tgl);
                    if ($c->month === $calMonth && $c->year === $calYear) {
                        $calendar['events'][(int) $c->day][] = [
                            'type' => 'terbit',
                            'label' => 'Tagihan terbit',
                            'nomor' => $nomor,
                            'status' => $st,
                        ];
                    }
                }
                if ($tempo) {
                    $c = \Illuminate\Support\Carbon::parse($tempo);
                    if ($c->month === $calMonth && $c->year === $calYear) {
                        $calendar['events'][(int) $c->day][] = [
                            'type' => $st === 'LUNAS' ? 'lunas' : 'jatuh_tempo',
                            'label' => $st === 'LUNAS' ? 'Lunas' : 'Jatuh tempo',
                            'nomor' => $nomor,
                            'status' => $st,
                        ];
                    }
                }
            }
        }

        return view('dashboard.mitra', compact(
            'vendor', 'contracts', 'tagihan',
            'totalKontrak', 'totalNilaiKontrak', 'totalDibayar', 'totalPending',
            'isMitraJasaPortal',
            'layananTreeItems',
            'selectedLayananIds',
            'visibleLayananIds',
            'chartTagihanBulanan',
            'chartStatus',
            'persentaseLunas',
            'calendar'
        ));
    }

    private function buildVisibleLayananIds(array $selectedIds): array
    {
        $visibleIds = collect($selectedIds);
        $items = LayananJasa::query()
            ->whereIn('id', $selectedIds)
            ->with('parent.parent.parent.parent.parent')
            ->get();

        foreach ($items as $item) {
            $parent = $item->parent;
            $guard = 0;

            while ($parent && $guard < 10) {
                $visibleIds->push($parent->id);
                $parent = $parent->parent;
                $guard++;
            }
        }

        return $visibleIds->unique()->values()->all();
    }
    /**
     * Dashboard khusus Role Pejabat Pengadaan
     */
    private function pejabatPengadaan()
    {
        $data = \Illuminate\Support\Facades\Cache::remember('dash_pejabat_pengadaan_' . auth()->id(), 60, function() {
            $now = now();
            
            // KPI 1: Kontrak Aktif
            $kontrakAktif = \App\Models\KontrakPengadaan::where('status_kontrak', 'AKTIF')->count();
            $selesaiBulanIni = \App\Models\KontrakPengadaan::where('status_kontrak', 'SELESAI')
                                ->whereMonth('tanggal_selesai', $now->month)
                                ->whereYear('tanggal_selesai', $now->year)
                                ->count();
            
            // KPI 2: Total Nilai Berjalan
            $nilaiKontrakAktif = \App\Models\KontrakPengadaan::where('status_kontrak', 'AKTIF')
                                ->sum('nilai_total_kontrak');
                                
            // KPI 3: Tagihan Menunggu (Pending PPK/Bendahara)
            $tagihanMenunggu = \App\Models\Tagihan::whereIn('status', ['PENDING_REVIEW', 'PENDING_BENDAHARA'])->count();
            
            // KPI 4: Butuh Perhatian (Ditolak / Revisi)
            $tagihanRevisi = \App\Models\Tagihan::whereIn('status', ['DITOLAK_PPK', 'REVISI_BENDAHARA', 'REVISI'])->count();

            // CHART KIRI: Serapan Anggaran Kontrak (Pagu DIPA vs Kontrak)
            $totalPaguDipa = \App\Models\RiwayatRevisiDipa::where('is_active', true)->sum('total_pagu');
            $chartSerapan = [
                'Terpakai (Nilai Kontrak)' => \App\Models\KontrakPengadaan::where('status_kontrak', '!=', 'DIBATALKAN')->sum('nilai_total_kontrak'),
                'Sisa Pagu Khusus' => max(0, $totalPaguDipa - \App\Models\KontrakPengadaan::where('status_kontrak', '!=', 'DIBATALKAN')->sum('nilai_total_kontrak'))
            ];

            // CHART KANAN: Distribusi Status Tagihan
            $chartStatusTagihan = \App\Models\Tagihan::where('tipe_tagihan', 'KONTRAK')
                ->select('status', \Illuminate\Support\Facades\DB::raw('count(*) as total'))
                ->groupBy('status')
                ->pluck('total', 'status')
                ->toArray();

            // TABEL KIRI: Peringatan Jatuh Tempo (H-14)
            $jatuhTempo = \App\Models\KontrakPengadaan::where('status_kontrak', 'AKTIF')
                        ->where('tanggal_selesai', '<=', $now->copy()->addDays(14))
                        ->orderBy('tanggal_selesai', 'asc')
                        ->take(5)
                        ->get();

            // TABEL KANAN: Tagihan Dikembalikan / Revisi
            $tagihanBermasalah = \App\Models\Tagihan::whereIn('status', ['DITOLAK_PPK', 'REVISI_BENDAHARA', 'REVISI'])
                                ->with(['logs' => function($q) {
                                    $q->latest()->limit(1); // Ambil log penolakan terakhir
                                }])
                                ->latest()
                                ->take(5)
                                ->get();

            return [
                'kpi' => [
                    'kontrak_aktif' => $kontrakAktif,
                    'selesai_bulan_ini' => $selesaiBulanIni,
                    'nilai_kontrak_aktif' => $nilaiKontrakAktif,
                    'tagihan_menunggu' => $tagihanMenunggu,
                    'tagihan_revisi' => $tagihanRevisi,
                ],
                'chart_serapan_labels' => array_keys($chartSerapan),
                'chart_serapan_data' => array_values($chartSerapan),
                'chart_status_labels' => array_keys($chartStatusTagihan),
                'chart_status_data' => array_values($chartStatusTagihan),
                'table_jatuh_tempo' => $jatuhTempo,
                'table_tagihan_revisi' => $tagihanBermasalah,
            ];
        });

        return view('dashboard.pejabat_pengadaan', $data);
    }

    /**
     * Dashboard khusus Role PPK
     */
    private function ppk()
    {
        $data = \Illuminate\Support\Facades\Cache::remember('dash_ppk_' . auth()->id(), 60, function() {
            $now = now();
            
            // KPI 1: SPK Baru
            $kpi_kontrak_baru = \App\Models\KontrakPengadaan::where('status_kontrak', 'PENDING_REVIEW')->count();
            
            // KPI 2: Tagihan BAST
            $kpi_tagihan_bast = \App\Models\Tagihan::where('status', 'PENDING_REVIEW')->count();
            
            // KPI 3: Pencairan
            $spp = DB::table('dokumen_spp')->where('status', 'DRAFT')->count();
            $npi = DB::table('dokumen_npi')->where('status', 'DRAFT')->count();
            $sp2d = DB::table('dokumen_sp2d')->where('status', 'DRAFT')->count();
            $kpi_pencairan = $spp + $npi + $sp2d;

            // KPI 4: Sisa Pagu DIPA
            $totalPaguDipa = DB::table('dipa_revisions')->where('is_active', true)->sum('total_pagu');
            $totalRealisasi = DB::table('realisasi_anggaran')->sum('nominal_cair');
            $sisaPagu = max(0, $totalPaguDipa - $totalRealisasi);

            // Alert Kritis Belanja Modal 53
            $pagu53 = DB::table('dipa_revision_items')
                ->join('master_coas', 'dipa_revision_items.coa_id', '=', 'master_coas.id')
                ->where('master_coas.kode_mak_lengkap', 'like', '%53%')
                ->sum('dipa_revision_items.nilai_pagu');
            $realisasi53 = DB::table('realisasi_anggaran')
                ->join('dipa_revision_items', 'realisasi_anggaran.dipa_revision_item_id', '=', 'dipa_revision_items.id')
                ->join('master_coas', 'dipa_revision_items.coa_id', '=', 'master_coas.id')
                ->where('master_coas.kode_mak_lengkap', 'like', '%53%')
                ->sum('realisasi_anggaran.nominal_cair');
                
            $alertModal = null;
            if ($pagu53 > 0) {
                $sisa53 = $pagu53 - $realisasi53;
                $persen53 = ($sisa53 / $pagu53) * 100;
                if ($persen53 < 10) {
                    $alertModal = "Peringatan: Pagu DIPA untuk Belanja Modal (MAK 53) tersisa kurang dari 10% (Sisa " . number_format($persen53, 1) . "%)!";
                }
            }

            // Charts
            // Doughnut: Serapan DIPA
            $chartSerapan = [
                'Terserap (SP2D)' => $totalRealisasi,
                'Sisa Pagu' => $sisaPagu
            ];

            // Bar: Serapan 51, 52, 53
            $pagu51 = DB::table('dipa_revision_items')->join('master_coas', 'dipa_revision_items.coa_id', '=', 'master_coas.id')->where('master_coas.kode_mak_lengkap', 'like', '%51%')->sum('dipa_revision_items.nilai_pagu');
            $pagu52 = DB::table('dipa_revision_items')->join('master_coas', 'dipa_revision_items.coa_id', '=', 'master_coas.id')->where('master_coas.kode_mak_lengkap', 'like', '%52%')->sum('dipa_revision_items.nilai_pagu');
            
            $realisasi51 = DB::table('realisasi_anggaran')->join('dipa_revision_items', 'realisasi_anggaran.dipa_revision_item_id', '=', 'dipa_revision_items.id')->join('master_coas', 'dipa_revision_items.coa_id', '=', 'master_coas.id')->where('master_coas.kode_mak_lengkap', 'like', '%51%')->sum('realisasi_anggaran.nominal_cair');
            $realisasi52 = DB::table('realisasi_anggaran')->join('dipa_revision_items', 'realisasi_anggaran.dipa_revision_item_id', '=', 'dipa_revision_items.id')->join('master_coas', 'dipa_revision_items.coa_id', '=', 'master_coas.id')->where('master_coas.kode_mak_lengkap', 'like', '%52%')->sum('realisasi_anggaran.nominal_cair');

            $chartBarLabels = ['Belanja Pegawai (51)', 'Belanja Barang (52)', 'Belanja Modal (53)'];
            $chartBarPagu = [$pagu51, $pagu52, $pagu53];
            $chartBarRealisasi = [$realisasi51, $realisasi52, $realisasi53];

            // Tabs Data
            // Kontrak Baru
            $tabKontrak = \App\Models\KontrakPengadaan::where('status_kontrak', 'PENDING_REVIEW')->latest()->get();
            // Tagihan
            $tabTagihan = \App\Models\Tagihan::where('status', 'PENDING_REVIEW')->latest()->get();
            
            // Pencairan (Union of SPP, NPI, SP2D)
            $pembuatJoin = function ($query) {
                $query
                    ->leftJoin('master_pegawai', function ($join) {
                        $join->on('master_pegawai.id', '=', 'users.profilable_id')
                            ->where('users.profilable_type', '=', \App\Models\MasterPegawai::class);
                    })
                    ->leftJoin('master_pihak', function ($join) {
                        $join->on('master_pihak.id', '=', 'users.profilable_id')
                            ->whereIn('users.profilable_type', [\App\Models\MasterPihak::class, \App\Models\MasterMitraVendor::class]);
                    });
            };
            $pembuatExpr = DB::raw('COALESCE(master_pegawai.nama_lengkap, master_pihak.nama_pihak, users.email) as pembuat');

            $listSpp = DB::table('dokumen_spp')
                ->join('users', 'dokumen_spp.dibuat_oleh_id', '=', 'users.id')
                ->tap($pembuatJoin)
                ->select('dokumen_spp.id', 'dokumen_spp.nomor_spp as nomor', 'dokumen_spp.nominal_spp as nilai', $pembuatExpr)
                ->where('dokumen_spp.status', 'DRAFT')
                ->get()->map(function($i) { $i->jenis = 'SPP'; $i->prioritas = 'Sedang'; $i->url_reject = route('verifikasi-ppk.spp.revisi', $i->id); $i->url_approve = route('verifikasi-ppk.spp.approve', $i->id); return $i; });
                
            $listNpi = DB::table('dokumen_npi')
                ->join('users', 'dokumen_npi.bendahara_penerimaan_id', '=', 'users.id')
                ->tap($pembuatJoin)
                ->join('dokumen_spm', 'dokumen_npi.spm_id', '=', 'dokumen_spm.id')
                ->join('dokumen_spp', 'dokumen_spm.spp_id', '=', 'dokumen_spp.id')
                ->select('dokumen_npi.id', 'dokumen_npi.nomor_npi as nomor', 'dokumen_spp.nominal_spp as nilai', $pembuatExpr)
                ->where('dokumen_npi.status', 'DRAFT')
                ->get()->map(function($i) { $i->jenis = 'NPI'; $i->prioritas = 'Sedang'; $i->url_reject = route('verifikasi-ppk.npi.revisi', $i->id); $i->url_approve = route('verifikasi-ppk.npi.approve', $i->id);  return $i; });
                
            $listSp2d = DB::table('dokumen_sp2d')
                ->join('users', 'dokumen_sp2d.bendahara_pengeluaran_id', '=', 'users.id')
                ->tap($pembuatJoin)
                ->join('dokumen_npi', 'dokumen_sp2d.npi_id', '=', 'dokumen_npi.id')
                ->join('dokumen_spm', 'dokumen_npi.spm_id', '=', 'dokumen_spm.id')
                ->join('dokumen_spp', 'dokumen_spm.spp_id', '=', 'dokumen_spp.id')
                ->select('dokumen_sp2d.id', 'dokumen_sp2d.nomor_sp2d as nomor', 'dokumen_spp.nominal_spp as nilai', $pembuatExpr)
                ->where('dokumen_sp2d.status', 'DRAFT')
                ->get()->map(function($i) { $i->jenis = 'SP2D'; $i->prioritas = 'Tinggi'; $i->url_reject = '#'; $i->url_approve = '#'; return $i; });

            $tabPencairan = $listSpp->concat($listNpi)->concat($listSp2d);

            return [
                'alertModal' => $alertModal,
                'kpi' => [
                    'kontrak_baru' => $kpi_kontrak_baru,
                    'tagihan_bast' => $kpi_tagihan_bast,
                    'pencairan' => $kpi_pencairan,
                    'sisa_pagu' => $sisaPagu,
                    'total_pagu' => $totalPaguDipa,
                ],
                'chart_serapan_labels' => array_keys($chartSerapan),
                'chart_serapan_data' => array_values($chartSerapan),
                'chart_bar_labels' => $chartBarLabels,
                'chart_bar_pagu' => $chartBarPagu,
                'chart_bar_realisasi' => $chartBarRealisasi,
                'tab_kontrak' => $tabKontrak,
                'tab_tagihan' => $tabTagihan,
                'tab_pencairan' => $tabPencairan,
            ];
        });

        return view('dashboard.ppk', $data);
    }
}
