<?php

namespace Tests\Feature;

use App\Models\DetailDipa;
use App\Models\MasterCoa;
use App\Models\MasterDipa;
use App\Models\RiwayatRevisiDipa;
use App\Models\User;
use App\Support\DipaBudgetOptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DipaPokDetailTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private MasterDipa $dipa;

    private RiwayatRevisiDipa $revision;

    private MasterCoa $coa;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('Super Admin', 'web');
        $this->admin = User::factory()->create();
        $this->admin->assignRole('Super Admin');

        $this->dipa = MasterDipa::create([
            'nomor_dipa' => 'DIPA-POK/2026',
            'tahun_anggaran' => 2026,
            'tanggal_disahkan' => '2025-12-15',
            'revisi_aktif_ke' => 0,
            'status_aktif' => true,
        ]);

        $this->revision = RiwayatRevisiDipa::create([
            'master_dipa_id' => $this->dipa->id,
            'nomor_revisi' => 0,
            'tanggal_revisi' => '2025-12-15',
            'total_pagu' => 1693000000,
            'is_active' => true,
        ]);

        $this->coa = MasterCoa::create([
            'kd_program' => 'GA',
            'kd_giat' => '4646',
            'kd_output' => 'CBE',
            'kd_suboutput' => '002',
            'kd_komponen' => '052',
            'kd_subkomponen' => 'B',
            'kd_akun' => '525112',
            'kd_item' => '00001',
            'kode_mak_lengkap' => 'GA.4646.CBE.002.052.B.525112.00001',
            'nama_akun' => 'Latihan Rutin PKP-PK',
            'jenis_akun' => '525',
            'sumber_dana' => 'BLU',
            'status_aktif' => true,
        ]);
    }

    public function test_store_item_menghitung_pagu_dari_volume_kali_harga_satuan(): void
    {
        $response = $this->actingAs($this->admin)->post(route('dipas.items.store', $this->dipa), [
            'coa_id' => $this->coa->id,
            'volume' => 12,
            'satuan' => 'bln',
            'harga_satuan' => 1500000,
            'status_aktif' => 1,
        ]);

        $response->assertRedirect(route('dipas.show', $this->dipa));

        $this->assertDatabaseHas('dipa_revision_items', [
            'dipa_revision_id' => $this->revision->id,
            'coa_id' => $this->coa->id,
            'nilai_pagu' => 18000000.00,
            'volume' => 12.00,
            'satuan' => 'bln',
            'harga_satuan' => 1500000.00,
            'blokir' => false,
        ]);
    }

    public function test_store_item_tanpa_rincian_tetap_memakai_nilai_pagu_manual(): void
    {
        $this->actingAs($this->admin)->post(route('dipas.items.store', $this->dipa), [
            'coa_id' => $this->coa->id,
            'nilai_pagu' => 900000000,
            'status_aktif' => 1,
        ])->assertRedirect(route('dipas.show', $this->dipa));

        $this->assertDatabaseHas('dipa_revision_items', [
            'coa_id' => $this->coa->id,
            'nilai_pagu' => 900000000.00,
            'volume' => null,
            'harga_satuan' => null,
        ]);
    }

    public function test_item_blokir_tidak_muncul_pada_pilihan_anggaran_tagihan(): void
    {
        $normal = DetailDipa::create([
            'dipa_revision_id' => $this->revision->id,
            'coa_id' => $this->coa->id,
            'nilai_pagu' => 100000,
            'status_aktif' => true,
            'blokir' => false,
        ]);

        $coaKedua = MasterCoa::create([
            'kd_akun' => '525113',
            'kd_item' => '00001',
            'kode_mak_lengkap' => 'GA.4647.CBE.001.054.A.525113.00001',
            'nama_akun' => 'Jasa Konsultansi',
            'sumber_dana' => 'BLU',
            'status_aktif' => true,
        ]);

        $terblokir = DetailDipa::create([
            'dipa_revision_id' => $this->revision->id,
            'coa_id' => $coaKedua->id,
            'nilai_pagu' => 200000,
            'status_aktif' => true,
            'blokir' => true,
        ]);

        $optionIds = DipaBudgetOptionService::groupedOptions()
            ->flatMap(fn (array $group) => collect($group['items'])->pluck('id'));

        $this->assertTrue($optionIds->contains($normal->id));
        $this->assertFalse($optionIds->contains($terblokir->id));

        $this->assertSame($normal->id, DipaBudgetOptionService::resolveActiveItem($normal->id)->id);

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        DipaBudgetOptionService::resolveActiveItem($terblokir->id);
    }

    public function test_salin_item_ke_revisi_baru_membawa_rincian_pok(): void
    {
        DetailDipa::create([
            'dipa_revision_id' => $this->revision->id,
            'coa_id' => $this->coa->id,
            'nilai_pagu' => 18000000,
            'volume' => 12,
            'satuan' => 'bln',
            'harga_satuan' => 1500000,
            'status_aktif' => true,
            'blokir' => true,
        ]);

        $this->actingAs($this->admin)->post(route('dipas.revisions.store', $this->dipa), [
            'nomor_revisi' => 1,
            'tanggal_revisi' => '2026-02-01',
            'total_pagu' => 18000000,
            'salin_item_anggaran' => 1,
        ])->assertRedirect(route('dipas.show', $this->dipa));

        $newRevision = RiwayatRevisiDipa::where('master_dipa_id', $this->dipa->id)
            ->where('nomor_revisi', 1)
            ->firstOrFail();

        $this->assertDatabaseHas('dipa_revision_items', [
            'dipa_revision_id' => $newRevision->id,
            'volume' => 12.00,
            'satuan' => 'bln',
            'harga_satuan' => 1500000.00,
            'blokir' => true,
        ]);
    }

    public function test_coa_store_mengisi_sumber_dana_otomatis_dari_kd_akun(): void
    {
        $this->actingAs($this->admin)->post(route('coas.store'), [
            'kd_program' => 'WA',
            'kd_giat' => '4611',
            'kd_output' => 'EBA',
            'kd_suboutput' => '994',
            'kd_komponen' => '001',
            'kd_subkomponen' => 'A',
            'kd_akun' => '511111',
            'kd_item' => '00001',
            'nama_akun' => 'Belanja Gaji Pokok PNS',
            'status_aktif' => 1,
        ]);

        $this->assertDatabaseHas('master_coas', [
            'kd_akun' => '511111',
            'sumber_dana' => 'RM',
        ]);

        $this->actingAs($this->admin)->post(route('coas.store'), [
            'kd_akun' => '525115',
            'nama_akun' => 'Belanja Perjalanan',
            'sumber_dana' => 'BLU',
            'status_aktif' => 1,
        ]);

        $this->assertDatabaseHas('master_coas', [
            'kd_akun' => '525115',
            'sumber_dana' => 'BLU',
        ]);
    }
}
