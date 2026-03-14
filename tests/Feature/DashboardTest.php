<?php

namespace Tests\Feature;

use App\Models\Budget;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_internal_dashboard_renders_with_current_budget_and_transaction_schema(): void
    {
        Role::findOrCreate('Operator BLU', 'web');

        $user = User::factory()->create();
        $user->assignRole('Operator BLU');

        $budget = Budget::create([
            'coa' => '524111',
            'description' => 'Belanja Pengujian',
            'initial_budget' => 1000000,
            'realized_budget' => 250000,
            'remaining_budget' => 750000,
            'year' => 2026,
        ]);

        Transaction::create([
            'transaction_number' => 'TRX-20260314-0001',
            'date' => '2026-03-14',
            'budget_id' => $budget->id,
            'type' => 'UP',
            'description' => 'Pembayaran pengujian',
            'gross_amount' => 250000,
            'net_amount' => 225000,
            'status' => 'Paid SP2D',
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('524111');
        $response->assertSee('Rp 1.000.000');
    }
}
