<?php

namespace Tests\Feature;

use App\Models\MasterDipa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Toggle aktif/nonaktif DIPA berjalan asinkron: request ber-header
 * X-Async-Form mendapat JSON (via middleware AjaxFlashToJson) tanpa
 * mengubah perilaku submit non-AJAX yang lama.
 */
class DipaToggleAsyncTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private MasterDipa $dipa;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('Super Admin', 'web');
        $this->admin = User::factory()->create();
        $this->admin->assignRole('Super Admin');

        $this->dipa = MasterDipa::create([
            'nomor_dipa' => 'DIPA-TOGGLE-UJI/2026',
            'tahun_anggaran' => 2026,
            'tanggal_disahkan' => '2026-01-03',
            'revisi_aktif_ke' => 0,
            'status_aktif' => true,
        ]);
    }

    public function test_toggle_async_mengembalikan_json_dan_membalik_status(): void
    {
        $response = $this->actingAs($this->admin)
            ->withHeaders([
                'X-Requested-With' => 'XMLHttpRequest',
                'X-Async-Form' => '1',
                'Accept' => 'application/json',
            ])
            ->post(route('dipas.toggle', $this->dipa));

        $response->assertOk()->assertJson(['ok' => true]);
        $this->assertStringContainsString(
            'Status DIPA',
            collect($response->json('messages'))->pluck('text')->implode(' ')
        );

        $this->assertFalse((bool) $this->dipa->fresh()->status_aktif);
        // Flash sudah di-pull middleware — render berikutnya tidak dobel-toast.
        $this->assertFalse(session()->has('success'));
    }

    public function test_toggle_non_async_tetap_redirect_dengan_flash(): void
    {
        $this->actingAs($this->admin)
            ->from(route('dipas.index'))
            ->post(route('dipas.toggle', $this->dipa))
            ->assertRedirect(route('dipas.index'))
            ->assertSessionHas('success');

        $this->assertFalse((bool) $this->dipa->fresh()->status_aktif);
    }
}
