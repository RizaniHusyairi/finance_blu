<?php

namespace Tests\Unit;

use App\Support\TerminScheme;
use PHPUnit\Framework\TestCase;

/**
 * Skema termin (LUMPSUM/TERMIN) + alokasi uang muka proporsional.
 */
class TerminSchemeTest extends TestCase
{
    public function test_lumpsum_satu_baris_pelunasan_penuh(): void
    {
        $rows = TerminScheme::allocateUangMuka(TerminScheme::lumpsum(100_000_000), 0);

        $this->assertCount(1, $rows);
        $this->assertSame('PELUNASAN', $rows[0]['jenis_termin']);
        $this->assertSame(100.0, $rows[0]['persentase']);
        $this->assertSame(100_000_000.0, $rows[0]['nilai_bruto_termin']);
        $this->assertSame(0.0, $rows[0]['potongan_angsuran_uang_muka']);
    }

    public function test_termin_progress_menghasilkan_pelunasan_otomatis(): void
    {
        $rows = TerminScheme::build(
            [['keterangan' => 'Tahap 1', 'persentase' => 40], ['keterangan' => 'Tahap 2', 'persentase' => 40]],
            null,
            null,
            100_000_000,
        );

        $this->assertCount(3, $rows);
        $this->assertSame(['PROGRESS', 'PROGRESS', 'PELUNASAN'], array_column($rows, 'jenis_termin'));
        $this->assertSame(20.0, $rows[2]['persentase']);
        $this->assertSame(100_000_000.0, array_sum(array_column($rows, 'nilai_bruto_termin')));
    }

    public function test_retensi_menambah_baris_terpisah_dan_pelunasan_menyesuaikan(): void
    {
        $rows = TerminScheme::build(
            [['keterangan' => 'Progress', 'persentase' => 70]],
            5.0,
            'Retensi Pemeliharaan',
            200_000_000,
        );

        $this->assertSame(['PROGRESS', 'PELUNASAN', 'RETENSI'], array_column($rows, 'jenis_termin'));
        $this->assertSame(25.0, $rows[1]['persentase']); // 100 - 70 - 5
        $this->assertSame(5.0, $rows[2]['persentase']);
        $this->assertSame(200_000_000.0, array_sum(array_column($rows, 'nilai_bruto_termin')));
    }

    public function test_uang_muka_dibagi_proporsional_dan_retensi_dikecualikan(): void
    {
        $rows = TerminScheme::build(
            [['keterangan' => 'Progress', 'persentase' => 60]],
            10.0,
            null,
            100_000_000,
        );
        // Bruto: PROGRESS 60jt, PELUNASAN 30jt, RETENSI 10jt.
        $rows = TerminScheme::allocateUangMuka($rows, 18_000_000);

        $progress = $rows[0];
        $pelunasan = $rows[1];
        $retensi = $rows[2];

        // Eligible = PROGRESS+PELUNASAN (total 90jt); UM 18jt → 20% tiap eligible.
        $this->assertEqualsWithDelta(12_000_000, $progress['potongan_angsuran_uang_muka'], 1);
        $this->assertEqualsWithDelta(6_000_000, $pelunasan['potongan_angsuran_uang_muka'], 1);
        $this->assertSame(0.0, $retensi['potongan_angsuran_uang_muka']);
        $this->assertSame(10_000_000.0, $retensi['nilai_retensi']);

        // Jumlah potongan = nilai uang muka.
        $this->assertEqualsWithDelta(
            18_000_000,
            array_sum(array_column($rows, 'potongan_angsuran_uang_muka')),
            0.01,
        );
    }

    public function test_total_progress_melebihi_100_ditolak(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        TerminScheme::build(
            [['keterangan' => 'A', 'persentase' => 70], ['keterangan' => 'B', 'persentase' => 40]],
            null,
            null,
            100_000_000,
        );
    }
}
