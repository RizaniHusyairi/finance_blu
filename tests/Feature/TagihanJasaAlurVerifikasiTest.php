<?php

namespace Tests\Feature;

use App\Models\IntegrationSetting;
use App\Models\LayananJasa;
use App\Models\MasterPihak;
use App\Models\TagihanJasa;
use App\Models\TagihanJasaDetail;
use App\Models\User;
use App\Services\TagihanJasaAlurService;
use Database\Seeders\WorkflowTagihanJasaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Dua alur verifikasi tagihan jasa:
 *  - Alur 1 (Kabandara): Koordinator -> Kasi Jasa -> Kasubag TU -> KPA, terbit otomatis.
 *  - Alur 2 (Kasi Jasa, khusus layanan AERO bila dipilih Super Admin): Koordinator -> Kasi Jasa
 *    -> Kasubag TU, surat otomatis ber-TTE Kasi Jasa, lalu Kasi Jasa yang menerbitkan.
 */
class TagihanJasaAlurVerifikasiTest extends TestCase
{
    use RefreshDatabase;

    private array $u = [];
    private LayananJasa $aero;
    private LayananJasa $nonAero;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->seed(WorkflowTagihanJasaSeeder::class);

        foreach (['Admin Jasa', 'Koordinator Jasa', 'Kepala Seksi Pelayanan dan Kerjasama', 'Kepala Subbagian Keuangan dan Tata Usaha', 'KPA', 'PLT/PLH', 'Super Admin', 'Super Admin Jasa'] as $role) {
            Role::findOrCreate($role, 'web');
            $user = User::factory()->create();
            $user->assignRole($role);
            $this->u[$role] = $user;
        }

        $mk = fn ($a) => LayananJasa::create($a + ['is_active' => true, 'tipe_layanan' => 'PNBP']);
        $rootA = $mk(['nama_layanan' => 'A. Tarif Jasa Kebandarudaraan (Layanan Domestik)', 'level' => 1, 'is_leaf' => false, 'kelompok_pnbp' => 'AERO']);
        $pend = $mk(['parent_id' => $rootA->id, 'nama_layanan' => 'Jasa Pendaratan Pesawat Udara', 'level' => 2, 'is_leaf' => false, 'kelompok_pnbp' => 'AERO']);
        // Daun tanpa kelompok_pnbp: harus mengikuti root (layanan yang ditambahkan belakangan).
        $this->aero = $mk(['parent_id' => $pend->id, 'nama_layanan' => 'a. bobot pesawat s.d 40.000kg', 'level' => 3, 'is_leaf' => true]);
        $rootB = $mk(['nama_layanan' => 'B. Tarif Jasa Terkait Bandar Udara (Layanan Domestik)', 'level' => 1, 'is_leaf' => false, 'kelompok_pnbp' => 'NON_AERO']);
        $this->nonAero = $mk(['parent_id' => $rootB->id, 'nama_layanan' => 'Penggunaan Tanah', 'level' => 2, 'is_leaf' => true, 'kelompok_pnbp' => 'NON_AERO']);

        IntegrationSetting::setValue('btn.enabled', false, 'btn', null, 'boolean');
        foreach (['whatsapp', 'email'] as $kanal) {
            IntegrationSetting::setValue($kanal . '.enabled', false, $kanal, null, 'boolean');
        }
    }

    private function aturAlurAero(string $alur): void
    {
        IntegrationSetting::setValue(TagihanJasaAlurService::SETTING_KEY, $alur, 'tagihan_jasa');
    }

    /** Tagihan berstatus REVISI lalu diajukan (ulang) oleh pembuatnya lewat route resmi. */
    private function ajukan(array $layanan): TagihanJasa
    {
        static $n = 0;
        $pihak = MasterPihak::create(['kategori' => 'PENERIMAAN', 'nama_pihak' => 'PT Uji Alur ' . (++$n)]);
        $mitra = \App\Models\MitraJasa::create(['nama_mitra' => $pihak->nama_pihak, 'email' => 'mitra' . $n . '@uji.test', 'no_telepon' => '081234567890', 'status_aktif' => true]);
        $tagihan = TagihanJasa::create([
            'mitra_id' => $pihak->id, 'mitra_jasa_id' => $mitra->id, 'created_by' => $this->u['Admin Jasa']->id, 'nomor_tagihan' => 'TGH/ALUR/' . $n,
            'tanggal_tagihan' => now(), 'tanggal_jatuh_tempo' => now()->addDays(30),
            'total_tagihan' => '1000000.00', 'jumlah_dibayar' => '0.00', 'sisa_tagihan' => '1000000.00',
            'status' => 'REVISI', 'status_pembayaran' => 'belum_dibayar', 'nomor_va' => '9333300000' . str_pad((string) $n, 4, '0', STR_PAD_LEFT),
        ]);
        foreach ($layanan as $l) {
            TagihanJasaDetail::create(['tagihan_jasa_id' => $tagihan->id, 'layanan_jasa_id' => $l->id, 'qty' => 1, 'harga_satuan' => 500000, 'subtotal' => 500000]);
        }

        $this->actingAs($this->u['Admin Jasa'])->post(route('tagihan-jasa.resubmit', $tagihan->id))->assertSessionHasNoErrors();

        return $tagihan->fresh();
    }

    private function setujui(TagihanJasa $tagihan, string $role): void
    {
        $this->actingAs($this->u[$role])->post(route('tagihan-jasa.approve', $tagihan->id))->assertSessionHas('success');
    }

    public function test_alur_kabandara_tetap_dipakai_bila_pengaturan_kabandara(): void
    {
        $this->aturAlurAero(TagihanJasaAlurService::KABANDARA);
        $tagihan = $this->ajukan([$this->aero]);

        $this->assertSame('KABANDARA', $tagihan->alur_verifikasi);
        $this->assertSame('TAGIHAN_JASA', $tagihan->workflowInstance->definition->kode ?? \App\Models\WorkflowDefinition::find($tagihan->workflowInstance->workflow_definition_id)->kode);
        $this->assertSame(4, $tagihan->workflowInstance->approvals()->count());
    }

    public function test_alur_kasi_jasa_ttd_otomatis_lalu_kasi_menerbitkan(): void
    {
        $this->aturAlurAero(TagihanJasaAlurService::KASI_JASA);
        $tagihan = $this->ajukan([$this->aero]);

        $this->assertSame('KASI_JASA', $tagihan->alur_verifikasi);
        $this->assertSame(3, $tagihan->workflowInstance->approvals()->count());

        $this->setujui($tagihan, 'Koordinator Jasa');
        $this->assertSame('VERIFIKASI_KASI_JASA', $tagihan->fresh()->status);
        $this->setujui($tagihan, 'Kepala Seksi Pelayanan dan Kerjasama');
        $this->assertSame('VERIFIKASI_KASUBAG_TU', $tagihan->fresh()->status);
        $this->setujui($tagihan, 'Kepala Subbagian Keuangan dan Tata Usaha');

        $tagihan->refresh();
        $kasi = $this->u['Kepala Seksi Pelayanan dan Kerjasama'];
        $this->assertSame('DISETUJUI', $tagihan->status, 'alur Kasi Jasa tidak boleh terbit otomatis');
        $this->assertSame('SUDAH_DITANDATANGANI', $tagihan->status_dokumen_pengantar);
        $this->assertSame($kasi->name, $tagihan->pejabat_penandatangan_nama);
        $this->assertSame('Kepala Seksi Pelayanan dan Kerjasama', $tagihan->pejabat_penandatangan_jabatan);
        Storage::disk('local')->assertExists($tagihan->file_surat_pengantar_final);
        $this->assertDatabaseHas('notifications', ['notifiable_id' => $kasi->id]);

        // Antrean Kasi Jasa menampilkan tagihan siap diterbitkan.
        $this->actingAs($kasi)->get(route('verifikasi-tagihan-jasa.index'))->assertOk()
            ->assertSee('Siap Diterbitkan')->assertSee($tagihan->nomor_tagihan);

        // Kasi Jasa menerbitkan (mode VA manual, nomor VA sudah terisi sejak draft).
        $this->actingAs($kasi)->post(route('tagihan-jasa.publish', $tagihan->id), ['wa_tujuan' => '081234567890'])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');
        $this->assertSame('PUBLISHED', $tagihan->fresh()->status);
    }

    public function test_tagihan_campuran_aero_dan_non_aero_memakai_alur_kabandara(): void
    {
        $this->aturAlurAero(TagihanJasaAlurService::KASI_JASA);
        $this->assertSame('KABANDARA', $this->ajukan([$this->aero, $this->nonAero])->alur_verifikasi);
        $this->assertSame('KABANDARA', $this->ajukan([$this->nonAero])->alur_verifikasi);
    }

    public function test_kasi_jasa_tidak_bisa_menerbitkan_tagihan_alur_kabandara(): void
    {
        $this->aturAlurAero(TagihanJasaAlurService::KABANDARA);
        $tagihan = $this->ajukan([$this->aero]);

        $this->actingAs($this->u['Kepala Seksi Pelayanan dan Kerjasama'])
            ->post(route('tagihan-jasa.publish', $tagihan->id), ['wa_tujuan' => '081234567890'])
            ->assertForbidden();
    }

    public function test_hanya_super_admin_yang_mengatur_alur(): void
    {
        $this->actingAs($this->u['Super Admin Jasa'])->get(route('jasa.alur-verifikasi.edit'))->assertForbidden();

        $this->actingAs($this->u['Super Admin'])->get(route('jasa.alur-verifikasi.edit'))->assertOk()
            ->assertSee('Alur 2 — Kasi Jasa');
        $this->actingAs($this->u['Super Admin'])->put(route('jasa.alur-verifikasi.update'), ['alur_aero' => 'KASI_JASA'])
            ->assertRedirect(route('jasa.alur-verifikasi.edit'));
        $this->assertSame('KASI_JASA', app(TagihanJasaAlurService::class)->pengaturanAero());
    }
}
