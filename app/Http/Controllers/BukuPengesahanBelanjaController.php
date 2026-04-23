<?php

namespace App\Http\Controllers;

use App\Models\LaporanPengesahanBlu;
use App\Services\Pembukuan\PembukuanService;
use Illuminate\Http\Request;

class BukuPengesahanBelanjaController extends Controller
{
    public function __construct(
        private readonly PembukuanService $pembukuanService
    ) {
    }

    public function index(Request $request)
    {
        return view('pembukuan.pengesahan.index', $this->pembukuanService->buildPengesahanIndexData(
            $request->only(['bulan', 'tahun', 'status_pengesahan'])
        ));
    }

    public function show(LaporanPengesahanBlu $laporan)
    {
        return view('pembukuan.pengesahan.show', $this->pembukuanService->buildPengesahanDetail($laporan));
    }

    public function pdf(Request $request)
    {
        $data = $this->pembukuanService->buildPengesahanIndexData(
            $request->only(['bulan', 'tahun', 'status_pengesahan'])
        );

        return $this->pembukuanService->streamPdf(
            'pembukuan.pengesahan.pdf',
            $data,
            'Buku_Pengesahan_Belanja_' . now()->format('Ymd_His') . '.pdf'
        );
    }
}
