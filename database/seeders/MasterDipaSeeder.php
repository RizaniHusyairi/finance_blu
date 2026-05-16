<?php

namespace Database\Seeders;

use App\Models\DetailDipa;
use App\Models\MasterCoa;
use App\Models\MasterDipa;
use App\Models\RiwayatRevisiDipa;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MasterDipaSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $coaMap = $this->resolveCoaMap();

            $this->seedDipaWithActiveRevision(
                nomorDipa: 'DIPA-025.01.2.400001/2025',
                tahunAnggaran: 2025,
                tanggalDisahkan: '2025-01-02',
                totalPagu: 3150000000,
                keterangan: 'DIPA tahun anggaran 2025 untuk operasional BLU.',
                revisionDate: '2025-01-02',
                items: [
                    ['kode' => 'GA.4645.CBE.001.054.A.537113.00001', 'nilai_pagu' => 850000000, 'status_aktif' => true],
                    ['kode' => 'GA.4645.CBE.001.054.A.537113.00002', 'nilai_pagu' => 1200000000, 'status_aktif' => true],
                    ['kode' => 'GA.4646.CAD.001.051.A.537112.00001', 'nilai_pagu' => 600000000, 'status_aktif' => true],
                    ['kode' => 'GA.4646.CBE.002.052.A.525112.00001', 'nilai_pagu' => 500000000, 'status_aktif' => true],
                ],
                coaMap: $coaMap,
            );

            $this->seedDipaWithActiveRevision(
                nomorDipa: 'DIPA-025.01.2.400001/2026',
                tahunAnggaran: 2026,
                tanggalDisahkan: '2026-01-03',
                totalPagu: 3825000000,
                keterangan: 'DIPA tahun anggaran 2026 untuk layanan dan dukungan operasional BLU.',
                revisionDate: '2026-01-03',
                items: [
                    ['kode' => 'WA.4611.EBA.962.052.E.525112.00005', 'nilai_pagu' => 950000000, 'status_aktif' => true],
                    ['kode' => 'WA.4611.EBA.994.002.N.525111.00001', 'nilai_pagu' => 1450000000, 'status_aktif' => true],
                    ['kode' => 'WA.4611.EBD.001.056.A.525112.00001', 'nilai_pagu' => 725000000, 'status_aktif' => true],
                    ['kode' => 'WA.4611.EBD.001.056.B.525112.00002', 'nilai_pagu' => 450000000, 'status_aktif' => true],
                    ['kode' => 'WA.4613.EBA.960.053.A.525115.00003', 'nilai_pagu' => 250000000, 'status_aktif' => true],
                ],
                coaMap: $coaMap,
            );
        });
    }

    private function resolveCoaMap(): array
    {
        $requiredCodes = [
            'GA.4645.CBE.001.054.A.537113.00001',
            'GA.4645.CBE.001.054.A.537113.00002',
            'GA.4646.CAD.001.051.A.537112.00001',
            'GA.4646.CBE.002.052.A.525112.00001',
            'WA.4611.EBA.962.052.E.525112.00005',
            'WA.4611.EBA.994.002.N.525111.00001',
            'WA.4611.EBD.001.056.A.525112.00001',
            'WA.4611.EBD.001.056.B.525112.00002',
            'WA.4613.EBA.960.053.A.525115.00003',
        ];

        $coas = MasterCoa::query()
            ->whereIn('kode_mak_lengkap', $requiredCodes)
            ->get()
            ->keyBy('kode_mak_lengkap');

        foreach ($requiredCodes as $code) {
            if (! $coas->has($code)) {
                throw new \RuntimeException('COA wajib untuk MasterDipaSeeder tidak ditemukan: ' . $code);
            }
        }

        return $coas->map(fn ($coa) => $coa->id)->all();
    }

    private function seedDipaWithActiveRevision(
        string $nomorDipa,
        int $tahunAnggaran,
        string $tanggalDisahkan,
        float $totalPagu,
        string $keterangan,
        string $revisionDate,
        array $items,
        array $coaMap
    ): void {
        $dipa = MasterDipa::updateOrCreate(
            ['nomor_dipa' => $nomorDipa],
            [
                'tahun_anggaran' => $tahunAnggaran,
                'tanggal_disahkan' => $tanggalDisahkan,
                'revisi_aktif_ke' => 0,
                'status_aktif' => true,
            ]
        );

        RiwayatRevisiDipa::where('master_dipa_id', $dipa->id)->update(['is_active' => false]);

        $revision = RiwayatRevisiDipa::updateOrCreate(
            [
                'master_dipa_id' => $dipa->id,
                'nomor_revisi' => 0,
            ],
            [
                'tanggal_revisi' => $revisionDate,
                'total_pagu' => $totalPagu,
                'file_dokumen_dipa' => null,
                'keterangan' => $keterangan,
                'is_active' => true,
            ]
        );

        $dipa->update(['revisi_aktif_ke' => 0]);

        foreach ($items as $item) {
            DetailDipa::updateOrCreate(
                [
                    'dipa_revision_id' => $revision->id,
                    'coa_id' => $coaMap[$item['kode']],
                ],
                [
                    'nilai_pagu' => $item['nilai_pagu'],
                    'status_aktif' => $item['status_aktif'],
                ]
            );
        }
    }
}
