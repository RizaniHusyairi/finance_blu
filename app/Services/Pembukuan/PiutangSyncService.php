<?php

namespace App\Services\Pembukuan;

use App\Models\BukuKasUmum;
use App\Models\MasterCoa;
use App\Models\MasterPihak;
use App\Models\MitraJasa;
use App\Models\RekeningBank;
use App\Models\TagihanJasa;
use App\Models\TransaksiPenerimaan;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Sync TagihanJasa <-> Piutang (TransaksiPenerimaan) <-> BKU.
 *
 * Alur:
 *  - publish()    : Tagihan PUBLISHED → buat baris piutang status UNPAID di
 *                   tabel `transaksi_penerimaan` (tampil di menu Piutang
 *                   Bendahara Penerimaan).
 *  - markAsPaid() : Tagihan LUNAS → update piutang status PAID + total_dibayar,
 *                   lalu append baris BKU `DEBIT_MASUK` (tampil di laporan BKU
 *                   dan Buku Pembantu Bank).
 *
 * Idempotent: pakai `nomor_invoice` = nomor_tagihan sebagai key, sehingga
 * dipanggil ulang tidak akan menghasilkan duplikat.
 */
class PiutangSyncService
{
    /**
     * Buat / refresh baris piutang berdasarkan tagihan yang baru saja PUBLISHED.
     */
    public function syncFromPublished(TagihanJasa $tagihan): ?TransaksiPenerimaan
    {
        try {
            return DB::transaction(function () use ($tagihan) {
                $mitraPihak = $this->resolveOrCreateMitraPihak($tagihan);
                if (! $mitraPihak) {
                    Log::warning('PiutangSync: gagal resolve master_pihak untuk tagihan ' . $tagihan->nomor_tagihan);
                    return null;
                }

                $coaId = $this->resolveCoaId($tagihan);
                if (! $coaId) {
                    Log::warning('PiutangSync: belum ada COA penerimaan default. Lewati publish piutang.');
                    return null;
                }

                $piutang = TransaksiPenerimaan::firstOrNew(['nomor_invoice' => $tagihan->nomor_tagihan]);
                $piutang->mitra_id = $mitraPihak->id;
                $piutang->coa_id = $coaId;
                $piutang->tanggal_invoice = $tagihan->tanggal_publish ?? now()->toDateString();
                $piutang->tanggal_jatuh_tempo = $tagihan->tanggal_jatuh_tempo;
                $piutang->nominal_tagihan = (float) $tagihan->total_tagihan;
                $piutang->status_pembayaran = 'UNPAID';
                $piutang->keterangan = 'Tagihan PNBP Jasa: ' . ($tagihan->nomor_tagihan)
                    . ' | Mitra: ' . ($tagihan->mitra->nama_mitra ?? '-');
                $piutang->save();

                return $piutang;
            });
        } catch (\Throwable $e) {
            Log::error('PiutangSync syncFromPublished error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Tandai piutang LUNAS dan catat ke BKU sebagai DEBIT_MASUK.
     *
     * @param array{amount?: float, paid_at?: \Carbon\Carbon|string|null, reference?: string|null} $payment
     */
    public function syncFromLunas(TagihanJasa $tagihan, array $payment = []): ?BukuKasUmum
    {
        try {
            return DB::transaction(function () use ($tagihan, $payment) {
                $piutang = TransaksiPenerimaan::where('nomor_invoice', $tagihan->nomor_tagihan)->first();

                // Kalau belum ada (mis. dipublish sebelum service ini ada), buat dulu.
                if (! $piutang) {
                    $piutang = $this->syncFromPublished($tagihan);
                    if (! $piutang) {
                        return null;
                    }
                }

                $amount = (float) ($payment['amount'] ?? $tagihan->total_tagihan);
                $paidAt = $payment['paid_at'] ?? now();
                if (! $paidAt instanceof Carbon) {
                    $paidAt = $paidAt ? Carbon::parse($paidAt) : now();
                }
                $reference = (string) ($payment['reference'] ?? ('LUNAS/' . $tagihan->nomor_tagihan));

                $piutang->total_dibayar = $amount;
                $piutang->status_pembayaran = 'PAID';
                $piutang->save();

                $rekeningId = $this->resolveDefaultRekeningId();
                if (! $rekeningId) {
                    Log::warning('PiutangSync: belum ada rekening_bank default. BKU tidak dicatat untuk ' . $tagihan->nomor_tagihan);
                    return null;
                }

                // Pastikan tidak duplikat BKU untuk piutang yang sama.
                $existingBku = BukuKasUmum::where('referensi_penerimaan_id', $piutang->id)
                    ->where('arus_kas', 'DEBIT_MASUK')
                    ->first();
                if ($existingBku) {
                    return $existingBku;
                }

                // Hitung saldo akhir berdasarkan saldo BKU terakhir untuk rekening tsb.
                $saldoTerakhir = (float) BukuKasUmum::where('sumber_rekening_id', $rekeningId)
                    ->latest('id')
                    ->value('saldo_akhir') ?? 0.0;

                return BukuKasUmum::create([
                    'tanggal_transaksi' => $paidAt->toDateString(),
                    'nomor_bukti' => $reference,
                    'uraian' => 'Penerimaan PNBP Jasa: ' . $tagihan->nomor_tagihan
                        . ' (' . ($tagihan->mitra->nama_mitra ?? '-') . ')',
                    'arus_kas' => 'DEBIT_MASUK',
                    'nominal' => $amount,
                    'saldo_akhir' => $saldoTerakhir + $amount,
                    'sumber_rekening_id' => $rekeningId,
                    'referensi_penerimaan_id' => $piutang->id,
                ]);
            });
        } catch (\Throwable $e) {
            Log::error('PiutangSync syncFromLunas error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Cari `master_pihak` yang sesuai untuk MitraJasa, atau buat shadow row.
     * Prioritas:
     *  1) `tagihan->mitraLegacy` (kalau tagihan punya mitra_id ke master_pihak)
     *  2) `master_pihak.npwp` = MitraJasa.npwp
     *  3) `master_pihak.nama_pihak` = MitraJasa.nama_mitra
     *  4) Buat baru.
     */
    private function resolveOrCreateMitraPihak(TagihanJasa $tagihan): ?MasterPihak
    {
        // 1) Legacy direct relation
        if ($tagihan->mitra_id) {
            $legacy = MasterPihak::find($tagihan->mitra_id);
            if ($legacy) return $legacy;
        }

        $mitra = $tagihan->mitra ?? null;
        if (! $mitra instanceof MitraJasa) {
            return null;
        }

        // 2) Cari berdasarkan NPWP/nama
        $existing = null;
        if (filled($mitra->npwp)) {
            $existing = MasterPihak::where('npwp', $mitra->npwp)->first();
        }
        if (! $existing && filled($mitra->nama_mitra)) {
            $existing = MasterPihak::where('nama_pihak', $mitra->nama_mitra)
                ->where('kategori', '!=', 'PENGELUARAN')
                ->first();
        }
        if ($existing) {
            return $existing;
        }

        // 3) Buat shadow record
        return MasterPihak::create([
            'kategori' => 'PENERIMAAN',
            'jenis_entitas' => 'BADAN_USAHA',
            'kode_pihak' => $mitra->kode_mitra ?: ('MTR-' . $mitra->id),
            'npwp' => $mitra->npwp,
            'nama_pihak' => $mitra->nama_mitra,
            'nama_penanggung_jawab' => $mitra->nama_penanggung_jawab,
            'alamat' => $mitra->alamat,
            'email' => $mitra->email,
            'no_telepon' => $mitra->no_telepon,
            'status_aktif' => (bool) $mitra->status_aktif,
        ]);
    }

    private function resolveCoaId(TagihanJasa $tagihan): ?int
    {
        // Pakai COA dari detail tagihan pertama, fallback ke COA penerimaan PNBP default.
        $firstDetail = $tagihan->details->first();
        if ($firstDetail && $firstDetail->kode_akun) {
            $coa = MasterCoa::where('kode_mak_lengkap', $firstDetail->kode_akun)
                ->orWhere('kode_akun', $firstDetail->kode_akun)
                ->first();
            if ($coa) return $coa->id;
        }

        // Fallback: COA penerimaan apa pun yang aktif.
        $any = MasterCoa::where('jenis_belanja', 'PENERIMAAN')
            ->orWhere('nama_akun', 'like', '%PENERIMAAN%PNBP%')
            ->first();

        return $any?->id ?? MasterCoa::query()->value('id');
    }

    private function resolveDefaultRekeningId(): ?int
    {
        return RekeningBank::query()
            ->where('status_aktif', true)
            ->orderBy('id')
            ->value('id') ?? RekeningBank::query()->orderBy('id')->value('id');
    }
}
