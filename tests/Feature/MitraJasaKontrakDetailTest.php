<?php

namespace Tests\Feature;

use App\Models\KontrakMitraJasa;
use App\Models\LayananJasa;
use App\Models\MasterPihak;
use App\Models\MitraJasa;
use App\Models\TagihanJasa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Halaman detail kontrak: peringatan yang perlu ditindaklanjuti, scope per
 * kategori, daftar tagihan terkait, dan pratinjau dokumen.
 */
class MitraJasaKontrakDetailTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        Role::findOrCreate('Super Admin Jasa', 'web');
        $user = User::factory()->create();
        $user->assignRole('Super Admin Jasa');

        return $user;
    }

    private function kontrak(MitraJasa $mitra, array $attr = []): KontrakMitraJasa
    {
        return KontrakMitraJasa::create($attr + [
            'mitra_jasa_id' => $mitra->id, 'nomor_kontrak' => 'PKS/001/2026', 'nama_kontrak' => 'Sewa Lahan Kargo',
            'jenis_dokumen' => 'PERJANJIAN_KERJA_SAMA', 'tanggal_kontrak' => now()->subMonth(), 'tanggal_mulai' => now()->subMonth(),
            'tanggal_selesai' => now()->addYear(), 'status_kontrak' => 'AKTIF',
        ]);
    }

    public function test_kontrak_aktif_yang_lewat_masa_dan_tanpa_scope_diberi_peringatan(): void
    {
        $user = $this->admin();
        $mitra = MitraJasa::create(['nama_mitra' => 'PT Uji', 'status_aktif' => true]);
        $kontrak = $this->kontrak($mitra, ['tanggal_mulai' => now()->subYear(), 'tanggal_selesai' => now()->subDays(5)]);

        $this->actingAs($user)->get(route('jasa.mitra.kontrak.show', [$mitra, $kontrak]))
            ->assertOk()
            ->assertSee('Masa berlaku sudah lewat, tetapi status masih AKTIF.')
            ->assertSee('Scope layanan masih kosong.')
            ->assertSee('Dokumen kontrak belum diunggah.')
            ->assertSee('Belum ada layanan dalam scope kontrak ini.')
            ->assertDontSee('seluruh layanan aktif', false);
    }

    public function test_scope_dikelompokkan_dan_tagihan_terkait_ditampilkan(): void
    {
        $user = $this->admin();
        $mitra = MitraJasa::create(['nama_mitra' => 'PT Uji', 'status_aktif' => true]);
        $kontrak = $this->kontrak($mitra);

        $root = LayananJasa::create(['nama_layanan' => 'B. Tarif Jasa Terkait Bandar Udara', 'level' => 1, 'is_active' => true, 'is_leaf' => false, 'tipe_layanan' => 'PNBP']);
        $kat = LayananJasa::create(['parent_id' => $root->id, 'nama_layanan' => 'Penggunaan Tanah dan Ruangan', 'level' => 2, 'is_active' => true, 'is_leaf' => false, 'tipe_layanan' => 'PNBP']);
        $tanah = LayananJasa::create(['parent_id' => $kat->id, 'nama_layanan' => 'Penggunaan Tanah UPBU', 'kode_layanan' => 'APT-01', 'level' => 3, 'is_active' => true, 'is_leaf' => true, 'tipe_layanan' => 'PNBP', 'satuan' => 'per m2 per bulan']);
        $kontrak->layananJasa()->attach($tanah->id);

        $pihak = MasterPihak::create(['kategori' => 'PENERIMAAN', 'nama_pihak' => 'PT Uji']);
        $tagihan = TagihanJasa::create([
            'mitra_id' => $pihak->id, 'mitra_jasa_id' => $mitra->id, 'kontrak_mitra_jasa_id' => $kontrak->id,
            'created_by' => $user->id, 'nomor_tagihan' => 'TGH/UJI/001', 'tanggal_tagihan' => now(),
            'tanggal_jatuh_tempo' => now()->addDays(30), 'total_tagihan' => '1500000.00', 'jumlah_dibayar' => '0.00',
            'sisa_tagihan' => '1500000.00', 'status' => 'DRAFT', 'status_pembayaran' => 'belum_dibayar',
        ]);

        $this->actingAs($user)->get(route('jasa.mitra.kontrak.show', [$mitra, $kontrak]))
            ->assertOk()
            ->assertSee('B. Tarif Jasa Terkait Bandar Udara')
            ->assertSee('Penggunaan Tanah dan Ruangan')
            ->assertSee('per m2 per bulan')
            ->assertSee(route('tagihan-jasa.show', $tagihan->id), false)
            ->assertSee('TGH/UJI/001')
            ->assertSee('Rp 1.500.000')
            ->assertSee('Tidak bisa dihapus: sudah dipakai 1 tagihan')
            ->assertDontSee('Scope layanan masih kosong.');
    }

    public function test_dokumen_bisa_dipratinjau_inline_dengan_nama_ramah(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $user = $this->admin();
        $mitra = MitraJasa::create(['nama_mitra' => 'PT Uji', 'status_aktif' => true]);
        $path = UploadedFile::fake()->create('8f3c2a9e-6d41.pdf', 10, 'application/pdf')->store('kontrak', 'local');
        $kontrak = $this->kontrak($mitra, ['file_kontrak' => $path]);

        $this->actingAs($user)->get(route('jasa.mitra.kontrak.show', [$mitra, $kontrak]))
            ->assertOk()
            ->assertSee(route('jasa.mitra.kontrak.download', [$mitra, $kontrak]).'?inline=1', false)
            ->assertDontSee(basename($path));

        $inline = $this->actingAs($user)->get(route('jasa.mitra.kontrak.download', [$mitra, $kontrak]).'?inline=1');
        $inline->assertOk();
        $this->assertStringStartsWith('inline', $inline->headers->get('content-disposition'));
        $this->assertStringContainsString('Kontrak-PKS-001-2026.pdf', $inline->headers->get('content-disposition'));

        $unduh = $this->actingAs($user)->get(route('jasa.mitra.kontrak.download', [$mitra, $kontrak]));
        $this->assertStringStartsWith('attachment', $unduh->headers->get('content-disposition'));
    }
}
