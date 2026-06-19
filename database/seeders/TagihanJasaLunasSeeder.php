<?php

namespace Database\Seeders;

use App\Models\LayananJasa;
use App\Models\MitraJasa;
use App\Models\TagihanJasa;
use App\Models\User;
use App\Services\Pembukuan\PiutangSyncService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * 5 tagihan jasa yang SUDAH DILUNASI mitra dan layak masuk BKU Penerimaan.
 *
 * Alur direplikasi PERSIS seperti produksi
 * (lihat App\Http\Controllers\TagihanJasaController::settleTagihanAsPaid):
 *
 *   TagihanJasa (status LUNAS)
 *     → PiutangSyncService::syncFromLunas()
 *       → TransaksiPenerimaan (status PAID)  [menu Piutang Bendahara Penerimaan]
 *       → BukuKasUmum (DEBIT_MASUK)          [BKU Penerimaan + Buku Pembantu Bank]
 *
 * Memakai service nyata (bukan insert manual) supaya resolusi rekening, akun
 * pendapatan, idempotensi, dan recompute saldo berjalan identik dengan alur UI.
 *
 * Prasyarat (urut di DatabaseSeeder): UserAccountSeeder, MasterLayananJasaSeeder,
 * AkunPendapatanSeeder, RekeningBankDefaultSeeder (rekening Penerimaan), dan
 * MitraJasaSeeder.
 *
 * Idempoten: tagihan updateOrCreate by nomor_tagihan; syncFromLunas idempoten
 * by nomor_invoice + referensi_penerimaan_id (tidak menggandakan baris BKU).
 */
class TagihanJasaLunasSeeder extends Seeder
{
    public function run(): void
    {
        $creatorId = User::role('Admin Jasa')->value('id')
            ?? User::role('Super Admin')->value('id')
            ?? User::query()->value('id');

        if (! $creatorId) {
            $this->command?->warn('⚠ Tidak ada user untuk created_by. TagihanJasaLunasSeeder dilewati.');
            return;
        }

        // Layanan jasa apa pun yang valid (detail tagihan wajib menunjuk layanan).
        $layananId = LayananJasa::query()
            ->where('is_leaf', true)->where('is_active', true)->where('tarif_dasar', '>', 0)
            ->value('id')
            ?? LayananJasa::query()->value('id');

        if (! $layananId) {
            $this->command?->warn('⚠ Tidak ada layanan_jasa. TagihanJasaLunasSeeder dilewati.');
            return;
        }

        $sync = app(PiutangSyncService::class);

        // [kode_mitra, nomor_tagihan, kode_akun (akun.jenis), uraian, total, tgl_publish, tgl_lunas]
        // kode_akun cocok dengan master AkunPendapatan (lihat AkunPendapatanSeeder).
        $rows = [
            ['MJ-001', 'INV/JASA/2026/06/001', '424115.905', 'PJP2U Periode Mei 2026',                 15_750_000, '2026-05-15', '2026-06-03'],
            ['MJ-002', 'INV/JASA/2026/06/002', '424115.907', 'Pemakaian Garbarata Periode Mei 2026',     8_500_000, '2026-05-20', '2026-06-06'],
            ['MJ-003', 'INV/JASA/2026/06/003', '424923.912', 'Sewa Ruangan Konter Periode Mei 2026',    12_000_000, '2026-05-22', '2026-06-09'],
            ['MJ-004', 'INV/JASA/2026/06/004', '424921.911', 'Penggunaan Lahan Parkir Periode Mei 2026', 5_250_000, '2026-05-25', '2026-06-12'],
            ['MJ-005', 'INV/JASA/2026/06/005', '424312.931', 'Konsesi Usaha Triwulan II 2026',          22_300_000, '2026-05-28', '2026-06-16'],
        ];

        $posted = 0;

        foreach ($rows as [$kodeMitra, $nomor, $kodeAkun, $uraian, $total, $publish, $lunas]) {
            $mitra = MitraJasa::where('kode_mitra', $kodeMitra)->first();

            if (! $mitra) {
                $this->command?->warn("⚠ Mitra {$kodeMitra} tidak ditemukan. Lewati {$nomor}.");
                continue;
            }

            $publishDate = Carbon::parse($publish);
            $lunasDate = Carbon::parse($lunas);

            $tagihan = TagihanJasa::updateOrCreate(
                ['nomor_tagihan' => $nomor],
                [
                    'mitra_id' => null, // master_pihak shadow dibuat otomatis oleh PiutangSyncService
                    'mitra_jasa_id' => $mitra->id,
                    'tipe_pnbp' => 'FUNGSI',
                    'tanggal_tagihan' => $publishDate->toDateString(),
                    'tanggal_publish' => $publishDate->toDateString(),
                    'jumlah_hari_jatuh_tempo' => 30,
                    'tanggal_jatuh_tempo' => $publishDate->copy()->addDays(30)->toDateString(),
                    'total_tagihan' => $total,
                    'status' => 'LUNAS',
                    'status_pembayaran' => 'lunas',
                    'tanggal_lunas' => $lunasDate->toDateString(),
                    'jumlah_dibayar' => $total,
                    'sisa_tagihan' => 0,
                    'created_by' => $creatorId,
                ],
            );

            if (! $tagihan->details()->exists()) {
                $tagihan->details()->create([
                    'layanan_jasa_id' => $layananId,
                    'kode_akun' => $kodeAkun,
                    'qty' => 1,
                    'harga_satuan' => $total,
                    'subtotal' => $total,
                    'keterangan' => $uraian,
                ]);
            }

            $bku = $sync->syncFromLunas(
                $tagihan->fresh(['mitra', 'mitraLegacy', 'details']),
                [
                    'amount' => (float) $total,
                    'paid_at' => $lunasDate,
                    'reference' => 'SEED-LUNAS/' . $nomor,
                ],
            );

            if ($bku) {
                $posted++;
                $this->command?->info("✓ {$nomor} — {$mitra->nama_mitra} → BKU Rp " . number_format($total, 0, ',', '.'));
            } else {
                $this->command?->warn("⚠ {$nomor} gagal masuk BKU (cek storage/logs/laravel.log).");
            }
        }

        $this->command?->info("Selesai: {$posted}/" . count($rows) . ' tagihan lunas tercatat di BKU Penerimaan.');
    }
}
