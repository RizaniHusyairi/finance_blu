<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTransaksiPembukuanRequest;
use App\Models\TransaksiPembukuan;
use App\Services\Pembukuan\PostingPembukuanService;
use Illuminate\Support\Facades\DB;

/**
 * "Input Transaksi" — jurnal manual SILABI Bendahara Pengeluaran. Satu baris
 * `transaksi_pembukuan` (kode A..R2) didistribusikan ke BKU + buku pembantu oleh
 * [[App\Services\Pembukuan\PostingPembukuanService]].
 *
 * Menutup kasus dana MASUK ke BKU Pengeluaran yang BUKAN dari tagihan
 * kontrak/perjaldin/honorarium (Terima UP/TUP, transfer dari Bendahara
 * Penerimaan, bunga rekening, pengembalian belanja, retur) — sekaligus jurnal
 * manual lainnya (setor pajak, perpindahan tunai↔bank, koreksi).
 */
class TransaksiPembukuanController extends Controller
{
    public function __construct(
        private readonly PostingPembukuanService $posting,
    ) {
    }

    public function store(StoreTransaksiPembukuanRequest $request)
    {
        $validated = $request->validated();

        try {
            DB::transaction(function () use ($validated) {
                $trx = TransaksiPembukuan::create($validated + ['created_by' => auth()->id()]);
                $this->posting->post($trx); // melempar bila kode tak dikenal / jumlah <= 0
            });
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'Gagal mencatat transaksi: ' . $e->getMessage());
        }

        return redirect()->route('pembukuan.pengeluaran.index')
            ->with('success', 'Transaksi pembukuan dicatat & didistribusikan ke BKU/buku pembantu.');
    }

    public function destroy(TransaksiPembukuan $transaksiPembukuan)
    {
        // Hanya jurnal manual (tanpa tautan dokumen SP2D/Tagihan) yang boleh dihapus.
        abort_if(
            $transaksiPembukuan->referensi_type !== null,
            403,
            'Jurnal turunan dokumen tidak dapat dibatalkan dari sini.'
        );

        DB::transaction(function () use ($transaksiPembukuan) {
            $this->posting->reverse($transaksiPembukuan); // hapus semua baris BKU + recompute saldo
            $transaksiPembukuan->delete();
        });

        return redirect()->route('pembukuan.pengeluaran.index')
            ->with('success', 'Transaksi pembukuan dibatalkan & baris BKU terkait dihapus.');
    }
}
