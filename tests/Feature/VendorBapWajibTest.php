<?php

namespace Tests\Feature;

use App\Models\DetailKontrak;
use App\Models\DocumentSignature;
use App\Models\KontrakPengadaan;
use App\Models\KontrakTermin;
use App\Models\MasterPihak;
use App\Models\Tagihan;
use App\Models\User;
use App\Services\DokumenChainService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Aturan dokumen vendor tagihan KONTRAK: BAP wajib diunggah vendor saat TTE;
 * BAPP (dan BAST untuk termin PELUNASAN) dapat menyusul lewat tautan yang sama.
 * Persetujuan per dokumen mengikuti file yang diunggah.
 */
class VendorBapWajibTest extends TestCase
{
    use RefreshDatabase;

    private const VENDOR_TOKEN = 'vendor-group-token-0000000000000000000000';
    private const PEMERIKSA_TOKEN = 'pemeriksa-group-token-000000000000000000';

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake();
        Storage::fake('local');
        Storage::fake('public');
    }

    /** Bangun tagihan KONTRAK + signature TTE meniru hasil sendTte(). */
    private function makeTagihanKontrak(string $jenisTermin = 'PROGRESS'): Tagihan
    {
        $vendor = MasterPihak::create([
            'kategori' => 'PENGELUARAN',
            'jenis_entitas' => 'BADAN_USAHA',
            'nama_pihak' => 'PT Vendor Uji',
            'no_telepon' => '081234500099',
        ]);

        $kontrak = KontrakPengadaan::create([
            'vendor_id' => $vendor->id,
            'nomor_spk' => 'SPK-UJI-' . Str::random(6),
            'tanggal_spk' => now()->toDateString(),
            'nama_pekerjaan' => 'Pekerjaan Uji',
            'nilai_total_kontrak' => 10_000_000,
            'metode_pembayaran' => 'TERMIN',
            'jangka_waktu' => 30,
            'satuan_waktu' => 'HARI',
            'tanggal_mulai' => now()->toDateString(),
            'tanggal_selesai' => now()->addDays(30)->toDateString(),
            'status_kontrak' => 'AKTIF',
        ]);

        $termin = KontrakTermin::create([
            'kontrak_pengadaan_id' => $kontrak->id,
            'jenis_termin' => $jenisTermin,
            'termin_ke' => 1,
            'keterangan_termin' => 'Termin 1',
            'persentase' => 100,
            'nilai_bruto_termin' => 10_000_000,
            'status_termin' => 'SUDAH_DITAGIH',
        ]);

        $tagihan = Tagihan::create([
            'nomor_tagihan' => 'TAG-K/UJI/' . Str::random(4),
            'tipe_tagihan' => 'KONTRAK',
            'pihak_id' => $vendor->id,
            'deskripsi' => 'Tagihan kontrak uji',
            'total_bruto' => 10_000_000,
            'total_potongan' => 0,
            'total_netto' => 10_000_000,
            'status' => 'READY_FOR_SPP',
            'created_by' => User::factory()->create()->id,
        ]);

        DetailKontrak::create([
            'tagihan_id' => $tagihan->id,
            'kontrak_termin_id' => $termin->id,
        ]);

        // Signature meniru sendTte(): vendor satu grup (BAPP, BAP[, BAST]),
        // pemeriksa grup terpisah (BAPP).
        $vendorDocs = $jenisTermin === 'PELUNASAN' ? ['BAPP', 'BAP', 'BAST'] : ['BAPP', 'BAP'];
        foreach ($vendorDocs as $label) {
            $tagihan->documentSignatures()->create([
                'document_label' => $label,
                'role' => 'vendor',
                'signer_name' => 'PT Vendor Uji',
                'signer_phone' => '6281234500099',
                'status' => 'pending',
                'magic_token' => Str::random(40),
                'group_token' => self::VENDOR_TOKEN,
            ]);
        }

        $tagihan->documentSignatures()->create([
            'document_label' => 'BAPP',
            'role' => 'tim_pemeriksa',
            'signer_name' => 'Pemeriksa Uji',
            'signer_phone' => '6281234500098',
            'status' => 'pending',
            'magic_token' => Str::random(40),
            'group_token' => self::PEMERIKSA_TOKEN,
        ]);

        return $tagihan;
    }

    private function vendorSig(Tagihan $tagihan, string $label): DocumentSignature
    {
        return $tagihan->documentSignatures()
            ->where('role', 'vendor')
            ->where('document_label', $label)
            ->firstOrFail();
    }

    public function test_vendor_tidak_bisa_tte_tanpa_mengunggah_bap(): void
    {
        $tagihan = $this->makeTagihanKontrak();

        // Halaman sign mode awal (BAP wajib) harus ter-render tanpa error.
        $this->get(route('public.magic-link.show', self::VENDOR_TOKEN))
            ->assertOk()
            ->assertViewIs('public.magic-link-sign')
            ->assertSee('Wajib Sekarang')
            ->assertSee('Dapat Menyusul');

        $response = $this->post(route('public.magic-link.sign', self::VENDOR_TOKEN));

        $response->assertSessionHasErrors('files.BAP_FINAL_TTD');
        $this->assertSame(0, $tagihan->documentSignatures()->where('status', 'signed')->count(),
            'Tidak boleh ada dokumen yang tertandatangani tanpa unggahan BAP.');
    }

    public function test_tte_dengan_bap_saja_menandatangani_bap_dan_membiarkan_bapp_menyusul(): void
    {
        $tagihan = $this->makeTagihanKontrak();
        $chain = app(DokumenChainService::class);

        $pesanBap = 'Vendor belum menandatangani (TTE) dan mengunggah scan BAP final untuk tagihan kontrak ini.';
        $this->assertContains($pesanBap, $chain->missingDraftPrerequisites($tagihan));

        $response = $this->post(route('public.magic-link.sign', self::VENDOR_TOKEN), [
            'files' => ['BAP_FINAL_TTD' => UploadedFile::fake()->create('bap.pdf', 40, 'application/pdf')],
        ]);

        $response->assertSessionHasNoErrors();
        // Masih ada dokumen menyusul → kembali ke halaman sign, bukan halaman selesai.
        $response->assertRedirect(route('public.magic-link.show', self::VENDOR_TOKEN));

        $this->assertSame('signed', $this->vendorSig($tagihan, 'BAP')->status);
        $this->assertSame('pending', $this->vendorSig($tagihan, 'BAPP')->status);

        $this->assertTrue(
            $tagihan->detailKontrak->arsipDokumen()
                ->where('jenis_dokumen', 'BAP_FINAL_TTD')
                ->where('is_active', true)
                ->exists(),
            'Arsip BAP vendor harus tersimpan aktif.'
        );

        $this->assertNotContains($pesanBap, $chain->missingDraftPrerequisites($tagihan->fresh()),
            'Prasyarat BAP harus terpenuhi setelah vendor mengunggah BAP.');

        // Tautan yang sama tetap terbuka untuk unggah menyusul.
        $this->get(route('public.magic-link.show', self::VENDOR_TOKEN))
            ->assertOk()
            ->assertViewIs('public.magic-link-sign');
    }

    public function test_bapp_menyusul_menunggu_pemeriksa_lalu_melengkapi_tte(): void
    {
        $tagihan = $this->makeTagihanKontrak();

        // Vendor TTE dengan BAP terlebih dahulu.
        $this->post(route('public.magic-link.sign', self::VENDOR_TOKEN), [
            'files' => ['BAP_FINAL_TTD' => UploadedFile::fake()->create('bap.pdf', 40, 'application/pdf')],
        ])->assertSessionHasNoErrors();

        // Upload BAPP sebelum Pemeriksa TTE → ditolak.
        $this->post(route('public.magic-link.upload', self::VENDOR_TOKEN), [
            'jenis_dokumen' => 'BAPP_FINAL_TTD',
            'file' => UploadedFile::fake()->create('bapp.pdf', 40, 'application/pdf'),
        ])->assertSessionHas('error');
        $this->assertSame('pending', $this->vendorSig($tagihan, 'BAPP')->status);

        // Pemeriksa menyetujui BAPP (tanpa file) — perilaku lama tetap.
        $this->post(route('public.magic-link.sign', self::PEMERIKSA_TOKEN))
            ->assertSessionHasNoErrors();
        $this->assertSame(
            'signed',
            $tagihan->documentSignatures()->where('role', 'tim_pemeriksa')->value('status')
        );

        // Vendor mengunggah BAPP menyusul → signature BAPP ikut signed.
        $this->post(route('public.magic-link.upload', self::VENDOR_TOKEN), [
            'jenis_dokumen' => 'BAPP_FINAL_TTD',
            'file' => UploadedFile::fake()->create('bapp.pdf', 40, 'application/pdf'),
        ])->assertSessionHas('success');
        $this->assertSame('signed', $this->vendorSig($tagihan, 'BAPP')->status);

        // Semua dokumen vendor lengkap → tautan menampilkan halaman selesai.
        $this->get(route('public.magic-link.show', self::VENDOR_TOKEN))
            ->assertOk()
            ->assertViewIs('public.magic-link-signed');
    }

    public function test_termin_pelunasan_bast_dapat_menyusul(): void
    {
        $tagihan = $this->makeTagihanKontrak('PELUNASAN');

        $this->post(route('public.magic-link.sign', self::VENDOR_TOKEN), [
            'files' => ['BAP_FINAL_TTD' => UploadedFile::fake()->create('bap.pdf', 40, 'application/pdf')],
        ])->assertSessionHasNoErrors();

        $this->assertSame('signed', $this->vendorSig($tagihan, 'BAP')->status);
        $this->assertSame('pending', $this->vendorSig($tagihan, 'BAST')->status);

        // BAST menyusul lewat tautan yang sama (tanpa syarat pemeriksa).
        $this->post(route('public.magic-link.upload', self::VENDOR_TOKEN), [
            'jenis_dokumen' => 'BAST_FINAL_TTD',
            'file' => UploadedFile::fake()->create('bast.pdf', 40, 'application/pdf'),
        ])->assertSessionHas('success');

        $this->assertSame('signed', $this->vendorSig($tagihan, 'BAST')->status);
    }
}
