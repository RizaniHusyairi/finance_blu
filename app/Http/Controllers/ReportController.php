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

        $query = BluPaymentSubmission::with(['contract.supplier', 'budget', 'taxes'])
            ->whereYear('date', $year)
            ->where('status', 'Paid SP2D')
            ->orderBy('date', 'asc');

        if ($month) {
            $query->whereMonth('date', $month);
        }

        if ($budgetId) {
            $query->where('budget_id', $budgetId);
        }

        $transactions = $query->get();

        // Calculate running balance
        $budgets = Budget::all();
        $totalPagu = $budgetId
            ? (float) (Budget::find($budgetId)?->initial_budget ?? 0)
            : (float) Budget::sum('initial_budget');

        $bkuRows = [];
        $runningDebit = 0;
        $runningSaldo = $totalPagu;

        foreach ($transactions as $t) {
            $taxTotal = $t->taxes->sum('amount');
            $netto = $t->gross_amount - $taxTotal;
            $runningDebit += $netto;
            $runningSaldo = $totalPagu - $runningDebit;

            $bkuRows[] = [
                'date' => $t->date,
                'transaction_number' => $t->transaction_number,
                'description' => $t->description,
                'supplier' => $t->contract->supplier->name ?? '-',
                'bruto' => $t->gross_amount,
                'tax' => $taxTotal,
                'netto' => $netto,
                'cumulative_debit' => $runningDebit,
                'saldo' => $runningSaldo,
                'budget_coa' => $t->budget->coa ?? '-',
                'type' => $t->type,
            ];
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

        $query = BluPaymentSubmission::with(['contract.supplier', 'budget', 'taxes'])
            ->whereYear('date', $year)
            ->where('status', 'Paid SP2D')
            ->orderBy('date', 'asc');

        if ($month) {
            $query->whereMonth('date', $month);
        }

        if ($budgetId) {
            $query->where('budget_id', $budgetId);
        }

        $transactions = $query->get();

        $budgets = Budget::all();
        $totalPagu = $budgetId
            ? (float) (Budget::find($budgetId)?->initial_budget ?? 0)
            : (float) Budget::sum('initial_budget');

        $bkuRows = [];
        $runningDebit = 0;
        $runningSaldo = $totalPagu;

        foreach ($transactions as $t) {
            $taxTotal = $t->taxes->sum('amount');
            $netto = $t->gross_amount - $taxTotal;
            $runningDebit += $netto;
            $runningSaldo = $totalPagu - $runningDebit;

            $bkuRows[] = [
                'date' => $t->date,
                'transaction_number' => $t->transaction_number,
                'description' => $t->description,
                'supplier' => $t->contract->supplier->name ?? '-',
                'bruto' => $t->gross_amount,
                'tax' => $taxTotal,
                'netto' => $netto,
                'cumulative_debit' => $runningDebit,
                'saldo' => $runningSaldo,
                'budget_coa' => $t->budget->coa ?? '-',
                'type' => $t->type,
            ];
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
