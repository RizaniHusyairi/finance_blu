<?php

namespace Tests\Feature;

use App\Models\LayananJasa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Menu & akses Data Operasional (dari AMC) untuk Admin Jasa mengikuti layanan yang ia kelola:
 * - Permohonan Non-Schedule → layanan penerbangan (pendaratan/penempatan/penyimpanan pesawat, luar jam operasi);
 * - Pemakaian Garbarata & Rekap Tagihan Garbarata → Jasa Pemakaian Garbarata (Aviobridge).
 */
class AksesDataAmcAdminJasaTest extends TestCase
{
    use RefreshDatabase;

    private array $L = [];

    protected function setUp(): void
    {
        parent::setUp();
        $mk = fn ($a) => LayananJasa::create($a + ['is_active' => true, 'tipe_layanan' => 'PNBP']);
        $a = $mk(['nama_layanan' => 'A. Tarif Jasa Kebandarudaraan (Layanan Domestik)', 'level' => 1, 'is_leaf' => false]);
        $pend = $mk(['parent_id' => $a->id, 'nama_layanan' => 'Jasa Pendaratan Pesawat Udara', 'level' => 2, 'is_leaf' => false]);
        $this->L['pendaratan'] = $mk(['parent_id' => $pend->id, 'nama_layanan' => 'a. bobot pesawat s.d 40.000kg', 'level' => 3, 'is_leaf' => true]);
        $this->L['luarJam'] = $mk(['parent_id' => $a->id, 'nama_layanan' => 'Jasa Penggunaan Bandar Udara untuk Pesawat Udara di Luar Jam Operasi', 'level' => 2, 'is_leaf' => true]);
        $this->L['garbarata'] = $mk(['parent_id' => $a->id, 'nama_layanan' => 'Jasa Pemakaian Garbarata (Aviobridge)', 'level' => 2, 'is_leaf' => true]);
        $b = $mk(['nama_layanan' => 'B. Tarif Jasa Terkait Bandar Udara (Layanan Domestik)', 'level' => 1, 'is_leaf' => false]);
        $this->L['bisApron'] = $mk(['parent_id' => $b->id, 'nama_layanan' => 'j. Garbarata/ Bis Layanan Penumpang di Apron', 'level' => 2, 'is_leaf' => true]);
        $this->L['fasilitasLuarJam'] = $mk(['parent_id' => $b->id, 'nama_layanan' => 'Penggunaan Fasilitas dan Personil di Luar Jam Operasional Bandar Udara', 'level' => 2, 'is_leaf' => true]);
        $this->L['tanah'] = $mk(['parent_id' => $b->id, 'nama_layanan' => 'Penggunaan Tanah', 'level' => 2, 'is_leaf' => true]);
    }

    private function adminJasa(array $layanan): User
    {
        Role::findOrCreate('Admin Jasa', 'web');
        $u = User::factory()->create();
        $u->assignRole('Admin Jasa');
        foreach ($layanan as $k) {
            $u->layananJasaDikelola()->attach($this->L[$k]->id, ['status_aktif' => true]);
        }

        return $u;
    }

    private function cek(User $u, bool $nonSchedule, bool $garbarata): void
    {
        $this->actingAs($u)->get(route('permohonan-non-schedule.index'))->assertStatus($nonSchedule ? 200 : 403);
        $this->actingAs($u)->get(route('pemakaian-garbarata.index'))->assertStatus($garbarata ? 200 : 403);
        $this->actingAs($u)->get(route('pengajuan-penagihan-garbarata.index'))->assertStatus($garbarata ? 200 : 403);
    }

    public function test_admin_jasa_tanpa_layanan_terkait_tidak_melihat_menu_dan_ditolak(): void
    {
        $u = $this->adminJasa(['tanah', 'bisApron', 'fasilitasLuarJam']);
        $this->cek($u, false, false);

        $this->actingAs($u)->get(route('tagihan-jasa.index'))->assertOk()
            ->assertDontSee('Data Operasional (dari AMC)')
            ->assertDontSee(route('permohonan-non-schedule.index'), false)
            ->assertDontSee(route('pemakaian-garbarata.index'), false);
    }

    public function test_admin_jasa_layanan_penerbangan_hanya_mendapat_permohonan_non_schedule(): void
    {
        foreach (['pendaratan', 'luarJam'] as $layanan) {
            $u = $this->adminJasa([$layanan]);
            $this->cek($u, true, false);
            $this->actingAs($u)->get(route('tagihan-jasa.index'))->assertOk()
                ->assertSee('Data Operasional (dari AMC)')
                ->assertSee(route('permohonan-non-schedule.index'), false)
                ->assertDontSee(route('pemakaian-garbarata.index'), false)
                ->assertDontSee(route('pengajuan-penagihan-garbarata.index'), false);
        }
    }

    public function test_admin_jasa_pemakaian_garbarata_hanya_mendapat_menu_garbarata(): void
    {
        $u = $this->adminJasa(['garbarata']);
        $this->cek($u, false, true);
        $this->actingAs($u)->get(route('tagihan-jasa.index'))->assertOk()
            ->assertSee(route('pemakaian-garbarata.index'), false)
            ->assertSee(route('pengajuan-penagihan-garbarata.index'), false)
            ->assertDontSee(route('permohonan-non-schedule.index'), false);
    }

    public function test_peran_lain_tidak_dipersempit(): void
    {
        foreach (['Koordinator Jasa', 'Super Admin Jasa'] as $role) {
            Role::findOrCreate($role, 'web');
            $u = User::factory()->create();
            $u->assignRole($role);
            $this->cek($u, true, true);
        }
        Role::findOrCreate('AMC', 'web');
        $amc = User::factory()->create();
        $amc->assignRole('AMC');
        $this->actingAs($amc)->get(route('permohonan-non-schedule.index'))->assertOk();
        $this->actingAs($amc)->get(route('pemakaian-garbarata.index'))->assertOk();
    }

    public function test_form_tagihan_memakai_pola_layanan_penerbangan(): void
    {
        // Halaman create membaca daftar penandatangan per peran.
        foreach (['PLT/PLH', 'KPA', 'Koordinator Jasa', 'Kepala Seksi Pelayanan dan Kerjasama', 'Kepala Subbagian Keuangan dan Tata Usaha'] as $r) Role::findOrCreate($r, 'web');
        $u = $this->adminJasa(['pendaratan']);
        $this->actingAs($u)->get(route('tagihan-jasa.create'))->assertOk()
            ->assertSee('const POLA_PANEL_PENERBANGAN', false)
            ->assertSee('(pendaratan|penempatan|penyimpanan) pesawat', false);
    }
}
