<?php

namespace Tests\Feature;

use App\Models\AkunPendapatan;
use App\Models\BukuKasUmum;
use App\Models\DetailMutasiBank;
use App\Models\ImportMutasiBank;
use App\Models\RekeningBank;
use App\Models\TransaksiPembukuan;
use App\Services\Pembukuan\BukuPembantuService;
use App\Services\Pembukuan\PostingPembukuanService;
use App\Services\Pembukuan\PostingPenerimaanService;
use App\Services\Pembukuan\RealisasiPenerimaanService;
use Database\Seeders\AkunPendapatanSeeder;
use Database\Seeders\KodeTransaksiSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Uji integrasi mesin pembukuan SILABI: distribusi kode transaksi, invariant kas,
 * idempotensi, klasifikasi penerimaan dari koran, dan rekap realisasi.
 */
class SilabiPembukuanTest extends TestCase
{
    use RefreshDatabase;

    private function rekening(string $jenis = 'PENGELUARAN'): RekeningBank
    {
        return RekeningBank::create([
            'pemilik_type' => 'App\\Models\\User', 'pemilik_id' => 1, 'nama_bank' => 'Uji',
            'nomor_rekening' => uniqid(), 'nama_rekening' => 'Uji', 'jenis_rekening' => $jenis,
            'saldo_awal' => 0, 'status_aktif' => true,
        ]);
    }

    private function jurnal(RekeningBank $rek, string $kode, float $nom): TransaksiPembukuan
    {
        return TransaksiPembukuan::create([
            'tanggal' => '2026-02-10', 'no_bukti' => 'T/' . uniqid(), 'kode_transaksi' => $kode,
            'uraian' => $kode, 'jumlah_kotor' => $nom, 'rekening_bank_id' => $rek->id,
        ]);
    }

    public function test_kode_i2_distribusi_ke_bku_bank_ls_pengesahan(): void
    {
        $this->seed(KodeTransaksiSeeder::class);
        $rek = $this->rekening();
        app(PostingPembukuanService::class)->post($this->jurnal($rek, 'I2', 10_000_000));

        $keys = BukuKasUmum::where('sumber_rekening_id', $rek->id)->get()
            ->map(fn ($r) => $r->kode_buku . '|' . $r->arus_kas)->all();

        // I2: BKU(1)/Bank(3)/LS Bendahara(6) KREDIT + Pengesahan(11) DEBIT.
        $this->assertEqualsCanonicalizing(
            ['1|KREDIT_KELUAR', '3|KREDIT_KELUAR', '6|KREDIT_KELUAR', '11|DEBIT_MASUK'],
            $keys
        );
    }

    public function test_g1_wash_dan_invariant_bku_sama_tunai_plus_bank(): void
    {
        $this->seed(KodeTransaksiSeeder::class);
        $rek = $this->rekening();
        $post = app(PostingPembukuanService::class);
        $post->post($this->jurnal($rek, 'C1', 10_000_000)); // terima ke bank
        $post->post($this->jurnal($rek, 'G1', 3_000_000));  // tarik tunai (wash di BKU)
        $post->post($this->jurnal($rek, 'I2', 2_000_000));  // belanja LS bank

        $buku = app(BukuPembantuService::class);
        $f = ['rekening_bank_id' => $rek->id];
        $this->assertSame(8_000_000.0, $buku->buildBuku(1, 'PENGELUARAN', $f)['saldo_akhir']);
        $this->assertSame(3_000_000.0, $buku->buildBuku(2, 'PENGELUARAN', $f)['saldo_akhir']);
        $this->assertSame(5_000_000.0, $buku->buildBuku(3, 'PENGELUARAN', $f)['saldo_akhir']);

        $inv = $buku->invariant('PENGELUARAN', $f);
        $this->assertTrue($inv['seimbang']);
        $this->assertSame(0.0, $inv['selisih']);
    }

    public function test_posting_idempoten(): void
    {
        $this->seed(KodeTransaksiSeeder::class);
        $rek = $this->rekening();
        $trx = $this->jurnal($rek, 'I2', 1_000_000);
        $post = app(PostingPembukuanService::class);
        $post->post($trx);
        $post->post($trx);

        $this->assertSame(4, BukuKasUmum::where('transaksi_pembukuan_id', $trx->id)->count());
    }

    public function test_klasifikasi_penerimaan_dari_rekening_koran(): void
    {
        $this->seed(AkunPendapatanSeeder::class);
        $rek = $this->rekening('PENERIMAAN');
        $imp = ImportMutasiBank::create(['rekening_bank_id' => $rek->id, 'nama_file_asli' => 'x',
            'path_file' => '', 'status_import' => 'PARSED', 'uploaded_at' => now()]);
        foreach ([['Konsesi januari 2024 cv.koetai', 287_650], ['BBSRI_LISTRIK DESEMBER 2023', 904_640]] as [$d, $n]) {
            DetailMutasiBank::create(['import_mutasi_bank_id' => $imp->id, 'tanggal_transaksi' => '2026-02-10',
                'deskripsi' => $d, 'debit' => 0, 'kredit' => $n, 'arah_mutasi' => 'MASUK', 'status_rekonsiliasi' => 'BELUM']);
        }

        $res = app(PostingPenerimaanService::class)->postBatch(['rekening_bank_id' => $rek->id]);
        $this->assertSame(2, $res['posted']);
        $this->assertSame(2, $res['classified']);

        $kode = BukuKasUmum::where('peran', 'PENERIMAAN')->where('sumber_rekening_id', $rek->id)
            ->with('akunPendapatan')->get()->pluck('akunPendapatan.kode_akun')->all();
        $this->assertContains('424312', $kode); // Konsesi
        $this->assertContains('424919', $kode); // Tagihan Listrik
    }

    public function test_realisasi_diagregasi_per_akun_dan_bulan(): void
    {
        $this->seed(AkunPendapatanSeeder::class);
        $rek = $this->rekening('PENERIMAAN');
        $konsesi = AkunPendapatan::where('kode_akun', '424312')->first();
        foreach ([['2026-01-10', 10_000_000], ['2026-01-20', 5_000_000]] as [$tgl, $n]) {
            BukuKasUmum::create(['tanggal_transaksi' => $tgl, 'nomor_bukti' => uniqid(), 'uraian' => 'r',
                'arus_kas' => 'DEBIT_MASUK', 'peran' => 'PENERIMAAN', 'kode_buku' => 1, 'nominal' => $n,
                'saldo_akhir' => 0, 'sumber_rekening_id' => $rek->id, 'akun_pendapatan_id' => $konsesi->id]);
        }

        $m = app(RealisasiPenerimaanService::class)->build(2026, ['rekening_bank_id' => $rek->id]);
        $this->assertSame(15_000_000.0, $m['grand_total']);
        $this->assertSame(15_000_000.0, $m['total_per_bulan'][1]);
    }
}
