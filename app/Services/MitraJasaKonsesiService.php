<?php

namespace App\Services;

use App\Models\LayananJasa;
use App\Models\MitraJasaKonsesi;

class MitraJasaKonsesiService
{
    public const DEFAULT_PERSENTASE_KONSESI = 5;

    public function hitungKonsesiDefault(float $totalOmzet): array
    {
        return $this->hitungKonsesiPersentase($totalOmzet, self::DEFAULT_PERSENTASE_KONSESI);
    }

    public function hitungKonsesiLayanan(LayananJasa $layanan, float $totalOmzet): array
    {
        $persentase = (float) ($layanan->persentase_konsesi ?? self::DEFAULT_PERSENTASE_KONSESI);

        return $this->hitungKonsesiPersentase($totalOmzet, $persentase);
    }

    public function hitungKonsesiPersentase(float $totalOmzet, float $persentase): array
    {
        $nilaiKonsesi = $totalOmzet * $persentase / 100;

        return [
            'total_omzet' => $totalOmzet,
            'persentase_konsesi' => $persentase,
            'nilai_konsesi' => $nilaiKonsesi,
            'nilai_tetap' => 0,
            'nilai_minimum_guarantee' => null,
            'nilai_tagihan' => $nilaiKonsesi,
            'rumus_perhitungan' => 'Omzet x ' . $persentase . '%',
        ];
    }

    public function hitungNilaiKonsesi(MitraJasaKonsesi $konsesi, float $totalOmzet): array
    {
        $persentase = (float) ($konsesi->persentase_konsesi ?? 0);
        $nilaiTetap = (float) ($konsesi->nilai_tetap ?? 0);
        $minimumGuarantee = (float) ($konsesi->nilai_minimum_guarantee ?? 0);
        $nilaiKonsesi = 0;
        $nilaiTagihan = 0;
        $rumus = '-';

        if ($konsesi->jenis_konsesi === 'persen_omzet') {
            $nilaiKonsesi = $totalOmzet * $persentase / 100;
            $nilaiTagihan = $nilaiKonsesi;
            $rumus = "Omzet x {$persentase}%";
        } elseif ($konsesi->jenis_konsesi === 'nilai_tetap') {
            $nilaiKonsesi = $nilaiTetap;
            $nilaiTagihan = $nilaiTetap;
            $rumus = 'Nilai tetap';
        } elseif ($konsesi->jenis_konsesi === 'minimum_guarantee') {
            $nilaiKonsesi = $totalOmzet * $persentase / 100;
            $nilaiTagihan = max($nilaiKonsesi, $minimumGuarantee);
            $rumus = "Maksimum antara omzet x {$persentase}% dan minimum guarantee";
        } elseif ($konsesi->jenis_konsesi === 'kombinasi') {
            $nilaiKonsesi = $totalOmzet * $persentase / 100;
            $nilaiTagihan = $nilaiKonsesi + $nilaiTetap;
            if ($minimumGuarantee > 0) {
                $nilaiTagihan = max($nilaiTagihan, $minimumGuarantee);
            }
            $rumus = "Omzet x {$persentase}% + nilai tetap";
        }

        return [
            'total_omzet' => $totalOmzet,
            'persentase_konsesi' => $persentase,
            'nilai_konsesi' => $nilaiKonsesi,
            'nilai_tetap' => $nilaiTetap,
            'nilai_minimum_guarantee' => $minimumGuarantee > 0 ? $minimumGuarantee : null,
            'nilai_tagihan' => $nilaiTagihan,
            'rumus_perhitungan' => $rumus,
        ];
    }
}
