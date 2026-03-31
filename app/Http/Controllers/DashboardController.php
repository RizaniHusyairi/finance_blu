<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use App\Models\Contract;
use App\Models\BluPaymentSubmission;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Internal Dashboard (KPA, PPK, Operator BLU, Bendahara)
     */
    public function internal()
    {
        if (\Illuminate\Support\Facades\Auth::user()->hasRole('Pejabat Pengadaan')) {
            return $this->pejabatPengadaan();   
        }
        if (\Illuminate\Support\Facades\Auth::user()->hasRole('PPK')) {
            return $this->ppk();   
        }
        // Summary cards
        $totalPagu = Budget::sum('initial_budget');
        $totalRealisasi = BluPaymentSubmission::where('status', 'Paid SP2D')->sum('gross_amount');
        $sisaAnggaran = $totalPagu - $totalRealisasi;
        $persenRealisasi = $totalPagu > 0 ? round(($totalRealisasi / $totalPagu) * 100, 1) : 0;

        $totalKontrakAktif = Contract::where('status', 'Aktif')->count();
        $totalMitra = Supplier::count();

        // Transaction status breakdown for donut chart
        $statusCounts = BluPaymentSubmission::select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        // Budget realization per COA for bar chart
        $budgets = Budget::all();
        $budgetLabels = [];
        $budgetPagu = [];
        $budgetRealisasi = [];
        foreach ($budgets as $b) {
            $budgetLabels[] = $b->coa;
            $budgetPagu[] = (float) $b->initial_budget;
            $budgetRealisasi[] = BluPaymentSubmission::where('budget_id', $b->id)
                ->where('status', 'Paid SP2D')
                ->sum('gross_amount');
        }

        // Recent transactions for table
        $recentTransactions = BluPaymentSubmission::with(['contract', 'budget'])
            ->latest()
            ->take(5)
            ->get();

        // Active contracts
        $activeContracts = Contract::where('status', 'Aktif')
            ->with('supplier')
            ->latest()
            ->take(5)
            ->get();

        // Transactions needing approval (status = Verified, Approved SPP, or Approved SPM)
        $pendingApproval = BluPaymentSubmission::whereIn('status', ['Verified', 'Approved SPP', 'Approved SPM'])
            ->with(['contract', 'budget'])
            ->latest()
            ->take(5)
            ->get();

        return view('dashboard.internal', compact(
            'totalPagu', 'totalRealisasi', 'sisaAnggaran', 'persenRealisasi',
            'totalKontrakAktif', 'totalMitra',
            'statusCounts',
            'budgetLabels', 'budgetPagu', 'budgetRealisasi',
            'recentTransactions', 'activeContracts', 'pendingApproval'
        ));
    }

    /**
     * Mitra Portal Dashboard (External suppliers)
     */
    public function mitra()
    {
        $user = auth()->user();

        // Find supplier linked to this user's email/name (simplified matching)
        $supplier = Supplier::where('user_id', $user->id)->first();

        $contracts = collect();
        $transactions = collect();

        if ($supplier) {
            $contracts = Contract::where('supplier_id', $supplier->id)
                ->with(['terms'])
                ->latest()
                ->get();

            $contractIds = $contracts->pluck('id');

            $transactions = BluPaymentSubmission::whereIn('contract_id', $contractIds)
                ->with(['contract', 'budget', 'taxes'])
                ->latest()
                ->get();
        }

        // Summary stats for the mitra
        $totalKontrak = $contracts->count();
        $totalNilaiKontrak = $contracts->sum('total_amount');
        $totalDibayar = $transactions->where('status', 'Paid SP2D')->sum('gross_amount');
        $totalPending = $transactions->whereNotIn('status', ['Paid SP2D', 'Rejected'])->sum('gross_amount');

        return view('dashboard.mitra', compact(
            'supplier', 'contracts', 'transactions',
            'totalKontrak', 'totalNilaiKontrak', 'totalDibayar', 'totalPending'
        ));
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
            $tagihanMenunggu = \App\Models\Tagihan::whereIn('status', ['PENDING_PPK', 'PENDING_BENDAHARA'])->count();
            
            // KPI 4: Butuh Perhatian (Ditolak / Revisi)
            $tagihanRevisi = \App\Models\Tagihan::whereIn('status', ['DITOLAK_PPK', 'REVISI_BENDAHARA', 'REVISI'])->count();

            // CHART KIRI: Serapan Anggaran Kontrak (Pagu DIPA vs Kontrak)
            $totalPaguDipa = \App\Models\MasterDipa::sum('total_pagu');
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
            $kpi_kontrak_baru = \App\Models\KontrakPengadaan::where('status_kontrak', 'PENDING_PPK')->count();
            
            // KPI 2: Tagihan BAST
            $kpi_tagihan_bast = \App\Models\Tagihan::where('status', 'PENDING_PPK')->count();
            
            // KPI 3: Pencairan
            $spp = DB::table('dokumen_spp')->whereNull('disetujui_ppk_id')->count();
            $npi = DB::table('dokumen_npi')->whereNull('disetujui_ppk_id')->count();
            $sp2d = DB::table('dokumen_sp2d')->whereNull('disetujui_ppk_id')->count();
            $kpi_pencairan = $spp + $npi + $sp2d;

            // KPI 4: Sisa Pagu DIPA
            $totalPaguDipa = DB::table('master_dipas')->sum('total_pagu');
            $totalRealisasi = DB::table('realisasi_anggaran')->sum('nominal_cair');
            $sisaPagu = max(0, $totalPaguDipa - $totalRealisasi);

            // Alert Kritis Belanja Modal 53
            $pagu53 = DB::table('detail_dipas')
                ->join('master_coas', 'detail_dipas.coa_id', '=', 'master_coas.id')
                ->where('master_coas.kode_mak_lengkap', 'like', '%53%')
                ->sum('detail_dipas.nilai_pagu');
            $realisasi53 = DB::table('realisasi_anggaran')
                ->join('detail_dipas', 'realisasi_anggaran.detail_dipa_id', '=', 'detail_dipas.id')
                ->join('master_coas', 'detail_dipas.coa_id', '=', 'master_coas.id')
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
            $pagu51 = DB::table('detail_dipas')->join('master_coas', 'detail_dipas.coa_id', '=', 'master_coas.id')->where('master_coas.kode_mak_lengkap', 'like', '%51%')->sum('detail_dipas.nilai_pagu');
            $pagu52 = DB::table('detail_dipas')->join('master_coas', 'detail_dipas.coa_id', '=', 'master_coas.id')->where('master_coas.kode_mak_lengkap', 'like', '%52%')->sum('detail_dipas.nilai_pagu');
            
            $realisasi51 = DB::table('realisasi_anggaran')->join('detail_dipas', 'realisasi_anggaran.detail_dipa_id', '=', 'detail_dipas.id')->join('master_coas', 'detail_dipas.coa_id', '=', 'master_coas.id')->where('master_coas.kode_mak_lengkap', 'like', '%51%')->sum('realisasi_anggaran.nominal_cair');
            $realisasi52 = DB::table('realisasi_anggaran')->join('detail_dipas', 'realisasi_anggaran.detail_dipa_id', '=', 'detail_dipas.id')->join('master_coas', 'detail_dipas.coa_id', '=', 'master_coas.id')->where('master_coas.kode_mak_lengkap', 'like', '%52%')->sum('realisasi_anggaran.nominal_cair');

            $chartBarLabels = ['Belanja Pegawai (51)', 'Belanja Barang (52)', 'Belanja Modal (53)'];
            $chartBarPagu = [$pagu51, $pagu52, $pagu53];
            $chartBarRealisasi = [$realisasi51, $realisasi52, $realisasi53];

            // Tabs Data
            // Kontrak Baru
            $tabKontrak = \App\Models\KontrakPengadaan::where('status_kontrak', 'PENDING_PPK')->latest()->get();
            // Tagihan
            $tabTagihan = \App\Models\Tagihan::where('status', 'PENDING_PPK')->latest()->get();
            
            // Pencairan (Union of SPP, NPI, SP2D)
            $listSpp = DB::table('dokumen_spp')
                ->join('users', 'dokumen_spp.dibuat_oleh_id', '=', 'users.id')
                ->select('dokumen_spp.id', 'dokumen_spp.nomor_spp as nomor', 'dokumen_spp.nominal_spp as nilai', 'users.name as pembuat')
                ->whereNull('dokumen_spp.disetujui_ppk_id')
                ->get()->map(function($i) { $i->jenis = 'SPP'; $i->prioritas = 'Sedang'; $i->url_reject = route('verifikasi-ppk.spp.revisi', $i->id); $i->url_approve = route('verifikasi-ppk.spp.approve', $i->id); return $i; });
                
            $listNpi = DB::table('dokumen_npi')
                ->join('users', 'dokumen_npi.bendahara_penerimaan_id', '=', 'users.id')
                ->join('dokumen_spm', 'dokumen_npi.spm_id', '=', 'dokumen_spm.id')
                ->join('dokumen_spp', 'dokumen_spm.spp_id', '=', 'dokumen_spp.id')
                ->select('dokumen_npi.id', 'dokumen_npi.nomor_npi as nomor', 'dokumen_spp.nominal_spp as nilai', 'users.name as pembuat')
                ->whereNull('dokumen_npi.disetujui_ppk_id')
                ->get()->map(function($i) { $i->jenis = 'NPI'; $i->prioritas = 'Sedang'; $i->url_reject = route('verifikasi-ppk.npi.revisi', $i->id); $i->url_approve = route('verifikasi-ppk.npi.approve', $i->id);  return $i; });
                
            $listSp2d = DB::table('dokumen_sp2d')
                ->join('users', 'dokumen_sp2d.bendahara_pengeluaran_id', '=', 'users.id')
                ->join('dokumen_npi', 'dokumen_sp2d.npi_id', '=', 'dokumen_npi.id')
                ->join('dokumen_spm', 'dokumen_npi.spm_id', '=', 'dokumen_spm.id')
                ->join('dokumen_spp', 'dokumen_spm.spp_id', '=', 'dokumen_spp.id')
                ->select('dokumen_sp2d.id', 'dokumen_sp2d.nomor_sp2d as nomor', 'dokumen_spp.nominal_spp as nilai', 'users.name as pembuat')
                ->whereNull('dokumen_sp2d.disetujui_ppk_id')
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
