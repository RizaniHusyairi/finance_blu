<?php

namespace App\Enums;

/**
 * Peran buku kas: memisahkan pembukuan Bendahara Penerimaan dan Pengeluaran.
 *
 * Mengacu dua dokumen acuan:
 *  - PENGELUARAN : BKU Bendahara Pengeluaran model SILABI (File 1) — digerakkan
 *                  oleh jurnal `transaksi_pembukuan` + kode transaksi.
 *  - PENERIMAAN  : BKU Bendahara Penerimaan (File 2) — digerakkan oleh klasifikasi
 *                  baris rekening koran ke akun pendapatan.
 *
 * Selaras dengan [[App\Enums\JenisRekening]] (penanda peran rekening).
 */
enum PeranBuku: string
{
    case PENERIMAAN = 'PENERIMAAN';
    case PENGELUARAN = 'PENGELUARAN';

    public function label(): string
    {
        return match ($this) {
            self::PENERIMAAN => 'Bendahara Penerimaan',
            self::PENGELUARAN => 'Bendahara Pengeluaran',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        $opts = [];
        foreach (self::cases() as $case) {
            $opts[$case->value] = $case->label();
        }

        return $opts;
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }
}
