<?php

namespace App\Http\Controllers;

use App\Enums\KodeBuku;
use App\Enums\PeranBuku;
use App\Services\Pembukuan\BukuPembantuService;
use App\Services\Pembukuan\DokumenPembukuanService;
use App\Services\Pembukuan\PembukuanService;
use Illuminate\Http\Request;

/**
 * Tampilan generik buku pembantu sebagai partisi buku_kas_umum — satu halaman
 * dengan pemilih buku (Kas Tunai, UP, BPP, UM Perjadin, Pajak LS, Pengesahan,
 * Pengembalian, dst). Surfacing langsung dari lapisan BukuPembantuService.
 */
class BukuPembantuPartisiController extends Controller
{
    /** Buku yang disajikan lewat halaman generik ini (BKU/Bank/Bunga/Pajak/LS punya halaman sendiri). */
    private const BUKU_TERSEDIA = [
        KodeBuku::KAS_TUNAI->value,
        KodeBuku::BPP->value,
        KodeBuku::UP->value,
        KodeBuku::UM_PERJADIN->value,
        KodeBuku::PAJAK_LS->value,
        KodeBuku::PENGESAHAN->value,
        KodeBuku::PENGEMBALIAN->value,
    ];

    public function __construct(
        private readonly BukuPembantuService $bukuService,
        private readonly DokumenPembukuanService $dokumen,
        private readonly PembukuanService $pembukuan,
    ) {
    }

    public function index(Request $request)
    {
        $kodeBuku = $this->resolveKodeBuku($request);
        $peran = $request->input('peran', PeranBuku::PENGELUARAN->value);
        $filters = $request->only(['rekening_bank_id', 'start_date', 'end_date']);

        return view('pembukuan.buku_pembantu.index', [
            'buku' => $this->bukuService->buildBuku($kodeBuku, $peran, $filters),
            'kodeBuku' => $kodeBuku,
            'peran' => $peran,
            'filters' => $filters,
            'bukuOptions' => collect(self::BUKU_TERSEDIA)->mapWithKeys(fn ($k) => [$k => KodeBuku::from($k)->label()]),
            'rekeningOptions' => $this->pembukuan->rekeningOptions(),
        ]);
    }

    public function pdf(Request $request)
    {
        return $this->dokumen->streamBukuPdf($this->resolveKodeBuku($request),
            $request->input('peran', PeranBuku::PENGELUARAN->value),
            $request->only(['rekening_bank_id', 'start_date', 'end_date']));
    }

    public function excel(Request $request)
    {
        return $this->dokumen->streamBukuExcel($this->resolveKodeBuku($request),
            $request->input('peran', PeranBuku::PENGELUARAN->value),
            $request->only(['rekening_bank_id', 'start_date', 'end_date']));
    }

    private function resolveKodeBuku(Request $request): int
    {
        $kode = (int) $request->input('kode_buku', KodeBuku::KAS_TUNAI->value);

        return in_array($kode, self::BUKU_TERSEDIA, true) ? $kode : KodeBuku::KAS_TUNAI->value;
    }
}
