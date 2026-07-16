<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Command Center — halaman audit aktivitas khusus Super Admin:
 * kontrol akses, pencatatan otomatis request mutasi (middleware AuditTrail),
 * jejak login/logout/login gagal, dan endpoint live feed.
 */
class CommandCenterTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('Super Admin', 'web');
        Role::findOrCreate('PPK', 'web');

        $this->superAdmin = User::factory()->create();
        $this->superAdmin->assignRole('Super Admin');
    }

    public function test_super_admin_dapat_membuka_command_center(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('command-center.index'));

        $response->assertOk();
        $response->assertSee('COMMAND CENTER');
    }

    public function test_role_lain_ditolak_akses_command_center(): void
    {
        $ppk = User::factory()->create();
        $ppk->assignRole('PPK');

        $this->actingAs($ppk)->get(route('command-center.index'))->assertForbidden();
        $this->actingAs($ppk)->get(route('command-center.feed'))->assertForbidden();
    }

    public function test_tamu_diarahkan_ke_login(): void
    {
        $this->get(route('command-center.index'))->assertRedirect(route('login'));
    }

    public function test_request_mutasi_tercatat_otomatis_tanpa_data_sensitif(): void
    {
        $this->actingAs($this->superAdmin)->put(route('profile.password.update'), [
            'current_password' => 'salah',
            'password' => 'RahasiaBaru123!',
            'password_confirmation' => 'RahasiaBaru123!',
        ]);

        $log = ActivityLog::where('route', 'profile.password.update')->first();

        $this->assertNotNull($log, 'Request mutasi seharusnya tercatat oleh middleware AuditTrail.');
        $this->assertSame('update', $log->event);
        $this->assertSame($this->superAdmin->id, $log->user_id);
        $this->assertSame('PUT', $log->method);

        // Password & token tidak boleh ikut tersimpan di properti audit.
        $properties = json_encode($log->properties);
        $this->assertStringNotContainsString('RahasiaBaru123!', $properties);
        $this->assertStringNotContainsString('salah', $properties);
    }

    public function test_login_gagal_tercatat(): void
    {
        $this->post(route('login'), [
            'email' => $this->superAdmin->email,
            'password' => 'password-yang-salah',
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'event' => 'login_gagal',
            'user_name' => $this->superAdmin->email,
        ]);
    }

    public function test_live_feed_mengembalikan_log_terbaru(): void
    {
        ActivityLog::create([
            'user_id' => $this->superAdmin->id,
            'user_name' => 'Tester',
            'event' => 'create',
            'modul' => 'tagihan',
            'description' => 'Tambah Data — tagihan.store',
            'ip' => '127.0.0.1',
        ]);

        $response = $this->actingAs($this->superAdmin)->getJson(route('command-center.feed'));

        $response->assertOk()
            ->assertJsonPath('items.0.description', 'Tambah Data — tagihan.store')
            ->assertJsonStructure(['last_id', 'items', 'stats' => ['hari_ini', 'online']]);
    }
}
