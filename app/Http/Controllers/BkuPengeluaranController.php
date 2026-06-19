<?php

namespace App\Http\Controllers;

use App\Enums\KodeBuku;
use App\Enums\PeranBuku;
use App\Models\KodeTransaksi;
use App\Models\TransaksiPembukuan;
use App\Services\Pembukuan\BukuPembantuService;
use App\Services\Pembukuan\DokumenPembukuanService;
use App\Services\Pembukuan\PembukuanService;
use Illuminate\Http\Request;

/**
 * BKU Bendahara Pengeluaran — partisi buku_kas_umum (peran=PENGELUARAN, buku=BKU),
 * digerakkan oleh jurnal SILABI (Input Transaksi / SP2D). Simetris dengan
 * [[BkuPenerimaanController]].
 */
class BkuPengeluaranController extends Controller
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

        return view('pembukuan.pengeluaran.index', [
            'buku' => $this->bukuService->buildBuku(KodeBuku::BKU->value, PeranBuku::PENGELUARAN->value, $filters),
            'invariant' => $this->bukuService->invariant(PeranBuku::PENGELUARAN->value, $filters),
            'filters' => $filters,
            'rekeningOptions' => $this->pembukuan->rekeningOptions(),
            'kodeTransaksiOptions' => KodeTransaksi::where('status_aktif', true)->orderBy('urutan')->get(),
            'manualJournals' => TransaksiPembukuan::with('kodeTransaksi')
                ->whereNull('referensi_type')
                ->latest()
                ->limit(15)
                ->get(),
        ]);
    }

    public function pdf(Request $request)
    {
        return $this->dokumen->streamBukuPdf(KodeBuku::BKU->value, PeranBuku::PENGELUARAN->value,
            $request->only(['rekening_bank_id', 'start_date', 'end_date']));
    }

    public function excel(Request $request)
    {
        return $this->dokumen->streamBukuExcel(KodeBuku::BKU->value, PeranBuku::PENGELUARAN->value,
            $request->only(['rekening_bank_id', 'start_date', 'end_date']));
    }
}
