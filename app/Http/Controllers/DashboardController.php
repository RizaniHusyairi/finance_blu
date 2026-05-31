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
use App\Models\DetailPerjaldin;
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
     * Dashboard Khusus PLT/PLH
     */
    public function pltPlh()
    {
        $user = Auth::user();
        $workflowService = app(\App\Services\WorkflowService::class);
        
        $userRoles = $user->getRoleNames()->toArray();
        if (in_array('PLT/PLH', $userRoles, true) && !in_array('KPA', $userRoles, true)) {
            $userRoles[] = 'KPA';
        }
        if (in_array('KPA', $userRoles, true) && !in_array('PLT/PLH', $userRoles, true)) {
            $userRoles[] = 'PLT/PLH';
        }

        // Tagihan Jasa Pending Verification
        $tagihans = TagihanJasa::with(['mitra', 'creator', 'workflowInstance.approvals'])
            ->whereHas('workflowInstance', function ($q) use ($userRoles) {
                $q->where('status', 'IN_PROGRESS')
                  ->whereHas('approvals', function ($q2) use ($userRoles) {
                      $q2->where('status', 'PENDING')
                         ->whereIn('role_code', $userRoles);
                  });
            })
            ->latest()
            ->get();
            
        $pendingTagihan = $tagihans->filter(function($tagihan) use ($workflowService, $user) {
            return $workflowService->hasPendingApprovalForUser($tagihan, $user->id);
        });

        // Tagihan Jasa Approved by PLT/PLH
        $approvedTagihan = TagihanJasa::with(['mitra', 'creator'])
            ->whereHas('workflowInstance.approvals', function ($q) use ($userRoles, $user) {
                $q->where('status', 'APPROVED')
                  ->where('acted_by_user_id', $user->id)
                  ->whereIn('role_code', $userRoles);
            })
            ->latest()
            ->take(5)
            ->get();
            
        $totalPending = $pendingTagihan->count();
        $totalApproved = TagihanJasa::whereHas('workflowInstance.approvals', function ($q) use ($userRoles, $user) {
                $q->where('status', 'APPROVED')
                  ->where('acted_by_user_id', $user->id)
                  ->whereIn('role_code', $userRoles);
            })->count();

        return view('dashboard.plt_plh', compact('pendingTagihan', 'approvedTagihan', 'totalPending', 'totalApproved'));
    }

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
        if (Auth::user()->hasRole('PPABP')) {
            return $this->ppabp();
        }
        if (Auth::user()->hasRole('Operator Perjaldin')) {
            return $this->operatorPerjaldin();
        }
        if (Auth::user()->hasRole('PPSPM')) {
            return $this->ppspm();
        }
        if (Auth::user()->hasRole('Koordinator Keuangan')) {
            return $this->koordinatorKeuangan();
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
        if (Auth::user()->hasRole('PLT/PLH')) {
            return $this->pltPlh();
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
        $data = \Illuminate\Support\Facades\Cache::remember('dash_pejabat_pengadaan_' . auth()->id(), 60, function () {
            $now = now();
            $tahun = $now->year;

            // ============================================================
            // KPI METRICS
            // ============================================================
            $kontrakAktif = \App\Models\KontrakPengadaan::where('status_kontrak', 'AKTIF')->count();
            $kontrakDraft = \App\Models\KontrakPengadaan::where('status_kontrak', 'DRAFT')->count();
            $kontrakPending = \App\Models\KontrakPengadaan::where('status_kontrak', 'PENDING_REVIEW')->count();
            $kontrakSelesai = \App\Models\KontrakPengadaan::where('status_kontrak', 'SELESAI')->count();
            $kontrakDibatalkan = \App\Models\KontrakPengadaan::where('status_kontrak', 'DIBATALKAN')->count();
            $kontrakTotal = $kontrakAktif + $kontrakDraft + $kontrakPending + $kontrakSelesai + $kontrakDibatalkan;

            $selesaiBulanIni = \App\Models\KontrakPengadaan::where('status_kontrak', 'SELESAI')
                ->whereMonth('tanggal_selesai', $now->month)
                ->whereYear('tanggal_selesai', $now->year)
                ->count();

            $nilaiKontrakAktif = \App\Models\KontrakPengadaan::where('status_kontrak', 'AKTIF')->sum('nilai_total_kontrak');
            $nilaiKontrakSelesai = \App\Models\KontrakPengadaan::where('status_kontrak', 'SELESAI')->sum('nilai_total_kontrak');
            $nilaiKontrakDraft = \App\Models\KontrakPengadaan::where('status_kontrak', 'DRAFT')->sum('nilai_total_kontrak');
            $totalNilaiSemua = $nilaiKontrakAktif + $nilaiKontrakSelesai + $nilaiKontrakDraft;

            // Vendor / Mitra
            $totalVendor = \App\Models\MasterMitraVendor::count();
            $vendorAktifIds = \App\Models\KontrakPengadaan::whereIn('status_kontrak', ['AKTIF', 'PENDING_REVIEW'])
                ->pluck('vendor_id')->unique();
            $vendorAktif = \App\Models\MasterMitraVendor::whereIn('id', $vendorAktifIds)->count();

            // Tagihan kontrak
            $tagihanMenunggu = \App\Models\Tagihan::whereIn('status', ['PENDING_REVIEW', 'PENDING_BENDAHARA'])->count();
            $tagihanRevisi = \App\Models\Tagihan::whereIn('status', ['DITOLAK_PPK', 'REVISI_BENDAHARA', 'REVISI'])->count();

            // ============================================================
            // CHART: Serapan Anggaran Kontrak
            // ============================================================
            $totalPaguDipa = \App\Models\RiwayatRevisiDipa::where('is_active', true)->sum('total_pagu');
            $totalKontrakNonBatal = \App\Models\KontrakPengadaan::where('status_kontrak', '!=', 'DIBATALKAN')->sum('nilai_total_kontrak');
            $chartSerapan = [
                'Terpakai Kontrak' => $totalKontrakNonBatal,
                'Sisa Pagu DIPA'    => max(0, $totalPaguDipa - $totalKontrakNonBatal),
            ];

            // ============================================================
            // CHART: Tren Kontrak Baru 6 Bulan
            // ============================================================
            $trenLabels = [];
            $trenJumlah = [];
            $trenNilai = [];
            for ($i = 5; $i >= 0; $i--) {
                $month = $now->copy()->subMonths($i);
                $trenLabels[] = $month->isoFormat('MMM YY');
                $jumlah = \App\Models\KontrakPengadaan::whereYear('created_at', $month->year)
                    ->whereMonth('created_at', $month->month)
                    ->count();
                $nilai = (float) \App\Models\KontrakPengadaan::whereYear('created_at', $month->year)
                    ->whereMonth('created_at', $month->month)
                    ->sum('nilai_total_kontrak');
                $trenJumlah[] = $jumlah;
                $trenNilai[] = $nilai;
            }

            // ============================================================
            // CHART: Distribusi Status Kontrak
            // ============================================================
            $statusKontrakDistribusi = [
                'AKTIF' => $kontrakAktif,
                'PENDING_REVIEW' => $kontrakPending,
                'DRAFT' => $kontrakDraft,
                'SELESAI' => $kontrakSelesai,
                'DIBATALKAN' => $kontrakDibatalkan,
            ];

            // ============================================================
            // TABEL: Jatuh Tempo H-14 + Terlambat
            // ============================================================
            $jatuhTempo = \App\Models\KontrakPengadaan::where('status_kontrak', 'AKTIF')
                ->where('tanggal_selesai', '<=', $now->copy()->addDays(14))
                ->with('vendor')
                ->orderBy('tanggal_selesai', 'asc')
                ->take(6)
                ->get();

            // ============================================================
            // TABEL: Kontrak Pending (Butuh tindakan PPK/Anda)
            // ============================================================
            $kontrakPendingList = \App\Models\KontrakPengadaan::whereIn('status_kontrak', ['DRAFT', 'PENDING_REVIEW'])
                ->with('vendor')
                ->latest('updated_at')
                ->take(6)
                ->get();

            // ============================================================
            // TABEL: Kontrak Aktif Terbaru
            // ============================================================
            $kontrakAktifTerbaru = \App\Models\KontrakPengadaan::where('status_kontrak', 'AKTIF')
                ->with('vendor')
                ->latest('tanggal_spk')
                ->take(5)
                ->get();

            // ============================================================
            // TABEL: Top Vendor by jumlah kontrak
            // ============================================================
            $topVendorRows = \App\Models\KontrakPengadaan::query()
                ->where('status_kontrak', '!=', 'DIBATALKAN')
                ->select('vendor_id', \DB::raw('COUNT(*) as total_kontrak'), \DB::raw('SUM(nilai_total_kontrak) as total_nilai_kontrak'))
                ->groupBy('vendor_id')
                ->orderByDesc('total_kontrak')
                ->take(5)
                ->get();
            $vendorIds = $topVendorRows->pluck('vendor_id');
            $vendorMap = \App\Models\MasterMitraVendor::whereIn('id', $vendorIds)->get()->keyBy('id');
            $topVendor = $topVendorRows->map(function ($row) use ($vendorMap) {
                $row->vendor = $vendorMap->get($row->vendor_id);
                return $row;
            });

            // ============================================================
            // TABEL: Tagihan Bermasalah
            // ============================================================
            $tagihanBermasalah = \App\Models\Tagihan::whereIn('status', ['DITOLAK_PPK', 'REVISI_BENDAHARA', 'REVISI'])
                ->with(['logs' => fn ($q) => $q->latest()->limit(1)])
                ->latest()
                ->take(5)
                ->get();

            return [
                'kpi' => [
                    'kontrak_aktif' => $kontrakAktif,
                    'kontrak_draft' => $kontrakDraft,
                    'kontrak_pending' => $kontrakPending,
                    'kontrak_selesai' => $kontrakSelesai,
                    'kontrak_total' => $kontrakTotal,
                    'selesai_bulan_ini' => $selesaiBulanIni,
                    'nilai_kontrak_aktif' => $nilaiKontrakAktif,
                    'nilai_kontrak_selesai' => $nilaiKontrakSelesai,
                    'total_nilai_semua' => $totalNilaiSemua,
                    'total_vendor' => $totalVendor,
                    'vendor_aktif' => $vendorAktif,
                    'tagihan_menunggu' => $tagihanMenunggu,
                    'tagihan_revisi' => $tagihanRevisi,
                    'total_pagu_dipa' => $totalPaguDipa,
                    'serapan_persen' => $totalPaguDipa > 0 ? round(($totalKontrakNonBatal / $totalPaguDipa) * 100, 1) : 0,
                ],
                'chart_serapan_labels' => array_keys($chartSerapan),
                'chart_serapan_data' => array_values($chartSerapan),
                'chart_status_labels' => array_keys($statusKontrakDistribusi),
                'chart_status_data' => array_values($statusKontrakDistribusi),
                'tren_labels' => $trenLabels,
                'tren_jumlah' => $trenJumlah,
                'tren_nilai' => $trenNilai,
                'table_jatuh_tempo' => $jatuhTempo,
                'kontrak_pending_list' => $kontrakPendingList,
                'kontrak_aktif_terbaru' => $kontrakAktifTerbaru,
                'top_vendor' => $topVendor,
                'table_tagihan_revisi' => $tagihanBermasalah,
                'tahun' => $tahun,
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
                ->get()->map(function($i) { $i->jenis = 'SPP'; $i->prioritas = 'Sedang'; $i->url_reject = route('verifikasi-spp.kontrak.revisi', $i->id); $i->url_approve = route('verifikasi-spp.kontrak.approve', $i->id); return $i; });
                
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

    /**
     * Dashboard khusus Role PPABP (Petugas Pengelola Administrasi Belanja Pegawai)
     * Fokus: pengelolaan tagihan honorarium — draft, perlu revisi, dalam verifikasi, disetujui.
     */
    private function ppabp()
    {
        $userId = Auth::id();

        $data = \Illuminate\Support\Facades\Cache::remember('dash_ppabp_' . $userId, 60, function () use ($userId) {
            $now = now();
            $tahun = $now->year;
            $bulan = $now->month;

            // ============================================================
            // STATUS BUCKETING
            // ============================================================
            $draftStatuses = ['DRAFT'];
            $revisiStatuses = [
                'DITOLAK_PPK', 'DITOLAK_PPSPM', 'DITOLAK_KOORDINATOR_KEUANGAN',
                'DITOLAK_BENDAHARA_PENGELUARAN', 'DITOLAK_BENDAHARA_PENERIMAAN', 'DITOLAK_KASUBBAG',
                'REVISI_PPK', 'REVISI_PPSPM', 'REVISI_KOORDINATOR_KEUANGAN',
                'REVISI_BENDAHARA_PENGELUARAN', 'REVISI_BENDAHARA_PENERIMAAN', 'REVISI_KASUBBAG',
            ];
            $verifikasiStatuses = [
                'PENDING_VERIFIKASI_HONORARIUM', 'PENDING_KASUBBAG', 'PENDING_PPK', 'PENDING_PPSPM',
                'PENDING_KOORDINATOR_KEUANGAN', 'PENDING_BENDAHARA_PENGELUARAN', 'PENDING_BENDAHARA_PENERIMAAN',
            ];
            $disetujuiStatuses = ['DISETUJUI', 'PROSES_SPP', 'SPP_TERBIT', 'SEBAGIAN_SPP_TERBIT', 'SPP_LENGKAP', 'SELESAI'];

            $baseQuery = fn () => Tagihan::where('tipe_tagihan', 'HONORARIUM');

            // ============================================================
            // KPI CARDS
            // ============================================================
            $kpiDraft = (clone $baseQuery())->whereIn('status', $draftStatuses)->count();
            $kpiRevisi = (clone $baseQuery())->whereIn('status', $revisiStatuses)->count();
            $kpiVerifikasi = (clone $baseQuery())->whereIn('status', $verifikasiStatuses)->count();
            $kpiDisetujui = (clone $baseQuery())->whereIn('status', $disetujuiStatuses)->count();

            $totalNominalDraft = (clone $baseQuery())->whereIn('status', $draftStatuses)->sum('total_bruto');
            $totalNominalVerifikasi = (clone $baseQuery())->whereIn('status', $verifikasiStatuses)->sum('total_bruto');

            // Total bulan ini (dibuat bulan ini)
            $tagihanBulanIni = (clone $baseQuery())
                ->whereYear('created_at', $tahun)
                ->whereMonth('created_at', $bulan)
                ->count();
            $nominalBulanIni = (clone $baseQuery())
                ->whereYear('created_at', $tahun)
                ->whereMonth('created_at', $bulan)
                ->sum('total_bruto');

            // Total Personel Penerima Honor (unique nrp_nip yang dibayar tahun ini)
            $totalPenerimaTahunIni = DB::table('detail_honorarium')
                ->join('tagihan', 'detail_honorarium.tagihan_id', '=', 'tagihan.id')
                ->where('tagihan.tipe_tagihan', 'HONORARIUM')
                ->whereYear('tagihan.created_at', $tahun)
                ->whereNull('tagihan.deleted_at')
                ->whereNull('detail_honorarium.deleted_at')
                ->distinct('detail_honorarium.nrp_nip')
                ->count('detail_honorarium.nrp_nip');

            // ============================================================
            // CHART: Tren Tagihan Honor 6 Bulan Terakhir
            // ============================================================
            $trenLabels = [];
            $trenJumlah = [];
            $trenNominal = [];
            for ($i = 5; $i >= 0; $i--) {
                $month = $now->copy()->subMonths($i);
                $trenLabels[] = $month->isoFormat('MMM YY');
                $trenJumlah[] = (clone $baseQuery())
                    ->whereYear('created_at', $month->year)
                    ->whereMonth('created_at', $month->month)
                    ->count();
                $trenNominal[] = (float) (clone $baseQuery())
                    ->whereYear('created_at', $month->year)
                    ->whereMonth('created_at', $month->month)
                    ->sum('total_bruto');
            }

            // ============================================================
            // CHART: Distribusi Status Tagihan Honor (Doughnut)
            // ============================================================
            $statusDistribusi = [
                'Draft' => $kpiDraft,
                'Perlu Revisi' => $kpiRevisi,
                'Dalam Verifikasi' => $kpiVerifikasi,
                'Disetujui / Selesai' => $kpiDisetujui,
            ];

            // ============================================================
            // TABEL: Tagihan Perlu Tindakan (Draft + Revisi)
            // ============================================================
            $needsActionStatuses = array_merge($draftStatuses, $revisiStatuses);
            $perluTindakan = (clone $baseQuery())
                ->whereIn('status', $needsActionStatuses)
                ->with(['detailHonorarium', 'logs' => fn ($q) => $q->latest()->limit(1)])
                ->latest('updated_at')
                ->take(8)
                ->get();

            // ============================================================
            // TABEL: Tagihan Dalam Verifikasi (sedang diproses verifikator)
            // ============================================================
            $dalamVerifikasi = (clone $baseQuery())
                ->whereIn('status', $verifikasiStatuses)
                ->with(['detailHonorarium'])
                ->latest('updated_at')
                ->take(5)
                ->get();

            // ============================================================
            // TABEL: Honorarium Selesai Terbaru
            // ============================================================
            $selesaiTerbaru = (clone $baseQuery())
                ->whereIn('status', $disetujuiStatuses)
                ->latest('updated_at')
                ->take(5)
                ->get();

            return [
                'kpi' => [
                    'draft' => $kpiDraft,
                    'revisi' => $kpiRevisi,
                    'verifikasi' => $kpiVerifikasi,
                    'disetujui' => $kpiDisetujui,
                    'nominal_draft' => $totalNominalDraft,
                    'nominal_verifikasi' => $totalNominalVerifikasi,
                    'tagihan_bulan_ini' => $tagihanBulanIni,
                    'nominal_bulan_ini' => $nominalBulanIni,
                    'total_penerima_tahun_ini' => $totalPenerimaTahunIni,
                ],
                'tren_labels' => $trenLabels,
                'tren_jumlah' => $trenJumlah,
                'tren_nominal' => $trenNominal,
                'status_labels' => array_keys($statusDistribusi),
                'status_data' => array_values($statusDistribusi),
                'perlu_tindakan' => $perluTindakan,
                'dalam_verifikasi' => $dalamVerifikasi,
                'selesai_terbaru' => $selesaiTerbaru,
                'tahun' => $tahun,
            ];
        });

        return view('dashboard.ppabp', $data);
    }

    /**
     * Dashboard khusus Role Operator Perjaldin
     */
    private function operatorPerjaldin()
    {
        $userId = Auth::id();

        $data = \Illuminate\Support\Facades\Cache::remember('dash_operator_perjaldin_' . $userId, 60, function () use ($userId) {
            $now = now();
            $tahun = $now->year;
            $bulan = $now->month;

            // ============================================================
            // STATUS BUCKETING
            // ============================================================
            $draftStatuses = ['DRAFT'];
            $revisiStatuses = [
                'DITOLAK_PPK', 'DITOLAK_PPSPM', 'DITOLAK_KOORDINATOR_KEUANGAN',
                'DITOLAK_BENDAHARA_PENGELUARAN', 'DITOLAK_BENDAHARA_PENERIMAAN', 'DITOLAK_KASUBBAG',
                'REVISI_PPK', 'REVISI_PPSPM', 'REVISI_KOORDINATOR_KEUANGAN',
                'REVISI_BENDAHARA_PENGELUARAN', 'REVISI_BENDAHARA_PENERIMAAN', 'REVISI_KASUBBAG',
            ];
            $verifikasiStatuses = [
                'PENDING_VERIFIKASI_PERJALDIN', 'PENDING_KASUBBAG', 'PENDING_PPK', 'PENDING_PPSPM',
                'PENDING_KOORDINATOR_KEUANGAN', 'PENDING_BENDAHARA_PENGELUARAN', 'PENDING_BENDAHARA_PENERIMAAN',
            ];
            $menungguTtdStatuses = ['MENUNGGU_UPLOAD_NOMINATIF_TTD'];
            $selesaiStatuses = ['DISETUJUI_PERJALDIN', 'PROSES_SPP', 'SPP_TERBIT', 'SEBAGIAN_SPP_TERBIT', 'SPP_LENGKAP', 'SELESAI', 'CAIR', 'SP2D'];

            $baseQuery = fn () => Tagihan::where('tipe_tagihan', 'PERJALDIN');

            // ============================================================
            // KPI CARDS
            // ============================================================
            $kpiDraft = (clone $baseQuery())->whereIn('status', $draftStatuses)->count();
            $kpiRevisi = (clone $baseQuery())->whereIn('status', $revisiStatuses)->count();
            $kpiVerifikasi = (clone $baseQuery())->whereIn('status', $verifikasiStatuses)->count();
            $kpiMenungguTtd = (clone $baseQuery())->whereIn('status', $menungguTtdStatuses)->count();
            $kpiSelesai = (clone $baseQuery())->whereIn('status', $selesaiStatuses)->count();

            $totalNominalDraft = (clone $baseQuery())->whereIn('status', $draftStatuses)->sum('total_bruto');
            $totalNominalVerifikasi = (clone $baseQuery())->whereIn('status', $verifikasiStatuses)->sum('total_bruto');
            $totalNominalSelesai = (clone $baseQuery())->whereIn('status', $selesaiStatuses)->sum('total_bruto');

            // Total bulan ini (dibuat bulan ini)
            $tagihanBulanIni = (clone $baseQuery())
                ->whereYear('created_at', $tahun)
                ->whereMonth('created_at', $bulan)
                ->count();
            $nominalBulanIni = (clone $baseQuery())
                ->whereYear('created_at', $tahun)
                ->whereMonth('created_at', $bulan)
                ->sum('total_bruto');

            // ============================================================
            // CHART: Tren Tagihan Perjaldin 6 Bulan Terakhir
            // ============================================================
            $trenLabels = [];
            $trenJumlah = [];
            $trenNominal = [];
            for ($i = 5; $i >= 0; $i--) {
                $month = $now->copy()->subMonths($i);
                $trenLabels[] = $month->isoFormat('MMM YY');
                $trenJumlah[] = (clone $baseQuery())
                    ->whereYear('created_at', $month->year)
                    ->whereMonth('created_at', $month->month)
                    ->count();
                $trenNominal[] = (float) (clone $baseQuery())
                    ->whereYear('created_at', $month->year)
                    ->whereMonth('created_at', $month->month)
                    ->sum('total_bruto');
            }

            // ============================================================
            // CHART: Distribusi Biaya Komponen
            // ============================================================
            $components = DB::table('detail_perjaldin')
                ->join('tagihan', 'detail_perjaldin.tagihan_id', '=', 'tagihan.id')
                ->where('tagihan.tipe_tagihan', 'PERJALDIN')
                ->whereNull('tagihan.deleted_at')
                ->select([
                    DB::raw('SUM(detail_perjaldin.biaya_tiket) as tiket'),
                    DB::raw('SUM(detail_perjaldin.biaya_transport) as transport'),
                    DB::raw('SUM(detail_perjaldin.biaya_penginapan) as penginapan'),
                    DB::raw('SUM(detail_perjaldin.uang_harian + detail_perjaldin.uang_representasi + detail_perjaldin.uang_rapat) as uang_harian_representasi_rapat')
                ])
                ->first();

            $komponenData = [
                'Tiket Pesawat' => (float) ($components->tiket ?? 0),
                'Transportasi' => (float) ($components->transport ?? 0),
                'Penginapan' => (float) ($components->penginapan ?? 0),
                'Uang Harian & Rapat' => (float) ($components->uang_harian_representasi_rapat ?? 0),
            ];

            // ============================================================
            // TABEL DATA
            // ============================================================
            // 1. Tagihan Butuh Tindakan (Draft & Revisi)
            $needsActionStatuses = array_merge($draftStatuses, $revisiStatuses);
            $perluTindakan = (clone $baseQuery())
                ->whereIn('status', $needsActionStatuses)
                ->with(['logs' => fn ($q) => $q->latest()])
                ->latest('updated_at')
                ->take(10)
                ->get();

            // 2. Tagihan Menunggu Upload TTD
            $menungguTtd = (clone $baseQuery())
                ->whereIn('status', $menungguTtdStatuses)
                ->with(['arsipDokumen'])
                ->latest('updated_at')
                ->take(5)
                ->get();

            // 3. Tagihan Dalam Verifikasi (dengan approvals)
            $dalamVerifikasi = (clone $baseQuery())
                ->whereIn('status', $verifikasiStatuses)
                ->with(['workflowInstances.approvals.actedByUser', 'logs'])
                ->latest('updated_at')
                ->take(10)
                ->get();

            // 4. Tagihan Selesai Terbaru
            $selesaiTerbaru = (clone $baseQuery())
                ->whereIn('status', $selesaiStatuses)
                ->latest('updated_at')
                ->take(5)
                ->get();

            return [
                'kpi' => [
                    'draft' => $kpiDraft,
                    'revisi' => $kpiRevisi,
                    'verifikasi' => $kpiVerifikasi,
                    'menunggu_ttd' => $kpiMenungguTtd,
                    'selesai' => $kpiSelesai,
                    'nominal_draft' => $totalNominalDraft,
                    'nominal_verifikasi' => $totalNominalVerifikasi,
                    'nominal_selesai' => $totalNominalSelesai,
                    'tagihan_bulan_ini' => $tagihanBulanIni,
                    'nominal_bulan_ini' => $nominalBulanIni,
                ],
                'tren_labels' => $trenLabels,
                'tren_jumlah' => $trenJumlah,
                'tren_nominal' => $trenNominal,
                'komponen_labels' => array_keys($komponenData),
                'komponen_data' => array_values($komponenData),
                'perlu_tindakan' => $perluTindakan,
                'menunggu_ttd' => $menungguTtd,
                'dalam_verifikasi' => $dalamVerifikasi,
                'selesai_terbaru' => $selesaiTerbaru,
                'tahun' => $tahun,
            ];
        });

        return view('dashboard.operator_perjaldin', $data);
    }

    /**
     * Hitung approval yang masih PENDING untuk user pada satu jenis dokumen
     * (berbasis workflow_instances + workflow_approvals).
     *
     * @param  class-string  $modelClass  Model dokumen (DokumenSpm::class, dll)
     * @param  array  $roleCodes  Role yang dimiliki user
     * @return int
     */
    private function countPendingApprovals(string $modelClass, array $roleCodes, ?int $userId = null): int
    {
        if (empty($roleCodes)) {
            return 0;
        }

        return $modelClass::whereHas('workflowInstances', function ($q) use ($roleCodes, $userId) {
            $q->where('status', '!=', 'DRAFT')
                ->whereHas('approvals', function ($a) use ($roleCodes, $userId) {
                    $a->whereIn('role_code', $roleCodes)
                        ->where('status', 'PENDING')
                        ->when($userId, function ($qq) use ($userId) {
                            $qq->where(function ($w) use ($userId) {
                                $w->whereNull('assigned_user_id')
                                    ->orWhere('assigned_user_id', $userId);
                            });
                        });
                });
        })->count();
    }

    /**
     * Ambil daftar approval PENDING terbaru milik user lintas dokumen,
     * dikemas jadi array task seragam untuk ditampilkan di dashboard.
     *
     * @return \Illuminate\Support\Collection
     */
    private function pendingApprovalTasks(array $roleCodes, int $userId, int $limit = 12): \Illuminate\Support\Collection
    {
        if (empty($roleCodes)) {
            return collect();
        }

        $approvals = \App\Models\WorkflowApproval::with(['instance.workflowable'])
            ->whereIn('role_code', $roleCodes)
            ->where('status', 'PENDING')
            ->where(function ($w) use ($userId) {
                $w->whereNull('assigned_user_id')->orWhere('assigned_user_id', $userId);
            })
            ->whereHas('instance', fn ($q) => $q->where('status', '!=', 'DRAFT'))
            ->latest('id')
            ->take(60)
            ->get();

        $meta = [
            \App\Models\DokumenSpp::class  => ['label' => 'SPP',  'no' => 'nomor_spp',  'icon' => 'bi-file-earmark-text',     'tone' => 'indigo'],
            \App\Models\DokumenSpm::class  => ['label' => 'SPM',  'no' => 'nomor_spm',  'icon' => 'bi-file-earmark-check',    'tone' => 'violet'],
            \App\Models\DokumenNpi::class  => ['label' => 'NPI',  'no' => 'nomor_npi',  'icon' => 'bi-file-earmark-ruled',    'tone' => 'amber'],
            \App\Models\DokumenSp2d::class => ['label' => 'SP2D', 'no' => 'nomor_sp2d', 'icon' => 'bi-cash-coin',             'tone' => 'emerald'],
            \App\Models\Tagihan::class     => ['label' => 'Tagihan', 'no' => 'nomor_tagihan', 'icon' => 'bi-receipt',        'tone' => 'rose'],
        ];

        return $approvals
            ->map(function ($app) use ($meta) {
                $doc = $app->instance?->workflowable;
                if (! $doc) {
                    return null;
                }
                $type = get_class($doc);
                $m = $meta[$type] ?? ['label' => 'Dokumen', 'no' => 'id', 'icon' => 'bi-file-earmark', 'tone' => 'indigo'];

                return (object) [
                    'jenis'   => $m['label'],
                    'icon'    => $m['icon'],
                    'tone'    => $m['tone'],
                    'nomor'   => $doc->{$m['no']} ?? ('#' . $doc->id),
                    'tanggal' => $app->created_at,
                    'doc_id'  => $doc->id,
                ];
            })
            ->filter()
            ->unique(fn ($t) => $t->jenis . $t->nomor)
            ->take($limit)
            ->values();
    }

    /**
     * Endpoint publik dashboard PPSPM (dipakai menu sidebar khusus).
     */
    public function ppspmDashboard()
    {
        return $this->ppspm();
    }

    /**
     * Endpoint publik dashboard Koordinator Keuangan (dipakai menu sidebar khusus).
     */
    public function koordinatorKeuanganDashboard()
    {
        return $this->koordinatorKeuangan();
    }

    /**
     * Dashboard khusus Role PPSPM (Pejabat Penanda tangan Surat Perintah Membayar).
     * Fokus: menguji & menandatangani SPM, serta menerbitkan/menguji SP2D.
     */
    private function ppspm()
    {
        $user = Auth::user();
        $userId = $user->id;
        $roleCodes = ['PPSPM'];

        $data = \Illuminate\Support\Facades\Cache::remember('dash_ppspm_' . $userId, 60, function () use ($roleCodes, $userId) {
            $now = now();

            // ===== KPI: tugas verifikasi PPSPM per jenis dokumen =====
            $spmPending  = $this->countPendingApprovals(\App\Models\DokumenSpm::class, $roleCodes, $userId);
            $sp2dPending = $this->countPendingApprovals(\App\Models\DokumenSp2d::class, $roleCodes, $userId);
            $tagihanPending = $this->countPendingApprovals(\App\Models\Tagihan::class, $roleCodes, $userId);
            $totalTugas = $spmPending + $sp2dPending + $tagihanPending;

            // ===== Riwayat tindakan PPSPM =====
            $sudahDisetujui = \App\Models\WorkflowApproval::whereIn('role_code', $roleCodes)
                ->where('status', 'APPROVED')
                ->where('acted_by_user_id', $userId)
                ->count();
            $diRevisi = \App\Models\WorkflowApproval::whereIn('role_code', $roleCodes)
                ->whereIn('status', ['REVISION', 'REJECTED'])
                ->where('acted_by_user_id', $userId)
                ->count();

            // SPM & SP2D terbit (output utama PPSPM)
            $spmTerbit = \App\Models\DokumenSpm::whereIn('status', [
                \App\Models\DokumenSpm::STATUS_DISETUJUI_FINAL ?? 'DISETUJUI_FINAL',
                'SPM_TERBIT',
            ])->count();
            $sp2dTerbit = \App\Models\DokumenSp2d::whereIn('status', ['DISETUJUI_FINAL', 'SP2D_TERBIT', 'EXECUTED'])->count();

            // ===== Nominal SPM yang sedang menunggu PPSPM =====
            $nominalSpmPending = \App\Models\DokumenSpm::whereHas('workflowInstances', function ($q) use ($roleCodes, $userId) {
                $q->where('status', '!=', 'DRAFT')->whereHas('approvals', function ($a) use ($roleCodes, $userId) {
                    $a->whereIn('role_code', $roleCodes)->where('status', 'PENDING')
                        ->where(fn ($w) => $w->whereNull('assigned_user_id')->orWhere('assigned_user_id', $userId));
                });
            })->sum('nominal_spm');

            // ===== CHART: tren approval PPSPM 6 bulan =====
            $trenLabels = [];
            $trenApprove = [];
            for ($i = 5; $i >= 0; $i--) {
                $m = $now->copy()->subMonths($i);
                $trenLabels[] = $m->isoFormat('MMM YY');
                $trenApprove[] = \App\Models\WorkflowApproval::whereIn('role_code', $roleCodes)
                    ->where('status', 'APPROVED')
                    ->where('acted_by_user_id', $userId)
                    ->whereYear('acted_at', $m->year)
                    ->whereMonth('acted_at', $m->month)
                    ->count();
            }

            // ===== Donut: komposisi tugas =====
            $chartTugas = [
                'SPM'     => $spmPending,
                'SP2D'    => $sp2dPending,
                'Tagihan' => $tagihanPending,
            ];

            // ===== Daftar tugas (antrian) =====
            $tasks = $this->pendingApprovalTasks($roleCodes, $userId, 12);

            return [
                'kpi' => [
                    'total_tugas'     => $totalTugas,
                    'spm_pending'     => $spmPending,
                    'sp2d_pending'    => $sp2dPending,
                    'tagihan_pending' => $tagihanPending,
                    'sudah_disetujui' => $sudahDisetujui,
                    'di_revisi'       => $diRevisi,
                    'spm_terbit'      => $spmTerbit,
                    'sp2d_terbit'     => $sp2dTerbit,
                    'nominal_spm_pending' => (float) $nominalSpmPending,
                ],
                'tren_labels'   => $trenLabels,
                'tren_approve'  => $trenApprove,
                'chart_tugas_labels' => array_keys($chartTugas),
                'chart_tugas_data'   => array_values($chartTugas),
                'tasks' => $tasks,
                'tahun' => $now->year,
            ];
        });

        return view('dashboard.ppspm', $data);
    }

    /**
     * Dashboard khusus Role Koordinator Keuangan.
     * Fokus: mengoordinasikan & memverifikasi seluruh alur dokumen keuangan
     * (SPP → SPM → NPI → SP2D) untuk kontrak, perjaldin, dan honorarium.
     */
    private function koordinatorKeuangan()
    {
        $user = Auth::user();
        $userId = $user->id;
        $roleCodes = ['Koordinator Keuangan'];

        $data = \Illuminate\Support\Facades\Cache::remember('dash_koorkeu_' . $userId, 60, function () use ($roleCodes, $userId) {
            $now = now();

            // ===== KPI: tugas verifikasi per tahap dokumen =====
            $sppPending  = $this->countPendingApprovals(\App\Models\DokumenSpp::class, $roleCodes, $userId);
            $spmPending  = $this->countPendingApprovals(\App\Models\DokumenSpm::class, $roleCodes, $userId);
            $npiPending  = $this->countPendingApprovals(\App\Models\DokumenNpi::class, $roleCodes, $userId);
            $sp2dPending = $this->countPendingApprovals(\App\Models\DokumenSp2d::class, $roleCodes, $userId);
            $tagihanPending = $this->countPendingApprovals(\App\Models\Tagihan::class, $roleCodes, $userId);
            $totalTugas = $sppPending + $spmPending + $npiPending + $sp2dPending + $tagihanPending;

            // ===== Riwayat tindakan =====
            $sudahDisetujui = \App\Models\WorkflowApproval::whereIn('role_code', $roleCodes)
                ->where('status', 'APPROVED')
                ->where('acted_by_user_id', $userId)
                ->count();
            $diRevisi = \App\Models\WorkflowApproval::whereIn('role_code', $roleCodes)
                ->whereIn('status', ['REVISION', 'REJECTED'])
                ->where('acted_by_user_id', $userId)
                ->count();

            // ===== Pipeline pencairan (gambaran umum koordinasi) =====
            $totalPagu = RiwayatRevisiDipa::where('is_active', true)->sum('total_pagu');
            $totalRealisasi = DB::table('realisasi_anggaran')->sum('nominal_cair');
            $sisaPagu = max(0, $totalPagu - $totalRealisasi);
            $persenRealisasi = $totalPagu > 0 ? round(($totalRealisasi / $totalPagu) * 100, 1) : 0;

            // ===== CHART: corong dokumen (jumlah pending tiap tahap) =====
            $chartFunnelLabels = ['SPP', 'SPM', 'NPI', 'SP2D'];
            $chartFunnelData = [$sppPending, $spmPending, $npiPending, $sp2dPending];

            // ===== CHART: tren approval 6 bulan =====
            $trenLabels = [];
            $trenApprove = [];
            for ($i = 5; $i >= 0; $i--) {
                $m = $now->copy()->subMonths($i);
                $trenLabels[] = $m->isoFormat('MMM YY');
                $trenApprove[] = \App\Models\WorkflowApproval::whereIn('role_code', $roleCodes)
                    ->where('status', 'APPROVED')
                    ->where('acted_by_user_id', $userId)
                    ->whereYear('acted_at', $m->year)
                    ->whereMonth('acted_at', $m->month)
                    ->count();
            }

            // ===== Daftar tugas (antrian lintas dokumen) =====
            $tasks = $this->pendingApprovalTasks($roleCodes, $userId, 14);

            return [
                'kpi' => [
                    'total_tugas'     => $totalTugas,
                    'spp_pending'     => $sppPending,
                    'spm_pending'     => $spmPending,
                    'npi_pending'     => $npiPending,
                    'sp2d_pending'    => $sp2dPending,
                    'tagihan_pending' => $tagihanPending,
                    'sudah_disetujui' => $sudahDisetujui,
                    'di_revisi'       => $diRevisi,
                    'total_pagu'      => (float) $totalPagu,
                    'total_realisasi' => (float) $totalRealisasi,
                    'sisa_pagu'       => (float) $sisaPagu,
                    'persen_realisasi'=> $persenRealisasi,
                ],
                'chart_funnel_labels' => $chartFunnelLabels,
                'chart_funnel_data'   => $chartFunnelData,
                'tren_labels'  => $trenLabels,
                'tren_approve' => $trenApprove,
                'tasks' => $tasks,
                'tahun' => $now->year,
            ];
        });

        return view('dashboard.koordinator_keuangan', $data);
    }
}
