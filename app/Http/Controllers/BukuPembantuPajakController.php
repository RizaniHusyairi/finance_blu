<?php

namespace App\Http\Controllers;

use App\Models\PotonganTagihan;
use App\Services\Pembukuan\PembukuanService;
use Illuminate\Http\Request;

class BukuPembantuPajakController extends Controller
{
    public function __construct(
        private readonly PembukuanService $pembukuanService
    ) {
    }

    public function index(Request $request)
    {
        return view('pembukuan.pajak.index', $this->pembukuanService->buildPajakIndexData(
            $request->only(['start_date', 'end_date', 'jenis_tagihan', 'jenis_pajak', 'status_billing_setor'])
        ));
    }

    public function show(PotonganTagihan $potongan)
    {
        return view('pembukuan.pajak.show', $this->pembukuanService->buildPajakDetail($potongan));
    }

    public function pdf(Request $request)
    {
        $data = $this->pembukuanService->buildPajakIndexData(
            $request->only(['start_date', 'end_date', 'jenis_tagihan', 'jenis_pajak', 'status_billing_setor'])
        );

        return $this->pembukuanService->streamPdf(
            'pembukuan.pajak.pdf',
            $data,
            'Buku_Pembantu_Pajak_' . now()->format('Ymd_His') . '.pdf'
        );
    }
}
