<?php

namespace App\Http\Controllers;

use App\Services\Pembukuan\PembukuanService;
use Illuminate\Http\Request;

class BukuPembantuBungaController extends Controller
{
    public function __construct(
        private readonly PembukuanService $pembukuanService
    ) {
    }

    public function index(Request $request)
    {
        return view('pembukuan.bunga.index', $this->pembukuanService->buildBungaIndexData(
            $request->only(['start_date', 'end_date', 'rekening_bank_id'])
        ));
    }

    public function pdf(Request $request)
    {
        $data = $this->pembukuanService->buildBungaIndexData(
            $request->only(['start_date', 'end_date', 'rekening_bank_id'])
        );

        return $this->pembukuanService->streamPdf(
            'pembukuan.bunga.pdf',
            $data,
            'Buku_Pembantu_Bunga_Rekening_' . now()->format('Ymd_His') . '.pdf'
        );
    }
}
