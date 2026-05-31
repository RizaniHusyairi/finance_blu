<?php

namespace Tests\Feature;

use App\Models\RekeningBank;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RekeningBankCrudTest extends TestCase
{
    use RefreshDatabase;

    /**
     * UserFactory bawaan rusak di environment ini (mengisi kolom `name` yang
     * sudah di-drop) dan guard profilable mewajibkan relasi pegawai/mitra.
     * Untuk test ini cukup buat user manual + skip guard via env console.
     */
    private function bendaharaPenerimaan(): User
    {
        putenv('SKIP_USER_PROFILABLE_GUARD=true');
        $_ENV['SKIP_USER_PROFILABLE_GUARD'] = true;
        $_SERVER['SKIP_USER_PROFILABLE_GUARD'] = true;

        Role::findOrCreate('Bendahara Penerimaan', 'web');

        $user = new User();
        $user->email = 'bp_' . uniqid() . '@test.local';
        $user->password = bcrypt('secret');
        $user->save();
        $user->assignRole('Bendahara Penerimaan');

        return $user;
    }

    private function makeRekening(User $owner, array $overrides = []): RekeningBank
    {
        return RekeningBank::create(array_merge([
            'pemilik_type' => User::class,
            'pemilik_id' => $owner->id,
            'nama_bank' => 'BTN',
            'nomor_rekening' => (string) random_int(1000000000, 9999999999),
            'nama_rekening' => 'RPL BLU',
            'jenis_rekening' => 'PENERIMAAN',
            'saldo_awal' => 0,
            'is_default' => false,
            'status_aktif' => true,
        ], $overrides));
    }

    public function test_bendahara_penerimaan_can_open_rekening_index(): void
    {
        $user = $this->bendaharaPenerimaan();

        $response = $this->actingAs($user)->get(route('rekening-bank.index'));

        $response->assertOk();
        $response->assertSee('Rekening Bank');
    }

    public function test_store_creates_rekening(): void
    {
        $user = $this->bendaharaPenerimaan();

        $response = $this->actingAs($user)->post(route('rekening-bank.store'), [
            'pemilik_id' => $user->id,
            'nama_bank' => 'BTN',
            'nomor_rekening' => '1234509876',
            'nama_rekening' => 'RPL BLU Penerimaan',
            'jenis_rekening' => 'PENERIMAAN',
            'saldo_awal' => 250000,
            'is_default' => 1,
            'status_aktif' => 1,
        ]);

        $response->assertRedirect(route('rekening-bank.index'));
        $this->assertDatabaseHas('rekening_bank', [
            'nomor_rekening' => '1234509876',
            'jenis_rekening' => 'PENERIMAAN',
        ]);
    }

    public function test_update_form_cannot_change_saldo_awal(): void
    {
        $user = $this->bendaharaPenerimaan();
        $rekening = $this->makeRekening($user, [
            'nomor_rekening' => '5550001111',
            'saldo_awal' => 500000,
        ]);

        // Form Rekening Bank tidak lagi menerima saldo_awal — saldo awal kini dicatat
        // lewat menu Buku Kas Umum. Nilai saldo_awal di payload harus DIABAIKAN dan
        // nilai lama dipertahankan (mencegah dobel-hitung dengan baris saldo awal BKU).
        $this->actingAs($user)->put(route('rekening-bank.update', $rekening), [
            'pemilik_id' => $user->id,
            'nama_bank' => 'BTN',
            'nomor_rekening' => '5550001111',
            'nama_rekening' => 'RPL BLU',
            'jenis_rekening' => 'PENERIMAAN',
            'saldo_awal' => 1000000,
            'status_aktif' => 1,
        ])->assertRedirect();

        $rekening->refresh();
        $this->assertSame(500000.0, (float) $rekening->saldo_awal, 'Saldo awal tidak boleh berubah lewat form Rekening Bank.');
    }

    public function test_only_one_default_per_jenis(): void
    {
        $user = $this->bendaharaPenerimaan();
        $first = $this->makeRekening($user, [
            'nomor_rekening' => '7770001111',
            'is_default' => true,
        ]);

        $this->actingAs($user)->post(route('rekening-bank.store'), [
            'pemilik_id' => $user->id,
            'nama_bank' => 'BTN',
            'nomor_rekening' => '7770002222',
            'nama_rekening' => 'RPL BLU 2',
            'jenis_rekening' => 'PENERIMAAN',
            'saldo_awal' => 0,
            'is_default' => 1,
            'status_aktif' => 1,
        ])->assertRedirect();

        $first->refresh();
        $this->assertFalse((bool) $first->is_default, 'Default lama harus dilepas saat ada default baru sejenis');
    }
}
