<?php

namespace App\Enums;

/**
 * Kode buku pembukuan bendahara (mengikuti penomoran SETUP!Nama_Buku File 1).
 *
 * BKU (1) adalah buku induk; sisanya buku pembantu. Invariant kas:
 *   Saldo BKU = Saldo Kas Tunai (2) + Saldo Kas Bank (3).
 * Satu baris jurnal `transaksi_pembukuan` dapat memunculkan baris di beberapa
 * buku sekaligus sesuai `kode_transaksi.posting_rules`.
 */
enum KodeBuku: int
{
    case BKU              = 1;  // Buku Kas Umum
    case KAS_TUNAI        = 2;  // Buku Pembantu Kas (Tunai)
    case BANK             = 3;  // Buku Pembantu Bank
    case BPP              = 4;  // Buku Pembantu BPP
    case UP               = 5;  // Buku Pembantu Uang Persediaan
    case LS_BENDAHARA     = 6;  // Buku Pembantu LS Bendahara
    case UM_PERJADIN      = 7;  // Buku Pembantu Uang Muka Perjadin
    case PAJAK            = 8;  // Buku Pembantu Pajak
    case BUNGA            = 9;  // Buku Pembantu Bunga Rekening
    case PAJAK_LS         = 10; // Buku Pembantu Pajak LS
    case PENGESAHAN       = 11; // Buku Pembantu Pengesahan
    case PENGEMBALIAN     = 12; // Buku Pengembalian Belanja

    public function label(): string
    {
        return match ($this) {
            self::BKU          => 'Buku Kas Umum',
            self::KAS_TUNAI    => 'Buku Pembantu Kas (Tunai)',
            self::BANK         => 'Buku Pembantu Bank',
            self::BPP          => 'Buku Pembantu BPP',
            self::UP           => 'Buku Pembantu Uang Persediaan',
            self::LS_BENDAHARA => 'Buku Pembantu LS Bendahara',
            self::UM_PERJADIN  => 'Buku Pembantu Uang Muka Perjadin',
            self::PAJAK        => 'Buku Pembantu Pajak',
            self::BUNGA        => 'Buku Pembantu Bunga Rekening',
            self::PAJAK_LS     => 'Buku Pembantu Pajak LS',
            self::PENGESAHAN   => 'Buku Pembantu Pengesahan',
            self::PENGEMBALIAN => 'Buku Pengembalian Belanja',
        };
    }

    /** @return array<int, string> */
    public static function options(): array
    {
        $opts = [];
        foreach (self::cases() as $case) {
            $opts[$case->value] = $case->label();
        }

        return $opts;
    }
}
