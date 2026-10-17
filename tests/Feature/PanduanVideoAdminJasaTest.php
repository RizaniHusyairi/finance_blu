<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Panduan\PanduanRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Video panduan alur tagihan tampil di Pusat Panduan Admin Jasa,
 * dan form Buat Tagihan menautkan ke video tersebut.
 */
class PanduanVideoAdminJasaTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['Admin Jasa', 'KPA', 'PLT/PLH'] as $role) {
            Role::findOrCreate($role, 'web');
        }
        $this->admin = User::factory()->create();
        $this->admin->assignRole('Admin Jasa');
    }

    public function test_pusat_panduan_admin_jasa_menampilkan_video_unggulan(): void
    {
        $video = PanduanRegistry::all()['Admin Jasa']['video_utama'];

        $this->actingAs($this->admin)
            ->get(route('panduan.index', ['role' => 'Admin Jasa']))
            ->assertOk()
            ->assertSee('id="video-panduan"', false)
            ->assertSee($video['src'], false)
            ->assertSee('data-pd-seek="98"', false)
            ->assertSee('Terbit otomatis ke mitra')
            ->assertSee('videos/panduan/buat-tagihan-jasa.webm', false);

        $this->assertFileExists(public_path($video['src']));
        $this->assertFileExists(public_path($video['poster']));
        $this->assertFileExists(public_path('videos/panduan/buat-tagihan-jasa.webm'));
    }

    public function test_form_buat_tagihan_menautkan_ke_video_panduan(): void
    {
        $this->actingAs($this->admin)
            ->get(route('tagihan-jasa.create'))
            ->assertOk()
            ->assertSee(route('panduan.index', ['role' => 'Admin Jasa']) . '#video-panduan', false)
            ->assertSee('Tonton Panduan');
    }
}
