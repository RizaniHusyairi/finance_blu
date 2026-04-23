<?php

namespace App\Http\Controllers;

use App\Models\BukuKasUmum;
use App\Services\Pembukuan\PembukuanService;
use Illuminate\Http\Request;

class BukuKasUmumController extends Controller
{
    public function __construct(
        private readonly PembukuanService $pembukuanService
    ) {
    }

    public function index(Request $request)
    {
        $data = $this->pembukuanService->buildBkuIndexData(
            $request->only(['start_date', 'end_date', 'rekening_bank_id', 'arus_kas', 'sumber_transaksi'])
        );

        return view('pembukuan.bku.index', $data);
    }

    public function show(BukuKasUmum $id)
    {
        return view('pembukuan.bku.show', $this->pembukuanService->buildBkuDetail($id));
    }

    public function pdf(Request $request)
    {
        $data = $this->pembukuanService->buildBkuIndexData(
            $request->only(['start_date', 'end_date', 'rekening_bank_id', 'arus_kas', 'sumber_transaksi'])
        );

        return $this->pembukuanService->streamPdf(
            'pembukuan.bku.pdf',
            $data,
            'Buku_Kas_Umum_' . now()->format('Ymd_His') . '.pdf'
        );
    }
}
