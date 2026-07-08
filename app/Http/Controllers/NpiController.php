<?php

namespace App\Http\Controllers;

use App\Models\DokumenNpi;
use App\Models\User;

/**
 * Endpoint NPI yang masih dipakai alur Proses Tagihan terpadu: cetak PDF.
 * Pembuatan/pengajuan dan verifikasi NPI kini ditangani DokumenChainService
 * dan halaman Proses Tagihan.
 */
class NpiController extends Controller
{
    public function cetakPdf($npi_id)
    {
        require_once app_path('Helpers/TerbilangHelper.php');

        $npi = DokumenNpi::with([
            'spm.spp.tagihan',
            'bendaharaPenerimaan.profilable',
            'workflowInstance.approvals',
        ])->findOrFail($npi_id);
        $spm = $npi->spm;
        $spp = $spm?->spp;

        if (! $npi->nomor_npi) {
            $npi->nomor_npi = 'NPI-BLU/APTP-' . date('Y') . '/DRAFT';
        }

        if (! $npi->tanggal_npi) {
            $npi->tanggal_npi = now()->toDateString();
        }

        $jumlahUang = (float) ($spp?->nominal_spp ?? 0);
        $terbilang = terbilang_rupiah($jumlahUang);

        // Penanda tangan NPI = pejabat yang ditunjuk pada tagihan (snapshot
        // nama+NIP saat tagihan dibuat), bukan sembarang user pemilik role.
        $tagihan = $spp?->tagihan;

        $resolveUser = static function (?int $userId, string $role): ?User {
            if ($userId) {
                return User::with('profilable')->find($userId);
            }

            return User::role($role)->with('profilable')->first();
        };

        $bendaharaPengeluaran = $resolveUser($tagihan?->bendahara_pengeluaran_user_id, 'Bendahara Pengeluaran');
        $bendaharaPenerimaan = $npi->bendaharaPenerimaan
            ?: $resolveUser($tagihan?->bendahara_penerimaan_user_id, 'Bendahara Penerimaan');
        $ppk = $resolveUser($tagihan?->ppk_user_id, 'PPK');

        $penandatanganPengeluaran = $tagihan?->bendahara_pengeluaran_nama_snapshot
            ?: ($bendaharaPengeluaran?->profilable?->nama_lengkap ?? $bendaharaPengeluaran?->name ?? 'BENDAHARA PENGELUARAN');
        $nipPengeluaran = $tagihan?->bendahara_pengeluaran_nip_snapshot
            ?: ($bendaharaPengeluaran?->profilable?->nip ?: '-');
        $penandatanganPenerimaan = $tagihan?->bendahara_penerimaan_nama_snapshot
            ?: ($bendaharaPenerimaan?->profilable?->nama_lengkap ?? $bendaharaPenerimaan?->name ?? 'BENDAHARA PENERIMAAN');
        $nipPenerimaan = $tagihan?->bendahara_penerimaan_nip_snapshot
            ?: ($bendaharaPenerimaan?->profilable?->nip ?: '-');
        $penandatanganPpk = $tagihan?->ppk_nama_snapshot
            ?: ($ppk?->profilable?->nama_lengkap ?? $ppk?->name ?? 'PEJABAT PEMBUAT KOMITMEN');
        $nipPpk = $tagihan?->ppk_nip_snapshot
            ?: ($ppk?->profilable?->nip ?: '-');

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('npis.pdf', compact(
            'spp',
            'spm',
            'npi',
            'jumlahUang',
            'terbilang',
            'penandatanganPengeluaran',
            'nipPengeluaran',
            'penandatanganPenerimaan',
            'nipPenerimaan',
            'ppk',
            'penandatanganPpk',
            'nipPpk'
        ));
        $pdf->setPaper('a4', 'portrait');

        return $pdf->stream('NPI-BLU-' . str_replace('/', '-', $npi->nomor_npi) . '.pdf');
    }
}
