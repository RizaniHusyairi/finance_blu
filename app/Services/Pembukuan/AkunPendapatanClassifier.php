<?php

namespace App\Services\Pembukuan;

use App\Models\AkunPendapatan;
use Illuminate\Support\Collection;

/**
 * Klasifikasi otomatis deskripsi baris rekening koran → akun pendapatan.
 *
 * Memetakan narasi bank mentah (mis. "Konsesi januari…", "SKN/CITILINK…PSC",
 * "Tagihan Listrik…", "Sewa ruang…") ke kombinasi (kode_akun, kode_jenis) pada
 * master akun_pendapatan. Berbasis kata kunci berurutan (spesifik → umum);
 * mengembalikan null bila ragu agar bisa dikoreksi manual.
 *
 * Pola diturunkan dari uraian transaksi pada File 2 (BKU Penerimaan & rekening
 * koran). Memperluas pendekatan [[App\Enums\KategoriMutasiBank]]::classify.
 */
class AkunPendapatanClassifier
{
    /** Aturan berurutan: regex → [kode_akun, kode_jenis]. Yang lebih spesifik dulu. */
    private const RULES = [
        '/konsesi/'                          => ['424312', '931'],
        '/sewa\s*ruang|penggunaan\s*ruang/'  => ['424923', '912'],
        '/sewa\s*(lahan|tanah)|penggunaan\s*lahan/' => ['424921', '911'],
        '/sewa\s*gedung|gedung\s*\/\s*bangunan/' => ['424922', '932'],
        '/sewa\s*(peralatan|mesin|kendaraan)/' => ['424924', '919'],
        '/hanggar/'                          => ['424923', '930'],
        // Hanya "penempatan/mesin ATM" (sewa), BUKAN kata "ATM" sebagai kanal transfer bank.
        '/penempatan\s*mesin\s*atm|mesin\s*atm|sewa\s*atm/' => ['424923', '913'],
        '/reklame/'                          => ['424924', '935'],
        '/garbarata/'                        => ['424115', '907'],
        '/\bpjp2u\b|pjp\s*2u|psc\b/'         => ['424115', '905'],
        '/\bjkp2u\b/'                        => ['424115', '906'],
        '/pendaratan/'                       => ['424115', '901'],
        '/penempatan\s*pesawat/'             => ['424115', '902'],
        '/penyimpanan\s*pesawat/'            => ['424115', '903'],
        '/pemeriksaan\s*kargo|\bkargo\b|\bpos\b/' => ['424919', '926'],
        '/pas\s*kendaraan/'                  => ['424919', '937'],
        '/pas\s*tim/'                        => ['424919', '923'],
        '/\bpas\b|pas\s*orang|pas\s*bandara|pas\s*id/' => ['424919', '922'],
        '/telekomunikasi/'                   => ['424919', '924'],
        '/parkir/'                           => ['424919', '929'],
        '/tagihan\s*listrik|listrik/'        => ['424919', '921'],
        // "air" dengan batas non-huruf (cocok "BBSRI_AIR", "Tagihan Air"; bukan "airport"/"fair").
        '/tagihan\s*air|(?<![a-z])air(?![a-z])/' => ['424919', '925'],
        '/bunga|jasa\s*giro|interest/'       => ['424919', '933'],
        '/pengembalian/'                     => ['424919', '934'],
        '/denda/'                            => ['424919', '939'],
        // Fallback umum: "sewa" tanpa kualifikasi → Sewa Ruangan (paling umum).
        '/\bsewa\b/'                         => ['424923', '912'],
    ];

    private ?Collection $cache = null;

    public function classify(?string $deskripsi): ?AkunPendapatan
    {
        $desc = mb_strtolower(trim((string) $deskripsi));

        if ($desc === '') {
            return null;
        }

        foreach (self::RULES as $pattern => [$akun, $jenis]) {
            if (preg_match($pattern, $desc)) {
                return $this->find($akun, $jenis);
            }
        }

        return null;
    }

    public function classifyId(?string $deskripsi): ?int
    {
        return $this->classify($deskripsi)?->id;
    }

    private function find(string $kodeAkun, string $kodeJenis): ?AkunPendapatan
    {
        $this->cache ??= AkunPendapatan::query()->get();

        return $this->cache->first(
            fn (AkunPendapatan $a) => $a->kode_akun === $kodeAkun && $a->kode_jenis === $kodeJenis
        );
    }
}
