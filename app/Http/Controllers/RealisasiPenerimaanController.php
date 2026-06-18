<?php

namespace App\Http\Controllers;

use App\Services\Pembukuan\DokumenPembukuanService;
use App\Services\Pembukuan\PembukuanService;
use App\Services\Pembukuan\RealisasiPenerimaanService;
use Illuminate\Http\Request;

/** Rekap realisasi pendapatan per akun × bulan (sheet REALISASI_PENERIMAAN). */
class RealisasiPenerimaanController extends Controller
{
    public function __construct(
        private readonly RealisasiPenerimaanService $realisasiService,
        private readonly DokumenPembukuanService $dokumen,
        private readonly PembukuanService $pembukuan,
    ) {
    }

    public function index(Request $request)
    {
        $tahun = (int) $request->input('tahun', now()->year);
        $filters = $request->only(['rekening_bank_id']);

        return view('pembukuan.realisasi.index', [
            'realisasi' => $this->realisasiService->build($tahun, $filters),
            'tahun' => $tahun,
            'filters' => $filters,
            'rekeningOptions' => $this->pembukuan->rekeningOptions(),
        ]);
    }

    public function pdf(Request $request)
    {
        $tahun = (int) $request->input('tahun', now()->year);

        return $this->dokumen->streamRealisasiPdf($tahun, $request->only(['rekening_bank_id']));
    }
}
