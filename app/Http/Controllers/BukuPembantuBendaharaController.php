<?php

namespace App\Http\Controllers;

use App\Services\Pembukuan\PembukuanService;
use Illuminate\Http\Request;

class BukuPembantuBendaharaController extends Controller
{
    public function __construct(
        private readonly PembukuanService $pembukuanService
    ) {
    }

    public function index(Request $request)
    {
        return view('pembukuan.bendahara.index', $this->pembukuanService->buildBendaharaIndexData(
            $request->only(['start_date', 'end_date', 'rekening_bank_id', 'jenis_transaksi', 'tagihan_id'])
        ));
    }

    public function pdf(Request $request)
    {
        $data = $this->pembukuanService->buildBendaharaIndexData(
            $request->only(['start_date', 'end_date', 'rekening_bank_id', 'jenis_transaksi', 'tagihan_id'])
        );

        return $this->pembukuanService->streamPdf(
            'pembukuan.bendahara.pdf',
            $data,
            'Buku_Pembantu_Bendahara_' . now()->format('Ymd_His') . '.pdf'
        );
    }
}
