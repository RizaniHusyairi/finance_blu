<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MasterCoa;
use App\Models\MasterDipa;
use App\Models\DetailDipa;
use App\Models\RiwayatRevisiDipa;

class MasterDipaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // ============================================================
        // DIPA Utama TA 2026 (Revisi 0 - Awal)
        // ============================================================
        $dipaUtama = MasterDipa::updateOrCreate(
            ['nomor_dipa' => 'DIPA-054.01-0/2026'],
            [
                'tahun_anggaran'   => 2026,
                'total_pagu'       => 18500000000.00, // 18.5 Miliar
                'revisi_ke'        => 2,
                'tanggal_disahkan' => '2026-01-02',
            ]
        );

        // Detail pagu per COA untuk DIPA Utama
        $detailDipaUtama = [
            // Belanja Pegawai
            ['kode_mak' => '054.01.2896.BMA.001.001.A.511111.001', 'nilai' => 3200000000],   // Gaji Pokok
            ['kode_mak' => '054.01.2896.BMA.001.001.A.511119.001', 'nilai' => 5000000],       // Pembulatan Gaji
            ['kode_mak' => '054.01.2896.BMA.001.001.A.511121.001', 'nilai' => 450000000],     // Tunj. Suami/Istri
            ['kode_mak' => '054.01.2896.BMA.001.001.A.511151.001', 'nilai' => 1800000000],    // Tunj. Kinerja

            // Belanja Barang Operasional
            ['kode_mak' => '054.01.2896.BMA.001.002.A.521111.001', 'nilai' => 750000000],     // Perkantoran
            ['kode_mak' => '054.01.2896.BMA.001.002.A.521211.001', 'nilai' => 350000000],     // Bahan
            ['kode_mak' => '054.01.2896.BMA.001.002.A.522111.001', 'nilai' => 480000000],     // Listrik
            ['kode_mak' => '054.01.2896.BMA.001.002.A.522112.001', 'nilai' => 120000000],     // Telepon
            ['kode_mak' => '054.01.2896.BMA.001.002.A.522113.001', 'nilai' => 85000000],      // Air
            ['kode_mak' => '054.01.2896.BMA.001.002.B.522151.001', 'nilai' => 600000000],     // Jasa Profesi

            // Honor & Perjalanan Dinas
            ['kode_mak' => '054.01.2896.QMA.001.001.A.521213.001', 'nilai' => 550000000],     // Honor Output
            ['kode_mak' => '054.01.2896.QMA.001.001.A.524111.001', 'nilai' => 1200000000],    // Perjaldin Biasa
            ['kode_mak' => '054.01.2896.QMA.001.001.A.524113.001', 'nilai' => 250000000],     // Perjaldin Dalam Kota

            // Belanja Modal
            ['kode_mak' => '054.01.2896.BMA.001.003.A.531111.001', 'nilai' => 2500000000],    // Tanah
            ['kode_mak' => '054.01.2896.BMA.001.003.A.532111.001', 'nilai' => 1850000000],    // Peralatan & Mesin
            ['kode_mak' => '054.01.2896.BMA.001.003.A.533111.001', 'nilai' => 2100000000],    // Gedung & Bangunan

            // Belanja BLU
            ['kode_mak' => '054.01.2896.BMA.001.004.A.525111.001', 'nilai' => 950000000],     // Operasional BLU
            ['kode_mak' => '054.01.2896.BMA.001.004.A.525112.001', 'nilai' => 760000000],     // Jasa BLU
            ['kode_mak' => '054.01.2896.BMA.001.004.A.525113.001', 'nilai' => 500000000],     // Pemeliharaan BLU
        ];

        $totalPaguComputed = 0;
        foreach ($detailDipaUtama as $detail) {
            $coa = MasterCoa::where('kode_mak_lengkap', $detail['kode_mak'])->first();
            if ($coa) {
                DetailDipa::updateOrCreate(
                    [
                        'dipa_id' => $dipaUtama->id,
                        'coa_id'  => $coa->id,
                    ],
                    [
                        'nilai_pagu' => $detail['nilai'],
                    ]
                );
                $totalPaguComputed += $detail['nilai'];
            }
        }

        // Update total_pagu dengan nilai yang dihitung
        $dipaUtama->update(['total_pagu' => $totalPaguComputed]);

        // ============================================================
        // Riwayat Revisi DIPA
        // ============================================================

        // Revisi 0 - DIPA Awal
        RiwayatRevisiDipa::updateOrCreate(
            [
                'master_dipa_id' => $dipaUtama->id,
                'nomor_revisi'   => 0,
            ],
            [
                'tanggal_revisi'  => '2026-01-02',
                'pagu_sebelumnya' => 0,
                'pagu_baru'       => 17800000000,
                'keterangan'      => 'DIPA awal Tahun Anggaran 2026 disahkan oleh Dirjen Anggaran.',
            ]
        );

        // Revisi 1 - Penambahan pagu BLU
        RiwayatRevisiDipa::updateOrCreate(
            [
                'master_dipa_id' => $dipaUtama->id,
                'nomor_revisi'   => 1,
            ],
            [
                'tanggal_revisi'  => '2026-04-15',
                'pagu_sebelumnya' => 17800000000,
                'pagu_baru'       => 18200000000,
                'keterangan'      => 'Revisi 1: Penambahan pagu belanja BLU berdasarkan persetujuan Kemenkeu.',
            ]
        );

        // Revisi 2 - Penyesuaian belanja modal
        RiwayatRevisiDipa::updateOrCreate(
            [
                'master_dipa_id' => $dipaUtama->id,
                'nomor_revisi'   => 2,
            ],
            [
                'tanggal_revisi'  => '2026-07-10',
                'pagu_sebelumnya' => 18200000000,
                'pagu_baru'       => $totalPaguComputed,
                'keterangan'      => 'Revisi 2: Realokasi belanja modal dan penyesuaian pagu akun perjalanan dinas.',
            ]
        );

        // ============================================================
        // DIPA Tambahan (Satker lain / Program lain) - opsional
        // ============================================================
        $dipaTambahan = MasterDipa::updateOrCreate(
            ['nomor_dipa' => 'DIPA-054.02-0/2026'],
            [
                'tahun_anggaran'   => 2026,
                'total_pagu'       => 5200000000.00, // 5.2 Miliar
                'revisi_ke'        => 0,
                'tanggal_disahkan' => '2026-01-05',
            ]
        );

        // Revisi 0 untuk DIPA Tambahan
        RiwayatRevisiDipa::updateOrCreate(
            [
                'master_dipa_id' => $dipaTambahan->id,
                'nomor_revisi'   => 0,
            ],
            [
                'tanggal_revisi'  => '2026-01-05',
                'pagu_sebelumnya' => 0,
                'pagu_baru'       => 5200000000,
                'keterangan'      => 'DIPA awal program pendukung TA 2026.',
            ]
        );

        $this->command->info('✅ Master DIPA seeded: 2 DIPA, ' . count($detailDipaUtama) . ' detail items, 4 revision records');
    }
}
