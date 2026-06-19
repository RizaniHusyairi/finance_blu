<?php

namespace Tests\Feature;

use App\Models\BukuKasUmum;
use App\Models\RekeningBank;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BkuSaldoRecalculationTest extends TestCase
{
    use RefreshDatabase;

    private function makeRekening(): RekeningBank
    {
        // pemilik adalah morph tanpa FK; cukup nilai arbitrer untuk uji saldo.
        return RekeningBank::create([
            'pemilik_type' => 'App\\Models\\User',
            'pemilik_id' => 1,
            'nama_bank' => 'Bank Uji',
            'nomor_rekening' => '1234567890',
            'nama_rekening' => 'Bendahara Uji',
            'is_default' => true,
            'status_aktif' => true,
        ]);
    }

    private function makeRow(int $rekeningId, string $tanggal, string $arus, float $nominal, float $saldoAwal, string $peran = 'PENGELUARAN'): BukuKasUmum
    {
        return BukuKasUmum::create([
            'tanggal_transaksi' => $tanggal,
            'nomor_bukti' => 'BUKTI/' . uniqid(),
            'uraian' => 'Uji',
            'arus_kas' => $arus,
            // Saldo berjalan dihitung per (rekening, peran, kode_buku); samakan
            // peran baris dengan jenis rekening agar seed saldo awal berlaku.
            'peran' => $peran,
            'nominal' => $nominal,
            // Saldo "salah" sengaja, meniru estimasi best-effort masing-masing service.
            'saldo_akhir' => $saldoAwal,
            'sumber_rekening_id' => $rekeningId,
        ]);
    }

    public function test_recalculate_running_balance_follows_chronological_order(): void
    {
        $rekening = $this->makeRekening();

        // Dimasukkan TIDAK urut tanggal:
        //  - r1: 10 Jan, DEBIT_MASUK 1.000 (saldo estimasi 1.000)
        //  - r2: 20 Jan, KREDIT_KELUAR 300 (saldo estimasi 700)
        $r1 = $this->makeRow($rekening->id, '2026-01-10', 'DEBIT_MASUK', 1000, 1000);
        $r2 = $this->makeRow($rekening->id, '2026-01-20', 'KREDIT_KELUAR', 300, 700);

        // Baris BACK-DATED disisipkan belakangan: 05 Jan, DEBIT_MASUK 500.
        // Karena dihitung dari "baris terakhir" (saldo 700), estimasinya 1.200 — salah.
        $r3 = $this->makeRow($rekening->id, '2026-01-05', 'DEBIT_MASUK', 500, 1200);

        BukuKasUmum::recalculateRunningBalance($rekening->id);

        $r1->refresh();
        $r2->refresh();
        $r3->refresh();

        // Urutan kronologis: 05 Jan (+500=500), 10 Jan (+1000=1500), 20 Jan (-300=1200).
        $this->assertSame(500.0, (float) $r3->saldo_akhir, 'Back-dated row salah');
        $this->assertSame(1500.0, (float) $r1->saldo_akhir, 'Saldo setelah back-date tidak terkoreksi');
        $this->assertSame(1200.0, (float) $r2->saldo_akhir, 'Saldo baris terakhir tidak terkoreksi');
    }

    public function test_recalculate_seeds_from_saldo_awal_and_ignores_pre_period_rows(): void
    {
        // Rekening dengan saldo awal 1.000.000 berlaku sejak 01 Feb 2026.
        $rekening = RekeningBank::create([
            'pemilik_type' => 'App\\Models\\User',
            'pemilik_id' => 1,
            'nama_bank' => 'Bank Uji',
            'nomor_rekening' => '9988776655',
            'nama_rekening' => 'Bendahara Penerimaan Uji',
            'jenis_rekening' => 'PENERIMAAN',
            'saldo_awal' => 1000000,
            'saldo_awal_per_tanggal' => '2026-02-01',
            'is_default' => true,
            'status_aktif' => true,
        ]);

        // Baris PRA-periode (31 Jan) harus diabaikan dari saldo berjalan.
        $pre = $this->makeRow($rekening->id, '2026-01-31', 'DEBIT_MASUK', 500, 500, 'PENERIMAAN');
        // Baris in-periode: 05 Feb +200, 10 Feb -300.
        $a = $this->makeRow($rekening->id, '2026-02-05', 'DEBIT_MASUK', 200, 0, 'PENERIMAAN');
        $b = $this->makeRow($rekening->id, '2026-02-10', 'KREDIT_KELUAR', 300, 0, 'PENERIMAAN');

        BukuKasUmum::recalculateRunningBalance($rekening->id);

        $pre->refresh();
        $a->refresh();
        $b->refresh();

        // Saldo mulai dari 1.000.000: 05 Feb → 1.000.200, 10 Feb → 999.900.
        $this->assertSame(1000200.0, (float) $a->saldo_akhir, 'Saldo tidak dimulai dari saldo awal');
        $this->assertSame(999900.0, (float) $b->saldo_akhir, 'Saldo berjalan setelah saldo awal salah');
        // Baris pra-periode tidak ikut dihitung → saldo_akhir-nya tidak diubah recompute.
        $this->assertSame(500.0, (float) $pre->saldo_akhir, 'Baris pra-periode seharusnya diabaikan');
    }

    public function test_recalculate_does_not_touch_updated_at(): void
    {
        $rekening = $this->makeRekening();

        $row = $this->makeRow($rekening->id, '2026-02-01', 'DEBIT_MASUK', 250, 9999);
        $originalUpdatedAt = $row->updated_at;

        BukuKasUmum::recalculateRunningBalance($rekening->id);
        $row->refresh();

        $this->assertSame(250.0, (float) $row->saldo_akhir);
        $this->assertEquals($originalUpdatedAt->timestamp, $row->updated_at->timestamp, 'updated_at tidak boleh berubah');
    }

    /**
     * Case A: mutasi non-jasa manual (PBK keluar) di BKU Penerimaan — tanpa akun
     * pendapatan, pakai jenis_transaksi — mengurangi saldo berjalan; soft-delete
     * mengembalikan saldo (baris terhapus diabaikan recompute).
     */
    public function test_manual_penerimaan_kredit_keluar_mengurangi_saldo_dan_delete_memulihkan(): void
    {
        $rekening = RekeningBank::create([
            'pemilik_type' => 'App\\Models\\User',
            'pemilik_id' => 1,
            'nama_bank' => 'Bank Uji',
            'nomor_rekening' => '5566778899',
            'nama_rekening' => 'Bendahara Penerimaan Uji',
            'jenis_rekening' => 'PENERIMAAN',
            'saldo_awal' => 1_000_000,
            'saldo_awal_per_tanggal' => '2026-06-01',
            'is_default' => true,
            'status_aktif' => true,
        ]);

        $pbk = BukuKasUmum::create([
            'tanggal_transaksi' => '2026-06-10',
            'nomor_bukti' => 'PBK/UJI',
            'uraian' => 'PBK OPS PENGELUARAN',
            'arus_kas' => 'KREDIT_KELUAR',
            'peran' => 'PENERIMAAN',
            'kode_buku' => 1,
            'akun_pendapatan_id' => null,
            'jenis_transaksi' => 'PEMINDAHBUKUAN',
            'nominal' => 250_000,
            'saldo_akhir' => 0,
            'sumber_rekening_id' => $rekening->id,
        ]);

        BukuKasUmum::recalculateRunningBalance($rekening->id, 1);
        $pbk->refresh();

        // Saldo awal 1.000.000 - 250.000 = 750.000; enum cast aktif.
        $this->assertSame(750000.0, (float) $pbk->saldo_akhir);
        $this->assertSame(\App\Enums\JenisTransaksiPenerimaan::PEMINDAHBUKUAN, $pbk->jenis_transaksi);

        // Soft-delete → baris diabaikan; saldo akhir buku kembali ke saldo awal.
        $pbk->delete();
        BukuKasUmum::recalculateRunningBalance($rekening->id, 1);

        $b = app(\App\Services\Pembukuan\BukuPembantuService::class)
            ->buildBuku(1, 'PENERIMAAN', ['rekening_bank_id' => $rekening->id]);
        $this->assertSame(1000000.0, $b['saldo_akhir']);
    }
}
