<?php

namespace App\Enums;

/**
 * Jenis rekening bank untuk menetapkan peran rekening pada pembukuan.
 *
 * PENERIMAAN  : Rekening Bendahara Penerimaan — muara penerimaan PNBP/jasa
 *               (baris BKU DEBIT_MASUK saat tagihan jasa LUNAS).
 * PENGELUARAN : Rekening Bendahara Pengeluaran — sumber pembayaran belanja
 *               (baris BKU KREDIT_KELUAR saat SP2D dieksekusi).
 * LAINNYA     : Rekening lain (operasional/penampungan) yang belum digolongkan.
 *
 * Penanda eksplisit ini menggantikan tebakan via role + is_default pada
 * resolusi sumber rekening BKU.
 */
enum JenisRekening: string
{
    case PENERIMAAN = 'PENERIMAAN';
    case PENGELUARAN = 'PENGELUARAN';
    case LAINNYA = 'LAINNYA';

    public function label(): string
    {
        return match ($this) {
            self::PENERIMAAN => 'Penerimaan',
            self::PENGELUARAN => 'Pengeluaran',
            self::LAINNYA => 'Lainnya',
        };
    }

    /**
     * Opsi untuk select/dropdown pada form.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        $opts = [];
        foreach (self::cases() as $case) {
            $opts[$case->value] = $case->label();
        }
        return $opts;
    }

    /**
     * Daftar nilai valid (untuk Rule::in).
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }
}
