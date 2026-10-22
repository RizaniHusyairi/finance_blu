<?php

namespace Tests\Feature;

use App\Models\LayananJasa;
use App\Models\MasterPegawai;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Edit dan Atur Layanan Admin Jasa digabung dalam satu form: profil, akun login, dan
 * layanan yang dikelola disimpan bersama. Layanan juga bisa dipilih saat tambah admin.
 */
class AdminJasaKelolaLayananTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdminJasa;
    private LayananJasa $pendaratan;
    private LayananJasa $garbarata;

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['Super Admin Jasa', 'Admin Jasa', 'Koordinator Jasa'] as $role) {
            Role::findOrCreate($role, 'web');
        }
        $this->superAdminJasa = User::factory()->create();
        $this->superAdminJasa->assignRole('Super Admin Jasa');

        $mk = fn ($a) => LayananJasa::create($a + ['is_active' => true, 'tipe_layanan' => 'PNBP']);
        $root = $mk(['nama_layanan' => 'A. Tarif Jasa Kebandarudaraan (Layanan Domestik)', 'level' => 1, 'is_leaf' => false]);
        $this->pendaratan = $mk(['parent_id' => $root->id, 'nama_layanan' => 'Jasa Pendaratan Pesawat Udara', 'kode_layanan' => 'APT-01-01', 'level' => 2, 'is_leaf' => true]);
        $this->garbarata = $mk(['parent_id' => $root->id, 'nama_layanan' => 'Jasa Pemakaian Garbarata (Aviobridge)', 'kode_layanan' => 'APT-01-07', 'level' => 2, 'is_leaf' => true]);
    }

    private function idLayananAktif(User $admin): array
    {
        return $admin->fresh()->layananJasaDikelola()->wherePivot('status_aktif', true)->pluck('layanan_jasas.id')->sort()->values()->all();
    }

    public function test_tambah_admin_sekaligus_memilih_layanan(): void
    {
        $this->actingAs($this->superAdminJasa)->get(route('jasa.admin.create'))->assertOk()
            ->assertSee('id="layanan-admin"', false)
            ->assertSee('id="ksScope"', false)
            ->assertSee('Jasa Pemakaian Garbarata (Aviobridge)');

        $pegawai = MasterPegawai::factory()->create(['status_aktif' => true]);
        $this->actingAs($this->superAdminJasa)->post(route('jasa.admin.store'), [
            'pegawai_id' => $pegawai->id,
            'email' => 'admin.baru@uji.test',
            'password' => 'rahasia123',
            'layanan_ids' => [$this->pendaratan->id],
        ])->assertSessionHasNoErrors();

        $admin = User::where('email', 'admin.baru@uji.test')->firstOrFail();
        $this->assertTrue($admin->hasRole('Admin Jasa'));
        $this->assertSame([$this->pendaratan->id], $this->idLayananAktif($admin));
    }

    public function test_edit_menyimpan_profil_dan_layanan_tanpa_menghapus_peran_lain(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(['Admin Jasa', 'Koordinator Jasa']);
        $admin->layananJasaDikelola()->attach($this->pendaratan->id, ['status_aktif' => true]);

        $this->actingAs($this->superAdminJasa)->get(route('jasa.admin.edit', $admin))->assertOk()
            ->assertSee('Layanan yang Dikelola')
            ->assertSee('value="' . $this->pendaratan->id . '" class="form-check-input" data-nama="Jasa Pendaratan Pesawat Udara" checked', false);

        $this->actingAs($this->superAdminJasa)->put(route('jasa.admin.update', $admin), [
            'nama_lengkap' => 'Admin Jasa Uji',
            'email' => $admin->email,
            'status_aktif' => 1,
            'layanan_ids' => [$this->garbarata->id],
        ])->assertSessionHasNoErrors()->assertRedirect(route('jasa.admin.show', $admin));

        $admin = $admin->fresh();
        $this->assertSame([$this->garbarata->id], $this->idLayananAktif($admin));
        $this->assertTrue($admin->hasRole('Koordinator Jasa'), 'peran lain tidak boleh hilang saat edit');
        $this->assertTrue($admin->hasRole('Admin Jasa'));
    }

    public function test_tautan_atur_layanan_lama_diarahkan_ke_form_edit(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin Jasa');

        $this->actingAs($this->superAdminJasa)->get(route('jasa.admin.layanan.edit', $admin))
            ->assertRedirect(route('jasa.admin.edit', $admin) . '#layanan-admin');

        $this->actingAs($this->superAdminJasa)->get(route('jasa.admin.index'))->assertOk()
            ->assertDontSee(route('jasa.admin.layanan.edit', $admin), false)
            ->assertSee(route('jasa.admin.edit', $admin), false);
    }
}
