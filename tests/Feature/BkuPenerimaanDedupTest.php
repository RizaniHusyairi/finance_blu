<?php

namespace Tests\Feature;

use App\Models\BukuKasUmum;
use App\Models\DetailMutasiBank;
use App\Models\ImportMutasiBank;
use App\Models\MasterCoa;
use App\Models\MasterPihak;
use App\Models\RekeningBank;
use App\Models\TransaksiPenerimaan;
use App\Services\Pembukuan\PiutangRekonsiliasiService;
use App\Services\Pembukuan\PostingPenerimaanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Cegah dobel-hitung BKU Penerimaan: satu penerimaan tidak boleh menghasilkan
 * dua baris DEBIT_MASUK walau tercatat dari dua jalur (penagihan jasa &
 * klasifikasi rekening koran). Lihat guard di PostingPenerimaanService &
 * PiutangSyncService, serta matcher/linker di PiutangRekonsiliasiService.
 */
class BkuPenerimaanDedupTest extends TestCase
{
    use RefreshDatabase;

    private function rekening(): RekeningBank
    {
        return RekeningBank::create([
            'pemilik_type' => 'App\\Models\\User', 'pemilik_id' => 1, 'nama_bank' => 'Uji',
            'nomor_rekening' => uniqid(), 'nama_rekening' => 'Uji', 'jenis_rekening' => 'PENERIMAAN',
            'saldo_awal' => 0, 'status_aktif' => true,
        ]);
    }

    private function piutang(float $nominal, string $tanggal): TransaksiPenerimaan
    {
        $pihak = MasterPihak::create(['kategori' => 'PENERIMAAN', 'nama_pihak' => 'Mitra ' . uniqid()]);
        $coa = MasterCoa::create(['nama_akun' => 'Pendapatan PNBP', 'kd_akun' => '424312']);

        return TransaksiPenerimaan::create([
            'mitra_id' => $pihak->id,
            'coa_id' => $coa->id,
            'nomor_invoice' => 'INV/' . uniqid(),
            'tanggal_invoice' => $tanggal,
            'tanggal_jatuh_tempo' => $tanggal,
            'nominal_tagihan' => $nominal,
            'status_pembayaran' => 'UNPAID',
        ]);
    }

    /** Baris BKU Penerimaan "Path Jasa" (punya referensi_penerimaan_id, belum bertaut koran). */
    private function bkuJasa(RekeningBank $rek, TransaksiPenerimaan $p, float $nominal, string $tanggal): BukuKasUmum
    {
        return BukuKasUmum::create([
            'tanggal_transaksi' => $tanggal, 'nomor_bukti' => 'BKU-MASUK/' . uniqid(), 'uraian' => 'jasa',
            'arus_kas' => 'DEBIT_MASUK', 'peran' => 'PENERIMAAN', 'kode_buku' => 1, 'nominal' => $nominal,
            'saldo_akhir' => 0, 'sumber_rekening_id' => $rek->id, 'referensi_penerimaan_id' => $p->id,
        ]);
    }

    private function koranRow(RekeningBank $rek, float $nominal, string $tanggal): DetailMutasiBank
    {
        $imp = ImportMutasiBank::create(['rekening_bank_id' => $rek->id, 'nama_file_asli' => 'x',
            'path_file' => '', 'status_import' => 'PARSED', 'uploaded_at' => now()]);

        return DetailMutasiBank::create(['import_mutasi_bank_id' => $imp->id, 'tanggal_transaksi' => $tanggal,
            'deskripsi' => 'Penerimaan jasa', 'debit' => 0, 'kredit' => $nominal,
            'arah_mutasi' => 'MASUK', 'status_rekonsiliasi' => 'BELUM']);
    }

    private function debitRows(RekeningBank $rek)
    {
        return BukuKasUmum::where('peran', 'PENERIMAAN')
            ->where('arus_kas', 'DEBIT_MASUK')
            ->where('sumber_rekening_id', $rek->id)
            ->get();
    }

    public function test_jasa_dulu_lalu_koran_digabung_bukan_diduplikasi(): void
    {
        $rek = $this->rekening();
        $p = $this->piutang(1_000_000, '2026-02-10');
        $this->bkuJasa($rek, $p, 1_000_000, '2026-02-10');

        $koran = $this->koranRow($rek, 1_000_000, '2026-02-10');
        app(PostingPenerimaanService::class)->post($koran);

        $rows = $this->debitRows($rek);
        $this->assertCount(1, $rows, 'Tidak boleh ada baris penerimaan ganda.');
        $this->assertSame($p->id, (int) $rows->first()->referensi_penerimaan_id);
        $this->assertSame($koran->id, (int) $rows->first()->detail_mutasi_bank_id);
    }

    public function test_koran_dulu_lalu_piutang_ditautkan_bukan_diduplikasi(): void
    {
        $rek = $this->rekening();
        $koran = $this->koranRow($rek, 500_000, '2026-02-12');
        app(PostingPenerimaanService::class)->post($koran);

        $p = $this->piutang(500_000, '2026-02-12');

        // Simulasikan guard PiutangSyncService::syncFromLunas (matcher + linker bersama).
        $reko = app(PiutangRekonsiliasiService::class);
        $found = $reko->findKoranRowForPiutang($rek->id, 500_000, '2026-02-12');
        $this->assertNotNull($found);
        $reko->attachPiutang($found, $p);

        $rows = $this->debitRows($rek);
        $this->assertCount(1, $rows);
        $this->assertSame($p->id, (int) $rows->first()->referensi_penerimaan_id);
        $this->assertSame('PAID', $p->refresh()->status_pembayaran);
    }

    public function test_penerimaan_non_jasa_tetap_satu_baris(): void
    {
        $rek = $this->rekening();
        app(PostingPenerimaanService::class)->post($this->koranRow($rek, 250_000, '2026-02-13'));

        $rows = $this->debitRows($rek);
        $this->assertCount(1, $rows);
        $this->assertNull($rows->first()->referensi_penerimaan_id);
        $this->assertNotNull($rows->first()->detail_mutasi_bank_id);
    }

    public function test_nominal_berbeda_tidak_ikut_tergabung(): void
    {
        $rek = $this->rekening();
        $p = $this->piutang(1_000_000, '2026-02-10');
        $this->bkuJasa($rek, $p, 1_000_000, '2026-02-10');

        // Koran nominal berbeda → penerimaan lain, tidak boleh merge.
        app(PostingPenerimaanService::class)->post($this->koranRow($rek, 999_000, '2026-02-10'));

        $this->assertCount(2, $this->debitRows($rek));
    }

    public function test_automatch_menautkan_piutang_belum_lunas_ke_baris_koran(): void
    {
        $rek = $this->rekening();
        $koran = $this->koranRow($rek, 750_000, '2026-02-15');
        app(PostingPenerimaanService::class)->post($koran);

        $p = $this->piutang(750_000, '2026-02-15');

        $res = app(PiutangRekonsiliasiService::class)->autoMatch(['rekening_bank_id' => $rek->id]);

        $this->assertSame(1, $res['matched']);
        $this->assertCount(1, $this->debitRows($rek));
        $this->assertSame($p->id, (int) $this->debitRows($rek)->first()->referensi_penerimaan_id);
        $this->assertSame('PAID', $p->refresh()->status_pembayaran);
    }
}
