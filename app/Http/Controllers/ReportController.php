<?php

namespace App\Http\Controllers;

use App\Services\Reports\ReportAggregationService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function __construct(
        private readonly ReportAggregationService $reportAggregationService
    ) {
    }

    public function bku(Request $request)
    {
        $data = $this->reportAggregationService->buildBkuReport($request->only([
            'year',
            'month',
            'budget_id',
        ]));

        return view('reports.bku', $data);
    }

    public function bkuPdf(Request $request)
    {
        $data = $this->reportAggregationService->buildBkuReport($request->only([
            'year',
            'month',
            'budget_id',
        ]));

        $monthName = $data['month']
            ? ($data['filterMonths'][(int) $data['month']] ?? '')
            : 'Semua Bulan';

        $pdf = Pdf::loadView('reports.bku-pdf', array_merge($data, [
            'monthName' => $monthName,
        ]));
        $pdf->setPaper('a4', 'landscape');

        $filename = 'BKU_' . $data['year'] . ($data['month'] ? '_' . str_pad($data['month'], 2, '0', STR_PAD_LEFT) : '') . '.pdf';

        return $pdf->stream($filename);
    }
}
