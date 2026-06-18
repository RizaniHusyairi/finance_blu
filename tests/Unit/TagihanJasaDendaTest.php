<?php

namespace Tests\Unit;

use App\Models\TagihanJasa;
use Carbon\Carbon;
use Tests\TestCase;

/**
 * Uji murni (tanpa DB) aturan denda & kualitas piutang tagihan jasa:
 *  - Denda 2% per periode 30 hari (dibulatkan ke atas), datar dalam periode
 *    lalu naik bertahap, akumulasi tanpa pembekuan.
 *  - Kualitas piutang berjenjang dari umur tunggakan (macet > 180 hari).
 *  - PJP2U khusus pada jatuh tempo (7 hari); rumus denda sama untuk semua.
 */
class TagihanJasaDendaTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-06-18'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function tagihan(int $hariTelat, int $masaDenda = 30, float $total = 1_000_000, string $statusBayar = 'belum_dibayar'): TagihanJasa
    {
        return new TagihanJasa([
            'total_tagihan' => $total,
            'masa_denda_hari' => $masaDenda,
            'status' => 'PUBLISHED',
            'status_pembayaran' => $statusBayar,
            'jumlah_dibayar' => 0,
            // hariTelat negatif = jatuh tempo masih di masa depan (belum telat).
            'tanggal_jatuh_tempo' => Carbon::today()->subDays($hariTelat)->toDateString(),
        ]);
    }

    public function test_denda_2_persen_per_periode_30_hari_datar_lalu_naik(): void
    {
        // [hari telat => [jumlah periode, nominal denda]]
        $kasus = [
            -3 => [0, 0.0],        // belum jatuh tempo
            1  => [1, 20_000.0],   // lewat tempo -> langsung 2%
            5  => [1, 20_000.0],   // tetap 2% (tidak nambah harian)
            30 => [1, 20_000.0],   // masih periode 1
            31 => [2, 40_000.0],   // genap 30 hari -> +2% = 4%
            60 => [2, 40_000.0],   // masih periode 2
            61 => [3, 60_000.0],   // periode 3 -> 6%
        ];

        foreach ($kasus as $telat => [$periode, $denda]) {
            $t = $this->tagihan($telat);
            $this->assertSame($periode, $t->jumlah_periode_denda, "periode utk telat $telat hari");
            $this->assertSame($denda, $t->nominal_denda_keterlambatan, "denda utk telat $telat hari");
        }
    }

    public function test_denda_akumulasi_tanpa_pembekuan(): void
    {
        $t = $this->tagihan(188); // ceil(188/30) = 7 periode

        $this->assertSame(7, $t->jumlah_periode_denda);
        $this->assertSame(140_000.0, $t->nominal_denda_keterlambatan); // 2% x 7
        $this->assertSame(1_140_000.0, $t->total_dengan_denda);
    }

    public function test_periode_default_30_saat_masa_denda_nol(): void
    {
        // Tagihan non-PJP2U (masa_denda_hari = 0) tetap memakai periode 30 hari.
        $t = $this->tagihan(40, masaDenda: 0);

        $this->assertSame(30, $t->periode_denda_hari);
        $this->assertSame(2, $t->jumlah_periode_denda); // ceil(40/30)
        $this->assertSame(40_000.0, $t->nominal_denda_keterlambatan);
    }

    public function test_kualitas_piutang_dan_status_macet(): void
    {
        $kasus = [
            -3  => ['LANCAR', 'MENDEKATI_JATUH_TEMPO'],
            45  => ['KURANG_LANCAR', 'LEWAT_JATUH_TEMPO'],
            120 => ['DIRAGUKAN', 'LEWAT_JATUH_TEMPO'],
            200 => ['MACET', 'MACET'],
        ];

        foreach ($kasus as $telat => [$kualitas, $status]) {
            $t = $this->tagihan($telat);
            $this->assertSame($kualitas, $t->kualitas_piutang, "kualitas utk telat $telat hari");
            $this->assertSame($status, $t->status_jatuh_tempo, "status utk telat $telat hari");
        }

        $this->assertTrue($this->tagihan(200)->is_macet);
        $this->assertFalse($this->tagihan(45)->is_macet);
    }

    public function test_tagihan_lunas_tanpa_denda_dan_lancar(): void
    {
        $t = $this->tagihan(100, statusBayar: 'lunas');

        $this->assertSame(0, $t->jumlah_periode_denda);
        $this->assertSame(0.0, $t->nominal_denda_keterlambatan);
        $this->assertSame('LANCAR', $t->kualitas_piutang);
        $this->assertFalse($t->is_macet);
    }
}
