<?php

namespace Tests\Unit;

use App\Models\TransaksiPenerimaan;
use App\Services\Pembukuan\PiutangAgingService;
use Carbon\Carbon;
use Tests\TestCase;

/**
 * Uji murni (tanpa DB) penilaian kualitas piutang & penyisihan berjenjang.
 */
class PiutangAgingTest extends TestCase
{
    private function piutang(string $jatuhTempo, float $tagihan = 1_000_000, float $dibayar = 0, string $status = 'UNPAID'): TransaksiPenerimaan
    {
        return new TransaksiPenerimaan([
            'nominal_tagihan' => $tagihan,
            'total_dibayar' => $dibayar,
            'status_pembayaran' => $status,
            'tanggal_jatuh_tempo' => $jatuhTempo,
        ]);
    }

    public function test_kualitas_berjenjang_dari_umur_tunggakan(): void
    {
        $svc = new PiutangAgingService();
        $asOf = Carbon::parse('2026-06-18');

        $kasus = [
            '2026-06-30' => ['LANCAR', 0.0, 0.0],          // belum jatuh tempo
            '2026-05-01' => ['KURANG_LANCAR', 10.0, 100_000.0], // 48 hari
            '2026-02-01' => ['DIRAGUKAN', 50.0, 500_000.0],     // 137 hari
            '2025-10-01' => ['MACET', 100.0, 1_000_000.0],      // 260 hari
        ];

        foreach ($kasus as $jt => [$kualitas, $persen, $penyisihan]) {
            $e = $svc->evaluate($this->piutang($jt), $asOf);
            $this->assertSame($kualitas, $e['kualitas'], "kualitas untuk jt $jt");
            $this->assertSame($persen, $e['persentase'], "persentase untuk jt $jt");
            $this->assertSame($penyisihan, $e['penyisihan'], "penyisihan untuk jt $jt");
        }
    }

    public function test_piutang_lunas_selalu_lancar_tanpa_penyisihan(): void
    {
        $svc = new PiutangAgingService();
        $e = $svc->evaluate($this->piutang('2025-01-01', 1_000_000, 1_000_000, 'PAID'), Carbon::parse('2026-06-18'));

        $this->assertSame('LANCAR', $e['kualitas']);
        $this->assertSame(0.0, $e['penyisihan']);
    }
}
