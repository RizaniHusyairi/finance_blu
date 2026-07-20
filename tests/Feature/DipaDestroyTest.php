<?php

namespace Tests\Feature;

use App\Models\DetailDipa;
use App\Models\MasterCoa;
use App\Models\MasterDipa;
use App\Models\RiwayatRevisiDipa;
use App\Models\Tagihan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Hapus terjaga DIPA: hanya DIPA yang benar-benar kosong (tanpa item anggaran
 * dan tanpa rujukan tagihan/kontrak) yang boleh dihapus permanen; selainnya
 * ditolak dengan arahan memakai status Nonaktif.
 */
class DipaDestroyTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('Super Admin', 'web');
        $this->admin = User::factory()->create();
        $this->admin->assignRole('Super Admin');
    }

    private function buatDipa(): MasterDipa
    {
        $dipa = MasterDipa::create([
            'nomor_dipa' => 'DIPA-HAPUS-UJI/' . uniqid(),
            'tahun_anggaran' => 2026,
            'tanggal_disahkan' => '2026-01-03',
            'revisi_aktif_ke' => 0,
            'status_aktif' => true,
        ]);
        RiwayatRevisiDipa::create([
            'master_dipa_id' => $dipa->id,
            'nomor_revisi' => 0,
            'tanggal_revisi' => '2026-01-03',
            'total_pagu' => 1000000,
            'is_active' => true,
        ]);

        return $dipa;
    }

    public function test_dipa_kosong_dapat_dihapus_beserta_revisinya(): void
    {
        $dipa = $this->buatDipa();

        $this->actingAs($this->admin)
            ->delete(route('dipas.destroy', $dipa))
            ->assertRedirect(route('dipas.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('master_dipas', ['id' => $dipa->id]);
        $this->assertDatabaseMissing('dipa_revisions', ['master_dipa_id' => $dipa->id]);
    }

    public function test_dipa_dengan_item_anggaran_ditolak(): void
    {
        $dipa = $this->buatDipa();
        $coa = MasterCoa::create(['kd_akun' => '525112', 'nama_akun' => 'Belanja Uji', 'status_aktif' => true]);
        DetailDipa::create([
            'dipa_revision_id' => $dipa->revisions()->first()->id,
            'coa_id' => $coa->id,
            'nilai_pagu' => 1000000,
            'status_aktif' => true,
        ]);

        $this->actingAs($this->admin)
            ->delete(route('dipas.destroy', $dipa))
            ->assertRedirect(route('dipas.show', $dipa))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('master_dipas', ['id' => $dipa->id]);
    }

    public function test_dipa_yang_dirujuk_tagihan_ditolak(): void
    {
        $dipa = $this->buatDipa();
        Tagihan::create([
            'nomor_tagihan' => 'TAGIHAN-UJI-001',
            'tipe_tagihan' => 'KONTRAK',
            'master_dipa_id' => $dipa->id,
            'deskripsi' => 'Tagihan uji rujukan DIPA',
            'total_bruto' => 100000,
            'total_potongan' => 0,
            'total_netto' => 100000,
            'status' => 'DRAFT',
            'created_by' => $this->admin->id,
        ]);

        $this->actingAs($this->admin)
            ->delete(route('dipas.destroy', $dipa))
            ->assertRedirect(route('dipas.show', $dipa))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('master_dipas', ['id' => $dipa->id]);
    }
}
