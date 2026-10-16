<?php

namespace Tests\Feature;

use App\Models\DetailDipa;
use App\Models\MasterCoa;
use App\Models\MasterDipa;
use App\Models\RealisasiAnggaran;
use App\Models\RiwayatRevisiDipa;
use App\Models\Tagihan;
use App\Models\TagihanPerjaldinKomponen;
use App\Models\User;
use App\Services\PerjaldinKomponenService;
use App\Support\DipaBudgetOptionService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Sisa pagu COA = pagu revisi aktif − realisasi lintas revisi DIPA yang sama
 * − komitmen tagihan berjalan (belum SP2D). Item yang dapat dibebani hanya
 * yang aktif, tidak diblokir, pada revisi aktif dari DIPA aktif.
 */
class DipaSisaPaguTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private MasterDipa $dipa;

    private MasterCoa $coa;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('Super Admin', 'web');
        $this->admin = User::factory()->create();
        $this->admin->assignRole('Super Admin');

        $this->dipa = MasterDipa::create([
            'nomor_dipa' => 'DIPA-SISA-UJI',
            'tahun_anggaran' => 2026,
            'tanggal_disahkan' => '2026-01-03',
            'revisi_aktif_ke' => 0,
            'status_aktif' => true,
        ]);
        $this->coa = MasterCoa::create([
            'kode_mak_lengkap' => '525112.UJI',
            'kd_akun' => '525112',
            'nama_akun' => 'Belanja Barang Uji',
            'status_aktif' => true,
        ]);
    }

    private function buatRevisi(int $nomor, bool $aktif): RiwayatRevisiDipa
    {
        return RiwayatRevisiDipa::create([
            'master_dipa_id' => $this->dipa->id,
            'nomor_revisi' => $nomor,
            'tanggal_revisi' => '2026-01-03',
            'total_pagu' => 10000000,
            'is_active' => $aktif,
        ]);
    }

    private function buatItem(RiwayatRevisiDipa $revisi, float $pagu, ?MasterCoa $coa = null, array $atribut = []): DetailDipa
    {
        return DetailDipa::create(array_merge([
            'dipa_revision_id' => $revisi->id,
            'coa_id' => ($coa ?? $this->coa)->id,
            'nilai_pagu' => $pagu,
            'status_aktif' => true,
            'blokir' => false,
        ], $atribut));
    }

    private function buatTagihan(DetailDipa $item, float $netto, string $status = 'DRAFT', string $tipe = 'KONTRAK'): Tagihan
    {
        return Tagihan::create([
            'nomor_tagihan' => 'TAG-' . uniqid(),
            'tipe_tagihan' => $tipe,
            'master_dipa_id' => $this->dipa->id,
            'dipa_revision_item_id' => $item->id,
            'deskripsi' => 'Tagihan uji sisa pagu',
            'total_bruto' => $netto,
            'total_potongan' => 0,
            'total_netto' => $netto,
            'status' => $status,
            'created_by' => $this->admin->id,
        ]);
    }

    private function catatRealisasi(DetailDipa $item, float $nominal, ?Tagihan $sumber = null): RealisasiAnggaran
    {
        return RealisasiAnggaran::create([
            'dipa_revision_item_id' => $item->id,
            'tanggal_pencairan' => '2026-02-01',
            'nomor_bukti' => 'SP2D-' . uniqid(),
            'nominal_cair' => $nominal,
            'status' => 'TERCATAT',
            'sourceable_type' => $sumber ? Tagihan::class : null,
            'sourceable_id' => $sumber?->id,
            'created_by' => $this->admin->id,
        ]);
    }

    public function test_realisasi_revisi_lama_mengurangi_sisa_pagu_revisi_baru(): void
    {
        $revisi0 = $this->buatRevisi(0, false);
        $itemLama = $this->buatItem($revisi0, 1000000);
        $this->catatRealisasi($itemLama, 400000);

        $itemBaru = $this->buatItem($this->buatRevisi(1, true), 1200000);

        $this->assertEquals(400000, $itemBaru->total_realisasi);
        $this->assertEquals(800000, $itemBaru->sisa_pagu);
        $this->assertEquals(0, $itemBaru->realisasi_item);
    }

    public function test_realisasi_dipa_lain_atau_coa_lain_tidak_ikut_terhitung(): void
    {
        $item = $this->buatItem($this->buatRevisi(0, true), 1000000);

        $coaLain = MasterCoa::create(['kode_mak_lengkap' => '521211.UJI', 'kd_akun' => '521211', 'nama_akun' => 'Lain', 'status_aktif' => true]);
        $this->catatRealisasi($this->buatItem($item->dipaRevision, 500000, $coaLain), 300000);

        $dipaLain = MasterDipa::create(['nomor_dipa' => 'DIPA-LAIN', 'tahun_anggaran' => 2026, 'tanggal_disahkan' => '2026-01-03', 'status_aktif' => true]);
        $revisiLain = RiwayatRevisiDipa::create(['master_dipa_id' => $dipaLain->id, 'nomor_revisi' => 0, 'total_pagu' => 0, 'is_active' => true]);
        $this->catatRealisasi(DetailDipa::create(['dipa_revision_id' => $revisiLain->id, 'coa_id' => $this->coa->id, 'nilai_pagu' => 900000, 'status_aktif' => true]), 200000);

        $this->assertEquals(1000000, $item->fresh()->sisa_pagu);
    }

    public function test_tagihan_berjalan_mengurangi_sisa_pagu_sebagai_komitmen(): void
    {
        $item = $this->buatItem($this->buatRevisi(0, true), 1000000);

        $berjalan = $this->buatTagihan($item, 300000);
        $this->buatTagihan($item, 150000, 'BATAL');
        $cair = $this->buatTagihan($item, 200000, 'SELESAI');
        $this->catatRealisasi($item, 200000, $cair);

        $item = $item->fresh();
        $this->assertEquals(300000, $item->total_komitmen);
        $this->assertEquals(200000, $item->total_realisasi);
        $this->assertEquals(500000, $item->sisa_pagu);

        // Validasi pembebanan tagihan itu sendiri tidak menghitung komitmennya.
        $this->assertEquals(800000, $item->sisaPaguTersedia(kecualiTagihanId: $berjalan->id));
    }

    public function test_komponen_perjaldin_berjalan_dihitung_sebagai_komitmen(): void
    {
        $item = $this->buatItem($this->buatRevisi(0, true), 1000000);
        $tagihan = $this->buatTagihan($item, 0, 'DRAFT', 'PERJALDIN');
        $tagihan->update(['dipa_revision_item_id' => null]);
        $komponen = TagihanPerjaldinKomponen::create([
            'tagihan_id' => $tagihan->id,
            'kode_komponen' => 'TIKET',
            'nama_komponen' => 'Tiket',
            'dipa_revision_item_id' => $item->id,
            'total_nominal' => 250000,
        ]);

        $item = $item->fresh();
        $this->assertEquals(250000, $item->total_komitmen);
        $this->assertEquals(1000000, $item->sisaPaguTersedia(kecualiKomponenId: $komponen->id));
        $this->assertEquals(1000000, $item->sisaPaguTersedia(kecualiTagihanId: $tagihan->id));
    }

    public function test_muat_pagu_massal_sama_dengan_hitung_per_item(): void
    {
        $revisi = $this->buatRevisi(0, true);
        $itemA = $this->buatItem($revisi, 1000000);
        $coaB = MasterCoa::create(['kode_mak_lengkap' => '524111.UJI', 'kd_akun' => '524111', 'nama_akun' => 'B', 'status_aktif' => true]);
        $itemB = $this->buatItem($revisi, 700000, $coaB);
        $this->buatTagihan($itemA, 100000);
        $this->catatRealisasi($itemB, 50000);

        $items = DetailDipa::whereIn('id', [$itemA->id, $itemB->id])->orderBy('id')->get();
        DetailDipa::muatPagu($items);

        $this->assertEquals([900000.0, 650000.0], $items->map(fn ($item) => (float) $item->sisa_pagu)->all());
    }

    public function test_hanya_item_revisi_aktif_dipa_aktif_tanpa_blokir_yang_dapat_dipilih(): void
    {
        $itemLama = $this->buatItem($this->buatRevisi(0, false), 1000000);
        $revisiAktif = $this->buatRevisi(1, true);
        $itemAktif = $this->buatItem($revisiAktif, 1000000);
        $coaBlokir = MasterCoa::create(['kode_mak_lengkap' => '522111.UJI', 'kd_akun' => '522111', 'nama_akun' => 'Blokir', 'status_aktif' => true]);
        $itemBlokir = $this->buatItem($revisiAktif, 1000000, $coaBlokir, ['blokir' => true]);

        $this->assertEquals([$itemAktif->id], DetailDipa::dapatDipilih()->pluck('id')->all());

        $this->dipa->update(['status_aktif' => false]);
        $this->assertSame(0, DetailDipa::dapatDipilih()->count());

        $this->expectException(ModelNotFoundException::class);
        DipaBudgetOptionService::resolveActiveItem($itemLama->id);
    }

    public function test_pilih_coa_komponen_menolak_item_diblokir_atau_revisi_lama(): void
    {
        $itemLama = $this->buatItem($this->buatRevisi(0, false), 1000000);
        $tagihan = $this->buatTagihan($itemLama, 0, 'DRAFT', 'PERJALDIN');
        $komponen = TagihanPerjaldinKomponen::create([
            'tagihan_id' => $tagihan->id,
            'kode_komponen' => 'TIKET',
            'nama_komponen' => 'Tiket',
            'total_nominal' => 100000,
        ]);

        try {
            app(PerjaldinKomponenService::class)->updateKomponenCoa($komponen, $itemLama->id);
            $this->fail('Item revisi nonaktif seharusnya ditolak.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('revisi aktif', $e->getMessage());
        }

        $this->assertNull($komponen->fresh()->dipa_revision_item_id);
    }

    public function test_pilih_coa_komponen_menolak_bila_melebihi_sisa_setelah_komitmen(): void
    {
        $item = $this->buatItem($this->buatRevisi(0, true), 1000000);
        $this->buatTagihan($item, 950000);

        $tagihan = $this->buatTagihan($item, 0, 'DRAFT', 'PERJALDIN');
        $tagihan->update(['dipa_revision_item_id' => null]);
        $komponen = TagihanPerjaldinKomponen::create([
            'tagihan_id' => $tagihan->id,
            'kode_komponen' => 'TIKET',
            'nama_komponen' => 'Tiket',
            'total_nominal' => 100000,
        ]);

        $this->expectExceptionMessage('melebihi sisa pagu');
        app(PerjaldinKomponenService::class)->updateKomponenCoa($komponen, $item->id);
    }

    public function test_aktivasi_revisi_memindahkan_tagihan_berjalan_ke_coa_padanan(): void
    {
        $revisi0 = $this->buatRevisi(0, true);
        $itemLama = $this->buatItem($revisi0, 1000000);
        $coaHilang = MasterCoa::create(['kode_mak_lengkap' => '523111.UJI', 'kd_akun' => '523111', 'nama_akun' => 'Hilang', 'status_aktif' => true]);
        $itemHilang = $this->buatItem($revisi0, 500000, $coaHilang);

        $berjalan = $this->buatTagihan($itemLama, 300000);
        $cair = $this->buatTagihan($itemLama, 200000, 'SELESAI');
        $this->catatRealisasi($itemLama, 200000, $cair);
        $tanpaPadanan = $this->buatTagihan($itemHilang, 100000);

        $revisi1 = $this->buatRevisi(1, false);
        $itemBaru = $this->buatItem($revisi1, 1500000);

        $this->actingAs($this->admin)
            ->post(route('dipas.revisions.activate', [$this->dipa, $revisi1]))
            ->assertRedirect(route('dipas.show', $this->dipa))
            ->assertSessionHas('success', fn ($pesan) => str_contains($pesan, '1 tagihan/komponen berjalan dipindahkan'))
            ->assertSessionHas('error', fn ($pesan) => str_contains($pesan, '1 tagihan/komponen'));

        $this->assertEquals($itemBaru->id, $berjalan->fresh()->dipa_revision_item_id);
        $this->assertEquals($itemLama->id, $cair->fresh()->dipa_revision_item_id, 'Tagihan yang sudah cair tetap di item lamanya.');
        $this->assertEquals($itemHilang->id, $tanpaPadanan->fresh()->dipa_revision_item_id);

        // 1.500.000 − realisasi revisi lama 200.000 − komitmen 300.000
        $this->assertEquals(1000000, $itemBaru->fresh()->sisa_pagu);
        $this->assertEquals(1, $this->dipa->fresh()->revisi_aktif_ke);
    }
}
