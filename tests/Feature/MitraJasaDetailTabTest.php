<?php

namespace Tests\Feature;

use App\Models\KontrakMitraJasa;
use App\Models\LayananJasa;
use App\Models\MasterPihak;
use App\Models\MitraJasa;
use App\Models\TagihanJasa;
use App\Models\User;
use App\Services\MitraLayananService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Detail mitra ditata dalam tab, ringkasan memakai total tagihan sebenarnya,
 * dan kondisi kontrak yang perlu ditindaklanjuti ditampilkan sebagai peringatan.
 */
class MitraJasaDetailTabTest extends TestCase
{
    use RefreshDatabase;

    public function test_detail_mitra_ditata_dalam_tab_dengan_ringkasan_dan_peringatan(): void
    {
        Role::findOrCreate('Super Admin Jasa', 'web');
        $user = User::factory()->create();
        $user->assignRole('Super Admin Jasa');

        $root = LayananJasa::create(['nama_layanan' => 'B. Tarif Jasa Terkait Bandar Udara', 'level' => 1, 'is_active' => true, 'is_leaf' => false, 'tipe_layanan' => 'PNBP']);
        $tanah = LayananJasa::create(['parent_id' => $root->id, 'nama_layanan' => 'Penggunaan Tanah UPBU', 'kode_layanan' => 'APT-01', 'level' => 2, 'is_active' => true, 'is_leaf' => true, 'tipe_layanan' => 'PNBP']);

        $mitra = MitraJasa::create(['nama_mitra' => 'PT Uji Tab', 'email' => 'mitra@uji.test', 'status_aktif' => true]);
        $berjalan = KontrakMitraJasa::create([
            'mitra_jasa_id' => $mitra->id, 'nomor_kontrak' => 'PKS/001', 'nama_kontrak' => 'Sewa Lahan',
            'jenis_dokumen' => 'PERJANJIAN_KERJA_SAMA', 'tanggal_kontrak' => now()->subMonth(), 'tanggal_mulai' => now()->subMonth(),
            'tanggal_selesai' => now()->addYear(), 'status_kontrak' => 'AKTIF',
        ]);
        $berjalan->layananJasa()->attach($tanah->id);
        KontrakMitraJasa::create([
            'mitra_jasa_id' => $mitra->id, 'nomor_kontrak' => 'ADD/002', 'nama_kontrak' => 'Addendum lama',
            'jenis_dokumen' => 'ADDENDUM', 'tanggal_kontrak' => now()->subYears(2), 'tanggal_mulai' => now()->subYears(2),
            'tanggal_selesai' => now()->subDays(3), 'status_kontrak' => 'AKTIF',
        ]);
        app(MitraLayananService::class)->syncFromKontrak($mitra, $user->id);

        $pihak = MasterPihak::create(['kategori' => 'PENERIMAAN', 'nama_pihak' => 'PT Uji Tab']);
        TagihanJasa::create([
            'mitra_id' => $pihak->id, 'mitra_jasa_id' => $mitra->id, 'kontrak_mitra_jasa_id' => $berjalan->id,
            'created_by' => $user->id, 'nomor_tagihan' => 'TGH/TAB/001', 'tanggal_tagihan' => now(),
            'tanggal_jatuh_tempo' => now()->addDays(30), 'total_tagihan' => '2500000.00', 'jumlah_dibayar' => '0.00',
            'sisa_tagihan' => '2500000.00', 'status' => 'DRAFT', 'status_pembayaran' => 'belum_dibayar',
        ]);

        $this->actingAs($user)->get(route('jasa.mitra.show', $mitra))
            ->assertOk()
            // tab
            ->assertSee('data-mt-tab="ringkasan"', false)
            ->assertSee('data-mt-pane="laporan"', false)
            ->assertSee('id="tab-tagihan"', false)
            ->assertSee('id="riwayat-pax-pjp2u"', false)
            // ringkasan memakai total tagihan yang sebenarnya, bukan nilai laporan
            ->assertSee('Total Tagihan')
            ->assertSee('Rp 2.500.000')
            ->assertSee('2 / 2')
            // peringatan kontrak
            ->assertSee('Kontrak ADD/002 sudah lewat masa berlakunya, tetapi status masih AKTIF.')
            ->assertSee('Kontrak ADD/002 aktif tetapi scope layanannya kosong.')
            ->assertSee('Masa berakhir')
            ->assertSee('Scope kosong — belum memberi layanan')
            ->assertDontSee('Berlaku untuk semua layanan aktif mitra')
            // akun & layanan aktif
            ->assertSee('Akun akan dibuat dengan email <strong>mitra@uji.test</strong>', false)
            ->assertSee('B. Tarif Jasa Terkait Bandar Udara')
            ->assertSee('Penggunaan Tanah UPBU')
            ->assertSee('Lengkapi', false);
    }
}
