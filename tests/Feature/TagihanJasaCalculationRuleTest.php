<?php

namespace Tests\Feature;

use App\Models\LayananJasa;
use App\Services\TagihanJasaCalculationService;
use Database\Seeders\MasterLayananJasaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Memastikan aturan perhitungan tagihan jasa yang dipilih backend cocok dengan
 * satuan pada data seed layanan (database/data/layanan-jasa-seed.json).
 * Frontend membaca calculation_rule hasil backend, jadi tes ini juga menjaga
 * kesamaan aturan di form tagihan.
 */
class TagihanJasaCalculationRuleTest extends TestCase
{
    use RefreshDatabase;

    private TagihanJasaCalculationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(MasterLayananJasaSeeder::class);
        $this->service = app(TagihanJasaCalculationService::class);
    }

    private function layanan(string $kode): LayananJasa
    {
        return LayananJasa::where('kode_layanan', $kode)->firstOrFail();
    }

    public static function ruleProvider(): array
    {
        return [
            'pendaratan s.d 40.000kg' => ['APT-01-01-01-BOBOT-PESAWAT-S-D-40-000KG', 'WEIGHT_PER_1000_ROUND_UP'],
            'pendaratan 40-100 ribu kg' => ['APT-01-01-02-BOBOT-PESAWAT-DIATAS-40-000KG-S-D-100-000KG', 'WEIGHT_PER_1000_ROUND_UP'],
            'pendaratan diatas 100.000kg' => ['APT-01-01-03-BOBOT-PESAWAT-DIATAS-100-000KG', 'WEIGHT_PER_1000_ROUND_UP'],
            'pendaratan internasional s.d 40.000kg' => ['APT-04-01-01-BOBOT-PESAWAT-S-D-40-000KG', 'WEIGHT_PER_1000_ROUND_UP'],
            'pendaratan internasional 40-100 ribu kg' => ['APT-04-01-02-BOBOT-PESAWAT-DIATAS-40-000KG-S-D-100-000KG', 'WEIGHT_PER_1000_ROUND_UP'],
            'pendaratan internasional diatas 100.000kg' => ['APT-04-01-03-BOBOT-PESAWAT-DIATAS-100-000KG', 'WEIGHT_PER_1000_ROUND_UP'],
            'penempatan pesawat' => ['APT-01-02-JASA-PENEMPATAN-PESAWAT-UDARA', 'PER_JAM_PER_TON'],
            'penempatan pesawat internasional' => ['APT-04-02-JASA-PENEMPATAN-PESAWAT-UDARA', 'PER_JAM_PER_TON'],
            'penyimpanan pesawat' => ['APT-01-03-JASA-PENYIMPANAN-PESAWAT-UDARA', 'WEIGHT_PER_1000_PER_12_HOURS_ROUND_UP'],
            'penyimpanan pesawat internasional' => ['APT-04-03-JASA-PENYIMPANAN-PESAWAT-UDARA', 'WEIGHT_PER_1000_PER_12_HOURS_ROUND_UP'],
            'tiang pancang reklame' => ['APT-02-01-PEMASANGAN-TIANG-PANCANG-REKLAME', 'PER_M2_REKLAME_PER_TAHUN'],
            'kursi' => ['APT-02-12-04-KURSI', 'PER_UNIT_PER_BULAN_PER_SISI_PANDANG'],
            'garbarata/bis di apron (sewa ruang)' => ['APT-02-12-10-GARBARATA-BIS-LAYANAN-PENUMPANG-DI-APRON', 'PER_M2_PER_BULAN'],
        ];
    }

    #[DataProvider('ruleProvider')]
    public function test_layanan_seed_memetakan_ke_rule_yang_benar(string $kode, string $expectedRule): void
    {
        $this->assertSame($expectedRule, $this->service->ruleFor($this->layanan($kode)));
    }

    public function test_tidak_ada_layanan_berbobot_atau_reklame_yang_jatuh_ke_volume_manual(): void
    {
        $missing = LayananJasa::where('is_leaf', true)
            ->get()
            ->filter(fn (LayananJasa $l) => preg_match('/kg|ton|reklame|rekalme|sisi pand/i', (string) $l->satuan))
            ->filter(fn (LayananJasa $l) => $this->service->ruleFor($l) === null)
            ->map(fn (LayananJasa $l) => $l->kode_layanan . ' [' . $l->satuan . ']')
            ->values()
            ->all();

        $this->assertSame([], $missing);
    }

    public function test_penyimpanan_pesawat_menghitung_kg_dan_rentang_12_jam(): void
    {
        $layanan = $this->layanan('APT-01-03-JASA-PENYIMPANAN-PESAWAT-UDARA');

        $payload = $this->service->buildPayload([
            'mode' => 'TARIF',
            'calculation_payload' => ['inputs' => ['berat_kg' => 40500, 'durasi_jam' => 25]],
        ], $layanan);

        // ceil(40500 / 1000) x ceil(25 / 12) = 41 x 3
        $this->assertSame('WEIGHT_PER_1000_PER_12_HOURS_ROUND_UP', $payload['rule']);
        $this->assertSame(123.0, (float) $payload['billable_qty']);
    }

    public function test_bracket_bobot_pendaratan_dibaca_dari_nama_seed(): void
    {
        $kecil = $this->service->parseLandingWeightRange($this->layanan('APT-01-01-01-BOBOT-PESAWAT-S-D-40-000KG'));
        $sedang = $this->service->parseLandingWeightRange($this->layanan('APT-01-01-02-BOBOT-PESAWAT-DIATAS-40-000KG-S-D-100-000KG'));
        $besar = $this->service->parseLandingWeightRange($this->layanan('APT-01-01-03-BOBOT-PESAWAT-DIATAS-100-000KG'));

        $this->assertSame([0, 40000], [$kecil['min'], $kecil['max']]);
        $this->assertSame([40000, 100000], [$sedang['min'], $sedang['max']]);
        $this->assertSame([100000, null], [$besar['min'], $besar['max']]);
        $this->assertNull($this->service->parseLandingWeightRange($this->layanan('APT-01-03-JASA-PENYIMPANAN-PESAWAT-UDARA')));
    }

    public function test_bobot_di_luar_bracket_pendaratan_ditolak_server(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->service->buildPayload([
            'mode' => 'TARIF',
            'calculation_payload' => ['inputs' => ['berat_kg' => 50000]],
        ], $this->layanan('APT-01-01-01-BOBOT-PESAWAT-S-D-40-000KG'));
    }

    public function test_hanya_pemakaian_garbarata_per_2_jam_yang_dianggap_garbarata(): void
    {
        $this->assertTrue($this->service->isGarbarataService($this->layanan('APT-01-07-JASA-PEMAKAIAN-GARBARATA-AVIOBRIDGE')));
        $this->assertTrue($this->service->isGarbarataService($this->layanan('APT-04-07-JASA-PEMAKAIAN-GARBARATA-AVIOBRIDGE')));
        $this->assertFalse($this->service->isGarbarataService($this->layanan('APT-02-12-10-GARBARATA-BIS-LAYANAN-PENUMPANG-DI-APRON')));
    }

    public function test_rincian_garbarata_tetap_dihitung_per_rentang_2_jam(): void
    {
        $payload = $this->service->buildPayload([
            'mode' => 'TARIF',
            'harga_satuan' => 100000,
            'calculation_payload' => [
                'rule' => 'GARBARATA_DETAIL',
                'rows' => [['docking' => '10:00', 'undocking' => '12:30']],
            ],
        ], $this->layanan('APT-01-07-JASA-PEMAKAIAN-GARBARATA-AVIOBRIDGE'));

        $this->assertSame('GARBARATA_DETAIL', $payload['rule']);
        $this->assertSame(2, (int) $payload['billable_qty']);
    }

    public function test_normalisasi_tetap_menoleransi_typo_data_lama(): void
    {
        $this->assertSame('per m2 reklame per tahun', $this->service->normalizeUnit('per m² rekalme per tahun'));
        $this->assertSame('per unit per bulan per sisi pandang', $this->service->normalizeUnit('per unit per bulan per sisi pandag'));
        $this->assertSame('tiap 1000 kg atau bagiannya per 12 jam', $this->service->normalizeUnit('tiap 1.000kg atau bagiannya, per 12 jam'));
    }

    public function test_meta_perhitungan_ditempel_untuk_form_tagihan(): void
    {
        $layanans = LayananJasa::whereIn('kode_layanan', [
            'APT-01-01-02-BOBOT-PESAWAT-DIATAS-40-000KG-S-D-100-000KG',
            'APT-01-07-JASA-PEMAKAIAN-GARBARATA-AVIOBRIDGE',
        ])->orderBy('kode_layanan')->get();

        $this->service->attachCalculationMeta($layanans);
        [$pendaratan, $garbarata] = $layanans->map->toArray()->all();

        $this->assertSame('WEIGHT_PER_1000_ROUND_UP', $pendaratan['calculation_rule']);
        $this->assertSame(['min' => 40000, 'max' => 100000, 'minExclusive' => true, 'maxInclusive' => true], array_intersect_key(
            $pendaratan['landing_weight_range'],
            array_flip(['min', 'max', 'minExclusive', 'maxInclusive'])
        ));
        $this->assertFalse($pendaratan['is_garbarata']);
        $this->assertTrue($garbarata['is_garbarata']);
    }
}
