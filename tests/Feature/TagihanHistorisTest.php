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

        // Progres pencairan tampil tuntas: KPA & seluruh dokumen dianggap
        // disetujui walau tanpa workflow (regresi panel "7/9 tahap").
        $chain = app(\App\Services\DokumenChainService::class);
        $this->assertTrue($chain->isKpaApproved($tagihan->fresh()));
        $this->assertTrue($chain->isDocumentApproved($spp));
        $this->assertTrue($chain->isDocumentApproved($spp->spm));
        $this->assertTrue($chain->isDocumentApproved($spp->spm->npi));
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

        // Berkas contoh dicari rekursif — folder docs/tagihan ditata per bulan.
        $kandidat = glob(__DIR__ . '/../../docs/tagihan/{,*/}0001.*.pdf', GLOB_BRACE) ?: [];
        if ($kandidat === []) {
            $this->markTestSkipped('Berkas contoh 0001 tidak ditemukan di docs/tagihan.');
        }

        $pdf = new \Illuminate\Http\UploadedFile(
            $kandidat[0],
            basename($kandidat[0]),
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

    public function test_arsip_bundel_dapat_diunduh_kembali_oleh_operator_blu(): void
    {
        Role::findOrCreate('Operator BLU', 'web');
        $operator = User::factory()->create();
        $operator->assignRole('Operator BLU');

        $this->actingAs($this->admin)->post(route('proses-tagihan.historis.store'), $this->payloadBerkas0001([
            'file_arsip' => \Illuminate\Http\UploadedFile::fake()->create('bundel-arsip-0001.pdf', 120, 'application/pdf'),
        ]))->assertSessionMissing('error');

        $arsip = \App\Models\ArsipDokumen::where('jenis_dokumen', 'BUKTI_TRANSFER_SP2D')->firstOrFail();

        // Mode inline: PDF tampil di tab browser, bukan dipaksa unduh.
        $inline = $this->actingAs($operator)
            ->get(route('arsip-sensitif.download', ['arsip' => $arsip->id, 'inline' => 1]));
        $inline->assertOk();
        $this->assertStringNotContainsString('attachment', (string) $inline->headers->get('Content-Disposition'));

        // SEMUA role yang boleh membuka detail Proses Tagihan dapat melihat
        // kembali bundelnya (selaras middleware grup route proses-tagihan).
        $this->actingAs($operator)->get(route('arsip-sensitif.download', $arsip->id))->assertOk();
        $this->actingAs($this->admin)->get(route('arsip-sensitif.download', $arsip->id))->assertOk();

        foreach ([
            'PPK', 'PPSPM', 'Bendahara Pengeluaran', 'Bendahara Penerimaan',
            'Koordinator Keuangan', 'Kepala Subbagian Keuangan dan Tata Usaha', 'KPA',
        ] as $role) {
            Role::findOrCreate($role, 'web');
            $userRole = User::factory()->create();
            $userRole->assignRole($role);
            $this->actingAs($userRole)
                ->get(route('arsip-sensitif.download', $arsip->id))
                ->assertOk();
        }

        // Role di luar lingkup Proses Tagihan tetap ditolak.
        Role::findOrCreate('Admin Jasa', 'web');
        $luar = User::factory()->create();
        $luar->assignRole('Admin Jasa');
        $this->actingAs($luar)
            ->get(route('arsip-sensitif.download', $arsip->id))
            ->assertForbidden();
    }

    public function test_parser_peserta_membaca_nominatif_honor_dari_fixture(): void
    {
        $teks = file_get_contents(__DIR__ . '/../Fixtures/ocr-nominatif-0167.txt');
        $rows = (new \App\Support\Historis\TagihanArsipReader())->parsePeserta($teks);

        $this->assertGreaterThanOrEqual(6, count($rows));

        $pertama = $rows[0];
        $this->assertSame('Abdullah Hakim', $pertama['nama']);
        $this->assertSame('530156', $pertama['nrp_nip']);
        $this->assertSame(500000.0, $pertama['nilai_honor']);
        $this->assertSame(25000.0, $pertama['pph']);
        $this->assertSame('Bank Mandiri', $pertama['jenis_bank']);
        $this->assertSame('081310998926', $pertama['no_hp']);

        $namaSemua = array_column($rows, 'nama');
        $this->assertContains('Muhammad Ikhlas', $namaSemua);
        $this->assertContains('Asfiansyah', $namaSemua);
    }

    public function test_peserta_tersimpan_ke_detail_honorarium_dan_perjaldin(): void
    {
        // HONORARIUM → detail_honorarium.
        $this->actingAs($this->admin)->post(route('proses-tagihan.historis.store'), $this->payloadBerkas0001([
            'tipe_tagihan' => 'HONORARIUM',
            'peserta_nama' => ['Abdullah Hakim', 'Asfiansyah', ''],
            'peserta_nrp' => ['530156', '87061361', ''],
            'peserta_pangkat' => ['Letda Pom', 'Bripka', ''],
            'peserta_jabatan' => ['PS Danunitpaspom Satpom Lanud Dmb', 'Banit Samapta Polsek', ''],
            'peserta_honor' => [500000, 500000, ''],
            'peserta_pph' => [25000, 0, ''],
            'peserta_rekening' => ['1290011244767', '012101107602503', ''],
            'peserta_bank' => ['Bank Mandiri', 'Bank BRI', ''],
            'peserta_nama_rekening' => ['Abdullah Hakim', 'Asfiansyah', ''],
            'peserta_hp' => ['081310998926', '081216255131', ''],
        ]))->assertSessionMissing('error');

        $tagihan = Tagihan::where('nomor_tagihan', 'HIS/2026/0001')->firstOrFail();
        $this->assertSame(2, $tagihan->detailHonorarium()->count());
        $this->assertDatabaseHas('detail_honorarium', [
            'tagihan_id' => $tagihan->id,
            'nama_personel' => 'Abdullah Hakim',
            'nrp_nip' => '530156',
            'pangkat_korp' => 'Letda Pom',
            'nilai_honor' => 500000.00,
            'pph' => 25000.00,
            'jenis_bank' => 'Bank Mandiri',
        ]);

        // PERJALDIN → detail_perjaldin.
        $this->actingAs($this->admin)->post(route('proses-tagihan.historis.store'), $this->payloadBerkas0001([
            'tipe_tagihan' => 'PERJALDIN',
            'nomor_tagihan' => 'HIS/2026/0210',
            'nomor_spp' => 'SPM-BLU/APTP-2026/0210',
            'nomor_spm' => 'SPM-BLU/APTP-2026/0210-SPM',
            'nomor_npi' => 'NPI-BLU/APTP-2026/0210',
            'nomor_sp2d' => 'SP2D-BLU/APTP-2026/0210',
            'register_nomor_urut' => 210,
            'peserta_nama' => ['Budi Santoso'],
            'peserta_nrp' => ['198411052007121001'],
            'peserta_honor' => [170000],
            'peserta_rekening' => ['1234567890'],
        ]))->assertSessionMissing('error');

        $perjaldin = Tagihan::where('nomor_tagihan', 'HIS/2026/0210')->firstOrFail();
        $this->assertDatabaseHas('detail_perjaldin', [
            'tagihan_id' => $perjaldin->id,
            'nama_pegawai' => 'Budi Santoso',
            'nip' => '198411052007121001',
            'uang_harian' => 170000.00,
        ]);
    }

    public function test_potongan_dengan_kode_akun_rusak_tetap_terbaca_nominalnya(): void
    {
        // Regresi berkas 0130: OCR merusak kode akun (411211 → "Tana2nt"),
        // dulu seluruh potongan hilang → netto salah.
        $teks = implode("\n", [
            '| PENGELUARAN JUMLAH UANG',
            '| GA.4647.CDE.001.055.B.525121.00002 11.669.500,00',
            '| Jumlah Pengeluaran 11.669.500,00',
            '| POTONGAN JUMLAH UANG',
            '| Tana2nt 1.156.437,00',
            '| latt122 157.696,00',
            'Jumlah Potongan 1.314.133,00',
            'TOTAL PEMBAYARAN 10.355.367,00',
        ]);

        $warnings = [];
        $f = (new \App\Support\Historis\TagihanArsipReader())->parseTeks($teks, $warnings);

        $this->assertCount(2, $f['potongan']);
        $this->assertSame(1156437.0, $f['potongan'][0]['nominal']);
        $this->assertSame(157696.0, $f['potongan'][1]['nominal']);
        $this->assertSame('', $f['potongan'][0]['nama']); // akun rusak → dikosongkan
        $this->assertNotEmpty(array_filter($warnings, fn ($w) => str_contains($w, 'Kode akun potongan')));
    }

    public function test_pihak_cocok_walau_nama_master_mengandung_artefak_ocr(): void
    {
        $kotor = MasterPihak::create([
            'kategori' => 'PENGELUARAN',
            'jenis_entitas' => 'BADAN_USAHA',
            'kode_pihak' => 'VDR-KOTOR-1',
            'nama_pihak' => '©: CV. GUNA AKHLAK SUKSES',
            'status_aktif' => true,
        ]);

        // Endpoint mencocokkan longgar (huruf/angka saja).
        $this->assertSame(
            \App\Support\Historis\TagihanArsipReader::kunciNama('CV. GUNA AKHLAK SUKSES'),
            \App\Support\Historis\TagihanArsipReader::kunciNama($kotor->nama_pihak)
        );

        // Store dengan "pihak baru" bernama sama → pakai vendor lama, tanpa duplikat.
        $this->actingAs($this->admin)->post(route('proses-tagihan.historis.store'), $this->payloadBerkas0001([
            'pihak_id' => null,
            'pihak_nama_baru' => 'CV. GUNA AKHLAK SUKSES',
        ]))->assertSessionMissing('error');

        $this->assertSame(1, MasterPihak::where('kode_pihak', 'like', 'VDR-KOTOR%')->count());
        $this->assertSame(0, MasterPihak::where('nama_pihak', 'CV. GUNA AKHLAK SUKSES')->count());
        $this->assertSame(
            $kotor->id,
            Tagihan::where('nomor_tagihan', 'HIS/2026/0001')->value('pihak_id')
        );
    }

    public function test_bundel_multi_spp_terdeteksi_sebagai_komponen(): void
    {
        $reader = new \App\Support\Historis\TagihanArsipReader();

        // Ringkasan OCR nyata berkas "0135-0136": dua SPP dalam satu bundel.
        $teks = "SURAT PERMINTAAN PEMBAYARAN BLU\n"
            . "Nomor:  SPM-BLU/APTP-2026/0135 Tanggal : 12-Mei-2026\n"
            . "Agar melakukan pembayaran tagihan sejumlah Rp2.970.000\n"
            . "WA.4613.EBA.960.053.A.525115.00004 2.970.000,00\n"
            . "TOTAL PEMBAYARAN 2.970.000,00\n"
            . "Uraian : Belanja Barang Perjalanan Dinas Pegawai - Taxi\n"
            . "SURAT PERMINTAAN PEMBAYARAN BLU\n"
            . "Nomor:  SPM-BLU/APTP-2026/0136 Tanggal : 12-Mei-2026\n"
            . "Agar melakukan pembayaran tagihan sejumlah Rp3.320.000\n"
            . "WA.4613.EBA.960.053.A.525115.00001 3.320.000,00\n"
            . "TOTAL PEMBAYARAN 3.320.000,00\n"
            . "Uraian : Belanja Barang Perjalanan Dinas Pegawai - Uang Harian\n";

        $komponen = $reader->deteksiKomponen($teks);
        $this->assertCount(2, $komponen);
        $this->assertSame('SPM-BLU/APTP-2026/0135', $komponen[0]['nomor_spp']);
        $this->assertSame(135, $komponen[0]['urut']);
        $this->assertSame('Taxi', $komponen[0]['nama']);
        $this->assertSame(2970000.0, $komponen[0]['nominal']);
        $this->assertSame(136, $komponen[1]['urut']);
        $this->assertSame('Uang Harian', $komponen[1]['nama']);
        $this->assertSame(3320000.0, $komponen[1]['nominal']);

        // Satu SPP saja → bukan bundel gabungan.
        $this->assertSame([], $reader->deteksiKomponen(
            "SURAT PERMINTAAN PEMBAYARAN BLU\nNomor:  SPM-BLU/APTP-2026/0135\nsejumlah Rp2.970.000\n"
        ));

        // Nama file rentang "0135-0136." → urut pertama + bruto total bundel.
        $nf = $reader->parseNamaFile('0135-0136. Belanja Barang Perjalanan Dinas Pegawai_Rp. 6.290.000.pdf');
        $this->assertSame(135, $nf['urut']);
        $this->assertSame(6290000.0, $nf['bruto']);
    }

    public function test_bundel_multi_spp_direkam_satu_tagihan_dengan_komponen(): void
    {
        $this->withoutExceptionHandling();

        // Item DIPA kedua untuk komponen uang harian (COA berbeda dari taxi).
        $coa2 = MasterCoa::create([
            'kd_akun' => '525115',
            'kode_mak_lengkap' => 'WA.4613.EBA.960.053.A.525115.00001',
            'nama_akun' => 'Belanja Perjalanan Dinas BLU',
            'sumber_dana' => 'BLU',
            'status_aktif' => true,
        ]);
        $item2 = DetailDipa::create([
            'dipa_revision_id' => $this->item->dipa_revision_id,
            'coa_id' => $coa2->id,
            'nilai_pagu' => 50000000,
            'status_aktif' => true,
        ]);

        $this->actingAs($this->admin)->post(route('proses-tagihan.historis.store'), $this->payloadBerkas0001([
            'tipe_tagihan' => 'PERJALDIN',
            'nomor_tagihan' => 'HIS/2026/0135',
            'deskripsi' => 'Belanja Barang Perjalanan Dinas Pegawai',
            'total_bruto' => 6290000,
            'potongan_nama' => [],
            'potongan_nominal' => [],
            'potongan_ntpn' => [],
            'nomor_spp' => 'SPM-BLU/APTP-2026/0135',
            'tanggal_spp' => '2026-05-12',
            'nomor_spm' => 'SPM-BLU/APTP-2026/0135',
            'tanggal_spm' => '2026-05-12',
            'nomor_npi' => 'NPI-BLU/APTP-2026/0135',
            'tanggal_npi' => '2026-05-12',
            'nomor_sp2d' => 'SP2D-BLU/APTP-2026/0135',
            'tanggal_sp2d' => '2026-05-12',
            'register_nomor_urut' => 135,
            'komponen_nama' => ['Taxi', 'Uang Harian'],
            'komponen_nomor_spp' => ['SPM-BLU/APTP-2026/0135', 'SPM-BLU/APTP-2026/0136'],
            'komponen_urut' => [135, 136],
            'komponen_dipa_item_id' => [$this->item->id, $item2->id],
            'komponen_nominal' => [2970000, 3320000],
        ]))->assertSessionHasNoErrors();

        $tagihan = Tagihan::where('nomor_tagihan', 'HIS/2026/0135')->firstOrFail();
        $this->assertSame('SELESAI', $tagihan->status);

        // Dua komponen tercatat final dengan COA masing-masing.
        $this->assertDatabaseHas('tagihan_perjaldin_komponen', [
            'tagihan_id' => $tagihan->id,
            'nama_komponen' => 'Taxi (SPM-BLU/APTP-2026/0135)',
            'dipa_revision_item_id' => $this->item->id,
            'total_nominal' => 2970000.00,
            'status_proses' => 'SELESAI',
        ]);
        $this->assertDatabaseHas('tagihan_perjaldin_komponen', [
            'tagihan_id' => $tagihan->id,
            'nama_komponen' => 'Uang Harian (SPM-BLU/APTP-2026/0136)',
            'dipa_revision_item_id' => $item2->id,
            'total_nominal' => 3320000.00,
            'status_proses' => 'SELESAI',
        ]);

        // Realisasi dicatat per komponen sesuai COA masing-masing.
        $this->assertDatabaseHas('realisasi_anggaran', [
            'dipa_revision_item_id' => $this->item->id,
            'nominal_cair' => 2970000.00,
            'status' => 'TERCATAT',
        ]);
        $this->assertDatabaseHas('realisasi_anggaran', [
            'dipa_revision_item_id' => $item2->id,
            'nominal_cair' => 3320000.00,
            'status' => 'TERCATAT',
        ]);

        // BKU terposting satu kali dengan nomor bukti SP2D bundel (BKU +
        // buku pembantu berbagi tabel, jadi cukup pastikan postingnya ada).
        $this->assertNotNull(BukuKasUmum::where('nomor_bukti', 'SP2D-BLU/APTP-2026/0135')->first());

        // Kedua nomor register (135 & 136) terkonsumsi → nomor otomatis berikutnya 137.
        $berikutnya = app(DocumentNumberService::class)->generateByKey('SPP_BLU', 2026);
        $this->assertStringContainsString('0137', $berikutnya);
    }

    public function test_nominatif_perjaldin_dua_baris_per_orang_terbaca(): void
    {
        $reader = new \App\Support\Historis\TagihanArsipReader();

        // Baris OCR nyata (PSM 4) halaman nominatif berkas 0145-0147:
        // nama di baris atas, data (NIP terpecah + SPPD + nominal + rekening)
        // di baris bawah; satu baris (Sugiyono) tergabung jadi satu.
        $teks = "DAFTAR NOMINATIF PEMBAYARAN PERJALANAN DINAS\n"
            . "Nomor: KU.201/3067/APTP/2026\n"
            . "Heriyanto \$ 5\n"
            . "19740714 201212 1004 KP.004/0665/APTP/2026 0666 Palembang 27 Januari 2026 47 (Diklat) 5,151,216 80,000 760,000 5,991,216 2001580030462\n"
            . "udhy Prasetiyo \$ F\n"
            . "2 19860604 200712 1001 KP.004/2658/APTP/2026 2741 Balikpapan 07 Mei 2026 1 283,500 430,000 713,500 2001580030234\n"
            . "Muhammad Zuher Ammar Dzaki \u{2018}\n"
            . "3 20001202 202310 1 002 | KP.004/2658/APTP/2026 2743 Balikpapan 07 Mei 2026 1 IN 430,000 2001580032595\n"
            . "ai | Suniyono KP.004/2658/APTP/2026 | 2742 Balik 07 Mei 2026 1 | 430,000 |  2001580030226\n"
            . "JUMLAH 5,151,216 363,500 - 7,564,716\n"
            . "PEJABAT PEMBUAT KOMITMEN BENDAHARA PENGELUARAN\n"
            . "NIP. 19920220 201012 2 001\n";

        $rows = $reader->parsePesertaPerjaldin($teks);

        $this->assertCount(4, $rows);
        $this->assertSame('Heriyanto', $rows[0]['nama']);
        $this->assertSame('197407142012121004', $rows[0]['nrp_nip']);
        $this->assertSame(5991216.0, $rows[0]['nilai_honor']); // kolom JUMLAH
        $this->assertSame('2001580030462', $rows[0]['rekening']);
        $this->assertSame('KP.004/0665/APTP/2026', $rows[0]['no_sppd']);
        $this->assertSame('Palembang', $rows[0]['tujuan']);
        $this->assertSame('2026-01-27', $rows[0]['tgl_berangkat']);
        $this->assertSame(47, $rows[0]['lama_hari']);
        $this->assertSame('2026-05-07', $rows[1]['tgl_berangkat']);
        $this->assertSame(1, $rows[1]['lama_hari']);
        $this->assertSame('udhy Prasetiyo', $rows[1]['nama']);
        $this->assertSame(713500.0, $rows[1]['nilai_honor']);
        $this->assertSame('Muhammad Zuher Ammar Dzaki', $rows[2]['nama']);
        $this->assertSame('200012022023101002', $rows[2]['nrp_nip']);
        $this->assertSame('Suniyono', $rows[3]['nama']);
        $this->assertSame('2001580030226', $rows[3]['rekening']);

        // Total kolom JUMLAH seluruh peserta = bruto bundel (7.564.716).
        $this->assertSame(7564716.0, array_sum(array_column($rows, 'nilai_honor')));
    }

    public function test_nama_direktur_terbaca_dari_tiga_pola_dokumen(): void
    {
        $reader = new \App\Support\Historis\TagihanArsipReader();
        $w = [];

        // Pola 1: blok "Nama : ... / Jabatan : Direktur ..." (BAPP/BAST) —
        // ':' kadang ter-OCR sebagai '1'.
        $f = $reader->parseTeks("Nama : YUARNO ARBI\nJabatan : Direktur PT. ANGKASA JAYA SERVIS\n", $w);
        $this->assertSame('YUARNO ARBI', $f['pihak_direktur']);
        $f = $reader->parseTeks("Nama : AHMAD\nJabatan 1 Direktur CV. GUNA AHKLAK SUKSES\n", $w);
        $this->assertSame('AHMAD', $f['pihak_direktur']);

        // Pola 2: kalimat naratif "..., dalam hal ini sebagai Penyedia".
        $f = $reader->parseTeks("2. AHMAD, dalam hal ini sebagai Penyedia, bertindak sebagai Direktur mewakili CV. GUNA\n", $w);
        $this->assertSame('AHMAD', $f['pihak_direktur']);

        // Pola 3: nama sendirian di atas baris "Direktur" (blok tanda tangan);
        // kata "Direktorat" tidak boleh ikut tertangkap.
        $f = $reader->parseTeks("Samarinda, 6 Januari 2026\nYuarno Arbi\nDirektur\n", $w);
        $this->assertSame('Yuarno Arbi', $f['pihak_direktur']);
        $f = $reader->parseTeks("KEMENTERIAN PERHUBUNGAN\nDIREKTORAT JENDERAL\n", $w);
        $this->assertNull($f['pihak_direktur']);
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
