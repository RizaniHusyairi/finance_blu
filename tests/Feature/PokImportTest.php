<?php

namespace Tests\Feature;

use App\Models\DetailDipa;
use App\Models\MasterCoa;
use App\Models\MasterDipa;
use App\Models\RiwayatRevisiDipa;
use App\Models\User;
use App\Support\Pok\PokPdfParser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PokImportTest extends TestCase
{
    use RefreshDatabase;

    private const POK_AWAL = __DIR__ . '/../../docs/DIPA/POK T.A 2026 DIPA awal.pdf';

    private User $admin;

    private MasterDipa $dipa;

    private RiwayatRevisiDipa $revision;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        Role::findOrCreate('Super Admin', 'web');
        $this->admin = User::factory()->create();
        $this->admin->assignRole('Super Admin');

        $this->dipa = MasterDipa::create([
            'nomor_dipa' => 'DIPA-025.01.2.288745/2026',
            'tahun_anggaran' => 2026,
            'tanggal_disahkan' => '2025-12-15',
            'revisi_aktif_ke' => 0,
            'status_aktif' => true,
        ]);

        $this->revision = RiwayatRevisiDipa::create([
            'master_dipa_id' => $this->dipa->id,
            'nomor_revisi' => 0,
            'tanggal_revisi' => '2025-12-15',
            'total_pagu' => 0,
            'is_active' => true,
        ]);
    }

    public function test_parser_membaca_pok_awal_seimbang_dengan_alokasi(): void
    {
        $hasil = (new PokPdfParser())->parse(self::POK_AWAL);

        $this->assertSame(2026, $hasil['tahun']);
        $this->assertSame(89112363000.0, $hasil['alokasi']);
        $this->assertCount(226, $hasil['rows']);
        $this->assertSame(89112363000.0, array_sum(array_column($hasil['rows'], 'jumlah')));

        // Baris yang sama dengan COA hasil input manual sebelumnya harus identik kodenya.
        $kode = array_column($hasil['rows'], 'kode_mak_lengkap');
        $this->assertContains('GA.4646.CBE.002.052.B.525112.00001', $kode);
        $this->assertContains('WA.4611.EBA.994.002.N.525111.00001', $kode);
    }

    public function test_importer_menulis_coa_dan_item_secara_idempoten(): void
    {
        $hasil = (new PokPdfParser())->parse(self::POK_AWAL);

        $putaranPertama = (new \App\Support\Pok\PokImporter())->importRows($hasil['rows'], $this->revision);
        $this->assertSame(['dibuat' => 226, 'dilewati' => 0], $putaranPertama);

        $this->assertSame(226, DetailDipa::where('dipa_revision_id', $this->revision->id)->count());
        $this->assertSame(226, MasterCoa::count());
        $this->assertSame(
            89112363000.0,
            (float) DetailDipa::where('dipa_revision_id', $this->revision->id)->sum('nilai_pagu')
        );

        $this->assertDatabaseHas('master_coas', [
            'kode_mak_lengkap' => 'GA.4646.CBE.002.052.B.525112.00001',
            'nama_akun' => 'Latihan Rutin PKP-PK',
            'sumber_dana' => 'BLU',
        ]);
        $this->assertDatabaseHas('master_coas', [
            'kode_mak_lengkap' => 'GA.1960.QAH.001.051.A.521219.00001',
            'sumber_dana' => 'RM',
        ]);

        $item = DetailDipa::whereHas('coa', fn ($q) => $q->where('kode_mak_lengkap', 'GA.4646.CBE.002.052.B.525112.00001'))->first();
        $this->assertSame(12.0, (float) $item->volume);
        $this->assertSame('bln', $item->satuan);
        $this->assertSame(1500000.0, (float) $item->harga_satuan);
        $this->assertSame(18000000.0, (float) $item->nilai_pagu);

        // Putaran kedua: seluruh baris dilewati, tidak ada duplikasi.
        $putaranKedua = (new \App\Support\Pok\PokImporter())->importRows($hasil['rows'], $this->revision);
        $this->assertSame(['dibuat' => 0, 'dilewati' => 226], $putaranKedua);
        $this->assertSame(226, DetailDipa::where('dipa_revision_id', $this->revision->id)->count());
        $this->assertSame(226, MasterCoa::count());
    }

    public function test_endpoint_parse_mengembalikan_ringkasan_dan_saran_nomor(): void
    {
        $file = new UploadedFile(self::POK_AWAL, 'pok-awal.pdf', 'application/pdf', null, true);

        $response = $this->actingAs($this->admin)->postJson(route('dipas.parse-pok'), [
            'file_pok' => $file,
        ]);

        $response->assertOk()->assertJson([
            'ok' => true,
            'tahun' => 2026,
            'alokasi' => 89112363000,
            'total' => 89112363000,
            'seimbang' => true,
            'jumlah_baris' => 226,
            'saran_nomor' => 'DIPA-022.05.2.288745/2026',
        ]);

        // Pratinjau lengkap: seluruh baris ikut dikirim untuk tabel di form.
        $this->assertCount(226, $response->json('rows'));
        $barisPertama = $response->json('rows.0');
        $this->assertSame('GA.1960.QAH.001.051.A.521219.00001', $barisPertama['kode_mak_lengkap']);
        $this->assertSame('Subsidi Angkutan Udara Perintis', $barisPertama['nama']);
        $this->assertSame('RM', $barisPertama['sumber_dana']);

        $token = $response->json('token');
        $this->assertTrue(Storage::disk('local')->exists('pok-import/' . $token . '.pdf'));
        Storage::disk('local')->delete('pok-import/' . $token . '.pdf');
    }

    public function test_tambah_dipa_dengan_pok_token_membuat_coa_otomatis(): void
    {
        $file = new UploadedFile(self::POK_AWAL, 'pok-awal.pdf', 'application/pdf', null, true);
        $token = $this->actingAs($this->admin)
            ->postJson(route('dipas.parse-pok'), ['file_pok' => $file])
            ->json('token');

        $response = $this->actingAs($this->admin)->post(route('dipas.store'), [
            'nomor_dipa' => 'DIPA-022.05.2.288745/2026',
            'tahun_anggaran' => 2026,
            'tanggal_disahkan' => '2025-12-15',
            'status_aktif' => 1,
            'total_pagu' => 89112363000,
            'pok_token' => $token,
        ]);

        $response->assertRedirect(route('dipas.index'));
        $response->assertSessionHas('success', fn ($msg) => str_contains($msg, '226 COA hasil impor POK'));

        $dipaBaru = MasterDipa::where('nomor_dipa', 'DIPA-022.05.2.288745/2026')->firstOrFail();
        $revisiAwal = $dipaBaru->activeRevision;

        $this->assertSame(226, DetailDipa::where('dipa_revision_id', $revisiAwal->id)->count());
        $this->assertSame(
            89112363000.0,
            (float) DetailDipa::where('dipa_revision_id', $revisiAwal->id)->sum('nilai_pagu')
        );
        $this->assertFalse(Storage::disk('local')->exists('pok-import/' . $token . '.pdf'));
    }

    public function test_tambah_revisi_dengan_pok_token_mengimpor_dan_melewati_salin(): void
    {
        // Revisi aktif punya 1 item lama yang nilainya beda dengan POK —
        // bila salin menang, nilai lama akan menimpa angka POK.
        $coaLama = MasterCoa::create([
            'kd_akun' => '525112',
            'kode_mak_lengkap' => 'GA.4646.CBE.002.052.B.525112.00001',
            'nama_akun' => 'Latihan Rutin PKP-PK',
            'sumber_dana' => 'BLU',
            'status_aktif' => true,
        ]);
        DetailDipa::create([
            'dipa_revision_id' => $this->revision->id,
            'coa_id' => $coaLama->id,
            'nilai_pagu' => 99999,
            'status_aktif' => true,
        ]);

        $file = new UploadedFile(self::POK_AWAL, 'pok-revisi.pdf', 'application/pdf', null, true);
        $parse = $this->actingAs($this->admin)->postJson(route('dipas.parse-pok'), ['file_pok' => $file]);
        $this->assertSame('2025-12-15', $parse->json('tanggal_ttd'));
        $token = $parse->json('token');

        $this->actingAs($this->admin)->post(route('dipas.revisions.store', $this->dipa), [
            'nomor_revisi' => 1,
            'tanggal_revisi' => '2026-01-10',
            'total_pagu' => 89112363000,
            'salin_item_anggaran' => 1,
            'pok_token' => $token,
        ])->assertRedirect(route('dipas.show', $this->dipa));

        $revisiBaru = RiwayatRevisiDipa::where('master_dipa_id', $this->dipa->id)
            ->where('nomor_revisi', 1)
            ->firstOrFail();

        $this->assertSame(226, DetailDipa::where('dipa_revision_id', $revisiBaru->id)->count());

        // Nilai dari POK yang dipakai, bukan hasil salin revisi lama.
        $item = DetailDipa::where('dipa_revision_id', $revisiBaru->id)
            ->where('coa_id', $coaLama->id)
            ->firstOrFail();
        $this->assertSame(18000000.0, (float) $item->nilai_pagu);
    }

}
