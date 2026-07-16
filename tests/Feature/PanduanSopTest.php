<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Panduan\PanduanRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Pusat Panduan & SOP PDF — memastikan seluruh peran pada registry punya
 * konten lengkap, halaman panduan dapat dibuka oleh tiap peran (termasuk
 * AMC & admin utilitas yang dulunya tertolak 403), dan SOP PDF terunduh.
 */
class PanduanSopTest extends TestCase
{
    use RefreshDatabase;

    private function userWithRole(string $role): User
    {
        Role::findOrCreate($role, 'web');
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    public function test_semua_entri_registry_lengkap_tanpa_placeholder(): void
    {
        foreach (PanduanRegistry::all() as $role => $guide) {
            foreach (['label', 'ikon', 'warna', 'ringkasan', 'menus', 'alur', 'faq'] as $kunci) {
                $this->assertArrayHasKey($kunci, $guide, "Panduan '$role' tidak punya kunci '$kunci'.");
            }

            $this->assertNotEmpty($guide['alur'], "Panduan '$role' masih placeholder (alur kosong).");
            $this->assertNotEmpty($guide['menus'], "Panduan '$role' tidak mencantumkan menu.");
            $this->assertNotEmpty($guide['faq'], "Panduan '$role' tidak punya FAQ.");

            foreach ($guide['alur'] as $i => $langkah) {
                foreach (['ikon', 'judul', 'detail', 'menu'] as $kunci) {
                    $this->assertArrayHasKey($kunci, $langkah, "Langkah #$i panduan '$role' tidak punya '$kunci'.");
                }
            }
        }
    }

    public function test_setiap_peran_registry_dapat_membuka_panduannya(): void
    {
        foreach (array_keys(PanduanRegistry::all()) as $role) {
            $response = $this->actingAs($this->userWithRole($role))->get(route('panduan.index'));

            $response->assertOk();
            $response->assertSee(PanduanRegistry::forRole($role)['label']);
            $response->assertDontSee('sedang disiapkan');
        }
    }

    public function test_peran_di_luar_internal_roles_tidak_lagi_tertolak(): void
    {
        // Regression: AMC, Admin Listrik, Admin Air melihat link Panduan di sidebar
        // tapi dulu kena 403 karena tidak masuk $internalRoles.
        foreach (['AMC', 'Admin Listrik', 'Admin Air'] as $role) {
            $this->actingAs($this->userWithRole($role))
                ->get(route('panduan.index'))
                ->assertOk();
        }
    }

    public function test_mitra_tetap_ditolak_membuka_panduan(): void
    {
        $this->actingAs($this->userWithRole('Mitra'))
            ->get(route('panduan.index'))
            ->assertForbidden();
    }

    public function test_sop_pdf_terunduh_untuk_peran_lama_dan_baru(): void
    {
        // Satu peran lama (PPK) & satu peran baru (AMC) mewakili render dompdf;
        // kelengkapan konten seluruh peran sudah dijamin test registry di atas.
        foreach (['PPK', 'AMC'] as $role) {
            $response = $this->actingAs($this->userWithRole($role))
                ->get(route('panduan.sop.download', ['slug' => PanduanRegistry::slugFor($role)]));

            $response->assertOk();
            $response->assertHeader('content-type', 'application/pdf');
            $response->assertDownload('SOP-SIKEREN-'.PanduanRegistry::slugFor($role).'.pdf');
        }
    }

    public function test_slug_tidak_dikenal_mengembalikan_404(): void
    {
        $this->actingAs($this->userWithRole('Super Admin'))
            ->get(route('panduan.sop.download', ['slug' => 'peran-tidak-ada']))
            ->assertNotFound();
    }

    public function test_tamu_diarahkan_ke_login(): void
    {
        $this->get(route('panduan.index'))->assertRedirect(route('login'));
        $this->get(route('panduan.sop.download', ['slug' => 'ppk']))->assertRedirect(route('login'));
    }
}
