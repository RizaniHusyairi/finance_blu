<?php

namespace App\Http\Controllers;

use App\Enums\JenisRekening;
use App\Enums\KodeBuku;
use App\Enums\PeranBuku;
use App\Http\Requests\StoreBkuPenerimaanManualRequest;
use App\Models\BukuKasUmum;
use App\Models\RekeningBank;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Pencatatan manual mutasi kas "non-pola" di BKU Penerimaan — baris yang BUKAN
 * penerimaan jasa (akun pendapatan) maupun jurnal SILABI. Menutup kasus seperti
 * "PBK OPS PENGELUARAN", setor kas negara, pengembalian, biaya admin bank, bunga.
 *
 * Menulis langsung satu baris buku_kas_umum (peran=PENERIMAAN, kode_buku=BKU,
 * akun_pendapatan_id=null, jenis_transaksi=<kategori>) lalu recompute saldo —
 * mengikuti pola [[App\Services\Pembukuan\PostingPenerimaanService]]::createBkuRow.
 */
class BkuPenerimaanManualController extends Controller
{
    public function store(StoreBkuPenerimaanManualRequest $request)
    {
        $validated = $request->validated();

        // Rekening selalu di-resolve otomatis ke rekening Bendahara Penerimaan.
        $rekeningId = $this->resolvePenerimaanRekeningId();

        abort_if(
            ! $rekeningId,
            422,
            'Tidak ada rekening Bendahara Penerimaan/aktif. Tetapkan rekening Penerimaan terlebih dahulu.'
        );

        DB::transaction(function () use ($validated, $rekeningId) {
            BukuKasUmum::create([
                'tanggal_transaksi' => $validated['tanggal_transaksi'],
                'nomor_bukti' => $validated['nomor_bukti'],
                'uraian' => $validated['uraian'],
                'arus_kas' => $validated['arus_kas'],
                'peran' => PeranBuku::PENERIMAAN->value,
                'kode_buku' => KodeBuku::BKU->value,
                'akun_pendapatan_id' => null, // non-jasa → tanpa klasifikasi akun pendapatan
                'jenis_transaksi' => $validated['jenis_transaksi'],
                'nominal' => $validated['nominal'],
                'saldo_akhir' => 0, // diisi benar oleh recompute kronologis
                'sumber_rekening_id' => $rekeningId,
            ]);

            BukuKasUmum::recalculateRunningBalance((int) $rekeningId, KodeBuku::BKU->value);
        });

        return redirect()->route('pembukuan.penerimaan.index')
            ->with('success', 'Mutasi non-jasa berhasil dicatat ke BKU Penerimaan.');
    }

    public function destroy(BukuKasUmum $bku)
    {
        // Hanya baris manual non-jasa yang boleh dihapus — lindungi baris turunan
        // jasa (referensi_penerimaan_id), koran (detail_mutasi_bank_id), atau SILABI.
        abort_unless(
            $bku->peran === PeranBuku::PENERIMAAN->value
                && $bku->jenis_transaksi !== null
                && $bku->referensi_penerimaan_id === null
                && $bku->detail_mutasi_bank_id === null
                && $bku->transaksi_pembukuan_id === null,
            403,
            'Hanya baris manual non-jasa yang dapat dihapus.'
        );

        $rekeningId = (int) $bku->sumber_rekening_id;

        DB::transaction(function () use ($bku, $rekeningId) {
            $bku->delete();
            BukuKasUmum::recalculateRunningBalance($rekeningId, KodeBuku::BKU->value);
        });

        return redirect()->route('pembukuan.penerimaan.index')
            ->with('success', 'Baris mutasi non-jasa dihapus.');
    }

    /**
     * Resolusi rekening tujuan BKU Penerimaan — cermin
     * [[App\Services\Pembukuan\PiutangSyncService]]::resolvePenerimaanRekeningId().
     * (Kandidat refactor: ekstrak ke resolver bersama.)
     */
    private function resolvePenerimaanRekeningId(): ?int
    {
        // 1) Penanda eksplisit jenis_rekening = PENERIMAAN (utamakan default).
        $rekeningId = RekeningBank::query()
            ->where('status_aktif', true)
            ->where('jenis_rekening', JenisRekening::PENERIMAAN->value)
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->value('id');

        if ($rekeningId) {
            return (int) $rekeningId;
        }

        // 2) Rekening milik User ber-role 'Bendahara Penerimaan'.
        $bendaharaIds = User::role('Bendahara Penerimaan')->pluck('id');

        if ($bendaharaIds->isNotEmpty()) {
            $rekeningId = RekeningBank::query()
                ->where('status_aktif', true)
                ->where('pemilik_type', User::class)
                ->whereIn('pemilik_id', $bendaharaIds)
                ->orderByDesc('is_default')
                ->orderBy('id')
                ->value('id');

            if ($rekeningId) {
                return (int) $rekeningId;
            }
        }

        // 3) Rekening default/aktif pertama.
        $rekeningId = RekeningBank::query()
            ->where('status_aktif', true)
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->value('id');

        return $rekeningId !== null ? (int) $rekeningId : null;
    }
}
