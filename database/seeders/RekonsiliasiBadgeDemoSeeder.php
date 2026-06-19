<?php

namespace Database\Seeders;

use App\Models\BukuKasUmum;
use App\Models\DetailMutasiBank;
use App\Models\ImportMutasiBank;
use App\Models\MasterCoa;
use App\Models\MasterPihak;
use App\Models\RekeningBank;
use App\Models\TransaksiPenerimaan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Contoh 6 baris BKU Penerimaan yang mendemonstrasikan 3 badge verifikasi
 * (lihat resources/views/pembukuan/penerimaan/index.blade.php):
 *
 *   🟢 Terverifikasi   → punya referensi_penerimaan_id (tagihan) DAN detail_mutasi_bank_id (koran)
 *   🟡 Belum cocok bank → hanya referensi_penerimaan_id (tagihan saja, tanpa bukti koran)
 *   🔵 Bank koran       → hanya detail_mutasi_bank_id (koran saja, belum tertaut tagihan)
 *
 * Di halaman Klasifikasi, baris yang punya koran ikut menampilkan:
 *   🟢 Cocok (bila BKU-nya bertaut tagihan) / 🔵 Terposting (bila belum).
 *
 * Idempoten: di-key oleh nomor_bukti "DEMO-BADGE/N". Bukan bagian DatabaseSeeder
 * (data ilustrasi). Hapus contoh ini:
 *   BukuKasUmum::where('nomor_bukti','like','DEMO-BADGE/%')->forceDelete();
 *   TransaksiPenerimaan::where('nomor_invoice','like','DEMO-BADGE/%')->forceDelete();
 */
class RekonsiliasiBadgeDemoSeeder extends Seeder
{
    public function run(): void
    {
        $rek = RekeningBank::query()
            ->where('jenis_rekening', 'PENERIMAAN')->where('status_aktif', true)
            ->orderByDesc('is_terkunci')->orderByDesc('is_default')->orderBy('id')
            ->first();

        if (! $rek) {
            $this->command?->warn('⚠ Tidak ada rekening Penerimaan aktif. Seeder dilewati.');
            return;
        }

        $coaId = MasterCoa::query()->value('id');
        $mitraId = MasterPihak::query()->where('kategori', '!=', 'PENGELUARAN')->value('id')
            ?? MasterPihak::query()->value('id');

        // Wadah impor untuk baris koran demo (dibuat sekali).
        $import = ImportMutasiBank::firstOrCreate(
            ['rekening_bank_id' => $rek->id, 'nama_file_asli' => '(demo badge)'],
            ['path_file' => '', 'status_import' => 'PARSED', 'uploaded_at' => now()],
        );

        // [label, punya_tagihan, punya_koran, nominal]
        $cases = [
            ['Terverifikasi (bank + tagihan)', true,  true,  1_111_000],
            ['Terverifikasi (bank + tagihan)', true,  true,  2_222_000],
            ['Belum cocok bank (tagihan saja)', true,  false, 3_333_000],
            ['Belum cocok bank (tagihan saja)', true,  false, 4_444_000],
            ['Bank koran (koran saja)',          false, true,  5_555_000],
            ['Bank koran (koran saja)',          false, true,  6_666_000],
        ];

        $tanggal = '2026-06-20';
        $dibuat = 0;

        DB::transaction(function () use ($cases, $rek, $coaId, $mitraId, $import, $tanggal, &$dibuat) {
            foreach ($cases as $i => [$label, $punyaTagihan, $punyaKoran, $nominal]) {
                $nomor = 'DEMO-BADGE/' . ($i + 1);

                if (BukuKasUmum::where('nomor_bukti', $nomor)->exists()) {
                    continue; // idempoten
                }

                $piutangId = null;
                if ($punyaTagihan) {
                    $piutangId = TransaksiPenerimaan::create([
                        'nomor_invoice' => $nomor,
                        'mitra_id' => $mitraId,
                        'coa_id' => $coaId,
                        'tanggal_invoice' => $tanggal,
                        'nominal_tagihan' => $nominal,
                        'total_dibayar' => $nominal,
                        'status_pembayaran' => 'PAID',
                        'keterangan' => 'DEMO BADGE: ' . $label,
                    ])->id;
                }

                $koranId = null;
                if ($punyaKoran) {
                    $koranId = DetailMutasiBank::create([
                        'import_mutasi_bank_id' => $import->id,
                        'tanggal_transaksi' => $tanggal,
                        'deskripsi' => 'DEMO BADGE koran: ' . $label,
                        'nomor_referensi_bank' => $nomor,
                        'kredit' => $nominal,
                        'debit' => 0,
                        'arah_mutasi' => 'MASUK',
                        'status_rekonsiliasi' => 'BELUM',
                    ])->id;
                }

                BukuKasUmum::create([
                    'tanggal_transaksi' => $tanggal,
                    'nomor_bukti' => $nomor,
                    'uraian' => 'DEMO BADGE — ' . $label,
                    'arus_kas' => 'DEBIT_MASUK',
                    'peran' => 'PENERIMAAN',
                    'kode_buku' => 1,
                    'nominal' => $nominal,
                    'saldo_akhir' => 0,
                    'sumber_rekening_id' => $rek->id,
                    'referensi_penerimaan_id' => $piutangId,
                    'detail_mutasi_bank_id' => $koranId,
                ]);

                $dibuat++;
            }

            BukuKasUmum::recalculateRunningBalance($rek->id, 1);
        });

        $this->command?->info("✓ {$dibuat} baris demo badge dibuat di BKU Penerimaan ({$rek->nama_bank} - {$rek->nomor_rekening}).");
    }
}
