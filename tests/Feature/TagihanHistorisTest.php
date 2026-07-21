<?php

namespace Tests\Feature;

use App\Models\BukuKasUmum;
use App\Models\DetailDipa;
use App\Models\MasterCoa;
use App\Models\MasterDipa;
use App\Models\MasterPihak;
use App\Models\RiwayatRevisiDipa;
use App\Models\Tagihan;
use App\Models\User;
use App\Services\DocumentNumberService;
use Database\Seeders\KodeTransaksiSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TagihanHistorisTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private DetailDipa $item;

    private MasterPihak $vendor;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        $this->seed(KodeTransaksiSeeder::class);

        Role::findOrCreate('Super Admin', 'web');
        Role::findOrCreate('PPK', 'web');
        Role::findOrCreate('Bendahara Pengeluaran', 'web');
        $this->admin = User::factory()->create();
        $this->admin->assignRole('Super Admin');

        // Rekening sumber wajib ada untuk posting BKU pengeluaran.
        $bendahara = User::factory()->create();
        $bendahara->assignRole('Bendahara Pengeluaran');
        \App\Models\RekeningBank::create([
            'pemilik_type' => User::class,
            'pemilik_id' => $bendahara->id,
            'nama_bank' => 'Bank Uji',
            'nomor_rekening' => '1234567890',
            'nama_rekening' => 'Bendahara Pengeluaran BLU',
            'jenis_rekening' => 'PENGELUARAN',
            'is_default' => true,
            'status_aktif' => true,
        ]);

        $dipa = MasterDipa::create([
            'nomor_dipa' => 'DIPA-022.05.2.288745/2026',
            'tahun_anggaran' => 2026,
            'tanggal_disahkan' => '2025-12-01',
            'revisi_aktif_ke' => 0,
            'status_aktif' => true,
        ]);

        $revision = RiwayatRevisiDipa::create([
            'master_dipa_id' => $dipa->id,
            'nomor_revisi' => 0,
            'tanggal_revisi' => '2025-12-01',
            'total_pagu' => 300000000,
            'is_active' => true,
        ]);

        $coa = MasterCoa::create([
            'kd_akun' => '525114',
            'kode_mak_lengkap' => 'GA.4647.CDE.001.051.A.525114.00002',
            'nama_akun' => 'Pemeliharaan Fasilitas Sisi Udara',
            'sumber_dana' => 'BLU',
            'status_aktif' => true,
        ]);

        $this->item = DetailDipa::create([
            'dipa_revision_id' => $revision->id,
            'coa_id' => $coa->id,
            'nilai_pagu' => 300000000,
            'status_aktif' => true,
        ]);

        $this->vendor = MasterPihak::create([
            'kategori' => 'PENGELUARAN',
            'jenis_entitas' => 'BADAN_USAHA',
            'kode_pihak' => 'VDR-TEST-1',
            'npwp' => '0607734639741000',
            'nama_pihak' => 'CV. GARUDA KARYA BERSAMA',
            'status_aktif' => true,
        ]);
    }

    /** Payload fixture dari berkas nyata 0001 (Pemotongan Rumput Sisi Udara). */
    private function payloadBerkas0001(array $override = []): array
    {
        return array_merge([
            'tipe_tagihan' => 'KONTRAK_EKSTERNAL',
            'nomor_tagihan' => 'HIS/2026/0001',
            'deskripsi' => 'Pembayaran Belanja Barang Pekerjaan Pemotongan Rumput Sisi Udara Tahap I : 1 (Satu) Paket',
            'pihak_id' => $this->vendor->id,
            'dipa_revision_item_id' => $this->item->id,
            'total_bruto' => 196697855,
            'potongan_nama' => ['411211', '411124'],
            'potongan_nominal' => [19492580, 3544106],
            'potongan_ntpn' => ['', ''],
            'nomor_spp' => 'SPM-BLU/APTP-2026/0001',
            'tanggal_spp' => '2026-01-29',
            'nomor_spm' => 'SPM-BLU/APTP-2026/0001-SPM',
            'tanggal_spm' => '2026-01-29',
            'nomor_npi' => 'NPI-BLU/APTP-2026/0001',
            'tanggal_npi' => '2026-01-29',
            'nomor_sp2d' => 'SP2D-BLU/APTP-2026/0001',
            'tanggal_sp2d' => '2026-01-29',
            'register_nomor_urut' => 1,
        ], $override);
    }

    public function test_rekam_historis_sampai_realisasi_dan_bku_bertanggal_arsip(): void
    {
        $this->withoutExceptionHandling();

        $response = $this->actingAs($this->admin)
            ->post(route('proses-tagihan.historis.store'), $this->payloadBerkas0001());

        $response->assertSessionHasNoErrors();

        $tagihan = Tagihan::where('nomor_tagihan', 'HIS/2026/0001')->firstOrFail();
        $response->assertRedirect(route('proses-tagihan.show', $tagihan));

        $this->assertTrue((bool) $tagihan->is_historis);
        $this->assertSame('SELESAI', $tagihan->status);
        $this->assertSame(173661169.0, (float) $tagihan->total_netto);

        $spp = \App\Models\DokumenSpp::where('tagihan_id', $tagihan->id)->firstOrFail();
        $this->assertSame('SPM-BLU/APTP-2026/0001', $spp->nomor_spp);
        $this->assertSame('2026-01-29', \Illuminate\Support\Carbon::parse($spp->tanggal_spp)->toDateString());
        $this->assertSame('DISETUJUI_FINAL', $spp->status);
        $this->assertSame(196697855.0, (float) $spp->nominal_spp);
        $this->assertDatabaseHas('dokumen_sp2d', [
            'nomor_sp2d' => 'SP2D-BLU/APTP-2026/0001',
            'status' => 'EXECUTED',
        ]);

        $this->assertDatabaseHas('potongan_tagihan', [
            'tagihan_id' => $tagihan->id,
            'nama_pajak_snapshot' => '411211',
            'nominal_potongan' => 19492580.00,
        ]);

        // Serapan anggaran tercatat pada item COA bertanggal arsip.
        $this->assertDatabaseHas('realisasi_anggaran', [
            'dipa_revision_item_id' => $this->item->id,
            'nominal_cair' => 196697855.00,
            'status' => 'TERCATAT',
        ]);

        // BKU bertanggal SP2D historis dengan nomor bukti SP2D arsip.
        $bku = BukuKasUmum::where('nomor_bukti', 'SP2D-BLU/APTP-2026/0001')->first();
        $this->assertNotNull($bku, 'Transaksi BKU historis tidak ditemukan.');
        $this->assertSame('2026-01-29', \Illuminate\Support\Carbon::parse($bku->tanggal_transaksi)->toDateString());

        // Timeline mencatat aksi impor historis.
        $this->assertDatabaseHas('log_status_dokumen', [
            'dokumen_id' => $tagihan->id,
            'aksi' => 'IMPORT_HISTORIS',
        ]);
    }

    public function test_register_spp_melompati_nomor_historis(): void
    {
        $this->actingAs($this->admin)
            ->post(route('proses-tagihan.historis.store'), $this->payloadBerkas0001());

        $nomorBerikutnya = app(DocumentNumberService::class)->generateByKey('SPP_BLU', 2026);

        $this->assertStringContainsString('/0002/', $nomorBerikutnya);
    }

    public function test_role_lain_tidak_boleh_membuka_form_historis(): void
    {
        $ppk = User::factory()->create();
        $ppk->assignRole('PPK');

        $this->actingAs($ppk)
            ->get(route('proses-tagihan.historis.create'))
            ->assertForbidden();
    }

    public function test_parser_ocr_membaca_field_berkas_0001_dari_fixture(): void
    {
        $teks = file_get_contents(__DIR__ . '/../Fixtures/ocr-tagihan-0001.txt');
        $warnings = [];
        $f = (new \App\Support\Historis\TagihanArsipReader())->parseTeks($teks, $warnings);

        $this->assertSame('SPM-BLU/APTP-2026/0001', $f['nomor_spp']);
        $this->assertSame('2026-01-29', $f['tanggal_spp']);
        $this->assertSame(196697855.0, $f['total_bruto']);
        $this->assertSame(173661169.0, $f['total_netto']);
        $this->assertSame('GA.4647.CDE.001.051.A.525114.000002', $f['kode_coa']);
        $this->assertSame('CV. GARUDA KARYA BERSAMA', $f['pihak_nama']);
        $this->assertSame('0607734639741000', $f['pihak_npwp']);
        $this->assertSame('KONTRAK_EKSTERNAL', $f['tipe_tagihan']);

        // Data bank & alamat vendor ikut terbaca dari blok kanan SPP.
        $this->assertSame('Bank Tabungan Negara', $f['pihak_bank']);
        $this->assertSame('0002001880002240', $f['pihak_rekening']);
        $this->assertSame('CV. GARUDA KARYA BERSAMA', $f['pihak_nama_rekening']);
        $this->assertStringContainsString('Jl. Trisari', $f['pihak_alamat']);

        $this->assertCount(2, $f['potongan']);
        $this->assertSame(['nama' => '411211', 'nominal' => 19492580.0], $f['potongan'][0]);
        $this->assertSame(['nama' => '411124', 'nominal' => 3544106.0], $f['potongan'][1]);
    }

    public function test_parser_nama_file_sebagai_fallback(): void
    {
        $hasil = (new \App\Support\Historis\TagihanArsipReader())->parseNamaFile(
            '0001. Pembayaran Belanja Barang Pekerjaan Pemotongan Rumput Sisi Udara Tahap I 1 (Satu) Paket_CV. Garuda Karya Bersama_Rp.196.697.855.pdf'
        );

        $this->assertSame(1, $hasil['urut']);
        $this->assertSame(196697855.0, $hasil['bruto']);
        $this->assertSame('CV. Garuda Karya Bersama', $hasil['vendor']);
        $this->assertStringContainsString('Pemotongan Rumput', $hasil['deskripsi']);
    }

    public function test_endpoint_baca_arsip_ocr_pdf_asli(): void
    {
        if (! (new \App\Support\Historis\TagihanArsipReader())->tesseractTersedia()) {
            $this->markTestSkipped('Tesseract tidak tersedia di mesin ini.');
        }

        $pdf = new \Illuminate\Http\UploadedFile(
            __DIR__ . '/../../docs/tagihan/0001. Pembayaran Belanja Barang Pekerjaan Pemotongan Rumput Sisi Udara Tahap I 1 (Satu) Paket_CV. Garuda Karya Bersama_Rp.196.697.855.pdf',
            '0001. Pembayaran Belanja Barang Pekerjaan Pemotongan Rumput Sisi Udara Tahap I 1 (Satu) Paket_CV. Garuda Karya Bersama_Rp.196.697.855.pdf',
            'application/pdf',
            null,
            true
        );

        $response = $this->actingAs($this->admin)->postJson(
            route('proses-tagihan.historis.baca-arsip'),
            ['file_arsip' => $pdf]
        );

        $response->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('fields.nomor_spp', 'SPM-BLU/APTP-2026/0001')
            ->assertJsonPath('fields.tanggal_spp', '2026-01-29')
            ->assertJsonPath('fields.total_bruto', 196697855)
            ->assertJsonPath('fields.register_nomor_urut', 1);

        $this->assertCount(2, $response->json('potongan'));
        $this->assertNotEmpty($response->json('preview'));
        $this->assertTrue(Storage::disk('local')->exists('historis-arsip/' . $response->json('token') . '.pdf'));
    }

    public function test_historis_perjaldin_tanpa_komponen_tercatat_lewat_cabang_generik(): void
    {
        // Regresi bug: tipe PERJALDIN dipaksa realisasi per-komponen padahal
        // tagihan historis tidak punya komponen → dulu 500.
        $this->actingAs($this->admin)->post(route('proses-tagihan.historis.store'), $this->payloadBerkas0001([
            'tipe_tagihan' => 'PERJALDIN',
            'nomor_tagihan' => 'HIS/2026/0210',
            'deskripsi' => 'Belanja Barang Perjalanan Dinas Pegawai - Taxi',
            'total_bruto' => 170000,
            'potongan_nama' => [],
            'potongan_nominal' => [],
            'potongan_ntpn' => [],
            'nomor_spp' => 'SPM-BLU/APTP-2026/0210',
            'nomor_spm' => 'SPM-BLU/APTP-2026/0210-SPM',
            'nomor_npi' => 'NPI-BLU/APTP-2026/0210',
            'nomor_sp2d' => 'SP2D-BLU/APTP-2026/0210',
            'register_nomor_urut' => 210,
        ]))->assertSessionMissing('error');

        $tagihan = Tagihan::where('nomor_tagihan', 'HIS/2026/0210')->firstOrFail();
        $this->assertSame('SELESAI', $tagihan->status);
        $this->assertDatabaseHas('realisasi_anggaran', [
            'dipa_revision_item_id' => $this->item->id,
            'nominal_cair' => 170000.00,
            'status' => 'TERCATAT',
        ]);
        $this->assertNotNull(BukuKasUmum::where('nomor_bukti', 'SP2D-BLU/APTP-2026/0210')->first());
    }

    public function test_pihak_baru_tersimpan_lengkap_beserta_rekeningnya(): void
    {
        $this->actingAs($this->admin)->post(route('proses-tagihan.historis.store'), $this->payloadBerkas0001([
            'pihak_id' => null,
            'pihak_nama_baru' => 'PT. ANGKASA JAYA SERVIS',
            'pihak_npwp' => '0539247528722000',
            'pihak_direktur' => 'Budi Santoso',
            'pihak_jabatan' => 'Direktur',
            'pihak_alamat' => 'Jl. Poros Samarinda - Bontang, Kel. Sungai Siring',
            'pihak_bank' => 'Bank Tabungan Negara',
            'pihak_norek' => '2001880001341',
            'pihak_nama_rekening' => 'PT. ANGKASA JAYA SERVIS',
        ]))->assertSessionMissing('error');

        $pihak = MasterPihak::where('nama_pihak', 'PT. ANGKASA JAYA SERVIS')->firstOrFail();
        $this->assertSame('0539247528722000', $pihak->npwp);
        $this->assertSame('Budi Santoso', $pihak->nama_penanggung_jawab);
        $this->assertSame('Direktur', $pihak->jabatan_penandatangan);
        $this->assertStringContainsString('Sungai Siring', $pihak->alamat);

        $rekening = $pihak->rekening()->first();
        $this->assertNotNull($rekening, 'Rekening vendor tidak tersimpan.');
        $this->assertSame('2001880001341', $rekening->nomor_rekening);
        $this->assertSame('Bank Tabungan Negara', $rekening->nama_bank);

        // Tagihan tertaut ke vendor baru.
        $this->assertSame(
            $pihak->id,
            Tagihan::where('nomor_tagihan', 'HIS/2026/0001')->value('pihak_id')
        );
    }

    public function test_cocokkan_coa_membandingkan_urutan_item_secara_numerik(): void
    {
        // Regresi bug: berkas menulis kd_item 6 digit (.000006), master POK
        // 5 digit (.00006); dengan banyak item se-prefix, pencocokan dulu
        // ambigu (8 kandidat) lalu menyerah.
        $revision = $this->item->dipaRevision;
        $target = null;
        // setUp sudah membuat item .00002 pada prefix yang sama.
        foreach ([3, 4, 5, 6] as $n) {
            $kdItem = str_pad((string) $n, 5, '0', STR_PAD_LEFT);
            $coa = MasterCoa::create([
                'kd_akun' => '525114',
                'kd_item' => $kdItem,
                'kode_mak_lengkap' => 'GA.4647.CDE.001.051.A.525114.' . $kdItem,
                'nama_akun' => 'Fasilitas Sisi Udara ' . $n,
                'sumber_dana' => 'BLU',
                'status_aktif' => true,
            ]);
            $item = DetailDipa::create([
                'dipa_revision_id' => $revision->id,
                'coa_id' => $coa->id,
                'nilai_pagu' => 1000000 * $n,
                'status_aktif' => true,
            ]);
            if ($n === 6) {
                $target = $item;
            }
        }

        $reader = new \App\Support\Historis\TagihanArsipReader();

        // Berkas 6 digit → item master 5 digit yang urutannya sama.
        $this->assertSame($target->id, $reader->cocokkanCoa('GA.4647.CDE.001.051.A.525114.000006'));
        // Exact match tetap bekerja.
        $this->assertSame($target->id, $reader->cocokkanCoa('GA.4647.CDE.001.051.A.525114.00006'));
        // Urutan yang tidak ada → null (tetap minta pilih manual).
        $this->assertNull($reader->cocokkanCoa('GA.4647.CDE.001.051.A.525114.000099'));
    }

    public function test_netto_nol_atau_negatif_ditolak(): void
    {
        $response = $this->actingAs($this->admin)->post(
            route('proses-tagihan.historis.store'),
            $this->payloadBerkas0001([
                'potongan_nominal' => [196697855, 1],
            ])
        );

        $response->assertSessionHasErrors('total_bruto');
        $this->assertDatabaseMissing('tagihan', ['nomor_tagihan' => 'HIS/2026/0001']);
    }
}
