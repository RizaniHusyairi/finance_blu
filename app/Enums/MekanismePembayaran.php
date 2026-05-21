<?php

namespace App\Enums;

/**
 * Mekanisme pembayaran untuk Tagihan (Kontrak/Perjaldin/Honorarium).
 *
 * LS_PIHAK_3   : Pembayaran LS dari Kas BLU langsung ke rekening vendor /
 *                pegawai / personel (via SP2D - transfer).
 * LS_BENDAHARA : Pembayaran LS melalui rekening Bendahara Pengeluaran,
 *                lalu didistribusikan ke banyak penerima.
 *
 * NB: BLU ini tidak menggunakan mekanisme UP/TUP/GU/NIHIL.
 */
enum MekanismePembayaran: string
{
    case LS_PIHAK_3   = 'LS_PIHAK_3';
    case LS_BENDAHARA = 'LS_BENDAHARA';

    public function label(): string
    {
        return match ($this) {
            self::LS_PIHAK_3   => 'LS - Pihak Ketiga',
            self::LS_BENDAHARA => 'LS - Via Bendahara',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::LS_PIHAK_3   => 'Dana ditransfer langsung ke rekening masing-masing penerima (vendor / pegawai / personel).',
            self::LS_BENDAHARA => 'Dana masuk ke rekening Bendahara Pengeluaran dulu, kemudian didistribusikan.',
        };
    }

    /**
     * Default mekanisme pembayaran per tipe tagihan.
     */
    public static function defaultFor(string $tipeTagihan): self
    {
        return self::LS_PIHAK_3;
    }

    /**
     * Daftar mekanisme yang boleh dipilih untuk tipe tagihan tertentu.
     *
     * @return array<int, self>
     */
    public static function allowedFor(string $tipeTagihan): array
    {
        return match (strtoupper($tipeTagihan)) {
            'KONTRAK'    => [self::LS_PIHAK_3],
            'PERJALDIN'  => [self::LS_PIHAK_3, self::LS_BENDAHARA],
            'HONORARIUM' => [self::LS_PIHAK_3, self::LS_BENDAHARA],
            default      => [self::LS_PIHAK_3, self::LS_BENDAHARA],
        };
    }

    /**
     * Validasi apakah sebuah nilai mekanisme legal untuk tipe tagihan tertentu.
     */
    public static function isAllowedFor(string $value, string $tipeTagihan): bool
    {
        $target = self::tryFrom($value);
        if ($target === null) {
            return false;
        }

        return in_array($target, self::allowedFor($tipeTagihan), true);
    }

    /**
     * Mapping nilai mekanisme ke label "kategori_pembayaran" pada DokumenSpp
     * dan "cara_bayar" pada DokumenSpm (label tercetak di PDF).
     */
    public function sppKategoriPembayaran(): string
    {
        return match ($this) {
            self::LS_PIHAK_3   => 'SP2D BLU - TRF',
            self::LS_BENDAHARA => 'SP2D BLU - TRF BENDAHARA',
        };
    }

    public function spmCaraBayar(): string
    {
        return $this->sppKategoriPembayaran();
    }

    /**
     * Opsi untuk select/dropdown pada form.
     *
     * @return array<string, string>
     */
    public static function optionsFor(string $tipeTagihan): array
    {
        $opts = [];
        foreach (self::allowedFor($tipeTagihan) as $case) {
            $opts[$case->value] = $case->label();
        }
        return $opts;
    }
}
