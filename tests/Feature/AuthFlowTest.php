<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AuthFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_internal_user_is_redirected_to_internal_dashboard_after_login(): void
    {
        Role::findOrCreate('Operator BLU', 'web');

        $user = User::factory()->create([
            'email' => 'operator@example.com',
            'password' => 'password',
        ]);
        $user->assignRole('Operator BLU');

        $response = $this->post(route('login'), [
            'email' => 'operator@example.com',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_mitra_user_is_redirected_to_mitra_dashboard_after_login(): void
    {
        Role::findOrCreate('Mitra', 'web');

        $user = User::factory()->create([
            'email' => 'mitra@example.com',
            'password' => 'password',
        ]);
        $user->assignRole('Mitra');

        $response = $this->post(route('login'), [
            'email' => 'mitra@example.com',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('mitra.dashboard'));
        $this->assertAuthenticatedAs($user);
    }
}
