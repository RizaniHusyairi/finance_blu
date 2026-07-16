<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Middleware AjaxFlashToJson (form async Proses Tagihan):
 * - Request ber-header X-Async-Form → redirect+flash dikonversi JSON dan
 *   flash TERHAPUS dari session (tidak dobel-toast setelah render berikutnya).
 * - Tanpa header → perilaku lama utuh (302 + flash tetap ada).
 * - Kegagalan validasi dengan Accept: application/json → 422 JSON native.
 */
class AsyncFormFlashJsonTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('Super Admin', 'web');
        $this->user = User::factory()->create();
        $this->user->assignRole('Super Admin');

        // Route uji ber-middleware web — cukup untuk memverifikasi konversi
        // redirect+flash oleh middleware yang di-append ke web group.
        Route::middleware('web')->group(function () {
            Route::post('/_uji/flash-sukses', fn () => back()
                ->with('success', 'Data tersimpan.')
                ->with('warning', 'Perhatikan ambang PMK.'));

            Route::post('/_uji/flash-gagal', fn () => back()->with('error', 'Saldo tidak cukup.'));

            Route::post('/_uji/flash-errors-bag', fn () => back()->withErrors(['ntpn' => 'NTPN tidak valid.']));

            Route::post('/_uji/flash-bulk', fn () => back()
                ->with('success', 'Semua disetujui.')
                ->with('bulk_approved', true));

            Route::post('/_uji/validasi', function (Request $request) {
                $request->validate(['wajib' => 'required']);

                return back()->with('success', 'Lolos.');
            });
        });
    }

    private function asyncHeaders(): array
    {
        return [
            'X-Requested-With' => 'XMLHttpRequest',
            'X-Async-Form' => '1',
            'Accept' => 'application/json',
        ];
    }

    public function test_redirect_flash_dikonversi_json_dan_flash_terhapus(): void
    {
        $response = $this->actingAs($this->user)
            ->withHeaders($this->asyncHeaders())
            ->post('/_uji/flash-sukses');

        $response->assertOk()
            ->assertJson([
                'ok' => true,
                'messages' => [
                    ['level' => 'success', 'text' => 'Data tersimpan.'],
                    ['level' => 'warning', 'text' => 'Perhatikan ambang PMK.'],
                ],
                'bulk_approved' => false,
            ]);

        // Flash sudah di-pull — render halaman berikutnya tidak dobel-toast.
        $this->assertFalse(session()->has('success'));
        $this->assertFalse(session()->has('warning'));
    }

    public function test_flash_error_menghasilkan_ok_false(): void
    {
        $this->actingAs($this->user)
            ->withHeaders($this->asyncHeaders())
            ->post('/_uji/flash-gagal')
            ->assertOk()
            ->assertJson([
                'ok' => false,
                'messages' => [['level' => 'danger', 'text' => 'Saldo tidak cukup.']],
            ]);
    }

    public function test_errors_bag_ikut_dikonversi(): void
    {
        $this->actingAs($this->user)
            ->withHeaders($this->asyncHeaders())
            ->post('/_uji/flash-errors-bag')
            ->assertOk()
            ->assertJson([
                'ok' => false,
                'messages' => [['level' => 'danger', 'text' => 'NTPN tidak valid.']],
            ]);
    }

    public function test_bulk_approved_diteruskan_dan_dihapus(): void
    {
        $this->actingAs($this->user)
            ->withHeaders($this->asyncHeaders())
            ->post('/_uji/flash-bulk')
            ->assertOk()
            ->assertJson(['ok' => true, 'bulk_approved' => true]);

        $this->assertFalse(session()->has('bulk_approved'));
    }

    public function test_tanpa_header_perilaku_lama_utuh(): void
    {
        $response = $this->actingAs($this->user)
            ->from('/_uji/asal')
            ->post('/_uji/flash-sukses');

        $response->assertRedirect('/_uji/asal');
        $response->assertSessionHas('success', 'Data tersimpan.');
    }

    public function test_kegagalan_validasi_tetap_422_json_native(): void
    {
        $this->actingAs($this->user)
            ->withHeaders($this->asyncHeaders())
            ->post('/_uji/validasi', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['wajib']);
    }
}
