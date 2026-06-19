<?php

namespace App\Http\Controllers;

use App\Models\BukuKasUmum;
use App\Services\Pembukuan\PembukuanService;

/**
 * BKU gabungan lama dipensiunkan demi BKU per-peran (Penerimaan/Pengeluaran).
 * Controller ini hanya menyisakan: redirect index → BKU per-peran sesuai role,
 * dan halaman detail transaksi (endpoint bersama kedua peran).
 *
 * Saldo awal kini dikelola via Setup Pembukuan (pembukuan_saldo_awal),
 * lihat [[App\Http\Controllers\PembukuanSetupController]]::storeSaldoAwal().
 */
class BukuKasUmumController extends Controller
{
    public function __construct(
        private readonly PembukuanService $pembukuanService
    ) {
    }

    /** Arahkan ke BKU per-peran sesuai role (cermin logika $bkuUrl di sidebar). */
    public function index()
    {
        return redirect()->route(
            auth()->user()?->hasAnyRole(['Bendahara Pengeluaran', 'Super Admin'])
                ? 'pembukuan.pengeluaran.index'
                : 'pembukuan.penerimaan.index'
        );
    }

    /**
     * Detail transaksi BKU — ditaut dari BKU Penerimaan/Pengeluaran & Tagihan Jasa.
     * Bendahara hanya boleh membuka detail peran-nya sendiri; Super Admin keduanya.
     */
    public function show(BukuKasUmum $id)
    {
        $this->authorizeBkuPeran($id->peran);

        return view('pembukuan.bku.show', $this->pembukuanService->buildBkuDetail($id));
    }

    private function authorizeBkuPeran(?string $peran): void
    {
        $user = auth()->user();

        if ($user?->hasRole('Super Admin')) {
            return;
        }

        $role = $peran === 'PENERIMAAN' ? 'Bendahara Penerimaan' : 'Bendahara Pengeluaran';

        abort_unless($user?->hasRole($role), 403, 'Anda tidak berwenang membuka detail BKU ini.');
    }
}
