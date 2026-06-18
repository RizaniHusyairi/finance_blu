<?php

namespace App\Http\Controllers;

use App\Enums\KodeBuku;
use App\Services\Pembukuan\BukuPembantuService;
use App\Services\Pembukuan\DokumenPembukuanService;
use App\Services\Pembukuan\PembukuanService;
use Illuminate\Http\Request;

/**
 * BKU Bendahara Penerimaan — partisi buku_kas_umum (peran=PENERIMAAN, buku=BKU),
 * sumbernya baris rekening koran terklasifikasi (Fase 3).
 */
class BkuPenerimaanController extends Controller
{
    public function __construct(
        private readonly BukuPembantuService $bukuService,
        private readonly DokumenPembukuanService $dokumen,
        private readonly PembukuanService $pembukuan,
    ) {
    }

    public function index(Request $request)
    {
        $filters = $request->only(['rekening_bank_id', 'start_date', 'end_date']);

        return view('pembukuan.penerimaan.index', [
            'buku' => $this->bukuService->buildBuku(KodeBuku::BKU->value, 'PENERIMAAN', $filters),
            'filters' => $filters,
            'rekeningOptions' => $this->pembukuan->rekeningOptions(),
        ]);
    }

    public function pdf(Request $request)
    {
        return $this->dokumen->streamBukuPdf(KodeBuku::BKU->value, 'PENERIMAAN',
            $request->only(['rekening_bank_id', 'start_date', 'end_date']));
    }

    public function excel(Request $request)
    {
        return $this->dokumen->streamBukuExcel(KodeBuku::BKU->value, 'PENERIMAAN',
            $request->only(['rekening_bank_id', 'start_date', 'end_date']));
    }
}
