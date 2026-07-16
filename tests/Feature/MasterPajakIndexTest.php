<?php

namespace Tests\Feature;

use App\Models\MasterTarifPajak;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Halaman list Master Pajak — render halaman (hero, tile statistik, tabel),
 * badge masa berlaku per kondisi, filter, dan respons AJAX partial.
 */
class MasterPajakIndexTest extends TestCase
{
    use RefreshDatabase;

    private User $operator;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('Operator BLU', 'web');
        $this->operator = User::factory()->create();
        $this->operator->assignRole('Operator BLU');
    }

    private function seedTarif(): void
    {
        MasterTarifPajak::create(['kode_pajak' => 'PPN-BERLAKU', 'jenis_pajak' => 'PPN', 'persentase' => 11,
            'berlaku_mulai' => now()->subYear(), 'berlaku_sampai' => now()->addYear(), 'status_aktif' => true]);
        MasterTarifPajak::create(['kode_pajak' => 'PPH21-SEGERA', 'jenis_pajak' => 'PPh 21', 'persentase' => 5,
            'berlaku_mulai' => now()->subYear(), 'berlaku_sampai' => now()->addDays(10), 'status_aktif' => true]);
        MasterTarifPajak::create(['kode_pajak' => 'PPH22-EXPIRED', 'jenis_pajak' => 'PPh 22', 'persentase' => 1.5,
            'berlaku_mulai' => now()->subYears(2), 'berlaku_sampai' => now()->subDay(), 'status_aktif' => true]);
        MasterTarifPajak::create(['kode_pajak' => 'PPH23-BELUM', 'jenis_pajak' => 'PPh 23', 'persentase' => 2,
            'berlaku_mulai' => now()->addMonth(), 'berlaku_sampai' => null, 'status_aktif' => true]);
        MasterTarifPajak::create(['kode_pajak' => 'PPNBM-OFF', 'jenis_pajak' => 'PPnBM', 'persentase' => 20,
            'berlaku_mulai' => null, 'berlaku_sampai' => null, 'status_aktif' => false]);
    }

    public function test_halaman_index_menampilkan_hero_statistik_dan_tabel(): void
    {
        $this->seedTarif();

        $response = $this->actingAs($this->operator)->get(route('master-pajak.index'));

        $response->assertOk();
        $response->assertSee('Master Pajak');
        $response->assertSee('Tambah Pajak');
        $response->assertSeeInOrder(['Total Tarif', 'Tarif Aktif', 'Tarif Nonaktif', 'Berlaku Saat Ini']);
        $response->assertSee('PPN-BERLAKU');
        $response->assertSee('PPNBM-OFF');
    }

    public function test_badge_masa_berlaku_sesuai_kondisi_tiap_tarif(): void
    {
        $this->seedTarif();

        $response = $this->actingAs($this->operator)->get(route('master-pajak.index'));

        $response->assertOk();
        $response->assertSee('Berlaku');
        $response->assertSee('hari lagi');       // PPH21-SEGERA (≤30 hari)
        $response->assertSee('Expired');          // PPH22-EXPIRED
        $response->assertSee('Belum berlaku');    // PPH23-BELUM
        $response->assertSee('Nonaktif');         // PPNBM-OFF
    }

    public function test_filter_status_nonaktif_hanya_menampilkan_tarif_nonaktif(): void
    {
        $this->seedTarif();

        $response = $this->actingAs($this->operator)
            ->get(route('master-pajak.index', ['status_aktif' => 'nonaktif']));

        $response->assertOk();
        $response->assertSee('PPNBM-OFF');
        $response->assertDontSee('PPN-BERLAKU');
    }

    public function test_respons_ajax_partial_hanya_berisi_tabel(): void
    {
        $this->seedTarif();

        $response = $this->actingAs($this->operator)->get(
            route('master-pajak.index', ['partial' => 1, 'search' => 'PPN-BERLAKU']),
            ['X-Requested-With' => 'XMLHttpRequest'],
        );

        $response->assertOk();
        $response->assertSee('PPN-BERLAKU');
        $response->assertDontSee('tp-hero');      // hero tidak ikut pada partial
        $response->assertDontSee('PPH22-EXPIRED'); // tersaring oleh pencarian
    }
}
