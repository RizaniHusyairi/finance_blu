<?php

namespace Database\Seeders;

use App\Models\DetailDipa;
use App\Models\MasterCoa;
use App\Models\MasterDipa;
use App\Models\RiwayatRevisiDipa;
use Illuminate\Database\Seeder;

class MasterDipaSeeder extends Seeder
{
    public function run(): void
    {
        $coas = [
            [
                'kode_mak_lengkap' => '524111.001',
                'kd_akun' => '524111',
                'kd_item' => '001',
                'nama_akun' => 'Belanja Perjalanan Dinas Biasa',
                'jenis_akun' => 'BELANJA',
            ],
            [
                'kode_mak_lengkap' => '521219.001',
                'kd_akun' => '521219',
                'kd_item' => '001',
                'nama_akun' => 'Belanja Barang Non Operasional Lainnya',
                'jenis_akun' => 'BELANJA',
            ],
            [
                'kode_mak_lengkap' => '522151.001',
                'kd_akun' => '522151',
                'kd_item' => '001',
                'nama_akun' => 'Belanja Jasa Profesi',
                'jenis_akun' => 'BELANJA',
            ],
        ];

        $coaModels = collect($coas)->map(function (array $coa) {
            return MasterCoa::updateOrCreate(
                ['kode_mak_lengkap' => $coa['kode_mak_lengkap']],
                array_merge([
                    'kd_program' => 'WA',
                    'kd_giat' => '001',
                    'kd_output' => '001',
                    'kd_suboutput' => '001',
                    'kd_komponen' => '051',
                    'kd_subkomponen' => 'A',
                    'status_aktif' => true,
                ], $coa)
            );
        })->keyBy('kode_mak_lengkap');

        $dipa = MasterDipa::updateOrCreate(
            ['nomor_dipa' => 'DIPA-018.01.2.123456/2026'],
            [
                'tahun_anggaran' => 2026,
                'tanggal_disahkan' => '2026-01-02',
                'revisi_aktif_ke' => 1,
                'status_aktif' => true,
            ]
        );

        $revision = RiwayatRevisiDipa::updateOrCreate(
            [
                'master_dipa_id' => $dipa->id,
                'nomor_revisi' => 1,
            ],
            [
                'tanggal_revisi' => '2026-01-15',
                'total_pagu' => 650000000,
                'file_dokumen_dipa' => 'seeders/dipa/dipa-2026-revisi-1.pdf',
                'keterangan' => 'Data awal DIPA revisi aktif untuk pengujian modul tagihan.',
                'is_active' => true,
            ]
        );

        $items = [
            ['kode_mak_lengkap' => '524111.001', 'nilai_pagu' => 150000000],
            ['kode_mak_lengkap' => '521219.001', 'nilai_pagu' => 200000000],
            ['kode_mak_lengkap' => '522151.001', 'nilai_pagu' => 300000000],
        ];

        foreach ($items as $item) {
            $coa = $coaModels->get($item['kode_mak_lengkap']);

            if (! $coa) {
                continue;
            }

            DetailDipa::updateOrCreate(
                [
                    'dipa_revision_id' => $revision->id,
                    'coa_id' => $coa->id,
                ],
                [
                    'nilai_pagu' => $item['nilai_pagu'],
                    'status_aktif' => true,
                ]
            );
        }

        $revision->update([
            'total_pagu' => DetailDipa::where('dipa_revision_id', $revision->id)->sum('nilai_pagu'),
        ]);
    }
}