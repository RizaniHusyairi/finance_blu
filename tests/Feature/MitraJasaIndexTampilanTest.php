<?php

namespace Tests\Feature;

use App\Models\KontrakMitraJasa;
use App\Models\MitraJasa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Daftar Mitra Jasa: tampilan tabel & kartu dirender bersamaan (dipilih lewat
 * tombol Tabel/Kartu di sisi browser) dengan data kontak & ringkasan yang jelas.
 */
class MitraJasaIndexTampilanTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('Super Admin Jasa', 'web');
        $this->user = User::factory()->create();
        $this->user->assignRole('Super Admin Jasa');
    }

    public function test_daftar_mitra_menampilkan_tabel_dan_kartu(): void
    {
        $mitra = MitraJasa::create([
            'kode_mitra' => 'MJ-0001', 'jenis_mitra' => 'BADAN_USAHA', 'npwp' => '01.234.567.8-912.000',
            'nama_mitra' => 'PT Angkasa Demo Sejahtera', 'email' => 'mitra@example.com', 'no_telepon' => '0812-3456-7890',
            'nama_penanggung_jawab' => 'Budi Santoso', 'jabatan_penanggung_jawab' => 'Direktur Utama', 'status_aktif' => true,
        ]);
        KontrakMitraJasa::create([
            'mitra_jasa_id' => $mitra->id, 'nomor_kontrak' => 'LEGACY-001', 'nama_kontrak' => 'Kontrak migrasi',
            'jenis_dokumen' => 'PERJANJIAN_KERJA_SAMA', 'tanggal_kontrak' => '2026-01-02', 'tanggal_mulai' => '2026-01-02',
            'tanggal_selesai' => '2026-12-31', 'status_kontrak' => 'AKTIF',
        ]);
        MitraJasa::create(['nama_mitra' => 'CV Tanpa Kontak', 'status_aktif' => false]);

        $this->actingAs($this->user)
            ->get(route('jasa.mitra.index'))
            ->assertOk()
            // pemilih tampilan + kedua tampilan
            ->assertSee('data-mj-view="table"', false)
            ->assertSee('data-mj-view="card"', false)
            ->assertSee('class="mj-view-card"', false)
            ->assertSee('mj-view-table', false)
            // inisial tanpa awalan PT/CV, tautan WA 62..., mailto, PIC
            ->assertSee('>AD<', false)
            ->assertSee('>TK<', false)
            ->assertSee('https://wa.me/6281234567890', false)
            ->assertSee('mailto:mitra@example.com', false)
            ->assertSee('Budi Santoso')
            // ringkasan & penanda
            ->assertSee('Legacy perlu dilengkapi')
            ->assertSee('Belum ada kontrak aktif')
            ->assertSee('WA belum diisi')
            // aksi tetap tersedia untuk pengelola master
            ->assertSee(route('jasa.mitra.edit', $mitra), false)
            ->assertSee(route('jasa.mitra.destroy', $mitra), false);
    }

    public function test_daftar_kosong_menampilkan_pesan(): void
    {
        $this->actingAs($this->user)
            ->get(route('jasa.mitra.index'))
            ->assertOk()
            ->assertSee('Belum ada mitra jasa.');
    }
}
