<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Seksi menu operasional penerbangan: bagi peran jasa judulnya "Data Operasional
 * (dari AMC)" karena isinya bahan penagihan; bagi AMC tetap "AMC — Operasional".
 */
class SidebarJudulOperasionalTest extends TestCase
{
    use RefreshDatabase;

    private function userDengan(string $role): User
    {
        Role::findOrCreate($role, 'web');
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    public function test_admin_jasa_melihat_judul_data_operasional(): void
    {
        $this->actingAs($this->userDengan('Admin Jasa'))
            ->get(route('permohonan-non-schedule.index'))
            ->assertOk()
            ->assertSee('Data Operasional (dari AMC)')
            ->assertDontSee('AMC &mdash; Operasional', false);
    }

    public function test_amc_tetap_melihat_judul_amc_operasional(): void
    {
        $this->actingAs($this->userDengan('AMC'))
            ->get(route('permohonan-non-schedule.index'))
            ->assertOk()
            ->assertSee('AMC &mdash; Operasional', false)
            ->assertDontSee('Data Operasional (dari AMC)');
    }
}
