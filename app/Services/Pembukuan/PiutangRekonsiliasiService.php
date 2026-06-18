<?php

namespace App\Services\Pembukuan;

use App\Enums\PeranBuku;
use App\Models\BukuKasUmum;
use App\Models\TransaksiPenerimaan;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Pencocokan baris BKU Penerimaan (asal rekening koran) ↔ piutang
 * (transaksi_penerimaan). Tautan disimpan pada buku_kas_umum.referensi_penerimaan_id.
 *
 * Saat cocok: piutang diperbarui (total_dibayar, status_pembayaran, tanggal_bayar).
 * Melengkapi rekonsiliasi bank↔BKU yang sudah ada di PembukuanService — di sini
 * fokusnya menautkan penerimaan ke tagihan/piutang.
 */
class PiutangRekonsiliasiService
{
    /**
     * Cocokkan otomatis: tiap piutang belum lunas dipasangkan ke satu baris BKU
     * Penerimaan (nominal sama, tanggal dalam toleransi) yang belum tertaut.
     *
     * @return array{matched:int, sisa_piutang:int}
     */
    public function autoMatch(array $filters, int $toleransiHari = 7): array
    {
        return DB::transaction(function () use ($filters, $toleransiHari) {
            $rekeningId = $filters['rekening_bank_id'] ?? null;

            $piutangs = TransaksiPenerimaan::query()
                ->where('status_pembayaran', '!=', 'PAID')
                ->when($filters['start_date'] ?? null, fn (Builder $q, $d) => $q->whereDate('tanggal_invoice', '>=', $d))
                ->when($filters['end_date'] ?? null, fn (Builder $q, $d) => $q->whereDate('tanggal_invoice', '<=', $d))
                ->orderBy('tanggal_invoice')->orderBy('id')
                ->get();

            $bkuRows = $this->unmatchedBku($rekeningId)->get();
            $used = [];
            $matched = 0;

            foreach ($piutangs as $p) {
                $sisa = $p->sisaPiutang();
                if ($sisa <= 0) {
                    continue;
                }

                $patokTanggal = Carbon::parse($p->tanggal_jatuh_tempo ?? $p->tanggal_invoice ?? now());

                $cocok = $bkuRows
                    ->reject(fn (BukuKasUmum $b) => in_array($b->id, $used, true))
                    ->filter(fn (BukuKasUmum $b) => abs((float) $b->nominal - $sisa) < 0.01
                        && abs(Carbon::parse($b->tanggal_transaksi)->diffInDays($patokTanggal)) <= $toleransiHari)
                    ->sortBy(fn (BukuKasUmum $b) => abs(Carbon::parse($b->tanggal_transaksi)->diffInDays($patokTanggal)))
                    ->first();

                if (! $cocok) {
                    continue;
                }

                $this->link($p, $cocok);
                $used[] = $cocok->id;
                $matched++;
            }

            return ['matched' => $matched, 'sisa_piutang' => $piutangs->count() - $matched];
        });
    }

    /** Pasangkan manual satu piutang ke satu baris BKU Penerimaan. */
    public function manualMatch(int $piutangId, int $bkuId): void
    {
        DB::transaction(function () use ($piutangId, $bkuId) {
            $p = TransaksiPenerimaan::findOrFail($piutangId);
            $bku = BukuKasUmum::where('peran', PeranBuku::PENERIMAAN->value)
                ->whereNull('referensi_penerimaan_id')
                ->findOrFail($bkuId);

            $this->link($p, $bku);
        });
    }

    /** Batalkan tautan satu baris BKU dan hitung ulang status piutangnya. */
    public function unmatch(int $bkuId): void
    {
        DB::transaction(function () use ($bkuId) {
            $bku = BukuKasUmum::findOrFail($bkuId);
            $piutang = $bku->referensi_penerimaan_id
                ? TransaksiPenerimaan::find($bku->referensi_penerimaan_id)
                : null;

            $bku->referensi_penerimaan_id = null;
            $bku->saveQuietly();

            if ($piutang) {
                $this->recomputePiutang($piutang);
            }
        });
    }

    private function link(TransaksiPenerimaan $p, BukuKasUmum $bku): void
    {
        $bku->referensi_penerimaan_id = $p->id;
        $bku->saveQuietly();

        $this->recomputePiutang($p);
    }

    /** Hitung ulang total_dibayar/status/tanggal_bayar dari baris BKU yang tertaut. */
    private function recomputePiutang(TransaksiPenerimaan $p): void
    {
        $linked = BukuKasUmum::where('referensi_penerimaan_id', $p->id)
            ->where('arus_kas', 'DEBIT_MASUK')
            ->get();

        $dibayar = (float) $linked->sum(fn (BukuKasUmum $b) => (float) $b->nominal);
        $tagihan = (float) $p->nominal_tagihan;

        $p->total_dibayar = $dibayar;
        $p->status_pembayaran = match (true) {
            $dibayar <= 0 => 'UNPAID',
            $dibayar + 0.01 >= $tagihan => 'PAID',
            default => 'PARTIAL',
        };
        $p->tanggal_bayar = $linked->max('tanggal_transaksi');
        $p->save();
    }

    private function unmatchedBku(?int $rekeningId): Builder
    {
        return BukuKasUmum::query()
            ->where('peran', PeranBuku::PENERIMAAN->value)
            ->where('arus_kas', 'DEBIT_MASUK')
            ->whereNull('referensi_penerimaan_id')
            ->when($rekeningId, fn (Builder $q) => $q->where('sumber_rekening_id', $rekeningId))
            ->orderBy('tanggal_transaksi')->orderBy('id');
    }
}
