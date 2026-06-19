<?php

namespace App\Enums;

/**
 * Kategori mutasi kas "non-pola" pada BKU Penerimaan — baris yang TIDAK
 * terklasifikasi ke akun pendapatan (bukan penerimaan jasa) maupun ke kode
 * SILABI. Mewakili kasus seperti pemindahbukuan saldo penerimaan, setor ke
 * kas negara, pengembalian, biaya admin bank, bunga, dan koreksi manual.
 *
 * Dipakai sebagai label kolom "Kode Akun & Jenis Pelayanan" pada baris manual
 * (lihat resources/views/pembukuan/penerimaan/index.blade.php) dan disimpan di
 * kolom buku_kas_umum.jenis_transaksi.
 */
enum JenisTransaksiPenerimaan: string
{
    case PEMINDAHBUKUAN = 'PEMINDAHBUKUAN';
    case SETOR_KAS_NEGARA = 'SETOR_KAS_NEGARA';
    case PENGEMBALIAN = 'PENGEMBALIAN';
    case BIAYA_ADMIN_BANK = 'BIAYA_ADMIN_BANK';
    case BUNGA = 'BUNGA';
    case KOREKSI = 'KOREKSI';
    case LAINNYA = 'LAINNYA';

    public function label(): string
    {
        return match ($this) {
            self::PEMINDAHBUKUAN => 'Pemindahbukuan (PBK)',
            self::SETOR_KAS_NEGARA => 'Setor Kas Negara',
            self::PENGEMBALIAN => 'Pengembalian',
            self::BIAYA_ADMIN_BANK => 'Biaya Admin Bank',
            self::BUNGA => 'Bunga Rekening',
            self::KOREKSI => 'Koreksi',
            self::LAINNYA => 'Lainnya',
        };
    }

    /**
     * Arah kas lazim untuk kategori ini (default form). MASUK untuk Bunga,
     * selain itu KELUAR. Hanya saran tampilan — server tetap menerima keduanya.
     */
    public function arusKasDefault(): string
    {
        return $this === self::BUNGA ? 'DEBIT_MASUK' : 'KREDIT_KELUAR';
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
