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

        $bendaharaPengeluaran = User::role('Bendahara Pengeluaran')->with('profilable')->first();
        $bendaharaPenerimaan = $npi->bendaharaPenerimaan ?: User::role('Bendahara Penerimaan')->with('profilable')->first();
        $ppk = User::role('PPK')->with('profilable')->first();

        $penandatanganPengeluaran = $bendaharaPengeluaran->name ?? 'BENDAHARA PENGELUARAN';
        $nipPengeluaran = $bendaharaPengeluaran?->pegawai?->nip ?: '-';
        $penandatanganPenerimaan = $bendaharaPenerimaan->name ?? 'BENDAHARA PENERIMAAN';
        $nipPenerimaan = $bendaharaPenerimaan?->pegawai?->nip ?: '-';
        $nipPpk = $ppk?->pegawai?->nip ?: '-';

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
            'nipPpk'
        ));
        $pdf->setPaper('a4', 'portrait');

        return $pdf->stream('NPI-BLU-' . str_replace('/', '-', $npi->nomor_npi) . '.pdf');
    }
}
