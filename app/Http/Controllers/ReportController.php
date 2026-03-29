<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use App\Models\BluPaymentSubmission;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    /**
     * BKU (Buku Kas Umum) Report — filterable by month/year & budget account
     */
    public function bku(Request $request)
    {
        $year = $request->input('year', date('Y'));
        $month = $request->input('month'); // null = all months
        $budgetId = $request->input('budget_id');

        // 1. Ambil data Tagihan Umum (Paid SP2D)
        $queryTagihan = BluPaymentSubmission::with(['contract.supplier', 'budget', 'taxes'])
            ->whereYear('date', $year)
            ->where('status', 'Paid SP2D');

        if ($month) {
            $queryTagihan->whereMonth('date', $month);
        }

        if ($budgetId) {
            $queryTagihan->where('budget_id', $budgetId);
        }

        $tagihans = $queryTagihan->get();

        // 2. Ambil data Perjaldin (Spp Lunas)
        $queryPerjaldin = \App\Models\Spp::where('status_spp', 'Lunas')
            ->where(function ($q) use ($year) {
                $q->whereYear('tanggal_sp2d', $year)
                  ->orWhereNull('tanggal_sp2d');
            });

        if ($month) {
            $queryPerjaldin->where(function ($q) use ($month) {
                $q->whereMonth('tanggal_sp2d', $month)
                  ->orWhereNull('tanggal_sp2d');
            });
        }

        // Filter Anggaran untuk Perjaldin (berdasarkan Coa/Akun MAK)
        if ($budgetId) {
            $selectedBudget = \App\Models\Budget::find($budgetId);
            if ($selectedBudget) {
                $queryPerjaldin->where('akun_mak', 'LIKE', '%' . $selectedBudget->coa . '%');
            }
        }

        $perjaldins = $queryPerjaldin->get();

        // 3. Mapping data ke Format Baris BKU yang seragam
        $allTransactions = collect();

        foreach ($tagihans as $t) {
            $taxTotal = $t->taxes->sum('amount');
            $allTransactions->push([
                'date' => $t->date,
                'transaction_number' => $t->transaction_number,
                'description' => $t->description,
                'supplier' => $t->contract->supplier->name ?? '-',
                'bruto' => $t->gross_amount,
                'tax' => $taxTotal,
                'netto' => $t->gross_amount - $taxTotal,
                'budget_coa' => $t->budget->coa ?? '-',
                'type' => $t->type ?? 'Tagihan',
            ]);
        }

        foreach ($perjaldins as $s) {
            $rowDate = $s->tanggal_sp2d ?? $s->updated_at;
            $allTransactions->push([
                'date' => $rowDate,
                'transaction_number' => $s->nomor_sp2d ?? 'BELUM DICATAT',
                'description' => $s->uraian,
                'supplier' => 'Pegawai (Internal)',
                'bruto' => $s->jumlah_uang,
                'tax' => 0,
                'netto' => $s->jumlah_uang,
                'budget_coa' => $s->akun_mak,
                'type' => 'Perjaldin',
            ]);
        }

        // 4. Urutkan berdasarkan Tanggal
        $sortedTransactions = $allTransactions->sortBy('date');

        // 5. Kalkulasi Saldo Berjalan (Running Balance)
        $budgets = Budget::all();
        $totalPagu = $budgetId
            ? (float) (Budget::find($budgetId)?->initial_budget ?? 0)
            : (float) Budget::sum('initial_budget');

        $bkuRows = [];
        $runningDebit = 0;
        $runningSaldo = $totalPagu;

        foreach ($sortedTransactions as $row) {
            $runningDebit += $row['netto'];
            $runningSaldo = $totalPagu - $runningDebit;

            $row['cumulative_debit'] = $runningDebit;
            $row['saldo'] = $runningSaldo;
            $bkuRows[] = $row;
        }

        $filterMonths = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        return view('reports.bku', compact(
            'bkuRows', 'budgets', 'totalPagu', 'runningDebit', 'runningSaldo',
            'year', 'month', 'budgetId', 'filterMonths'
        ));
    }

    /**
     * Export BKU as PDF
     */
    public function bkuPdf(Request $request)
    {
        $year = $request->input('year', date('Y'));
        $month = $request->input('month');
        $budgetId = $request->input('budget_id');

        // 1. Ambil data Tagihan Umum (Paid SP2D)
        $queryTagihan = BluPaymentSubmission::with(['contract.supplier', 'budget', 'taxes'])
            ->whereYear('date', $year)
            ->where('status', 'Paid SP2D');

        if ($month) {
            $queryTagihan->whereMonth('date', $month);
        }

        if ($budgetId) {
            $queryTagihan->where('budget_id', $budgetId);
        }

        $tagihans = $queryTagihan->get();

        // 2. Ambil data Perjaldin (Spp Lunas)
        $queryPerjaldin = \App\Models\Spp::where('status_spp', 'Lunas')
            ->where(function ($q) use ($year) {
                $q->whereYear('tanggal_sp2d', $year)
                  ->orWhereNull('tanggal_sp2d');
            });

        if ($month) {
            $queryPerjaldin->where(function ($q) use ($month) {
                $q->whereMonth('tanggal_sp2d', $month)
                  ->orWhereNull('tanggal_sp2d');
            });
        }

        if ($budgetId) {
            $selectedBudget = \App\Models\Budget::find($budgetId);
            if ($selectedBudget) {
                $queryPerjaldin->where('akun_mak', 'LIKE', '%' . $selectedBudget->coa . '%');
            }
        }

        $perjaldins = $queryPerjaldin->get();

        // 3. Mapping data ke Format Baris BKU yang seragam
        $allTransactions = collect();

        foreach ($tagihans as $t) {
            $taxTotal = $t->taxes->sum('amount');
            $allTransactions->push([
                'date' => $t->date,
                'transaction_number' => $t->transaction_number,
                'description' => $t->description,
                'supplier' => $t->contract->supplier->name ?? '-',
                'bruto' => $t->gross_amount,
                'tax' => $taxTotal,
                'netto' => $t->gross_amount - $taxTotal,
                'budget_coa' => $t->budget->coa ?? '-',
                'type' => $t->type ?? 'Tagihan',
            ]);
        }

        foreach ($perjaldins as $s) {
            $rowDate = $s->tanggal_sp2d ?? $s->updated_at;
            $allTransactions->push([
                'date' => $rowDate,
                'transaction_number' => $s->nomor_sp2d ?? 'BELUM DICATAT',
                'description' => $s->uraian,
                'supplier' => 'Pegawai (Internal)',
                'bruto' => $s->jumlah_uang,
                'tax' => 0,
                'netto' => $s->jumlah_uang,
                'budget_coa' => $s->akun_mak,
                'type' => 'Perjaldin',
            ]);
        }

        // 4. Urutkan berdasarkan Tanggal
        $sortedTransactions = $allTransactions->sortBy('date');

        // 5. Kalkulasi Saldo Berjalan (Running Balance)
        $totalPagu = $budgetId
            ? (float) (Budget::find($budgetId)?->initial_budget ?? 0)
            : (float) Budget::sum('initial_budget');

        $bkuRows = [];
        $runningDebit = 0;
        $runningSaldo = $totalPagu;

        foreach ($sortedTransactions as $row) {
            $runningDebit += $row['netto'];
            $runningSaldo = $totalPagu - $runningDebit;

            $row['cumulative_debit'] = $runningDebit;
            $row['saldo'] = $runningSaldo;
            $bkuRows[] = $row;
        }

        $filterMonths = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        $monthName = $month ? ($filterMonths[(int)$month] ?? '') : 'Semua Bulan';

        $pdf = Pdf::loadView('reports.bku-pdf', compact(
            'bkuRows', 'totalPagu', 'runningDebit', 'runningSaldo',
            'year', 'month', 'monthName', 'budgetId'
        ));
        $pdf->setPaper('a4', 'landscape');

        $filename = 'BKU_' . $year . ($month ? '_' . str_pad($month, 2, '0', STR_PAD_LEFT) : '') . '.pdf';
        return $pdf->stream($filename);
    }
}
