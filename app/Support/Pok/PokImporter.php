<?php

namespace App\Support\Pok;

use App\Models\DetailDipa;
use App\Models\MasterCoa;
use App\Models\RiwayatRevisiDipa;
use Illuminate\Support\Str;

/**
 * Tulis baris detil hasil PokPdfParser menjadi COA + item revisi DIPA.
 * COA dibuat/dipakai-ulang berdasarkan kode MAK lengkap; item yang sudah
 * ada pada revisi tujuan dilewati sehingga impor aman diulang.
 */
class PokImporter
{
    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array{dibuat: int, dilewati: int}
     */
    public function importRows(array $rows, RiwayatRevisiDipa $revision): array
    {
        $dibuat = 0;
        $dilewati = 0;

        foreach ($rows as $row) {
            $coa = MasterCoa::withTrashed()->firstOrCreate(
                ['kode_mak_lengkap' => $row['kode_mak_lengkap']],
                [
                    'kd_program' => $row['kd_program'],
                    'kd_giat' => $row['kd_giat'],
                    'kd_output' => $row['kd_output'],
                    'kd_suboutput' => $row['kd_suboutput'],
                    'kd_komponen' => $row['kd_komponen'],
                    'kd_subkomponen' => $row['kd_subkomponen'],
                    'kd_akun' => $row['kd_akun'],
                    'kd_item' => $row['kd_item'],
                    'nama_akun' => Str::limit($row['nama'], 145, '…'),
                    'jenis_akun' => substr((string) $row['kd_akun'], 0, 3),
                    'sumber_dana' => $row['sumber_dana'],
                    'status_aktif' => true,
                ]
            );

            if ($coa->trashed()) {
                $coa->restore();
            }

            $sudahAda = DetailDipa::where('dipa_revision_id', $revision->id)
                ->where('coa_id', $coa->id)
                ->exists();

            if ($sudahAda) {
                $dilewati++;

                continue;
            }

            DetailDipa::create([
                'dipa_revision_id' => $revision->id,
                'coa_id' => $coa->id,
                'nilai_pagu' => $row['jumlah'],
                'volume' => $row['volume'],
                'satuan' => $row['satuan'],
                'harga_satuan' => $row['harga_satuan'],
                'status_aktif' => true,
                'blokir' => false,
            ]);
            $dibuat++;
        }

        return ['dibuat' => $dibuat, 'dilewati' => $dilewati];
    }
}
