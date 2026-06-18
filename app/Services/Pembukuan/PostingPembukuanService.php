<?php

namespace App\Services\Pembukuan;

use App\Enums\JenisRekening;
use App\Enums\PeranBuku;
use App\Models\BukuKasUmum;
use App\Models\KodeTransaksi;
use App\Models\RekeningBank;
use App\Models\TransaksiPembukuan;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Mesin distribusi SILABI sisi PENGELUARAN.
 *
 * Menerima satu baris jurnal `transaksi_pembukuan`, membaca matriks
 * `kode_transaksi.posting_rules`, lalu membuat baris `buku_kas_umum` untuk BKU
 * (kode_buku 1) dan tiap buku pembantu terkait — arah TERIMA → DEBIT_MASUK,
 * KELUAR → KREDIT_KELUAR. Idempoten per (transaksi_pembukuan_id, kode_buku,
 * arus_kas) sehingga aman dipanggil ulang.
 *
 * Semua baris di-anchor ke rekening pengeluaran yang sama agar saldo berjalan
 * per (rekening, peran, kode_buku) konsisten — lihat
 * [[App\Models\BukuKasUmum]]::recalculateRunningBalance().
 */
class PostingPembukuanService
{
    /**
     * @return Collection<int, BukuKasUmum> baris BKU yang dihasilkan/ditemukan
     */
    public function post(TransaksiPembukuan $trx): Collection
    {
        $kode = KodeTransaksi::where('kode', $trx->kode_transaksi)->first();

        if (! $kode) {
            throw new RuntimeException("Kode transaksi '{$trx->kode_transaksi}' tidak dikenal.");
        }

        $rules = $kode->postings();

        if (empty($rules)) {
            // Kode tanpa aturan distribusi (mis. memo murni) — tidak ada baris kas.
            return collect();
        }

        $nominal = (float) $trx->jumlah_kotor;

        if ($nominal <= 0) {
            throw new RuntimeException('Jumlah kotor transaksi pembukuan harus lebih dari 0.');
        }

        return DB::transaction(function () use ($trx, $rules, $nominal) {
            // Serialize per-jurnal untuk cegah double-post (TOCTOU).
            TransaksiPembukuan::whereKey($trx->id)->lockForUpdate()->first();

            $rekening = $this->resolveRekening($trx);

            if (! $rekening) {
                throw new RuntimeException('Rekening sumber pengeluaran tidak ditemukan untuk posting BKU.');
            }

            $created = collect();

            foreach ($rules as $rule) {
                $kodeBuku = (int) $rule['kode_buku'];
                $arusKas = $rule['arah'] === 'TERIMA' ? 'DEBIT_MASUK' : 'KREDIT_KELUAR';

                $existing = BukuKasUmum::query()
                    ->where('transaksi_pembukuan_id', $trx->id)
                    ->where('kode_buku', $kodeBuku)
                    ->where('arus_kas', $arusKas)
                    ->first();

                if ($existing) {
                    $created->push($existing);

                    continue;
                }

                $created->push(BukuKasUmum::create([
                    'tanggal_transaksi' => $trx->tanggal,
                    'nomor_bukti' => $trx->no_bukti,
                    'uraian' => $trx->uraian,
                    'arus_kas' => $arusKas,
                    'peran' => PeranBuku::PENGELUARAN->value,
                    'kode_buku' => $kodeBuku,
                    'kode_transaksi' => $trx->kode_transaksi,
                    'nominal' => $nominal,
                    'saldo_akhir' => 0, // diisi benar oleh recompute kronologis
                    'sumber_rekening_id' => $rekening->id,
                    'transaksi_pembukuan_id' => $trx->id,
                ]));
            }

            // Recompute saldo seluruh buku rekening ini (per peran, kode_buku).
            BukuKasUmum::recalculateRunningBalance($rekening->id);

            return $created->map->refresh();
        });
    }

    /**
     * Hapus seluruh baris BKU yang dihasilkan satu jurnal lalu recompute —
     * dipakai bila jurnal diedit/dibatalkan (re-post bersih).
     */
    public function reverse(TransaksiPembukuan $trx): void
    {
        DB::transaction(function () use ($trx) {
            $rekeningIds = BukuKasUmum::where('transaksi_pembukuan_id', $trx->id)
                ->pluck('sumber_rekening_id')->unique();

            BukuKasUmum::where('transaksi_pembukuan_id', $trx->id)->delete();

            foreach ($rekeningIds as $rid) {
                if ($rid) {
                    BukuKasUmum::recalculateRunningBalance((int) $rid);
                }
            }
        });
    }

    private function resolveRekening(TransaksiPembukuan $trx): ?RekeningBank
    {
        if ($trx->rekening_bank_id) {
            $rek = RekeningBank::find($trx->rekening_bank_id);
            if ($rek) {
                return $rek;
            }
        }

        return RekeningBank::query()
            ->where('status_aktif', true)
            ->where('jenis_rekening', JenisRekening::PENGELUARAN->value)
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->first()
            ?? RekeningBank::query()
                ->where('status_aktif', true)
                ->orderByDesc('is_default')
                ->orderBy('id')
                ->first();
    }
}
