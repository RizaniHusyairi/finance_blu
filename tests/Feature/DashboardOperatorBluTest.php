<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Dashboard Operator BLU — kontrol akses dan render halaman (KPI anggaran,
 * pipeline pencairan, kesehatan master data, antrean aksi, tren).
 */
class DashboardOperatorBluTest extends TestCase
{
    use RefreshDatabase;

    private function userWithRole(string $role): User
    {
        Role::findOrCreate($role, 'web');
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    public function test_operator_blu_dapat_membuka_dashboardnya(): void
    {
        $response = $this->actingAs($this->userWithRole('Operator BLU'))
            ->get(route('dashboard.operator-blu'));

        $response->assertOk();
        $response->assertSee('Pusat Kendali Operator BLU');
        $response->assertSee('Pipeline Pencairan');
        $response->assertSee('Kesehatan Master Data');
        $response->assertSee('Antrean Aksi Anda');
    }

    public function test_route_dashboard_umum_mengarahkan_operator_blu_ke_dashboardnya(): void
    {
        $response = $this->actingAs($this->userWithRole('Operator BLU'))
            ->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Pusat Kendali Operator BLU');
    }

    public function test_super_admin_boleh_membuka_dashboard_operator_blu(): void
    {
        $this->actingAs($this->userWithRole('Super Admin'))
            ->get(route('dashboard.operator-blu'))
            ->assertOk();
    }

    public function test_role_lain_ditolak(): void
    {
        $this->actingAs($this->userWithRole('PPK'))
            ->get(route('dashboard.operator-blu'))
            ->assertForbidden();
    }

    public function test_tamu_diarahkan_ke_login(): void
    {
        $this->get(route('dashboard.operator-blu'))->assertRedirect(route('login'));
    }
}
