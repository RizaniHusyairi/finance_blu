<?php

namespace Tests\Feature;

use App\Models\MitraJasa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Video panduan "Tambah Mitra Jasa" tampil di form tambah mitra,
 * tetapi tidak di form edit.
 */
class MitraJasaPanduanVideoTest extends TestCase
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

    public function test_form_tambah_mitra_menampilkan_video_panduan(): void
    {
        $this->actingAs($this->user)
            ->get(route('jasa.mitra.create'))
            ->assertOk()
            ->assertSee('id="mjGuideModal"', false)
            ->assertSee('videos/panduan/tambah-mitra-jasa.webm', false)
            ->assertSee('Buat akun portal mitra')
            ->assertSee('Scope layanan kontrak')     // bab baru: scope kontrak menentukan layanan aktif
            ->assertSee('data-mj-seek="124"', false)
            ->assertSee('2:29');

        $this->assertFileExists(public_path('videos/panduan/tambah-mitra-jasa.webm'));
        $this->assertFileExists(public_path('videos/panduan/tambah-mitra-jasa-poster.jpg'));
    }

    public function test_form_edit_mitra_tidak_menampilkan_video_panduan(): void
    {
        $mitra = MitraJasa::create(['nama_mitra' => 'PT Uji Panduan', 'status_aktif' => true]);

        $this->actingAs($this->user)
            ->get(route('jasa.mitra.edit', $mitra))
            ->assertOk()
            ->assertDontSee('id="mjGuideModal"', false);
    }
}
