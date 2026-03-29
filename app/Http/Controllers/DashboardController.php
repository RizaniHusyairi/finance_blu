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
}
