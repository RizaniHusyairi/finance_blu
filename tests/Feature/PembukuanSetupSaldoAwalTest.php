<?php

namespace Tests\Feature;

use App\Enums\JenisRekening;
use App\Enums\KodeBuku;
use App\Enums\PeranBuku;
use App\Models\PembukuanSaldoAwal;
use App\Models\RekeningBank;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Setup Pembukuan — tabel Saldo Awal per Rekening: kedua peran selalu tampil
 * (baris baru bila rekening belum ada), pembuatan rekening langsung dari
 * tabel, dan tiap peran hanya bisa dikelola role bendahara-nya sendiri.
 */
class PembukuanSetupSaldoAwalTest extends TestCase
{
    use RefreshDatabase;

    private User $bp;

    private User $bpn;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['Bendahara Pengeluaran', 'Bendahara Penerimaan'] as $role) {
            Role::findOrCreate($role, 'web');
        }

        $this->bp = User::factory()->create();
        $this->bp->assignRole('Bendahara Pengeluaran');
        $this->bpn = User::factory()->create();
        $this->bpn->assignRole('Bendahara Penerimaan');
    }

    public function test_halaman_setup_selalu_menampilkan_baris_kedua_peran(): void
    {
        // Tanpa rekening sama sekali: kedua baris tampil sebagai input baru.
        $this->actingAs($this->bp)
            ->get(route('pembukuan.setup.edit'))
            ->assertOk()
            ->assertSee('PENGELUARAN')
            ->assertSee('PENERIMAAN')
            ->assertSee('Belum terdaftar')
            ->assertSee('Hanya Bendahara Penerimaan')
            ->assertDontSee('Belum ada rekening Penerimaan/Pengeluaran aktif.');
    }

    public function test_bendahara_pengeluaran_membuat_rekening_dari_tabel_saldo_awal(): void
    {
        $this->actingAs($this->bp)->post(route('pembukuan.setup.saldo-awal'), [
            'saldo' => [
                'new_PENGELUARAN' => [
                    'nama_bank' => 'Bank BTN',
                    'nomor_rekening' => '0006201880003220',
                    'nama_rekening' => 'BPG BLU UPBU APT Pranoto',
                    'nominal' => 150_000_000,
                    'tanggal' => now()->startOfYear()->toDateString(),
                ],
                // Peran penerimaan BUKAN wewenangnya — harus diabaikan.
                'new_PENERIMAAN' => [
                    'nama_bank' => 'Bank Ilegal',
                    'nomor_rekening' => '999999',
                    'nama_rekening' => 'Tidak Boleh',
                    'nominal' => 1,
                ],
            ],
        ])->assertRedirect(route('pembukuan.setup.edit'));

        $rekening = RekeningBank::where('nomor_rekening', '0006201880003220')->firstOrFail();
        $this->assertSame(JenisRekening::PENGELUARAN->value, $rekening->jenis_rekening?->value ?? $rekening->jenis_rekening);
        $this->assertTrue((bool) $rekening->status_aktif);
        $this->assertSame($this->bp->id, (int) $rekening->pemilik_id, 'Pemilik = user Bendahara Pengeluaran.');

        $saldo = PembukuanSaldoAwal::where('rekening_bank_id', $rekening->id)
            ->where('kode_buku', KodeBuku::BKU->value)
            ->where('peran', PeranBuku::PENGELUARAN->value)
            ->first();
        $this->assertNotNull($saldo, 'Saldo awal tersimpan bersamaan dengan pembuatan rekening.');
        $this->assertSame(150_000_000.0, (float) $saldo->nominal);

        $this->assertDatabaseMissing('rekening_bank', ['nomor_rekening' => '999999']);
    }

    public function test_bendahara_tidak_bisa_mengubah_rekening_peran_lain(): void
    {
        $rekeningPengeluaran = RekeningBank::create([
            'pemilik_type' => User::class,
            'pemilik_id' => $this->bp->id,
            'nama_bank' => 'Bank Asli',
            'nomor_rekening' => '111222333',
            'nama_rekening' => 'BPG Asli',
            'jenis_rekening' => JenisRekening::PENGELUARAN->value,
            'is_default' => true,
            'status_aktif' => true,
        ]);

        // Bendahara Penerimaan mencoba mengubah rekening pengeluaran — diabaikan.
        $this->actingAs($this->bpn)->post(route('pembukuan.setup.saldo-awal'), [
            'saldo' => [
                (string) $rekeningPengeluaran->id => [
                    'nama_bank' => 'Bank Bajakan',
                    'nomor_rekening' => '444555666',
                    'nama_rekening' => 'Diambil Alih',
                    'nominal' => 5,
                ],
            ],
        ])->assertRedirect(route('pembukuan.setup.edit'));

        $rekeningPengeluaran->refresh();
        $this->assertSame('Bank Asli', $rekeningPengeluaran->nama_bank);
        $this->assertSame('111222333', $rekeningPengeluaran->nomor_rekening);
        $this->assertSame(0, PembukuanSaldoAwal::where('rekening_bank_id', $rekeningPengeluaran->id)->count());
    }
}
