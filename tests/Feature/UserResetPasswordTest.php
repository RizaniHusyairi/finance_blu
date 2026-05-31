<?php

namespace Tests\Feature;

use App\Models\MasterPegawai;
use App\Models\User;
use App\Models\WhatsappNotificationLog;
use App\Services\Admin\UserProvisioningService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserResetPasswordTest extends TestCase
{
    use RefreshDatabase;

    private function superAdmin(): User
    {
        Role::findOrCreate('Super Admin', 'web');

        $admin = User::factory()->create();
        $admin->assignRole('Super Admin');

        return $admin;
    }

    public function test_super_admin_reset_password_sets_default_and_sends_whatsapp(): void
    {
        $admin = $this->superAdmin();

        $pegawai = MasterPegawai::factory()->create(['nomor_hp' => '081234567890']);
        $target = User::factory()->pegawai($pegawai)->create(['email' => 'pegawai@example.test']);

        $response = $this->actingAs($admin)
            ->post(route('admin.users.reset-password', $target));

        $response->assertRedirect();

        // Password direset ke nilai default.
        $target->refresh();
        $this->assertTrue(
            Hash::check(UserProvisioningService::DEFAULT_RESET_PASSWORD, $target->password),
            'Password tidak direset ke password default.'
        );

        // Notifikasi WhatsApp tercatat ke nomor pegawai, berisi email & password baru.
        $log = WhatsappNotificationLog::latest('id')->first();
        $this->assertNotNull($log, 'Log WhatsApp tidak terbentuk.');
        $this->assertSame('081234567890', $log->target);
        $this->assertStringContainsString('pegawai@example.test', $log->message);
        $this->assertStringContainsString(UserProvisioningService::DEFAULT_RESET_PASSWORD, $log->message);
    }

    public function test_reset_password_without_whatsapp_number_skips_notification(): void
    {
        $admin = $this->superAdmin();

        // Pegawai tanpa nomor_hp -> tidak ada nomor WA.
        $pegawai = MasterPegawai::factory()->create(['nomor_hp' => null]);
        $target = User::factory()->pegawai($pegawai)->create();

        $response = $this->actingAs($admin)
            ->post(route('admin.users.reset-password', $target));

        $response->assertRedirect();

        $target->refresh();
        $this->assertTrue(
            Hash::check(UserProvisioningService::DEFAULT_RESET_PASSWORD, $target->password)
        );

        // Tidak ada notifikasi WhatsApp yang dikirim.
        $this->assertSame(0, WhatsappNotificationLog::count());
    }

    public function test_non_super_admin_cannot_reset_password(): void
    {
        Role::findOrCreate('Bendahara Pengeluaran', 'web');
        $actor = User::factory()->create();
        $actor->assignRole('Bendahara Pengeluaran');

        $target = User::factory()->create();
        $originalPassword = $target->password;

        $response = $this->actingAs($actor)
            ->post(route('admin.users.reset-password', $target));

        $response->assertForbidden();

        $target->refresh();
        $this->assertSame($originalPassword, $target->password, 'Password seharusnya tidak berubah.');
    }
}
