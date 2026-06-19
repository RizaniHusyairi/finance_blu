<?php

namespace Database\Seeders;

use App\Models\AkunPendapatan;
use App\Models\BukuKasUmum;
use App\Models\MasterCoa;
use App\Models\MasterPihak;
use App\Models\RekeningBank;
use App\Models\TransaksiPenerimaan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Data simulasi: 4 baris BKU Penerimaan berstatus 🟡 "Belum cocok bank"
 * (punya tautan tagihan/piutang, BELUM ada padanan rekening koran).
 *
 * Dipakai untuk menyimulasikan perubahan status menjadi 🟢 "Terverifikasi":
 * impor file `public/Contoh_Rekening_Koran_Simulasi.xlsx` (nominal + tanggal
 * sengaja dibuat cocok), lalu jalankan "Klasifikasi & Posting Massal".
 *
 * Tanggal sengaja di jendela 25–27 Jun 2026 (di luar rentang data CMS lain)
 * agar mudah diisolasi lewat filter tanggal saat Posting Massal.
 *
 * Idempoten (key nomor_bukti "SIM/..."). Bukan bagian DatabaseSeeder.
 * Hapus: BukuKasUmum::where('nomor_bukti','like','SIM/%')->forceDelete();
 *        TransaksiPenerimaan::where('nomor_invoice','like','SIM/%')->forceDelete();
 */
class SimulasiBelumCocokBankSeeder extends Seeder
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
        $akunId = AkunPendapatan::query()->where('kode_akun', '424115')->where('kode_jenis', '905')->value('id')
            ?? AkunPendapatan::query()->value('id');

        // [nomor, nominal, tanggal, uraian]
        $cases = [
            ['SIM/2026/06/001', 7_250_000,  '2026-06-25', 'PJP2U — simulasi (menunggu bukti bank)'],
            ['SIM/2026/06/002', 11_800_000, '2026-06-26', 'Sewa Ruangan — simulasi (menunggu bukti bank)'],
            ['SIM/2026/06/003', 4_950_000,  '2026-06-26', 'Garbarata — simulasi (menunggu bukti bank)'],
            ['SIM/2026/06/004', 18_400_000, '2026-06-27', 'Konsesi — simulasi (menunggu bukti bank)'],
        ];

        $dibuat = 0;

        DB::transaction(function () use ($cases, $rek, $coaId, $mitraId, $akunId, &$dibuat) {
            foreach ($cases as [$nomor, $nominal, $tanggal, $uraian]) {
                if (BukuKasUmum::where('nomor_bukti', $nomor)->exists()) {
                    continue; // idempoten
                }

                $piutangId = TransaksiPenerimaan::create([
                    'nomor_invoice' => $nomor,
                    'mitra_id' => $mitraId,
                    'coa_id' => $coaId,
                    'tanggal_invoice' => $tanggal,
                    'nominal_tagihan' => $nominal,
                    'total_dibayar' => $nominal,
                    'status_pembayaran' => 'PAID',
                    'keterangan' => 'SIMULASI: ' . $uraian,
                ])->id;

                BukuKasUmum::create([
                    'tanggal_transaksi' => $tanggal,
                    'nomor_bukti' => $nomor,
                    'uraian' => $uraian,
                    'arus_kas' => 'DEBIT_MASUK',
                    'peran' => 'PENERIMAAN',
                    'kode_buku' => 1,
                    'akun_pendapatan_id' => $akunId,
                    'nominal' => $nominal,
                    'saldo_akhir' => 0,
                    'sumber_rekening_id' => $rek->id,
                    'referensi_penerimaan_id' => $piutangId, // tagihan ADA
                    'detail_mutasi_bank_id' => null,         // koran BELUM → 🟡
                ]);

                $dibuat++;
            }

            BukuKasUmum::recalculateRunningBalance($rek->id, 1);
        });

        $this->command?->info("✓ {$dibuat} baris BKU 🟡 'Belum cocok bank' dibuat (rekening {$rek->nomor_rekening}). Impor Contoh_Rekening_Koran_Simulasi.xlsx untuk menyandingkannya.");
    }
}
