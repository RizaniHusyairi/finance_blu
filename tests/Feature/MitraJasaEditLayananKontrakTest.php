<?php

namespace Tests\Feature;

use App\Models\KontrakMitraJasa;
use App\Models\MitraJasa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Layanan aktif mitra diturunkan dari scope layanan kontrak aktif, jadi tombol
 * "Atur layanan" (pengaturan manual yang sudah dipensiunkan) dihapus dan diganti
 * tombol "Edit layanan" pada aksi tiap kontrak yang membuka scope kontrak.
 */
class MitraJasaEditLayananKontrakTest extends TestCase
{
    use RefreshDatabase;

    public function test_detail_mitra_mengarahkan_edit_layanan_ke_scope_kontrak(): void
    {
        Role::findOrCreate('Super Admin Jasa', 'web');
        $user = User::factory()->create();
        $user->assignRole('Super Admin Jasa');

        $mitra = MitraJasa::create(['nama_mitra' => 'PT Angkasa Demo Sejahtera', 'status_aktif' => true]);
        $kontrak = KontrakMitraJasa::create([
            'mitra_jasa_id' => $mitra->id, 'nomor_kontrak' => 'PKS/001', 'nama_kontrak' => 'Kontrak demo',
            'jenis_dokumen' => 'PERJANJIAN_KERJA_SAMA', 'tanggal_kontrak' => '2026-01-02', 'tanggal_mulai' => '2026-01-02',
            'tanggal_selesai' => '2026-12-31', 'status_kontrak' => 'AKTIF',
        ]);

        $this->actingAs($user)
            ->get(route('jasa.mitra.show', $mitra))
            ->assertOk()
            ->assertSee(route('jasa.mitra.kontrak.edit', [$mitra, $kontrak]) . '#scope-layanan', false)
            ->assertSee('aria-label="Edit layanan"', false)
            ->assertDontSee(route('jasa.mitra.layanan.edit', $mitra), false);

        $this->actingAs($user)
            ->get(route('jasa.mitra.kontrak.edit', [$mitra, $kontrak]))
            ->assertOk()
            ->assertSee('id="scope-layanan"', false);
    }

    public function test_scope_layanan_dikelompokkan_dan_tersimpan_ke_layanan_mitra(): void
    {
        Role::findOrCreate('Super Admin Jasa', 'web');
        $user = User::factory()->create();
        $user->assignRole('Super Admin Jasa');

        $root = \App\Models\LayananJasa::create(['nama_layanan' => 'B. Tarif Jasa Terkait Bandar Udara', 'level' => 1, 'is_active' => true, 'is_leaf' => false, 'tipe_layanan' => 'PNBP']);
        $kat = \App\Models\LayananJasa::create(['parent_id' => $root->id, 'nama_layanan' => 'Penggunaan Tanah dan Ruangan', 'level' => 2, 'is_active' => true, 'is_leaf' => false, 'tipe_layanan' => 'PNBP']);
        $tanah = \App\Models\LayananJasa::create(['parent_id' => $kat->id, 'nama_layanan' => 'Penggunaan Tanah UPBU', 'kode_layanan' => 'APT-01', 'level' => 3, 'is_active' => true, 'is_leaf' => true, 'tipe_layanan' => 'PNBP', 'satuan' => 'per m2 per bulan']);
        $ruang = \App\Models\LayananJasa::create(['parent_id' => $kat->id, 'nama_layanan' => 'Penggunaan Ruangan Kantor', 'kode_layanan' => 'APT-02', 'level' => 3, 'is_active' => true, 'is_leaf' => true, 'tipe_layanan' => 'PNBP']);

        $mitra = MitraJasa::create(['nama_mitra' => 'PT Angkasa Demo Sejahtera', 'status_aktif' => true]);
        $kontrak = KontrakMitraJasa::create([
            'mitra_jasa_id' => $mitra->id, 'nomor_kontrak' => 'PKS/001', 'nama_kontrak' => 'Kontrak demo',
            'jenis_dokumen' => 'PERJANJIAN_KERJA_SAMA', 'tanggal_kontrak' => '2026-01-02', 'tanggal_mulai' => '2026-01-02',
            'tanggal_selesai' => '2026-12-31', 'status_kontrak' => 'AKTIF',
        ]);
        $kontrak->layananJasa()->attach($tanah->id);

        $this->actingAs($user)
            ->get(route('jasa.mitra.kontrak.edit', [$mitra, $kontrak]))
            ->assertOk()
            ->assertSee('id="ksScope"', false)
            ->assertSee('B. Tarif Jasa Terkait Bandar Udara')      // grup kategori puncak
            ->assertSee('Penggunaan Tanah dan Ruangan')              // subjudul jalur
            ->assertSee('1/2', false)                                // badge terpilih/total
            ->assertSee('menjadi <b>layanan aktif mitra</b>', false) // teks bantuan baru
            ->assertDontSee('Kosongkan jika kontrak berlaku untuk semua layanan aktif mitra')
            ->assertSee('value="' . $tanah->id . '" class="form-check-input" data-nama="Penggunaan Tanah UPBU" checked', false);

        // Simpan dengan scope baru: kontrak & layanan aktif mitra ikut diperbarui.
        $this->actingAs($user)->put(route('jasa.mitra.kontrak.update', [$mitra, $kontrak]), [
            'nomor_kontrak' => 'PKS/001', 'nama_kontrak' => 'Kontrak demo', 'jenis_dokumen' => 'PERJANJIAN_KERJA_SAMA',
            'tanggal_kontrak' => '2026-01-02', 'tanggal_mulai' => '2026-01-02', 'tanggal_selesai' => '2026-12-31',
            'status_kontrak' => 'AKTIF', 'layanan_ids' => [$tanah->id, $ruang->id],
        ])->assertSessionHasNoErrors();

        $this->assertEqualsCanonicalizing([$tanah->id, $ruang->id], $kontrak->fresh()->layananJasa->pluck('id')->all());
        $this->assertEqualsCanonicalizing([$tanah->id, $ruang->id], $mitra->fresh()->layananJasaAktif()->pluck('layanan_jasas.id')->all());
    }
}
