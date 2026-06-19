<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PembukuanMenuTest extends TestCase
{
    use RefreshDatabase;

    public function test_bendahara_pengeluaran_can_open_all_pembukuan_pages(): void
    {
        Role::findOrCreate('Bendahara Pengeluaran', 'web');

        $user = User::factory()->create();
        $user->assignRole('Bendahara Pengeluaran');

        // BKU gabungan lama dipensiunkan → pakai BKU per-peran (Pengeluaran).
        $routes = [
            'pembukuan.pengeluaran.index',
            'pembukuan.bank.index',
            'pembukuan.bendahara.index',
            'pembukuan.bunga.index',
            'pembukuan.pajak.index',
            'pembukuan.pengesahan.index',
        ];

        foreach ($routes as $routeName) {
            $response = $this->actingAs($user)->get(route($routeName));

            $this->assertSame(200, $response->getStatusCode(), 'Route gagal diakses: ' . $routeName);
            $response->assertSee('Pembukuan');
        }
    }

    public function test_bendahara_penerimaan_can_open_his_pembukuan_pages(): void
    {
        Role::findOrCreate('Bendahara Penerimaan', 'web');

        $user = User::factory()->create();
        $user->assignRole('Bendahara Penerimaan');

        // BKU per-peran (Penerimaan); pengesahan sisi penerimaan = Pengesahan Pendapatan.
        $routes = [
            'pembukuan.penerimaan.index',
            'pembukuan.bank.index',
            'pembukuan.bendahara.index',
            'pembukuan.bunga.index',
            'pembukuan.pengesahan-pendapatan.index',
            'pembukuan.piutang.index',
        ];

        foreach ($routes as $routeName) {
            $response = $this->actingAs($user)->get(route($routeName));

            $this->assertSame(200, $response->getStatusCode(), 'Route gagal diakses: ' . $routeName);
            $response->assertSee('Pembukuan');
        }
    }
}
