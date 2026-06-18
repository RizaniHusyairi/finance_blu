<?php

namespace App\Services\Pembukuan;

use App\Enums\KodeBuku;
use App\Enums\PeranBuku;
use App\Models\BukuKasUmum;
use App\Models\DetailMutasiBank;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Mesin BKU Penerimaan (model File 2): baris rekening koran → baris BKU.
 *
 * Tiap baris `detail_mutasi_bank`:
 *  - arah MASUK  → DEBIT_MASUK pada BKU Penerimaan, diklasifikasi ke akun pendapatan;
 *  - arah KELUAR → KREDIT_KELUAR (mis. PBK transfer ke rekening pengeluaran).
 *
 * Idempoten per `detail_mutasi_bank_id` (satu baris koran → satu baris BKU).
 * Saldo berjalan dihitung per (rekening, PENERIMAAN, kode_buku=BKU) oleh
 * [[App\Models\BukuKasUmum]]::recalculateRunningBalance().
 */
class PostingPenerimaanService
{
    public function __construct(
        private readonly AkunPendapatanClassifier $classifier,
    ) {
    }

    /** Set akun_pendapatan_id otomatis untuk baris MASUK bila belum diisi. */
    public function classify(DetailMutasiBank $row): ?int
    {
        if ($row->arah_mutasi !== 'MASUK') {
            return null;
        }

        if ($row->akun_pendapatan_id) {
            return (int) $row->akun_pendapatan_id;
        }

        $id = $this->classifier->classifyId($row->deskripsi);

        if ($id) {
            $row->akun_pendapatan_id = $id;
            $row->save();
        }

        return $id;
    }

    /** Posting satu baris koran ke BKU Penerimaan (idempoten) + recompute saldo. */
    public function post(DetailMutasiBank $row): ?BukuKasUmum
    {
        return DB::transaction(function () use ($row) {
            $bku = $this->createBkuRow($row);

            if ($bku) {
                BukuKasUmum::recalculateRunningBalance((int) $bku->sumber_rekening_id, KodeBuku::BKU->value);
                $bku->refresh();
            }

            return $bku;
        });
    }

    /**
     * Klasifikasi + posting massal seluruh baris koran satu rekening (opsional
     * rentang tanggal) yang belum terposting. Recompute saldo sekali di akhir.
     *
     * @return array{posted:int, classified:int, skipped:int, unclassified:int}
     */
    public function postBatch(array $filters): array
    {
        $rekeningId = $filters['rekening_bank_id'] ?? null;

        if (! $rekeningId) {
            throw new RuntimeException('Pilih rekening penerimaan untuk posting massal.');
        }

        $start = $filters['start_date'] ?? null;
        $end = $filters['end_date'] ?? null;

        return DB::transaction(function () use ($rekeningId, $start, $end) {
            $rows = DetailMutasiBank::query()
                ->whereHas('importMutasiBank', fn (Builder $q) => $q->where('rekening_bank_id', $rekeningId))
                ->when($start, fn (Builder $q) => $q->whereDate('tanggal_transaksi', '>=', $start))
                ->when($end, fn (Builder $q) => $q->whereDate('tanggal_transaksi', '<=', $end))
                ->orderBy('tanggal_transaksi')->orderBy('id')
                ->get();

            $posted = $classified = $skipped = $unclassified = 0;

            foreach ($rows as $row) {
                if ($row->arah_mutasi === 'MASUK' && ! $row->akun_pendapatan_id) {
                    if ($this->classify($row)) {
                        $classified++;
                    } else {
                        $unclassified++;
                    }
                }

                $bku = $this->createBkuRow($row);
                $bku ? $posted++ : $skipped++;
            }

            BukuKasUmum::recalculateRunningBalance((int) $rekeningId, KodeBuku::BKU->value);

            return compact('posted', 'classified', 'skipped', 'unclassified');
        });
    }

    /** Hapus baris BKU Penerimaan hasil satu baris koran + recompute. */
    public function reverse(DetailMutasiBank $row): void
    {
        DB::transaction(function () use ($row) {
            $rekIds = BukuKasUmum::where('detail_mutasi_bank_id', $row->id)
                ->where('peran', PeranBuku::PENERIMAAN->value)
                ->pluck('sumber_rekening_id')->unique();

            BukuKasUmum::where('detail_mutasi_bank_id', $row->id)
                ->where('peran', PeranBuku::PENERIMAAN->value)
                ->delete();

            foreach ($rekIds as $rid) {
                if ($rid) {
                    BukuKasUmum::recalculateRunningBalance((int) $rid, KodeBuku::BKU->value);
                }
            }
        });
    }

    /**
     * Buat baris BKU Penerimaan dari satu baris koran (tanpa recompute).
     * Idempoten: kembalikan baris yang sudah ada bila pernah diposting.
     */
    private function createBkuRow(DetailMutasiBank $row): ?BukuKasUmum
    {
        $existing = BukuKasUmum::query()
            ->where('detail_mutasi_bank_id', $row->id)
            ->where('peran', PeranBuku::PENERIMAAN->value)
            ->first();

        if ($existing) {
            return $existing;
        }

        $rekeningId = $row->importMutasiBank?->rekening_bank_id;

        if (! $rekeningId) {
            throw new RuntimeException('Baris koran tidak terkait rekening — tidak bisa diposting ke BKU Penerimaan.');
        }

        $masuk = $row->arah_mutasi === 'MASUK';
        $nominal = $masuk ? (float) $row->kredit : (float) $row->debit;

        if ($nominal <= 0) {
            return null; // baris tanpa nilai relevan — lewati.
        }

        return BukuKasUmum::create([
            'tanggal_transaksi' => $row->tanggal_transaksi,
            'nomor_bukti' => $row->nomor_referensi_bank ?: ('KORAN/' . $row->id),
            'uraian' => $row->deskripsi ?: 'Mutasi rekening koran',
            'arus_kas' => $masuk ? 'DEBIT_MASUK' : 'KREDIT_KELUAR',
            'peran' => PeranBuku::PENERIMAAN->value,
            'kode_buku' => KodeBuku::BKU->value,
            'nominal' => $nominal,
            'saldo_akhir' => 0,
            'sumber_rekening_id' => $rekeningId,
            'akun_pendapatan_id' => $masuk ? $row->akun_pendapatan_id : null,
            'detail_mutasi_bank_id' => $row->id,
        ]);
    }
}
