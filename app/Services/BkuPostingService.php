<?php

namespace App\Services;

use App\Models\BukuKasUmum;
use App\Models\DokumenSp2d;
use App\Models\LogStatusDokumen;
use App\Models\RekeningBank;
use App\Models\Tagihan;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class BkuPostingService
{
    public function postTagihanPengeluaran(
        Tagihan $tagihan,
        ?DokumenSp2d $sp2d = null,
        ?string $catatan = null,
        ?float $nominal = null
    ): BukuKasUmum {
        $sp2d ??= $this->resolveSp2d($tagihan);

        if (! $sp2d) {
            throw new RuntimeException('SP2D untuk tagihan ini tidak ditemukan.');
        }

        $nomorBukti = $sp2d->nomor_sp2d ?: $tagihan->nomor_tagihan;

        return DB::transaction(function () use ($tagihan, $sp2d, $catatan, $nominal, $nomorBukti) {
            // Serialize posting per-tagihan untuk mencegah race condition (TOCTOU) double-click.
            Tagihan::whereKey($tagihan->id)->lockForUpdate()->first();

            $existing = $this->findExistingBku($tagihan->id, $nomorBukti);

            if ($existing) {
                return $existing;
            }

            $rekening = $this->resolveSumberRekening($tagihan, $sp2d);

            if (! $rekening) {
                throw new RuntimeException('Rekening sumber BKU tidak ditemukan. Lengkapi rekening sumber pada Standing Instruction atau rekening default Bendahara Pengeluaran.');
            }

            $nominalTransaksi = $nominal ?? $this->resolveNominal($tagihan, $sp2d);

            if ($nominalTransaksi <= 0) {
                throw new RuntimeException('Nominal BKU tidak valid.');
            }

            $tanggalTransaksi = $sp2d->tanggal_sp2d ?? now();
            $saldoAkhir = $this->nextSaldoAkhir($rekening, $nominalTransaksi);

            try {
                $bku = BukuKasUmum::create([
                    'tanggal_transaksi' => $tanggalTransaksi,
                    'nomor_bukti' => $nomorBukti,
                    'uraian' => $catatan ?: $this->defaultUraian($tagihan, $sp2d),
                    'arus_kas' => 'KREDIT_KELUAR',
                    'nominal' => $nominalTransaksi,
                    'saldo_akhir' => $saldoAkhir,
                    'sumber_rekening_id' => $rekening->id,
                    'referensi_pengeluaran_id' => $tagihan->id,
                    'referensi_penerimaan_id' => null,
                ]);
            } catch (QueryException $e) {
                // Pelanggaran unique akibat race: baris BKU sudah dibuat request lain.
                $existing = $this->findExistingBku($tagihan->id, $nomorBukti);

                if ($existing) {
                    return $existing;
                }

                throw $e;
            }

            LogStatusDokumen::create([
                'dokumen_type' => Tagihan::class,
                'dokumen_id' => $tagihan->id,
                'user_id' => Auth::id(),
                'role_saat_itu' => Auth::user()?->getRoleNames()->first() ?? 'SYSTEM',
                'status_sebelumnya' => $tagihan->status,
                'status_baru' => $tagihan->status,
                'aksi' => 'POST_BKU',
                'catatan' => "Tagihan masuk BKU dengan nomor bukti {$nomorBukti}.",
                'ip_address' => request()?->ip(),
            ]);

            return $bku;
        });
    }

    private function findExistingBku(int $tagihanId, string $nomorBukti): ?BukuKasUmum
    {
        return BukuKasUmum::query()
            ->where('referensi_pengeluaran_id', $tagihanId)
            ->where('nomor_bukti', $nomorBukti)
            ->first();
    }

    private function resolveSp2d(Tagihan $tagihan): ?DokumenSp2d
    {
        $spp = $tagihan->relationLoaded('spps')
            ? $tagihan->spps->sortByDesc('created_at')->first()
            : $tagihan->spps()->latest()->first();

        return $spp?->spm?->npi?->sp2d;
    }

    private function resolveSumberRekening(Tagihan $tagihan, DokumenSp2d $sp2d): ?RekeningBank
    {
        $sp2d->loadMissing('npi.spm.spp.standingInstruction');
        $spp = $sp2d->npi?->spm?->spp;

        if (! $spp) {
            $spp = $tagihan->relationLoaded('spps')
                ? $tagihan->spps->sortByDesc('created_at')->first()
                : $tagihan->spps()->with('standingInstruction')->latest()->first();
        }

        $standingInstruction = $spp?->standingInstruction;
        $nomorSumber = $this->normalizeAccountNumber($standingInstruction?->rekening_sumber_nomor);

        if ($nomorSumber) {
            $rekening = RekeningBank::query()
                ->where('status_aktif', true)
                ->get()
                ->first(fn (RekeningBank $item) => $this->normalizeAccountNumber($item->nomor_rekening) === $nomorSumber);

            if ($rekening) {
                return $rekening;
            }
        }

        if ($sp2d->bendahara_pengeluaran_id) {
            $rekening = RekeningBank::query()
                ->where('pemilik_type', User::class)
                ->where('pemilik_id', $sp2d->bendahara_pengeluaran_id)
                ->where('status_aktif', true)
                ->orderByDesc('is_default')
                ->orderBy('id')
                ->first();

            if ($rekening) {
                return $rekening;
            }
        }

        return RekeningBank::query()
            ->where('status_aktif', true)
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->first();
    }

    private function resolveNominal(Tagihan $tagihan, DokumenSp2d $sp2d): float
    {
        $sp2d->loadMissing('npi.spm.spp');
        $spp = $sp2d->npi?->spm?->spp;

        if (! $spp) {
            $spp = $tagihan->relationLoaded('spps')
                ? $tagihan->spps->sortByDesc('created_at')->first()
                : $tagihan->spps()->latest()->first();
        }

        return (float) ($spp?->nominal_spp ?? $tagihan->total_netto ?? $tagihan->total_bruto ?? 0);
    }

    private function nextSaldoAkhir(RekeningBank $rekening, float $nominalKeluar): float
    {
        $latest = BukuKasUmum::query()
            ->where('sumber_rekening_id', $rekening->id)
            ->orderByDesc('tanggal_transaksi')
            ->orderByDesc('id')
            ->first();

        return (float) ($latest?->saldo_akhir ?? 0) - $nominalKeluar;
    }

    private function defaultUraian(Tagihan $tagihan, DokumenSp2d $sp2d): string
    {
        return trim("Pembayaran {$tagihan->tipe_tagihan} {$tagihan->nomor_tagihan} melalui SP2D {$sp2d->nomor_sp2d}");
    }

    private function normalizeAccountNumber(?string $number): ?string
    {
        if (! $number) {
            return null;
        }

        $normalized = preg_replace('/\D+/', '', $number);

        return $normalized !== '' ? $normalized : null;
    }
}
