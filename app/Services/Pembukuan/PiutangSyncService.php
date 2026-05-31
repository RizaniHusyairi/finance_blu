<?php

namespace App\Services\Pembukuan;

use App\Models\BukuKasUmum;
use App\Models\MasterCoa;
use App\Models\MasterPihak;
use App\Models\MitraJasa;
use App\Models\RekeningBank;
use App\Models\TagihanJasa;
use App\Models\TransaksiPenerimaan;
use App\Models\User;
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
                $existingStatus = $piutang->exists ? $piutang->status_pembayaran : null;
                $piutang->mitra_id = $mitraPihak->id;
                $piutang->coa_id = $coaId;
                $piutang->tanggal_invoice = $tagihan->tanggal_publish ?? now()->toDateString();
                $piutang->tanggal_jatuh_tempo = $tagihan->tanggal_jatuh_tempo;
                $piutang->nominal_tagihan = (float) $tagihan->total_tagihan;
                $piutang->status_pembayaran = in_array($existingStatus, ['PARTIAL', 'PAID'], true)
                    ? $existingStatus
                    : 'UNPAID';
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
     * Catat pembayaran sebagian ke piutang tanpa membentuk BKU.
     *
     * @param array{amount?: float, paid_at?: \Carbon\Carbon|string|null} $payment
     */
    public function syncFromPartial(TagihanJasa $tagihan, array $payment = []): ?TransaksiPenerimaan
    {
        try {
            return DB::transaction(function () use ($tagihan, $payment) {
                $piutang = TransaksiPenerimaan::where('nomor_invoice', $tagihan->nomor_tagihan)->first();

                if (! $piutang) {
                    $piutang = $this->syncFromPublished($tagihan);
                    if (! $piutang) {
                        return null;
                    }
                }

                if ($piutang->status_pembayaran === 'PAID') {
                    return $piutang;
                }

                $amount = max(0, (float) ($payment['amount'] ?? $tagihan->jumlah_dibayar ?? 0));
                $piutang->total_dibayar = $amount;
                $piutang->status_pembayaran = $amount > 0 ? 'PARTIAL' : 'UNPAID';
                $piutang->save();

                return $piutang;
            });
        } catch (\Throwable $e) {
            Log::error('PiutangSync syncFromPartial error: ' . $e->getMessage());
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

                $rekeningId = $this->resolvePenerimaanRekeningId();
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

                // Estimasi awal saldo akhir dari saldo BKU terakhir untuk rekening tsb.
                $saldoTerakhir = (float) BukuKasUmum::where('sumber_rekening_id', $rekeningId)
                    ->latest('id')
                    ->value('saldo_akhir') ?? 0.0;

                $bku = BukuKasUmum::create([
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

                // Saldo di atas hanya estimasi. Recompute KRONOLOGIS (tanggal_transaksi
                // ASC, id ASC) supaya saldo berjalan tetap benar walau penerimaan ini
                // bertanggal lebih awal dari baris pengeluaran yang sudah ada lebih dulu.
                BukuKasUmum::recalculateRunningBalance($rekeningId);
                $bku->refresh();

                return $bku;
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
            $kodeAkun = trim((string) $firstDetail->kode_akun);
            $kdAkun = strtok($kodeAkun, '.') ?: $kodeAkun;

            $coa = MasterCoa::query()
                ->where('kode_mak_lengkap', $kodeAkun)
                ->orWhere('kd_akun', $kodeAkun)
                ->orWhere('kd_akun', $kdAkun)
                ->first();
            if ($coa) return $coa->id;
        }

        // Fallback: COA penerimaan apa pun yang aktif.
        $any = MasterCoa::where('jenis_akun', 'like', '4%')
            ->orWhere('nama_akun', 'like', '%PENERIMAAN%PNBP%')
            ->orWhere('nama_akun', 'like', '%PNBP%')
            ->first();

        return $any?->id ?? MasterCoa::query()->value('id');
    }

    /**
     * Tentukan rekening tujuan penerimaan PNBP/Jasa (sumber_rekening_id) untuk BKU.
     *
     * Penerimaan jasa harus masuk ke rekening Bendahara Penerimaan, BUKAN sekadar
     * rekening aktif pertama (yang bisa saja milik Bendahara Pengeluaran).
     *
     * Prioritas:
     *  1) Rekening aktif yang ditandai eksplisit jenis_rekening = PENERIMAAN
     *     (utamakan is_default = true). Penanda eksplisit > tebakan.
     *  2) Rekening aktif milik User ber-role 'Bendahara Penerimaan'
     *     (utamakan is_default = true, lalu id terkecil).
     *  3) Rekening aktif yang ditandai is_default = true.
     *  4) Rekening aktif pertama berdasarkan id (perilaku lama).
     *  5) Last resort: rekening apa pun berdasarkan id.
     */
    private function resolvePenerimaanRekeningId(): ?int
    {
        // 1) Penanda eksplisit jenis_rekening = PENERIMAAN.
        $rekeningId = RekeningBank::query()
            ->where('status_aktif', true)
            ->where('jenis_rekening', \App\Enums\JenisRekening::PENERIMAAN->value)
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->value('id');

        if ($rekeningId) {
            return (int) $rekeningId;
        }

        // 2) Rekening milik Bendahara Penerimaan (lihat role via HasRoles di User).
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

        // 2) Rekening aktif default.
        $rekeningId = RekeningBank::query()
            ->where('status_aktif', true)
            ->where('is_default', true)
            ->orderBy('id')
            ->value('id');

        if ($rekeningId) {
            return (int) $rekeningId;
        }

        // 3) Rekening aktif pertama, lalu 4) rekening apa pun.
        $rekeningId = RekeningBank::query()
            ->where('status_aktif', true)
            ->orderBy('id')
            ->value('id') ?? RekeningBank::query()->orderBy('id')->value('id');

        return $rekeningId !== null ? (int) $rekeningId : null;
    }
}
