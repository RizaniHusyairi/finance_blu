<?php

namespace Tests\Feature;

use App\Models\MasterMitraVendor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SupplierDatatableTest extends TestCase
{
    use RefreshDatabase;

    public function test_suppliers_datatable_endpoint_returns_server_side_json(): void
    {
        Role::findOrCreate('Pejabat Pengadaan', 'web');
        $user = User::factory()->create();
        $user->assignRole('Pejabat Pengadaan');

        MasterMitraVendor::create([
            'kategori' => 'PENGELUARAN',
            'jenis_entitas' => 'BADAN_USAHA',
            'nama_perusahaan' => 'PT Uji Server Side',
            'status_aktif' => true,
        ]);

        $response = $this->actingAs($user)
            ->getJson(route('suppliers.index-data') . '?draw=1&start=0&length=10');

        $response->assertOk();
        $response->assertJsonStructure(['draw', 'recordsTotal', 'recordsFiltered', 'data']);
        $this->assertSame(1, $response->json('recordsTotal'));
        $this->assertCount(1, $response->json('data'));
        $this->assertCount(5, $response->json('data.0')); // 5 kolom ter-render

        // Pencarian server-side: kata tak cocok → recordsFiltered 0, total tetap.
        $empty = $this->actingAs($user)
            ->getJson(route('suppliers.index-data') . '?draw=2&start=0&length=10&search[value]=ZZZ-TIDAK-ADA');
        $empty->assertOk();
        $this->assertSame(0, $empty->json('recordsFiltered'));
        $this->assertSame(1, $empty->json('recordsTotal'));
    }
}
