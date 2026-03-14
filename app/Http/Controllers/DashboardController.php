<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use App\Models\Contract;
use App\Models\Transaction;
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
        // Summary cards
        $totalPagu = Budget::sum('initial_budget');
        $totalRealisasi = Transaction::where('status', 'Paid SP2D')->sum('gross_amount');
        $sisaAnggaran = $totalPagu - $totalRealisasi;
        $persenRealisasi = $totalPagu > 0 ? round(($totalRealisasi / $totalPagu) * 100, 1) : 0;

        $totalKontrakAktif = Contract::where('status', 'Active')->count();
        $totalMitra = Supplier::count();

        // Transaction status breakdown for donut chart
        $statusCounts = Transaction::select('status', DB::raw('count(*) as total'))
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
            $budgetRealisasi[] = Transaction::where('budget_id', $b->id)
                ->where('status', 'Paid SP2D')
                ->sum('gross_amount');
        }

        // Recent transactions for table
        $recentTransactions = Transaction::with(['contract', 'budget'])
            ->latest()
            ->take(5)
            ->get();

        // Active contracts
        $activeContracts = Contract::where('status', 'Active')
            ->with('supplier')
            ->latest()
            ->take(5)
            ->get();

        // Transactions needing approval (status = Verified, Approved SPP, or Approved SPM)
        $pendingApproval = Transaction::whereIn('status', ['Verified', 'Approved SPP', 'Approved SPM'])
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

            $transactions = Transaction::whereIn('contract_id', $contractIds)
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
}
