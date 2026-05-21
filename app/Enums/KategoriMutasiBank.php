<?php

namespace App\Enums;

/**
 * Kategori klasifikasi baris detail_mutasi_bank.
 *
 * Digunakan untuk memilah baris rekening koran menjadi klasifikasi baku sehingga
 * buku pembantu (mis. Buku Pembantu Bunga Rekening) tidak mengandalkan pencarian
 * keyword deskripsi saja.
 */
enum KategoriMutasiBank: string
{
    case BUNGA_MASUK             = 'BUNGA_MASUK';
    case TRANSFER_BUNGA_KELUAR   = 'TRANSFER_BUNGA_KELUAR';
    case BIAYA_ADMIN_BANK        = 'BIAYA_ADMIN_BANK';
    case PAJAK_BUNGA             = 'PAJAK_BUNGA';
    case LAINNYA                 = 'LAINNYA';

    public function label(): string
    {
        return match ($this) {
            self::BUNGA_MASUK           => 'Bunga Masuk',
            self::TRANSFER_BUNGA_KELUAR => 'Transfer Bunga Keluar',
            self::BIAYA_ADMIN_BANK      => 'Biaya Admin Bank',
            self::PAJAK_BUNGA           => 'Pajak Bunga',
            self::LAINNYA               => 'Lainnya',
        };
    }

    /**
     * Coba klasifikasikan baris mutasi berdasarkan deskripsi + arah debit/kredit.
     *
     * @return self|null  null bila tidak jelas; caller boleh biarkan kolom null.
     */
    public static function classify(?string $deskripsi, float $debit, float $kredit): ?self
    {
        $desc = mb_strtolower((string) $deskripsi);

        if ($desc === '') {
            return null;
        }

        $hasBunga = str_contains($desc, 'bunga')
            || str_contains($desc, 'jasa giro')
            || str_contains($desc, 'interest');

        $indicatesTransferKeluar = str_contains($desc, 'transfer')
            || str_contains($desc, 'pindah buku')
            || str_contains($desc, 'setor');

        $indicatesPajak = str_contains($desc, 'pajak')
            || str_contains($desc, 'pph');

        $indicatesAdmin = str_contains($desc, 'biaya admin')
            || str_contains($desc, 'adm bank')
            || str_contains($desc, 'admin bank')
            || str_contains($desc, 'biaya materai');

        if ($hasBunga && $indicatesPajak && $kredit > 0) {
            return self::PAJAK_BUNGA;
        }

        if ($hasBunga && $indicatesTransferKeluar && $kredit > 0) {
            return self::TRANSFER_BUNGA_KELUAR;
        }

        if ($hasBunga && $debit > 0) {
            return self::BUNGA_MASUK;
        }

        if ($hasBunga && $kredit > 0) {
            // ada "bunga" tapi arah kredit dan tanpa pola lain → asumsikan transfer keluar
            return self::TRANSFER_BUNGA_KELUAR;
        }

        if ($indicatesAdmin && $kredit > 0) {
            return self::BIAYA_ADMIN_BANK;
        }

        return null;
    }
}
