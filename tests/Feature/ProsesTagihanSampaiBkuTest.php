<?php

namespace Tests\Feature;

use App\Models\Budget;
use App\Models\BukuKasUmum;
use App\Models\DocumentSignature;
use App\Models\DokumenNpi;
use App\Models\DokumenSp2d;
use App\Models\DokumenSpm;
use App\Models\KontrakPengadaan;
use App\Models\KontrakTermin;
use App\Models\MasterPihak;
use App\Models\MasterTarifPajak;
use App\Models\MasterUangHarianPerjaldin;
use App\Models\RealisasiAnggaran;
use App\Models\RekeningBank;
use App\Models\Tagihan;
use App\Models\TransaksiPembukuan;
use App\Models\User;
use App\Models\WorkflowApproval;
use App\Models\WorkflowInstance;
use Database\Seeders\KodeTransaksiSeeder;
use Database\Seeders\SppPerjaldinWorkflowSeeder;
use Database\Seeders\WorkflowDefinitionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * E2E: tiga tipe tagihan (KONTRAK, PERJALDIN, HONORARIUM) dijalankan lewat
 * endpoint HTTP nyata dari pembuatan tagihan, verifikasi 6 verifikator,
 * COA + pajak + persetujuan KPA, rantai SPP→SPM→NPI→SP2D, sampai baris
 * BKU pengeluaran tercatat.
 */
class ProsesTagihanSampaiBkuTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<string, User> */
    private array $users = [];

    private Budget $budget;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake();
        Storage::fake('local');
        Storage::fake('public');

        $this->seed([
            WorkflowDefinitionSeeder::class,
            SppPerjaldinWorkflowSeeder::class,
            KodeTransaksiSeeder::class,
        ]);

        foreach ([
            'pengadaan' => 'Pejabat Pengadaan',
            'operator_perjaldin' => 'Operator Perjaldin',
            'ppabp' => 'PPABP',
            'operator_blu' => 'Operator BLU',
            'ppk' => 'PPK',
            'ppspm' => 'PPSPM',
            'koordinator' => 'Koordinator Keuangan',
            'bp' => 'Bendahara Pengeluaran',
            'bpn' => 'Bendahara Penerimaan',
            'kasubbag' => 'Kepala Subbagian Keuangan dan Tata Usaha',
            'kpa' => 'KPA',
        ] as $key => $role) {
            Role::findOrCreate($role, 'web');
            $user = User::factory()->create();
            $user->assignRole($role);
            $this->users[$key] = $user;
        }

        $this->budget = Budget::create([
            'coa' => '525111',
            'description' => 'Belanja Barang BLU',
            'initial_budget' => 500_000_000,
            'year' => (int) date('Y'),
        ]);

        RekeningBank::create([
            'pemilik_type' => User::class,
            'pemilik_id' => $this->users['bp']->id,
            'nama_bank' => 'Bank Uji',
            'nomor_rekening' => '1234567890',
            'nama_rekening' => 'Bendahara Pengeluaran BLU',
            'jenis_rekening' => 'PENGELUARAN',
            'is_default' => true,
            'status_aktif' => true,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────
    // KONTRAK
    // ─────────────────────────────────────────────────────────────────

    public function test_tagihan_kontrak_pengadaan_sampai_masuk_bku(): void
    {
        $ppn = MasterTarifPajak::create([
            'kode_pajak' => 'PPN-11',
            'jenis_pajak' => 'PPN',
            'persentase' => 11,
            'status_aktif' => true,
        ]);

        $vendor = MasterPihak::create([
            'kategori' => 'PENGELUARAN',
            'jenis_entitas' => 'BADAN_USAHA',
            'nama_pihak' => 'PT Vendor Uji',
            'npwp' => '01.234.567.8-901.000',
            'no_telepon' => '081234500099',
        ]);

        $kontrak = KontrakPengadaan::create([
            'vendor_id' => $vendor->id,
            'ppk_user_id' => $this->users['ppk']->id,
            'master_dipa_id' => $this->budget->dipaRevision->master_dipa_id,
            'nomor_spk' => 'SPK-UJI-001',
            'tanggal_spk' => now()->toDateString(),
            'nama_pekerjaan' => 'Pengadaan ATK Uji',
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
            'jenis_termin' => 'PROGRESS',
            'termin_ke' => 1,
            'keterangan_termin' => 'Termin 1',
            'persentase' => 100,
            'nilai_bruto_termin' => 10_000_000,
            'status_termin' => 'READY_TO_BILL',
        ]);

        // 1. Pejabat Pengadaan membuat draft tagihan termin.
        //    (Form create harus ter-render — memakai partial CSS bersama dengan form edit.)
        $this->actingAs($this->users['pengadaan'])
            ->get(route('tagihan.kontrak.create'))
            ->assertOk();

        $this->ok($this->actingAs($this->users['pengadaan'])->post(route('tagihan.kontrak.store'), [
            'kontrak_pengadaan_id' => $kontrak->id,
            'kontrak_termin_id' => $termin->id,
            'tanggal_bapp' => now()->toDateString(),
            'tanggal_bap' => now()->toDateString(),
            'nomor_invoice' => 'INV-001',
            'tanggal_invoice' => now()->toDateString(),
            'nama_pemeriksa' => 'Pemeriksa Uji',
            'jabatan_pemeriksa' => 'Staf Teknik',
            'wa_pemeriksa' => '081234567890',
            'total_bruto' => 10_000_000,
            'gambar_rab_bapp' => UploadedFile::fake()->image('rab.jpg'),
            'file_invoice' => UploadedFile::fake()->create('invoice.pdf', 100, 'application/pdf'),
            'ppspm_user_id' => $this->users['ppspm']->id,
            'koordinator_keuangan_user_id' => $this->users['koordinator']->id,
            'bendahara_pengeluaran_user_id' => $this->users['bp']->id,
            'bendahara_penerimaan_user_id' => $this->users['bpn']->id,
            'kasubbag_user_id' => $this->users['kasubbag']->id,
        ]));

        $tagihan = Tagihan::where('tipe_tagihan', 'KONTRAK')->latest('id')->firstOrFail();
        $this->assertSame('DRAFT', $tagihan->status);

        // 1b. Selama belum diajukan, data tagihan masih dapat diedit.
        $this->actingAs($this->users['pengadaan'])
            ->get(route('tagihan.kontrak.edit', $tagihan->id))
            ->assertOk();

        $this->ok($this->actingAs($this->users['pengadaan'])->put(route('tagihan.kontrak.update', $tagihan->id), [
            'tanggal_bapp' => now()->toDateString(),
            'tanggal_bap' => now()->toDateString(),
            'nomor_invoice' => 'INV-001-REV',
            'tanggal_invoice' => now()->toDateString(),
            'nama_pemeriksa' => 'Pemeriksa Uji Revisi',
            'jabatan_pemeriksa' => 'Staf Teknik',
            'wa_pemeriksa' => '081234567890',
            'ppspm_user_id' => $this->users['ppspm']->id,
            'koordinator_keuangan_user_id' => $this->users['koordinator']->id,
            'bendahara_pengeluaran_user_id' => $this->users['bp']->id,
            'bendahara_penerimaan_user_id' => $this->users['bpn']->id,
            'kasubbag_user_id' => $this->users['kasubbag']->id,
        ]));
        $this->assertSame('INV-001-REV', $tagihan->fresh()->detailKontrak->nomor_invoice);
        $this->assertSame('Pemeriksa Uji Revisi', $tagihan->fresh()->detailKontrak->nama_pemeriksa);

        // 2. Diajukan — tanpa tahap verifikasi, langsung siap diproses.
        $this->ok($this->actingAs($this->users['pengadaan'])
            ->post(route('tagihan.kontrak.submit', $tagihan->id)));
        $this->assertSame('READY_FOR_SPP', $tagihan->fresh()->status);
        $this->assertSame('SUDAH_DITAGIH', $termin->fresh()->status_termin);

        // Setelah diajukan, tagihan terkunci dari pengeditan.
        $this->actingAs($this->users['pengadaan'])
            ->put(route('tagihan.kontrak.update', $tagihan->id), ['nomor_invoice' => 'INV-ILEGAL'])
            ->assertRedirect(route('tagihan.kontrak.show', $tagihan->id));
        $this->assertSame('INV-001-REV', $tagihan->fresh()->detailKontrak->nomor_invoice,
            'Data tagihan tidak boleh berubah setelah diajukan.');

        // Pengajuan harus men-generate PDF final BAPP & BAP (bukan gagal senyap).
        $finalDocs = $tagihan->fresh()->detailKontrak->arsipDokumen()
            ->whereIn('jenis_dokumen', ['BAPP_FINAL_TTD', 'BAP_FINAL_TTD'])
            ->where('is_active', true)
            ->get();
        $this->assertCount(2, $finalDocs, 'PDF final BAPP/BAP tidak di-generate saat tagihan diajukan.');
        foreach ($finalDocs as $doc) {
            $this->assertTrue(
                Storage::disk($doc->disk ?: 'public')->exists($doc->path_file),
                "File {$doc->jenis_dokumen} tidak tersimpan di storage."
            );
        }

        // 4. PPK memilih COA.
        $this->ok($this->actingAs($this->users['ppk'])->post(route('proses-tagihan.coa', $tagihan->id), [
            'dipa_revision_item_id' => $this->budget->id,
        ]));
        $this->assertSame($this->budget->id, $tagihan->fresh()->dipa_revision_item_id);

        // 5. Operator BLU memilih pajak + unggah faktur pajak.
        $this->ok($this->actingAs($this->users['operator_blu'])->post(route('proses-tagihan.pajak-kontrak', $tagihan->id), [
            'pajak' => [$ppn->id],
            'faktur_pajak' => UploadedFile::fake()->create('faktur.pdf', 50, 'application/pdf'),
        ]));

        $tagihan->refresh();
        $this->assertTrue($tagihan->potonganTagihan()->where('jenis_potongan', 'PAJAK')->exists());
        $this->assertGreaterThan(0, (float) $tagihan->total_potongan);
        $this->assertSame((float) $tagihan->total_bruto - (float) $tagihan->total_potongan, (float) $tagihan->total_netto);

        // 6. KPA menyetujui Standing Instruction — rantai BELUM boleh dibuat
        //    karena vendor belum mengunggah BAP (prasyarat wajib).
        $this->approveKpa($tagihan);
        $this->assertNull($this->chainSpp($tagihan->fresh()),
            'Draft rantai tidak boleh dibuat sebelum vendor mengunggah BAP.');
        $this->assertContains(
            'Vendor belum menandatangani (TTE) dan mengunggah scan BAP final untuk tagihan kontrak ini.',
            app(\App\Services\DokumenChainService::class)->missingDraftPrerequisites($tagihan->fresh())
        );

        // 7. Kirim akses TTE ke vendor, lalu vendor menyetujui dengan mengunggah
        //    BAP (wajib) — BAPP menyusul. Persetujuan BAP memicu draft rantai.
        $this->ok($this->actingAs($this->users['pengadaan'])
            ->post(route('tagihan.kontrak.send-tte', $tagihan->id)));

        $vendorToken = DocumentSignature::where('documentable_id', $tagihan->id)
            ->where('documentable_type', Tagihan::class)
            ->where('role', 'vendor')
            ->value('group_token');
        $this->assertNotNull($vendorToken);

        // Halaman detail tagihan (blok status TTE) harus ter-render tanpa error.
        $this->actingAs($this->users['pengadaan'])
            ->get(route('tagihan.kontrak.show', $tagihan->id))
            ->assertOk();

        $this->ok($this->post(route('public.magic-link.sign', $vendorToken), [
            'files' => ['BAP_FINAL_TTD' => UploadedFile::fake()->create('bap_ttd.pdf', 60, 'application/pdf')],
        ]));

        $vendorSigs = DocumentSignature::where('documentable_id', $tagihan->id)
            ->where('role', 'vendor')->get()->keyBy('document_label');
        $this->assertSame('signed', $vendorSigs['BAP']->status);
        $this->assertSame('pending', $vendorSigs['BAPP']->status, 'BAPP harus bisa menyusul (tetap pending).');

        $this->assertChainDrafted($tagihan);
        $this->assertSame('PROSES_SPP', $tagihan->fresh()->status);

        // Halaman Proses Tagihan ter-render dan menampilkan nama pekerjaan
        // beserta nomor SPK pada kartu ringkasan.
        $this->actingAs($this->users['operator_blu'])
            ->get(route('proses-tagihan.show', $tagihan->id))
            ->assertOk()
            ->assertSee('Pengadaan ATK Uji')
            ->assertSee('SPK-UJI-001')
            ->assertSee('Netto Dibayarkan ke Vendor');

        // Kirim ulang akses TTE tidak boleh membatalkan persetujuan BAP
        // maupun rantai yang sudah dibuat.
        $this->ok($this->actingAs($this->users['pengadaan'])
            ->post(route('tagihan.kontrak.send-tte', $tagihan->id)));
        $this->assertSame('signed', DocumentSignature::where('documentable_id', $tagihan->id)
            ->where('role', 'vendor')->where('document_label', 'BAP')->value('status'));
        $this->assertChainDrafted($tagihan);

        // 8-9. SPP + SPM diajukan & diverifikasi, lalu NPI.
        $this->runChainUntilSiapBayar($tagihan);

        // 10. Bendahara Pengeluaran unggah bukti transfer → SP2D ke PPK.
        $this->ok($this->actingAs($this->users['bp'])->post(route('proses-tagihan.bukti-transfer', $tagihan->id), [
            'bukti_transfer' => UploadedFile::fake()->create('bukti.pdf', 50, 'application/pdf'),
        ]));

        // 11. PPK menerbitkan SP2D. Ada pajak belum NTPN → BKU ditunda.
        $this->approveChainDocument($tagihan, 'sp2d', ['ppk']);

        $tagihan->refresh();
        $this->assertSame('SELESAI', $tagihan->status);
        $this->assertSame(DokumenSp2d::STATUS_EXECUTED, $this->sp2d($tagihan)->status);
        $this->assertSame(0, BukuKasUmum::where('referensi_pengeluaran_id', $tagihan->id)->count(),
            'BKU kontrak seharusnya ditunda sampai pajak disetor (NTPN).');

        // 12. Bendahara Pengeluaran menyetor pajak (billing + NTPN) → BKU terbit.
        $potongan = $tagihan->potonganTagihan()->where('jenis_potongan', 'PAJAK')->firstOrFail();

        $this->ok($this->actingAs($this->users['bp'])->post(route('pajak-potongan.kontrak.billing', $potongan->id), [
            'kode_billing' => '0123456789',
            'file_billing' => UploadedFile::fake()->create('billing.pdf', 20, 'application/pdf'),
        ]));

        $this->ok($this->actingAs($this->users['bp'])->post(route('pajak-potongan.kontrak.ntpn', $potongan->id), [
            'ntpn' => 'NTPN0001',
            'file_bukti_setor' => UploadedFile::fake()->create('bpn.pdf', 20, 'application/pdf'),
        ]));

        $this->assertBkuPosted($tagihan, (float) $tagihan->fresh()->total_bruto);
        $this->assertRealisasiTercatat($tagihan, (float) $tagihan->fresh()->total_netto);
    }

    // ─────────────────────────────────────────────────────────────────
    // PERJALDIN
    // ─────────────────────────────────────────────────────────────────

    public function test_tagihan_perjaldin_sampai_masuk_bku(): void
    {
        $provinsi = MasterUangHarianPerjaldin::create([
            'provinsi' => 'DKI Jakarta',
            'satuan' => 'OH',
            'luar_kota' => 500_000,
            'dalam_kota_lebih_8_jam' => 200_000,
            'diklat' => 150_000,
        ]);

        // 1. Operator Perjaldin membuat tagihan (1 peserta: tiket + uang harian).
        $this->ok($this->actingAs($this->users['operator_perjaldin'])->post(route('perjaldins.store'), [
            'deskripsi' => 'Perjadin Rakor Uji',
            'nomor_urut' => '11',
            'periode_bulan' => (int) date('n'),
            'periode_tahun' => (int) date('Y'),
            'kota_ttd' => 'Banjarmasin',
            'tanggal_ttd' => now()->toDateString(),
            'ppk_user_id' => $this->users['ppk']->id,
            'ppk_nama_snapshot' => $this->users['ppk']->name,
            'ppk_nip_snapshot' => '111111',
            'ppspm_user_id' => $this->users['ppspm']->id,
            'ppspm_nama_snapshot' => $this->users['ppspm']->name,
            'bendahara_penerimaan_user_id' => $this->users['bpn']->id,
            'bendahara_penerimaan_nama_snapshot' => $this->users['bpn']->name,
            'bendahara_pengeluaran_user_id' => $this->users['bp']->id,
            'bendahara_pengeluaran_nama_snapshot' => $this->users['bp']->name,
            'bendahara_pengeluaran_nip_snapshot' => '222222',
            'kasubbag_user_id' => $this->users['kasubbag']->id,
            'kasubbag_nama_snapshot' => $this->users['kasubbag']->name,
            'koordinator_keuangan_user_id' => $this->users['koordinator']->id,
            'koordinator_keuangan_nama_snapshot' => $this->users['koordinator']->name,
            'peserta' => [
                [
                    'nama_pegawai' => 'Pegawai Uji',
                    'nip' => '333333',
                    'no_spt' => 'SPT-001',
                    'no_sppd' => 'SPPD-001',
                    'provinsi_id' => $provinsi->id,
                    'tipe_perjalanan' => 'luar_kota',
                    'spt_file' => UploadedFile::fake()->create('spt.pdf', 20, 'application/pdf'),
                    'tgl_berangkat' => now()->toDateString(),
                    'lama_hari' => 2,
                    'biaya_tiket' => 1_500_000,
                    'uang_harian' => 1_000_000,
                ],
            ],
        ]));

        $tagihan = Tagihan::where('tipe_tagihan', 'PERJALDIN')->latest('id')->firstOrFail();
        $this->assertSame('DRAFT', $tagihan->status);
        $this->assertSame(2_500_000.0, (float) $tagihan->total_netto);
        $this->assertSame(2, $tagihan->komponenPerjaldin()->count());

        // 2. Diajukan ke workflow verifikasi.
        $this->ok($this->actingAs($this->users['operator_perjaldin'])
            ->post(route('perjaldin.workflow.submit', $tagihan->id)));
        $this->assertSame('PENDING_VERIFIKASI_PERJALDIN', $tagihan->fresh()->status);

        // 3. Lima verifikator paralel + Kasubbag final, tiap role lewat halamannya sendiri.
        foreach ([
            'ppk' => 'verifikasi-ppk.perjaldin.approve',
            'ppspm' => 'verifikasi-ppspm.perjaldin.approve',
            'bp' => 'verifikasi-bendahara.perjaldin.approve',
            'bpn' => 'verifikasi-bendahara-penerimaan.perjaldin.approve',
            'koordinator' => 'verifikasi-koordinator.perjaldin.approve',
        ] as $key => $routeName) {
            $this->ok($this->actingAs($this->users[$key])
                ->post(route($routeName, $tagihan->id), ['catatan' => 'Setuju']));
        }
        $this->assertSame('PENDING_KASUBBAG', $tagihan->fresh()->status);

        $this->ok($this->actingAs($this->users['kasubbag'])
            ->post(route('verifikasi-kasubag.perjaldin.approve', $tagihan->id), ['catatan' => 'Final']));
        $this->assertSame('DISETUJUI_PERJALDIN', $tagihan->fresh()->status);

        // 4. PPK memilih COA untuk semua komponen biaya.
        $coaInput = $tagihan->komponenPerjaldin()->pluck('id')
            ->mapWithKeys(fn ($id) => [$id => $this->budget->id])
            ->all();
        $this->ok($this->actingAs($this->users['ppk'])
            ->post(route('proses-tagihan.coa', $tagihan->id), ['coa' => $coaInput]));

        // 5. KPA menyetujui → draft rantai dibuat.
        $this->approveKpa($tagihan);
        $this->assertChainDrafted($tagihan);

        // 6-8. SPP + SPM + NPI.
        $this->runChainUntilSiapBayar($tagihan);

        // 9. Bukti transfer + penerbitan SP2D. Tanpa pajak → BKU langsung terbit.
        $this->ok($this->actingAs($this->users['bp'])->post(route('proses-tagihan.bukti-transfer', $tagihan->id), [
            'bukti_transfer' => UploadedFile::fake()->create('bukti.pdf', 50, 'application/pdf'),
        ]));
        $this->approveChainDocument($tagihan, 'sp2d', ['ppk']);

        $tagihan->refresh();
        $this->assertSame('SELESAI', $tagihan->status);
        $this->assertSame(DokumenSp2d::STATUS_EXECUTED, $this->sp2d($tagihan)->status);
        $this->assertBkuPosted($tagihan, 2_500_000.0);
        $this->assertRealisasiTercatat($tagihan, 2_500_000.0);

        $this->assertSame(
            ['SELESAI', 'SELESAI'],
            $tagihan->komponenPerjaldin()->pluck('status_proses')->all(),
            'Semua komponen perjaldin harus berstatus SELESAI setelah SP2D terbit.'
        );
    }

    // ─────────────────────────────────────────────────────────────────
    // HONORARIUM
    // ─────────────────────────────────────────────────────────────────

    public function test_tagihan_honorarium_sampai_masuk_bku(): void
    {
        MasterTarifPajak::create([
            'kode_pajak' => 'PPH21-TER',
            'jenis_pajak' => 'PPh 21',
            'persentase' => 5,
            'status_aktif' => true,
        ]);

        // 1. PPABP membuat tagihan honorarium (1 personel, honor 2 jt, PPh 100 rb).
        $this->ok($this->actingAs($this->users['ppabp'])->post(route('honorarium.store'), [
            'deskripsi' => 'Honor Narasumber Uji',
            'nomor_urut' => '21',
            'nama_supplier' => 'Tim Kegiatan Uji',
            'items' => [
                [
                    'nama_personel' => 'Narasumber Uji',
                    'nrp_nip' => '444444',
                    'jabatan' => 'Narasumber',
                    'nilai_honor' => 2_000_000,
                    'pph' => 100_000,
                    'rekening' => '9876543210',
                    'jenis_bank' => 'BRI',
                    'nama_rekening' => 'Narasumber Uji',
                ],
            ],
            'ppk_id' => $this->users['ppk']->id,
            'ppspm_id' => $this->users['ppspm']->id,
            'koordinator_keuangan_id' => $this->users['koordinator']->id,
            'bendahara_pengeluaran_id' => $this->users['bp']->id,
            'bendahara_penerimaan_id' => $this->users['bpn']->id,
            'kasubbag_id' => $this->users['kasubbag']->id,
        ]));

        $tagihan = Tagihan::where('tipe_tagihan', 'HONORARIUM')->latest('id')->firstOrFail();
        $this->assertSame('DRAFT', $tagihan->status);
        $this->assertSame(1_900_000.0, (float) $tagihan->total_netto);
        $this->assertTrue($tagihan->potonganTagihan()->where('jenis_potongan', 'PAJAK')->exists());

        // 2. Diajukan ke 6 verifikator.
        $this->ok($this->actingAs($this->users['ppabp'])
            ->post(route('honorarium.submit-verifikasi', $tagihan->id)));
        $this->assertSame('PENDING_VERIFIKASI_HONORARIUM', $tagihan->fresh()->status);

        // 3. Lima verifikator paralel lalu Kasubbag final.
        foreach (['ppk', 'ppspm', 'koordinator', 'bp', 'bpn'] as $key) {
            $this->ok($this->actingAs($this->users[$key])
                ->post(route('verifikasi-tagihan-honorarium.approve', $tagihan->id), ['catatan' => 'Setuju']));
        }
        $this->assertSame('PENDING_KASUBBAG', $tagihan->fresh()->status);

        $this->ok($this->actingAs($this->users['kasubbag'])
            ->post(route('verifikasi-tagihan-honorarium.approve', $tagihan->id), ['catatan' => 'Final']));
        $this->assertSame('DISETUJUI', $tagihan->fresh()->status);

        // 4. PPK memilih COA, KPA menyetujui.
        $this->ok($this->actingAs($this->users['ppk'])->post(route('proses-tagihan.coa', $tagihan->id), [
            'dipa_revision_item_id' => $this->budget->id,
        ]));
        $this->approveKpa($tagihan);
        $this->assertChainDrafted($tagihan);

        // 5-7. SPP + SPM + NPI.
        $this->runChainUntilSiapBayar($tagihan);

        // 8. Bukti transfer + penerbitan SP2D. PPh belum NTPN → BKU ditunda.
        $this->ok($this->actingAs($this->users['bp'])->post(route('proses-tagihan.bukti-transfer', $tagihan->id), [
            'bukti_transfer' => UploadedFile::fake()->create('bukti.pdf', 50, 'application/pdf'),
        ]));
        $this->approveChainDocument($tagihan, 'sp2d', ['ppk']);

        $tagihan->refresh();
        $this->assertSame('SELESAI', $tagihan->status);
        $this->assertSame(0, BukuKasUmum::where('referensi_pengeluaran_id', $tagihan->id)->count(),
            'BKU honorarium seharusnya ditunda sampai PPh disetor (NTPN).');

        // 9. Setor PPh 21 (billing + NTPN) → BKU terbit senilai bruto.
        $potongan = $tagihan->potonganTagihan()->where('jenis_potongan', 'PAJAK')->firstOrFail();

        $this->ok($this->actingAs($this->users['bp'])->post(route('pajak-potongan.honor.billing', $potongan->id), [
            'kode_billing' => '9876543210',
            'file_billing' => UploadedFile::fake()->create('billing.pdf', 20, 'application/pdf'),
        ]));

        $this->ok($this->actingAs($this->users['bp'])->post(route('pajak-potongan.honor.ntpn', $potongan->id), [
            'ntpn' => 'NTPN0002',
            'file_bukti_setor' => UploadedFile::fake()->create('bpn.pdf', 20, 'application/pdf'),
        ]));

        $this->assertBkuPosted($tagihan, 2_000_000.0);
        $this->assertRealisasiTercatat($tagihan, 1_900_000.0);
    }

    // ─────────────────────────────────────────────────────────────────
    // KONTRAK EKSTERNAL (Surat Pesanan e-Purchasing/INAPROC)
    // ─────────────────────────────────────────────────────────────────

    public function test_tagihan_kontrak_eksternal_sampai_masuk_bku(): void
    {
        $ppn = MasterTarifPajak::create([
            'kode_pajak' => 'PPN-12',
            'jenis_pajak' => 'PPN',
            'persentase' => 12,
            'status_aktif' => true,
        ]);

        // 1. PPK membuat draft tagihan dari Surat Pesanan eksternal (vendor
        //    baru + rekening didaftarkan sekaligus dari form).
        $this->actingAs($this->users['ppk'])
            ->get(route('tagihan-kontrak-eksternal.create'))
            ->assertOk();

        $this->ok($this->actingAs($this->users['ppk'])->post(route('tagihan-kontrak-eksternal.store'), [
            'nomor_surat_pesanan' => 'EP-01KNNRTK-UJI',
            'tanggal_surat_pesanan' => now()->toDateString(),
            'sumber' => 'INAPROC',
            'nama_pekerjaan' => 'Pengadaan CCTV Uji',
            'metode_pembayaran' => 'LUMPSUM',
            'nilai_total_kontrak' => 11_100_000,
            'vendor_nama' => 'PT Vendor Eksternal Uji',
            'vendor_npwp' => '71.740.860.3-411.000',
            'vendor_penanggung_jawab' => 'Direktur Uji',
            'vendor_alamat' => 'Tangerang Selatan',
            'vendor_nama_bank' => 'Bank Uji Eksternal',
            'vendor_nomor_rekening' => '9988776655',
            'vendor_nama_rekening' => 'PT Vendor Eksternal Uji',
            'file_surat_pesanan' => UploadedFile::fake()->create('surat_pesanan.pdf', 120, 'application/pdf'),
            'ppk_user_id' => $this->users['ppk']->id,
            'ppspm_user_id' => $this->users['ppspm']->id,
            'koordinator_keuangan_user_id' => $this->users['koordinator']->id,
            'bendahara_pengeluaran_user_id' => $this->users['bp']->id,
            'bendahara_penerimaan_user_id' => $this->users['bpn']->id,
            'kasubbag_user_id' => $this->users['kasubbag']->id,
        ]));

        $tagihan = Tagihan::where('tipe_tagihan', 'KONTRAK_EKSTERNAL')->latest('id')->firstOrFail();
        $this->assertSame('DRAFT', $tagihan->status);
        $this->assertSame('EP-01KNNRTK-UJI', $tagihan->detailKontrakEksternal->nomor_surat_pesanan);
        $this->assertNotNull($tagihan->detailKontrakEksternal->file_surat_pesanan,
            'PDF Surat Pesanan harus tercatat sebagai arsip detail.');
        $this->assertNotNull($tagihan->pihak, 'Vendor baru harus terdaftar sebagai MasterPihak.');
        $this->assertSame('9988776655', $tagihan->pihak->rekening()->first()?->nomor_rekening);

        // Halaman daftar ter-render dengan identitas tagihan.
        $this->actingAs($this->users['ppk'])
            ->get(route('tagihan-kontrak-eksternal.index'))
            ->assertOk()
            ->assertSee('Pengadaan CCTV Uji')
            ->assertSee('PT Vendor Eksternal Uji')
            ->assertSee('EP-01KNNRTK-UJI');

        // Halaman detail ter-render: identitas, vendor + rekening, penanda tangan.
        $this->actingAs($this->users['ppk'])
            ->get(route('tagihan-kontrak-eksternal.show', $tagihan->id))
            ->assertOk()
            ->assertSee('Pengadaan CCTV Uji')
            ->assertSee('PT Vendor Eksternal Uji')
            ->assertSee('9988776655')
            ->assertSee('Ajukan Tagihan');

        // 1b. Masih dapat diedit selama DRAFT.
        $this->actingAs($this->users['ppk'])
            ->get(route('tagihan-kontrak-eksternal.edit', $tagihan->id))
            ->assertOk();

        $this->ok($this->actingAs($this->users['ppk'])->put(route('tagihan-kontrak-eksternal.update', $tagihan->id), [
            'nomor_surat_pesanan' => 'EP-01KNNRTK-UJI',
            'tanggal_surat_pesanan' => now()->toDateString(),
            'sumber' => 'INAPROC',
            'nama_pekerjaan' => 'Pengadaan CCTV dan Jaringan Uji',
            'pihak_id' => $tagihan->pihak_id,
            'ppk_user_id' => $this->users['ppk']->id,
            'ppspm_user_id' => $this->users['ppspm']->id,
            'koordinator_keuangan_user_id' => $this->users['koordinator']->id,
            'bendahara_pengeluaran_user_id' => $this->users['bp']->id,
            'bendahara_penerimaan_user_id' => $this->users['bpn']->id,
            'kasubbag_user_id' => $this->users['kasubbag']->id,
        ]));
        $this->assertSame('Pengadaan CCTV dan Jaringan Uji', $tagihan->fresh()->detailKontrakEksternal->nama_pekerjaan);

        // 2. Diajukan — langsung READY_FOR_SPP tanpa tahap verifikasi tagihan.
        $this->ok($this->actingAs($this->users['ppk'])
            ->post(route('tagihan-kontrak-eksternal.submit', $tagihan->id)));
        $this->assertSame('READY_FOR_SPP', $tagihan->fresh()->status);

        // Setelah diajukan, terkunci dari pengeditan.
        $this->actingAs($this->users['ppk'])
            ->put(route('tagihan-kontrak-eksternal.update', $tagihan->id), ['nama_pekerjaan' => 'ILEGAL'])
            ->assertRedirect(route('tagihan-kontrak-eksternal.show', $tagihan->id));
        $this->assertSame('Pengadaan CCTV dan Jaringan Uji', $tagihan->fresh()->detailKontrakEksternal->nama_pekerjaan);

        // 3. PPK memilih COA; Operator BLU mengisi pajak PPN + faktur.
        $this->ok($this->actingAs($this->users['ppk'])->post(route('proses-tagihan.coa', $tagihan->id), [
            'dipa_revision_item_id' => $this->budget->id,
        ]));

        $this->ok($this->actingAs($this->users['operator_blu'])->post(route('proses-tagihan.pajak-kontrak', $tagihan->id), [
            'pajak' => [$ppn->id],
            'faktur_pajak' => UploadedFile::fake()->create('faktur.pdf', 50, 'application/pdf'),
        ]));

        $tagihan->refresh();
        $this->assertTrue($tagihan->potonganTagihan()->where('jenis_potongan', 'PAJAK')->exists());
        $this->assertGreaterThan(0, (float) $tagihan->total_potongan);

        // Rantai belum boleh terbit sebelum KPA — dan pesan prasyaratnya TIDAK
        // menyebut BAP vendor (gate TTE internal tidak berlaku untuk tipe ini).
        $this->assertNull($this->chainSpp($tagihan->fresh()));
        $missing = app(\App\Services\DokumenChainService::class)->missingDraftPrerequisites($tagihan->fresh());
        $this->assertContains('Standing Instruction belum disetujui KPA.', $missing);
        $this->assertNotContains(
            'Vendor belum menandatangani (TTE) dan mengunggah scan BAP final untuk tagihan kontrak ini.',
            $missing,
            'Tagihan kontrak eksternal tidak boleh terkena gate BAP vendor.'
        );

        // 4. KPA menyetujui → rantai SPP/SPM/NPI/SP2D langsung ter-draft.
        $this->approveKpa($tagihan);
        $this->assertChainDrafted($tagihan);
        $this->assertSame('PROSES_SPP', $tagihan->fresh()->status);

        // Halaman Proses Tagihan menampilkan identitas kontrak eksternal.
        $this->actingAs($this->users['operator_blu'])
            ->get(route('proses-tagihan.show', $tagihan->id))
            ->assertOk()
            ->assertSee('Pengadaan CCTV dan Jaringan Uji')
            ->assertSee('Kontrak Eksternal')
            ->assertSee('EP-01KNNRTK-UJI')
            ->assertSee('PT Vendor Eksternal Uji');

        // 5. Verifikasi SPP/SPM/NPI (bulk + per dokumen) sampai siap bayar.
        $this->runChainUntilSiapBayar($tagihan);

        // 6. Bukti transfer → SP2D terbit. Ada PPN belum NTPN → BKU ditunda.
        $this->ok($this->actingAs($this->users['bp'])->post(route('proses-tagihan.bukti-transfer', $tagihan->id), [
            'bukti_transfer' => UploadedFile::fake()->create('bukti.pdf', 50, 'application/pdf'),
        ]));
        $this->approveChainDocument($tagihan, 'sp2d', ['ppk']);

        $tagihan->refresh();
        $this->assertSame('SELESAI', $tagihan->status);
        $this->assertSame(DokumenSp2d::STATUS_EXECUTED, $this->sp2d($tagihan)->status);
        $this->assertSame(0, BukuKasUmum::where('referensi_pengeluaran_id', $tagihan->id)->count(),
            'BKU kontrak eksternal seharusnya ditunda sampai pajak disetor (NTPN).');

        // 7. Setor PPN (billing + NTPN) via jalur penyetoran pajak kontrak → BKU terbit.
        $potongan = $tagihan->potonganTagihan()->where('jenis_potongan', 'PAJAK')->firstOrFail();

        $this->ok($this->actingAs($this->users['bp'])->post(route('pajak-potongan.kontrak.billing', $potongan->id), [
            'kode_billing' => '1122334455',
            'file_billing' => UploadedFile::fake()->create('billing.pdf', 20, 'application/pdf'),
        ]));

        $this->ok($this->actingAs($this->users['bp'])->post(route('pajak-potongan.kontrak.ntpn', $potongan->id), [
            'ntpn' => 'NTPN0003',
            'file_bukti_setor' => UploadedFile::fake()->create('bpn.pdf', 20, 'application/pdf'),
        ]));

        $this->assertBkuPosted($tagihan, (float) $tagihan->fresh()->total_bruto);
        $this->assertRealisasiTercatat($tagihan, (float) $tagihan->fresh()->total_netto);
    }

    public function test_kontrak_eksternal_termin_menghasilkan_beberapa_tagihan_dengan_uang_muka(): void
    {
        // Kontrak 100 jt: progress 40% + 30% → pelunasan 25% + retensi 5%;
        // uang muka 20 jt (20%) dipotong proporsional dari progress+pelunasan.
        $this->ok($this->actingAs($this->users['ppk'])->post(route('tagihan-kontrak-eksternal.store'), [
            'nomor_surat_pesanan' => 'EP-TERMIN-UJI',
            'tanggal_surat_pesanan' => now()->toDateString(),
            'sumber' => 'INAPROC',
            'nama_pekerjaan' => 'Pengadaan Server Bertermin',
            'metode_pembayaran' => 'TERMIN',
            'nilai_total_kontrak' => 100_000_000,
            'ada_uang_muka' => 1,
            'nilai_uang_muka' => 20_000_000,
            'progress_persentase' => [40, 30],
            'progress_keterangan' => ['Termin 1', 'Termin 2'],
            'gunakan_retensi' => 1,
            'retensi_persentase' => 5,
            'retensi_keterangan' => 'Retensi Pemeliharaan',
            'vendor_nama' => 'PT Vendor Termin',
            'vendor_npwp' => '01.111.222.3-444.000',
            'vendor_nama_bank' => 'Bank Termin',
            'vendor_nomor_rekening' => '5566778899',
            'vendor_nama_rekening' => 'PT Vendor Termin',
            'file_surat_pesanan' => UploadedFile::fake()->create('sp.pdf', 100, 'application/pdf'),
            'ppk_user_id' => $this->users['ppk']->id,
            'ppspm_user_id' => $this->users['ppspm']->id,
            'koordinator_keuangan_user_id' => $this->users['koordinator']->id,
            'bendahara_pengeluaran_user_id' => $this->users['bp']->id,
            'bendahara_penerimaan_user_id' => $this->users['bpn']->id,
            'kasubbag_user_id' => $this->users['kasubbag']->id,
        ]));

        $tagihans = Tagihan::where('tipe_tagihan', 'KONTRAK_EKSTERNAL')
            ->whereHas('detailKontrakEksternal', fn ($q) => $q->where('nomor_surat_pesanan', 'EP-TERMIN-UJI'))
            ->with('detailKontrakEksternal', 'potonganTagihan')
            ->orderBy('id')
            ->get();

        // 2 progress + pelunasan + retensi = 4 tagihan termin.
        $this->assertCount(4, $tagihans);
        $this->assertSame(['PROGRESS', 'PROGRESS', 'PELUNASAN', 'RETENSI'],
            $tagihans->map(fn ($t) => $t->detailKontrakEksternal->jenis_termin)->all());

        // Σ bruto = nilai total kontrak; tiap tagihan punya arsip Surat Pesanan.
        $this->assertSame(100_000_000.0, (float) $tagihans->sum('total_bruto'));
        foreach ($tagihans as $t) {
            $this->assertNotNull($t->detailKontrakEksternal->file_surat_pesanan, 'Tiap termin wajib punya arsip Surat Pesanan.');
            $this->assertSame(4, (int) $t->detailKontrakEksternal->total_termin);
        }

        // Uang muka hanya di PROGRESS/PELUNASAN, total = nilai uang muka.
        $retensi = $tagihans->firstWhere('detailKontrakEksternal.jenis_termin', 'RETENSI');
        $this->assertSame(0.0, (float) $retensi->total_potongan);
        $this->assertFalse($retensi->potonganTagihan()->where('jenis_potongan', 'ANGSURAN_UANG_MUKA')->exists());

        $totalUm = $tagihans->sum(fn ($t) => (float) $t->potonganTagihan()->where('jenis_potongan', 'ANGSURAN_UANG_MUKA')->sum('nominal_potongan'));
        $this->assertEqualsWithDelta(20_000_000, $totalUm, 1);

        // Termin pertama (progress 40jt) dapat potongan UM proporsional (~8.9jt dari 90jt eligible).
        $termin1 = $tagihans->first();
        $this->assertGreaterThan(0, (float) $termin1->total_potongan);
        $this->assertSame(round(40_000_000 - (float) $termin1->total_potongan, 2), (float) $termin1->total_netto);
    }

    public function test_kontrak_eksternal_lumpsum_satu_tagihan_tanpa_uang_muka(): void
    {
        $this->ok($this->actingAs($this->users['ppk'])->post(route('tagihan-kontrak-eksternal.store'), [
            'nomor_surat_pesanan' => 'EP-LUMPSUM-UJI',
            'tanggal_surat_pesanan' => now()->toDateString(),
            'nama_pekerjaan' => 'Pengadaan Lumpsum',
            'metode_pembayaran' => 'LUMPSUM',
            'nilai_total_kontrak' => 50_000_000,
            'vendor_nama' => 'PT Vendor Lumpsum',
            'vendor_nama_bank' => 'Bank Lumpsum',
            'vendor_nomor_rekening' => '111222333',
            'vendor_nama_rekening' => 'PT Vendor Lumpsum',
            'file_surat_pesanan' => UploadedFile::fake()->create('sp.pdf', 100, 'application/pdf'),
            'ppk_user_id' => $this->users['ppk']->id,
            'ppspm_user_id' => $this->users['ppspm']->id,
            'koordinator_keuangan_user_id' => $this->users['koordinator']->id,
            'bendahara_pengeluaran_user_id' => $this->users['bp']->id,
            'bendahara_penerimaan_user_id' => $this->users['bpn']->id,
            'kasubbag_user_id' => $this->users['kasubbag']->id,
        ]));

        $tagihans = Tagihan::where('tipe_tagihan', 'KONTRAK_EKSTERNAL')
            ->whereHas('detailKontrakEksternal', fn ($q) => $q->where('nomor_surat_pesanan', 'EP-LUMPSUM-UJI'))
            ->get();

        $this->assertCount(1, $tagihans);
        $t = $tagihans->first();
        $this->assertSame(50_000_000.0, (float) $t->total_bruto);
        $this->assertSame(0.0, (float) $t->total_potongan);
        $this->assertSame(50_000_000.0, (float) $t->total_netto);
        $this->assertSame('PELUNASAN', $t->detailKontrakEksternal->jenis_termin);
    }

    // ─────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────

    /** Pastikan aksi HTTP sukses: redirect tanpa error validasi/flash error. */
    private function ok(TestResponse $response): TestResponse
    {
        $response->assertSessionHasNoErrors();

        $flashError = session('error');
        $this->assertNull($flashError, "Aksi mengembalikan flash error: {$flashError}");

        $exception = $response->exception
            ? get_class($response->exception) . ': ' . $response->exception->getMessage()
            : '(tanpa exception)';

        $this->assertTrue(
            $response->isRedirection() || $response->isSuccessful(),
            "Aksi mengembalikan status {$response->getStatusCode()}. {$exception}"
        );

        return $response;
    }

    /** Simulasikan SI terkirim ke KPA lalu KPA menyetujui lewat endpoint-nya. */
    private function approveKpa(Tagihan $tagihan): void
    {
        $tagihan->update(['kpa_approval_status' => 'PENDING_KPA']);

        $this->ok($this->actingAs($this->users['kpa'])->post(route('kpa.approval.process', $tagihan->id), [
            'action' => 'approve',
            'notes' => 'Disetujui KPA',
        ]));

        $this->assertSame('APPROVED', $tagihan->fresh()->kpa_approval_status);
    }

    private function assertChainDrafted(Tagihan $tagihan): void
    {
        $spp = $this->chainSpp($tagihan);
        $this->assertNotNull($spp, 'Draft SPP tidak dibuat setelah seluruh prasyarat terpenuhi.');
        $this->assertNotNull($spp->spm, 'Draft SPM tidak dibuat.');
        $this->assertNotNull($spp->spm->npi, 'Draft NPI tidak dibuat.');
        $this->assertNotNull($spp->spm->npi->sp2d, 'Draft SP2D tidak dibuat.');
    }

    /**
     * Verifikasi SPP, SPM, NPI. Ketiganya sudah diajukan otomatis & bersamaan
     * saat draft rantai dibuat — tanpa tombol "Ajukan". Kasubbag & Koordinator
     * (verifikator ketiga dokumen) memakai verifikasi massal satu tombol;
     * verifikator lain per dokumen. Urutan verifikasi bebas (independen).
     */
    private function runChainUntilSiapBayar(Tagihan $tagihan): void
    {
        $spp = $this->chainSpp($tagihan->fresh());
        $this->assertSame('Menunggu Verifikasi', $spp->status, 'SPP harus auto-diajukan.');
        $this->assertSame(DokumenSpm::STATUS_MENUNGGU_VERIFIKASI, $spp->spm->status, 'SPM harus auto-diajukan.');
        $this->assertSame(DokumenNpi::STATUS_MENUNGGU_VERIFIKASI, $spp->spm->npi->status, 'NPI harus auto-diajukan.');

        // Banner verifikasi massal tampil bagi verifikator multi-dokumen.
        $this->actingAs($this->users['kasubbag'])
            ->get(route('proses-tagihan.show', $tagihan->id))
            ->assertOk()
            ->assertSee('Setujui Semua');

        // User tanpa approval pending ditolak dengan pesan yang jelas.
        $this->actingAs($this->users['operator_blu'])
            ->post(route('proses-tagihan.dokumen.setujui-semua', $tagihan->id));
        $this->assertNotNull(session('error'), 'Bulk tanpa approval pending harus ditolak.');

        // Kasubbag & Koordinator menyetujui seluruh dokumennya sekali klik.
        foreach (['kasubbag', 'koordinator'] as $key) {
            $this->ok($this->actingAs($this->users[$key])
                ->post(route('proses-tagihan.dokumen.setujui-semua', $tagihan->id), [
                    'catatan' => 'Setuju (verifikasi massal).',
                ]));
        }

        foreach (['spp', 'spm', 'npi'] as $jenis) {
            $instance = $this->latestInstance($this->chainDocument($tagihan, $jenis));
            foreach (['kasubbag', 'koordinator'] as $key) {
                $this->assertSame(1, $instance->approvals()
                    ->where('acted_by_user_id', $this->users[$key]->id)
                    ->where('status', 'APPROVED')
                    ->count(), "Approval {$key} pada {$jenis} harus APPROVED lewat verifikasi massal.");
            }
        }

        // Verifikator sisanya menyetujui per dokumen seperti biasa.
        $this->approveChainDocument($tagihan, 'npi', ['bpn', 'ppk']);
        $this->approveChainDocument($tagihan, 'spp', ['ppk']);
        $this->approveChainDocument($tagihan, 'spm', ['ppspm']);

        // Smoke: PDF SPM & NPI harus ter-render (penanda tangan dari snapshot tagihan).
        $spp = $this->chainSpp($tagihan->fresh());
        $this->actingAs($this->users['ppspm'])
            ->get(route('spms.cetak-pdf', $spp->spm->id))
            ->assertOk();
        $this->actingAs($this->users['ppk'])
            ->get(route('npis.cetak-pdf', $spp->spm->npi->id))
            ->assertOk();
    }

    /** Setujui satu dokumen rantai oleh sederet role melalui endpoint aksi terpadu. */
    private function approveChainDocument(Tagihan $tagihan, string $jenis, array $userKeys): void
    {
        foreach ($userKeys as $key) {
            $document = $this->chainDocument($tagihan, $jenis);
            $approval = $this->pendingApprovalFor($document, $this->users[$key]);

            $this->assertNotNull(
                $approval,
                "Tidak ada approval PENDING {$jenis} untuk {$this->users[$key]->name}."
            );

            $this->ok($this->actingAs($this->users[$key])
                ->post(route('proses-tagihan.dokumen.aksi', [$tagihan->id, $jenis]), [
                    'aksi' => 'approve',
                    'approval_id' => $approval->id,
                    'catatan' => 'Setuju',
                ]));
        }

        $document = $this->chainDocument($tagihan, $jenis);
        $instance = $this->latestInstance($document);
        $this->assertSame('APPROVED', $instance?->status,
            "Workflow {$jenis} belum APPROVED setelah semua verifikator menyetujui.");
    }

    private function chainSpp(Tagihan $tagihan)
    {
        $query = $tagihan->spps()->with('spm.npi.sp2d');
        if ($tagihan->tipe_tagihan === 'PERJALDIN') {
            $query->whereNull('tagihan_perjaldin_komponen_id');
        }

        return $query->latest('id')->first();
    }

    private function chainDocument(Tagihan $tagihan, string $jenis)
    {
        $spp = $this->chainSpp($tagihan->fresh());

        return match ($jenis) {
            'spp' => $spp,
            'spm' => $spp?->spm,
            'npi' => $spp?->spm?->npi,
            'sp2d' => $spp?->spm?->npi?->sp2d,
        };
    }

    private function sp2d(Tagihan $tagihan): DokumenSp2d
    {
        return $this->chainDocument($tagihan, 'sp2d');
    }

    private function latestInstance($document): ?WorkflowInstance
    {
        return WorkflowInstance::where('workflowable_type', $document->getMorphClass())
            ->where('workflowable_id', $document->getKey())
            ->latest('id')
            ->first();
    }

    private function pendingApprovalFor($document, User $user): ?WorkflowApproval
    {
        $instance = $this->latestInstance($document);
        if (! $instance || $instance->status !== 'IN_PROGRESS') {
            return null;
        }

        $variants = [
            'PPK' => ['PPK'],
            'PPSPM' => ['PPSPM'],
            'Koordinator Keuangan' => ['Koordinator Keuangan', 'KOORDINATOR_KEUANGAN'],
            'Kepala Subbagian Keuangan dan Tata Usaha' => ['Kepala Subbagian Keuangan dan Tata Usaha', 'KASUBBAG'],
            'Bendahara Penerimaan' => ['Bendahara Penerimaan', 'BENDAHARA_PENERIMAAN'],
            'Bendahara Pengeluaran' => ['Bendahara Pengeluaran', 'BENDAHARA_PENGELUARAN'],
        ];

        $roleCodes = [];
        foreach ($user->getRoleNames() as $name) {
            foreach ($variants[$name] ?? [$name] as $code) {
                $roleCodes[] = $code;
            }
        }

        return $instance->approvals()
            ->where('urutan_step', $instance->step_saat_ini)
            ->where('status', 'PENDING')
            ->where(function ($q) use ($user, $roleCodes) {
                $q->where('assigned_user_id', $user->id)
                    ->orWhere(function ($q2) use ($roleCodes) {
                        $q2->whereNull('assigned_user_id')->whereIn('role_code', $roleCodes);
                    });
            })
            ->first();
    }

    /** Assert baris BKU induk + jurnal SILABI I2 tercatat dengan nominal benar. */
    private function assertBkuPosted(Tagihan $tagihan, float $expectedNominal): void
    {
        $bku = BukuKasUmum::where('referensi_pengeluaran_id', $tagihan->id)->first();

        $this->assertNotNull($bku, "Tagihan {$tagihan->nomor_tagihan} tidak tercatat di BKU.");
        $this->assertSame('KREDIT_KELUAR', $bku->arus_kas);
        $this->assertSame('PENGELUARAN', $bku->peran instanceof \BackedEnum ? $bku->peran->value : (string) $bku->peran);
        $this->assertSame(1, (int) $bku->kode_buku, 'Baris referensi tagihan harus berada di buku induk (BKU).');
        $this->assertSame('I2', $bku->kode_transaksi);
        $this->assertSame($expectedNominal, (float) $bku->nominal);
        $this->assertSame($this->sp2d($tagihan)->nomor_sp2d, $bku->nomor_bukti);

        $trx = TransaksiPembukuan::where('referensi_type', Tagihan::class)
            ->where('referensi_id', $tagihan->id)
            ->first();
        $this->assertNotNull($trx, 'Jurnal SILABI (transaksi_pembukuan) tidak dibuat.');
        $this->assertSame('I2', $trx->kode_transaksi);

        // Distribusi kode I2: keluar di BKU(1), Bank(3), LS Bendahara(6); masuk di Pengesahan(11).
        $rows = BukuKasUmum::where('transaksi_pembukuan_id', $trx->id)
            ->get()
            ->map(fn ($row) => (int) $row->kode_buku . ':' . $row->arus_kas)
            ->sort()
            ->values()
            ->all();
        $this->assertSame(['11:DEBIT_MASUK', '1:KREDIT_KELUAR', '3:KREDIT_KELUAR', '6:KREDIT_KELUAR'], $rows);

        // Idempoten: hanya satu baris BKU induk untuk tagihan ini.
        $this->assertSame(1, BukuKasUmum::where('referensi_pengeluaran_id', $tagihan->id)->count());
    }

    private function assertRealisasiTercatat(Tagihan $tagihan, float $expectedTotal): void
    {
        $total = (float) RealisasiAnggaran::where('dokumen_sp2d_id', $this->sp2d($tagihan)->id)
            ->where('status', 'TERCATAT')
            ->sum('nominal_cair');

        $this->assertSame($expectedTotal, $total, 'Realisasi anggaran tidak sesuai nominal tagihan.');
    }
}
