<?php

namespace Tests\Feature;

use App\Models\MasterPihak;
use App\Models\User;
use App\Services\SuratPesananPdfExtractor;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Auto-isi form Tagihan Kontrak Eksternal dari PDF Surat Pesanan INAPROC:
 * ekstraksi teks PDF (smalot/pdfparser) + endpoint AJAX parse.
 */
class SuratPesananAutofillTest extends TestCase
{
    use RefreshDatabase;

    private User $ppk;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('PPK', 'web');
        $this->ppk = User::factory()->create();
        $this->ppk->assignRole('PPK');
    }

    /** PDF fixture ber-layer teks meniru pola label Surat Pesanan INAPROC v6. */
    private function suratPesananPdfBytes(): string
    {
        $html = <<<'HTML'
        <h1>Surat Pesanan</h1>
        <p>No. Surat Pesanan : EP-01KNNRTKUJI123</p>
        <p>Tanggal Surat Pesanan : 08 Apr 2026, 14:21:58 WIB</p>
        <h3>Pemesan</h3>
        <p>KANTOR UPBU AJI PANGERAN TUMENGGUNG PRANOTO</p>
        <p>Nama Penanggung Jawab : gunawan</p>
        <p>Jabatan Penanggung Jawab : Pejabat Pembuat Komitmen (PPK)</p>
        <p>NPWP Pemesan : 00.168.190.7-722.000</p>
        <p>Alamat Pemesan : Jl. Poros Samarinda Bontang</p>
        <h3>Informasi Pembayaran dan Pengiriman</h3>
        <p>Pembayaran : 1 Termin</p>
        <p>Pengiriman : 1 Tahap</p>
        <h3>Penyedia</h3>
        <p>BERKAT DAMAI SEJAHTERA INDONESIA UMKK</p>
        <p>Nama Penanggung Jawab : David Petra Chendana</p>
        <p>Jabatan Penanggung Jawab : DIREKTUR</p>
        <p>NPWP Penyedia : 71.740.860.3-411.000</p>
        <p>Alamat Penyedia : Jl Kasuari V HB 9 No 17, Tangerang Selatan</p>
        <h3>Ringkasan Pesanan</h3>
        <p>Barang PDN CCTV IP Outdoor 8 MP 7,00 unit (2.800 gr)</p>
        <h3>Ringkasan Pembayaran</h3>
        <p>Estimasi Total Pembayaran Rp435.675.000,00</p>
        HTML;

        return Pdf::loadHTML($html)->output();
    }

    /**
     * Dokumen gabungan: halaman-halaman Kontrak/SPK dulu, blok Surat Pesanan
     * INAPROC baru muncul jauh di belakang (meniru PDF kontrak + SP jadi satu).
     */
    private function dokumenGabunganPdfBytes(): string
    {
        $halamanKontrak = '';
        for ($i = 1; $i <= 8; $i++) {
            $halamanKontrak .= <<<HTML
            <p>SURAT PERJANJIAN KERJA (KONTRAK) halaman {$i} — Syarat-Syarat Umum Kontrak,
            harga kontrak Rp6.368.521.200, pembayaran termin bulanan sesuai SSKK.</p>
            <div style="page-break-after: always;"></div>
            HTML;
        }

        $html = $halamanKontrak.<<<'HTML'
        <h1>Surat Pesanan</h1>
        <p>No. Surat Pesanan : EP-01KDQ67XCWUJI999</p>
        <p>Tanggal Surat Pesanan : 01 Jan 2026, 18:45:45 WIB</p>
        <h3>Pemesan</h3>
        <p>BANDAR UDARA AJI PANGERAN TUMENGGUNG PRANOTO</p>
        <p>Nama Penanggung Jawab : gunawan</p>
        <p>NPWP Pemesan : 00.168.190.7-722.000</p>
        <p>Alamat Pemesan : Jl. Poros Samarinda, Kalimantan TimurPenyediaGARUDA TAWAKAL ABADIUMKK</p>
        <p>Nama Penanggung Jawab : ibnoe legowo</p>
        <p>Jabatan Penanggung Jawab : DIREKTUR</p>
        <p>NPWP Penyedia : 003.008.751.4-617.000</p>
        <p>Alamat Penyedia : JALAN GRIYO MAPAN SENTOSA BLOK EA1 NOMOR 2B, Kab. Sidoarjo</p>
        <h3>Ringkasan Pesanan</h3>
        <p>Jasa PDN Samarinda - Tenaga Danru Junior Avsec 48,00 paket</p>
        <p>Jasa PDN Samarinda - Tenaga Basic Avsec 384,00 paket</p>
        <h3>Ringkasan Pembayaran</h3>
        <p>Estimasi Total Pembayaran Rp6.368.521.200,00</p>
        <h3>Skema Pembayaran</h3>
        <p>Masukkan detail skema sesuai dengan termin yang disetujui. Contoh:
        Termin 1: Rp 530.843.210 Termin 2: Rp 530.843.210 Termin 3: Rp 530.843.210</p>
        <div style="page-break-after: always;"></div>
        <h3>SYARAT-SYARAT KHUSUS KONTRAK</h3>
        <p>Pembayaran termin dilakukan sebanyak 12 (dua belas) kali dengan rincian sebagai berikut :
        1. Termin Pertama sebesar Rp530.710.100,-
        2. Termin Kedua sebesar Rp530.710.100,-
        3. Termin Ketiga sebesar Rp530.710.100,-
        4. Termin Keempat sebesar Rp530.710.100,-
        5. Termin Kelima sebesar Rp530.710.100,-
        6. Termin Keenam sebesar Rp530.710.100,-
        7. Termin Ketujuh sebesar Rp530.710.100,-
        8. Termin Kedelapan sebesar Rp530.710.100,-
        9. Termin Kesembilan sebesar Rp530.710.100,-
        10. Termin Kesepuluh sebesar Rp530.710.100,-
        11. Termin Kesebelas sebesar Rp530.710.100,-
        12. Termin Keduabelas sebesar Rp530.710.100,-</p>
        HTML;

        return Pdf::loadHTML($html)->output();
    }

    public function test_extractor_membaca_surat_pesanan_di_tengah_dokumen_gabungan(): void
    {
        $data = app(SuratPesananPdfExtractor::class)->extract($this->dokumenGabunganPdfBytes());

        $this->assertSame('EP-01KDQ67XCWUJI999', $data['nomor_surat_pesanan']);
        $this->assertSame('2026-01-01', $data['tanggal_surat_pesanan']);
        $this->assertSame('GARUDA TAWAKAL ABADI', $data['vendor_nama']);
        $this->assertSame('ibnoe legowo', $data['vendor_penanggung_jawab']);
        $this->assertSame('003.008.751.4-617.000', $data['vendor_npwp']);
        $this->assertSame(6_368_521_200.0, $data['total_bruto']);
        $this->assertSame('Pengadaan Samarinda - Tenaga Danru Junior Avsec', $data['nama_pekerjaan_saran']);
        $this->assertStringContainsString('Avsec', (string) $data['ringkasan_produk']);
        $this->assertTrue(SuratPesananPdfExtractor::hasUsefulData($data));

        // Skema termin SSKK terbaca (12 × 530.710.100 = total); blok "Contoh"
        // INAPROC yang nilainya tidak cocok dengan total harus terabaikan.
        $this->assertIsArray($data['skema_termin']);
        $this->assertCount(12, $data['skema_termin']);
        $this->assertSame(530_710_100.0, $data['skema_termin'][0]);
        $this->assertEqualsWithDelta(6_368_521_200.0, array_sum($data['skema_termin']), 1);
    }

    public function test_endpoint_parse_mengembalikan_skema_termin_dengan_persentase(): void
    {
        $file = UploadedFile::fake()->createWithContent('kontrak_sp.pdf', $this->dokumenGabunganPdfBytes());

        $response = $this->actingAs($this->ppk)
            ->post(route('kontrak-eksternal.parse'), ['file' => $file])
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonCount(12, 'data.skema_termin')
            ->assertJsonPath('data.skema_termin.0.termin_ke', 1)
            ->assertJsonPath('data.skema_termin.0.persentase', 8.3333);

        $this->assertSame(530_710_100, (int) $response->json('data.skema_termin.0.nilai'));
    }

    public function test_extractor_membaca_field_inti_surat_pesanan(): void
    {
        $data = app(SuratPesananPdfExtractor::class)->extract($this->suratPesananPdfBytes());

        $this->assertSame('EP-01KNNRTKUJI123', $data['nomor_surat_pesanan']);
        $this->assertSame('2026-04-08', $data['tanggal_surat_pesanan']);
        $this->assertSame('BERKAT DAMAI SEJAHTERA INDONESIA', $data['vendor_nama']);
        $this->assertSame('David Petra Chendana', $data['vendor_penanggung_jawab']);
        $this->assertSame('71.740.860.3-411.000', $data['vendor_npwp']);
        $this->assertStringContainsString('Kasuari', (string) $data['vendor_alamat']);
        $this->assertSame(435_675_000.0, $data['total_bruto']);
        $this->assertSame(1, $data['total_termin']);
        $this->assertSame(1, $data['termin_ke']);
        $this->assertTrue(SuratPesananPdfExtractor::hasUsefulData($data));
    }

    public function test_endpoint_parse_mengembalikan_data_dan_pihak_id_bila_npwp_terdaftar(): void
    {
        $vendor = MasterPihak::create([
            'kategori' => 'PENGELUARAN',
            'jenis_entitas' => 'BADAN_USAHA',
            'nama_pihak' => 'BERKAT DAMAI SEJAHTERA INDONESIA',
            'npwp' => '71.740.860.3-411.000',
            'status_aktif' => true,
        ]);

        $file = UploadedFile::fake()->createWithContent('surat_pesanan.pdf', $this->suratPesananPdfBytes());

        $response = $this->actingAs($this->ppk)
            ->post(route('kontrak-eksternal.parse'), ['file' => $file]);

        $response->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.nomor_surat_pesanan', 'EP-01KNNRTKUJI123')
            ->assertJsonPath('data.tanggal_surat_pesanan', '2026-04-08')
            ->assertJsonPath('data.total_termin', 1)
            ->assertJsonPath('data.pihak_id', $vendor->id);
    }

    public function test_endpoint_parse_tanpa_vendor_terdaftar_pihak_id_null(): void
    {
        $file = UploadedFile::fake()->createWithContent('surat_pesanan.pdf', $this->suratPesananPdfBytes());

        $this->actingAs($this->ppk)
            ->post(route('kontrak-eksternal.parse'), ['file' => $file])
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.pihak_id', null);
    }

    public function test_pdf_tanpa_data_relevan_mengembalikan_ok_false(): void
    {
        $pdf = Pdf::loadHTML('<p>Dokumen lain yang tidak relevan.</p>')->output();
        $file = UploadedFile::fake()->createWithContent('lain.pdf', $pdf);

        $this->actingAs($this->ppk)
            ->post(route('kontrak-eksternal.parse'), ['file' => $file])
            ->assertOk()
            ->assertJsonPath('ok', false);
    }

    public function test_nama_pekerjaan_dirangkum_gemini_bila_api_key_diset(): void
    {
        config(['services.gemini.key' => 'AIza-test-key']);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'content' => ['parts' => [['text' => "Pengadaan CCTV dan Perangkat Jaringan Bandara\n"]]],
                ]],
            ]),
        ]);

        $file = UploadedFile::fake()->createWithContent('surat_pesanan.pdf', $this->suratPesananPdfBytes());

        $this->actingAs($this->ppk)
            ->post(route('kontrak-eksternal.parse'), ['file' => $file])
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.nama_pekerjaan_saran', 'Pengadaan CCTV dan Perangkat Jaringan Bandara')
            ->assertJsonMissingPath('data.ringkasan_produk');

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'generativelanguage.googleapis.com')
                && $request->hasHeader('x-goog-api-key', 'AIza-test-key');
        });
    }

    public function test_gemini_gagal_fallback_ke_saran_heuristik(): void
    {
        config(['services.gemini.key' => 'AIza-test-key']);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response(['error' => 'quota'], 429),
        ]);

        $file = UploadedFile::fake()->createWithContent('surat_pesanan.pdf', $this->suratPesananPdfBytes());

        $this->actingAs($this->ppk)
            ->post(route('kontrak-eksternal.parse'), ['file' => $file])
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.nama_pekerjaan_saran', 'Pengadaan CCTV IP Outdoor 8 MP');
    }

    public function test_tanpa_api_key_gemini_tidak_dipanggil(): void
    {
        config(['services.gemini.key' => null]);
        Http::fake();

        $file = UploadedFile::fake()->createWithContent('surat_pesanan.pdf', $this->suratPesananPdfBytes());

        $this->actingAs($this->ppk)
            ->post(route('kontrak-eksternal.parse'), ['file' => $file])
            ->assertOk()
            ->assertJsonPath('data.nama_pekerjaan_saran', 'Pengadaan CCTV IP Outdoor 8 MP');

        Http::assertNothingSent();
    }

    public function test_user_tanpa_role_ppk_ditolak(): void
    {
        Role::findOrCreate('Operator BLU', 'web');
        $operator = User::factory()->create();
        $operator->assignRole('Operator BLU');

        $file = UploadedFile::fake()->createWithContent('surat_pesanan.pdf', $this->suratPesananPdfBytes());

        $this->actingAs($operator)
            ->post(route('kontrak-eksternal.parse'), ['file' => $file])
            ->assertForbidden();
    }
}
