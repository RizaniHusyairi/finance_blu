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
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Opsi unggah manual: staf mengunggah scan TTD basah vendor sebagai
 * alternatif jalur TTE online — untuk dokumen BA tagihan (BAP/BAPP/BAST)
 * maupun dokumen final kontrak (SPK/SPMK/Ringkasan Kontrak).
 */
class ManualTtdVendorTest extends TestCase
{
    use RefreshDatabase;

    private User $pengadaan;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake();
        Storage::fake('local');
        Storage::fake('public');

        Role::findOrCreate('Pejabat Pengadaan', 'web');
        $this->pengadaan = User::factory()->create();
        $this->pengadaan->assignRole('Pejabat Pengadaan');
    }

    private function makeKontrak(string $statusKontrak = 'AKTIF', bool $ppkApproved = true): KontrakPengadaan
    {
        $vendor = MasterPihak::create([
            'kategori' => 'PENGELUARAN',
            'jenis_entitas' => 'BADAN_USAHA',
            'nama_pihak' => 'PT Vendor Uji',
            'no_telepon' => '081234500099',
        ]);

        return KontrakPengadaan::create([
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
            'status_kontrak' => $statusKontrak,
            'ppk_approved_at' => $ppkApproved ? now() : null,
        ]);
    }

    private function makeTagihanKontrak(string $jenisTermin = 'PROGRESS'): Tagihan
    {
        $kontrak = $this->makeKontrak();

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
            'pihak_id' => $kontrak->vendor_id,
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

        return $tagihan;
    }

    public function test_unggah_manual_bap_menandatangani_vendor_dan_memenuhi_gate_spp(): void
    {
        $tagihan = $this->makeTagihanKontrak();
        $chain = app(DokumenChainService::class);
        $pesanBap = 'Vendor belum menandatangani (TTE) dan mengunggah scan BAP final untuk tagihan kontrak ini.';

        $this->assertContains($pesanBap, $chain->missingDraftPrerequisites($tagihan));

        // Tanpa pernah mengirim akses TTE — unggah manual berdiri sendiri.
        $response = $this->actingAs($this->pengadaan)->post(route('tagihan.kontrak.ttd-manual', $tagihan->id), [
            'dokumen' => ['BAP_FINAL_TTD' => UploadedFile::fake()->create('bap.pdf', 40, 'application/pdf')],
            'tanggal_ttd_vendor' => now()->toDateString(),
            'keterangan' => 'TTD saat serah terima di lokasi.',
            'pernyataan' => 1,
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertNull(session('error'), (string) session('error'));

        $bapSig = $tagihan->documentSignatures()->where('role', 'vendor')->where('document_label', 'BAP')->firstOrFail();
        $this->assertSame('signed', $bapSig->status);
        $this->assertSame('MANUAL', $bapSig->signed_via);
        $this->assertSame($this->pengadaan->id, $bapSig->signed_by_user_id);

        // Baris signature BAPP ikut dibuat (pending) agar vendor tetap bisa TTE online menyusul.
        $this->assertSame('pending', $tagihan->documentSignatures()
            ->where('role', 'vendor')->where('document_label', 'BAPP')->value('status'));

        $arsip = $tagihan->detailKontrak->arsipDokumen()
            ->where('jenis_dokumen', 'BAP_FINAL_TTD')->where('is_active', true)->firstOrFail();
        $this->assertSame($this->pengadaan->id, $arsip->uploaded_by, 'Asal manual terbaca dari uploaded_by staf.');

        $this->assertNotContains($pesanBap, $chain->missingDraftPrerequisites($tagihan->fresh()));

        $this->assertTrue($tagihan->logs()->where('aksi', 'UPLOAD_MANUAL_TTD')->exists());
    }

    public function test_unggah_manual_wajib_pernyataan_dan_bap(): void
    {
        $tagihan = $this->makeTagihanKontrak();

        // Tanpa pernyataan tanggung jawab → ditolak validasi.
        $this->actingAs($this->pengadaan)->post(route('tagihan.kontrak.ttd-manual', $tagihan->id), [
            'dokumen' => ['BAP_FINAL_TTD' => UploadedFile::fake()->create('bap.pdf', 40, 'application/pdf')],
            'tanggal_ttd_vendor' => now()->toDateString(),
        ])->assertSessionHasErrors('pernyataan');

        // Tanpa file BAP (belum signed) → ditolak validasi.
        $this->actingAs($this->pengadaan)->post(route('tagihan.kontrak.ttd-manual', $tagihan->id), [
            'tanggal_ttd_vendor' => now()->toDateString(),
            'pernyataan' => 1,
        ])->assertSessionHasErrors('dokumen.BAP_FINAL_TTD');

        $this->assertSame(0, $tagihan->documentSignatures()->where('status', 'signed')->count());
    }

    public function test_bapp_manual_ikut_menandai_ttd_tim_pemeriksa(): void
    {
        // Berbeda dengan jalur TTE online, unggah manual BAPP TIDAK menunggu
        // TTE Tim Pemeriksa: scan dianggap sudah memuat TTD basah vendor DAN
        // Tim Pemeriksa sekaligus. Tak ada baris pemeriksa dibuat lebih dulu.
        $tagihan = $this->makeTagihanKontrak();

        $this->actingAs($this->pengadaan)->post(route('tagihan.kontrak.ttd-manual', $tagihan->id), [
            'dokumen' => [
                'BAP_FINAL_TTD' => UploadedFile::fake()->create('bap.pdf', 40, 'application/pdf'),
                'BAPP_FINAL_TTD' => UploadedFile::fake()->create('bapp.pdf', 40, 'application/pdf'),
            ],
            'tanggal_ttd_vendor' => now()->toDateString(),
            'pernyataan' => 1,
        ])->assertSessionHasNoErrors();

        $this->assertNull(session('error'), (string) session('error'));

        // BAP + BAPP vendor ditandai MANUAL.
        $vendorSigned = $tagihan->documentSignatures()
            ->where('role', 'vendor')->where('status', 'signed')
            ->pluck('signed_via', 'document_label');
        $this->assertSame('MANUAL', $vendorSigned['BAP'] ?? null);
        $this->assertSame('MANUAL', $vendorSigned['BAPP'] ?? null);

        // Persetujuan Tim Pemeriksa BAPP ikut ditandai MANUAL (dibuat otomatis).
        $pemeriksaSig = $tagihan->documentSignatures()
            ->where('role', 'tim_pemeriksa')->where('document_label', 'BAPP')->first();
        $this->assertNotNull($pemeriksaSig, 'Baris persetujuan Tim Pemeriksa harus dibuat otomatis.');
        $this->assertSame('signed', $pemeriksaSig->status);
        $this->assertSame('MANUAL', $pemeriksaSig->signed_via);
        $this->assertSame($this->pengadaan->id, $pemeriksaSig->signed_by_user_id);
    }

    public function test_bapp_manual_menandai_pemeriksa_yang_sudah_ada(): void
    {
        // Bila akses TTE sudah pernah dikirim (baris pemeriksa ada, masih
        // pending), unggah manual BAPP tetap menandainya signed tanpa duplikat.
        $tagihan = $this->makeTagihanKontrak();
        $tagihan->documentSignatures()->create([
            'document_label' => 'BAPP',
            'role' => 'tim_pemeriksa',
            'signer_name' => 'Pemeriksa Uji',
            'signer_phone' => '6281234500098',
            'status' => 'pending',
            'magic_token' => Str::random(40),
            'group_token' => Str::random(40),
        ]);

        $this->actingAs($this->pengadaan)->post(route('tagihan.kontrak.ttd-manual', $tagihan->id), [
            'dokumen' => [
                'BAP_FINAL_TTD' => UploadedFile::fake()->create('bap.pdf', 40, 'application/pdf'),
                'BAPP_FINAL_TTD' => UploadedFile::fake()->create('bapp.pdf', 40, 'application/pdf'),
            ],
            'tanggal_ttd_vendor' => now()->toDateString(),
            'pernyataan' => 1,
        ])->assertSessionHasNoErrors();

        $pemeriksaSigs = $tagihan->documentSignatures()
            ->where('role', 'tim_pemeriksa')->where('document_label', 'BAPP')->get();
        $this->assertCount(1, $pemeriksaSigs, 'Tidak boleh ada baris pemeriksa duplikat.');
        $this->assertSame('signed', $pemeriksaSigs->first()->status);
        $this->assertSame('MANUAL', $pemeriksaSigs->first()->signed_via);
    }

    public function test_unggah_manual_dokumen_kontrak_membuka_gate_buat_tagihan(): void
    {
        $kontrak = $this->makeKontrak();
        $this->assertFalse($kontrak->hasVendorUploadedFinalDocs());

        // Tanpa pernyataan → ditolak.
        $this->actingAs($this->pengadaan)->post(route('contracts.final-docs-manual', $kontrak->id), [
            'file_spk_final' => UploadedFile::fake()->create('spk.pdf', 40, 'application/pdf'),
            'tanggal_ttd_vendor' => now()->toDateString(),
        ])->assertSessionHasErrors('pernyataan');

        $response = $this->actingAs($this->pengadaan)->post(route('contracts.final-docs-manual', $kontrak->id), [
            'file_spk_final' => UploadedFile::fake()->create('spk.pdf', 40, 'application/pdf'),
            'file_spmk_final' => UploadedFile::fake()->create('spmk.pdf', 40, 'application/pdf'),
            'file_ringkasan_final' => UploadedFile::fake()->create('ringkasan.pdf', 40, 'application/pdf'),
            'tanggal_ttd_vendor' => now()->toDateString(),
            'pernyataan' => 1,
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertNull(session('error'), (string) session('error'));

        $kontrak->refresh()->load('arsipDokumen');
        $this->assertTrue($kontrak->hasVendorUploadedFinalDocs(),
            'Gate "Buat Tagihan" harus terbuka setelah tiga dokumen final diunggah manual.');

        // Asal manual terbaca dari uploaded_by staf (portal vendor = null).
        $kontrak->arsipDokumen
            ->whereIn('jenis_dokumen', ['SPK_FINAL_TTD', 'SPMK_FINAL_TTD', 'RINGKASAN_KONTRAK_FINAL_TTD'])
            ->where('is_active', true)
            ->each(fn ($arsip) => $this->assertSame($this->pengadaan->id, $arsip->uploaded_by));

        $this->assertTrue(
            \App\Models\LogStatusDokumen::where('dokumen_type', KontrakPengadaan::class)
                ->where('dokumen_id', $kontrak->id)
                ->where('aksi', 'UPLOAD_MANUAL_TTD')
                ->exists()
        );

        // Halaman Detail SPK (blok metode + lencana Manual) ter-render tanpa error.
        $this->actingAs($this->pengadaan)
            ->get(route('contracts.show', $kontrak->id))
            ->assertOk()
            ->assertSee('Dokumen Final Bertanda Tangan Vendor');
    }

    public function test_pdf_ba_kontrak_tanpa_dipa_tetap_ter_render(): void
    {
        // Regresi: kontrak boleh dibuat tanpa DIPA (master_dipa_id nullable) —
        // template BA dulu membaca $kontrak->dipa->tanggal_disahkan tanpa
        // null-safe sehingga pratinjau/finalisasi PDF meledak 500.
        $tagihan = $this->makeTagihanKontrak();

        $html = app(\App\Http\Controllers\TagihanController::class)
            ->exportPdfKontrakHtml($tagihan->id, 'BAP', false);

        $this->assertIsString($html);
        $this->assertStringContainsString('DIPA Kantor UPBU', $html);
    }

    public function test_unggah_manual_dokumen_kontrak_ditolak_sebelum_ppk_menyetujui(): void
    {
        $kontrak = $this->makeKontrak('PENDING_REVIEW', false);

        $this->actingAs($this->pengadaan)->post(route('contracts.final-docs-manual', $kontrak->id), [
            'file_spk_final' => UploadedFile::fake()->create('spk.pdf', 40, 'application/pdf'),
            'tanggal_ttd_vendor' => now()->toDateString(),
            'pernyataan' => 1,
        ]);

        $this->assertNotNull(session('error'));
        $this->assertFalse($kontrak->fresh()->load('arsipDokumen')->hasVendorUploadedFinalDocs());
    }
}
